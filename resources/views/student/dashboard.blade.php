@extends('student.layouts.app', ['title' => 'Beranda Siswa'])

@section('content')
    <section style="margin-bottom: 22px;">
        <p class="student-eyebrow">Tahun Ajaran {{ $borrower->academic_year }}</p>
        <h1 class="student-title">Halo, {{ $borrower->name }}</h1>
        <p class="student-subtitle">{{ $borrower->class_level }} {{ $borrower->class_name }} · NIS {{ $borrower->nis }}</p>
    </section>

    <div class="student-grid">
        <section class="student-card" id="request">
            <div class="student-card-head">
                <p class="student-eyebrow">Permintaan Perangkat</p>
                <h2>Ajukan Peminjaman</h2>
                <p class="student-muted" style="margin: 8px 0 0;">Pilih perangkat yang dibutuhkan. Perangkat baru dicatat sebagai dipinjam setelah disetujui guru atau administrator.</p>
            </div>
            <div class="student-card-body">
                <form class="student-form" method="POST" action="{{ route('student.loan-requests.store') }}">
                    @csrf
                    <div class="student-field">
                        <label for="asset_ids">Perangkat yang diminta</label>
                        <div data-student-asset-scanner data-scan-url="{{ route('student.assets.scan') }}">
                            <button class="student-button student-button-secondary" type="button" data-student-scan-toggle aria-expanded="false">
                                Scan QR Barang
                            </button>
                            <p class="student-scan-count" data-student-asset-count>{{ count(old('asset_ids', [])) }} barang dipilih</p>
                            <p class="student-scan-status" data-student-scan-status role="status" aria-live="polite">Kamera akan tertutup otomatis setelah setiap barang berhasil dipindai.</p>
                            <div class="student-scan-panel" data-student-scan-panel hidden>
                                <div class="student-scan-reader" id="student-asset-scan-reader"></div>
                                <div class="student-scan-actions">
                                    <button class="student-button" type="button" data-student-scan-start>Aktifkan Kamera</button>
                                    <button class="student-button student-button-secondary" type="button" data-student-scan-stop hidden>Hentikan Kamera</button>
                                    <label class="student-button student-button-secondary student-scan-file-label">
                                        Scan dari Foto
                                        <input type="file" data-student-scan-file accept="image/*" capture="environment">
                                    </label>
                                </div>
                            </div>
                        </div>
                        <select id="asset_ids" name="asset_ids[]" multiple hidden>
                            @foreach ($assets as $asset)
                                <option value="{{ $asset['id'] }}" @selected(in_array((string) $asset['id'], old('asset_ids', []), true))>{{ $asset['label'] }}{{ $asset['details'] ? ' · '.$asset['details'] : '' }}</option>
                            @endforeach
                        </select>
                        <p class="student-help">Scan barang satu per satu. Kamera akan tertutup setelah setiap pemindaian; tekan tombol scan lagi untuk menambahkan barang berikutnya.</p>
                        @error('asset_ids') <p class="student-error">{{ $message }}</p> @enderror
                    </div>
                    <div class="student-field">
                        <label for="purpose">Keperluan</label>
                        <textarea id="purpose" name="purpose" required maxlength="5000">{{ old('purpose', 'Praktikum pembelajaran') }}</textarea>
                        @error('purpose') <p class="student-error">{{ $message }}</p> @enderror
                    </div>
                    <div class="student-field">
                        <label for="due_date">Batas pengembalian</label>
                        <input id="due_date" name="due_date" type="date" min="{{ today()->toDateString() }}" value="{{ old('due_date', $defaultDueDate) }}" required>
                        @error('due_date') <p class="student-error">{{ $message }}</p> @enderror
                    </div>
                    <button class="student-button" type="submit">Kirim untuk Persetujuan</button>
                </form>
            </div>
        </section>

        <section class="student-card" id="qr">
            <div class="student-card-head">
                <p class="student-eyebrow">Identitas Siswa</p>
                <h2>QR Siswa Saya</h2>
            </div>
            <div class="student-card-body">
                <div class="student-qr">{!! $qrCode !!}<p class="student-muted" style="margin: 0;">Tunjukkan QR ini kepada petugas saat mengajukan peminjaman.</p></div>
                <div class="student-info">
                    <div class="student-info-row"><span>Nama</span><strong>{{ $borrower->name }}</strong></div>
                    <div class="student-info-row"><span>NIS</span><strong>{{ $borrower->nis }}</strong></div>
                    <div class="student-info-row"><span>Kelas</span><strong>{{ $borrower->class_level }} {{ $borrower->class_name }}</strong></div>
                    <div class="student-info-row"><span>Tahun Ajaran</span><strong>{{ $borrower->academic_year }}</strong></div>
                </div>
            </div>
        </section>
    </div>

    <section class="student-card" id="history" style="margin-top: 20px;">
        <div class="student-card-head">
            <p class="student-eyebrow">Status & Riwayat</p>
            <h2>Permintaan Peminjaman Saya</h2>
        </div>
        <div class="student-card-body">
            @if ($requests->isEmpty())
                <p class="student-muted" style="margin: 0;">Belum ada permintaan peminjaman.</p>
            @else
                <div class="student-history">
                    @foreach ($requests as $loanRequest)
                        @php
                            $statusClass = match ($loanRequest->status) {
                                'approved' => 'student-status-approved',
                                'rejected' => 'student-status-rejected',
                                default => '',
                            };
                            $statusLabel = match ($loanRequest->status) {
                                'approved' => 'Disetujui',
                                'rejected' => 'Ditolak',
                                default => 'Menunggu Persetujuan',
                            };
                        @endphp
                        <article class="student-request">
                            <div class="student-request-top">
                                <div><strong>{{ $loanRequest->created_at->format('d M Y, H:i') }}</strong><p class="student-muted" style="margin: 5px 0 0;">Batas kembali {{ $loanRequest->due_date->format('d M Y') }}</p></div>
                                <span class="student-status {{ $statusClass }}">{{ $statusLabel }}</span>
                            </div>
                            <p class="student-muted" style="margin: 0;">{{ $loanRequest->purpose }}</p>
                            <ul style="margin: 0; padding-left: 20px; color: #475569; font-size: 13px; line-height: 1.7;">
                                @foreach ($loanRequest->items as $item)
                                    <li>{{ $item->asset_code }} — {{ $item->asset_name }}</li>
                                @endforeach
                            </ul>
                            @if ($loanRequest->loan)
                                <p class="student-muted" style="margin: 0;">Nomor peminjaman: <strong>{{ $loanRequest->loan->code }}</strong></p>
                            @endif
                            @if ($loanRequest->review_notes)
                                <p class="student-alert student-alert-error" style="margin: 0;">Catatan: {{ $loanRequest->review_notes }}</p>
                            @endif
                        </article>
                    @endforeach
                </div>
            @endif
        </div>
    </section>
@endsection

@push('scripts')
    @vite('resources/js/student-asset-scanner.js')
@endpush
