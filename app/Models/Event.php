<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    protected $fillable = [
        'invitation_id',
        'title',
        'date',
        'start_time',
        'end_time',
        'map_id',
        'notes',
        'metadata'
    ];

    protected $casts = [
        'date' => 'date',
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'metadata' => 'array'
    ];

    // Relasi
    public function invitation()
    {
        return $this->belongsTo(Invitation::class);
    }

    public function map()
    {
        return $this->belongsTo(Map::class);
    }
}