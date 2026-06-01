<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class FinancingProduct extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'cooperative_id',
        'financing_category_id',
        'name',
        'slug',
        'description',
        'min_amount',
        'max_amount',
        'min_tenure_months',
        'max_tenure_months',
        'annual_rate_percent',
        'rate_tiers_json',
        'rate_image_path',
        'form_template_path',
        'form_template_name',
        'rate_note',
        'requires_guarantor',
        'guarantor_count',
        'requires_stamped_upload',
        'stamped_upload_instructions',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'min_amount' => 'decimal:2',
            'max_amount' => 'decimal:2',
            'annual_rate_percent' => 'decimal:2',
            'rate_tiers_json' => 'array',
            'requires_guarantor' => 'boolean',
            'requires_stamped_upload' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function cooperative(): BelongsTo
    {
        return $this->belongsTo(Cooperative::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(FinancingCategory::class, 'financing_category_id');
    }

    public function sections(): HasMany
    {
        return $this->hasMany(FinancingProductSection::class)->orderBy('sort_order');
    }

    public function fields(): HasMany
    {
        return $this->hasMany(FinancingProductField::class)->latest();
    }

    public function applications(): HasMany
    {
        return $this->hasMany(FinancingApplication::class);
    }

    public function documentTemplates(): HasMany
    {
        return $this->hasMany(FinancingDocumentTemplate::class, 'financing_product_id')->orderBy('sort_order');
    }

    public function scopeForCooperative($query, $cooperativeId)
    {
        return $query->where('cooperative_id', $cooperativeId);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->latest();
    }

    public function rateImageUrl(): ?string
    {
        if (! $this->rate_image_path) {
            return null;
        }

        return asset('storage/'.$this->rate_image_path);
    }

    public function resolveRate(?int $tenureMonths): ?float
    {
        $tiers = $this->rate_tiers_json;

        if (! empty($tiers) && $tenureMonths !== null) {
            foreach ($tiers as $tier) {
                $min = (int) ($tier['min_months'] ?? 0);
                $max = (int) ($tier['max_months'] ?? 0);
                if ($tenureMonths >= $min && $tenureMonths <= $max) {
                    return (float) ($tier['rate_percent'] ?? 0);
                }
            }
        }

        if ($this->annual_rate_percent !== null) {
            return (float) $this->annual_rate_percent;
        }

        return null;
    }
}
