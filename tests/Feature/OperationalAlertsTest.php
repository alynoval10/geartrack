<?php

namespace Tests\Feature;

use App\Filament\Resources\Loans\LoanResource;
use App\Filament\Resources\MaintenanceReports\MaintenanceReportResource;
use App\Filament\Resources\MaintenanceSchedules\MaintenanceScheduleResource;
use App\Filament\Resources\StockTakes\StockTakeResource;
use App\Filament\Widgets\OperationalAlerts;
use App\Models\Asset;
use App\Models\Loan;
use App\Models\MaintenanceReport;
use App\Models\MaintenanceSchedule;
use App\Models\StockTake;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class OperationalAlertsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_admin_sees_all_current_operational_alerts_and_navigation_badges(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);
        Loan::factory()->create(['code' => 'PJM-TERLAMBAT', 'due_date' => today()->subDays(3), 'status' => 'open']);
        Loan::factory()->create(['code' => 'PJM-MENDATANG', 'due_date' => today()->addDay(), 'status' => 'open']);
        MaintenanceSchedule::factory()->create(['asset_code' => 'AST-JADWAL', 'due_date' => today()->addDays(2)]);
        MaintenanceSchedule::factory()->create(['asset_code' => 'AST-NANTI', 'due_date' => today()->addDays(10)]);
        StockTake::factory()->create(['name' => 'Opname Aktif', 'status' => 'open']);
        StockTake::factory()->create(['name' => 'Opname Selesai', 'status' => 'completed']);
        MaintenanceReport::factory()->create(['asset_code' => 'AST-RUSAK', 'title' => 'Router mati', 'status' => 'open']);
        MaintenanceReport::factory()->create(['asset_code' => 'AST-SELESAI', 'status' => 'resolved']);

        Livewire::test(OperationalAlerts::class)
            ->assertSee('Notifikasi &amp; Pengingat', false)
            ->assertSee('PJM-TERLAMBAT')
            ->assertDontSee('PJM-MENDATANG')
            ->assertSee('AST-JADWAL')
            ->assertDontSee('AST-NANTI')
            ->assertSee('Opname Aktif')
            ->assertDontSee('Opname Selesai')
            ->assertSee('AST-RUSAK')
            ->assertDontSee('AST-SELESAI');

        $this->assertSame('1', LoanResource::getNavigationBadge());
        $this->assertSame('1', MaintenanceScheduleResource::getNavigationBadge());
        $this->assertSame('1', StockTakeResource::getNavigationBadge());
        $this->assertSame('1', MaintenanceReportResource::getNavigationBadge());
    }

    public function test_guru_only_sees_personal_loan_and_maintenance_alerts(): void
    {
        $guru = User::factory()->create(['role' => 'guru']);
        $otherGuru = User::factory()->create(['role' => 'guru']);
        $this->actingAs($guru);

        Loan::factory()->create(['code' => 'PJM-SAYA', 'responsible_user_id' => $guru->id, 'due_date' => today()->subDay()]);
        Loan::factory()->create(['code' => 'PJM-LAIN', 'responsible_user_id' => $otherGuru->id, 'due_date' => today()->subDay()]);
        MaintenanceSchedule::factory()->create(['asset_code' => 'JADWAL-SAYA', 'technician_user_id' => $guru->id, 'due_date' => today()]);
        MaintenanceSchedule::factory()->create(['asset_code' => 'JADWAL-LAIN', 'technician_user_id' => $otherGuru->id, 'due_date' => today()]);
        $ownedAsset = Asset::factory()->create(['custodian_user_id' => $guru->id]);
        $otherAsset = Asset::factory()->create(['custodian_user_id' => $otherGuru->id]);
        MaintenanceReport::factory()->create(['asset_id' => $ownedAsset->id, 'asset_code' => 'RUSAK-SAYA', 'reported_by' => $otherGuru->id]);
        MaintenanceReport::factory()->create(['asset_id' => $otherAsset->id, 'asset_code' => 'RUSAK-LAIN', 'reported_by' => $otherGuru->id]);

        Livewire::test(OperationalAlerts::class)
            ->assertSee('PJM-SAYA')
            ->assertDontSee('PJM-LAIN')
            ->assertSee('JADWAL-SAYA')
            ->assertDontSee('JADWAL-LAIN')
            ->assertSee('RUSAK-SAYA')
            ->assertDontSee('RUSAK-LAIN');

        $this->assertSame('1', LoanResource::getNavigationBadge());
        $this->assertSame('1', MaintenanceScheduleResource::getNavigationBadge());
        $this->assertSame('1', MaintenanceReportResource::getNavigationBadge());
    }

    public function test_dashboard_contains_operational_alert_widget(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));

        $this->get(route('filament.admin.pages.dashboard'))
            ->assertOk()
            ->assertSee('app.filament.widgets.operational-alerts', false);
    }
}
