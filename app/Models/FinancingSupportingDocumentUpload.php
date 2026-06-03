<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinancingSupportingDocumentUpload extends Model
{
    protected $fillable = [
        'cooperative_id',
        'financing_application_id',
        'financing_supporting_document_id',
        'upload_index',
        'label',
        'file_path',
        'original_name',
        'mime_type',
        'file_size',
        'uploaded_at',
        'uploaded_by',
    ];

    protected function casts(): array
    {
        return [
            'upload_index' => 'integer',
            'file_size' => 'integer',
            'uploaded_at' => 'datetime',
        ];
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(FinancingApplication::class, 'financing_application_id');
    }

    public function supportingDocument(): BelongsTo
    {
        return $this->belongsTo(FinancingSupportingDocument::class, 'financing_supporting_document_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function getFileUrlAttribute(): ?string
    {
        return $this->file_path ? asset('storage/' . $this->file_path) : null;
    }
}
