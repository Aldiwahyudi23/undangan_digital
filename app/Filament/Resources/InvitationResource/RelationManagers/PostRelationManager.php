<?php

namespace App\Filament\Resources\InvitationResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Post;
use App\Models\InvitationGuest;

class PostRelationManager extends RelationManager
{
    protected static string $relationship = 'posts'; // Pastikan relasi ini ada di model Invitation
    
    protected static ?string $title = 'Moment & Status';
    
    protected static ?string $label = 'Post';
    
    protected static ?string $pluralLabel = 'Posts';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('invitation_guest_id')
                    ->label('Dibuat Oleh')
                    ->relationship('guest', 'name') // Sesuaikan dengan field nama di InvitationGuest
                    ->required()
                    ->searchable()
                    ->preload()
                    ->disabled(), // Tidak bisa diubah karena hanya edit/hapus
                
                Forms\Components\Select::make('type')
                    ->label('Tipe')
                    ->options([
                        'moment' => 'Moment',
                        'status' => 'Status',
                    ])
                    ->required()
                    ->native(false),
                
                Forms\Components\Textarea::make('caption')
                    ->label('Caption')
                    ->maxLength(65535)
                    ->rows(3)
                    ->columnSpanFull(),
                
                            Forms\Components\SpatieMediaLibraryFileUpload::make('media')
                ->label('Gambar/Video')
                ->collection('post_media')
                ->multiple()
                ->image() // Untuk gambar
                // Video tetap bisa upload tanpa method video()
                ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/jpg', 'image/webp', 'video/mp4', 'video/mov', 'video/avi'])
                ->maxSize(10240) // 10MB
                ->imageResizeTargetWidth('800')
                ->imageResizeTargetHeight('800')
                ->columnSpanFull(),

            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('caption')
            ->columns([
                Tables\Columns\TextColumn::make('guest.name')
                    ->label('Dibuat Oleh')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('type')
                    ->label('Tipe')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'moment' => 'success',
                        'status' => 'info',
                        default => 'secondary',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'moment' => '📸 Moment',
                        'status' => '💬 Status',
                        default => ucfirst($state),
                    }),
                
                Tables\Columns\SpatieMediaLibraryImageColumn::make('media')
                    ->label('Preview')
                    ->collection('post_media')
                    ->limit(3)
                    ->square()
                    ->height(50)
                    ->width(50),
                
                Tables\Columns\TextColumn::make('caption')
                    ->label('Caption')
                    ->limit(100)
                    ->searchable(),
                
                Tables\Columns\TextColumn::make('media_count')
                    ->label('Media')
                    ->formatStateUsing(fn ($record) => $record->getMedia('post_media')->count() . ' file')
                    ->badge()
                    ->color('primary'),
                
                Tables\Columns\TextColumn::make('likes_count')
                    ->label('👍 Likes')
                    ->counts('likes')
                    ->sortable()
                    ->badge()
                    ->color('success'),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(),
                
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Diperbarui')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Tipe')
                    ->options([
                        'moment' => 'Moment',
                        'status' => 'Status',
                    ]),
                
                Tables\Filters\SelectFilter::make('invitation_guest_id')
                    ->label('Dibuat Oleh')
                    ->relationship('guest', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->headerActions([
                // Tidak ada CreateAction karena hanya edit dan hapus
                // Tapi jika Anda ingin tetap bisa menambah, bisa ditambahkan:
                // Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('Edit')
                    ->modalHeading('Edit Post')
                    ->mutateFormDataUsing(function (array $data, $record): array {
                        // Pastikan invitation_guest_id tidak berubah
                        $data['invitation_id'] = $record->invitation_id;
                        return $data;
                    }),
                
                Tables\Actions\DeleteAction::make()
                    ->label('Hapus')
                    ->modalHeading('Hapus Post')
                    ->modalDescription('Post, semua like, dan media akan dihapus secara permanen. Apakah Anda yakin?')
                    ->action(function ($record) {
                        // Hapus media terlebih dahulu
                        $record->clearMediaCollection('post_media');
                        // Hapus post (cascade akan menghapus likes)
                        $record->delete();
                    }),
                
                Tables\Actions\ViewAction::make()
                    ->label('Lihat')
                    ->modalHeading('Detail Post')
                    ->mutateRecordDataUsing(function (array $data, $record): array {
                        $data['media_preview'] = $record->getMedia('post_media');
                        return $data;
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Hapus Terpilih')
                        ->modalHeading('Hapus Post Terpilih')
                        ->modalDescription('Post, like, dan media yang terpilih akan dihapus permanen')
                        ->action(function ($records) {
                            foreach ($records as $record) {
                                $record->clearMediaCollection('post_media');
                                $record->delete();
                            }
                        }),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}