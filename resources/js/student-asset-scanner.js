import { Html5Qrcode } from 'html5-qrcode';
import { extractAssetQrToken } from './qr-destination';
import { cameraBlockReason, cameraErrorMessage, startLiveCamera } from './scanner-camera';

const initializeStudentAssetScanner = (root) => {
    if (root.dataset.initialized === 'true') {
        return;
    }

    const panel = root.querySelector('[data-student-scan-panel]');
    const toggleButton = root.querySelector('[data-student-scan-toggle]');
    const reader = root.querySelector('.student-scan-reader');
    const status = root.querySelector('[data-student-scan-status]');
    const startButton = root.querySelector('[data-student-scan-start]');
    const stopButton = root.querySelector('[data-student-scan-stop]');
    const fileInput = root.querySelector('[data-student-scan-file]');
    const assetSelect = document.querySelector('#asset_ids');
    const scanUrl = root.dataset.scanUrl;

    if (!panel || !toggleButton || !reader || !status || !startButton || !stopButton || !fileInput || !assetSelect || !scanUrl) {
        return;
    }

    if (!reader.id) {
        reader.id = `student-asset-reader-${crypto.randomUUID()}`;
    }

    const scanner = new Html5Qrcode(reader.id);
    const blocked = cameraBlockReason(window.isSecureContext, navigator.mediaDevices);
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
    let busy = false;
    let lastToken = null;
    let lastScannedAt = 0;
    root.dataset.initialized = 'true';

    const showStatus = (message, state = '') => {
        status.textContent = message;
        status.dataset.state = state;
    };

    const refreshControls = () => {
        startButton.hidden = scanner.isScanning;
        stopButton.hidden = !scanner.isScanning;
        startButton.disabled = busy || Boolean(blocked);
        stopButton.disabled = busy;
        fileInput.disabled = busy;
    };

    const stopCamera = async () => {
        if (scanner.isScanning) {
            await scanner.stop();
        }

        refreshControls();
    };

    const addAsset = async (decodedText) => {
        if (busy) {
            return;
        }

        let token;

        try {
            token = extractAssetQrToken(decodedText);
        } catch {
            showStatus('QR tidak dikenali sebagai label aset GearTrack.', 'error');

            return;
        }

        const now = Date.now();

        if (token === lastToken && now - lastScannedAt < 1800) {
            return;
        }

        lastToken = token;
        lastScannedAt = now;
        busy = true;
        refreshControls();
        showStatus('Memeriksa ketersediaan barang…');

        try {
            const response = await fetch(scanUrl, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken ?? '',
                },
                body: JSON.stringify({ token }),
            });
            const result = await response.json().catch(() => ({}));

            if (!response.ok) {
                throw new Error(result.message ?? 'Barang tidak dapat ditambahkan. Silakan coba lagi.');
            }

            const existingOption = Array.from(assetSelect.options).find((option) => option.value === String(result.id));

            if (existingOption) {
                existingOption.selected = true;
                assetSelect.dispatchEvent(new Event('change', { bubbles: true }));
                showStatus(`${result.label} sudah ada dan dipilih.`, 'success');

                return;
            }

            const selectedCount = Array.from(assetSelect.options).filter((option) => option.selected).length;

            if (selectedCount >= 100) {
                throw new Error('Maksimal 100 barang dalam satu pengajuan. Hapus pilihan sebelum memindai barang lain.');
            }

            const option = new Option(result.label, String(result.id), true, true);
            assetSelect.add(option);
            assetSelect.dispatchEvent(new Event('change', { bubbles: true }));
            showStatus(result.message, 'success');
        } catch (error) {
            showStatus(error instanceof Error ? error.message : 'Barang gagal ditambahkan. Silakan coba lagi.', 'error');
        } finally {
            busy = false;
            refreshControls();
        }
    };

    toggleButton.addEventListener('click', async () => {
        const isOpening = panel.hidden;
        panel.hidden = !isOpening;
        toggleButton.setAttribute('aria-expanded', String(isOpening));

        if (!isOpening) {
            try {
                await stopCamera();
                showStatus('Pemindai ditutup. Pilihan barang yang sudah masuk tetap tersimpan.');
            } catch (error) {
                showStatus(cameraErrorMessage(error), 'error');
            }
        }
    });

    startButton.addEventListener('click', async () => {
        if (busy || blocked || scanner.isScanning) {
            return;
        }

        busy = true;
        refreshControls();
        showStatus('Meminta izin kamera…');

        try {
            await startLiveCamera(scanner, () => Html5Qrcode.getCameras(), addAsset);
            showStatus('Kamera aktif. Arahkan ke QR barang; pemindai siap membaca barang berikutnya.');
        } catch (error) {
            showStatus(cameraErrorMessage(error), 'error');
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
            showStatus('Kamera dihentikan. Pilihan barang yang sudah masuk tetap tersimpan.');
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
        refreshControls();
        showStatus('Membaca QR dari foto…');

        try {
            await stopCamera();
            const decodedText = await scanner.scanFile(file, true);
            busy = false;
            await addAsset(decodedText);
        } catch (error) {
            showStatus(error instanceof Error ? error.message : 'QR tidak ditemukan pada foto. Coba foto lebih dekat.', 'error');
        } finally {
            fileInput.value = '';
            busy = false;
            refreshControls();
        }
    });

    window.addEventListener('pagehide', () => void stopCamera().catch(() => {}));

    if (blocked) {
        showStatus(blocked, 'error');
    }

    refreshControls();
};

const initializeStudentAssetScanners = () => {
    document.querySelectorAll('[data-student-asset-scanner]').forEach(initializeStudentAssetScanner);
};

document.addEventListener('DOMContentLoaded', initializeStudentAssetScanners);
document.addEventListener('livewire:navigated', initializeStudentAssetScanners);
initializeStudentAssetScanners();
