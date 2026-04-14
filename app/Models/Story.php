<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Story extends Model
{
    protected $fillable = [
        'invitation_id',
        'title',
        'description',
        'image_id',
        'date_event',
        'order',
        'is_featured'
    ];

    protected $casts = [
        'date_event' => 'date',
        'order' => 'integer',
        'is_featured' => 'boolean'
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