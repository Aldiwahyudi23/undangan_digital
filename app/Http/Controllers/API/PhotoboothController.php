<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invitation;
use App\Models\PhotoboothTemplate;

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
                'frame_image' => $template->frame_image_url,
                'thumbnail' => $template->thumbnail_url,
                'is_active' => $template->is_active,
                'slots' => $template->slots(),
            ]);

        return response()->json([
            'success' => true,
            'data' => $templates->values(),
        ]);
    }
}
