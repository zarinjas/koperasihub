<?php

namespace Database\Seeders;

use App\Enums\MemberStatus;
use App\Models\Cooperative;
use App\Models\Member;
use App\Models\User;
use App\Support\AccessControl;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class MemberDemoSeeder extends Seeder
{
    public function run(): void
    {
        $cooperative = Cooperative::query()->where('slug', 'koperasi-demo-berhad')->first();
        if (! $cooperative) {
            return;
        }

        $adminId = User::query()->where('email', 'admin@koperasihub.test')->value('id');
        $password = Hash::make('password');

        Storage::disk('public')->makeDirectory('member-photos');

        $photoColors = [
            [15, 118, 110],
            [29, 78, 216],
            [124, 58, 237],
            [217, 119, 6],
            [220, 38, 38],
        ];

        $data = [
            [
                'member_no' => '8847',
                'full_name' => 'Ahli Demo',
                'identity_no' => '920207146739',
                'email' => 'member@koperasihub.test',
                'phone' => '0129929195',
                'address_line_1' => 'E-05-03 Blok E Residensi Zamrud, Jalan Zamrud Utama, Sg Tangkas',
                'city' => 'Kajang',
                'postcode' => '43000',
                'state' => 'Selangor',
                'date_of_birth' => '1992-02-07',
                'gender' => 'male',
                'position' => 'Pegawai Perkhidmatan',
                'department' => 'Pejabat Pendaftar',
                'employer' => 'UKM Bangi',
                'employment_no' => 'K020740',
                'salary' => 3800.00,
                'bank' => 'Maybank',
                'bank_account' => '561234567890',
                'monthly_fee' => 100.00,
                'total_fee' => 1200.00,
                'special_savings' => 5000.00,
                'monthly_deduction' => 200.00,
                'total_debt' => 0,
                'next_of_kin_name' => 'Siti Aminah Binti Abdullah',
                'next_of_kin_relation' => 'Adik beradik',
                'next_of_kin_phone' => '0198765432',
                'next_of_kin_address' => 'No. 15, Jalan Harmoni, Taman Bahagia, 43000 Kajang, Selangor',
                'spouse_name' => null,
                'spouse_phone' => null,
                'spouse_address' => null,
                'membership_status' => MemberStatus::Active->value,
                'joined_at' => '2025-03-06',
                'user_email' => 'member@koperasihub.test',
            ],
            [
                'member_no' => '8848',
                'full_name' => 'Siti Aisyah Binti Mohd Nor',
                'identity_no' => '880712085432',
                'email' => 'aisyah@koperasihub.test',
                'phone' => '0134567890',
                'address_line_1' => 'No. 15, Jalan Universiti, Seksyen 16',
                'city' => 'Bangi',
                'postcode' => '43650',
                'state' => 'Selangor',
                'date_of_birth' => '1988-07-12',
                'gender' => 'female',
                'position' => 'Pegawai Perubatan',
                'department' => 'Unit Perubatan Am',
                'employer' => 'HCTM',
                'employment_no' => 'K015709',
                'salary' => 7500.00,
                'bank' => 'CIMB',
                'bank_account' => '7012345678',
                'monthly_fee' => 100.00,
                'total_fee' => 1200.00,
                'special_savings' => 5000.00,
                'monthly_deduction' => 200.00,
                'total_debt' => 0,
                'next_of_kin_name' => 'Ahmad Faiz Bin Ismail',
                'next_of_kin_relation' => 'Pasangan',
                'next_of_kin_phone' => '0198765432',
                'next_of_kin_address' => 'No. 8, Jalan Sains, Taman Teknologi, 43600 Bangi, Selangor',
                'spouse_name' => 'Ahmad Faiz Bin Ismail',
                'spouse_phone' => '0198765432',
                'spouse_address' => 'No. 8, Jalan Sains, Taman Teknologi, 43600 Bangi, Selangor',
                'membership_status' => MemberStatus::Active->value,
                'joined_at' => '2025-06-15',
                'user_email' => 'aisyah@koperasihub.test',
            ],
            [
                'member_no' => '8849',
                'full_name' => 'Ahmad Faiz Bin Ismail',
                'identity_no' => '900305087654',
                'email' => 'faiz@koperasihub.test',
                'phone' => '0198765432',
                'address_line_1' => 'No. 8, Jalan Sains, Taman Teknologi',
                'city' => 'Bangi',
                'postcode' => '43600',
                'state' => 'Selangor',
                'date_of_birth' => '1990-03-05',
                'gender' => 'male',
                'position' => 'Pensyarah',
                'department' => 'Fakulti Sains & Teknologi',
                'employer' => 'UKM Bangi',
                'employment_no' => 'K013566',
                'salary' => 10291.22,
                'bank' => 'Bank Islam',
                'bank_account' => '120340567890',
                'monthly_fee' => 100.00,
                'total_fee' => 1200.00,
                'special_savings' => 5000.00,
                'monthly_deduction' => 200.00,
                'total_debt' => 0,
                'next_of_kin_name' => null,
                'next_of_kin_relation' => null,
                'next_of_kin_phone' => null,
                'next_of_kin_address' => null,
                'spouse_name' => null,
                'spouse_phone' => null,
                'spouse_address' => null,
                'membership_status' => MemberStatus::Active->value,
                'joined_at' => '2025-09-01',
                'user_email' => 'faiz@koperasihub.test',
            ],
            [
                'member_no' => '8850',
                'full_name' => 'Nurul Huda Binti Abdul Rahman',
                'identity_no' => '830520035578',
                'email' => null,
                'phone' => '0161122334',
                'address_line_1' => 'No. 42, Jalan Kasturi 4, Taman Kasturi',
                'city' => 'Seremban',
                'postcode' => '70400',
                'state' => 'Negeri Sembilan',
                'date_of_birth' => '1983-05-20',
                'gender' => 'female',
                'position' => 'Pembantu Tadbir',
                'department' => 'Pejabat Timbalan Naib Canselor',
                'employer' => 'UKM Kampus Kuala Lumpur',
                'employment_no' => null,
                'salary' => 4500.00,
                'bank' => 'Bank Rakyat',
                'bank_account' => '210980987654',
                'monthly_fee' => 100.00,
                'total_fee' => 1200.00,
                'special_savings' => 5000.00,
                'monthly_deduction' => 200.00,
                'total_debt' => 0,
                'next_of_kin_name' => null,
                'next_of_kin_relation' => null,
                'next_of_kin_phone' => null,
                'next_of_kin_address' => null,
                'spouse_name' => null,
                'spouse_phone' => null,
                'spouse_address' => null,
                'membership_status' => MemberStatus::Inactive->value,
                'joined_at' => '2024-01-10',
                'user_email' => null,
            ],
            [
                'member_no' => '8851',
                'full_name' => 'Mohd Hafiz Bin Kamaruddin',
                'identity_no' => '910815105509',
                'email' => null,
                'phone' => '0178899001',
                'address_line_1' => 'No. 3A, Jalan Perindustrian 7, Kawasan Perindustrian Ringan',
                'city' => 'Kajang',
                'postcode' => '43000',
                'state' => 'Selangor',
                'date_of_birth' => '1991-08-15',
                'gender' => 'male',
                'position' => 'Pegawai Penyelidik',
                'department' => 'Institut Penyelidikan',
                'employer' => 'UKM Bangi',
                'employment_no' => null,
                'salary' => 6000.00,
                'bank' => 'Maybank',
                'bank_account' => '551234098765',
                'monthly_fee' => 100.00,
                'total_fee' => 1200.00,
                'special_savings' => 5000.00,
                'monthly_deduction' => 200.00,
                'total_debt' => 0,
                'next_of_kin_name' => null,
                'next_of_kin_relation' => null,
                'next_of_kin_phone' => null,
                'next_of_kin_address' => null,
                'spouse_name' => null,
                'spouse_phone' => null,
                'spouse_address' => null,
                'membership_status' => MemberStatus::Active->value,
                'joined_at' => '2025-11-20',
                'user_email' => null,
            ],
        ];

        foreach ($data as $i => $item) {
            $userId = null;

            if ($item['user_email']) {
                $user = User::query()->updateOrCreate([
                    'email' => $item['user_email'],
                ], [
                    'cooperative_id' => $cooperative->id,
                    'name' => $item['full_name'],
                    'role' => 'member',
                    'user_type' => 'member',
                    'status' => 'active',
                    'password' => $password,
                ]);
                $user->syncRoles([AccessControl::ROLE_MEMBER]);
                $userId = $user->id;
            }

            $color = $photoColors[$i % count($photoColors)];
            $photoPath = 'member-photos/demo-'.str($item['member_no'])->slug().'.jpg';
            $this->generateProfilePhoto(storage_path('app/public/'.$photoPath), $item['full_name'], $color);

            Member::query()->updateOrCreate([
                'cooperative_id' => $cooperative->id,
                'member_no' => $item['member_no'],
            ], [
                'user_id' => $userId,
                'profile_photo_path' => $photoPath,
                'full_name' => $item['full_name'],
                'identity_no' => $item['identity_no'],
                'email' => $item['email'] ?? ($item['user_email']),
                'phone' => $item['phone'],
                'address_line_1' => $item['address_line_1'],
                'city' => $item['city'],
                'state' => $item['state'],
                'postcode' => $item['postcode'],
                'country' => 'Malaysia',
                'date_of_birth' => $item['date_of_birth'],
                'gender' => $item['gender'],
                'position' => $item['position'],
                'department' => $item['department'],
                'employer' => $item['employer'],
                'employment_no' => $item['employment_no'],
                'salary' => $item['salary'],
                'bank' => $item['bank'],
                'bank_account' => $item['bank_account'],
                'monthly_fee' => $item['monthly_fee'],
                'total_fee' => $item['total_fee'],
                'special_savings' => $item['special_savings'],
                'monthly_deduction' => $item['monthly_deduction'],
                'total_debt' => $item['total_debt'],
            'next_of_kin_name' => $item['next_of_kin_name'],
            'next_of_kin_relation' => $item['next_of_kin_relation'],
            'next_of_kin_phone' => $item['next_of_kin_phone'],
                'next_of_kin_address' => $item['next_of_kin_address'],
                'spouse_name' => $item['spouse_name'],
                'spouse_phone' => $item['spouse_phone'],
                'spouse_address' => $item['spouse_address'],
                'membership_status' => $item['membership_status'],
                'joined_at' => $item['joined_at'],
                'approved_at' => $item['joined_at'],
                'approved_by' => $adminId,
                'onboarding_completed_at' => now(),
            ]);
        }
    }

    private function generateProfilePhoto(string $path, string $name, array $rgb): void
    {
        $size = 200;
        $image = imagecreatetruecolor($size, $size);

        $bg = imagecolorallocate($image, $rgb[0], $rgb[1], $rgb[2]);
        imagefill($image, 0, 0, $bg);

        $light = imagecolorallocatealpha($image, 255, 255, 255, 60);
        imagefilledellipse($image, $size / 2, $size / 2, 160, 160, $light);

        $white = imagecolorallocate($image, 255, 255, 255);

        $initial = mb_strtoupper(mb_substr($name, 0, 1));

        $fontPath = null;
        $fontCandidates = [
            '/System/Library/Fonts/Helvetica.ttc',
            '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf',
            '/usr/share/fonts/TTF/DejaVuSans.ttf',
            '/usr/share/fonts/noto/NotoSans-Regular.ttf',
            'C:\Windows\Fonts\Arial.ttf',
        ];

        foreach ($fontCandidates as $f) {
            if (file_exists($f)) {
                $fontPath = $f;
                break;
            }
        }

        if ($fontPath) {
            $fontSize = 72;
            $bbox = imagettfbbox($fontSize, 0, $fontPath, $initial);
            $textWidth = $bbox[2] - $bbox[0];
            $textHeight = $bbox[1] - $bbox[7];
            $x = ($size - $textWidth) / 2;
            $y = ($size / 2) + ($textHeight / 2);
            imagettftext($image, $fontSize, 0, (int) $x, (int) $y, $white, $fontPath, $initial);
        } else {
            $fontSize = 5;
            $charWidth = imagefontwidth($fontSize);
            $x = ($size - $charWidth) / 2;
            $y = ($size - imagefontheight($fontSize)) / 2;
            imagestring($image, $fontSize, (int) $x, (int) $y, $initial, $white);
        }

        imagejpeg($image, $path, 85);
        imagedestroy($image);
    }
}