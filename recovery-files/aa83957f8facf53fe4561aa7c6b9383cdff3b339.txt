<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FinancingGeneratedDocument extends Model
{
    use HasFactory;

    public const STATUS_PENDING_GENERATION = 'pending_generation';
    public const STATUS_GENERATED = 'generated';
    public const STATUS_PENDING_UPLOAD = 'pending_upload';
    public const STATUS_UPLOADED = 'uploaded';
    public const STATUS_VERIFIED = 'verified';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_NOT_REQUIRED = 'not_required';

    public const STATUSES = [
        self::STATUS_PENDING_GENERATION,
        self::STATUS_GENERATED,
        self::STATUS_PENDING_UPLOAD,
        self::STATUS_UPLOADED,
        self::STATUS_VERIFIED,
        self::STATUS_REJECTED,
        self::STATUS_NOT_REQUIRED,
    ];

    protected $fillable = [
        'cooperative_id',
        'financing_application_id',
        'financing_document_template_id',
        'document_code',
        'document_name',
        'document_type',
        'source_type',
        'status',
        'requires_upload',
        'requires_verification',
        'generated_path',
        'uploaded_path',
        'uploaded_original_name',
        'mime_type',
        'file_size',
        'generated_at',
        'downloaded_at',
        'uploaded_at',
        'verified_by',
        'verified_at',
        'rejected_by',
        'rejected_at',
        'rejection_reason',
        'metadata_json',
    ];

    protected function casts(): array
    {
        return [
            'requires_upload' => 'boolean',
            'requires_verification' => 'boolean',
            'generated_at' => 'datetime',
            'downloaded_at' => 'datetime',
            'uploaded_at' => 'datetime',
            'verified_at' => 'datetime',
            'rejected_at' => 'datetime',
            'metadata_json' => 'array',
        ];
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(FinancingApplication::class, 'financing_application_id');
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(FinancingDocumentTemplate::class, 'financing_document_template_id');
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function rejecter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    public function events(): HasMany
    {
        return $this->hasMany(FinancingDocumentEvent::class, 'financing_generated_document_id')->orderBy('created_at');
    }
}
