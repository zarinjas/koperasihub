<?php

namespace Database\Seeders;

use App\Models\Cooperative;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Database\Seeder;

class UnitDemoSeeder extends Seeder
{
    public function run(): void
    {
        $cooperative = Cooperative::query()->where('slug', 'koperasi-demo-berhad')->first();

        if (! $cooperative) {
            return;
        }

        $superAdminId = User::query()->where('email', 'superadmin@koperasihub.test')->value('id');

        $units = [
            ['name' => 'Unit Peruncitan'],
            ['name' => 'Unit Sumber Manusia'],
            ['name' => 'Unit Kewangan'],
            ['name' => 'Unit IT'],
            ['name' => 'Unit Keanggotaan'],
            ['name' => 'Unit Pinjaman'],
        ];

        foreach ($units as $unit) {
            Unit::query()->firstOrCreate([
                'cooperative_id' => $cooperative->id,
                'slug' => str($unit['name'])->slug()->value(),
            ], [
                'name' => $unit['name'],
                'description' => "{$unit['name']} — unit operasi koperasi.",
                'is_active' => true,
                'created_by' => $superAdminId,
                'updated_by' => $superAdminId,
            ]);
        }
    }
}