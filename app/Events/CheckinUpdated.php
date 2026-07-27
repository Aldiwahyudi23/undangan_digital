<?php

namespace App\Events;

use App\Models\InvitationGuest;
use App\Models\GuestCheckin;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CheckinUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public InvitationGuest $guest,
        public GuestCheckin $checkin,
    ) {}

    public function broadcastOn(): array
    {
        return [new Channel('invitation.' . $this->guest->invitation_id . '.checkins')];
    }

    public function broadcastAs(): string
    {
        return 'checkin.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'guest_name' => $this->guest->name,
            'invitation_type' => $this->guest->invitation_type,
            'arrival_with' => $this->checkin->arrival_with,
            'attended_people' => $this->checkin->attended_people,
            'checkin_at' => $this->checkin->checkin_at?->toISOString(),
            'checkout_at' => $this->checkin->checkout_at?->toISOString(),
            'is_checkout' => $this->checkin->checkout_at !== null,
        ];
    }
}
