<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Label {{ $location->fullName() }} | GearTrack</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; padding: 24px; font-family: Arial, Helvetica, sans-serif; color: #0f172a; background: #f1f5f9; }
        .toolbar { max-width: 297mm; margin: 0 auto 20px; display: flex; align-items: center; justify-content: space-between; gap: 16px; }
        .toolbar-title { font-size: 16px; font-weight: 700; }
        .toolbar-info { margin-top: 4px; color: #64748b; font-size: 13px; }
        .print-button { border: 0; border-radius: 8px; padding: 10px 18px; color: #fff; background: #0369a1; font: inherit; font-weight: 700; cursor: pointer; }
        .sheet { position: relative; width: 297mm; height: 210mm; margin: 0 auto; background: #fff; box-shadow: 0 8px 30px rgb(15 23 42 / 12%); }
        .label { position: absolute; top: 50%; left: 50%; width: 230mm; height: 120mm; padding: 14mm; display: flex; flex-direction: column; align-items: center; justify-content: center; transform: translate(-50%, -50%); border: 1.2mm solid #94a3b8; border-radius: 5mm; background: #fff; text-align: center; break-inside: avoid; }
        .brand { color: #0369a1; font-size: 14pt; font-weight: 800; letter-spacing: 2pt; text-transform: uppercase; }
        .name { margin-top: 6mm; font-size: 44pt; font-weight: 800; line-height: 1.1; overflow-wrap: anywhere; }
        .parent { margin-top: 5mm; color: #334155; font-size: 22pt; }
        .code { margin-top: 7mm; color: #64748b; font-size: 14pt; }
        @page { size: A4 landscape; margin: 0; }
        @media print {
            body { padding: 0; background: #fff; }
            .toolbar { display: none; }
            .sheet { width: 297mm; height: 210mm; margin: 0; box-shadow: none; }
            .label { print-color-adjust: exact; -webkit-print-color-adjust: exact; }
        }
        @media screen and (max-width: 760px) {
            body { padding: 16px; }
            .toolbar { align-items: flex-start; }
            .sheet { width: 100%; height: auto; aspect-ratio: 297 / 210; }
            .label { width: calc(100% - 24px); height: auto; aspect-ratio: 23 / 12; padding: clamp(16px, 4vw, 40px); }
            .brand { font-size: clamp(9px, 2.5vw, 14pt); }
            .name { margin-top: 2vw; font-size: clamp(22px, 7vw, 44pt); }
            .parent { margin-top: 1.5vw; font-size: clamp(13px, 3.5vw, 22pt); }
            .code { margin-top: 2vw; font-size: clamp(9px, 2.5vw, 14pt); }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <div>
            <div class="toolbar-title">Label Lokasi GearTrack</div>
            <div class="toolbar-info">Label 230 × 120 mm berada di tengah kertas A4 landscape. Nonaktifkan Header dan footer pada dialog cetak.</div>
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
