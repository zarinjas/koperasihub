<?php

namespace App\Services;

use App\Enums\MemberStatus;
use App\Models\Member;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MemberImportService
{
    private const ALL_COLUMNS = [
        'member_no',
        'employment_no',
        'identity_no',
        'full_name',
        'address_line_1',
        'address_line_2',
        'city',
        'postcode',
        'state',
        'date_of_birth',
        'joined_at',
        'termination_date',
        'ethnicity',
        'gender',
        'marital_status',
        'phone',
        'email',
        'employer',
        'department',
        'employer_billing_address',
        'salary',
        'monthly_fee',
        'special_savings',
        'total_fee',
        'monthly_deduction',
        'bank',
        'bank_account',
        'membership_status',
    ];

    public function __construct(
        private readonly AuditLogService $auditLogs,
    ) {
    }

    public static function templateHeaders(): array
    {
        return self::ALL_COLUMNS;
    }

    public static function templateExampleRow(): array
    {
        return [
            '0001',
            'P001',
            '900101-14-1234',
            'Ali bin Abu',
            'No. 1, Jalan Bukit',
            'Taman Mutiara',
            'Bangi',
            '43650',
            'Selangor',
            '1990-01-01',
            '2020-05-01',
            '',
            'melayu',
            'male',
            'married',
            '0123456789',
            'ali@example.com',
            'Demo Holdings',
            'Pentadbiran',
            'No. 10, Jalan Ilmu, Bangi',
            '3500.00',
            '50.00',
            '0.00',
            '0.00',
            '0.00',
            'Bank Islam',
            '1234567890',
            'active',
        ];
    }

    public function parseFile(UploadedFile $file): array
    {
        $extension = Str::lower($file->getClientOriginalExtension());

        if ($extension !== 'csv') {
            return ['error' => 'Format fail tidak disokong. Sila muat naik fail CSV sahaja.'];
        }

        $path = $file->getRealPath();
        $handle = fopen($path, 'r');

        if (! $handle) {
            return ['error' => 'Gagal membaca fail. Sila cuba lagi.'];
        }

        $rows = [];
        $rowNumber = 0;

        while (($data = fgetcsv($handle)) !== false) {
            $rowNumber++;

            if ($rowNumber === 1 && $this->isHeaderRow($data)) {
                continue;
            }

            if (count($data) < 3) {
                continue;
            }

            $rows[] = $data;
        }

        fclose($handle);

        if (empty($rows)) {
            return ['error' => 'Fail CSV kosong atau tidak mempunyai baris data.'];
        }

        return ['rows' => $rows];
    }

    public function preview(array $rows): array
    {
        $previewRows = [];
        $errors = [];
        $totalRows = count($rows);

        foreach ($rows as $index => $row) {
            $parsed = $this->parseRow($row);
            $rowErrors = $this->validateRow($parsed, $index + 1);

            if (! empty($rowErrors)) {
                $errors[] = [
                    'row' => $index + 1,
                    'errors' => $rowErrors,
                ];
                continue;
            }

            $previewRows[] = $parsed;
        }

        return [
            'totalRows' => $totalRows,
            'previewRows' => $previewRows,
            'validRows' => count($previewRows),
            'invalidRows' => count($errors),
            'errors' => $errors,
        ];
    }

    public function import(array $rows, int $cooperativeId, User $actor): array
    {
        $summary = [
            'total' => 0,
            'imported' => 0,
            'skipped' => 0,
            'duplicates' => 0,
            'errors' => 0,
            'errorDetails' => [],
        ];

        $seenMemberNos = [];
        $seenIdentityNos = [];

        foreach ($rows as $index => $row) {
            $summary['total']++;
            $parsed = $this->parseRow($row);
            $rowErrors = $this->validateRow($parsed, $index + 1);

            if (! empty($rowErrors)) {
                $summary['errors']++;
                $summary['errorDetails'][] = [
                    'row' => $index + 1,
                    'errors' => $rowErrors,
                ];
                continue;
            }

            $memberNo = $this->normalizeMemberNo($parsed['member_no']);
            $identityNo = $this->normalizeText($parsed['identity_no']);

            if (in_array($memberNo, $seenMemberNos, true)) {
                $summary['duplicates']++;
                continue;
            }

            if ($identityNo && in_array($identityNo, $seenIdentityNos, true)) {
                $summary['duplicates']++;
                continue;
            }

            $seenMemberNos[] = $memberNo;
            if ($identityNo) {
                $seenIdentityNos[] = $identityNo;
            }

            $existing = Member::query()
                ->where('cooperative_id', $cooperativeId)
                ->where(function ($query) use ($memberNo, $identityNo): void {
                    $query->where('member_no', $memberNo);
                    if ($identityNo) {
                        $query->orWhere('identity_no', $identityNo);
                    }
                })
                ->first();

            if ($existing) {
                $summary['duplicates']++;
                continue;
            }

            $email = $this->normalizeEmail($parsed['email']);

            if ($email) {
                $emailExists = User::query()
                    ->where('email', $email)
                    ->exists();

                if ($emailExists) {
                    $summary['errorDetails'][] = [
                        'row' => $index + 1,
                        'errors' => ['email' => "E-mel '{$email}' sudah digunakan oleh akaun pengguna sedia ada."],
                    ];
                    $summary['errors']++;
                    continue;
                }
            }

            DB::transaction(function () use ($cooperativeId, $actor, $parsed, $memberNo, $identityNo, $email): void {
                $member = Member::query()->create([
                    'cooperative_id' => $cooperativeId,
                    'user_id' => null,
                    'member_no' => $memberNo,
                    'employment_no' => $this->normalizeText($parsed['employment_no']),
                    'full_name' => $parsed['full_name'],
                    'identity_no' => $identityNo,
                    'email' => $email,
                    'phone' => $this->normalizeText($parsed['phone']),
                    'address_line_1' => $this->normalizeText($parsed['address_line_1']),
                    'address_line_2' => $this->normalizeText($parsed['address_line_2']),
                    'city' => $this->normalizeText($parsed['city']),
                    'postcode' => $this->normalizeText($parsed['postcode']),
                    'state' => $this->normalizeText($parsed['state']),
                    'country' => 'Malaysia',
                    'date_of_birth' => $this->normalizeDate($parsed['date_of_birth']),
                    'gender' => $this->normalizeGender($parsed['gender']),
                    'marital_status' => $this->normalizeText($parsed['marital_status']),
                    'ethnicity' => $this->normalizeText($parsed['ethnicity']),
                    'employer' => $this->normalizeText($parsed['employer']),
                    'department' => $this->normalizeText($parsed['department']),
                    'employer_billing_address' => $this->normalizeText($parsed['employer_billing_address']),
                    'salary' => $this->normalizeDecimal($parsed['salary']),
                    'monthly_fee' => $this->normalizeDecimal($parsed['monthly_fee']),
                    'special_savings' => $this->normalizeDecimal($parsed['special_savings']),
                    'total_fee' => $this->normalizeDecimal($parsed['total_fee']),
                    'monthly_deduction' => $this->normalizeDecimal($parsed['monthly_deduction']),
                    'bank' => $this->normalizeText($parsed['bank']),
                    'bank_account' => $this->normalizeText($parsed['bank_account']),
                    'joined_at' => $this->normalizeDate($parsed['joined_at']) ?? now(),
                    'termination_date' => $this->normalizeDate($parsed['termination_date']),
                    'membership_status' => $this->normalizeMembershipStatus($parsed['membership_status']),
                ]);

                $this->auditLogs->record('member_imported', $member, [], [
                    'member_no' => $member->member_no,
                    'full_name' => $member->full_name,
                    'identity_no' => $member->identity_no,
                ]);
            });

            $summary['imported']++;
        }

        $summary['skipped'] = $summary['total'] - $summary['imported'] - $summary['duplicates'] - $summary['errors'];

        return $summary;
    }

    private function parseRow(array $row): array
    {
        $mapped = [];
        $headers = self::ALL_COLUMNS;

        foreach ($headers as $i => $header) {
            $mapped[$header] = isset($row[$i]) ? trim((string) $row[$i]) : null;
        }

        return $mapped;
    }

    private function validateRow(array $parsed, int $rowNumber): array
    {
        $errors = [];

        if (empty($parsed['member_no'])) {
            $errors['member_no'] = "No. ahli diperlukan (baris {$rowNumber}).";
        }

        if (empty($parsed['full_name'])) {
            $errors['full_name'] = "Nama penuh diperlukan (baris {$rowNumber}).";
        }

        if (empty($parsed['identity_no'])) {
            $errors['identity_no'] = "No. kad pengenalan diperlukan (baris {$rowNumber}).";
        }

        if (! empty($parsed['date_of_birth']) && ! $this->isValidDate($parsed['date_of_birth'])) {
            $errors['date_of_birth'] = "Format tarikh lahir tidak sah (baris {$rowNumber}).";
        }

        if (! empty($parsed['joined_at']) && ! $this->isValidDate($parsed['joined_at'])) {
            $errors['joined_at'] = "Format tarikh sertai tidak sah (baris {$rowNumber}).";
        }

        if (! empty($parsed['termination_date']) && ! $this->isValidDate($parsed['termination_date'])) {
            $errors['termination_date'] = "Format tarikh berhenti tidak sah (baris {$rowNumber}).";
        }

        if (! empty($parsed['membership_status']) && ! in_array($parsed['membership_status'], MemberStatus::values(), true)) {
            $errors['membership_status'] = "Status keahlian tidak sah (baris {$rowNumber}).";
        }

        return $errors;
    }

    private function isHeaderRow(array $row): bool
    {
        $first = Str::lower(trim((string) ($row[0] ?? '')));

        return $first === 'member_no' || $first === 'no._ahli' || $first === 'bil';
    }

    private function isValidDate(?string $value): bool
    {
        if (empty($value)) {
            return false;
        }

        return (bool) strtotime($value);
    }

    private function normalizeText(?string $value): ?string
    {
        $value = is_string($value) ? trim($value) : null;

        return $value !== '' ? $value : null;
    }

    private function normalizeDecimal(?string $value): ?float
    {
        $value = $this->normalizeText($value);

        if (! $value) {
            return null;
        }

        $value = str_replace(',', '', $value);

        return is_numeric($value) ? (float) $value : null;
    }

    private function normalizeEmail(?string $value): ?string
    {
        $value = $this->normalizeText($value);

        return $value ? Str::lower($value) : null;
    }

    private function normalizeMemberNo(?string $value): string
    {
        return trim((string) $value);
    }

    private function normalizeDate(?string $value): ?string
    {
        $value = $this->normalizeText($value);

        if (! $value) {
            return null;
        }

        $timestamp = strtotime($value);

        return $timestamp ? date('Y-m-d', $timestamp) : null;
    }

    private function normalizeGender(?string $value): ?string
    {
        $value = Str::lower($this->normalizeText($value) ?? '');

        return match ($value) {
            'male', 'lelaki', 'l' => 'male',
            'female', 'perempuan', 'p', 'wanita' => 'female',
            default => null,
        };
    }

    private function normalizeMembershipStatus(?string $value): string
    {
        $value = $this->normalizeText($value);

        if ($value && in_array($value, MemberStatus::values(), true)) {
            return $value;
        }

        return MemberStatus::Active->value;
    }
}
