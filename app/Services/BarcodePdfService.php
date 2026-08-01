<?php

namespace App\Services;

use App\Filament\Forms\BarcodePdfSettingsForm;
use App\Models\BarcodePdfBatch;
use Barryvdh\DomPDF\Facade\Pdf;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class BarcodePdfService
{
    public static function generatePdf(BarcodePdfBatch $batch, ?array $settings = null): void
    {
        $settings = BarcodePdfSettingsForm::normalize($settings ?? $batch->pdf_settings ?? []);

        $barcodes = $batch->barcodes()->with('guest')->orderBy('barcode_code')->get();

        $qrSvgs = [];
        foreach ($barcodes as $barcode) {
            $qrSvgs[$barcode->id] = base64_encode(
                QrCode::format('svg')
                    ->size(180)
                    ->margin(1)
                    ->generate(BarcodeQrService::content($barcode))
            );
        }

        $labelsPerPage = $settings['columns'] * $settings['rows'];
        $pages = $barcodes->chunk($labelsPerPage);
        $headerOffset = $settings['show_header'] ? $settings['header_height_mm'] : 0.0;

        $pdf = Pdf::loadView('pdf.barcode-batch', [
            'batch' => $batch,
            'pages' => $pages,
            'totalPages' => $pages->count(),
            // 'qrSvgs' => null,
            'qrSvgs' => $qrSvgs,
            'settings' => $settings,
            'headerOffset' => $headerOffset,
        ])->setPaper(self::paperSize($settings['paper_width_mm'], $settings['paper_height_mm']), 'portrait');

        $filename = "barcode-batches/{$batch->uuid}.pdf";
        $path = storage_path('app/public/'.$filename);

        $dir = dirname($path);
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents($path, $pdf->output());

        $batch->update(['pdf_path' => $filename]);
    }

    protected static function paperSize(float $widthMm, float $heightMm): array
    {
        return [
            0,
            0,
            round($widthMm * 72 / 25.4, 2),
            round($heightMm * 72 / 25.4, 2),
        ];
    }
}
