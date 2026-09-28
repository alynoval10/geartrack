<?php

namespace Tests\Feature;

use App\Filament\Resources\AssetHistories\AssetHistoryResource;
use App\Models\Asset;
use App\Models\AssetHistory;
use App\Models\User;
use App\Services\AssetTransferService;
use App\Services\StockTakeService;
use Database\Factories\LocationFactory;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivityLogTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_asset_create_update_and_delete_are_attributed_and_preserved(): void
    {
        $admin = User::factory()->create(['name' => 'Admin Inventaris', 'role' => 'admin']);
        $this->actingAs($admin);
        $asset = Asset::factory()->create([
            'asset_code' => 'RTR-AUDIT-01',
            'name' => 'Router Lama',
        ]);

        $this->assertDatabaseHas('asset_histories', [
            'asset_id' => $asset->id,
            'asset_code' => 'RTR-AUDIT-01',
            'asset_name' => 'Router Lama',
            'user_id' => $admin->id,
            'user_name' => 'Admin Inventaris',
            'action' => 'created',
        ]);

        $asset->update(['name' => 'Router Utama']);

        $this->assertDatabaseHas('asset_histories', [
            'asset_id' => $asset->id,
            'action' => 'updated',
            'field' => 'name',
            'old_value' => 'Router Lama',
            'new_value' => 'Router Utama',
        ]);

        $asset->delete();

        $this->assertDatabaseHas('asset_histories', [
            'asset_id' => null,
            'asset_code' => 'RTR-AUDIT-01',
            'asset_name' => 'Router Utama',
            'user_name' => 'Admin Inventaris',
            'action' => 'deleted',
        ]);
        $this->assertSame(3, AssetHistory::where('asset_code', 'RTR-AUDIT-01')->count());
    }

    public function test_transfer_and_stock_take_store_actor_and_changes(): void
    {
        $user = User::factory()->create(['name' => 'Guru Pemeriksa', 'role' => 'guru']);
        $this->actingAs($user);
        $origin = LocationFactory::new()->create(['name' => 'Lab Lama']);
        $destination = LocationFactory::new()->create(['name' => 'Lab Baru']);
        $foundAt = LocationFactory::new()->create(['name' => 'Ruang Server']);
        $asset = Asset::factory()->create([
            'location_id' => $origin->id,
            'status' => 'available',
        ]);

        app(AssetTransferService::class)->transfer([
            'asset_ids' => [$asset->id],
            'destination_location_id' => $destination->id,
            'sender_name' => 'Guru Lama',
            'receiver_name' => 'Guru Baru',
            'reason' => 'Penataan laboratorium',
        ], $user);

        $this->assertDatabaseHas('asset_histories', [
            'asset_id' => $asset->id,
            'user_name' => 'Guru Pemeriksa',
            'action' => 'transfer',
            'old_value' => 'Lab Lama',
            'new_value' => 'Lab Baru',
        ]);

        $stockTakeService = app(StockTakeService::class);
        $stockTake = $stockTakeService->start(['name' => 'Opname Bulanan'], $user);
        $item = $stockTake->items()->where('asset_id', $asset->id)->sole();
        $stockTakeService->record($stockTake, $item, [
            'result' => 'moved',
            'observed_location_id' => $foundAt->id,
            'notes' => 'Ditemukan saat pemeriksaan.',
        ], $user);

        $this->assertDatabaseHas('asset_histories', [
            'asset_id' => $asset->id,
            'user_name' => 'Guru Pemeriksa',
            'action' => 'stock_take',
            'old_value' => 'Belum Diperiksa',
            'new_value' => 'Berpindah',
        ]);
    }

    public function test_only_admin_can_open_activity_log_page(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $guru = User::factory()->create(['role' => 'guru']);

        $this->actingAs($admin)
            ->get(AssetHistoryResource::getUrl('index'))
            ->assertOk()
            ->assertSee('Log Aktivitas');

        $this->actingAs($guru)
            ->get(AssetHistoryResource::getUrl('index'))
            ->assertForbidden();
    }
}
