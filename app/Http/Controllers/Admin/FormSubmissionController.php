<?php

namespace App\Http\Controllers\Admin;

use App\Enums\FormSubmissionStatus;
use App\Http\Controllers\Concerns\InteractsWithActiveCooperative;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateFormSubmissionRequest;
use App\Models\FormSubmission;
use App\Models\FormSubmissionFile;
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
use Symfony\Component\HttpFoundation\StreamedResponse;

class FormSubmissionController extends Controller
{
    use InteractsWithActiveCooperative;

    public function __construct(
        private readonly FormSubmissionService $submissions,
        private readonly AuditLogService $auditLog,
    ) {}

    public function index(OnlineForm $onlineForm, Request $request): Response
    {
        $this->ensureSameCooperative($onlineForm);

        $search = trim((string) $request->string('search'));
        $status = $request->string('status')->toString();
        $date = $request->string('date')->toString();
        $stampedState = $request->string('stamped_state')->toString();

        $user = $request->user();
        $isSuperAdmin = $user?->hasRole(AccessControl::ROLE_SUPER_ADMIN);

        $submissions = $onlineForm->submissions()
            ->with(['member', 'unit'])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('reference_no', 'like', "%{$search}%")
                        ->orWhere('submitted_by_name', 'like', "%{$search}%")
                        ->orWhere('submitted_by_email', 'like', "%{$search}%");
                });
            })
            ->when(in_array($status, FormSubmissionStatus::values(), true), fn ($query) => $query->where('status', $status))
            ->when($date !== '', fn ($query) => $query->whereDate('submitted_at', $date))
            ->when($stampedState === 'uploaded', fn ($query) => $query->whereNotNull('stamped_file_path'))
            ->when($stampedState === 'missing', fn ($query) => $query->whereNull('stamped_file_path'))
            ->when(! $isSuperAdmin && $user?->unit_id, fn ($query) => $query->where('unit_id', $user->unit_id))
            ->when(! $isSuperAdmin && ! $user?->unit_id, fn ($query) => $query->whereRaw('1 = 0'))
            ->paginate(12)
            ->withQueryString()
            ->through(fn (FormSubmission $submission) => [
                'id' => $submission->id,
                'reference_no' => $submission->reference_no,
                'status' => $submission->status->value,
                'status_label' => $submission->status->label(),
                'submitted_by_name' => $submission->submitted_by_name,
                'submitted_by_email' => $submission->submitted_by_email,
                'member_name' => $submission->member?->full_name,
                'unit_name' => $submission->unit?->name ?? $submission->unit_name_snapshot,
                'has_stamped_file' => ! is_null($submission->stamped_file_path),
                'submitted_at' => $submission->submitted_at?->format('d/m/Y H:i'),
                'detail_url' => route('admin.forms.submissions.show', [$onlineForm, $submission]),
                'print_url' => route('admin.forms.submissions.print', [$onlineForm, $submission]),
            ]);

        return Inertia::render('Admin/Pages/Forms/Submissions/Index', [
            'formRecord' => [
                'id' => $onlineForm->id,
                'title' => $onlineForm->title,
                'submission_method' => $onlineForm->submission_method->value,
                'preview_pdf_url' => route('admin.forms.preview-pdf', $onlineForm),
            ],
            'filters' => [
                'search' => $search,
                'status' => $status,
                'date' => $date,
                'stamped_state' => $stampedState,
            ],
            'statusOptions' => $this->statusOptions(includeAll: true),
            'stampedStateOptions' => [
                ['value' => '', 'label' => 'Semua'],
                ['value' => 'uploaded', 'label' => 'Dimuat naik'],
                ['value' => 'missing', 'label' => 'Belum dimuat naik'],
            ],
            'submissions' => $submissions,
        ]);
    }

    public function show(OnlineForm $onlineForm, FormSubmission $submission): Response
    {
        $this->authorizeSubmissionUnitAccess($submission, request()->user());

        $payload = $this->submissionPayload($onlineForm, $submission);

        return Inertia::render('Admin/Pages/Forms/Submissions/Show', [
            'submissionRecord' => $payload,
            'statusOptions' => $this->statusOptions(),
        ]);
    }

    public function update(UpdateFormSubmissionRequest $request, OnlineForm $onlineForm, FormSubmission $submission): RedirectResponse
    {
        $payload = $this->submissionPayload($onlineForm, $submission);
        $this->authorizeSubmissionUnitAccess($submission, $request->user());
        $this->submissions->updateStatus($submission, $request->validated(), $request->user());
        $this->auditLog->record('form_submission.updated', $onlineForm,
            oldValues: ['before' => $payload],
            newValues: ['after' => $submission->fresh()->toArray()],
            metadata: $this->auditActorMetadata($request->user(), $submission),
        );

        return back()->with('status', 'Status submission berjaya dikemas kini.');
    }

    public function print(OnlineForm $onlineForm, FormSubmission $submission)
    {
        $this->authorizeSubmissionUnitAccess($submission, request()->user());

        $payload = $this->submissionPayload($onlineForm, $submission);

        return response()->view('forms.print-submission', $payload);
    }

    public function downloadFile(OnlineForm $onlineForm, FormSubmission $submission, FormSubmissionFile $file): StreamedResponse
    {
        $this->ensureSameCooperative($onlineForm);
        abort_unless($submission->online_form_id === $onlineForm->id, 404);
        abort_unless($file->form_submission_id === $submission->id, 404);
        abort_unless(Storage::disk('local')->exists($file->stored_path), 404);

        return Storage::disk('local')->download(
            $file->stored_path,
            $file->original_name ?: basename($file->stored_path)
        );
    }

    public function downloadStampedFile(OnlineForm $onlineForm, FormSubmission $submission): StreamedResponse
    {
        $this->ensureSameCooperative($onlineForm);
        abort_unless($submission->online_form_id === $onlineForm->id, 404);
        abort_unless(filled($submission->stamped_file_path), 404);
        abort_unless(Storage::disk('local')->exists($submission->stamped_file_path), 404);

        return Storage::disk('local')->download(
            $submission->stamped_file_path,
            $submission->stamped_file_original_name ?: basename($submission->stamped_file_path)
        );
    }

    private function statusOptions(bool $includeAll = false): array
    {
        $options = [
            ['value' => FormSubmissionStatus::Draft->value, 'label' => FormSubmissionStatus::Draft->label()],
            ['value' => FormSubmissionStatus::PendingStampUpload->value, 'label' => FormSubmissionStatus::PendingStampUpload->label()],
            ['value' => FormSubmissionStatus::Submitted->value, 'label' => FormSubmissionStatus::Submitted->label()],
            ['value' => FormSubmissionStatus::UnderReview->value, 'label' => FormSubmissionStatus::UnderReview->label()],
            ['value' => FormSubmissionStatus::IncompleteDocuments->value, 'label' => FormSubmissionStatus::IncompleteDocuments->label()],
            ['value' => FormSubmissionStatus::Approved->value, 'label' => FormSubmissionStatus::Approved->label()],
            ['value' => FormSubmissionStatus::Rejected->value, 'label' => FormSubmissionStatus::Rejected->label()],
            ['value' => FormSubmissionStatus::Closed->value, 'label' => FormSubmissionStatus::Closed->label()],
        ];

        return $includeAll ? [['value' => '', 'label' => 'Semua status'], ...$options] : $options;
    }

    private function submissionPayload(OnlineForm $onlineForm, FormSubmission $submission): array
    {
        $this->ensureSameCooperative($onlineForm);
        abort_unless($submission->online_form_id === $onlineForm->id, 404);

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

        $filesByField = $submission->files->keyBy('field_key');
        $sections = $submission->form->sections->map(function ($section) use ($submission, $filesByField) {
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

        return [
            'cooperative' => $this->activeCooperative(),
            'logoUrl' => $this->activeCooperative()?->logo_path ? Storage::disk('public')->url($this->activeCooperative()->logo_path) : null,
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
            'index_url' => route('admin.forms.submissions.index', $onlineForm),
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