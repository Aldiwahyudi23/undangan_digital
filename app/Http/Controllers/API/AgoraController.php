<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\InvitationGuest;
use App\Services\Agora\RtcTokenBuilder;
use App\Services\Agora\RtmTokenBuilder;

class AgoraController extends Controller
{
    /**
     * 🔑 GET TOKEN + AUTO JOIN LOGIC
     */
    public function token(Request $request)
    {
        $guestUuid = $request->guest_uuid;

        $guest = InvitationGuest::where('uuid', $guestUuid)->first();

        if (!$guest) {
            return response()->json(['message' => 'Guest tidak valid'], 403);
        }

        if ($guest->is_locked) {
            return response()->json(['message' => 'Akun di lock'], 403);
        }

        if (!$guest->invitation_id) {
            return response()->json(['message' => 'Invitation tidak valid'], 403);
        }

        // 🔥 CEK HOST SUDAH LIVE ATAU BELUM
        $hostActive = InvitationGuest::where('invitation_id', $guest->invitation_id)
            ->where('role', 'host')
            ->where('is_streaming', true)
            ->exists();

        if ($guest->role !== 'host' && !$hostActive) {
            return response()->json([
                'message' => 'Live belum dimulai oleh host'
            ], 403);
        }

        // ✅ TANDAI USER SEDANG NONTON LIVE
        $guest->is_watching_live = true;
        $guest->last_seen_live = now();
        $guest->save();

        $channelName        = env('AGORA_CHANNEL_PREFIX', 'invitation_') . $guest->invitation_id;
        $uid                = abs(crc32($guest->uuid));
        $expireTime         = env('AGORA_TOKEN_EXPIRE', 3600);
        $privilegeExpiredTs = now()->timestamp + $expireTime;

        if ($guest->role === 'host') {
            $agoraRole = RtcTokenBuilder::RolePublisher;

            // Host mulai streaming
            $guest->is_streaming = true;
            $guest->save();

        } else {
            $agoraRole = RtcTokenBuilder::RoleSubscriber;
        }

        $token = RtcTokenBuilder::buildTokenWithUid(
            env('AGORA_APP_ID'),
            env('AGORA_APP_CERTIFICATE'),
            $channelName,
            $uid,
            $agoraRole,
            $privilegeExpiredTs
        );

        $rtmToken = RtmTokenBuilder::buildToken(
            env('AGORA_APP_ID'),
            env('AGORA_APP_CERTIFICATE'),
            (string) $uid,
            RtmTokenBuilder::RoleRtmUser,
            $privilegeExpiredTs
        );

        return response()->json([
            'appId'   => env('AGORA_APP_ID'),
            'rtcToken'   => $token,
            'rtmToken' => $rtmToken,
            'channel' => $channelName,
            'uid'     => $uid,
            'role'    => $agoraRole === RtcTokenBuilder::RolePublisher ? 'publisher' : 'viewer',
            'guest'   => [
                'uuid' => $guest->uuid,
                'name' => $guest->name,
                'role' => $guest->role,
            ]
        ]);
    }

    /**
     * ❤️ HEARTBEAT (dipanggil tiap 10 detik dari frontend)
     */
    public function heartbeat(Request $request)
    {
        $guest = InvitationGuest::where('uuid', $request->guest_uuid)->first();

        if (!$guest) {
            return response()->json(['message' => 'Guest tidak valid'], 403);
        }

        $guest->last_seen_live = now();
        $guest->is_watching_live = true;
        $guest->save();

        return response()->json(['status' => 'ok']);
    }

    /**
     * 👀 HITUNG VIEWER
     */
    public function viewers(Request $request)
    {
        $invitationId = $request->invitation_id;

        $count = InvitationGuest::where('invitation_id', $invitationId)
            ->where('is_watching_live', true)
            ->where('last_seen_live', '>=', now()->subSeconds(30))
            ->count();

        return response()->json([
            'viewers' => $count
        ]);
    }

    /**
     * 🎥 JOIN SLOT (naik ke video)
     */
    public function joinSlot(Request $request)
    {
        $guestUuid = $request->guest_uuid;

        $guest = InvitationGuest::where('uuid', $guestUuid)->first();

        if (!$guest) {
            return response()->json(['message' => 'Guest tidak valid'], 403);
        }

        $hostActive = InvitationGuest::where('invitation_id', $guest->invitation_id)
            ->where('role', 'host')
            ->where('is_streaming', true)
            ->exists();

        if (!$hostActive) {
            return response()->json(['message' => 'Live sudah berakhir'], 403);
        }

        $activeCount = InvitationGuest::where('invitation_id', $guest->invitation_id)
            ->where('is_streaming', true)
            ->count();

        if ($activeCount >= 4) {
            return response()->json(['message' => 'Slot penuh'], 403);
        }

        $guest->is_streaming = true;
        $guest->save();

        $channelName        = env('AGORA_CHANNEL_PREFIX', 'invitation_') . $guest->invitation_id;
        $uid                = abs(crc32($guest->uuid));
        $expireTime         = env('AGORA_TOKEN_EXPIRE', 3600);
        $privilegeExpiredTs = now()->timestamp + $expireTime;

        $token = RtcTokenBuilder::buildTokenWithUid(
            env('AGORA_APP_ID'),
            env('AGORA_APP_CERTIFICATE'),
            $channelName,
            $uid,
            RtcTokenBuilder::RolePublisher,
            $privilegeExpiredTs
        );

        return response()->json([
            'rtcToken' => $token,
            'uid'   => $uid
        ]);
    }

    /**
     * ⬇️ LEAVE SLOT
     */
    public function leaveSlot(Request $request)
    {
        $guest = InvitationGuest::where('uuid', $request->guest_uuid)->first();

        if (!$guest) {
            return response()->json(['message' => 'Guest tidak valid'], 403);
        }

        $guest->is_streaming = false;
        $guest->save();

        return response()->json(['message' => 'Keluar slot']);
    }

    /**
     * 🚪 LEAVE LIVE (tutup tab)
     */
    public function leave(Request $request)
    {
        $guest = InvitationGuest::where('uuid', $request->guest_uuid)->first();

        if (!$guest) {
            return response()->json(['message' => 'Guest tidak valid'], 403);
        }

        $guest->is_streaming = false;
        $guest->is_watching_live = false;
        $guest->save();

        return response()->json([
            'message' => 'Keluar dari live'
        ]);
    }

    /**
     * 👑 HOST KICK USER
     */
    public function kick(Request $request)
    {
        $host = InvitationGuest::where('uuid', $request->guest_uuid)->first();

        if (!$host || $host->role !== 'host') {
            return response()->json(['message' => 'Bukan host'], 403);
        }

        $target = InvitationGuest::where('uuid', $request->target_uuid)->first();

        if (!$target || $target->invitation_id !== $host->invitation_id) {
            return response()->json(['message' => 'Target tidak valid'], 403);
        }

        $target->is_streaming = false;
        $target->is_watching_live = false;
        $target->save();

        return response()->json([
            'message' => 'User di-kick'
        ]);
    }

    /**
     * 📡 STATUS LIVE
     */
    public function status(Request $request)
    {
        $invitationId = $request->invitation_id;

        $active = InvitationGuest::where('invitation_id', $invitationId)
            ->where('role', 'host')
            ->where('is_streaming', true)
            ->exists();

        return response()->json([
            'is_live' => $active
        ]);
    }
}