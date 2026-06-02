<?php

namespace App\Models;

use App\Enums\AnsuranPaymentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnsuranApplicationPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'ansuran_application_id',
        'month_number',
        'amount',
        'due_date',
        'paid_amount',
        'paid_date',
        'status',
        'payment_method',
        'reference_no',
        'notes',
        'recorded_by',
    ];

    protected function casts(): array
    {
        return [
            'status' => AnsuranPaymentStatus::class,
            'amount' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'due_date' => 'date',
            'paid_date' => 'date',
        ];
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(AnsuranApplication::class, 'ansuran_application_id');
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}