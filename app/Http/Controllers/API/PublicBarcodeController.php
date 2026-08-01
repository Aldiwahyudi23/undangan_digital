<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\InvitationBarcode;
use App\Services\BarcodeQrService;

class PublicBarcodeController extends Controller
{
    public function show(string $token)
    {
        $barcode = InvitationBarcode::where('barcode_token', $token)
            ->with('guest')
            ->first();

        if (!$barcode) {
            return response()->json([
                'success' => false,
                'message' => 'Barcode tidak ditemukan.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'barcode' => [
                    'id' => $barcode->id,
                    'barcode_code' => $barcode->barcode_code,
                    'barcode_url' => BarcodeQrService::content($barcode),
                    'barcode_svg' => BarcodeQrService::svg($barcode),
                ],
                'guest' => $barcode->guest ? [
                    'name' => $barcode->guest->name,
                    'group_name' => $barcode->guest->group_name,
                    'location_tag' => $barcode->guest->location_tag,
                ] : null,
            ],
        ]);
    }
}
