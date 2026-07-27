<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ReceptionistRole
{
    public function handle(Request $request, Closure $next)
    {
        if (!$request->user() || !$request->user()->hasRole('receptionist')) {
            return response()->json([
                'message' => 'Akses ditolak. Hanya resepsionis yang diizinkan.',
            ], 403);
        }

        return $next($request);
    }
}
