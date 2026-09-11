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

    <div class="scanner-card">

        <div id="reader"></div>

        <div id="status">
            Menyiapkan kamera...
        </div>

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
            Jika kamera live tidak tersedia, ambil foto QR menggunakan kamera HP.
        </p>

    </div>

    <a href="{{ url('/') }}" class="back">
        ← Kembali ke GearTrack
    </a>

</div>


<script>
document.addEventListener('DOMContentLoaded', async () => {

    const status = document.getElementById('status');
    const fileInput = document.getElementById('qr-file');

    const scanner = new Html5Qrcode('reader');

    let scanned = false;
    let cameraRunning = false;


    /*
    |--------------------------------------------------------------------------
    | Proses hasil QR
    |--------------------------------------------------------------------------
    */
    const processQr = async (decodedText) => {

        if (scanned) {
            return;
        }

        let url;

        try {
            url = new URL(decodedText);
        } catch {
            status.textContent = 'QR tidak dikenali sebagai label GearTrack.';
            status.className = 'error';
            return;
        }

        /*
         * QR GearTrack:
         * /q/{token}
         */
        if (! /^\/q\/[^\/]+\/?$/.test(url.pathname)) {
            status.textContent = 'QR ini bukan label aset GearTrack.';
            status.className = 'error';
            return;
        }

        scanned = true;

        status.textContent = 'Aset ditemukan. Membuka detail...';
        status.className = 'success';

        if (cameraRunning) {
            try {
                await scanner.stop();
                cameraRunning = false;
            } catch (error) {
                console.warn(error);
            }
        }

        /*
         * Gunakan host GearTrack yang sedang dibuka.
         * Jadi kalau QR masih menyimpan IP lama,
         * path token tetap dapat digunakan.
         */
        window.location.href =
            window.location.origin + url.pathname;
    };


    /*
    |--------------------------------------------------------------------------
    | Kamera Live
    |--------------------------------------------------------------------------
  /*
|--------------------------------------------------------------------------
| Kamera Live
|--------------------------------------------------------------------------
*/
try {

    if (typeof Html5Qrcode === 'undefined') {
        throw new Error('Library html5-qrcode belum termuat.');
    }

    status.textContent = 'Meminta izin kamera...';
    status.className = '';

    await scanner.start(
        {
            facingMode: 'environment'
        },
        {
            fps: 10,

            qrbox: {
                width: 240,
                height: 240,
            },

            aspectRatio: 1.0,
        },

        processQr,

        () => {}
    );

    cameraRunning = true;

    status.textContent =
        'Kamera aktif — arahkan ke QR aset.';

    status.className = '';

} catch (error) {

    console.error('Camera error:', error);

    status.textContent =
        'Kamera gagal dibuka: ' +
        (error?.message ?? error);

    status.className = 'error';
}


    /*
    |--------------------------------------------------------------------------
    | Foto / File QR
    |--------------------------------------------------------------------------
    */
    fileInput.addEventListener('change', async (event) => {

        const file = event.target.files[0];

        if (! file) {
            return;
        }

        scanned = false;

        status.textContent = 'Membaca QR dari gambar...';
        status.className = '';

        try {

            /*
             * scanFile perlu scanner dalam keadaan berhenti.
             */
            if (cameraRunning) {
                try {
                    await scanner.stop();
                    cameraRunning = false;
                } catch (error) {
                    console.warn(error);
                }
            }

            const decodedText =
                await scanner.scanFile(file, true);

            await processQr(decodedText);

        } catch (error) {

            console.error(error);

            status.textContent =
                'QR tidak ditemukan pada gambar. Coba foto lebih dekat dan pastikan QR terlihat jelas.';

            status.className = 'error';

            fileInput.value = '';
        }

    });

});
</script>

</body>
</html>