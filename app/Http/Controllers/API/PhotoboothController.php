<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invitation;
use App\Models\PhotoboothTemplate;
use Illuminate\Support\Facades\Storage;

class PhotoboothController extends Controller
{
    public function templates(string $invitation)
    {
        $invitation = Invitation::find($invitation);

        if (!$invitation) {
            return response()->json([
                'success' => false,
                'message' => 'Invitation tidak ditemukan.',
            ], 404);
        }

        $templates = $invitation->photoboothTemplates()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->map(fn (PhotoboothTemplate $template) => [
                'id' => $template->id,
                'uuid' => $template->uuid,
                'title' => $template->title,
                'slug' => $template->slug,
                // 'frame_image' => $template->frame_image_url,
                'frame_image' => url('/api/photobooth/frame/' . $template->uuid),
                'thumbnail' => $template->thumbnail_url,
                'is_active' => $template->is_active,
                'slots' => $template->slots(),
            ]);

        return response()->json([
            'success' => true,
            'data' => $templates->values(),
        ]);
    }
    


    public function frame(string $uuid)
    {
        $template = PhotoboothTemplate::where('uuid', $uuid)->firstOrFail();
    
        if (!$template->frame_image) {
            abort(404);
        }
    
        $path = Storage::disk('public')->path($template->frame_image);
    
        if (!file_exists($path)) {
            abort(404);
        }
    
        return response()->file($path, [
            'Content-Type' => mime_content_type($path),
            'Access-Control-Allow-Origin' => '*',
            'Access-Control-Allow-Methods' => 'GET, OPTIONS',
            'Access-Control-Allow-Headers' => '*',
            'Cache-Control' => 'public, max-age=31536000',
        ]);
    }
}
