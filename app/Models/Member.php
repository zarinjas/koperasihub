<?php

namespace App\Models;

use App\Enums\MemberStatus;
use Database\Factories\MemberFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[UseFactory(MemberFactory::class)]
#[Fillable([
    'cooperative_id',
    'user_id',
    'member_no',
    'profile_photo_path',
    'full_name',
    'identity_no',
    'email',
    'phone',
    'address_line_1',
    'address_line_2',
    'city',
    'state',
    'postcode',
    'country',
    'date_of_birth',
    'gender',
    'occupation',
    'employer_name',
    'membership_status',
    'joined_at',
    'approved_at',
    'approved_by',
    'notes',
])]
class Member extends Model
{
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'joined_at' => 'datetime',
            'approved_at' => 'datetime',
            'membership_status' => MemberStatus::class,
        ];
    }

    public function cooperative(): BelongsTo
    {
        return $this->belongsTo(Cooperative::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    public function complaints(): HasMany
    {
        return $this->hasMany(Complaint::class);
    }

    public function approvedApplications(): HasMany
    {
        return $this->hasMany(MembershipApplication::class, 'approved_member_id');
    }

    public function scopeForCooperative(Builder $query, ?int $cooperativeId): Builder
    {
        return $query->when($cooperativeId, fn (Builder $query) => $query->where('cooperative_id', $cooperativeId));
    }

    public function scopeSearch(Builder $query, string $search): Builder
    {
        return $query->when($search !== '', function (Builder $query) use ($search): void {
            $query->where(function (Builder $query) use ($search): void {
                $query->where('member_no', 'like', "%{$search}%")
                    ->orWhere('full_name', 'like', "%{$search}%")
                    ->orWhere('identity_no', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        });
    }
}
