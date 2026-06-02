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

# 3. Semakan / Review Inbox Module

## Purpose
Unified admin inbox showing all items pending review: membership applications, form submissions, financing applications, and complaints.

## Admin Routes

```txt
/admin/semakan
```

## Core Actions

- View all pending review items in one place
- Filter by module type
- Click through to individual review pages
- Quick status overview

## Permissions

```txt
view_semakan
```

## Rules

- Aggregates pending items across modules.
- Acts as a landing page for admin workflow.
- Each item links to its dedicated review page.

## Out of Scope

- Automated approval routing
- SLA tracking

---

# 4. Settings Module

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

# 5. CMS Pages Module

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

# 6. CMS Sections Module

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

# 7. Media Library Module

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

# 8. Services Module

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

# 9. Announcements Module

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

# 10. News Module

## Purpose
Manage news articles for public website and member portal.

## Admin Routes

```txt
/admin/news
/admin/news/create
/admin/news/{news}/edit
```

## Public/Member Usage

```txt
/berita
/berita/{slug}
/news
/news/{slug}
```

## Core Actions

- List news articles
- Create news article
- Edit news article
- Publish/unpublish
- Archive article
- Set publish date

## Statuses

```txt
draft
published
archived
```

## Permissions

```txt
view_news
create_news
edit_news
delete_news
publish_news
```

## Rules

- News is primarily public-facing content.
- Supports categories.
- Published articles visible on public website.

## Out of Scope

- RSS feed
- Newsletter integration

---

# 11. Posters Module

## Purpose
Manage poster/banner gallery for public website and member portal carousels.

## Admin Routes

```txt
/admin/posters
/admin/posters/create
/admin/posters/{poster}/edit
```

## Public/Member Usage

```txt
/posters
/member/posters
```

## Core Actions

- Upload poster image
- Set alt text
- Publish/unpublish
- Reorder posters
- Toggle active/inactive

## Statuses

```txt
draft
published
archived
```

## Permissions

```txt
view_posters
create_posters
edit_posters
delete_posters
publish_posters
```

## Rules

- Posters display in carousel/gallery components.
- Active published posters visible on public website.
- Member portal may show different posters if configured.

## Out of Scope

- Video posters
- Animated banners

---

# 12. Downloads / Documents Module

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

# 13. Members Module

## Purpose
Manage approved cooperative members, including import and portal activation.

## Admin Routes

```txt
/admin/members
/admin/members/create
/admin/members/{member}
/admin/members/{member}/edit
/admin/members/import
/admin/members/search
```

## Member Routes

```txt
/member/profile
/member/activate
```

## Core Admin Actions

- List members with search/filter
- Create member manually
- View member profile
- Edit member profile
- Change member status
- Link member to user account
- View member documents/related records
- **Import members** via CSV/Excel with template download and preview
- **Search members** via AJAX for form/assignment selectors

## Member Statuses

```txt
active
inactive
suspended
```

## Member Import

- Download CSV template
- Upload file with preview before committing
- Validate and create members in batch
- Audit log import actions

## Member Portal Activation

- Member activates portal account using identity number and other verification
- Step-based activation flow (verify identity → set password → complete)
- Password reset flow for members

## Permissions

```txt
view_members
create_members
edit_members
suspend_members
delete_members
import_members
```

## Rules

- Member data is sensitive.
- Admin edits must be audit logged.
- Members can only view/edit allowed fields in their own profile.
- Do not expose member identity data publicly.
- Import requires validation before final commit.

## Out of Scope

- Share ledger
- Dividend calculation
- Loan repayment ledger
- Payment history

---

# 14. Caruman / Member Contributions Module

## Purpose
Manage and display member contribution records (capital shares, savings, dividends).

## Admin Routes

```txt
/admin/caruman
```

## Member Routes

```txt
/member/caruman
```

## Core Actions

- View contribution records per member (admin)
- Update contribution data
- Store yearly contributions including current shares, total shares, and dividend
- Member views own contribution history

## Permissions

```txt
view_caruman
edit_caruman
```

## Rules

- Contribution data may be entered manually or imported.
- Members see their own records only.
- Designed as a reference display, not a full accounting ledger.

## Out of Scope

- Full share capital ledger
- Dividend calculation engine
- Automated transaction posting

---

# 15. Public Membership Applications Module

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

# 16. Member Portal Dashboard Module

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

# 17. Member Profile Module

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

# 18. Borang Online / Member Permohonan Module

## Purpose
Full dynamic form builder system. Admin creates structured online forms with sections and fields. Members submit forms through a unified `Permohonan` flow. Public can view form directory and submit where allowed.

## Admin Routes

```txt
/admin/forms
/admin/forms/{form}
/admin/forms/{form}/edit
/admin/forms/categories
/admin/forms/categories/{category}/edit
/admin/forms/{form}/sections
/admin/forms/{form}/fields
/admin/forms/{form}/submissions
/admin/form-submissions
```

## Member Routes

```txt
/member/permohonan
/member/applications
/member/applications/submissions/{submission}
```

## Public Routes

```txt
/forms
/forms/category/{category}
/forms/{slug}
```

## Core Actions

- Manage form categories (with unit assignment)
- Create structured forms with metadata (document code, revision, effective date)
- Define sections with page breaks
- Define typed fields (text, number, date, select, checkbox, radio, file, signature, etc.)
- Save/reuse form section templates
- Set form visibility (public / members only)
- Set submission method (online only / print then submit / online with stamped document upload)
- Submit form with signature capture and agreement/acknowledgement
- Show office use box
- Print-friendly preview
- Hybrid online/manual submission support
- Upload stamped/endorsed document for hybrid workflow
- Multi-step submission flow for complex forms
- Admin submission review inbox (cross-form)

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

- Form builder supports sections, fields, templates, and typed inputs.
- Public may view and submit forms marked as public visibility.
- Members submit through unified `Permohonan` flow.
- Signature and agreement blocks are structured form components.
- Stamped document upload supports hybrid workflow.
- Section templates can be saved and reused across forms.
- Form submissions have workflow statuses (draft, submitted, under review, approved, rejected).

## Out of Scope

- General-purpose BPM engine
- Native mobile form app
- External e-signature integration

---

# 19. Financing Module

## Purpose
Manage cooperative financing/financing products with dynamic application forms. Supports full application workflow including guarantor management.

## Admin Routes

```txt
/admin/financing/categories
/admin/financing/categories/{category}/edit
/admin/financing/products
/admin/financing/products/{product}/edit
/admin/financing/applications
/admin/financing/applications/{application}
/admin/financing/applications/{application}/print
```

## Member Routes

```txt
/member/financing
/member/financing/products/{product}
/member/financing/applications
/member/financing/applications/create
/member/financing/applications/{application}
/member/financing/guarantor-requests
/member/financing/guarantor-requests/{guarantorRequest}
```

## Core Actions

- Manage financing categories (type, icon, ordering)
- Manage financing products with dynamic form builder (sections + fields)
- Set product parameters (min/max amount, tenure, rate, guarantor requirements)
- Member applies for financing with dynamic form
- Upload supporting documents per field
- Guarantor search and selection
- Admin reviews applications (in review, incomplete, approve, reject)
- Cancel application
- Print application summary
- Upload stamped/endorsed form
- Guarantor consent request and response flow
- Application status history tracking
- Download application documents

## Application Statuses

```txt
draft
submitted
under_review
incomplete
approved
rejected
cancelled
active
closed
```

## Guarantor Statuses

```txt
pending
approved
rejected
```

## Permissions

```txt
view_financing
create_financing
edit_financing
delete_financing
publish_financing
view_financing_applications
review_financing_applications
approve_financing_applications
reject_financing_applications
```

## Rules

- Financing products use the same section/field pattern as Borang Online.
- Each product defines its own form fields dynamically.
- Admin configures guarantor requirements per product.
- Guarantor must be an existing cooperative member.
- Guarantor consent captured through member portal.
- Supporting documents uploaded per form field.
- Stamped form upload supports post-approval processing.
- All status changes are logged in application history.
- This is application workflow only, not a full loan ledger.

## Out of Scope

- Full loan amortization ledger
- Payment collection
- Interest calculation engine
- Automated disbursement

---

# 20. Digital Membership Card Module

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

# 21. Complaints / Suggestions Module

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

# 22. Units / Department Management Module

## Purpose
Manage cooperative business units/departments. Units are used for form categories and staff assignment.

## Admin Routes

```txt
/admin/units
/admin/units/create
/admin/units/{unit}/edit
```

## Core Actions

- List units
- Create unit
- Edit unit
- Delete unit
- Toggle active/inactive
- Reorder units

## Permissions

```txt
view_units
create_units
edit_units
delete_units
```

## Rules

- Units are referenced by form categories and staff profiles.
- Deleting a unit may affect related records.

## Out of Scope

- Organizational hierarchy
- Multi-level department tree

---

# 23. Staff & Admin User Management Module

## Purpose
Manage admin staff accounts including position and unit assignment.

## Admin Routes

```txt
/admin/staff
/admin/staff/create
/admin/staff/{user}/edit
```

## Core Actions

- List staff users
- Create staff account
- Edit staff profile
- Assign unit and position title
- Activate/deactivate user

## Permissions

```txt
view_staff
create_staff
edit_staff
delete_staff
```

## Rules

- Staff are `User` records with role assignment.
- Unit assignment links staff to a cooperative unit/department.

## Out of Scope

- HR management
- Payroll

---

# 24. Users & Roles Module

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

# 25. Notifications Module

## Purpose
In-app notification system for admin and member users.

## Admin Routes

```txt
/admin/notifications
```

## Member Routes

```txt
/member/notifications
```

## Core Actions

- View notification list
- Mark notification as read
- Mark all as read
- Auto-generate notifications for key events

## Notification Events

- Announcement published (with notification enabled)
- Membership application status change
- Complaint reply received
- Financing application status change
- Guarantor request received

## Permissions

```txt
(available to all authenticated users by area)
```

## Rules

- Uses Laravel database notifications.
- Notifications are per-user.
- Unread count shown in navigation bell icon.
- Announcement notifications can be sent to specific members.

## Out of Scope

- Push notifications (mobile)
- Email campaign engine
- SMS notifications
- WebSocket/real-time delivery

---

# 26. Audit Logs Module

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

# 27. Reports Module

## Purpose
Provide basic operational reports for demo/MVP. Currently a prototype/placeholder.

## Status
⚠ **Prototype**. Basic summary page exists but is not fully developed.

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

# 28. Demo Seed Data Module

## Purpose
Provide dummy data for presentation/demo using SQLite.

## Seeders Create

- Demo cooperative settings and branding
- Super admin, admin, and member users
- Demo members with profiles
- Membership applications in various statuses
- Borang Online categories, forms, sections, fields, submissions
- Homepage page with all section types
- Services with images
- Announcements (public and member-only)
- News articles
- Posters / banners
- Public and member documents with categories
- Complaints with replies
- Member contributions (Caruman)
- Units
- Financing categories, products, applications, guarantors
- Demo form submissions

## Rules

- Use fake data only.
- No real names, phone numbers, IC numbers, addresses, or cooperative data.
- Data should look realistic enough for demo.
- SQLite demo database should be easy to reset.

## Out of Scope

- Production migration data
- Real cooperative import

---

# 29. Package B Scope (Current State)

Package B includes all implemented modules:

```txt
Public Website & CMS (14 section types)
Custom Admin Panel
Settings & White-Label Branding
Members Management (CRUD + Import + Activation)
Public Membership Applications
Member Portal (Dashboard, Profile, Documents)
Borang Online (Dynamic Form Builder with Sections/Fields/Templates)
Financing Module (Products, Applications, Guarantors)
Digital Membership Card (QR Verification)
News
Announcements (Public/Member Audience)
Posters / Banner Gallery
Documents/Downloads (Visibility Control)
Services Management
Complaints / Suggestions (Ticketing)
Caruman / Member Contributions
Units / Department Management
Staff & Admin User Management
Users & Roles (39 Permissions, 3 Roles)
Notifications (In-App)
Semakan / Review Inbox
Audit Logs
Basic Reports (⚠ Prototype)
Demo Seed Data
```

Do not include (still postponed):

```txt
Mobile app frontend
API endpoints
Payment gateway
Full accounting / general ledger
Loan amortization / dividend engine
Inventory / POS
E-voting
Payroll integration
Bank reconciliation
```

---

# 30. Package C Scope (Future)

Package C extends Package B.

Potential additions:

```txt
Mobile API expansion
Push notification foundation (mobile)
Device management
Advanced member segmentation
Campaign targeting
Advanced reporting & analytics
Payment gateway integration if requested
```

Build Package C only after Package B foundation is stable.

---

# Build Priority

Following build order has been completed:

```txt
✓ Auth + layouts
✓ Settings
✓ Roles & permissions
✓ CMS pages + sections + public renderer
✓ Media library
✓ Services
✓ News
✓ Announcements
✓ Posters
✓ Documents/downloads
✓ Members (with import and activation)
✓ Public membership applications
✓ Member portal
✓ Borang Online (dynamic form builder)
✓ Digital membership card
✓ Complaints
✓ Units
✓ Staff & admin users
✓ Caruman / contributions
✓ Financing module (products, applications, guarantors)
✓ Notifications
✓ Audit logs
✓ Demo seed data
```
⚠ Semakan / Review Inbox
⚠ Basic reports (prototype)

Future work:
```txt
- Full roles management UI
- Reports development
- Package C features as requested
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