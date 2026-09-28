<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\User;
use App\Services\UserManagementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AssetCustodianTest extends TestCase
{
    use RefreshDatabase;

    public function test_asset_stores_selected_user_and_name_snapshot(): void
    {
        $guru = User::factory()->create(['name' => 'Guru Lab', 'role' => 'guru']);
        $asset = Asset::factory()->create(['custodian_user_id' => $guru->id]);

        $this->assertTrue($asset->custodian->is($guru));
        $this->assertSame('Guru Lab', $asset->custodian_name);
    }

    public function test_user_name_change_updates_current_asset_snapshot(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $guru = User::factory()->create(['name' => 'Nama Lama', 'role' => 'guru']);
        $asset = Asset::factory()->create(['custodian_user_id' => $guru->id]);
        $this->actingAs($admin);

        app(UserManagementService::class)->save([
            'name' => 'Nama Baru',
            'email' => $guru->email,
            'role' => 'guru',
            'is_active' => true,
        ], $guru);

        $this->assertSame('Nama Baru', $asset->fresh()->custodian_name);
    }

    public function test_inactive_user_cannot_be_selected_as_custodian(): void
    {
        $guru = User::factory()->create(['role' => 'guru', 'is_active' => false]);

        $this->expectException(ValidationException::class);

        Asset::factory()->create(['custodian_user_id' => $guru->id]);
    }
}
