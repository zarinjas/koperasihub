<?php

namespace Database\Seeders;

use App\Enums\MemberStatus;
use App\Models\Cooperative;
use App\Models\Member;
use App\Models\User;
use Illuminate\Database\Seeder;

class MemberDemoSeeder extends Seeder
{
    public function run(): void
    {
        $cooperative = Cooperative::query()->where('slug', 'koperasi-demo-berhad')->first();

        if (! $cooperative) {
            return;
        }

        $approverId = User::query()->where('email', 'admin@koperasihub.test')->value('id');
        $memberUserId = User::query()->where('email', 'member@koperasihub.test')->value('id');

        Member::query()->updateOrCreate([
            'cooperative_id' => $cooperative->id,
            'member_no' => 'MBR-'.now()->format('Ymd').'-0001',
        ], [
            'user_id' => $memberUserId,
            'full_name' => 'Ahli Demo',
            'identity_no' => '910101105555',
            'email' => 'member@koperasihub.test',
            'phone' => '0121111111',
            'address_line_1' => 'No. 10, Jalan Harmoni, 43000 Kajang, Selangor',
            'country' => 'Malaysia',
            'date_of_birth' => '1991-01-01',
            'gender' => 'female',
            'position' => 'Pegawai Operasi',
            'employer' => 'KoperasiHub Demo',
            'membership_status' => MemberStatus::Active->value,
            'joined_at' => now()->subMonths(8),
            'approved_at' => now()->subMonths(8),
            'approved_by' => $approverId,
            'notes' => 'Akaun demo ahli aktif.',
        ]);

        Member::query()->updateOrCreate([
            'cooperative_id' => $cooperative->id,
            'member_no' => 'MBR-'.now()->format('Ymd').'-0002',
        ], [
            'full_name' => 'Roslan Bin Yahya',
            'identity_no' => '830202105432',
            'email' => 'roslan@example.test',
            'phone' => '0132222222',
            'address_line_1' => 'No. 21, Taman Sejahtera, 75400 Melaka',
            'country' => 'Malaysia',
            'date_of_birth' => '1983-02-02',
            'gender' => 'male',
            'position' => 'Penyelia',
            'employer' => 'Demo Manufacturing',
            'membership_status' => MemberStatus::Inactive->value,
            'joined_at' => now()->subMonths(14),
            'approved_at' => now()->subMonths(14),
            'approved_by' => $approverId,
            'notes' => 'Status tidak aktif untuk demo penapisan.',
        ]);

        Member::query()->updateOrCreate([
            'cooperative_id' => $cooperative->id,
            'member_no' => 'MBR-'.now()->format('Ymd').'-0003',
        ], [
            'full_name' => 'Faizal Bin Omar',
            'identity_no' => '790909105432',
            'email' => 'faizal@example.test',
            'phone' => '0143333333',
            'address_line_1' => 'Lot 5, Jalan Wawasan, 25000 Kuantan, Pahang',
            'country' => 'Malaysia',
            'date_of_birth' => '1979-09-09',
            'gender' => 'male',
            'position' => 'Usahawan',
            'employer' => 'Faizal Trading',
            'membership_status' => MemberStatus::Suspended->value,
            'joined_at' => now()->subMonths(18),
            'approved_at' => now()->subMonths(18),
            'approved_by' => $approverId,
            'notes' => 'Status digantung untuk demo pengurusan status.',
        ]);
    }
}