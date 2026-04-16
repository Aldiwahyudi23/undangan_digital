<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Post extends Model implements HasMedia
{
    use InteractsWithMedia;
    
    protected $table = 'posts';
    
    protected $fillable = [
        'invitation_id', 
        'invitation_guest_id', 
        'type', 
        'caption'
    ];
    
    protected $casts = [
        'type' => 'string',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
    
    // Register media collections untuk v11.x
    public function registerMediaCollections(): void
    {
        // Collection untuk moments (bisa multiple files)
        $this->addMediaCollection('moments')
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/jpg', 'image/gif', 'video/mp4', 'video/quicktime', 'video/mov', 'video/avi']);
        
        // Collection untuk status (single file only)
        $this->addMediaCollection('status')
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/jpg', 'image/gif', 'video/mp4', 'video/quicktime', 'video/mov', 'video/avi'])
            ->singleFile();
    }
    
    // Register media conversions (optional, untuk kompresi)
    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->width(300)
            ->height(300)
            ->sharpen(10)
            ->nonQueued();
    }
    
    // Relationships
    public function invitation(): BelongsTo
    {
        return $this->belongsTo(Invitation::class);
    }
    
    public function guest(): BelongsTo
    {
        return $this->belongsTo(InvitationGuest::class, 'invitation_guest_id');
    }
    
    public function likes(): HasMany
    {
        return $this->hasMany(PostLike::class);
    }
    
    // Helper methods
    public function getLikesCountAttribute(): int
    {
        return $this->likes()->count();
    }
    
    public function isLikedBy(int $guestId): bool
    {
        return $this->likes()->where('invitation_guest_id', $guestId)->exists();
    }
}