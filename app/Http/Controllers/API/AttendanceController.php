<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\GiftAccount;
use App\Models\InvitationGuest;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    // ✅ GET DATA (list ucapan + status user login)
public function index(Request $request)
{
    $guestId = $request->get('guest_id');

    if (!$guestId) {
        return response()->json([
            'success' => false,
            'message' => 'Guest ID diperlukan'
        ], 400);
    }

    $guest = InvitationGuest::find($guestId);

    if (!$guest) {
        return response()->json([
            'success' => false,
            'message' => 'Guest tidak ditemukan'
        ], 404);
    }

    $invitationId = $guest->invitation_id;

    // 🔥 ambil semua attendance
    $attendances = Attendance::with('guest')
        ->where('invitation_id', $invitationId)
        ->latest()
        ->get();

    // 🔥 FIX: cek langsung ke DB
    $hasAttendance = Attendance::where('invitation_guest_id', $guestId)->exists();

    // 🔥 format data
    $data = $attendances->map(function ($item) {
        return [
            'id' => $item->id,
            'name' => optional($item->guest)->name,
            'group_name' => optional($item->guest)->group_name,
            'status' => $item->status,
            'total_guests' => $item->total_guests,
            'message' => $item->message,
            'is_private' => $item->is_private,
            'replied_at' => $item->replied_at,
            'created_at' => $item->created_at,
        ];
    });

    return response()->json([
        'success' => true,
        'data' => $data,
        'meta' => [
            'has_attendance' => $hasAttendance // ✅ FIXED
        ]
    ]);
}

    // ✅ POST (kirim konfirmasi + ucapan)
    public function store(Request $request)
    {
        $request->validate([
            'status' => 'required|in:attending,not_attending',
            'total_guests' => 'nullable|integer|min:1',
            'message' => 'nullable|string|max:1000',
            'is_private' => 'boolean'
        ]);

        $guest = $request->user();

        if (!$guest) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 401);
        }

        $attendance = Attendance::updateOrCreate(
            [
                'invitation_guest_id' => $guest->id
            ],
            [
                'invitation_id' => $guest->invitation_id,
                'status' => $request->status,
                'total_guests' => $request->status === 'attending' ? $request->total_guests : null,
                'message' => $request->message,
                'is_private' => $request->is_private ?? false,
                'replied_at' => now()
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Berhasil mengirim konfirmasi',
            'data' => [
                'id' => $attendance->id,
                'name' => $guest->name,
                'group_name' => $guest->group_name,
                'status' => $attendance->status,
                'message' => $attendance->message,
                'total_guests' => $attendance->total_guests,
                'is_private' => $attendance->is_private,
                'replied_at' => $attendance->replied_at
            ],
            'meta' => [
                'has_attendance' => true
            ]
        ]);
    }

    public function getGiftAccounts(Request $request)
    {
        $invitationId = $request->get('invitation_id');

        if (!$invitationId) {
            return response()->json([
                'success' => false,
                'message' => 'Invitation ID diperlukan'
            ], 400);
        }

        $accounts = GiftAccount::with('couple')
            ->where('invitation_id', $invitationId)
            ->get();

        $data = $accounts->map(function ($item) {
            return [
                'id' => $item->id,
                'couple_name' => optional($item->couple)->full_name,
                'bank_name' => $item->bank_name,
                'account_number' => $item->account_number,
                'account_name' => $item->account_name,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

}