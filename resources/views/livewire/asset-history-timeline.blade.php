<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            Riwayat Aset
        </x-slot>

        <x-slot name="description">
            Catatan perubahan data dan status aset.
        </x-slot>

        @if ($this->histories->isEmpty())
            <div class="py-6 text-center text-sm text-gray-500 dark:text-gray-400">
                Belum ada riwayat perubahan.
            </div>
        @else
            <div class="divide-y divide-gray-200 dark:divide-white/10">

                @foreach ($this->histories as $history)
                    <div class="flex gap-4 py-4 first:pt-1 last:pb-1">

                        {{-- Icon --}}
                        <div class="shrink-0">
                            <div
                                class="flex h-9 w-9 items-center justify-center
                                       rounded-full bg-sky-50 text-sky-600
                                       dark:bg-sky-500/10 dark:text-sky-400"
                            >
                                <x-filament::icon
                                    icon="heroicon-o-clock"
                                    class="h-5 w-5"
                                />
                            </div>
                        </div>

                        {{-- Isi --}}
                        <div class="min-w-0 flex-1">

                            <div class="flex flex-wrap items-start justify-between gap-2">

                                <div class="font-semibold text-gray-950 dark:text-white">
                                    {{ $history->description }}
                                </div>

                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ $history->created_at->format('d M Y, H:i') }}
                                </div>

                            </div>

                            {{-- Perubahan --}}
                            @if ($history->field)
                                <div class="mt-2 flex flex-wrap items-center gap-2">

                                    <span
                                        class="rounded-md bg-gray-100 px-2.5 py-1
                                               text-sm text-gray-600
                                               dark:bg-white/5 dark:text-gray-300"
                                    >
                                        {{ $history->old_value ?: '-' }}
                                    </span>

                                    <x-filament::icon
                                        icon="heroicon-m-arrow-right"
                                        class="h-4 w-4 text-gray-400"
                                    />

                                    <span
                                        class="rounded-md bg-sky-50 px-2.5 py-1
                                               text-sm font-medium text-sky-700
                                               dark:bg-sky-500/10 dark:text-sky-400"
                                    >
                                        {{ $history->new_value ?: '-' }}
                                    </span>

                                </div>
                            @endif

                            {{-- User --}}
                            <div class="mt-2 flex items-center gap-1.5 text-xs text-gray-500 dark:text-gray-400">

                                <x-filament::icon
                                    icon="heroicon-m-user"
                                    class="h-3.5 w-3.5"
                                />

                                <span>
                                    {{ $history->user?->name ?? 'Sistem' }}
                                </span>

                            </div>

                        </div>
                    </div>
                @endforeach

            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>