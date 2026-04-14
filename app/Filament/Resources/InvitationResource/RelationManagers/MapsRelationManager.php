<?php

namespace App\Filament\Resources\InvitationResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Table;

class MapsRelationManager extends RelationManager
{
    protected static string $relationship = 'maps';

    protected static ?string $recordTitleAttribute = 'name';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Nama Lokasi')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('label')
                    ->label('Label')
                    ->maxLength(255),
                Forms\Components\Textarea::make('address')
                    ->label('Alamat Lengkap')
                    ->required()
                    ->rows(3)
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('url_frame')
                    ->label('URL Frame Google Maps')
                    ->url(),
                Forms\Components\Select::make('image_id')
                    ->label('Foto Lokasi')
                    ->relationship('image', 'title')
                    ->searchable()
                    ->preload(),
                Forms\Components\Textarea::make('notes')
                    ->label('Catatan Tambahan')
                    ->rows(3)
                    ->columnSpanFull(),

            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\ImageColumn::make('image.path')
                    ->label('Gambar')
                    ->circular()
                    ->width(50)
                    ->height(50)
                    ->getStateUsing(function ($record) {
                        // Return full URL untuk gambar
                        return asset('storage/' . $record->image->path);
                    })
                     ->action(
                        Action::make('preview')
                            ->modalHeading('Preview Gambar')
                            ->modalSubmitAction(false) // hilangkan tombol submit
                            ->modalCancelActionLabel('Tutup')
                            ->modalWidth('lg')
                            ->modalContent(fn ($record) => view('filament.preview-image', [
                                'image' => asset('storage/' . $record->image->path),
                            ]))
                    ),
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Lokasi')
                    ->searchable(),
                Tables\Columns\TextColumn::make('label')
                    ->label('Label'),
                Tables\Columns\TextColumn::make('address')
                    ->label('Alamat')
                    ->limit(50),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}