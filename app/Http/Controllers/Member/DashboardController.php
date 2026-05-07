<?php

namespace App\Http\Controllers\Member;

use App\Enums\AnnouncementAudience;
use App\Enums\FinancingApplicationStatus;
use App\Models\Announcement;
use App\Models\FinancingApplication;
use App\Models\MemberContribution;
use App\Models\MembershipApplication;
use App\Models\OnlineForm;
use App\Models\Poster;
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
        $financingSummary = $member ? $this->financingSummary($member) : null;
        $caruman = $member ? $this->carumanSummary($member, $cooperativeId) : null;

        $posters = Poster::query()
            ->where('cooperative_id', $cooperativeId)
            ->published()
            ->ordered()
            ->limit(8)
            ->get()
            ->map(fn (Poster $poster) => [
                'id' => $poster->id,
                'title' => $poster->title,
                'image_url' => $poster->imageUrl(),
                'alt_text' => $poster->alt_text,
            ])
            ->all();

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
                    'label' => 'Mohon Pembiayaan Baru',
                    'description' => 'Semak produk pembiayaan yang tersedia dan mulakan permohonan baharu.',
                    'href' => route('member.financing.index'),
                    'icon' => 'HandCoins',
                ],
                [
                    'label' => 'Hantar Aduan',
                    'description' => 'Hantar aduan atau cadangan dan semak maklum balas admin.',
                    'href' => route('member.complaints.index'),
                    'icon' => 'MessagesSquare',
                ],
            ],
            'posters' => $posters,
            'caruman' => $caruman,
            'featuredForms' => $forms,
            'latestAnnouncements' => $announcements,
            'financingSummary' => $financingSummary,
        ]);
    }

    private function carumanSummary(object $member, ?int $cooperativeId): ?array
    {
        $currentYear = (int) now()->format('Y');

        $availableYears = MemberContribution::query()
            ->forCooperative($cooperativeId)
            ->where('member_id', $member->id)
            ->pluck('year')
            ->unique()
            ->sortDesc()
            ->values();

        if ($availableYears->isEmpty()) {
            return null;
        }

        $year = $availableYears->contains($currentYear) ? $currentYear : $availableYears->first();

        $contribution = MemberContribution::query()
            ->forCooperative($cooperativeId)
            ->where('member_id', $member->id)
            ->year($year)
            ->first();

        if (! $contribution) {
            return null;
        }

        return [
            'id' => $contribution->id,
            'year' => $contribution->year,
            'caruman_semasa' => (float) $contribution->caruman_semasa,
            'caruman_keseluruhan' => (float) $contribution->caruman_keseluruhan,
            'dividen' => (float) $contribution->dividen,
            'available_years' => $availableYears->all(),
        ];
    }

    private function financingSummary(object $member): array
    {
        $underReview = FinancingApplication::query()
            ->where('member_id', $member->id)
            ->where('cooperative_id', $member->cooperative_id)
            ->whereIn('status', [
                FinancingApplicationStatus::Submitted->value,
                FinancingApplicationStatus::InReview->value,
                FinancingApplicationStatus::PendingGuarantor->value,
                FinancingApplicationStatus::PendingUpload->value,
                FinancingApplicationStatus::Incomplete->value,
            ])
            ->count();

        $guarantorRequests = $member->financingGuarantorRequests()
            ->where('status', 'pending')
            ->count();

        return [
            'under_review' => $underReview,
            'guarantor_requests' => $guarantorRequests,
        ];
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
