<?php

namespace App\Models;

use App\Enums\ServiceStatus;
use Database\Factories\ServiceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

#[UseFactory(ServiceFactory::class)]
#[Fillable([
    'cooperative_id',
    'title',
    'slug',
    'category',
    'summary',
    'description',
    'image_path',
    'icon',
    'contact_name',
    'contact_phone',
    'contact_email',
    'whatsapp',
    'button_text',
    'button_url',
    'status',
    'is_featured',
    'created_by',
    'updated_by',
])]
class Service extends Model
{
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'is_featured' => 'boolean',
            'status' => ServiceStatus::class,
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
        return $query->where('status', ServiceStatus::Published->value);
    }

    public function scopeForPublicSlug(Builder $query, string $slug): Builder
    {
        return $query->published()->where('slug', Str::slug($slug));
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->latest();
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
        ];
    }

    protected function slug(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value) => filled($value) ? Str::slug($value) : null,
        );
    }
}