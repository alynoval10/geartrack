import { Html5Qrcode } from 'html5-qrcode';
import { extractAssetQrToken } from './qr-destination';
import { cameraBlockReason, cameraErrorMessage, startLiveCamera } from './scanner-camera';

export const initializeAssetSetScanner = (root) => {
    if (root.dataset.initialized === 'true') {
        return;
    }

    const reader = root.querySelector('[data-asset-set-scanner-reader]');
    const status = root.querySelector('[data-asset-set-scanner-status]');
    const startButton = root.querySelector('[data-asset-set-scanner-start]');
    const stopButton = root.querySelector('[data-asset-set-scanner-stop]');
    const fileInput = root.querySelector('[data-asset-set-scanner-file]');

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

            // Kamera membaca label yang sama berkali-kali; jeda singkat mencegah permintaan ganda ke Livewire.
            if (token === lastToken && scannedAt - lastScannedAt < 2500) {
                return;
            }

            lastToken = token;
            lastScannedAt = scannedAt;
            busy = true;
            refreshControls();
            showStatus('Memeriksa aset...');
            window.Livewire.dispatch('asset-set-member-scanned', { token });
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
            showStatus('Kamera aktif. Arahkan ke QR aset yang akan dikaitkan.');
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
            showStatus('Kamera dihentikan. Anda tetap dapat memilih aset secara manual.');
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
            console.warn('GearTrack asset set QR image:', error);
            showStatus('QR tidak ditemukan pada gambar. Ambil foto lebih dekat dan pastikan label terlihat jelas.', false);
        } finally {
            fileInput.value = '';

            if (!busy) {
                refreshControls();
            }
        }
    });

    window.addEventListener('asset-set-member-scan-result', async (event) => {
        busy = false;
        showStatus(event.detail.message, event.detail.success);

        if (event.detail.success) {
            await stopCamera().catch(() => {});
        }

        refreshControls();
    });
    window.addEventListener('close-modal', () => void stopCamera().catch(() => {}));
    window.addEventListener('pagehide', () => void stopCamera().catch(() => {}));

    showStatus(blocked || 'Aktifkan kamera, lalu scan QR aset yang akan dikaitkan.', blocked ? false : null);
    refreshControls();
};

const initializeAssetSetScanners = () => {
    document.querySelectorAll('[data-asset-set-scanner]').forEach(initializeAssetSetScanner);
};

// Modal Filament dibuat setelah halaman selesai dimuat; fase capture menangani klik pertama pada tombol kamera.
document.addEventListener('click', (event) => {
    const root = event.target.closest?.('[data-asset-set-scanner]');

    if (root) {
        initializeAssetSetScanner(root);
    }
}, true);
document.addEventListener('DOMContentLoaded', initializeAssetSetScanners);
document.addEventListener('livewire:navigated', initializeAssetSetScanners);
window.Livewire?.hook('morphed', initializeAssetSetScanners);
window.GearTrackAssetSetScanner = { initialize: initializeAssetSetScanner };
initializeAssetSetScanners();
