<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class FinancingDocumentTemplate extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'cooperative_id',
        'financing_product_id',
        'code',
        'name',
        'type',
        'source_type',
        'requires_upload',
        'requires_verification',
        'template_path',
        'html_template',
        'settings_json',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'requires_upload' => 'boolean',
            'requires_verification' => 'boolean',
            'settings_json' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(FinancingProduct::class, 'financing_product_id');
    }

    public function generatedDocuments(): HasMany
    {
        return $this->hasMany(FinancingGeneratedDocument::class, 'financing_document_template_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }
}
