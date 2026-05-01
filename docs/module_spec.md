# module_spec.md

# KoperasiHub — Module Specification

Purpose: define what each module does so Codex builds the right features without guessing.

Read with:
- `AGENTS.md`
- `project_overview.md`
- `docs/database_schema.md`

Do not repeat database details here. Use `database_schema.md` for tables/columns.

---

## Global Rules

- Stack: Laravel + Vue 3 + Inertia + Tailwind + shadcn-vue.
- Build a custom admin panel. Do not use Filament.
- Keep the product white-label. Do not hardcode real cooperative names, logos, addresses, or phone numbers.
- Use dummy/demo data only.
- Separate Public, Admin, Member, and API areas.
- Enforce permissions on backend, not only in frontend.
- Use Form Requests for validation.
- Use Policies/Gates for authorization.
- Use Service classes for non-trivial workflows.
- Add audit logs for sensitive admin actions.
- Keep MVP focused. Do not build accounting, loan ledger, payments, inventory, POS, dividend engine, or e-voting unless requested.

---

## Route Areas

```txt
/                 Public website
/admin            Admin panel
/member           Member portal
/api/v1           Future mobile/API layer
```

Recommended route files:

```txt
routes/web.php       Public website + auth redirects
routes/admin.php     Admin routes
routes/member.php    Member portal routes
routes/api.php       API v1 routes
```

---

## User Roles

Default roles:

```txt
super_admin
admin
cms_manager
membership_manager
support_staff
finance_viewer
member
```

Role notes:
- `super_admin`: full access.
- `admin`: most admin operations except system-critical settings if restricted.
- `cms_manager`: website/CMS content only.
- `membership_manager`: members and applications.
- `support_staff`: complaints/support tickets.
- `finance_viewer`: read-only financial/member summary areas when added.
- `member`: member portal only.

---

# 1. Authentication Module

## Purpose
Provide login/logout and access separation for admin users and members.

## Areas

```txt
/admin/login
/member/login
/logout
```

## Core Actions

- Admin login
- Member login
- Logout
- Password reset-ready structure
- Redirect user based on role
- Block unauthorized area access

## Permissions

Auth itself does not require custom permissions, but protected routes must require roles/permissions.

## Rules

- Members cannot access `/admin`.
- Admin/staff cannot access member-only data unless authorized.
- Use Laravel auth/session for web.
- Use Sanctum later for API/mobile.

## Out of Scope

- Social login
- SSO
- Biometric login
- Native mobile auth UI

---

# 2. Admin Dashboard Module

## Purpose
Give admins a quick operational overview.

## Route

```txt
/admin/dashboard
```

## Widgets MVP

- Total members
- Pending membership applications
- Published pages
- Active announcements
- Open complaints
- Recent admin activity

## Actions

- View dashboard
- Click through to related module

## Permissions

```txt
view_admin_dashboard
```

## Related Modules

- Members
- Membership Applications
- CMS
- Announcements
- Complaints
- Audit Logs

## Out of Scope

- Complex BI dashboard
- Financial analytics
- Accounting reports

---

# 3. Settings Module

## Purpose
Manage white-label cooperative settings and system preferences.

## Admin Routes

```txt
/admin/settings/brand
/admin/settings/contact
/admin/settings/social
/admin/settings/seo
/admin/settings/membership
/admin/settings/system
```

## Settings Groups

```txt
brand
contact
social
seo
membership
notification
security
system
```

## Core Actions

- View settings
- Update cooperative name/logo/colors
- Update contact info
- Update social links
- Update SEO defaults
- Update membership application settings
- Update system preferences

## Permissions

```txt
view_settings
edit_settings
```

## Rules

- Settings should be database-driven.
- Cache settings when useful.
- Clear cache after update.
- Validate all settings.
- Do not hardcode cooperative identity in frontend.

## Out of Scope

- Multi-tenant billing settings
- Subscription management

---

# 4. CMS Pages Module

## Purpose
Manage website pages and their section-based content.

## Admin Routes

```txt
/admin/pages
/admin/pages/create
/admin/pages/{page}
/admin/pages/{page}/edit
/admin/pages/{page}/sections
/admin/pages/{page}/preview
```

## Public Routes

```txt
/
/{slug}
```

## Core Actions

- List pages
- Create page
- Edit page metadata
- Publish/unpublish page
- Archive page
- Preview page
- Manage page sections
- Reorder sections

## Page Statuses

```txt
draft
published
archived
```

## Permissions

```txt
view_pages
create_pages
edit_pages
delete_pages
publish_pages
```

## Rules

- Homepage should be a page record or known system page.
- Published public pages render only active sections.
- Draft pages are not public.
- Slugs must be unique.
- SEO fields should be editable.

## Out of Scope

- Raw HTML page builder
- Elementor-style freeform layout builder
- Custom CSS editor

---

# 5. CMS Sections Module

## Purpose
Power dynamic landing pages using predefined section components.

## Managed Under

```txt
/admin/pages/{page}/sections
```

## Core Actions

- Add section
- Edit section content
- Choose predefined variant
- Toggle active/inactive
- Reorder section
- Delete section
- Preview changes

## Section Status

```txt
active
inactive
```

## Permissions

```txt
view_pages
edit_pages
publish_pages
```

## Rules

- Use section types defined in `cms_section_spec.md`.
- Admin can edit data/settings only.
- Admin cannot edit raw CSS/JS.
- Unknown section type should not break production render.
- Frontend maps section type to Vue component.

## Out of Scope

- Arbitrary drag-and-drop website builder
- Nested sections unless requested

---

# 6. Media Library Module

## Purpose
Manage reusable public media for CMS content.

## Admin Routes

```txt
/admin/media
/admin/media/upload
```

## Core Actions

- Upload media
- View media
- Search/filter media
- Select media for CMS fields
- Delete media if unused or allowed

## Permissions

```txt
view_media
upload_media
delete_media
```

## Rules

- Public media can be served publicly.
- Validate MIME type and size.
- Store uploaded_by.
- Use image preview.
- Do not mix private member documents with public media.

## Out of Scope

- Advanced image editor
- CDN integration

---

# 7. Services Module

## Purpose
Manage cooperative services/business offerings shown on public website and optionally member portal.

## Admin Routes

```txt
/admin/services
/admin/services/create
/admin/services/{service}/edit
```

## Public Usage

- Service listing section
- Service detail page if enabled
- Homepage service cards

## Core Actions

- List services
- Create service
- Edit service
- Publish/unpublish service
- Reorder services
- Attach image/icon

## Statuses

```txt
draft
published
archived
```

## Permissions

```txt
view_services
create_services
edit_services
delete_services
publish_services
```

## Rules

- Services are generic/dummy by default.
- No real cooperative-specific service names unless configured by admin.
- Service CTA can link to page, form, WhatsApp, or external URL.

## Out of Scope

- Inventory
- POS
- E-commerce checkout

---

# 8. Announcements Module

## Purpose
Manage public and member-only announcements.

## Admin Routes

```txt
/admin/announcements
/admin/announcements/create
/admin/announcements/{announcement}/edit
```

## Public/Member Usage

```txt
/announcements
/announcements/{slug}
/member/announcements
```

## Core Actions

- List announcements
- Create announcement
- Edit announcement
- Publish/unpublish
- Pin announcement
- Set audience
- Set publish/expiry date

## Audience Types

```txt
public
members
admins
specific_roles
```

## Statuses

```txt
draft
published
archived
```

## Permissions

```txt
view_announcements
create_announcements
edit_announcements
delete_announcements
publish_announcements
```

## Rules

- Public announcements can appear on website.
- Member-only announcements require member login.
- Expired announcements hidden by default.
- Pinned announcements appear first.

## Out of Scope

- Push notifications unless Package C scope is active
- SMS blast

---

# 9. Downloads / Documents Module

## Purpose
Manage public downloads and protected member/admin documents.

## Admin Routes

```txt
/admin/documents
/admin/documents/create
/admin/documents/{document}/edit
```

## Public/Member Usage

```txt
/downloads
/member/documents
```

## Visibility Types

```txt
public
members_only
admin_only
specific_member
```

## Core Actions

- Upload document
- Categorize document
- Set visibility
- Publish/unpublish document
- Assign document to specific member when needed
- Download document with access control

## Permissions

```txt
view_documents
create_documents
edit_documents
delete_documents
publish_documents
```

## Rules

- Public documents can be downloaded without login.
- Member documents require member auth.
- Specific member documents visible only to that member and authorized admins.
- Private documents must use protected download routes.

## Out of Scope

- Digital signing
- Document versioning unless requested

---

# 10. Members Module

## Purpose
Manage approved cooperative members.

## Admin Routes

```txt
/admin/members
/admin/members/create
/admin/members/{member}
/admin/members/{member}/edit
```

## Member Routes

```txt
/member/profile
```

## Core Admin Actions

- List members
- Search/filter members
- Create member manually
- View member profile
- Edit member profile
- Change member status
- Link member to user account
- View member documents/related records

## Member Statuses

```txt
active
inactive
suspended
```

## Permissions

```txt
view_members
create_members
edit_members
suspend_members
delete_members
```

## Rules

- Member data is sensitive.
- Admin edits must be audit logged.
- Members can only view/edit allowed fields in their own profile.
- Do not expose member identity data publicly.

## Out of Scope

- Share ledger
- Dividend calculation
- Loan repayment ledger
- Payment history

---

# 11. Membership Applications Module

## Purpose
Handle membership registration/application workflow.

## Public/Member Routes

```txt
/apply-membership
/member/applications
/member/applications/{application}
```

## Admin Routes

```txt
/admin/membership-applications
/admin/membership-applications/{application}
/admin/membership-applications/{application}/review
```

## Core Actions

- Submit application
- Upload supporting documents
- View application status
- Admin review application
- Approve application
- Reject application with reason
- Convert approved application into member record

## Statuses

```txt
pending
under_review
approved
rejected
cancelled
```

## Permissions

```txt
view_membership_applications
review_membership_applications
approve_membership_applications
reject_membership_applications
```

## Rules

- Application number should be generated.
- Approval should create/link member record.
- Rejection must store reason.
- Status changes must be audit logged.
- Applicant should only see their own application.

## Out of Scope

- Payment during application
- Auto-approval
- External KYC verification

---

# 12. Member Portal Dashboard Module

## Purpose
Give members a simple self-service dashboard.

## Routes

```txt
/member/dashboard
```

## Widgets MVP

- Membership status
- Profile completion indicator
- Latest member announcements
- Recent documents
- Application status if any
- Complaint/ticket summary

## Actions

- View dashboard
- Navigate to profile/documents/applications/complaints

## Permissions

```txt
member_access
```

## Rules

- Member sees only their own data.
- Keep dashboard mobile responsive.
- Use simple language.

## Out of Scope

- Financial dashboard unless requested
- Loan balances
- Payment gateway

---

# 13. Member Profile Module

## Purpose
Allow members to view and update selected personal details.

## Routes

```txt
/member/profile
/member/profile/edit
```

## Core Actions

- View own profile
- Edit allowed fields
- Change password-ready structure

## Editable Fields MVP

- Phone
- Email if allowed
- Address
- Emergency contact if added

## Permissions

```txt
member_access
```

## Rules

- Sensitive locked fields may require admin approval to change.
- Member cannot change membership status.
- Changes should be audit logged where appropriate.

## Out of Scope

- Full profile verification workflow
- Identity document re-verification

---

# 14. Complaints / Suggestions Module

## Purpose
Allow members to submit support tickets, complaints, or suggestions.

## Member Routes

```txt
/member/complaints
/member/complaints/create
/member/complaints/{complaint}
```

## Admin Routes

```txt
/admin/complaints
/admin/complaints/{complaint}
```

## Core Actions

- Member submits complaint/suggestion
- Member views own tickets
- Admin views tickets
- Admin assigns ticket
- Admin replies
- Admin changes status
- Admin closes ticket

## Statuses

```txt
open
in_progress
resolved
closed
```

## Permissions

```txt
view_complaints
reply_complaints
close_complaints
```

## Rules

- Member sees own tickets only.
- Internal admin notes are not visible to members.
- Status changes should be logged.

## Out of Scope

- SLA automation
- Live chat
- WhatsApp integration

---

# 15. Users & Roles Module

## Purpose
Manage admin/staff users and role-based permissions.

## Admin Routes

```txt
/admin/users
/admin/users/create
/admin/users/{user}/edit
/admin/roles
/admin/roles/{role}/edit
```

## Core Actions

- List users
- Create admin/staff user
- Edit user
- Activate/deactivate user
- Assign roles
- View roles
- Edit role permissions

## Permissions

```txt
view_users
create_users
edit_users
delete_users
view_roles
edit_roles
```

## Rules

- Only authorized users can manage roles.
- Role/permission changes must be audit logged.
- Do not allow normal admin to remove last super_admin.

## Out of Scope

- Organization chart
- HR management

---

# 16. Audit Logs Module

## Purpose
Allow authorized admins to inspect sensitive system activity.

## Admin Routes

```txt
/admin/audit-logs
/admin/audit-logs/{log}
```

## Core Actions

- View audit log list
- Filter by actor/action/module/date
- View log details

## Permissions

```txt
view_audit_logs
```

## Rules

- Audit logs are read-only.
- Do not allow normal deletion/editing.
- Log sensitive admin actions.

## Out of Scope

- Advanced SIEM export
- Tamper-proof external logging

---

# 17. Reports Module

## Purpose
Provide basic operational reports for demo/MVP.

## Admin Routes

```txt
/admin/reports
```

## MVP Reports

- Member count by status
- Membership applications by status
- Announcements by status
- Complaints by status
- Recent activity summary

## Permissions

```txt
view_reports
```

## Rules

- Keep reports simple.
- Use existing data.
- Avoid financial/accounting reports for MVP.

## Out of Scope

- Accounting reports
- Loan reports
- Dividend reports
- Export-heavy analytics unless requested

---

# 18. API v1 Module

## Purpose
Prepare backend for future mobile apps and external clients.

## Routes

```txt
/api/v1/auth
/api/v1/member
/api/v1/announcements
/api/v1/documents
/api/v1/applications
/api/v1/complaints
/api/v1/settings
```

## MVP API Endpoints

- Login/logout-ready structure
- Current member profile
- Member announcements
- Member documents
- Membership application status
- Submit complaint
- Public settings/branding

## Permissions/Auth

- Use Sanctum when API auth is implemented.
- Public endpoints only expose public settings/content.
- Member endpoints require authenticated member.

## Rules

- Use API Resources.
- Do not return raw Eloquent models.
- Keep response shape consistent.
- Version API under `/api/v1`.

## Out of Scope

- Public third-party developer API
- Webhooks
- OAuth server
- Native mobile frontend

---

# 19. Demo Seed Data Module

## Purpose
Provide dummy data for presentation/demo using SQLite.

## Seeders Should Create

- Demo cooperative settings
- Super admin user
- Staff users
- Member users
- Demo members
- Membership applications
- Homepage page + sections
- Services
- Announcements
- Public downloads
- Member documents
- Complaints

## Rules

- Use fake data only.
- No real names, phone numbers, IC numbers, addresses, or cooperative data.
- Data should look realistic enough for demo.
- SQLite demo database should be easy to reset.

## Out of Scope

- Production migration data
- Real cooperative import

---

# 20. Package B Scope

Package B includes:

```txt
Public Website
Section-based CMS
Custom Admin Panel
Settings
Members
Membership Applications
Member Portal
Announcements
Documents/Downloads
Complaints
Users & Roles
Audit Logs
Basic Reports
API-ready structure
Demo Seed Data
```

Do not include:

```txt
Mobile app frontend
Payment gateway
Accounting
Loan ledger
Dividend engine
Inventory
POS
E-voting
```

---

# 21. Package C Scope

Package C extends Package B.

Potential additions:

```txt
Mobile API hardening
Digital member card
QR member ID
Push notification foundation
Device management
Advanced member segmentation
Campaign targeting
Advanced reporting
Financing application workflow only, not full loan ledger
Payment gateway integration if requested
```

Build Package C only after Package B foundation is stable.

---

# Build Priority

Recommended implementation order:

```txt
1. Auth + layouts
2. Settings
3. Roles & permissions
4. CMS pages + sections
5. Public website renderer
6. Media library
7. Services
8. Announcements
9. Documents/downloads
10. Members
11. Membership applications
12. Member portal
13. Complaints
14. Users/roles admin UI
15. Audit logs
16. Basic reports
17. API v1 foundation
18. Demo seed data polish
```

---

# Definition of Done Per Module

A module is done when:

- Routes exist.
- Pages/screens exist.
- Validation exists.
- Authorization exists.
- CRUD/workflow works.
- Empty/loading/error states exist.
- Relevant audit logs exist.
- Demo data exists if useful.
- Feature respects white-label rules.
- Sensitive data is protected.
- Tests exist for core workflows where practical.
