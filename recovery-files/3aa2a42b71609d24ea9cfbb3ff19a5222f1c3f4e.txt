<?php

namespace App\Models;

use App\Enums\ProgramStatus;
use App\Enums\ProgramType;
use Database\Factories\ProgramFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[UseFactory(ProgramFactory::class)]
class Program extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'cooperative_id',
        'title',
        'slug',
        'description',
        'category',
        'program_type',
        'location',
        'online_url',
        'capacity',
        'start_date',
        'end_date',
        'registration_deadline',
        'cover_image_path',
        'status',
        'is_featured',
        'sort_order',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_featured' => 'boolean',
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'registration_deadline' => 'datetime',
        'capacity' => 'integer',
        'sort_order' => 'integer',
    ];

    public function cooperative(): BelongsTo
    {
        return $this->belongsTo(Cooperative::class);
    }

    public function rsvps(): HasMany
    {
        return $this->hasMany(ProgramRsvp::class);
    }

    public function confirmedAttendees(): HasMany
    {
        return $this->hasMany(ProgramRsvp::class)->whereNotNull('checked_in_at');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function scopeForCooperative(Builder $query, ?int $cooperativeId): Builder
    {
        return $query->when($cooperativeId, fn (Builder $query) => $query->where('cooperative_id', $cooperativeId));
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', ProgramStatus::Published->value);
    }

    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->where('start_date', '>=', now());
    }

    public function scopePast(Builder $query): Builder
    {
        return $query->where('start_date', '<', now());
    }
}
