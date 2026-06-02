<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateSettingsRequest;
use App\Models\Unit;
use App\Services\AuditLogService;
use App\Services\Settings\SettingsService;
use App\Support\AccessControl;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    public function __construct(
        private readonly SettingsService $settings,
        private readonly AuditLogService $auditLogs,
    ) {}

    public function edit(): Response
    {
        $cooperative = $this->settings->activeCooperative();

        abort_unless($cooperative, 404);

        return Inertia::render('Admin/Pages/Settings/Edit', [
            'cooperative' => [
                'id' => $cooperative->id,
                'name' => $cooperative->name,
                'slug' => $cooperative->slug,
                'status' => $cooperative->status,
            ],
            'settings' => $this->settings->grouped($cooperative->id),
            'canEdit' => request()->user()?->can(AccessControl::PERMISSION_EDIT_SETTINGS) ?? false,
            'units' => Unit::query()
                ->where('cooperative_id', $cooperative->id)
                ->active()
                ->latest()
                ->get()
                ->map(fn (Unit $unit) => [
                    'id' => $unit->id,
                    'name' => $unit->name,
                ])
                ->values(),
        ]);
    }

    public function update(UpdateSettingsRequest $request): RedirectResponse
    {
        $cooperative = $this->settings->activeCooperative();

        abort_unless($cooperative, 404);

        $oldValues = $this->settings->grouped($cooperative->id);
        $this->settings->update($cooperative, $request->validated());
        $this->auditLogs->record(
            'settings_updated',
            $cooperative,
            $oldValues,
            $this->settings->grouped($cooperative->id),
        );

        return back()->with('status', 'Tetapan koperasi berjaya dikemas kini.');
    }
}