<?php

namespace App\Models;

use App\Enums\MediaVisibility;
use Database\Factories\MediaFileFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

#[UseFactory(MediaFileFactory::class)]
#[Fillable([
    'cooperative_id',
    'uploaded_by',
    'disk',
    'path',
    'original_name',
    'file_name',
    'mime_type',
    'extension',
    'size',
    'visibility',
    'collection',
    'alt_text',
    'caption',
    'metadata',
])]
class MediaFile extends Model
{
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'visibility' => MediaVisibility::class,
        ];
    }

    public function cooperative(): BelongsTo
    {
        return $this->belongsTo(Cooperative::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function scopePublic(Builder $query): Builder
    {
        return $query->where('visibility', MediaVisibility::Public->value);
    }

    public function publicUrl(): ?string
    {
        if ($this->disk !== 'public') {
            return null;
        }

        return Storage::disk($this->disk)->url($this->path);
    }
}
