<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PostLike extends Model
{
    protected $fillable = ['post_id', 'invitation_guest_id'];
    
    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }
    
    public function guest(): BelongsTo
    {
        return $this->belongsTo(InvitationGuest::class);
    }
}