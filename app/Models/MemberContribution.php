<?php

namespace App\Models;

use Database\Factories\MemberContributionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[UseFactory(MemberContributionFactory::class)]
#[Fillable([
    'cooperative_id',
    'member_id',
    'year',
    'caruman_semasa',
    'caruman_keseluruhan',
    'dividen',
    'notes',
    'created_by',
    'updated_by',
])]
class MemberContribution extends Model
{
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'caruman_semasa' => 'decimal:2',
            'caruman_keseluruhan' => 'decimal:2',
            'dividen' => 'decimal:2',
        ];
    }

    public function cooperative(): BelongsTo
    {
        return $this->belongsTo(Cooperative::class);
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function scopeForCooperative(Builder $query, ?int $cooperativeId): Builder
    {
        return $query->when($cooperativeId, fn (Builder $query) => $query->where('cooperative_id', $cooperativeId));
    }

    public function scopeYear(Builder $query, int $year): Builder
    {
        return $query->where('year', $year);
    }

    public function scopeSearch(Builder $query, string $search): Builder
    {
        return $query->when($search !== '', function (Builder $query) use ($search): void {
            $query->whereHas('member', fn (Builder $q) => $q->where('member_no', 'like', "%{$search}%")
                ->orWhere('full_name', 'like', "%{$search}%"));
        });
    }
}