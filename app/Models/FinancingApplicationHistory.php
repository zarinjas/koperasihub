<?php

namespace App\Models;

use Database\Factories\FinancingApplicationHistoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[UseFactory(FinancingApplicationHistoryFactory::class)]
#[Fillable([
    'cooperative_id',
    'financing_application_id',
    'actor_id',
    'action',
    'from_status',
    'to_status',
    'notes',
    'metadata',
])]
class FinancingApplicationHistory extends Model
{
    use HasFactory;

    public $timestamps = true;

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
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

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
