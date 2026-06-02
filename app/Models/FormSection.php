<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'online_form_id',
    'title',
    'description',
    'page_break_before',
    'is_active',
])]
class FormSection extends Model
{
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'page_break_before' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function form(): BelongsTo
    {
        return $this->belongsTo(OnlineForm::class, 'online_form_id');
    }

    public function fields(): HasMany
    {
        return $this->hasMany(FormField::class)->latest();
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}