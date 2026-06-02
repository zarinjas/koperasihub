<?php

namespace App\Http\Controllers\Admin;

use App\Enums\FormSubmissionStatus;
use App\Http\Controllers\Concerns\InteractsWithActiveCooperative;
use App\Http\Controllers\Controller;
use App\Models\FormCategory;
use App\Models\FormSubmission;
use App\Models\OnlineForm;
use App\Models\Unit;
use App\Models\User;
use App\Services\AuditLogService;
use App\Services\Forms\FormSubmissionService;
use App\Support\AccessControl;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class FormSubmissionReviewController extends Controller
{
    use InteractsWithActiveCooperative;

    public function __construct(
        private readonly FormSubmissionService $submissions,
        private readonly AuditLogService $auditLog,
    ) {}

    public function index(Request $request): Response
    {
        $cooperative = $this->activeCooperative();
        $user = $request->user();
        $isSuperAdmin = $user?->hasRole(AccessControl::ROLE_SUPER_ADMIN);

        $search = trim((string) $request->string('search'));
        $status = $request->string('status')->toString();
        $category = $request->integer('category') ?: null;
        $form = $request->integer('form') ?: null;
        $unit = $request->integer('unit') ?: null;
        $date = $request->string('date')->toString();

        $submissions = FormSubmission::query()
            ->where('cooperative_id', $cooperative->id)
            ->with(['form.category', 'member', 'unit'])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('reference_no', 'like', "%{$search}%")
                        ->orWhere('submitted_by_name', 'like', "%{$search}%")
                        ->orWhere('submitted_by_email', 'like', "%{$search}%");
                });
            })
            ->when(in_array($status, FormSubmissionStatus::values(), true), fn ($query) => $query->where('status', $status))
            ->when($category, fn ($query) => $query->whereHas('form', fn ($q) => $q->where('form_category_id', $category)))
            ->when($form, fn ($query) => $query->where('online_form_id', $form))
            ->when($date !== '', fn ($query) => $query->whereDate('submitted_at', $date))
            ->when($isSuperAdmin && $unit, fn ($query) => $query->where('unit_id', $unit))
            ->when(! $isSuperAdmin && $user?->unit_id, fn ($query) => $query->where('unit_id', $user->unit_id))
            ->when(! $isSuperAdmin && ! $user?->unit_id, fn ($query) => $query->whereRaw('1 = 0'))
            ->latest('submitted_at')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (FormSubmission $submission) => [
                'id' => $submission->id,
                'reference_no' => $submission->reference_no,
                'status' => $submission->status->value,
                'status_label' => $submission->status->label(),
                'form_title' => $submission->form?->title,
                'category_name' => $submission->form?->category?->name,
                'unit_name' => $submission->unit?->name ?? $submission->unit_name_snapshot,
                'submitted_by_name' => $submission->submitted_by_name,
                'member_name' => $submission->member?->full_name,
                'has_stamped_file' => filled($submission->stamped_file_path),
                'submitted_at' => $submission->submitted_at?->format('d/m/Y H:i'),
                'detail_url' => route('admin.form-submissions.show', $submission),
                'print_url' => route('admin.forms.submissions.print', [$submission->form, $submission]),
            ]);

        $categories = FormCategory::query()
            ->where('cooperative_id', $cooperative->id)
            ->active()
            ->withCount(['forms as submissions_count' => fn ($q) => $q->has('submissions')])
            ->orderBy('name')
            ->get()
            ->map(fn (FormCategory $cat) => [
                'value' => $cat->id,
                'label' => $cat->name,
            ])
            ->all();

        $forms = OnlineForm::query()
            ->where('cooperative_id', $cooperative->id)
            ->published()
            ->when($category, fn ($query) => $query->where('form_category_id', $category))
            ->orderBy('title')
            ->get()
            ->map(fn (OnlineForm $f) => [
                'value' => $f->id,
                'label' => $f->title,
            ])
            ->all();

        $units = $isSuperAdmin ? Unit::query()
            ->where('cooperative_id', $cooperative->id)
            ->active()
            ->orderBy('name')
            ->get()
            ->map(fn (Unit $u) => [
                'value' => $u->id,
                'label' => $u->name,
            ])
            ->all() : [];

        return Inertia::render('Admin/Pages/FormSubmissions/Index', [
            'filters' => [
                'search' => $search,
                'status' => $status,
                'category' => $category,
                'form' => $form,
                'unit' => $unit,
                'date' => $date,
            ],
            'statusOptions' => $this->statusOptions(),
            'categoryOptions' => $categories,
            'formOptions' => $forms,
            'unitOptions' => $units,
            'submissions' => $submissions,
            'isSuperAdmin' => $isSuperAdmin,
        ]);
    }

    public function show(FormSubmission $submission): Response
    {
        $cooperative = $this->activeCooperative();
        abort_unless($submission->cooperative_id === $cooperative->id, 404);
        $this->authorizeSubmissionUnitAccess($submission, request()->user());

        $submission->load([
            'member',
            'reviewer',
            'approver',
            'rejector',
            'unit',
            'files',
            'form.category',
            'form.sections.fields',
        ]);

        $onlineForm = $submission->form;

        $filesByField = $submission->files->keyBy('field_key');
        $sections = $onlineForm->sections->map(function ($section) use ($submission, $filesByField) {
            return [
                'id' => $section->id,
                'title' => $section->title,
                'description' => $section->description,
                'page_break_before' => $section->page_break_before,
                'fields' => $section->fields->map(function ($field) use ($submission, $filesByField) {
                    $answer = $submission->data_json[$field->field_key] ?? null;
                    $file = $filesByField->get($field->field_key);

                    return [
                        'id' => $field->id,
                        'label' => $field->label,
                        'type' => $field->type->value,
                        'help_text' => $field->help_text,
                        'display_mode' => $field->displayMode()->value,
                        'settings_json' => $field->settings_json ?? [],
                        'value' => $answer['value'] ?? null,
                        'agreement_text' => $answer['agreement_text'] ?? null,
                        'file' => $file ? [
                            'id' => $file->id,
                            'name' => $file->original_name,
                            'download_url' => route('admin.forms.submissions.files.download', [$onlineForm, $submission, $file]),
                            'is_signature' => $file->is_signature,
                            'signature_data_url' => $file->is_signature ? $this->submissions->signatureDataUrl($file) : null,
                        ] : null,
                    ];
                })->all(),
            ];
        })->all();

        $stampedFileDownloadUrl = filled($submission->stamped_file_path)
            ? route('admin.forms.submissions.stamped-file.download', [$onlineForm, $submission])
            : null;

        return Inertia::render('Admin/Pages/Forms/Submissions/Show', [
            'submissionRecord' => [
                'cooperative' => $cooperative,
                'logoUrl' => $cooperative->logo_path ? Storage::disk('public')->url($cooperative->logo_path) : null,
                'form' => $onlineForm,
                'submission' => $submission,
                'submission_method' => $onlineForm->submission_method->value,
                'has_stamped_file' => filled($submission->stamped_file_path),
                'stamped_file_original_name' => $submission->stamped_file_original_name,
                'stamped_file_uploaded_at' => $submission->stamped_file_uploaded_at?->format('d/m/Y H:i'),
                'stamped_file_download_url' => $stampedFileDownloadUrl,
                'submission_unit_name' => $submission->unit?->name ?? $submission->unit_name_snapshot,
                'sections' => $sections,
                'print_url' => route('admin.forms.submissions.print', [$onlineForm, $submission]),
                'index_url' => route('admin.form-submissions.index'),
            ],
            'statusOptions' => $this->statusOptions(),
        ]);
    }

    public function updateStatus(Request $request, FormSubmission $submission): RedirectResponse
    {
        $cooperative = $this->activeCooperative();
        abort_unless($submission->cooperative_id === $cooperative->id, 404);
        $this->authorizeSubmissionUnitAccess($submission, $request->user());

        $validated = $request->validate([
            'status' => ['required', \Illuminate\Validation\Rule::in(FormSubmissionStatus::values())],
            'admin_notes' => ['nullable', 'string', 'max:3000'],
        ]);

        $this->submissions->updateStatus($submission, $validated, $request->user());
        $this->auditLog->record('form_submission.updated', $submission->form,
            newValues: $submission->fresh()->toArray(),
            metadata: $this->auditActorMetadata($request->user(), $submission),
        );

        return back()->with('status', 'Status permohonan berjaya dikemas kini.');
    }

    private function statusOptions(): array
    {
        return [
            ['value' => '', 'label' => 'Semua Status'],
            ['value' => FormSubmissionStatus::Draft->value, 'label' => FormSubmissionStatus::Draft->label()],
            ['value' => FormSubmissionStatus::PendingStampUpload->value, 'label' => FormSubmissionStatus::PendingStampUpload->label()],
            ['value' => FormSubmissionStatus::Submitted->value, 'label' => FormSubmissionStatus::Submitted->label()],
            ['value' => FormSubmissionStatus::UnderReview->value, 'label' => FormSubmissionStatus::UnderReview->label()],
            ['value' => FormSubmissionStatus::IncompleteDocuments->value, 'label' => FormSubmissionStatus::IncompleteDocuments->label()],
            ['value' => FormSubmissionStatus::Approved->value, 'label' => FormSubmissionStatus::Approved->label()],
            ['value' => FormSubmissionStatus::Rejected->value, 'label' => FormSubmissionStatus::Rejected->label()],
            ['value' => FormSubmissionStatus::Closed->value, 'label' => FormSubmissionStatus::Closed->label()],
        ];
    }

    private function authorizeSubmissionUnitAccess(FormSubmission $submission, ?User $user): void
    {
        if (! $user) {
            abort(403);
        }

        if ($user->hasRole([AccessControl::ROLE_SUPER_ADMIN, AccessControl::ROLE_ADMIN])) {
            return;
        }

        if (! $user->unit_id) {
            abort(403, 'Akaun anda belum ditetapkan kepada mana-mana unit.');
        }

        if ($submission->unit_id !== $user->unit_id) {
            abort(403);
        }
    }

    private function auditActorMetadata(?User $user, ?FormSubmission $submission = null): array
    {
        return [
            'actor_name' => $user?->name,
            'actor_staff_id' => $user?->staff_id,
            'actor_unit' => $user?->unit?->name,
            'submission_reference_no' => $submission?->reference_no,
        ];
    }
}