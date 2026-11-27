<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kartu QR {{ $borrower->name }} | GearTrack</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; padding: 24px; background: #f1f5f9; color: #0f172a; font-family: Arial, Helvetica, sans-serif; }
        .toolbar { max-width: 210mm; margin: 0 auto 16px; display: flex; align-items: center; justify-content: space-between; gap: 16px; }
        .toolbar-title { font-size: 15px; font-weight: 700; }
        .toolbar-hint { margin-top: 4px; color: #64748b; font-size: 12px; }
        .print-button { border: 0; border-radius: 8px; padding: 10px 16px; background: #0369a1; color: white; font-weight: 700; cursor: pointer; }
        .page { width: 210mm; min-height: 297mm; margin: 0 auto; display: grid; place-items: center; background: white; box-shadow: 0 4px 18px #0f172a1a; }
        .card { width: 85mm; height: 54mm; padding: 4mm; display: flex; align-items: center; gap: 4mm; border: 1px solid #94a3b8; border-radius: 3mm; overflow: hidden; }
        .qr { width: 39mm; height: 39mm; flex: 0 0 39mm; }
        .qr svg { display: block; width: 100%; height: 100%; }
        .info { min-width: 0; flex: 1; }
        .school { margin-bottom: 2mm; color: #0369a1; font-size: 7pt; font-weight: 800; letter-spacing: .04em; text-transform: uppercase; }
        .title { margin-bottom: 1mm; color: #64748b; font-size: 6.5pt; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; }
        .name { overflow-wrap: anywhere; font-size: 11pt; font-weight: 800; line-height: 1.15; }
        .nis { margin-top: 2mm; font-size: 8pt; }
        .class { margin-top: 1mm; color: #334155; font-size: 8pt; }
        .year { margin-top: 1mm; color: #64748b; font-size: 7pt; }
        @page { size: A4 portrait; margin: 0; }
        @media print {
            body { padding: 0; background: white; }
            .toolbar { display: none; }
            .page { width: 210mm; height: 297mm; min-height: 0; box-shadow: none; }
            .card { border-color: #64748b; }
        }
        @media screen and (max-width: 700px) {
            body { padding: 12px; }
            .page { width: 100%; min-height: 75vh; }
            .toolbar { align-items: flex-start; }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <div>
            <div class="toolbar-title">Kartu QR Siswa</div>
            <div class="toolbar-hint">Cetak pada kertas A4. QR hanya berisi token identitas acak.</div>
        </div>
        <button class="print-button" type="button" onclick="window.print()">Cetak Kartu</button>
    </div>

    <main class="page">
        <article class="card">
            <div class="qr">{!! (string) $qrCode !!}</div>
            <div class="info">
                <div class="school">{{ $schoolName }}</div>
                <div class="title">Kartu Peminjam</div>
                <div class="name">{{ $borrower->name }}</div>
                <div class="nis">NIS: {{ $borrower->nis }}</div>
                <div class="class">{{ $borrower->class_level }} {{ $borrower->class_name }}</div>
                <div class="year">Tahun ajaran {{ $borrower->academic_year }}</div>
            </div>
        </article>
    </main>
</body>
</html>
