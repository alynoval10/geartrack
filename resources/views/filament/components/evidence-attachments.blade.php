@php
    $files = array_values(array_filter((array) ($attachments ?? [])));
@endphp

<div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
    @forelse ($files as $path)
        @php
            $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
            $isImage = in_array($extension, ['jpg', 'jpeg', 'png', 'webp'], true);
            $url = Storage::disk('public')->url($path);
        @endphp
        <a href="{{ $url }}" target="_blank" rel="noopener noreferrer" class="flex items-center gap-3 rounded-xl border border-gray-200 p-3 text-sm transition hover:border-primary-500 dark:border-gray-700">
            @if ($isImage)
                <img src="{{ $url }}" alt="Lampiran {{ $loop->iteration }}" class="size-12 rounded-lg object-cover">
            @else
                <x-filament::icon icon="heroicon-o-document-text" class="size-10 text-red-600" />
            @endif
            <span><strong class="block">Lampiran {{ $loop->iteration }}</strong>{{ strtoupper($extension) }} · Buka / unduh</span>
        </a>
    @empty
        <p class="text-sm text-gray-500 dark:text-gray-400">Tidak ada lampiran.</p>
    @endforelse
</div>
