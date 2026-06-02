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
}