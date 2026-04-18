<?php

namespace App\Filament\Resources\InvitationResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Models\InvitationGuest;

class AttendanceRelationManager extends RelationManager
{
    protected static string $relationship = 'attendances';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('invitation_guest_id')
                    ->label('Guest')
                    ->relationship('guest', 'name') // Sesuaikan 'name' dengan field nama di InvitationGuest
                    ->required()
                    ->searchable()
                    ->preload(),
                
                Forms\Components\Select::make('status')
                    ->label('Status')
                    ->options([
                        'pending' => 'Pending',
                        'confirmed' => 'Confirmed',
                        'declined' => 'Declined',
                        'maybe' => 'Maybe',
                    ])
                    ->required()
                    ->default('pending'),
                
                Forms\Components\TextInput::make('total_guests')
                    ->label('Total Guests')
                    ->numeric()
                    ->minValue(1)
                    ->default(1)
                    ->required(),
                
                Forms\Components\Textarea::make('message')
                    ->label('Message')
                    ->maxLength(1000)
                    ->columnSpanFull(),
                
                Forms\Components\Toggle::make('is_private')
                    ->label('Private Response')
                    ->default(false),
                
                Forms\Components\DateTimePicker::make('replied_at')
                    ->label('Replied At')
                    ->default(now()),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('status')
            ->columns([
                Tables\Columns\TextColumn::make('guest.name')
                    ->label('Nama Tamu')
                    ->searchable()
                    ->sortable()
                    ->formatStateUsing(function ($state, $record) {
                        // Menampilkan nama dan status di sampingnya
                        $statusText = '';
                        $statusColor = '';
                        
                        switch ($record->status) {
                            case 'attending':
                                $statusText = 'Hadir';
                                $statusColor = 'text-success-600';
                                break;
                            case 'not_attending':
                                $statusText = 'Tidak Hadir';
                                $statusColor = 'text-danger-600';
                                break;
                            case 'pending':
                                $statusText = 'Menunggu';
                                $statusColor = 'text-warning-600';
                                break;
                            default:
                                $statusText = ucfirst($record->status);
                                $statusColor = '';
                        }
                        
                        // Jika ada message, tampilkan di bawah nama
                        $messageHtml = '';
                        if ($record->message && !empty($record->message)) {
                            $messageHtml = '<div class="text-xs text-gray-500 mt-1">📝 ' . e($record->message) . '</div>';
                        }
                        
                        return '<div>' . $state . ' <span class="' . $statusColor . ' font-medium">(' . $statusText . ')</span>' . $messageHtml . '</div>';
                    })
                    ->html(),
                
                Tables\Columns\TextColumn::make('total_guests')
                    ->label('Jumlah Tamu')
                    ->numeric()
                    ->sortable()
                    ->toggleable() // Bisa disembunyikan/ditampilkan
                    ->toggleable(isToggledHiddenByDefault: false),
                
                Tables\Columns\TextColumn::make('replied_at')
                    ->label('Waktu Konfirmasi')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('is_private')
                    ->label('Privat')
                    ->formatStateUsing(fn (bool $state): string => $state ? 'Ya' : 'Tidak')
                    ->badge()
                    ->color(fn (bool $state): string => $state ? 'warning' : 'success')
                    ->toggleable() // Bisa disembunyikan/ditampilkan
                    ->toggleable(isToggledHiddenByDefault: false),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'attending' => 'Hadir',
                        'not_attending' => 'Tidak Hadir',
                        'pending' => 'Menunggu',
                    ]),
                
                Tables\Filters\Filter::make('replied_at')
                    ->form([
                        Forms\Components\DatePicker::make('replied_from')
                            ->label('Replied From'),
                        Forms\Components\DatePicker::make('replied_until')
                            ->label('Replied Until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['replied_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('replied_at', '>=', $date),
                            )
                            ->when(
                                $data['replied_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('replied_at', '<=', $date),
                            );
                    }),
                
                Tables\Filters\TernaryFilter::make('is_private')
                    ->label('Private Response'),
            ])
            ->headerActions([
                // Tables\Actions\CreateAction::make()
                //     ->mutateFormDataUsing(function (array $data): array {
                //         $data['invitation_id'] = $this->getOwnerRecord()->id;
                //         if (empty($data['replied_at'])) {
                //             $data['replied_at'] = now();
                //         }
                //         return $data;
                //     }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('replied_at', 'desc');
    }
}