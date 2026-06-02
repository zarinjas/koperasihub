<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AnsuranProductVariant extends Model
{
    use HasFactory;

    protected $fillable = [
        'ansuran_product_id',
        'name',
        'sku',
        'price',
        'stock',
        'attributes',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'attributes' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(AnsuranProduct::class, 'ansuran_product_id');
    }

    public function applications(): HasMany
    {
        return $this->hasMany(AnsuranApplication::class, 'ansuran_product_variant_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->latest();
    }

    public function formattedPrice(): string
    {
        return 'RM '.number_format($this->price, 2);
    }
}