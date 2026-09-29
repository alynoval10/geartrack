<x-filament-widgets::widget>
    <x-filament::section heading="Notifikasi & Pengingat" description="Pekerjaan yang perlu segera diperiksa berdasarkan data terbaru GearTrack.">
        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ($cards as $card)
                <a href="{{ $card['url'] }}" class="rounded-xl border border-gray-200 p-4 transition hover:border-primary-500 dark:border-gray-700">
                    <div class="flex items-center justify-between gap-3">
                        <span class="text-sm text-gray-600 dark:text-gray-300">{{ $card['label'] }}</span>
                        <x-filament::badge :color="$card['tone']">{{ $card['value'] }}</x-filament::badge>
                    </div>
                </a>
            @endforeach
        </div>

        <div class="mt-5 grid gap-4 xl:grid-cols-2">
            <div class="rounded-xl border border-gray-200 p-4 dark:border-gray-700">
                <h3 class="font-semibold">Pinjaman Terlambat</h3>
                <div class="mt-3 grid gap-2">
                    @forelse ($overdueLoans as $loan)
                        <a href="{{ \App\Filament\Resources\Loans\LoanResource::getUrl('view', ['record' => $loan]) }}" class="flex items-center justify-between gap-4 rounded-lg bg-red-50 p-3 text-sm dark:bg-red-950">
                            <span><strong>{{ $loan->code }}</strong> · {{ $loan->borrower_name }}</span>
                            <span>{{ $loan->due_date->diffInDays(today()) }} hari</span>
                        </a>
                    @empty
                        <p class="text-sm text-gray-500">Tidak ada pinjaman terlambat.</p>
                    @endforelse
                </div>
            </div>

            <div class="rounded-xl border border-gray-200 p-4 dark:border-gray-700">
                <h3 class="font-semibold">Jadwal Perawatan</h3>
                <div class="mt-3 grid gap-2">
                    @forelse ($dueSchedules as $schedule)
                        <a href="{{ \App\Filament\Resources\MaintenanceSchedules\MaintenanceScheduleResource::getUrl('view', ['record' => $schedule]) }}" class="flex items-center justify-between gap-4 rounded-lg bg-amber-50 p-3 text-sm dark:bg-amber-950">
                            <span><strong>{{ $schedule->asset_code }}</strong> · {{ $schedule->title }}</span>
                            <span>{{ $schedule->due_date->format('d/m/Y') }}</span>
                        </a>
                    @empty
                        <p class="text-sm text-gray-500">Tidak ada jadwal dalam tujuh hari ke depan.</p>
                    @endforelse
                </div>
            </div>

            <div class="rounded-xl border border-gray-200 p-4 dark:border-gray-700">
                <h3 class="font-semibold">Stock Opname Belum Selesai</h3>
                <div class="mt-3 grid gap-2">
                    @forelse ($openStockTakes as $stockTake)
                        <a href="{{ \App\Filament\Resources\StockTakes\StockTakeResource::getUrl('view', ['record' => $stockTake]) }}" class="flex items-center justify-between gap-4 rounded-lg bg-blue-50 p-3 text-sm dark:bg-blue-950">
                            <span><strong>{{ $stockTake->name }}</strong> · {{ $stockTake->location_name ?: 'Semua lokasi' }}</span>
                            <span>{{ $stockTake->unchecked_items_count }}/{{ $stockTake->items_count }} belum diperiksa</span>
                        </a>
                    @empty
                        <p class="text-sm text-gray-500">Tidak ada stock opname aktif.</p>
                    @endforelse
                </div>
            </div>

            <div class="rounded-xl border border-gray-200 p-4 dark:border-gray-700">
                <h3 class="font-semibold">Kerusakan / Perawatan Belum Selesai</h3>
                <div class="mt-3 grid gap-2">
                    @forelse ($openReports as $report)
                        <a href="{{ \App\Filament\Resources\MaintenanceReports\MaintenanceReportResource::getUrl('view', ['record' => $report]) }}" class="flex items-center justify-between gap-4 rounded-lg bg-red-50 p-3 text-sm dark:bg-red-950">
                            <span><strong>{{ $report->asset_code }}</strong> · {{ $report->title }}</span>
                            <x-filament::badge :color="$report->status === 'open' ? 'danger' : 'warning'">{{ \App\Models\MaintenanceReport::STATUSES[$report->status] ?? $report->status }}</x-filament::badge>
                        </a>
                    @empty
                        <p class="text-sm text-gray-500">Tidak ada laporan yang menunggu penanganan.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
