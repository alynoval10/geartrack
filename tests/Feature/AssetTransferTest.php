<?php

namespace Tests\Feature;

use App\Filament\Resources\AssetTransfers\AssetTransferResource;
use App\Filament\Resources\AssetTransfers\Pages\CreateAssetTransfer;
use App\Models\Asset;
use App\Models\AssetSet;
use App\Models\AssetTransfer;
use App\Models\User;
use App\Services\AssetTransferService;
use App\Services\LoanService;
use Database\Factories\LocationFactory;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class AssetTransferTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['app.env' => 'local']);
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    private function data(array $extra): array
    {
        return [...[
            'sender_name' => 'Petugas Lama', 'receiver_name' => 'Guru Baru', 'reason' => 'Pemindahan lab',
        ], ...$extra];
    }

    public function test_form_updates_placement_and_produces_printable_handover(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $origin = LocationFactory::new()->create(['name' => 'Lab Asal']);
        $target = LocationFactory::new()->create(['name' => 'Lab Tujuan']);
        $asset = Asset::factory()->create(['location_id' => $origin->id, 'custodian_name' => 'Guru Lama', 'status' => 'available']);

        Livewire::test(CreateAssetTransfer::class)->fillForm($this->data([
            'asset_ids' => [$asset->id], 'destination_location_id' => $target->id,
        ]))->call('create')->assertHasNoFormErrors();

        $transfer = AssetTransfer::sole();
        $this->assertSame($target->id, $asset->fresh()->location_id);
        $this->assertSame('Guru Baru', $asset->fresh()->custodian_name);
        $this->assertDatabaseHas('asset_transfer_items', [
            'asset_id' => $asset->id, 'source_location_name' => 'Lab Asal', 'previous_custodian' => 'Guru Lama',
        ]);
        $this->get(AssetTransferResource::getUrl('view', ['record' => $transfer]))->assertOk();
        $this->get(route('transfers.document', $transfer))->assertOk()
            ->assertSee('BERITA ACARA SERAH TERIMA ASET')
            ->assertSee($asset->asset_code)->assertSee('Lab Asal')->assertSee('Lab Tujuan');
    }

    public function test_package_transfer_moves_all_members_and_package_location(): void
    {
        $user = User::factory()->create();
        $origin = LocationFactory::new()->create();
        $target = LocationFactory::new()->create();
        $package = AssetSet::create(['name' => 'Paket Lab', 'location_id' => $origin->id, 'is_active' => true]);
        $assets = collect([
            Asset::factory()->create(['asset_set_id' => $package->id, 'set_role' => 'main_pc', 'location_id' => $origin->id, 'status' => 'available']),
            Asset::factory()->create(['asset_set_id' => $package->id, 'set_role' => 'monitor', 'location_id' => $origin->id, 'status' => 'available']),
        ]);

        $transfer = app(AssetTransferService::class)->transfer($this->data([
            'asset_set_id' => $package->id, 'destination_location_id' => $target->id,
        ]), $user);

        $this->assertSame(2, $transfer->items()->count());
        $this->assertSame($target->id, $package->fresh()->location_id);
        foreach ($assets as $asset) {
            $this->assertSame($target->id, $asset->fresh()->location_id);
        }
    }

    public function test_borrowed_member_rejects_entire_transfer(): void
    {
        $user = User::factory()->create();
        $target = LocationFactory::new()->create();
        $first = Asset::factory()->create(['status' => 'available']);
        $borrowed = Asset::factory()->create(['status' => 'available', 'condition' => 'good']);
        app(LoanService::class)->borrow([
            'borrower_name' => 'Siswa', 'responsible_name' => 'Guru', 'purpose' => 'Praktik',
            'due_date' => today()->toDateString(), 'asset_ids' => [$borrowed->id],
        ], $user);

        try {
            app(AssetTransferService::class)->transfer($this->data([
                'asset_ids' => [$first->id, $borrowed->id], 'destination_location_id' => $target->id,
            ]), $user);
            $this->fail('Borrowed device was transferred.');
        } catch (ValidationException) {
            $this->assertDatabaseCount('asset_transfers', 0);
            $this->assertSame($first->location_id, $first->fresh()->location_id);
        }
    }

    public function test_custodian_can_change_without_changing_room_but_no_op_is_rejected(): void
    {
        $user = User::factory()->create();
        $location = LocationFactory::new()->create();
        $asset = Asset::factory()->create(['location_id' => $location->id, 'custodian_name' => 'Guru Lama', 'status' => 'available']);
        $data = $this->data(['asset_ids' => [$asset->id], 'destination_location_id' => $location->id]);
        app(AssetTransferService::class)->transfer($data, $user);
        $this->assertSame('Guru Baru', $asset->fresh()->custodian_name);

        $this->expectException(ValidationException::class);
        app(AssetTransferService::class)->transfer($data, $user);
    }

    public function test_document_keeps_snapshots_after_asset_and_location_are_changed_or_deleted(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $origin = LocationFactory::new()->create(['name' => 'Lokasi Sebelumnya']);
        $target = LocationFactory::new()->create(['name' => 'Lokasi Tujuan Awal']);
        $asset = Asset::factory()->create(['name' => 'Nama Saat Serah Terima', 'location_id' => $origin->id, 'status' => 'available']);
        $transfer = app(AssetTransferService::class)->transfer($this->data([
            'asset_ids' => [$asset->id], 'destination_location_id' => $target->id,
        ]), $user);
        $asset->delete();
        $target->update(['name' => 'Nama Lokasi Baru']);
        $origin->delete();

        $this->get(route('transfers.document', $transfer))->assertOk()
            ->assertSee('Nama Saat Serah Terima')->assertSee('Lokasi Sebelumnya')->assertSee('Lokasi Tujuan Awal')
            ->assertDontSee('Nama Lokasi Baru');
        $this->assertNull($transfer->items()->sole()->asset_id);
    }

    public function test_inactive_destination_is_rejected(): void
    {
        $user = User::factory()->create();
        $target = LocationFactory::new()->create(['is_active' => false]);
        $asset = Asset::factory()->create(['status' => 'available']);

        $this->expectException(ValidationException::class);
        app(AssetTransferService::class)->transfer($this->data([
            'asset_ids' => [$asset->id], 'destination_location_id' => $target->id,
        ]), $user);
    }

    public function test_document_escapes_names_and_requires_login(): void
    {
        $transfer = AssetTransfer::factory()->create(['sender_name' => '<script>alert(1)</script>']);
        $this->get(route('transfers.document', $transfer))->assertRedirect();

        $this->actingAs(User::factory()->create())->get(route('transfers.document', $transfer))
            ->assertOk()->assertDontSee('<script>alert(1)</script>', false)
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false);
        $this->assertFalse(AssetTransferResource::canEdit($transfer));
        $this->assertFalse(AssetTransferResource::canDelete($transfer));
    }
}
