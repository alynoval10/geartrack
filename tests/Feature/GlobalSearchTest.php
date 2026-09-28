<?php

namespace Tests\Feature;

use App\Filament\Resources\Assets\AssetResource;
use App\Filament\Resources\AssetSets\AssetSetResource;
use App\Models\Asset;
use App\Models\AssetSet;
use App\Models\Location;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GlobalSearchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAs(User::factory()->create(['role' => 'guru']));
    }

    public function test_asset_can_be_found_by_identity_and_related_data(): void
    {
        $location = Location::query()->create(['name' => 'Laboratorium Jaringan']);
        $custodian = User::factory()->create(['name' => 'Budi Santoso', 'role' => 'guru']);
        $assetSet = AssetSet::query()->create([
            'code' => 'SET-LAB-01',
            'name' => 'Paket Praktik Router',
            'location_id' => $location->id,
            'is_active' => true,
        ]);
        $asset = Asset::factory()->create([
            'asset_code' => 'RTR-000321',
            'name' => 'Router Praktik Utama',
            'serial_number' => 'SN-MT-99881',
            'location_id' => $location->id,
            'custodian_user_id' => $custodian->id,
            'asset_set_id' => $assetSet->id,
            'set_role' => 'device',
        ]);

        foreach (['RTR-000321', 'Router Praktik', 'SN-MT-99881', 'Budi Santoso', 'Laboratorium Jaringan', 'Paket Praktik Router'] as $search) {
            $result = AssetResource::getGlobalSearchResults($search)->first();

            $this->assertNotNull($result, "Aset tidak ditemukan menggunakan: {$search}");
            $this->assertSame($asset->name, (string) $result->title);
        }

        $details = AssetResource::getGlobalSearchResults('RTR-000321')->first()->details;

        $this->assertSame('RTR-000321', $details['Kode']);
        $this->assertSame('Laboratorium Jaringan', $details['Lokasi']);
        $this->assertSame('Budi Santoso', $details['Penanggung Jawab']);
        $this->assertSame('Paket Praktik Router', $details['Paket']);
    }

    public function test_asset_set_can_be_found_by_code_name_and_location(): void
    {
        $location = Location::query()->create(['name' => 'Ruang Server Utama']);
        $assetSet = AssetSet::query()->create([
            'code' => 'SET-SERVER-01',
            'name' => 'Paket Komputer Server',
            'location_id' => $location->id,
            'is_active' => true,
        ]);

        foreach (['SET-SERVER-01', 'Paket Komputer', 'Ruang Server'] as $search) {
            $result = AssetSetResource::getGlobalSearchResults($search)->first();

            $this->assertNotNull($result, "Paket tidak ditemukan menggunakan: {$search}");
            $this->assertSame($assetSet->name, (string) $result->title);
        }
    }
}
