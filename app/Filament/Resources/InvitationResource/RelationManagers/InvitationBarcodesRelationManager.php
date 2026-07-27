<?php

namespace App\Filament\Resources\InvitationResource\RelationManagers;

use App\Models\InvitationBarcode;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;

class InvitationBarcodesRelationManager extends RelationManager
{
    protected static string $relationship = 'invitationBarcodes';
    protected static ?string $title = 'Semua Barcode';

    public function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('barcode_code')
            ->columns([
                Tables\Columns\TextColumn::make('barcode_code')
                    ->label('Kode Barcode')
                    ->searchable()
                    ->weight('bold')
                    ->copyable(),

                Tables\Columns\TextColumn::make('barcode_token')
                    ->label('Token')
                    ->limit(15)
                    ->copyable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('guest.name')
                    ->label('Tamu')
                    ->placeholder('Belum terkait')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('batch.title')
                    ->label('Batch')
                    ->placeholder('-')
                    ->badge()
                    ->color('info'),

                Tables\Columns\IconColumn::make('is_used')
                    ->label('Status')
                    ->boolean(),

                Tables\Columns\TextColumn::make('generated_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Waktu Sistem')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'used' => 'Sudah Terpakai',
                        'unused' => 'Belum Terpakai',
                    ])
                    ->query(function ($query, $data) {
                        if ($data['value'] === 'used') {
                            $query->where('is_used', true);
                        } elseif ($data['value'] === 'unused') {
                            $query->where('is_used', false);
                        }
                    })
                    ->native(false),

                Tables\Filters\SelectFilter::make('batch_id')
                    ->label('Batch')
                    ->options(function () {
                        return $this->getOwnerRecord()
                            ->barcodeBatches()
                            ->pluck('title', 'id')
                            ->toArray();
                    })
                    ->native(false),
            ])
            ->headerActions([])
            ->actions([
                Tables\Actions\Action::make('connect_guest')
                    ->label('Connect ke Tamu')
                    ->icon('heroicon-o-user-plus')
                    ->color('success')
                    ->form([
                        Forms\Components\Select::make('guest_id')
                            ->label('Pilih Tamu Physical')
                            ->options(function () {
                                $invitationId = $this->getOwnerRecord()->id;

                                $connectedGuestIds = InvitationBarcode::where('invitation_id', $invitationId)
                                    ->whereNotNull('invitation_guest_id')
                                    ->pluck('invitation_guest_id')
                                    ->toArray();

                                return \App\Models\InvitationGuest::where('invitation_id', $invitationId)
                                    ->where('invitation_type', 'physical')
                                    ->whereNotIn('id', $connectedGuestIds)
                                    ->pluck('name', 'id')
                                    ->toArray();
                            })
                            ->searchable()
                            ->preload()
                            ->required()
                            ->placeholder('Pilih tamu physical yang belum terkait')
                            ->helperText(function () {
                                $invitationId = $this->getOwnerRecord()->id;
                                $count = \App\Models\InvitationGuest::where('invitation_id', $invitationId)
                                    ->where('invitation_type', 'physical')
                                    ->whereDoesntHave('barcodes')
                                    ->count();
                                return "{$count} tamu physical belum terkait barcode";
                            }),
                    ])
                    ->action(function ($record, $data) {
                        if ($record->invitation_guest_id) {
                            Notification::make()
                                ->title('Gagal')
                                ->body('Barcode ini sudah terkait dengan tamu lain.')
                                ->danger()
                                ->send();
                            return;
                        }

                        $record->update([
                            'invitation_guest_id' => $data['guest_id'],
                            'is_used' => true,
                        ]);

                        Notification::make()
                            ->title('Berhasil')
                            ->body("Barcode {$record->barcode_code} berhasil dikaitkan dengan tamu.")
                            ->success()
                            ->send();
                    })
                    ->visible(fn ($record) => !$record->invitation_guest_id),

                Tables\Actions\Action::make('disconnect_guest')
                    ->label('Putuskan')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Putuskan Barcode')
                    ->modalDescription('Barcode akan diputus dari tamu dan status akan menjadi belum terpakai.')
                    ->modalSubmitActionLabel('Ya, Putuskan')
                    ->action(function ($record) {
                        $record->update([
                            'invitation_guest_id' => null,
                            'is_used' => false,
                        ]);

                        Notification::make()
                            ->title('Berhasil')
                            ->body("Barcode {$record->barcode_code} berhasil diputus dari tamu.")
                            ->success()
                            ->send();
                    })
                    ->visible(fn ($record) => $record->invitation_guest_id),
            ])
            ->bulkActions([])
            ->defaultSort('created_at', 'desc');
    }
}
