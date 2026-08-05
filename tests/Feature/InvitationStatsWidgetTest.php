<?php

namespace Tests\Feature;

use App\Filament\Widgets\InvitationStatsOverview;
use App\Models\Attendance;
use App\Models\GuestCheckin;
use App\Models\Invitation;
use App\Models\InvitationGuest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class InvitationStatsWidgetTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate('super_admin');
        Role::findOrCreate('receptionist');
    }

    private function makeInvitation(User $user, string $title): Invitation
    {
        return Invitation::create([
            'user_id' => $user->id,
            'title' => $title,
        ]);
    }

    public function test_super_admin_sees_all_invitations_with_correct_stats(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $invitation = $this->makeInvitation($user, 'Pernikahan Test');

        $digital = InvitationGuest::create([
            'invitation_id' => $invitation->id,
            'name' => 'Tamu Digital',
            'invitation_type' => 'digital',
        ]);

        InvitationGuest::create([
            'invitation_id' => $invitation->id,
            'name' => 'Tamu Fisik',
            'invitation_type' => 'physical',
        ]);

        Attendance::create([
            'invitation_id' => $invitation->id,
            'invitation_guest_id' => $digital->id,
            'status' => 'attending',
        ]);

        GuestCheckin::create([
            'invitation_id' => $invitation->id,
            'invitation_guest_id' => $digital->id,
            'checkin_at' => now(),
            'attended_people' => 2,
        ]);

        Livewire::actingAs($user)
            ->test(InvitationStatsOverview::class)
            ->assertSee('Pernikahan Test')
            ->assertSee('Total Tamu')
            ->assertSee('Tamu Digital')
            ->assertSee('Tamu Fisik')
            ->assertSee('Konfirmasi Hadir')
            ->assertSee('Hadir Check-in')
            ->assertSee('50% dari total tamu')
            ->assertSee('2 orang hadir · 2 sedang di lokasi')
            ->assertDontSee('Pilih Undangan');
    }

    public function test_receptionist_only_sees_linked_invitations(): void
    {
        $user = User::factory()->create();
        $user->assignRole('receptionist');

        $linked = $this->makeInvitation($user, 'Undangan Linked');
        $other = $this->makeInvitation($user, 'Undangan Lain');

        $user->invitations()->attach($linked->id);

        InvitationGuest::create([
            'invitation_id' => $linked->id,
            'name' => 'A',
            'invitation_type' => 'digital',
        ]);

        InvitationGuest::create([
            'invitation_id' => $other->id,
            'name' => 'B',
            'invitation_type' => 'digital',
        ]);

        Livewire::actingAs($user)
            ->test(InvitationStatsOverview::class)
            ->assertSee('Undangan Linked')
            ->assertDontSee('Undangan Lain')
            ->assertDontSee('Pilih Undangan');
    }

    public function test_invitation_filter_shown_when_user_has_multiple_invitations(): void
    {
        $user = User::factory()->create();
        $user->assignRole('receptionist');

        $first = $this->makeInvitation($user, 'Undangan Pertama');
        $second = $this->makeInvitation($user, 'Undangan Kedua');

        $user->invitations()->attach([$first->id, $second->id]);

        Livewire::actingAs($user)
            ->test(InvitationStatsOverview::class)
            ->assertSee('Pilih Undangan')
            ->assertSee('Undangan Pertama')
            ->assertSee('Undangan Kedua');
    }
}
