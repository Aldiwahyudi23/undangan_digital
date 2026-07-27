<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DoorprizeWinner extends Model
{
    protected $fillable = [
        'invitation_id',
        'invitation_guest_id',
        'prize',
        'session',
    ];

    public function invitation()
    {
        return $this->belongsTo(Invitation::class);
    }

    public function guest()
    {
        return $this->belongsTo(InvitationGuest::class, 'invitation_guest_id');
    }
}
