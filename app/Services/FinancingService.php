<?php

namespace App\Services;

use App\Enums\FinancingApplicationStatus;
use App\Enums\FinancingCategoryType;
use App\Enums\FinancingGuarantorStatus;
use App\Models\FinancingApplication;
use App\Models\FinancingApplicationHistory;
use App\Models\FinancingCategory;
use App\Models\FinancingDocument;
use App\Models\FinancingGuarantor;
use App\Models\FinancingProduct;
use App\Models\Member;
use App\Models\Unit;
use App\Models\User;
use App\Notifications\FinancingWorkflowNotification;
use App\Services\Files\FinancingFileService;
use App\Services\Settings\SettingsService;
use App\Support\AccessControl;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class FinancingService
{
    public function __construct(
        private readonly SettingsService $settings,
        private readonly AuditLogService $auditLogs,
        private readonly FinancingFileService $files,
    ) {}

    public function createOrUpdateCategory(array $attributes, User $actor, ?FinancingCategory $category = null): FinancingCategory
    {
        $category ??= new FinancingCategory();
        $currentImage = $category->rate_image_path;

        if (($attributes['rate_image'] ?? null) instanceof UploadedFile) {
            $attributes['rate_image_path'] = $this->files->storeRateImage($attributes['rate_image']);
        }

        unset($attributes['rate_image']);

        $category->fill([
            'cooperative_id' => $category->cooperative_id ?? $actor->cooperative_id,
            'name' => $attributes['name'],
            'slug' => $attributes['slug'] ?? $attributes['name'],
            'description' => $attributes['description'] ?? null,
            'type' => $attributes['type'],
            'rate_image_path' => $attributes['rate_image_path'] ?? $category->rate_image_path,
            'is_active' => (bool) ($attributes['is_active'] ?? true),
            'sort_order' => (int) ($attributes['sort_order'] ?? 0),
            'created_by' => $category->exists ? $category->created_by : $actor->id,
            'updated_by' => $actor->id,
        ]);
        $category->save();

        if (($attributes['remove_rate_image'] ?? false) && $currentImage) {
            $this->files->deletePublicFile($currentImage);
            $category->forceFill(['rate_image_path' => null])->save();
        } elseif (($attributes['rate_image_path'] ?? null) && $currentImage && $currentImage !== $attributes['rate_image_path']) {
            $this->files->deletePublicFile($currentImage);
        }

        return $category->refresh();
    }

    public function createOrUpdateProduct(array $attributes, User $actor, ?FinancingProduct $product = null): FinancingProduct
    {
        $product ??= new FinancingProduct();

        $product->fill([
            'cooperative_id' => $product->cooperative_id ?? $actor->cooperative_id,
            'financing_category_id' => $attributes['financing_category_id'],
            'unit_id' => $attributes['unit_id'] ?? $this->defaultFinancingUnitId($actor->cooperative_id),
            'name' => $attributes['name'],
            'slug' => $attributes['slug'] ?? $attributes['name'],
            'description' => $attributes['description'] ?? null,
            'min_amount' => $attributes['min_amount'] ?? null,
            'max_amount' => $attributes['max_amount'] ?? null,
            'min_tenure_months' => $attributes['min_tenure_months'] ?? null,
            'max_tenure_months' => $attributes['max_tenure_months'] ?? null,
            'requires_guarantor' => (bool) ($attributes['requires_guarantor'] ?? false),
            'guarantor_count' => (int) ($attributes['guarantor_count'] ?? 0),
            'required_documents_json' => $this->normalizeRequiredDocuments($attributes['required_documents_text'] ?? null),
            'is_active' => (bool) ($attributes['is_active'] ?? true),
            'sort_order' => (int) ($attributes['sort_order'] ?? 0),
            'created_by' => $product->exists ? $product->created_by : $actor->id,
            'updated_by' => $actor->id,
        ]);

        $this->validateProductRules($product);
        $product->save();

        return $product->refresh();
    }

    public function submitApplication(array $attributes, Member $member, User $actor): FinancingApplication
    {
        $product = $this->resolveProductForMember($attributes['financing_product_id'], $member->cooperative_id);
        $category = $product->category;
        $guarantorIds = collect($attributes['guarantor_member_ids'] ?? [])->filter()->map(fn ($id) => (int) $id)->values();
        $documents = collect($attributes['documents'] ?? []);

        $this->validateSubmission($member, $product, $guarantorIds, $attributes);

        return DB::transaction(function () use ($attributes, $member, $actor, $product, $category, $guarantorIds, $documents): FinancingApplication {
            $application = FinancingApplication::query()->create([
                'cooperative_id' => $member->cooperative_id,
                'unit_id' => $product->unit_id ?? $this->defaultFinancingUnitId($member->cooperative_id),
                'reference_no' => $this->generateReferenceNumber($member->cooperative_id),
                'member_id' => $member->id,
                'financing_category_id' => $category->id,
                'financing_product_id' => $product->id,
                'amount_requested' => $attributes['amount_requested'],
                'tenure_months' => $attributes['tenure_months'],
                'purpose' => $attributes['purpose'],
                'monthly_income' => $attributes['monthly_income'] ?? null,
                'monthly_commitment' => $attributes['monthly_commitment'] ?? null,
                'employment_notes' => $attributes['employment_notes'] ?? null,
                'status' => $product->requires_guarantor
                    ? FinancingApplicationStatus::GuarantorPending->value
                    : FinancingApplicationStatus::Submitted->value,
                'submitted_at' => now(),
            ]);

            $this->recordHistory($application, 'submitted', null, FinancingApplicationStatus::Submitted->value, $actor, 'Permohonan pembiayaan dihantar.');

            $documents->each(function (UploadedFile $file, int|string $index) use ($application, $actor, $product): void {
                $labels = $product->required_documents_json ?? [];
                $label = $labels[(int) $index] ?? 'Dokumen Sokongan '.((int) $index + 1);
                $this->attachDocument($application, $file, $actor, $label);
            });

            if ($product->requires_guarantor) {
                $guarantorIds->each(function (int $guarantorId) use ($application): void {
                    FinancingGuarantor::query()->create([
                        'cooperative_id' => $application->cooperative_id,
                        'financing_application_id' => $application->id,
                        'guarantor_member_id' => $guarantorId,
                        'status' => FinancingGuarantorStatus::Pending->value,
                    ]);
                });

                $this->recordHistory(
                    $application,
                    'guarantor_request_created',
                    FinancingApplicationStatus::Submitted->value,
                    FinancingApplicationStatus::GuarantorPending->value,
                    $actor,
                    'Permintaan persetujuan penjamin telah diwujudkan.'
                );

                $this->recordAudit('guarantor_request_created', $application, $actor, [
                    'guarantor_count' => $guarantorIds->count(),
                ]);
            }

            $application->load(['member.user', 'product', 'guarantors.guarantorMember.user', 'unit']);

            $this->recordAudit('financing_application_submitted', $application, $actor);
            $this->notifyApplicant($application, 'Permohonan pembiayaan diterima', 'Permohonan pembiayaan anda telah berjaya dihantar.', route('member.financing.applications.show', $application), 'Lihat Permohonan');
            $this->notifyAdmins($application, 'Permohonan pembiayaan baharu', 'Permohonan pembiayaan baharu memerlukan semakan.', route('admin.financing.applications.show', $application), 'Buka Permohonan');
            $this->notifyGuarantors($application);

            return $application;
        });
    }

    public function uploadAdditionalDocument(FinancingApplication $application, UploadedFile $file, User $actor, string $label): FinancingDocument
    {
        return DB::transaction(function () use ($application, $file, $actor, $label): FinancingDocument {
            $document = $this->attachDocument($application, $file, $actor, $label);
            $this->recordHistory($application, 'document_uploaded', $application->status->value, $application->status->value, $actor, "Dokumen {$label} dimuat naik.");
            $this->recordAudit('financing_document_uploaded', $application, $actor, ['label' => $label]);
            $this->notifyAdmins($application->fresh(['member.user', 'product', 'unit']), 'Dokumen pembiayaan dikemas kini', 'Dokumen baharu telah dimuat naik untuk permohonan pembiayaan.', route('admin.financing.applications.show', $application), 'Semak Permohonan');

            return $document;
        });
    }

    public function respondToGuarantor(FinancingGuarantor $guarantor, User $actor, array $attributes): FinancingGuarantor
    {
        $guarantor->loadMissing(['application.member.user', 'application.product', 'guarantorMember.user', 'application.unit']);

        if ($guarantor->status !== FinancingGuarantorStatus::Pending) {
            throw ValidationException::withMessages([
                'status' => 'Permintaan penjamin ini telah dijawab.',
            ]);
        }

        return DB::transaction(function () use ($guarantor, $actor, $attributes): FinancingGuarantor {
            $application = FinancingApplication::query()->whereKey($guarantor->financing_application_id)->lockForUpdate()->firstOrFail();
            $guarantor = FinancingGuarantor::query()->whereKey($guarantor->id)->lockForUpdate()->firstOrFail();

            if (($attributes['action'] ?? null) === 'accept') {
                $signaturePath = $this->files->storeSignatureDataUrl($attributes['signature']);

                $guarantor->update([
                    'status' => FinancingGuarantorStatus::Accepted->value,
                    'consent_text' => $attributes['consent_text'],
                    'consented_at' => now(),
                    'signature_path' => $signaturePath,
                    'rejection_reason' => null,
                    'responded_at' => now(),
                ]);

                $this->recordHistory($application, 'guarantor_accepted', $application->status->value, $application->status->value, $actor, 'Penjamin telah bersetuju.', [
                    'guarantor_member_id' => $guarantor->guarantor_member_id,
                ]);
                $this->recordAudit('guarantor_accepted', $application, $actor, [
                    'guarantor_member_id' => $guarantor->guarantor_member_id,
                ]);

                if (! FinancingGuarantor::query()
                    ->where('financing_application_id', $application->id)
                    ->where('status', FinancingGuarantorStatus::Pending->value)
                    ->exists()) {
                    $application->update([
                        'status' => FinancingApplicationStatus::GuarantorAccepted->value,
                    ]);

                    $this->recordHistory(
                        $application,
                        'all_guarantors_accepted',
                        FinancingApplicationStatus::GuarantorPending->value,
                        FinancingApplicationStatus::GuarantorAccepted->value,
                        $actor,
                        'Semua penjamin telah memberikan persetujuan.'
                    );

                    $this->notifyApplicant($application->fresh(['member.user', 'product', 'unit']), 'Semua penjamin telah bersetuju', 'Semua penjamin yang dipilih telah memberikan persetujuan.', route('member.financing.applications.show', $application), 'Lihat Permohonan');
                    $this->notifyAdmins($application->fresh(['member.user', 'product', 'unit']), 'Semua penjamin telah bersetuju', 'Permohonan pembiayaan sedia untuk semakan admin.', route('admin.financing.applications.show', $application), 'Semak Permohonan');
                }
            } else {
                $guarantor->update([
                    'status' => FinancingGuarantorStatus::Rejected->value,
                    'rejection_reason' => $attributes['rejection_reason'],
                    'responded_at' => now(),
                ]);

                $application->update([
                    'status' => FinancingApplicationStatus::GuarantorRejected->value,
                    'rejected_at' => now(),
                    'rejection_reason' => $attributes['rejection_reason'],
                ]);

                $this->recordHistory(
                    $application,
                    'guarantor_rejected',
                    FinancingApplicationStatus::GuarantorPending->value,
                    FinancingApplicationStatus::GuarantorRejected->value,
                    $actor,
                    $attributes['rejection_reason'],
                    ['guarantor_member_id' => $guarantor->guarantor_member_id]
                );
                $this->recordAudit('guarantor_rejected', $application, $actor, [
                    'guarantor_member_id' => $guarantor->guarantor_member_id,
                ]);
                $this->notifyApplicant($application->fresh(['member.user', 'product', 'unit']), 'Penjamin menolak permohonan', 'Salah seorang penjamin telah menolak permohonan pembiayaan anda.', route('member.financing.applications.show', $application), 'Lihat Permohonan');
            }

            if ($guarantor->guarantorMember?->user) {
                $guarantor->guarantorMember->user->notify(new FinancingWorkflowNotification(
                    subjectLine: 'Maklum balas penjamin direkodkan',
                    introLine: 'Maklum balas anda sebagai penjamin telah direkodkan oleh sistem.',
                    summaryLines: [
                        'No. Rujukan: '.$application->reference_no,
                        'Produk: '.$application->product?->name,
                    ],
                    actionUrl: route('member.financing.guarantor-requests.show', $guarantor),
                    actionLabel: 'Lihat Butiran',
                    cooperativeName: $this->cooperativeName()
                ));
            }

            return $guarantor->fresh(['application.member', 'application.product', 'guarantorMember']);
        });
    }

    public function markUnderReview(FinancingApplication $application, User $actor, ?string $notes = null): FinancingApplication
    {
        return $this->transitionApplication(
            $application,
            $actor,
            allowed: [
                FinancingApplicationStatus::Submitted,
                FinancingApplicationStatus::GuarantorAccepted,
                FinancingApplicationStatus::IncompleteDocuments,
            ],
            nextStatus: FinancingApplicationStatus::UnderReview,
            historyAction: 'under_review',
            notes: $notes,
            mutate: fn (FinancingApplication $application) => $application->forceFill([
                'reviewed_by' => $actor->id,
                'reviewed_at' => now(),
                'decision_notes' => $notes ?: $application->decision_notes,
            ])->save(),
            afterCommit: function (FinancingApplication $application): void {
                $this->notifyApplicant($application, 'Permohonan dalam semakan', 'Permohonan pembiayaan anda kini sedang disemak oleh pihak admin.', route('member.financing.applications.show', $application), 'Lihat Permohonan');
                $this->recordAudit('financing_application_under_review', $application);
            }
        );
    }

    public function markIncompleteDocuments(FinancingApplication $application, User $actor, string $notes): FinancingApplication
    {
        return $this->transitionApplication(
            $application,
            $actor,
            allowed: [
                FinancingApplicationStatus::Submitted,
                FinancingApplicationStatus::GuarantorAccepted,
                FinancingApplicationStatus::UnderReview,
            ],
            nextStatus: FinancingApplicationStatus::IncompleteDocuments,
            historyAction: 'incomplete_documents',
            notes: $notes,
            mutate: fn (FinancingApplication $application) => $application->forceFill([
                'reviewed_by' => $actor->id,
                'reviewed_at' => now(),
                'decision_notes' => $notes,
            ])->save(),
            afterCommit: function (FinancingApplication $application): void {
                $this->notifyApplicant($application, 'Dokumen tambahan diperlukan', 'Permohonan pembiayaan anda memerlukan dokumen tambahan sebelum semakan dapat diteruskan.', route('member.financing.applications.show', $application), 'Muat Naik Dokumen');
                $this->recordAudit('financing_application_incomplete_documents', $application);
            }
        );
    }

    public function approve(FinancingApplication $application, User $actor, array $attributes): FinancingApplication
    {
        return $this->transitionApplication(
            $application,
            $actor,
            allowed: [FinancingApplicationStatus::UnderReview],
            nextStatus: FinancingApplicationStatus::Approved,
            historyAction: 'approved',
            notes: $attributes['decision_notes'] ?? null,
            mutate: fn (FinancingApplication $application) => $application->forceFill([
                'approved_amount' => $attributes['approved_amount'],
                'approved_tenure_months' => $attributes['approved_tenure_months'],
                'decision_notes' => $attributes['decision_notes'] ?? null,
                'approved_by' => $actor->id,
                'approved_at' => now(),
                'reviewed_by' => $application->reviewed_by ?: $actor->id,
                'reviewed_at' => $application->reviewed_at ?: now(),
                'rejected_by' => null,
                'rejected_at' => null,
                'rejection_reason' => null,
            ])->save(),
            afterCommit: function (FinancingApplication $application): void {
                $this->notifyApplicant($application, 'Permohonan pembiayaan diluluskan', 'Permohonan pembiayaan anda telah diluluskan.', route('member.financing.applications.show', $application), 'Lihat Keputusan');
                $this->recordAudit('financing_application_approved', $application);
            }
        );
    }

    public function reject(FinancingApplication $application, User $actor, array $attributes): FinancingApplication
    {
        return $this->transitionApplication(
            $application,
            $actor,
            allowed: [FinancingApplicationStatus::UnderReview],
            nextStatus: FinancingApplicationStatus::Rejected,
            historyAction: 'rejected',
            notes: $attributes['rejection_reason'],
            mutate: fn (FinancingApplication $application) => $application->forceFill([
                'decision_notes' => $attributes['decision_notes'] ?? null,
                'rejected_by' => $actor->id,
                'rejected_at' => now(),
                'rejection_reason' => $attributes['rejection_reason'],
                'reviewed_by' => $application->reviewed_by ?: $actor->id,
                'reviewed_at' => $application->reviewed_at ?: now(),
            ])->save(),
            afterCommit: function (FinancingApplication $application): void {
                $this->notifyApplicant($application, 'Permohonan pembiayaan ditolak', 'Permohonan pembiayaan anda tidak dapat diluluskan pada masa ini.', route('member.financing.applications.show', $application), 'Lihat Keputusan');
                $this->recordAudit('financing_application_rejected', $application);
            }
        );
    }

    public function cancel(FinancingApplication $application, User $actor, ?string $notes = null): FinancingApplication
    {
        return $this->transitionApplication(
            $application,
            $actor,
            allowed: [
                FinancingApplicationStatus::Submitted,
                FinancingApplicationStatus::GuarantorPending,
                FinancingApplicationStatus::GuarantorRejected,
                FinancingApplicationStatus::IncompleteDocuments,
            ],
            nextStatus: FinancingApplicationStatus::Cancelled,
            historyAction: 'cancelled',
            notes: $notes,
            mutate: fn (FinancingApplication $application) => $application->forceFill([
                'decision_notes' => $notes ?: $application->decision_notes,
            ])->save()
        );
    }

    public function close(FinancingApplication $application, User $actor, ?string $notes = null): FinancingApplication
    {
        return $this->transitionApplication(
            $application,
            $actor,
            allowed: [
                FinancingApplicationStatus::Approved,
                FinancingApplicationStatus::Rejected,
                FinancingApplicationStatus::Cancelled,
            ],
            nextStatus: FinancingApplicationStatus::Closed,
            historyAction: 'closed',
            notes: $notes,
            mutate: fn (FinancingApplication $application) => $application->forceFill([
                'decision_notes' => $notes ?: $application->decision_notes,
            ])->save()
        );
    }

    public function guarantorSearchResults(Member $member, string $search): Collection
    {
        return Member::query()
            ->where('cooperative_id', $member->cooperative_id)
            ->where('membership_status', 'active')
            ->whereKeyNot($member->id)
            ->whereHas('user')
            ->with('user')
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('full_name', 'like', "%{$search}%")
                        ->orWhere('member_no', 'like', "%{$search}%")
                        ->orWhereHas('user', fn (Builder $userQuery) => $userQuery->where('staff_id', 'like', "%{$search}%"));
                });
            })
            ->orderBy('full_name')
            ->limit(10)
            ->get()
            ->map(fn (Member $candidate) => [
                'id' => $candidate->id,
                'name' => $candidate->full_name,
                'member_no' => $candidate->member_no,
                'employee_no' => $candidate->user?->staff_id,
                'membership_status' => $candidate->membership_status->value,
                'membership_status_label' => 'Aktif',
                'has_login' => (bool) $candidate->user_id,
            ]);
    }

    public function adminVisibleApplications(User $user, ?int $cooperativeId): Builder
    {
        $query = FinancingApplication::query()->forCooperative($cooperativeId);

        if ($user->role === AccessControl::ROLE_SUPER_ADMIN) {
            return $query;
        }

        if (! $user->unit_id) {
            return $query;
        }

        return $query->where(function (Builder $query) use ($user): void {
            $query->where('unit_id', $user->unit_id)
                ->orWhereHas('unit', fn (Builder $unitQuery) => $unitQuery->where('slug', 'unit-pinjaman'));
        });
    }

    private function transitionApplication(
        FinancingApplication $application,
        User $actor,
        array $allowed,
        FinancingApplicationStatus $nextStatus,
        string $historyAction,
        ?string $notes = null,
        ?callable $mutate = null,
        ?callable $afterCommit = null,
    ): FinancingApplication {
        return DB::transaction(function () use ($application, $actor, $allowed, $nextStatus, $historyAction, $notes, $mutate, $afterCommit): FinancingApplication {
            $application = FinancingApplication::query()->whereKey($application->id)->lockForUpdate()->firstOrFail();
            $fromStatus = $application->status;

            if (! in_array($fromStatus, $allowed, true)) {
                throw ValidationException::withMessages([
                    'status' => 'Status permohonan tidak sah untuk tindakan ini.',
                ]);
            }

            $application->forceFill(['status' => $nextStatus->value])->save();

            if ($mutate) {
                $mutate($application);
            }

            $application->refresh()->load(['member.user', 'product', 'unit']);

            $this->recordHistory($application, $historyAction, $fromStatus->value, $nextStatus->value, $actor, $notes);

            if ($afterCommit) {
                $afterCommit($application);
            }

            return $application;
        });
    }

    private function attachDocument(FinancingApplication $application, UploadedFile $file, User $actor, string $label): FinancingDocument
    {
        $stored = $this->files->storeSupportingDocument($file);

        return FinancingDocument::query()->create([
            'cooperative_id' => $application->cooperative_id,
            'financing_application_id' => $application->id,
            'uploaded_by' => $actor->id,
            'label' => $label,
            'document_key' => Str::slug($label),
            ...$stored,
        ]);
    }

    private function notifyApplicant(FinancingApplication $application, string $subject, string $intro, ?string $url, ?string $actionLabel): void
    {
        $application->loadMissing(['member.user', 'product']);

        $recipient = $application->member?->user;

        if (! $recipient) {
            return;
        }

        $recipient->notify(new FinancingWorkflowNotification(
            subjectLine: $subject,
            introLine: $intro,
            summaryLines: $this->summaryLines($application),
            actionUrl: $url,
            actionLabel: $actionLabel,
            cooperativeName: $this->cooperativeName(),
        ));
    }

    private function notifyGuarantors(FinancingApplication $application): void
    {
        $application->loadMissing(['guarantors.guarantorMember.user', 'member', 'product']);

        foreach ($application->guarantors as $guarantor) {
            $recipient = $guarantor->guarantorMember?->user;

            if (! $recipient) {
                continue;
            }

            $recipient->notify(new FinancingWorkflowNotification(
                subjectLine: 'Tindakan diperlukan sebagai penjamin',
                introLine: 'Anda telah dipilih sebagai penjamin bagi satu permohonan pembiayaan dan tindakan anda diperlukan.',
                summaryLines: [
                    'Pemohon: '.$application->member?->full_name,
                    'No. Ahli Pemohon: '.($application->member?->member_no ?: '-'),
                    ...$this->summaryLines($application),
                ],
                actionUrl: route('member.financing.guarantor-requests.show', $guarantor),
                actionLabel: 'Buka Permintaan Penjamin',
                cooperativeName: $this->cooperativeName(),
            ));
        }
    }

    private function notifyAdmins(FinancingApplication $application, string $subject, string $intro, ?string $url, ?string $actionLabel): void
    {
        $recipients = User::query()
            ->where('cooperative_id', $application->cooperative_id)
            ->whereIn('role', [AccessControl::ROLE_SUPER_ADMIN, AccessControl::ROLE_ADMIN])
            ->where(function (Builder $query) use ($application): void {
                $query->where('role', AccessControl::ROLE_SUPER_ADMIN)
                    ->orWhereNull('unit_id')
                    ->orWhere('unit_id', $application->unit_id)
                    ->orWhereHas('unit', fn (Builder $unitQuery) => $unitQuery->where('slug', 'unit-pinjaman'));
            })
            ->get();

        if ($recipients->isEmpty()) {
            return;
        }

        Notification::send($recipients, new FinancingWorkflowNotification(
            subjectLine: $subject,
            introLine: $intro,
            summaryLines: [
                'Pemohon: '.$application->member?->full_name,
                'No. Ahli: '.($application->member?->member_no ?: '-'),
                ...$this->summaryLines($application),
            ],
            actionUrl: $url,
            actionLabel: $actionLabel,
            cooperativeName: $this->cooperativeName(),
        ));
    }

    private function summaryLines(FinancingApplication $application): array
    {
        return [
            'No. Rujukan: '.$application->reference_no,
            'Produk: '.($application->product?->name ?: '-'),
            'Amaun Dimohon: RM '.number_format((float) $application->amount_requested, 2),
            'Tempoh: '.$application->tenure_months.' bulan',
            'Status: '.$application->status->label(),
        ];
    }

    private function recordHistory(
        FinancingApplication $application,
        string $action,
        ?string $fromStatus,
        ?string $toStatus,
        ?User $actor,
        ?string $notes = null,
        array $metadata = [],
    ): FinancingApplicationHistory {
        return FinancingApplicationHistory::query()->create([
            'cooperative_id' => $application->cooperative_id,
            'financing_application_id' => $application->id,
            'actor_id' => $actor?->id,
            'action' => $action,
            'from_status' => $fromStatus,
            'to_status' => $toStatus,
            'notes' => $notes,
            'metadata' => $metadata ?: null,
        ]);
    }

    private function recordAudit(string $action, FinancingApplication $application, ?User $actor = null, array $metadata = []): void
    {
        $actor ??= request()->user();

        $this->auditLogs->record($action, $application, [], [
            'reference_no' => $application->reference_no,
            'status' => $application->status->value,
        ], [
            ...$metadata,
            'actor_name' => $actor?->name,
            'staff_id' => $actor?->staff_id,
            'unit' => $actor?->unit?->name,
        ]);
    }

    private function normalizeRequiredDocuments(?string $text): array
    {
        return collect(preg_split('/\r\n|\r|\n/', (string) $text))
            ->map(fn ($line) => trim((string) $line))
            ->filter()
            ->values()
            ->all();
    }

    private function validateProductRules(FinancingProduct $product): void
    {
        $category = $product->relationLoaded('category')
            ? $product->category
            : FinancingCategory::query()->find($product->financing_category_id);

        if ($product->requires_guarantor && $product->guarantor_count < 1) {
            throw ValidationException::withMessages([
                'guarantor_count' => 'Bilangan penjamin mesti sekurang-kurangnya 1.',
            ]);
        }

        if (! $product->requires_guarantor) {
            $product->guarantor_count = 0;
        }

        if ($category?->type === FinancingCategoryType::Guaranteed && ! $product->requires_guarantor) {
            throw ValidationException::withMessages([
                'requires_guarantor' => 'Produk bagi kategori berpenjamin perlu mempunyai penjamin.',
            ]);
        }
    }

    private function validateSubmission(Member $member, FinancingProduct $product, Collection $guarantorIds, array $attributes): void
    {
        if ($member->membership_status->value !== 'active') {
            throw ValidationException::withMessages([
                'member' => 'Hanya ahli aktif boleh membuat permohonan pembiayaan.',
            ]);
        }

        if ($product->requires_guarantor && $guarantorIds->count() !== (int) $product->guarantor_count) {
            throw ValidationException::withMessages([
                'guarantor_member_ids' => 'Bilangan penjamin tidak memenuhi keperluan produk pembiayaan ini.',
            ]);
        }

        if (! $product->requires_guarantor && $guarantorIds->isNotEmpty()) {
            throw ValidationException::withMessages([
                'guarantor_member_ids' => 'Produk ini tidak memerlukan penjamin.',
            ]);
        }

        if ($guarantorIds->duplicates()->isNotEmpty()) {
            throw ValidationException::withMessages([
                'guarantor_member_ids' => 'Penjamin yang sama tidak boleh dipilih lebih daripada sekali.',
            ]);
        }

        if ($guarantorIds->contains($member->id)) {
            throw ValidationException::withMessages([
                'guarantor_member_ids' => 'Anda tidak boleh memilih diri sendiri sebagai penjamin.',
            ]);
        }

        $guarantors = Member::query()
            ->where('cooperative_id', $member->cooperative_id)
            ->whereIn('id', $guarantorIds)
            ->with('user')
            ->get();

        if ($guarantors->count() !== $guarantorIds->count()) {
            throw ValidationException::withMessages([
                'guarantor_member_ids' => 'Penjamin yang dipilih tidak sah.',
            ]);
        }

        foreach ($guarantors as $guarantor) {
            if ($guarantor->membership_status->value !== 'active') {
                throw ValidationException::withMessages([
                    'guarantor_member_ids' => 'Semua penjamin mestilah ahli aktif.',
                ]);
            }

            if (! $guarantor->user_id) {
                throw ValidationException::withMessages([
                    'guarantor_member_ids' => 'Penjamin yang dipilih mesti mempunyai akaun log masuk ahli.',
                ]);
            }
        }

        $amount = (float) ($attributes['amount_requested'] ?? 0);
        $tenure = (int) ($attributes['tenure_months'] ?? 0);

        if ($product->min_amount && $amount < (float) $product->min_amount) {
            throw ValidationException::withMessages([
                'amount_requested' => 'Amaun dimohon lebih rendah daripada had minimum produk.',
            ]);
        }

        if ($product->max_amount && $amount > (float) $product->max_amount) {
            throw ValidationException::withMessages([
                'amount_requested' => 'Amaun dimohon melebihi had maksimum produk.',
            ]);
        }

        if ($product->min_tenure_months && $tenure < (int) $product->min_tenure_months) {
            throw ValidationException::withMessages([
                'tenure_months' => 'Tempoh pembiayaan lebih pendek daripada had minimum produk.',
            ]);
        }

        if ($product->max_tenure_months && $tenure > (int) $product->max_tenure_months) {
            throw ValidationException::withMessages([
                'tenure_months' => 'Tempoh pembiayaan melebihi had maksimum produk.',
            ]);
        }
    }

    private function resolveProductForMember(int $productId, int $cooperativeId): FinancingProduct
    {
        return FinancingProduct::query()
            ->forCooperative($cooperativeId)
            ->active()
            ->with('category')
            ->findOrFail($productId);
    }

    private function generateReferenceNumber(int $cooperativeId): string
    {
        $prefix = 'FIN-'.now()->format('Ymd').'-';
        $latest = FinancingApplication::query()
            ->withTrashed()
            ->where('cooperative_id', $cooperativeId)
            ->where('reference_no', 'like', $prefix.'%')
            ->count();

        return $prefix.str_pad((string) ($latest + 1), 4, '0', STR_PAD_LEFT);
    }

    private function defaultFinancingUnitId(?int $cooperativeId): ?int
    {
        return Unit::query()
            ->when($cooperativeId, fn (Builder $query) => $query->where('cooperative_id', $cooperativeId))
            ->where('slug', 'unit-pinjaman')
            ->value('id');
    }

    private function cooperativeName(): string
    {
        return $this->settings->shared()['cooperative']['name'] ?? config('app.name');
    }
}
