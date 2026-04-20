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

    // ❌ Guest biasa tidak bisa request token kalau host belum live
    if ($guest->role !== 'host' && !$hostActive) {
        return response()->json([
            'message' => 'Live belum dimulai oleh host'
        ], 403);
    }

    $channelName        = env('AGORA_CHANNEL_PREFIX', 'invitation_') . $guest->invitation_id;
    $uid                = abs(crc32($guest->uuid));
    $expireTime         = env('AGORA_TOKEN_EXPIRE', 3600);
    $privilegeExpiredTs = now()->timestamp + $expireTime;

    // ─────────────────────────────────────────────────────────────────────
    // ROLE AGORA & is_streaming
    //
    // HOST yang request token:
    //   → Selalu publisher (dia yang memulai live)
    //   → is_streaming = true
    //
    // GUEST yang request token:
    //   → Selalu viewer/audience dulu (nonton dulu, belum ikut di video)
    //   → is_streaming = false  ← tidak diubah di sini
    //   → Nanti kalau mau ikut video, dia hit endpoint /agora/join-slot
    // ─────────────────────────────────────────────────────────────────────

    if ($guest->role === 'host') {
        $agoraRole = RtcTokenBuilder::RolePublisher;

        // Tandai host sedang streaming
        $guest->is_streaming = true;
        $guest->save();

    } else {
        // Guest → masuk sebagai audience dulu
        // is_streaming tetap false, tidak diubah
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
        (string) $uid, // penting: string
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
            'role' => $guest->role,  // 'host' atau 'guest' dari DB
        ]
    ]);
}

// ─────────────────────────────────────────────────────────────────────────────
// ENDPOINT BARU: Guest minta naik ke slot video
// POST /guest/agora/join-slot
// ─────────────────────────────────────────────────────────────────────────────
public function joinSlot(Request $request)
{
    $guestUuid = $request->guest_uuid;

    $guest = InvitationGuest::where('uuid', $guestUuid)->first();

    if (!$guest) {
        return response()->json(['message' => 'Guest tidak valid'], 403);
    }

    // Cek host masih aktif
    $hostActive = InvitationGuest::where('invitation_id', $guest->invitation_id)
        ->where('role', 'host')
        ->where('is_streaming', true)
        ->exists();

    if (!$hostActive) {
        return response()->json(['message' => 'Live sudah berakhir'], 403);
    }

    // Hitung slot yang terpakai (is_streaming = true), max 4
    $activeCount = InvitationGuest::where('invitation_id', $guest->invitation_id)
        ->where('is_streaming', true)
        ->count();

    if ($activeCount >= 4) {
        return response()->json(['message' => 'Slot penuh, tidak bisa bergabung'], 403);
    }

    // Tandai guest ini sekarang ikut streaming
    $guest->is_streaming = true;
    $guest->save();

    // Generate token publisher untuk guest ini
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
        'uid'   => $uid,
        'message' => 'Berhasil bergabung ke slot'
    ]);
}

// ─────────────────────────────────────────────────────────────────────────────
// ENDPOINT: Leave slot (turun dari video, tetap nonton)
// POST /guest/agora/leave-slot
// ─────────────────────────────────────────────────────────────────────────────
public function leaveSlot(Request $request)
{
    $guestUuid = $request->guest_uuid;

    $guest = InvitationGuest::where('uuid', $guestUuid)->first();

    if (!$guest) {
        return response()->json(['message' => 'Guest tidak valid'], 403);
    }

    // Set is_streaming = false, tapi guest masih di halaman live
    $guest->is_streaming = false;
    $guest->save();

    return response()->json(['message' => 'Berhasil keluar dari slot']);
}

// ─────────────────────────────────────────────────────────────────────────────
// ENDPOINT: User disconnect/keluar tab — update is_streaming via UID
// POST /guest/agora/leave-by-uid
// Dipanggil oleh host saat event user-left di Agora (karena hanya host yang tau)
// ─────────────────────────────────────────────────────────────────────────────
public function leaveByUid(Request $request)
{
    $uid          = $request->uid;
    $invitationId = $request->invitation_id;
 
    if (!$uid || !$invitationId) {
        return response()->json(['message' => 'Data tidak lengkap'], 422);
    }
 
    // Cari guest berdasarkan uid yang digenerate dari crc32(uuid)
    // Loop semua guest di invitation, cek uid-nya
    $guests = InvitationGuest::where('invitation_id', $invitationId)
        ->where('is_streaming', true)
        ->get();
 
    foreach ($guests as $g) {
        $generatedUid = abs(crc32($g->uuid));
        if ($generatedUid === (int) $uid) {
            $g->is_streaming = false;
            $g->save();
            return response()->json(['message' => 'is_streaming diupdate']);
        }
    }
 
    return response()->json(['message' => 'Guest tidak ditemukan'], 404);
}

    /**
     * 🚪 LEAVE / DISCONNECT
     */
    public function leave(Request $request)
    {
        $guest = InvitationGuest::where('uuid', $request->guest_uuid)->first();

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
        $host = InvitationGuest::where('uuid', $request->guest_uuid)->first();

        if (!$host || $host->role !== 'host') {
            return response()->json(['message' => 'Bukan host'], 403);
        }

        $target = InvitationGuest::where('uuid', $request->target_uuid)->first();

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
            ->where('role', 'host')
            ->where('is_streaming', true)
            ->exists();

        return response()->json([
            'success' => true,
            'is_live' => $active
        ]);
    }
}