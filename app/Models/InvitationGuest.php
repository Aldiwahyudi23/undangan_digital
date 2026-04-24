<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;

class InvitationGuest extends Authenticatable
{

 use HasApiTokens;

 protected $table = 'invitation_guests';

    protected $fillable = [
        'invitation_id',
        'uuid',
        'token',
        'name',
        'role',
        'share_whatsapp',
        'note',
        'group_name',
        'location_tag',
        'is_opened',
        'opened_at',
        'max_device',
        'device_ids',
        'last_ip',
        'last_user_agent',
        'is_locked',
        'is_streaming',
        'is_watching_live',
        'last_seen_live',
        'permissions'
    ];

    protected $casts = [
        'device_ids' => 'array',
        'is_opened' => 'boolean',
        'is_locked' => 'boolean',
        'opened_at' => 'datetime',
        'last_seen_live' => 'datetime',
        'permissions' => 'array',

    ];

    // 🔗 RELATION
    public function invitation()
    {
        return $this->belongsTo(Invitation::class);
    }

    public function attendance()
    {
        return $this->hasOne(Attendance::class);
    }


    // 🔥 AUTO GENERATE UUID + TOKEN
    protected static function booted()
    {
        static::creating(function ($guest) {

            // UUID (unik global)
            if (!$guest->uuid) {
                $guest->uuid = (string) Str::uuid();
            }

            // Token (random string)
            if (!$guest->token) {
                $guest->token = Str::random(40);
            }
        });
    }
  // Generate device fingerprint
    public function getDeviceFingerprint($request)
    {
        $data = [
            $request->ip(),
            $request->userAgent(),
            $request->header('x-device-id'), // Frontend kirim device ID
            $request->header('x-platform'),   // Platform info
        ];
        
        return hash('sha256', implode('|', $data));
    }
    
    // Register device
    public function registerDevice($fingerprint)
    {
        if ($this->is_locked) {
            throw new \Exception('Akun undangan telah di-lock.', 403);
        }
        
        $currentDevices = $this->device_ids ?? [];
        
        if (!in_array($fingerprint, $currentDevices)) {
            if (count($currentDevices) >= $this->max_device) {
                throw new \Exception("Link undangan sudah digunakan di {$this->max_device} device berbeda.", 403);
            }
            
            $currentDevices[] = $fingerprint;
            $this->device_ids = $currentDevices;
            $this->save();
        }
        
        return true;
    }
    
    // Update tracking
    public function updateTracking($request, $fingerprint)
    {
        $this->last_ip = $request->ip();
        $this->last_user_agent = $request->userAgent();
        
        if (!$this->is_opened) {
            $this->is_opened = true;
            $this->opened_at = now();
        }
        
        $this->save();
    }

    public function posts()
    {
        return $this->hasMany(Post::class);
    }

    public function postLikes()
    {
        return $this->hasMany(PostLike::class);
    }

    public function liveChats()
    {
        return $this->hasMany(LiveChat::class);
    }

}