<?php

namespace App\Filament\Resources\InvitationResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class GuestsRelationManager extends RelationManager
{
    protected static string $relationship = 'guests';
    protected static ?string $title = 'Tamu Undangan';
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->label('Nama Tamu'),

                Forms\Components\Textarea::make('note')
                    ->label('Catatan'),

                Forms\Components\TextInput::make('group_name')
                    ->label('Group'),

                Forms\Components\TextInput::make('location_tag')
                    ->label('Lokasi'),

                Forms\Components\TextInput::make('max_device')
                    ->numeric()
                    ->default(1),

            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),

                Tables\Columns\TextColumn::make('group_name'),

                Tables\Columns\TextColumn::make('location_tag'),

                Tables\Columns\TextColumn::make('uuid')
                    ->copyable()
                    ->label('UUID'),

                Tables\Columns\TextColumn::make('token')
                    ->copyable()
                    ->limit(10),

                Tables\Columns\TextColumn::make('last_ip')
                    ->label('IP'),

                Tables\Columns\IconColumn::make('is_locked')
                    ->boolean(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
                
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                // Tables\Actions\DeleteAction::make(),
                Tables\Actions\Action::make('copy_link')
                    ->label('Copy Link')
                    ->icon('heroicon-o-link')
                    ->action(function ($record) {

                        $link = url('http://localhost:5173/invitation/' . $record->uuid . '?token=' . $record->token);

                        \Filament\Notifications\Notification::make()
                            ->title('Link Undangan')
                            ->body($link)
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
