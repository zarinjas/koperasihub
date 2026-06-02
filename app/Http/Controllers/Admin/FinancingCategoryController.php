<?php

namespace App\Http\Controllers\Admin;

use App\Enums\FinancingCategoryType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreFinancingCategoryRequest;
use App\Http\Requests\Admin\UpdateFinancingCategoryRequest;
use App\Models\FinancingCategory;
use App\Services\FinancingService;
use App\Services\Settings\SettingsService;
use App\Support\AccessControl;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class FinancingCategoryController extends Controller
{
    public function __construct(
        private readonly SettingsService $settings,
        private readonly FinancingService $financing,
    ) {}

    public function index(Request $request): Response
    {
        $categories = FinancingCategory::query()
            ->forCooperative($this->settings->activeCooperative()?->id)
            ->withCount('products')
            ->latest()
            ->get()
            ->map(fn (FinancingCategory $category) => $this->serializeCategory($category))
            ->all();

        return Inertia::render('Admin/Pages/Financing/Categories/Index', [
            'categories' => $categories,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Pages/Financing/Categories/Form', [
            'mode' => 'create',
            'category' => null,
            'types' => $this->typeOptions(),
        ]);
    }

    public function store(StoreFinancingCategoryRequest $request): RedirectResponse
    {
        $data = $request->validated();

        if (empty($data['slug'] ?? null)) {
            $data['slug'] = Str::slug($data['name']);
        }

        $data['cooperative_id'] = $this->settings->activeCooperative()?->id;
        $data['created_by'] = $request->user()?->id;

        FinancingCategory::create($data);

        return redirect()
            ->route('admin.financing.categories.index')
            ->with('status', 'Kategori pembiayaan berjaya disimpan.');
    }

    public function edit(FinancingCategory $category): Response
    {
        $this->ensureSameCooperative($category);

        return Inertia::render('Admin/Pages/Financing/Categories/Form', [
            'mode' => 'edit',
            'category' => $this->serializeCategory($category),
            'types' => $this->typeOptions(),
        ]);
    }

    public function update(UpdateFinancingCategoryRequest $request, FinancingCategory $category): RedirectResponse
    {
        $this->ensureSameCooperative($category);

        $data = $request->validated();

        if (empty($data['slug'] ?? null)) {
            $data['slug'] = Str::slug($data['name']);
        }

        $data['updated_by'] = $request->user()?->id;

        $category->update($data);

        return back()->with('status', 'Kategori pembiayaan berjaya dikemas kini.');
    }

    private function serializeCategory(FinancingCategory $category): array
    {
        return [
            'id' => $category->id,
            'name' => $category->name,
            'slug' => $category->slug,
            'description' => $category->description,
            'type' => $category->type->value,
            'type_label' => $category->type->label(),
            'icon' => $category->icon,
            'is_active' => $category->is_active,
            'products_count' => $category->products_count ?? 0,
        ];
    }

    private function typeOptions(): array
    {
        return collect(FinancingCategoryType::cases())
            ->map(fn (FinancingCategoryType $type) => [
                'value' => $type->value,
                'label' => $type->label(),
            ])
            ->all();
    }

    private function ensureSameCooperative(FinancingCategory $category): void
    {
        abort_unless($category->cooperative_id === $this->settings->activeCooperative()?->id, 404);
    }
}