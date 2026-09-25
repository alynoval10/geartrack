<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\Loan;
use App\Models\MaintenanceReport;
use App\Models\StockTake;
use App\Models\User;
use App\Services\InventoryReportService;
use App\Services\SpreadsheetService;
use Database\Factories\LocationFactory;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use OpenSpout\Reader\XLSX\Reader;
use Tests\TestCase;
use ZipArchive;

class InventoryReportTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['app.env' => 'local']);
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_report_page_and_exports_require_login(): void
    {
        $this->get(route('filament.admin.pages.reports'))->assertRedirect(route('filament.admin.auth.login'));
        $this->get(route('reports.export', ['type' => 'assets', 'format' => 'xlsx']))->assertRedirect(route('filament.admin.auth.login'));
        $this->actingAs(User::factory()->create())->get(route('filament.admin.pages.reports'))->assertSee('Unduh Excel');
    }

    public function test_asset_report_filters_and_print_escaping(): void
    {
        $this->actingAs(User::factory()->create());
        $location = LocationFactory::new()->create();
        Asset::factory()->create(['name' => '<script>alert(1)</script>', 'location_id' => $location->id, 'condition' => 'good', 'acquisition_date' => '2026-01-10', 'purchase_price' => 1250000]);
        Asset::factory()->create(['name' => 'Excluded damaged', 'location_id' => $location->id, 'condition' => 'major_damage']);
        $this->get(route('reports.export', ['type' => 'assets', 'format' => 'print', 'location_id' => $location->id, 'condition' => 'good', 'date_to' => '2026-12-31']))
            ->assertOk()->assertSee('<script>alert(1)</script>')->assertDontSee('<script>alert(1)</script>', false)
            ->assertDontSee('Excluded damaged')->assertSee('1.250.000,00');
    }

    public function test_overdue_report_excludes_returned_items_and_today_due_loans(): void
    {
        $this->freezeTime();
        $loan = Loan::factory()->create(['due_date' => today()->subDays(2), 'status' => 'open']);
        $loan->items()->create(['asset_code' => 'LATE', 'asset_name' => 'Late laptop', 'condition_before' => 'good', 'previous_status' => 'available']);
        $loan->items()->create(['asset_code' => 'RETURNED', 'asset_name' => 'Returned laptop', 'returned_at' => now(), 'condition_before' => 'good', 'previous_status' => 'available']);
        $todayLoan = Loan::factory()->create(['due_date' => today()]);
        $todayLoan->items()->create(['asset_code' => 'TODAY', 'asset_name' => 'Not overdue', 'condition_before' => 'good', 'previous_status' => 'available']);
        $report = app(InventoryReportService::class)->generate(['type' => 'overdue']);
        $this->assertCount(1, $report['rows']);
        $this->assertSame('LATE', $report['rows'][0][3]);
        $this->assertSame(2, $report['rows'][0][7]);
    }

    public function test_stock_take_preserves_snapshot_and_pending_rows(): void
    {
        $stockTake = StockTake::factory()->create();
        $stockTake->items()->create(['asset_code' => 'OLD-01', 'asset_name' => 'Snapshot asset', 'expected_location_name' => 'Original lab', 'original_status' => 'available', 'result' => 'pending']);
        $report = app(InventoryReportService::class)->generate(['type' => 'stock_take', 'stock_take_id' => $stockTake->id]);
        $this->assertSame('Original lab', $report['rows'][0][3]);
        $this->assertSame('Belum Diperiksa', $report['rows'][0][5]);
    }

    public function test_maintenance_costs_are_summed_without_duplicate_report_rows(): void
    {
        $report = MaintenanceReport::factory()->create();
        $report->entries()->create(['action' => 'note', 'notes' => 'Part', 'cost' => 125000]);
        $report->entries()->create(['action' => 'note', 'notes' => 'Service', 'cost' => 50000]);
        $data = app(InventoryReportService::class)->generate(['type' => 'maintenance']);
        $this->assertCount(1, $data['rows']);
        $this->assertSame(175000.0, $data['rows'][0][8]);
    }

    public function test_xlsx_preserves_leading_zero_and_does_not_execute_text_as_formula(): void
    {
        $path = app(SpreadsheetService::class)->write(['Data' => [['Code', 'Text', 'Amount'], ['000123', '=1+1', 12.5]]]);
        try {
            $reader = new Reader;
            $reader->open($path);
            $rows = [];
            foreach ($reader->getSheetIterator() as $sheet) {
                foreach ($sheet->getRowIterator() as $row) {
                    $rows[] = $row->toArray();
                }
            }
            $reader->close();
            $this->assertSame(['000123', '=1+1', 12.5], $rows[1]);
            $zip = new ZipArchive;
            $zip->open($path);
            $this->assertStringNotContainsString('<f>', $zip->getFromName('xl/worksheets/sheet1.xml'));
            $zip->close();
        } finally {
            unlink($path);
        }
    }

    public function test_export_download_and_invalid_filters(): void
    {
        $this->actingAs(User::factory()->create());
        $response = $this->get(route('reports.export', ['type' => 'assets', 'format' => 'xlsx']));
        $response->assertDownload();
        unlink($response->baseResponse->getFile()->getPathname());
        $this->getJson(route('reports.export', ['type' => 'unknown', 'format' => 'print']))->assertUnprocessable()->assertJsonValidationErrors('type');
        $this->getJson(route('reports.export', ['type' => 'stock_take', 'format' => 'print']))->assertJsonValidationErrors('stock_take_id');
        $this->getJson(route('reports.export', ['type' => 'assets', 'format' => 'print', 'date_from' => '2026-12-01', 'date_to' => '2026-01-01']))->assertJsonValidationErrors('date_to');
    }
}
