<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invitation;
use App\Models\InvitationGuest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InvitationController extends Controller
{
    /**
     * Get complete invitation data by UUID and token
     * GET /api/invitation/{uuid}?token={token}
     */
    public function show($uuid, Request $request)
    {
        // $token = $request->query('token');
        
        // if (!$token) {
        //     return response()->json([
        //         'success' => false,
        //         'message' => 'Token required'
        //     ], 400);
        // }
        
        // Cari guest berdasarkan uuid dan token
        $guest = InvitationGuest::where('uuid', $uuid)
            // ->where('token', $token)
            ->first();
        
        if (!$guest) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid invitation link'
            ], 404);
        }
        
        // Ambil data invitation
        $invitation = $guest->invitation;
        
        if (!$invitation || !$invitation->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Invitation not active'
            ], 403);
        }
        
        // Kirim data lengkap
        return response()->json([
            'success' => true,
            'data' => [
                'guest' => $this->formatGuest($guest),
                'invitation' => $this->formatInvitation($invitation),
                'cover' => $this->getCoverImages($invitation),
                'slides' => $this->getSlideImages($invitation),
                'gallery' => $this->getGalleryImages($invitation),
                'couples' => $this->getCouples($invitation),
                'stories' => $this->getStories($invitation),
                'events' => $this->getEvents($invitation),
            ]
        ]);
    }
    
    /**
     * Get invitation by ID (for testing)
     */
    public function getById($id)
    {
        $invitation = Invitation::with([
            'images.placements',
            'couples.image',
            'stories.image',
            'events.map.image',
            'maps.image'
        ])->find($id);
        
        if (!$invitation) {
            return response()->json([
                'success' => false,
                'message' => 'Invitation not found'
            ], 404);
        }
        
        return response()->json([
            'success' => true,
            'data' => [
                'invitation' => $this->formatInvitation($invitation),
                'cover' => $this->getCoverImages($invitation),
                'slides' => $this->getSlideImages($invitation),
                'gallery' => $this->getGalleryImages($invitation),
                'couples' => $this->getCouples($invitation),
                'stories' => $this->getStories($invitation),
                'events' => $this->getEvents($invitation),
            ]
        ]);
    }
    
    // ==================== PRIVATE FORMATTERS ====================
    
    /**
     * Format guest data
     */
    private function formatGuest($guest)
    {
        return [
            'id' => $guest->id,
            'name' => $guest->name,
            'uuid' => $guest->uuid,
            'group_name' => $guest->group_name,
            'location_tag' => $guest->location_tag,
            'note' => $guest->note,
        ];
    }
    
    /**
     * Format invitation basic data
     */
    private function formatInvitation($invitation)
    {
        return [
            'id' => $invitation->id,
            'title' => $invitation->title,
            'theme' => $invitation->theme,
            'settings' => $invitation->settings,
            'expired_at' => $invitation->expired_at,
        ];
    }
    
    /**
     * Get cover images (placement = 'cover')
     */
    private function getCoverImages($invitation)
    {
        return $invitation->images
            ->filter(function ($image) {
                return $image->placements->contains('placement', 'cover');
            })
            ->map(function ($image) {
                return [
                    'id' => $image->id,
                    'title' => $image->title,
                    'path' => asset('storage/' . $image->path),
                    'theme' => $image->theme,
                    'note' => $image->note,
                    'metadata' => $image->metadata,
                ];
            })
            ->values();
    }
    
    /**
     * Get slide images for hero slider (placement = 'hero_slide')
     */
    private function getSlideImages($invitation)
    {
        return $invitation->images
            ->filter(function ($image) {
                return $image->placements->contains('placement', 'hero_slide');
            })
            ->sortBy('order')
            ->map(function ($image) {
                return [
                    'id' => $image->id,
                    'title' => $image->title,
                    'path' => asset('storage/' . $image->path),
                    'theme' => $image->theme,
                    'note' => $image->note,
                    'order' => $image->order,
                    'metadata' => $image->metadata,
                ];
            })
            ->values();
    }
    
    /**
     * Get gallery images (placement = 'gallery')
     */
    private function getGalleryImages($invitation)
    {
        return $invitation->images
            ->filter(function ($image) {
                return $image->placements->contains('placement', 'gallery');
            })
            ->sortBy('order')
            ->map(function ($image) {
                return [
                    'id' => $image->id,
                    'title' => $image->title,
                    'path' => asset('storage/' . $image->path),
                    'note' => $image->note,
                    'order' => $image->order,
                ];
            })
            ->values();
    }
    
    /**
     * Get couples data (pengantin)
     */
    private function getCouples($invitation)
    {
        $couples = $invitation->couples;
        
        $male = $couples->firstWhere('gender', 'male');
        $female = $couples->firstWhere('gender', 'female');
        
        return [
            'male' => $male ? [
                'id' => $male->id,
                'full_name' => $male->full_name,
                'nickname' => $male->nickname,
                'father_name' => $male->father_name,
                'mother_name' => $male->mother_name,
                'birth_order' => $male->birth_order,
                'photo' => $male->image ? asset('storage/' . $male->image->path) : null,
                'metadata' => $male->metadata,
            ] : null,
            'female' => $female ? [
                'id' => $female->id,
                'full_name' => $female->full_name,
                'nickname' => $female->nickname,
                'father_name' => $female->father_name,
                'mother_name' => $female->mother_name,
                'birth_order' => $female->birth_order,
                'photo' => $female->image ? asset('storage/' . $female->image->path) : null,
                'metadata' => $female->metadata,
            ] : null,
        ];
    }
    
    /**
     * Get stories data
     */
    private function getStories($invitation)
    {
        return $invitation->stories
            ->sortBy('order')
            ->map(function ($story) {
                return [
                    'id' => $story->id,
                    'title' => $story->title,
                    'description' => $story->description,
                    'date_event' => $story->date_event,
                    'is_featured' => $story->is_featured,
                    'image' => $story->image ? asset('storage/' . $story->image->path) : null,
                ];
            })
            ->values();
    }
    
    /**
     * Get events data (akad, resepsi, etc)
     */
    private function getEvents($invitation)
    {
        return $invitation->events
            ->sortBy('date')
            ->map(function ($event) {
                return [
                    'id' => $event->id,
                    'type' => $event->type,
                    'title' => $event->title,
                    'date' => $event->date,
                    'start_time' => $event->start_time,
                    'end_time' => $event->end_time,
                    'notes' => $event->notes,
                    'metadata' => $event->metadata,
                    'location' => $event->map ? [
                        'maps_id' => $event->map->id,
                        'name' => $event->map->name,
                        'label' => $event->map->label,
                        'notes' => $event->map->notes,
                        'address' => $event->map->address,
                        'url_frame' => $event->map->url_frame,
                        'photo' => $event->map->image ? asset('storage/' . $event->map->image->path) : null,
                    ] : null,
                ];
            })
            ->values();
    }

}