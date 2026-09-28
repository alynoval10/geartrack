<div data-loan-scanner class="grid gap-4">
    <div
        id="loan-qr-reader-{{ uniqid() }}"
        data-loan-scanner-reader
        class="min-h-52 overflow-hidden rounded-xl bg-black"
    ></div>

    <p
        data-loan-scanner-status
        class="rounded-lg bg-gray-50 px-4 py-3 text-sm text-gray-600 dark:bg-white/5 dark:text-gray-300"
        role="status"
        aria-live="polite"
    >
        Aktifkan kamera, lalu arahkan ke QR perangkat.
    </p>

    <div class="grid gap-3 sm:grid-cols-2">
        <x-filament::button type="button" icon="heroicon-o-camera" data-loan-scanner-start>
            Aktifkan Kamera
        </x-filament::button>

        <x-filament::button type="button" color="gray" icon="heroicon-o-stop" data-loan-scanner-stop hidden>
            Hentikan Kamera
        </x-filament::button>
    </div>

    <div class="flex items-center gap-3 text-xs font-medium uppercase tracking-wide text-gray-400">
        <span class="h-px flex-1 bg-gray-200 dark:bg-white/10"></span>
        atau
        <span class="h-px flex-1 bg-gray-200 dark:bg-white/10"></span>
    </div>

    <label class="flex cursor-pointer items-center justify-center gap-2 rounded-xl border border-gray-300 px-4 py-3 text-sm font-semibold text-gray-700 hover:border-primary-500 hover:text-primary-600 dark:border-white/15 dark:text-gray-200">
        <x-filament::icon icon="heroicon-o-photo" class="h-5 w-5" />
        Foto / Pilih QR
        <input data-loan-scanner-file type="file" accept="image/*" capture="environment" class="sr-only">
    </label>

    <p class="text-xs leading-5 text-gray-500 dark:text-gray-400">
        Setelah satu perangkat ditambahkan, arahkan kamera ke QR berikutnya. Anda juga tetap dapat memilih perangkat secara manual setelah menutup pemindai.
    </p>
</div>

{{-- Livewire memuat aset ini ketika modal pertama kali dibuka dan tidak mengunduhnya kembali pada pembukaan berikutnya. --}}
@assets
    @vite('resources/js/loan-scanner.js')
@endassets
