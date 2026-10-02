const assetPhotoInput = () => document.querySelector(
    '[data-asset-photo-upload] input[type="file"]',
);

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

    input.setAttribute('accept', 'image/*');
    input.setAttribute('capture', 'environment');
    clearCaptureAfterPickerCloses(input);
    input.click();
};

const openGallery = () => {
    const input = assetPhotoInput();

    if (!input) {
        return;
    }

    input.setAttribute('accept', 'image/*');
    input.removeAttribute('capture');
    input.click();
};

window.GearTrackAssetPhotoPicker = { openCamera, openGallery };
