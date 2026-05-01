<?php

namespace Database\Seeders;

use App\Models\User;
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
        User::query()->updateOrCreate([
            'email' => 'admin@koperasihub.test',
        ], [
            'name' => 'Pentadbir Demo',
            'role' => User::ROLE_ADMIN,
            'password' => Hash::make('password'),
        ]);

        User::query()->updateOrCreate([
            'email' => 'member@koperasihub.test',
        ], [
            'name' => 'Ahli Demo',
            'role' => User::ROLE_MEMBER,
            'password' => Hash::make('password'),
        ]);
    }
}
