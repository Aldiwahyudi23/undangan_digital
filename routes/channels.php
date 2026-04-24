<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('live-chat.{id}', function () {
    return true;
});