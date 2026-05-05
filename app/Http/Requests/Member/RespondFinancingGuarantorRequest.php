<?php

namespace App\Http\Requests\Member;

use App\Support\AccessControl;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RespondFinancingGuarantorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(AccessControl::PERMISSION_MEMBER_ACCESS) ?? false;
    }

    public function rules(): array
    {
        return [
            'action' => ['required', Rule::in(['accept', 'reject'])],
            'consent' => ['nullable', 'boolean'],
            'consent_text' => ['nullable', 'string', 'max:1000'],
            'signature' => ['nullable', 'string'],
            'rejection_reason' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $action = $this->input('action');

            if ($action === 'accept') {
                if (! $this->boolean('consent')) {
                    $validator->errors()->add('consent', 'Sila sahkan persetujuan penjamin.');
                }

                if (! filled($this->input('signature'))) {
                    $validator->errors()->add('signature', 'Tandatangan penjamin diperlukan.');
                }
            }

            if ($action === 'reject' && ! filled($this->input('rejection_reason'))) {
                $validator->errors()->add('rejection_reason', 'Sila nyatakan sebab penolakan.');
            }
        });
    }
}
