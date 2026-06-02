<?php

namespace App\Models;

use App\Enums\NewsStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

#[Fillable([
    'cooperative_id',
    'title',
    'slug',
    'excerpt',
    'content',
    'image_path',
    'category',
    'status',
    'published_at',
    'created_by',
    'updated_by',
])]
class News extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'news';

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
            'status' => NewsStatus::class,
        ];
    }

    public function imageUrl(): ?string
    {
        if (! $this->image_path) {
            return null;
        }

        return Storage::disk('public')->url($this->image_path);
    }

    public function cooperative(): BelongsTo
    {
        return $this->belongsTo(Cooperative::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query
            ->where('status', NewsStatus::Published->value)
            ->where(function (Builder $query): void {
                $query->whereNull('published_at')
                    ->orWhere('published_at', '<=', now());
            });
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query
            ->orderByDesc('published_at')
            ->orderByDesc('created_at');
    }

    public function scopeForPublicSlug(Builder $query, string $slug): Builder
    {
        return $query->published()->where('slug', Str::slug($slug));
    }

    public static function reservedSlugs(): array
    {
        return [
            'admin',
            'member',
            'api',
            'login',
            'register',
            'dashboard',
            'storage',
            'assets',
            'services',
            'perkhidmatan',
            'announcements',
            'pengumuman',
            'downloads',
            'muat-turun',
            'berita',
        ];
    }

    protected function slug(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value) => filled($value) ? Str::slug($value) : null,
        );
    }
}