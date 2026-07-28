<?php

namespace App\Http\Controllers\Api\Pengantin;

use App\Http\Controllers\Controller;
use App\Models\InvitationGuest;
use Illuminate\Http\Request;

class GuestSearchController extends Controller
{
    public function search(Request $request)
    {
        $request->validate([
            'q' => 'required|string|min:2',
            'invitation_id' => 'required|exists:invitations,id',
        ]);

        $hasAccess = $request->user()->invitations->contains('id', $request->invitation_id);

        if (!$hasAccess) {
            return response()->json([
                'message' => 'Anda tidak memiliki akses ke invitation ini.',
            ], 403);
        }

        $guests = InvitationGuest::where('invitation_id', $request->invitation_id)
            ->where(function ($query) use ($request) {
                $query->where('name', 'like', "%{$request->q}%")
                    ->orWhere('share_whatsapp', 'like', "%{$request->q}%")
                    ->orWhere('group_name', 'like', "%{$request->q}%");
            })
            ->with(['attendance', 'checkins' => function ($q) {
                $q->latest('checkin_at');
            }])
            ->limit(20)
            ->get()
            ->map(function ($guest) {
                $latestCheckin = $guest->checkins->first();

                $checkinStatus = 'not_checked_in';
                if ($latestCheckin) {
                    $checkinStatus = $latestCheckin->checkout_at ? 'checked_out' : 'checked_in';
                }

                $typeCheckin = 'manual';
                if ($latestCheckin?->invitation_barcode_id) {
                    $typeCheckin = 'barcode';
                }

                return [
                    'id' => $guest->id,
                    'name' => $guest->name,
                    'group_name' => $guest->group_name,
                    'share_whatsapp' => $guest->share_whatsapp,
                    'invitation_type' => $guest->invitation_type,
                    'barcode' => $guest->barcodes->first()?->barcode_code,
                    'checkin_status' => $checkinStatus,
                    'latest_checkin' => $latestCheckin ? [
                        'checkin_at' => $latestCheckin->checkin_at,
                        'checkout_at' => $latestCheckin->checkout_at,
                        'arrival_with' => $latestCheckin->arrival_with,
                        'attended_people' => $latestCheckin->attended_people,
                        'type_checkin' => $typeCheckin,
                    ] : null,
                    'total_checkins' => $guest->checkins->count(),
                ];
            });

        return response()->json(['guests' => $guests]);
    }
}
