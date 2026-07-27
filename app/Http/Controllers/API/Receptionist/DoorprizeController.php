<?php

namespace App\Http\Controllers\Api\Receptionist;

use App\Http\Controllers\Controller;
use App\Models\InvitationGuest;
use App\Models\GuestCheckin;
use App\Models\DoorprizeWinner;
use Illuminate\Http\Request;

class DoorprizeController extends Controller
{
    public function spin(Request $request)
    {
        $request->validate([
            'invitation_id' => 'required|exists:invitations,id',
        ]);

        $invitationId = $request->invitation_id;

        $hasAccess = $request->user()->invitations->contains('id', $invitationId);

        if (!$hasAccess) {
            return response()->json([
                'message' => 'Anda tidak memiliki akses ke invitation ini.',
            ], 403);
        }

        $winnerGuestIds = DoorprizeWinner::where('invitation_id', $invitationId)
            ->pluck('invitation_guest_id')
            ->toArray();

        $eligibleGuests = InvitationGuest::where('invitation_id', $invitationId)
            ->whereHas('checkins', function ($q) {
                $q->whereNull('checkout_at');
            })
            ->whereHas('barcodes')
            ->when(!empty($winnerGuestIds), function ($q) use ($winnerGuestIds) {
                $q->whereNotIn('id', $winnerGuestIds);
            })
            ->with('barcodes')
            ->get();

        if ($eligibleGuests->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak ada tamu yang eligible untuk doorprize.',
            ], 404);
        }

        $winner = $eligibleGuests->random();

        $decoyPool = InvitationGuest::where('invitation_id', $invitationId)
            ->whereHas('barcodes')
            ->where('id', '!=', $winner->id)
            ->with('barcodes')
            ->get();

        $decoys = $decoyPool->random(min(20, $decoyPool->count()));

        $allEntries = $decoys->concat([$winner])->shuffle()->values();

        $winnerIndex = $allEntries->search(fn ($g) => $g->id === $winner->id);

        return response()->json([
            'success' => true,
            'entries' => $allEntries->map(fn ($g) => [
                'guest_id' => $g->id,
                'name' => $g->name,
                'barcode_code' => $g->barcodes->first()?->barcode_code,
            ]),
            'winner_index' => $winnerIndex,
            'winner' => [
                'guest_id' => $winner->id,
                'name' => $winner->name,
                'barcode_code' => $winner->barcodes->first()?->barcode_code,
            ],
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'invitation_id' => 'required|exists:invitations,id',
            'guest_id' => 'required|exists:invitation_guests,id',
            'prize' => 'required|string',
            'session' => 'required|string',
        ]);

        $hasAccess = $request->user()->invitations->contains('id', $request->invitation_id);

        if (!$hasAccess) {
            return response()->json([
                'message' => 'Anda tidak memiliki akses ke invitation ini.',
            ], 403);
        }

        $alreadyWon = DoorprizeWinner::where('invitation_id', $request->invitation_id)
            ->where('invitation_guest_id', $request->guest_id)
            ->exists();

        if ($alreadyWon) {
            return response()->json([
                'success' => false,
                'message' => 'Tamu ini sudah pernah menang doorprize.',
            ], 409);
        }

        $winner = DoorprizeWinner::create([
            'invitation_id' => $request->invitation_id,
            'invitation_guest_id' => $request->guest_id,
            'prize' => $request->prize,
            'session' => $request->session,
        ]);

        $guest = InvitationGuest::find($request->guest_id);

        return response()->json([
            'success' => true,
            'message' => "Selamat, {$guest->name}! Anda memenangkan {$request->prize}.",
            'data' => [
                'id' => $winner->id,
                'guest_name' => $guest->name,
                'prize' => $winner->prize,
                'session' => $winner->session,
            ],
        ]);
    }
}
