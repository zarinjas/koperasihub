<?php

namespace App\Http\Requests\Public;

use App\Enums\FormStatus;
use App\Enums\FormSubmissionMethod;
use Illuminate\Foundation\Http\FormRequest;

class UploadStampedFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        $form = $this->route('onlineForm');

        if (! $form || $form->status !== FormStatus::Published) {
            return false;
        }

        if ($form->submission_method !== FormSubmissionMethod::RequiresStampedUpload) {
            return false;
        }

        return true;
    }

    public function rules(): array
    {
        return [
            'stamped_file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:5120'],
        ];
    }

    public function messages(): array
    {
        return [
            'stamped_file.required' => 'Sila muat naik borang bercop.',
            'stamped_file.file' => 'Fail tidak sah.',
            'stamped_file.mimes' => 'Format fail tidak disokong. Sila gunakan PDF, JPG, PNG, atau WEBP.',
            'stamped_file.max' => 'Saiz fail melebihi had 5MB.',
        ];
    }
}
