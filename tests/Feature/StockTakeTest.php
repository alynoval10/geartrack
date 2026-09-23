<?php

namespace Tests\Feature;

use App\Filament\Resources\Assets\AssetResource;
use App\Filament\Resources\Assets\Pages\ViewAsset;
use App\Filament\Resources\StockTakes\Pages\CreateStockTake;
use App\Filament\Resources\StockTakes\Pages\ViewStockTake;
use App\Filament\Resources\StockTakes\RelationManagers\ItemsRelationManager;
use App\Filament\Resources\StockTakes\StockTakeResource;
use App\Models\Asset;
use App\Models\StockTake;
use App\Models\User;
use App\Services\StockTakeService;
use Database\Factories\LocationFactory;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class StockTakeTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['app.env' => 'local']);
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    private function operator(): User
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        return $user;
    }

    public function test_creating_session_snapshots_only_assets_in_selected_location(): void
    {
        $this->operator();
        $location = LocationFactory::new()->create(['name' => 'Lab Satu']);
        $asset = Asset::factory()->create(['location_id' => $location->id]);
        Asset::factory()->create();

        Livewire::test(CreateStockTake::class)->fillForm([
            'name' => 'Opname September', 'location_id' => $location->id,
        ])->call('create')->assertHasNoFormErrors();

        $session = StockTake::sole();
        $this->assertDatabaseCount('stock_take_items', 1);
        $this->assertDatabaseHas('stock_take_items', [
            'stock_take_id' => $session->id, 'asset_id' => $asset->id,
            'result' => 'pending', 'expected_location_name' => 'Lab Satu',
        ]);
        Asset::factory()->create(['location_id' => $location->id]);
        $this->assertSame(1, $session->items()->count());
    }

    public function test_empty_scope_does_not_create_session(): void
    {
        $this->operator();

        Livewire::test(CreateStockTake::class)->fillForm(['name' => 'Kosong'])
            ->call('create')->assertHasFormErrors(['location_id'])
            ->assertSee('Tidak ada aset untuk diperiksa dalam cakupan ini.');

        $this->assertDatabaseCount('stock_takes', 0);
    }

    public function test_asset_action_records_found_and_preserves_scan_session(): void
    {
        $user = $this->operator();
        $asset = Asset::factory()->create();
        $session = app(StockTakeService::class)->start(['name' => 'Sesi'], $user);

        Livewire::test(ViewAsset::class, ['record' => $asset->id])
            ->callAction('stockTake', data: ['stock_take_id' => $session->id, 'result' => 'found'])
            ->assertHasNoActionErrors()->assertSet('stockTakeId', $session->id);

        $this->assertDatabaseHas('stock_take_items', [
            'asset_id' => $asset->id, 'result' => 'found', 'checked_by' => $user->id,
        ]);
        $this->assertDatabaseHas('asset_histories', ['asset_id' => $asset->id, 'action' => 'stock_take', 'new_value' => 'Ditemukan']);
    }

    public function test_missing_requires_note_and_updates_asset_status(): void
    {
        $user = $this->operator();
        $asset = Asset::factory()->create();
        $session = app(StockTakeService::class)->start(['name' => 'Sesi'], $user);
        $item = $session->items()->sole();

        Livewire::test(ItemsRelationManager::class, ['ownerRecord' => $session, 'pageClass' => ViewStockTake::class])
            ->callTableAction('recordResult', $item, data: ['result' => 'missing'])
            ->assertHasTableActionErrors(['notes' => 'required']);

        $this->assertDatabaseHas('assets', ['id' => $asset->id, 'status' => 'available']);

        Livewire::test(ItemsRelationManager::class, ['ownerRecord' => $session, 'pageClass' => ViewStockTake::class])
            ->callTableAction('recordResult', $item, data: ['result' => 'missing', 'notes' => 'Tidak ditemukan setelah pengecekan lemari.'])
            ->assertHasNoTableActionErrors();

        $this->assertDatabaseHas('assets', ['id' => $asset->id, 'status' => 'lost']);
        $this->get(route('asset.qr.show', $asset->qr_token))->assertSee('Status Aset')->assertSee('Hilang');
    }

    public function test_moved_updates_only_the_checked_asset_location(): void
    {
        $user = $this->operator();
        $original = LocationFactory::new()->create();
        $actual = LocationFactory::new()->create();
        $asset = Asset::factory()->create(['location_id' => $original->id]);
        $other = Asset::factory()->create(['location_id' => $original->id]);
        $service = app(StockTakeService::class);
        $session = $service->start(['name' => 'Sesi'], $user);

        $service->record($session, $session->items()->where('asset_id', $asset->id)->sole(), [
            'result' => 'moved', 'observed_location_id' => $actual->id, 'notes' => 'Ditemukan di lab lain.',
        ], $user);

        $this->assertDatabaseHas('assets', ['id' => $asset->id, 'location_id' => $actual->id]);
        $this->assertDatabaseHas('assets', ['id' => $other->id, 'location_id' => $original->id]);
        $this->assertDatabaseHas('stock_take_items', ['asset_id' => $asset->id, 'expected_location_id' => $original->id, 'observed_location_id' => $actual->id]);
    }

    public function test_moved_rejects_unchanged_location_without_writes(): void
    {
        $user = $this->operator();
        $location = LocationFactory::new()->create();
        Asset::factory()->create(['location_id' => $location->id]);
        $service = app(StockTakeService::class);
        $session = $service->start(['name' => 'Sesi'], $user);

        try {
            $service->record($session, $session->items()->sole(), ['result' => 'moved', 'observed_location_id' => $location->id], $user);
            $this->fail('Lokasi yang sama harus ditolak.');
        } catch (ValidationException $exception) {
            $this->assertSame('Lokasi masih sama dengan lokasi awal. Pilih Ditemukan.', $exception->errors()['observed_location_id'][0]);
        }

        $this->assertDatabaseHas('stock_take_items', ['stock_take_id' => $session->id, 'result' => 'pending']);
    }

    public function test_finding_missing_asset_restores_its_previous_status(): void
    {
        $user = $this->operator();
        $asset = Asset::factory()->create(['status' => 'in_use']);
        $service = app(StockTakeService::class);
        $session = $service->start(['name' => 'Sesi'], $user);
        $item = $session->items()->sole();
        $service->record($session, $item, ['result' => 'missing', 'notes' => 'Belum ditemukan'], $user);

        $service->record($session, $item, ['result' => 'found', 'notes' => 'Sudah ditemukan'], $user);

        $this->assertDatabaseHas('assets', ['id' => $asset->id, 'status' => 'in_use']);
        $this->assertSame(2, $asset->histories()->where('action', 'stock_take')->count());
    }

    public function test_cannot_complete_with_unchecked_assets(): void
    {
        $user = $this->operator();
        Asset::factory()->create();
        $session = app(StockTakeService::class)->start(['name' => 'Sesi'], $user);

        Livewire::test(ViewStockTake::class, ['record' => $session->id])
            ->callAction('complete')->assertNotified('Belum dapat disimpan');

        $this->assertDatabaseHas('stock_takes', ['id' => $session->id, 'status' => 'open', 'completed_at' => null]);
    }

    public function test_completed_session_rejects_late_updates(): void
    {
        $user = $this->operator();
        Asset::factory()->create();
        $service = app(StockTakeService::class);
        $session = $service->start(['name' => 'Sesi'], $user);
        $item = $session->items()->sole();
        $service->record($session, $item, ['result' => 'found'], $user);
        Livewire::test(ViewStockTake::class, ['record' => $session->id])
            ->callAction('complete')->assertNotified('Berhasil disimpan');

        try {
            $service->record($session, $item, ['result' => 'missing', 'notes' => 'Terlambat'], $user);
            $this->fail('Sesi selesai harus terkunci.');
        } catch (ValidationException $exception) {
            $this->assertSame('Sesi sudah selesai dan hasilnya tidak dapat diubah.', $exception->errors()['result'][0]);
        }

        $this->assertDatabaseHas('stock_takes', ['id' => $session->id, 'status' => 'completed']);
        $this->assertDatabaseHas('stock_take_items', ['id' => $item->id, 'result' => 'found']);
    }

    public function test_qr_links_to_authenticated_actions_with_session_context(): void
    {
        $asset = Asset::factory()->create();

        $this->get(route('asset.qr.show', ['token' => $asset->qr_token, 'stock_take' => 12]))
            ->assertSee('Catat Stock Opname')->assertSee('Laporkan Kerusakan / Perawatan')
            ->assertSee('actionArguments%5Bstock_take_id%5D=12')->assertSee('stock_take=12');
    }

    public function test_scanner_carries_only_valid_session_id(): void
    {
        $this->get(route('qr.scan', ['stock_take' => 12]))->assertSee('data-stock-take="12"', false)
            ->assertSee('Mode stock opname');
    }

    public function test_invalid_scanner_context_is_ignored(): void
    {
        $this->get(route('qr.scan', ['stock_take' => '<script>bad</script>']))
            ->assertDontSee('Mode stock opname')->assertDontSee('<script>bad</script>', false);
    }

    public function test_guests_must_login_before_recording_qr_result(): void
    {
        $asset = Asset::factory()->create();

        $this->get(AssetResource::getUrl('view', ['record' => $asset, 'action' => 'stockTake']))
            ->assertRedirect(route('filament.admin.auth.login'));
    }

    public function test_session_detail_and_list_render(): void
    {
        $user = $this->operator();
        Asset::factory()->create();
        $session = app(StockTakeService::class)->start(['name' => 'Pemeriksaan Lab'], $user);

        Livewire::test(ViewStockTake::class, ['record' => $session->id])->assertSee('Pemeriksaan Lab')->assertSee('Belum diperiksa: 1');
        $this->get(StockTakeResource::getUrl('index'))->assertSee('Pemeriksaan Lab');
    }

    public function test_asset_outside_scope_cannot_be_recorded(): void
    {
        $user = $this->operator();
        $location = LocationFactory::new()->create();
        Asset::factory()->create(['location_id' => $location->id]);
        $outside = Asset::factory()->create();
        $session = app(StockTakeService::class)->start(['name' => 'Terbatas', 'location_id' => $location->id], $user);

        Livewire::test(ViewAsset::class, ['record' => $outside->id])
            ->callAction('stockTake', data: ['stock_take_id' => $session->id, 'result' => 'found'])
            ->assertHasActionErrors(['stock_take_id']);

        $this->assertDatabaseMissing('stock_take_items', ['stock_take_id' => $session->id, 'asset_id' => $outside->id]);
    }

    public function test_qr_deep_link_opens_result_form_with_selected_session(): void
    {
        $user = $this->operator();
        $asset = Asset::factory()->create();
        $session = app(StockTakeService::class)->start(['name' => 'QR Session'], $user);

        Livewire::withQueryParams([
            'action' => 'stockTake', 'actionArguments' => ['stock_take_id' => $session->id], 'stock_take' => $session->id,
        ])->test(ViewAsset::class, ['record' => $asset->id])
            ->assertSet('defaultAction', 'stockTake')
            ->assertSet('defaultActionArguments', ['stock_take_id' => $session->id])
            ->call('mountAction', 'stockTake', ['stock_take_id' => $session->id], ['mountedFromUrl' => true])
            ->assertSet('mountedActions.0.name', 'stockTake')->assertActionDataSet(['stock_take_id' => $session->id])
            ->assertSet('stockTakeId', $session->id);
    }

    public function test_deleted_asset_remains_in_checklist_and_can_be_marked_missing(): void
    {
        $user = $this->operator();
        $asset = Asset::factory()->create();
        $service = app(StockTakeService::class);
        $session = $service->start(['name' => 'Sesi'], $user);
        $item = $session->items()->sole();
        $asset->delete();

        $service->record($session, $item, ['result' => 'missing', 'notes' => 'Aset dihapus dari inventaris.'], $user);

        $this->assertDatabaseHas('stock_take_items', ['id' => $item->id, 'asset_id' => null, 'asset_code' => $asset->asset_code, 'result' => 'missing']);
    }

    public function test_repeated_check_does_not_duplicate_checklist_item(): void
    {
        $user = $this->operator();
        Asset::factory()->create();
        $service = app(StockTakeService::class);
        $session = $service->start(['name' => 'Sesi'], $user);
        $item = $session->items()->sole();
        $service->record($session, $item, ['result' => 'found'], $user);

        $service->record($session, $item, ['result' => 'found', 'notes' => 'Pemeriksaan ulang'], $user);

        $this->assertDatabaseCount('stock_take_items', 1);
        $this->assertDatabaseHas('stock_take_items', ['id' => $item->id, 'notes' => 'Pemeriksaan ulang']);
    }
}
