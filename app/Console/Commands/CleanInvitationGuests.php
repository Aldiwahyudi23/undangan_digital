<?php

namespace App\Console\Commands;

use App\Models\InvitationGuest;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CleanInvitationGuests extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'guests:clean';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Lock guest & hapus token setelah event + 3 hari';

    /**
     * Execute the console command.
     */


    public function handle()
    {
        $now = Carbon::now();
        $updated = 0;
        InvitationGuest::with(['invitation.events'])
            ->whereNotNull('token')
            ->where('is_locked', false)
            ->chunk(100, function ($guests) use ($now, &$updated) {

                foreach ($guests as $guest) {

                    $events = $guest->invitation->events ?? collect();

                    if ($events->isEmpty()) {
                        continue;
                    }

                    // 🔥 Ambil tanggal paling akhir
                    $lastEventDate = $events->max('date');

                    if (!$lastEventDate) {
                        continue;
                    }

                    $expiredAt = Carbon::parse($lastEventDate)->addDays(3);

                    if ($now->gte($expiredAt)) {
                        $guest->update([
                            'token' => null,
                            'is_locked' => true
                        ]);

                        $updated++;
                    }
                }
            });

        $this->info("Berhasil update {$updated} guest.");
    }
}
