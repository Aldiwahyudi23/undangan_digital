<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LiveChat extends Model
{
    protected $fillable = [
        'invitation_id',
        'invitation_guest_id',
        'message',
        'type',
        'is_deleted'
    ];

    /**
     * 🔗 Relasi ke Invitation (Live)
     */
    public function invitation()
    {
        return $this->belongsTo(Invitation::class);
    }

    /**
     * 🔗 Relasi ke Guest
     */
    public function guest()
    {
        return $this->belongsTo(InvitationGuest::class);
    }

    /**
     * 🎯 Accessor: ambil nama guest langsung
     */
    public function getGuestNameAttribute()
    {
        return $this->guest->name ?? 'Guest';
    }
}