<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UploadFaviconRequest;
use App\Http\Requests\Admin\UploadLogoRequest;
use App\Services\AuditLogService;
use App\Services\Files\BrandingStorageService;
use App\Services\Settings\SettingsService;
use Illuminate\Http\RedirectResponse;

class BrandingController extends Controller
{
    public function __construct(
        private readonly SettingsService $settings,
        private readonly BrandingStorageService $branding,
        private readonly AuditLogService $auditLogs,
    ) {}

    public function uploadLogo(UploadLogoRequest $request): RedirectResponse
    {
        $cooperative = $this->settings->activeCooperative();

        abort_unless($cooperative, 404);

        $path = $this->branding->storeLogo($request->file('logo'), $cooperative);

        $this->settings->updateLogoPath($cooperative, $path);

        $this->auditLogs->record('settings_logo_updated', $cooperative, [], ['logo_path' => $path]);

        return back()->with('status', 'Logo koperasi berjaya dikemas kini.');
    }

    public function uploadFavicon(UploadFaviconRequest $request): RedirectResponse
    {
        $cooperative = $this->settings->activeCooperative();

        abort_unless($cooperative, 404);

        $path = $this->branding->storeFavicon($request->file('favicon'), $cooperative);

        $this->settings->updateFaviconPath($cooperative, $path);

        $this->auditLogs->record('settings_favicon_updated', $cooperative, [], ['favicon_path' => $path]);

        return back()->with('status', 'Favicon koperasi berjaya dikemas kini.');
    }
}
