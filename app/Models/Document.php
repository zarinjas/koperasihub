<?php

namespace App\Models;

use App\Enums\DocumentStatus;
use App\Enums\DocumentVisibility;
use Database\Factories\DocumentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

#[UseFactory(DocumentFactory::class)]
#[Fillable([
    'cooperative_id',
    'document_category_id',
    'member_id',
    'uploaded_by',
    'title',
    'slug',
    'description',
    'file_path',
    'file_name',
    'mime_type',
    'file_size',
    'visibility',
    'status',
    'version',
    'published_at',
    'expires_at',
])]
class Document extends Model
{
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
            'expires_at' => 'datetime',
            'visibility' => DocumentVisibility::class,
            'status' => DocumentStatus::class,
        ];
    }

    public function cooperative(): BelongsTo
    {
        return $this->belongsTo(Cooperative::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(DocumentCategory::class, 'document_category_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query
            ->where('status', DocumentStatus::Published->value)
            ->where(function (Builder $query): void {
                $query->whereNull('published_at')
                    ->orWhere('published_at', '<=', now());
            })
            ->where(function (Builder $query): void {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }

    public function scopePubliclyVisible(Builder $query): Builder
    {
        return $query
            ->published()
            ->where('visibility', DocumentVisibility::Public->value);
    }

    protected function slug(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value) => filled($value) ? Str::slug($value) : null,
        );
    }
}
