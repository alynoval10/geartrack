<x-filament-panels::page>
    <style>
        .backup-stack { display: grid; gap: 24px; }
        .backup-stack p { line-height: 1.65; }
        .backup-form { display: grid; gap: 16px; }
        .backup-form label { display: grid; gap: 6px; font-weight: 600; }
        .backup-input { width: 100%; padding: 10px 12px; border: 1px solid #94a3b8; border-radius: 8px; color: inherit; background: transparent; }
        .backup-scroll { overflow-x: auto; }
        .backup-table { width: 100%; border-collapse: collapse; text-align: left; }
        .backup-table th, .backup-table td { padding: 12px; border-bottom: 1px solid #94a3b855; }
        .backup-filename { max-width: 420px; overflow-wrap: anywhere; }
        .backup-message { padding: 14px; border: 1px solid #0ea5e9; border-radius: 10px; }
        .backup-error { border-color: #ef4444; }
        .backup-delete { min-width: 220px; }
        .backup-delete summary { cursor: pointer; }
    </style>

    <div class="backup-stack">
        @if (session('backup_status'))
            <p class="backup-message" role="status">{{ session('backup_status') }}</p>
        @endif
        @if ($errors->any())
            <div class="backup-message backup-error" role="alert">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <x-filament::section heading="Simpan salinan data">
            <div class="backup-stack">
                <p>Backup berisi database lengkap: akun, aset, paket perangkat, stock opname, riwayat perawatan, serta foto yang terhubung ke aset. Unduh salinannya dan simpan di tempat yang aman.</p>
                <p>Arsip disimpan privat di server. Konfigurasi aplikasi dan APP_KEY tidak dimasukkan. Simpan APP_KEY secara terpisah; restore hanya menerima backup dengan kunci yang sama dan struktur database yang sesuai.</p>
                <form method="POST" action="{{ route('backups.store') }}">
                    @csrf
                    <x-filament::button type="submit">Buat Backup Sekarang</x-filament::button>
                </form>
            </div>
        </x-filament::section>

        <x-filament::section heading="Backup tersimpan">
            <div class="backup-scroll">
                <table class="backup-table">
                    <thead><tr><th>File</th><th>Dibuat</th><th>Ukuran</th><th>Tindakan</th></tr></thead>
                    <tbody>
                        @forelse ($archives as $archive)
                            <tr>
                                <td class="backup-filename">{{ $archive['name'] }}</td>
                                <td>{{ $archive['created_at'] }}</td>
                                <td>{{ number_format($archive['size'] / 1024 / 1024, 2) }} MB</td>
                                <td>
                                    <x-filament::button tag="a" size="sm" color="gray" href="{{ route('backups.download', ['name' => $archive['name']]) }}">Unduh</x-filament::button>
                                    <details class="backup-delete">
                                        <summary>Hapus backup</summary>
                                        <form class="backup-form" method="POST" action="{{ route('backups.destroy', ['name' => $archive['name']]) }}">
                                            @csrf
                                            @method('DELETE')
                                            <label>Kata sandi akun Anda
                                                <input class="backup-input" type="password" name="password" required autocomplete="current-password">
                                            </label>
                                            <x-filament::button type="submit" color="danger" size="sm">Hapus File Ini</x-filament::button>
                                        </form>
                                    </details>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4">Belum ada backup. Buat salinan pertama sebelum melakukan perubahan besar.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-filament::section>

        <x-filament::section heading="Pulihkan dari backup">
            <div class="backup-stack">
                <p>Restore mengganti seluruh database dengan isi backup, termasuk akun dan kata sandi. Backup pengaman dibuat otomatis sebelum perubahan. Semua sesi login dan antrean pekerjaan lama akan dikosongkan; pengguna perlu masuk kembali.</p>
                <p>Akun admin Anda harus ada dalam backup. Jika foto saat ini sudah hilang, backup pengaman tetap menyimpan database dan foto yang tersedia; salinan pengaman yang tidak lengkap tidak dapat dipulihkan dari halaman ini.</p>
                <p>Lakukan saat tidak ada aktivitas pengguna. Hentikan worker antrean / tugas terjadwal selama restore. Foto lama tetap disimpan sebagai pengaman. Batas arsip {{ $maxUploadMb }} MB; batas unggah server juga berlaku.</p>
                <form class="backup-form" method="POST" action="{{ route('backups.restore') }}" enctype="multipart/form-data">
                    @csrf
                    <label>File backup GearTrack (.zip)
                        <input class="backup-input" type="file" name="backup" accept=".zip,application/zip" required>
                    </label>
                    <label>Kata sandi akun Anda saat ini
                        <input class="backup-input" type="password" name="password" required autocomplete="current-password">
                    </label>
                    <label>Ketik RESTORE untuk menyetujui penggantian data
                        <input class="backup-input" type="text" name="confirmation" required pattern="RESTORE" autocomplete="off">
                    </label>
                    <div><x-filament::button type="submit" color="danger">Pulihkan Data</x-filament::button></div>
                </form>
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>

