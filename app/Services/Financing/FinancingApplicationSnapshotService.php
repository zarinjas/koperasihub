<?php

namespace App\Services\Financing;

use App\Models\FinancingApplication;
use App\Models\FinancingApplicationSnapshot;
use App\Models\FinancingProduct;

class FinancingApplicationSnapshotService
{
    public function createForApplication(FinancingApplication $application): FinancingApplicationSnapshot
    {
        $product = $application->product;
        $product->load([
            'sections' => fn ($q) => $q->ordered(),
            'fields' => fn ($q) => $q->ordered(),
            'documentTemplates' => fn ($q) => $q->ordered(),
        ]);

        $productSnap = $this->buildProductSnapshot($product);
        $sectionsSnap = $this->buildSectionsWithFieldsSnapshot($product);
        $templatesSnap = $this->buildDocumentTemplatesSnapshot($product);
        $resolvedConfig = $this->buildResolvedConfiguration($product, $application);
        $flatFieldsSnap = $this->buildFlatFieldsSnapshot($product);

        return FinancingApplicationSnapshot::create([
            'cooperative_id' => $product->cooperative_id,
            'financing_application_id' => $application->id,
            'financing_product_id' => $product->id,
            'product_snapshot_json' => $productSnap,
            'sections_snapshot_json' => $sectionsSnap,
            'fields_snapshot_json' => $flatFieldsSnap,
            'document_templates_snapshot_json' => $templatesSnap,
            'resolved_configuration_json' => $resolvedConfig,
        ]);
    }

    public function buildProductSnapshot(FinancingProduct $product): array
    {
        return [
            'name' => $product->name,
            'description' => $product->description,
            'min_amount' => $product->min_amount,
            'max_amount' => $product->max_amount,
            'min_tenure_months' => $product->min_tenure_months,
            'max_tenure_months' => $product->max_tenure_months,
            'annual_rate_percent' => $product->annual_rate_percent,
            'rate_tiers_json' => $product->rate_tiers_json,
            'rate_note' => $product->rate_note,
            'rate_image_path' => $product->rate_image_path,
            'requires_guarantor' => $product->requires_guarantor,
            'guarantor_count' => $product->guarantor_count,
            'requires_stamped_upload' => $product->requires_stamped_upload,
            'stamped_upload_instructions' => $product->stamped_upload_instructions,
            'form_template_path' => $product->form_template_path,
            'form_template_name' => $product->form_template_name,
            'is_active' => $product->is_active,
        ];
    }

    /**
     * Build sections with their fields embedded — ready for frontend consumption.
     */
    public function buildSectionsWithFieldsSnapshot(FinancingProduct $product): array
    {
        $fieldsBySection = $product->fields->groupBy('financing_product_section_id');

        return $product->sections
            ->map(fn ($section) => [
                'id' => $section->id,
                'title' => $section->title,
                'description' => $section->description,
                'page_break_before' => $section->page_break_before,
                'sort_order' => $section->sort_order,
                'is_active' => $section->is_active,
                'fields' => ($fieldsBySection->get($section->id) ?? collect())
                    ->map(fn ($field) => $this->serializeField($field))
                    ->values()
                    ->toArray(),
            ])
            ->values()
            ->toArray();
    }

    /**
     * Flat fields list for direct field lookups.
     */
    public function buildFlatFieldsSnapshot(FinancingProduct $product): array
    {
        return $product->fields
            ->map(fn ($field) => $this->serializeField($field))
            ->values()
            ->toArray();
    }

    private function serializeField($field): array
    {
        return [
            'id' => $field->id,
            'section_id' => $field->financing_product_section_id,
            'label' => $field->label,
            'field_key' => $field->field_key,
            'type' => $field->type->value,
            'type_label' => $field->type->label(),
            'placeholder' => $field->placeholder,
            'help_text' => $field->help_text,
            'is_required' => $field->is_required,
            'options_json' => $field->options_json,
            'validation_json' => $field->validation_json,
            'settings_json' => $field->settings_json,
            'file_url' => $field->file_url,
            'sort_order' => $field->sort_order,
            'is_active' => $field->is_active,
        ];
    }

    public function buildDocumentTemplatesSnapshot(FinancingProduct $product): array
    {
        return $product->documentTemplates
            ->map(fn ($template) => [
                'id' => $template->id,
                'code' => $template->code,
                'name' => $template->name,
                'type' => $template->type,
                'source_type' => $template->source_type,
                'requires_upload' => $template->requires_upload,
                'requires_verification' => $template->requires_verification,
                'template_path' => $template->template_path,
                'html_template' => $template->html_template,
                'settings_json' => $template->settings_json,
                'sort_order' => $template->sort_order,
            ])
            ->values()
            ->toArray();
    }

    public function buildResolvedConfiguration(FinancingProduct $product, FinancingApplication $application): array
    {
        $resolvedRate = $product->resolveRate($application->tenure_months);

        return [
            'resolved_rate_percent' => $resolvedRate,
            'guarantor_count_required' => $product->requires_guarantor ? $product->guarantor_count : 0,
            'submission_timestamp' => $application->submitted_at?->toIso8601String(),
            'amount_requested' => $application->amount_requested,
            'tenure_months' => $application->tenure_months,
        ];
    }
}