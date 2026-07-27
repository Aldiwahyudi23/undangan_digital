<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 10px; }

        .page {
            width: 210mm;
            min-height: 297mm;
            padding: 10mm;
            page-break-after: always;
        }

        .batch-header {
            text-align: center;
            margin-bottom: 8mm;
            border-bottom: 1px solid #ccc;
            padding-bottom: 3mm;
        }

        .batch-header h1 {
            font-size: 16px;
            margin-bottom: 2mm;
        }

        .batch-header p {
            font-size: 11px;
            color: #555;
        }

        .barcode-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 4mm;
            justify-content: flex-start;
        }

        .barcode-item {
            width: 48mm;
            border: 0.3px dashed #ccc;
            padding: 2mm;
            text-align: center;
            page-break-inside: avoid;
        }

        .barcode-item .instruction {
            font-size: 7px;
            color: #666;
            margin-bottom: 1mm;
            line-height: 1.3;
        }

        .barcode-item .qr-image {
            width: 15mm;
            height: 15mm;
            margin: 0 auto 1mm;
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
    @foreach($barcodes->chunk(40) as $pageIndex => $chunk)
        <div class="page">
            <div class="batch-header">
                <h1>{{ $batch->title }}</h1>
                <p>Batch: {{ $batch->title }} | Total: {{ $batch->quantity }} Barcode | Halaman {{ $pageIndex + 1 }}</p>
            </div>

            <div class="barcode-grid">
                @foreach($chunk as $barcode)
                    <div class="barcode-item">
                        <div class="instruction">
                            Gunakan barcode ini untuk masuk ke kondangan dan nantikan Doorprize nya!
                        </div>
                        <div class="qr-image">
                            <img src="data:image/svg+xml;base64,{{ $qrSvgs[$barcode->id] }}" alt="QR Code">
                        </div>
                        <div class="code">{{ $barcode->barcode_code }}</div>
                    </div>
                @endforeach
            </div>
        </div>
    @endforeach
</body>
</html>
