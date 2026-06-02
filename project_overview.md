# project_overview.md

# KoperasiHub — Project Overview

Product brief for KoperasiHub. Keep short, stable, and product-focused.

## 1. Product Name & Purpose

**KoperasiHub** is a white-label cooperative digital operations platform.

It replaces scattered tools (website, paper forms, spreadsheets) with one integrated system for cooperatives to manage their public web presence, membership operations, member self-service, and digital workflows.

Not built for one specific cooperative. All branding, content, and copywriting are configurable.

---

## 2. Target Users

| User | Description |
|---|---|
| **Super Admin** | Full system access. Manages settings, users, roles. |
| **Admin** | Cooperative staff managing content, members, applications, and daily operations. |
| **Member** | Registered cooperative member accessing portal for self-service. |
| **Public** | Website visitors browsing content, applying for membership, or viewing forms directory. |

---

## 3. Tech Stack

| Layer | Technology |
|---|---|
| Backend | Laravel 13 |
| Frontend | Vue 3 (Composition API, `<script setup>`) |
| Bridge | Inertia 3 |
| Styling | Tailwind CSS |
| UI Library | shadcn-vue / reka-ui |
| Icons | lucide-vue-next |
| Auth | Laravel session-based web authentication |
| Roles/Permissions | spatie/laravel-permission |
| Audit Logs | spatie/laravel-activitylog |
| Database | SQLite (local dev/demo); MySQL/PostgreSQL-ready for production |

---

## 4. Current Scope (Package B)

The platform is web-only with three distinct areas:

```
/           Public website
/admin      Admin panel
/member     Member portal
```

### Implemented Modules

| Module | Status |
|---|---|
| Public Website & Section-based CMS | ✓ Complete — 14 section types, dynamic pages |
| Admin Panel | ✓ Complete — custom-built, not Filament |
| Settings & White-Label Branding | ✓ Complete — name, logo, colors, contact, social |
| Services Management | ✓ Complete |
| News & Announcements | ✓ Complete — audience targeting (public/members/admins) |
| Documents & Downloads | ✓ Complete — visibility control per document |
| Posters / Banner Gallery | ✓ Complete |
| Media Library | ✓ Complete |
| Membership Applications (Public) | ✓ Complete — apply, review, approve/reject, convert to member |
| Members Management | ✓ Complete — CRUD, status, import CSV/Excel |
| Member Portal | ✓ Complete — dashboard, profile, documents, announcements |
| Digital Membership Card | ✓ Complete — QR verification, download/share |
| Online Forms / Borang Online | ✓ Complete — dynamic form builder, sections, fields, templates, submissions |
| Financing Module | ✓ Complete — product catalog, dynamic forms, applications, guarantor management |
| Complaints / Suggestions | ✓ Complete — ticketing workflow with replies |
| Units / Department Management | ✓ Complete |
| Staff & Admin User Management | ✓ Complete |
| Member Contributions (Caruman) | ✓ Complete — view and manage |
| Member Import & Account Activation | ✓ Complete — CSV import, portal activation flow |
| Review Inbox (Semakan) | ✓ Complete — unified pending review dashboard |
| Notifications | ✓ Complete — in-app database notifications |
| Audit Logs | ✓ Complete — view and filter |
| Roles & Permissions | ✓ Complete — 39 permissions, 3 roles (super_admin, admin, member) |
| Reports | ⚠ Prototype — basic summary page only |
| Roles Management UI | ⚠ Prototype — placeholder page only |

---

## 5. Postponed / Out of Scope (Not Built)

These are intentionally excluded unless explicitly requested later:

- Full accounting / general ledger
- Loan amortization / dividend engine
- Payment gateway / POS / inventory
- E-voting
- Native mobile app
- Mobile API endpoints
- Payroll integration
- Bank reconciliation
- AI credit scoring
- Marketplace / e-commerce
- Push notifications (SMS/email broadcast)

---

## 6. Key Technical Decisions

| Decision | Rationale |
|---|---|
| **White-label from day one** | Cooperative name, logo, colors, content all in database settings. No hardcoded identity. |
| **Section-based CMS, not page builder** | Developer controls design; admin fills content. No raw CSS/JS editing. |
| **Custom admin panel, not Filament** | Full control over UX and white-label look. |
| **Three-area separation** | Public, Admin, Member each have separate controllers, routes, and Vue pages. |
| **Single-tenant per installation** | MVP serves one cooperative per deployment. No SaaS multi-tenancy yet. |
| **Service layer for business logic** | Non-trivial workflows extracted into Service classes. |
| **Backend authorization enforced** | Permissions on routes + policies + super admin gate. Frontend is secondary. |
| **Dynamic form builder pattern** | Borang Online and Financing both use section/field/submission architecture. |
| **Audit logging on sensitive actions** | Membership changes, settings updates, publishing actions all logged. |

---

## 7. Product Principle

```
Simple enough for cooperative staff.
Powerful enough for cooperative operations.
Flexible enough for multiple cooperatives.
Structured enough to avoid messy customization.
```

---

## 8. Language Policy

UI copywriting uses **Bahasa Malaysia** by default. Technical naming stays in English.

English terms used where BM sounds awkward or overly formal.

Professional tone suitable for cooperative staff and older users. No slang, no emojis.