<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Label {{ $asset->asset_code }} | GearTrack</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 30px;
            font-family: Arial, Helvetica, sans-serif;
            background: #f1f5f9;
            color: #0f172a;
        }

        .toolbar {
            max-width: 500px;
            margin: 0 auto 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .toolbar-title {
            font-size: 14px;
            font-weight: 700;
        }

        .print-button {
            display: inline-flex;
            align-items: center;
            text-decoration: none;
            border: 0;
            border-radius: 8px;
            padding: 10px 16px;
            background: #0369a1;
            color: white;
            font-weight: 700;
            cursor: pointer;
        }

        .label {
            width: 85mm;
            height: 45mm;
            margin: 0 auto;
            padding: 4mm;

            display: flex;
            align-items: center;
            gap: 4mm;

            background: white;
            border: 1px solid #cbd5e1;
            border-radius: 3mm;
            overflow: hidden;
        }

        .qr {
            width: 33mm;
            height: 33mm;
            flex: 0 0 33mm;
        }

        .qr svg {
            display: block;
            width: 100%;
            height: 100%;
        }

        .info {
            flex: 1;
            min-width: 0;
        }

        .brand {
            margin-bottom: 2mm;
            color: #0369a1;
            font-size: 9pt;
            font-weight: 800;
        }

        .code {
            font-size: 12pt;
            font-weight: 800;
            line-height: 1.1;
        }

        .name {
            margin-top: 1.5mm;
            font-size: 8.5pt;
            font-weight: 700;
            line-height: 1.2;
        }

        .meta {
            margin-top: 1.5mm;
            color: #475569;
            font-size: 7.5pt;
            line-height: 1.3;
        }

        .hint {
            margin-top: 2mm;
            color: #64748b;
            font-size: 6.5pt;
        }

        @page {
            margin: 10mm;
        }

        @media print {
            body {
                padding: 0;
                background: white;
            }

            .toolbar {
                display: none;
            }

            .label {
                margin: 0;
                break-inside: avoid;
                page-break-inside: avoid;
            }
        }
    </style>
</head>

<body>

    <div class="toolbar">

        <div class="toolbar-title">
            Label {{ $asset->asset_code }}
        </div>

        <div style="display:flex;gap:8px">
            <a class="print-button" href="{{ route('asset.qr.label', ['token' => $asset->qr_token, 'test' => 1]) }}">
                Uji Ukuran 50 × 30 mm
            </a>
            <button
                type="button"
                class="print-button"
                onclick="window.print()"
            >
                Cetak Label
            </button>
        </div>

    </div>


    <div class="label">

        <div class="qr">
            {!! (string) $qrCode !!}
        </div>

        <div class="info">

            <div class="brand">
                GearTrack
            </div>

            <div class="code">
                {{ $asset->asset_code }}
            </div>

            <div class="name">
                {{ $asset->name }}
            </div>

            <div class="meta">
                {{ $asset->brand?->name ?? '-' }}

                @if ($asset->model)
                    • {{ $asset->model }}
                @endif
            </div>

            <div class="hint">
                Scan untuk informasi aset
            </div>

        </div>

    </div>

</body>
</html>
