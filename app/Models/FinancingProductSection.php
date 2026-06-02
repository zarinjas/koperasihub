<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FinancingProductSection extends Model
{
    use HasFactory;

    protected $fillable = [
        'financing_product_id',
        'title',
        'description',
        'page_break_before',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'page_break_before' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(FinancingProduct::class, 'financing_product_id');
    }

    public function fields(): HasMany
    {
        return $this->hasMany(FinancingProductField::class, 'financing_product_section_id')->orderBy('sort_order');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }
}