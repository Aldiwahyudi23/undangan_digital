<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\InvitationGuest;
use App\Services\Agora\RtcTokenBuilder;

class AgoraController extends Controller
{
    /**
     * 🔑 GET TOKEN + AUTO JOIN LOGIC
     */
    public function token(Request $request)
    {
        $guestToken = $request->guest_token;

        $guest = InvitationGuest::where('token', $guestToken)->first();

        if (!$guest) {
            return response()->json(['message' => 'Guest tidak valid'], 403);
        }

        if ($guest->is_locked) {
            return response()->json(['message' => 'Akun di lock'], 403);
        }

        if (!$guest->invitation_id) {
            return response()->json(['message' => 'Invitation tidak valid'], 403);
        }

        // 🔥 HITUNG YANG LAGI STREAMING
        $activeStreaming = InvitationGuest::where('invitation_id', $guest->invitation_id)
            ->where('is_streaming', true)
            ->count();

        // 🎯 TENTUKAN ROLE
        if ($activeStreaming < 4) {
            $role = RtcTokenBuilder::RolePublisher;

            // tandai user ini sebagai streaming
            $guest->is_streaming = true;
            $guest->save();
        } else {
            $role = RtcTokenBuilder::RoleSubscriber;
        }

        // 🎯 CHANNEL
        $channelName = env('AGORA_CHANNEL_PREFIX', 'invitation_') . $guest->invitation_id;

        // 🎯 UID
        $uid = abs(crc32($guest->uuid));

        // 🎯 EXPIRE
        $expireTime = env('AGORA_TOKEN_EXPIRE', 3600);
        $currentTs = now()->timestamp;
        $privilegeExpiredTs = $currentTs + $expireTime;

        // 🎯 GENERATE TOKEN
        $token = RtcTokenBuilder::buildTokenWithUid(
            env('AGORA_APP_ID'),
            env('AGORA_APP_CERTIFICATE'),
            $channelName,
            $uid,
            $role,
            $privilegeExpiredTs
        );

        return response()->json([
            'appId' => env('AGORA_APP_ID'),
            'token' => $token,
            'channel' => $channelName,
            'uid' => $uid,
            'role' => $role === 1 ? 'publisher' : 'viewer',
            'is_streaming' => $guest->is_streaming
        ]);
    }

    /**
     * 🚪 LEAVE / DISCONNECT
     */
    public function leave(Request $request)
    {
        $guest = InvitationGuest::where('token', $request->guest_token)->first();

        if (!$guest) {
            return response()->json(['message' => 'Guest tidak valid'], 403);
        }

        $guest->is_streaming = false;
        $guest->save();

        return response()->json([
            'message' => 'Berhasil keluar dari live'
        ]);
    }

    /**
     * 👑 HOST KICK USER
     */
    public function kick(Request $request)
    {
        $host = InvitationGuest::where('token', $request->guest_token)->first();

        if (!$host || $host->role !== 'host') {
            return response()->json(['message' => 'Bukan host'], 403);
        }

        $target = InvitationGuest::find($request->target_id);

        if (!$target || $target->invitation_id !== $host->invitation_id) {
            return response()->json(['message' => 'Target tidak valid'], 403);
        }

        $target->is_streaming = false;
        $target->save();

        return response()->json([
            'message' => 'User berhasil di-kick'
        ]);
    }

    // cek apakah ada live
    public function status(Request $request)
    {
        $invitationId = $request->invitation_id;

        $active = InvitationGuest::where('invitation_id', $invitationId)
            ->where('is_streaming', true)
            ->exists();

        return response()->json([
            'success' => true,
            'is_live' => $active
        ]);
    }
}