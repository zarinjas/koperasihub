<?php

namespace App\Models;

use App\Enums\FinancingCategoryType;
use Database\Factories\FinancingCategoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

#[UseFactory(FinancingCategoryFactory::class)]
#[Fillable([
    'cooperative_id',
    'name',
    'slug',
    'description',
    'type',
    'rate_image_path',
    'is_active',
    'sort_order',
    'created_by',
    'updated_by',
])]
class FinancingCategory extends Model
{
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'type' => FinancingCategoryType::class,
            'is_active' => 'boolean',
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

    public function products(): HasMany
    {
        return $this->hasMany(FinancingProduct::class);
    }

    public function applications(): HasMany
    {
        return $this->hasMany(FinancingApplication::class);
    }

    public function scopeForCooperative(Builder $query, ?int $cooperativeId): Builder
    {
        return $query->when($cooperativeId, fn (Builder $query) => $query->where('cooperative_id', $cooperativeId));
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function setSlugAttribute(?string $value): void
    {
        $this->attributes['slug'] = filled($value)
            ? Str::slug($value)
            : Str::slug($this->attributes['name'] ?? '');
    }
}
