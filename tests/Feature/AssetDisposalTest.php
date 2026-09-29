<?php

namespace Tests\Feature;

use App\Filament\Resources\AssetDisposals\AssetDisposalResource;
use App\Filament\Resources\AssetDisposals\Pages\CreateAssetDisposal;
use App\Filament\Resources\AssetDisposals\Pages\ViewAssetDisposal;
use App\Models\Asset;
use App\Models\AssetDisposal;
use App\Models\AssetSet;
use App\Models\MaintenanceSchedule;
use App\Models\User;
use App\Services\AssetDisposalService;
use Filament\Facades\Filament;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class AssetDisposalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    private function data(Asset $asset, array $extra = []): array
    {
        return [...[
            'asset_ids' => [$asset->id],
            'reason_type' => 'major_damage',
            'reason' => 'Biaya perbaikan melebihi nilai manfaat aset.',
            'disposal_date' => today()->toDateString(),
        ], ...$extra];
    }

    public function test_guru_can_submit_and_admin_can_approve_disposal_from_ui(): void
    {
        $guru = User::factory()->create(['name' => 'Guru Pengusul', 'role' => 'guru']);
        $admin = User::factory()->create(['name' => 'Admin Pemeriksa', 'role' => 'admin']);
        $package = AssetSet::create(['name' => 'Paket Lama', 'is_active' => true]);
        $asset = Asset::factory()->create([
            'name' => 'Router Lama',
            'status' => 'available',
            'condition' => 'major_damage',
            'asset_set_id' => $package->id,
            'set_role' => 'device',
        ]);
        MaintenanceSchedule::factory()->create(['asset_id' => $asset->id, 'is_active' => true]);

        $this->actingAs($guru);
        Livewire::test(CreateAssetDisposal::class)
            ->fillForm($this->data($asset))
            ->call('create')
            ->assertHasNoFormErrors();

        $disposal = AssetDisposal::query()->with('items')->sole();
        $this->assertSame('pending', $disposal->status);
        $this->assertSame('available', $asset->fresh()->status);
        $this->assertSame('Router Lama', $disposal->items->sole()->asset_name);

        $this->actingAs($admin);
        Livewire::test(ViewAssetDisposal::class, ['record' => $disposal->id])
            ->callAction('approve', ['notes' => 'Disetujui setelah pemeriksaan fisik.'])
            ->assertHasNoActionErrors();

        $this->assertSame('approved', $disposal->fresh()->status);
        $this->assertSame($admin->id, $disposal->fresh()->reviewed_by);
        $this->assertSame('retired', $asset->fresh()->status);
        $this->assertNull($asset->fresh()->asset_set_id);
        $this->assertFalse($package->fresh()->is_active);
        $this->assertFalse(MaintenanceSchedule::query()->sole()->is_active);
        $this->assertDatabaseHas('asset_histories', [
            'asset_id' => $asset->id,
            'action' => 'retirement',
        ]);

        $this->get(route('asset-disposals.document', $disposal))
            ->assertOk()
            ->assertSee('BERITA ACARA PENGHAPUSAN / PENSIUN ASET')
            ->assertSee($asset->asset_code)
            ->assertSee('Admin Pemeriksa');
    }

    public function test_rejection_keeps_asset_active_and_document_unavailable(): void
    {
        $guru = User::factory()->create(['role' => 'guru']);
        $admin = User::factory()->create(['role' => 'admin']);
        $asset = Asset::factory()->create(['status' => 'available']);
        $disposal = app(AssetDisposalService::class)->submit($this->data($asset), $guru);

        app(AssetDisposalService::class)->reject($disposal, $admin, 'Aset masih layak digunakan.');

        $this->assertSame('rejected', $disposal->fresh()->status);
        $this->assertSame('available', $asset->fresh()->status);
        $this->actingAs($admin)->get(route('asset-disposals.document', $disposal))->assertNotFound();
    }

    public function test_guru_cannot_approve_disposal(): void
    {
        $guru = User::factory()->create(['role' => 'guru']);
        $asset = Asset::factory()->create(['status' => 'available']);
        $disposal = app(AssetDisposalService::class)->submit($this->data($asset), $guru);

        $this->expectException(AuthorizationException::class);
        app(AssetDisposalService::class)->approve($disposal, $guru);
    }

    public function test_duplicate_pending_and_borrowed_assets_are_rejected(): void
    {
        $guru = User::factory()->create(['role' => 'guru']);
        $asset = Asset::factory()->create(['status' => 'available']);
        app(AssetDisposalService::class)->submit($this->data($asset), $guru);

        try {
            app(AssetDisposalService::class)->submit($this->data($asset), $guru);
            $this->fail('Duplicate pending disposal was created.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('asset_ids', $exception->errors());
        }

        $borrowed = Asset::factory()->create(['status' => 'borrowed']);
        try {
            app(AssetDisposalService::class)->submit($this->data($borrowed), $guru);
            $this->fail('Borrowed asset was submitted for disposal.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('asset_ids', $exception->errors());
        }

        $this->assertDatabaseCount('asset_disposals', 1);
    }

    public function test_disposal_records_are_immutable_and_print_requires_login(): void
    {
        $disposal = AssetDisposal::factory()->create(['status' => 'approved', 'reviewed_at' => now()]);

        $this->assertFalse(AssetDisposalResource::canEdit($disposal));
        $this->assertFalse(AssetDisposalResource::canDelete($disposal));
        $this->get(route('asset-disposals.document', $disposal))->assertRedirect();
    }
}
