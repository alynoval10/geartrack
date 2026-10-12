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
        .signatures {display:flex;justify-content:space-between;gap:24px;margin-top:40px;break-inside:avoid;} .signature {width:280px;text-align:center;}
        .signature-name {display:block;margin-top:65px;min-height:1em;text-decoration:underline;} .signature-name:empty {border-bottom:1px solid #172033;text-decoration:none;}
        @media print {body {padding:0; font-size:9px;} .tools {display:none;} .scroll {overflow:visible;} thead {display:table-header-group;} tr {break-inside:avoid;} }
    </style>
</head>
<body>
    <div class="tools"><button onclick="window.print()">Cetak / Simpan PDF</button><a href="{{ route('filament.admin.pages.reports') }}">Kembali ke laporan</a></div>
    <div style="display:flex;align-items:center;gap:16px;border-bottom:2px solid #172033;padding-bottom:12px">
        @if ($school->logo)<img src="{{ Storage::disk('public')->url($school->logo) }}" alt="Logo" style="width:70px;height:70px;object-fit:contain">@endif
        <div><strong style="font-size:18px">{{ $school->school_name }}</strong><br>{{ $school->address }}<br>Tahun Ajaran {{ $school->academic_year ?: '—' }}</div>
    </div>
    <h1>{{ $title }}</h1>
    <p>Periode: {{ $period }} · Dibuat: {{ now()->format('d/m/Y H:i') }}<br>{{ $summary }}</p>
    <div class="scroll"><table><thead><tr>@foreach ($headers as $header)<th>{{ $header }}</th>@endforeach</tr></thead>
        <tbody>@forelse ($rows as $row)<tr>@foreach ($row as $value)<td>{{ $value ?? '—' }}</td>@endforeach</tr>
        @empty<tr><td colspan="{{ count($headers) }}">Tidak ada data sesuai filter.</td></tr>@endforelse</tbody>
    </table></div>
    <div class="signatures">
        @if ($responsible)
            <div class="signature">
                Penanggung Jawab<br><strong class="signature-name">{{ $responsible->name }}</strong>
            </div>
        @endif
        <div class="signature">
            {{ $school->report_signer_title ?: 'Kepala Program Keahlian' }}<br><strong class="signature-name">{{ $school->report_signer_name }}</strong>
            <span>NIP. {{ $school->report_signer_nip ?: '—' }}</span>
        </div>
    </div>
</body>
</html>
