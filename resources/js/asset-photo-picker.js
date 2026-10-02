const assetPhotoInput = () => document.querySelector(
    '[data-asset-photo-upload] input[type="file"]',
);

const configurePhotoCompression = (remainingAttempts = 20) => {
    const root = document.querySelector('[data-asset-photo-upload]');
    const component = root && window.Alpine?.$data(root);

    if (component?.pond) {
        // FilePond mengodekan ulang foto sebelum upload. Dimensi piksel tetap, sedangkan kualitas JPEG dibuat tinggi agar detail aset tetap jelas.
        component.pond.setOptions({
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

const clearCaptureAfterPickerCloses = (input) => {
    const clearCapture = () => input.removeAttribute('capture');

    input.addEventListener('change', clearCapture, { once: true });

    // Jika pengguna membatalkan kamera, event change tidak terpanggil. Hapus capture saat halaman aktif kembali agar klik area unggah tetap membuka galeri.
    window.addEventListener('focus', () => {
        window.setTimeout(clearCapture, 300);
    }, { once: true });
};

const openCamera = () => {
    const input = assetPhotoInput();

    if (!input) {
        return;
    }

    configurePhotoCompression();
    input.setAttribute('accept', 'image/*');
    input.setAttribute('capture', 'environment');
    clearCaptureAfterPickerCloses(input);
    input.click();
};

document.addEventListener('DOMContentLoaded', () => configurePhotoCompression());
document.addEventListener('livewire:navigated', () => configurePhotoCompression());
window.Livewire?.hook('morphed', () => configurePhotoCompression());
window.GearTrackAssetPhotoPicker = { openCamera };
configurePhotoCompression();
