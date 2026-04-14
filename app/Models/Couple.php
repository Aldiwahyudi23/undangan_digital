<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Couple extends Model
{
    protected $fillable = [
        'invitation_id',
        'gender',
        'full_name',
        'nickname',
        'father_name',
        'mother_name',
        'image_id',
        'birth_order',
        'metadata'
    ];

    protected $casts = [
        'metadata' => 'array',
        'birth_order' => 'integer'
    ];

    // Relasi
    public function invitation()
    {
        return $this->belongsTo(Invitation::class);
    }

    public function image()
    {
        return $this->belongsTo(Image::class);
    }
}