<?php

namespace App\Filament\Resources\InvitationResource\RelationManagers;

use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;

class GuestCheckinsRelationManager extends RelationManager
{
    protected static string $relationship = 'guestCheckins';
    protected static ?string $title = 'Check-in Tamu';

    public function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                Tables\Columns\TextColumn::make('guest.name')
                    ->label('Nama Tamu')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('barcode.barcode_code')
                    ->label('Barcode')
                    ->placeholder('-')
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('checkin_at')
                    ->label('Check-in')
                    ->dateTime('d M Y H:i:s')
                    ->sortable()
                    ->color('success'),

                Tables\Columns\TextColumn::make('checkout_at')
                    ->label('Check-out')
                    ->dateTime('d M Y H:i:s')
                    ->placeholder('Masih hadir')
                    ->sortable()
                    ->color(fn ($state) => $state ? 'gray' : 'warning'),

                Tables\Columns\TextColumn::make('attended_people')
                    ->label('Jumlah Orang')
                    ->numeric()
                    ->badge()
                    ->color('primary'),

                Tables\Columns\TextColumn::make('arrival_with')
                    ->label('Bersama')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'sendiri' => 'Sendiri',
                        'suami' => 'Suami',
                        'istri' => 'Istri',
                        'anak' => 'Anak',
                        'orang_tua' => 'Orang Tua',
                        'saudara' => 'Saudara',
                        'teman' => 'Teman',
                        default => ucfirst($state),
                    })
                    ->color(fn ($state) => match ($state) {
                        'sendiri' => 'gray',
                        'suami', 'istri' => 'primary',
                        'anak', 'orang_tua' => 'success',
                        'saudara', 'teman' => 'info',
                        default => 'gray',
                    }),

                Tables\Columns\IconColumn::make('checkout_at')
                    ->label('Status')
                    ->boolean(fn ($record) => $record->checkout_at === null)
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('gray'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'present' => 'Sedang Hadir',
                        'left' => 'Sudah Checkout',
                    ])
                    ->query(function ($query, $data) {
                        if ($data['value'] === 'present') {
                            $query->whereNull('checkout_at');
                        } elseif ($data['value'] === 'left') {
                            $query->whereNotNull('checkout_at');
                        }
                    })
                    ->native(false),

                Tables\Filters\SelectFilter::make('arrival_with')
                    ->label('Bersama')
                    ->options([
                        'sendiri' => 'Sendiri',
                        'suami' => 'Suami',
                        'istri' => 'Istri',
                        'anak' => 'Anak',
                        'orang_tua' => 'Orang Tua',
                        'saudara' => 'Saudara',
                        'teman' => 'Teman',
                    ])
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
            ->defaultSort('checkin_at', 'desc');
    }
}
