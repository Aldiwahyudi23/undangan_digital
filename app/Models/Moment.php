<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Moment extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $table = 'moments';

    protected $fillable = [
        'invitation_id',
        'guest_name',
        'type',
        'caption',
    ];

    protected $casts = [
        'type' => 'string',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('moments')
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/jpg', 'image/gif', 'video/mp4', 'video/quicktime', 'video/mov', 'video/avi']);

        $this->addMediaCollection('voice')
            ->acceptsMimeTypes(['audio/mpeg', 'audio/mp4', 'audio/m4a', 'audio/wav', 'audio/x-wav', 'audio/ogg', 'audio/aac', 'audio/webm', 'audio/amr', 'audio/opus', 'video/webm'])
            ->singleFile();
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->width(300)
            ->height(300)
            ->sharpen(10)
            ->nonQueued();
    }

    public function invitation(): BelongsTo
    {
        return $this->belongsTo(Invitation::class);
    }
}
