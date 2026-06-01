<?php

namespace Database\Seeders;

use App\Models\Cooperative;
use App\Models\Unit;
use App\Models\User;
use App\Support\AccessControl;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(RolePermissionSeeder::class);
        $this->call(CooperativeSettingsSeeder::class);

        $cooperativeId = Cooperative::query()
            ->where('slug', 'koperasi-demo-berhad')
            ->value('id');

        $password = Hash::make('password');

        $superAdmin = User::query()->updateOrCreate([
            'email' => 'superadmin@koperasihub.test',
        ], [
            'name' => 'Super Admin Demo',
            'cooperative_id' => $cooperativeId,
            'role' => AccessControl::ROLE_SUPER_ADMIN,
            'user_type' => AccessControl::ROLE_SUPER_ADMIN,
            'status' => 'active',
            'password' => $password,
        ]);
        $superAdmin->syncRoles([AccessControl::ROLE_SUPER_ADMIN]);

        $admin = User::query()->updateOrCreate([
            'email' => 'admin@koperasihub.test',
        ], [
            'name' => 'Pentadbir Demo',
            'cooperative_id' => $cooperativeId,
            'role' => User::ROLE_ADMIN,
            'user_type' => User::ROLE_ADMIN,
            'status' => 'active',
            'password' => $password,
        ]);
        $admin->syncRoles([AccessControl::ROLE_ADMIN]);

        $member = User::query()->updateOrCreate([
            'email' => 'member@koperasihub.test',
        ], [
            'name' => 'Ahli Demo',
            'cooperative_id' => $cooperativeId,
            'role' => User::ROLE_MEMBER,
            'user_type' => User::ROLE_MEMBER,
            'status' => 'active',
            'password' => $password,
        ]);
        $member->syncRoles([AccessControl::ROLE_MEMBER]);

        User::query()
            ->whereIn('role', AccessControl::roles())
            ->each(fn (User $user) => $user->syncRoles([$user->role]));

        $this->call(CmsDemoSeeder::class);
        $this->call(ServiceDemoSeeder::class);
        $this->call(AnnouncementDemoSeeder::class);
        $this->call(NewsDemoSeeder::class);
        $this->call(DocumentsDemoSeeder::class);
        $this->call(MemberDemoSeeder::class);
        $this->call(MembershipApplicationDemoSeeder::class);
        $this->call(ComplaintDemoSeeder::class);
        $this->call(CarumanDemoSeeder::class);
        $this->call(UnitDemoSeeder::class);
        $this->call(FinancingDemoSeeder::class);
        $this->call(OnlineFormDemoSeeder::class);
        $this->call(PosterDemoSeeder::class);
        $this->call(ProgramDemoSeeder::class);
        $this->call(FormSubmissionDemoSeeder::class);

        $unitKeanggotaan = Unit::query()
            ->where('cooperative_id', $cooperativeId)
            ->where('slug', 'unit-keanggotaan')
            ->first();

        if ($unitKeanggotaan) {
            $admin->update([
                'unit_id' => $unitKeanggotaan->id,
                'staff_id' => 'STF001',
                'position_title' => 'Pegawai Keanggotaan',
            ]);
        }
    }
}
