<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinancingApplicationDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'cooperative_id',
        'financing_application_id',
        'financing_product_field_id',
        'uploaded_by',
        'label',
        'field_key',
        'file_path',
        'original_name',
        'mime_type',
        'file_size',
    ];

    public function application(): BelongsTo
    {
        return $this->belongsTo(FinancingApplication::class, 'financing_application_id');
    }

    public function productField(): BelongsTo
    {
        return $this->belongsTo(FinancingProductField::class, 'financing_product_field_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function downloadUrl(): string
    {
        return route('admin.financing.applications.documents.download', [
            'application' => $this->financing_application_id,
            'document' => $this->id,
        ]);
    }
}