<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    protected $fillable = [
        'invitation_id',
        'invitation_guest_id',
        'status',
        'total_guests',
        'message',
        'is_private',
        'replied_at'
    ];

    protected $casts = [
        'total_guests' => 'integer',
        'is_private' => 'boolean',
        'replied_at' => 'datetime'
    ];

    // Relasi
    public function guest()
    {
        return $this->belongsTo(InvitationGuest::class, 'invitation_guest_id');
    }

    public function invitation()
    {
        return $this->belongsTo(Invitation::class);
    }
}