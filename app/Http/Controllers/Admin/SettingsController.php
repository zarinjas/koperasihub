<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateSettingsRequest;
use App\Services\Settings\SettingsService;
use App\Support\AccessControl;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    public function __construct(private readonly SettingsService $settings) {}

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
        ]);
    }

    public function update(UpdateSettingsRequest $request): RedirectResponse
    {
        $cooperative = $this->settings->activeCooperative();

        abort_unless($cooperative, 404);

        $this->settings->update($cooperative, $request->validated());

        return back()->with('status', 'Tetapan koperasi berjaya dikemas kini.');
    }
}
