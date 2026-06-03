<?php

namespace App\Enums;

enum FinancingFieldType: string
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
    case RepeaterTable = 'repeater_table';
    case File = 'file';
    case RichText = 'rich_text';
    case Image = 'image';
    case PdfDocument = 'pdf_document';
    case Note = 'note';
    case InstructionText = 'instruction_text';
    case DocumentChecklist = 'document_checklist';
    case SignatureBlock = 'signature_block';
    case AddressMy = 'address_my';
    case DigitalSignature = 'digital_signature';
    case MemberName = 'member_name';
    case MemberIdentityNo = 'member_identity_no';
    case MemberDob = 'member_dob';
    case MemberPhone = 'member_phone';
    case MemberEmail = 'member_email';
    case MemberPosition = 'member_position';
    case MemberEmployer = 'member_employer';
    case MemberMemberNo = 'member_member_no';
    case MemberEmploymentNo = 'member_employment_no';
    case MemberBank = 'member_bank';
    case MemberBankAccount = 'member_bank_account';
    case MemberMaritalStatus = 'member_marital_status';
    case AddressSpouse = 'address_spouse';
    case AddressBeneficiary = 'address_beneficiary';
    case FinancingAmount = 'financing_amount';
    case FinancingTenure = 'financing_tenure';
    case MemberDepartment = 'member_department';
    case MemberSpouseName = 'member_spouse_name';
    case MemberSpousePhone = 'member_spouse_phone';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function inputTypes(): array
    {
        return [
            self::ShortText,
            self::LongText,
            self::Email,
            self::Phone,
            self::IdentityNo,
            self::Number,
            self::Currency,
            self::Date,
            self::Select,
            self::Radio,
            self::Checkbox,
            self::YesNo,
            self::RepeaterTable,
            self::File,
            self::AddressMy,
            self::AddressSpouse,
            self::AddressBeneficiary,
            self::DigitalSignature,
            self::MemberName,
            self::MemberIdentityNo,
            self::MemberDob,
            self::MemberPhone,
            self::MemberEmail,
            self::MemberPosition,
            self::MemberEmployer,
            self::MemberMemberNo,
            self::MemberEmploymentNo,
            self::MemberBank,
            self::MemberBankAccount,
            self::MemberMaritalStatus,
            self::FinancingAmount,
            self::FinancingTenure,
            self::MemberDepartment,
            self::MemberSpouseName,
            self::MemberSpousePhone,
        ];
    }

    public static function contentTypes(): array
    {
        return [
            self::RichText,
            self::Image,
            self::PdfDocument,
            self::Note,
            self::InstructionText,
            self::DocumentChecklist,
            self::SignatureBlock,
        ];
    }

    public function label(): string
    {
        return match ($this) {
            self::ShortText => 'Teks Pendek',
            self::LongText => 'Teks Panjang',
            self::Email => 'E-mel',
            self::Phone => 'Telefon',
            self::IdentityNo => 'No. Kad Pengenalan',
            self::Number => 'Nombor',
            self::Currency => 'Mata Wang (RM)',
            self::Date => 'Tarikh',
            self::Select => 'Pilihan (Dropdown)',
            self::Radio => 'Pilihan (Radio)',
            self::Checkbox => 'Pilihan (Checkbox)',
            self::YesNo => 'Ya / Tidak',
            self::RepeaterTable => 'Jadual Berulang',
            self::File => 'Muat Naik Fail',
            self::RichText => 'Teks Kaya',
            self::Image => 'Imej',
            self::PdfDocument => 'Dokumen PDF',
            self::Note => 'Nota',
            self::InstructionText => 'Arahan',
            self::DocumentChecklist => 'Senarai Semak Dokumen',
            self::SignatureBlock => 'Blok Tandatangan',
            self::AddressMy => 'Alamat (Malaysia)',
            self::DigitalSignature => 'Tandatangan Digital',
            self::MemberName => 'Nama Penuh (Ahli)',
            self::MemberIdentityNo => 'No. Kad Pengenalan (Ahli)',
            self::MemberDob => 'Tarikh Lahir (Ahli)',
            self::MemberPhone => 'No. Telefon (Ahli)',
            self::MemberEmail => 'E-mel (Ahli)',
            self::MemberPosition => 'Jawatan (Ahli)',
            self::MemberEmployer => 'Majikan (Ahli)',
            self::MemberMemberNo => 'No. Ahli (Ahli)',
            self::MemberEmploymentNo => 'No. Pekerjaan (Ahli)',
            self::MemberBank => 'Nama Bank (Ahli)',
            self::MemberBankAccount => 'No. Akaun Bank (Ahli)',
            self::MemberMaritalStatus => 'Status Perkahwinan (Ahli)',
            self::AddressSpouse => 'Alamat Pasangan',
            self::AddressBeneficiary => 'Alamat Waris',
            self::FinancingAmount => 'Jumlah Pembiayaan',
            self::FinancingTenure => 'Tempoh Pembiayaan',
            self::MemberDepartment => 'Jabatan (Ahli)',
            self::MemberSpouseName => 'Nama Pasangan (Ahli)',
            self::MemberSpousePhone => 'No. Telefon Pasangan (Ahli)',
        };
    }

    public function category(): string
    {
        return match ($this) {
            self::ShortText, self::LongText, self::Email, self::Phone,
            self::IdentityNo, self::Number, self::Currency, self::Date,
            self::Select, self::Radio, self::Checkbox, self::YesNo,
            self::RepeaterTable, self::File, self::AddressMy, self::AddressSpouse, self::AddressBeneficiary, self::DigitalSignature,
            self::MemberName, self::MemberIdentityNo, self::MemberDob,
            self::MemberPhone, self::MemberEmail, self::MemberPosition,
            self::MemberEmployer, self::MemberMemberNo, self::MemberEmploymentNo,
            self::MemberBank, self::MemberBankAccount, self::MemberMaritalStatus,
            self::FinancingAmount, self::FinancingTenure,
            self::MemberDepartment, self::MemberSpouseName, self::MemberSpousePhone => 'input',
            self::RichText, self::Image, self::PdfDocument,
            self::Note, self::InstructionText,
            self::DocumentChecklist, self::SignatureBlock => 'content',
        };
    }

    public function needsOptions(): bool
    {
        return match ($this) {
            self::Select, self::Radio, self::Checkbox => true,
            default => false,
        };
    }

    public function isFileUpload(): bool
    {
        return match ($this) {
            self::File, self::Image, self::PdfDocument => true,
            default => false,
        };
    }

    public function isAdminUpload(): bool
    {
        return match ($this) {
            self::Image, self::PdfDocument => true,
            default => false,
        };
    }
}