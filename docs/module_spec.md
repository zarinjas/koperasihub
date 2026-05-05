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
- MVP is a single-tenant web application installed separately for each cooperative.
- Separate Public, Admin, and Member areas.
- Enforce permissions on backend, not only in frontend.
- Use Form Requests for validation.
- Use Policies/Gates for authorization.
- Use Service classes for non-trivial workflows.
- Add audit logs for sensitive admin actions.
- Use local storage for uploaded files/documents in MVP.
- Keep only these active MVP roles: `super_admin`, `admin`, `member`.
- Keep MVP focused. Do not build accounting, loan ledger, payments, inventory, POS, dividend engine, or e-voting unless requested.
- Do not build API endpoints or mobile app features unless explicitly requested later.
- Keep public membership application separate from member `Permohonan`.
- Use `Dokumen` for downloads/reference files, not as the main application submission flow.

---

## Route Areas

```txt
/                 Public website
/admin            Admin panel
/member           Member portal
```

Recommended route files:

```txt
routes/web.php       Public website + auth redirects
routes/admin.php     Admin routes
routes/member.php    Member portal routes
```

---

## User Roles

Default roles:

```txt
super_admin
admin
member
```

Role notes:
- `super_admin`: full access.
- `admin`: most admin operations except system-critical settings if restricted.
- `member`: member portal only.

Future roles such as `cms_manager`, `membership_manager`, and `support_staff` may be introduced later if the cooperative needs finer permission splits.

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
- Admin users cannot access member-only data unless authorized.
- Use Laravel auth/session for web.

## Out of Scope

- Social login
- SSO
- Biometric login
- API/mobile auth

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
- Use local storage in MVP.
- Do not mix private member documents with public media.

## Out of Scope

- Advanced image editor
- CDN integration
- S3/object storage integration

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
- Position this module as `Dokumen & Muat Turun` / `Pusat Muat Turun`.
- Do not treat this module as the main form submission workflow.

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

# 11. Public Membership Applications Module

## Purpose
Handle the public membership registration/application workflow.

## Public Routes

```txt
/apply-membership
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
- This flow is separate from member `Permohonan`.

## Out of Scope

- Payment during application
- Auto-approval
- External KYC verification
- Member post-login online forms

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
- Recent `Permohonan` status
- Digital card shortcut
- Complaint/ticket summary

## Actions

- View dashboard
- Navigate to profile/documents/permohonan/complaints/digital-card

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

# 14. Member Permohonan / Borang Online Module

## Purpose
Provide a unified member-side `Permohonan` flow for structured online form submissions after login.

## Member Routes

```txt
/member/permohonan
/member/permohonan/{form}
/member/permohonan/submissions/{submission}
```

## Admin Routes

```txt
/admin/forms
/admin/forms/categories
/admin/forms/units
/admin/forms/{form}
/admin/forms/submissions
/admin/forms/submissions/{submission}
```

## Core Actions

- Manage form categories and units
- Create structured forms
- Define sections and fields
- Publish forms to a public directory when appropriate
- Submit form under member `Permohonan`
- Save submission status
- Show print preview
- Capture signature
- Capture agreement/acknowledgement
- Show office use box
- Support hybrid online/manual submission method

## Permissions

```txt
view_forms
create_forms
edit_forms
publish_forms
view_form_submissions
manage_form_submissions
member_access
```

## Rules

- This module is for member-authenticated online form submissions.
- Keep the public membership application as a separate workflow.
- Public website may list available forms in a directory without replacing the member-authenticated submission flow.
- Support print-friendly output for branch or office processing.
- Signature, agreement, and office-use areas should be structured parts of the form, not ad hoc uploads.
- Hybrid submission may combine online completion with in-office verification or final processing.

## Out of Scope

- General-purpose BPM engine
- Native mobile form app
- External e-signature integration

---

# 15. Digital Membership Card Module

## Purpose
Give members a web-based digital card for identity display and simple verification.

## Member Routes

```txt
/member/digital-card
```

## Core Actions

- View digital membership card
- Show member profile photo
- Show QR verification payload
- Download/share card
- Show wallet prototype buttons

## Permissions

```txt
member_access
```

## Rules

- Card is part of the web MVP member experience.
- QR verification should support a web verification flow.
- Wallet buttons may remain prototype/placeholder if platform integration is not ready.

## Out of Scope

- Native wallet deep integration
- NFC card support
- Device-bound anti-fraud controls

---

# 16. Complaints / Suggestions Module

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

# 17. Users & Roles Module

## Purpose
Manage admin users and MVP role assignments.

## Admin Routes

```txt
/admin/users
/admin/users/create
/admin/users/{user}/edit
```

## Core Actions

- List users
- Create admin user
- Edit user
- Activate/deactivate user
- Assign supported MVP roles

## Permissions

```txt
view_users
create_users
edit_users
delete_users
```

## Rules

- Only authorized users can assign roles.
- Role assignment changes must be audit logged.
- Do not allow normal admin to remove last super_admin.

## Out of Scope

- Organization chart
- HR management
- Full role management UI for future roles

---

# 18. Audit Logs Module

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

# 19. Reports Module

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

# 20. Demo Seed Data Module

## Purpose
Provide dummy data for presentation/demo using SQLite.

## Seeders Should Create

- Demo cooperative settings
- Super admin user
- Admin users
- Member users
- Demo members
- Membership applications
- Borang Online categories/forms/submissions
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

# 21. Package B Scope

Package B includes:

```txt
Public Website
Section-based CMS
Custom Admin Panel
Settings
Members
Membership Applications
Member Portal
Member Permohonan / Borang Online
Digital Membership Card
Announcements
Documents/Downloads
Complaints
Users & Roles
Audit Logs
Basic Reports
Demo Seed Data
```

Do not include:

```txt
Mobile app frontend
API endpoints
Payment gateway
Accounting
Loan ledger
Dividend engine
Inventory
POS
E-voting
```

---

# 22. Package C Scope

Package C extends Package B.

Potential additions:

```txt
Mobile API hardening
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
11. Public membership applications
12. Member portal
13. Member Permohonan / Borang Online
14. Digital membership card
15. Complaints
16. Users/roles admin UI
17. Audit logs
18. Basic reports
19. Demo seed data polish
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
