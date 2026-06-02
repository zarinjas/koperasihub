<?php

namespace App\Models;

use App\Enums\PageStatus;
use App\Enums\PageTemplate;
use Database\Factories\PageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

#[UseFactory(PageFactory::class)]
#[Fillable([
    'cooperative_id',
    'title',
    'slug',
    'template',
    'summary',
    'status',
    'meta_title',
    'meta_description',
    'featured_image_path',
    'published_at',
    'created_by',
    'updated_by',
])]
class Page extends Model
{
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
            'status' => PageStatus::class,
            'template' => PageTemplate::class,
        ];
    }

    public function featuredImageUrl(): ?string
    {
        if (! $this->featured_image_path) {
            return null;
        }

        return Storage::disk('public')->url($this->featured_image_path);
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

    public function sections(): HasMany
    {
        return $this->hasMany(PageSection::class)->latest();
    }

    public function activeSections(): HasMany
    {
        return $this->sections()->active();
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query
            ->where('status', PageStatus::Published->value)
            ->where(function (Builder $query): void {
                $query->whereNull('published_at')
                    ->orWhere('published_at', '<=', now());
            });
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
            'downloads',
            'services',
            'announcements',
            'perkhidmatan',
            'pengumuman',
        ];
    }

    protected function slug(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value) => filled($value) ? Str::slug($value) : null,
        );
    }
}