<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak QR Siswa Tingkat {{ $classLevel }} | GearTrack</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; padding: 24px; background: #f1f5f9; color: #0f172a; font-family: Arial, Helvetica, sans-serif; }
        .toolbar { max-width: 210mm; margin: 0 auto 16px; display: flex; align-items: center; justify-content: space-between; gap: 16px; }
        .toolbar-title { font-size: 16px; font-weight: 700; }
        .toolbar-hint { margin-top: 4px; color: #64748b; font-size: 12px; }
        .toolbar-actions { display: flex; align-items: center; gap: 8px; }
        .button { display: inline-flex; align-items: center; justify-content: center; border: 0; border-radius: 8px; padding: 10px 16px; background: #0369a1; color: white; font-size: 13px; font-weight: 700; text-decoration: none; cursor: pointer; }
        .button-secondary { background: white; color: #0f172a; border: 1px solid #cbd5e1; }
        .sheet { width: 200mm; height: 287mm; margin: 0 auto 18px; padding: .5mm 0; display: grid; grid-template-columns: repeat(2, 85mm); grid-template-rows: repeat(5, 54mm); gap: 4mm; justify-content: center; align-content: start; background: white; box-shadow: 0 4px 18px #0f172a1a; break-after: page; page-break-after: always; }
        .sheet:last-child { break-after: auto; page-break-after: auto; }
        .card { width: 85mm; height: 54mm; padding: 4mm; display: flex; align-items: center; gap: 3mm; border: 1px solid #94a3b8; border-radius: 3mm; overflow: hidden; }
        .qr { width: 30mm; height: 30mm; flex: 0 0 30mm; }
        .qr svg { display: block; width: 100%; height: 100%; }
        .info { min-width: 0; flex: 1; }
        .school { margin-bottom: 1.5mm; color: #0369a1; font-size: 6.5pt; font-weight: 800; letter-spacing: .04em; text-transform: uppercase; }
        .title { margin-bottom: 1mm; color: #64748b; font-size: 6pt; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; }
        .name { overflow-wrap: anywhere; font-size: 9.5pt; font-weight: 800; line-height: 1.12; }
        .nis { margin-top: 1.5mm; font-size: 7.5pt; }
        .class { margin-top: 1mm; color: #334155; font-size: 7.5pt; }
        .year { margin-top: 1mm; color: #64748b; font-size: 6.5pt; }
        .empty { width: 210mm; min-height: 80mm; margin: 0 auto; padding: 24px; display: grid; place-content: center; text-align: center; background: white; border: 1px solid #cbd5e1; border-radius: 12px; }
        .empty-title { font-weight: 700; }
        .empty-hint { margin-top: 6px; color: #64748b; font-size: 13px; }
        @page { size: A4 portrait; margin: 5mm; }
        @media print {
            body { padding: 0; background: white; }
            .toolbar, .empty { display: none; }
            .sheet { width: 200mm; height: 287mm; margin: 0; box-shadow: none; }
        }
        @media screen and (max-width: 700px) {
            body { padding: 12px; }
            .toolbar { align-items: flex-start; flex-direction: column; }
            .sheet { transform-origin: top left; }
        }
    </style>
</head>
<body>
    <header class="toolbar">
        <div>
            <div class="toolbar-title">Cetak QR Siswa — {{ $className ? $classLevel.' '.$className : 'Tingkat '.$classLevel }}</div>
            <div class="toolbar-hint">
                {{ $qrCards->count() }} siswa aktif · Tahun ajaran {{ $academicYear ?: 'belum diatur' }} · Kertas A4
            </div>
        </div>
        <div class="toolbar-actions">
            <a class="button button-secondary" href="{{ $backUrl }}">Kembali</a>
            <button class="button" type="button" onclick="window.print()" @disabled($qrCards->isEmpty())>Cetak A4</button>
        </div>
    </header>

    @if ($qrCards->isEmpty())
        <div class="empty">
            <div class="empty-title">Belum ada siswa aktif untuk dicetak</div>
            <div class="empty-hint">
                @if (blank($academicYear))
                    Atur tahun ajaran aktif di Pengaturan Sekolah terlebih dahulu.
                @else
                    Periksa kembali tingkat, rombel, dan tahun ajaran aktif.
                @endif
            </div>
        </div>
    @else
        @foreach ($qrCards->chunk(10) as $pageCards)
            <main class="sheet">
                @foreach ($pageCards as $card)
                    <article class="card">
                        <div class="qr">{!! (string) $card['qrCode'] !!}</div>
                        <div class="info">
                            <div class="school">{{ $schoolName ?: 'GearTrack' }}</div>
                            <div class="title">Kartu Peminjam</div>
                            <div class="name">{{ $card['borrower']->name }}</div>
                            <div class="nis">NIS: {{ $card['borrower']->nis }}</div>
                            <div class="class">{{ $card['borrower']->class_level }} {{ $card['borrower']->class_name }}</div>
                            <div class="year">Tahun ajaran {{ $card['borrower']->academic_year }}</div>
                        </div>
                    </article>
                @endforeach
            </main>
        @endforeach
    @endif
</body>
</html>
