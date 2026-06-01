<?php

namespace App\Http\Controllers\Admin;

use App\Enums\FinancingApplicationStatus;
use App\Enums\FormSubmissionStatus;
use App\Enums\MembershipApplicationStatus;
use App\Http\Controllers\Concerns\InteractsWithActiveCooperative;
use App\Http\Controllers\Controller;
use App\Models\FinancingApplication;
use App\Models\FinancingCategory;
use App\Models\FormCategory;
use App\Models\FormSubmission;
use App\Models\MembershipApplication;
use App\Support\AccessControl;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ReviewInboxController extends Controller
{
    use InteractsWithActiveCooperative;

    public function index(Request $request): Response
    {
        $cooperative = $this->activeCooperative();
        $user = $request->user();

        abort_unless($cooperative, 404);

        $type = $request->string('type')->toString();
        $search = trim((string) $request->string('search'));
        $status = $request->string('status')->toString();
        $category = $request->string('category')->toString();

        $allItems = collect();

        if ($user?->can(AccessControl::PERMISSION_VIEW_MEMBERSHIP_APPLICATIONS)) {
            $query = MembershipApplication::query()
                ->forCooperative($cooperative->id)
                ->with('reviewer')
                ->whereIn('status', [
                    MembershipApplicationStatus::Pending->value,
                    MembershipApplicationStatus::UnderReview->value,
                ]);

            if ($search !== '') {
                $query->where(function ($q) use ($search): void {
                    $q->where('application_no', 'like', "%{$search}%")
                        ->orWhere('full_name', 'like', "%{$search}%")
                        ->orWhere('identity_no', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            }

            if (in_array($status, MembershipApplicationStatus::values(), true)) {
                $query->where('status', $status);
            }

            if ($category !== '' && in_array($type, ['', 'keahlian'], true)) {
                $query->whereNotNull('metadata')->where('metadata->membership_type', $category);
            }

            if ($type === '' || $type === 'keahlian') {
                $allItems = $allItems->concat(
                    $query->latest('submitted_at')->limit(200)->get()->map(fn ($app) => [
                        'type' => 'keahlian',
                        'type_label' => 'Permohonan Keahlian',
                        'id' => $app->id,
                        'reference' => $app->application_no,
                        'applicant' => $app->full_name,
                        'identity_no' => $app->identity_no,
                        'status' => $app->status->value,
                        'submitted_at' => $app->submitted_at?->format('d/m/Y H:i'),
                        'detail_url' => route('admin.membership-applications.show', $app),
                        'can_approve' => $user->can(AccessControl::PERMISSION_APPROVE_MEMBERSHIP_APPLICATIONS),
                        'can_reject' => $user->can(AccessControl::PERMISSION_REJECT_MEMBERSHIP_APPLICATIONS),
                        'approve_url' => route('admin.membership-applications.approve', $app),
                        'reject_url' => route('admin.membership-applications.reject', $app),
                    ])
                );
            }
        }

        if ($user?->can(AccessControl::PERMISSION_VIEW_FORM_SUBMISSIONS)) {
            $query = FormSubmission::query()
                ->where('cooperative_id', $cooperative->id)
                ->with(['form.category', 'member'])
                ->whereIn('status', [
                    FormSubmissionStatus::PendingStampUpload->value,
                    FormSubmissionStatus::Submitted->value,
                    FormSubmissionStatus::IncompleteDocuments->value,
                ]);

            if ($search !== '') {
                $query->where(function ($q) use ($search): void {
                    $q->where('reference_no', 'like', "%{$search}%")
                        ->orWhere('submitted_by_name', 'like', "%{$search}%");
                });
            }

            if (in_array($status, FormSubmissionStatus::values(), true)) {
                $query->where('status', $status);
            }

            if ($category !== '' && in_array($type, ['', 'borang'], true)) {
                $query->whereHas('form', fn ($q) => $q->where('form_category_id', $category));
            }

            if ($type === '' || $type === 'borang') {
                $allItems = $allItems->concat(
                    $query->latest('submitted_at')->limit(200)->get()->map(fn ($sub) => [
                        'type' => 'borang',
                        'type_label' => 'Permohonan Borang',
                        'id' => $sub->id,
                        'reference' => $sub->reference_no,
                        'applicant' => $sub->submitted_by_name ?? $sub->member?->full_name ?? '-',
                        'status' => $sub->status->value,
                        'submitted_at' => $sub->submitted_at?->format('d/m/Y H:i'),
                        'detail_url' => route('admin.form-submissions.show', $sub),
                        'can_approve' => false,
                        'can_reject' => false,
                        'approve_url' => null,
                        'reject_url' => null,
                    ])
                );
            }
        }

        if ($user?->can(AccessControl::PERMISSION_VIEW_FINANCING)) {
            $query = FinancingApplication::query()
                ->where('cooperative_id', $cooperative->id)
                ->with(['member', 'product.category'])
                ->whereIn('status', array_map(fn ($s) => $s->value, FinancingApplicationStatus::active()));

            if ($search !== '') {
                $query->where(function ($q) use ($search): void {
                    $q->where('reference_no', 'like', "%{$search}%")
                        ->orWhereHas('member', fn ($m) => $m->where('full_name', 'like', "%{$search}%"));
                });
            }

            if (in_array($status, FinancingApplicationStatus::values(), true)) {
                $query->where('status', $status);
            }

            if ($category !== '' && in_array($type, ['', 'pembiayaan'], true)) {
                $query->where('financing_category_id', $category);
            }

            if ($type === '' || $type === 'pembiayaan') {
                $allItems = $allItems->concat(
                    $query->latest('submitted_at')->limit(200)->get()->map(fn ($app) => [
                        'type' => 'pembiayaan',
                        'type_label' => 'Permohonan Pembiayaan',
                        'id' => $app->id,
                        'reference' => $app->reference_no,
                        'applicant' => $app->member?->full_name ?? '-',
                        'status' => $app->status->value,
                        'submitted_at' => $app->submitted_at?->format('d/m/Y H:i'),
                        'detail_url' => route('admin.financing.applications.show', $app),
                        'can_approve' => false,
                        'can_reject' => false,
                        'approve_url' => null,
                        'reject_url' => null,
                    ])
                );
            }
        }

        $sorted = $allItems->sortByDesc('submitted_at')->values();

        $page = (int) $request->integer('page', 1);
        $perPage = 15;
        $paginated = new \Illuminate\Pagination\LengthAwarePaginator(
            $sorted->forPage($page, $perPage)->values(),
            $sorted->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()],
        );

        $typeOptions = [
            ['value' => '', 'label' => 'Semua Jenis'],
            ['value' => 'keahlian', 'label' => 'Permohonan Keahlian'],
            ['value' => 'borang', 'label' => 'Permohonan Borang'],
            ['value' => 'pembiayaan', 'label' => 'Permohonan Pembiayaan'],
        ];

        $statusOptions = [
            ['value' => '', 'label' => 'Semua Status'],
            ['value' => 'pending', 'label' => 'Menunggu'],
            ['value' => 'submitted', 'label' => 'Dihantar'],
            ['value' => 'under_review', 'label' => 'Dalam Semakan'],
            ['value' => 'incomplete_documents', 'label' => 'Dokumen Tidak Lengkap'],
            ['value' => 'pending_stamp_upload', 'label' => 'Menunggu Borang Bercop'],
        ];

        $membershipTypes = MembershipApplication::query()
            ->forCooperative($cooperative->id)
            ->whereNotNull('metadata')
            ->get()
            ->pluck('metadata.membership_type')
            ->filter()
            ->unique()
            ->values()
            ->map(fn (string $t) => ['value' => $t, 'label' => $t])
            ->all();

        $formCategories = FormCategory::query()
            ->where('cooperative_id', $cooperative->id)
            ->active()
            ->orderBy('name')
            ->get()
            ->map(fn ($c) => ['value' => (string) $c->id, 'label' => $c->name])
            ->all();

        $financingCategories = FinancingCategory::query()
            ->where('cooperative_id', $cooperative->id)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(fn ($c) => ['value' => (string) $c->id, 'label' => $c->name])
            ->all();

        return Inertia::render('Admin/Pages/Semakan/Index', [
            'items' => $paginated,
            'filters' => [
                'type' => $type,
                'search' => $search,
                'status' => $status,
                'category' => $category,
            ],
            'typeOptions' => $typeOptions,
            'statusOptions' => $statusOptions,
            'categoryOptions' => [
                'keahlian' => $membershipTypes,
                'borang' => $formCategories,
                'pembiayaan' => $financingCategories,
            ],
        ]);
    }
}