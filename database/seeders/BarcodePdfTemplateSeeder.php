<?php

namespace Database\Seeders;

use App\Models\BarcodePdfTemplate;
use Illuminate\Database\Seeder;

class BarcodePdfTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $presets = [
            [
                'name' => 'Label A4 3×4 (63,5 × 31,5 mm)',
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
                'is_default' => true,
            ],
            [
                'name' => 'Label A4 2×7 (100 × 37 mm)',
                'paper_width_mm' => 210,
                'paper_height_mm' => 297,
                'margin_top_mm' => 5,
                'margin_right_mm' => 5,
                'margin_bottom_mm' => 5,
                'margin_left_mm' => 5,
                'label_width_mm' => 100,
                'label_height_mm' => 37,
                'gap_x_mm' => 2,
                'gap_y_mm' => 2,
                'columns' => 2,
                'rows' => 7,
                'corner_radius_mm' => 2,
                'border_width_mm' => 0.3,
                'border_style' => 'dashed',
                'show_header' => false,
                'header_height_mm' => 14,
                'is_default' => false,
            ],
            [
                'name' => 'Stiker 198 × 136 mm (3×4)',
                'paper_width_mm' => 198,
                'paper_height_mm' => 136,
                'margin_top_mm' => 3,
                'margin_right_mm' => 3,
                'margin_bottom_mm' => 3,
                'margin_left_mm' => 3,
                'label_width_mm' => 63.5,
                'label_height_mm' => 31.5,
                'gap_x_mm' => 0.5,
                'gap_y_mm' => 0.5,
                'columns' => 3,
                'rows' => 4,
                'corner_radius_mm' => 2,
                'border_width_mm' => 0.3,
                'border_style' => 'solid',
                'show_header' => false,
                'header_height_mm' => 12,
                'is_default' => false,
            ],
            [
                'name' => 'Label Termal 50 × 25 mm (2×5)',
                'paper_width_mm' => 110,
                'paper_height_mm' => 140,
                'margin_top_mm' => 3,
                'margin_right_mm' => 3,
                'margin_bottom_mm' => 3,
                'margin_left_mm' => 3,
                'label_width_mm' => 50,
                'label_height_mm' => 25,
                'gap_x_mm' => 2,
                'gap_y_mm' => 1,
                'columns' => 2,
                'rows' => 5,
                'corner_radius_mm' => 1,
                'border_width_mm' => 0.3,
                'border_style' => 'dashed',
                'show_header' => false,
                'header_height_mm' => 10,
                'is_default' => false,
            ],
            [
                'name' => 'Undangan Besar 90 × 55 mm (1×1)',
                'paper_width_mm' => 90,
                'paper_height_mm' => 55,
                'margin_top_mm' => 3,
                'margin_right_mm' => 3,
                'margin_bottom_mm' => 3,
                'margin_left_mm' => 3,
                'label_width_mm' => 84,
                'label_height_mm' => 49,
                'gap_x_mm' => 0,
                'gap_y_mm' => 0,
                'columns' => 1,
                'rows' => 1,
                'corner_radius_mm' => 3,
                'border_width_mm' => 0.5,
                'border_style' => 'solid',
                'show_header' => false,
                'header_height_mm' => 10,
                'is_default' => false,
            ],
        ];

        foreach ($presets as $preset) {
            BarcodePdfTemplate::firstOrCreate(
                ['name' => $preset['name']],
                $preset
            );
        }

        $this->command->info('Template label PDF berhasil dibuat.');
    }
}
