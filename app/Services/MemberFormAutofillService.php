<?php

namespace App\Services;

use App\Models\Member;

class MemberFormAutofillService
{
    public function build(Member $member): array
    {
        $source = [
            'full_name' => $member->full_name,
            'identity_no' => $member->identity_no,
            'phone' => $member->phone,
            'email' => $member->email,
            'member_no' => $member->member_no,
            'date_of_birth' => $member->date_of_birth?->format('Y-m-d'),
            'gender' => $member->gender,
            'address' => $member->address_line_1,
            'address_line_1' => $member->address_line_1,
            'address_line_2' => $member->address_line_2,
            'city' => $member->city,
            'state' => $member->state,
            'postcode' => $member->postcode,
            'country' => $member->country,
            'occupation' => $member->position,
            'position' => $member->position,
            'job_title' => $member->position,
            'employer_name' => $member->employer,
            'employer' => $member->employer,
            'department' => $member->department,
            'employment_no' => $member->employment_no,
            'monthly_income' => $member->salary,
            'salary' => $member->salary,
            'bank' => $member->bank,
            'bank_name' => $member->bank,
            'bank_account' => $member->bank_account,
            'account_holder' => $member->full_name,
            'marital_status' => $member->marital_status,
            'nominee_name' => $member->next_of_kin_name,
            'nominee_relationship' => $member->next_of_kin_relation,
            'nominee_phone' => $member->next_of_kin_phone,
            'nominee_address' => $member->next_of_kin_address,
            'spouse_name' => $member->spouse_name,
            'new_spouse_name' => $member->spouse_name,
            'spouse_phone' => $member->spouse_phone,
            'spouse_address' => $member->spouse_address,
            'new_nominee_name' => $member->next_of_kin_name,
            'monthly_contribution' => $member->monthly_fee,
            'monthly_commitment' => $member->monthly_deduction,
        ];

        $aliases = [
            'full_name' => ['nama-penuh', 'nama'],
            'identity_no' => ['no-kad-pengenalan', 'no-kp', 'kad-pengenalan'],
            'phone' => ['nombor-telefon', 'no-telefon', 'telefon'],
            'email' => ['emel'],
            'member_no' => ['no-anggota', 'nombor-ahli'],
            'date_of_birth' => ['tarikh-lahir'],
            'gender' => ['jantina'],
            'address_line_1' => ['alamat', 'alamat-terkini', 'alamat-surat-menyurat'],
            'city' => ['bandar'],
            'state' => ['negeri'],
            'postcode' => ['poskod'],
            'country' => ['negara'],
            'position' => ['jawatan', 'pekerjaan', 'jenis-pekerjaan'],
            'employer' => ['majikan', 'nama-majikan'],
            'employer_name' => ['nama-majikan'],
            'department' => ['jabatan'],
            'employment_no' => ['no-pekerjaan', 'nombor-pekerjaan'],
            'monthly_income' => ['pendapatan-bulanan'],
            'salary' => ['gaji', 'gaji-bulanan'],
            'bank' => ['nama-bank'],
            'bank_account' => ['no-akaun-bank', 'akaun-bank'],
            'marital_status' => ['status-perkahwinan', 'taraf-perkahwinan'],
            'account_holder' => ['nama-pemegang-akaun'],
            'nominee_name' => ['nama-waris'],
            'nominee_relationship' => ['hubungan-waris'],
            'nominee_phone' => ['no-telefon-waris', 'telefon-waris'],
            'nominee_address' => ['alamat-waris'],
            'spouse_name' => ['nama-pasangan'],
            'spouse_phone' => ['no-telefon-pasangan', 'telefon-pasangan'],
            'spouse_address' => ['alamat-pasangan'],
            'new_spouse_name' => ['nama-pasangan-baharu'],
            'new_nominee_name' => ['nama-waris-baharu'],
            'monthly_contribution' => ['caruman-bulanan'],
            'monthly_commitment' => ['komitmen-bulanan'],
        ];

        foreach ($aliases as $enKey => $aliasKeys) {
            if (! isset($source[$enKey])) {
                continue;
            }
            foreach ($aliasKeys as $alias) {
                $source[$alias] = $source[$enKey];
            }
        }

        return array_filter($source, fn ($value) => $value !== null && $value !== '');
    }
}
