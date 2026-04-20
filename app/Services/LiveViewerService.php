<?php

namespace App\Services;

use Illuminate\Support\Facades\Redis;

class LiveViewerService
{
    public static function key($invitationId)
    {
        return "live:{$invitationId}:viewers";
    }

    // user masuk
    public static function join($invitationId, $userId)
    {
        Redis::sadd(self::key($invitationId), $userId);
    }

    // user keluar
    public static function leave($invitationId, $userId)
    {
        Redis::srem(self::key($invitationId), $userId);
    }

    // total viewer
    public static function count($invitationId)
    {
        return Redis::scard(self::key($invitationId));
    }
}