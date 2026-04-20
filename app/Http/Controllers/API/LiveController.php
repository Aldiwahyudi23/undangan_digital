<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\LiveViewerService;
use Illuminate\Http\Request;

class LiveController extends Controller
{
    public function joinViewer(Request $request)
    {
        LiveViewerService::join(
            $request->invitation_id,
            $request->guest_uuid
        );

        return response()->json([
            'success' => true
        ]);
    }

    public function leaveViewer(Request $request)
    {
        LiveViewerService::leave(
            $request->invitation_id,
            $request->guest_uuid
        );

        return response()->json([
            'success' => true
        ]);
    }

    public function getViewerCount($invitationId)
    {
        return response()->json([
            'count' => LiveViewerService::count($invitationId)
        ]);
    }
}
