<?php

namespace App\Models;

use App\Enums\PageSectionType;
use Database\Factories\PageSectionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[UseFactory(PageSectionFactory::class)]
#[Fillable([
    'cooperative_id',
    'page_id',
    'type',
    'name',
    'data',
    'settings',
    'is_active',
    'created_by',
    'updated_by',
])]
class PageSection extends Model
{
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'data' => 'array',
            'settings' => 'array',
            'is_active' => 'boolean',
            'type' => PageSectionType::class,
        ];
    }

    public function cooperative(): BelongsTo
    {
        return $this->belongsTo(Cooperative::class);
    }

    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query
            ->where('is_active', true)
            ->latest();
    }
}