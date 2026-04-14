<?php

namespace App\Filament\Resources\InvitationResource\RelationManagers;

use App\Models\Image;
use App\Models\Story;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class StoriesRelationManager extends RelationManager
{
    protected static string $relationship = 'stories';

    protected static ?string $recordTitleAttribute = 'title';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('title')
                    ->label('Judul Cerita')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Select::make('image_id')
                    ->label('Foto')
                    ->required()
                    ->options(function (callable $get) {

                        // 🔥 Ambil image_id yang sedang dipilih (edit / create)
                        $currentImageId = $get('image_id');

                        // 🔥 Ambil semua image yang sudah dipakai
                        $usedImageIds = Story::whereNotNull('image_id')
                            ->when($currentImageId, function ($query) use ($currentImageId) {
                                $query->where('image_id', '!=', $currentImageId);
                            })
                            ->pluck('image_id')
                            ->toArray();

                        return Image::where('invitation_id', $this->getOwnerRecord()->id)
                            ->whereHas('placements', function (Builder $query) {
                                $query->where('placement', 'story_page');
                            })
                            ->whereNotIn('id', $usedImageIds)
                            ->pluck('title', 'id')
                            ->toArray();
                    })
                    ->searchable()
                    ->preload()
                    ->placeholder('Pilih foto pengantin')
                    ->helperText('Hanya menampilkan gambar yang belum digunakan'),
                Forms\Components\Textarea::make('description')
                    ->label('Deskripsi')
                    ->rows(4)
                    ->required()
                    ->columnSpanFull(),
                Forms\Components\DatePicker::make('date_event')
                    ->label('Tanggal Kejadian'),
                Forms\Components\TextInput::make('order')
                    ->label('Urutan')
                    ->numeric()
                    ->hidden()
                    ->default(0),
                Forms\Components\Toggle::make('is_featured')
                    ->label('Unggulkan')
                    ->default(false),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
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
                Tables\Columns\TextColumn::make('title')
                    ->label('Judul')
                    ->searchable(),
                Tables\Columns\TextColumn::make('description')
                    ->label('Deskripsi')
                    ->limit(50),
                Tables\Columns\TextColumn::make('date_event')
                    ->label('Tanggal')
                    ->date('d M Y'),
                Tables\Columns\IconColumn::make('is_featured')
                    ->label('Unggulan')
                    ->boolean(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_featured')
                    ->label('Unggulan'),
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
            ])
            // Tambahkan reorder column untuk drag and drop
            ->reorderable('order')
            ->defaultSort('order', 'asc');
    }
}