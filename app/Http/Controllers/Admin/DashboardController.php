<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ComplaintStatus;
use App\Enums\FinancingApplicationStatus;
use App\Enums\FormSubmissionStatus;
use App\Enums\MemberStatus;
use App\Enums\MembershipApplicationStatus;
use App\Http\Controllers\Concerns\InteractsWithActiveCooperative;
use App\Http\Controllers\Controller;
use App\Models\Complaint;
use App\Models\FinancingApplication;
use App\Models\FormCategory;
use App\Models\FormSubmission;
use App\Models\Member;
use App\Models\MembershipApplication;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    use InteractsWithActiveCooperative;

    public function __invoke(Request $request): Response
    {
        $cooperative = $this->activeCooperative();

        if (! $cooperative) {
            return Inertia::render('Admin/Pages/Dashboard', [
                'stats' => [],
                'charts' => [
                    'submissionsByStatus' => ['labels' => [], 'data' => [], 'colors' => []],
                    'submissionsByUnit' => ['labels' => [], 'data' => []],
                    'membersByMonth' => ['labels' => [], 'data' => []],
                    'complaintsByStatus' => ['labels' => [], 'data' => [], 'colors' => []],
                ],
            ]);
        }

        $cooperativeId = $cooperative->id;

        $membersTotal = Member::query()->where('cooperative_id', $cooperativeId)->count();
        $membersActive = Member::query()->where('cooperative_id', $cooperativeId)->where('membership_status', MemberStatus::Active->value)->count();
        $submissionsTotal = FormSubmission::query()->where('cooperative_id', $cooperativeId)->count();
        $submissionsPending = FormSubmission::query()->where('cooperative_id', $cooperativeId)->whereIn('status', [
            FormSubmissionStatus::PendingStampUpload->value,
            FormSubmissionStatus::Submitted->value,
            FormSubmissionStatus::UnderReview->value,
        ])->count();
        $complaintsTotal = Complaint::query()->where('cooperative_id', $cooperativeId)->count();
        $complaintsOpen = Complaint::query()->where('cooperative_id', $cooperativeId)->whereIn('status', [
            ComplaintStatus::Open->value,
            ComplaintStatus::InProgress->value,
        ])->count();
        $membershipPending = MembershipApplication::query()->forCooperative($cooperativeId)
            ->whereIn('status', [
                MembershipApplicationStatus::Pending->value,
                MembershipApplicationStatus::UnderReview->value,
            ])->count();
        $financingPending = FinancingApplication::query()->where('cooperative_id', $cooperativeId)
            ->whereIn('status', array_map(fn ($s) => $s->value, FinancingApplicationStatus::active()))->count();

        $totalPending = $membershipPending + $submissionsPending + $financingPending;

        $submissionsByStatus = $this->submissionsByStatus($cooperativeId);
        $submissionsByUnit = $this->submissionsByUnit($cooperativeId);
        $membersByMonth = $this->membersByMonth($cooperativeId);
        $complaintsByStatus = $this->complaintsByStatus($cooperativeId);

        return Inertia::render('Admin/Pages/Dashboard', [
            'stats' => [
                [
                    'label' => 'Menunggu Tindakan',
                    'value' => $totalPending,
                    'suffix' => 'perlu semakan',
                    'description' => 'Jumlah semua permohonan yang menunggu tindakan admin.',
                    'icon' => 'FileCheck',
                    'tone' => 'danger',
                ],
                [
                    'label' => 'Jumlah Ahli',
                    'value' => $membersTotal,
                    'suffix' => 'aktif: '.$membersActive,
                    'description' => 'Keseluruhan rekod keahlian.',
                    'icon' => 'Users',
                    'tone' => 'info',
                ],
                [
                    'label' => 'Permohonan Keahlian',
                    'value' => $membershipPending,
                    'suffix' => 'menunggu',
                    'description' => 'Permohonan keahlian baharu yang belum diproses.',
                    'icon' => 'ClipboardCheck',
                    'tone' => 'warning',
                ],
                [
                    'label' => 'Permohonan Borang',
                    'value' => $submissionsTotal,
                    'suffix' => 'belum selesai: '.$submissionsPending,
                    'description' => 'Jumlah keseluruhan permohonan borang.',
                    'icon' => 'FileCheck',
                    'tone' => 'warning',
                ],
                [
                    'label' => 'Permohonan Pembiayaan',
                    'value' => $financingPending,
                    'suffix' => 'menunggu',
                    'description' => 'Permohonan pembiayaan yang sedang dalam proses.',
                    'icon' => 'HandCoins',
                    'tone' => 'warning',
                ],
                [
                    'label' => 'Aduan & Cadangan',
                    'value' => $complaintsTotal,
                    'suffix' => 'terbuka: '.$complaintsOpen,
                    'description' => 'Tiket aduan dan cadangan ahli.',
                    'icon' => 'MessagesSquare',
                    'tone' => 'danger',
                ],
            ],
            'charts' => [
                'submissionsByStatus' => $submissionsByStatus,
                'submissionsByUnit' => $submissionsByUnit,
                'membersByMonth' => $membersByMonth,
                'complaintsByStatus' => $complaintsByStatus,
            ],
        ]);
    }

    private function submissionsByStatus(int $cooperativeId): array
    {
        $counts = FormSubmission::query()
            ->where('cooperative_id', $cooperativeId)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->all();

        $labels = [];
        $data = [];
        $colors = [];

        $map = [
            FormSubmissionStatus::PendingStampUpload->value => ['label' => 'Menunggu Cop', 'color' => '#F59E0B'],
            FormSubmissionStatus::Submitted->value => ['label' => 'Dihantar', 'color' => '#3B82F6'],
            FormSubmissionStatus::UnderReview->value => ['label' => 'Dalam Proses', 'color' => '#EAB308'],
            FormSubmissionStatus::IncompleteDocuments->value => ['label' => 'Tak Lengkap', 'color' => '#EF4444'],
            FormSubmissionStatus::Approved->value => ['label' => 'Diluluskan', 'color' => '#16A34A'],
            FormSubmissionStatus::Rejected->value => ['label' => 'Ditolak', 'color' => '#DC2626'],
            FormSubmissionStatus::Closed->value => ['label' => 'Ditutup', 'color' => '#64748B'],
        ];

        foreach ($map as $status => $config) {
            if (($counts[$status] ?? 0) > 0) {
                $labels[] = $config['label'];
                $data[] = $counts[$status];
                $colors[] = $config['color'];
            }
        }

        return compact('labels', 'data', 'colors');
    }

    private function submissionsByUnit(int $cooperativeId): array
    {
        $categories = FormCategory::query()
            ->where('cooperative_id', $cooperativeId)
            ->active()
            ->whereHas('forms.submissions')
            ->withCount(['forms as total' => fn ($q) => $q->whereHas('submissions')])
            ->get();

        $units = $categories->map(function ($cat) {
            $count = FormSubmission::query()
                ->where('cooperative_id', $cat->cooperative_id)
                ->whereHas('form', fn ($q) => $q->where('form_category_id', $cat->id))
                ->count();

            return ['label' => $cat->name, 'count' => $count];
        })->sortByDesc('count')->values();

        return [
            'labels' => $units->pluck('label')->all(),
            'data' => $units->pluck('count')->all(),
        ];
    }

    private function membersByMonth(int $cooperativeId): array
    {
        $labels = [];
        $data = [];

        for ($i = 5; $i >= 0; $i--) {
            $month = now()->subMonths($i)->startOfMonth();
            $labels[] = $month->translatedFormat('M Y');
            $data[] = Member::query()
                ->where('cooperative_id', $cooperativeId)
                ->whereBetween('joined_at', [$month, $month->copy()->endOfMonth()])
                ->count();
        }

        return compact('labels', 'data');
    }

    private function complaintsByStatus(int $cooperativeId): array
    {
        $counts = Complaint::query()
            ->where('cooperative_id', $cooperativeId)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->all();

        $labels = [];
        $data = [];
        $colors = [];

        $map = [
            ComplaintStatus::Open->value => ['label' => 'Terbuka', 'color' => '#F59E0B'],
            ComplaintStatus::InProgress->value => ['label' => 'Dalam Tindakan', 'color' => '#3B82F6'],
            ComplaintStatus::Resolved->value => ['label' => 'Selesai', 'color' => '#16A34A'],
            ComplaintStatus::Closed->value => ['label' => 'Ditutup', 'color' => '#64748B'],
        ];

        foreach ($map as $status => $config) {
            $count = $counts[$status] ?? 0;
            $labels[] = $config['label'];
            $data[] = $count;
            $colors[] = $config['color'];
        }

        return compact('labels', 'data', 'colors');
    }
}