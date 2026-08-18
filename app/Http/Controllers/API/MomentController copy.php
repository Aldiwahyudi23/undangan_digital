<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invitation;
use App\Models\Moment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class MomentController extends Controller
{
    public function createPost(Request $request)
    {
        $rules = [
            'invitation_id' => 'required|exists:invitations,id',
            'guest_name' => 'required|string|max:255',
            'caption' => 'nullable|string',
        ];

        if ($request->hasFile('files')) {
            $files = $request->file('files');
            if (is_array($files)) {
                $rules['files'] = 'required|array|min:1|max:5';
                $rules['files.*'] = 'file|mimes:jpeg,png,jpg,gif,mp4,mov,avi|max:30720';
            } else {
                $rules['files'] = 'required|file|mimes:jpeg,png,jpg,gif,mp4,mov,avi|max:30720';
            }
        } else {
            $rules['files'] = 'required';
        }

        $request->validate($rules);

        if (!$this->isEventActive($request->invitation_id)) {
            return response()->json([
                'success' => false,
                'message' => 'Moment hanya bisa diupload saat hari acara atau setelahnya.',
            ], 403);
        }

        DB::beginTransaction();
        try {
            $moment = Moment::create([
                'invitation_id' => $request->invitation_id,
                'guest_name' => $request->guest_name,
                'type' => 'post',
                'caption' => $request->caption,
            ]);

            $files = $request->file('files');
            if (!is_array($files)) {
                $files = [$files];
            }

            foreach ($files as $file) {
                $moment->addMedia($file)->toMediaCollection('moments');
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Moment created successfully',
                'data' => [
                    'id' => $moment->id,
                    'guest_name' => $moment->guest_name,
                    'caption' => $moment->caption,
                    'media' => $this->getMediaUrls($moment),
                ],
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to create moment',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function createVoice(Request $request)
    {
        $request->validate([
            'invitation_id' => 'required|exists:invitations,id',
            'guest_name' => 'required|string|max:255',
            'caption' => 'nullable|string|max:1000',
            'file' => 'required|file|mimes:m4a,mp3,wav,ogg,oga,aac,amr,webm,opus|max:30720',
            'duration' => 'nullable|numeric|min:0',
        ]);

        if (!$this->isEventActive($request->invitation_id)) {
            return response()->json([
                'success' => false,
                'message' => 'Voice note hanya bisa diupload saat hari acara atau setelahnya.',
            ], 403);
        }

        DB::beginTransaction();
        try {
            $moment = Moment::create([
                'invitation_id' => $request->invitation_id,
                'guest_name' => $request->guest_name,
                'type' => 'voice',
                'caption' => $request->caption,
            ]);

            $media = $moment->addMedia($request->file('file'))
                ->toMediaCollection('voice');

            if ($request->filled('duration')) {
                $media->setCustomProperty('duration', $request->input('duration'));
                $media->save();
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Voice note created successfully',
                'data' => [
                    'id' => $moment->id,
                    'guest_name' => $moment->guest_name,
                    'caption' => $moment->caption,
                    'media' => $this->getMediaUrls($moment),
                ],
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to create voice note',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function getPosts(Request $request)
    {
        $request->validate([
            'invitation_id' => 'required|exists:invitations,id',
            'limit' => 'nullable|integer|min:1|max:50',
            'offset' => 'nullable|integer|min:0',
        ]);

        if (!$this->isEventActive($request->invitation_id)) {
            return response()->json([
                'success' => false,
                'message' => 'Moment hanya bisa dilihat saat hari acara atau setelahnya.',
            ], 403);
        }

        $limit = $request->get('limit', 10);
        $offset = $request->get('offset', 0);

        $posts = Moment::with('media')
            ->where('invitation_id', $request->invitation_id)
            ->where('type', 'post')
            ->orderBy('created_at', 'desc')
            ->skip($offset)
            ->take($limit)
            ->get()
            ->map(function ($moment) {
                return $this->formatMomentResponse($moment);
            });

        $total = Moment::where('invitation_id', $request->invitation_id)
            ->where('type', 'post')
            ->count();

        return response()->json([
            'success' => true,
            'data' => $posts,
            'pagination' => [
                'limit' => $limit,
                'offset' => $offset,
                'total' => $total,
                'has_more' => $total > ($offset + $limit),
            ],
        ]);
    }

    public function getVoices(Request $request)
    {
        $request->validate([
            'invitation_id' => 'required|exists:invitations,id',
            'limit' => 'nullable|integer|min:1|max:50',
            'offset' => 'nullable|integer|min:0',
        ]);

        if (!$this->isEventActive($request->invitation_id)) {
            return response()->json([
                'success' => false,
                'message' => 'Voice note hanya bisa dilihat saat hari acara atau setelahnya.',
            ], 403);
        }

        $limit = $request->get('limit', 10);
        $offset = $request->get('offset', 0);

        $voices = Moment::with('media')
            ->where('invitation_id', $request->invitation_id)
            ->where('type', 'voice')
            ->orderBy('created_at', 'desc')
            ->skip($offset)
            ->take($limit)
            ->get()
            ->map(function ($moment) {
                return $this->formatMomentResponse($moment);
            });

        $total = Moment::where('invitation_id', $request->invitation_id)
            ->where('type', 'voice')
            ->count();

        return response()->json([
            'success' => true,
            'data' => $voices,
            'pagination' => [
                'limit' => $limit,
                'offset' => $offset,
                'total' => $total,
                'has_more' => $total > ($offset + $limit),
            ],
        ]);
    }

    private function isEventActive(int $invitationId): bool
    {
        $earliestEventDate = Invitation::find($invitationId)
            ?->events()
            ->min('date');

        if (!$earliestEventDate) {
            return false;
        }

        return Carbon::parse($earliestEventDate)->startOfDay()->lte(Carbon::now()->startOfDay());
    }

    private function getMediaUrls($moment)
    {
        $collection = $moment->type === 'voice' ? 'voice' : 'moments';
        $mediaItems = $moment->getMedia($collection);

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

    private function formatMomentResponse($moment)
    {
        return [
            'id' => $moment->id,
            'type' => $moment->type,
            'guest_name' => $moment->guest_name,
            'caption' => $moment->caption,
            'created_at' => $moment->created_at,
            'created_at_human' => $moment->created_at->diffForHumans(),
            'media' => $this->getMediaUrls($moment),
        ];
    }
}
