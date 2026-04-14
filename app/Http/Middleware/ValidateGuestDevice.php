<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ValidateGuestDevice
{
    public function handle(Request $request, Closure $next)
    {
        $guest = $request->user();
        
        if (!$guest) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated'
            ], 401);
        }
        
        $currentToken = $guest->currentAccessToken();
        $deviceFingerprint = $currentToken->device_fingerprint ?? null;
        
        // Validasi device masih terdaftar
        if ($deviceFingerprint && !in_array($deviceFingerprint, $guest->device_ids ?? [])) {
            // Hapus token yang tidak valid
            $currentToken->delete();
            
            return response()->json([
                'success' => false,
                'message' => 'Device tidak terdaftar, silahkan login ulang'
            ], 401);
        }
        
        return $next($request);
    }
}