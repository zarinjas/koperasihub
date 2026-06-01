<?php

namespace App\Models;

use App\Enums\AnsuranGuarantorStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnsuranApplicationGuarantor extends Model
{
    use HasFactory;

    protected $fillable = [
        'cooperative_id',
        'ansuran_application_id',
        'guarantor_member_id',
        'status',
        'rejection_reason',
        'responded_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => AnsuranGuarantorStatus::class,
            'responded_at' => 'datetime',
        ];
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(AnsuranApplication::class, 'ansuran_application_id');
    }

    public function guarantorMember(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'guarantor_member_id');
    }
}
