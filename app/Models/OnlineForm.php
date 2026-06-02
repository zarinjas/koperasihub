<?php

namespace App\Models;

use App\Enums\FormStatus;
use App\Enums\FormSubmissionMethod;
use App\Enums\FormVisibility;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

#[Fillable([
    'cooperative_id',
    'form_category_id',
    'created_by',
    'title',
    'slug',
    'description',
    'visibility',
    'status',
    'success_message',
    'submission_method',
    'stamped_upload_instructions',
    'document_code',
    'revision_no',
    'effective_date',
    'document_title',
    'show_document_header',
])]
class OnlineForm extends Model
{
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'effective_date' => 'date',
            'show_document_header' => 'boolean',
            'visibility' => FormVisibility::class,
            'status' => FormStatus::class,
            'submission_method' => FormSubmissionMethod::class,
        ];
    }

    public function cooperative(): BelongsTo
    {
        return $this->belongsTo(Cooperative::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(FormCategory::class, 'form_category_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function sections(): HasMany
    {
        return $this->hasMany(FormSection::class)->latest();
    }

    public function fields(): HasMany
    {
        return $this->hasMany(FormField::class)->latest();
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(FormSubmission::class)->latest('submitted_at');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', FormStatus::Published->value);
    }

    public function scopePubliclyVisible(Builder $query): Builder
    {
        return $query->published();
    }

    protected function slug(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value) => filled($value) ? Str::slug($value) : null,
        );
    }
}