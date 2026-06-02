<?php

namespace App\Services\Forms;

use App\Enums\FormFieldType;
use App\Enums\FormSubmissionMethod;
use App\Enums\FormSubmissionStatus;
use App\Models\FormField;
use App\Models\FormSubmission;
use App\Models\FormSubmissionFile;
use App\Models\Member;
use App\Models\OnlineForm;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FormSubmissionService
{
    public function submit(OnlineForm $form, array $validated, ?User $user = null, ?Member $member = null): FormSubmission
    {
        $requiresStampedUpload = $form->submission_method === FormSubmissionMethod::RequiresStampedUpload;
        $initialStatus = $requiresStampedUpload
            ? FormSubmissionStatus::PendingStampUpload
            : FormSubmissionStatus::Submitted;

        $attempts = 0;

        beginning:
        $attempts++;

        try {
            return DB::transaction(function () use ($form, $validated, $user, $member, $initialStatus): FormSubmission {
                $submission = FormSubmission::query()->create([
                    'cooperative_id' => $form->cooperative_id,
                    'online_form_id' => $form->id,
                    'unit_id' => $form->category?->unit_id,
                    'unit_name_snapshot' => $form->category?->unit?->name,
                    'member_id' => $member?->id,
                    'reference_no' => $this->generateReferenceNo(),
                    'submitted_by_name' => $member?->full_name ?? ($validated['submitted_by_name'] ?? $user?->name),
                    'submitted_by_email' => $member?->email ?? ($validated['submitted_by_email'] ?? $user?->email),
                    'data_json' => [],
                    'status' => $initialStatus->value,
                    'submitted_at' => now(),
                ]);

                $answers = $validated['answers'] ?? [];
                $files = $validated['files'] ?? [];
                $data = [];

                $fields = $this->activeSectionFields($form)
                    ->with('section')
                    ->get()
                    ->keyBy('field_key');

                foreach ($fields as $fieldKey => $field) {
                    $fieldType = $field->type;

                    if (! $fieldType->acceptsInput() || ! $field->showsOnline()) {
                        continue;
                    }

                    if ($fieldType === FormFieldType::File) {
                        $uploadedFile = $files[$fieldKey] ?? null;

                        if ($uploadedFile instanceof UploadedFile) {
                            $stored = $this->storeUploadedFile($submission, $field, $uploadedFile);

                            $data[$fieldKey] = [
                                'type' => $fieldType->value,
                                'label' => $field->label,
                                'value' => $stored->original_name,
                                'file_id' => $stored->id,
                            ];
                        }

                        continue;
                    }

                    if ($fieldType === FormFieldType::Signature) {
                        $signature = $answers[$fieldKey] ?? null;

                        if (is_string($signature) && str_starts_with($signature, 'data:image/')) {
                            $stored = $this->storeSignature($submission, $field, $signature);

                            $data[$fieldKey] = [
                                'type' => $fieldType->value,
                                'label' => $field->label,
                                'value' => 'signature',
                                'file_id' => $stored->id,
                            ];
                        }

                        continue;
                    }

                    $raw = $answers[$fieldKey] ?? null;

                    if ($fieldType === FormFieldType::AgreementCheckbox) {
                        $data[$fieldKey] = [
                            'type' => $fieldType->value,
                            'label' => $field->label,
                            'value' => (bool) $raw,
                            'agreement_text' => $field->help_text,
                        ];

                        continue;
                    }

                    $data[$fieldKey] = [
                        'type' => $fieldType->value,
                        'label' => $field->label,
                        'value' => $raw,
                    ];
                }

                $submission->update([
                    'data_json' => $data,
                ]);

                return $submission->load('files');
            });
        } catch (QueryException $exception) {
            if ($attempts < 5 && $this->isDuplicateReferenceNumberException($exception)) {
                goto beginning;
            }

            throw $exception;
        }
    }

    public function uploadStampedFile(FormSubmission $submission, UploadedFile $file): FormSubmission
    {
        $path = $file->store("forms/stamped/{$submission->id}", 'local');

        $submission->update([
            'stamped_file_path' => $path,
            'stamped_file_original_name' => $file->getClientOriginalName(),
            'stamped_file_uploaded_at' => now(),
            'status' => FormSubmissionStatus::Submitted->value,
        ]);

        return $submission->fresh();
    }

    public function updateStatus(FormSubmission $submission, array $validated, User $reviewer): void
    {
        $status = $validated['status'] ?? $submission->status->value;

        $updates = [
            'status' => $status,
            'admin_notes' => $validated['admin_notes'] ?: null,
            'reviewed_at' => $status === FormSubmissionStatus::PendingStampUpload->value ? null : now(),
            'reviewed_by' => $status === FormSubmissionStatus::PendingStampUpload->value ? null : $reviewer->id,
            'approved_at' => $status === FormSubmissionStatus::Approved->value ? now() : null,
            'approved_by' => $status === FormSubmissionStatus::Approved->value ? $reviewer->id : null,
            'rejected_at' => $status === FormSubmissionStatus::Rejected->value ? now() : null,
            'rejected_by' => $status === FormSubmissionStatus::Rejected->value ? $reviewer->id : null,
        ];

        $submission->update($updates);
    }

    public function signatureDataUrl(?FormSubmissionFile $file): ?string
    {
        if (! $file || ! Storage::disk('local')->exists($file->stored_path)) {
            return null;
        }

        $contents = Storage::disk('local')->get($file->stored_path);
        $mime = $file->mime_type ?: 'image/png';

        return 'data:'.$mime.';base64,'.base64_encode($contents);
    }

    private function generateReferenceNo(): string
    {
        $date = now()->format('Ymd');
        $prefix = "FRM-{$date}-";
        $latestReferenceNo = FormSubmission::withTrashed()
            ->where('reference_no', 'like', $prefix.'%')
            ->orderByDesc('reference_no')
            ->value('reference_no');
        $latestSequence = (int) Str::afterLast((string) $latestReferenceNo, '-');
        $sequence = str_pad((string) ($latestSequence + 1), 4, '0', STR_PAD_LEFT);

        return "FRM-{$date}-{$sequence}";
    }

    private function activeSectionFields(OnlineForm $form)
    {
        return $form->fields()
            ->where('is_active', true)
            ->whereHas('section', fn ($query) => $query->where('is_active', true));
    }

    private function isDuplicateReferenceNumberException(QueryException $exception): bool
    {
        $errorInfo = $exception->errorInfo;
        $driverCode = $errorInfo[1] ?? null;
        $sqlState = $errorInfo[0] ?? null;
        $message = strtolower($exception->getMessage());

        if (in_array($driverCode, [19, 1062], true)) {
            return str_contains($message, 'reference_no');
        }

        return $sqlState === '23505' && str_contains($message, 'reference_no');
    }

    private function storeUploadedFile(FormSubmission $submission, FormField $field, UploadedFile $file): FormSubmissionFile
    {
        $path = $file->store("forms/submissions/{$submission->id}", 'local');

        return FormSubmissionFile::query()->create([
            'form_submission_id' => $submission->id,
            'form_field_id' => $field->id,
            'field_key' => $field->field_key,
            'stored_path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
            'is_signature' => false,
        ]);
    }

    private function storeSignature(FormSubmission $submission, FormField $field, string $dataUrl): FormSubmissionFile
    {
        [$meta, $encoded] = explode(',', $dataUrl, 2);
        $mime = Str::between($meta, 'data:', ';');
        $extension = match ($mime) {
            'image/jpeg' => 'jpg',
            'image/webp' => 'webp',
            default => 'png',
        };
        $contents = base64_decode($encoded, true) ?: '';
        $path = "forms/signatures/{$submission->id}/{$field->field_key}.{$extension}";

        Storage::disk('local')->put($path, $contents);

        return FormSubmissionFile::query()->create([
            'form_submission_id' => $submission->id,
            'form_field_id' => $field->id,
            'field_key' => $field->field_key,
            'stored_path' => $path,
            'original_name' => $field->field_key.'.'.$extension,
            'mime_type' => $mime,
            'file_size' => strlen($contents),
            'is_signature' => true,
        ]);
    }
}