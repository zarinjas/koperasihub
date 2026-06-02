# AGENTS.md

# KoperasiHub — Codex Agent Instructions

KoperasiHub is a white-label cooperative management platform.

Primary goal of this file:

1. Keep Codex aligned with the product direction.
2. Reduce hallucination.
3. Save tokens by avoiding unnecessary full-context loading.
4. Tell Codex which documentation to read only when relevant.

Do not load every documentation file for every task.
Read only the files that are relevant to the requested change.

---

## 1. Product Summary

KoperasiHub is a reusable platform for multiple cooperatives.

Current MVP direction:

- Single-tenant web application per cooperative installation
- Separate deployment per cooperative
- No full SaaS multi-tenancy in MVP
- Web app only for now
- No active API/mobile scope in MVP

It includes:

- Public website
- Section-based CMS
- Custom admin panel
- Member portal
- Membership management
- Public membership application workflow
- Unified member `Permohonan` workflow for online form submissions
- Borang Online management
- Announcements
- Documents/downloads
- Digital membership card
- Complaints/suggestions
- Settings and white-label branding
- Audit logging for sensitive web admin actions

The project is not built for one specific cooperative only.
All names, logos, colors, copywriting, contact details, and business content must be configurable or seeded as dummy demo data.

---

## 2. Tech Stack

Use:

- Laravel
- Vue 3
- Inertia.js
- Tailwind CSS
- shadcn-vue
- SQLite for local demo/development
- MySQL or PostgreSQL only as a future production option after client confirmation
- Local storage for uploaded files/documents in MVP

Do not use Filament unless explicitly requested.

Build a custom admin panel.

---

## 3. Application Areas

The app has three active MVP areas:

```txt
/              Public website
/admin         Custom admin panel
/member        Member portal
```

Keep Public, Admin, and Member concerns separated.
API/mobile structure can be introduced later if future scope is approved.

Suggested frontend structure:

```txt
resources/js/
├── Public/
├── Admin/
├── Member/
└── Shared/
```

Suggested route files:

```txt
routes/web.php
routes/admin.php
routes/member.php
```

---

## 4. Core Rules

Always follow these rules:

- Keep the platform white-label.
- Do not hardcode cooperative-specific content.
- Use dummy content only in seeders/demo data.
- Use Vue 3 Composition API with `<script setup>`.
- Use Inertia for web pages.
- Use Tailwind and shadcn-vue for UI.
- Use Laravel Form Requests for validation.
- Use Policies/Gates for authorization.
- Use service classes for business logic when workflows are more than simple CRUD.
- Do not expose sensitive member data publicly.
- Enforce permissions on the backend, not only in the frontend.
- Keep code modular, readable, and maintainable.
- Keep only these active MVP roles: `super_admin`, `admin`, `member`.
- Treat `cms_manager`, `membership_manager`, and `support_staff` as future roles, not MVP.
- Keep public membership application separate from member `Permohonan`.
- Treat `Dokumen` as download/reference content, not as the main application submission flow.

---

## 5. White-Label Rules

Never hardcode:

- Cooperative name
- Logo
- Registration number
- Address
- Phone number
- Email
- WhatsApp number
- Brand colors
- Social media links
- Homepage content
- Footer content
- Service names
- Membership terms

Use database settings, CMS content, or seed data.

Acceptable dummy names:

- Koperasi Demo Berhad
- Koperasi Wawasan Berhad
- Koperasi Sejahtera Berhad
- KoperasiHub Demo

---

## 6. Language Policy

Product UI and copywriting should primarily use Bahasa Malaysia across the user-facing system.

Use Bahasa Malaysia for:

- Public website copywriting
- Admin UI labels
- Member portal labels
- Form labels
- Buttons
- Validation messages
- Empty states
- Dashboard copy
- Toasts and confirmation messages

English terms are allowed when the Bahasa Malaysia translation sounds awkward, uncommon, or overly formal.

Keep technical and internal naming in English, including:

- Code identifiers
- Database table names
- Route names
- Model names
- Component names
- API keys
- Package and library names

UI copy should sound professional, clear, and suitable for cooperative clients, including older users.
Avoid slang, emojis, and overly casual wording.

---

## 7. CMS Direction

The CMS is section-based.

Admin can manage:

- Pages
- Page sections
- Text
- Images
- Buttons
- Links
- SEO metadata
- Services
- Announcements
- FAQs
- Downloads
- Forms directory / Borang Online listings
- Contact details
- Section visibility
- Section order
- Predefined section variants

Admin must not freely edit raw CSS, arbitrary JavaScript, or uncontrolled page structure.

Principle:

```txt
Developer controls design system.
Admin controls content.
Admin can choose predefined variants.
```

---

## 8. Documentation Loading Rules

To save tokens, do not read all docs automatically.

Use this guide:

### Always start with
Read:

```txt
AGENTS.md
```

### For general product direction
Read:

```txt
project_overview.md
```

### For database/migration/model work
Read:

```txt
docs/database_schema.md
```

### For CMS/page builder/public website sections
Read:

```txt
docs/cms_section_spec.md
```

### For feature/module implementation
Read:

```txt
docs/module_spec.md
```

### For admin/member/public UI work
Read:

```txt
docs/ui_ux_guidelines.md
```

### For implementation order
Read:

```txt
docs/development_roadmap.md
```

### Optional future docs
Only read these if they exist and the task explicitly needs them:

```txt
docs/api_spec.md
docs/roles_permissions.md
docs/seed_data.md
```

If an optional future document does not exist, do not block implementation. Use AGENTS.md plus the existing relevant docs.

---

## 9. Token-Saving Behavior

When working on a task:

1. Read this file first.
2. Identify the task category.
3. Read only the relevant documentation files.
4. Do not summarize all documentation unless asked.
5. Do not restate product background in every response.
6. Make focused changes only.
7. Avoid large unrelated refactors.
8. Prefer small, reviewable changes.
9. Do not generate excessive explanation unless requested.
10. If context is missing but the implementation is obvious, proceed with a sensible default.

---

## 10. Recommended Build Order

Use this order unless instructed otherwise:

```txt
1. Laravel + Inertia + Vue + Tailwind setup
2. shadcn-vue setup
3. Auth setup
4. Layouts: Public, Admin, Member
5. Roles and permissions
6. Settings foundation
7. CMS pages and sections
8. Public website renderer
9. Admin CMS editor
10. Members module
11. Membership applications
12. Member portal dashboard
13. Announcements
14. Downloads/documents
15. Complaints/suggestions
16. Audit logs
17. Demo seed data
18. Tests
```

---

## 11. Package Scope

### Package B

Core web platform:

- Public website
- Section-based CMS
- Custom admin panel
- Member portal
- Membership management
- Membership applications
- Borang Online / member `Permohonan`
- Announcements
- Documents/downloads
- Digital membership card
- Complaints/suggestions
- Settings
- Roles and permissions
- Audit logs

### Package C

Package B plus future mobile/API-ready and advanced modules:

- Mobile API support
- Push notification foundation
- Advanced reporting
- Member segmentation
- Campaign targeting
- Device management
- Optional payment/financing workflows if requested

Do not build mobile app frontend unless explicitly requested.

---

## 12. Out of Scope Unless Requested

Do not build these unless explicitly requested:

- Full accounting system
- General ledger
- Payroll integration
- Bank reconciliation
- POS system
- Inventory system
- E-voting
- Dividend calculation engine
- Loan amortization engine
- API endpoints
- Native mobile app
- Marketplace/e-commerce
- AI credit scoring

Design the system so these can be added later, but do not implement them by default.

---

## 13. Security Expectations

For protected features:

- Validate input.
- Authorize actions.
- Use role-based permissions.
- Protect private files.
- Do not expose member-sensitive data.
- Audit sensitive admin actions.
- Use rate limiting for login/sensitive endpoints.
- Avoid storing sensitive data in logs.

Sensitive actions should be audit logged.

Examples:

- Membership approval/rejection
- Member profile update by admin
- Role/permission changes
- CMS publishing
- Settings updates
- Document upload/delete

---

## 14. Frontend UX Expectations

UI should be:

- Clean
- Modern
- Professional
- Responsive
- Easy for cooperative staff
- Consistent across modules

Admin pages should support where relevant:

- Search
- Filters
- Sorting
- Pagination
- Status badges
- Empty states
- Loading states
- Form validation errors
- Confirmation dialogs
- Toast notifications

---

## 15. Definition of Done

A task is done when:

- It follows this AGENTS.md.
- It matches the requested scope.
- It is white-label safe.
- It validates input.
- It enforces backend authorization where needed.
- It has clean UI states where relevant.
- It avoids unrelated changes.
- It does not introduce hardcoded cooperative-specific data.
- It remains compatible with future API/mobile expansion where relevant, without implementing it now.

---

## 16. Final Principle

Build KoperasiHub as:

```txt
Simple enough for cooperative staff.
Powerful enough for cooperative operations.
Flexible enough for many cooperatives.
Structured enough to avoid messy customization.
```