<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class FamilyMember extends Model
{
    use HasFactory;

    protected $fillable = [
        'invitation_id',
        'name',
        'role',
        'group',
        'is_core',
        'relation_label',
        'order',
    ];

    protected $casts = [
        'is_core' => 'boolean',
    ];

    /*
    |--------------------------------------------------------------------------
    | RELATIONSHIP
    |--------------------------------------------------------------------------
    */

    public function invitation()
    {
        return $this->belongsTo(Invitation::class);
    }

    /*
    |--------------------------------------------------------------------------
    | SCOPES (BIAR ENAK DIPAKE 🔥)
    |--------------------------------------------------------------------------
    */

    public function scopeBrideFamily($query)
    {
        return $query->where('group', 'bride_family');
    }

    public function scopeGroomFamily($query)
    {
        return $query->where('group', 'groom_family');
    }

    public function scopeBrideInvite($query)
    {
        return $query->where('group', 'bride_invite');
    }

    public function scopeGroomInvite($query)
    {
        return $query->where('group', 'groom_invite');
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('order');
    }

    /*
    |--------------------------------------------------------------------------
    | ACCESSOR (BIAR API RAPI 🔥)
    |--------------------------------------------------------------------------
    */

    public function getGroupLabelAttribute()
    {
        return match ($this->group) {
            'bride_family' => 'Keluarga Mempelai Wanita',
            'groom_family' => 'Keluarga Mempelai Pria',
            'bride_invite' => 'Turut Mengundang Wanita',
            'groom_invite' => 'Turut Mengundang Pria',
            default => $this->group,
        };
    }
}