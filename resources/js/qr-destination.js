export function resolveQrDestination(decodedText, origin, stockTakeId = null) {
    const source = new URL(decodedText);

    if (!['http:', 'https:'].includes(source.protocol) || !/^\/q\/[^/]+\/?$/.test(source.pathname)) {
        throw new Error('QR ini bukan label aset GearTrack.');
    }

    const destination = new URL(source.pathname, origin);

    if (/^[1-9]\d*$/.test(String(stockTakeId))) {
        destination.searchParams.set('stock_take', stockTakeId);
    }

    return destination.href;
}
