<?php

namespace App\Filament\Resources\InvitationResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use App\Models\Moment;

class MomentRelationManager extends RelationManager
{
    protected static string $relationship = 'moments';

    protected static ?string $title = 'Public Moments';

    protected static ?string $label = 'Moment';

    protected static ?string $pluralLabel = 'Moments';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('guest_name')
                    ->label('Nama Tamu')
                    ->required()
                    ->maxLength(255),

                Forms\Components\Select::make('type')
                    ->label('Tipe')
                    ->options([
                        'post' => 'Post (Foto/Video)',
                        'voice' => 'Voice Note',
                    ])
                    ->required()
                    ->native(false),

                Forms\Components\Textarea::make('caption')
                    ->label('Caption')
                    ->maxLength(65535)
                    ->rows(3)
                    ->columnSpanFull(),

                Forms\Components\SpatieMediaLibraryFileUpload::make('media_moments')
                    ->label('Media (bisa lebih dari 1, foto & video)')
                    ->collection('moments')
                    ->multiple()
                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/jpg', 'image/gif', 'video/mp4', 'video/quicktime', 'video/mov', 'video/avi'])
                    ->maxSize(30720)
                    ->columnSpanFull()
                    ->visible(fn (Get $get) => $get('type') === 'post')
                    ->dehydrated(fn (Get $get) => $get('type') === 'post'),

                Forms\Components\SpatieMediaLibraryFileUpload::make('media_voice')
                    ->label('Voice Note (satu file audio)')
                    ->collection('voice')
                    ->multiple(false)
                    ->acceptedFileTypes(['audio/mpeg', 'audio/mp4', 'audio/m4a', 'audio/wav', 'audio/x-wav', 'audio/ogg', 'audio/aac', 'audio/webm', 'audio/amr', 'audio/opus', 'video/webm'])
                    ->maxSize(30720)
                    ->columnSpanFull()
                    ->visible(fn (Get $get) => $get('type') === 'voice')
                    ->dehydrated(fn (Get $get) => $get('type') === 'voice'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('guest_name')
            ->columns([
                Tables\Columns\TextColumn::make('guest_name')
                    ->label('Nama Tamu')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('type')
                    ->label('Tipe')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'post' => 'success',
                        'voice' => 'warning',
                        default => 'secondary',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'post' => '📸 Post',
                        'voice' => '🎙️ Voice Note',
                        default => ucfirst($state),
                    }),

                Tables\Columns\ViewColumn::make('media_preview')
                    ->label('Media')
                    ->view('filament.columns.moment-media-preview'),

                Tables\Columns\TextColumn::make('caption')
                    ->label('Caption')
                    ->limit(100)
                    ->searchable(),

                Tables\Columns\TextColumn::make('media_count')
                    ->label('Media')
                    ->formatStateUsing(fn ($record) => $record->getMedia('*')->count() . ' file')
                    ->badge()
                    ->color('primary'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Diperbarui')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Tipe')
                    ->options([
                        'post' => 'Post (Foto/Video)',
                        'voice' => 'Voice Note',
                    ]),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Tambah Moment')
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['invitation_id'] = $this->getOwnerRecord()->id;
                        return $data;
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('Edit')
                    ->modalHeading('Edit Moment')
                    ->mutateFormDataUsing(function (array $data, $record): array {
                        $data['invitation_id'] = $record->invitation_id;
                        return $data;
                    }),

                Tables\Actions\DeleteAction::make()
                    ->label('Hapus')
                    ->modalHeading('Hapus Moment')
                    ->modalDescription('Moment dan semua media akan dihapus secara permanen. Apakah Anda yakin?')
                    ->action(function ($record) {
                        $record->clearMediaCollection('*');
                        $record->delete();
                    }),

                Tables\Actions\ViewAction::make()
                    ->label('Lihat')
                    ->modalHeading('Detail Moment')
                    ->modalContent(fn (Moment $record) => view('filament.moment-media-review', ['moment' => $record])),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Hapus Terpilih')
                        ->modalHeading('Hapus Moment Terpilih')
                        ->modalDescription('Moment dan media yang terpilih akan dihapus permanen')
                        ->action(function ($records) {
                            foreach ($records as $record) {
                                $record->clearMediaCollection('*');
                                $record->delete();
                            }
                        }),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
