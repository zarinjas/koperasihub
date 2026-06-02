<?php

namespace App\Models;

use App\Enums\FinancingGuarantorStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinancingGuarantor extends Model
{
    use HasFactory;

    protected $fillable = [
        'cooperative_id',
        'financing_application_id',
        'guarantor_member_id',
        'status',
        'rejection_reason',
        'signature_path',
        'responded_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => FinancingGuarantorStatus::class,
            'responded_at' => 'datetime',
        ];
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(FinancingApplication::class, 'financing_application_id');
    }

    public function guarantorMember(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'guarantor_member_id');
    }
}