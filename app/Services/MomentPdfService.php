<?php

namespace App\Services;

use App\Models\Invitation;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MomentPdfService
{
    public static function publicUrl(Invitation $invitation): string
    {
        return rtrim((string) config('services.barcode.base_url', 'https://fixnikah.miraaldi.my.id'), '/')
            . '/moment/public/' . $invitation->id;
    }

    public static function pdf(Invitation $invitation): \Barryvdh\DomPDF\PDF
    {
        $qrSvg = base64_encode(
            QrCode::format('svg')
                ->size(320)
                ->margin(1)
                ->generate(self::publicUrl($invitation))
        );

        return Pdf::loadView('pdf.moment-public-instruction', [
            'invitation' => $invitation,
            'url' => self::publicUrl($invitation),
            'qrSvg' => $qrSvg,
        ])->setPaper('a4', 'portrait');
    }

    public static function download(Invitation $invitation): StreamedResponse
    {
        $filename = 'moment-public-' . Str::slug($invitation->title ?: "invitation-{$invitation->id}") . '-' . $invitation->id . '.pdf';

        return response()->streamDownload(
            fn () => print(self::pdf($invitation)->output()),
            $filename,
            ['Content-Type' => 'application/pdf'],
        );
    }
}
