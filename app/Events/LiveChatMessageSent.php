<?php

namespace App\Events;

use App\Models\LiveChat;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Queue\SerializesModels;

class LiveChatMessageSent implements ShouldBroadcast
{
    use SerializesModels;

    public $chat;

    public function __construct(LiveChat $chat)
    {
        $this->chat = $chat;
    }

    /**
     * 📡 Channel berdasarkan invitation (room live)
     */
    public function broadcastOn()
    {
        return new Channel('live-chat.' . $this->chat->invitation_id);
    }

    /**
     * 🏷 Nama event di frontend
     */
    public function broadcastAs()
    {
        return 'chat.sent';
    }

    /**
     * 📦 Data yang dikirim ke frontend
     */
    public function broadcastWith()
    {
        return [
            'id' => $this->chat->id,
            'message' => $this->chat->message,
            'type' => $this->chat->type,
            'created_at' => $this->chat->created_at,
            'guest' => [
                'id' => $this->chat->guest?->id,
                'name' => $this->chat->guest?->name ?? 'Guest'
            ]
        ];
    }
}