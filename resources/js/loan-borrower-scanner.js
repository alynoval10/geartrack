import { Html5Qrcode } from 'html5-qrcode';
import { extractBorrowerQrToken } from './qr-destination';
import { cameraBlockReason, cameraErrorMessage, startLiveCamera } from './scanner-camera';

export const initializeBorrowerScanner = (root) => {
    if (root.dataset.initialized === 'true') {
        return;
    }

    const reader = root.querySelector('[data-borrower-scanner-reader]');
    const status = root.querySelector('[data-borrower-scanner-status]');
    const startButton = root.querySelector('[data-borrower-scanner-start]');
    const stopButton = root.querySelector('[data-borrower-scanner-stop]');
    const fileInput = root.querySelector('[data-borrower-scanner-file]');

    if (!reader || !status || !startButton || !stopButton || !fileInput) {
        return;
    }

    const scanner = new Html5Qrcode(reader.id);
    const blocked = cameraBlockReason(window.isSecureContext, navigator.mediaDevices);
    let busy = false;
    let lastToken = null;
    let lastScannedAt = 0;
    root.dataset.initialized = 'true';

    const showStatus = (message, success = null) => {
        status.textContent = message;
        status.classList.toggle('text-gray-600', success === null);
        status.classList.toggle('dark:text-gray-300', success === null);
        status.classList.toggle('text-success-600', success === true);
        status.classList.toggle('dark:text-success-400', success === true);
        status.classList.toggle('text-danger-600', success === false);
        status.classList.toggle('dark:text-danger-400', success === false);
    };

    const refreshControls = () => {
        startButton.hidden = scanner.isScanning;
        startButton.disabled = busy || Boolean(blocked);
        stopButton.hidden = !scanner.isScanning;
        stopButton.disabled = busy;
        fileInput.disabled = busy;
    };

    const stopCamera = async () => {
        if (scanner.isScanning) {
            await scanner.stop();
        }

        refreshControls();
    };

    const processQr = (decodedText) => {
        if (busy) {
            return;
        }

        try {
            const token = extractBorrowerQrToken(decodedText);
            const scannedAt = Date.now();

            if (token === lastToken && scannedAt - lastScannedAt < 2500) {
                return;
            }

            lastToken = token;
            lastScannedAt = scannedAt;
            busy = true;
            refreshControls();
            showStatus('Memeriksa data siswa...');
            window.Livewire.dispatch('loan-borrower-scanned', { token });
        } catch {
            showStatus('QR tidak dikenali sebagai kartu siswa GearTrack.', false);
        }
    };

    startButton.addEventListener('click', async () => {
        if (busy || blocked || scanner.isScanning) {
            return;
        }

        busy = true;
        refreshControls();
        showStatus('Meminta izin kamera...');

        try {
            await startLiveCamera(scanner, () => Html5Qrcode.getCameras(), processQr);
            showStatus('Kamera aktif. Arahkan ke QR siswa.');
        } catch (error) {
            showStatus(cameraErrorMessage(error), false);
        } finally {
            busy = false;
            refreshControls();
        }
    });

    stopButton.addEventListener('click', async () => {
        busy = true;
        refreshControls();

        try {
            await stopCamera();
            showStatus('Kamera dihentikan.');
        } catch (error) {
            showStatus(cameraErrorMessage(error), false);
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
        refreshControls();
        showStatus('Membaca QR dari gambar...');

        try {
            await stopCamera();
            const decodedText = await scanner.scanFile(file, true);
            busy = false;
            processQr(decodedText);
        } catch (error) {
            console.warn('GearTrack borrower QR image:', error);
            showStatus('QR siswa tidak ditemukan pada gambar. Ambil foto lebih dekat dan pastikan label terlihat jelas.', false);
        } finally {
            fileInput.value = '';

            if (!busy) {
                refreshControls();
            }
        }
    });

    window.addEventListener('loan-borrower-scan-result', async (event) => {
        busy = false;
        showStatus(event.detail.message, event.detail.success);

        if (event.detail.success) {
            await stopCamera().catch(() => {});
        }

        refreshControls();
    });
    window.addEventListener('close-modal', () => void stopCamera().catch(() => {}));
    window.addEventListener('pagehide', () => void stopCamera().catch(() => {}));

    showStatus(blocked || 'Aktifkan kamera, lalu scan QR siswa.', blocked ? false : null);
    refreshControls();
};

const initializeBorrowerScanners = () => {
    document.querySelectorAll('[data-borrower-scanner]').forEach(initializeBorrowerScanner);
};

document.addEventListener('click', (event) => {
    const root = event.target.closest?.('[data-borrower-scanner]');

    if (root) {
        initializeBorrowerScanner(root);
    }
}, true);
document.addEventListener('DOMContentLoaded', initializeBorrowerScanners);
document.addEventListener('livewire:navigated', initializeBorrowerScanners);
window.Livewire?.hook('morphed', initializeBorrowerScanners);
window.GearTrackBorrowerScanner = { initialize: initializeBorrowerScanner };
initializeBorrowerScanners();
