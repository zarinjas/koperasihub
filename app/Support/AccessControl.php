<?php

namespace App\Support;

class AccessControl
{
    public const ROLE_SUPER_ADMIN = 'super_admin';

    public const ROLE_ADMIN = 'admin';

    public const ROLE_CMS_MANAGER = 'cms_manager';

    public const ROLE_MEMBERSHIP_MANAGER = 'membership_manager';

    public const ROLE_SUPPORT_STAFF = 'support_staff';

    public const ROLE_MEMBER = 'member';

    public const PERMISSION_VIEW_ADMIN_DASHBOARD = 'view_admin_dashboard';

    public const PERMISSION_VIEW_PAGES = 'view_pages';

    public const PERMISSION_VIEW_MEDIA = 'view_media';

    public const PERMISSION_VIEW_SERVICES = 'view_services';

    public const PERMISSION_VIEW_ANNOUNCEMENTS = 'view_announcements';

    public const PERMISSION_VIEW_DOCUMENTS = 'view_documents';

    public const PERMISSION_VIEW_MEMBERS = 'view_members';

    public const PERMISSION_VIEW_MEMBERSHIP_APPLICATIONS = 'view_membership_applications';

    public const PERMISSION_VIEW_COMPLAINTS = 'view_complaints';

    public const PERMISSION_VIEW_USERS = 'view_users';

    public const PERMISSION_VIEW_ROLES = 'view_roles';

    public const PERMISSION_VIEW_SETTINGS = 'view_settings';

    public const PERMISSION_EDIT_SETTINGS = 'edit_settings';

    public const PERMISSION_VIEW_AUDIT_LOGS = 'view_audit_logs';

    public const PERMISSION_VIEW_REPORTS = 'view_reports';

    public const PERMISSION_MEMBER_ACCESS = 'member_access';

    public static function roles(): array
    {
        return [
            self::ROLE_SUPER_ADMIN,
            self::ROLE_ADMIN,
            self::ROLE_CMS_MANAGER,
            self::ROLE_MEMBERSHIP_MANAGER,
            self::ROLE_SUPPORT_STAFF,
            self::ROLE_MEMBER,
        ];
    }

    public static function permissions(): array
    {
        return [
            self::PERMISSION_VIEW_ADMIN_DASHBOARD,
            self::PERMISSION_VIEW_PAGES,
            self::PERMISSION_VIEW_MEDIA,
            self::PERMISSION_VIEW_SERVICES,
            self::PERMISSION_VIEW_ANNOUNCEMENTS,
            self::PERMISSION_VIEW_DOCUMENTS,
            self::PERMISSION_VIEW_MEMBERS,
            self::PERMISSION_VIEW_MEMBERSHIP_APPLICATIONS,
            self::PERMISSION_VIEW_COMPLAINTS,
            self::PERMISSION_VIEW_USERS,
            self::PERMISSION_VIEW_ROLES,
            self::PERMISSION_VIEW_SETTINGS,
            self::PERMISSION_EDIT_SETTINGS,
            self::PERMISSION_VIEW_AUDIT_LOGS,
            self::PERMISSION_VIEW_REPORTS,
            self::PERMISSION_MEMBER_ACCESS,
        ];
    }

    public static function rolePermissions(): array
    {
        $adminDashboard = [self::PERMISSION_VIEW_ADMIN_DASHBOARD];

        return [
            self::ROLE_SUPER_ADMIN => self::permissions(),
            self::ROLE_ADMIN => [
                ...$adminDashboard,
                self::PERMISSION_VIEW_PAGES,
                self::PERMISSION_VIEW_MEDIA,
                self::PERMISSION_VIEW_SERVICES,
                self::PERMISSION_VIEW_ANNOUNCEMENTS,
                self::PERMISSION_VIEW_DOCUMENTS,
                self::PERMISSION_VIEW_MEMBERS,
                self::PERMISSION_VIEW_MEMBERSHIP_APPLICATIONS,
                self::PERMISSION_VIEW_COMPLAINTS,
                self::PERMISSION_VIEW_USERS,
                self::PERMISSION_VIEW_SETTINGS,
                self::PERMISSION_EDIT_SETTINGS,
                self::PERMISSION_VIEW_REPORTS,
            ],
            self::ROLE_CMS_MANAGER => [
                ...$adminDashboard,
                self::PERMISSION_VIEW_PAGES,
                self::PERMISSION_VIEW_MEDIA,
                self::PERMISSION_VIEW_SERVICES,
                self::PERMISSION_VIEW_ANNOUNCEMENTS,
                self::PERMISSION_VIEW_DOCUMENTS,
            ],
            self::ROLE_MEMBERSHIP_MANAGER => [
                ...$adminDashboard,
                self::PERMISSION_VIEW_MEMBERS,
                self::PERMISSION_VIEW_MEMBERSHIP_APPLICATIONS,
                self::PERMISSION_VIEW_DOCUMENTS,
                self::PERMISSION_VIEW_REPORTS,
            ],
            self::ROLE_SUPPORT_STAFF => [
                ...$adminDashboard,
                self::PERMISSION_VIEW_COMPLAINTS,
                self::PERMISSION_VIEW_ANNOUNCEMENTS,
            ],
            self::ROLE_MEMBER => [
                self::PERMISSION_MEMBER_ACCESS,
            ],
        ];
    }

    public static function adminRoles(): array
    {
        return [
            self::ROLE_SUPER_ADMIN,
            self::ROLE_ADMIN,
            self::ROLE_CMS_MANAGER,
            self::ROLE_MEMBERSHIP_MANAGER,
            self::ROLE_SUPPORT_STAFF,
        ];
    }
}
