<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GuestCheckin;
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
                $rules['files.*'] = 'file|mimes:jpeg,png,jpg,gif,mp4,mov,avi|max:30720'; // max 30MB per file
            } else {
                $rules['files'] = 'required|file|mimes:jpeg,png,jpg,gif,mp4,mov,avi|max:30720'; // max 30MB per file
            }
        } else {
            $rules['files'] = 'required';
        }

        $request->validate($rules);

        // =========================
        // 1. CEK STATUS CHECK-IN
        // =========================
        if (!$this->isGuestCheckedIn($request->invitation_guest_id)) {
            return response()->json([
                'success' => false,
                'message' => 'Mohon Maaf, Halaman ini hanya bisa diakses oleh tamu yang sudah check-in.'
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
            'file' => 'required|file|mimes:jpeg,png,jpg,gif,mp4,mov,avi|max:30720'
        ]);

        // =========================
        // 1. CEK STATUS CHECK-IN
        // =========================
        if (!$this->isGuestCheckedIn($request->invitation_guest_id)) {
            return response()->json([
                'success' => false,
                'message' => 'Mohon Maaf, Halaman ini hanya bisa diakses oleh tamu yang sudah check-in.'
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

    // Create Voice Note (single audio file)
    // public function createVoice(Request $request)
    // {
    //     $request->validate([
    //         'invitation_id' => 'required|exists:invitations,id',
    //         'invitation_guest_id' => 'required|exists:invitation_guests,id',
    //         'caption' => 'nullable|string|max:1000',
    //         'file' => 'required|file|mimes:m4a,mp3,wav,ogg,oga,aac,amr,webm,opus|max:30720',
    //         'duration' => 'nullable|numeric|min:0'
    //     ]);

    //     // =========================
    //     // 1. CEK STATUS CHECK-IN
    //     // =========================
    //     if (!$this->isGuestCheckedIn($request->invitation_guest_id)) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Mohon Maaf, Halaman ini hanya bisa diakses oleh tamu yang sudah check-in.'
    //         ], 403);
    //     }

    //     DB::beginTransaction();
    //     try {
    //         $post = Post::create([
    //             'invitation_id' => $request->invitation_id,
    //             'invitation_guest_id' => $request->invitation_guest_id,
    //             'type' => 'voice',
    //             'caption' => $request->caption
    //         ]);

    //         $media = $post->addMedia($request->file('file'))
    //             ->toMediaCollection('voice');

    //         if ($request->filled('duration')) {
    //             $media->setCustomProperty('duration', $request->input('duration'));
    //             $media->save();
    //         }

    //         DB::commit();

    //         $mediaUrls = $this->getMediaUrls($post);

    //         return response()->json([
    //             'success' => true,
    //             'message' => 'Voice note created successfully',
    //             'data' => [
    //                 'id' => $post->id,
    //                 'caption' => $post->caption,
    //                 'media' => $mediaUrls
    //             ]
    //         ], 201);

    //     } catch (\Exception $e) {
    //         DB::rollBack();
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Failed to create voice note',
    //             'error' => $e->getMessage()
    //         ], 500);
    //     }
    // }

    public function createVoice(Request $request)
{
    Log::info('========== CREATE VOICE START ==========');

    Log::info('Request Data', [
        'invitation_id' => $request->invitation_id,
        'invitation_guest_id' => $request->invitation_guest_id,
        'caption' => $request->caption,
        'duration' => $request->duration,
        'has_file' => $request->hasFile('file'),
    ]);

    if ($request->hasFile('file')) {
        $file = $request->file('file');

        Log::info('Uploaded File', [
            'original_name' => $file->getClientOriginalName(),
            'mime' => $file->getMimeType(),
            'extension' => $file->getClientOriginalExtension(),
            'size' => $file->getSize(),
            'is_valid' => $file->isValid(),
            'error' => $file->getError(),
        ]);
    } else {
        Log::warning('No file uploaded.');
    }

    try {

        $request->validate([
            'invitation_id' => 'required|exists:invitations,id',
            'invitation_guest_id' => 'required|exists:invitation_guests,id',
            'caption' => 'nullable|string|max:1000',
            'file' => 'required|file|mimes:m4a,mp3,wav,ogg,oga,aac,amr,webm,opus|max:30720',
            'duration' => 'nullable|numeric|min:0'
        ]);

        Log::info('Validation passed.');

    } catch (\Illuminate\Validation\ValidationException $e) {

        Log::error('Validation failed', [
            'errors' => $e->errors()
        ]);

        throw $e;
    }

    // =========================
    // 1. CEK STATUS CHECK-IN
    // =========================
    if (!$this->isGuestCheckedIn($request->invitation_guest_id)) {

        Log::warning('Guest has not checked in.', [
            'guest_id' => $request->invitation_guest_id
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Mohon Maaf, Halaman ini hanya bisa diakses oleh tamu yang sudah check-in.'
        ], 403);
    }

    DB::beginTransaction();

    try {

        Log::info('Creating Post...');

        $post = Post::create([
            'invitation_id' => $request->invitation_id,
            'invitation_guest_id' => $request->invitation_guest_id,
            'type' => 'voice',
            'caption' => $request->caption
        ]);

        Log::info('Post Created', [
            'post_id' => $post->id
        ]);

        Log::info('Uploading media to Spatie...');

        $media = $post
            ->addMedia($request->file('file'))
            ->toMediaCollection('voice');

        Log::info('Media Uploaded', [
            'media_id' => $media->id,
            'file_name' => $media->file_name,
            'disk' => $media->disk,
            'path' => $media->getPath(),
        ]);

        if ($request->filled('duration')) {

            $media->setCustomProperty('duration', $request->duration);
            $media->save();

            Log::info('Duration saved', [
                'duration' => $request->duration
            ]);
        }

        DB::commit();

        Log::info('Database Commit Success');

        $mediaUrls = $this->getMediaUrls($post);

        Log::info('========== CREATE VOICE SUCCESS ==========');

        return response()->json([
            'success' => true,
            'message' => 'Voice note created successfully',
            'data' => [
                'id' => $post->id,
                'caption' => $post->caption,
                'media' => $mediaUrls
            ]
        ], 201);

    } catch (\Throwable $e) {

        DB::rollBack();

        Log::error('========== CREATE VOICE FAILED ==========', [
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Failed to create voice note',
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

    // Get Voice Notes (infinite scroll)
    public function getVoices(Request $request)
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

        $voices = Post::with(['guest', 'likes'])
            ->where('invitation_id', $request->invitation_id)
            ->where('type', 'voice')
            ->orderBy('created_at', 'desc')
            ->skip($offset)
            ->take($limit)
            ->get()
            ->map(function ($post) use ($guestId) {
                return $this->formatPostResponse($post, $guestId);
            });

        $hasMore = Post::where('invitation_id', $request->invitation_id)
            ->where('type', 'voice')
            ->count() > ($offset + $limit);

        return response()->json([
            'success' => true,
            'data' => $voices,
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
        $collection = match ($post->type) {
            'status' => 'status',
            'voice' => 'voice',
            default => 'moments',
        };
        $mediaItems = $post->getMedia($collection);
        
        if ($mediaItems->isEmpty()) {
            return [];
        }
        
        return $mediaItems->map(function ($media) {
            return [
                'id' => $media->id,
                'type' => match (true) {
                    str_starts_with($media->mime_type, 'video') => 'video',
                    str_starts_with($media->mime_type, 'audio') => 'audio',
                    default => 'image',
                },
                'original_url' => $media->getUrl(),
                'thumbnail_url' => $media->getUrl('thumb'),
                'mime_type' => $media->mime_type,
                'size' => $media->size,
                'file_name' => $media->file_name,
                'duration' => $media->getCustomProperty('duration'),
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

    // Helper: cek tamu sudah check-in dan belum checkout
    private function isGuestCheckedIn(int $invitationGuestId): bool
    {
        return GuestCheckin::where('invitation_guest_id', $invitationGuestId)
            ->whereNull('checkout_at')
            ->exists();
    }
}