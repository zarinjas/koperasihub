<?php

namespace App\Models;

use App\Enums\AnnouncementAudience;
use App\Enums\AnnouncementStatus;
use Database\Factories\AnnouncementFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

#[UseFactory(AnnouncementFactory::class)]
#[Fillable([
    'cooperative_id',
    'title',
    'slug',
    'summary',
    'content',
    'image_path',
    'audience',
    'status',
    'is_pinned',
    'send_notification',
    'send_email',
    'published_at',
    'expires_at',
    'created_by',
    'updated_by',
])]
class Announcement extends Model
{
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'is_pinned' => 'boolean',
            'send_notification' => 'boolean',
            'send_email' => 'boolean',
            'published_at' => 'datetime',
            'expires_at' => 'datetime',
            'audience' => AnnouncementAudience::class,
            'status' => AnnouncementStatus::class,
        ];
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

    public function imageUrl(): ?string
    {
        if (! $this->image_path) {
            return null;
        }

        return Storage::disk('public')->url($this->image_path);
    }

    public function specificMembers(): BelongsToMany
    {
        return $this->belongsToMany(Member::class, 'announcement_member');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query
            ->where('status', AnnouncementStatus::Published->value)
            ->where(function (Builder $query): void {
                $query->whereNull('published_at')
                    ->orWhere('published_at', '<=', now());
            })
            ->where(function (Builder $query): void {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }

    public function scopePublicAudience(Builder $query): Builder
    {
        return $query->where('audience', AnnouncementAudience::Public->value);
    }

    public function scopeVisibleToPublic(Builder $query): Builder
    {
        return $query->published()->publicAudience();
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query
            ->orderByDesc('is_pinned')
            ->orderByDesc('published_at')
            ->orderByDesc('created_at');
    }

    public function scopeForPublicSlug(Builder $query, string $slug): Builder
    {
        return $query->visibleToPublic()->where('slug', Str::slug($slug));
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