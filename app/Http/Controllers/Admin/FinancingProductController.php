<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreFinancingProductRequest;
use App\Http\Requests\Admin\UpdateFinancingProductRequest;
use App\Models\FinancingCategory;
use App\Models\FinancingProduct;
use App\Models\Unit;
use App\Services\FinancingService;
use App\Services\Settings\SettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
        ]);
    }

    public function store(StoreFinancingProductRequest $request): RedirectResponse
    {
        $product = $this->financing->createOrUpdateProduct($request->validated(), $request->user());

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
        ]);
    }

    public function update(UpdateFinancingProductRequest $request, FinancingProduct $product): RedirectResponse
    {
        $this->ensureSameCooperative($product);

        $this->financing->createOrUpdateProduct($request->validated(), $request->user(), $product);

        return back()->with('status', 'Produk pembiayaan berjaya dikemas kini.');
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
            'min_amount' => $product->min_amount !== null ? (float) $product->min_amount : null,
            'max_amount' => $product->max_amount !== null ? (float) $product->max_amount : null,
            'min_tenure_months' => $product->min_tenure_months,
            'max_tenure_months' => $product->max_tenure_months,
            'requires_guarantor' => $product->requires_guarantor,
            'guarantor_count' => $product->guarantor_count,
            'required_documents' => $product->required_documents_json ?? [],
            'required_documents_text' => implode("\n", $product->required_documents_json ?? []),
            'is_active' => $product->is_active,
            'sort_order' => $product->sort_order,
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
            ])
            ->all();

        return $includeAll
            ? [['value' => '', 'label' => 'Semua kategori'], ...$options]
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

    private function ensureSameCooperative(FinancingProduct $product): void
    {
        abort_unless($product->cooperative_id === $this->settings->activeCooperative()?->id, 404);
    }
}
