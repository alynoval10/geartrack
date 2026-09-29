<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Berita Acara {{ $disposal->code }}</title>
    <style>
        body { margin: 0; background: #e2e8f0; color: #111827; font: 14px/1.6 Arial, sans-serif; }
        main { max-width: 980px; margin: 24px auto; padding: 36px; background: white; }
        h1 { margin-bottom: 0; font-size: 22px; text-align: center; }
        .number, .actions { text-align: center; }
        .actions { padding: 16px; }
        button { padding: 12px 20px; border: 0; border-radius: 8px; background: #0369a1; color: white; cursor: pointer; }
        table { width: 100%; border-collapse: collapse; font-size: 11px; }
        th, td { border: 1px solid #64748b; padding: 7px; text-align: left; vertical-align: top; overflow-wrap: anywhere; }
        th { background: #e2e8f0; }
        .notes { white-space: pre-wrap; overflow-wrap: anywhere; }
        .signatures { display: flex; gap: 30px; margin-top: 40px; text-align: center; break-inside: avoid; }
        .signature { flex: 1; }
        .signature strong { display: block; margin-top: 70px; text-decoration: underline; }
        @page { size: A4 landscape; margin: 12mm; }
        @media print {
            body { background: white; font-size: 10pt; }
            main { padding: 0; margin: 0; max-width: none; }
            .actions { display: none; }
            thead { display: table-header-group; }
            tr { break-inside: avoid; }
        }
    </style>
</head>
<body>
    <div class="actions"><button type="button" onclick="window.print()">Cetak / Simpan PDF</button></div>
    <main>
        <div style="display:flex;align-items:center;gap:16px;border-bottom:2px solid #111827;padding-bottom:12px">
            @if ($school->logo)
                <img src="{{ Storage::disk('public')->url($school->logo) }}" alt="Logo" style="width:70px;height:70px;object-fit:contain">
            @endif
            <div><strong style="font-size:18px">{{ $school->school_name }}</strong><br>{{ $school->address }}<br>Tahun Ajaran {{ $school->academic_year ?: '—' }}</div>
        </div>

        <h1>BERITA ACARA PENGHAPUSAN / PENSIUN ASET</h1>
        <p class="number">Nomor: {{ $disposal->code }}</p>
        <p>Pada tanggal <strong>{{ $disposal->disposal_date->format('d/m/Y') }}</strong>, telah disetujui penghapusan atau pensiun aset dengan jenis
            <strong>{{ \App\Models\AssetDisposal::REASON_TYPES[$disposal->reason_type] ?? $disposal->reason_type }}</strong>.</p>

        <table>
            <thead>
                <tr><th>No.</th><th>Kode / Nama Aset</th><th>Nomor Seri</th><th>Kondisi</th><th>Status Sebelumnya</th><th>Lokasi</th><th>Penanggung Jawab</th></tr>
            </thead>
            <tbody>
                @foreach ($disposal->items as $item)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>{{ $item->asset_code }}<br>{{ $item->asset_name }}</td>
                        <td>{{ $item->serial_number ?: '—' }}</td>
                        <td>{{ \App\Models\MaintenanceReport::CONDITIONS[$item->condition] ?? $item->condition }}</td>
                        <td>{{ match ($item->previous_status) { 'available' => 'Tersedia', 'in_use' => 'Digunakan', 'lost' => 'Hilang', 'maintenance' => 'Perawatan', default => $item->previous_status } }}</td>
                        <td>{{ $item->location_name ?: '—' }}</td>
                        <td>{{ $item->custodian_name ?: '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <p><strong>Alasan / dasar penghapusan:</strong></p>
        <p class="notes">{{ $disposal->reason }}</p>
        @if ($disposal->review_notes)
            <p><strong>Catatan persetujuan:</strong></p>
            <p class="notes">{{ $disposal->review_notes }}</p>
        @endif

        <div class="signatures">
            <div class="signature">Pengusul<strong>{{ $disposal->submitted_by_name }}</strong></div>
            <div class="signature">Administrator Pemeriksa<strong>{{ $disposal->reviewed_by_name }}</strong></div>
            @if ($school->asset_manager_name)
                <div class="signature">Mengetahui, Pengurus Barang<strong>{{ $school->asset_manager_name }}</strong>NIP. {{ $school->asset_manager_nip ?: '—' }}</div>
            @endif
        </div>
        <p>Disetujui pada: {{ $disposal->reviewed_at?->format('d/m/Y H:i') }}</p>
    </main>
</body>
</html>
