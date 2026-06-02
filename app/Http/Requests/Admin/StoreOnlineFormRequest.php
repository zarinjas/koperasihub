<?php

namespace App\Http\Requests\Admin;

use App\Enums\FormStatus;
use App\Enums\FormSubmissionMethod;
use App\Enums\FormVisibility;
use App\Models\FormCategory;
use App\Models\OnlineForm;
use App\Support\AccessControl;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StoreOnlineFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(AccessControl::PERMISSION_CREATE_FORMS) ?? false;
    }

    public function rules(): array
    {
        return [
            'form_category_id' => [
                'nullable',
                'integer',
                Rule::exists(FormCategory::class, 'id')
                    ->where(fn ($query) => $query->where('cooperative_id', $this->user()?->cooperative_id)),
            ],
            'title' => ['required', 'string', 'max:255'],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique(OnlineForm::class, 'slug')
                    ->where(fn ($query) => $query->where('cooperative_id', $this->user()?->cooperative_id)),
            ],
            'description' => ['nullable', 'string', 'max:2000'],
            'visibility' => ['required', Rule::in(FormVisibility::values())],
            'status' => ['required', Rule::in(FormStatus::values())],
            'success_message' => ['nullable', 'string', 'max:1000'],
            'submission_method' => ['required', Rule::in(FormSubmissionMethod::values())],
            'stamped_upload_instructions' => ['nullable', 'string', 'max:2000'],
            'document_code' => ['nullable', 'string', 'max:100'],
            'revision_no' => ['nullable', 'string', 'max:100'],
            'effective_date' => ['nullable', 'date'],
            'document_title' => ['nullable', 'string', 'max:255'],
            'show_document_header' => ['required', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $slugSource = $this->input('slug') ?: $this->input('title');

        $this->merge([
            'slug' => filled($slugSource) ? Str::slug($slugSource) : null,
        ]);
    }
}