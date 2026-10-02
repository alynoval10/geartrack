<?php

namespace Tests\Feature;

use App\Filament\Resources\Categories\CategoryResource;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InterfaceLanguageTest extends TestCase
{
    use RefreshDatabase;

    public function test_application_and_filament_use_indonesian_interface_terms(): void
    {
        $this->assertSame('id', app()->getLocale());
        $this->assertSame('Tambah', __('filament-panels::resources/pages/create-record.breadcrumb'));
        $this->assertSame('Simpan & tambah lagi', __('filament-panels::resources/pages/create-record.form.actions.create_another.label'));
        $this->assertSame('Pilih data', __('filament-forms::components.select.placeholder'));
    }

    public function test_create_page_displays_consistent_indonesian_actions(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));

        $this->get(CategoryResource::getUrl('create'))
            ->assertSee('Tambah Kategori')
            ->assertSee('Simpan')
            ->assertSee('Simpan &amp; tambah lagi', false)
            ->assertSee('Batal')
            ->assertDontSee('Create');
    }
}
