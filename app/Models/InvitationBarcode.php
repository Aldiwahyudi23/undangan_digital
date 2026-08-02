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

    // 🔢 KODE BARCODE: "BC" + kode undangan (id, di-pad min 2 digit) + urutan 4 digit per undangan.
    // Contoh: undangan id 1 urutan ke-1 → BC010001, undangan id 2 → BC020001.
    public static function codePrefix(int $invitationId): string
    {
        return 'BC'.str_pad((string) $invitationId, 2, '0', STR_PAD_LEFT);
    }

    public static function formatCode(int $invitationId, int $sequence): string
    {
        return static::codePrefix($invitationId).str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
    }

    public static function nextSequence(int $invitationId): int
    {
        $prefix = static::codePrefix($invitationId);

        $lastCode = static::where('invitation_id', $invitationId)
            ->where('barcode_code', 'like', $prefix.'%')
            ->max('barcode_code');

        if ($lastCode && preg_match('/^'.preg_quote($prefix, '/').'(\d{4})$/', $lastCode, $matches)) {
            return (int) $matches[1] + 1;
        }

        return 1;
    }

    public static function nextCode(int $invitationId): string
    {
        $sequence = static::nextSequence($invitationId);

        do {
            $code = static::formatCode($invitationId, $sequence);
            $sequence++;
        } while (static::where('barcode_code', $code)->exists());

        return $code;
    }
}
