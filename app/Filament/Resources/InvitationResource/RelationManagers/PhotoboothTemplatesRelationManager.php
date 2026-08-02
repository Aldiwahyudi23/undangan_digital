<?php

namespace App\Filament\Resources\InvitationResource\RelationManagers;

use App\Models\PhotoboothTemplate;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class PhotoboothTemplatesRelationManager extends RelationManager
{
    protected static string $relationship = 'photoboothTemplates';

    protected static ?string $recordTitleAttribute = 'title';

    protected static ?string $title = 'Template Photo Booth';

    protected static ?string $label = 'Template';

    protected static ?string $pluralLabel = 'Template Photo Booth';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('title')
                    ->label('Judul Template')
                    ->required()
                    ->maxLength(255)
                    ->reactive()
                    ->afterStateUpdated(function (Forms\Set $set, ?string $state) {
                        $set('slug', Str::slug($state ?: ''));
                    }),
                Forms\Components\TextInput::make('slug')
                    ->label('Slug')
                    ->required()
                    ->maxLength(255),

                Forms\Components\FileUpload::make('frame_image')
                    ->label('Gambar Frame')
                    ->image()
                    ->disk('public')
                    ->directory('photobooth/frames')
                    ->required()
                    ->live()
                    ->helperText('Gambar frame Photo Booth. Slot akan diposisikan di atas gambar ini sesuai koordinat.')
                    ->columnSpanFull(),

                Forms\Components\FileUpload::make('thumbnail')
                    ->label('Thumbnail')
                    ->image()
                    ->disk('public')
                    ->directory('photobooth/thumbs')
                    ->columnSpanFull(),

                Forms\Components\Toggle::make('is_active')
                    ->label('Aktif')
                    ->default(true),

                Forms\Components\Repeater::make('slots')
                    ->label('Slot Posisi')
                    ->statePath('layout.slots')
                    ->schema([
                        Forms\Components\TextInput::make('index')
                            ->label('Index')
                            ->numeric()
                            ->default(1)
                            ->required()
                            ->helperText('Nomor urut slot. Ditampilkan sebagai label (#1, #2, dst) di preview.')
                            ->live(),
                        Forms\Components\Select::make('shape')
                            ->label('Bentuk')
                            ->options([
                                'rect' => 'Rect',
                                'circle' => 'Circle',
                            ])
                            ->default('rect')
                            ->required()
                            ->helperText('Bentuk area foto. Rect = persegi panjang, Circle = lingkaran.')
                            ->live(),
                        Forms\Components\TextInput::make('x')
                            ->label('X')
                            ->numeric()
                            ->default(0)
                            ->required()
                            ->helperText('Posisi dari tepi KIRI frame (dalam pixel). Semakin besar nilainya, slot makin ke KANAN.')
                            ->live(),
                        Forms\Components\TextInput::make('y')
                            ->label('Y')
                            ->numeric()
                            ->default(0)
                            ->required()
                            ->helperText('Posisi dari tepi ATAS frame (dalam pixel). Semakin besar nilainya, slot makin ke BAWAH.')
                            ->live(),
                        Forms\Components\TextInput::make('width')
                            ->label('Lebar')
                            ->numeric()
                            ->default(350)
                            ->required()
                            ->helperText('Lebar area foto (dalam pixel) dari kiri ke kanan.')
                            ->live(),
                        Forms\Components\TextInput::make('height')
                            ->label('Tinggi')
                            ->numeric()
                            ->default(350)
                            ->required()
                            ->helperText('Tinggi area foto (dalam pixel) dari atas ke bawah.')
                            ->live(),
                        Forms\Components\TextInput::make('rotation')
                            ->label('Rotasi (°)')
                            ->numeric()
                            ->default(0)
                            ->helperText('Perputaran area foto dalam derajat. Contoh: 90 = diputar seperempat putaran.')
                            ->live(),
                        Forms\Components\TextInput::make('radius')
                            ->label('Radius Sudut (opsional)')
                            ->numeric()
                            ->nullable()
                            ->helperText('Lengkungan sudut slot (dalam pixel). 0 = sudut kotak siku, semakin besar semakin membulat. Kosongkan untuk sudut kotak.')
                            ->live(),
                    ])
                    ->collapsible()
                    ->cloneable()
                    ->live()
                    ->addActionLabel('+ Tambah Slot')
                    ->itemLabel(fn (array $state): ?string => 'Slot #' . ($state['index'] ?? '-'))
                    ->default([
                        ['index' => 1, 'shape' => 'rect', 'x' => 120, 'y' => 180, 'width' => 350, 'height' => 500, 'rotation' => 0, 'radius' => 20],
                    ])
                    ->columnSpanFull(),

                Forms\Components\Placeholder::make('slot_preview')
                    ->label('Preview Posisi Slot')
                    ->columnSpanFull()
                    ->content(function (Forms\Get $get) {
                        $frame = $get('frame_image');
                        if (is_array($frame)) {
                            $frame = array_values($frame)[0] ?? null;
                        }

                        [$width, $height] = array_pad(
                            $frame ? (PhotoboothTemplate::dimensionsOf($frame) ?? [null, null]) : [null, null],
                            2,
                            null,
                        );

                        return view('filament.partials.photobooth-slot-preview', [
                            'slots' => $get('layout.slots') ?? [],
                            'frame' => $frame,
                            'frameWidth' => $width,
                            'frameHeight' => $height,
                        ]);
                    }),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->columns([
                Tables\Columns\ImageColumn::make('frame_image')
                    ->label('Frame')
                    ->width(70)
                    ->height(70)
                    ->getStateUsing(fn ($record) => $record->frame_image
                        ? (str_starts_with($record->frame_image, 'http') ? $record->frame_image : asset('storage/' . ltrim($record->frame_image, '/')))
                        : null),
                Tables\Columns\TextColumn::make('title')
                    ->label('Judul')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('slug')
                    ->label('Slug')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('slots_count')
                    ->label('Jumlah Slot')
                    ->badge()
                    ->color('primary')
                    ->getStateUsing(fn ($record) => count($record->slots())),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Status Aktif'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
                Tables\Actions\Action::make('download_photobooth_pdf')
                    ->label('Download Poster QR Photo Booth (PDF)')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('success')
                    ->action(fn () => \App\Services\PhotoboothPdfService::download($this->getOwnerRecord())),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->modalContent(fn ($record) => view('filament.photobooth-template-review', ['template' => $record])),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->reorderable('sort_order')
            ->defaultSort('sort_order');
    }
}
