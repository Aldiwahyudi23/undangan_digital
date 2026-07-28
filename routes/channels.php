<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('live-chat.{id}', function () {
    return true;
});

Broadcast::channel('invitation.{id}.checkins', function () {
    return true;
});