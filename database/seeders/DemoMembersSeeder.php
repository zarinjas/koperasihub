<?php

namespace Database\Seeders;

use App\Enums\MemberStatus;
use App\Models\Cooperative;
use App\Models\Member;
use App\Models\User;
use App\Support\AccessControl;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoMembersSeeder extends Seeder
{
    public function run(): void
    {
        $cooperative = Cooperative::query()->where('slug', 'koperasi-demo-berhad')->first();
        if (! $cooperative) {
            return;
        }

        $admin = User::query()->where('email', 'admin@koperasihub.test')->first();
        $password = Hash::make('password');

        for ($i = 1; $i <= 30; $i++) {
            $email = 'member'.str_pad($i, 2, '0', STR_PAD_LEFT).'@koperasihub.test';

            if (User::query()->where('email', $email)->exists()) {
                continue;
            }

            $name = fake()->name();

            $user = User::query()->create([
                'cooperative_id' => $cooperative->id,
                'name' => $name,
                'email' => $email,
                'role' => User::ROLE_MEMBER,
                'user_type' => User::ROLE_MEMBER,
                'status' => 'active',
                'password' => $password,
                'email_verified_at' => now(),
            ]);
            $user->syncRoles([AccessControl::ROLE_MEMBER]);

            Member::factory()->create([
                'cooperative_id' => $cooperative->id,
                'user_id' => $user->id,
                'member_no' => 'DEMO-'.str_pad($i, 4, '0', STR_PAD_LEFT),
                'full_name' => $name,
                'email' => $email,
                'approved_by' => $admin?->id,
                'membership_status' => MemberStatus::Active->value,
            ]);
        }
    }
}
