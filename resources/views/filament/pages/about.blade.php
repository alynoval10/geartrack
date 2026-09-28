<x-filament-panels::page>
    <div class="mx-auto grid w-full max-w-3xl gap-6">
        <x-filament::section>
            <div class="grid gap-6 text-center">
                <div class="mx-auto flex size-20 items-center justify-center rounded-2xl bg-primary-600 text-2xl font-bold text-white shadow-lg">
                    GT
                </div>
                <div class="grid gap-2">
                    <h2 class="text-2xl font-bold tracking-tight">GearTrack</h2>
                    <p class="text-sm leading-6 text-gray-500 dark:text-gray-400">
                        Sistem pengelolaan inventaris perangkat berbasis QR untuk pencatatan, pemantauan, dan pelaporan aset sekolah.
                    </p>
                </div>
            </div>
        </x-filament::section>

        <div class="grid gap-4 sm:grid-cols-2">
            <x-filament::section heading="Versi Aplikasi">
                <p class="text-xl font-semibold">{{ $version }}</p>
            </x-filament::section>

            <x-filament::section heading="Pembuat">
                <p class="text-xl font-semibold">{{ $creator }}</p>
            </x-filament::section>
        </div>

        <x-filament::section heading="Fungsi Utama">
            <div class="grid gap-3 text-sm text-gray-600 sm:grid-cols-2 dark:text-gray-400">
                <p>• Inventaris dan paket perangkat</p>
                <p>• Label serta pemindaian QR</p>
                <p>• Stock opname aset</p>
                <p>• Peminjaman dan mutasi perangkat</p>
                <p>• Kerusakan serta riwayat perawatan</p>
                <p>• Laporan dan backup data</p>
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
