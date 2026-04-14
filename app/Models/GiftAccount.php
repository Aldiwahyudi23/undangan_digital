<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GiftAccount extends Model
{
    protected $fillable = [
        'invitation_id',
        'couple_id',
        'bank_name',
        'account_number',
        'account_name'
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relasi ke Invitation
     */
    public function invitation(): BelongsTo
    {
        return $this->belongsTo(Invitation::class);
    }

    /**
     * Relasi ke Couple
     */
    public function couple(): BelongsTo
    {
        return $this->belongsTo(Couple::class);
    }

    /**
     * Accessor untuk format rekening yang lebih rapi
     */
    public function getFormattedAccountAttribute(): string
    {
        return "{$this->bank_name} - {$this->account_number} - {$this->account_name}";
    }
}