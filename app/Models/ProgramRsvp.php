<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProgramRsvp extends Model
{
    protected $fillable = [
        'cooperative_id',
        'program_id',
        'member_id',
        'response',
        'responded_at',
        'checked_in_at',
        'checked_in_by',
        'attendance_method',
        'notes',
    ];

    protected $casts = [
        'responded_at' => 'datetime',
        'checked_in_at' => 'datetime',
    ];

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function checkedInBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checked_in_by');
    }
}