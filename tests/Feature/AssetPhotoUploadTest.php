<?php

namespace Tests\Feature;

use App\Filament\Resources\Assets\AssetResource;
use App\Filament\Resources\Assets\Pages\CreateAsset;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\FileUpload;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AssetPhotoUploadTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAs(User::factory()->create());
    }

    public function test_asset_form_offers_camera_beside_the_default_photo_uploader(): void
    {
        $cameraAction = TestAction::make('takeAssetPhoto')->schemaComponent('assetPhotoActions');
        $removedGalleryAction = TestAction::make('chooseAssetPhoto')->schemaComponent('assetPhotoActions');

        Livewire::test(CreateAsset::class)
            ->assertActionExists(
                $cameraAction,
                fn (Action $action): bool => str_contains($action->getCustomAlpineClickHandler(), 'openCamera'),
            )
            ->assertActionDoesNotExist($removedGalleryAction)
            ->assertSchemaComponentExists(
                'photo',
                'form',
                fn (FileUpload $component): bool => $component->getExtraAttributeBag()->get('data-asset-photo-upload') === 'true'
                    && $component->getAutomaticallyResizeImagesWidth() === null
                    && $component->getAutomaticallyResizeImagesHeight() === null,
            );

        $this->get(AssetResource::getUrl('create'))
            ->assertOk()
            ->assertSee('asset-photo-picker-', false);
    }
}
