<?php

namespace App\Http\Middleware;

use App\Services\Settings\SettingsService;
use App\Support\AccessControl;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'roles' => fn () => $user->getRoleNames()->values(),
                    'permissions' => fn () => $user->getAllPermissions()->pluck('name')->values(),
                ] : null,
            ],
            'navigation' => [
                'admin' => fn () => $this->adminNavigation($request),
                'member' => fn () => $this->memberNavigation($request),
            ],
            'flash' => [
                'status' => fn () => $request->session()->get('status'),
            ],
            'appSettings' => fn () => app(SettingsService::class)->shared(),
        ];
    }

    private function adminNavigation(Request $request): array
    {
        $items = [
            ['label' => 'Papan Pemuka', 'href' => route('admin.dashboard'), 'permission' => AccessControl::PERMISSION_VIEW_ADMIN_DASHBOARD, 'icon' => 'LayoutDashboard'],
            ['label' => 'Halaman CMS', 'href' => route('admin.pages.index'), 'permission' => AccessControl::PERMISSION_VIEW_PAGES, 'icon' => 'PanelsTopLeft'],
            ['label' => 'Media', 'href' => route('admin.media.index'), 'permission' => AccessControl::PERMISSION_VIEW_MEDIA, 'icon' => 'Image'],
            ['label' => 'Perkhidmatan', 'href' => route('admin.services.index'), 'permission' => AccessControl::PERMISSION_VIEW_SERVICES, 'icon' => 'BriefcaseBusiness'],
            ['label' => 'Pengumuman', 'href' => route('admin.announcements.index'), 'permission' => AccessControl::PERMISSION_VIEW_ANNOUNCEMENTS, 'icon' => 'Megaphone'],
            ['label' => 'Dokumen', 'href' => route('admin.documents.index'), 'permission' => AccessControl::PERMISSION_VIEW_DOCUMENTS, 'icon' => 'Files'],
            ['label' => 'Ahli', 'href' => route('admin.members.index'), 'permission' => AccessControl::PERMISSION_VIEW_MEMBERS, 'icon' => 'Users'],
            ['label' => 'Permohonan', 'href' => route('admin.membership-applications.index'), 'permission' => AccessControl::PERMISSION_VIEW_MEMBERSHIP_APPLICATIONS, 'icon' => 'ClipboardCheck'],
            ['label' => 'Aduan', 'href' => route('admin.complaints.index'), 'permission' => AccessControl::PERMISSION_VIEW_COMPLAINTS, 'icon' => 'MessagesSquare'],
            ['label' => 'Pengguna', 'href' => route('admin.users.index'), 'permission' => AccessControl::PERMISSION_VIEW_USERS, 'icon' => 'UserCog'],
            ['label' => 'Peranan', 'href' => route('admin.roles.index'), 'permission' => AccessControl::PERMISSION_VIEW_ROLES, 'icon' => 'ShieldCheck'],
            ['label' => 'Tetapan', 'href' => route('admin.settings.index'), 'permission' => AccessControl::PERMISSION_VIEW_SETTINGS, 'icon' => 'Settings'],
            ['label' => 'Log Audit', 'href' => route('admin.audit-logs.index'), 'permission' => AccessControl::PERMISSION_VIEW_AUDIT_LOGS, 'icon' => 'History'],
            ['label' => 'Laporan', 'href' => route('admin.reports.index'), 'permission' => AccessControl::PERMISSION_VIEW_REPORTS, 'icon' => 'ChartNoAxesColumnIncreasing'],
        ];

        return $this->filterNavigation($request, $items);
    }

    private function memberNavigation(Request $request): array
    {
        $items = [
            ['label' => 'Papan Pemuka', 'href' => route('member.dashboard'), 'permission' => AccessControl::PERMISSION_MEMBER_ACCESS, 'icon' => 'Home'],
            ['label' => 'Profil Saya', 'href' => route('member.profile'), 'permission' => AccessControl::PERMISSION_MEMBER_ACCESS, 'icon' => 'UserRound'],
            ['label' => 'Dokumen', 'href' => route('member.documents.index'), 'permission' => AccessControl::PERMISSION_MEMBER_ACCESS, 'icon' => 'FileText'],
            ['label' => 'Permohonan', 'href' => route('member.applications.index'), 'permission' => AccessControl::PERMISSION_MEMBER_ACCESS, 'icon' => 'ClipboardList'],
            ['label' => 'Aduan', 'href' => route('member.complaints.index'), 'permission' => AccessControl::PERMISSION_MEMBER_ACCESS, 'icon' => 'MessagesSquare'],
        ];

        return $this->filterNavigation($request, $items);
    }

    private function filterNavigation(Request $request, array $items): array
    {
        $user = $request->user();

        if (! $user) {
            return [];
        }

        return array_values(array_filter(
            $items,
            fn (array $item): bool => $user->can($item['permission'])
        ));
    }
}
