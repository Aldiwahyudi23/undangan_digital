<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class PengantinRole
{
    public function handle(Request $request, Closure $next)
    {
        if (!$request->user() || !$request->user()->hasRole('pengantin')) {
            return response()->json([
                'message' => 'Akses ditolak. Hanya mempelai yang diizinkan.',
            ], 403);
        }

        return $next($request);
    }
}
