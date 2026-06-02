<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\InteractsWithActiveCooperative;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreFormCategoryRequest;
use App\Http\Requests\Admin\UpdateFormCategoryRequest;
use App\Models\FormCategory;
use App\Models\Unit;
use App\Services\AuditLogService;
use App\Support\AccessControl;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FormCategoryController extends Controller
{
    use InteractsWithActiveCooperative;

    public function __construct(
        private readonly AuditLogService $auditLog,
    ) {}

    public function index(Request $request): Response
    {
        $search = trim((string) $request->string('search'));

        $categories = FormCategory::query()
            ->where('cooperative_id', $this->activeCooperative()?->id)
            ->with('unit')
            ->withCount(['forms as published_forms_count' => fn ($query) => $query->published()])
            ->when($search !== '', fn ($query) => $query->where('name', 'like', "%{$search}%"))
            ->latest()
            ->get()
            ->map(fn (FormCategory $category) => [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
                'description' => $category->description,
                'icon' => $category->icon,
                'is_active' => $category->is_active,
                'published_forms_count' => $category->published_forms_count,
                'unit_name' => $category->unit?->name,
            ])
            ->all();

        return Inertia::render('Admin/Pages/Forms/Categories/Index', [
            'filters' => ['search' => $search],
            'categories' => $categories,
            'canCreate' => $request->user()?->can(AccessControl::PERMISSION_CREATE_FORMS) ?? false,
            'canEdit' => $request->user()?->can(AccessControl::PERMISSION_EDIT_FORMS) ?? false,
            'canDelete' => $request->user()?->can(AccessControl::PERMISSION_DELETE_FORMS) ?? false,
        ]);
    }

    public function create(): Response
    {
        $user = request()->user();
        $isSuperAdmin = $user?->hasRole(AccessControl::ROLE_SUPER_ADMIN);

        return Inertia::render('Admin/Pages/Forms/Categories/Form', [
            'mode' => 'create',
            'category' => null,
            'units' => $isSuperAdmin ? $this->activeUnits() : [],
            'canAssignUnit' => $isSuperAdmin,
        ]);
    }

    public function edit(FormCategory $category): Response
    {
        $this->ensureSameCooperative($category);
        $user = request()->user();
        $isSuperAdmin = $user?->hasRole(AccessControl::ROLE_SUPER_ADMIN);

        return Inertia::render('Admin/Pages/Forms/Categories/Form', [
            'mode' => 'edit',
            'category' => $this->serializeCategory($category),
            'units' => $isSuperAdmin ? $this->activeUnits() : [],
            'canAssignUnit' => $isSuperAdmin,
        ]);
    }

    public function store(StoreFormCategoryRequest $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validated();

        if (! $user?->hasRole(AccessControl::ROLE_SUPER_ADMIN)) {
            unset($data['unit_id']);
        }

        $category = FormCategory::query()->create([
            ...$data,
            'cooperative_id' => $this->activeCooperative()?->id,
        ]);

        $this->auditLog->record('form_category.created', $category, newValues: $category->toArray());

        return redirect()
            ->route('admin.form-categories.edit', $category)
            ->with('status', 'Kategori borang berjaya dicipta.');
    }

    public function update(UpdateFormCategoryRequest $request, FormCategory $category): RedirectResponse
    {
        $this->ensureSameCooperative($category);
        $old = $category->toArray();
        $user = $request->user();
        $data = $request->validated();

        if (! $user?->hasRole(AccessControl::ROLE_SUPER_ADMIN)) {
            unset($data['unit_id']);
        }

        $category->update($data);

        $this->auditLog->record('form_category.updated', $category, $old, $category->fresh()->toArray());

        return back()->with('status', 'Kategori borang berjaya dikemas kini.');
    }

    public function toggle(FormCategory $category): RedirectResponse
    {
        $this->ensureSameCooperative($category);
        $old = $category->toArray();

        $category->update([
            'is_active' => ! $category->is_active,
        ]);

        $this->auditLog->record('form_category.toggled', $category, $old, $category->fresh()->toArray());

        return back()->with('status', 'Status kategori berjaya dikemas kini.');
    }

    public function destroy(FormCategory $category): RedirectResponse
    {
        $this->ensureSameCooperative($category);

        if ($category->forms()->exists()) {
            $category->update(['is_active' => false]);

            return back()->with('status', 'Kategori ini telah dinyahaktifkan kerana masih mempunyai borang.');
        }

        $this->auditLog->record('form_category.deleted', $category, $category->toArray());
        $category->delete();

        return redirect()->route('admin.form-categories.index')->with('status', 'Kategori borang berjaya dipadam.');
    }

    private function serializeCategory(FormCategory $category): array
    {
        return [
            'id' => $category->id,
            'name' => $category->name,
            'slug' => $category->slug,
            'description' => $category->description,
            'icon' => $category->icon,
            'unit_id' => $category->unit_id,
            'unit_name' => $category->unit?->name,
            'is_active' => $category->is_active,
        ];
    }

    private function activeUnits(): array
    {
        return Unit::query()
            ->where('cooperative_id', $this->activeCooperative()?->id)
            ->active()
            ->orderBy('name')
            ->get()
            ->map(fn (Unit $unit) => [
                'value' => $unit->id,
                'label' => $unit->name,
            ])
            ->all();
    }

}