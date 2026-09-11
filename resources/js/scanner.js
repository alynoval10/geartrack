import { Html5Qrcode } from 'html5-qrcode';

document.addEventListener('DOMContentLoaded', async () => {
    const status = document.getElementById('status');
    const fileInput = document.getElementById('qr-file');

    if (!status) {
        return;
    }

    status.textContent = 'Memulai scanner...';

    let scanner;
    let cameraRunning = false;
    let scanned = false;

    const showError = (message) => {
        status.textContent = message;
        status.className = 'error';
    };

    const processQr = async (decodedText) => {
        if (scanned) {
            return;
        }

        let url;

        try {
            url = new URL(decodedText);
        } catch {
            showError('QR tidak dikenali sebagai label GearTrack.');
            return;
        }

        if (!/^\/q\/[^/]+\/?$/.test(url.pathname)) {
            showError('QR ini bukan label aset GearTrack.');
            return;
        }

        scanned = true;

        status.textContent = 'Aset ditemukan. Membuka detail...';
        status.className = 'success';

        if (cameraRunning) {
            try {
                await scanner.stop();
            } catch (error) {
                console.warn(error);
            }
        }

        window.location.href = window.location.origin + url.pathname;
    };

    try {
        scanner = new Html5Qrcode('reader');

        status.textContent = 'Meminta izin kamera...';
        status.className = '';

        await scanner.start(
            {
                facingMode: {
                    exact: 'environment',
                },
            },
            {
                fps: 10,
                qrbox: {
                    width: 240,
                    height: 240,
                },
            },
            processQr,
            () => {}
        );

        cameraRunning = true;

        status.textContent = 'Kamera aktif — arahkan ke QR aset.';
        status.className = '';
    } catch (error) {
        console.error('GearTrack camera error:', error);

        /*
         * Beberapa HP tidak menerima exact environment.
         * Coba lagi tanpa "exact".
         */
        try {
            status.textContent = 'Mencoba kamera belakang...';

            if (!scanner) {
                scanner = new Html5Qrcode('reader');
            }

            await scanner.start(
                {
                    facingMode: 'environment',
                },
                {
                    fps: 10,
                    qrbox: {
                        width: 240,
                        height: 240,
                    },
                },
                processQr,
                () => {}
            );

            cameraRunning = true;

            status.textContent = 'Kamera aktif — arahkan ke QR aset.';
            status.className = '';
        } catch (fallbackError) {
            console.error(
                'GearTrack fallback camera error:',
                fallbackError
            );

            showError(
                'Kamera live tidak dapat dibuka. Gunakan Foto / Pilih QR.'
            );
        }
    }

    if (fileInput) {
        fileInput.addEventListener('change', async (event) => {
            const file = event.target.files?.[0];

            if (!file) {
                return;
            }

            scanned = false;

            status.textContent = 'Membaca QR dari gambar...';
            status.className = '';

            try {
                if (cameraRunning) {
                    await scanner.stop();
                    cameraRunning = false;
                }

                const decodedText = await scanner.scanFile(file, true);

                await processQr(decodedText);
            } catch (error) {
                console.error('GearTrack image scan error:', error);

                showError(
                    'QR tidak ditemukan pada gambar. Coba ambil foto lebih dekat.'
                );

                fileInput.value = '';
            }
        });
    }
});