<?php

namespace App\Models;

use App\Enums\ReferralCommissionStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'cooperative_id',
    'referrer_member_id',
    'referred_member_id',
    'membership_application_id',
    'commission_amount',
    'status',
    'eligible_at',
    'paid_at',
    'paid_by',
    'payment_notes',
    'metadata',
])]
class ReferralCommission extends Model
{
    use SoftDeletes;

    protected function casts(): array
    {
        return [
            'commission_amount' => 'decimal:2',
            'eligible_at' => 'datetime',
            'paid_at' => 'datetime',
            'metadata' => 'array',
            'status' => ReferralCommissionStatus::class,
        ];
    }

    public function cooperative(): BelongsTo
    {
        return $this->belongsTo(Cooperative::class);
    }

    public function referrer(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'referrer_member_id');
    }

    public function referredMember(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'referred_member_id');
    }

    public function membershipApplication(): BelongsTo
    {
        return $this->belongsTo(MembershipApplication::class);
    }

    public function paidBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'paid_by');
    }

    public function scopeForCooperative(Builder $query, ?int $cooperativeId): Builder
    {
        return $query->when($cooperativeId, fn (Builder $query) => $query->where('cooperative_id', $cooperativeId));
    }

    public function scopeByStatus(Builder $query, string|array $status): Builder
    {
        $statuses = (array) $status;

        return $query->whereIn('status', $statuses);
    }

    public function scopeSearch(Builder $query, string $search): Builder
    {
        return $query->when($search !== '', function (Builder $query) use ($search): void {
            $query->where(function (Builder $query) use ($search): void {
                $query->whereHas('referrer', fn ($q) => $q->where('full_name', 'like', "%{$search}%")
                    ->orWhere('member_no', 'like', "%{$search}%"))
                    ->orWhereHas('referredMember', fn ($q) => $q->where('full_name', 'like', "%{$search}%")
                        ->orWhere('member_no', 'like', "%{$search}%"))
                    ->orWhereHas('membershipApplication', fn ($q) => $q->where('application_no', 'like', "%{$search}%"));
            });
        });
    }
}
