<x-filament-panels::page>
    <div class="space-y-6">
        <div class="rounded-xl border border-primary-200 bg-primary-50 p-4 text-sm text-primary-900 dark:border-primary-400/20 dark:bg-primary-400/10 dark:text-primary-100">
            Periksa daftar barang dan batas pengembalian. Aset baru tercatat sebagai dipinjam setelah permintaan disetujui.
        </div>

        @forelse ($requests as $loanRequest)
            @php
                $statusColor = match ($loanRequest->status) {
                    'approved' => 'success',
                    'rejected' => 'danger',
                    default => 'warning',
                };
                $statusLabel = match ($loanRequest->status) {
                    'approved' => 'Disetujui',
                    'rejected' => 'Ditolak',
                    default => 'Menunggu Persetujuan',
                };
            @endphp
            <article id="pengajuan-{{ $loanRequest->id }}" @class([
                'rounded-xl border bg-white p-5 shadow-sm dark:bg-gray-900',
                'border-primary-500 ring-2 ring-primary-200 dark:border-primary-400 dark:ring-primary-400/20' => $focusedRequestId === $loanRequest->id,
                'border-gray-200 dark:border-white/10' => $focusedRequestId !== $loanRequest->id,
            ])>
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <h2 class="text-base font-bold text-gray-950 dark:text-white">{{ $loanRequest->borrower_name }}</h2>
                            <x-filament::badge :color="$statusColor">
                                {{ $statusLabel }}
                            </x-filament::badge>
                        </div>
                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">NIS {{ $loanRequest->borrower_nis }} · {{ $loanRequest->borrower_class }} · Tahun ajaran {{ $loanRequest->academic_year }}</p>
                    </div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ $loanRequest->created_at->format('d M Y, H:i') }}</p>
                </div>

                <div class="mt-4 grid gap-4 md:grid-cols-2">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Keperluan</p>
                        <p class="mt-1 whitespace-pre-line text-sm text-gray-800 dark:text-gray-100">{{ $loanRequest->purpose }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Batas Pengembalian</p>
                        <p class="mt-1 text-sm text-gray-800 dark:text-gray-100">{{ $loanRequest->due_date->format('d M Y') }}</p>
                    </div>
                </div>

                <div class="mt-4 rounded-lg bg-gray-50 p-4 dark:bg-white/5">
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Perangkat yang diminta</p>
                    <ul class="mt-2 grid gap-1 text-sm text-gray-800 dark:text-gray-100 sm:grid-cols-2">
                        @foreach ($loanRequest->items as $item)
                            <li>{{ $item->asset_code }} — {{ $item->asset_name }}</li>
                        @endforeach
                    </ul>
                </div>

                @if ($loanRequest->status === 'pending')
                    <div class="mt-5 grid gap-3 border-t border-gray-100 pt-4 dark:border-white/10 sm:grid-cols-[1fr_auto_auto] sm:items-end">
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-200" for="review-note-{{ $loanRequest->id }}">Alasan penolakan (diisi jika ditolak)</label>
                            <textarea id="review-note-{{ $loanRequest->id }}" wire:model="reviewNotes.{{ $loanRequest->id }}" rows="2" maxlength="2000" class="w-full rounded-lg border-gray-300 text-sm shadow-sm dark:border-white/15 dark:bg-gray-950 dark:text-white"></textarea>
                        </div>
                        <x-filament::button color="danger" icon="heroicon-o-x-mark" wire:click="reject({{ $loanRequest->id }})" wire:loading.attr="disabled" wire:target="reject({{ $loanRequest->id }})">
                            Tolak
                        </x-filament::button>
                        <x-filament::button color="success" icon="heroicon-o-check" wire:click="approve({{ $loanRequest->id }})" wire:loading.attr="disabled" wire:target="approve({{ $loanRequest->id }})">
                            Setujui & Buat Peminjaman
                        </x-filament::button>
                    </div>
                @else
                    <div class="mt-4 flex flex-wrap gap-x-5 gap-y-2 border-t border-gray-100 pt-4 text-sm text-gray-500 dark:border-white/10 dark:text-gray-400">
                        <span>Diproses {{ $loanRequest->reviewed_at?->format('d M Y, H:i') ?? '—' }} oleh {{ $loanRequest->reviewer?->name ?? 'akun yang sudah tidak aktif' }}</span>
                        @if ($loanRequest->loan)
                            <a href="{{ \App\Filament\Resources\Loans\LoanResource::getUrl('view', ['record' => $loanRequest->loan]) }}">Peminjaman {{ $loanRequest->loan->code }}</a>
                        @endif
                        @if ($loanRequest->review_notes)
                            <span>Alasan: {{ $loanRequest->review_notes }}</span>
                        @endif
                    </div>
                @endif
            </article>
        @empty
            <div class="rounded-xl border border-gray-200 bg-white px-6 py-14 text-center dark:border-white/10 dark:bg-gray-900">
                <x-heroicon-o-clipboard-document-check class="mx-auto h-10 w-10 text-gray-400" />
                <h2 class="mt-3 font-semibold text-gray-950 dark:text-white">Belum ada permintaan peminjaman</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Permintaan siswa akan muncul di sini untuk ditinjau.</p>
            </div>
        @endforelse

        {{ $requests->links() }}
    </div>
</x-filament-panels::page>
