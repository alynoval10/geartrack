<?php

namespace Tests\Feature;

use App\Models\SchoolSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SchoolSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_save_school_identity(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->post(route('school-settings.update'), [
            'school_name' => 'SMKN 1 Krangkeng',
            'address' => 'Kabupaten Indramayu',
            'academic_year' => '2026/2027',
            'principal_name' => 'Kepala Sekolah',
            'asset_manager_name' => 'Pengurus Barang',
            'report_signer_name' => 'Penandatangan',
            'report_signer_title' => 'Wakasek Sarpras',
            'qr_label_width_mm' => 60,
            'qr_label_height_mm' => 40,
        ])->assertRedirect(route('filament.admin.pages.school-settings'));

        $this->assertDatabaseHas('school_settings', [
            'school_name' => 'SMKN 1 Krangkeng',
            'academic_year' => '2026/2027',
            'report_signer_title' => 'Wakasek Sarpras',
            'qr_label_width_mm' => 60,
            'qr_label_height_mm' => 40,
        ]);
    }

    public function test_guru_cannot_change_school_identity(): void
    {
        $guru = User::factory()->create(['role' => 'guru']);

        $this->actingAs($guru)->post(route('school-settings.update'), [
            'school_name' => 'Tidak Diizinkan',
        ])->assertForbidden();

        $this->assertDatabaseCount('school_settings', 0);
    }

    public function test_settings_page_displays_saved_identity(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        SchoolSetting::create(['school_name' => 'Sekolah Pengujian']);

        $this->actingAs($admin)
            ->get(route('filament.admin.pages.school-settings'))
            ->assertOk()
            ->assertSee('Sekolah Pengujian');
    }

    public function test_label_size_is_validated(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->from(route('filament.admin.pages.school-settings'))
            ->post(route('school-settings.update'), [
                'school_name' => 'Sekolah Pengujian',
                'qr_label_width_mm' => 20,
                'qr_label_height_mm' => 10,
            ])
            ->assertRedirect(route('filament.admin.pages.school-settings'))
            ->assertSessionHasErrors(['qr_label_width_mm', 'qr_label_height_mm']);
    }
}
