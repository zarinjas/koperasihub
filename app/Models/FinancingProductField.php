<?php

namespace App\Models;

use App\Enums\FinancingFieldType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinancingProductField extends Model
{
    use HasFactory;

    protected $fillable = [
        'financing_product_id',
        'financing_product_section_id',
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
    ];

    protected function casts(): array
    {
        return [
            'type' => FinancingFieldType::class,
            'is_required' => 'boolean',
            'is_active' => 'boolean',
            'options_json' => 'array',
            'validation_json' => 'array',
            'settings_json' => 'array',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(FinancingProduct::class, 'financing_product_id');
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(FinancingProductSection::class, 'financing_product_section_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }

    public function getFileUrlAttribute(): ?string
    {
        $path = $this->settings_json['file_path'] ?? null;

        if (! $path) {
            return null;
        }

        return asset('storage/'.$path);
    }
}