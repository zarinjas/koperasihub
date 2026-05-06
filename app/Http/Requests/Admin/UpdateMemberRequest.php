<?php

namespace App\Http\Requests\Admin;

use App\Enums\MemberStatus;
use App\Models\Member;
use App\Models\User;
use App\Support\AccessControl;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(AccessControl::PERMISSION_EDIT_MEMBERS) ?? false;
    }

    public function rules(): array
    {
        /** @var Member|null $member */
        $member = $this->route('member');

        return [
            'user_id' => [
                'nullable',
                'integer',
                Rule::exists(User::class, 'id')->where(fn ($query) => $query->where('cooperative_id', $this->user()?->cooperative_id)),
                Rule::unique('members', 'user_id')->ignore($member?->id),
            ],
            'member_no' => [
                'required',
                'string',
                'max:50',
                Rule::unique('members', 'member_no')
                    ->where(fn ($query) => $query->where('cooperative_id', $this->user()?->cooperative_id))
                    ->ignore($member?->id),
            ],
            'full_name' => ['required', 'string', 'max:255'],
            'identity_no' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('members', 'identity_no')
                    ->where(fn ($query) => $query->where('cooperative_id', $this->user()?->cooperative_id))
                    ->ignore($member?->id),
            ],
            'email' => [
                'nullable',
                'email',
                'max:255',
                Rule::unique('members', 'email')
                    ->where(fn ($query) => $query->where('cooperative_id', $this->user()?->cooperative_id))
                    ->ignore($member?->id),
            ],
            'phone' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:2000'],
            'date_of_birth' => ['nullable', 'date'],
            'gender' => ['nullable', Rule::in(['male', 'female', 'other'])],
            'occupation' => ['nullable', 'string', 'max:255'],
            'employer_name' => ['nullable', 'string', 'max:255'],
            'membership_status' => ['required', Rule::in(MemberStatus::values())],
            'joined_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'member_no.required' => 'No. ahli diperlukan.',
            'member_no.unique' => 'No. ahli ini telah digunakan oleh rekod ahli lain.',
            'full_name.required' => 'Nama penuh diperlukan.',
            'membership_status.required' => 'Status ahli diperlukan.',
            'identity_no.unique' => 'Nombor pengenalan ini telah digunakan oleh rekod ahli lain.',
            'email.unique' => 'E-mel ini telah digunakan oleh rekod ahli lain.',
            'user_id.unique' => 'Akaun pengguna ini telah dipautkan kepada ahli lain.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'member_no' => filled($this->member_no) ? trim((string) $this->member_no) : null,
            'email' => filled($this->email) ? mb_strtolower(trim((string) $this->email)) : null,
            'identity_no' => filled($this->identity_no) ? trim((string) $this->identity_no) : null,
            'phone' => filled($this->phone) ? trim((string) $this->phone) : null,
            'address' => filled($this->address) ? trim((string) $this->address) : null,
            'occupation' => filled($this->occupation) ? trim((string) $this->occupation) : null,
            'employer_name' => filled($this->employer_name) ? trim((string) $this->employer_name) : null,
            'notes' => filled($this->notes) ? trim((string) $this->notes) : null,
            'user_id' => filled($this->user_id) ? (int) $this->user_id : null,
        ]);
    }
}
