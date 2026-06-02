<?php

namespace App\Http\Controllers\Admin;

use App\Enums\FormSubmissionStatus;
use App\Http\Controllers\Concerns\InteractsWithActiveCooperative;
use App\Http\Controllers\Controller;
use App\Models\FormCategory;
use App\Models\FormSubmission;
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
        abort_unless($user?->can(AccessControl::PERMISSION_VIEW_FORM_SUBMISSIONS), 403);

        $search = trim((string) $request->string('search'));
        $status = $request->string('status')->toString();
        $category = $request->string('category')->toString();

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

        if ($category !== '') {
            $query->whereHas('form', fn ($q) => $q->where('form_category_id', $category));
        }

        $paginated = $query->latest('submitted_at')->paginate(15)
            ->through(fn ($sub) => [
                'type' => 'borang',
                'type_label' => 'Permohonan Borang',
                'id' => $sub->id,
                'reference' => $sub->reference_no,
                'applicant' => $sub->submitted_by_name ?? $sub->member?->full_name ?? '-',
                'member_no' => $sub->member?->member_no,
                'category' => $sub->form?->category?->name,
                'status' => $sub->status->value,
                'submitted_at' => $sub->submitted_at?->format('d/m/Y H:i'),
                'detail_url' => route('admin.form-submissions.show', $sub),
                'can_approve' => false,
                'can_reject' => false,
                'approve_url' => null,
                'reject_url' => null,
            ]);

        $statusOptions = [
            ['value' => '', 'label' => 'Semua Status'],
            ['value' => 'pending_stamp_upload', 'label' => 'Menunggu Borang Bercop'],
            ['value' => 'submitted', 'label' => 'Dihantar'],
            ['value' => 'incomplete_documents', 'label' => 'Dokumen Tidak Lengkap'],
        ];

        $formCategories = FormCategory::query()
            ->where('cooperative_id', $cooperative->id)
            ->active()
            ->orderBy('name')
            ->get()
            ->map(fn ($c) => ['value' => (string) $c->id, 'label' => $c->name])
            ->all();

        return Inertia::render('Admin/Pages/Semakan/Index', [
            'items' => $paginated,
            'filters' => [
                'search' => $search,
                'status' => $status,
                'category' => $category,
            ],
            'statusOptions' => $statusOptions,
            'categoryOptions' => $formCategories,
        ]);
    }
}