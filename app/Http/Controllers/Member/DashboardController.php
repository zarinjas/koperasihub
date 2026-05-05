<?php

namespace App\Http\Controllers\Member;

use App\Enums\AnnouncementAudience;
use App\Models\Announcement;
use App\Models\MembershipApplication;
use App\Models\OnlineForm;
use App\Services\MemberCardService;
use App\Services\Files\MemberPhotoStorageService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends MemberPortalController
{
    public function __construct(
        private readonly MemberPhotoStorageService $memberPhotos,
        private readonly MemberCardService $memberCards,
    ) {
    }

    public function __invoke(Request $request): Response
    {
        $user = $this->currentUser($request);
        $member = $this->currentMemberOrNull($request);
        $cooperativeId = $this->activeCooperativeId($request);
        $application = $member ? $this->latestApplication($member->id, $member->cooperative_id) : null;

        $forms = OnlineForm::query()
            ->published()
            ->where('cooperative_id', $cooperativeId)
            ->with('category')
            ->whereHas('category', fn ($query) => $query->where('is_active', true))
            ->orderBy('sort_order')
            ->latest('updated_at')
            ->limit(4)
            ->get()
            ->map(fn (OnlineForm $form) => [
                'id' => $form->id,
                'title' => $form->title,
                'description' => $form->description,
                'category_name' => $form->category?->name,
                'visibility' => $form->visibility->value,
                'visibility_label' => $form->visibility->value === 'members_only' ? 'Ahli sahaja' : 'Terbuka',
                'updated_at' => $form->updated_at?->format('d/m/Y H:i'),
                'url' => route('public.forms.show', $form->slug),
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
                'profile_photo_url' => $this->memberPhotos->url($member?->profile_photo_path),
                'full_name' => $member?->full_name ?? $user->name,
                'membership_status' => $member?->membership_status->value ?? 'inactive',
                'joined_at' => $member?->joined_at?->format('d/m/Y'),
            ],
            'digitalCard' => $member ? [
                ...$this->memberCards->memberPayload($member),
                'view_url' => route('member.card'),
            ] : null,
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
                    'label' => 'Permohonan Borang',
                    'description' => 'Isi borang permohonan dan semak status permohonan anda.',
                    'href' => route('member.applications.index'),
                    'icon' => 'FileCheck',
                ],
                [
                    'label' => 'Hantar Aduan',
                    'description' => 'Hantar aduan atau cadangan dan semak maklum balas admin.',
                    'href' => route('member.complaints.index'),
                    'icon' => 'MessagesSquare',
                ],
            ],
            'featuredForms' => $forms,
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
