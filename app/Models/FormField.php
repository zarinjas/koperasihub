<?php

namespace App\Models;

use App\Enums\FormFieldDisplayMode;
use App\Enums\FormFieldType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

#[Fillable([
    'online_form_id',
    'form_section_id',
    'label',
    'field_key',
    'type',
    'placeholder',
    'help_text',
    'is_required',
    'options_json',
    'validation_json',
    'settings_json',
    'is_active',
])]
class FormField extends Model
{
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'is_required' => 'boolean',
            'is_active' => 'boolean',
            'options_json' => 'array',
            'validation_json' => 'array',
            'settings_json' => 'array',
            'type' => FormFieldType::class,
        ];
    }

    public function form(): BelongsTo
    {
        return $this->belongsTo(OnlineForm::class, 'online_form_id');
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(FormSection::class, 'form_section_id');
    }

    public function submissionFiles(): HasMany
    {
        return $this->hasMany(FormSubmissionFile::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function setFieldKeyAttribute(?string $value): void
    {
        $key = filled($value) ? Str::snake($value) : Str::snake($this->attributes['label'] ?? 'field');
        $this->attributes['field_key'] = trim($key, '_');
    }

    public function displayMode(): FormFieldDisplayMode
    {
        $settings = $this->settings_json ?? [];
        $displayMode = $settings['display_mode'] ?? null;

        if (is_string($displayMode) && in_array($displayMode, FormFieldDisplayMode::values(), true)) {
            return FormFieldDisplayMode::from($displayMode);
        }

        if (($settings['print_only'] ?? false) === true) {
            return FormFieldDisplayMode::PrintOnly;
        }

        return FormFieldDisplayMode::OnlineAndPrint;
    }

    public function showsOnline(): bool
    {
        return $this->displayMode()->showsOnline();
    }

    public function showsPrint(): bool
    {
        return $this->displayMode()->showsPrint();
    }
}