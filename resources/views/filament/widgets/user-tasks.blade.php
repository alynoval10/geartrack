<x-filament-widgets::widget>
    <div class="grid gap-6">
        <x-filament::section :heading="$isAdmin ? 'Ringkasan Operasional' : 'Tugas Saya'" :description="$isAdmin ? 'Kondisi inventaris seluruh sekolah yang perlu dipantau.' : 'Ringkasan pekerjaan dan aset yang terkait dengan akun Anda.'">
            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                @foreach ($taskCounts as $task)
                    <a href="{{ $task['url'] }}" class="rounded-xl border border-gray-200 p-4 transition hover:border-primary-500 dark:border-gray-700">
                        <span class="text-sm text-gray-500 dark:text-gray-400">{{ $task['label'] }}</span>
                        <strong class="mt-2 block text-3xl">{{ $task['value'] }}</strong>
                    </a>
                @endforeach
            </div>

            @if ($isAdmin)
                <div class="mt-5 grid gap-4 lg:grid-cols-2">
                    <div class="rounded-xl bg-red-50 p-4 dark:bg-red-950"><strong>{{ $overdueLoans }}</strong> peminjaman terlambat</div>
                    <div class="rounded-xl bg-amber-50 p-4 dark:bg-amber-950"><strong>{{ $dueMaintenance }}</strong> jadwal perawatan mendekati atau terlewat</div>
                </div>
                <div class="mt-5 grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
                    @foreach ($locationSummaries as $location)
                        <div class="rounded-xl bg-gray-50 p-3 text-sm dark:bg-gray-800"><strong class="block">{{ $location->name }}</strong>{{ $location->assets_count }} aset</div>
                    @endforeach
                </div>
            @else
                <div class="mt-5 grid gap-3">
                    <h3 class="font-semibold">Aset yang menjadi tanggung jawab Anda</h3>
                    @forelse ($assignedAssets as $asset)
                        <a href="{{ \App\Filament\Resources\Assets\AssetResource::getUrl('view', ['record' => $asset]) }}" class="flex items-center justify-between gap-4 rounded-xl bg-gray-50 p-3 text-sm dark:bg-gray-800">
                            <span><strong>{{ $asset->asset_code }}</strong> · {{ $asset->name }}</span><span class="text-gray-500">{{ $asset->location?->name ?: 'Tanpa lokasi' }}</span>
                        </a>
                    @empty
                        <p class="rounded-xl border border-dashed border-gray-300 p-4 text-sm text-gray-500 dark:border-gray-700">Belum ada aset yang ditugaskan kepada akun Anda.</p>
                    @endforelse
                </div>
            @endif
        </x-filament::section>

        <x-filament::section heading="Timeline Pengisian GearTrack" description="Ikuti urutan ini agar data inventaris saling terhubung dengan benar.">
            <ol class="grid gap-4 lg:grid-cols-3">
                @foreach ($timeline as $step)
                    <li class="relative rounded-xl border p-4 {{ $step['done'] ? 'border-green-300 bg-green-50 dark:border-green-800 dark:bg-green-950' : 'border-gray-200 dark:border-gray-700' }}">
                        <div class="flex items-start gap-3">
                            <span class="flex size-8 shrink-0 items-center justify-center rounded-full {{ $step['done'] ? 'bg-green-600 text-white' : 'bg-gray-200 text-gray-700 dark:bg-gray-700 dark:text-gray-200' }}">{{ $loop->iteration }}</span>
                            <div class="grid gap-2"><strong>{{ $step['title'] }}</strong><p class="text-sm text-gray-500 dark:text-gray-400">{{ $step['description'] }}</p><a class="text-sm font-medium text-primary-600" href="{{ $step['url'] }}">{{ $step['done'] ? 'Lihat data' : 'Mulai isi' }} →</a></div>
                        </div>
                    </li>
                @endforeach
            </ol>
        </x-filament::section>
    </div>
</x-filament-widgets::widget>
