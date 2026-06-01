<?php

namespace Database\Seeders;

use App\Enums\AttendanceMethod;
use App\Enums\ProgramStatus;
use App\Enums\RsvpResponse;
use App\Models\Cooperative;
use App\Models\Member;
use App\Models\Program;
use App\Models\ProgramRsvp;
use App\Models\User;
use Illuminate\Database\Seeder;

class ProgramDemoSeeder extends Seeder
{
    public function run(): void
    {
        $cooperative = Cooperative::query()->where('slug', 'koperasi-demo-berhad')->first();

        if (! $cooperative) {
            return;
        }

        $adminId = User::query()->where('email', 'admin@koperasihub.test')->value('id');
        $memberUserIds = Member::query()
            ->where('cooperative_id', $cooperative->id)
            ->where('membership_status', 'active')
            ->pluck('id')
            ->toArray();

        $programsData = [
            [
                'title' => 'Mesyuarat Agung Tahunan 2026',
                'category' => 'agm',
                'program_type' => 'physical',
                'location' => 'Dewan Serbaguna Koperasi, Lot 1234, Jalan Koperasi',
                'capacity' => 300,
                'start_date' => now()->addMonths(2),
                'end_date' => now()->addMonths(2)->addHours(5),
                'registration_deadline' => now()->addMonths(2)->subWeek(),
                'description' => "Mesyuarat Agung Tahunan (MAT) Koperasi Demo Berhad bagi tahun kewangan 2025.\n\nAntara agenda:\n- Pembentangan Laporan Tahunan\n- Pengesahan Penyata Kewangan\n- Pelantikan Juruaudit\n- Pembahagian Dividen\n- Pemilihan Ahli Lembaga",
                'status' => ProgramStatus::Published->value,
                'is_featured' => true,
            ],
            [
                'title' => 'Seminar Kewangan Ahli 2026',
                'category' => 'seminar',
                'program_type' => 'hybrid',
                'location' => 'Hotel Grand Riverview, Kajang',
                'online_url' => 'https://zoom.us/j/koperasihub-seminar',
                'capacity' => 150,
                'start_date' => now()->addMonth(),
                'end_date' => now()->addMonth()->addDays(1),
                'registration_deadline' => now()->addMonth()->subWeek(),
                'description' => "Seminar kewangan untuk ahli koperasi. Topik merangkumi pengurusan kewangan peribadi, pelaburan koperasi, dan perancangan persaraan.\n\nYuran: Percuma untuk ahli koperasi.\n\nTermasuk: Makan tengahari, kit seminar, dan sijil penyertaan.",
                'status' => ProgramStatus::Published->value,
                'is_featured' => true,
            ],
            [
                'title' => 'Kursus Asas Keusahawanan Digital',
                'category' => 'kursus',
                'program_type' => 'online',
                'online_url' => 'https://meet.google.com/koperasi-2026',
                'capacity' => 50,
                'start_date' => now()->addWeeks(3),
                'end_date' => now()->addWeeks(3)->addHours(3),
                'registration_deadline' => now()->addWeeks(2),
                'description' => "Kursus percuma untuk ahli yang berminat menceburi bidang perniagaan digital. Peserta akan belajar asas pemasaran media sosial, pembangunan laman web mudah, dan pengurusan kedai online.",
                'status' => ProgramStatus::Published->value,
                'is_featured' => false,
            ],
            [
                'title' => 'Hari Keluarga Koperasi Demo Berhad',
                'category' => 'community',
                'program_type' => 'physical',
                'location' => 'Taman Botani Shah Alam',
                'capacity' => 500,
                'start_date' => now()->addMonths(3),
                'end_date' => now()->addMonths(3)->addDays(1),
                'registration_deadline' => now()->addMonths(3)->subWeeks(2),
                'description' => "Hari keluarga tahunan untuk semua ahli koperasi dan keluarga.\n\nAktiviti:\n- Sukaneka keluarga\n- Cabutan bertuah\n- Jualan murah\n- Aktiviti kanak-kanak\n- Makan bersama",
                'status' => ProgramStatus::Published->value,
                'is_featured' => true,
            ],
            [
                'title' => 'Webinar: Pelaburan Bijak untuk Ahli Koperasi',
                'category' => 'webinar',
                'program_type' => 'online',
                'online_url' => 'https://zoom.us/j/webinar-pelaburan',
                'capacity' => 200,
                'start_date' => now()->addWeeks(1)->addDays(2),
                'end_date' => now()->addWeeks(1)->addDays(2)->addHours(2),
                'registration_deadline' => now()->addWeeks(1)->addDay(),
                'description' => "Webinar santai tentang asas pelaburan yang sesuai untuk ahli koperasi. Dibimbing oleh pensijil kewangan bertauliah. Topik termasuk pelaburan dalam koperasi, ASNB, dan emas.",
                'status' => ProgramStatus::Published->value,
                'is_featured' => false,
            ],
            [
                'title' => 'Program Gotong Royong Komuniti',
                'category' => 'volunteer',
                'program_type' => 'physical',
                'location' => 'Kawasan Perumahan Ahli Koperasi, Taman Sejahtera',
                'capacity' => 80,
                'start_date' => now()->addWeeks(4),
                'end_date' => now()->addWeeks(4)->addHours(6),
                'registration_deadline' => now()->addWeeks(3)->addDays(5),
                'description' => "Program sukarelawan membersihkan dan menceriakan kawasan perumahan ahli koperasi. Semua ahli dialu-alukan.\n\nSumbangan: Sediakan peralatan kebersihan asas.\nMakan tengahari dan minuman disediakan.",
                'status' => ProgramStatus::Published->value,
                'is_featured' => false,
            ],
            [
                'title' => 'Seminar Perancangan Persaraan',
                'category' => 'seminar',
                'program_type' => 'physical',
                'location' => 'Pejabat Koperasi, Aras 3, Wisma Koperasi',
                'capacity' => 40,
                'start_date' => now()->addMonths(1)->addWeeks(2),
                'end_date' => now()->addMonths(1)->addWeeks(2)->addHours(4),
                'registration_deadline' => now()->addMonths(1)->addWeek(),
                'description' => 'Seminar eksklusif untuk ahli yang bakal bersara. Ketahui cara merancang kewangan persaraan dengan bijak menggunakan kemudahan dan produk koperasi.',
                'status' => ProgramStatus::Draft->value,
                'is_featured' => false,
            ],
            [
                'title' => 'Mesyuarat Agung Tahunan 2025',
                'category' => 'agm',
                'program_type' => 'physical',
                'location' => 'Dewan Serbaguna Koperasi',
                'capacity' => 280,
                'start_date' => now()->subMonths(6),
                'end_date' => now()->subMonths(6)->addHours(5),
                'description' => "Mesyuarat Agung Tahunan (MAT) Koperasi Demo Berhad bagi tahun kewangan 2024. (Acara lepas)",
                'status' => ProgramStatus::Completed->value,
                'is_featured' => false,
            ],
            [
                'title' => 'Seminar Kewangan 2025',
                'category' => 'seminar',
                'program_type' => 'physical',
                'location' => 'Hotel Grand Riverview',
                'capacity' => 120,
                'start_date' => now()->subMonths(4),
                'end_date' => now()->subMonths(4)->addDays(1),
                'description' => 'Seminar kewangan tahun lepas. (Acara lepas)',
                'status' => ProgramStatus::Completed->value,
                'is_featured' => false,
            ],
        ];

        $programIds = [];
        foreach ($programsData as $data) {
            $program = Program::query()->updateOrCreate(
                ['cooperative_id' => $cooperative->id, 'title' => $data['title']],
                [
                    'slug' => str($data['title'])->slug(),
                    'cooperative_id' => $cooperative->id,
                    'description' => $data['description'] ?? null,
                    'category' => $data['category'] ?? null,
                    'program_type' => $data['program_type'] ?? 'physical',
                    'location' => $data['location'] ?? null,
                    'online_url' => $data['online_url'] ?? null,
                    'capacity' => $data['capacity'] ?? null,
                    'start_date' => $data['start_date'],
                    'end_date' => $data['end_date'] ?? null,
                    'registration_deadline' => $data['registration_deadline'] ?? null,
                    'status' => $data['status'] ?? ProgramStatus::Published->value,
                    'is_featured' => $data['is_featured'] ?? false,
                    'created_by' => $adminId,
                    'updated_by' => $adminId,
                ],
            );
            $programIds[] = $program->id;
        }

        if ($memberUserIds === []) {
            return;
        }

        $responses = [RsvpResponse::Hadir->value, RsvpResponse::Hadir->value, RsvpResponse::Hadir->value, RsvpResponse::Mungkin->value, RsvpResponse::TidakHadir->value];

        foreach ($programIds as $programId) {
            $assigned = [];
            $numRsvps = min(count($memberUserIds), rand(4, 8));

            $shuffled = $memberUserIds;
            shuffle($shuffled);
            $selectedMembers = array_slice($shuffled, 0, $numRsvps);

            foreach ($selectedMembers as $memberId) {
                $response = $responses[array_rand($responses)];
                $rsvp = ProgramRsvp::query()->updateOrCreate(
                    ['program_id' => $programId, 'member_id' => $memberId],
                    [
                        'cooperative_id' => $cooperative->id,
                        'response' => $response,
                        'responded_at' => now()->subDays(rand(0, 14)),
                    ],
                );

                if ($response === RsvpResponse::Hadir->value && rand(0, 1) === 1) {
                    $rsvp->update([
                        'checked_in_at' => $rsvp->responded_at?->copy()->addHours(rand(1, 5)),
                        'checked_in_by' => $adminId,
                        'attendance_method' => AttendanceMethod::ManualEntry->value,
                    ]);
                }

                $assigned[] = $memberId;
            }
        }
    }
}
