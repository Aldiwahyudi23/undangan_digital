<?php

namespace App\Filament\Resources\InvitationResource\RelationManagers;

use App\Models\Couple;
use App\Models\InvitationBarcode;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Services\WhatsAppInvitationService;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\Group;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class GuestsRelationManager extends RelationManager
{
    protected static string $relationship = 'guests';
    protected static ?string $title = 'Tamu Digital';

    protected static ?string $recordTitleAttribute = 'name';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->label('Nama Tamu')
                    ->maxLength(255),

                Forms\Components\TextInput::make('share_whatsapp')
                    ->label('No WhatsApp')
                    ->required()
                    ->tel()
                    ->rule(function ($record) {

                        return Rule::unique('invitation_guests', 'share_whatsapp')
                            ->where('invitation_id', $this->getOwnerRecord()->id)
                            ->ignore($record);

                    }),

                Forms\Components\Textarea::make('note')
                    ->label('Catatan')
                    ->rows(3)
                    ->maxLength(500),

                Forms\Components\TextInput::make('group_name')
                    ->label('Group')
                    ->maxLength(100),

                Forms\Components\TextInput::make('location_tag')
                    ->label('Lokasi')
                    ->maxLength(100),

                Forms\Components\TextInput::make('max_device')
                    ->numeric()
                    ->default(1)
                    ->minValue(1)
                    ->maxValue(10)
                    ->label('Maksimal Device'),

                Forms\Components\Toggle::make('role')
                    ->label('Host')
                    ->default(false)
                    ->onColor('success')
                    ->offColor('gray')
                    ->onIcon('heroicon-o-user')
                    ->offIcon('heroicon-o-user')
                    ->afterStateHydrated(function ($component, $state) {
                        $component->state($state === 'host');
                    })
                    ->dehydrateStateUsing(function ($state) {
                        return $state ? 'host' : 'guest';
                    }),

            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->modifyQueryUsing(fn (Builder $query) => $query->where('invitation_type', 'digital'))
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('share_whatsapp')
                    ->label('WhatsApp')
                    ->searchable()
                    ->copyable()
                    ->copyMessage('Nomor WhatsApp disalin'),

                Tables\Columns\TextColumn::make('uuid')
                    ->label('UUID')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->copyable(),

                Tables\Columns\TextColumn::make('barcodes.barcode_code')
                    ->label('Barcode')
                    ->placeholder('Belum ada')
                    ->badge()
                    ->color(fn ($state) => $state ? 'success' : 'gray')
                    ->sortable(),

                Tables\Columns\TextColumn::make('attendance.status')
                    ->label('Kehadiran')
                    ->badge()
                    ->formatStateUsing(function ($state, $record) {
                        return match ($state) {
                            'attending' => 'Hadir (' . ($record->attendance?->total_guests ?? 1) . ' Org)',
                            'not_attending' => 'Tidak Hadir',
                            'pending' => 'Belum Konfirmasi',
                            default => 'Belum Konfirmasi',
                        };
                    })
                    ->color(fn ($state) => match ($state) {
                        'attending' => 'success',
                        'not_attending' => 'danger',
                        'pending' => 'warning',
                        default => 'gray',
                    })
                    ->toggleable(),

                Tables\Columns\TextColumn::make('group_name')
                    ->label('Group')
                    ->searchable()
                    ->toggleable()
                    ->badge()
                    ->color('primary'),

                Tables\Columns\TextColumn::make('location_tag')
                    ->label('Lokasi')
                    ->toggleable()
                    ->badge()
                    ->color('info'),

                Tables\Columns\IconColumn::make('wa_sent')
                    ->label('WA')
                    ->getStateUsing(function ($record) {
                        return data_get($record->permissions, 'whatsapp.is_sent', false);
                    })
                    ->boolean(),

                Tables\Columns\TextColumn::make('wa_sent_count')
                    ->label('Jumlah')
                    ->alignCenter()
                    ->getStateUsing(function ($record) {
                        return data_get($record->permissions, 'whatsapp.sent_count', 0);
                    })
                    ->badge()
                    ->color(fn ($state) => $state > 0 ? 'success' : 'gray'),

                Tables\Columns\TextColumn::make('wa_last_sent_at')
                    ->label('Terakhir Dikirim')
                    ->getStateUsing(function ($record) {
                        return data_get($record->permissions, 'whatsapp.last_sent_at');
                    })
                    ->since()
                    ->sortable(),

                Tables\Columns\TextColumn::make('wa_last_sent_by')
                    ->label('Dikirim Oleh')
                    ->getStateUsing(function ($record) {
                        return data_get($record->permissions, 'whatsapp.last_sent_by', '-');
                    })
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('device_count')
                    ->label('Device')
                    ->getStateUsing(function ($record) {
                        $devices = $record->device_ids ?? [];
                        $count = count($devices);
                        $max = $record->max_device ?? 1;
                        return "{$count}/{$max}";
                    })
                    ->badge()
                    ->color(function ($record) {
                        $devices = $record->device_ids ?? [];
                        $count = count($devices);
                        $max = $record->max_device ?? 1;
                        if ($count >= $max) return 'danger';
                        if ($count > 0) return 'warning';
                        return 'gray';
                    })
                    ->tooltip(function ($record) {
                        $devices = $record->device_ids ?? [];
                        if (empty($devices)) return 'Belum ada device';
                        return 'Device terdaftar: ' . implode(', ', array_slice($devices, 0, 3)) . (count($devices) > 3 ? ' ...' : '');
                    }),

                Tables\Columns\TextColumn::make('last_ip')
                    ->label('IP Terakhir')
                    ->toggleable()
                    ->copyable(),

                Tables\Columns\TextColumn::make('last_user_agent')
                    ->label('User Agent')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->limit(30)
                    ->tooltip(fn ($record) => $record->last_user_agent),

                Tables\Columns\TextColumn::make('opened_at')
                    ->label('Dibuka')
                    ->dateTime('d M Y H:i')
                    ->toggleable()
                    ->sortable(),

                ToggleColumn::make('is_locked')
                    ->label('Terkunci')
                    ->onColor('danger')
                    ->offColor('gray')
                    ->sortable(),

                ToggleColumn::make('is_streaming')
                    ->label('Streaming')
                    ->onColor('success')
                    ->offColor('gray')
                    ->sortable()
                    ->toggleable(),

                ToggleColumn::make('is_opened')
                    ->label('Sudah Dibuka')
                    ->onColor('success')
                    ->offColor('gray')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\IconColumn::make('role')
                    ->label('Host')
                    ->boolean(fn ($record) => $record->role === 'host'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('created_by')
                    ->label('Dibuat Oleh')
                    ->default(Auth::user()?->name)
                    ->options(function () {

                        return $this->getOwnerRecord()
                            ->guests()
                            ->where('invitation_type', 'digital')
                            ->get()
                            ->groupBy(fn ($guest) => data_get($guest->permissions, 'created_by'))
                            ->filter()
                            ->mapWithKeys(function ($items, $name) {
                                return [
                                    $name => "{$name} ({$items->count()})"
                                ];
                            })
                            ->toArray();

                    })
                    ->query(function (Builder $query, array $data) {

                        if (! filled($data['value'])) {
                            return;
                        }

                        $query->where(
                            'permissions->created_by',
                            $data['value']
                        );

                    }),

                Tables\Filters\SelectFilter::make('attendance.status')
                    ->label('Status Kehadiran')
                    ->options([
                        'attending' => 'Hadir',
                        'not_attending' => 'Tidak Hadir',
                        'pending' => 'Belum Konfirmasi',
                    ])
                    ->native(false),

                Tables\Filters\TernaryFilter::make('is_locked')
                    ->label('Terkunci')
                    ->trueLabel('Ya')
                    ->falseLabel('Tidak')
                    ->native(false),

                Tables\Filters\TernaryFilter::make('is_opened')
                    ->label('Sudah Dibuka')
                    ->trueLabel('Ya')
                    ->falseLabel('Tidak')
                    ->native(false),

                Tables\Filters\SelectFilter::make('group_name')
                    ->label('Group')
                    ->options(function () {
                        return $this->getOwnerRecord()
                            ->guests()
                            ->where('invitation_type', 'digital')
                            ->whereNotNull('group_name')
                            ->where('group_name', '!=', '')
                            ->pluck('group_name', 'group_name')
                            ->toArray();
                    })
                    ->native(false),

                Tables\Filters\SelectFilter::make('location_tag')
                    ->label('Lokasi')
                    ->options(function () {
                        return $this->getOwnerRecord()
                            ->guests()
                            ->where('invitation_type', 'digital')
                            ->whereNotNull('location_tag')
                            ->where('location_tag', '!=', '')
                            ->pluck('location_tag', 'location_tag')
                            ->toArray();
                    })
                    ->native(false),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Tambah Tamu Digital')
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['invitation_type'] = 'digital';
                        return $data;
                    })
                    ->after(function ($record) {
                        $this->generateBarcodeForGuest($record);
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('Detail')
                    ->icon('heroicon-o-eye')
                    ->infolist(fn (): Infolist => $this->getInfolist('view')),

                Tables\Actions\EditAction::make()
                    ->label('Edit'),

                Tables\Actions\Action::make('copy_link')
                    ->label('Copy Link')
                    ->icon('heroicon-o-link')
                    ->color('info')
                    ->action(function ($record) {

                        $link = url('https://fixnikah.miraaldi.my.id/undangan/' . $record->uuid );

                        Notification::make()
                            ->title('Link Undangan')
                            ->body($link)
                            ->success()
                            ->send();
                    }),

                Tables\Actions\Action::make('share_whatsapp')
                    ->label('Share WA')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('success')
                    ->action(function ($record) {

                        $service = app(WhatsAppInvitationService::class);

                        $service->markAsSent($record);

                        return redirect()->away(
                            $service->generateUrl($record)
                        );
                    })
                    ->openUrlInNewTab(),

                Tables\Actions\Action::make('reset')
                    ->label('Reset Device')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Reset Device Tamu')
                    ->modalDescription('Apakah Anda yakin ingin mereset semua device, IP, dan status buka untuk tamu ini?')
                    ->modalSubmitActionLabel('Ya, Reset!')
                    ->action(function ($record) {
                        $record->device_ids = [];
                        $record->last_ip = null;
                        $record->last_user_agent = null;
                        $record->is_opened = false;
                        $record->opened_at = null;
                        $record->is_locked = false;
                        $record->save();

                        Notification::make()
                            ->title('Berhasil direset')
                            ->body('Device, IP, dan status buka telah direset')
                            ->success()
                            ->send();
                    }),

                Tables\Actions\Action::make('reset_device_only')
                    ->label('Reset Device Saja')
                    ->icon('heroicon-o-device-phone-mobile')
                    ->color('gray')
                    ->requiresConfirmation()
                    ->modalHeading('Reset Device')
                    ->modalDescription('Reset hanya device yang terdaftar, tanpa merubah data lainnya')
                    ->modalSubmitActionLabel('Ya, Reset Device')
                    ->action(function ($record) {
                        $record->device_ids = [];
                        $record->save();

                        Notification::make()
                            ->title('Device direset')
                            ->body('Semua device telah dihapus')
                            ->success()
                            ->send();
                    }),

                Tables\Actions\Action::make('reset_status')
                    ->label('Reset Status')
                    ->icon('heroicon-o-eye-slash')
                    ->color('gray')
                    ->requiresConfirmation()
                    ->modalHeading('Reset Status Buka')
                    ->modalDescription('Reset status is_opened dan opened_at')
                    ->modalSubmitActionLabel('Ya, Reset Status')
                    ->action(function ($record) {
                        $record->is_opened = false;
                        $record->opened_at = null;
                        $record->save();

                        Notification::make()
                            ->title('Status direset')
                            ->body('Status buka telah direset')
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Hapus Terpilih'),

                    Tables\Actions\BulkAction::make('bulk_reset')
                        ->label('Reset Terpilih')
                        ->icon('heroicon-o-arrow-path')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->modalHeading('Reset Semua Tamu Terpilih')
                        ->modalDescription('Apakah Anda yakin ingin mereset semua data tamu yang dipilih?')
                        ->modalSubmitActionLabel('Ya, Reset Semua!')
                        ->action(function ($records) {
                            foreach ($records as $record) {
                                $record->device_ids = [];
                                $record->last_ip = null;
                                $record->last_user_agent = null;
                                $record->is_opened = false;
                                $record->opened_at = null;
                                $record->save();
                            }

                            Notification::make()
                                ->title('Berhasil direset')
                                ->body('Semua data tamu terpilih telah direset')
                                ->success()
                                ->send();
                        }),

                    Tables\Actions\BulkAction::make('bulk_lock')
                        ->label('Kunci Terpilih')
                        ->icon('heroicon-o-lock-closed')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            foreach ($records as $record) {
                                $record->is_locked = true;
                                $record->save();
                            }

                            Notification::make()
                                ->title('Berhasil dikunci')
                                ->body('Semua tamu terpilih telah dikunci')
                                ->success()
                                ->send();
                        }),

                    Tables\Actions\BulkAction::make('bulk_unlock')
                        ->label('Buka Kunci Terpilih')
                        ->icon('heroicon-o-lock-open')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            foreach ($records as $record) {
                                $record->is_locked = false;
                                $record->save();
                            }

                            Notification::make()
                                ->title('Berhasil dibuka')
                                ->body('Semua tamu terpilih telah dibuka kuncinya')
                                ->success()
                                ->send();
                        }),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    protected function generateBarcodeForGuest($guest): void
    {
        $lastBarcode = InvitationBarcode::where('invitation_id', $guest->invitation_id)
            ->where('invitation_guest_id', null)
            ->orderByDesc('id')
            ->first();

        if ($lastBarcode) {
            $lastBarcode->update([
                'invitation_guest_id' => $guest->id,
                'is_used' => true,
            ]);
        } else {
            $code = InvitationBarcode::nextCode($guest->invitation_id);

            InvitationBarcode::create([
                'invitation_id' => $guest->invitation_id,
                'barcode_code' => $code,
                'invitation_guest_id' => $guest->id,
                'is_used' => true,
                'generated_at' => now(),
            ]);
        }
    }

    public function getInfolist(string $name): ?Infolist
    {
        if ($name === 'view') {
            return Infolist::make()
                ->schema([
                    Section::make('Informasi Tamu')
                        ->schema([
                            Grid::make(2)
                                ->schema([
                                    TextEntry::make('name')
                                        ->label('Nama Tamu')
                                        ->weight('bold')
                                        ->size(TextEntry\TextEntrySize::Large),

                                    TextEntry::make('role')
                                        ->label('Role')
                                        ->badge()
                                        ->formatStateUsing(fn ($state) => ucfirst($state))
                                        ->color(fn ($state) => $state === 'host' ? 'warning' : 'gray'),

                                    TextEntry::make('share_whatsapp')
                                        ->label('Nomor WhatsApp')
                                        ->copyable()
                                        ->icon('heroicon-o-phone'),

                                    TextEntry::make('group_name')
                                        ->label('Group')
                                        ->badge()
                                        ->color('primary')
                                        ->placeholder('-'),

                                    TextEntry::make('location_tag')
                                        ->label('Lokasi')
                                        ->badge()
                                        ->color('info')
                                        ->placeholder('-'),

                                    TextEntry::make('note')
                                        ->label('Catatan')
                                        ->columnSpan(2)
                                        ->placeholder('Tidak ada catatan')
                                        ->limit(200),
                                ]),
                        ])
                        ->collapsible(),

                    Section::make('Informasi Barcode')
                        ->schema([
                            Grid::make(2)
                                ->schema([
                                    TextEntry::make('barcodes.barcode_code')
                                        ->label('Kode Barcode')
                                        ->copyable()
                                        ->placeholder('Belum ada barcode'),

                                    TextEntry::make('barcodes.barcode_token')
                                        ->label('Token Barcode')
                                        ->copyable()
                                        ->limit(20)
                                        ->placeholder('Belum ada barcode'),
                                ]),
                        ])
                        ->collapsible(),

                    Section::make('Informasi Akses')
                        ->schema([
                            Grid::make(2)
                                ->schema([
                                    TextEntry::make('uuid')
                                        ->label('UUID')
                                        ->copyable(),

                                    TextEntry::make('token')
                                        ->label('Token')
                                        ->copyable()
                                        ->limit(20),

                                    TextEntry::make('max_device')
                                        ->label('Maksimal Device')
                                        ->numeric(),

                                    TextEntry::make('device_ids')
                                        ->label('Device Terdaftar')
                                        ->formatStateUsing(function ($state) {
                                            if (empty($state)) return 'Belum ada device';
                                            $count = count($state);
                                            return "{$count} device terdaftar\n" . implode("\n", $state);
                                        })
                                        ->columnSpan(2)
                                        ->limit(5),

                                    TextEntry::make('is_locked')
                                        ->label('Status Kunci')
                                        ->badge()
                                        ->formatStateUsing(fn ($state) => $state ? 'Terkunci' : 'Tidak Terkunci')
                                        ->color(fn ($state) => $state ? 'danger' : 'success'),

                                    TextEntry::make('is_streaming')
                                        ->label('Streaming')
                                        ->badge()
                                        ->formatStateUsing(fn ($state) => $state ? 'Aktif' : 'Nonaktif')
                                        ->color(fn ($state) => $state ? 'success' : 'gray'),
                                ]),
                        ])
                        ->collapsible(),

                    Section::make('Informasi Kunjungan')
                        ->schema([
                            Grid::make(2)
                                ->schema([
                                    TextEntry::make('is_opened')
                                        ->label('Sudah Dibuka')
                                        ->badge()
                                        ->formatStateUsing(fn ($state) => $state ? 'Sudah' : 'Belum')
                                        ->color(fn ($state) => $state ? 'success' : 'gray'),

                                    TextEntry::make('opened_at')
                                        ->label('Waktu Dibuka')
                                        ->dateTime('d M Y H:i:s')
                                        ->placeholder('Belum dibuka'),

                                    TextEntry::make('last_ip')
                                        ->label('IP Terakhir')
                                        ->copyable()
                                        ->placeholder('-'),

                                    TextEntry::make('last_user_agent')
                                        ->label('User Agent')
                                        ->limit(50)
                                        ->placeholder('-')
                                        ->columnSpan(2),
                                ]),
                        ])
                        ->collapsible(),

                    Section::make('Informasi Kehadiran & Interaksi')
                        ->schema([
                            Grid::make(2)
                                ->schema([
                                    TextEntry::make('attendance.status')
                                        ->label('Status Kehadiran')
                                        ->badge()
                                        ->formatStateUsing(function ($state) {
                                            return match ($state) {
                                                'attending' => 'Hadir',
                                                'not_attending' => 'Tidak Hadir',
                                                'pending' => 'Belum Konfirmasi',
                                                default => 'Belum Konfirmasi',
                                            };
                                        })
                                        ->color(fn ($state) => match ($state) {
                                            'attending' => 'success',
                                            'not_attending' => 'danger',
                                            'pending' => 'warning',
                                            default => 'gray',
                                        }),

                                    TextEntry::make('attendance.total_guests')
                                        ->label('Jumlah Tamu')
                                        ->numeric()
                                        ->placeholder('-'),

                                    TextEntry::make('attendance.notes')
                                        ->label('Catatan Kehadiran')
                                        ->placeholder('-')
                                        ->columnSpan(2),
                                ]),
                        ])
                        ->collapsible(),

                    Section::make('Informasi Waktu')
                        ->schema([
                            Grid::make(2)
                                ->schema([
                                    TextEntry::make('created_at')
                                        ->label('Dibuat')
                                        ->dateTime('d M Y H:i:s'),

                                    TextEntry::make('updated_at')
                                        ->label('Diperbarui')
                                        ->dateTime('d M Y H:i:s'),

                                    TextEntry::make('last_seen_live')
                                        ->label('Terakhir Live')
                                        ->dateTime('d M Y H:i:s')
                                        ->placeholder('Belum pernah live'),

                                    TextEntry::make('is_watching_live')
                                        ->label('Menonton Live')
                                        ->badge()
                                        ->formatStateUsing(fn ($state) => $state ? 'Ya' : 'Tidak')
                                        ->color(fn ($state) => $state ? 'success' : 'gray'),
                                ]),
                        ])
                        ->collapsible(),
                ])
                ->columns(1);
        }

        return null;
    }
}
