<?php

namespace App\Http\Requests\Public;

use App\Enums\FormFieldType;
use App\Enums\FormStatus;
use App\Enums\FormVisibility;
use App\Models\Member;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOnlineFormSubmissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $form = $this->route('onlineForm');

        if (! $form || $form->status !== FormStatus::Published) {
            return false;
        }

        if ($form->visibility === FormVisibility::MembersOnly) {
            return $this->user()?->isMember() ?? false;
        }

        return true;
    }

    public function rules(): array
    {
        $form = $this->route('onlineForm');
        $fields = $form->fields()
            ->where('is_active', true)
            ->whereHas('section', fn ($query) => $query->where('is_active', true))
            ->get();
        $rules = [
            'submitted_by_name' => ['nullable', 'string', 'max:255'],
            'submitted_by_email' => ['nullable', 'email', 'max:255'],
            'answers' => ['nullable', 'array'],
            'files' => ['nullable', 'array'],
        ];

        foreach ($fields as $field) {
            if (! $field->showsOnline()) {
                continue;
            }

            $answerKey = "answers.{$field->field_key}";
            $fileKey = "files.{$field->field_key}";
            $required = $field->is_required ? ['required'] : ['nullable'];
            $validation = $field->validation_json ?? [];
            $maxSize = (int) ($validation['max_size_kb'] ?? 5120);

            switch ($field->type) {
                case FormFieldType::ShortText:
                case FormFieldType::LongText:
                    $rules[$answerKey] = [...$required, 'string', 'max:2000'];
                    break;
                case FormFieldType::Phone:
                    $rules[$answerKey] = [...$required, 'string', 'regex:/^[0-9+\\-\\s]{8,20}$/'];
                    break;
                case FormFieldType::IdentityNo:
                    $rules[$answerKey] = [...$required, 'string', 'regex:/^\\d{6}-?\\d{2}-?\\d{4}$/'];
                    break;
                case FormFieldType::Date:
                    $rules[$answerKey] = [...$required, 'date'];
                    break;
                case FormFieldType::Email:
                    $rules[$answerKey] = [...$required, 'email', 'max:255'];
                    break;
                case FormFieldType::Number:
                case FormFieldType::Currency:
                    $rules[$answerKey] = [...$required, 'numeric'];
                    break;
                case FormFieldType::Select:
                case FormFieldType::Radio:
                    $rules[$answerKey] = [...$required, 'string', Rule::in($field->options_json ?? [])];
                    break;
                case FormFieldType::Checkbox:
                    $rules[$answerKey] = [...$required, 'array'];
                    $rules["{$answerKey}.*"] = ['string', Rule::in($field->options_json ?? [])];
                    break;
                case FormFieldType::YesNo:
                    $rules[$answerKey] = [...$required, Rule::in(['yes', 'no'])];
                    break;
                case FormFieldType::AgreementCheckbox:
                    $rules[$answerKey] = $field->is_required ? ['accepted'] : ['nullable', 'boolean'];
                    break;
                case FormFieldType::File:
                    $rules[$fileKey] = [...$required, 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:'.$maxSize];
                    break;
                case FormFieldType::Signature:
                    $rules[$answerKey] = [...$required, 'string', 'starts_with:data:image/'];
                    break;
                case FormFieldType::AddressMy:
                case FormFieldType::MemberAddress:
                    $rules[$answerKey] = [...$required, 'array'];
                    $rules["{$answerKey}.line1"] = ['required_with:'.$answerKey, 'string', 'max:255'];
                    $rules["{$answerKey}.line2"] = ['nullable', 'string', 'max:255'];
                    $rules["{$answerKey}.postcode"] = ['required_with:'.$answerKey, 'string', 'digits:5'];
                    $rules["{$answerKey}.city"] = ['required_with:'.$answerKey, 'string', 'max:100'];
                    $rules["{$answerKey}.state"] = ['required_with:'.$answerKey, 'string', Rule::in([
                        'Johor', 'Kedah', 'Kelantan', 'Melaka', 'Negeri Sembilan',
                        'Pahang', 'Perak', 'Perlis', 'Pulau Pinang', 'Sabah',
                        'Sarawak', 'Selangor', 'Terengganu',
                        'W.P. Kuala Lumpur', 'W.P. Labuan', 'W.P. Putrajaya',
                    ])];
                    break;

                case FormFieldType::Note:
                case FormFieldType::InstructionText:
                case FormFieldType::OfficeUseBox:
                    break;

                // ── Member Autofill Types (validated as regular strings) ──
                case FormFieldType::MemberName:
                case FormFieldType::MemberIdentityNo:
                case FormFieldType::MemberDob:
                case FormFieldType::MemberPhone:
                case FormFieldType::MemberEmail:
                case FormFieldType::MemberNo:
                case FormFieldType::MemberPosition:
                case FormFieldType::MemberEmployer:
                case FormFieldType::MemberEmploymentNo:
                case FormFieldType::MemberBank:
                case FormFieldType::MemberBankAccount:
                case FormFieldType::MemberMaritalStatus:
                case FormFieldType::MemberDepartment:
                case FormFieldType::MemberSpouseName:
                case FormFieldType::MemberSpousePhone:
                    $rules[$answerKey] = [...$required, 'string', 'max:255'];
                    break;
            }
        }

        if ($form->visibility === FormVisibility::Public) {
            $rules['submitted_by_name'] = ['required', 'string', 'max:255'];
            $rules['submitted_by_email'] = ['nullable', 'email', 'max:255'];
        } else {
            $rules['submitted_by_name'] = ['nullable'];
            $rules['submitted_by_email'] = ['nullable'];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'submitted_by_name.required' => 'Nama penghantar diperlukan.',
            'submitted_by_email.email' => 'Sila masukkan emel yang sah.',
            'regex' => 'Sila masukkan format maklumat yang sah.',
            'accepted' => 'Ruangan ini diperlukan.',
            'mimes' => 'Format fail tidak disokong.',
            'max' => 'Saiz fail melebihi had yang dibenarkan.',
            'starts_with' => 'Tandatangan tidak sah.',
        ];
    }

    protected function passedValidation(): void
    {
        $form = $this->route('onlineForm');

        if ($form->visibility === FormVisibility::MembersOnly && $this->user()?->member instanceof Member) {
            $this->merge([
                'submitted_by_name' => $this->user()->member->full_name ?? $this->user()->name,
                'submitted_by_email' => $this->user()->member->email ?? $this->user()->email,
            ]);
        }
    }

    protected function getValidatorInstance(): Validator
    {
        return parent::getValidatorInstance();
    }
}