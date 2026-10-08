<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak {{ $assets->count() }} Label NIIMBOT | GearTrack</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; padding: 24px; background: #f1f5f9; color: #0f172a; font-family: Arial, Helvetica, sans-serif; }
        .card { max-width: 720px; margin: 0 auto; padding: 24px; background: white; border-radius: 16px; box-shadow: 0 10px 30px rgb(15 23 42 / 10%); }
        h1 { margin-top: 0; }
        p { color: #475569; line-height: 1.5; }
        .labels { display: grid; gap: 12px; }
        .label { width: 384px; max-width: 100%; aspect-ratio: 384 / 240; border: 1px solid #cbd5e1; }
        .label svg { display: block; width: 100%; height: 100%; }
        button { width: 100%; margin-top: 20px; padding: 14px 18px; border: 0; border-radius: 10px; background: #16a34a; color: white; font-size: 16px; font-weight: 700; cursor: pointer; }
        button:disabled { opacity: .6; cursor: wait; }
        #status { display: flex; align-items: flex-start; gap: 10px; margin-top: 12px; padding: 14px 16px; border: 1px solid #cbd5e1; border-radius: 10px; background: #f8fafc; color: #334155; white-space: pre-wrap; line-height: 1.5; }
        #status::before { flex: 0 0 auto; font-weight: 700; content: 'i'; }
        #status[data-state="progress"] { border-color: #93c5fd; background: #eff6ff; color: #1e40af; }
        #status[data-state="progress"]::before { content: '…'; }
        #status[data-state="success"] { border-color: #86efac; background: #f0fdf4; color: #166534; }
        #status[data-state="success"]::before { content: '✓'; }
        #status[data-state="error"] { border-color: #fca5a5; background: #fef2f2; color: #b91c1c; }
        #status[data-state="error"]::before { content: '!'; }
    </style>
</head>
<body>
<div class="card">
    <h1>Cetak NIIMBOT · {{ $assets->count() }} aset</h1>
    <p>Semua label dicetak berurutan melalui satu sambungan Bluetooth. Pastikan printer berisi label 50 × 30 mm.</p>
    <div class="labels">
        @foreach ($assets as $asset)
            <div class="label">
                <svg class="niimbot-label" xmlns="http://www.w3.org/2000/svg" width="384" height="240" viewBox="0 0 384 240">
                    <rect width="384" height="240" fill="white" />
                    <g transform="translate(12 30)">{!! (string) $asset->generatedQr !!}</g>
                    <text x="205" y="85" font-family="Arial, Helvetica, sans-serif" font-size="22" font-weight="700" fill="#000">{{ $asset->asset_code }}</text>
                    <text x="205" y="115" font-family="Arial, Helvetica, sans-serif" font-size="15" fill="#000">{{ \Illuminate\Support\Str::limit($asset->name, 22) }}</text>
                    <text x="205" y="145" font-family="Arial, Helvetica, sans-serif" font-size="11" fill="#000">GEARTRACK</text>
                </svg>
            </div>
        @endforeach
    </div>
    <button id="printButton">🖨️ Sambungkan ke Printer & Cetak Semua</button>
    <div id="status" data-state="info" role="status" aria-live="polite" aria-atomic="true">
        <span class="status-message">Siap mencetak {{ $assets->count() }} label. Tekan tombol di atas untuk memulai.</span>
    </div>
</div>
<script src="{{ asset('js/niimbot.js') }}?v={{ filemtime(public_path('js/niimbot.js')) }}"></script>
<script>
const button = document.getElementById('printButton');
const statusBox = document.getElementById('status');
function setStatus(message, state = 'info') {
    statusBox.dataset.state = state;
    statusBox.querySelector('.status-message').textContent = message;
}
function svgToDataUrl(svg) {
    return 'data:image/svg+xml;charset=utf-8,' + encodeURIComponent(new XMLSerializer().serializeToString(svg));
}
button.addEventListener('click', async () => {
    button.disabled = true;
    try {
        if (!Niimbot.isSupported()) {
            const message = !window.isSecureContext
                ? `Web Bluetooth diblokir karena halaman ini memakai HTTP (${window.location.origin}). Buka GearTrack melalui HTTPS dengan sertifikat yang dipercaya perangkat.`
                : 'Browser ini tidak menyediakan Web Bluetooth. Gunakan Google Chrome di Android atau Chrome/Edge di komputer.';
            throw new Error(message);
        }
        const model = { name_prefixes: ['B1'], task: 'b1', density: 3, label_type: 1, speed: 1 };
        const size = { w_px: 384, h_px: 240, offset_y_px: 4 };
        const labels = Array.from(document.querySelectorAll('.niimbot-label'), svgToDataUrl);
        setStatus('Menghubungkan ke printer. Pilih NIIMBOT pada daftar perangkat yang ditampilkan browser.', 'progress');
        await Niimbot.printBatch(labels, {
            model,
            size,
            onProgress(progress) {
                const message = String(progress || '').toLowerCase();
                const labelNumber = message.match(/label\s+(\d+)\/(\d+)/);
                const percentage = message.match(/(\d+)%/);
                if (message.includes('connecting')) {
                    setStatus('Menghubungkan ke printer NIIMBOT...', 'progress');
                } else if (message.includes('configuring')) {
                    setStatus('Menyiapkan printer...', 'progress');
                } else if (message.includes('sending')) {
                    setStatus(labelNumber ? `Mengirim label ${labelNumber[1]} dari ${labelNumber[2]}...` : 'Mengirim label ke printer...', 'progress');
                } else if (message.includes('printing')) {
                    const progressText = percentage ? ` ${percentage[1]}%` : '';
                    setStatus(labelNumber ? `Mencetak label ${labelNumber[1]} dari ${labelNumber[2]}...${progressText}` : `Mencetak ${labels.length} label...${progressText}`, 'progress');
                }
            },
        });
        setStatus(`${labels.length} label berhasil dicetak.`, 'success');
    } catch (error) {
        console.error(error);
        setStatus('Pencetakan gagal. ' + (error?.message || String(error)), 'error');
    } finally {
        button.disabled = false;
    }
});
</script>
</body>
</html>
