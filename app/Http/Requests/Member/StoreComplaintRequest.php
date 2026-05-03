<?php

namespace App\Http\Requests\Member;

use App\Enums\ComplaintPriority;
use App\Support\AccessControl;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreComplaintRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(AccessControl::PERMISSION_MEMBER_ACCESS) ?? false;
    }

    public function rules(): array
    {
        return [
            'category' => ['required', Rule::in(['aduan', 'cadangan', 'portal', 'dokumen', 'keahlian', 'lain_lain'])],
            'subject' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:5000'],
            'priority' => ['required', Rule::in(ComplaintPriority::values())],
        ];
    }

    public function messages(): array
    {
        return [
            'category.required' => 'Kategori diperlukan.',
            'category.in' => 'Kategori yang dipilih tidak sah.',
            'subject.required' => 'Tajuk diperlukan.',
            'message.required' => 'Mesej diperlukan.',
            'priority.required' => 'Keutamaan diperlukan.',
            'priority.in' => 'Keutamaan yang dipilih tidak sah.',
        ];
    }
}
