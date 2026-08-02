<?php

namespace App\Services;

use App\Models\InvitationBarcode;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class BarcodeQrService
{
    public static function baseUrl(): string
    {
        return rtrim((string) config('services.barcode.base_url', 'https://fixnikah-miraaldi.my.id'), '/');
    }

    public static function content(InvitationBarcode $barcode): string
    {
        return self::baseUrl().'/'.$barcode->barcode_token;
    }

    public static function svg(InvitationBarcode $barcode, int $size = 180, int $margin = 1): string
    {
        return 'data:image/svg+xml;base64,'.base64_encode(
            QrCode::format('svg')
                ->size($size)
                ->margin($margin)
                ->generate(self::content($barcode))
        );
    }

    public static function extractToken(string $scanned): string
    {
        $value = trim($scanned);

        $baseHost = parse_url(self::baseUrl(), PHP_URL_HOST);

        if ($baseHost !== null) {
            $hostPos = stripos($value, $baseHost);

            if ($hostPos !== false) {
                $rest = substr($value, $hostPos + strlen($baseHost));
                $token = trim(explode('?', $rest, 2)[0], '/');

                if ($token !== '') {
                    return $token;
                }
            }
        }

        return $value;
    }
}
