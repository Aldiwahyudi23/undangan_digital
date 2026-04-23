Broadcast::channel('live-chat.{invitationId}', function ($user = null, $invitationId) {
    return true; // semua guest boleh join
});