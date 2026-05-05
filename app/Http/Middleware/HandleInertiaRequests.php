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
                    'staff_id' => $user->staff_id,
                    'unit_name' => fn () => $user->unit?->name,
                    'position_title' => $user->position_title,
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
            ['label' => 'Berita', 'href' => route('admin.news.index'), 'permission' => AccessControl::PERMISSION_VIEW_NEWS, 'icon' => 'Newspaper'],
            ['label' => 'Dokumen & Muat Turun', 'href' => route('admin.documents.index'), 'permission' => AccessControl::PERMISSION_VIEW_DOCUMENTS, 'icon' => 'Files'],
            [
                'label' => 'Borang Online',
                'href' => route('admin.forms.index'),
                'icon' => 'ClipboardList',
                'roles' => AccessControl::adminRoles(),
                'active_patterns' => [
                    '/admin/forms',
                    '/admin/forms/create',
                    '/admin/forms/*/edit',
                    '/admin/forms/*/preview-pdf',
                    '/admin/forms/*/sections',
                    '/admin/forms/*/sections/*',
                    '/admin/forms/*/fields',
                    '/admin/forms/*/fields/*',
                    '/admin/forms/*/submissions',
                    '/admin/forms/*/submissions/*',
                    '/admin/form-categories',
                    '/admin/form-categories/*',
                ],
            ],
            ['label' => 'Ahli', 'href' => route('admin.members.index'), 'permission' => AccessControl::PERMISSION_VIEW_MEMBERS, 'icon' => 'Users'],
            ['label' => 'Permohonan Borang', 'href' => route('admin.form-submissions.index'), 'permission' => AccessControl::PERMISSION_VIEW_FORM_SUBMISSIONS, 'icon' => 'FileCheck'],
            ['label' => 'Permohonan Keahlian', 'href' => route('admin.membership-applications.index'), 'permission' => AccessControl::PERMISSION_VIEW_MEMBERSHIP_APPLICATIONS, 'icon' => 'ClipboardCheck'],
            ['label' => 'Aduan', 'href' => route('admin.complaints.index'), 'permission' => AccessControl::PERMISSION_VIEW_COMPLAINTS, 'icon' => 'MessagesSquare'],
            ['label' => 'Unit', 'href' => route('admin.units.index'), 'permission' => AccessControl::PERMISSION_MANAGE_UNITS, 'icon' => 'Building2'],
            ['label' => 'Staff & Akses', 'href' => route('admin.staff.index'), 'permission' => AccessControl::PERMISSION_MANAGE_STAFF, 'icon' => 'UserCog'],
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
            ['label' => 'Kad Digital', 'href' => route('member.card'), 'permission' => AccessControl::PERMISSION_MEMBER_ACCESS, 'icon' => 'CreditCard'],
            ['label' => 'Profil Saya', 'href' => route('member.profile'), 'permission' => AccessControl::PERMISSION_MEMBER_ACCESS, 'icon' => 'UserRound'],
            ['label' => 'Permohonan', 'href' => route('member.applications.index'), 'permission' => AccessControl::PERMISSION_MEMBER_ACCESS, 'icon' => 'FileCheck'],
            ['label' => 'Pengumuman', 'href' => route('member.announcements.index'), 'permission' => AccessControl::PERMISSION_MEMBER_ACCESS, 'icon' => 'Megaphone'],
            ['label' => 'Aduan', 'href' => route('member.complaints.index'), 'permission' => AccessControl::PERMISSION_MEMBER_ACCESS, 'icon' => 'MessagesSquare'],
            ['label' => 'Dokumen Saya', 'href' => route('member.documents.index'), 'permission' => AccessControl::PERMISSION_MEMBER_ACCESS, 'icon' => 'Files'],
        ];

        return $this->filterNavigation($request, $items);
    }

    private function filterNavigation(Request $request, array $items): array
    {
        $user = $request->user();

        if (! $user) {
            return [];
        }

        return array_values(array_filter(array_map(function (array $item) use ($request, $user): ?array {
            $children = [];

            if (isset($item['children']) && is_array($item['children'])) {
                $children = $this->filterNavigation($request, $item['children']);
            }

            $hasRoleAccess = isset($item['roles']) && $this->userMatchesNavigationRoles($user, $item['roles']);
            $hasPermission = isset($item['permission']) ? $user->can($item['permission']) : false;

            if ($children !== []) {
                $item['children'] = $children;

                return $item;
            }

            return ($hasRoleAccess || $hasPermission) ? $item : null;
        }, $items)));
    }

    private function userMatchesNavigationRoles($user, array $roles): bool
    {
        $allowedRoles = array_values(array_filter($roles, fn ($role): bool => is_string($role) && $role !== ''));

        if ($allowedRoles === []) {
            return false;
        }

        return in_array($user->role, $allowedRoles, true)
            || in_array($user->user_type, $allowedRoles, true);
    }
}
