import test from 'node:test';
import assert from 'node:assert/strict';
import { resolveQrDestination } from '../../resources/js/qr-destination.js';

test('old QR host opens on current GearTrack host with the selected session', () => {
    assert.equal(resolveQrDestination('http://old-host/q/token?stock_take=999', 'https://geartrack.test', 12),
        'https://geartrack.test/q/token?stock_take=12');
});

test('ordinary scan strips untrusted query parameters and keeps the asset path', () => {
    assert.equal(resolveQrDestination('https://other-host/q/token?redirect=https://other-host', 'https://geartrack.test'),
        'https://geartrack.test/q/token');
});

test('invalid session context is not propagated', () => {
    assert.equal(resolveQrDestination('https://geartrack.test/q/token', 'https://geartrack.test', '-2'),
        'https://geartrack.test/q/token');
});

test('scanner rejects malformed text, non-asset paths, and unsafe protocols', () => {
    for (const value of ['random text', 'https://geartrack.test/login', 'https://geartrack.test/q/a/label', 'javascript:/q/token']) {
        assert.throws(() => resolveQrDestination(value, 'https://geartrack.test'));
    }
});
