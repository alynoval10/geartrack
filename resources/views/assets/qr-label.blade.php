<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Label {{ $asset->asset_code }} | GearTrack</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; padding: 24px; background: #e2e8f0; color: #0f172a; font-family: Arial, Helvetica, sans-serif; }
        .toolbar { max-width: 720px; margin: 0 auto 18px; padding: 14px; border-radius: 10px; background: white; }
        .toolbar strong { display: block; }
        .toolbar p { margin: 5px 0 12px; color: #475569; font-size: 13px; }
        .actions { display: flex; gap: 8px; }
        .button { display: inline-flex; align-items: center; padding: 10px 16px; border: 0; border-radius: 8px; background: #0369a1; color: white; font: inherit; font-weight: 700; text-decoration: none; cursor: pointer; }
        .preview { display: flex; justify-content: center; overflow: auto; padding: 18px; }
        .label-page { width: {{ $labelWidth }}mm; height: {{ $labelHeight }}mm; flex: 0 0 auto; background: white; }
        .label-page svg { display: block; width: 100%; height: 100%; }
        @page { size: {{ $labelWidth }}mm {{ $labelHeight }}mm; margin: 0; }
        @media print {
            body { padding: 0; background: white; }
            .toolbar { display: none; }
            .preview { display: block; overflow: visible; padding: 0; }
        }
    </style>
</head>
<body>
    <section class="toolbar">
        <strong>Label printer {{ $labelWidth }} × {{ $labelHeight }} mm</strong>
        <p>Pilih ukuran kertas yang sama pada driver printer dan gunakan skala 100% / Actual size.</p>
        <div class="actions">
            <button type="button" class="button" onclick="window.print()">Cetak Label QR</button>
        </div>
    </section>

    <main class="preview">
        <div class="label-page">
            @include('assets.partials.compact-qr-label', ['qrCode' => $qrCode])
        </div>
    </main>
</body>
</html>
