<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\User;
use App\Services\AssetImportService;
use App\Services\SpreadsheetService;
use Database\Factories\CategoryFactory;
use Database\Factories\LocationFactory;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Writer;
use Tests\TestCase;
use ZipArchive;

class AssetImportTest extends TestCase
{
    use LazilyRefreshDatabase;

    private array $files = [];

    protected function setUp(): void
    {
        parent::setUp();
        config(['app.env' => 'local']);
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    protected function tearDown(): void
    {
        foreach ($this->files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        parent::tearDown();
    }

    private function row(array $overrides = []): array
    {
        return array_replace(array_fill_keys(AssetImportService::HEADERS, ''), [
            'kode_aset' => '000001', 'nama_aset' => 'Laptop baru', 'kode_kategori' => 'CAT-TEST',
            'nomor_seri' => '000099', 'kondisi' => 'Baik', 'status' => 'Tersedia', 'harga_perolehan' => '2500000.50',
        ], $overrides);
    }

    private function workbook(array $rows, ?array $headers = null): string
    {
        $file = app(SpreadsheetService::class)->write(['Aset' => [$headers ?? AssetImportService::HEADERS, ...array_map('array_values', $rows)]]);
        $this->files[] = $file;

        return $file;
    }

    private function upload(string $file): UploadedFile
    {
        return new UploadedFile($file, 'aset.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
    }

    private function prepare(): void
    {
        $this->actingAs(User::factory()->create());
        CategoryFactory::new()->create(['code' => 'CAT-TEST', 'is_active' => true]);
    }

    public function test_preview_does_not_write_and_confirmation_imports_with_history_and_qr(): void
    {
        $this->prepare();
        $file = $this->workbook([$this->row()]);
        $this->post(route('asset-import.preview'), ['file' => $this->upload($file)])->assertRedirect(route('filament.admin.pages.import-assets'));
        $this->assertDatabaseCount('assets', 0);
        $draft = session('asset_import');
        $this->assertSame([], $draft['errors']);
        $this->get(route('filament.admin.pages.import-assets'))->assertSee('000001')->assertSee('Simpan 1 Aset');

        $this->post(route('asset-import.store'), ['token' => $draft['token'], 'confirm' => 1, 'rows' => [['name' => 'Tampered']]])->assertRedirect(route('filament.admin.pages.import-assets'));
        $asset = Asset::sole();
        $this->assertSame('000001', $asset->asset_code);
        $this->assertSame('000099', $asset->serial_number);
        $this->assertSame('Laptop baru', $asset->name);
        $this->assertSame('2500000.50', $asset->purchase_price);
        $this->assertNotEmpty($asset->qr_token);
        $this->assertDatabaseHas('asset_histories', ['asset_id' => $asset->id, 'user_id' => auth()->id(), 'action' => 'created']);
        $this->assertNull(session('asset_import'));
        $this->post(route('asset-import.store'), ['token' => $draft['token'], 'confirm' => 1])->assertSessionHasErrors('file');
        $this->assertDatabaseCount('assets', 1);
    }

    public function test_duplicates_and_invalid_master_data_block_entire_import(): void
    {
        $this->prepare();
        Asset::factory()->create(['asset_code' => 'EXISTING', 'serial_number' => 'old-serial']);
        $file = $this->workbook([
            $this->row(['kode_aset' => 'existing', 'nomor_seri' => 'new-one']),
            $this->row(['kode_aset' => 'new-two', 'nomor_seri' => 'OLD-SERIAL', 'kode_kategori' => 'UNKNOWN']),
            $this->row(['kode_aset' => 'new-two', 'nomor_seri' => 'new-three']),
        ]);
        $this->post(route('asset-import.preview'), ['file' => $this->upload($file)]);
        $draft = session('asset_import');
        $this->assertCount(3, $draft['errors']);
        $this->post(route('asset-import.store'), ['token' => $draft['token'], 'confirm' => 1])->assertSessionHasErrors('file');
        $this->assertDatabaseCount('assets', 1);
    }

    public function test_revalidates_changes_since_preview_and_rolls_back_all_rows(): void
    {
        $this->prepare();
        $rows = [2 => $this->row(), 3 => $this->row(['kode_aset' => '000002', 'nomor_seri' => '000088'])];
        $file = $this->workbook($rows);
        $this->post(route('asset-import.preview'), ['file' => $this->upload($file)]);
        $draft = session('asset_import');
        Asset::factory()->create(['asset_code' => '000002']);
        $this->post(route('asset-import.store'), ['token' => $draft['token'], 'confirm' => 1])->assertSessionHasErrors('file');
        $this->assertDatabaseCount('assets', 1);
        $this->assertDatabaseMissing('assets', ['asset_code' => '000001']);
    }

    public function test_preview_rejects_invalid_dates_prices_and_borrowed_status(): void
    {
        $this->prepare();
        $file = $this->workbook([$this->row(['tanggal_pengadaan' => '2026-02-30', 'harga_perolehan' => '-2', 'status' => 'borrowed'])]);
        $draft = app(AssetImportService::class)->preview($file);
        $messages = implode(' ', $draft['errors'][2]);
        $this->assertStringContainsString('status', $messages);
        $this->assertStringContainsString('acquisition date', $messages);
        $this->assertStringContainsString('purchase price', $messages);
    }

    public function test_cancel_and_expired_preview_cannot_save(): void
    {
        $this->prepare();
        $file = $this->workbook([$this->row()]);
        $this->post(route('asset-import.preview'), ['file' => $this->upload($file)]);
        $token = session('asset_import.token');
        $this->post(route('asset-import.store'), ['token' => $token])->assertSessionHasErrors('file');
        $this->travel(31)->minutes();
        $this->post(route('asset-import.store'), ['token' => $token, 'confirm' => 1])->assertSessionHasErrors('file');
        $this->post(route('asset-import.cancel'))->assertRedirect(route('filament.admin.pages.import-assets'));
        $this->assertNull(session('asset_import'));
        $this->assertDatabaseCount('assets', 0);
    }

    public function test_guest_cannot_download_preview_or_import(): void
    {
        $this->get(route('asset-import.template'))->assertRedirect(route('filament.admin.auth.login'));
        $this->post(route('asset-import.preview'))->assertRedirect(route('filament.admin.auth.login'));
        $this->post(route('asset-import.store'))->assertRedirect(route('filament.admin.auth.login'));
    }

    public function test_template_contains_active_master_codes_and_expected_headers(): void
    {
        $this->prepare();
        LocationFactory::new()->create(['code' => 'LAB-A', 'is_active' => true]);
        $sheets = app(AssetImportService::class)->template();
        $this->assertSame(AssetImportService::HEADERS, $sheets['Aset'][0]);
        $this->assertSame('CAT-TEST', $sheets['Kategori'][1][0]);
        $this->assertSame('LAB-A', $sheets['Lokasi'][1][0]);
        $response = $this->get(route('asset-import.template'))->assertDownload('template-impor-aset-geartrack.xlsx');
        $this->files[] = $response->baseResponse->getFile()->getPathname();
    }

    public function test_wrong_headers_are_rejected(): void
    {
        $this->prepare();
        $file = $this->workbook([$this->row()], ['wrong']);
        $this->post(route('asset-import.preview'), ['file' => $this->upload($file)])->assertSessionHasErrors('file');
        $this->assertNull(session('asset_import'));
    }

    public function test_excel_formula_is_rejected(): void
    {
        $this->prepare();
        $file = $this->workbook([$this->row()]);
        $zip = new ZipArchive;
        $zip->open($file);
        $xml = $zip->getFromName('xl/worksheets/sheet1.xml');
        $xml = preg_replace('/<c r="B2".*?<\/c>/s', '<c r="B2"><f>1+1</f><v>2</v></c>', $xml, 1, $replaced);
        $this->assertSame(1, $replaced);
        $zip->addFromString('xl/worksheets/sheet1.xml', $xml);
        $zip->close();
        $this->post(route('asset-import.preview'), ['file' => $this->upload($file)])->assertSessionHasErrors('file');
    }

    public function test_too_many_rows_and_invalid_archives_are_rejected(): void
    {
        $this->prepare();
        $file = $this->workbook(array_fill(0, 501, $this->row()));
        $this->post(route('asset-import.preview'), ['file' => $this->upload($file)])->assertSessionHasErrors('file');
        $this->post(route('asset-import.preview'), ['file' => UploadedFile::fake()->createWithContent('bad.xlsx', 'not a workbook')])->assertSessionHasErrors('file');
    }

    public function test_other_user_and_stale_token_cannot_confirm_preview(): void
    {
        $this->prepare();
        $file = $this->workbook([$this->row()]);
        $this->post(route('asset-import.preview'), ['file' => $this->upload($file)]);
        $draft = session('asset_import');
        $this->post(route('asset-import.store'), ['token' => 'wrong', 'confirm' => 1])->assertSessionHasErrors('file');
        $this->actingAs(User::factory()->create())->withSession(['asset_import' => $draft])
            ->post(route('asset-import.store'), ['token' => $draft['token'], 'confirm' => 1])->assertSessionHasErrors('file');
        $this->assertDatabaseCount('assets', 0);
    }

    public function test_database_failure_rolls_back_assets_and_their_history(): void
    {
        $this->prepare();
        $file = $this->workbook([$this->row(), $this->row(['kode_aset' => '000002', 'nomor_seri' => '000088'])]);
        $this->post(route('asset-import.preview'), ['file' => $this->upload($file)]);
        $token = session('asset_import.token');
        Asset::created(function (Asset $asset): void {
            if ($asset->asset_code === '000002') {
                throw new \RuntimeException('Simulated storage failure');
            }
        });

        $this->post(route('asset-import.store'), ['token' => $token, 'confirm' => 1])->assertSessionHasErrors('file');
        $this->assertDatabaseCount('assets', 0);
        $this->assertDatabaseCount('asset_histories', 0);
    }

    public function test_excel_dates_are_read_and_preview_escapes_asset_names(): void
    {
        $this->prepare();
        $name = '<script>alert(1)</script>';
        $file = tempnam(sys_get_temp_dir(), 'geartrack-date-');
        $this->files[] = $file;
        $writer = new Writer;
        $writer->openToFile($file);
        $writer->getCurrentSheet()->setName('Aset');
        $writer->addRow(Row::fromValues(AssetImportService::HEADERS));
        $writer->addRow(Row::fromValuesWithStyles(
            array_values($this->row(['nama_aset' => $name, 'tanggal_pengadaan' => new \DateTimeImmutable('2026-09-20')])),
            null,
            [9 => (new Style)->setFormat('yyyy-mm-dd')],
        ));
        $writer->close();
        $this->post(route('asset-import.preview'), ['file' => $this->upload($file)]);
        $draft = session('asset_import');
        $this->assertSame([], $draft['errors']);
        $this->assertSame('2026-09-20', $draft['rows'][2]['tanggal_pengadaan']);
        $this->get(route('filament.admin.pages.import-assets'))->assertSee($name)->assertDontSee($name, false);
    }
}
