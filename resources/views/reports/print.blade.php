<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }} — GearTrack</title>
    <style>
        body {font-family: Arial, sans-serif; color:#172033; padding:24px; font-size:12px;}
        h1 {font-size:22px;} p {line-height:1.6;} table {width:100%; border-collapse:collapse;}
        th,td {border:1px solid #cbd5e1; padding:8px; text-align:left; overflow-wrap:anywhere;}
        th {background:#e2e8f0;} .scroll {overflow:auto;} .tools {display:flex;gap:16px; margin-bottom:24px;}
        button,a {font:inherit;} @page {size:A4 landscape; margin:12mm;}
        @media print {body {padding:0; font-size:9px;} .tools {display:none;} .scroll {overflow:visible;} thead {display:table-header-group;} tr {break-inside:avoid;} }
    </style>
</head>
<body>
    <div class="tools"><button onclick="window.print()">Cetak / Simpan PDF</button><a href="{{ route('filament.admin.pages.reports') }}">Kembali ke laporan</a></div>
    <h1>GearTrack · {{ $title }}</h1>
    <p>Periode: {{ $period }} · Dibuat: {{ now()->format('d/m/Y H:i') }}<br>{{ $summary }}</p>
    <div class="scroll"><table><thead><tr>@foreach ($headers as $header)<th>{{ $header }}</th>@endforeach</tr></thead>
        <tbody>@forelse ($rows as $row)<tr>@foreach ($row as $value)<td>{{ $value ?? '—' }}</td>@endforeach</tr>
        @empty<tr><td colspan="{{ count($headers) }}">Tidak ada data sesuai filter.</td></tr>@endforelse</tbody>
    </table></div>
</body>
</html>
