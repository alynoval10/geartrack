<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Uji Label 50 × 30 mm | {{ $asset->asset_code }}</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; padding: 18px; background: #e2e8f0; color: #0f172a; font-family: Arial, Helvetica, sans-serif; }
        .toolbar { max-width: 210mm; margin: 0 auto 16px; padding: 16px; background: white; border-radius: 10px; }
        .toolbar h1 { margin: 0 0 6px; font-size: 18px; }
        .toolbar p { margin: 4px 0; color: #475569; font-size: 13px; }
        .toolbar-actions { display: flex; gap: 8px; margin-top: 12px; }
        .button { display: inline-flex; align-items: center; padding: 10px 16px; border: 0; border-radius: 8px; background: #0369a1; color: white; font: inherit; font-weight: 700; text-decoration: none; cursor: pointer; }
        .button.secondary { background: #475569; }
        .paper { width: 210mm; min-height: 297mm; margin: 0 auto; padding: 14mm; background: white; box-shadow: 0 6px 20px rgb(15 23 42 / 14%); }
        .paper-title { margin: 0 0 2mm; font-size: 14pt; }
        .paper-note { margin: 0 0 10mm; font-size: 9pt; }
        .sample-row { display: flex; align-items: flex-start; gap: 12mm; }
        .measurement { position: relative; padding-top: 7mm; padding-left: 8mm; }
        .width-guide { position: absolute; top: 0; left: 8mm; width: 50mm; border-top: .3mm solid #64748b; text-align: center; font-size: 7pt; }
        .width-guide::before, .width-guide::after { position: absolute; top: -1.5mm; content: ''; height: 3mm; border-left: .3mm solid #64748b; }
        .width-guide::before { left: 0; }
        .width-guide::after { right: 0; }
        .height-guide { position: absolute; top: 7mm; left: 1mm; width: 7mm; height: 30mm; border-left: .3mm solid #64748b; font-size: 7pt; writing-mode: vertical-rl; text-align: center; }
        .height-guide::before, .height-guide::after { position: absolute; left: -1.5mm; content: ''; width: 3mm; border-top: .3mm solid #64748b; }
        .height-guide::before { top: 0; }
        .height-guide::after { bottom: 0; }
        .label { width: 50mm; height: 30mm; padding: 1.5mm; display: flex; align-items: center; gap: 1.5mm; overflow: hidden; border: .25mm solid #0f172a; border-radius: 1.5mm; background: white; }
        .qr { width: 22mm; height: 22mm; flex: 0 0 22mm; }
        .qr svg { display: block; width: 100%; height: 100%; }
        .info { min-width: 0; flex: 1; }
        .brand { color: #0369a1; font-size: 6.5pt; font-weight: 800; }
        .code { margin-top: .8mm; overflow-wrap: anywhere; font-size: 8pt; font-weight: 800; line-height: 1.05; }
        .name { margin-top: 1mm; display: -webkit-box; overflow: hidden; font-size: 6.8pt; font-weight: 700; line-height: 1.15; -webkit-box-orient: vertical; -webkit-line-clamp: 2; }
        .hint { margin-top: 1mm; color: #475569; font-size: 5.5pt; line-height: 1.1; }
        .check { width: 45mm; font-size: 9pt; line-height: 1.5; }
        .check strong { display: block; margin-bottom: 2mm; }
        .calibration { margin-top: 18mm; }
        .calibration h2 { font-size: 11pt; }
        .square { width: 20mm; height: 20mm; border: .3mm solid #0f172a; }
        .calibration p { max-width: 125mm; font-size: 8.5pt; }
        @page { size: A4 portrait; margin: 0; }
        @media print {
            body { padding: 0; background: white; }
            .toolbar { display: none; }
            .paper { margin: 0; box-shadow: none; }
        }
    </style>
</head>
<body>
    <section class="toolbar">
        <h1>Uji fisik label 50 × 30 mm</h1>
        <p>Pilih kertas A4, skala 100% atau Actual size, lalu matikan Fit to page.</p>
        <p>Setelah dicetak, ukur kotak kalibrasi, potong label, tempel sementara, dan scan QR menggunakan HP.</p>
        <div class="toolbar-actions">
            <button type="button" class="button" onclick="window.print()">Cetak Lembar Uji A4</button>
            <a class="button secondary" href="{{ route('asset.qr.label', $asset->qr_token) }}">Kembali</a>
        </div>
    </section>

    <main class="paper">
        <h1 class="paper-title">Uji Label Aset GearTrack</h1>
        <p class="paper-note">Aset contoh: {{ $asset->asset_code }} · Label di bawah harus berukuran tepat 50 × 30 mm.</p>

        <div class="sample-row">
            <div class="measurement">
                <div class="width-guide">50 mm</div>
                <div class="height-guide">30 mm</div>
                <div class="label">
                    <div class="qr">{!! (string) $qrCode !!}</div>
                    <div class="info">
                        <div class="brand">GearTrack</div>
                        <div class="code">{{ $asset->asset_code }}</div>
                        <div class="name">{{ $asset->name }}</div>
                        <div class="hint">Scan untuk detail aset</div>
                    </div>
                </div>
            </div>

            <div class="check">
                <strong>Yang perlu diperiksa:</strong>
                <div>□ Kode aset mudah dibaca</div>
                <div>□ Nama aset masih cukup jelas</div>
                <div>□ QR terbaca dari jarak 15–30 cm</div>
                <div>□ Ukuran cocok ketika ditempel</div>
            </div>
        </div>

        <section class="calibration">
            <h2>Pemeriksaan skala cetak</h2>
            <div class="square"></div>
            <p>Kotak ini harus tepat 20 × 20 mm ketika diukur dengan penggaris. Jika berbeda, ulangi pencetakan dengan skala 100% / Actual size dan nonaktifkan Fit to page.</p>
        </section>
    </main>
</body>
</html>
