<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreFinancingProductRequest;
use App\Http\Requests\Admin\UpdateFinancingProductRequest;
use App\Models\FinancingCategory;
use App\Models\FinancingProduct;
use App\Models\FinancingProductField;
use App\Models\Unit;
use App\Services\FinancingService;
use App\Services\Settings\SettingsService;
use App\Support\AccessControl;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
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
            ->with(['category', 'unit'])
            ->when($search !== '', fn ($query) => $query->where('name', 'like', "%{$search}%"))
            ->when($categoryId > 0, fn ($query) => $query->where('financing_category_id', $categoryId))
            ->orderBy('sort_order')
            ->orderBy('name')
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
            'unitOptions' => $this->unitOptions(),
            'productFields' => [],
            'fieldTypeOptions' => $this->fieldTypeOptions(),
        ]);
    }

    public function store(StoreFinancingProductRequest $request): RedirectResponse
    {
        $product = $this->financing->createOrUpdateProduct($request->validated() + [
            'rate_image' => $request->file('rate_image'),
        ], $request->user());

        return redirect()
            ->route('admin.financing.products.edit', $product)
            ->with('status', 'Produk pembiayaan berjaya disimpan.');
    }

    public function edit(FinancingProduct $product): Response
    {
        $this->ensureSameCooperative($product);

        return Inertia::render('Admin/Pages/Financing/Products/Form', [
            'mode' => 'edit',
            'product' => $this->serializeProduct($product->load(['category', 'unit'])),
            'categoryOptions' => $this->categoryOptions(),
            'unitOptions' => $this->unitOptions(),
            'productFields' => $this->serializeProductFields($product),
            'fieldTypeOptions' => $this->fieldTypeOptions(),
        ]);
    }

    public function update(UpdateFinancingProductRequest $request, FinancingProduct $product): RedirectResponse
    {
        $this->ensureSameCooperative($product);

        $this->financing->createOrUpdateProduct($request->validated() + [
            'rate_image' => $request->file('rate_image'),
        ], $request->user(), $product);

        return back()->with('status', 'Produk pembiayaan berjaya dikemas kini.');
    }

    public function destroy(Request $request, FinancingProduct $product): RedirectResponse
    {
        $this->ensureSameCooperative($product);
        abort_unless($request->user()?->can(AccessControl::PERMISSION_MANAGE_FINANCING_PRODUCTS), 403);

        if ($product->applications()->exists()) {
            return back()->withErrors([
                'product' => 'Produk ini mempunyai permohonan dan tidak boleh dipadam. Sila nyahaktifkan produk.',
            ]);
        }

        $this->financing->deleteProduct($product, $request->user());

        return redirect()
            ->route('admin.financing.products.index')
            ->with('status', 'Produk pembiayaan berjaya dipadam.');
    }

    public function deactivate(Request $request, FinancingProduct $product): RedirectResponse
    {
        $this->ensureSameCooperative($product);
        abort_unless($request->user()?->can(AccessControl::PERMISSION_MANAGE_FINANCING_PRODUCTS), 403);

        $this->financing->deactivateProduct($product, $request->user());

        return back()->with('status', 'Produk pembiayaan telah dinyahaktifkan.');
    }

    // --- Product field CRUD (JSON responses for the inline form builder) ---

    public function storeField(Request $request, FinancingProduct $product): JsonResponse
    {
        $this->ensureSameCooperative($product);
        abort_unless($request->user()?->can(AccessControl::PERMISSION_MANAGE_FINANCING_PRODUCTS), 403);

        $validated = $request->validate([
            'label' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'max:50'],
            'placeholder' => ['nullable', 'string', 'max:500'],
            'help_text' => ['nullable', 'string', 'max:1000'],
            'is_required' => ['nullable', 'boolean'],
            'options_json' => ['nullable', 'array'],
            'settings_json' => ['nullable', 'array'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $field = $this->financing->saveProductField($product, $validated);

        return response()->json($this->serializeField($field));
    }

    public function updateField(Request $request, FinancingProduct $product, FinancingProductField $field): JsonResponse
    {
        $this->ensureSameCooperative($product);
        abort_unless($request->user()?->can(AccessControl::PERMISSION_MANAGE_FINANCING_PRODUCTS), 403);
        abort_unless($field->financing_product_id === $product->id, 404);

        $validated = $request->validate([
            'label' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'max:50'],
            'placeholder' => ['nullable', 'string', 'max:500'],
            'help_text' => ['nullable', 'string', 'max:1000'],
            'is_required' => ['nullable', 'boolean'],
            'options_json' => ['nullable', 'array'],
            'settings_json' => ['nullable', 'array'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $field = $this->financing->saveProductField($product, $validated, $field);

        return response()->json($this->serializeField($field));
    }

    public function destroyField(Request $request, FinancingProduct $product, FinancingProductField $field): JsonResponse
    {
        $this->ensureSameCooperative($product);
        abort_unless($request->user()?->can(AccessControl::PERMISSION_MANAGE_FINANCING_PRODUCTS), 403);
        abort_unless($field->financing_product_id === $product->id, 404);

        $this->financing->deleteProductField($field);

        return response()->json(['ok' => true]);
    }

    public function reorderFields(Request $request, FinancingProduct $product): JsonResponse
    {
        $this->ensureSameCooperative($product);
        abort_unless($request->user()?->can(AccessControl::PERMISSION_MANAGE_FINANCING_PRODUCTS), 403);

        $ids = $request->validate(['ids' => ['required', 'array'], 'ids.*' => ['integer']])['ids'];
        $this->financing->reorderProductFields($product, $ids);

        return response()->json(['ok' => true]);
    }

    private function serializeProduct(FinancingProduct $product): array
    {
        return [
            'id' => $product->id,
            'financing_category_id' => $product->financing_category_id,
            'category_name' => $product->category?->name,
            'unit_id' => $product->unit_id,
            'unit_name' => $product->unit?->name,
            'name' => $product->name,
            'slug' => $product->slug,
            'description' => $product->description,
            'eligibility_terms' => $product->eligibility_terms,
            'product_terms' => $product->product_terms,
            'application_notes' => $product->application_notes,
            'application_instructions' => $product->application_instructions,
            'required_documents_note' => $product->required_documents_note,
            'officer_contact_name' => $product->officer_contact_name,
            'officer_contact_phone' => $product->officer_contact_phone,
            'officer_contact_email' => $product->officer_contact_email,
            'min_amount' => $product->min_amount !== null ? (float) $product->min_amount : null,
            'max_amount' => $product->max_amount !== null ? (float) $product->max_amount : null,
            'min_tenure_months' => $product->min_tenure_months,
            'max_tenure_months' => $product->max_tenure_months,
            'rate_image_path' => $product->rate_image_path,
            'existing_rate_image_url' => $product->rate_image_path ? Storage::disk('public')->url($product->rate_image_path) : null,
            'annual_rate_percent' => $product->annual_rate_percent !== null ? (float) $product->annual_rate_percent : null,
            'rate_note' => $product->rate_note,
            'requires_guarantor' => $product->requires_guarantor,
            'guarantor_count' => $product->guarantor_count,
            'required_documents' => $product->required_documents_json ?? [],
            'required_documents_text' => implode("\n", $product->required_documents_json ?? []),
            'product_documents' => collect(FinancingProduct::PRODUCT_DOCUMENTS)->map(function (array $definition, string $key) use ($product): ?array {
                $path = $product->{$definition['path']};

                if (! $path) {
                    return null;
                }

                return [
                    'key' => $key,
                    'label' => $definition['label'],
                    'file_name' => $product->{$definition['name']} ?: basename($path),
                ];
            })->filter()->values()->all(),
            'is_active' => $product->is_active,
            'sort_order' => $product->sort_order,
            'has_applications' => $product->relationLoaded('applications')
                ? $product->applications->isNotEmpty()
                : $product->applications()->exists(),
        ];
    }

    private function serializeProductFields(FinancingProduct $product): array
    {
        return $product->productFields()
            ->get()
            ->map(fn (FinancingProductField $f) => $this->serializeField($f))
            ->all();
    }

    private function serializeField(FinancingProductField $field): array
    {
        return [
            'id' => $field->id,
            'label' => $field->label,
            'field_key' => $field->field_key,
            'type' => $field->type,
            'placeholder' => $field->placeholder,
            'help_text' => $field->help_text,
            'is_required' => $field->is_required,
            'options_json' => $field->options_json,
            'settings_json' => $field->settings_json,
            'sort_order' => $field->sort_order,
            'is_active' => $field->is_active,
            'is_content_block' => $field->isContentBlock(),
        ];
    }

    private function categoryOptions(bool $includeAll = false): array
    {
        $options = FinancingCategory::query()
            ->forCooperative($this->settings->activeCooperative()?->id)
            ->active()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(fn (FinancingCategory $category) => [
                'value' => $category->id,
                'label' => $category->name,
                'type' => $category->type->value,
            ])
            ->all();

        return $includeAll
            ? [['value' => '', 'label' => 'Semua kategori', 'type' => ''], ...$options]
            : $options;
    }

    private function unitOptions(): array
    {
        $options = Unit::query()
            ->where('cooperative_id', $this->settings->activeCooperative()?->id)
            ->active()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(fn (Unit $unit) => [
                'value' => $unit->id,
                'label' => $unit->name,
            ])
            ->all();

        return [['value' => '', 'label' => 'Unit Pinjaman / Lalai'], ...$options];
    }

    private function fieldTypeOptions(): array
    {
        return [
            ['value' => 'short_text', 'label' => 'Jawapan Pendek'],
            ['value' => 'long_text', 'label' => 'Jawapan Panjang'],
            ['value' => 'email', 'label' => 'Email'],
            ['value' => 'phone', 'label' => 'No. Telefon'],
            ['value' => 'identity_no', 'label' => 'No. Kad Pengenalan'],
            ['value' => 'number', 'label' => 'Nombor'],
            ['value' => 'currency', 'label' => 'Jumlah Wang (RM)'],
            ['value' => 'date', 'label' => 'Tarikh'],
            ['value' => 'select', 'label' => 'Dropdown'],
            ['value' => 'radio', 'label' => 'Pilihan Tunggal'],
            ['value' => 'checkbox', 'label' => 'Kotak Pilihan'],
            ['value' => 'yes_no', 'label' => 'Ya / Tidak'],
            ['value' => 'file', 'label' => 'Muat Naik Fail'],
            ['value' => 'signature', 'label' => 'Tandatangan'],
            ['value' => 'agreement_checkbox', 'label' => 'Persetujuan'],
            ['value' => 'instruction_text', 'label' => 'Teks Arahan'],
            ['value' => 'note', 'label' => 'Nota'],
            ['value' => 'rich_text', 'label' => 'Teks Kaya'],
        ];
    }

    private function ensureSameCooperative(FinancingProduct $product): void
    {
        abort_unless($product->cooperative_id === $this->settings->activeCooperative()?->id, 404);
    }
}
