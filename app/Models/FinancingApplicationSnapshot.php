<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinancingApplicationSnapshot extends Model
{
    protected $fillable = [
        'cooperative_id',
        'financing_application_id',
        'financing_product_id',
        'product_snapshot_json',
        'sections_snapshot_json',
        'fields_snapshot_json',
        'document_templates_snapshot_json',
        'resolved_configuration_json',
    ];

    protected function casts(): array
    {
        return [
            'product_snapshot_json' => 'array',
            'sections_snapshot_json' => 'array',
            'fields_snapshot_json' => 'array',
            'document_templates_snapshot_json' => 'array',
            'resolved_configuration_json' => 'array',
        ];
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(FinancingApplication::class, 'financing_application_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(FinancingProduct::class, 'financing_product_id');
    }
}