<x-filament-panels::page>
    @if ($errors->any())
        <div role="alert" class="rounded-xl bg-red-50 p-4 text-sm text-red-800 dark:bg-red-950 dark:text-red-200">{{ $errors->first() }}</div>
    @endif
    <x-filament::section heading="1. Isi template Excel" description="Gunakan template agar kolom sesuai. Sheet referensi berisi kategori, merek, dan lokasi yang tersedia.">
        <div class="grid gap-4">
            <p class="text-sm text-gray-600 dark:text-gray-400">Isi sheet Aset. Kode aset, nama aset, dan kode kategori wajib diisi. Kode aset serta nomor seri tidak boleh berulang. Impor hanya menambah aset baru; data lama tidak diganti.</p>
            <div><x-filament::button tag="a" color="gray" href="{{ route('asset-import.template') }}">Unduh Template Excel</x-filament::button></div>
        </div>
    </x-filament::section>
    <x-filament::section heading="2. Unggah dan periksa" description="Maksimal 500 aset per file .xlsx (5 MB). Data belum disimpan pada tahap ini.">
        <form method="POST" action="{{ route('asset-import.preview') }}" enctype="multipart/form-data" class="grid gap-4">
            @csrf
            <label for="import-file" class="text-sm font-medium">File Excel aset</label>
            <input id="import-file" name="file" type="file" accept=".xlsx" required class="w-full rounded-lg border border-gray-300 p-3 text-sm dark:border-gray-700">
            <div><x-filament::button type="submit">Periksa File</x-filament::button></div>
        </form>
    </x-filament::section>
    @if ($draft)
        <x-filament::section heading="3. Tinjau dan simpan">
            <div class="grid gap-5">
                <p class="text-sm"><strong>{{ $draft['count'] }} aset</strong> diperiksa. <strong>{{ count($draft['errors']) }} baris</strong> perlu diperbaiki. Pratinjau berlaku selama 30 menit.</p>
                @if ($draft['errors'])
                    <p class="rounded-lg bg-amber-50 p-3 text-sm text-amber-900 dark:bg-amber-950 dark:text-amber-200">Belum ada data disimpan. Perbaiki baris yang ditandai di Excel, lalu unggah kembali.</p>
                @endif
                <div class="max-h-96 overflow-auto rounded-lg border border-gray-200 dark:border-gray-700">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-gray-100 dark:bg-gray-800"><tr>@foreach (['Baris', 'Kode', 'Nama', 'Kategori', 'Merek / Lokasi', 'Kondisi / Status', 'Pengadaan / Harga', 'Model / Seri', 'Penanggung Jawab / Dana / Catatan', 'Pemeriksaan'] as $header)<th class="p-3">{{ $header }}</th>@endforeach</tr></thead>
                        <tbody>
                            @foreach ($draft['rows'] as $number => $row)
                                <tr class="border-t border-gray-200 align-top dark:border-gray-700">
                                    <td class="p-3">{{ $number }}</td><td class="p-3">{{ $row['kode_aset'] }}</td><td class="p-3">{{ $row['nama_aset'] }}</td><td class="p-3">{{ $row['kode_kategori'] }}</td>
                                    <td class="p-3">{{ $row['nama_merek'] ?: '—' }}<br>{{ $row['kode_lokasi'] ?: '—' }}</td>
                                    <td class="p-3">{{ $row['kondisi'] ?: 'Baik' }}<br>{{ $row['status'] ?: 'Tersedia' }}</td>
                                    <td class="p-3">{{ $row['tanggal_pengadaan'] ?: '—' }}<br>{{ $row['harga_perolehan'] === '' ? '—' : $row['harga_perolehan'] }}</td>
                                    <td class="p-3">{{ $row['model'] ?: '—' }}<br>{{ $row['nomor_seri'] ?: '—' }}</td>
                                    <td class="p-3">{{ $row['penanggung_jawab'] ?: '—' }}<br>{{ $row['sumber_dana'] ?: '—' }}<br>{{ $row['catatan'] ?: '—' }}</td>
                                    <td class="min-w-48 p-3">@forelse ($draft['errors'][$number] ?? [] as $error)<p class="text-red-600 dark:text-red-400">{{ $error }}</p>@empty<span class="text-green-700 dark:text-green-400">Siap diimpor</span>@endforelse</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if (! $draft['errors'])
                    <form method="POST" action="{{ route('asset-import.store') }}" class="grid gap-4">
                        @csrf
                        <input type="hidden" name="token" value="{{ $draft['token'] }}">
                        <label class="flex items-start gap-3 text-sm"><input type="checkbox" name="confirm" value="1" required class="mt-1">Saya sudah memeriksa data dan setuju menambahkan {{ $draft['count'] }} aset baru.</label>
                        <div><x-filament::button type="submit">Simpan {{ $draft['count'] }} Aset</x-filament::button></div>
                    </form>
                @endif
                <form method="POST" action="{{ route('asset-import.cancel') }}">@csrf<x-filament::button type="submit" color="gray">Batalkan Pratinjau</x-filament::button></form>
            </div>
        </x-filament::section>
    @endif
</x-filament-panels::page>
