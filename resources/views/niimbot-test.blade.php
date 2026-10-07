<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Test NIIMBOT B1 | GearTrack</title>

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
            max-width: 520px;
            margin: 0 auto;
            padding: 24px;
            background: white;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgb(15 23 42 / 10%);
        }

        h1 {
            margin-top: 0;
        }

        p {
            color: #475569;
            line-height: 1.6;
        }

        button {
            width: 100%;
            padding: 14px 18px;
            border: 0;
            border-radius: 10px;
            background: #0369a1;
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
        }
    </style>
</head>

<body>

<div class="card">

    <h1>Test NIIMBOT B1</h1>

    <p>
        Pastikan NIIMBOT B1 menyala dan Bluetooth HP aktif.
        Tekan tombol di bawah untuk memilih printer.
    </p>

    <button id="printButton">
        🖨️ Hubungkan & Cetak Test
    </button>

    <div id="status">
        Siap.
    </div>

</div>

<script src="{{ asset('js/niimbot.js') }}"></script>

<script>

const button = document.getElementById('printButton');
const statusBox = document.getElementById('status');

function setStatus(message) {
    statusBox.textContent = message;
}

button.addEventListener('click', async () => {

    button.disabled = true;

    try {

        if (!Niimbot.isSupported()) {
            throw new Error(
                'Browser ini tidak mendukung Web Bluetooth.'
            );
        }

        setStatus(
            'Membuka Bluetooth...\n' +
            'Pilih NIIMBOT B1 pada daftar perangkat.'
        );

        /*
         * Konfigurasi khusus NIIMBOT B1.
         *
         * B1:
         * - protocol: b1
         * - 203 DPI
         * - label 50 × 30 mm
         * - raster 384 × 240 px
         */
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

        /*
         * Untuk percobaan pertama kita gunakan gambar
         * yang berada di public.
         */
        const imageUrl = "{{ asset('images/niimbot-test.png') }}";

        setStatus(
            'Menghubungkan ke NIIMBOT B1...'
        );

        await Niimbot.printImage(imageUrl, {
            model,
            size,

            onProgress: (progress) => {

                setStatus(
                    'Mencetak...\n' +
                    JSON.stringify(progress, null, 2)
                );

            },
        });

        setStatus(
            '✅ Berhasil!\n\n' +
            'Label sudah dikirim ke NIIMBOT B1.'
        );

    } catch (error) {

        console.error(error);

        setStatus(
            '❌ Gagal mencetak.\n\n' +
            error.message
        );

    } finally {

        button.disabled = false;

    }

});

</script>

</body>
</html>