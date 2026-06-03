<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreFinancingProductRequest;
use App\Http\Requests\Admin\UpdateFinancingProductRequest;
use App\Models\FinancingCategory;
use App\Models\FinancingProduct;
use App\Models\FinancingProductField;
use App\Models\FinancingProductSection;
use App\Services\FinancingService;
use App\Services\Settings\SettingsService;
use App\Support\AccessControl;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class FinancingProductController extends Controller
{
    public function __construct(
        private readonly SettingsService $settings,
        private readonly FinancingService $financing,
    ) {}

    public function index(Request $request): Response
    {
        $search = trim((string) $request->string('search'));
        $categoryId = $request->integer('category');

        $products = FinancingProduct::query()
            ->forCooperative($this->settings->activeCooperative()?->id)
            ->with('category')
            ->when($search !== '', fn ($query) => $query->where('name', 'like', "%{$search}%"))
            ->when($categoryId > 0, fn ($query) => $query->where('financing_category_id', $categoryId))
            ->latest()
            ->get()
            ->map(fn (FinancingProduct $product) => $this->serializeProduct($product))
            ->all();

        return Inertia::render('Admin/Pages/Financing/Products/Index', [
            'filters' => [
                'search' => $search,
                'category' => $categoryId ?: '',
            ],
            'products' => $products,
            'categoryOptions' => $this->categoryOptions(includeAll: true),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Pages/Financing/Products/Form', [
            'mode' => 'create',
            'product' => null,
            'categoryOptions' => $this->categoryOptions(),
            'fieldTypeOptions' => $this->fieldTypeOptions(),
            'sections' => [],
            'documentTemplates' => [],
        ]);
    }

    public function store(StoreFinancingProductRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $data = $this->sanitizeProductData($data);

        $cooperativeId = $this->settings->activeCooperative()?->id;

        if (empty($data['slug'] ?? null)) {
            $data['slug'] = $this->uniqueSlug(Str::slug($data['name']), $cooperativeId);
        }

        $data['cooperative_id'] = $cooperativeId;
        $data['created_by'] = $request->user()?->id;

        if ($request->hasFile('rate_image') && $request->file('rate_image')->isValid()) {
            $data['rate_image_path'] = $request->file('rate_image')->store('financing/rate-images', 'public');
        }

        if ($request->hasFile('form_template') && $request->file('form_template')->isValid()) {
            $file = $request->file('form_template');
            $data['form_template_path'] = $file->store('financing/form-templates', 'public');
            $data['form_template_name'] = $file->getClientOriginalName();
        }

        $product = FinancingProduct::create($data);

        return redirect()
            ->route('admin.financing.products.edit', $product)
            ->with('status', 'Produk pembiayaan berjaya disimpan.');
    }

    public function edit(FinancingProduct $product): Response
    {
        $this->ensureSameCooperative($product);

        $product->load(['category', 'documentTemplates', 'supportingDocuments']);

        $sections = $product->sections()
            ->orderBy('sort_order')
            ->with(['fields' => fn ($query) => $query->orderBy('sort_order')])
            ->get()
            ->map(fn (FinancingProductSection $section) => $this->serializeSection($section))
            ->all();

        return Inertia::render('Admin/Pages/Financing/Products/Form', [
            'mode' => 'edit',
            'product' => $this->serializeProduct($product),
            'categoryOptions' => $this->categoryOptions(),
            'fieldTypeOptions' => $this->fieldTypeOptions(),
            'sections' => $sections,
            'documentTemplates' => $product->documentTemplates->map(fn ($template) => [
                'id' => $template->id,
                'code' => $template->code,
                'name' => $template->name,
                'type' => $template->type,
                'source_type' => $template->source_type,
                'requires_upload' => $template->requires_upload,
                'requires_verification' => $template->requires_verification,
                'template_url' => $template->template_path ? Storage::disk('public')->url($template->template_path) : null,
                'html_template' => $template->html_template,
                'settings_json' => $template->settings_json ?? [],
                'sort_order' => $template->sort_order,
                'is_active' => $template->is_active,
            ])->values()->all(),
            'supportingDocuments' => $product->supportingDocuments->map(fn ($doc) => [
                'id' => $doc->id,
                'name' => $doc->name,
                'description' => $doc->description,
                'mode' => $doc->mode,
                'count' => $doc->count,
                'is_required' => $doc->is_required,
                'accepted_types' => $doc->accepted_types,
                'max_size_kb' => $doc->max_size_kb,
                'sort_order' => $doc->sort_order,
                'is_active' => $doc->is_active,
                'slot_labels' => $doc->slotLabels(),
            ])->values()->all(),
        ]);
    }

    public function update(UpdateFinancingProductRequest $request, FinancingProduct $product): RedirectResponse
    {
        $this->ensureSameCooperative($product);

        $data = $request->validated();

        $data = $this->sanitizeProductData($data);

        if (empty($data['slug'] ?? null)) {
            $data['slug'] = $this->uniqueSlug(Str::slug($data['name']), $product->cooperative_id, $product->id);
        }

        $data['updated_by'] = $request->user()?->id;

        if ($request->hasFile('rate_image') && $request->file('rate_image')->isValid()) {
            if ($product->rate_image_path) {
                Storage::disk('public')->delete($product->rate_image_path);
            }
            $data['rate_image_path'] = $request->file('rate_image')->store('financing/rate-images', 'public');
        }

        if ($request->hasFile('form_template') && $request->file('form_template')->isValid()) {
            if ($product->form_template_path) {
                Storage::disk('public')->delete($product->form_template_path);
            }
            $file = $request->file('form_template');
            $data['form_template_path'] = $file->store('financing/form-templates', 'public');
            $data['form_template_name'] = $file->getClientOriginalName();
        }

        if ($request->boolean('remove_form_template') && $product->form_template_path) {
            Storage::disk('public')->delete($product->form_template_path);
            $data['form_template_path'] = null;
            $data['form_template_name'] = null;
        }

        $product->update($data);

        return back()->with('status', 'Produk pembiayaan berjaya dikemas kini.');
    }

    public function destroy(Request $request, FinancingProduct $product): RedirectResponse
    {
        $this->ensureSameCooperative($product);
        abort_unless($request->user()?->can(AccessControl::PERMISSION_MANAGE_FINANCING_PRODUCTS), 403);

        if ($product->rate_image_path) {
            Storage::disk('public')->delete($product->rate_image_path);
        }

        if ($product->form_template_path) {
            Storage::disk('public')->delete($product->form_template_path);
        }

        $product->delete();

        return redirect()
            ->route('admin.financing.products.index')
            ->with('status', 'Produk pembiayaan berjaya dipadam.');
    }

    private function serializeProduct(FinancingProduct $product): array
    {
        return [
            'id' => $product->id,
            'financing_category_id' => $product->financing_category_id,
            'category_name' => $product->category?->name,
            'name' => $product->name,
            'slug' => $product->slug,
            'description' => $product->description,
            'min_amount' => $product->min_amount !== null ? (float) $product->min_amount : null,
            'max_amount' => $product->max_amount !== null ? (float) $product->max_amount : null,
            'min_tenure_months' => $product->min_tenure_months,
            'max_tenure_months' => $product->max_tenure_months,
            'annual_rate_percent' => $product->annual_rate_percent !== null ? (float) $product->annual_rate_percent : null,
            'rate_tiers_json' => $product->rate_tiers_json ?? [],
            'rate_note' => $product->rate_note,
            'rate_image_path' => $product->rate_image_path,
            'existing_rate_image_url' => $product->rate_image_path ? Storage::disk('public')->url($product->rate_image_path) : null,
            'form_template_path' => $product->form_template_path,
            'form_template_name' => $product->form_template_name,
            'existing_form_template_url' => $product->form_template_path ? Storage::disk('public')->url($product->form_template_path) : null,
            'requires_guarantor' => $product->requires_guarantor,
            'guarantor_count' => $product->guarantor_count,
            'is_active' => $product->is_active,
        ];
    }

    private function serializeSection(FinancingProductSection $section): array
    {
        return [
            'id' => $section->id,
            'financing_product_id' => $section->financing_product_id,
            'title' => $section->title,
            'description' => $section->description,
            'page_break_before' => $section->page_break_before,
            'is_active' => $section->is_active,
            'fields' => $section->fields->map(fn (FinancingProductField $field) => $this->serializeField($field))->all(),
        ];
    }

    private function serializeField(FinancingProductField $field): array
    {
        return [
            'id' => $field->id,
            'financing_product_section_id' => $field->financing_product_section_id,
            'label' => $field->label,
            'field_key' => $field->field_key,
            'type' => $field->type->value,
            'type_label' => $field->type->label(),
            'placeholder' => $field->placeholder,
            'help_text' => $field->help_text,
            'is_required' => $field->is_required,
            'options_json' => $field->options_json,
            'settings_json' => $field->settings_json,
            'is_active' => $field->is_active,
            'file_url' => $field->file_url,
        ];
    }

    private function categoryOptions(bool $includeAll = false): array
    {
        $options = FinancingCategory::query()
            ->forCooperative($this->settings->activeCooperative()?->id)
            ->active()
            ->latest()
            ->get()
            ->map(fn (FinancingCategory $category) => [
                'value' => $category->id,
                'label' => $category->name,
            ])
            ->all();

        return $includeAll
            ? [['value' => '', 'label' => 'Semua kategori'], ...$options]
            : $options;
    }

    private function fieldTypeOptions(): array
    {
        return \App\Enums\FinancingFieldType::cases();
    }

    private function ensureSameCooperative(FinancingProduct $product): void
    {
        abort_unless($product->cooperative_id === $this->settings->activeCooperative()?->id, 404);
    }

    private function uniqueSlug(string $base, ?int $cooperativeId, ?int $excludeId = null): string
    {
        $slug = $base;
        $i = 2;

        while (
            FinancingProduct::query()
                ->withTrashed()
                ->where('cooperative_id', $cooperativeId)
                ->where('slug', $slug)
                ->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId))
                ->exists()
        ) {
            $slug = "{$base}-{$i}";
            $i++;
        }

        return $slug;
    }

    private function sanitizeProductData(array $data): array
    {
        unset($data['rate_image'], $data['form_template'], $data['remove_form_template']);

        if (isset($data['rate_tiers_json'])) {
            $data['rate_tiers_json'] = is_string($data['rate_tiers_json'])
                ? json_decode($data['rate_tiers_json'], true)
                : $data['rate_tiers_json'];
        }

        $data['requires_guarantor'] = filter_var($data['requires_guarantor'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $data['requires_stamped_upload'] = filter_var($data['requires_stamped_upload'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $data['is_active'] = filter_var($data['is_active'] ?? true, FILTER_VALIDATE_BOOLEAN);

        if (! $data['requires_guarantor']) {
            $data['guarantor_count'] = 0;
        }

        return $data;
    }
}