<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Scan QR - GearTrack</title>

  @vite(['resources/css/app.css', 'resources/js/scanner.js'])

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: Inter, ui-sans-serif, system-ui, sans-serif;
            background: #07101c;
            color: #fff;
        }

        .page {
            width: min(100% - 32px, 520px);
            margin: 0 auto;
            padding: 36px 0;
        }

        .brand {
            margin-bottom: 30px;
            font-size: 21px;
            font-weight: 800;
            letter-spacing: -.03em;
        }

        .brand span {
            color: #0ea5e9;
        }

        h1 {
            margin: 0;
            font-size: 30px;
            letter-spacing: -.03em;
        }

        .subtitle {
            margin: 8px 0 24px;
            color: #94a3b8;
            line-height: 1.6;
        }

        .scanner-card {
            padding: 16px;
            border: 1px solid #253247;
            border-radius: 20px;
            background: #101a2a;
        }

        #reader {
            overflow: hidden;
            width: 100%;
            border-radius: 14px;
            background: #000;
        }

        #status {
            margin-top: 16px;
            color: #94a3b8;
            font-size: 14px;
            line-height: 1.5;
            text-align: center;
        }

        .success {
            color: #34d399 !important;
        }

        .error {
            color: #fb7185 !important;
        }

        .divider {
            display: flex;
            align-items: center;
            gap: 12px;
            margin: 22px 0;
            color: #64748b;
            font-size: 12px;
        }

        .divider::before,
        .divider::after {
            content: "";
            height: 1px;
            flex: 1;
            background: #253247;
        }

        .file-button {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 9px;

            width: 100%;
            min-height: 48px;

            border: 1px solid #334155;
            border-radius: 12px;

            background: #172235;
            color: #fff;

            font-size: 14px;
            font-weight: 700;

            cursor: pointer;
        }

        .file-button:hover {
            border-color: #0284c7;
            background: #1b2b42;
        }

        .camera-controls {
            display: grid;
            gap: 12px;
            margin-top: 16px;
        }

        .camera-button {
            background: #0369a1;
            border-color: #0284c7;
        }

        button:disabled {
            opacity: .55;
            cursor: not-allowed;
        }

        button:focus-visible, select:focus-visible, .file-button:focus-within {
            outline: 3px solid #38bdf8;
            outline-offset: 3px;
        }

        .camera-choice label {
            display: block;
            margin-bottom: 8px;
            font-size: 14px;
        }

        .camera-choice select {
            width: 100%;
            padding: 12px;
            border: 1px solid #334155;
            border-radius: 10px;
            background: #172235;
            color: #fff;
        }

        [hidden] {
            display: none !important;
        }

        .file-button svg {
            width: 20px;
            height: 20px;
        }

        #qr-file {
            display: none;
        }

        .hint {
            margin: 10px 0 0;
            color: #64748b;
            font-size: 12px;
            line-height: 1.5;
            text-align: center;
        }

        .back {
            display: inline-flex;
            margin-top: 22px;
            color: #38bdf8;
            font-size: 14px;
            text-decoration: none;
        }
    </style>
</head>

<body>

<div class="page">

    <div class="brand">
        Gear<span>Track</span>
    </div>

    <h1>Scan QR Aset</h1>

    <p class="subtitle">
        Arahkan kamera ke label QR GearTrack.
        Detail aset akan terbuka secara otomatis.
    </p>

    @if ($stockTakeId)
        <p class="subtitle">Mode stock opname · Sesi #{{ $stockTakeId }}. Scan aset, lalu pilih Catat Stock Opname.</p>
    @endif

    <div class="scanner-card">

        <div id="reader" data-stock-take="{{ $stockTakeId }}"></div>

        <div id="status" role="status" aria-live="polite">
            Menyiapkan scanner...
        </div>

        <div class="camera-controls">
            <div id="camera-choices" class="camera-choice" hidden>
                <label for="camera-select">Pilih Kamera</label>
                <select id="camera-select">
                    <option value="">Otomatis — utamakan kamera belakang</option>
                </select>
                <p class="hint">Hentikan kamera terlebih dahulu untuk mengganti kamera.</p>
            </div>
            <button id="start-camera" type="button" class="file-button camera-button">Aktifkan Kamera</button>
            <button id="stop-camera" type="button" class="file-button" hidden>Hentikan Kamera</button>
        </div>

        <noscript><p class="error">Aktifkan JavaScript di Chrome untuk memindai QR.</p></noscript>

        <div class="divider">
            ATAU
        </div>

        <label for="qr-file" class="file-button">
            <svg
                xmlns="http://www.w3.org/2000/svg"
                fill="none"
                viewBox="0 0 24 24"
                stroke-width="1.8"
                stroke="currentColor"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M6.827 6.175A2.31 2.31 0 0 1
                    9.08 4.5h5.84a2.31 2.31 0 0 1
                    2.253 1.675l.172.605a2.31 2.31
                    0 0 0 2.253 1.675H20.25A2.25
                    2.25 0 0 1 22.5 10.705v6.545a2.25
                    2.25 0 0 1-2.25 2.25H3.75A2.25
                    2.25 0 0 1 1.5 17.25v-6.545a2.25
                    2.25 0 0 1 2.25-2.25h.652A2.31
                    2.31 0 0 0 6.655 6.78l.172-.605Z"
                />
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M15.75 13.5a3.75 3.75
                    0 1 1-7.5 0 3.75 3.75
                    0 0 1 7.5 0Z"
                />
            </svg>

            Foto / Pilih QR
        </label>

        <input
            type="file"
            id="qr-file"
            accept="image/*"
            capture="environment"
        >

        <p class="hint">
            Kamera live membaca QR secara otomatis tanpa mengambil foto.
            Foto / Pilih QR tetap tersedia sebagai alternatif.
        </p>

    </div>

    <a href="{{ url('/') }}" class="back">
        ← Kembali ke GearTrack
    </a>

</div>




</body>
</html>
