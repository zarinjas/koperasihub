<?php

namespace App\Models;

use App\Enums\FinancingApplicationStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class FinancingApplication extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'cooperative_id',
        'unit_id',
        'member_id',
        'financing_category_id',
        'financing_product_id',
        'reference_no',
        'amount_requested',
        'tenure_months',
        'purpose',
        'monthly_income',
        'monthly_commitment',
        'employment_notes',
        'custom_answers_json',
        'status',
        'admin_notes',
        'stamped_form_path',
        'stamped_form_original_name',
        'stamped_form_uploaded_at',
        'submitted_at',
        'reviewed_by',
        'reviewed_at',
        'approved_amount',
        'approved_tenure_months',
        'decision_notes',
        'approved_by',
        'approved_at',
        'rejected_by',
        'rejected_at',
        'rejection_reason',
        'cancelled_by',
        'cancelled_at',
        'cancellation_reason',
    ];

    protected function casts(): array
    {
        return [
            'status' => FinancingApplicationStatus::class,
            'amount_requested' => 'decimal:2',
            'monthly_income' => 'decimal:2',
            'monthly_commitment' => 'decimal:2',
            'approved_amount' => 'decimal:2',
            'custom_answers_json' => 'array',
            'submitted_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'stamped_form_uploaded_at' => 'datetime',
        ];
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(FinancingCategory::class, 'financing_category_id');
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(FinancingProduct::class, 'financing_product_id');
    }

    public function guarantors(): HasMany
    {
        return $this->hasMany(FinancingGuarantor::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(FinancingApplicationDocument::class);
    }

    public function generatedDocuments(): HasMany
    {
        return $this->hasMany(FinancingGeneratedDocument::class);
    }

    public function supportingDocumentUploads(): HasMany
    {
        return $this->hasMany(FinancingSupportingDocumentUpload::class);
    }

    public function histories(): HasMany
    {
        return $this->hasMany(FinancingApplicationHistory::class)->orderBy('created_at');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function rejecter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    public function canceller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function snapshot(): HasOne
    {
        return $this->hasOne(FinancingApplicationSnapshot::class, 'financing_application_id');
    }

    public function scopeForCooperative($query, $cooperativeId)
    {
        return $query->where('cooperative_id', $cooperativeId);
    }

    public function scopeForMember($query, $memberId)
    {
        return $query->where('member_id', $memberId);
    }

    public function stampedFormUrl(): ?string
    {
        if (! $this->stamped_form_path) {
            return null;
        }

        return asset('storage/'.$this->stamped_form_path);
    }
}