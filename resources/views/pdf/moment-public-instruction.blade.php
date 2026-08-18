<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <style>
        @page { size: A4; margin: 15mm; }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Helvetica', 'Arial', sans-serif; color: #333; background: #fff; }

        .container { text-align: center; padding: 20mm 10mm; }

        .header { margin-bottom: 30px; }
        .header h1 { font-size: 28px; color: #1a1a2e; margin-bottom: 6px; }
        .header p { font-size: 14px; color: #666; }

        .qr-section { margin: 30px auto; }
        .qr-section img { width: 52mm; height: 52mm; }

        .url { font-size: 10px; color: #999; margin-top: 8px; word-break: break-all; }

        .instructions { margin-top: 40px; text-align: left; max-width: 420px; margin-left: auto; margin-right: auto; }
        .instructions h2 { font-size: 16px; color: #1a1a2e; margin-bottom: 16px; text-align: center; }

        .step { display: flex; align-items: flex-start; margin-bottom: 14px; }
        .step-num { flex-shrink: 0; width: 28px; height: 28px; background: #1a1a2e; color: #fff; border-radius: 50%; text-align: center; line-height: 28px; font-size: 13px; font-weight: bold; margin-right: 12px; }
        .step-text { font-size: 13px; line-height: 1.5; padding-top: 3px; }

        .rules { margin-top: 24px; background: #f8f8f8; border-radius: 8px; padding: 16px 20px; }
        .rules h3 { font-size: 13px; color: #1a1a2e; margin-bottom: 10px; }
        .rules ul { list-style: none; padding: 0; }
        .rules li { font-size: 12px; color: #555; margin-bottom: 6px; padding-left: 16px; position: relative; }
        .rules li::before { content: ''; position: absolute; left: 0; top: 6px; width: 6px; height: 6px; background: #e74c3c; border-radius: 50%; }

        .footer { margin-top: 40px; font-size: 11px; color: #999; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Upload Moment</h1>
            <p>Bagikan momen indahmu bersama kami</p>
        </div>

        <div class="qr-section">
            <img src="data:image/svg+xml;base64,{{ $qrSvg }}" alt="QR Code Moment">
            <div class="url">{{ $url }}</div>
        </div>

        <div class="instructions">
            <h2>Cara Menggunakan</h2>

            <div class="step">
                <div class="step-num">1</div>
                <div class="step-text">Scan QR Code di atas menggunakan kamera HP kamu</div>
            </div>
            <div class="step">
                <div class="step-num">2</div>
                <div class="step-text">Masukkan <strong>nama kamu</strong> pada kolom yang tersedia</div>
            </div>
            <div class="step">
                <div class="step-num">3</div>
                <div class="step-text">Pilih jenis upload: <strong>gambar/video</strong> atau <strong>voice note</strong></div>
            </div>
            <div class="step">
                <div class="step-num">4</div>
                <div class="step-text">Upload momen terbaikmu dan kirim!</div>
            </div>
        </div>

        <div class="rules">
            <h3>Batasan Upload</h3>
            <ul>
                <li>Maksimal <strong>3 kali</strong> upload</li>
                <li>Sekali upload maksimal <strong>5 file</strong> gambar atau video</li>
                <li>Ukuran maksimal per file: <strong>30 MB</strong></li>
                <li>Video maksimal durasi: <strong>30 detik</strong></li>
                <li>Format gambar: JPG, PNG, GIF</li>
                <li>Format video: MP4, MOV, AVI</li>
                <li>Format voice note: MP3, M4A, WAV, OGG, AAC</li>
            </ul>
        </div>

        <div class="footer">
            Terima kasih telah menjadi bagian dari hari istimewa kami
        </div>
    </div>
</body>
</html>
