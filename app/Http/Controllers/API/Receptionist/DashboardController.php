<?php

namespace App\Http\Controllers\Api\Receptionist;

use App\Http\Controllers\Controller;
use App\Models\InvitationGuest;
use App\Models\GuestCheckin;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        $invitationId = $request->query('invitation_id');

        if (!$invitationId) {
            $invitationId = $request->user()->invitations->first()?->id;
        }

        if (!$invitationId) {
            return response()->json([
                'message' => 'Tidak ada invitation yang terhubung.',
            ], 404);
        }

        $hasAccess = $request->user()->invitations->contains('id', $invitationId);

        if (!$hasAccess) {
            return response()->json([
                'message' => 'Anda tidak memiliki akses ke invitation ini.',
            ], 403);
        }

        $guests = InvitationGuest::where('invitation_id', $invitationId);

        $totalGuests = (clone $guests)->count();

        $digitalGuests = (clone $guests)
            ->where('invitation_type', 'digital')
            ->count();

        $physicalGuests = (clone $guests)
            ->where('invitation_type', 'physical')
            ->count();

        $digitalConfirmed = (clone $guests)
            ->where('invitation_type', 'digital')
            ->whereHas('attendance', fn ($q) => $q->where('status', 'attending'))
            ->count();

        $checkins = GuestCheckin::where('invitation_id', $invitationId);

        $totalAttended = (clone $checkins)->sum('attended_people');

        $currentlyPresent = (clone $checkins)
            ->whereNull('checkout_at')
            ->sum('attended_people');

        $alreadyCheckedOut = (clone $checkins)
            ->whereNotNull('checkout_at')
            ->sum('attended_people');

        return response()->json([
            'invitation' => [
                'id' => $invitationId,
                'title' => $request->user()->invitations->firstWhere('id', $invitationId)?->title,
            ],
            'stats' => [
                'total_guests' => $totalGuests,
                'digital_guests' => $digitalGuests,
                'physical_guests' => $physicalGuests,
                'digital_confirmed' => $digitalConfirmed,
                'total_attended' => $totalAttended,
                'currently_present' => $currentlyPresent,
                'already_checked_out' => $alreadyCheckedOut,
            ],
        ]);
    }
}
