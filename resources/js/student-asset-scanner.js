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
    const count = root.querySelector('[data-student-asset-count]');
    const selectedAssets = root.querySelector('[data-student-selected-assets]');
    const startButton = root.querySelector('[data-student-scan-start]');
    const stopButton = root.querySelector('[data-student-scan-stop]');
    const fileInput = root.querySelector('[data-student-scan-file]');
    const assetSelect = document.querySelector('#asset_ids');
    const scanUrl = root.dataset.scanUrl;

    if (!panel || !toggleButton || !reader || !status || !count || !selectedAssets || !startButton || !stopButton || !fileInput || !assetSelect || !scanUrl) {
        return;
    }

    const scanner = new Html5Qrcode(reader.id);
    const blocked = cameraBlockReason(window.isSecureContext, navigator.mediaDevices);
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
    let busy = false;
    root.dataset.initialized = 'true';

    const selectedCount = () => Array.from(assetSelect.options).filter((option) => option.selected).length;

    const updateCount = () => {
        const total = selectedCount();
        count.textContent = `${total} barang dipilih`;
        selectedAssets.hidden = total === 0;
        selectedAssets.replaceChildren();

        Array.from(assetSelect.options)
            .filter((option) => option.selected)
            .forEach((option) => {
                const chip = document.createElement('span');
                chip.className = 'student-selected-chip';

                const label = document.createElement('span');
                label.textContent = option.textContent;

                const removeButton = document.createElement('button');
                removeButton.type = 'button';
                removeButton.textContent = '×';
                removeButton.setAttribute('aria-label', `Hapus pilihan ${option.textContent}`);
                removeButton.addEventListener('click', () => {
                    option.selected = false;
                    assetSelect.dispatchEvent(new Event('change', { bubbles: true }));
                    updateCount();
                });

                chip.append(label, removeButton);
                selectedAssets.append(chip);
            });
    };

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

    const closeAfterOneScan = async () => {
        try {
            await stopCamera();
        } catch (error) {
            showStatus(cameraErrorMessage(error), 'error');
        } finally {
            panel.hidden = true;
            toggleButton.textContent = 'Scan QR Barang';
            toggleButton.setAttribute('aria-expanded', 'false');
        }
    };

    const processQr = async (decodedText) => {
        if (busy) {
            return;
        }

        busy = true;
        refreshControls();
        showStatus('Memeriksa ketersediaan barang…');

        try {
            const token = extractAssetQrToken(decodedText);

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
            const wasSelected = existingOption?.selected ?? false;

            if (existingOption) {
                existingOption.selected = true;
            } else {
                if (selectedCount() >= 100) {
                    throw new Error('Maksimal 100 barang dalam satu pengajuan.');
                }

                assetSelect.add(new Option(result.label, String(result.id), true, true));
            }

            assetSelect.dispatchEvent(new Event('change', { bubbles: true }));
            updateCount();
            showStatus(wasSelected ? 'Barang tersebut sudah ada dalam pengajuan.' : `${result.message} Total ${selectedCount()} barang.`, 'success');
        } catch (error) {
            showStatus(error instanceof Error ? error.message : 'Barang gagal ditambahkan. Silakan coba lagi.', 'error');
        } finally {
            await closeAfterOneScan();
            busy = false;
            refreshControls();
        }
    };

    const startCamera = async () => {
        if (busy || blocked || scanner.isScanning) {
            if (blocked) {
                showStatus(blocked, 'error');
            }

            return;
        }

        busy = true;
        refreshControls();
        showStatus('Meminta izin kamera…');

        try {
            await startLiveCamera(scanner, () => Html5Qrcode.getCameras(), processQr);
            showStatus('Kamera aktif. Arahkan ke QR barang yang akan dipinjam.');
        } catch (error) {
            showStatus(cameraErrorMessage(error), 'error');
        } finally {
            busy = false;
            refreshControls();
        }
    };

    toggleButton.addEventListener('click', async () => {
        if (panel.hidden) {
            panel.hidden = false;
            toggleButton.textContent = 'Tutup Pemindai';
            toggleButton.setAttribute('aria-expanded', 'true');
            await startCamera();

            return;
        }

        await closeAfterOneScan();
        showStatus('Pemindai ditutup. Barang yang sudah dipindai tetap dipilih.');
    });

    startButton.addEventListener('click', startCamera);

    stopButton.addEventListener('click', async () => {
        try {
            await closeAfterOneScan();
            showStatus('Kamera dihentikan. Barang yang sudah dipindai tetap dipilih.');
        } catch (error) {
            showStatus(cameraErrorMessage(error), 'error');
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
            await processQr(decodedText);
        } catch (error) {
            await closeAfterOneScan();
            showStatus(error instanceof Error ? error.message : 'QR tidak ditemukan pada foto. Ambil foto lebih dekat.', 'error');
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

    updateCount();
    refreshControls();
};

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-student-asset-scanner]').forEach(initializeStudentAssetScanner);
});

initializeStudentAssetScanner(document.querySelector('[data-student-asset-scanner]'));
