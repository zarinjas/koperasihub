<?php

namespace App\Services;

use App\Enums\MembershipApplicationStatus;
use App\Models\Cooperative;
use App\Models\MembershipApplication;
use App\Models\User;
use App\Services\Settings\SettingsService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class MembershipApplicationService
{
    public function __construct(
        private readonly SettingsService $settings,
        private readonly AuditLogService $auditLogs,
        private readonly MemberService $members,
        private readonly ReferralCommissionService $referralCommissions,
    ) {}

    public function submit(array $attributes, ?UploadedFile $supportingDocument = null): MembershipApplication
    {
        $cooperative = $this->activeCooperative();

        if (! $cooperative) {
            throw ValidationException::withMessages([
                'cooperative' => 'Koperasi aktif tidak ditemui.',
            ]);
        }

        $metadata = array_filter([
            'membership_type' => $attributes['membership_type'] ?? null,
            'notes' => $attributes['notes'] ?? null,
            'supporting_document' => $supportingDocument
                ? $this->storeSupportingDocument($supportingDocument)
                : null,
        ]);

        return DB::transaction(function () use ($attributes, $cooperative, $metadata): MembershipApplication {
            $application = MembershipApplication::query()->create([
                'cooperative_id' => $cooperative->id,
                'application_no' => $this->generateApplicationNumber($cooperative->id),
                'full_name' => $attributes['full_name'],
                'identity_no' => $attributes['identity_no'],
                'email' => $attributes['email'],
                'phone' => $attributes['phone'],
                'date_of_birth' => $attributes['date_of_birth'],
                'gender' => $attributes['gender'],
                'address_line_1' => $attributes['address_line_1'] ?? $attributes['address'] ?? null,
                'city' => $attributes['city'] ?? null,
                'state' => $attributes['state'] ?? null,
                'postcode' => $attributes['postcode'] ?? null,
                'country' => 'Malaysia',
                'occupation' => $attributes['occupation'] ?? null,
                'employer_name' => $attributes['employer_name'] ?? null,
                'referred_by_member_id' => $attributes['referred_by_member_id'] ?? null,
                'status' => MembershipApplicationStatus::Pending->value,
                'submitted_at' => now(),
                'metadata' => $metadata ?: null,
            ]);

            $this->auditLogs->record('membership_application_submitted', $application, [], $this->auditSnapshot($application));

            return $application;
        });
    }

    public function markUnderReview(MembershipApplication $application, User $reviewer, ?string $reviewNotes = null): MembershipApplication
    {
        return DB::transaction(function () use ($application, $reviewer, $reviewNotes): MembershipApplication {
            $application = $this->lockApplication($application);

            $this->guardTransition($application, [
                MembershipApplicationStatus::Pending,
                MembershipApplicationStatus::UnderReview,
            ]);

            $oldValues = $this->auditSnapshot($application);

            $application->update([
                'status' => MembershipApplicationStatus::UnderReview->value,
                'reviewed_at' => now(),
                'reviewed_by' => $reviewer->id,
                'review_notes' => $reviewNotes ?: $application->review_notes,
                'rejection_reason' => null,
            ]);

            $this->auditLogs->record('application_under_review', $application, $oldValues, $this->auditSnapshot($application));

            return $application->refresh();
        });
    }

    public function approve(MembershipApplication $application, User $reviewer): MembershipApplication
    {
        return DB::transaction(function () use ($application, $reviewer): MembershipApplication {
            $application = $this->lockApplication($application);

            $this->guardTransition($application, [
                MembershipApplicationStatus::Pending,
                MembershipApplicationStatus::UnderReview,
            ]);

            $oldValues = $this->auditSnapshot($application);

            $member = $this->members->createOrLinkFromApplication($application, $reviewer);

            $application->update([
                'status' => MembershipApplicationStatus::Approved->value,
                'reviewed_at' => now(),
                'reviewed_by' => $reviewer->id,
                'approved_member_id' => $member->id,
                'rejection_reason' => null,
            ]);

            $this->referralCommissions->createCommission($application, $member);

            $this->auditLogs->record('application_approved', $application, $oldValues, $this->auditSnapshot($application), [
                'approved_member_id' => $member->id,
            ]);

            return $application->refresh();
        });
    }

    public function reject(MembershipApplication $application, User $reviewer, string $reason, ?string $reviewNotes = null): MembershipApplication
    {
        return DB::transaction(function () use ($application, $reviewer, $reason, $reviewNotes): MembershipApplication {
            $application = $this->lockApplication($application);

            $this->guardTransition($application, [
                MembershipApplicationStatus::Pending,
                MembershipApplicationStatus::UnderReview,
            ]);

            $oldValues = $this->auditSnapshot($application);

            $application->update([
                'status' => MembershipApplicationStatus::Rejected->value,
                'reviewed_at' => now(),
                'reviewed_by' => $reviewer->id,
                'review_notes' => $reviewNotes ?: $application->review_notes,
                'rejection_reason' => $reason,
            ]);

            $this->auditLogs->record('application_rejected', $application, $oldValues, $this->auditSnapshot($application));

            return $application->refresh();
        });
    }

    public function cancel(MembershipApplication $application, User $reviewer, ?string $reviewNotes = null): MembershipApplication
    {
        return DB::transaction(function () use ($application, $reviewer, $reviewNotes): MembershipApplication {
            $application = $this->lockApplication($application);

            $this->guardTransition($application, [
                MembershipApplicationStatus::Pending,
                MembershipApplicationStatus::UnderReview,
            ]);

            $oldValues = $this->auditSnapshot($application);

            $application->update([
                'status' => MembershipApplicationStatus::Cancelled->value,
                'reviewed_at' => now(),
                'reviewed_by' => $reviewer->id,
                'review_notes' => $reviewNotes ?: $application->review_notes,
            ]);

            $this->auditLogs->record('membership_application.cancelled', $application, $oldValues, $this->auditSnapshot($application));

            return $application->refresh();
        });
    }

    private function activeCooperative(): ?Cooperative
    {
        return $this->settings->activeCooperative();
    }

    private function generateApplicationNumber(int $cooperativeId): string
    {
        return $this->generateUniqueNumber('APP', MembershipApplication::query()
            ->withTrashed()
            ->where('cooperative_id', $cooperativeId), 'application_no');
    }

    private function storeSupportingDocument(UploadedFile $file): array
    {
        $path = $file->store('membership-applications', 'local');

        return [
            'path' => $path,
            'name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
        ];
    }

    private function lockApplication(MembershipApplication $application): MembershipApplication
    {
        return MembershipApplication::query()
            ->whereKey($application->getKey())
            ->lockForUpdate()
            ->firstOrFail();
    }

    private function generateUniqueNumber(string $prefix, mixed $query, string $column): string
    {
        do {
            $number = sprintf(
                '%s-%s-%s',
                $prefix,
                now()->format('Ymd'),
                Str::upper(Str::random(6)),
            );
        } while ((clone $query)->where($column, $number)->exists());

        return $number;
    }

    private function guardTransition(MembershipApplication $application, array $allowedStatuses): void
    {
        if (! in_array($application->status, $allowedStatuses, true)) {
            throw new RuntimeException('Status permohonan tidak sah untuk tindakan ini.');
        }
    }

    private function auditSnapshot(MembershipApplication $application): array
    {
        return [
            'status' => $application->status->value,
            'reviewed_at' => $application->reviewed_at?->toISOString(),
            'reviewed_by' => $application->reviewed_by,
            'approved_member_id' => $application->approved_member_id,
            'rejection_reason' => $application->rejection_reason,
            'review_notes' => $application->review_notes,
        ];
    }
}
