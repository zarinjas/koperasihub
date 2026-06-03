<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FinancingSupportingDocument extends Model
{
    protected $fillable = [
        'cooperative_id',
        'financing_product_id',
        'name',
        'description',
        'mode',
        'count',
        'is_required',
        'accepted_types',
        'max_size_kb',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_required' => 'boolean',
            'is_active' => 'boolean',
            'count' => 'integer',
            'sort_order' => 'integer',
            'max_size_kb' => 'integer',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(FinancingProduct::class, 'financing_product_id');
    }

    public function uploads(): HasMany
    {
        return $this->hasMany(FinancingSupportingDocumentUpload::class);
    }

    public function cooperative(): BelongsTo
    {
        return $this->belongsTo(Cooperative::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }

    public function slotLabels(): array
    {
        if ($this->mode === 'monthly') {
            return collect(range(1, $this->count))
                ->map(fn ($i) => "{$this->name} {$i}/{$this->count}")
                ->all();
        }

        return ["{$this->name}"];
    }
}
