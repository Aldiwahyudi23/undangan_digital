<?php

namespace App\Filament\Resources\InvitationResource\RelationManagers;

use App\Models\Couple;
use App\Models\Image;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CouplesRelationManager extends RelationManager
{
    protected static string $relationship = 'couples';

    protected static ?string $recordTitleAttribute = 'full_name';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('full_name')
                    ->label('Nama Lengkap')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('nickname')
                    ->label('Nama Panggilan')
                    ->maxLength(255),

                Forms\Components\Select::make('gender')
                    ->label('Jenis Kelamin')
                    ->options(function (callable $get) {

                        // 🔥 gender yang sedang dipilih (edit mode)
                        $currentGender = $get('gender');

                        // 🔥 ambil semua gender yang sudah dipakai
                        $existingGenders = $this->getOwnerRecord()
                            ->couples
                            ->pluck('gender')
                            ->toArray();

                        $allOptions = [
                            'male' => 'Laki-laki',
                            'female' => 'Perempuan',
                        ];

                        // 🔥 hapus gender yang sudah dipakai KECUALI milik sendiri
                        foreach ($existingGenders as $gender) {
                            if ($gender !== $currentGender) {
                                unset($allOptions[$gender]);
                            }
                        }

                        return $allOptions;
                    })
                    ->required()
                    ->placeholder('Pilih Jenis Kelamin')
                    ->reactive(),

                Forms\Components\Select::make('image_id')
                    ->label('Foto')
                    ->options(function (callable $get) {

                        // 🔥 Ambil image_id yang sedang dipilih (edit / create)
                        $currentImageId = $get('image_id');

                        // 🔥 Ambil semua image yang sudah dipakai
                        $usedImageIds = Couple::whereNotNull('image_id')
                            ->when($currentImageId, function ($query) use ($currentImageId) {
                                $query->where('image_id', '!=', $currentImageId);
                            })
                            ->pluck('image_id')
                            ->toArray();

                        return Image::where('invitation_id', $this->getOwnerRecord()->id)
                            ->whereHas('placements', function (Builder $query) {
                                $query->where('placement', 'couple_photo');
                            })
                            ->whereNotIn('id', $usedImageIds)
                            ->pluck('title', 'id')
                            ->toArray();
                    })
                    ->searchable()
                    ->preload()
                    ->placeholder('Pilih foto pengantin')
                    ->helperText('Hanya menampilkan gambar yang belum digunakan'),

                Forms\Components\TextInput::make('father_name')
                    ->label('Nama Ayah')
                    ->maxLength(255),
                Forms\Components\TextInput::make('mother_name')
                    ->label('Nama Ibu')
                    ->maxLength(255),
                Forms\Components\TextInput::make('birth_order')
                    ->label('Anak ke-')
                    ->numeric(),
                Forms\Components\KeyValue::make('metadata')
                    ->label('Metadata')
                    ->reorderable()
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('full_name')
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
                Tables\Columns\TextColumn::make('full_name')
                    ->label('Nama Lengkap')
                    ->searchable(),
                Tables\Columns\TextColumn::make('nickname')
                    ->label('Panggilan'),
                Tables\Columns\TextColumn::make('gender')
                    ->label('Jenis Kelamin')
                    ->badge()
                    ->color(fn($state) => $state === 'male' ? 'primary' : 'danger')
                    ->formatStateUsing(fn($state) => $state === 'male' ? 'Laki-laki' : 'Perempuan'),
                Tables\Columns\TextColumn::make('father_name')
                    ->label('Ayah'),
                Tables\Columns\TextColumn::make('mother_name')
                    ->label('Ibu'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('gender')
                    ->options([
                        'male' => 'Laki-laki',
                        'female' => 'Perempuan',
                    ]),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Tambah Pengantin')
                    ->disabled(function (): bool {
                        // Sembunyikan tombol create jika sudah ada 2 data
                        return $this->getOwnerRecord()->couples()->count() >= 2;
                    })
                    ->tooltip(function () {
                        if ($this->getOwnerRecord()->couples()->count() >= 2) {
                            return 'Maksimal 2 data pengantin sudah tercapai';
                        }
                        return null;
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
            ]);
    }
}