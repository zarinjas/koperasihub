<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinancingDocumentEvent extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'cooperative_id',
        'financing_application_id',
        'financing_generated_document_id',
        'actor_id',
        'action',
        'from_status',
        'to_status',
        'notes',
        'metadata_json',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'metadata_json' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(FinancingGeneratedDocument::class, 'financing_generated_document_id');
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(FinancingApplication::class, 'financing_application_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
