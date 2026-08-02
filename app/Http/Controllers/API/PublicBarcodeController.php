<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GuestCheckin;
use App\Models\InvitationBarcode;
use App\Models\InvitationGuest;
use App\Services\BarcodeQrService;
use Illuminate\Http\Request;

class PublicBarcodeController extends Controller
{
    public function show(string $token)
    {
        $barcode = InvitationBarcode::where('barcode_token', $token)
            ->with('guest')
            ->first();

        if (!$barcode) {
            return response()->json([
                'success' => false,
                'message' => 'Barcode tidak ditemukan.',
            ], 404);
        }

        $guest = $barcode->guest;

        $isCheckin = $guest && GuestCheckin::where('invitation_guest_id', $guest->id)
            ->whereNull('checkout_at')
            ->exists();

        $isCheckout = $guest && GuestCheckin::where('invitation_guest_id', $guest->id)
            ->whereNotNull('checkout_at')
            ->exists();

        if ($isCheckout) {
            return response()->json([
                'success' => true,
                'data' => [
                    'is_checkin' => false,
                    'is_checkout' => true,
                ],
            ]);
        }

        $couple = null;
        $event = null;

        $invitation = $guest?->invitation;

        if ($invitation) {
            $male = $invitation->couples->firstWhere('gender', 'male');
            $female = $invitation->couples->firstWhere('gender', 'female');

            $couple = [
                'cpp' => $male ? [
                    'nickname' => $male->nickname,
                    'full_name' => $male->full_name,
                ] : null,
                'cpw' => $female ? [
                    'nickname' => $female->nickname,
                    'full_name' => $female->full_name,
                ] : null,
            ];

            $latestEventWithMap = $invitation->events()
                ->whereNotNull('map_id')
                ->orderBy('date', 'desc')
                ->first();

            if ($latestEventWithMap) {
                $event = [
                    'title' => $latestEventWithMap->title,
                    'date' => $latestEventWithMap->date?->toDateString(),
                    'start_time' => $latestEventWithMap->start_time?->format('H:i'),
                    'end_time' => $latestEventWithMap->end_time?->format('H:i'),
                ];
            }
        }

        $moments = collect();
        $voices = collect();

        if ($guest) {
            $posts = $guest->posts()
                ->whereIn('type', ['moment', 'voice'])
                ->with('media')
                ->orderByDesc('created_at')
                ->get();

            $moments = $posts->where('type', 'moment')->values()->map(fn ($post) => $this->formatPostForBarcode($post));
            $voices = $posts->where('type', 'voice')->values()->map(fn ($post) => $this->formatPostForBarcode($post));
        }

        return response()->json([
            'success' => true,
            'data' => [
                'is_checkin' => (bool) $isCheckin,
                'is_checkout' => false,
                'invitation_id' => $guest?->invitation_id,
                'guest' => $guest ? [
                    'id' => $guest->id,
                    'name' => $guest->name,
                    'group_name' => $guest->group_name,
                    'location_tag' => $guest->location_tag,
                    'has_shared_moment' => $moments->isNotEmpty(),
                    'has_shared_voice' => $voices->isNotEmpty(),
                ] : null,
                'moments' => $moments->values(),
                'voices' => $voices->values(),
                'couple' => $couple,
                'event' => $event,
                'barcode' => [
                    'id' => $barcode->id,
                    'barcode_code' => $barcode->barcode_code,
                    'barcode_url' => BarcodeQrService::content($barcode),
                    'barcode_svg' => BarcodeQrService::svg($barcode),
                ],
            ],
        ]);
    }

    private function formatPostForBarcode($post): array
    {
        $collection = $post->type === 'voice' ? 'voice' : 'moments';
        $mediaItems = $post->getMedia($collection);

        return [
            'id' => $post->id,
            'type' => $post->type,
            'caption' => $post->caption,
            'created_at' => $post->created_at,
            'media' => $mediaItems->map(fn ($media) => [
                'id' => $media->id,
                'type' => match (true) {
                    str_starts_with($media->mime_type, 'video') => 'video',
                    str_starts_with($media->mime_type, 'audio') => 'audio',
                    default => 'image',
                },
                'original_url' => $media->getUrl(),
                'thumbnail_url' => $media->getUrl('thumb'),
                'mime_type' => $media->mime_type,
                'duration' => $media->getCustomProperty('duration'),
            ])->values(),
        ];
    }

    public function search(Request $request)
    {
        $request->validate([
            'name' => 'required|string|min:2',
        ]);

        $guests = InvitationGuest::with('invitation')
            ->whereHas('barcode')
            ->where('name', 'like', '%'.$request->name.'%')
            ->orderBy('name')
            ->limit(10)
            ->get()
            ->map(function ($guest) {
                return [
                    'id' => $guest->id,
                    'name' => $guest->name,
                    'group_name' => $guest->group_name,
                    'location_tag' => $guest->location_tag,
                    'invitation_id' => $guest->invitation_id,
                    'invitation_title' => optional($guest->invitation)->title,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $guests,
        ]);
    }

    public function verify(Request $request)
    {
        $request->validate([
            'invitation_guest_id' => 'required|exists:invitation_guests,id',
            'code' => 'required|string',
        ]);

        $barcode = InvitationBarcode::where('invitation_guest_id', $request->invitation_guest_id)
            ->whereRaw('UPPER(barcode_code) = ?', [strtoupper($request->code)])
            ->first();

        if (!$barcode) {
            return response()->json([
                'success' => false,
                'message' => 'Kode tidak valid.',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'barcode_token' => $barcode->barcode_token,
                'barcode_url' => BarcodeQrService::content($barcode),
            ],
        ]);
    }
}
