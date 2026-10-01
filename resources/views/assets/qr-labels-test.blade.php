<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Uji Label Aset 50 × 30 mm | GearTrack</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; padding: 18px; background: #e2e8f0; color: #0f172a; font-family: Arial, Helvetica, sans-serif; }
        .toolbar { max-width: 210mm; margin: 0 auto 16px; padding: 16px; background: white; border-radius: 10px; }
        .toolbar h1 { margin: 0 0 6px; font-size: 18px; }
        .toolbar p { margin: 4px 0; color: #475569; font-size: 13px; }
        .toolbar-actions { display: flex; gap: 8px; margin-top: 12px; }
        .button { display: inline-flex; align-items: center; padding: 10px 16px; border: 0; border-radius: 8px; background: #0369a1; color: white; font: inherit; font-weight: 700; text-decoration: none; cursor: pointer; }
        .button.secondary { background: #475569; }
        .paper { width: 210mm; min-height: 297mm; margin: 0 auto; padding: 12mm 14mm; background: white; box-shadow: 0 6px 20px rgb(15 23 42 / 14%); }
        .paper-title { margin: 0; font-size: 14pt; }
        .paper-note { margin: 2mm 0 7mm; font-size: 8.5pt; }
        .labels { display: grid; grid-template-columns: repeat(3, 50mm); gap: 5mm; align-items: start; }
        .label { width: 50mm; height: 30mm; padding: 1.5mm; display: flex; align-items: center; gap: 1.5mm; overflow: hidden; border: .25mm solid #0f172a; border-radius: 1.5mm; background: white; break-inside: avoid; page-break-inside: avoid; }
        .qr { width: 22mm; height: 22mm; flex: 0 0 22mm; }
        .qr svg { display: block; width: 100%; height: 100%; }
        .info { min-width: 0; flex: 1; }
        .brand { color: #0369a1; font-size: 6.5pt; font-weight: 800; }
        .code { margin-top: .8mm; overflow-wrap: anywhere; font-size: 8pt; font-weight: 800; line-height: 1.05; }
        .name { margin-top: 1mm; display: -webkit-box; overflow: hidden; font-size: 6.8pt; font-weight: 700; line-height: 1.15; -webkit-box-orient: vertical; -webkit-line-clamp: 2; }
        .hint { margin-top: 1mm; color: #475569; font-size: 5.5pt; line-height: 1.1; }
        .calibration { display: flex; align-items: center; gap: 4mm; margin-top: 10mm; font-size: 8pt; }
        .square { width: 20mm; height: 20mm; flex: 0 0 20mm; border: .3mm solid #0f172a; }
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
        <h1>Uji massal label 50 × 30 mm</h1>
        <p>Ini berbeda dari tampilan lama 85 × 45 mm. Setiap kotak pada lembar A4 di bawah berukuran fisik tepat 50 × 30 mm.</p>
        <p>Saat mencetak pilih A4, skala 100% / Actual size, matikan Fit to page, serta matikan header dan footer.</p>
        <div class="toolbar-actions">
            <button type="button" class="button" onclick="window.print()">Cetak Lembar Uji A4</button>
            <a class="button secondary" href="{{ route('asset.qr.bulk-label', ['assets' => $selection, 'legacy' => 1]) }}">Lihat Ukuran Lama</a>
        </div>
    </section>

    <main class="paper">
        <h1 class="paper-title">Uji Label Aset GearTrack</h1>
        <p class="paper-note">{{ $assets->count() }} label ukuran 50 × 30 mm · Potong mengikuti garis kotak, lalu coba tempel dan scan.</p>

        <div class="labels">
            @foreach ($assets as $asset)
                <div class="label">
                    <div class="qr">{!! (string) $asset->generatedQr !!}</div>
                    <div class="info">
                        <div class="brand">GearTrack</div>
                        <div class="code">{{ $asset->asset_code }}</div>
                        <div class="name">{{ $asset->name }}</div>
                        <div class="hint">Scan untuk detail aset</div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="calibration">
            <div class="square"></div>
            <div><strong>Kotak kalibrasi 20 × 20 mm.</strong><br>Jika ukurannya berbeda, periksa kembali skala cetak.</div>
        </div>
    </main>
</body>
</html>
