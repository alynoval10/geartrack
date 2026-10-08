<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        Cetak NIIMBOT - {{ $asset->asset_code }}
    </title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 24px;
            background: #f1f5f9;
            color: #0f172a;
            font-family: Arial, Helvetica, sans-serif;
        }

        .card {
            width: 100%;
            max-width: 560px;
            margin: 0 auto;
            padding: 24px;
            background: white;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgb(15 23 42 / 10%);
        }

        h1 {
            margin-top: 0;
        }

        .info {
            color: #475569;
            line-height: 1.6;
        }

        .preview {
            margin: 24px auto;
            width: 384px;
            max-width: 100%;
            aspect-ratio: 384 / 240;
            display: flex;
            align-items: center;
            justify-content: center;
            background: white;
            border: 1px solid #cbd5e1;
            overflow: hidden;
        }

        .preview svg {
            width: 100%;
            height: 100%;
            display: block;
        }

        button {
            width: 100%;
            padding: 14px 18px;
            border: 0;
            border-radius: 10px;
            background: #16a34a;
            color: white;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
        }

        button:disabled {
            opacity: .6;
            cursor: wait;
        }

        #status {
            margin-top: 16px;
            padding: 12px;
            background: #f8fafc;
            border-radius: 10px;
            white-space: pre-wrap;
            line-height: 1.5;
        }
    </style>
</head>

<body>

<div class="card">

    <h1>Cetak NIIMBOT</h1>

    <div class="info">
        <strong>{{ $asset->asset_code }}</strong><br>
        {{ $asset->name }}
    </div>

    <div class="preview">
        <svg
            id="niimbotLabel"
            xmlns="http://www.w3.org/2000/svg"
            width="384"
            height="240"
            viewBox="0 0 384 240"
        >
            <rect
                x="0"
                y="0"
                width="384"
                height="240"
                fill="white"
            />

            <g transform="translate(12 30)">
                {!! (string) $qrCode !!}
            </g>

            <text
                x="205"
                y="85"
                font-family="Arial, Helvetica, sans-serif"
                font-size="22"
                font-weight="700"
                fill="#000"
            >
                {{ $asset->asset_code }}
            </text>

            <text
                x="205"
                y="115"
                font-family="Arial, Helvetica, sans-serif"
                font-size="15"
                fill="#000"
            >
                {{ \Illuminate\Support\Str::limit($asset->name, 22) }}
            </text>

            <text
                x="205"
                y="145"
                font-family="Arial, Helvetica, sans-serif"
                font-size="11"
                fill="#000"
            >
                GEARTRACK
            </text>
        </svg>
    </div>

    <button id="printButton">
        🖨️ Hubungkan & Cetak
    </button>

    <div id="status">
        Siap.
    </div>

</div>

<script src="{{ asset('js/niimbot.js') }}?v={{ filemtime(public_path('js/niimbot.js')) }}"></script>

<script>
const button = document.getElementById('printButton');
const statusBox = document.getElementById('status');
const svg = document.getElementById('niimbotLabel');

function setStatus(message) {
    statusBox.textContent = message;
}

function svgToDataUrl() {
    const serializer = new XMLSerializer();

    const svgText = serializer.serializeToString(svg);

    return 'data:image/svg+xml;charset=utf-8,' +
        encodeURIComponent(svgText);
}

button.addEventListener('click', async () => {

    button.disabled = true;

    try {

        if (!Niimbot.isSupported()) {
            const message = !window.isSecureContext
                ? `Web Bluetooth diblokir karena halaman ini memakai HTTP (${window.location.origin}). Buka GearTrack melalui HTTPS dengan sertifikat yang dipercaya perangkat.`
                : 'Browser ini tidak menyediakan Web Bluetooth. Gunakan Google Chrome di Android atau Chrome/Edge di komputer.';
            throw new Error(message);
        }

        setStatus(
            'Membuka Bluetooth...\n' +
            'Pilih NIIMBOT B1 pada daftar perangkat.'
        );

        const model = {
            name_prefixes: ['B1'],
            task: 'b1',
            density: 3,
            label_type: 1,
            speed: 1,
        };

        const size = {
            w_px: 384,
            h_px: 240,
            offset_y_px: 4,
        };

        const imageUrl = svgToDataUrl();

        setStatus(
            'Mengirim label ke NIIMBOT...'
        );

        await Niimbot.printImage(imageUrl, {
            model,
            size,

            onProgress(progress) {
                setStatus(
                    'Mencetak label...\n' +
                    `${Math.round(progress * 100)}%`
                );
            },
        });

        setStatus(
            'Berhasil mencetak label ' +
            '{{ $asset->asset_code }}.'
        );

    } catch (error) {

        console.error(error);

        setStatus(
            'Gagal mencetak.\n\n' +
            (error?.message || String(error))
        );

    } finally {

        button.disabled = false;

    }
});
</script>

</body>
</html>