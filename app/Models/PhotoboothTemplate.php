<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Spatie\EloquentSortable\Sortable;
use Spatie\EloquentSortable\SortableTrait;

class PhotoboothTemplate extends Model implements Sortable
{
    use HasUuids;
    use SortableTrait;

    protected $table = 'photobooth_templates';

    public $sortable = [
        'order_column_name' => 'sort_order',
        'sort_when_creating' => true,
    ];

    protected $fillable = [
        'invitation_id',
        'uuid',
        'title',
        'slug',
        'frame_image',
        'thumbnail',
        'layout',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'layout' => 'array',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (PhotoboothTemplate $template) {
            $template->slug = $template->slug ?: Str::slug($template->title);
        });
    }

    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    public function invitation()
    {
        return $this->belongsTo(Invitation::class);
    }

    public function slots(): array
    {
        return $this->layout['slots'] ?? [];
    }

    public static function dimensionsOf(?string $path): ?array
    {
        if (blank($path)) {
            return null;
        }

        try {
            $fullPath = Storage::disk('public')->path($path);

            if (! is_file($fullPath)) {
                return null;
            }

            $size = @getimagesize($fullPath);

            return $size === false ? null : [$size[0], $size[1]];
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function frameDimensions(): ?array
    {
        return self::dimensionsOf($this->frame_image);
    }

    public function frameImageUrl(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->frame_image ? (str_starts_with($this->frame_image, 'http') ? $this->frame_image : Storage::disk('public')->url($this->frame_image)) : null,
        );
    }

    public function thumbnailUrl(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->thumbnail ? (str_starts_with($this->thumbnail, 'http') ? $this->thumbnail : Storage::disk('public')->url($this->thumbnail)) : null,
        );
    }

    public function buildSortQuery()
    {
        return static::query()->where('invitation_id', $this->invitation_id);
    }
}
