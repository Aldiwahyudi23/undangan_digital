<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class BarcodePdfTemplate extends Model
{
    protected $fillable = [
        'name',
        'paper_width_mm',
        'paper_height_mm',
        'margin_top_mm',
        'margin_right_mm',
        'margin_bottom_mm',
        'margin_left_mm',
        'label_width_mm',
        'label_height_mm',
        'gap_x_mm',
        'gap_y_mm',
        'columns',
        'rows',
        'corner_radius_mm',
        'border_width_mm',
        'border_style',
        'show_header',
        'header_height_mm',
        'is_default',
    ];

    protected $casts = [
        'paper_width_mm' => 'float',
        'paper_height_mm' => 'float',
        'margin_top_mm' => 'float',
        'margin_right_mm' => 'float',
        'margin_bottom_mm' => 'float',
        'margin_left_mm' => 'float',
        'label_width_mm' => 'float',
        'label_height_mm' => 'float',
        'gap_x_mm' => 'float',
        'gap_y_mm' => 'float',
        'columns' => 'integer',
        'rows' => 'integer',
        'corner_radius_mm' => 'float',
        'border_width_mm' => 'float',
        'show_header' => 'boolean',
        'header_height_mm' => 'float',
        'is_default' => 'boolean',
    ];

    public function scopeDefault(Builder $query): Builder
    {
        return $query->where('is_default', true);
    }

    public function settings(): array
    {
        return $this->only([
            'paper_width_mm',
            'paper_height_mm',
            'margin_top_mm',
            'margin_right_mm',
            'margin_bottom_mm',
            'margin_left_mm',
            'label_width_mm',
            'label_height_mm',
            'gap_x_mm',
            'gap_y_mm',
            'columns',
            'rows',
            'corner_radius_mm',
            'border_width_mm',
            'border_style',
            'show_header',
            'header_height_mm',
        ]);
    }
}
