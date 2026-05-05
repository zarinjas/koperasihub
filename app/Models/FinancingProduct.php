<?php

namespace App\Models;

use Database\Factories\FinancingProductFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

#[UseFactory(FinancingProductFactory::class)]
#[Fillable([
    'cooperative_id',
    'financing_category_id',
    'unit_id',
    'name',
    'slug',
    'description',
    'eligibility_terms',
    'product_terms',
    'application_notes',
    'application_instructions',
    'required_documents_note',
    'officer_contact_name',
    'officer_contact_phone',
    'officer_contact_email',
    'consent_pdf_path',
    'consent_pdf_name',
    'undertaking_pdf_path',
    'undertaking_pdf_name',
    'guide_pdf_path',
    'guide_pdf_name',
    'official_form_template_pdf_path',
    'official_form_template_pdf_name',
    'min_amount',
    'max_amount',
    'min_tenure_months',
    'max_tenure_months',
    'rate_image_path',
    'annual_rate_percent',
    'rate_note',
    'requires_guarantor',
    'guarantor_count',
    'required_documents_json',
    'is_active',
    'sort_order',
    'created_by',
    'updated_by',
])]
class FinancingProduct extends Model
{
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'min_amount' => 'decimal:2',
            'max_amount' => 'decimal:2',
            'annual_rate_percent' => 'decimal:2',
            'requires_guarantor' => 'boolean',
            'required_documents_json' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public const PRODUCT_DOCUMENTS = [
        'consent' => [
            'path' => 'consent_pdf_path',
            'name' => 'consent_pdf_name',
            'label' => 'Dokumen Consent / Persetujuan',
            'download_label' => 'Muat Turun Dokumen Consent',
        ],
        'undertaking' => [
            'path' => 'undertaking_pdf_path',
            'name' => 'undertaking_pdf_name',
            'label' => 'Letter of Undertaking',
            'download_label' => 'Muat Turun Letter of Undertaking',
        ],
        'guide' => [
            'path' => 'guide_pdf_path',
            'name' => 'guide_pdf_name',
            'label' => 'Dokumen Panduan',
            'download_label' => 'Muat Turun Panduan Permohonan',
        ],
        'official_form_template' => [
            'path' => 'official_form_template_pdf_path',
            'name' => 'official_form_template_pdf_name',
            'label' => 'Template Borang Rasmi',
            'download_label' => 'Muat Turun Template Borang Rasmi',
        ],
    ];

    public function cooperative(): BelongsTo
    {
        return $this->belongsTo(Cooperative::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(FinancingCategory::class, 'financing_category_id');
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function applications(): HasMany
    {
        return $this->hasMany(FinancingApplication::class);
    }

    public function productFields(): HasMany
    {
        return $this->hasMany(FinancingProductField::class)->orderBy('sort_order')->orderBy('id');
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
