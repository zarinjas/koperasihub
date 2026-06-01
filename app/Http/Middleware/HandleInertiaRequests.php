<?php

namespace App\Http\Middleware;

use App\Enums\FinancingApplicationStatus;
use App\Enums\FormSubmissionStatus;
use App\Enums\MembershipApplicationStatus;
use App\Models\FinancingApplication;
use App\Models\FormSubmission;
use App\Models\MembershipApplication;
use App\Models\Program;
use App\Services\Files\MemberPhotoStorageService;
use App\Services\Settings\SettingsService;
use App\Support\AccessControl;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

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
                    'profile_photo_url' => fn () => $this->memberProfilePhotoUrl($user),
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
            'notifications' => $user ? [
                'unread_count' => fn () => $user->unreadNotifications()
                    ->where('type', 'App\Notifications\AnnouncementNotification')
                    ->count(),
                'recent' => fn () => $user->unreadNotifications()
                    ->where('type', 'App\Notifications\AnnouncementNotification')
                    ->take(5)
                    ->get()
                    ->map(fn ($n) => [
                        'id' => $n->id,
                        'title' => $n->data['title'] ?? '',
                        'summary' => $n->data['summary'] ?? '',
                        'url' => $n->data['url'] ?? '#',
                        'created_at' => $n->created_at->diffForHumans(),
                    ]),
            ] : null,
            'flash' => [
                'status' => fn () => $request->session()->get('status'),
            ],
            'appSettings' => fn () => app(SettingsService::class)->shared(),
        ];
    }

    private function adminNavigation(Request $request): array
    {
        $user = $request->user();
        $cooperativeId = app(SettingsService::class)->activeCooperative()?->id;

        $pendingMembership = 0;
        $pendingForms = 0;
        $pendingFinancing = 0;
        $upcomingPrograms = 0;

        if ($user && $cooperativeId) {
            if ($user->can(AccessControl::PERMISSION_VIEW_MEMBERSHIP_APPLICATIONS)) {
                $pendingMembership = MembershipApplication::query()
                    ->forCooperative($cooperativeId)
                    ->whereIn('status', [
                        MembershipApplicationStatus::Pending->value,
                        MembershipApplicationStatus::UnderReview->value,
                    ])
                    ->count();
            }

            if ($user->can(AccessControl::PERMISSION_VIEW_FORM_SUBMISSIONS)) {
                $pendingForms = FormSubmission::query()
                    ->where('cooperative_id', $cooperativeId)
                    ->whereIn('status', [
                        FormSubmissionStatus::PendingStampUpload->value,
                        FormSubmissionStatus::Submitted->value,
                        FormSubmissionStatus::IncompleteDocuments->value,
                    ])
                    ->count();
            }

            if ($user->can(AccessControl::PERMISSION_VIEW_PROGRAMS)) {
                $upcomingPrograms = Program::query()
                    ->forCooperative($cooperativeId)
                    ->published()
                    ->upcoming()
                    ->count();
            }

            if ($user->can(AccessControl::PERMISSION_VIEW_FINANCING)) {
                $pendingFinancing = FinancingApplication::query()
                    ->where('cooperative_id', $cooperativeId)
                    ->whereIn('status', array_map(fn ($s) => $s->value, FinancingApplicationStatus::active()))
                    ->count();
            }
        }

        $semakanBadge = $pendingMembership + $pendingForms + $pendingFinancing;

        $items = [
            ['label' => 'Papan Pemuka', 'href' => route('admin.dashboard'), 'permission' => AccessControl::PERMISSION_VIEW_ADMIN_DASHBOARD, 'icon' => 'LayoutDashboard'],
            ['label' => 'Semakan', 'href' => route('admin.semakan.index'), 'roles' => AccessControl::adminRoles(), 'icon' => 'Inbox', 'badge' => $semakanBadge],
            ['label' => 'Halaman CMS', 'href' => route('admin.pages.index'), 'permission' => AccessControl::PERMISSION_VIEW_PAGES, 'icon' => 'PanelsTopLeft'],
            ['label' => 'Media', 'href' => route('admin.media.index'), 'permission' => AccessControl::PERMISSION_VIEW_MEDIA, 'icon' => 'Image'],
            ['label' => 'Perkhidmatan', 'href' => route('admin.services.index'), 'permission' => AccessControl::PERMISSION_VIEW_SERVICES, 'icon' => 'BriefcaseBusiness'],
            ['label' => 'Pengumuman', 'href' => route('admin.announcements.index'), 'permission' => AccessControl::PERMISSION_VIEW_ANNOUNCEMENTS, 'icon' => 'Megaphone'],
            ['label' => 'Berita', 'href' => route('admin.news.index'), 'permission' => AccessControl::PERMISSION_VIEW_NEWS, 'icon' => 'Newspaper'],
            ['label' => 'Dokumen & Muat Turun', 'href' => route('admin.documents.index'), 'permission' => AccessControl::PERMISSION_VIEW_DOCUMENTS, 'icon' => 'Files'],
            ['label' => 'Poster & Infografik', 'href' => route('admin.posters.index'), 'permission' => AccessControl::PERMISSION_VIEW_POSTERS, 'icon' => 'ImagePlay'],
            [
                'label' => 'Program & Kehadiran',
                'href' => route('admin.programs.index'),
                'icon' => 'CalendarDays',
                'active_patterns' => [
                    '/admin/programs',
                    '/admin/programs/create',
                    '/admin/programs/*/edit',
                    '/admin/programs/*/attendance',
                    '/admin/programs/*/event-qr',
                ],
                'children' => [
                    ['label' => 'Senarai Program', 'href' => route('admin.programs.index'), 'permission' => AccessControl::PERMISSION_VIEW_PROGRAMS],
                    ['label' => 'Tambah Program', 'href' => route('admin.programs.create'), 'permission' => AccessControl::PERMISSION_CREATE_PROGRAMS],
                ],
            ],
            [
                'label' => 'Pembiayaan',
                'href' => route('admin.financing.applications.index'),
                'icon' => 'HandCoins',
                'active_patterns' => [
                    '/admin/financing/categories',
                    '/admin/financing/categories/*',
                    '/admin/financing/products',
                    '/admin/financing/products/*',
                    '/admin/financing/applications',
                    '/admin/financing/applications/*',
                ],
                'children' => [
                    ['label' => 'Kategori Pembiayaan', 'href' => route('admin.financing.categories.index'), 'permission' => AccessControl::PERMISSION_VIEW_FINANCING],
                    ['label' => 'Produk Pembiayaan', 'href' => route('admin.financing.products.index'), 'permission' => AccessControl::PERMISSION_VIEW_FINANCING],
                    ['label' => 'Permohonan Pembiayaan', 'href' => route('admin.financing.applications.index'), 'permission' => AccessControl::PERMISSION_VIEW_FINANCING, 'badge' => $pendingFinancing],
                ],
            ],
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
            ['label' => 'Permohonan Borang', 'href' => route('admin.form-submissions.index'), 'permission' => AccessControl::PERMISSION_VIEW_FORM_SUBMISSIONS, 'icon' => 'FileCheck', 'badge' => $pendingForms],
            ['label' => 'Permohonan Keahlian', 'href' => route('admin.membership-applications.index'), 'permission' => AccessControl::PERMISSION_VIEW_MEMBERSHIP_APPLICATIONS, 'icon' => 'ClipboardCheck', 'badge' => $pendingMembership],
            ['label' => 'Aduan', 'href' => route('admin.complaints.index'), 'permission' => AccessControl::PERMISSION_VIEW_COMPLAINTS, 'icon' => 'MessagesSquare'],
            ['label' => 'Caruman Ahli', 'href' => route('admin.caruman.index'), 'permission' => AccessControl::PERMISSION_VIEW_CARUMAN, 'icon' => 'PiggyBank'],
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
            ['label' => 'Pembiayaan', 'href' => route('member.financing.index'), 'permission' => AccessControl::PERMISSION_MEMBER_ACCESS, 'icon' => 'HandCoins'],
            ['label' => 'Kalkulator Pembiayaan', 'href' => route('member.financing.calculator'), 'permission' => AccessControl::PERMISSION_MEMBER_ACCESS, 'icon' => 'Calculator'],
            ['label' => 'Program', 'href' => route('member.programs.index'), 'permission' => AccessControl::PERMISSION_MEMBER_ACCESS, 'icon' => 'CalendarDays'],
            ['label' => 'Kehadiran Saya', 'href' => route('member.attendance.index'), 'permission' => AccessControl::PERMISSION_MEMBER_ACCESS, 'icon' => 'CalendarCheck'],
            ['label' => 'Permohonan', 'href' => route('member.applications.index'), 'permission' => AccessControl::PERMISSION_MEMBER_ACCESS, 'icon' => 'FileCheck'],
            ['label' => 'Pengumuman', 'href' => route('member.announcements.index'), 'permission' => AccessControl::PERMISSION_MEMBER_ACCESS, 'icon' => 'Megaphone'],
            ['label' => 'Aduan', 'href' => route('member.complaints.index'), 'permission' => AccessControl::PERMISSION_MEMBER_ACCESS, 'icon' => 'MessagesSquare'],
            ['label' => 'Caruman Saya', 'href' => route('member.caruman.index'), 'permission' => AccessControl::PERMISSION_MEMBER_ACCESS, 'icon' => 'PiggyBank'],
            ['label' => 'Dokumen Saya', 'href' => route('member.documents.index'), 'permission' => AccessControl::PERMISSION_MEMBER_ACCESS, 'icon' => 'Files'],
            ['label' => 'Galeri Poster', 'href' => route('member.posters.index'), 'permission' => AccessControl::PERMISSION_MEMBER_ACCESS, 'icon' => 'ImagePlay'],
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

    private function memberProfilePhotoUrl($user): ?string
    {
        $member = $user->member;

        if (! $member?->profile_photo_path) {
            return null;
        }

        return app(MemberPhotoStorageService::class)->url($member->profile_photo_path);
    }
}
