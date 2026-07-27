<?php

namespace App\Http\Controllers\Api\Receptionist;

use App\Http\Controllers\Controller;
use App\Models\InvitationGuest;
use App\Models\GuestCheckin;
use App\Events\CheckinUpdated;
use Illuminate\Http\Request;

class ManualCheckinController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'guest_id' => 'required|exists:invitation_guests,id',
            'arrival_with' => 'required|in:sendiri,suami,istri,anak,orang_tua,saudara,teman',
            'attended_people' => 'nullable|integer|min:1',
        ]);

        $guest = InvitationGuest::findOrFail($request->guest_id);

        $activeCheckin = GuestCheckin::where('invitation_guest_id', $guest->id)
            ->whereNull('checkout_at')
            ->first();

        if ($activeCheckin) {
            return response()->json([
                'success' => false,
                'message' => 'Tamu sudah sedang dalam acara. Tidak bisa check-in ganda.',
            ], 409);
        }

        $previousCheckouts = GuestCheckin::where('invitation_guest_id', $guest->id)
            ->whereNotNull('checkout_at')
            ->count();

        $checkin = GuestCheckin::create([
            'invitation_id' => $guest->invitation_id,
            'invitation_guest_id' => $guest->id,
            'invitation_barcode_id' => null,
            'checkin_at' => now(),
            'arrival_with' => $request->arrival_with,
            'attended_people' => $request->attended_people ?? 1,
        ]);

        broadcast(new CheckinUpdated($guest, $checkin));

        $arrivalLabel = match ($request->arrival_with) {
            'suami' => 'sareng rabi',
            'istri' => 'sareng istri',
            'anak' => 'sareng budak',
            'orang_tua' => 'sareng kolot',
            'saudara' => 'sareng sadulur',
            'teman' => 'sareng babaturan',
            default => '',
        };

        if ($previousCheckouts > 0) {
            $message = "Wilujeng sumping deui, {$guest->name} {$arrivalLabel}.";
        } else {
            $message = "Wilujeng sumping, {$guest->name} {$arrivalLabel}.";
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => [
                'guest_name' => $guest->name,
                'arrival_with' => $request->arrival_with,
                'attended_people' => $checkin->attended_people,
                'checkin_at' => $checkin->checkin_at,
                'is_reentry' => $previousCheckouts > 0,
            ],
        ]);
    }

    public function checkout(Request $request)
    {
        $request->validate([
            'guest_id' => 'required|exists:invitation_guests,id',
        ]);

        $guest = InvitationGuest::findOrFail($request->guest_id);

        $activeCheckin = GuestCheckin::where('invitation_guest_id', $guest->id)
            ->whereNull('checkout_at')
            ->latest('checkin_at')
            ->first();

        if (!$activeCheckin) {
            $hasAnyCheckin = GuestCheckin::where('invitation_guest_id', $guest->id)->exists();

            if (!$hasAnyCheckin) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tamu belum pernah masuk. Tidak bisa checkout.',
                ], 404);
            }

            return response()->json([
                'success' => false,
                'message' => 'Tamu sudah checkout sebelumnya. Tidak ada sesi aktif.',
            ], 409);
        }

        $activeCheckin->update([
            'checkout_at' => now(),
        ]);

        broadcast(new CheckinUpdated($guest, $activeCheckin));

        return response()->json([
            'success' => true,
            'message' => "Terima kasih, {$guest->name}. Sampai jumpa!",
            'data' => [
                'guest_name' => $guest->name,
                'checkin_at' => $activeCheckin->checkin_at,
                'checkout_at' => $activeCheckin->checkout_at,
                'duration' => $activeCheckin->checkin_at->diffForHumans($activeCheckin->checkout_at, true),
            ],
        ]);
    }
}
