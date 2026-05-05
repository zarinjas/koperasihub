<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'form_submission_id',
    'form_field_id',
    'field_key',
    'stored_path',
    'original_name',
    'mime_type',
    'file_size',
    'is_signature',
])]
class FormSubmissionFile extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'is_signature' => 'boolean',
        ];
    }

    public function submission(): BelongsTo
    {
        return $this->belongsTo(FormSubmission::class, 'form_submission_id');
    }

    public function field(): BelongsTo
    {
        return $this->belongsTo(FormField::class, 'form_field_id');
    }
}
