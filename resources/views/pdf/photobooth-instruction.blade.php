<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 12mm; }
        * { margin: 0; padding: 0; }
        body { font-family: Arial, sans-serif; color: #1e293b; font-size: 11px; }

        .header {
            text-align: center;
            border-bottom: 2px solid #6366f1;
            padding-bottom: 4mm;
        }

        .title {
            font-size: 28px;
            font-weight: bold;
            color: #4338ca;
            letter-spacing: 3px;
        }

        .subtitle {
            font-size: 13px;
            color: #4b5563;
            margin-top: 2mm;
        }

        .qr-wrap {
            text-align: center;
            margin-top: 6mm;
        }

        .qr-wrap img {
            width: 58mm;
            height: 58mm;
        }

        .url {
            text-align: center;
            font-size: 9px;
            color: #6b7280;
            margin-top: 2mm;
            word-wrap: break-word;
        }

        .section-title {
            font-size: 13px;
            font-weight: bold;
            color: #4338ca;
            margin: 6mm 0 3mm;
        }

        .cara {
            border: 1px solid #c7d2fe;
            border-radius: 3mm;
            padding: 4mm;
            margin-bottom: 3mm;
        }

        .cara-title {
            font-weight: bold;
            font-size: 12px;
            color: #312e81;
            margin-bottom: 2mm;
        }

        .step {
            line-height: 1.6;
        }

        .note {
            background: #eef2ff;
            border-left: 2mm solid #6366f1;
            padding: 3mm;
            margin-top: 4mm;
            line-height: 1.6;
        }

        .footer {
            text-align: center;
            color: #9ca3af;
            font-size: 8px;
            margin-top: 6mm;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="title">PHOTO BOOTH</div>
        <div class="subtitle">Scan me untuk membuat fotobooth dengan frame kita</div>
    </div>

    <div class="qr-wrap">
        <img src="data:image/svg+xml;base64,{{ $qrSvg }}" alt="QR Code">
    </div>

    <div class="url">{{ $url }}</div>

    <div class="section-title">Ada 2 cara untuk membuat Photo Booth:</div>

    <div class="cara">
        <div class="cara-title">Cara 1 &mdash; Scan QR &amp; Buat Langsung</div>
        <div class="step">1. Scan QR code di atas.</div>
        <div class="step">2. Klik menu PhotoBooth.</div>
        <div class="step">3. Pilih template frame yang kamu suka.</div>
        <div class="step">4. Masukkan gambar kamu, lalu download.</div>
    </div>

    <div class="cara">
        <div class="cara-title">Cara 2 &mdash; Lewat Barcode Undangan</div>
        <div class="step">1. Scan barcode yang sudah diberikan di undangan.</div>
        <div class="step">2. Kamu akan masuk ke halaman buat photo booth.</div>
        <div class="step">3. Upload moment kebersamaan kamu di acara.</div>
        <div class="step">4. Jangan lupa berikan ucapan suara kepada pengantin.</div>
    </div>

    <div class="note">
        <b>Catatan:</b> Tidak punya barcode undangan tapi sudah masuk ke data tamu? Cukup scan &amp; cari data nama tamu,
        lalu masukkan kode yang sudah diberikan di undangan.
    </div>

    <div class="footer">Moment kebersamaan &amp; ucapan suara kamu sangat berarti untuk kami.</div>
</body>
</html>
