import { Html5Qrcode } from 'html5-qrcode';
import { resolveQrDestination } from './qr-destination';
import { cameraBlockReason, cameraErrorMessage, startLiveCamera } from './scanner-camera';

document.addEventListener('DOMContentLoaded', () => {
    const status = document.getElementById('status');
    const reader = document.getElementById('reader');
    const fileInput = document.getElementById('qr-file');
    const startButton = document.getElementById('start-camera');
    const stopButton = document.getElementById('stop-camera');
    const cameraSelect = document.getElementById('camera-select');
    const cameraChoices = document.getElementById('camera-choices');

    if (!status || !reader || !fileInput || !startButton || !stopButton || !cameraSelect || !cameraChoices) {
        return;
    }

    const scanner = new Html5Qrcode('reader');
    const blocked = cameraBlockReason(window.isSecureContext, navigator.mediaDevices);
    let busy = false;
    let scanned = false;

    const showStatus = (message, kind = '') => {
        status.textContent = message;
        status.className = kind;
    };

    const refreshControls = () => {
        startButton.disabled = busy || Boolean(blocked);
        startButton.hidden = scanner.isScanning;
        stopButton.disabled = busy;
        stopButton.hidden = !scanner.isScanning;
        cameraSelect.disabled = busy || scanner.isScanning;
        fileInput.disabled = busy;
    };

    const stopCamera = async () => {
        if (scanner.isScanning) {
            await scanner.stop();
        }
    };

    const processQr = async (decodedText) => {
        if (scanned) {
            return;
        }

        let destination;
        try {
            destination = resolveQrDestination(decodedText, window.location.origin, reader.dataset.stockTake);
        } catch {
            showStatus('QR tidak dikenali sebagai label GearTrack. Arahkan ke label aset yang benar.', 'error');
            return;
        }

        scanned = true;
        showStatus('Aset ditemukan. Membuka detail...', 'success');

        try {
            await stopCamera();
        } catch (error) {
            console.warn('GearTrack camera cleanup:', error);
        }

        window.location.assign(destination);
    };

    const loadCameraChoices = async () => {
        if (typeof navigator.mediaDevices?.enumerateDevices !== 'function') {
            return;
        }

        try {
            const devices = (await navigator.mediaDevices.enumerateDevices())
                .filter((device) => device.kind === 'videoinput' && device.deviceId);
            const previous = cameraSelect.value;
            cameraSelect.replaceChildren(new Option('Otomatis — utamakan kamera belakang', ''));

            devices.forEach((device, index) => {
                cameraSelect.add(new Option(device.label || `Kamera ${index + 1}`, device.deviceId));
            });

            if (devices.some((device) => device.deviceId === previous)) {
                cameraSelect.value = previous;
            }
            cameraChoices.hidden = devices.length < 2;
        } catch (error) {
            console.warn('GearTrack camera list:', error);
        }
    };

    startButton.addEventListener('click', async () => {
        if (busy || blocked || scanner.isScanning) {
            return;
        }

        busy = true;
        scanned = false;
        refreshControls();
        showStatus('Meminta izin kamera... Pilih Izinkan jika Chrome menampilkan permintaan.');

        try {
            await startLiveCamera(scanner, () => Html5Qrcode.getCameras(), processQr, cameraSelect.value);

            if (!scanned) {
                showStatus('Kamera live aktif. Arahkan ke QR; hasil terbuka otomatis tanpa memotret.', 'success');
                await loadCameraChoices();
            }
        } catch (error) {
            console.warn('GearTrack camera start:', error);
            showStatus(cameraErrorMessage(error), 'error');
        } finally {
            busy = false;
            refreshControls();
        }
    });

    stopButton.addEventListener('click', async () => {
        if (busy) {
            return;
        }

        busy = true;
        refreshControls();
        try {
            await stopCamera();
            showStatus('Kamera dihentikan. Anda dapat memilih kamera lain lalu mengaktifkannya kembali.');
        } catch (error) {
            showStatus(cameraErrorMessage(error), 'error');
        } finally {
            busy = false;
            refreshControls();
        }
    });

    fileInput.addEventListener('change', async (event) => {
        const file = event.target.files?.[0];
        if (!file || busy) {
            return;
        }

        busy = true;
        scanned = false;
        refreshControls();
        showStatus('Membaca QR dari gambar...');

        try {
            await stopCamera();
            const decodedText = await scanner.scanFile(file, true);
            await processQr(decodedText);
        } catch (error) {
            console.warn('GearTrack image scan:', error);
            showStatus('QR tidak ditemukan pada gambar. Coba ambil foto lebih dekat dan pastikan label terlihat jelas.', 'error');
        } finally {
            fileInput.value = '';
            busy = false;
            refreshControls();
        }
    });

    window.addEventListener('pagehide', () => {
        void stopCamera().catch(() => {});
    });

    showStatus(blocked || 'Ketuk Aktifkan Kamera, lalu izinkan Chrome memakai kamera.', blocked ? 'error' : '');
    refreshControls();
});
