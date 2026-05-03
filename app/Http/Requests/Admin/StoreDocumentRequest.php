<?php

namespace App\Http\Requests\Admin;

use App\Enums\DocumentStatus;
use App\Enums\DocumentVisibility;
use App\Models\DocumentCategory;
use App\Support\AccessControl;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(AccessControl::PERMISSION_CREATE_DOCUMENTS) ?? false;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'document_category_id' => [
                'nullable',
                'integer',
                Rule::exists(DocumentCategory::class, 'id')
                    ->where(fn ($query) => $query->where('cooperative_id', $this->user()?->cooperative_id)),
            ],
            'visibility' => ['required', Rule::in([
                DocumentVisibility::Public->value,
                DocumentVisibility::MembersOnly->value,
                DocumentVisibility::AdminOnly->value,
            ])],
            'status' => ['required', Rule::in(DocumentStatus::values())],
            'version' => ['nullable', 'string', 'max:80'],
            'published_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date', 'after_or_equal:published_at'],
            'file' => ['required', 'file', 'mimes:pdf,doc,docx,xls,xlsx,ppt,pptx', 'max:10240'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Tajuk dokumen diperlukan.',
            'visibility.required' => 'Tahap akses dokumen diperlukan.',
            'status.required' => 'Status dokumen diperlukan.',
            'file.required' => 'Sila pilih fail dokumen.',
            'file.mimes' => 'Format fail tidak disokong.',
            'file.max' => 'Saiz fail melebihi had yang dibenarkan.',
            'expires_at.after_or_equal' => 'Tarikh tamat mesti sama atau selepas tarikh terbit.',
        ];
    }
}
