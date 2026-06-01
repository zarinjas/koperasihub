<?php

namespace App\Http\Controllers\Admin\Financing;

use App\Http\Controllers\Controller;
use App\Models\FinancingDocumentTemplate;
use App\Models\FinancingProduct;
use App\Services\Settings\SettingsService;
use App\Support\AccessControl;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class FinancingDocumentTemplateController extends Controller
{
    public function __construct(
        private readonly SettingsService $settings,
    ) {}

    public function store(Request $request, FinancingProduct $product): JsonResponse
    {
        $this->authorizeProduct($request, $product);

        $data = $this->validated($request, $product);

        if ($request->hasFile('template_file')) {
            $data['template_path'] = $request->file('template_file')->store('financing/document-templates', 'public');
        }

        $template = $product->documentTemplates()->create([
            ...$data,
            'cooperative_id' => $product->cooperative_id,
        ]);

        return response()->json(['template' => $this->serialize($template)]);
    }

    public function update(Request $request, FinancingProduct $product, FinancingDocumentTemplate $template): JsonResponse
    {
        $this->authorizeProduct($request, $product);
        abort_unless($template->financing_product_id === $product->id, 404);

        $data = $this->validated($request, $product, $template);

        if ($request->hasFile('template_file')) {
            if ($template->template_path) {
                Storage::disk('public')->delete($template->template_path);
            }
            $data['template_path'] = $request->file('template_file')->store('financing/document-templates', 'public');
        }

        $template->update($data);

        return response()->json(['template' => $this->serialize($template->fresh())]);
    }

    public function destroy(Request $request, FinancingProduct $product, FinancingDocumentTemplate $template): JsonResponse
    {
        $this->authorizeProduct($request, $product);
        abort_unless($template->financing_product_id === $product->id, 404);

        $template->delete();

        return response()->json(['deleted' => true]);
    }

    private function validated(Request $request, FinancingProduct $product, ?FinancingDocumentTemplate $template = null): array
    {
        return $request->validate([
            'code' => [
                'required', 'string', 'max:80',
                Rule::unique('financing_document_templates', 'code')
                    ->where('financing_product_id', $product->id)
                    ->ignore($template?->id),
            ],
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'max:80'],
            'source_type' => ['required', 'string', 'in:html,pdf_upload,manual_upload_only'],
            'requires_upload' => ['nullable', 'boolean'],
            'requires_verification' => ['nullable', 'boolean'],
            'html_template' => ['nullable', 'string'],
            'settings_json' => ['nullable', 'array'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
            'template_file' => ['nullable', 'file', 'mimes:pdf,html,htm', 'max:20480'],
        ]);
    }

    private function authorizeProduct(Request $request, FinancingProduct $product): void
    {
        abort_unless($request->user()?->can(AccessControl::PERMISSION_MANAGE_FINANCING_PRODUCTS), 403);
        abort_unless($product->cooperative_id === $this->settings->activeCooperative()?->id, 404);
    }

    private function serialize(FinancingDocumentTemplate $template): array
    {
        return [
            'id' => $template->id,
            'code' => $template->code,
            'name' => $template->name,
            'type' => $template->type,
            'source_type' => $template->source_type,
            'requires_upload' => $template->requires_upload,
            'requires_verification' => $template->requires_verification,
            'template_path' => $template->template_path,
            'template_url' => $template->template_path ? Storage::disk('public')->url($template->template_path) : null,
            'html_template' => $template->html_template,
            'settings_json' => $template->settings_json ?? [],
            'sort_order' => $template->sort_order,
            'is_active' => $template->is_active,
        ];
    }
}
