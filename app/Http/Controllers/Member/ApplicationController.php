<?php

namespace App\Http\Controllers\Member;

use App\Models\FormSubmission;
use App\Models\OnlineForm;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class ApplicationController extends MemberPortalController
{
    public function index(Request $request): Response
    {
        $member = $this->currentMemberOrNull($request);
        $cooperativeId = $this->activeCooperativeId($request);

        $availableForms = OnlineForm::query()
            ->where('cooperative_id', $cooperativeId)
            ->published()
            ->where(function ($query) {
                $query->whereDoesntHave('category')
                    ->orWhereHas('category', fn ($q) => $q->where('is_active', true));
            })
            ->with('category')
            ->latest('updated_at')
            ->get()
            ->map(fn (OnlineForm $form) => [
                'id' => $form->id,
                'title' => $form->title,
                'slug' => $form->slug,
                'description' => $form->description,
                'category_name' => $form->category?->name,
                'submission_method' => $form->submission_method->value,
                'url' => route('public.forms.show', $form->slug),
            ])
            ->all();

        $submissions = $member
            ? FormSubmission::query()
                ->where('cooperative_id', $cooperativeId)
                ->where('member_id', $member->id)
                ->with('form.category')
                ->latest('submitted_at')
                ->get()
                ->map(fn (FormSubmission $submission) => $this->serializeSubmission($submission))
                ->all()
            : [];

        return Inertia::render('Member/Pages/Applications/Index', [
            'availableForms' => $availableForms,
            'submissions' => $submissions,
            'memberLinked' => (bool) $member,
        ]);
    }

    public function show(Request $request, FormSubmission $submission): Response
    {
        $member = $this->currentMemberOrNull($request);
        $cooperativeId = $this->activeCooperativeId($request);

        abort_unless($member && $submission->member_id === $member->id, 404);
        abort_unless($submission->cooperative_id === $cooperativeId, 404);

        $submission->load([
            'form.category',
            'form.sections.fields',
            'files',
        ]);

        $filesByField = $submission->files->keyBy('field_key');

        $sections = $submission->form->sections->map(function ($section) use ($submission, $filesByField) {
            return [
                'id' => $section->id,
                'title' => $section->title,
                'description' => $section->description,
                'fields' => $section->fields->map(function ($field) use ($submission, $filesByField) {
                    $answer = $submission->data_json[$field->field_key] ?? null;
                    $file = $filesByField->get($field->field_key);

                    return [
                        'id' => $field->id,
                        'label' => $field->label,
                        'type' => $field->type->value,
                        'help_text' => $field->help_text,
                        'value' => $answer['value'] ?? null,
                        'agreement_text' => $answer['agreement_text'] ?? null,
                        'file' => $file ? [
                            'name' => $file->original_name,
                            'is_signature' => $file->is_signature,
                            'signature_data_url' => $file->is_signature ? $this->signatureDataUrl($file) : null,
                        ] : null,
                    ];
                })->all(),
            ];
        })->all();

        $stampedFile = null;
        if (filled($submission->stamped_file_path) && Storage::disk('local')->exists($submission->stamped_file_path)) {
            $stampedFile = [
                'name' => $submission->stamped_file_original_name,
                'uploaded_at' => $submission->stamped_file_uploaded_at?->format('d/m/Y H:i'),
            ];
        }

        return Inertia::render('Member/Pages/Applications/Show', [
            'submission' => [
                'id' => $submission->id,
                'reference_no' => $submission->reference_no,
                'status' => $submission->status->value,
                'status_label' => $submission->status->label(),
                'admin_notes' => $submission->admin_notes,
                'submitted_at' => $submission->submitted_at?->format('d/m/Y H:i'),
                'reviewed_at' => $submission->reviewed_at?->format('d/m/Y H:i'),
            ],
            'form' => [
                'id' => $submission->form->id,
                'title' => $submission->form->title,
                'category_name' => $submission->form->category?->name,
                'submission_method' => $submission->form->submission_method->value,
                'stamped_upload_instructions' => $submission->form->stamped_upload_instructions,
            ],
            'sections' => $sections,
            'stampedFile' => $stampedFile,
        ]);
    }

    private function serializeSubmission(FormSubmission $submission): array
    {
        $needsStampedUpload = $submission->form?->submission_method->value === 'requires_stamped_upload';

        return [
            'id' => $submission->id,
            'reference_no' => $submission->reference_no,
            'status' => $submission->status->value,
            'status_label' => $submission->status->label(),
            'form_title' => $submission->form?->title,
            'category_name' => $submission->form?->category?->name,
            'submitted_at' => $submission->submitted_at?->format('d/m/Y H:i'),
            'needs_stamped_upload' => $needsStampedUpload,
            'has_stamped_file' => filled($submission->stamped_file_path),
            'detail_url' => route('member.applications.submissions.show', $submission),
        ];
    }

    private function signatureDataUrl($file): ?string
    {
        if (! $file || ! Storage::disk('local')->exists($file->stored_path)) {
            return null;
        }

        $contents = Storage::disk('local')->get($file->stored_path);
        $mime = $file->mime_type ?: 'image/png';

        return 'data:' . $mime . ';base64,' . base64_encode($contents);
    }
}