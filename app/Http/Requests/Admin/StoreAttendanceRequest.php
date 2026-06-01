<?php

namespace App\Http\Requests\Admin;

use App\Support\AccessControl;
use Illuminate\Foundation\Http\FormRequest;

class StoreAttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(AccessControl::PERMISSION_SCAN_ATTENDANCE) ?? false;
    }

    public function rules(): array
    {
        return [
            'member_id' => ['required', 'integer', 'exists:members,id'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }
}
