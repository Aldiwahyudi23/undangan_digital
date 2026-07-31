<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class BarcodePdfBatch extends Model
{
    protected $fillable = [
        'invitation_id',
        'uuid',
        'title',
        'quantity',
        'pdf_path',
        'pdf_settings',
    ];

    protected $casts = [
        'pdf_settings' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function (BarcodePdfBatch $batch) {
            if (! $batch->uuid) {
                $batch->uuid = (string) Str::uuid();
            }
        });
    }

    public function invitation()
    {
        return $this->belongsTo(Invitation::class);
    }

    public function barcodes()
    {
        return $this->hasMany(InvitationBarcode::class, 'barcode_pdf_batch_id');
    }
}
