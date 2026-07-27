<?php

namespace App\Filament\Resources\InvitationResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;

class DoorprizeWinnersRelationManager extends RelationManager
{
    protected static string $relationship = 'doorprizeWinners';
    protected static ?string $title = 'Pemenang Doorprize';

    public function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('prize')
            ->columns([
                Tables\Columns\TextColumn::make('guest.name')
                    ->label('Nama Tamu')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('prize')
                    ->label('Hadiah')
                    ->searchable()
                    ->weight('bold')
                    ->color('success'),

                Tables\Columns\TextColumn::make('session')
                    ->label('Sesi')
                    ->badge()
                    ->color('info')
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Waktu')
                    ->dateTime('d M Y H:i:s')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('session')
                    ->label('Sesi')
                    ->options(function () {
                        return $this->getOwnerRecord()
                            ->doorprizeWinners()
                            ->whereNotNull('session')
                            ->distinct()
                            ->pluck('session', 'session')
                            ->toArray();
                    })
                    ->native(false),
            ])
            ->headerActions([])
            ->actions([
                Tables\Actions\DeleteAction::make()
                    ->label('Hapus'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Hapus Terpilih'),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
