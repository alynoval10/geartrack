<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Log Aktivitas — GearTrack</title>
    <style>
        body { font-family: Arial, sans-serif; color: #172033; padding: 24px; font-size: 11px; }
        h1 { margin: 22px 0 4px; font-size: 22px; }
        p { line-height: 1.5; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #cbd5e1; padding: 7px; text-align: left; vertical-align: top; overflow-wrap: anywhere; }
        th { background: #e2e8f0; }
        .tools { display: flex; gap: 16px; margin-bottom: 24px; }
        .tools button, .tools a { color: #172033; font: inherit; }
        .school { display: flex; align-items: center; gap: 16px; border-bottom: 2px solid #172033; padding-bottom: 12px; }
        .school img { width: 70px; height: 70px; object-fit: contain; }
        .school strong { font-size: 18px; }
        @page { size: A4 landscape; margin: 10mm; }
        @media print {
            body { padding: 0; font-size: 8px; }
            .tools { display: none; }
            thead { display: table-header-group; }
            tr { break-inside: avoid; }
        }
    </style>
</head>
<body>
    <div class="tools">
        <button type="button" onclick="window.print()">Cetak / Simpan PDF</button>
        <a href="{{ route('filament.admin.resources.asset-histories.index') }}">Kembali ke Log Aktivitas</a>
    </div>

    <div class="school">
        @if ($school->logo)
            <img src="{{ Storage::disk('public')->url($school->logo) }}" alt="Logo">
        @endif
        <div>
            <strong>{{ $school->school_name }}</strong><br>
            {{ $school->address }}<br>
            Tahun Ajaran {{ $school->academic_year ?: '—' }}
        </div>
    </div>

    <h1>LOG AKTIVITAS PENGGUNA</h1>
    <p>Periode: {{ $period }} · Dicetak: {{ now()->format('d/m/Y H:i') }} · Jumlah aktivitas: {{ $histories->count() }}</p>

    <table>
        <thead>
            <tr>
                <th>No.</th>
                <th>Waktu</th>
                <th>Pengguna</th>
                <th>Aktivitas</th>
                <th>Kode Aset</th>
                <th>Nama Aset</th>
                <th>Keterangan</th>
                <th>Sebelum</th>
                <th>Sesudah</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($histories as $history)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $history->created_at->format('d/m/Y H:i:s') }}</td>
                    <td>{{ $history->user_name ?: 'Sistem' }}</td>
                    <td>{{ \App\Models\AssetHistory::ACTIONS[$history->action] ?? $history->action }}</td>
                    <td>{{ $history->asset_code ?: '—' }}</td>
                    <td>{{ $history->asset_name ?: '—' }}</td>
                    <td>{{ $history->description ?: '—' }}</td>
                    <td>{{ $history->old_value ?: '—' }}</td>
                    <td>{{ $history->new_value ?: '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="9">Tidak ada log aktivitas sesuai filter.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
