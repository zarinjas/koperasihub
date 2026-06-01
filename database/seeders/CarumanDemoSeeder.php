<?php

namespace Database\Seeders;

use App\Models\Cooperative;
use App\Models\Member;
use App\Models\MemberContribution;
use App\Models\User;
use Illuminate\Database\Seeder;

class CarumanDemoSeeder extends Seeder
{
    public function run(): void
    {
        $cooperative = Cooperative::query()->where('slug', 'koperasi-demo-berhad')->first();

        if (! $cooperative) {
            return;
        }

        $members = Member::query()
            ->where('cooperative_id', $cooperative->id)
            ->orderBy('member_no')
            ->limit(3)
            ->get();

        if ($members->count() < 3) {
            return;
        }

        $memberIds = $members->pluck('id')->values()->all();

        $adminId = User::query()->where('email', 'admin@koperasihub.test')->value('id');

        $data = [
            ['member_id' => $memberIds[0], 'year' => 2024, 'caruman_semasa' => 3600.00, 'caruman_keseluruhan' => 28800.00, 'dividen' => 1200.00],
            ['member_id' => $memberIds[0], 'year' => 2025, 'caruman_semasa' => 4800.00, 'caruman_keseluruhan' => 33600.00, 'dividen' => 1800.00],
            ['member_id' => $memberIds[0], 'year' => 2026, 'caruman_semasa' => 2400.00, 'caruman_keseluruhan' => 36000.00, 'dividen' => 600.00],
            ['member_id' => $memberIds[1], 'year' => 2024, 'caruman_semasa' => 2400.00, 'caruman_keseluruhan' => 15600.00, 'dividen' => 600.00],
            ['member_id' => $memberIds[1], 'year' => 2025, 'caruman_semasa' => 3000.00, 'caruman_keseluruhan' => 18600.00, 'dividen' => 900.00],
            ['member_id' => $memberIds[2], 'year' => 2024, 'caruman_semasa' => 6000.00, 'caruman_keseluruhan' => 42000.00, 'dividen' => 2400.00],
            ['member_id' => $memberIds[2], 'year' => 2025, 'caruman_semasa' => 7200.00, 'caruman_keseluruhan' => 49200.00, 'dividen' => 3000.00],
            ['member_id' => $memberIds[2], 'year' => 2026, 'caruman_semasa' => 3600.00, 'caruman_keseluruhan' => 52800.00, 'dividen' => 1200.00],
        ];

        foreach ($data as $row) {
            MemberContribution::query()->firstOrCreate([
                'cooperative_id' => $cooperative->id,
                'member_id' => $row['member_id'],
                'year' => $row['year'],
            ], [
                'caruman_semasa' => $row['caruman_semasa'],
                'caruman_keseluruhan' => $row['caruman_keseluruhan'],
                'dividen' => $row['dividen'],
                'created_by' => $adminId,
                'updated_by' => $adminId,
            ]);
        }
    }
}