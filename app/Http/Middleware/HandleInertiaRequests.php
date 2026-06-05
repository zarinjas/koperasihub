<?php

namespace App\Http\Middleware;

use App\Enums\AnsuranApplicationStatus;
use App\Enums\FinancingApplicationStatus;
use App\Enums\FormSubmissionStatus;
use App\Enums\MembershipApplicationStatus;
use App\Models\AnsuranApplication;
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
                'unread_count' => fn () => $user->unreadNotifications()->count(),
                'recent' => fn () => $user->unreadNotifications()
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
            'popup' => function () use ($request): ?array {
                $user = $request->user();
                if (! $user || $user->role !== 'member') {
                    return null;
                }

                if ($request->session()->get('popup_dismissed')) {
                    return null;
                }

                $cooperativeId = app(SettingsService::class)->activeCooperative()?->id;
                if (! $cooperativeId) {
                    return null;
                }

                $popup = \App\Models\Popup::query()
                    ->where('cooperative_id', $cooperativeId)
                    ->active()
                    ->latest()
                    ->first();

                if (! $popup) {
                    return null;
                }

                return [
                    'id' => $popup->id,
                    'title' => $popup->title,
                    'content' => $popup->content,
                    'image_url' => $popup->imageUrl(),
                    'button_text' => $popup->button_text,
                    'button_url' => $popup->button_url,
                ];
            },
        ];
    }

    private function adminNavigation(Request $request): array
    {
        $user = $request->user();
        $cooperativeId = app(SettingsService::class)->activeCooperative()?->id;

        $pendingMembership = 0;
        $pendingForms = 0;
        $pendingFinancing = 0;
        $pendingAnsuran = 0;
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

            if ($user->can(AccessControl::PERMISSION_VIEW_ANSURAN)) {
                $pendingAnsuran = AnsuranApplication::query()
                    ->forCooperative($cooperativeId)
                    ->whereIn('status', [
                        AnsuranApplicationStatus::PendingGuarantor->value,
                        AnsuranApplicationStatus::Pending->value,
                        AnsuranApplicationStatus::UnderReview->value,
                    ])
                    ->count();
            }
        }

        $items = [
            ['label' => 'Papan Pemuka', 'href' => route('admin.dashboard'), 'permission' => AccessControl::PERMISSION_VIEW_ADMIN_DASHBOARD, 'icon' => 'LayoutDashboard'],
            ['label' => 'Semakan', 'href' => route('admin.semakan.index'), 'permission' => AccessControl::PERMISSION_VIEW_FORM_SUBMISSIONS, 'icon' => 'ClipboardCheck', 'badge' => $pendingForms],
            [
                'label' => 'Pengurusan Kandungan',
                'href' => route('admin.pages.index'),
                'icon' => 'PanelsTopLeft',
                'active_patterns' => [
                    '/admin/pages', '/admin/pages/*',
                    '/admin/services', '/admin/services/*',
                    '/admin/announcements', '/admin/announcements/*',
                    '/admin/news', '/admin/news/*',
                    '/admin/documents', '/admin/documents/*',
                ],
                'children' => [
                    ['label' => 'Halaman CMS', 'href' => route('admin.pages.index'), 'permission' => AccessControl::PERMISSION_VIEW_PAGES],
                    ['label' => 'Perkhidmatan', 'href' => route('admin.services.index'), 'permission' => AccessControl::PERMISSION_VIEW_SERVICES],
                    ['label' => 'Pengumuman', 'href' => route('admin.announcements.index'), 'permission' => AccessControl::PERMISSION_VIEW_ANNOUNCEMENTS],
                    ['label' => 'Berita', 'href' => route('admin.news.index'), 'permission' => AccessControl::PERMISSION_VIEW_NEWS],
                    ['label' => 'Dokumen & Muat Turun', 'href' => route('admin.documents.index'), 'permission' => AccessControl::PERMISSION_VIEW_DOCUMENTS],
                ],
            ],
            [
                'label' => 'Media',
                'href' => route('admin.media.index'),
                'icon' => 'Image',
                'permission' => AccessControl::PERMISSION_VIEW_MEDIA,
                'active_patterns' => [
                    '/admin/media',
                    '/admin/posters', '/admin/posters/*',
                    '/admin/banners', '/admin/banners/*',
                    '/admin/popups', '/admin/popups/*',
                ],
                'children' => [
                    ['label' => 'Poster & Infografik', 'href' => route('admin.posters.index'), 'permission' => AccessControl::PERMISSION_VIEW_POSTERS],
                    ['label' => 'Banner Digital', 'href' => route('admin.banners.index'), 'permission' => AccessControl::PERMISSION_VIEW_BANNERS],
                    ['label' => 'Popup Ahli', 'href' => route('admin.popups.index'), 'permission' => AccessControl::PERMISSION_VIEW_POPUPS],
                ],
            ],
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
                'label' => 'Ansuran Mudah',
                'href' => route('admin.ansuran.products.index'),
                'icon' => 'ShoppingCart',
                'permission' => AccessControl::PERMISSION_VIEW_ANSURAN,
                'active_patterns' => [
                    '/admin/ansuran/categories', '/admin/ansuran/categories/*',
                    '/admin/ansuran/products', '/admin/ansuran/products/*',
                    '/admin/ansuran/tenures', '/admin/ansuran/tenures/*',
                    '/admin/ansuran/templates', '/admin/ansuran/templates/*',
                    '/admin/ansuran/applications', '/admin/ansuran/applications/*',
                ],
                'children' => [
                    ['label' => 'Kategori Produk', 'href' => route('admin.ansuran.categories.index'), 'permission' => AccessControl::PERMISSION_VIEW_ANSURAN],
                    ['label' => 'Produk Ansuran', 'href' => route('admin.ansuran.products.index'), 'permission' => AccessControl::PERMISSION_VIEW_ANSURAN],
                    ['label' => 'Tempoh Ansuran', 'href' => route('admin.ansuran.tenures.index'), 'permission' => AccessControl::PERMISSION_VIEW_ANSURAN],
                    ['label' => 'Template Perjanjian', 'href' => route('admin.ansuran.templates.index'), 'permission' => AccessControl::PERMISSION_VIEW_ANSURAN],
                    ['label' => 'Permohonan Ansuran', 'href' => route('admin.ansuran.applications.index'), 'permission' => AccessControl::PERMISSION_VIEW_ANSURAN, 'badge' => $pendingAnsuran],
                ],
            ],
            [
                'label' => 'Borang Online',
                'href' => route('admin.forms.index'),
                'icon' => 'ClipboardList',
                'roles' => AccessControl::adminRoles(),
                'active_patterns' => [
                    '/admin/forms', '/admin/forms/create',
                    '/admin/forms/*/edit', '/admin/forms/*/preview-pdf',
                    '/admin/forms/*/sections', '/admin/forms/*/sections/*',
                    '/admin/forms/*/fields', '/admin/forms/*/fields/*',
                    '/admin/forms/*/submissions', '/admin/forms/*/submissions/*',
                    '/admin/form-categories', '/admin/form-categories/*',
                    '/admin/form-submissions', '/admin/form-submissions/*',
                ],
                'children' => [
                    ['label' => 'Permohonan Borang', 'href' => route('admin.form-submissions.index'), 'permission' => AccessControl::PERMISSION_VIEW_FORM_SUBMISSIONS, 'badge' => $pendingForms],
                ],
            ],
            [
                'label' => 'Ahli',
                'href' => route('admin.members.index'),
                'icon' => 'Users',
                'permission' => AccessControl::PERMISSION_VIEW_MEMBERS,
                'active_patterns' => [
                    '/admin/members', '/admin/members/*',
                    '/admin/membership-applications', '/admin/membership-applications/*',
                ],
                'children' => [
                    ['label' => 'Senarai Ahli', 'href' => route('admin.members.index'), 'permission' => AccessControl::PERMISSION_VIEW_MEMBERS],
                    ['label' => 'Permohonan Keahlian', 'href' => route('admin.membership-applications.index'), 'permission' => AccessControl::PERMISSION_VIEW_MEMBERSHIP_APPLICATIONS, 'badge' => $pendingMembership],
                ],
            ],
            ['label' => 'Rujukan & Komisyen', 'href' => route('admin.referral-commissions.index'), 'permission' => AccessControl::PERMISSION_VIEW_REFERRAL_COMMISSIONS, 'icon' => 'Handshake'],
            ['label' => 'Aduan', 'href' => route('admin.complaints.index'), 'permission' => AccessControl::PERMISSION_VIEW_COMPLAINTS, 'icon' => 'MessagesSquare'],
            ['label' => 'Caruman Ahli', 'href' => route('admin.caruman.index'), 'permission' => AccessControl::PERMISSION_VIEW_CARUMAN, 'icon' => 'Wallet'],
            [
                'label' => 'Pentadbiran',
                'href' => route('admin.staff.index'),
                'icon' => 'UserCog',
                'active_patterns' => [
                    '/admin/units', '/admin/units/*',
                    '/admin/staff', '/admin/staff/*',
                    '/admin/roles', '/admin/roles/*',
                    '/admin/settings', '/admin/settings/*',
                    '/admin/email-templates', '/admin/email-templates/*',
                    '/admin/audit-logs', '/admin/audit-logs/*',
                    '/admin/reports', '/admin/reports/*',
                    '/admin/ai-knowledge',
                ],
                'children' => [
                    ['label' => 'Unit', 'href' => route('admin.units.index'), 'permission' => AccessControl::PERMISSION_MANAGE_UNITS],
                    ['label' => 'Staff & Akses', 'href' => route('admin.staff.index'), 'permission' => AccessControl::PERMISSION_MANAGE_STAFF],
                    ['label' => 'Peranan', 'href' => route('admin.roles.index'), 'permission' => AccessControl::PERMISSION_VIEW_ROLES],
                    ['label' => 'Tetapan', 'href' => route('admin.settings.index'), 'permission' => AccessControl::PERMISSION_VIEW_SETTINGS],
                    ['label' => 'Templat E-mel', 'href' => route('admin.email-templates.index'), 'permission' => AccessControl::PERMISSION_VIEW_SETTINGS],
                    ['label' => 'Log Audit', 'href' => route('admin.audit-logs.index'), 'permission' => AccessControl::PERMISSION_VIEW_AUDIT_LOGS],
                    ['label' => 'Laporan', 'href' => route('admin.reports.index'), 'permission' => AccessControl::PERMISSION_VIEW_REPORTS],
                    ['label' => 'Pengetahuan AI', 'href' => route('admin.ai-knowledge.index'), 'permission' => AccessControl::PERMISSION_VIEW_AI_KNOWLEDGE],
                ],
            ],
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
            [
                'label' => 'Ansuran Mudah',
                'icon' => 'ShoppingCart',
                'children' => [
                    ['label' => 'Katalog Produk', 'href' => route('member.ansuran.index'), 'permission' => AccessControl::PERMISSION_MEMBER_ACCESS],
                    ['label' => 'Permohonan Saya', 'href' => route('member.ansuran.applications.index'), 'permission' => AccessControl::PERMISSION_MEMBER_ACCESS],
                    ['label' => 'Permintaan Penjamin', 'href' => route('member.ansuran.guarantor-requests.index'), 'permission' => AccessControl::PERMISSION_MEMBER_ACCESS],
                ],
            ],
            ['label' => 'Program', 'href' => route('member.programs.index'), 'permission' => AccessControl::PERMISSION_MEMBER_ACCESS, 'icon' => 'CalendarDays'],
            ['label' => 'Kehadiran Saya', 'href' => route('member.attendance.index'), 'permission' => AccessControl::PERMISSION_MEMBER_ACCESS, 'icon' => 'CalendarCheck'],
            [
                'label' => 'Borang Online',
                'icon' => 'FileText',
                'children' => [
                    ['label' => 'Senarai Borang', 'href' => route('member.forms.index'), 'permission' => AccessControl::PERMISSION_MEMBER_ACCESS],
                    ['label' => 'Hantaran Saya', 'href' => route('member.applications.index'), 'permission' => AccessControl::PERMISSION_MEMBER_ACCESS],
                ],
            ],
            ['label' => 'Pengumuman', 'href' => route('member.announcements.index'), 'permission' => AccessControl::PERMISSION_MEMBER_ACCESS, 'icon' => 'Megaphone'],
            ['label' => 'Aduan', 'href' => route('member.complaints.index'), 'permission' => AccessControl::PERMISSION_MEMBER_ACCESS, 'icon' => 'MessagesSquare'],
            ['label' => 'Rujukan Saya', 'href' => route('member.referrals.index'), 'permission' => AccessControl::PERMISSION_MEMBER_ACCESS, 'icon' => 'Handshake'],
            ['label' => 'Caruman Saya', 'href' => route('member.caruman.index'), 'permission' => AccessControl::PERMISSION_MEMBER_ACCESS, 'icon' => 'Wallet'],
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

            if ($hasRoleAccess || $hasPermission) {
                unset($item['children']);

                return $item;
            }

            return null;
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