# project_overview.md

# KoperasiHub — Project Overview

This document is the high-level product brief for KoperasiHub. Keep it short, stable, and product-focused.

For implementation details, refer only to the relevant document when needed:

```txt
AGENTS.md
project_overview.md
/docs/database_schema.md
/docs/module_spec.md
/docs/cms_section_spec.md
/docs/ui_ux_guidelines.md
/docs/development_roadmap.md
```

Optional future docs may be added later, but they are not required to start:

```txt
/docs/api_spec.md
/docs/roles_permissions.md
/docs/seed_data.md
```

---

## 1. Product Name

**KoperasiHub**

KoperasiHub is a white-label digital platform for cooperatives.

It is not built for one specific cooperative. All names, logos, colors, content, contact details, services, and branding must be configurable.

Use dummy cooperative content during development.

Example dummy names:

```txt
Koperasi Demo Berhad
Koperasi Wawasan Berhad
Koperasi Sejahtera Berhad
```

---

## 2. Product Vision

KoperasiHub helps cooperatives modernize their digital operations through one integrated platform.

The platform combines:

1. Public website
2. Section-based CMS
3. Custom admin panel
4. Membership management
5. Member portal
6. Announcements and documents
7. Complaint/enquiry management
8. API-ready backend for future mobile apps

Long-term, KoperasiHub should be reusable as a product that can be deployed or customized for multiple cooperatives.

---

## 3. Product Positioning

KoperasiHub is not only a corporate website.

KoperasiHub is a **cooperative digital operations platform**.

It should allow a cooperative to:

- Manage its website content without a developer
- Manage members and membership applications
- Publish announcements and downloadable documents
- Provide a self-service member portal
- Manage enquiries, complaints, and service requests
- Prepare for future mobile app integration
- Keep branding and content white-label

---

## 4. Target Users

### 4.1 Platform Owner

The person or company operating KoperasiHub.

Future responsibilities may include:

- Manage multiple cooperative tenants
- Configure tenant branding
- Control package access
- Monitor platform usage

For the first version, full SaaS multi-tenancy can be simplified, but the architecture must not block it.

---

### 4.2 Cooperative Admin

Internal staff managing the cooperative platform.

Main responsibilities:

- Manage website pages and content
- Manage CMS sections
- Manage members
- Review membership applications
- Publish announcements
- Upload documents
- Manage services
- Respond to complaints/enquiries
- Configure cooperative settings

---

### 4.3 Management Viewer

Management or board-level users who need visibility but limited editing access.

Main responsibilities:

- View dashboards
- View reports
- View member statistics
- View application statistics
- Export selected data if permitted

---

### 4.4 Member

A registered cooperative member.

Main responsibilities:

- Log in to member portal
- View membership profile
- Update allowed profile details
- View membership status
- Read member announcements
- Download member documents
- Submit complaints or enquiries
- Track request/application status

---

### 4.5 Public Visitor

Anyone visiting the public website.

Main responsibilities:

- Learn about the cooperative
- View services
- Read public announcements
- Download public documents/forms
- Submit membership application or enquiry
- Contact the cooperative

---

## 5. Core Areas

KoperasiHub has four main areas.

```txt
/           Public website
/admin      Custom admin panel
/member     Member portal
/api/v1     API endpoints for future mobile apps
```

---

## 6. Tech Stack

Use this stack unless explicitly changed:

```txt
Backend: Laravel
Frontend: Vue 3
Bridge: Inertia.js
Styling: Tailwind CSS
UI: shadcn-vue / reka-ui
Auth/API: Laravel Sanctum
Database: SQLite for local demo/development; MySQL or PostgreSQL for staging/production later
```

Recommended Laravel packages:

```txt
spatie/laravel-permission
spatie/laravel-activitylog
spatie/laravel-medialibrary
laravel/sanctum
```

Do not use these for the main product unless explicitly requested:

```txt
WordPress
Filament
Elementor-style page builder
```

---

## 7. Product Architecture

Use a Laravel monolith with Inertia-powered web screens.

Keep these areas separated:

```txt
Public website
Admin panel
Member portal
API layer
```

Recommended frontend folder structure:

```txt
resources/js/
├── Public/
├── Admin/
├── Member/
└── Shared/
```

Recommended route structure:

```txt
routes/web.php       Public routes
routes/admin.php     Admin routes
routes/member.php    Member portal routes
routes/api.php       API routes
```

---

## 8. White-Label Requirement

KoperasiHub must be reusable for many cooperatives.

Do not hardcode:

- Cooperative name
- Logo
- Favicon
- Registration number
- Address
- Email
- Phone number
- WhatsApp number
- Social media links
- Brand colors
- Homepage text
- Footer text
- Services
- Business units
- Membership terms

These must come from settings, CMS content, or seed data.

---

## 9. Language Policy

Product UI and copywriting should primarily use Bahasa Malaysia because many cooperative staff and members are more comfortable with clear local wording.

User-facing copy should use Bahasa Malaysia for:

- Public website content and copywriting
- Admin panel labels and actions
- Member portal labels and actions
- Form labels and helper text
- Buttons
- Validation messages
- Empty states
- Dashboard copy
- Toasts and confirmation messages

English terms may be used when the Bahasa Malaysia translation sounds awkward, uncommon, or overly formal.

Technical and internal naming must remain in English, including code identifiers, database names, route names, model names, component names, API keys, and package/library names.

The writing tone should be professional, clear, and suitable for cooperative clients with older users.
Avoid slang, emojis, and overly casual wording.

---

## 10. CMS Concept

Use a **section-based CMS**.

The developer controls the design system. The admin controls the content.

Admins may edit:

- Text
- Images
- Buttons
- Links
- Visibility
- Ordering
- Predefined variants

Admins should not directly edit:

- Raw CSS
- Arbitrary JavaScript
- Uncontrolled HTML layouts
- Responsive structure

Example page structure:

```txt
Page
└── Sections
    ├── Hero
    ├── Stats
    ├── Services
    ├── Announcements
    ├── FAQ
    └── CTA
```

Detailed CMS section rules belong in:

```txt
/docs/cms_section_spec.md
```

---

## 11. Admin Panel Scope

The admin panel is custom-built with Vue 3 + Inertia.

Core admin modules:

```txt
Dashboard
CMS Pages
Page Sections
Media Library
Services
Announcements
Documents
Members
Membership Applications
Complaints / Enquiries
Settings
Users
Roles & Permissions
Audit Logs
Reports
```

The admin panel must support:

- Search
- Filters
- Pagination
- Status badges
- Form validation
- Role-based navigation
- Confirmation dialogs
- Toast notifications
- Loading and empty states

---

## 12. Member Portal Scope

The member portal should be simple, clean, and mobile-friendly.

Core member modules:

```txt
Dashboard
Profile
Membership Status
Announcements
Documents
Applications / Requests
Complaints / Enquiries
Settings
```

Future member modules may include:

```txt
Digital member card
QR member ID
Statement downloads
Share/savings summary
Financing application status
Payment history
Dividend summary
```

---

## 13. API-Ready Backend

The backend must be designed so a mobile app can be added later.

Use versioned API routes:

```txt
/api/v1
```

Possible future API areas:

```txt
/api/v1/auth
/api/v1/member
/api/v1/announcements
/api/v1/documents
/api/v1/applications
/api/v1/complaints
/api/v1/settings
```

Do not build the mobile app frontend in the base version unless explicitly requested.

---

## 14. Package B Scope

Package B is the main web platform.

It includes:

```txt
Public website
Section-based CMS
Custom admin panel
Member portal
Membership management
Membership application workflow
Announcements
Documents/downloads
Complaints/enquiries
Settings
Roles and permissions
Audit logs
API-ready foundation
```

Package B does not include a mobile app frontend.

---

## 15. Package C Scope

Package C extends Package B.

Possible additions:

```txt
Mobile app API support
Digital member card
QR member ID
Push notification foundation
Advanced reporting
Member segmentation
Campaign targeting
Payment gateway integration
Advanced document delivery
Device management
```

Package C should build on Package B, not duplicate it.

---

## 16. Out of Scope Unless Requested

Do not build these unless explicitly requested:

```txt
Full accounting system
General ledger
Payroll integration
Bank reconciliation
Inventory/POS system
E-voting
Dividend calculation engine
Loan amortization engine
Payment gateway
Native mobile app
Marketplace/e-commerce
AI credit scoring
```

The architecture may allow these later, but they are not part of the default build.

---

## 17. Product Principle

Build KoperasiHub with this principle:

```txt
Simple enough for cooperative staff.
Powerful enough for cooperative operations.
Flexible enough for multiple cooperatives.
Structured enough to avoid messy customization.
```

Do not build a chaotic page builder.
Do not build a rigid static website.
Build a controlled, section-based, white-label cooperative platform.
