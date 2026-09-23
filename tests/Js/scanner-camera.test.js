import test from 'node:test';
import assert from 'node:assert/strict';
import { cameraBlockReason, cameraErrorMessage, cameraScanConfig, startLiveCamera } from '../../resources/js/scanner-camera.js';

test('HTTP on a phone explains the HTTPS requirement instead of blaming camera permission', () => {
    const message = cameraBlockReason(false, undefined);
    assert.match(message, /HTTP/);
    assert.match(message, /HTTPS/);
    assert.match(message, /sertifikat/);
});

test('secure contexts still require a camera API', () => {
    assert.match(cameraBlockReason(true, undefined), /Browser ini tidak menyediakan/);
    assert.equal(cameraBlockReason(true, { getUserMedia() {} }), null);
});

test('permission and busy-camera errors have different actionable instructions', () => {
    assert.match(cameraErrorMessage({ name: 'NotAllowedError' }), /Izinkan/);
    assert.match(cameraErrorMessage('Error getting userMedia, error = NotAllowedError: Permission denied'), /Izinkan/);
    assert.match(cameraErrorMessage({ name: 'NotReadableError' }), /Tutup aplikasi/);
    assert.match(cameraErrorMessage({ name: 'NotFoundError' }), /tidak terdeteksi/);
    assert.match(cameraErrorMessage({ name: 'SecurityError' }), /HTTPS/);
    assert.match(cameraErrorMessage({ name: 'OverconstrainedError' }), /Pilih kamera lain/);
    assert.match(cameraErrorMessage('unrecognized failure'), /mencoba lagi/);
});

test('scan box fits narrow phone previews', () => {
    const { qrbox } = cameraScanConfig();
    assert.deepEqual(qrbox(200, 300), { width: 150, height: 150 });
    assert.deepEqual(qrbox(600, 400), { width: 240, height: 240 });
});

test('camera starts using a rear-camera preference without an exact requirement', async () => {
    let configuration;
    const scanner = { async start(camera) { configuration = camera; } };

    await startLiveCamera(scanner, () => assert.fail('Camera enumeration was unnecessary'), () => {});

    assert.deepEqual(configuration, { facingMode: 'environment' });
});

test('unsupported camera preference falls back to the actual rear camera ID', async () => {
    const attempts = [];
    const scanner = {
        async start(camera) {
            attempts.push(camera);
            if (attempts.length === 1) {
                throw new Error('OverconstrainedError: facingMode');
            }
        },
    };

    await startLiveCamera(scanner, async () => [
        { id: 'front', label: 'Front camera' },
        { id: 'rear', label: 'Back camera' },
    ], () => {});

    assert.equal(attempts.length, 2);
    assert.equal(attempts[1], 'rear');
});

test('devices without a labelled rear camera can use the available camera', async () => {
    const attempts = [];
    const scanner = {
        async start(camera) {
            attempts.push(camera);
            if (attempts.length === 1) {
                throw new Error('NotFoundError');
            }
        },
    };

    await startLiveCamera(scanner, async () => [{ id: 'one', label: '' }], () => {});

    assert.equal(attempts[1], 'one');
});

test('denied permission does not cause repeated permission requests', async () => {
    let requests = 0;
    const scanner = {
        async start() {
            requests += 1;
            throw new Error('NotAllowedError: Permission denied');
        },
    };

    await assert.rejects(startLiveCamera(scanner, () => assert.fail('Permission denial must not trigger enumeration'), () => {}), /NotAllowedError/);
    assert.equal(requests, 1);
});

test('explicit camera choice is used without silently switching cameras on failure', async () => {
    let selected;
    const scanner = {
        async start(camera) {
            selected = camera;
            throw new Error('NotFoundError');
        },
    };

    await assert.rejects(startLiveCamera(scanner, () => assert.fail('Do not override explicit choice'), () => {}, 'chosen'), /NotFoundError/);
    assert.equal(selected, 'chosen');
});

test('empty camera inventory gives a recognizable error', async () => {
    const scanner = { async start() { throw new Error('NotFoundError'); } };

    await assert.rejects(startLiveCamera(scanner, async () => [], () => {}), /No cameras found/);
});
