<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <style>
        @page { 
            margin: 10mm; 
            size: A4;
        }
        
        * { 
            margin: 0; 
            padding: 0; 
            box-sizing: border-box;
        }
        
        body { 
            font-family: 'Segoe UI', Arial, sans-serif; 
            color: #2d3748; 
            font-size: 11px;
            background: #fff;
        }

        /* HEADER SECTION */
        .header {
            text-align: center;
            padding-bottom: 6mm;
            border-bottom: 3px solid #e879a8;
            margin-bottom: 8mm;
            position: relative;
        }

        .header::before {
            content: "💕";
            display: block;
            font-size: 24px;
            margin-bottom: 3mm;
        }

        .title {
            font-size: 32px;
            font-weight: 700;
            color: #d63384;
            letter-spacing: 2px;
            margin-bottom: 2mm;
            text-transform: uppercase;
        }

        .subtitle {
            font-size: 12px;
            color: #666;
            margin: 1mm 0;
            font-weight: 500;
        }

        .tagline {
            font-size: 10px;
            color: #999;
            font-style: italic;
            margin-top: 1mm;
        }

        /* QR CODE SECTION */
        .qr-section {
            background: linear-gradient(135deg, #fff5f7 0%, #f9f5ff 100%);
            border-radius: 4mm;
            padding: 8mm;
            margin: 6mm 0;
            text-align: center;
            border: 2px solid #f0e6ff;
        }

        .qr-label {
            font-size: 11px;
            font-weight: 600;
            color: #d63384;
            margin-bottom: 4mm;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .qr-wrap {
            text-align: center;
            background: white;
            padding: 6mm;
            border-radius: 2mm;
            display: inline-block;
            box-shadow: 0 2px 8px rgba(214, 51, 132, 0.1);
        }

        .qr-wrap img {
            width: 52mm;
            height: 52mm;
            display: block;
        }

        .url {
            text-align: center;
            font-size: 9px;
            color: #666;
            margin-top: 3mm;
            word-break: break-all;
            font-weight: 500;
            font-family: 'Courier New', monospace;
            background: #f5f5f5;
            padding: 2mm;
            border-radius: 2mm;
        }

        /* INSTRUCTIONS SECTION */
        .instructions-header {
            font-size: 14px;
            font-weight: 700;
            color: #d63384;
            margin: 8mm 0 5mm;
            text-transform: uppercase;
            letter-spacing: 1px;
            display: flex;
            align-items: center;
        }

        .instructions-header::before {
            content: "→";
            margin-right: 4mm;
            font-size: 16px;
            color: #e879a8;
        }

        /* CARA BOXES */
        .cara-container {
            display: flex;
            gap: 5mm;
            margin-bottom: 6mm;
            flex-wrap: wrap;
        }

        .cara {
            flex: 1;
            min-width: 70mm;
            background: white;
            border: 2px solid #e879a8;
            border-radius: 3mm;
            padding: 5mm;
            box-shadow: 0 2px 6px rgba(214, 51, 132, 0.08);
            transition: all 0.3s;
        }

        .cara-number {
            display: inline-block;
            background: #d63384;
            color: white;
            width: 7mm;
            height: 7mm;
            border-radius: 50%;
            text-align: center;
            line-height: 7mm;
            font-weight: bold;
            font-size: 10px;
            margin-bottom: 3mm;
        }

        .cara-title {
            font-weight: 700;
            font-size: 11px;
            color: #d63384;
            margin-bottom: 3mm;
            display: flex;
            align-items: center;
        }

        .cara-title::before {
            content: "✓";
            display: inline-block;
            margin-right: 3mm;
            color: #e879a8;
            font-size: 12px;
        }

        .steps {
            background: #fafafa;
            border-left: 3px solid #e879a8;
            padding: 3mm 3mm 3mm 4mm;
            border-radius: 2mm;
        }

        .step {
            line-height: 1.7;
            margin-bottom: 2mm;
            font-size: 10px;
            color: #555;
        }

        .step:last-child {
            margin-bottom: 0;
        }

        .step::before {
            content: counter(step-counter);
            counter-increment: step-counter;
            display: inline-block;
            background: #f0e6ff;
            color: #d63384;
            width: 5mm;
            height: 5mm;
            border-radius: 50%;
            text-align: center;
            line-height: 5mm;
            margin-right: 2mm;
            font-weight: bold;
            font-size: 9px;
        }

        .cara:nth-child(1) {
            counter-reset: step-counter;
        }

        .cara:nth-child(2) {
            counter-reset: step-counter;
        }

        /* NOTE SECTION */
        .note {
            background: linear-gradient(135deg, #fff5f7 0%, #ffe8f0 100%);
            border-left: 4mm solid #d63384;
            border-radius: 3mm;
            padding: 4mm;
            margin: 6mm 0 4mm;
            line-height: 1.7;
            font-size: 10px;
            color: #555;
        }

        .note-title {
            font-weight: 700;
            color: #d63384;
            margin-bottom: 2mm;
        }

        /* FOOTER SECTION */
        .footer-divider {
            border-top: 2px dashed #e879a8;
            margin: 6mm 0;
        }

        .footer {
            text-align: center;
            color: #999;
            font-size: 9px;
            font-style: italic;
        }

        .footer-text {
            margin-bottom: 2mm;
        }

        .footer-heart {
            color: #d63384;
            font-size: 11px;
        }

        /* MOBILE/PRINT OPTIMIZATION */
        @media print {
            body {
                margin: 0;
                padding: 0;
            }
            
            .cara {
                page-break-inside: avoid;
            }
        }
    </style>
</head>
<body>
    <!-- HEADER -->
    <div class="header">
        <div class="title">Photo Booth</div>
        <div class="subtitle">Kenang Momen Istimewa Bersama Kami</div>
        <div class="tagline">Scan QR code di bawah untuk membuat foto kenangan yang indah</div>
    </div>

    <!-- QR CODE SECTION -->
    <div class="qr-section">
        <div class="qr-label">Scan QR Code Di Sini</div>
        <div class="qr-wrap">
            <img src="data:image/svg+xml;base64,{{ $qrSvg }}" alt="QR Code Photo Booth">
        </div>
    </div>

    <!-- INSTRUCTIONS -->
    <div class="instructions-header">Bagaimana Cara Menggunakan?</div>

    <!-- TWO METHODS -->
    <div class="cara-container">
        <!-- METHOD 1 -->
        <div class="cara">
            <div class="cara-number">1</div>
            <div class="cara-title">Scan & Buat Langsung</div>
            <div class="steps">
                <div class="step">Scan QR code di atas menggunakan smartphone</div>
                <div class="step">Buka menu Photo Booth</div>
                <div class="step">Pilih template frame yang paling kamu suka</div>
                <div class="step">Upload foto kamu dan download hasilnya</div>
            </div>
        </div>

        <!-- METHOD 2 -->
        <div class="cara">
            <div class="cara-number">2</div>
            <div class="cara-title">Gunakan Barcode Undangan</div>
            <div class="steps">
                <div class="step">Scan barcode yang ada di kartu undangan kamu</div>
                <div class="step">Masuk ke halaman pembuatan photo booth</div>
                <div class="step">Upload foto moment kebersamaan kamu</div>
                <div class="step">Jangan lupa tinggalkan ucapan suara untuk kami</div>
            </div>
        </div>
    </div>

    <!-- NOTE -->
    <div class="note">
        <div class="note-title">💡 Tips Penting:</div>
        Tidak punya barcode undangan? Tidak masalah! Cukup scan QR code ini, masukkan nama kamu, dan kami akan mencari data tamu kamu di sistem kami. Masukkan kode yang tertera di undangan untuk melanjutkan.
    </div>

    <!-- FOOTER -->
    <div class="footer-divider"></div>
    <div class="footer">
        <div class="footer-text">
            <span class="footer-heart">💕</span> 
            Terima kasih telah menjadi bagian dari hari istimewa kami!
            <span class="footer-heart">💕</span>
        </div>
        <div class="footer-text">Foto dan ucapan suara kalian sangat berarti bagi kami</div>
    </div>
</body>
</html>
