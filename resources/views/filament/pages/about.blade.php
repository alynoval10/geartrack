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
                <p>• Stok barang habis pakai</p>
                <p>• Pengelolaan siswa lintas tahun ajaran</p>
                <p>• Lokasi bertingkat dengan pin Google Maps</p>
                <p>• Laporan dan backup data</p>
            </div>
        </x-filament::section>

        <x-filament::section heading="Riwayat Versi">
            <div class="grid gap-6">
                @foreach ($versionHistory as $release)
                    <article class="border-s-2 border-primary-600 ps-4">
                        <div class="flex flex-wrap items-baseline justify-between gap-2">
                            <h3 class="font-semibold text-gray-950 dark:text-white">Versi {{ $release['version'] }}</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ $release['released_at'] }}</p>
                        </div>
                        <ul class="mt-2 list-inside list-disc space-y-1 text-sm text-gray-600 dark:text-gray-400">
                            @foreach ($release['changes'] as $change)
                                <li>{{ $change }}</li>
                            @endforeach
                        </ul>
                    </article>
                @endforeach
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
