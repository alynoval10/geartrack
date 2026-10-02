<?php

namespace Tests\Feature;

use App\Filament\Resources\Assets\AssetResource;
use App\Models\Asset;
use App\Models\Category;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AssetDetailTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAs(User::factory()->create());
    }

    public function test_asset_detail_displays_the_photo_and_localized_information(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('assets/router.jpg', 'photo-content');

        $category = Category::query()->create([
            'name' => 'Router',
            'asset_prefix' => 'RTR',
            'is_active' => true,
        ]);
        $asset = Asset::factory()->create([
            'category_id' => $category->id,
            'photo' => 'assets/router.jpg',
            'condition' => 'good',
            'status' => 'available',
        ]);

        $this->get(AssetResource::getUrl('view', ['record' => $asset]))
            ->assertOk()
            ->assertSee('Foto Aset')
            ->assertSee('Klik foto untuk melihat ukuran penuh.')
            ->assertSee(Storage::disk('public')->url('assets/router.jpg'), false)
            ->assertSee('Kategori')
            ->assertSee('Router')
            ->assertSee('Baik')
            ->assertSee('Tersedia');
    }
}
