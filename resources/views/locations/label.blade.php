<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Label {{ $location->fullName() }} | GearTrack</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; padding: 24px; font-family: Arial, Helvetica, sans-serif; color: #0f172a; background: #f1f5f9; }
        .toolbar { max-width: 210mm; margin: 0 auto 20px; display: flex; align-items: center; justify-content: space-between; gap: 16px; }
        .toolbar-title { font-size: 16px; font-weight: 700; }
        .toolbar-info { margin-top: 4px; color: #64748b; font-size: 13px; }
        .print-button { border: 0; border-radius: 8px; padding: 10px 18px; color: #fff; background: #0369a1; font: inherit; font-weight: 700; cursor: pointer; }
        .sheet { width: 190mm; min-height: 277mm; margin: 0 auto; padding: 0; }
        .label { width: 85mm; min-height: 45mm; padding: 5mm; display: flex; flex-direction: column; justify-content: center; border: 1px solid #94a3b8; border-radius: 2mm; background: #fff; break-inside: avoid; }
        .brand { color: #0369a1; font-size: 8pt; font-weight: 800; letter-spacing: 1.2pt; text-transform: uppercase; }
        .name { margin-top: 2mm; font-size: 19pt; font-weight: 800; line-height: 1.1; overflow-wrap: anywhere; }
        .parent { margin-top: 2mm; color: #334155; font-size: 10pt; }
        .code { margin-top: 3mm; color: #64748b; font-size: 8pt; }
        @page { size: A4 portrait; margin: 10mm; }
        @media print {
            body { padding: 0; background: #fff; }
            .toolbar { display: none; }
            .sheet { width: auto; min-height: 0; margin: 0; }
            .label { print-color-adjust: exact; -webkit-print-color-adjust: exact; }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <div>
            <div class="toolbar-title">Label Lokasi GearTrack</div>
            <div class="toolbar-info">Ukuran label 85 × 45 mm pada kertas A4. Cetak dengan skala 100%.</div>
        </div>
        <button type="button" class="print-button" onclick="window.print()">Cetak Label A4</button>
    </div>

    <main class="sheet">
        <section class="label" aria-label="Label lokasi {{ $location->fullName() }}">
            <div class="brand">Lokasi Penyimpanan</div>
            <div class="name">{{ $location->name }}</div>
            @if ($location->parent)
                <div class="parent">{{ $location->parent->name }}</div>
            @endif
            <div class="code">Kode: {{ $location->code ?: '—' }}</div>
        </section>
    </main>
</body>
</html>
