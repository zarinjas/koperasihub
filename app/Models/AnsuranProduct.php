<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class AnsuranProduct extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'cooperative_id',
        'ansuran_category_id',
        'name',
        'slug',
        'description',
        'min_down_payment_percent',
        'guarantor_count',
        'status',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'min_down_payment_percent' => 'decimal:2',
        ];
    }

    public function cooperative(): BelongsTo
    {
        return $this->belongsTo(Cooperative::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(AnsuranCategory::class, 'ansuran_category_id');
    }

    public function images(): HasMany
    {
        return $this->hasMany(AnsuranProductImage::class, 'ansuran_product_id')->latest();
    }

    public function variants(): HasMany
    {
        return $this->hasMany(AnsuranProductVariant::class, 'ansuran_product_id')->latest();
    }

    public function applications(): HasMany
    {
        return $this->hasMany(AnsuranApplication::class, 'ansuran_product_id');
    }

    public function primaryImage(): ?AnsuranProductImage
    {
        return $this->images->where('is_primary', true)->first() ?? $this->images->first();
    }

    public function scopeForCooperative($query, $cooperativeId)
    {
        return $query->where('cooperative_id', $cooperativeId);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'aktif');
    }

    public function scopeOrdered($query)
    {
        return $query->latest();
    }

    protected static function booted(): void
    {
        static::creating(function (AnsuranProduct $product) {
            if (! $product->slug) {
                $product->slug = Str::slug($product->name);
            }
        });
    }
}
