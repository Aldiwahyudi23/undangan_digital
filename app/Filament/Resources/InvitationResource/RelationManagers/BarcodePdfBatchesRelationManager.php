<?php

namespace App\Filament\Resources\InvitationResource\RelationManagers;

use App\Filament\Forms\BarcodePdfSettingsForm;
use App\Models\BarcodePdfBatch;
use App\Models\InvitationBarcode;
use App\Services\BarcodePdfService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;

class BarcodePdfBatchesRelationManager extends RelationManager
{
    protected static string $relationship = 'barcodeBatches';

    protected static ?string $title = 'Batch Barcode PDF';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Grid::make(2)
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->required()
                            ->label('Judul Batch')
                            ->maxLength(255)
                            ->placeholder('Contoh: Batch 001'),

                        Forms\Components\TextInput::make('quantity')
                            ->required()
                            ->numeric()
                            ->label('Jumlah Barcode')
                            ->minValue(1)
                            ->maxValue(1000)
                            ->placeholder('Contoh: 100'),
                    ]),

                ...BarcodePdfSettingsForm::schema(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('Judul')
                    ->searchable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('quantity')
                    ->label('Jumlah')
                    ->numeric()
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('barcodes_count')
                    ->label('Barcode Terbuat')
                    ->counts('barcodes')
                    ->badge()
                    ->color(fn ($state, $record) => $state >= $record->quantity ? 'success' : 'warning'),

                Tables\Columns\TextColumn::make('pdf_path')
                    ->label('PDF')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state ? 'Tersedia' : 'Belum')
                    ->color(fn ($state) => $state ? 'success' : 'gray'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
            ])
            ->filters([])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Generate Batch Barcode')
                    ->icon('heroicon-o-qr-code')
                    ->color('success')
                    ->modalWidth('7xl')
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['invitation_id'] = $this->getOwnerRecord()->id;
                        $data['pdf_settings'] = BarcodePdfSettingsForm::normalize($data);

                        return $data;
                    })
                    ->after(function ($record) {
                        $this->generateBatch($record, $record->quantity);
                        $this->generatePdf($record);

                        Notification::make()
                            ->title('Batch Barcode Berhasil Dibuat')
                            ->body("{$record->quantity} barcode telah digenerate. PDF siap diunduh.")
                            ->success()
                            ->send();
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('download_pdf')
                    ->label('Download PDF')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('primary')
                    ->url(function ($record) {
                        if (! $record->pdf_path) {
                            return null;
                        }

                        return Storage::url($record->pdf_path);
                    })
                    ->openUrlInNewTab()
                    ->disabled(fn ($record) => ! $record->pdf_path),

                Tables\Actions\Action::make('regenerate_pdf')
                    ->label('Regenerate PDF')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->modalWidth('7xl')
                    ->modalHeading('Regenerate PDF')
                    ->modalDescription('Atur ulang layout label, lalu PDF akan digenerate ulang dari barcode yang sudah ada.')
                    ->modalSubmitActionLabel('Ya, Regenerate')
                    ->form(BarcodePdfSettingsForm::schema())
                    ->fillForm(fn ($record) => BarcodePdfSettingsForm::normalize($record->pdf_settings ?? []))
                    ->action(function ($record, array $data) {
                        $settings = BarcodePdfSettingsForm::normalize($data);
                        $record->update(['pdf_settings' => $settings]);

                        $this->generatePdf($record, $settings);

                        Notification::make()
                            ->title('PDF Berhasil Diregenerate')
                            ->success()
                            ->send();
                    })
                    ->visible(fn ($record) => $record->barcodes()->count() > 0),
            ])
            ->bulkActions([]);
    }

    protected function generateBatch(BarcodePdfBatch $batch, int $quantity): void
    {
        $invitationId = $batch->invitation_id;

        $lastCode = InvitationBarcode::where('invitation_id', $invitationId)
            ->max('barcode_code');

        if ($lastCode && preg_match('/^BC(\d+)$/', $lastCode, $matches)) {
            $lastNumber = (int) $matches[1];
        } else {
            $lastNumber = 0;
        }

        $barcodes = [];
        for ($i = 1; $i <= $quantity; $i++) {
            $code = 'BC'.str_pad($lastNumber + $i, 6, '0', STR_PAD_LEFT);

            $barcodes[] = [
                'invitation_id' => $invitationId,
                'uuid' => (string) \Illuminate\Support\Str::uuid(),
                'barcode_code' => $code,
                'barcode_token' => strtoupper(\Illuminate\Support\Str::random(16)),
                'barcode_pdf_batch_id' => $batch->id,
                'generated_at' => now(),
                'is_used' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        InvitationBarcode::insert($barcodes);
    }

    protected function generatePdf(BarcodePdfBatch $batch, ?array $settings = null): void
    {
        BarcodePdfService::generatePdf($batch, $settings);
    }
}
