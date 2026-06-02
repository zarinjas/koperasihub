<?php

namespace App\Models;

use App\Enums\AnsuranApplicationStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class AnsuranApplication extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'cooperative_id',
        'unit_id',
        'member_id',
        'ansuran_product_id',
        'ansuran_product_variant_id',
        'ansuran_tenure_option_id',
        'ansuran_agreement_template_id',
        'application_no',
        'full_price',
        'down_payment',
        'financed_amount',
        'interest_rate_percent',
        'tenure_months',
        'monthly_amount',
        'total_payable',
        'status',
        'delivery_method',
        'delivery_address',
        'delivery_status',
        'delivery_tracking_no',
        'agreement_content',
        'signed_agreement_content',
        'signed_at',
        'notes',
        'admin_notes',
        'rejection_reason',
        'reviewed_by',
        'reviewed_at',
        'approved_by',
        'approved_at',
        'rejected_by',
        'rejected_at',
        'cancelled_by',
        'cancelled_at',
        'cancellation_reason',
    ];

    protected function casts(): array
    {
        return [
            'status' => AnsuranApplicationStatus::class,
            'full_price' => 'decimal:2',
            'down_payment' => 'decimal:2',
            'financed_amount' => 'decimal:2',
            'interest_rate_percent' => 'decimal:2',
            'monthly_amount' => 'decimal:2',
            'total_payable' => 'decimal:2',
            'signed_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
            'cancelled_at' => 'datetime',
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

    public function product(): BelongsTo
    {
        return $this->belongsTo(AnsuranProduct::class, 'ansuran_product_id');
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(AnsuranProductVariant::class, 'ansuran_product_variant_id');
    }

    public function tenureOption(): BelongsTo
    {
        return $this->belongsTo(AnsuranTenureOption::class, 'ansuran_tenure_option_id');
    }

    public function agreementTemplate(): BelongsTo
    {
        return $this->belongsTo(AnsuranAgreementTemplate::class, 'ansuran_agreement_template_id');
    }

    public function guarantors(): HasMany
    {
        return $this->hasMany(AnsuranApplicationGuarantor::class);
    }

    public function histories(): HasMany
    {
        return $this->hasMany(AnsuranApplicationHistory::class)->orderBy('created_at');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(AnsuranApplicationPayment::class)->orderBy('month_number');
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

    public function scopeForCooperative($query, $cooperativeId)
    {
        return $query->where('cooperative_id', $cooperativeId);
    }

    public function scopeForMember($query, $memberId)
    {
        return $query->where('member_id', $memberId);
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }
}