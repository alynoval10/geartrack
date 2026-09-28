export function extractAssetQrToken(decodedText) {
    const source = new URL(decodedText);
    const match = source.pathname.match(/^\/q\/([^/]+)\/?$/);

    if (!['http:', 'https:'].includes(source.protocol) || !match) {
        throw new Error('QR ini bukan label aset GearTrack.');
    }

    return decodeURIComponent(match[1]);
}

export function resolveQrDestination(decodedText, origin, stockTakeId = null) {
    const token = extractAssetQrToken(decodedText);
    const destination = new URL(`/q/${encodeURIComponent(token)}`, origin);

    if (/^[1-9]\d*$/.test(String(stockTakeId))) {
        destination.searchParams.set('stock_take', stockTakeId);
    }

    return destination.href;
}
