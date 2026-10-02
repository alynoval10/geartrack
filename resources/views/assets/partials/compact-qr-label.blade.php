<div style="width:{{ $labelWidth }}mm;height:{{ $labelHeight }}mm;padding:{{ $labelPadding }}mm;display:flex;flex-direction:{{ $isStacked ? 'column' : 'row' }};align-items:center;justify-content:center;gap:{{ $labelGap }}mm;overflow:hidden;border:.2mm solid #0f172a;background:white;color:#0f172a;font-family:Arial,Helvetica,sans-serif">
    <div style="width:{{ $qrSize }}mm;height:{{ $qrSize }}mm;flex:0 0 {{ $qrSize }}mm">
        {!! (string) $qrCode !!}
    </div>
    <div style="width:{{ $isStacked ? '100%' : 'auto' }};min-width:0;flex:{{ $isStacked ? '0 1 auto' : '1' }};text-align:{{ $isStacked ? 'center' : 'left' }}">
        <div style="color:#0369a1;font-size:{{ $brandFontSize }}pt;font-weight:800">GearTrack</div>
        <div style="margin-top:{{ $lineGap }}mm;overflow-wrap:anywhere;font-size:{{ $codeFontSize }}pt;font-weight:800;line-height:1.05">{{ $asset->asset_code }}</div>
        <div style="margin-top:{{ $lineGap }}mm;display:-webkit-box;overflow:hidden;font-size:{{ $nameFontSize }}pt;font-weight:700;line-height:1.12;-webkit-box-orient:vertical;-webkit-line-clamp:2">{{ $asset->name }}</div>
        <div style="margin-top:{{ $lineGap }}mm;color:#475569;font-size:{{ $hintFontSize }}pt;line-height:1.05">Scan untuk detail aset</div>
    </div>
</div>
