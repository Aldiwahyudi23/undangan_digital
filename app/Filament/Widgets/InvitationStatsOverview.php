<?php

namespace App\Filament\Widgets;

use App\Models\GuestCheckin;
use App\Models\Invitation;
use App\Models\InvitationGuest;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;

class InvitationStatsOverview extends StatsOverviewWidget implements HasForms
{
    use InteractsWithForms;

    protected static string $view = 'filament.widgets.invitation-stats-overview';

    protected static ?int $sort = 1;

    protected ?string $heading = 'Statistik Undangan';

    public ?int $invitationId = null;

    public function mount(): void
    {
        $this->invitationId = (int) $this->invitationsQuery()->orderBy('title')->value('invitations.id');
    }

    protected function getFormSchema(): array
    {
        return [
            Select::make('invitationId')
                ->label('Pilih Undangan')
                ->placeholder('Pilih undangan…')
                ->options(fn (): array => $this->getInvitationOptions())
                ->live()
                ->afterStateUpdated(function (): void {
                    $this->cachedStats = null;
                }),
        ];
    }

    public function getInvitationOptions(): array
    {
        return $this->invitationsQuery()
            ->orderBy('title')
            ->pluck('invitations.title', 'invitations.id')
            ->mapWithKeys(fn (string $title, int $id): array => [(int) $id => $title])
            ->all();
    }

    protected function invitationsQuery(): Builder
    {
        $user = auth()->user();

        if ($user && $user->hasRole('super_admin')) {
            return Invitation::query();
        }

        return $user?->invitations()->getQuery() ?? Invitation::query()->whereRaw('1 = 0');
    }

    protected function getDescription(): ?string
    {
        $invitation = Invitation::find($this->invitationId);

        return $invitation?->title;
    }

    protected function getStats(): array
    {
        $invitationId = (int) ($this->invitationId ?? 0);

        $guests = InvitationGuest::query()->where('invitation_id', $invitationId);

        $totalGuests = (clone $guests)->count();
        $digitalGuests = (clone $guests)->where('invitation_type', 'digital')->count();
        $physicalGuests = (clone $guests)->where('invitation_type', 'physical')->count();
        $confirmed = (clone $guests)
            ->whereHas('attendance', fn ($q) => $q->where('status', 'attending'))
            ->count();

        $checkins = GuestCheckin::query()->where('invitation_id', $invitationId);

        $checkedInGuests = (clone $checkins)->distinct()->count('invitation_guest_id');
        $attendedPeople = (clone $checkins)->sum('attended_people');
        $currentlyPresent = (clone $checkins)->whereNull('checkout_at')->sum('attended_people');

        $confirmedPct = $totalGuests > 0 ? round($confirmed / $totalGuests * 100) : 0;

        return [
            Stat::make('Total Tamu', $totalGuests)
                ->description('Semua tamu terdaftar')
                ->descriptionIcon('heroicon-m-users')
                ->color('primary'),
            Stat::make('Tamu Digital', $digitalGuests)
                ->description('Undangan digital')
                ->descriptionIcon('heroicon-m-device-phone-mobile')
                ->color('info'),
            Stat::make('Tamu Fisik', $physicalGuests)
                ->description('Undangan fisik')
                ->descriptionIcon('heroicon-m-map-pin')
                ->color('warning'),
            Stat::make('Konfirmasi Hadir', $confirmed)
                ->description("{$confirmedPct}% dari total tamu")
                ->descriptionIcon('heroicon-m-check-badge')
                ->color('success'),
            Stat::make('Hadir Check-in', $checkedInGuests)
                ->description("{$attendedPeople} orang hadir · {$currentlyPresent} sedang di lokasi")
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),
        ];
    }
}
