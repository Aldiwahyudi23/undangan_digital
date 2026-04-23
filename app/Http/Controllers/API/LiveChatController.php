<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\LiveChat;
use App\Events\LiveChatMessageSent;

class LiveChatController extends Controller
{
    /**
     * 📥 GET: ambil chat awal
     */
    public function index(Request $request)
    {
        $request->validate([
            'invitation_id' => 'required|exists:invitations,id'
        ]);

        $chats = LiveChat::with('guest')
            ->where('invitation_id', $request->invitation_id)
            ->where('is_deleted', false)
            ->latest()
            ->take(50)
            ->get()
            ->reverse()
            ->values();

        return response()->json([
            'success' => true,
            'data' => $chats
        ]);
    }

    /**
     * 📤 POST: kirim chat
     */
    public function store(Request $request)
    {
        $request->validate([
            'invitation_id' => 'required|exists:invitations,id',
            'message' => 'required|string|max:1000',
            'invitation_guest_id' => 'nullable|exists:invitation_guests,id'
        ]);

        $chat = LiveChat::create([
            'invitation_id' => $request->invitation_id,
            'invitation_guest_id' => $request->invitation_guest_id,
            'message' => $request->message,
            'type' => 'text'
        ]);

        // 🔥 load relasi guest biar langsung ada nama
        $chat->load('guest');

        // 🔥 broadcast realtime
        broadcast(new LiveChatMessageSent($chat))->toOthers();

        return response()->json([
            'success' => true,
            'data' => $chat
        ]);
    }

    /**
     * 🗑 DELETE: hapus chat (soft)
     */
    public function destroy($id)
    {
        $chat = LiveChat::findOrFail($id);
        $chat->update(['is_deleted' => true]);

        return response()->json([
            'success' => true
        ]);
    }
}