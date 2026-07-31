<?php

namespace App\Filament\Resources\InvitationResource\RelationManagers;

use App\Filament\Forms\BarcodePdfSettingsForm;
use App\Models\InvitationBarcode;
use App\Services\BarcodePdfService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

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
                    ->label('PDF')
                    ->badge()
                    ->color(fn ($record) => $record->batch ? 'success' : 'gray')
                    ->formatStateUsing(fn ($state, $record) => $record->batch ? $state : 'Belum ada PDF'),

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

                Tables\Filters\SelectFilter::make('pdf_status')
                    ->label('Status PDF')
                    ->options([
                        'has_pdf' => 'Sudah Ada PDF',
                        'no_pdf' => 'Belum Ada PDF',
                    ])
                    ->query(function (Builder $query, array $data) {
                        if ($data['value'] === 'has_pdf') {
                            $query->whereNotNull('barcode_pdf_batch_id');
                        } elseif ($data['value'] === 'no_pdf') {
                            $query->whereNull('barcode_pdf_batch_id');
                        }
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
                    ->visible(fn ($record) => ! $record->invitation_guest_id),

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
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('generate_pdf')
                        ->label('Generate PDF Terpilih')
                        ->icon('heroicon-o-document-arrow-down')
                        ->color('success')
                        ->modalWidth('7xl')
                        ->modalHeading('Generate PDF dari Barcode Terpilih')
                        ->modalDescription('Barcode yang belum memiliki PDF akan dimasukkan ke dalam satu PDF baru.')
                        ->modalSubmitActionLabel('Ya, Generate')
                        ->deselectRecordsAfterCompletion()
                        ->form(BarcodePdfSettingsForm::schema())
                        ->action(function (Collection $records, array $data) {
                            $invitation = $this->getOwnerRecord();

                            $eligible = $records->filter(
                                fn (InvitationBarcode $barcode) => is_null($barcode->barcode_pdf_batch_id)
                            );

                            if ($eligible->isEmpty()) {
                                Notification::make()
                                    ->title('Tidak Ada Barcode yang Bisa Digenerate')
                                    ->body('Semua barcode terpilih sudah memiliki PDF.')
                                    ->danger()
                                    ->send();

                                return;
                            }

                            $count = $eligible->count();
                            $batch = $invitation->barcodeBatches()->create([
                                'title' => 'Batch PDF '.str_pad($invitation->barcodeBatches()->count() + 1, 3, '0', STR_PAD_LEFT),
                                'quantity' => $count,
                                'pdf_settings' => BarcodePdfSettingsForm::normalize($data),
                            ]);

                            $eligible->each(fn (InvitationBarcode $barcode) => $barcode->update([
                                'barcode_pdf_batch_id' => $batch->id,
                            ]));

                            BarcodePdfService::generatePdf($batch);

                            Notification::make()
                                ->title('PDF Berhasil Digenerate')
                                ->body("{$count} barcode masuk ke PDF {$batch->title}.")
                                ->success()
                                ->send();
                        }),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
