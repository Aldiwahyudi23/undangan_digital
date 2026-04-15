<?php

namespace App\Filament\Resources\InvitationResource\RelationManagers;

use App\Models\Couple;
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

                Forms\Components\TextInput::make('share_whatsapp')
                    ->required()
                    ->label('No Whatsapp'),

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

                Tables\Columns\TextColumn::make('share_whatsapp')
                    ->label('Share WhatsApp')
                    ->formatStateUsing(fn ($state) => $state ? 'Ya' : 'Tidak'),

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

                        $link = url('https://undangan-api.keluargamahaya.com/invitation/' . $record->uuid . '?token=' . $record->token);

                        \Filament\Notifications\Notification::make()
                            ->title('Link Undangan')
                            ->body($link)
                            ->success()
                            ->send();
                    }),
Tables\Actions\Action::make('share_whatsapp')
    ->label('Share WA')
    ->icon('heroicon-o-paper-airplane')
    ->color('success')
    ->url(function ($record) {

        // 🔥 Format nomor (hapus 0 depan → jadi 62)
        $phone = $record->share_whatsapp;

        // bersihin karakter aneh (spasi, -, dll)
        $phone = preg_replace('/[^0-9]/', '', $phone);

        if (substr($phone, 0, 1) === '0') {
            $phone = '62' . substr($phone, 1);
        }

        $male = Couple::firstWhere('gender', 'male');
        $female = Couple::firstWhere('gender', 'female');

        // 🔗 Link undangan
        $link = 'https://wedding-mira-aldi.keluargamahaya.com/invitation/' 
            . $record->uuid 
            . '?token=' . $record->token;

        // 💌 Pesan FULL (lebih niat 😎)
        $message =
        "*Assalamualaikum Wr. Wb.* 🙏\n\n" .

        "Tanpa mengurangi rasa hormat, kami mengundang Bapak/Ibu/Saudara/i:\n\n" .

        "*{$record->name}*\n\n" .

        "Untuk menghadiri acara pernikahan kami:\n\n" .

        "💍 *{$male->nickname} & {$female->nickname}*\n\n" .

        "Yang InsyaAllah akan dilaksanakan pada:\n\n" .

        "📅 *13 september 2026*\n" .
        "⏰ *09:00 WIB*\n" .
        "📍 *Cigawir*\n\n" .

        "Berikut link undangan digital kami:\n" .
        "$link\n\n" .

        "Merupakan suatu kehormatan dan kebahagiaan bagi kami apabila Bapak/Ibu/Saudara/i berkenan hadir dan memberikan doa restu.\n\n" .

        "Atas kehadiran dan doa restunya kami ucapkan terima kasih 🙏\n\n" .

        "*Wassalamualaikum Wr. Wb.*";

        return 'https://wa.me/' . $phone . '?text=' . rawurlencode($message);
    })
    ->openUrlInNewTab()
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
