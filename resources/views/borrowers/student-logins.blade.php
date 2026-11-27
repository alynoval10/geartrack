<div>
    <!-- No surplus words or unnecessary actions. - Marcus Aurelius -->
</div>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Akun Login Siswa {{ $classLevel }} | GearTrack</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; padding: 24px; background: #f1f5f9; color: #0f172a; font-family: Arial, Helvetica, sans-serif; }
        .toolbar { max-width: 210mm; margin: 0 auto 16px; display: flex; align-items: center; justify-content: space-between; gap: 16px; }
        .toolbar-title { font-size: 16px; font-weight: 700; }
        .toolbar-hint { margin-top: 4px; color: #64748b; font-size: 12px; }
        .toolbar-actions { display: flex; gap: 8px; }
        .button { display: inline-flex; align-items: center; justify-content: center; border: 0; border-radius: 8px; padding: 10px 16px; background: #0369a1; color: white; font-size: 13px; font-weight: 700; text-decoration: none; cursor: pointer; }
        .button-secondary { background: white; color: #0f172a; border: 1px solid #cbd5e1; }
        .sheet { width: 190mm; min-height: 277mm; margin: 0 auto 18px; padding: 12mm; background: white; box-shadow: 0 4px 18px #0f172a1a; break-after: page; page-break-after: always; }
        .sheet:last-child { break-after: auto; page-break-after: auto; }
        .heading { padding-bottom: 7mm; border-bottom: 2px solid #0f172a; }
        .school { color: #0369a1; font-size: 9pt; font-weight: 800; letter-spacing: .05em; text-transform: uppercase; }
        h1 { margin: 2mm 0; font-size: 17pt; }
        .metadata { color: #475569; font-size: 9pt; }
        .notice { margin: 6mm 0; padding: 3mm; border: 1px solid #cbd5e1; border-radius: 2mm; color: #334155; font-size: 8.5pt; line-height: 1.5; }
        table { width: 100%; border-collapse: collapse; font-size: 8.5pt; }
        th, td { padding: 2.5mm 2mm; border: 1px solid #cbd5e1; text-align: left; vertical-align: top; }
        th { background: #e2e8f0; font-weight: 700; }
        .number { width: 9mm; text-align: center; }
        .username { font-weight: 700; white-space: nowrap; }
        .password-status { width: 48mm; }
        .empty { width: 210mm; margin: 0 auto; padding: 24px; background: white; text-align: center; }
        @page { size: A4 portrait; margin: 10mm; }
        @media print {
            body { padding: 0; background: white; }
            .toolbar, .empty { display: none; }
            .sheet { width: auto; min-height: 277mm; margin: 0; padding: 2mm; box-shadow: none; }
            tr { break-inside: avoid; page-break-inside: avoid; }
            thead { display: table-header-group; }
        }
        @media screen and (max-width: 700px) {
            body { padding: 12px; }
            .toolbar { align-items: flex-start; flex-direction: column; }
            .sheet { width: 100%; min-height: 0; padding: 18px; overflow-x: auto; }
            table { min-width: 600px; }
        }
    </style>
</head>
<body>
    <header class="toolbar">
        <div>
            <div class="toolbar-title">Akun Login Siswa — {{ $className ? $classLevel.' '.$className : 'Tingkat '.$classLevel }}</div>
            <div class="toolbar-hint">{{ $borrowers->count() }} siswa aktif · Tahun ajaran {{ $academicYear ?: 'belum diatur' }} · Kertas A4</div>
        </div>
        <div class="toolbar-actions">
            <a class="button button-secondary" href="{{ route('filament.admin.resources.borrowers.index') }}">Kembali</a>
            <button class="button" type="button" onclick="window.print()" @disabled($borrowers->isEmpty())>Cetak A4</button>
        </div>
    </header>

    @if ($borrowers->isEmpty())
        <main class="empty">Belum ada siswa aktif pada pilihan tingkat atau rombel ini.</main>
    @else
        @foreach ($borrowers->chunk(28) as $pageBorrowers)
            <main class="sheet">
                <header class="heading">
                    <div class="school">{{ $schoolName ?: 'GearTrack' }}</div>
                    <h1>Daftar Akun Login Siswa</h1>
                    <div class="metadata">{{ $className ? $classLevel.' '.$className : 'Tingkat '.$classLevel }} · Tahun Ajaran {{ $academicYear }}</div>
                </header>
                <div class="notice">
                    Alamat login: <strong>{{ $loginUrl }}</strong><br>
                    Username siswa menggunakan NIS. Kata sandi awal sama dengan NIS dan wajib diganti saat masuk pertama kali. Untuk akun yang sudah mengganti kata sandi, gunakan kata sandi pribadi; hubungi administrator jika lupa.
                </div>
                <table>
                    <thead>
                        <tr>
                            <th class="number">No.</th>
                            <th>Nama Siswa</th>
                            <th>Kelas</th>
                            <th>Username (NIS)</th>
                            <th class="password-status">Status Kata Sandi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($pageBorrowers as $index => $borrower)
                            <tr>
                                <td class="number">{{ $loop->parent->iteration === 1 ? $index + 1 : ($loop->parent->iteration - 1) * 28 + $index + 1 }}</td>
                                <td>{{ $borrower->name }}</td>
                                <td>{{ $borrower->class_level }} {{ $borrower->class_name }}</td>
                                <td class="username">{{ $borrower->nis }}</td>
                                <td>
                                    @if ($borrower->studentAccount?->must_change_password ?? true)
                                        Kata sandi awal: NIS
                                    @else
                                        Sudah diganti
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </main>
        @endforeach
    @endif
</body>
</html>
