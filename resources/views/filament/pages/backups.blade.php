<x-filament-panels::page>
    <div class="grid gap-6">
        @if (! $canManageBackups)
            <x-filament::section heading="Akses backup belum aktif">
                <div class="grid gap-3 text-sm leading-6">
                    @if (! $backupAccessConfigured)
                        <p>Administrator backup belum ditentukan. Minta pengelola server mengaktifkan akses backup untuk akun Anda.</p>
                    @else
                        <p>Akun Anda belum memiliki izin untuk mengelola backup. Hubungi pengelola server untuk meminta akses.</p>
                    @endif
                    <p>Email akun Anda: <strong>{{ auth()->user()->email }}</strong></p>
                    <p>Setelah akses diaktifkan, muat ulang halaman ini.</p>
                </div>
            </x-filament::section>
        @else
            @if ($errors->any())
                <div role="alert" class="rounded-xl border border-red-300 bg-red-50 p-4 text-sm text-red-800 dark:border-red-800 dark:bg-red-950 dark:text-red-200">
                    @foreach ($errors->all() as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <x-filament::section heading="Simpan salinan data" description="Amankan data inventaris dan foto perangkat dalam satu file backup.">
                <div class="flex flex-col gap-5 sm:flex-row sm:items-center sm:justify-between">
                    <p class="max-w-2xl text-sm leading-6 text-gray-600 dark:text-gray-400">Backup mencakup akun, aset, paket perangkat, peminjaman, mutasi, stock opname, dan riwayat perawatan. Unduh salinannya setelah selesai.</p>
                    <form method="POST" action="{{ route('backups.store') }}" class="shrink-0">
                        @csrf
                        <x-filament::button type="submit" icon="heroicon-o-arrow-down-tray">Buat Backup Sekarang</x-filament::button>
                    </form>
                </div>
            </x-filament::section>

            <x-filament::section heading="Backup tersimpan" description="Unduh salinan atau hapus file yang sudah tidak diperlukan.">
                <div class="grid gap-4">
                    @forelse ($archives as $archive)
                        <article class="rounded-xl border border-gray-200 p-4 dark:border-gray-700">
                            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                                <div class="min-w-0 grid gap-2">
                                    <h3 class="text-sm font-semibold break-all">{{ $archive['name'] }}</h3>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ $archive['created_at'] }} &middot; {{ number_format($archive['size'] / 1024 / 1024, 2) }} MB</p>
                                </div>
                                <x-filament::button tag="a" size="sm" color="gray" icon="heroicon-o-arrow-down-tray" href="{{ route('backups.download', ['name' => $archive['name']]) }}">Unduh</x-filament::button>
                            </div>
                            <details class="mt-4 border-t border-gray-200 pt-3 dark:border-gray-700">
                                <summary class="cursor-pointer text-sm font-medium text-red-600 dark:text-red-400">Hapus backup</summary>
                                <form method="POST" action="{{ route('backups.destroy', ['name' => $archive['name']]) }}" class="mt-4 grid max-w-md gap-3">
                                    @csrf
                                    @method('DELETE')
                                    <p class="text-sm text-gray-500">File ini akan dihapus dari server. Data inventaris tetap tersedia.</p>
                                    <label for="delete-password-{{ $loop->index }}" class="text-sm font-medium">Kata sandi akun Anda</label>
                                    <x-filament::input.wrapper>
                                        <x-filament::input id="delete-password-{{ $loop->index }}" type="password" name="password" required autocomplete="current-password" />
                                    </x-filament::input.wrapper>
                                    <div><x-filament::button type="submit" color="danger" size="sm">Hapus File Ini</x-filament::button></div>
                                </form>
                            </details>
                        </article>
                    @empty
                        <div class="rounded-xl border border-dashed border-gray-300 p-8 text-center dark:border-gray-700">
                            <p class="font-medium">Belum ada backup</p>
                            <p class="mt-2 text-sm text-gray-500">Buat salinan pertama menggunakan tombol di atas.</p>
                        </div>
                    @endforelse
                </div>
            </x-filament::section>

            <x-filament::section heading="Pulihkan dari backup" description="Ganti data saat ini dengan salinan yang tersimpan.">
                <div class="grid gap-6 lg:grid-cols-2">
                    <div class="grid content-start gap-4 text-sm leading-6 text-gray-600 dark:text-gray-400">
                        <div class="rounded-xl bg-amber-50 p-4 text-amber-900 dark:bg-amber-950 dark:text-amber-200">
                            <p class="font-semibold">Data saat ini akan diganti</p>
                            <p class="mt-2">Termasuk akun dan kata sandi. Backup pengaman dibuat sebelum pemulihan. Semua pengguna perlu login kembali.</p>
                        </div>
                        <p>Pilih waktu saat aplikasi tidak digunakan. Akun Anda harus ada dalam backup. Batas file {{ $maxUploadMb }} MB; batas unggah server juga berlaku.</p>
                        <details>
                            <summary class="cursor-pointer font-medium">Ketentuan pemulihan</summary>
                            <div class="mt-3 grid gap-3">
                                <p>Backup hanya dapat dipulihkan dengan kunci aplikasi dan struktur database yang sesuai. Konfigurasi server dan APP_KEY tidak termasuk dalam arsip; simpan terpisah.</p>
                                <p>Hentikan worker antrean dan tugas terjadwal selama restore. Antrean pekerjaan lama dikosongkan, sedangkan foto lama tetap disimpan.</p>
                                <p>Jika foto saat ini hilang, backup pengaman tetap dibuat, tetapi salinan yang tidak lengkap tidak dapat dipulihkan dari halaman ini.</p>
                            </div>
                        </details>
                    </div>
                    <form method="POST" action="{{ route('backups.restore') }}" enctype="multipart/form-data" class="grid content-start gap-4">
                        @csrf
                        <div class="grid gap-2">
                            <label for="restore-file" class="text-sm font-medium">File backup (.zip)</label>
                            <input id="restore-file" type="file" name="backup" accept=".zip,application/zip" required class="block w-full rounded-lg border border-gray-300 p-3 text-sm dark:border-gray-700">
                        </div>
                        <div class="grid gap-2">
                            <label for="restore-password" class="text-sm font-medium">Kata sandi akun Anda</label>
                            <x-filament::input.wrapper><x-filament::input id="restore-password" type="password" name="password" required autocomplete="current-password" /></x-filament::input.wrapper>
                        </div>
                        <div class="grid gap-2">
                            <label for="restore-confirmation" class="text-sm font-medium">Ketik RESTORE untuk menyetujui penggantian data</label>
                            <x-filament::input.wrapper><x-filament::input id="restore-confirmation" type="text" name="confirmation" required pattern="RESTORE" autocomplete="off" /></x-filament::input.wrapper>
                        </div>
                        <div><x-filament::button type="submit" color="danger">Pulihkan Data</x-filament::button></div>
                    </form>
                </div>
            </x-filament::section>
        @endif
    </div>
</x-filament-panels::page>
