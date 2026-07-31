<?php

namespace App\Filament\Forms;

use App\Models\BarcodePdfTemplate;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Get;
use Filament\Forms\Set;

class BarcodePdfSettingsForm
{
    public static function defaults(): array
    {
        return [
            'template_id' => null,
            'paper_width_mm' => 210,
            'paper_height_mm' => 297,
            'margin_top_mm' => 6,
            'margin_right_mm' => 6,
            'margin_bottom_mm' => 6,
            'margin_left_mm' => 6,
            'label_width_mm' => 63.5,
            'label_height_mm' => 31.5,
            'gap_x_mm' => 2.5,
            'gap_y_mm' => 2.5,
            'columns' => 3,
            'rows' => 4,
            'corner_radius_mm' => 2,
            'border_width_mm' => 0.3,
            'border_style' => 'dashed',
            'show_header' => false,
            'header_height_mm' => 14,
        ];
    }

    public static function keys(): array
    {
        return array_keys(self::defaults());
    }

    public static function normalize(array $input): array
    {
        $settings = array_merge(
            self::defaults(),
            array_intersect_key($input, array_flip(self::keys()))
        );

        foreach ([
            'paper_width_mm', 'paper_height_mm',
            'margin_top_mm', 'margin_right_mm', 'margin_bottom_mm', 'margin_left_mm',
            'label_width_mm', 'label_height_mm',
            'gap_x_mm', 'gap_y_mm',
            'corner_radius_mm', 'border_width_mm',
            'header_height_mm',
        ] as $key) {
            $settings[$key] = (float) $settings[$key];
        }

        $settings['columns'] = max(1, (int) $settings['columns']);
        $settings['rows'] = max(1, (int) $settings['rows']);
        $settings['show_header'] = (bool) $settings['show_header'];
        $settings['template_id'] = filled($settings['template_id']) ? (int) $settings['template_id'] : null;

        $settings['border_style'] = in_array($settings['border_style'], ['solid', 'dashed', 'none'], true)
            ? $settings['border_style']
            : 'dashed';

        return $settings;
    }

    public static function schema(): array
    {
        return [
            Grid::make(4)
                ->schema([
                    Grid::make()
                        ->columns(2)
                        ->columnSpan(3)
                        ->schema([
                            Section::make('Template Tersimpan (Opsional)')
                                ->description('Pilih template untuk mengisi semua pengaturan di bawah secara otomatis.')
                                ->schema([
                                    self::templateSelectorField(),
                                ]),
                            Section::make('Ukuran Kertas & Margin')
                                ->description('Batas fisik kertas dan area aman. Margin = batas tepi card ke sisi kertas.')
                                ->columns(2)
                                ->schema(self::paperAndMarginFields()),
                            Section::make('Ukuran Card & Jarak Antar Card')
                                ->description('Batas card ke samping (lebar) & ke bawah (tinggi), plus jarak antar card.')
                                ->columns(2)
                                ->schema(self::labelAndGapFields()),
                            Section::make('Jumlah Card per Halaman')
                                ->columns(2)
                                ->schema(self::gridFields()),
                            Section::make('Style Card & Header')
                                ->columns(2)
                                ->schema(self::styleFields()),
                        ]),
                    Placeholder::make('preview')
                        ->label('Preview Layout')
                        ->columnSpan(1)
                        ->content(fn (Get $get) => view('pdf.batch-settings-preview', self::previewData($get))),
                ]),
        ];
    }

    public static function previewData(Get $get): array
    {
        $values = [];

        foreach (self::keys() as $key) {
            $values[$key] = $get($key);
        }

        return ['settings' => self::normalize($values)];
    }

    public static function templateSelectorField(): Select
    {
        return Select::make('template_id')
            ->label('Pilih Template')
            ->placeholder('Pilih template tersimpan…')
            ->options(fn () => BarcodePdfTemplate::query()->orderBy('name')->pluck('name', 'id')->toArray())
            ->searchable()
            ->preload()
            ->live()
            ->afterStateUpdated(function ($state, Set $set): void {
                if (blank($state)) {
                    return;
                }

                $template = BarcodePdfTemplate::find($state);

                if (! $template) {
                    return;
                }

                foreach ($template->settings() as $key => $value) {
                    $set($key, $value);
                }
            });
    }

    public static function paperAndMarginFields(): array
    {
        $defaults = self::defaults();

        return [
            TextInput::make('paper_width_mm')
                ->label('Lebar Kertas')
                ->numeric()->required()->step(0.5)->minValue(10)->maxValue(500)
                ->suffix('mm')->default($defaults['paper_width_mm'])->live(),
            TextInput::make('paper_height_mm')
                ->label('Tinggi Kertas')
                ->numeric()->required()->step(0.5)->minValue(10)->maxValue(500)
                ->suffix('mm')->default($defaults['paper_height_mm'])->live(),
            TextInput::make('margin_top_mm')
                ->label('Margin Atas')
                ->numeric()->step(0.5)->minValue(0)->maxValue(100)
                ->suffix('mm')->default($defaults['margin_top_mm'])->live(),
            TextInput::make('margin_right_mm')
                ->label('Margin Kanan')
                ->numeric()->step(0.5)->minValue(0)->maxValue(100)
                ->suffix('mm')->default($defaults['margin_right_mm'])->live(),
            TextInput::make('margin_bottom_mm')
                ->label('Margin Bawah')
                ->numeric()->step(0.5)->minValue(0)->maxValue(100)
                ->suffix('mm')->default($defaults['margin_bottom_mm'])->live(),
            TextInput::make('margin_left_mm')
                ->label('Margin Kiri')
                ->numeric()->step(0.5)->minValue(0)->maxValue(100)
                ->suffix('mm')->default($defaults['margin_left_mm'])->live(),
        ];
    }

    public static function labelAndGapFields(): array
    {
        $defaults = self::defaults();

        return [
            TextInput::make('label_width_mm')
                ->label('Lebar Card')
                ->numeric()->required()->step(0.5)->minValue(10)->maxValue(500)
                ->suffix('mm')->default($defaults['label_width_mm'])->live(),
            TextInput::make('label_height_mm')
                ->label('Tinggi Card')
                ->numeric()->required()->step(0.5)->minValue(10)->maxValue(500)
                ->suffix('mm')->default($defaults['label_height_mm'])->live(),
            TextInput::make('gap_x_mm')
                ->label('Jarak Antar Kolom (Gap X)')
                ->numeric()->step(0.5)->minValue(0)->maxValue(50)
                ->suffix('mm')->default($defaults['gap_x_mm'])->live(),
            TextInput::make('gap_y_mm')
                ->label('Jarak Antar Baris (Gap Y)')
                ->numeric()->step(0.5)->minValue(0)->maxValue(50)
                ->suffix('mm')->default($defaults['gap_y_mm'])->live(),
        ];
    }

    public static function gridFields(): array
    {
        $defaults = self::defaults();

        return [
            TextInput::make('columns')
                ->label('Jumlah Kolom')
                ->numeric()->required()->minValue(1)->maxValue(20)
                ->default($defaults['columns'])->live(),
            TextInput::make('rows')
                ->label('Jumlah Baris')
                ->numeric()->required()->minValue(1)->maxValue(50)
                ->default($defaults['rows'])->live(),
        ];
    }

    public static function styleFields(): array
    {
        $defaults = self::defaults();

        return [
            TextInput::make('corner_radius_mm')
                ->label('Radius Sudut')
                ->numeric()->step(0.5)->minValue(0)->maxValue(20)
                ->suffix('mm')->helperText('0 = kotak biasa')
                ->default($defaults['corner_radius_mm'])->live(),
            TextInput::make('border_width_mm')
                ->label('Ketebalan Border')
                ->numeric()->step(0.1)->minValue(0)->maxValue(5)
                ->suffix('mm')->default($defaults['border_width_mm'])->live(),
            Select::make('border_style')
                ->label('Style Border')
                ->options([
                    'dashed' => 'Dashed',
                    'solid' => 'Solid',
                    'none' => 'Tanpa Border',
                ])
                ->native(false)
                ->default($defaults['border_style'])->live(),
            Toggle::make('show_header')
                ->label('Tampilkan Header di PDF')
                ->helperText('Judul batch & nomor halaman di atas halaman')
                ->default($defaults['show_header'])->live(),
            TextInput::make('header_height_mm')
                ->label('Tinggi Header')
                ->numeric()->step(0.5)->minValue(0)->maxValue(100)
                ->suffix('mm')->default($defaults['header_height_mm'])->live(),
        ];
    }
}
