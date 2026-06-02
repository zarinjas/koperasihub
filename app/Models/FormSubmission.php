<?php

namespace App\Models;

use App\Enums\FormSubmissionStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'cooperative_id',
    'online_form_id',
    'unit_id',
    'unit_name_snapshot',
    'member_id',
    'reviewed_by',
    'approved_by',
    'rejected_by',
    'reference_no',
    'submitted_by_name',
    'submitted_by_email',
    'data_json',
    'status',
    'admin_notes',
    'stamped_file_path',
    'stamped_file_original_name',
    'stamped_file_uploaded_at',
    'submitted_at',
    'reviewed_at',
    'approved_at',
    'rejected_at',
])]
class FormSubmission extends Model
{
    use HasFactory, SoftDeletes;

    public $timestamps = true;

    protected function casts(): array
    {
        return [
            'data_json' => 'array',
            'submitted_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
            'stamped_file_uploaded_at' => 'datetime',
            'status' => FormSubmissionStatus::class,
        ];
    }

    public function cooperative(): BelongsTo
    {
        return $this->belongsTo(Cooperative::class);
    }

    public function form(): BelongsTo
    {
        return $this->belongsTo(OnlineForm::class, 'online_form_id');
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function rejector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    public function files(): HasMany
    {
        return $this->hasMany(FormSubmissionFile::class);
    }
}