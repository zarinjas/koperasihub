<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

#[Fillable([
    'cooperative_id',
    'name',
    'slug',
    'description',
    'is_active',
    'created_by',
    'updated_by',
])]
class Unit extends Model
{
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
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

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function financingProducts(): HasMany
    {
        return $this->hasMany(FinancingProduct::class);
    }

    public function financingApplications(): HasMany
    {
        return $this->hasMany(FinancingApplication::class);
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