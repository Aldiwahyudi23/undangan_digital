<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class InvitationBarcode extends Model
{
    protected $fillable = [
        'invitation_id',
        'uuid',
        'barcode_code',
        'barcode_token',
        'invitation_guest_id',
        'barcode_pdf_batch_id',
        'generated_at',
        'is_used',
    ];

    protected $casts = [
        'generated_at' => 'datetime',
        'is_used' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (InvitationBarcode $barcode) {
            if (!$barcode->uuid) {
                $barcode->uuid = (string) Str::uuid();
            }
            if (!$barcode->barcode_token) {
                $barcode->barcode_token = strtoupper(Str::random(16));
            }
            if (!$barcode->generated_at) {
                $barcode->generated_at = now();
            }
        });
    }

    public function invitation()
    {
        return $this->belongsTo(Invitation::class);
    }

    public function guest()
    {
        return $this->belongsTo(InvitationGuest::class, 'invitation_guest_id');
    }

    public function batch()
    {
        return $this->belongsTo(BarcodePdfBatch::class, 'barcode_pdf_batch_id');
    }

    public function checkins()
    {
        return $this->hasMany(GuestCheckin::class, 'invitation_barcode_id');
    }
}
