<?php

namespace App\Http\Controllers\Member;

use App\Enums\AnnouncementAudience;
use App\Enums\DocumentVisibility;
use App\Models\Announcement;
use App\Models\Document;
use App\Models\MembershipApplication;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends MemberPortalController
{
    public function __invoke(Request $request): Response
    {
        $user = $this->currentUser($request);
        $member = $this->currentMemberOrNull($request);
        $cooperativeId = $this->activeCooperativeId($request);
        $application = $member ? $this->latestApplication($member->id, $member->cooperative_id) : null;

        $documents = Document::query()
            ->published()
            ->where('cooperative_id', $cooperativeId)
            ->where(function ($query) use ($member): void {
                $query->where('visibility', DocumentVisibility::MembersOnly->value)
                    ->when($member, function ($query) use ($member): void {
                        $query->orWhere(function ($query) use ($member): void {
                            $query->where('visibility', DocumentVisibility::SpecificMember->value)
                                ->where('member_id', $member->id);
                        });
                    });
            })
            ->latest('published_at')
            ->latest('updated_at')
            ->limit(3)
            ->get()
            ->map(fn (Document $document) => [
                'id' => $document->id,
                'title' => $document->title,
                'visibility' => $document->visibility->value,
                'file_size_label' => $this->formatBytes($document->file_size),
                'updated_at' => $document->updated_at?->format('d/m/Y H:i'),
                'download_url' => route('member.documents.download', $document),
            ])
            ->all();

        $announcements = Announcement::query()
            ->published()
            ->ordered()
            ->where('cooperative_id', $cooperativeId)
            ->whereIn('audience', [
                AnnouncementAudience::Public->value,
                AnnouncementAudience::Members->value,
            ])
            ->limit(4)
            ->get()
            ->map(fn (Announcement $announcement) => [
                'id' => $announcement->id,
                'title' => $announcement->title,
                'summary' => $announcement->summary,
                'audience' => $announcement->audience->value,
                'published_at' => $announcement->published_at?->format('d/m/Y'),
            ])
            ->all();

        return Inertia::render('Member/Pages/Dashboard', [
            'member' => [
                'is_linked' => (bool) $member,
                'member_no' => $member?->member_no,
                'full_name' => $member?->full_name ?? $user->name,
                'membership_status' => $member?->membership_status->value ?? 'inactive',
                'joined_at' => $member?->joined_at?->format('d/m/Y'),
            ],
            'application' => $application ? [
                'application_no' => $application->application_no,
                'status' => $application->status->value,
                'submitted_at' => $application->submitted_at?->format('d/m/Y H:i'),
            ] : null,
            'quickActions' => [
                [
                    'label' => 'Kemaskini Profil',
                    'description' => 'Semak dan kemas kini maklumat hubungan anda.',
                    'href' => route('member.profile'),
                    'icon' => 'UserRound',
                ],
                [
                    'label' => 'Lihat Dokumen',
                    'description' => 'Akses dokumen ahli yang tersedia untuk akaun anda.',
                    'href' => route('member.documents.index'),
                    'icon' => 'FileText',
                ],
                [
                    'label' => 'Semak Permohonan',
                    'description' => 'Lihat status permohonan keahlian yang dipautkan.',
                    'href' => route('member.applications.index'),
                    'icon' => 'ClipboardList',
                ],
                [
                    'label' => 'Hantar Aduan',
                    'description' => 'Hantar aduan atau cadangan dan semak maklum balas admin.',
                    'href' => route('member.complaints.index'),
                    'icon' => 'MessagesSquare',
                ],
            ],
            'recentDocuments' => $documents,
            'latestAnnouncements' => $announcements,
        ]);
    }

    private function latestApplication(int $memberId, int $cooperativeId): ?MembershipApplication
    {
        return MembershipApplication::query()
            ->where('cooperative_id', $cooperativeId)
            ->where('approved_member_id', $memberId)
            ->latest('submitted_at')
            ->first();
    }
}
