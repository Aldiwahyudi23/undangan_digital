<?php

namespace App\Filament\Resources\InvitationResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class EventsRelationManager extends RelationManager
{
    protected static string $relationship = 'events';

    protected static ?string $recordTitleAttribute = 'title';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('title')
                    ->label('Judul Acara')
                    ->required()
                    ->maxLength(255),
                Forms\Components\DatePicker::make('date')
                    ->label('Tanggal')
                    ->required(),
                Forms\Components\TimePicker::make('start_time')
                    ->label('Jam Mulai')
                    ->required(),
                Forms\Components\TimePicker::make('end_time')
                    ->label('Jam Selesai'),
                Forms\Components\Select::make('map_id')
                    ->label('Lokasi')
                    ->relationship('map', 'name')
                    ->searchable()
                    ->preload(),
                Forms\Components\Textarea::make('notes')
                    ->label('Catatan')
                    ->rows(3),
                Forms\Components\KeyValue::make('metadata')
                    ->label('Metadata')
                    ->reorderable()
                    ->schema([
                        Forms\Components\TextInput::make('link_streaming')
                            ->label('Link Streaming')
                            ->url(),
                        Forms\Components\TextInput::make('dress_code')
                            ->label('Dress Code'),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('Judul')
                    ->searchable(),
                Tables\Columns\TextColumn::make('date')
                    ->label('Tanggal')
                    ->date('d M Y'),
                Tables\Columns\TextColumn::make('start_time')
                    ->label('Jam Mulai')
                    ->time('H:i'),
                Tables\Columns\TextColumn::make('end_time')
                    ->label('Jam Selesai')
                    ->time('H:i'),
                Tables\Columns\TextColumn::make('map.name')
                    ->label('Lokasi'),
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