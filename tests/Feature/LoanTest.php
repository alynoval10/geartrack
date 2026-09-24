<?php

namespace Tests\Feature;

use App\Filament\Resources\Loans\LoanResource;
use App\Filament\Resources\Loans\Pages\CreateLoan;
use App\Filament\Resources\Loans\Pages\ViewLoan;
use App\Filament\Resources\Loans\RelationManagers\ItemsRelationManager;
use App\Models\Asset;
use App\Models\AssetSet;
use App\Models\Loan;
use App\Models\MaintenanceReport;
use App\Models\User;
use App\Services\LoanService;
use App\Services\StockTakeService;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class LoanTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['app.env' => 'local']);
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    private function data(array $extra = []): array
    {
        return [...[
            'borrower_name' => 'Budi', 'responsible_name' => 'Guru TKJ',
            'purpose' => 'Praktik kelas', 'due_date' => today()->addDays(3)->toDateString(),
        ], ...$extra];
    }

    public function test_operator_can_borrow_from_form_and_return_from_detail(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $asset = Asset::factory()->create(['condition' => 'good', 'status' => 'available']);

        Livewire::test(CreateLoan::class)->fillForm($this->data(['asset_ids' => [$asset->id]]))
            ->call('create')->assertHasNoFormErrors();

        $loan = Loan::sole();
        $this->assertSame('borrowed', $asset->fresh()->status);
        $this->get(LoanResource::getUrl('view', ['record' => $loan]))->assertOk();
        Livewire::test(ItemsRelationManager::class, ['ownerRecord' => $loan, 'pageClass' => ViewLoan::class])
            ->callTableAction('receive', $loan->items()->sole(), ['condition' => 'good'])
            ->assertHasNoTableActionErrors();
        $this->assertSame('returned', $loan->fresh()->status);
        $this->assertSame('available', $asset->fresh()->status);
    }

    public function test_package_borrow_is_a_snapshot_and_supports_partial_returns(): void
    {
        $user = User::factory()->create();
        $package = AssetSet::create(['name' => 'Paket Lab', 'is_active' => true]);
        $pc = Asset::factory()->create(['asset_set_id' => $package->id, 'set_role' => 'main_pc', 'status' => 'in_use', 'condition' => 'good']);
        $monitor = Asset::factory()->create(['asset_set_id' => $package->id, 'set_role' => 'monitor', 'status' => 'available', 'condition' => 'good']);
        $service = app(LoanService::class);
        $loan = $service->borrow($this->data(['asset_set_id' => $package->id]), $user);
        Asset::factory()->create(['asset_set_id' => $package->id, 'set_role' => 'device']);

        $service->receive($loan, $loan->items()->where('asset_id', $pc->id)->firstOrFail(), ['condition' => 'good'], $user);

        $this->assertSame(2, $loan->items()->count());
        $this->assertSame('open', $loan->fresh()->status);
        $this->assertSame('in_use', $pc->fresh()->status);
        $this->assertSame('borrowed', $monitor->fresh()->status);
        $service->receive($loan, $loan->items()->where('asset_id', $monitor->id)->firstOrFail(), ['condition' => 'good'], $user);
        $this->assertSame('returned', $loan->fresh()->status);
    }

    public function test_unavailable_member_rolls_back_entire_package_loan(): void
    {
        $user = User::factory()->create();
        $package = AssetSet::create(['name' => 'Paket Lab', 'is_active' => true]);
        $available = Asset::factory()->create(['asset_set_id' => $package->id, 'set_role' => 'main_pc', 'status' => 'available', 'condition' => 'good']);
        Asset::factory()->create(['asset_set_id' => $package->id, 'set_role' => 'monitor', 'status' => 'maintenance']);
        try {
            app(LoanService::class)->borrow($this->data(['asset_set_id' => $package->id]), $user);
            $this->fail('Unavailable package was loaned.');
        } catch (ValidationException) {
            $this->assertDatabaseCount('loans', 0);
            $this->assertSame('available', $available->fresh()->status);
        }
    }

    public function test_double_borrow_is_rejected(): void
    {
        $user = User::factory()->create();
        $asset = Asset::factory()->create(['status' => 'available', 'condition' => 'good']);
        $data = $this->data(['asset_ids' => [$asset->id]]);
        app(LoanService::class)->borrow($data, $user);

        $this->expectException(ValidationException::class);
        app(LoanService::class)->borrow($data, $user);
    }

    public function test_damaged_return_creates_report_and_keeps_asset_out_of_available_stock(): void
    {
        $user = User::factory()->create();
        $asset = Asset::factory()->create(['status' => 'available', 'condition' => 'good']);
        $loan = app(LoanService::class)->borrow($this->data(['asset_ids' => [$asset->id]]), $user);

        app(LoanService::class)->receive($loan, $loan->items()->sole(), [
            'condition' => 'minor_damage', 'notes' => 'Port USB rusak',
        ], $user);

        $this->assertSame('maintenance', $asset->fresh()->status);
        $this->assertDatabaseHas('maintenance_reports', ['asset_id' => $asset->id, 'status' => 'open', 'type' => 'damage']);
        $this->assertDatabaseHas('loan_items', ['loan_id' => $loan->id, 'condition_after' => 'minor_damage', 'active_asset_id' => null]);
    }

    public function test_repeat_return_is_rejected(): void
    {
        $user = User::factory()->create();
        $asset = Asset::factory()->create(['status' => 'available', 'condition' => 'good']);
        $loan = app(LoanService::class)->borrow($this->data(['asset_ids' => [$asset->id]]), $user);
        $item = $loan->items()->sole();
        app(LoanService::class)->receive($loan, $item, ['condition' => 'good'], $user);

        $this->expectException(ValidationException::class);
        app(LoanService::class)->receive($loan, $item, ['condition' => 'good'], $user);
    }

    public function test_borrowed_asset_cannot_be_manually_made_available_or_deleted(): void
    {
        $user = User::factory()->create();
        $asset = Asset::factory()->create(['status' => 'available', 'condition' => 'good']);
        app(LoanService::class)->borrow($this->data(['asset_ids' => [$asset->id]]), $user);
        try {
            $asset->fresh()->update(['status' => 'available']);
            $this->fail('Loan status bypassed.');
        } catch (ValidationException) {
            $this->assertSame('borrowed', $asset->fresh()->status);
        }

        $this->expectException(ValidationException::class);
        $asset->fresh()->delete();
    }

    public function test_found_asset_restores_borrowed_status_when_loan_is_still_open(): void
    {
        $user = User::factory()->create();
        $asset = Asset::factory()->create(['status' => 'available', 'condition' => 'good']);
        $opname = app(StockTakeService::class)->start(['name' => 'Sesi'], $user);
        app(LoanService::class)->borrow($this->data(['asset_ids' => [$asset->id]]), $user);
        $item = $opname->items()->where('asset_id', $asset->id)->firstOrFail();
        app(StockTakeService::class)->record($opname, $item, ['result' => 'missing', 'notes' => 'Tidak terlihat'], $user);

        app(StockTakeService::class)->record($opname, $item->fresh(), ['result' => 'found'], $user);

        $this->assertSame('borrowed', $asset->fresh()->status);
    }

    public function test_overdue_is_calculated_by_due_date_and_excludes_returned_loans(): void
    {
        $this->freezeTime();
        $loan = Loan::factory()->create(['due_date' => today()->subDay()]);
        $this->assertTrue($loan->isOverdue());
        $loan->update(['status' => 'returned']);
        $this->assertFalse($loan->fresh()->isOverdue());
        $loan->update(['status' => 'open', 'due_date' => today()]);
        $this->assertFalse($loan->fresh()->isOverdue());
    }

    public function test_guest_cannot_access_loans(): void
    {
        $this->get(LoanResource::getUrl('index'))->assertRedirect();
        $this->get(LoanResource::getUrl('create'))->assertRedirect();
    }

    public function test_empty_selection_and_past_due_date_are_rejected(): void
    {
        $user = User::factory()->create();
        try {
            app(LoanService::class)->borrow($this->data(), $user);
            $this->fail('Empty loan was accepted.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('asset_ids', $exception->errors());
        }
        $this->expectException(ValidationException::class);
        app(LoanService::class)->borrow($this->data(['due_date' => today()->subDay()->toDateString()]), $user);
    }

    public function test_open_damage_report_blocks_borrow_even_if_status_is_available(): void
    {
        $user = User::factory()->create();
        $asset = Asset::factory()->create(['status' => 'available', 'condition' => 'good']);
        MaintenanceReport::factory()->create(['asset_id' => $asset->id, 'status' => 'open']);

        $this->expectException(ValidationException::class);
        app(LoanService::class)->borrow($this->data(['asset_ids' => [$asset->id]]), $user);
    }
}
