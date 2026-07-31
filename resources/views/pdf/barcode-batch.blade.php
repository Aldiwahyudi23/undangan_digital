<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    @php
        $paddingMm = 1.5;
        $borderMm = (float) $settings['border_width_mm'];
        $qrRightMargin = 2.0;
        $infoLeftPadding = 2.0;

        $innerW = max(1, $settings['label_width_mm'] - 2 * ($paddingMm + $borderMm));
        $innerH = max(1, $settings['label_height_mm'] - 2 * ($paddingMm + $borderMm));

        $instructionH = min(8, max(4.5, $innerH * 0.27));
        $instructionFontPx = $innerH >= 22 ? 8.5 : 6.5;
        $codeH = min(3.5, max(2.5, $innerH * 0.12));
        $qrSize = max(8, min(18, $innerH - $instructionH - $codeH - 0.5));
        $qrSize = min($qrSize, $innerW * 0.5);
        $qrCellW = $qrSize + 2 * $qrRightMargin;
        $infoW = max(10, $innerW - $qrCellW);
        $qrCellPct = $qrCellW / $innerW * 100;
        $infoCellPct = 100 - $qrCellPct;
        $bodyH = $innerH - $instructionH;
    @endphp
    <style>
        @page { margin: 0; }
        * { margin: 0; padding: 0; }
        html, body { margin: 0; padding: 0; }
        body { font-family: Arial, sans-serif; font-size: 10px; }

        .page {
            position: relative;
            width: {{ $settings['paper_width_mm'] }}mm;
            height: {{ $settings['paper_height_mm'] }}mm;
            page-break-after: always;
            overflow: hidden;
        }

        .page:last-child {
            page-break-after: auto;
        }

        .batch-header {
            height: {{ $settings['header_height_mm'] }}mm;
            text-align: center;
            border-bottom: 1px solid #ccc;
            padding: 1mm 3mm;
        }

        .batch-header h1 {
            font-size: 13px;
            margin-bottom: 1mm;
        }

        .batch-header p {
            font-size: 9px;
            color: #555;
        }

        .barcode-item {
            position: absolute;
            border: {{ $settings['border_width_mm'] }}mm {{ $settings['border_style'] === 'none' ? 'none' : $settings['border_style'] }} #9ca3af;
            border-radius: {{ $settings['corner_radius_mm'] }}mm;
            padding: 1.5mm;
            text-align: center;
            overflow: hidden;
            page-break-inside: avoid;
        }

        .barcode-item .instruction {
            font-size: {{ $instructionFontPx }}px;
            color: #666;
            line-height: 1.25;
            text-align: center;
            margin-bottom: 0.8mm;
        }

        .barcode-item .divider {
            border-top: 0.4pt dashed #9ca3af;
            margin-bottom: 1mm;
        }

        .barcode-item .card-body {
            width: {{ $innerW }}mm;
            border-collapse: collapse;
        }

        .barcode-item .info-cell {
            vertical-align: middle;
            text-align: left;
            word-wrap: break-word;
            padding-left: {{ $infoLeftPadding }}mm;
        }

        .barcode-item .to-label {
            font-size: 8px;
            color: #555;
            margin-bottom: 0.4mm;
        }

        .barcode-item .name {
            font-size: 12px;
            font-weight: bold;
            color: #000;
            word-wrap: break-word;
        }

        .barcode-item .di-place {
            font-size: 8px;
            font-weight: normal;
            color: #333;
            word-wrap: break-word;
            margin-top: 0.8mm;
        }

        .barcode-item .qr-cell {
            vertical-align: middle;
            text-align: center;
            word-wrap: break-word;
        }

        .barcode-item .qr-image {
            margin: 0 auto 0.8mm;
        }

        .barcode-item .qr-image img {
            width: 100%;
            height: 100%;
        }

        .barcode-item .code {
            font-size: 8px;
            font-weight: bold;
            letter-spacing: 0.5px;
            color: #333;
        }
    </style>
</head>
<body>
    @foreach($pages as $pageIndex => $chunk)
        <div class="page">
            @if($settings['show_header'])
                <div class="batch-header">
                    <h1>{{ $batch->title }}</h1>
                    <p>Total: {{ $batch->quantity }} Barcode | Halaman {{ $pageIndex + 1 }} / {{ $totalPages }}</p>
                </div>
            @endif

            @foreach($chunk as $index => $barcode)
                @php
                    $row = intdiv($index, $settings['columns']);
                    $col = $index % $settings['columns'];
                    $left = $settings['margin_left_mm'] + $col * ($settings['label_width_mm'] + $settings['gap_x_mm']);
                    $top = $settings['margin_top_mm'] + $row * ($settings['label_height_mm'] + $settings['gap_y_mm']) + $headerOffset;

                    $guest = $barcode->guest;
                @endphp
                <div class="barcode-item" style="left: {{ $left }}mm; top: {{ $top }}mm; width: {{ $innerW }}mm; height: {{ $innerH }}mm;">
                    <div class="instruction">
                        Gunakan barcode ini untuk masuk ke kondangan dan nantikan Doorprize nya!
                    </div>
                    <div class="divider"></div>
                    <table class="card-body" style="height: {{ $bodyH }}mm;">
                        <tr>
                            <td class="info-cell" style="width: {{ $infoCellPct }}%;">
                                <div class="to-label">Kepada Yth. :</div>
                                <div class="name">{!! $guest?->name ? e($guest->name) : '&nbsp;' !!}</div>
                                <div class="di-place">
                                    di {{ $guest?->location_tag ?: 'Tempat' }}
                                </div>
                            </td>
                            <td class="qr-cell" style="width: {{ $qrCellPct }}%;">
                                <div class="qr-image" style="width: {{ $qrSize }}mm; height: {{ $qrSize }}mm;">
                                    <img src="data:image/svg+xml;base64,{{ $qrSvgs[$barcode->id] }}" alt="QR Code">
                                </div>
                                <div class="code">{{ $barcode->barcode_code }}</div>
                            </td>
                        </tr>
                    </table>
                </div>
            @endforeach
        </div>
    @endforeach
</body>
</html>
