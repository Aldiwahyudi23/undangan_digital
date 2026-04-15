<?php

namespace App\Filament\Resources\InvitationResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use App\Models\FamilyMember;

class FamilyRelationManager extends RelationManager
{
    protected static string $relationship = 'familyMembers'; // 🔥 FIX relasi

    protected static ?string $recordTitleAttribute = 'name';

    public function form(Form $form): Form
    {
        return $form
            ->schema([

                Forms\Components\TextInput::make('name')
                    ->label('Nama')
                    ->required()
                    ->maxLength(255),

                Forms\Components\TextInput::make('role')
                    ->label('Peran')
                    ->placeholder('Contoh: Ayah, Ibu, Kakak'),

                Forms\Components\Select::make('group')
                    ->label('Kategori')
                    ->required()
                    ->options([
                        'bride_family' => 'Keluarga Mempelai Wanita',
                        'groom_family' => 'Keluarga Mempelai Pria',
                        'bride_invite' => 'Turut Mengundang Wanita',
                        'groom_invite' => 'Turut Mengundang Pria',
                    ]),

                Forms\Components\Toggle::make('is_core')
                    ->label('Keluarga Inti')
                    ->default(true)
                    ->helperText('Aktif = keluarga utama (bold di frontend)'),

                Forms\Components\TextInput::make('relation_label')
                    ->label('Keterangan')
                    ->placeholder('Contoh: Istri dari Kakak, Anak ke 2'),

                // order disembunyikan (auto)
                Forms\Components\TextInput::make('order')
                    ->hidden()
                    ->dehydrated(false),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')

            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama')
                    ->weight(fn ($record) => $record->is_core ? 'bold' : 'normal'),

                Tables\Columns\TextColumn::make('role')
                    ->label('Peran'),

                Tables\Columns\TextColumn::make('group')
                    ->label('Kategori')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'bride_family' => 'Wanita',
                        'groom_family' => 'Pria',
                        'bride_invite' => 'Turut Wanita',
                        'groom_invite' => 'Turut Pria',
                        default => $state,
                    }),

                Tables\Columns\IconColumn::make('is_core')
                    ->label('Inti')
                    ->boolean(),

                Tables\Columns\TextColumn::make('relation_label')
                    ->label('Keterangan')
                    ->limit(30),

                Tables\Columns\TextColumn::make('order')
                    ->label('Urutan'),
            ])

            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->using(function (array $data, string $model): FamilyMember {

                        // 🔥 AUTO ORDER
                        $maxOrder = FamilyMember::where('invitation_id', $this->getOwnerRecord()->id)
                            ->max('order');

                        $data['order'] = ($maxOrder ?? 0) + 1;

                        return $this->getOwnerRecord()
                            ->familyMembers()
                            ->create($data);
                    }),
            ])

            ->actions([
                Tables\Actions\EditAction::make(),

                Tables\Actions\DeleteAction::make(),
            ])

            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])

            // 🔥 DRAG & DROP ORDER
            ->reorderable('order')
            ->defaultSort('order', 'asc');
    }
}