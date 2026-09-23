<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>{{ $asset->asset_code }} | GearTrack</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Inter, system-ui, -apple-system, BlinkMacSystemFont,
                "Segoe UI", sans-serif;
            background: #f5f7fb;
            color: #172033;
        }

        .page {
            max-width: 520px;
            min-height: 100vh;
            margin: 0 auto;
            background: #ffffff;
        }

        .header {
            padding: 28px 24px 60px;
           background: #0369a1;
            color: white;
        }

        .brand {
            font-size: 21px;
            font-weight: 800;
            letter-spacing: -0.5px;
        }

        .brand-subtitle {
            margin-top: 4px;
            font-size: 13px;
            opacity: .8;
        }

        .content {
            padding: 0 20px 32px;
        }

        .asset-card {
            position: relative;
            margin-top: -32px;
            padding: 22px;
            background: white;
            border: 1px solid #e8eaf0;
            border-radius: 18px;
            box-shadow: 0 10px 30px rgba(30, 41, 59, .08);
        }

        .asset-code {
            color: #0284c7;
            font-size: 13px;
            font-weight: 700;
        }

        .asset-name {
            margin: 6px 0 14px;
            font-size: 24px;
            line-height: 1.25;
        }

        .badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 7px 11px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 700;
        }

        .badge-good {
            background: #ecfdf5;
            color: #047857;
        }

        .badge-minor {
            background: #fffbeb;
            color: #b45309;
        }

        .badge-major {
            background: #fef2f2;
            color: #b91c1c;
        }

        .section-title {
            margin: 28px 4px 10px;
            color: #64748b;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: .08em;
            text-transform: uppercase;
        }

        .details {
            overflow: hidden;
            background: white;
            border: 1px solid #e8eaf0;
            border-radius: 16px;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            gap: 20px;
            padding: 16px 18px;
            border-bottom: 1px solid #eef0f4;
        }

        .detail-row:last-child {
            border-bottom: none;
        }

        .label {
            color: #64748b;
            font-size: 14px;
        }

        .value {
            max-width: 60%;
            font-size: 14px;
            font-weight: 650;
            text-align: right;
            word-break: break-word;
        }

        .footer {
            padding: 28px 0 10px;
            color: #94a3b8;
            font-size: 12px;
            text-align: center;
        }

        .verified {
            margin-top: 5px;
            color: #64748b;
        }

        @media (min-width: 521px) {
            body {
                padding: 30px 0;
            }

            .page {
                min-height: auto;
                border: 1px solid #e8eaf0;
                border-radius: 22px;
                overflow: hidden;
                box-shadow: 0 20px 50px rgba(15, 23, 42, .08);
            }
        }





        .spec-section {
    margin-top: 20px;
}

.spec-heading {
    margin-bottom: 10px;

    color: #64748b;

    font-size: 11px;
    font-weight: 800;

    letter-spacing: .08em;
    text-transform: uppercase;
}

.spec-card {
    overflow: hidden;

    background: #ffffff;

    border: 1px solid #e2e8f0;
    border-radius: 14px;
}

.spec-item {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;

    gap: 20px;

    padding: 13px 14px;

    border-bottom: 1px solid #e2e8f0;
}

.spec-item:last-child {
    border-bottom: 0;
}

.spec-name {
    color: #64748b;

    font-size: 13px;
}

.spec-value {
    max-width: 60%;

    color: #0f172a;

    font-size: 13px;
    font-weight: 700;

    text-align: right;

    word-break: break-word;
}


.section {
    margin-top: 18px;
}

.section-title {
    margin-bottom: 10px;
    color: #0f172a;
    font-size: 13px;
    font-weight: 800;
    letter-spacing: .04em;
    text-transform: uppercase;
}

.spec-list {
    overflow: hidden;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    background: #ffffff;
}

.spec-row {
    display: flex;
    justify-content: space-between;
    gap: 20px;
    padding: 11px 13px;
    border-bottom: 1px solid #e2e8f0;
}

.spec-row:last-child {
    border-bottom: 0;
}

.spec-label {
    color: #64748b;
    font-size: 13px;
}

.spec-value {
    color: #0f172a;
    font-size: 13px;
    font-weight: 700;
    text-align: right;
    word-break: break-word;
}



    </style>
</head>

<body>

<div class="page">

    <header class="header">
        <div class="brand">GearTrack</div>
        <div class="brand-subtitle">Inventaris TKJ</div>
    </header>




    <main class="content">

        <section class="asset-card">

            <div class="asset-code">
                {{ $asset->asset_code }}
            </div>

            <h1 class="asset-name">
                {{ $asset->name }}
            </h1>

            @switch($asset->condition)

                @case('good')
                    <span class="badge badge-good">
                        ● Kondisi Baik
                    </span>
                    @break

                @case('minor_damage')
                    <span class="badge badge-minor">
                        ● Rusak Ringan
                    </span>
                    @break

                @case('major_damage')
                    <span class="badge badge-major">
                        ● Rusak Berat
                    </span>
                    @break

                @default
                    <span class="badge">
                        {{ $asset->condition }}
                    </span>

            @endswitch

        </section>


        <div class="section-title">
            Informasi Perangkat
        </div>

        <section class="details">

            <div class="detail-row">
                <span class="label">Kategori</span>
                <span class="value">
                    {{ $asset->category?->name ?? '-' }}
                </span>
            </div>

            <div class="detail-row">
                <span class="label">Merek</span>
                <span class="value">
                    {{ $asset->brand?->name ?? '-' }}
                </span>
            </div>

            <div class="detail-row">
                <span class="label">Model / Tipe</span>
                <span class="value">
                    {{ $asset->model ?? '-' }}
                </span>
            </div>

            <div class="detail-row">
                <span class="label">Nomor Seri</span>
                <span class="value">
                    {{ $asset->serial_number ?? '-' }}
                </span>
            </div>

            <div class="detail-row">
                <span class="label">Lokasi</span>
                <span class="value">
                    {{ $asset->location?->name ?? '-' }}
                </span>
            </div>

        </section>

        <div class="section-title">Paket Perangkat</div>
        <section class="details" aria-label="Paket Perangkat">
            @if ($asset->assetSet)
                <div class="detail-row">
                    <span class="label">Paket</span>
                    <span class="value">{{ $asset->assetSet->name }}<br>{{ $asset->assetSet->code }}</span>
                </div>
                <div class="detail-row">
                    <span class="label">Status Paket</span>
                    <span class="value">{{ $asset->assetSet->is_active ? 'Aktif' : 'Tidak Aktif' }}</span>
                </div>
                <div class="detail-row">
                    <span class="label">Lokasi Paket</span>
                    <span class="value">{{ $asset->assetSet->location?->name ?? 'Belum ditentukan' }}</span>
                </div>
                <div class="detail-row">
                    <span class="label">Peran Perangkat Ini</span>
                    <span class="value">{{ \App\Models\Asset::SET_ROLES[$asset->set_role] ?? 'Belum ditentukan' }}</span>
                </div>
                <div class="detail-row">
                    <span class="label">Kelengkapan Tanpa QR</span>
                    <span class="value">{{ $asset->assetSet->accessories ?: 'Belum diisi' }}</span>
                </div>
                @foreach ($asset->assetSet->assets->sortBy('asset_code') as $member)
                    <div class="detail-row">
                        <span class="label">{{ \App\Models\Asset::SET_ROLES[$member->set_role] ?? 'Belum ditentukan' }}</span>
                        <span class="value">
                            @if ($member->is($asset))
                                {{ $member->name }}<br>{{ $member->asset_code }} · Perangkat ini
                            @else
                                <a href="{{ route('asset.qr.show', ['token' => $member->qr_token]) }}">{{ $member->name }}<br>{{ $member->asset_code }}</a>
                            @endif
                        </span>
                    </div>
                @endforeach
            @else
                <div class="detail-row"><span class="label">Perangkat ini belum tergabung dalam paket.</span></div>
            @endif
        </section>

        @if ($asset->specifications->isNotEmpty())
            <div class="section-title">Spesifikasi Teknis</div>
            <section class="details" aria-label="Spesifikasi Teknis">
                @foreach ($asset->specifications as $spec)
                    <div class="detail-row">
                        <span class="label">{{ $spec->label }}</span>
                        <span class="value">{{ filled($spec->value) ? $spec->value : 'Belum diisi' }}</span>
                    </div>
                @endforeach
            </section>
        @endif
        <footer class="footer">
            <strong>GearTrack</strong>
            <div class="verified">
                Data inventaris terverifikasi
            </div>
        </footer>

    </main>

</div>

</body>
</html>
