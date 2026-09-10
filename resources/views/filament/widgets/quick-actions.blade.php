<x-filament-widgets::widget>
    <div>
        <div class="mb-4">
            <h2 class="text-base font-semibold text-gray-950 dark:text-white">
                Aksi Cepat
            </h2>

            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Akses fitur yang paling sering digunakan.
            </p>
        </div>

        <div class="grid gap-4 md:grid-cols-2">

            {{-- Scan QR --}}
            <div
                class="group relative overflow-hidden rounded-2xl border
                       border-gray-200 bg-white p-6 shadow-sm
                       transition hover:shadow-md
                       dark:border-white/10 dark:bg-gray-900"
            >
                <div class="flex items-start gap-4">

                    <div
                        class="flex shrink-0 items-center justify-center rounded-xl
                               bg-primary-50 text-primary-600
                               dark:bg-primary-500/10 dark:text-primary-400"
                        style="width: 48px; height: 48px;"
                    >
                        <x-heroicon-o-qr-code
                            style="width: 24px; height: 24px;"
                        />
                    </div>

                    <div>
                        <h3 class="font-semibold text-gray-950 dark:text-white">
                            Scan QR
                        </h3>

                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            Scan label menggunakan kamera HP untuk menemukan aset.
                        </p>

                        <span class="mt-4 inline-flex text-sm font-semibold text-primary-600 dark:text-primary-400">
                            Segera tersedia
                        </span>
                    </div>

                </div>
            </div>

            {{-- Tambah Aset --}}
            <a
                href="{{ \App\Filament\Resources\Assets\AssetResource::getUrl('create') }}"
                class="group relative overflow-hidden rounded-2xl border
                       border-gray-200 bg-white p-6 shadow-sm
                       transition hover:shadow-md
                       dark:border-white/10 dark:bg-gray-900"
            >
                <div class="flex items-start gap-4">

                    <div
                        class="flex shrink-0 items-center justify-center rounded-xl
                               bg-primary-50 text-primary-600
                               dark:bg-primary-500/10 dark:text-primary-400"
                        style="width: 48px; height: 48px;"
                    >
                        <x-heroicon-o-plus
                            style="width: 24px; height: 24px;"
                        />
                    </div>

                    <div>
                        <h3 class="font-semibold text-gray-950 dark:text-white">
                            Tambah Aset
                        </h3>

                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            Daftarkan perangkat baru ke inventaris GearTrack.
                        </p>

                        <span class="mt-4 inline-flex items-center gap-1 text-sm font-semibold text-primary-600 dark:text-primary-400">
                            Tambah sekarang

                            <x-heroicon-m-arrow-right
                                style="width: 16px; height: 16px;"
                            />
                        </span>
                    </div>

                </div>
            </a>

        </div>
    </div>
</x-filament-widgets::widget>