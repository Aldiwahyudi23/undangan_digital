<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Map extends Model
{
    protected $table = 'maps'; 
    protected $fillable = [
        'invitation_id',
        'name',
        'label',
        'url_frame',
        'address',
        'image_id',
        'notes',
        'metadata'
    ];

    protected $casts = [
        'metadata' => 'array'
    ];

    // Relasi
    public function invitation()
    {
        return $this->belongsTo(Invitation::class);
    }

    public function image()
    {
        return $this->belongsTo(Image::class, 'image_id');
    }

    public function events()
    {
        return $this->hasMany(Event::class);
    }
}