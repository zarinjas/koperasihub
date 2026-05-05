<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'cooperative_id',
    'financing_product_id',
    'label',
    'field_key',
    'type',
    'placeholder',
    'help_text',
    'is_required',
    'options_json',
    'validation_json',
    'settings_json',
    'sort_order',
    'is_active',
])]
class FinancingProductField extends Model
{
    protected function casts(): array
    {
        return [
            'is_required' => 'boolean',
            'is_active' => 'boolean',
            'options_json' => 'array',
            'validation_json' => 'array',
            'settings_json' => 'array',
        ];
    }

    // Field types that are content blocks, not user-input fields.
    public const CONTENT_TYPES = ['instruction_text', 'note', 'rich_text'];

    public function cooperative(): BelongsTo
    {
        return $this->belongsTo(Cooperative::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(FinancingProduct::class, 'financing_product_id');
    }

    public function scopeForCooperative(Builder $query, ?int $cooperativeId): Builder
    {
        return $query->when($cooperativeId, fn (Builder $q) => $q->where('cooperative_id', $cooperativeId));
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function isContentBlock(): bool
    {
        return in_array($this->type, self::CONTENT_TYPES, true);
    }
}
