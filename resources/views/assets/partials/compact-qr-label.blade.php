<div style="width:{{ $labelWidth }}mm;height:{{ $labelHeight }}mm;padding:1.5mm;display:flex;align-items:center;gap:1.5mm;overflow:hidden;border:.2mm solid #0f172a;background:white;color:#0f172a;font-family:Arial,Helvetica,sans-serif">
    <div style="width:{{ $qrSize }}mm;height:{{ $qrSize }}mm;flex:0 0 {{ $qrSize }}mm">
        {!! (string) $qrCode !!}
    </div>
    <div style="min-width:0;flex:1">
        <div style="color:#0369a1;font-size:{{ $brandFontSize }}pt;font-weight:800">GearTrack</div>
        <div style="margin-top:.7mm;overflow-wrap:anywhere;font-size:{{ $codeFontSize }}pt;font-weight:800;line-height:1.05">{{ $asset->asset_code }}</div>
        <div style="margin-top:.8mm;display:-webkit-box;overflow:hidden;font-size:{{ $nameFontSize }}pt;font-weight:700;line-height:1.12;-webkit-box-orient:vertical;-webkit-line-clamp:2">{{ $asset->name }}</div>
        <div style="margin-top:.8mm;color:#475569;font-size:{{ $hintFontSize }}pt;line-height:1.05">Scan untuk detail aset</div>
    </div>
</div>
