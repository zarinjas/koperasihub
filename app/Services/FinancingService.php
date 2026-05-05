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
use App\Models\FinancingProductField;
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
        $isExistingCategory = $category->exists;

        if (($attributes['rate_image'] ?? null) instanceof UploadedFile) {
            $attributes['rate_image_path'] = $this->files->storeRateImage($attributes['rate_image']);
        }

        unset($attributes['rate_image']);

        $category->fill([
            'cooperative_id' => $category->cooperative_id ?? $actor->cooperative_id,
            'name' => $attributes['name'] ?? $category->name,
            'slug' => $isExistingCategory
                ? $category->slug
                : ($attributes['slug'] ?? $attributes['name']),
            'description' => array_key_exists('description', $attributes) ? $attributes['description'] : $category->description,
            'type' => $attributes['type'] ?? $category->type?->value,
            'rate_image_path' => $attributes['rate_image_path'] ?? $category->rate_image_path,
            'is_active' => array_key_exists('is_active', $attributes) ? (bool) $attributes['is_active'] : ($category->is_active ?? true),
            'sort_order' => array_key_exists('sort_order', $attributes) ? (int) $attributes['sort_order'] : ($category->sort_order ?? 0),
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
        $currentProductDocuments = $this->currentProductDocuments($product);
        $currentRateImage = $product->rate_image_path;

        if (($attributes['rate_image'] ?? null) instanceof UploadedFile) {
            $attributes['rate_image_path'] = $this->files->storeRateImage($attributes['rate_image']);
        }

        unset($attributes['rate_image']);

        foreach (FinancingProduct::PRODUCT_DOCUMENTS as $key => $definition) {
            $uploadKey = $key.'_pdf';

            if (($attributes[$uploadKey] ?? null) instanceof UploadedFile) {
                $stored = $this->files->storeProductPdf($attributes[$uploadKey]);
                $attributes[$definition['path']] = $stored['file_path'];
                $attributes[$definition['name']] = $stored['file_name'];
            }

            unset($attributes[$uploadKey]);
        }

        $cooperativeId = $product->cooperative_id ?? $actor->cooperative_id;
        $name = $attributes['name'];
        $slug = $this->generateProductSlug($name, $cooperativeId, $product->exists ? $product->id : null);

        $product->fill([
            'cooperative_id' => $cooperativeId,
            'financing_category_id' => $attributes['financing_category_id'],
            'unit_id' => $attributes['unit_id'] ?? $this->defaultFinancingUnitId($cooperativeId),
            'name' => $name,
            'slug' => $slug,
            'description' => $attributes['description'] ?? null,
            'eligibility_terms' => $attributes['eligibility_terms'] ?? null,
            'product_terms' => $attributes['product_terms'] ?? null,
            'application_notes' => $attributes['application_notes'] ?? null,
            'application_instructions' => $attributes['application_instructions'] ?? null,
            'min_amount' => $attributes['min_amount'] ?? null,
            'max_amount' => $attributes['max_amount'] ?? null,
            'min_tenure_months' => $attributes['min_tenure_months'] ?? null,
            'max_tenure_months' => $attributes['max_tenure_months'] ?? null,
            'rate_image_path' => $attributes['rate_image_path'] ?? $product->rate_image_path,
            'annual_rate_percent' => $attributes['annual_rate_percent'] ?? null,
            'rate_note' => $attributes['rate_note'] ?? null,
            'requires_guarantor' => (bool) ($attributes['requires_guarantor'] ?? false),
            'guarantor_count' => (int) ($attributes['guarantor_count'] ?? 0),
            'required_documents_json' => $this->normalizeRequiredDocuments($attributes['required_documents_text'] ?? null),
            'required_documents_note' => $attributes['required_documents_note'] ?? null,
            'officer_contact_name' => $attributes['officer_contact_name'] ?? null,
            'officer_contact_phone' => $attributes['officer_contact_phone'] ?? null,
            'officer_contact_email' => $attributes['officer_contact_email'] ?? null,
            'consent_pdf_path' => $attributes['consent_pdf_path'] ?? $product->consent_pdf_path,
            'consent_pdf_name' => $attributes['consent_pdf_name'] ?? $product->consent_pdf_name,
            'undertaking_pdf_path' => $attributes['undertaking_pdf_path'] ?? $product->undertaking_pdf_path,
            'undertaking_pdf_name' => $attributes['undertaking_pdf_name'] ?? $product->undertaking_pdf_name,
            'guide_pdf_path' => $attributes['guide_pdf_path'] ?? $product->guide_pdf_path,
            'guide_pdf_name' => $attributes['guide_pdf_name'] ?? $product->guide_pdf_name,
            'official_form_template_pdf_path' => $attributes['official_form_template_pdf_path'] ?? $product->official_form_template_pdf_path,
            'official_form_template_pdf_name' => $attributes['official_form_template_pdf_name'] ?? $product->official_form_template_pdf_name,
            'is_active' => (bool) ($attributes['is_active'] ?? true),
            'sort_order' => (int) ($attributes['sort_order'] ?? 0),
            'created_by' => $product->exists ? $product->created_by : $actor->id,
            'updated_by' => $actor->id,
        ]);

        $this->validateProductRules($product);
        $product->save();
        $this->cleanupReplacedRateImage($product, $currentRateImage, $attributes);
        $this->cleanupReplacedProductDocuments($currentProductDocuments, $product);
        $this->recordProductAudit($product, $actor);

        return $product->refresh();
    }

    public function deactivateProduct(FinancingProduct $product, User $actor): FinancingProduct
    {
        $product->forceFill(['is_active' => false, 'updated_by' => $actor->id])->save();

        $this->auditLogs->record('financing_product_deactivated', $product, [], [
            'name' => $product->name,
        ], [
            'actor_name' => $actor->name,
        ]);

        return $product->refresh();
    }

    public function deleteProduct(FinancingProduct $product, User $actor): void
    {
        $this->auditLogs->record('financing_product_deleted', $product, [], [
            'name' => $product->name,
        ], [
            'actor_name' => $actor->name,
        ]);

        $this->files->deletePublicFile($product->rate_image_path);
        $product->delete();
    }

    private function cleanupReplacedRateImage(FinancingProduct $product, ?string $currentRateImage, array $attributes): void
    {
        if (($attributes['remove_rate_image'] ?? false) && $currentRateImage) {
            $this->files->deletePublicFile($currentRateImage);
            $product->forceFill(['rate_image_path' => null])->save();

            return;
        }

        if (($attributes['rate_image_path'] ?? null) && $currentRateImage && $currentRateImage !== $attributes['rate_image_path']) {
            $this->files->deletePublicFile($currentRateImage);
        }
    }

    public function saveProductField(FinancingProduct $product, array $attributes, ?FinancingProductField $field = null): FinancingProductField
    {
        $field ??= new FinancingProductField();
        $fieldKey = Str::snake(Str::slug($attributes['label'], '_'));

        // Ensure field_key uniqueness within the product when creating.
        if (! $field->exists) {
            $existing = FinancingProductField::query()
                ->where('financing_product_id', $product->id)
                ->where('field_key', $fieldKey)
                ->exists();

            if ($existing) {
                $fieldKey = $fieldKey.'_'.uniqid();
            }
        }

        $settings = isset($attributes['settings_json']) ? (array) $attributes['settings_json'] : null;

        if ($settings && in_array($attributes['type'], ['rich_text', 'instruction_text'], true) && ! empty($settings['content'] ?? null)) {
            $settings['content'] = $this->sanitizeRichText($settings['content']);
        }

        $field->fill([
            'cooperative_id' => $product->cooperative_id,
            'financing_product_id' => $product->id,
            'label' => $attributes['label'],
            'field_key' => $field->exists ? $field->field_key : $fieldKey,
            'type' => $attributes['type'],
            'placeholder' => $attributes['placeholder'] ?? null,
            'help_text' => $attributes['help_text'] ?? null,
            'is_required' => (bool) ($attributes['is_required'] ?? false),
            'options_json' => isset($attributes['options_json']) ? (array) $attributes['options_json'] : null,
            'validation_json' => isset($attributes['validation_json']) ? (array) $attributes['validation_json'] : null,
            'settings_json' => $settings,
            'sort_order' => (int) ($attributes['sort_order'] ?? 0),
            'is_active' => (bool) ($attributes['is_active'] ?? true),
        ]);
        $field->save();

        return $field->refresh();
    }

    public function deleteProductField(FinancingProductField $field): void
    {
        $field->delete();
    }

    public function reorderProductFields(FinancingProduct $product, array $orderedIds): void
    {
        foreach ($orderedIds as $index => $id) {
            FinancingProductField::query()
                ->where('financing_product_id', $product->id)
                ->where('id', $id)
                ->update(['sort_order' => $index]);
        }
    }

    private function sanitizeRichText(string $html): string
    {
        $allowedTags = '<p><br><strong><b><em><i><u><s><ul><ol><li><a><table><thead><tbody><tr><th><td><h2><h3><h4><hr><blockquote><code><pre><sub><sup>';

        $html = strip_tags($html, $allowedTags);

        $html = preg_replace('/<([a-z][a-z0-9]*)\s[^>]*?(on\w+)=["\'][^"\']*["\'][^>]*?>/i', '<$1>', $html);
        $html = preg_replace('/javascript\s*:/i', '', $html);

        if (stripos($html, '<a ') !== false) {
            $html = preg_replace_callback('/<a\s([^>]*)>([^<]*)<\/a>/is', function ($matches) {
                $attrs = $matches[1];
                $attrs = preg_replace('/(href\s*=\s*["\'])(?!https?:\/\/|mailto:|tel:|#|\/)[^"\']*(["\'])/i', '$1#$2', $attrs);
                return '<a '.$attrs.'>'.$matches[2].'</a>';
            }, $html);
        }

        return $html;
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
                'custom_answers_json' => $this->normalizeCustomAnswers($attributes['custom_answers'] ?? [], $product),
                'status' => $product->requires_guarantor
                    ? FinancingApplicationStatus::GuarantorPending->value
                    : FinancingApplicationStatus::PendingCompletedForm->value,
                'submitted_at' => now(),
            ]);

            $this->recordHistory(
                $application,
                'submitted',
                null,
                $application->status->value,
                $actor,
                $product->requires_guarantor
                    ? 'Permohonan pembiayaan dihantar dan sedang menunggu maklum balas penjamin.'
                    : 'Permohonan pembiayaan dihantar dan sedang menunggu borang lengkap bercop.'
            );

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
                    FinancingApplicationStatus::GuarantorPending->value,
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
            $this->notifyApplicant(
                $application,
                'Permohonan pembiayaan diterima',
                $product->requires_guarantor
                    ? 'Permohonan pembiayaan anda telah berjaya dihantar. Sistem sedang menunggu maklum balas penjamin sebelum anda boleh memuat naik borang lengkap bercop.'
                    : 'Permohonan pembiayaan anda telah berjaya dihantar. Sila cetak borang, dapatkan tandatangan serta cop pengesahan, kemudian muat naik semula borang lengkap bercop.'
                ,
                route('member.financing.applications.show', $application),
                'Lihat Permohonan'
            );
            $this->notifyGuarantors($application);

            return $application;
        });
    }

    public function uploadCompletedForm(FinancingApplication $application, UploadedFile $file, User $actor): FinancingApplication
    {
        $application->loadMissing(['member.user', 'product', 'guarantors', 'unit']);

        return DB::transaction(function () use ($application, $file, $actor): FinancingApplication {
            $application = FinancingApplication::query()->whereKey($application->id)->lockForUpdate()->firstOrFail();
            $application->loadMissing(['member.user', 'product', 'guarantors', 'unit']);

            $this->ensureCompletedFormUploadAllowed($application);

            $stored = $this->files->storeCompletedFormPdf($file);
            $previousPath = $application->completed_form_pdf_path;
            $fromStatus = $application->status;
            $nextStatus = FinancingApplicationStatus::Submitted;

            $application->forceFill([
                'completed_form_pdf_path' => $stored['file_path'],
                'completed_form_original_name' => $stored['file_name'],
                'completed_form_uploaded_at' => now(),
                'status' => $nextStatus->value,
            ])->save();

            if ($previousPath && $previousPath !== $stored['file_path']) {
                $this->files->deletePrivateFile($previousPath);
            }

            $application->refresh()->load(['member.user', 'product', 'guarantors', 'unit']);

            $this->recordHistory(
                $application,
                'completed_form_uploaded',
                $fromStatus->value,
                $nextStatus->value,
                $actor,
                'Borang lengkap bercop telah dimuat naik oleh pemohon.'
            );

            $this->recordAudit('financing_completed_form_uploaded', $application, $actor, [
                'file_name' => $application->completed_form_original_name,
            ]);

            $this->notifyApplicant(
                $application,
                'Borang lengkap bercop diterima',
                'Borang lengkap bercop anda telah diterima. Permohonan kini sedia untuk tindakan pihak admin.',
                route('member.financing.applications.show', $application),
                'Lihat Permohonan'
            );

            $this->notifyAdmins(
                $application,
                'Permohonan sedia untuk semakan',
                'Borang lengkap bercop telah dimuat naik dan permohonan pembiayaan kini sedia untuk semakan admin.',
                route('admin.financing.applications.show', $application),
                'Semak Permohonan'
            );

            $this->recordAudit('financing_application_ready_for_review', $application, $actor);

            return $application;
        });
    }

    public function uploadAdditionalDocument(FinancingApplication $application, UploadedFile $file, User $actor, string $label): FinancingDocument
    {
        return DB::transaction(function () use ($application, $file, $actor, $label): FinancingDocument {
            $application = FinancingApplication::query()->whereKey($application->id)->lockForUpdate()->firstOrFail();
            $this->ensureAdditionalDocumentUploadAllowed($application);

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
                        'status' => FinancingApplicationStatus::PendingCompletedForm->value,
                    ]);

                    $this->recordHistory(
                        $application,
                        'all_guarantors_accepted',
                        FinancingApplicationStatus::GuarantorPending->value,
                        FinancingApplicationStatus::PendingCompletedForm->value,
                        $actor,
                        'Semua penjamin telah memberikan persetujuan. Permohonan kini menunggu borang lengkap bercop.'
                    );

                    $this->notifyApplicant(
                        $application->fresh(['member.user', 'product', 'unit']),
                        'Semua penjamin telah bersetuju',
                        'Semua penjamin yang dipilih telah memberikan persetujuan. Sila muat naik borang lengkap bercop untuk meneruskan proses permohonan.',
                        route('member.financing.applications.show', $application),
                        'Lihat Permohonan'
                    );
                    $this->notifyAdmins(
                        $application->fresh(['member.user', 'product', 'unit']),
                        'Semua penjamin telah bersetuju',
                        'Semua penjamin telah bersetuju. Sistem kini menunggu borang lengkap bercop daripada pemohon.',
                        route('admin.financing.applications.show', $application),
                        'Semak Permohonan'
                    );
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
                $this->notifyApplicant($application->fresh(['member.user', 'product', 'unit']), 'Penjamin tidak bersetuju', 'Salah seorang penjamin tidak bersetuju untuk menyokong permohonan pembiayaan anda.', route('member.financing.applications.show', $application), 'Lihat Permohonan');
            }

            if ($guarantor->guarantorMember?->user) {
                $guarantor->guarantorMember->user->notify(new FinancingWorkflowNotification(
                    subjectLine: 'Maklum balas penjamin direkodkan',
                    introLine: 'Maklum balas anda sebagai penjamin telah berjaya direkodkan.',
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
            },
            beforeTransition: fn (FinancingApplication $application) => $this->ensureProcessingReady($application, $actor),
        );
    }

    public function markIncompleteDocuments(FinancingApplication $application, User $actor, string $notes): FinancingApplication
    {
        return $this->transitionApplication(
            $application,
            $actor,
            allowed: [
                FinancingApplicationStatus::Submitted,
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
                $this->notifyApplicant($application, 'Dokumen tambahan diperlukan', 'Permohonan pembiayaan anda memerlukan dokumen tambahan sebelum semakan dapat diteruskan.', route('member.financing.applications.show', $application), 'Semak Permohonan');
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
                FinancingApplicationStatus::PendingCompletedForm,
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

    public function cancelByApplicant(FinancingApplication $application, User $actor, string $reason): FinancingApplication
    {
        return $this->transitionApplication(
            $application,
            $actor,
            allowed: FinancingApplicationStatus::memberCancellable(),
            nextStatus: FinancingApplicationStatus::Cancelled,
            historyAction: 'cancelled',
            notes: $reason,
            mutate: fn (FinancingApplication $application) => $application->forceFill([
                'cancelled_by' => $actor->id,
                'cancelled_at' => now(),
                'cancellation_reason' => $reason,
            ])->save(),
            afterCommit: function (FinancingApplication $application) use ($actor): void {
                $this->notifyApplicant(
                    $application,
                    'Permohonan pembiayaan dibatalkan',
                    'Permohonan pembiayaan anda telah dibatalkan seperti diminta.',
                    route('member.financing.applications.show', $application),
                    'Lihat Permohonan'
                );
                $this->notifyAdmins(
                    $application,
                    'Permohonan pembiayaan dibatalkan oleh pemohon',
                    'Pemohon telah membatalkan permohonan pembiayaan dan menyertakan sebab pembatalan untuk semakan admin.',
                    route('admin.financing.applications.show', $application),
                    'Semak Permohonan'
                );
                $this->recordAudit('financing_application_cancelled', $application, $actor, [
                    'cancelled_by_member' => true,
                    'cancellation_reason' => $application->cancellation_reason,
                ]);
            }
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
        ?callable $beforeTransition = null,
    ): FinancingApplication {
        return DB::transaction(function () use ($application, $actor, $allowed, $nextStatus, $historyAction, $notes, $mutate, $afterCommit, $beforeTransition): FinancingApplication {
            $application = FinancingApplication::query()->whereKey($application->id)->lockForUpdate()->firstOrFail();
            $fromStatus = $application->status;

            if (! in_array($fromStatus, $allowed, true)) {
                throw ValidationException::withMessages([
                    'status' => 'Status permohonan tidak sah untuk tindakan ini.',
                ]);
            }

            if ($beforeTransition) {
                $beforeTransition($application);
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
        $application->loadMissing(['member.user', 'product', 'canceller']);

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
                introLine: 'Anda telah dipilih sebagai penjamin bagi satu permohonan pembiayaan. Sila semak butiran dan berikan maklum balas anda.',
                summaryLines: [
                    'Pemohon: '.$application->member?->full_name,
                    'No. Ahli Pemohon: '.($application->member?->member_no ?: '-'),
                    ...$this->summaryLines($application),
                ],
                actionUrl: route('member.financing.guarantor-requests.show', $guarantor),
                actionLabel: 'Semak Permintaan Penjamin',
                cooperativeName: $this->cooperativeName(),
            ));
        }
    }

    private function notifyAdmins(FinancingApplication $application, string $subject, string $intro, ?string $url, ?string $actionLabel): void
    {
        $application->loadMissing(['member.user', 'product', 'canceller']);

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
        $lines = [
            'No. Rujukan: '.$application->reference_no,
            'Produk: '.($application->product?->name ?: '-'),
            'Amaun Dimohon: RM '.number_format((float) $application->amount_requested, 2),
            'Tempoh: '.$application->tenure_months.' bulan',
            'Status: '.$application->status->label(),
        ];

        if ($application->status === FinancingApplicationStatus::Cancelled && $application->cancellation_reason) {
            $lines[] = 'Sebab Pembatalan: '.$application->cancellation_reason;
        }

        return $lines;
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

    private function recordProductAudit(FinancingProduct $product, User $actor): void
    {
        $this->auditLogs->record('financing_product_terms_updated', $product, [], [
            'name' => $product->name,
            'slug' => $product->slug,
        ], [
            'actor_name' => $actor->name,
            'staff_id' => $actor->staff_id,
            'unit' => $actor->unit?->name,
        ]);

        $this->auditLogs->record('financing_product_documents_updated', $product, [], [
            'product_documents' => collect(FinancingProduct::PRODUCT_DOCUMENTS)
                ->mapWithKeys(fn (array $definition, string $key): array => [$key => filled($product->{$definition['path']})])
                ->all(),
        ], [
            'actor_name' => $actor->name,
            'staff_id' => $actor->staff_id,
            'unit' => $actor->unit?->name,
        ]);
    }

    private function generateProductSlug(string $name, int $cooperativeId, ?int $excludeId = null): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $counter = 2;

        while (
            FinancingProduct::query()
                ->withTrashed()
                ->where('cooperative_id', $cooperativeId)
                ->where('slug', $slug)
                ->when($excludeId, fn (Builder $q) => $q->whereKeyNot($excludeId))
                ->exists()
        ) {
            $slug = $base.'-'.$counter;
            $counter++;
        }

        return $slug;
    }

    private function normalizeCustomAnswers(array $rawAnswers, FinancingProduct $product): array
    {
        $activeFields = $product->productFields()->active()->get()->keyBy('field_key');
        $normalized = [];

        foreach ($rawAnswers as $key => $value) {
            $field = $activeFields->get($key);

            if (! $field || $field->isContentBlock()) {
                continue;
            }

            $normalized[$key] = $value;
        }

        return $normalized;
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

        $customAnswers = $attributes['custom_answers'] ?? [];
        $activeFields = $product->productFields()->active()->get();

        foreach ($activeFields as $field) {
            if ($field->isContentBlock()) {
                continue;
            }

            if ($field->is_required) {
                $value = $customAnswers[$field->field_key] ?? null;

                if (is_array($value)) {
                    $value = array_filter($value);
                }

                if ($value === null || $value === '' || (is_array($value) && empty($value))) {
                    throw ValidationException::withMessages([
                        "custom_answers.{$field->field_key}" => 'Ruangan ini diperlukan.',
                    ]);
                }
            }
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

    private function currentProductDocuments(FinancingProduct $product): array
    {
        return collect(FinancingProduct::PRODUCT_DOCUMENTS)
            ->mapWithKeys(fn (array $definition, string $key): array => [$key => $product->{$definition['path']}])
            ->all();
    }

    private function cleanupReplacedProductDocuments(array $currentProductDocuments, FinancingProduct $product): void
    {
        foreach (FinancingProduct::PRODUCT_DOCUMENTS as $key => $definition) {
            $previousPath = $currentProductDocuments[$key] ?? null;
            $newPath = $product->{$definition['path']};

            if ($previousPath && $previousPath !== $newPath) {
                $this->files->deletePrivateFile($previousPath);
            }
        }
    }

    private function ensureCompletedFormUploadAllowed(FinancingApplication $application): void
    {
        $allowed = [
            FinancingApplicationStatus::PendingCompletedForm,
            FinancingApplicationStatus::Submitted,
            FinancingApplicationStatus::IncompleteDocuments,
        ];

        if (! in_array($application->status, $allowed, true)) {
            throw ValidationException::withMessages([
                'completed_form' => 'Borang lengkap bercop tidak boleh dimuat naik pada status semasa.',
            ]);
        }

        if ($application->product?->requires_guarantor) {
            $hasPendingOrRejected = $application->guarantors()
                ->whereIn('status', [FinancingGuarantorStatus::Pending->value, FinancingGuarantorStatus::Rejected->value])
                ->exists();

            if ($hasPendingOrRejected) {
                throw ValidationException::withMessages([
                    'completed_form' => 'Sila tunggu sehingga semua penjamin memberikan maklum balas sebelum memuat naik borang lengkap bercop.',
                ]);
            }
        }

        if ($application->status === FinancingApplicationStatus::Submitted && $application->reviewed_at) {
            throw ValidationException::withMessages([
                'completed_form' => 'Borang lengkap bercop tidak boleh diganti selepas semakan dimulakan.',
            ]);
        }
    }

    private function ensureAdditionalDocumentUploadAllowed(FinancingApplication $application): void
    {
        if ($application->status !== FinancingApplicationStatus::IncompleteDocuments) {
            throw ValidationException::withMessages([
                'file' => 'Dokumen tambahan hanya boleh dimuat naik apabila diminta oleh pihak admin.',
            ]);
        }
    }

    private function ensureProcessingReady(FinancingApplication $application, ?User $actor = null): void
    {
        $application->loadMissing(['product', 'guarantors']);

        if (! filled($application->completed_form_pdf_path)) {
            $this->recordAudit('financing_application_blocked_missing_completed_form', $application, $actor);

            throw ValidationException::withMessages([
                'status' => 'Permohonan belum boleh diproses kerana borang lengkap bercop belum dimuat naik.',
            ]);
        }

        if ($application->product?->requires_guarantor && $application->guarantors()->where('status', '!=', FinancingGuarantorStatus::Accepted->value)->exists()) {
            throw ValidationException::withMessages([
                'status' => 'Permohonan belum boleh diproses kerana persetujuan penjamin masih belum lengkap.',
            ]);
        }
    }
}
