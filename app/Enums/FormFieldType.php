<?php

namespace App\Enums;

enum FormFieldType: string
{
    case ShortText = 'short_text';
    case LongText = 'long_text';
    case Email = 'email';
    case Phone = 'phone';
    case IdentityNo = 'identity_no';
    case Number = 'number';
    case Currency = 'currency';
    case Date = 'date';
    case Select = 'select';
    case Radio = 'radio';
    case Checkbox = 'checkbox';
    case YesNo = 'yes_no';
    case File = 'file';
    case Signature = 'signature';
    case AgreementCheckbox = 'agreement_checkbox';
    case Note = 'note';
    case InstructionText = 'instruction_text';
    case OfficeUseBox = 'office_use_box';

    case AddressMy = 'address_my';

    // ── Member Autofill Types ──
    case MemberName = 'member_name';
    case MemberIdentityNo = 'member_identity_no';
    case MemberAddress = 'member_address';
    case MemberDob = 'member_dob';
    case MemberPhone = 'member_phone';
    case MemberEmail = 'member_email';
    case MemberNo = 'member_member_no';
    case MemberPosition = 'member_position';
    case MemberEmployer = 'member_employer';
    case MemberEmploymentNo = 'member_employment_no';
    case MemberBank = 'member_bank';
    case MemberBankAccount = 'member_bank_account';
    case MemberMaritalStatus = 'member_marital_status';
    case MemberDepartment = 'member_department';
    case MemberSpouseName = 'member_spouse_name';
    case MemberSpousePhone = 'member_spouse_phone';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function acceptsInput(): bool
    {
        return ! in_array($this, [self::InstructionText, self::Note, self::OfficeUseBox], true);
    }

    public function isFileLike(): bool
    {
        return in_array($this, [self::File, self::Signature], true);
    }

    public function allowsOptions(): bool
    {
        return in_array($this, [self::Select, self::Radio, self::Checkbox], true);
    }

    public function isMemberAutofill(): bool
    {
        return in_array($this, [
            self::MemberName,
            self::MemberIdentityNo,
            self::MemberAddress,
            self::MemberDob,
            self::MemberPhone,
            self::MemberEmail,
            self::MemberNo,
            self::MemberPosition,
            self::MemberEmployer,
            self::MemberEmploymentNo,
            self::MemberBank,
            self::MemberBankAccount,
            self::MemberMaritalStatus,
            self::MemberDepartment,
            self::MemberSpouseName,
            self::MemberSpousePhone,
        ], true);
    }

    public function isAddress(): bool
    {
        return in_array($this, [self::AddressMy, self::MemberAddress], true);
    }
}