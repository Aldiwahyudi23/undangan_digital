<?php

namespace App\Events;

use App\Models\LiveChat;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Queue\SerializesModels;

class LiveChatMessageSent implements ShouldBroadcastNow
{
    use SerializesModels;

    public $chat;

    /**
     * Create a new event instance.
     */
    public function __construct(LiveChat $chat)
    {
        // penting: load relasi biar tidak null di broadcast
        $this->chat = $chat->load('guest');
    }

    /**
     * 📡 Channel (public)
     */
    public function broadcastOn(): Channel
    {
        return new Channel('live-chat.' . $this->chat->invitation_id);
    }

    /**
     * 🏷 Nama event custom
     */
    public function broadcastAs(): string
    {
        return 'chat.sent';
    }

    /**
     * 📦 Payload ke frontend
     */
    public function broadcastWith(): array
    {
        return [
            'id' => $this->chat->id,
            'message' => $this->chat->message,
            'type' => $this->chat->type,
            'created_at' => $this->chat->created_at->toDateTimeString(),

            'guest' => [
                'id'   => $this->chat->guest?->id,
                'name' => $this->chat->guest?->name ?? 'Guest',
            ]
        ];
    }
}