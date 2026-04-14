<?php

namespace App\Filament\Resources\InvitationResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Image;
use Filament\Tables\Actions\Action;

class ImagesRelationManager extends RelationManager
{
    protected static string $relationship = 'images';

    protected static ?string $recordTitleAttribute = 'title';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\FileUpload::make('path')
                    ->label('File Gambar')
                    ->image()
                    ->disk('public')
                    ->visibility('public')
                    ->directory('images')
                    ->required()
                    ->columnSpanFull(),
                
                Forms\Components\TextInput::make('title')
                    ->label('Judul')
                    ->maxLength(255)
                    ->required(),
                
                Forms\Components\TextInput::make('theme')
                    ->label('Tema')
                    ->maxLength(255),
                
                Forms\Components\Textarea::make('note')
                    ->label('Deskripsi')
                    ->rows(3),
                
                Forms\Components\Select::make('placements')
                    ->label('Penempatan')
                    ->multiple()
                    ->options(function ($livewire, $record) {
                        $invitationId = $livewire->getOwnerRecord()->id;

                        $coverCount = \App\Models\ImagePlacement::where('placement', 'cover')
                            ->whereHas('image', fn ($q) => $q->where('invitation_id', $invitationId))
                            ->count();

                        $coupleCount = \App\Models\ImagePlacement::where('placement', 'couple_photo')
                            ->whereHas('image', fn ($q) => $q->where('invitation_id', $invitationId))
                            ->count();

                        $options = [
                            'cover' => 'Cover',
                            'video_cinematic' => 'video Cinematic ',
                            'video_story' => 'video story ',
                            'hero_slide' => 'Hero Slide',
                            'gallery' => 'Gallery',
                            'story_page' => 'Story Page',
                            'couple_photo' => 'Foto Pengantin',
                        ];

                        $currentPlacements = $record?->placements->pluck('placement')->toArray() ?? [];

                        // Cover logic
                        if ($coverCount >= 1 && !in_array('cover', $currentPlacements)) {
                            unset($options['cover']);
                        }

                        // Couple logic
                        if ($coupleCount >= 2 && !in_array('couple_photo', $currentPlacements)) {
                            unset($options['couple_photo']);
                        }

                        return $options;
                    })
                    // Perbaikan: handle untuk create dan edit
                    ->afterStateHydrated(function ($component, $record) {
                        if ($record) {
                            $component->state($record->placements->pluck('placement')->toArray());
                        }
                    })
                    ->saveRelationshipsUsing(function ($component, $state, $record) {
                        // Handle untuk create (record null) dan edit
                        if (!$record) {
                            // Simpan sementara ke state, akan diproses setelah record dibuat
                            $component->state($state);
                        } else {
                            // Update placements untuk edit
                            $record->placements()->delete();
                            if (is_array($state)) {
                                foreach ($state as $placement) {
                                    $record->placements()->create(['placement' => $placement]);
                                }
                            }
                        }
                    }),
                
                Forms\Components\TextInput::make('order')
                    ->label('Urutan')
                    ->numeric()
                    ->default(0)
                    ->hidden() // Disable karena akan otomatis
                    ->dehydrated(false), // Tidak perlu dikirim ke form
                
                Forms\Components\KeyValue::make('metadata')
                    ->label('Metadata')
                    ->reorderable()
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->columns([
                Tables\Columns\ImageColumn::make('path')
                    ->label('Gambar')
                    ->circular()
                    ->width(50)
                    ->height(50)
                    ->getStateUsing(function ($record) {
                        // Return full URL untuk gambar
                        return asset('storage/' . $record->path);
                    })
                     ->action(
                        Action::make('preview')
                            ->modalHeading('Preview Gambar')
                            ->modalSubmitAction(false) // hilangkan tombol submit
                            ->modalCancelActionLabel('Tutup')
                            ->modalWidth('lg')
                            ->modalContent(fn ($record) => view('filament.preview-image', [
                                'image' => asset('storage/' . $record->path),
                            ]))
                    ),
                Tables\Columns\TextColumn::make('title')
                    ->label('Judul')
                    ->searchable(),
                Tables\Columns\TextColumn::make('theme')
                    ->label('Tema')
                    ->searchable(),
                Tables\Columns\TextColumn::make('placements.placement')
                    ->label('Penempatan')
                    ->badge()
                    ->formatStateUsing(fn($state) => match($state) {
                        'cover' => 'Cover',
                        'hero_slide' => 'Hero Slide',
                        'gallery' => 'Gallery',
                        'story_page' => 'Story Page',
                        'couple_photo' => 'Foto Pengantin',
                        default => $state,
                    })
                    ->listWithLineBreaks(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('placement')
                    ->relationship('placements', 'placement')
                    ->options([
                        'cover' => 'Cover',
                        'hero_slide' => 'Hero Slide',
                        'gallery' => 'Gallery',
                        'story_page' => 'Story Page',
                        'couple_photo' => 'Foto Pengantin',
                    ]),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->using(function (array $data, string $model): Image {
                        // Handle custom create dengan placements
                        $placements = $data['placements'] ?? [];
                        unset($data['placements']);
                        
                        // Set order otomatis
                        $maxOrder = Image::where('invitation_id', $this->getOwnerRecord()->id)->max('order');
                        $data['order'] = $maxOrder + 1;
                        
                        // Create image
                        $image = $this->getOwnerRecord()->images()->create($data);
                        
                        // Create placements
                        if (is_array($placements)) {
                            foreach ($placements as $placement) {
                                $image->placements()->create(['placement' => $placement]);
                            }
                        }
                        
                        return $image;
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->using(function (Image $record, array $data): Image {
                        // Handle edit dengan placements
                        $placements = $data['placements'] ?? [];
                        unset($data['placements']);
                        
                        // Update image
                        $record->update($data);
                        
                        // Update placements
                        $record->placements()->delete();
                        if (is_array($placements)) {
                            foreach ($placements as $placement) {
                                $record->placements()->create(['placement' => $placement]);
                            }
                        }
                        
                        return $record;
                    }),
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