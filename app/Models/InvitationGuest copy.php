<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class InvitationGuest extends Model
{
    protected $fillable = [
        'invitation_id',
        'uuid',
        'token',
        'name',
        'note',
        'group_name',
        'location_tag',
        'max_device',
        'last_ip',
        'last_user_agent',
        'is_locked',
    ];

    protected $casts = [
        'is_locked' => 'boolean',
    ];

    // 🔗 RELATION
    public function invitation()
    {
        return $this->belongsTo(Invitation::class);
    }

    // 🔥 AUTO GENERATE UUID + TOKEN
    protected static function booted()
    {
        static::creating(function ($guest) {

            // UUID (unik global)
            if (!$guest->uuid) {
                $guest->uuid = (string) Str::uuid();
            }

            // Token (random string)
            if (!$guest->token) {
                $guest->token = Str::random(40);
            }
        });
    }
}