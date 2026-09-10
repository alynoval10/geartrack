<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Cetak Label Aset | GearTrack</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 24px;
            font-family: Arial, Helvetica, sans-serif;
            background: #f1f5f9;
            color: #0f172a;
        }

        .toolbar {
            max-width: 210mm;
            margin: 0 auto 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .toolbar-title {
            font-size: 16px;
            font-weight: 700;
        }

        .toolbar-info {
            margin-top: 4px;
            color: #64748b;
            font-size: 13px;
        }

        .print-button {
            border: 0;
            border-radius: 8px;
            padding: 10px 18px;
            background: #0369a1;
            color: white;
            font-weight: 700;
            cursor: pointer;
        }

        .sheet {
            width: 100%;
            max-width: 210mm;
            margin: 0 auto;

            display: grid;
            grid-template-columns: repeat(2, 85mm);
            gap: 5mm;

            justify-content: center;
        }

        .label {
            width: 85mm;
            height: 45mm;
            padding: 4mm;

            display: flex;
            align-items: center;
            gap: 4mm;

            background: white;
            border: 1px solid #cbd5e1;
            border-radius: 3mm;

            overflow: hidden;
            break-inside: avoid;
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
            size: A4 portrait;
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

            .sheet {
                gap: 5mm;
            }

            .label {
                page-break-inside: avoid;
                break-inside: avoid;
            }
        }
    </style>
</head>

<body>

    <div class="toolbar">

        <div>
            <div class="toolbar-title">
                Label Aset GearTrack
            </div>

            <div class="toolbar-info">
                {{ $assets->count() }} label siap dicetak
            </div>
        </div>

        <button
            type="button"
            class="print-button"
            onclick="window.print()"
        >
            Cetak Semua
        </button>

    </div>


    <div class="sheet">

        @foreach ($assets as $asset)

            <div class="label">

                <div class="qr">
                    {!! (string) $asset->generatedQr !!}
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

        @endforeach

    </div>

</body>
</html>