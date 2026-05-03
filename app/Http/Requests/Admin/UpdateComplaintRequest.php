<?php

namespace App\Http\Requests\Admin;

use App\Enums\ComplaintPriority;
use App\Enums\ComplaintStatus;
use App\Support\AccessControl;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateComplaintRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(AccessControl::PERMISSION_CLOSE_COMPLAINTS) ?? false;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in(ComplaintStatus::values())],
            'priority' => ['required', Rule::in(ComplaintPriority::values())],
            'assigned_to' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id')->where(fn ($query) => $query->where('cooperative_id', $this->user()?->cooperative_id)),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'status.required' => 'Status diperlukan.',
            'priority.required' => 'Keutamaan diperlukan.',
            'assigned_to.exists' => 'Pegawai yang dipilih tidak sah.',
        ];
    }
}
