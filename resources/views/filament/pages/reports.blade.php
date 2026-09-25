<x-filament-panels::page>
    @if ($errors->any())
        <div role="alert" class="rounded-xl bg-red-50 p-4 text-red-800 dark:bg-red-950 dark:text-red-200">{{ $errors->first() }}</div>
    @endif
    <x-filament::section heading="Buat laporan" description="Pilih data dan periode, lalu unduh Excel atau buka tampilan cetak untuk menyimpan PDF.">
        <form method="GET" action="{{ route('reports.export') }}" class="grid gap-6" x-data="{ type: 'assets' }">
            <label class="grid gap-2 text-sm font-medium">Jenis laporan
                <x-filament::input.wrapper><x-filament::input.select name="type" x-model="type">
                    @foreach ($types as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach
                </x-filament::input.select></x-filament::input.wrapper>
            </label>
            <div x-show="type === 'assets'" class="grid gap-4 sm:grid-cols-2">
                @foreach (['location_id' => ['Lokasi', $locations], 'category_id' => ['Kategori', $categories], 'condition' => ['Kondisi', $conditions], 'status' => ['Status', $statuses]] as $name => [$label, $options])
                    <label class="grid gap-2 text-sm font-medium">{{ $label }}
                        <x-filament::input.wrapper><x-filament::input.select :name="$name" x-bind:disabled="type !== 'assets'">
                            <option value="">Semua</option>
                            @foreach ($options as $value => $text)<option value="{{ $value }}">{{ $text }}</option>@endforeach
                        </x-filament::input.select></x-filament::input.wrapper>
                    </label>
                @endforeach
            </div>
            <div x-cloak x-show="type === 'stock_take'" class="grid gap-4 sm:grid-cols-2">
                <label class="grid gap-2 text-sm font-medium">Stock opname
                    <x-filament::input.wrapper><x-filament::input.select name="stock_take_id" x-bind:disabled="type !== 'stock_take'" x-bind:required="type === 'stock_take'">
                        <option value="">Pilih stock opname</option>
                        @foreach ($stockTakes as $value => $label)<option value="{{ $value }}">{{ $label }} (#{{ $value }})</option>@endforeach
                    </x-filament::input.select></x-filament::input.wrapper>
                </label>
                <label class="grid gap-2 text-sm font-medium">Hasil pemeriksaan
                    <x-filament::input.wrapper><x-filament::input.select name="result" x-bind:disabled="type !== 'stock_take'">
                        <option value="">Semua</option>
                        @foreach ($results as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach
                    </x-filament::input.select></x-filament::input.wrapper>
                </label>
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <label class="grid gap-2 text-sm font-medium">Dari tanggal (opsional)
                    <x-filament::input.wrapper><x-filament::input type="date" name="date_from" /></x-filament::input.wrapper>
                </label>
                <label class="grid gap-2 text-sm font-medium">Sampai tanggal (opsional)
                    <x-filament::input.wrapper><x-filament::input type="date" name="date_to" /></x-filament::input.wrapper>
                </label>
            </div>
            <p class="text-sm text-gray-500" x-show="type === 'assets'">Periode berdasarkan tanggal pengadaan. Kosongkan untuk menyertakan aset tanpa tanggal pengadaan.</p>
            <p class="text-sm text-gray-500" x-cloak x-show="type === 'overdue'">Hanya perangkat yang belum kembali dan sudah lewat jatuh tempo. Periode berdasarkan tanggal jatuh tempo.</p>
            <p class="text-sm text-gray-500" x-cloak x-show="type === 'stock_take'">Periode berdasarkan waktu pemeriksaan. Kosongkan untuk menyertakan aset yang belum diperiksa.</p>
            <p class="text-sm text-gray-500" x-cloak x-show="type === 'maintenance'">Periode berdasarkan tanggal laporan. Biaya menjumlahkan seluruh penanganan pada laporan tersebut, termasuk laporan yang dibatalkan.</p>
            <div class="flex flex-wrap gap-3">
                <x-filament::button type="submit" name="format" value="xlsx">Unduh Excel</x-filament::button>
                <x-filament::button type="submit" name="format" value="print" color="gray">Cetak / Simpan PDF</x-filament::button>
            </div>
            <p class="text-sm text-gray-500">Maksimal 5.000 baris per laporan. Gunakan filter untuk membatasi hasil.</p>
        </form>
    </x-filament::section>
</x-filament-panels::page>
