<?php

namespace App\Filament\Resources;

use App\Filament\Forms\BarcodePdfSettingsForm;
use App\Filament\Resources\BarcodePdfTemplateResource\Pages;
use App\Models\BarcodePdfTemplate;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class BarcodePdfTemplateResource extends Resource
{
    protected static ?string $model = BarcodePdfTemplate::class;

    protected static ?string $navigationIcon = 'heroicon-o-squares-2x2';

    protected static ?string $navigationGroup = 'Pengaturan';

    protected static ?string $navigationLabel = 'Template Label PDF';

    protected static ?string $modelLabel = 'Template Label PDF';

    protected static ?string $pluralModelLabel = 'Template Label PDF';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->label('Nama Template')
                    ->placeholder('Contoh: Stiker Undangan 12 Label')
                    ->columnSpanFull(),

                Forms\Components\Toggle::make('is_default')
                    ->label('Jadikan Template Default')
                    ->helperText('Template default otomatis terpilih saat membuat batch baru.')
                    ->default(false)
                    ->columnSpanFull(),

                Forms\Components\Section::make('Ukuran Kertas & Margin')
                    ->description('Batas fisik kertas dan area aman. Margin = batas tepi card ke sisi kertas.')
                    ->columns(2)
                    ->schema(BarcodePdfSettingsForm::paperAndMarginFields()),

                Forms\Components\Section::make('Ukuran Card & Jarak Antar Card')
                    ->description('Batas card ke samping (lebar) & ke bawah (tinggi), plus jarak antar card.')
                    ->columns(2)
                    ->schema(BarcodePdfSettingsForm::labelAndGapFields()),

                Forms\Components\Section::make('Jumlah Card per Halaman')
                    ->columns(2)
                    ->schema(BarcodePdfSettingsForm::gridFields()),

                Forms\Components\Section::make('Style Card & Header')
                    ->columns(2)
                    ->schema(BarcodePdfSettingsForm::styleFields()),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Template')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\IconColumn::make('is_default')
                    ->label('Default')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->trueColor('success')
                    ->falseIcon('heroicon-o-x-circle')
                    ->falseColor('gray'),

                Tables\Columns\TextColumn::make('paper_width_mm')
                    ->label('Kertas')
                    ->formatStateUsing(fn ($state, $record) => "{$record->paper_width_mm} × {$record->paper_height_mm} mm")
                    ->badge(),

                Tables\Columns\TextColumn::make('columns')
                    ->label('Layout')
                    ->formatStateUsing(fn ($state, $record) => "{$record->columns} × {$record->rows}")
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('label_width_mm')
                    ->label('Card')
                    ->formatStateUsing(fn ($state, $record) => "{$record->label_width_mm} × {$record->label_height_mm} mm"),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Diperbarui')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
            ])
            ->filters([])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('name');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBarcodePdfTemplates::route('/'),
            'create' => Pages\CreateBarcodePdfTemplate::route('/create'),
            'edit' => Pages\EditBarcodePdfTemplate::route('/{record}/edit'),
        ];
    }
}
