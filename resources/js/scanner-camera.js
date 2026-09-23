export function cameraBlockReason(secureContext, mediaDevices) {
    if (!secureContext) {
        return 'Kamera live diblokir browser karena GearTrack dibuka melalui HTTP. Gunakan alamat HTTPS dengan sertifikat yang dipercaya HP. Izin kamera saja tidak cukup untuk alamat HTTP.';
    }

    if (typeof mediaDevices?.getUserMedia !== 'function') {
        return 'Browser ini tidak menyediakan akses kamera live. Buka langsung di Chrome terbaru, bukan di browser dalam aplikasi lain.';
    }

    return null;
}

function cameraErrorText(error) {
    return `${error?.name ?? ''} ${error?.message ?? String(error)}`;
}

export function cameraErrorMessage(error) {
    const text = cameraErrorText(error);

    if (/NotAllowedError|PermissionDeniedError|permission denied|permission dismissed/i.test(text)) {
        return 'Izin kamera belum diberikan. Di Chrome, buka pengaturan situs ini → Kamera → Izinkan, lalu ketuk Aktifkan Kamera. Periksa juga izin kamera Chrome di pengaturan HP.';
    }

    if (/NotReadableError|TrackStartError|could not start|device.*busy/i.test(text)) {
        return 'Kamera sedang dipakai aplikasi lain atau belum siap. Tutup aplikasi kamera/panggilan video, lalu coba lagi.';
    }

    if (/NotFoundError|DevicesNotFoundError|no cameras|camera.*not found/i.test(text)) {
        return 'Kamera tidak terdeteksi. Periksa apakah akses kamera HP dinonaktifkan, lalu coba lagi.';
    }

    if (/OverconstrainedError|ConstraintNotSatisfiedError/i.test(text)) {
        return 'Kamera yang dipilih tidak tersedia. Pilih kamera lain, lalu aktifkan kembali.';
    }

    if (/SecurityError/i.test(text)) {
        return 'Akses kamera dibatasi browser. Buka GearTrack langsung melalui HTTPS dan periksa izin situs.';
    }

    return 'Kamera belum berhasil dimulai. Periksa izin kamera, tutup aplikasi yang memakai kamera, lalu ketuk Aktifkan Kamera untuk mencoba lagi.';
}

export function cameraScanConfig() {
    return {
        fps: 10,
        qrbox: (width, height) => {
            const side = Math.min(240, Math.floor(Math.min(width, height) * 0.75));
            return { width: side, height: side };
        },
    };
}

export async function startLiveCamera(scanner, getCameras, onScan, cameraId = null) {
    const config = cameraScanConfig();
    const ignoreUnreadableFrame = () => {};

    try {
        await scanner.start(cameraId || { facingMode: 'environment' }, config, onScan, ignoreUnreadableFrame);
        return;
    } catch (error) {
        if (cameraId || !/OverconstrainedError|ConstraintNotSatisfiedError|NotFoundError|DevicesNotFoundError/i.test(cameraErrorText(error))) {
            throw error;
        }
    }

    const cameras = await getCameras();
    const preferred = cameras.find((camera) => /back|rear|environment|belakang/i.test(camera.label)) ?? cameras[0];

    if (!preferred) {
        throw new Error('No cameras found');
    }

    await scanner.start(preferred.id, config, onScan, ignoreUnreadableFrame);
}
