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
            ['name' => 'Unit Peruncitan', 'sort_order' => 1],
            ['name' => 'Unit Sumber Manusia', 'sort_order' => 2],
            ['name' => 'Unit Kewangan', 'sort_order' => 3],
            ['name' => 'Unit IT', 'sort_order' => 4],
            ['name' => 'Unit Keanggotaan', 'sort_order' => 5],
            ['name' => 'Unit Pinjaman', 'sort_order' => 6],
        ];

        foreach ($units as $unit) {
            Unit::query()->firstOrCreate([
                'cooperative_id' => $cooperative->id,
                'slug' => str($unit['name'])->slug()->value(),
            ], [
                'name' => $unit['name'],
                'description' => "{$unit['name']} — unit operasi koperasi.",
                'is_active' => true,
                'sort_order' => $unit['sort_order'],
                'created_by' => $superAdminId,
                'updated_by' => $superAdminId,
            ]);
        }
    }
}