<?php

namespace Tests\Feature;

use App\Filament\Resources\MaintenanceSchedules\MaintenanceScheduleResource;
use App\Filament\Resources\MaintenanceSchedules\Pages\CreateMaintenanceSchedule;
use App\Filament\Resources\MaintenanceSchedules\Pages\EditMaintenanceSchedule;
use App\Filament\Resources\MaintenanceSchedules\Pages\ViewMaintenanceSchedule;
use App\Filament\Widgets\MaintenanceReminders;
use App\Models\Asset;
use App\Models\MaintenanceSchedule;
use App\Models\User;
use App\Services\MaintenanceScheduleService;
use App\Services\MaintenanceService;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class MaintenanceScheduleTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['app.env' => 'local']);
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    private function data(Asset $asset, array $extra = []): array
    {
        return [...[
            'asset_id' => $asset->id, 'title' => 'Bersihkan kipas', 'due_date' => today()->toDateString(),
            'interval_days' => 30, 'is_active' => true, 'technician' => 'Teknisi Lab',
        ], ...$extra];
    }

    public function test_operator_can_create_edit_and_start_schedule_from_ui(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $asset = Asset::factory()->create(['status' => 'available', 'condition' => 'good']);

        Livewire::test(CreateMaintenanceSchedule::class)->fillForm($this->data($asset))
            ->call('create')->assertHasNoFormErrors();

        $schedule = MaintenanceSchedule::sole();
        Livewire::test(EditMaintenanceSchedule::class, ['record' => $schedule->id])
            ->fillForm(['technician' => 'Teknisi Baru'])->call('save')->assertHasNoFormErrors();
        $this->assertSame('Teknisi Baru', $schedule->fresh()->technician);
        Livewire::test(ViewMaintenanceSchedule::class, ['record' => $schedule->id])
            ->callAction('createReport')->assertHasNoActionErrors();
        $this->assertDatabaseHas('maintenance_reports', ['maintenance_schedule_id' => $schedule->id, 'status' => 'open']);
        $this->assertFalse(MaintenanceScheduleResource::canEdit($schedule));
    }

    public function test_resolving_recurring_report_advances_from_completion_day_once(): void
    {
        $this->freezeTime();
        $user = User::factory()->create();
        $asset = Asset::factory()->create(['status' => 'available', 'condition' => 'good']);
        $schedule = app(MaintenanceScheduleService::class)->save($this->data($asset, [
            'due_date' => today()->subDays(10)->toDateString(),
        ]), $user);
        $report = app(MaintenanceScheduleService::class)->createReport($schedule, $user);
        $this->assertSame(today()->subDays(10)->toDateString(), $schedule->fresh()->due_date->toDateString());
        app(MaintenanceService::class)->update($report, 'started', ['notes' => 'Mulai', 'technician' => 'Teknisi'], $user);

        app(MaintenanceService::class)->update($report, 'resolved', [
            'notes' => 'Selesai dibersihkan', 'condition' => 'good', 'asset_status' => 'available',
        ], $user);

        $this->assertSame(today()->addDays(30)->toDateString(), $schedule->fresh()->due_date->toDateString());
        $this->assertNotNull($schedule->fresh()->last_completed_at);
        $this->assertTrue($schedule->fresh()->is_active);
        $this->expectException(ValidationException::class);
        app(MaintenanceService::class)->update($report, 'resolved', [
            'notes' => 'Ulang', 'condition' => 'good', 'asset_status' => 'available',
        ], $user);
    }

    public function test_one_time_schedule_deactivates_after_completion(): void
    {
        $user = User::factory()->create();
        $asset = Asset::factory()->create(['status' => 'available', 'condition' => 'good']);
        $schedule = app(MaintenanceScheduleService::class)->save($this->data($asset, ['interval_days' => null]), $user);
        $report = app(MaintenanceScheduleService::class)->createReport($schedule, $user);
        app(MaintenanceService::class)->update($report, 'started', ['notes' => 'Mulai', 'technician' => 'Teknisi'], $user);

        app(MaintenanceService::class)->update($report, 'resolved', [
            'notes' => 'Selesai', 'condition' => 'good', 'asset_status' => 'available',
        ], $user);

        $this->assertFalse($schedule->fresh()->is_active);
        $this->assertNotNull($schedule->fresh()->last_completed_at);
    }

    public function test_cancelled_report_does_not_advance_schedule_and_can_be_reopened_as_new_report(): void
    {
        $user = User::factory()->create();
        $asset = Asset::factory()->create(['status' => 'available', 'condition' => 'good']);
        $schedule = app(MaintenanceScheduleService::class)->save($this->data($asset), $user);
        $report = app(MaintenanceScheduleService::class)->createReport($schedule, $user);

        app(MaintenanceService::class)->update($report, 'cancelled', ['notes' => 'Jadwal ditunda'], $user);

        $this->assertSame(today()->toDateString(), $schedule->fresh()->due_date->toDateString());
        $this->assertNull($schedule->fresh()->last_completed_at);
        $newReport = app(MaintenanceScheduleService::class)->createReport($schedule, $user);
        $this->assertNotSame($report->id, $newReport->id);
    }

    public function test_duplicate_report_for_schedule_is_rejected(): void
    {
        $user = User::factory()->create();
        $asset = Asset::factory()->create(['status' => 'available', 'condition' => 'good']);
        $schedule = app(MaintenanceScheduleService::class)->save($this->data($asset), $user);
        app(MaintenanceScheduleService::class)->createReport($schedule, $user);

        $this->expectException(ValidationException::class);
        app(MaintenanceScheduleService::class)->createReport($schedule, $user);
    }

    public function test_schedule_cannot_be_changed_while_report_is_open(): void
    {
        $user = User::factory()->create();
        $asset = Asset::factory()->create(['status' => 'available', 'condition' => 'good']);
        $schedule = app(MaintenanceScheduleService::class)->save($this->data($asset), $user);
        app(MaintenanceScheduleService::class)->createReport($schedule, $user);

        $this->expectException(ValidationException::class);
        app(MaintenanceScheduleService::class)->save($this->data($asset, ['interval_days' => 90]), $user, $schedule);
    }

    public function test_reminders_include_overdue_today_and_next_week_but_exclude_inactive_deleted_and_later_assets(): void
    {
        $this->freezeTime();
        $this->actingAs(User::factory()->create());
        $overdue = MaintenanceSchedule::factory()->create(['due_date' => today()->subDay()]);
        $today = MaintenanceSchedule::factory()->create(['due_date' => today()]);
        $soon = MaintenanceSchedule::factory()->create(['due_date' => today()->addDays(7)]);
        $later = MaintenanceSchedule::factory()->create(['due_date' => today()->addDays(8)]);
        $inactive = MaintenanceSchedule::factory()->create(['due_date' => today(), 'is_active' => false]);
        $deleted = MaintenanceSchedule::factory()->create(['due_date' => today()]);
        $deleted->asset->delete();

        Livewire::test(MaintenanceReminders::class)->assertCanSeeTableRecords([$overdue, $today, $soon])
            ->assertCanNotSeeTableRecords([$later, $inactive, $deleted]);

        $this->assertSame('2', MaintenanceScheduleResource::getNavigationBadge());
        $this->assertSame('Lewat Jadwal', $overdue->reminderLabel());
        $this->assertSame('Hari Ini', $today->reminderLabel());
    }

    public function test_inactive_schedule_and_borrowed_asset_cannot_start_report(): void
    {
        $user = User::factory()->create();
        $schedule = MaintenanceSchedule::factory()->create(['is_active' => false]);
        try {
            app(MaintenanceScheduleService::class)->createReport($schedule, $user);
            $this->fail('Inactive schedule was started.');
        } catch (ValidationException) {
            $this->assertDatabaseCount('maintenance_reports', 0);
        }
        $schedule->update(['is_active' => true]);
        $schedule->asset->update(['status' => 'borrowed']);

        $this->expectException(ValidationException::class);
        app(MaintenanceScheduleService::class)->createReport($schedule, $user);
    }

    public function test_retirement_stops_recurring_reminder(): void
    {
        $user = User::factory()->create();
        $asset = Asset::factory()->create(['status' => 'available', 'condition' => 'good']);
        $schedule = app(MaintenanceScheduleService::class)->save($this->data($asset), $user);
        $report = app(MaintenanceScheduleService::class)->createReport($schedule, $user);
        app(MaintenanceService::class)->update($report, 'started', ['notes' => 'Periksa', 'technician' => 'Teknisi'], $user);

        app(MaintenanceService::class)->update($report, 'resolved', [
            'notes' => 'Tidak dapat diperbaiki', 'condition' => 'major_damage', 'asset_status' => 'retired',
        ], $user);

        $this->assertFalse($schedule->fresh()->is_active);
        $this->assertSame('retired', $asset->fresh()->status);
    }

    public function test_invalid_repeat_interval_and_guest_access_are_rejected(): void
    {
        $this->get(MaintenanceScheduleResource::getUrl('index'))->assertRedirect();
        $user = User::factory()->create();
        $asset = Asset::factory()->create();

        $this->expectException(ValidationException::class);
        app(MaintenanceScheduleService::class)->save($this->data($asset, ['interval_days' => 0]), $user);
    }
}
