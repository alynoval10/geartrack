<x-filament-panels::page>
    @if (session('school_settings_status'))
        <div class="rounded-xl bg-green-50 p-4 text-sm font-medium text-green-800 dark:bg-green-950 dark:text-green-200">
            {{ session('school_settings_status') }}
        </div>
    @endif

    <form method="POST" action="{{ route('school-settings.update') }}" enctype="multipart/form-data" class="grid gap-6">
        @csrf
        <x-filament::section heading="Identitas sekolah" description="Informasi ini tampil pada laporan dan berita acara.">
            <div class="grid gap-5 md:grid-cols-2">
                <label class="grid gap-2 text-sm font-medium md:col-span-2">Nama sekolah
                    <x-filament::input.wrapper><x-filament::input name="school_name" value="{{ old('school_name', $setting->school_name) }}" required /></x-filament::input.wrapper>
                    @error('school_name')<span class="text-sm text-danger-600">{{ $message }}</span>@enderror
                </label>
                <label class="grid gap-2 text-sm font-medium md:col-span-2">Alamat
                    <x-filament::input.wrapper><textarea name="address" rows="3" class="block w-full border-none bg-transparent px-3 py-2 text-sm focus:ring-0">{{ old('address', $setting->address) }}</textarea></x-filament::input.wrapper>
                </label>
                <label class="grid gap-2 text-sm font-medium">Tahun ajaran
                    <x-filament::input.wrapper><x-filament::input name="academic_year" value="{{ old('academic_year', $setting->academic_year) }}" placeholder="2026/2027" /></x-filament::input.wrapper>
                </label>
                <label class="grid gap-2 text-sm font-medium">Logo sekolah (maks. 2 MB)
                    <input type="file" name="logo" accept="image/*" class="block w-full text-sm">
                </label>
            </div>
        </x-filament::section>

        @foreach ([
            ['Kepala sekolah', 'principal', 'Nama kepala sekolah', 'NIP kepala sekolah'],
            ['Pengurus barang', 'asset_manager', 'Nama pengurus barang', 'NIP pengurus barang'],
            ['Penandatangan laporan', 'report_signer', 'Nama penandatangan', 'NIP penandatangan'],
        ] as [$heading, $prefix, $nameLabel, $nipLabel])
            <x-filament::section :heading="$heading">
                <div class="grid gap-5 md:grid-cols-2">
                    <label class="grid gap-2 text-sm font-medium">{{ $nameLabel }}
                        <x-filament::input.wrapper><x-filament::input name="{{ $prefix }}_name" value="{{ old($prefix.'_name', $setting->{$prefix.'_name'}) }}" /></x-filament::input.wrapper>
                    </label>
                    <label class="grid gap-2 text-sm font-medium">{{ $nipLabel }}
                        <x-filament::input.wrapper><x-filament::input name="{{ $prefix }}_nip" value="{{ old($prefix.'_nip', $setting->{$prefix.'_nip'}) }}" /></x-filament::input.wrapper>
                    </label>
                    @if ($prefix === 'report_signer')
                        <label class="grid gap-2 text-sm font-medium md:col-span-2">Jabatan penandatangan
                            <x-filament::input.wrapper><x-filament::input name="report_signer_title" value="{{ old('report_signer_title', $setting->report_signer_title) }}" placeholder="Wakil Kepala Sekolah Bidang Sarana Prasarana" /></x-filament::input.wrapper>
                        </label>
                    @endif
                </div>
            </x-filament::section>
        @endforeach

        <x-filament::section heading="Ukuran label QR" description="Ukuran ini digunakan oleh Cetak Label QR untuk printer label. Cetak Label QR A4 tetap menggunakan kertas HVS A4.">
            <div class="grid gap-5 md:grid-cols-2">
                <label class="grid gap-2 text-sm font-medium">Lebar label (mm)
                    <x-filament::input.wrapper>
                        <x-filament::input type="number" name="qr_label_width_mm" min="30" max="100" value="{{ old('qr_label_width_mm', $setting->qr_label_width_mm) }}" required />
                    </x-filament::input.wrapper>
                    @error('qr_label_width_mm')<span class="text-sm text-danger-600">{{ $message }}</span>@enderror
                </label>
                <label class="grid gap-2 text-sm font-medium">Tinggi label (mm)
                    <x-filament::input.wrapper>
                        <x-filament::input type="number" name="qr_label_height_mm" min="20" max="100" value="{{ old('qr_label_height_mm', $setting->qr_label_height_mm) }}" required />
                    </x-filament::input.wrapper>
                    @error('qr_label_height_mm')<span class="text-sm text-danger-600">{{ $message }}</span>@enderror
                </label>
            </div>
            <p class="mt-4 text-sm text-gray-500 dark:text-gray-400">Bawaan NIIMBOT B21 Pro: 50 × 30 mm. Setelah mengganti ukuran, lakukan satu kali cetak percobaan pada skala 100%.</p>
        </x-filament::section>

        <div><x-filament::button type="submit">Simpan Pengaturan</x-filament::button></div>
    </form>
</x-filament-panels::page>
