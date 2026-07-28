<?php

namespace App\Http\Controllers\Api\Receptionist;

use App\Http\Controllers\Controller;
use App\Models\GuestCheckin;
use Illuminate\Http\Request;

class DashboardCheckinController extends Controller
{
    public function index(Request $request)
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

        $query = GuestCheckin::where('guest_checkins.invitation_id', $invitationId)
            ->join('invitation_guests', 'guest_checkins.invitation_guest_id', '=', 'invitation_guests.id')
            ->leftJoin('invitation_barcodes', 'guest_checkins.invitation_barcode_id', '=', 'invitation_barcodes.id')
            ->select([
                'guest_checkins.id',
                'invitation_guests.name',
                'invitation_guests.group_name',
                'invitation_barcodes.barcode_code',
                'guest_checkins.checkin_at',
                'guest_checkins.checkout_at',
                'guest_checkins.arrival_with',
                'guest_checkins.attended_people',
                'guest_checkins.invitation_barcode_id',
            ])
            ->orderByDesc('guest_checkins.checkin_at');

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('invitation_guests.name', 'like', "%{$search}%")
                  ->orWhere('invitation_guests.group_name', 'like', "%{$search}%");
            });
        }

        $perPage = (int) $request->query('per_page', 10);
        $checkins = $query->paginate($perPage);

        $data = $checkins->getCollection()->map(function ($checkin) {
            $status = $checkin->checkout_at ? 'checked_out' : 'checked_in';
            $typeCheckin = $checkin->invitation_barcode_id ? 'barcode' : 'manual';

            return [
                'id' => $checkin->id,
                'name' => $checkin->name,
                'group_name' => $checkin->group_name,
                'barcode_code' => $checkin->barcode_code,
                'status' => $status,
                'checkin_at' => $checkin->checkin_at,
                'checkout_at' => $checkin->checkout_at,
                'arrival_with' => $checkin->arrival_with,
                'attended_people' => $checkin->attended_people,
                'type_checkin' => $typeCheckin,
            ];
        });

        return response()->json([
            'data' => $data,
            'meta' => [
                'current_page' => $checkins->currentPage(),
                'last_page' => $checkins->lastPage(),
                'per_page' => $checkins->perPage(),
                'total' => $checkins->total(),
            ],
        ]);
    }
}
