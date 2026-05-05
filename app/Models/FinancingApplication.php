<?php

namespace App\Models;

use App\Enums\FinancingApplicationStatus;
use Database\Factories\FinancingApplicationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[UseFactory(FinancingApplicationFactory::class)]
#[Fillable([
    'cooperative_id',
    'unit_id',
    'reference_no',
    'member_id',
    'financing_category_id',
    'financing_product_id',
    'amount_requested',
    'tenure_months',
    'purpose',
    'monthly_income',
    'monthly_commitment',
    'employment_notes',
    'custom_answers_json',
    'completed_form_pdf_path',
    'completed_form_original_name',
    'completed_form_uploaded_at',
    'status',
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
])]
class FinancingApplication extends Model
{
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'amount_requested' => 'decimal:2',
            'monthly_income' => 'decimal:2',
            'monthly_commitment' => 'decimal:2',
            'approved_amount' => 'decimal:2',
            'completed_form_uploaded_at' => 'datetime',
            'submitted_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'custom_answers_json' => 'array',
            'status' => FinancingApplicationStatus::class,
        ];
    }

    public function cooperative(): BelongsTo
    {
        return $this->belongsTo(Cooperative::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(FinancingCategory::class, 'financing_category_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(FinancingProduct::class, 'financing_product_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function rejector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    public function canceller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function guarantors(): HasMany
    {
        return $this->hasMany(FinancingGuarantor::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(FinancingDocument::class);
    }

    public function histories(): HasMany
    {
        return $this->hasMany(FinancingApplicationHistory::class)->latest('id');
    }

    public function scopeForCooperative(Builder $query, ?int $cooperativeId): Builder
    {
        return $query->when($cooperativeId, fn (Builder $query) => $query->where('cooperative_id', $cooperativeId));
    }

    public function scopeSearch(Builder $query, string $search): Builder
    {
        return $query->when($search !== '', function (Builder $query) use ($search): void {
            $query->where(function (Builder $query) use ($search): void {
                $query->where('reference_no', 'like', "%{$search}%")
                    ->orWhereHas('member', fn (Builder $memberQuery) => $memberQuery
                        ->where('full_name', 'like', "%{$search}%")
                        ->orWhere('member_no', 'like', "%{$search}%"));
            });
        });
    }
}
