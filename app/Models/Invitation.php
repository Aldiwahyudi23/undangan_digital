<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Invitation extends Model
{
    protected $fillable = [
        'user_id',
        'title',
        'theme',
        'settings',
        'is_active',
    ];

    protected $casts = [
        'settings' => 'array',
        'is_active' => 'boolean',
    ];

    // 🔗 RELATION
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function guests()
    {
        return $this->hasMany(InvitationGuest::class);
    }

        public function images()
    {
        return $this->hasMany(Image::class);
    }

    public function couples()
    {
        return $this->hasMany(Couple::class);
    }

    public function maps()
    {
        return $this->hasMany(Map::class);
    }

    public function events()
    {
        return $this->hasMany(Event::class);
    }

    public function stories()
    {
        return $this->hasMany(Story::class);
    }

    public function GiftAccount()
    {
        return $this->hasMany(GiftAccount::class);
    }

    public function familyMembers()
    {
        return $this->hasMany(FamilyMember::class)->orderBy('order');
    }

    public function posts()
    {
        return $this->hasMany(Post::class);
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }
}