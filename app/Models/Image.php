<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Spatie\EloquentSortable\Sortable;
use Spatie\EloquentSortable\SortableTrait;

class Image extends Model implements Sortable
{

    use SortableTrait;

        // Tentukan kolom yang digunakan untuk sorting
    public $sortable = [
        'order_column_name' => 'order',
        'sort_when_creating' => true,
    ];
    protected $fillable = [
        'invitation_id',
        'title',
        'theme',
        'note',
        'path',
        'order',
        'metadata'
    ];

    protected $casts = [
        'metadata' => 'array',
        'order' => 'integer'
    ];

    // Relasi
    public function invitation()
    {
        return $this->belongsTo(Invitation::class);
    }

    public function placements()
    {
        return $this->hasMany(ImagePlacement::class);
    }

    public function couple()
    {
        return $this->hasOne(Couple::class);
    }

    public function map()
    {
        return $this->hasOne(Map::class);
    }

    public function story()
    {
        return $this->hasOne(Story::class);
    }

     public function buildSortQuery()
    {
        return static::query()->where('invitation_id', $this->invitation_id);
    }

        // Accessor untuk mendapatkan URL gambar
    protected function imageUrl()
    {
        return Attribute::make(
            get: fn () => $this->path ? asset('storage/' . $this->path) : null,
        );
    }
}