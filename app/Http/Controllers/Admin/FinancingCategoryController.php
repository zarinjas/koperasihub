<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreFinancingCategoryRequest;
use App\Http\Requests\Admin\UpdateFinancingCategoryRequest;
use App\Models\FinancingCategory;
use App\Services\FinancingService;
use App\Services\Settings\SettingsService;
use App\Support\AccessControl;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
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
        $search = trim((string) $request->string('search'));

        $categories = FinancingCategory::query()
            ->forCooperative($this->settings->activeCooperative()?->id)
            ->withCount('products')
            ->when($search !== '', fn ($query) => $query->where('name', 'like', "%{$search}%"))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(fn (FinancingCategory $category) => $this->serializeCategory($category))
            ->all();

        return Inertia::render('Admin/Pages/Financing/Categories/Index', [
            'filters' => ['search' => $search],
            'categories' => $categories,
            'canEdit' => $request->user()?->hasRole(AccessControl::ROLE_SUPER_ADMIN) ?? false,
        ]);
    }

    public function create(): Response
    {
        abort(403, 'Kategori pembiayaan ialah rujukan sistem dan tidak boleh dicipta melalui panel admin.');
    }

    public function store(StoreFinancingCategoryRequest $request): RedirectResponse
    {
        abort(403, 'Kategori pembiayaan ialah rujukan sistem dan tidak boleh dicipta melalui panel admin.');
    }

    public function edit(FinancingCategory $category): Response
    {
        $this->ensureSameCooperative($category);
        abort_unless(request()->user()?->hasRole(AccessControl::ROLE_SUPER_ADMIN), 403);

        return Inertia::render('Admin/Pages/Financing/Categories/Form', [
            'mode' => 'edit',
            'category' => $this->serializeCategory($category),
        ]);
    }

    public function update(UpdateFinancingCategoryRequest $request, FinancingCategory $category): RedirectResponse
    {
        $this->ensureSameCooperative($category);

        $this->financing->createOrUpdateCategory($request->validated(), $request->user(), $category);

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
            'existing_rate_image_url' => $category->rate_image_path ? Storage::disk('public')->url($category->rate_image_path) : null,
            'is_active' => $category->is_active,
            'sort_order' => $category->sort_order,
            'products_count' => $category->products_count ?? 0,
        ];
    }

    private function ensureSameCooperative(FinancingCategory $category): void
    {
        abort_unless($category->cooperative_id === $this->settings->activeCooperative()?->id, 404);
    }
}
