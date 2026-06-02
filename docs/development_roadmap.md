# development_roadmap.md

# KoperasiHub Development Roadmap

Purpose: define the recommended build order for KoperasiHub.

Use this file to keep Codex and developers focused on one phase at a time.

Rules:

- Do not skip dependencies.
- Do not build future phases early.
- Keep each phase small and reviewable.
- Build working foundations before advanced features.
- Prefer complete MVP workflows over partial advanced modules.
- Use SQLite for demo development unless changed later.
- Keep the product white-label and dummy-data friendly.
- Lock MVP as a single-tenant web application installed separately per cooperative.
- Do not build API/mobile scope in the current MVP.

---

## Phase 0 — Project Setup

Goal:
Create the base Laravel + Vue + Inertia project structure.

Build:
- Laravel project setup
- Vue 3 setup
- Inertia.js setup
- Tailwind CSS setup
- shadcn-vue setup
- SQLite database config
- Base folder structure
- Route files for public, admin, and member
- Basic app config
- Demo `.env.example`

Deliverables:
- App runs locally
- SQLite database works
- Frontend assets compile
- Public homepage route loads
- Admin and member route groups exist

Depends on:
- None

Read:
- AGENTS.md
- project_overview.md
- ui_ux_guidelines.md

Do not build:
- Full CMS
- Member module
- Mobile app
- Payment
- Accounting

Done when:
- `php artisan serve` works
- `npm run dev` works
- `/`, `/admin`, and `/member` can render placeholder pages

---

## Phase 1 — Authentication & Base Layouts

Goal:
Create authentication foundation and separate layouts.

Build:
- Login/logout foundation
- Admin login page
- Member login page
- Public layout
- Admin layout
- Member layout
- Basic route protection
- Role-aware redirect after login
- Basic user seeder

Deliverables:
- Admin can log in
- Member can log in
- Guest can view public pages
- Admin cannot access member-only pages as a member unless linked
- Member cannot access admin pages

Depends on:
- Phase 0

Read:
- AGENTS.md
- module_spec.md
- ui_ux_guidelines.md

Do not build:
- Full roles UI
- CMS editor
- Membership approval
- API login
- Password reset unless easy from starter kit

Done when:
- Admin login works
- Member login works
- Protected routes redirect correctly
- Layouts are visually separated

---

## Phase 2 — Roles, Permissions & Navigation

Goal:
Add role-based access control and clean navigation.

Build:
- Role and permission models/package setup
- Default roles
- Default permissions
- Role middleware
- Permission checks
- Admin sidebar navigation
- Member navigation
- Role-aware menu visibility
- Seed default roles and users

Default roles:
- super_admin
- admin
- member

Deliverables:
- Admin menu changes based on permissions
- Backend routes enforce permissions
- Seeded users have correct access

Depends on:
- Phase 1

Read:
- module_spec.md
- database_schema.md

Do not build:
- Full role management UI unless required
- Audit log UI
- Advanced user management

Done when:
- Unauthorized users cannot access restricted admin pages
- Navigation hides modules without permission
- Backend authorization works even if frontend is bypassed

---

## Phase 3 — Settings Foundation

Goal:
Create white-label cooperative settings.

Build:
- Settings table/model
- Settings service/helper
- Brand settings
- Contact settings
- Social link settings
- Basic admin settings page
- Settings cache strategy
- Demo cooperative settings seeder

Settings groups:
- brand
- contact
- social
- seo
- system

Deliverables:
- App can display cooperative name/logo/contact from database
- Public layout uses settings
- Admin can edit basic settings
- Settings are not hardcoded

Depends on:
- Phase 2

Read:
- database_schema.md
- module_spec.md
- ui_ux_guidelines.md

Do not build:
- Multi-tenant billing
- Theme builder
- Arbitrary CSS editor
- Advanced white-label domains

Done when:
- Changing settings updates public/admin UI
- Dummy cooperative data is loaded from seeders
- No cooperative identity is hardcoded in layout components

---

## Phase 4 — CMS Foundation

Goal:
Create the database and backend foundation for section-based CMS.

Build:
- Pages table/model
- Page sections table/model
- Section type constants/enums
- Page CRUD backend
- Page section CRUD backend
- Section ordering
- Section visibility
- Basic publishing status
- Slug handling
- SEO fields
- Demo homepage seed data

Deliverables:
- Pages can be created
- Sections can be attached to pages
- Sections can store `data` and `settings`
- Sections can be ordered and activated/deactivated
- Demo homepage content exists

Depends on:
- Phase 3

Read:
- database_schema.md
- cms_section_spec.md
- module_spec.md

Do not build:
- Live drag-drop page builder
- Raw CSS editor
- Raw JavaScript editor
- Complex revision history
- Multi-language CMS unless requested

Done when:
- CMS records can be created through backend/admin placeholder
- Demo homepage sections exist in SQLite
- Section data structure follows `cms_section_spec.md`

---

## Phase 5 — Public Website Renderer

Goal:
Render public website pages from CMS sections.

Build:
- Public page controller
- Dynamic route by slug
- Homepage renderer
- Section component map
- Core public section components
- Public navbar and footer
- 404 fallback
- Published-only rendering
- Basic SEO metadata rendering

Core sections:
- hero
- stats
- feature_grid
- service_grid
- announcement_list
- cta_banner
- faq
- contact_block
- download_list
- image_text

Deliverables:
- `/` renders dynamic homepage from database
- Published pages render by slug
- Hidden sections are not shown
- Unknown section types fail safely
- Public UI follows design guidelines

Depends on:
- Phase 4

Read:
- cms_section_spec.md
- ui_ux_guidelines.md

Do not build:
- Admin CMS editor UI
- Live preview
- Full content versioning
- Complex page builder

Done when:
- Public site can be changed by changing CMS database records
- Homepage looks demo-ready
- Website is responsive

---

## Phase 6 — Admin CMS Editor

Goal:
Build custom admin UI to manage pages and sections.

Build:
- Admin pages list
- Create/edit page form
- Page status controls
- Page sections list
- Add section
- Edit section fields
- Reorder sections
- Hide/show section
- Delete section
- Basic section variant/settings controls
- Toasts, validation, empty states

Deliverables:
- Admin can manage homepage content
- Admin can add and edit predefined section types
- Admin can reorder sections
- Admin cannot edit raw CSS/JS
- Admin CMS UI is clean and controlled

Depends on:
- Phase 5

Read:
- cms_section_spec.md
- ui_ux_guidelines.md
- module_spec.md

Do not build:
- Full Elementor-style builder
- Arbitrary HTML blocks
- Advanced media library unless needed
- Complex live preview

Done when:
- Admin can update homepage content from UI
- Public website reflects saved CMS changes
- CMS editor remains structured and safe

---

## Phase 7 — Media & Documents Foundation

Goal:
Add file handling for public media and downloadable documents.

Build:
- Media upload foundation
- Public media handling
- Document records
- Document categories
- Document visibility
- Admin document management
- Download routes
- File validation
- Demo downloads seeder

Document visibility:
- public
- members_only
- admin_only
- specific_member

Deliverables:
- Admin can upload website images/documents
- Public documents can be downloaded
- Private documents are protected
- Download centre can render documents

Depends on:
- Phase 6

Read:
- database_schema.md
- module_spec.md

Do not build:
- Advanced versioning
- Large DAM system
- OCR
- E-signature
- Payment-protected files

Done when:
- Documents can be uploaded and categorized
- Public download list works
- Private documents are not publicly accessible

---

## Phase 8 — Announcements, Services & Public Forms Directory

Goal:
Build common public/member content modules and public form discovery.

Build:
- Announcements module
- Services module
- Public Borang Online directory/listing
- Categories if needed
- Admin CRUD
- Public listing/detail
- Member-only visibility support
- Pinned announcements
- Expiry date support
- Demo seed data

Deliverables:
- Admin can manage announcements
- Admin can manage services
- Public can view public announcements/services/forms
- Member portal can later show member-only announcements

Depends on:
- Phase 6

Read:
- database_schema.md
- module_spec.md
- ui_ux_guidelines.md

Do not build:
- Email broadcast
- Push notifications
- Campaign engine
- Advanced segmentation

Done when:
- Public announcement/service/form sections can display real records
- Admin CRUD works
- Visibility/status rules work

---

## Phase 9 — Public Membership Application

Goal:
Create membership application workflow.

Build:
- Membership application model
- Public membership application form
- Admin application list
- Admin application detail
- Review workflow
- Approve action
- Reject action with reason
- Application status tracking
- Basic document upload support
- Audit log hook if available

Statuses:
- pending
- under_review
- approved
- rejected
- cancelled

Deliverables:
- Applicant can submit application
- Admin can review application
- Admin can approve/reject application
- Approved application can create/link member record
- Rejection stores reason

Depends on:
- Phase 7
- Phase 8 optional

Read:
- database_schema.md
- module_spec.md

Do not build:
- Payment for membership fee
- E-KYC verification
- Digital signature
- Complex approval chains
- Loan application
- Member `Permohonan` forms

Done when:
- End-to-end membership application workflow works
- Admin can convert approved application into member
- Status history is clear enough for demo

---

## Phase 10 — Members Module

Goal:
Allow admins to manage member records.

Build:
- Members list
- Member detail page
- Create/edit member
- Member status management
- Link member to user account
- Basic profile fields
- Admin notes if needed
- Member document relationship
- Demo members seeder

Member statuses:
- active
- inactive
- suspended

Deliverables:
- Admin can view/search/filter members
- Admin can manage member profiles
- Admin can change member status
- Member records connect to user accounts

Depends on:
- Phase 9

Read:
- database_schema.md
- module_spec.md

Do not build:
- Dividend calculation
- Share capital ledger
- Loan ledger
- Payment history
- Accounting system

Done when:
- Members module supports demo-ready member management
- Approved applications can become members
- Member data is protected by permissions

---

## Phase 11 — Member Portal

Goal:
Create member self-service portal.

Build:
- Member dashboard
- Member profile view/edit
- Member `Permohonan` list/detail/status
- Member documents page
- Member announcements page
- Digital membership card page
- Member complaints page placeholder or basic
- Quick actions
- Member-friendly layout

Deliverables:
- Member can log in and view own dashboard
- Member can view own profile
- Member can submit and track `Permohonan`
- Member can view own documents
- Member can view member-only announcements
- Member can access digital card with QR verification and download/share actions
- Member cannot access another member data

Depends on:
- Phase 10
- Phase 7
- Phase 8

Read:
- module_spec.md
- ui_ux_guidelines.md

Do not build:
- Financial ledger
- Online payment
- Loan amortization
- Mobile app frontend
- Push notification

Done when:
- Member portal is usable on mobile
- Member sees only own data
- Demo member account looks complete

---

## Phase 11A — Borang Online & Member Permohonan

Goal:
Build the structured online form system for member submissions.

Build:
- Borang Online categories and units
- Form sections and fields
- Member submission flow under unified `Permohonan`
- Print preview
- Signature capture
- Agreement/acknowledgement block
- Office use box
- Hybrid submission method support

Deliverables:
- Admin can manage structured online forms
- Members submit forms through `Permohonan`
- Print-friendly preview exists for operational use
- Paper/hybrid handling is supported where needed

Depends on:
- Phase 11

Read:
- module_spec.md
- ui_ux_guidelines.md

Do not build:
- General-purpose workflow engine
- Native mobile submission app
- OCR
- External signing platform integration

Done when:
- Member `Permohonan` supports structured form submissions end-to-end
- Required form metadata and signature/agreement/office-use areas are represented clearly

---

## Phase 12 — Complaints / Suggestions

Goal:
Add support ticket style complaint/suggestion workflow.

Build:
- Complaint model
- Member submit form
- Member complaint list/detail
- Admin complaint list/detail
- Status update
- Admin reply
- Internal note optional
- Basic categories

Statuses:
- open
- in_progress
- resolved
- closed

Deliverables:
- Member can submit complaint/suggestion
- Admin/support can reply
- Member can view replies
- Admin can close complaint

Depends on:
- Phase 11

Read:
- database_schema.md
- module_spec.md

Do not build:
- SLA engine
- Email piping
- Chat system
- WhatsApp integration

Done when:
- Complaint workflow works end-to-end
- Member/admin visibility rules are correct

---

## Phase 13 — Audit Logs

Goal:
Track sensitive admin actions.

Build:
- Audit/activity log foundation
- Log important admin actions
- Admin audit log viewer
- Filters by actor/action/module/date
- Read-only audit log UI

Log actions:
- settings_updated
- page_published
- section_updated
- member_created
- member_updated
- member_status_changed
- application_approved
- application_rejected
- document_uploaded
- document_deleted
- user_role_updated

Deliverables:
- Important actions are logged
- Authorized admins can view logs
- Logs cannot be edited through UI

Depends on:
- Phase 2
- Phase 3 onwards

Read:
- module_spec.md
- database_schema.md

Do not build:
- Complex compliance reporting
- Export unless easy
- Tamper-proof external log storage

Done when:
- Demo actions appear in audit log
- Audit UI is searchable/filterable enough for admin

---

## Phase 14 — Demo Data & Presentation Polish

Goal:
Make the platform demo-ready for client presentation.

Build:
- High-quality dummy cooperative profile
- Demo homepage content
- Demo services
- Demo announcements
- Demo documents
- Demo members
- Demo applications
- Demo complaints
- Demo dashboard stats
- UI polish pass
- Empty/loading/error states
- Mobile responsiveness check

Deliverables:
- App looks realistic with SQLite demo data
- Admin demo account works
- Member demo account works
- Public website looks professional
- Core flows are presentable

Depends on:
- Phase 13

Read:
- ui_ux_guidelines.md
- project_overview.md

Do not build:
- Real client-specific content
- Real personal data
- Production deployment automation
- Advanced modules outside MVP

Done when:
- Project can be zipped/shared with SQLite demo database
- Demo can be run locally with minimal setup
- UI looks credible for cooperative clients

---

## Phase 14A — Ansuran Mudah Module

Goal:
Build installment-based product purchase system for members.

Build:
- Product categories (ansuran_categories)
- Products with image gallery (ansuran_products, ansuran_product_images)
- Product variants with SKU, price, stock (ansuran_product_variants)
- Installment tenure options with interest rates (ansuran_tenure_options)
- Agreement templates with placeholder system (ansuran_agreement_templates)
- Member applications with full lifecycle (ansuran_applications)
- Application history tracking (ansuran_application_histories)
- Monthly payment schedule and tracking (ansuran_application_payments)
- Guarantor system (ansuran_application_guarantors)
- Public product catalog
- Member catalog with apply flow
- Installment calculator
- Admin CRUD for all entities
- Admin application review workflow
- Auto-generate agreement from template
- Digital agreement signing (canvas)
- Delivery/pickup fulfillment tracking
- Email + in-app notifications (9 events)
- Confirmation modal after submission

Key features:
- Products organized by category with image gallery
- Multiple variants per product with attributes
- Configurable down payment (admin minimum %, member can pay more)
- Flat interest rate per tenure, admin-configurable
- Guarantor: 0, 1, or 2 required per product, admin-configurable
- Full status lifecycle: pending_guarantor → pending → under_review → approved → agreement_generated → signed → processing → completed
- Member self-service tracking
- Payment recording (manual by admin in MVP)
- 9 notification events

Depends on:
- Phase 14

Read:
- database_schema.md
- module_spec.md
- ui_ux_guidelines.md

Do not build:
- Payment gateway integration
- Cart / multiple products per application
- Complex interest calculations beyond flat rate
- Push notification (Package C)

Done when:
- Members can browse and apply for installment purchases
- Admin can manage full lifecycle from review to fulfillment
- Notifications work end-to-end
- Demo data is realistic

---

## Phase 15 — Future Package C Extensions

Goal:
Add optional advanced features after Package B is stable.

Possible features:
- Mobile device registration
- Push notification foundation
- Advanced dashboard reporting
- Campaign targeting
- Member segmentation
- Financing application workflow
- Payment gateway integration
- API expansion

Depends on:
- Package B complete
- Client/demo feedback

Read:
- module_spec.md
- database_schema.md
- ui_ux_guidelines.md

Do not build before:
- Public website works
- Admin CMS works
- Member module works
- Member portal works
- Demo data is polished

Done when:
- Selected Package C features are implemented without breaking Package B

---

## Recommended Codex Prompts

Use focused prompts.

Example:

```txt
Read AGENTS.md and development_roadmap.md.
Implement Phase 0 only.
Do not start Phase 1.
```

Example:

```txt
Read AGENTS.md, database_schema.md, and cms_section_spec.md.
Implement Phase 4 only.
Create migrations, models, factories, seeders, and basic controllers.
Do not build UI yet.
```

Example:

```txt
Read AGENTS.md, cms_section_spec.md, and ui_ux_guidelines.md.
Implement Phase 5 public website renderer.
Do not build admin CMS editor.
```

Example:

```txt
Continue Phase 10 from development_roadmap.md.
Use database_schema.md and module_spec.md.
Keep the UI consistent with ui_ux_guidelines.md.
```

---

## MVP Completion Definition

The MVP is considered complete when Phase 0 to Phase 14 are done.

Minimum demo-ready capabilities:

```txt
Public website rendered from CMS
Admin can edit CMS content
Admin can manage announcements/services/documents
Admin can manage membership applications
Admin can manage members
Member can log in to portal
Member can view own profile/documents/announcements
Member can submit complaint
SQLite demo data is realistic
UI is responsive and professional
```

Out of MVP unless selected later:

```txt
Full accounting
Loan ledger
Payment gateway
Dividend engine
Inventory/POS
E-voting
Native mobile app
Advanced financing workflow
```

---

## Roadmap Principle

Build in this order:

```txt
Foundation first.
CMS second.
Membership third.
Member portal fourth.
Demo polish before advanced features.
```