const assetPhotoInput = () => document.querySelector(
    '[data-asset-photo-upload] input[type="file"]',
);

const getAssetPhotoPond = () => {
    const root = document.querySelector('[data-asset-photo-upload]');

    return root && window.Alpine?.$data(root)?.pond;
};

const configurePhotoCompression = (remainingAttempts = 20) => {
    const pond = getAssetPhotoPond();

    if (pond) {
        // FilePond compresses and resizes large photos before upload.
        pond.setOptions({
            allowImageTransform: true,
            imageTransformOutputQuality: 82,
            imageTransformOutputQualityMode: 'always',
            imageTransformOutputStripImageHead: true,
        });

        return;
    }

    if (remainingAttempts > 0) {
        window.setTimeout(() => configurePhotoCompression(remainingAttempts - 1), 150);
    }
};

let cameraDialog;
let cameraStream;

const stopCamera = () => {
    cameraStream?.getTracks().forEach((track) => track.stop());
    cameraStream = null;

    const video = cameraDialog?.querySelector('[data-camera-video]');

    if (video) {
        video.srcObject = null;
    }
};

const createCameraDialog = () => {
    if (cameraDialog) {
        return cameraDialog;
    }

    const styles = document.createElement('style');
    styles.textContent = `
        .asset-photo-camera {
            width: min(92vw, 640px);
            max-width: none;
            box-sizing: border-box;
            padding: 24px;
            border: 0;
            border-radius: 16px;
            color: #111827;
            background: #fff;
            box-shadow: 0 24px 64px rgb(0 0 0 / 30%);
        }
        .asset-photo-camera::backdrop { background: rgb(15 23 42 / 70%); }
        .asset-photo-camera h2 { margin: 0 0 8px; font-size: 1.25rem; font-weight: 700; }
        .asset-photo-camera p { margin: 0 0 16px; font-size: .9rem; line-height: 1.5; }
        .asset-photo-camera video {
            display: block;
            width: 100%;
            max-height: min(62vh, 540px);
            border-radius: 10px;
            background: #111827;
            object-fit: contain;
        }
        .asset-photo-camera [data-camera-error] { color: #b91c1c; }
        .asset-photo-camera [data-camera-actions] {
            display: flex;
            flex-wrap: wrap;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 18px;
        }
        .asset-photo-camera button {
            min-height: 42px;
            padding: 9px 16px;
            border: 1px solid #d1d5db;
            border-radius: 9px;
            color: #111827;
            background: #fff;
            font: inherit;
            font-weight: 600;
            cursor: pointer;
        }
        .asset-photo-camera [data-camera-capture] {
            border-color: #0369a1;
            color: #fff;
            background: #0369a1;
        }
        .asset-photo-camera button:disabled { cursor: wait; opacity: .55; }
        @media (prefers-color-scheme: dark) {
            .asset-photo-camera { color: #f9fafb; background: #1f2937; }
            .asset-photo-camera button { border-color: #4b5563; color: #f9fafb; background: #374151; }
        }
    `;
    document.head.append(styles);

    cameraDialog = document.createElement('dialog');
    cameraDialog.className = 'asset-photo-camera';
    cameraDialog.setAttribute('aria-labelledby', 'asset-photo-camera-title');
    cameraDialog.innerHTML = `
        <h2 id="asset-photo-camera-title">Ambil Foto Aset</h2>
        <p data-camera-status role="status">Menghubungkan ke kamera…</p>
        <p data-camera-error role="alert" hidden></p>
        <video data-camera-video aria-label="Pratinjau kamera" autoplay playsinline></video>
        <div data-camera-actions>
            <button type="button" data-camera-fallback hidden>Pilih dari perangkat</button>
            <button type="button" data-camera-close>Tutup</button>
            <button type="button" data-camera-capture disabled>Ambil Foto</button>
        </div>
    `;
    document.body.append(cameraDialog);

    const closeButton = cameraDialog.querySelector('[data-camera-close]');
    const captureButton = cameraDialog.querySelector('[data-camera-capture]');
    const fallbackButton = cameraDialog.querySelector('[data-camera-fallback]');
    const video = cameraDialog.querySelector('[data-camera-video]');
    const status = cameraDialog.querySelector('[data-camera-status]');
    const error = cameraDialog.querySelector('[data-camera-error]');

    closeButton.addEventListener('click', () => cameraDialog.close());
    cameraDialog.addEventListener('close', stopCamera);
    cameraDialog.addEventListener('click', (event) => {
        if (event.target === cameraDialog) {
            cameraDialog.close();
        }
    });

    fallbackButton.addEventListener('click', () => {
        cameraDialog.close();

        const input = assetPhotoInput();

        if (input) {
            input.setAttribute('accept', 'image/*');
            input.removeAttribute('capture');
            input.click();
        }
    });

    video.addEventListener('loadedmetadata', () => {
        captureButton.disabled = false;
        status.textContent = 'Posisikan aset di dalam bingkai, lalu ambil foto.';
    });

    captureButton.addEventListener('click', async () => {
        if (!video.videoWidth || !video.videoHeight) {
            return;
        }

        captureButton.disabled = true;
        status.textContent = 'Menyiapkan foto…';

        const canvas = document.createElement('canvas');
        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;
        canvas.getContext('2d')?.drawImage(video, 0, 0, canvas.width, canvas.height);

        canvas.toBlob(async (blob) => {
            if (!blob) {
                error.hidden = false;
                error.textContent = 'Foto tidak dapat diproses. Silakan coba kembali.';
                status.textContent = '';
                captureButton.disabled = false;

                return;
            }

            const pond = getAssetPhotoPond();

            if (!pond) {
                error.hidden = false;
                error.textContent = 'Form unggah foto belum siap. Silakan muat ulang halaman.';
                status.textContent = '';
                captureButton.disabled = false;

                return;
            }

            try {
                await pond.addFile(new File([blob], `asset-${Date.now()}.jpg`, {
                    type: 'image/jpeg',
                    lastModified: Date.now(),
                }));
                cameraDialog.close();
            } catch {
                error.hidden = false;
                error.textContent = 'Foto gagal ditambahkan. Silakan ambil foto kembali.';
                status.textContent = '';
                captureButton.disabled = false;
            }
        }, 'image/jpeg', 0.9);
    });

    return cameraDialog;
};

const cameraErrorMessage = (error) => {
    if (error.name === 'NotAllowedError' || error.name === 'PermissionDeniedError') {
        return 'Izin kamera tidak diberikan. Aktifkan izin kamera untuk situs ini atau pilih foto dari perangkat.';
    }

    if (error.name === 'NotFoundError' || error.name === 'DevicesNotFoundError') {
        return 'Kamera tidak ditemukan pada perangkat ini. Anda dapat memilih foto dari perangkat.';
    }

    if (error.name === 'NotReadableError' || error.name === 'TrackStartError') {
        return 'Kamera sedang digunakan aplikasi lain. Tutup aplikasi tersebut, lalu coba kembali.';
    }

    return 'Kamera tidak dapat dibuka. Periksa izin kamera atau pilih foto dari perangkat.';
};

const openCamera = async () => {
    if (!assetPhotoInput()) {
        return;
    }

    configurePhotoCompression();

    const dialog = createCameraDialog();
    const video = dialog.querySelector('[data-camera-video]');
    const captureButton = dialog.querySelector('[data-camera-capture]');
    const fallbackButton = dialog.querySelector('[data-camera-fallback]');
    const status = dialog.querySelector('[data-camera-status]');
    const error = dialog.querySelector('[data-camera-error]');

    error.hidden = true;
    error.textContent = '';
    fallbackButton.hidden = true;
    captureButton.disabled = true;
    status.textContent = 'Menghubungkan ke kamera…';

    dialog.showModal();

    if (!navigator.mediaDevices?.getUserMedia) {
        status.textContent = '';
        error.hidden = false;
        error.textContent = window.isSecureContext
            ? 'Browser ini tidak menyediakan akses kamera langsung.'
            : 'Akses kamera memerlukan halaman HTTPS yang aman.';
        fallbackButton.hidden = false;

        return;
    }

    try {
        cameraStream = await navigator.mediaDevices.getUserMedia({
            audio: false,
            video: { facingMode: { ideal: 'environment' } },
        });
        video.srcObject = cameraStream;
        await video.play();
    } catch (cameraError) {
        status.textContent = '';
        error.hidden = false;
        error.textContent = cameraErrorMessage(cameraError);
        fallbackButton.hidden = false;
        stopCamera();
    }
};

document.addEventListener('DOMContentLoaded', () => configurePhotoCompression());
document.addEventListener('livewire:navigated', () => configurePhotoCompression());
window.Livewire?.hook('morphed', () => configurePhotoCompression());
window.GearTrackAssetPhotoPicker = { openCamera };
configurePhotoCompression();
