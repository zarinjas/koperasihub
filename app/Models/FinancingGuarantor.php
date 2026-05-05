<?php

namespace App\Models;

use App\Enums\FinancingGuarantorStatus;
use Database\Factories\FinancingGuarantorFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[UseFactory(FinancingGuarantorFactory::class)]
#[Fillable([
    'cooperative_id',
    'financing_application_id',
    'guarantor_member_id',
    'status',
    'consent_text',
    'consented_at',
    'signature_path',
    'rejection_reason',
    'responded_at',
])]
class FinancingGuarantor extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'status' => FinancingGuarantorStatus::class,
            'consented_at' => 'datetime',
            'responded_at' => 'datetime',
        ];
    }

    public function cooperative(): BelongsTo
    {
        return $this->belongsTo(Cooperative::class);
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
