<?php

namespace Tests\Feature;

use App\Filament\Resources\AssetSets\AssetSetResource;
use App\Filament\Resources\AssetSets\Pages\CreateAssetSet;
use App\Filament\Resources\AssetSets\Pages\ViewAssetSet;
use App\Filament\Resources\AssetSets\RelationManagers\AssetsRelationManager;
use App\Models\Asset;
use App\Models\AssetSet;
use App\Models\Category;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Tests\TestCase;

class AssetSetTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    private function asset(array $attributes = []): Asset
    {
        $category = Category::firstOrCreate(['name' => 'Komputer'], ['asset_prefix' => 'PC']);

        return Asset::create(array_merge([
            'name' => 'PC Laboratorium',
            'asset_code' => 'PC-'.fake()->unique()->numerify('#####'),
            'category_id' => $category->id,
        ], $attributes));
    }

    private function members(AssetSet $set): Testable
    {
        $this->actingAs(User::factory()->create());

        return Livewire::test(AssetsRelationManager::class, [
            'ownerRecord' => $set,
            'pageClass' => ViewAssetSet::class,
        ]);
    }

    public function test_creates_package_and_redirects_to_detail(): void
    {
        $this->actingAs(User::factory()->create());

        $page = Livewire::test(CreateAssetSet::class)
            ->fillForm(['name' => 'PC Lab 01', 'accessories' => 'Keyboard dan Mouse', 'is_active' => true])
            ->call('create')->assertHasNoFormErrors();

        $set = AssetSet::sole();
        $this->assertSame('SET-001', $set->code);
        $this->assertSame('PC Lab 01', $set->name);
        $page->assertRedirect(AssetSetResource::getUrl('view', ['record' => $set]));
    }

    public function test_package_name_is_required(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(CreateAssetSet::class)->fillForm(['name' => ''])
            ->call('create')->assertHasFormErrors(['name' => 'required']);

        $this->assertDatabaseCount('asset_sets', 0);
    }

    public function test_adds_pc_and_monitor_with_roles_from_detail_page(): void
    {
        $set = AssetSet::create(['name' => 'PC Lab 01']);
        $pc = $this->asset();
        $monitor = $this->asset(['name' => 'Monitor Lab']);

        $this->members($set)
            ->callTableAction('associate', data: ['recordId' => $pc->id, 'set_role' => 'main_pc'])
            ->assertHasNoTableActionErrors()
            ->callTableAction('associate', data: ['recordId' => $monitor->id, 'set_role' => 'monitor'])
            ->assertHasNoTableActionErrors();

        $this->assertDatabaseHas('assets', ['id' => $pc->id, 'asset_set_id' => $set->id, 'set_role' => 'main_pc']);
        $this->assertDatabaseHas('assets', ['id' => $monitor->id, 'asset_set_id' => $set->id, 'set_role' => 'monitor']);
        $this->assertDatabaseHas('asset_histories', ['asset_id' => $pc->id, 'field' => 'asset_set_id', 'new_value' => 'PC Lab 01']);
    }

    public function test_rejects_missing_and_invalid_roles(): void
    {
        $set = AssetSet::create(['name' => 'Paket']);
        $asset = $this->asset();
        $page = $this->members($set);

        $page->callTableAction('associate', data: ['recordId' => $asset->id, 'set_role' => null])
            ->assertHasTableActionErrors(['set_role' => 'required']);
        $this->members($set)->callTableAction('associate', data: ['recordId' => $asset->id, 'set_role' => 'invalid'])
            ->assertHasTableActionErrors(['set_role']);

        $this->assertDatabaseHas('assets', ['id' => $asset->id, 'asset_set_id' => null]);
    }

    public function test_cannot_take_asset_from_another_package(): void
    {
        $first = AssetSet::create(['name' => 'Pertama']);
        $second = AssetSet::create(['name' => 'Kedua']);
        $asset = $this->asset(['asset_set_id' => $first->id, 'set_role' => 'main_pc']);

        $this->members($second)->callTableAction('associate', data: ['recordId' => $asset->id, 'set_role' => 'monitor']);

        $this->assertDatabaseHas('assets', ['id' => $asset->id, 'asset_set_id' => $first->id, 'set_role' => 'main_pc']);
    }

    public function test_changes_role_and_records_history(): void
    {
        $set = AssetSet::create(['name' => 'Paket']);
        $asset = $this->asset(['asset_set_id' => $set->id, 'set_role' => 'monitor']);

        $this->members($set)->call('updateTableColumnState', 'set_role', (string) $asset->id, 'device');

        $this->assertDatabaseHas('assets', ['id' => $asset->id, 'set_role' => 'device']);
        $this->assertDatabaseHas('asset_histories', ['asset_id' => $asset->id, 'field' => 'set_role', 'old_value' => 'Monitor', 'new_value' => 'Perangkat Tambahan']);
    }

    public function test_removing_member_preserves_asset_and_clears_role(): void
    {
        $set = AssetSet::create(['name' => 'Paket']);
        $asset = $this->asset(['asset_set_id' => $set->id, 'set_role' => 'main_pc']);

        $this->members($set)->callTableAction('dissociate', $asset)->assertHasNoTableActionErrors();

        $this->assertDatabaseHas('assets', ['id' => $asset->id, 'asset_set_id' => null, 'set_role' => null]);
    }

    public function test_deleting_package_preserves_members_without_stale_roles(): void
    {
        $set = AssetSet::create(['name' => 'Paket']);
        $asset = $this->asset(['asset_set_id' => $set->id, 'set_role' => 'main_pc']);

        $set->delete();

        $this->assertDatabaseHas('assets', ['id' => $asset->id, 'asset_set_id' => null, 'set_role' => null]);
    }

    public function test_detail_renders_package_information(): void
    {
        config(['app.env' => 'local']);
        $set = AssetSet::create(['name' => 'PC Lab 01', 'accessories' => 'Keyboard', 'notes' => 'Catatan internal']);
        $this->actingAs(User::factory()->create());

        $this->get(AssetSetResource::getUrl('view', ['record' => $set]))
            ->assertSee('PC Lab 01')->assertSee('Keyboard')->assertSee('Catatan internal');
    }

    public function test_qr_renders_package_members_and_specifications_once(): void
    {
        $set = AssetSet::create(['name' => 'PC Lab 01', 'accessories' => '<script>alert(1)</script>', 'notes' => 'Catatan rahasia', 'is_active' => false]);
        $pc = $this->asset(['asset_set_id' => $set->id, 'set_role' => 'main_pc']);
        $monitor = $this->asset(['name' => 'Monitor Lab', 'asset_set_id' => $set->id, 'set_role' => 'monitor']);
        $pc->specifications()->create(['key' => 'ram', 'label' => 'RAM', 'value' => '16 GB']);

        $response = $this->get(route('asset.qr.show', $pc->qr_token));

        $response->assertSee('PC Lab 01')->assertSee('Tidak Aktif')->assertSee('PC Utama')
            ->assertSee('Monitor Lab')->assertSee(route('asset.qr.show', $monitor->qr_token))
            ->assertSee('<script>alert(1)</script>')->assertDontSee('<script>alert(1)</script>', false)
            ->assertDontSee('Catatan rahasia')->assertSeeInOrder(['Spesifikasi Teknis', '16 GB', '</main>'], false);
        $this->assertSame(1, substr_count($response->getContent(), '>Spesifikasi Teknis<'));
    }

    public function test_qr_without_package_remains_available(): void
    {
        $asset = $this->asset();

        $this->get(route('asset.qr.show', $asset->qr_token))
            ->assertSee('PC Laboratorium')->assertSee('Perangkat ini belum tergabung dalam paket.');
    }

    public function test_unknown_qr_returns_404(): void
    {
        $this->get(route('asset.qr.show', 'unknown'))->assertNotFound();
    }

    public function test_package_management_requires_login(): void
    {
        $this->get(AssetSetResource::getUrl('index'))->assertRedirect(route('filament.admin.auth.login'));
    }

    public function test_login_page_remains_available(): void
    {
        $this->get(route('filament.admin.auth.login'))->assertSee('Masuk ke GearTrack');
    }

    public function test_qr_label_remains_available_for_package_member(): void
    {
        $set = AssetSet::create(['name' => 'Paket']);
        $asset = $this->asset(['asset_set_id' => $set->id, 'set_role' => 'main_pc']);

        $this->get(route('asset.qr.label', $asset->qr_token))
            ->assertSee($asset->asset_code)->assertSee('<svg', false);
    }

    public function test_scanner_remains_available(): void
    {
        $this->get(route('qr.scan'))->assertOk()->assertViewIs('scanner.index');
    }
}
