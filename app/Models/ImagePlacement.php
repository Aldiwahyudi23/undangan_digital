<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ImagePlacement extends Model
{
    protected $fillable = [
        'image_id',
        'placement',
        'metadata'
    ];

    protected $casts = [
        'metadata' => 'array'
    ];

    // Relasi
    public function image()
    {
        return $this->belongsTo(Image::class);
    }
}