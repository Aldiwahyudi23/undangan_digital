<?php

namespace App\Services;

use App\Models\Invitation;
use App\Models\InvitationGuest;
use Carbon\Carbon;

class WhatsAppInvitationService
{
    public function generateMessage(InvitationGuest $guest): string
    {
        $invitation = Invitation::with([
            'couples',
            'events.map',
        ])->findOrFail($guest->invitation_id);

        $male = $invitation->couples
            ->firstWhere('gender', 'male');

        $female = $invitation->couples
            ->firstWhere('gender', 'female');

        // Tetap dipersiapkan untuk kebutuhan di masa mendatang
        $event = $invitation->events
            ->sortBy('date')
            ->first();

        $date = '-';
        if ($event?->date) {
            $date = Carbon::parse($event->date)
                ->translatedFormat('d F Y');
        }

        $time = $event?->start_time
            ? $event->start_time . ' WIB'
            : '-';

        $locations = $invitation->events
            ->filter(fn ($event) => !empty($event->map?->address))
            ->map(function ($event) {
                return [
                    'title'   => $event->title,
                    'address' => $event->map->address,
                ];
            })
            ->unique('address')
            ->values();

        // Disimpan untuk kebutuhan jika nanti ingin ditampilkan
        $locationText = '';

        foreach ($locations as $location) {
            $locationText .=
                "📍 {$location['title']}\n" .
                "{$location['address']}\n\n";
        }

        $link = 'https://fixnikah.miraaldi.my.id/undangan/'
            . $guest->uuid;
            // . '?token='
            // . $guest->token;

        return
            "*Assalamu'alaikum Wr. Wb.* 🙏\n\n" .

            "Tanpa mengurangi rasa hormat, kami mengundang Bapak/Ibu/Saudara/i:\n\n" .

            "*{$guest->name}*\n\n" .

            "untuk hadir dan memberikan doa restu pada hari bahagia pernikahan kami.\n\n" .

            "💍 *{$female?->nickname} & {$male?->nickname}*\n\n" .

            "Silakan buka undangan digital kami melalui tautan berikut:\n\n" .
            "{$link}\n\n" .

            "📺 *Belum bisa hadir?*\n" .
            "Anda tetap dapat mengikuti jalannya acara melalui fitur *Live Streaming* yang tersedia di dalam undangan. Cukup buka kembali link undangan, lalu pilih *Profil FIXNIKAH*.\n\n" .

            "📸 *Bisa hadir?*\n" .
            "Yuk abadikan momen kebersamaan kita. Setelah acara, buka kembali link undangan, pilih *Profil Bebas* atau *Profil FIXNIKAH*, kemudian masuk ke menu *News & Hots* untuk membagikan foto maupun video terbaik Anda.\n\n" .

            "Kehadiran dan doa restu Anda merupakan kebahagiaan yang sangat berarti bagi kami.\n\n" .

            "Terima kasih. 🤍\n\n" .

            "*Wassalamu'alaikum Wr. Wb.*";
    }

    public function generateUrl(InvitationGuest $guest): string
    {
        $phone = preg_replace('/[^0-9]/', '', $guest->share_whatsapp);

        if (str_starts_with($phone, '0')) {
            $phone = '62' . substr($phone, 1);
        }

        return 'https://wa.me/' . $phone .
            '?text=' . rawurlencode(
                $this->generateMessage($guest)
            );
    }

    public function markAsSent(InvitationGuest $guest): void
    {
        $permissions = $guest->permissions ?? [];

        $wa = $permissions['whatsapp'] ?? [];

        $wa['is_sent'] = true;
        $wa['sent_count'] = ($wa['sent_count'] ?? 0) + 1;
        $wa['last_sent_at'] = now()->format('Y-m-d H:i:s');
        $wa['last_sent_by'] = auth()->user()->name ?? 'System';

        $history = $wa['history'] ?? [];

        $history[] = [
            'sent_at' => now()->format('Y-m-d H:i:s'),
            'sent_by' => auth()->user()->name ?? 'System',
        ];

        $wa['history'] = $history;

        $permissions['whatsapp'] = $wa;

        $guest->permissions = $permissions;
        $guest->save();
    }
}