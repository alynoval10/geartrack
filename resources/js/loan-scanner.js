import { Html5Qrcode } from 'html5-qrcode';
import { extractAssetQrToken } from './qr-destination';
import { cameraBlockReason, cameraErrorMessage, startLiveCamera } from './scanner-camera';

export const initializeLoanScanner = (root) => {
    if (root.dataset.initialized === 'true') {
        return;
    }

    const reader = root.querySelector('[data-loan-scanner-reader]');
    const status = root.querySelector('[data-loan-scanner-status]');
    const startButton = root.querySelector('[data-loan-scanner-start]');
    const stopButton = root.querySelector('[data-loan-scanner-stop]');
    const fileInput = root.querySelector('[data-loan-scanner-file]');

    if (!reader || !status || !startButton || !stopButton || !fileInput) {
        return;
    }

    const scanner = new Html5Qrcode(reader.id);
    root.dataset.initialized = 'true';
    const blocked = cameraBlockReason(window.isSecureContext, navigator.mediaDevices);
    let busy = false;
    let lastToken = null;
    let lastScannedAt = 0;

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
            const token = extractAssetQrToken(decodedText);
            const scannedAt = Date.now();

            // Kamera dapat membaca label yang sama berkali-kali per detik; tahan duplikat sesaat agar pengguna sempat mengarahkan kamera ke perangkat berikutnya.
            if (token === lastToken && scannedAt - lastScannedAt < 2500) {
                return;
            }

            lastToken = token;
            lastScannedAt = scannedAt;
            busy = true;
            refreshControls();
            showStatus('Memeriksa perangkat...');
            window.Livewire.dispatch('loan-asset-scanned', { token });
        } catch {
            showStatus('QR tidak dikenali sebagai label aset GearTrack.', false);
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
            showStatus('Kamera aktif. Arahkan ke QR perangkat pertama.');
        } catch (error) {
            showStatus(cameraErrorMessage(error), false);
        } finally {
            busy = false;
            refreshControls();
        }
    });

    stopButton.addEventListener('click', async () => {
        busy = true;

        try {
            await stopCamera();
            showStatus('Kamera dihentikan. Pilihan perangkat yang sudah masuk tetap tersimpan.');
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
            console.warn('GearTrack loan QR image:', error);
            showStatus('QR tidak ditemukan pada gambar. Ambil foto lebih dekat dan pastikan label terlihat jelas.', false);
        } finally {
            fileInput.value = '';

            if (!busy) {
                refreshControls();
            }
        }
    });

    window.addEventListener('loan-asset-scan-result', (event) => {
        busy = false;
        showStatus(event.detail.message, event.detail.success);
        refreshControls();
    });
    window.addEventListener('close-modal', () => void stopCamera().catch(() => {}));
    window.addEventListener('pagehide', () => void stopCamera().catch(() => {}));

    showStatus(blocked || 'Aktifkan kamera, lalu scan satu atau beberapa QR perangkat.', blocked ? false : null);
    refreshControls();
};

const initializeLoanScanners = () => {
    document.querySelectorAll('[data-loan-scanner]').forEach(initializeLoanScanner);
};

// Modal Filament dibuat setelah halaman selesai dimuat. Inisialisasi pada fase capture memastikan klik pertama sudah diterima tombol kamera.
document.addEventListener('click', (event) => {
    const root = event.target.closest?.('[data-loan-scanner]');

    if (root) {
        initializeLoanScanner(root);
    }
}, true);
document.addEventListener('DOMContentLoaded', initializeLoanScanners);
document.addEventListener('livewire:navigated', initializeLoanScanners);
window.Livewire?.hook('morphed', initializeLoanScanners);
window.GearTrackLoanScanner = { initialize: initializeLoanScanner };
initializeLoanScanners();
