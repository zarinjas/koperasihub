<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\InteractsWithActiveCooperative;
use App\Http\Controllers\Controller;
use App\Models\Unit;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class UnitController extends Controller
{
    use InteractsWithActiveCooperative;

    public function __construct(
        private readonly AuditLogService $auditLog,
    ) {}

    public function index(Request $request): Response
    {
        $cooperative = $this->activeCooperative();
        $search = trim((string) $request->string('search'));

        $units = Unit::query()
            ->where('cooperative_id', $cooperative?->id)
            ->when($search !== '', fn ($q) => $q->where('name', 'like', "%{$search}%"))
            ->latest()
            ->withCount('users')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Admin/Pages/Units/Index', [
            'filters' => ['search' => $search],
            'units' => $units,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Pages/Units/Form', [
            'unit' => null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $cooperative = $this->activeCooperative();
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('units', 'slug')->where('cooperative_id', $cooperative?->id)],
            'description' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['required', 'boolean'],
        ]);

        $unit = Unit::query()->create([
            'cooperative_id' => $cooperative?->id,
            'name' => $validated['name'],
            'slug' => data_get($validated, 'slug') ?: str($validated['name'])->slug()->value(),
            'description' => $validated['description'] ?? null,
            'is_active' => (bool) $validated['is_active'],
            'created_by' => $request->user()?->id,
            'updated_by' => $request->user()?->id,
        ]);

        $this->auditLog->record('unit.created', $unit, newValues: $unit->toArray());

        return redirect()->route('admin.units.index')->with('status', 'Unit berjaya ditambah.');
    }

    public function edit(Unit $unit): Response
    {
        $this->ensureSameCooperative($unit);

        return Inertia::render('Admin/Pages/Units/Form', [
            'unit' => [
                'id' => $unit->id,
                'name' => $unit->name,
                'slug' => $unit->slug,
                'description' => $unit->description,
                'is_active' => $unit->is_active,
            ],
        ]);
    }

    public function update(Request $request, Unit $unit): RedirectResponse
    {
        $this->ensureSameCooperative($unit);
        $cooperative = $this->activeCooperative();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('units', 'slug')->ignore($unit->id)->where('cooperative_id', $cooperative?->id)],
            'description' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['required', 'boolean'],
        ]);

        $old = $unit->toArray();

        $unit->update([
            'name' => $validated['name'],
            'slug' => data_get($validated, 'slug') ?: str($validated['name'])->slug()->value(),
            'description' => $validated['description'] ?? null,
            'is_active' => (bool) $validated['is_active'],
            'updated_by' => $request->user()?->id,
        ]);

        $this->auditLog->record('unit.updated', $unit, oldValues: $old, newValues: $unit->fresh()->toArray());

        return redirect()->route('admin.units.index')->with('status', 'Unit berjaya dikemas kini.');
    }

    public function destroy(Unit $unit): RedirectResponse
    {
        $this->ensureSameCooperative($unit);

        $this->auditLog->record('unit.deleted', $unit, oldValues: $unit->toArray());
        $unit->delete();

        return back()->with('status', 'Unit berjaya dipadam.');
    }
}