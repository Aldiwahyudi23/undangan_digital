<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\InvitationGuest;
use App\Models\Post;
use App\Models\PostLike;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PostController extends Controller
{
    // Create Moment (bisa multiple file, max 5)
    public function createMoment(Request $request)
    {
        // Ubah validasi: files bisa array atau single file
        $rules = [
            'invitation_id' => 'required|exists:invitations,id',
            'invitation_guest_id' => 'required|exists:invitation_guests,id',
            'caption' => 'nullable|string',
        ];

        // Cek apakah files dikirim sebagai array atau single
        if ($request->hasFile('files')) {
            $files = $request->file('files');
            if (is_array($files)) {
                $rules['files'] = 'required|array|min:1|max:5';
                $rules['files.*'] = 'file|mimes:jpeg,png,jpg,gif,mp4,mov,avi|max:15360';
            } else {
                $rules['files'] = 'required|file|mimes:jpeg,png,jpg,gif,mp4,mov,avi|max:15360';
            }
        } else {
            $rules['files'] = 'required';
        }

        $request->validate($rules);

             // =========================
            // 1. CEK STATUS KEHADIRAN
            // =========================
            $attendance = Attendance::where('invitation_guest_id', $request->invitation_guest_id)
                ->where('invitation_id', $request->invitation_id)
                ->first();

            $isAttending = $attendance && $attendance->status === 'attending';

        if ($isAttending === false) {
            return response()->json([
                'success' => false,
                'message' => 'Mohon Maaf, Halaman ini hanya bisa update Postingan khusus tamu yang Hadir.'
            ], 403);
        }


        DB::beginTransaction();
        try {
            
            $post = Post::create([
                'invitation_id' => $request->invitation_id,
                'invitation_guest_id' => $request->invitation_guest_id,
                'type' => 'moment',
                'caption' => $request->caption
            ]);
            
            // Handle files (baik array atau single)
            $files = $request->file('files');
            if (!is_array($files)) {
                $files = [$files];
            }
            
            foreach ($files as $index => $file) {
                $post->addMedia($file)->toMediaCollection('moments');
            }

            DB::commit();
            
            $mediaUrls = $this->getMediaUrls($post);
            
            return response()->json([
                'success' => true,
                'message' => 'Moment created successfully',
                'data' => [
                    'id' => $post->id,
                    'caption' => $post->caption,
                    'media' => $mediaUrls
                ]
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to create moment',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Create Status (single file)
    public function createStatus(Request $request)
    {
        $request->validate([
            'invitation_id' => 'required|exists:invitations,id',
            'invitation_guest_id' => 'required|exists:invitation_guests,id',
            'caption' => 'nullable|string',
            'file' => 'required|file|mimes:jpeg,png,jpg,gif,mp4,mov,avi|max:15360'
        ]);

                     // =========================
            // 1. CEK STATUS KEHADIRAN
            // =========================
            $attendance = Attendance::where('invitation_guest_id', $request->invitation_guest_id)
                ->where('invitation_id', $request->invitation_id)
                ->first();

            $isAttending = $attendance && $attendance->status === 'attending';

        if ($isAttending === false) {
            return response()->json([
                'success' => false,
                'message' => 'Mohon Maaf, Halaman ini hanya bisa update Postingan khusus tamu yang Hadir.'
            ], 403);
        }

        DB::beginTransaction();
        try {
            $post = Post::create([
                'invitation_id' => $request->invitation_id,
                'invitation_guest_id' => $request->invitation_guest_id,
                'type' => 'status',
                'caption' => $request->caption
            ]);

            // Upload ke media library (single file collection)
            $post->addMedia($request->file('file'))
                ->toMediaCollection('status');

            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Status created successfully',
                'data' => [
                    'id' => $post->id,
                    'caption' => $post->caption,
                    'media' => $this->getMediaUrls($post),
                    'expires_at' => now()->addHours(24)
                ]
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create status',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Get Moments (infinite scroll)
    public function getMoments(Request $request)
    {
        $request->validate([
            'invitation_id' => 'required|exists:invitations,id',
            'invitation_guest_id' => 'nullable|exists:invitation_guests,id',
            'limit' => 'nullable|integer|min:1|max:50',
            'offset' => 'nullable|integer|min:0'
        ]);

        $limit = $request->get('limit', 10);
        $offset = $request->get('offset', 0);
        $guestId = $request->get('invitation_guest_id');

        $moments = Post::with(['guest', 'likes'])
            ->where('invitation_id', $request->invitation_id)
            ->where('type', 'moment')
            ->orderBy('created_at', 'desc')
            ->skip($offset)
            ->take($limit)
            ->get()
            ->map(function ($post) use ($guestId) {
                return $this->formatPostResponse($post, $guestId);
            });

        $hasMore = Post::where('invitation_id', $request->invitation_id)
            ->where('type', 'moment')
            ->count() > ($offset + $limit);

        return response()->json([
            'success' => true,
            'data' => $moments,
            'pagination' => [
                'limit' => $limit,
                'offset' => $offset,
                'has_more' => $hasMore
            ]
        ]);
    }

    // Get Statuses (last 24 hours)
    public function getStatuses(Request $request)
    {
        $request->validate([
            'invitation_id' => 'required|exists:invitations,id',
            'invitation_guest_id' => 'nullable|exists:invitation_guests,id',
            'limit' => 'nullable|integer|min:1|max:50',
            'offset' => 'nullable|integer|min:0'
        ]);

        $limit = $request->get('limit', 10);
        $offset = $request->get('offset', 0);
        $guestId = $request->get('invitation_guest_id');

        $statuses = Post::with(['guest', 'likes'])
            ->where('invitation_id', $request->invitation_id)
            ->where('type', 'status')
            ->where('created_at', '>=', now()->subHours(24))
            ->orderBy('created_at', 'desc')
            ->skip($offset)
            ->take($limit)
            ->get()
            ->map(function ($post) use ($guestId) {
                return $this->formatPostResponse($post, $guestId);
            });

        $hasMore = Post::where('invitation_id', $request->invitation_id)
            ->where('type', 'status')
            ->where('created_at', '>=', now()->subHours(24))
            ->count() > ($offset + $limit);

        return response()->json([
            'success' => true,
            'data' => $statuses,
            'pagination' => [
                'limit' => $limit,
                'offset' => $offset,
                'has_more' => $hasMore
            ]
        ]);
    }

    // Like/Unlike post
    public function toggleLike(Request $request, $postId)
    {
        $request->validate([
            'invitation_guest_id' => 'required|exists:invitation_guests,id'
        ]);

        $post = Post::findOrFail($postId);
        $guestId = $request->invitation_guest_id;

        $existingLike = PostLike::where('post_id', $postId)
            ->where('invitation_guest_id', $guestId)
            ->first();

        if ($existingLike) {
            $existingLike->delete();
            $liked = false;
            $message = 'Post unliked';
        } else {
            PostLike::create([
                'post_id' => $postId,
                'invitation_guest_id' => $guestId
            ]);
            $liked = true;
            $message = 'Post liked';
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => [
                'liked' => $liked,
                'likes_count' => $post->likes()->count()
            ]
        ]);
    }

    // Get single post
    public function show(Request $request, $id)
    {
        $guestId = $request->get('invitation_guest_id');
        $post = Post::with(['guest', 'likes'])->findOrFail($id);
        
        return response()->json([
            'success' => true,
            'data' => $this->formatPostResponse($post, $guestId)
        ]);
    }

    // Helper: Get media URLs untuk version 11.x
    private function getMediaUrls($post)
    {
        $collection = $post->type === 'status' ? 'status' : 'moments';
        $mediaItems = $post->getMedia($collection);
        
        if ($mediaItems->isEmpty()) {
            return [];
        }
        
        return $mediaItems->map(function ($media) {
            return [
                'id' => $media->id,
                'type' => str_starts_with($media->mime_type, 'video') ? 'video' : 'image',
                'original_url' => $media->getUrl(),
                'thumbnail_url' => $media->getUrl('thumb'),
                'mime_type' => $media->mime_type,
                'size' => $media->size,
                'file_name' => $media->file_name
            ];
        })->toArray();
    }

    // Helper format response
    private function formatPostResponse($post, $guestId = null)
    {
        return [
            'id' => $post->id,
            'type' => $post->type,
            'caption' => $post->caption,
            'created_at' => $post->created_at,
            'created_at_human' => $post->created_at->diffForHumans(),
            'is_expired' => $post->type === 'status' && $post->created_at->diffInHours(now()) >= 24,
            'guest' => [
                'id' => $post->guest->id,
                'name' => $post->guest->name,
                'avatar' => $post->guest->avatar ?? null
            ],
            'media' => $this->getMediaUrls($post),
            'likes_count' => $post->likes()->count(),
            'is_liked_by_me' => $guestId ? $post->isLikedBy($guestId) : false
        ];
    }
}