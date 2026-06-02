<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\InteractsWithActiveCooperative;
use App\Http\Controllers\Controller;
use App\Models\Unit;
use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class StaffController extends Controller
{
    use InteractsWithActiveCooperative;

    public function __construct(
        private readonly AuditLogService $auditLog,
    ) {}

    public function index(Request $request): Response
    {
        $cooperative = $this->activeCooperative();
        $search = trim((string) $request->string('search'));
        $role = $request->string('role')->toString();
        $unit = $request->integer('unit_id') ?: null;

        $staff = User::query()
            ->where('cooperative_id', $cooperative?->id)
            ->whereIn('role', ['super_admin', 'admin'])
            ->with('unit')
            ->when($search !== '', fn ($q) => $q->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('staff_id', 'like', "%{$search}%");
            }))
            ->when(in_array($role, ['super_admin', 'admin'], true), fn ($q) => $q->where('role', $role))
            ->when($unit, fn ($q) => $q->where('unit_id', $unit))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'staff_id' => $user->staff_id,
                'unit_name' => $user->unit?->name,
                'position_title' => $user->position_title,
                'role' => $user->role,
                'status' => $user->status ?? 'active',
                'last_login_at' => $user->last_login_at?->format('d/m/Y H:i'),
            ]);

        $units = Unit::query()
            ->where('cooperative_id', $cooperative?->id)
            ->active()
            ->latest()
            ->get()
            ->map(fn (Unit $u) => ['value' => $u->id, 'label' => $u->name])
            ->all();

        return Inertia::render('Admin/Pages/Staff/Index', [
            'filters' => ['search' => $search, 'role' => $role, 'unit_id' => $unit],
            'staff' => $staff,
            'unitOptions' => $units,
        ]);
    }

    public function create(Request $request): Response
    {
        $cooperative = $this->activeCooperative();

        $units = Unit::query()
            ->where('cooperative_id', $cooperative?->id)
            ->active()
            ->latest()
            ->get()
            ->map(fn (Unit $u) => ['value' => $u->id, 'label' => $u->name])
            ->all();

        return Inertia::render('Admin/Pages/Staff/Form', [
            'staff' => null,
            'unitOptions' => $units,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $cooperative = $this->activeCooperative();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'staff_id' => ['required', 'string', 'max:50', Rule::unique('users', 'staff_id')],
            'unit_id' => ['nullable', 'integer', Rule::exists('units', 'id')->where('cooperative_id', $cooperative?->id)],
            'position_title' => ['nullable', 'string', 'max:255'],
            'role' => ['required', Rule::in(['super_admin', 'admin'])],
            'password' => ['required', 'string', 'min:6'],
        ]);

        $user = User::query()->create([
            'cooperative_id' => $cooperative?->id,
            'name' => $validated['name'],
            'email' => $validated['email'],
            'staff_id' => $validated['staff_id'],
            'unit_id' => $validated['unit_id'] ?? null,
            'position_title' => $validated['position_title'] ?? null,
            'role' => $validated['role'],
            'user_type' => $validated['role'],
            'status' => 'active',
            'password' => Hash::make($validated['password']),
        ]);

        $user->syncRoles([$validated['role']]);

        $this->auditLog->record('staff.created', $user, newValues: $user->toArray());

        return redirect()->route('admin.staff.index')->with('status', 'Akaun staff berjaya ditambah.');
    }

    public function edit(User $user): Response
    {
        $cooperative = $this->activeCooperative();
        abort_unless($user->cooperative_id === $cooperative?->id, 404);

        $units = Unit::query()
            ->where('cooperative_id', $cooperative?->id)
            ->active()
            ->latest()
            ->get()
            ->map(fn (Unit $u) => ['value' => $u->id, 'label' => $u->name])
            ->all();

        return Inertia::render('Admin/Pages/Staff/Form', [
            'staff' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'staff_id' => $user->staff_id,
                'unit_id' => $user->unit_id,
                'position_title' => $user->position_title,
                'role' => $user->role,
                'status' => $user->status ?? 'active',
                'phone' => $user->phone,
            ],
            'unitOptions' => $units,
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $cooperative = $this->activeCooperative();
        abort_unless($user->cooperative_id === $cooperative?->id, 404);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'staff_id' => ['required', 'string', 'max:50', Rule::unique('users', 'staff_id')->ignore($user->id)],
            'unit_id' => ['nullable', 'integer', Rule::exists('units', 'id')->where('cooperative_id', $cooperative?->id)],
            'position_title' => ['nullable', 'string', 'max:255'],
            'role' => ['required', Rule::in(['super_admin', 'admin'])],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'phone' => ['nullable', 'string', 'max:20'],
            'password' => ['nullable', 'string', 'min:6'],
        ]);

        $old = $user->toArray();

        $updateData = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'staff_id' => $validated['staff_id'],
            'unit_id' => $validated['unit_id'] ?? null,
            'position_title' => $validated['position_title'] ?? null,
            'role' => $validated['role'],
            'user_type' => $validated['role'],
            'status' => $validated['status'],
            'phone' => $validated['phone'] ?? null,
        ];

        if (! empty($validated['password'])) {
            $updateData['password'] = Hash::make($validated['password']);
        }

        $user->update($updateData);

        if ($user->wasChanged('role')) {
            $user->syncRoles([$validated['role']]);
        }

        $this->auditLog->record('staff.updated', $user, oldValues: $old, newValues: $user->fresh()->toArray());

        return redirect()->route('admin.staff.index')->with('status', 'Akaun staff berjaya dikemas kini.');
    }
}