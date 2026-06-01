<?php

namespace Database\Seeders;

use App\Enums\ComplaintPriority;
use App\Enums\ComplaintStatus;
use App\Models\Complaint;
use App\Models\Cooperative;
use App\Models\Member;
use App\Models\User;
use Illuminate\Database\Seeder;

class ComplaintDemoSeeder extends Seeder
{
    public function run(): void
    {
        $cooperative = Cooperative::query()->where('slug', 'koperasi-demo-berhad')->first();

        if (! $cooperative) {
            return;
        }

        $admin = User::query()->where('email', 'admin@koperasihub.test')->first();
        $memberUser = User::query()->where('email', 'member@koperasihub.test')->first();
        $member = Member::query()->where('cooperative_id', $cooperative->id)->where('user_id', $memberUser?->id)->first();

        if (! $admin || ! $memberUser) {
            return;
        }

        $complaints = [
            [
                'ticket_no' => 'ADU-'.now()->format('Ymd').'-0001',
                'category' => 'aduan',
                'subject' => 'Kesukaran log masuk portal ahli',
                'message' => 'Saya menghadapi masalah untuk log masuk pada waktu malam dan paparan menjadi sangat perlahan.',
                'status' => ComplaintStatus::Open->value,
                'priority' => ComplaintPriority::High->value,
                'assigned_to' => $admin->id,
                'closed_at' => null,
            ],
            [
                'ticket_no' => 'ADU-'.now()->format('Ymd').'-0002',
                'category' => 'cadangan',
                'subject' => 'Cadangan tambah panduan penggunaan portal',
                'message' => 'Mohon sediakan panduan ringkas untuk ahli baharu supaya lebih mudah menggunakan portal.',
                'status' => ComplaintStatus::InProgress->value,
                'priority' => ComplaintPriority::Medium->value,
                'assigned_to' => $admin->id,
                'closed_at' => null,
            ],
            [
                'ticket_no' => 'ADU-'.now()->format('Ymd').'-0003',
                'category' => 'dokumen',
                'subject' => 'Dokumen tidak dapat dimuat turun',
                'message' => 'Fail panduan ahli memaparkan ralat semasa cuba dimuat turun dari portal.',
                'status' => ComplaintStatus::Resolved->value,
                'priority' => ComplaintPriority::Medium->value,
                'assigned_to' => $admin->id,
                'closed_at' => null,
            ],
            [
                'ticket_no' => 'ADU-'.now()->format('Ymd').'-0004',
                'category' => 'keahlian',
                'subject' => 'Maklumat nombor telefon belum dikemas kini',
                'message' => 'Saya telah kemas kini nombor telefon tetapi paparan lama masih kelihatan di portal.',
                'status' => ComplaintStatus::Closed->value,
                'priority' => ComplaintPriority::Low->value,
                'assigned_to' => $admin->id,
                'closed_at' => now()->subDay(),
            ],
            [
                'ticket_no' => 'ADU-'.now()->format('Ymd').'-0005',
                'category' => 'portal',
                'subject' => 'Cadangan susunan menu yang lebih jelas',
                'message' => 'Menu dokumen dan permohonan boleh diletakkan lebih menonjol supaya mudah dicari.',
                'status' => ComplaintStatus::Open->value,
                'priority' => ComplaintPriority::Low->value,
                'assigned_to' => null,
                'closed_at' => null,
            ],
        ];

        foreach ($complaints as $item) {
            $complaint = Complaint::query()->updateOrCreate([
                'cooperative_id' => $cooperative->id,
                'ticket_no' => $item['ticket_no'],
            ], [
                'member_id' => $member?->id,
                'created_by' => $memberUser->id,
                'assigned_to' => $item['assigned_to'],
                'category' => $item['category'],
                'subject' => $item['subject'],
                'message' => $item['message'],
                'status' => $item['status'],
                'priority' => $item['priority'],
                'closed_at' => $item['closed_at'],
            ]);

            if ($complaint->replies()->count() === 0 && $item['assigned_to']) {
                $complaint->replies()->create([
                    'user_id' => $admin->id,
                    'message' => 'Terima kasih. Aduan ini telah diterima dan sedang disemak oleh pihak admin.',
                    'is_internal' => false,
                ]);
            }
        }
    }
}