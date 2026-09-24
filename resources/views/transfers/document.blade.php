<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Berita Acara {{ $transfer->code }}</title>
    <style>
        body { margin: 0; background: #e2e8f0; color: #111827; font: 14px/1.6 Arial, sans-serif; }
        main { max-width: 920px; margin: 24px auto; padding: 36px; background: white; }
        h1 { font-size: 22px; text-align: center; margin-bottom: 0; }
        .number, .actions { text-align: center; }
        .actions { padding: 16px; }
        button { padding: 12px 20px; border: 0; border-radius: 8px; background: #0369a1; color: white; cursor: pointer; }
        table { width: 100%; border-collapse: collapse; font-size: 12px; }
        th, td { border: 1px solid #64748b; padding: 8px; text-align: left; overflow-wrap: anywhere; }
        .scroll { overflow-x: auto; }
        .notes { white-space: pre-wrap; overflow-wrap: anywhere; }
        .signatures { display: flex; gap: 30px; margin-top: 40px; break-inside: avoid; text-align: center; }
        .signature { flex: 1; }
        .signature strong { display: block; margin-top: 70px; text-decoration: underline; }
        @page { size: A4; margin: 15mm; }
        @media print {
            body { background: white; font-size: 11pt; }
            main { padding: 0; margin: 0; max-width: none; }
            .actions { display: none; }
            .scroll { overflow: visible; }
            thead { display: table-header-group; }
            tr { break-inside: avoid; }
        }
    </style>
</head>
<body>
    <div class="actions"><button type="button" onclick="window.print()">Cetak / Simpan PDF</button></div>
    <main>
        <h1>BERITA ACARA SERAH TERIMA ASET</h1>
        <p class="number">Nomor: {{ $transfer->code }}</p>
        <p>Pada tanggal {{ $transfer->transferred_at->format('d/m/Y') }}, telah dilakukan serah terima perangkat dari
            <strong>{{ $transfer->sender_name }}</strong> kepada <strong>{{ $transfer->receiver_name }}</strong>
            untuk ditempatkan di <strong>{{ $transfer->destination_location_name }}</strong>.</p>
        @if ($transfer->package_name)
            <p>Paket perangkat: {{ $transfer->package_name }}</p>
        @endif
        <div class="scroll">
            <table>
                <thead><tr><th>No.</th><th>Kode / Perangkat</th><th>Nomor Seri</th><th>Lokasi Asal</th><th>Penanggung Jawab Lama</th><th>Kondisi</th></tr></thead>
                <tbody>
                    @foreach ($transfer->items as $item)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $item->asset_code }}<br>{{ $item->asset_name }}</td>
                            <td>{{ $item->serial_number ?: '—' }}</td>
                            <td>{{ $item->source_location_name ?: '—' }}</td>
                            <td>{{ $item->previous_custodian ?: '—' }}</td>
                            <td>{{ \App\Models\MaintenanceReport::CONDITIONS[$item->condition] ?? $item->condition }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <p><strong>Alasan / keterangan:</strong></p>
        <p class="notes">{{ $transfer->reason }}</p>
        <p>Dokumen ini mencatat keadaan perangkat pada saat serah terima. Para pihak membubuhkan tanda tangan setelah melakukan pemeriksaan.</p>
        <div class="signatures">
            <div class="signature">Yang Menyerahkan<strong>{{ $transfer->sender_name }}</strong></div>
            <div class="signature">Yang Menerima<strong>{{ $transfer->receiver_name }}</strong></div>
        </div>
        <p>Dicatat oleh: {{ $transfer->created_by_name }} · {{ $transfer->transferred_at->format('d/m/Y H:i') }}</p>
    </main>
</body>
</html>
