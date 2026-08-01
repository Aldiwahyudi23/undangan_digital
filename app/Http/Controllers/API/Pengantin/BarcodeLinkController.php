<?php

namespace App\Http\Controllers\Api\Pengantin;

use App\Http\Controllers\Controller;
use App\Models\InvitationBarcode;
use App\Models\InvitationGuest;
use Illuminate\Http\Request;

class BarcodeLinkController extends Controller
{
    public function searchBarcode(Request $request)
    {
        $request->validate([
            'code' => 'required|string|min:2',
            'invitation_id' => 'required|exists:invitations,id',
        ]);

        $hasAccess = $request->user()->invitations->contains('id', $request->invitation_id);

        if (!$hasAccess) {
            return response()->json([
                'message' => 'Anda tidak memiliki akses ke invitation ini.',
            ], 403);
        }

        $barcode = InvitationBarcode::where('barcode_code', 'like', "%{$request->code}%")
            ->where('invitation_id', $request->invitation_id)
            ->with('guest')
            ->first();

        if (!$barcode) {
            return response()->json([
                'message' => 'Barcode tidak ditemukan.',
            ], 404);
        }

        return response()->json([
            'data' => [
                'id' => $barcode->id,
                'barcode_code' => $barcode->barcode_code,
                'is_used' => $barcode->is_used,
                'guest' => $barcode->guest ? [
                    'id' => $barcode->guest->id,
                    'name' => $barcode->guest->name,
                    'group_name' => $barcode->guest->group_name,
                    'invitation_type' => $barcode->guest->invitation_type,
                ] : null,
            ],
        ]);
    }

    public function scanBarcode(Request $request)
    {
        $request->validate([
            'barcode_token' => 'required|string',
            'invitation_id' => 'required|exists:invitations,id',
        ]);

        $hasAccess = $request->user()->invitations->contains('id', $request->invitation_id);

        if (!$hasAccess) {
            return response()->json([
                'message' => 'Anda tidak memiliki akses ke invitation ini.',
            ], 403);
        }

        $barcode = InvitationBarcode::where('barcode_token', $request->barcode_token)
            ->where('invitation_id', $request->invitation_id)
            ->with('guest')
            ->first();

        if (!$barcode) {
            return response()->json([
                'message' => 'Barcode tidak ditemukan.',
            ], 404);
        }

        return response()->json([
            'data' => [
                'id' => $barcode->id,
                'barcode_code' => $barcode->barcode_code,
                'barcode_token' => $barcode->barcode_token,
                'is_used' => $barcode->is_used,
                'guest' => $barcode->guest ? [
                    'id' => $barcode->guest->id,
                    'name' => $barcode->guest->name,
                    'group_name' => $barcode->guest->group_name,
                    'invitation_type' => $barcode->guest->invitation_type,
                ] : null,
            ],
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'barcode_id' => 'required|exists:invitation_barcodes,id',
            'name' => 'required|string|max:255',
            'group_name' => 'nullable|string|max:255',
        ]);

        $barcode = InvitationBarcode::with('guest')->findOrFail($request->barcode_id);

        $hasAccess = $request->user()->invitations->contains('id', $barcode->invitation_id);

        if (!$hasAccess) {
            return response()->json([
                'message' => 'Anda tidak memiliki akses ke invitation ini.',
            ], 403);
        }

        if ($barcode->guest) {
            return response()->json([
                'success' => false,
                'message' => 'Barcode sudah terhubung dengan tamu.',
            ], 409);
        }

        $guest = InvitationGuest::create([
            'invitation_id' => $barcode->invitation_id,
            'name' => $request->name,
            'group_name' => $request->group_name,
            'invitation_type' => 'physical',
        ]);

        $barcode->update([
            'invitation_guest_id' => $guest->id,
            'is_used' => true,
        ]);

        return response()->json([
            'success' => true,
            'message' => "Tamu {$guest->name} berhasil dikaitkan dengan barcode {$barcode->barcode_code}.",
            'data' => [
                'guest_id' => $guest->id,
                'name' => $guest->name,
                'group_name' => $guest->group_name,
                'invitation_type' => $guest->invitation_type,
                'barcode_code' => $barcode->barcode_code,
            ],
        ]);
    }
}
