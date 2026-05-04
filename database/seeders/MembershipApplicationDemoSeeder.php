<?php

namespace Database\Seeders;

use App\Enums\MembershipApplicationStatus;
use App\Models\Cooperative;
use App\Models\MembershipApplication;
use App\Models\User;
use Illuminate\Database\Seeder;

class MembershipApplicationDemoSeeder extends Seeder
{
    public function run(): void
    {
        $cooperative = Cooperative::query()->where('slug', 'koperasi-demo-berhad')->first();
        $reviewerId = User::query()->where('email', 'admin@koperasihub.test')->value('id');

        if (! $cooperative) {
            return;
        }

        MembershipApplication::factory()->create([
            'cooperative_id' => $cooperative->id,
            'application_no' => 'APP-'.now()->format('Ymd').'-0001',
            'full_name' => 'Nur Aina Binti Hassan',
            'identity_no' => '900101105432',
            'email' => 'aina.hassan@example.test',
            'phone' => '0123456789',
            'status' => MembershipApplicationStatus::Pending->value,
            'submitted_at' => now()->subDays(2),
            'metadata' => [
                'membership_type' => 'Individu',
                'notes' => 'Berminat untuk menyertai program simpanan dan kebajikan ahli.',
            ],
        ]);

        MembershipApplication::factory()->underReview()->create([
            'cooperative_id' => $cooperative->id,
            'application_no' => 'APP-'.now()->format('Ymd').'-0002',
            'full_name' => 'Muhammad Firdaus Bin Rahman',
            'identity_no' => '880212106543',
            'email' => 'firdaus.rahman@example.test',
            'phone' => '0134567890',
            'reviewed_by' => $reviewerId,
            'review_notes' => 'Semakan dokumen sedang dibuat.',
            'submitted_at' => now()->subDay(),
            'reviewed_at' => now()->subHours(6),
            'metadata' => [
                'membership_type' => 'Individu',
            ],
        ]);

        MembershipApplication::factory()->approved()->create([
            'cooperative_id' => $cooperative->id,
            'application_no' => 'APP-'.now()->format('Ymd').'-0003',
            'full_name' => 'Ahli Demo',
            'identity_no' => '910101105555',
            'email' => 'member@koperasihub.test',
            'phone' => '0121111111',
            'reviewed_by' => $reviewerId,
            'review_notes' => 'Permohonan diluluskan selepas semakan dokumen lengkap.',
            'submitted_at' => now()->subMonths(8)->subDays(5),
            'reviewed_at' => now()->subMonths(8),
            'metadata' => [
                'membership_type' => 'Individu',
            ],
        ]);
    }
}
