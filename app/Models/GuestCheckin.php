<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GuestCheckin extends Model
{
    protected $fillable = [
        'invitation_id',
        'invitation_guest_id',
        'invitation_barcode_id',
        'checkin_at',
        'checkout_at',
        'attended_people',
        'arrival_with',
    ];

    protected $casts = [
        'checkin_at' => 'datetime',
        'checkout_at' => 'datetime',
        'attended_people' => 'integer',
    ];

    public function invitation()
    {
        return $this->belongsTo(Invitation::class);
    }

    public function guest()
    {
        return $this->belongsTo(InvitationGuest::class, 'invitation_guest_id');
    }

    public function barcode()
    {
        return $this->belongsTo(InvitationBarcode::class, 'invitation_barcode_id');
    }

    public function getIsStillPresentAttribute(): bool
    {
        return $this->checkin_at !== null && $this->checkout_at === null;
    }
}
