<?php

namespace App\Filament\Resources\InvitationResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class GiftAccountRelationManager extends RelationManager
{
    protected static string $relationship = 'GiftAccount';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('couple_id')
                    ->relationship('couple', 'full_name')
                    ->required()
                    ->searchable()
                    ->preload(),
                    
                Forms\Components\Select::make('bank_name')
                    ->options([
                        'BCA' => 'BCA',
                        'Mandiri' => 'Mandiri',
                        'BRI' => 'BRI',
                        'BNI' => 'BNI',
                        'Dana' => 'Dana',
                        'OVO' => 'OVO',
                        'GoPay' => 'GoPay',
                        'ShopeePay' => 'ShopeePay',
                        'LinkAja' => 'LinkAja',
                        'Other' => 'Other',
                    ])
                    ->required()
                    ->searchable(),
                    
                Forms\Components\TextInput::make('account_number')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
                    
                Forms\Components\TextInput::make('account_name')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('bank_name')
                    ->columns([
                    
                Tables\Columns\TextColumn::make('couple.full_name')
                    ->label('Pengantin')
                    ->sortable()
                    ->searchable(),
                    
                Tables\Columns\TextColumn::make('bank_name')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'BCA' => 'primary',
                        'Mandiri' => 'success',
                        'BRI' => 'warning',
                        'BNI' => 'info',
                        'Dana', 'OVO', 'GoPay', 'ShopeePay', 'LinkAja' => 'danger',
                        default => 'gray',
                    })
                    ->searchable(),
                    
                Tables\Columns\TextColumn::make('account_number')
                    ->searchable()
                    ->copyable()
                    ->copyMessage('Account number copied!'),
                    
                Tables\Columns\TextColumn::make('account_name')
                    ->searchable(),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                    
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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

