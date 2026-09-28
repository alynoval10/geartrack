<?php

namespace App\Filament\Widgets;

use App\Filament\Pages\ImportAssets;
use App\Filament\Pages\SchoolSettings;
use App\Filament\Resources\Assets\AssetResource;
use App\Filament\Resources\Categories\CategoryResource;
use App\Filament\Resources\MaintenanceReports\MaintenanceReportResource;
use App\Filament\Resources\StockTakes\StockTakeResource;
use App\Filament\Resources\Users\UserResource;
use App\Models\Asset;
use App\Models\AssetSet;
use App\Models\Category;
use App\Models\Loan;
use App\Models\Location;
use App\Models\MaintenanceReport;
use App\Models\MaintenanceSchedule;
use App\Models\SchoolSetting;
use App\Models\StockTake;
use App\Models\StockTakeItem;
use App\Models\User;
use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Builder;

class UserTasks extends Widget
{
    protected string $view = 'filament.widgets.user-tasks';

    protected static ?int $sort = 0;

    protected int|string|array $columnSpan = 'full';

    protected function getViewData(): array
    {
        /** @var User $user */
        $user = auth()->user();
        $isAdmin = $user->isAdmin();

        $assets = Asset::query();
        $loans = Loan::query()->where('status', 'open');
        $maintenance = MaintenanceReport::query()->whereIn('status', ['open', 'in_progress']);

        if (! $isAdmin) {
            $assets->where('custodian_user_id', $user->id);
            $loans->where('created_by', $user->id);
            $maintenance->where(function (Builder $query) use ($user): void {
                $query->where('reported_by', $user->id)
                    ->orWhereHas('asset', fn (Builder $asset): Builder => $asset->where('custodian_user_id', $user->id));
            });
        }

        // Angka pada kartu mengikuti ruang lingkup user: pribadi untuk guru, global untuk admin.
        $taskCounts = [
            ['label' => 'Aset Tanggung Jawab', 'value' => $assets->clone()->count(), 'url' => AssetResource::getUrl(), 'color' => 'blue'],
            ['label' => 'Pinjaman Aktif', 'value' => $loans->clone()->count(), 'url' => route('filament.admin.resources.loans.index'), 'color' => 'amber'],
            ['label' => 'Perawatan Terbuka', 'value' => $maintenance->clone()->count(), 'url' => MaintenanceReportResource::getUrl(), 'color' => 'red'],
            ['label' => 'Stock Opname Aktif', 'value' => StockTake::where('status', 'open')->count(), 'url' => StockTakeResource::getUrl(), 'color' => 'green'],
        ];

        $school = SchoolSetting::query()->first();
        $adminTimeline = [
            ['title' => 'Isi identitas sekolah', 'description' => 'Lengkapi nama, logo, pejabat, dan tahun ajaran.', 'done' => filled($school?->school_name), 'url' => SchoolSettings::getUrl()],
            ['title' => 'Lengkapi data dasar', 'description' => 'Buat kategori dan lokasi sebelum memasukkan aset.', 'done' => Category::exists() && Location::exists(), 'url' => CategoryResource::getUrl()],
            ['title' => 'Buat akun guru', 'description' => 'Tambahkan guru yang akan menjadi penanggung jawab aset.', 'done' => User::where('role', 'guru')->exists(), 'url' => UserResource::getUrl()],
            ['title' => 'Masukkan inventaris', 'description' => 'Tambah aset satu per satu atau gunakan impor Excel.', 'done' => Asset::exists(), 'url' => ImportAssets::getUrl()],
            ['title' => 'Susun paket perangkat', 'description' => 'Gabungkan PC, monitor, dan perangkat tambahan.', 'done' => AssetSet::exists(), 'url' => route('filament.admin.resources.asset-sets.index')],
            ['title' => 'Cetak QR dan lakukan stock opname', 'description' => 'Tempel label QR lalu periksa aset dari ponsel.', 'done' => StockTake::exists(), 'url' => route('qr.scan')],
        ];
        $guruTimeline = [
            ['title' => 'Periksa aset tanggung jawab', 'description' => 'Pastikan nama, lokasi, kondisi, dan paket perangkat sudah benar.', 'done' => $assets->clone()->exists(), 'url' => AssetResource::getUrl()],
            ['title' => 'Gunakan pemindai QR', 'description' => 'Pindai label untuk membuka detail aset dari ponsel.', 'done' => $assets->clone()->exists(), 'url' => route('qr.scan')],
            ['title' => 'Isi stock opname', 'description' => 'Tandai aset ditemukan, hilang, atau berpindah.', 'done' => StockTakeItem::where('checked_by', $user->id)->exists(), 'url' => StockTakeResource::getUrl()],
            ['title' => 'Laporkan kerusakan', 'description' => 'Buat laporan saat perangkat memerlukan pemeriksaan atau perawatan.', 'done' => MaintenanceReport::where('reported_by', $user->id)->exists(), 'url' => MaintenanceReportResource::getUrl('create')],
            ['title' => 'Pantau pinjaman', 'description' => 'Periksa perangkat yang belum kembali dan tanggal jatuh temponya.', 'done' => Loan::where('created_by', $user->id)->exists(), 'url' => route('filament.admin.resources.loans.index')],
        ];

        return [
            'isAdmin' => $isAdmin,
            'taskCounts' => $taskCounts,
            'assignedAssets' => $assets->clone()->with('location')->latest()->limit(5)->get(),
            'overdueLoans' => $isAdmin ? Loan::where('status', 'open')->whereDate('due_date', '<', today())->count() : 0,
            'dueMaintenance' => $isAdmin ? MaintenanceSchedule::upcoming()->count() : 0,
            'locationSummaries' => $isAdmin ? Location::withCount('assets')->orderByDesc('assets_count')->limit(5)->get() : collect(),
            'timeline' => $isAdmin ? $adminTimeline : $guruTimeline,
        ];
    }
}
