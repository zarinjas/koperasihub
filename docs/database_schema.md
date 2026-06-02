# database_schema.md

# KoperasiHub Database Schema

This document defines the initial database schema for KoperasiHub development.

For the demo and presentation build, the project will use **SQLite** because it is simple to ship with dummy data. The schema must still be designed so it can be migrated later to MySQL or PostgreSQL with minimal changes.

KoperasiHub is a white-label cooperative platform with:

- Public website
- Section-based CMS
- Custom admin panel
- Member portal
- Dummy/demo cooperative data
- Audit logging for sensitive admin actions

This document is intentionally focused on database structure only. Product explanation belongs in `project_overview.md`. Coding rules belong in `AGENTS.md`.

---

## 1. Database Strategy

### Development Database

Use SQLite for local development and demo presentation.

Recommended `.env`:

```env
DB_CONNECTION=sqlite
DB_DATABASE=database/database.sqlite
```

The file `database/database.sqlite` may be included in a demo package if needed.

### Future Production Database

The schema should be compatible with:

- MySQL
- PostgreSQL

Avoid SQLite-only assumptions where possible.

### Migration Rules

- Use Laravel migrations.
- Use standard Laravel integer IDs unless UUIDs are explicitly introduced later.
- Use `timestamps()` for most tables.
- Use `softDeletes()` for important business records.
- Use indexes for searchable/filterable columns.
- Use JSON columns carefully. SQLite stores JSON as text, but Laravel can still cast it to array.
- Avoid database-specific raw SQL in migrations.
- Keep names generic and white-label.

---

## 2. Single-Tenant MVP / White-Label Approach

For the MVP, KoperasiHub is a **single-tenant web application** installed separately for each cooperative.
The schema should still include a `cooperatives` table so future expansion remains possible.

Most major records should include:

```txt
cooperative_id
```

This keeps the deployment white-label and makes later expansion easier, without building SaaS multi-tenancy now.

For the first demo, seed only one dummy cooperative.

Example dummy cooperative:

```txt
Koperasi Demo Berhad
```

Do not seed real cooperative data unless explicitly requested.

---

## 3. Core Tables Overview

Initial tables:

```txt
cooperatives
users
members
membership_applications
settings
pages
page_sections
media_files
services
announcements
document_categories
documents
complaints
complaint_replies
audit_logs
```

Recommended package tables may also exist:

```txt
roles
permissions
model_has_roles
model_has_permissions
role_has_permissions
password_reset_tokens
sessions
cache
jobs
failed_jobs
```

Use Laravel and package migrations for these where possible.

---

## 4. Table: cooperatives

Stores white-label cooperative profile and branding.

### Purpose

Used for:

- Cooperative identity
- Logo and brand settings
- Contact information
- Public website footer/header
- Default metadata
- Future multi-cooperative support

### Columns

```txt
id                      integer primary key
name                    string required
short_name              string nullable
registration_no         string nullable
slug                    string unique required
logo_path               string nullable
favicon_path            string nullable
primary_color           string nullable
secondary_color         string nullable
address_line_1          string nullable
address_line_2          string nullable
city                    string nullable
state                   string nullable
postcode                string nullable
country                 string default 'Malaysia'
phone                   string nullable
email                   string nullable
whatsapp                string nullable
website_url             string nullable
facebook_url            string nullable
instagram_url           string nullable
linkedin_url            string nullable
footer_text             text nullable
status                  string default 'active'
created_at              timestamp
updated_at              timestamp
soft_deleted_at         timestamp nullable
```

### Status Values

```txt
active
inactive
suspended
```

### Indexes

```txt
slug
status
```

---

## 5. Table: users

Stores login accounts for admins and members.

Use Laravel default users table as base, extended for KoperasiHub.

### Purpose

Used for:

- Admin login
- Member login
- Role-based access
- Audit actor references

### Columns

```txt
id                      integer primary key
cooperative_id          foreign id nullable -> cooperatives.id
name                    string required
email                   string unique required
email_verified_at       timestamp nullable
password                string required
avatar_path             string nullable
phone                   string nullable
user_type               string default 'member'
status                  string default 'active'
last_login_at           timestamp nullable
remember_token          string nullable
created_at              timestamp
updated_at              timestamp
soft_deleted_at         timestamp nullable
```

### user_type Values

```txt
super_admin
admin
member
```

### status Values

```txt
active
inactive
suspended
```

### Indexes

```txt
cooperative_id
email
user_type
status
```

### Notes

- In the MVP, `super_admin` should still belong to the installed cooperative instance.
- For the demo, create one admin and several member users.
- Roles and permissions should be handled by Spatie Laravel Permission if installed.

---

## 6. Table: members

Stores approved cooperative members.

### Purpose

Used for:

- Member profile
- Member portal
- Membership status
- Future share/savings/financing modules

### Columns

```txt
id                      integer primary key
cooperative_id          foreign id required -> cooperatives.id
user_id                 foreign id nullable -> users.id
member_no               string required
full_name               string required
identity_no             string nullable
email                   string nullable
phone                   string nullable
date_of_birth           date nullable
gender                  string nullable
address_line_1          string nullable
address_line_2          string nullable
city                    string nullable
state                   string nullable
postcode                string nullable
country                 string default 'Malaysia'
occupation              string nullable
employer_name           string nullable
employment_no           string nullable
membership_status       string default 'active'
joined_at               timestamp nullable
approved_at             timestamp nullable
approved_by             foreign id nullable -> users.id
notes                   text nullable
created_at              timestamp
updated_at              timestamp
soft_deleted_at         timestamp nullable
```

### membership_status Values

```txt
active
inactive
suspended
terminated
pending_update
```

### Indexes

```txt
cooperative_id
user_id
member_no
identity_no
email
phone
membership_status
```

### Constraints

- `member_no` should be unique per cooperative.
- If using Laravel validation, enforce uniqueness as `(cooperative_id, member_no)`.
- Avoid relying on compound unique constraints if SQLite compatibility becomes difficult; validation can enforce this during demo.

---

## 7. Table: membership_applications

Stores membership applications before approval.

### Purpose

Used for:

- Online member registration
- Admin review workflow
- Approval/rejection tracking
- Conversion into member record

### Columns

```txt
id                      integer primary key
cooperative_id          foreign id required -> cooperatives.id
application_no          string required
full_name               string required
identity_no             string nullable
email                   string nullable
phone                   string nullable
date_of_birth           date nullable
gender                  string nullable
address_line_1          string nullable
address_line_2          string nullable
city                    string nullable
state                   string nullable
postcode                string nullable
country                 string default 'Malaysia'
occupation              string nullable
employer_name           string nullable
employment_no           string nullable
status                  string default 'pending'
submitted_at            timestamp nullable
reviewed_at             timestamp nullable
reviewed_by             foreign id nullable -> users.id
approved_member_id      foreign id nullable -> members.id
review_notes            text nullable
rejection_reason        text nullable
metadata                json nullable
created_at              timestamp
updated_at              timestamp
soft_deleted_at         timestamp nullable
```

### status Values

```txt
pending
under_review
approved
rejected
cancelled
```

### Indexes

```txt
cooperative_id
application_no
identity_no
email
phone
status
submitted_at
```

### Notes

- Approval should create or link a `members` record.
- Rejection must store `rejection_reason`.
- `metadata` can store additional demo form fields.

---

## 8. Table: settings

Stores configurable system and white-label settings.

### Purpose

Used for:

- Brand settings
- Contact settings
- SEO defaults
- Membership settings
- Notification settings
- System flags

### Columns

```txt
id                      integer primary key
cooperative_id          foreign id nullable -> cooperatives.id
group                   string required
key                     string required
value                   text nullable
type                    string default 'string'
is_public               boolean default false
created_at              timestamp
updated_at              timestamp
```

### type Values

```txt
string
text
boolean
integer
float
json
image
file
color
url
email
```

### Indexes

```txt
cooperative_id
group
key
is_public
```

### Recommended Setting Groups

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

### Notes

- Public website may read only `is_public = true` settings.
- Sensitive settings must not be exposed publicly or to unauthorized members.
- Cache settings where appropriate.

---

## 9. Table: pages

Stores public website pages.

### Purpose

Used for:

- Homepage
- About page
- Service pages
- Contact page
- Landing pages
- Campaign pages

### Columns

```txt
id                      integer primary key
cooperative_id          foreign id required -> cooperatives.id
title                   string required
slug                    string required
template                string default 'default'
summary                 text nullable
status                  string default 'draft'
meta_title              string nullable
meta_description        text nullable
featured_image_path     string nullable
published_at            timestamp nullable
created_by              foreign id nullable -> users.id
updated_by              foreign id nullable -> users.id
created_at              timestamp
updated_at              timestamp
soft_deleted_at         timestamp nullable
```

### status Values

```txt
draft
published
archived
```

### template Values

```txt
default
homepage
landing
service
contact
```

### Indexes

```txt
cooperative_id
slug
status
published_at
```

### Notes

- `slug` should be unique per cooperative.
- Homepage can use slug `home` or `/` routing can resolve the page with template `homepage`.

---

## 10. Table: page_sections

Stores section-based CMS blocks for public pages.

### Purpose

Used for:

- Dynamic landing page sections
- Reorderable content blocks
- Controlled layout variants
- CMS-driven website rendering

### Columns

```txt
id                      integer primary key
cooperative_id          foreign id required -> cooperatives.id
page_id                 foreign id required -> pages.id
type                    string required
name                    string nullable
data                    json nullable
settings                json nullable
sort_order              integer default 0
is_active               boolean default true
created_by              foreign id nullable -> users.id
updated_by              foreign id nullable -> users.id
created_at              timestamp
updated_at              timestamp
soft_deleted_at         timestamp nullable
```

### Common type Values

```txt
hero
stats
feature_grid
service_grid
business_units
announcement_list
cta_banner
faq
contact_block
download_list
image_text
testimonial
```

### Indexes

```txt
cooperative_id
page_id
type
sort_order
is_active
```

### Notes

- `data` stores section content.
- `settings` stores controlled design options such as variant, background, spacing.
- Do not allow arbitrary raw CSS or JavaScript.
- Unknown section types should not break the public page.

---

## 11. Table: media_files

Stores uploaded media metadata.

### Purpose

Used for:

- Website images
- Logos
- Banners
- Document metadata
- Media picker

### Columns

```txt
id                      integer primary key
cooperative_id          foreign id nullable -> cooperatives.id
uploaded_by             foreign id nullable -> users.id
disk                    string default 'public'
path                    string required
original_name           string nullable
file_name               string nullable
mime_type               string nullable
extension               string nullable
size                    integer nullable
visibility              string default 'public'
collection              string nullable
alt_text                string nullable
caption                 text nullable
metadata                json nullable
created_at              timestamp
updated_at              timestamp
soft_deleted_at         timestamp nullable
```

### visibility Values

```txt
public
members_only
admin_only
private
```

### Indexes

```txt
cooperative_id
uploaded_by
visibility
collection
mime_type
```

### Notes

- Public media can use `public` disk.
- Private/member documents should not be served directly from public storage.
- Use local storage in MVP.
- For demo, public storage is acceptable for non-sensitive dummy files.
- S3 or external object storage is future scope only.

---

## 12. Table: services

Stores cooperative services and business units shown on website.

### Purpose

Used for:

- Service listing
- Business unit cards
- Public website service pages
- Admin-managed content

### Columns

```txt
id                      integer primary key
cooperative_id          foreign id required -> cooperatives.id
title                   string required
slug                    string required
category                string nullable
summary                 text nullable
description             text nullable
image_path              string nullable
icon                    string nullable
contact_name            string nullable
contact_phone           string nullable
contact_email           string nullable
whatsapp                string nullable
button_text             string nullable
button_url              string nullable
status                  string default 'draft'
sort_order              integer default 0
is_featured             boolean default false
created_by              foreign id nullable -> users.id
updated_by              foreign id nullable -> users.id
created_at              timestamp
updated_at              timestamp
soft_deleted_at         timestamp nullable
```

### status Values

```txt
draft
published
archived
```

### Suggested category Values

```txt
membership
financing
retail
property
insurance
education
community
other
```

### Indexes

```txt
cooperative_id
slug
category
status
is_featured
sort_order
```

---

## 13. Table: announcements

Stores public and member announcements.

### Purpose

Used for:

- Public news
- Member portal announcements
- Admin notices
- Campaign updates

### Columns

```txt
id                      integer primary key
cooperative_id          foreign id required -> cooperatives.id
title                   string required
slug                    string required
summary                 text nullable
content                 text nullable
image_path              string nullable
audience                string default 'public'
status                  string default 'draft'
is_pinned               boolean default false
published_at            timestamp nullable
expires_at              timestamp nullable
created_by              foreign id nullable -> users.id
updated_by              foreign id nullable -> users.id
created_at              timestamp
updated_at              timestamp
soft_deleted_at         timestamp nullable
```

### audience Values

```txt
public
members
admins
specific_roles
```

### status Values

```txt
draft
published
archived
```

### Indexes

```txt
cooperative_id
slug
audience
status
is_pinned
published_at
expires_at
```

### Notes

- Public announcements appear on website.
- Member announcements appear in member portal.
- Expired announcements should be hidden by default.

---

## 14. Table: document_categories

Stores document/download categories.

### Purpose

Used for grouping:

- Forms
- Policies
- Reports
- Member documents
- Internal documents

### Columns

```txt
id                      integer primary key
cooperative_id          foreign id required -> cooperatives.id
name                    string required
slug                    string required
description             text nullable
sort_order              integer default 0
is_active               boolean default true
created_at              timestamp
updated_at              timestamp
soft_deleted_at         timestamp nullable
```

### Indexes

```txt
cooperative_id
slug
is_active
sort_order
```

---

## 15. Table: documents

Stores downloadable documents and private member files.

### Purpose

Used for:

- Public forms
- Member-only documents
- Member-specific statements
- Admin-only internal documents

### Columns

```txt
id                      integer primary key
cooperative_id          foreign id required -> cooperatives.id
document_category_id    foreign id nullable -> document_categories.id
member_id               foreign id nullable -> members.id
uploaded_by             foreign id nullable -> users.id
title                   string required
slug                    string nullable
description             text nullable
file_path               string required
file_name               string nullable
mime_type               string nullable
file_size               integer nullable
visibility              string default 'public'
status                  string default 'published'
version                 string nullable
published_at            timestamp nullable
expires_at              timestamp nullable
created_at              timestamp
updated_at              timestamp
soft_deleted_at         timestamp nullable
```

### visibility Values

```txt
public
members_only
admin_only
specific_member
```

### status Values

```txt
draft
published
archived
expired
```

### Indexes

```txt
cooperative_id
document_category_id
member_id
visibility
status
published_at
expires_at
```

### Notes

- `specific_member` documents must have `member_id`.
- Public documents can appear on public website.
- Members-only documents require member login.
- Admin-only documents are internal.

---

## 16. Table: complaints

Stores member complaints, suggestions, and support tickets.

### Purpose

Used for:

- Member support
- Suggestions
- Complaints
- Admin follow-up

### Columns

```txt
id                      integer primary key
cooperative_id          foreign id required -> cooperatives.id
member_id               foreign id nullable -> members.id
created_by              foreign id nullable -> users.id
assigned_to             foreign id nullable -> users.id
ticket_no               string required
category                string nullable
subject                 string required
message                 text required
status                  string default 'open'
priority                string default 'normal'
closed_at               timestamp nullable
created_at              timestamp
updated_at              timestamp
soft_deleted_at         timestamp nullable
```

### status Values

```txt
open
in_progress
resolved
closed
cancelled
```

### priority Values

```txt
low
normal
high
urgent
```

### Indexes

```txt
cooperative_id
member_id
created_by
assigned_to
ticket_no
status
priority
created_at
```

---

## 17. Table: complaint_replies

Stores replies and internal notes for complaints.

### Purpose

Used for:

- Admin replies
- Member replies
- Internal support notes

### Columns

```txt
id                      integer primary key
complaint_id            foreign id required -> complaints.id
user_id                 foreign id nullable -> users.id
message                 text required
is_internal             boolean default false
created_at              timestamp
updated_at              timestamp
soft_deleted_at         timestamp nullable
```

### Indexes

```txt
complaint_id
user_id
is_internal
created_at
```

### Notes

- `is_internal = true` replies must not be visible to members.

---

## 18. Table: audit_logs

Stores important system activity.

### Purpose

Used for:

- Admin accountability
- Sensitive record changes
- Approval history
- Security review

### Columns

```txt
id                      integer primary key
cooperative_id          foreign id nullable -> cooperatives.id
actor_id                foreign id nullable -> users.id
action                  string required
subject_type            string nullable
subject_id              integer nullable
old_values              json nullable
new_values              json nullable
ip_address              string nullable
user_agent              text nullable
metadata                json nullable
created_at              timestamp
updated_at              timestamp
```

### Indexes

```txt
cooperative_id
actor_id
action
subject_type
subject_id
created_at
```

### Notes

If using `spatie/laravel-activitylog`, the package table can replace or complement this table. If using the package, map project requirements to package structure.

Audit important actions such as:

```txt
member.created
member.updated
member.suspended
membership_application.approved
membership_application.rejected
page.published
page.updated
section.updated
announcement.published
document.uploaded
document.deleted
settings.updated
role.updated
user.suspended
```

---

Future API/mobile tables such as `api_clients`, `mobile_devices`, and `personal_access_tokens` are not part of the current MVP database build.
They can be added later after client confirmation for API/mobile scope.

---

## 21. Relationship Summary

```txt
cooperatives has many users
cooperatives has many members
cooperatives has many membership_applications
cooperatives has many pages
cooperatives has many page_sections
cooperatives has many services
cooperatives has many announcements
cooperatives has many document_categories
cooperatives has many documents
cooperatives has many complaints

users may have one member profile
users may create/update pages, sections, announcements, documents
users may review membership applications
users may be assigned complaints

members belongs to cooperative
members may belong to user
members has many documents
members has many complaints

membership_applications belongs to cooperative
membership_applications may be reviewed by user
membership_applications may create approved member

pages has many page_sections
page_sections belongs to page

announcements belongs to cooperative
services belongs to cooperative

documents belongs to cooperative
documents may belong to document_category
documents may belong to member

complaints belongs to cooperative
complaints may belong to member
complaints has many complaint_replies
complaint_replies belongs to complaint
```

---

## 22. Recommended Demo Seed Data

For the first presentable demo, seed:

### Cooperative

```txt
Koperasi Demo Berhad
```

### Users

```txt
Super Admin
Admin Koperasi
Staff Keahlian
Staff Sokongan
Member Demo 1
Member Demo 2
```

### Members

At least 10 dummy members with different statuses:

```txt
active
inactive
suspended
```

### Membership Applications

At least 8 dummy applications:

```txt
pending
under_review
approved
rejected
```

### Public Pages

```txt
Homepage
About
Services
Contact
Membership
```

### Homepage Sections

```txt
hero
stats
service_grid
announcement_list
cta_banner
faq
```

### Services

Use generic dummy services:

```txt
Pembiayaan Anggota
Simpanan Anggota
Kedai Koperasi
Takaful & Perlindungan
Sewaan Ruang
Program Komuniti
```

### Announcements

```txt
Notis Mesyuarat Agung Tahunan
Promosi Kedai Koperasi
Kemaskini Sistem Keahlian
Program Literasi Kewangan
```

### Document Categories

```txt
Borang
Polisi
Laporan
Panduan Anggota
```

### Documents

```txt
Borang Permohonan Keahlian
Borang Kemaskini Maklumat Anggota
Panduan Portal Anggota
Laporan Tahunan Demo
```

### Complaints

At least 5 dummy complaints with mixed statuses.

---

## 23. SQLite Compatibility Notes

When writing migrations for SQLite demo:

- Avoid complex enum column constraints. Use `string` columns and validate statuses in Laravel.
- Avoid database-specific generated columns.
- Avoid advanced JSON querying for core logic.
- Use Laravel casts for JSON fields.
- Be careful when modifying existing columns; SQLite has limitations with altering tables.
- Prefer creating correct columns from the start.
- Use nullable foreign keys where demo flexibility is useful.

Example JSON cast in model:

```php
protected $casts = [
    'data' => 'array',
    'settings' => 'array',
    'metadata' => 'array',
    'is_active' => 'boolean',
];
```

---

## 24. Migration Build Order

Recommended migration order:

```txt
1. cooperatives
2. users
3. password_reset_tokens / sessions / cache / jobs if needed
4. roles and permissions package tables
5. members
6. membership_applications
7. settings
8. pages
9. page_sections
10. media_files
11. services
12. announcements
13. document_categories
14. documents
15. complaints
16. complaint_replies
17. audit_logs
18. API/mobile tables only when future scope is approved
```

---

## 25. Model Casts Recommendation

### Cooperative

```php
protected $casts = [
    'deleted_at' => 'datetime',
];
```

### PageSection

```php
protected $casts = [
    'data' => 'array',
    'settings' => 'array',
    'is_active' => 'boolean',
];
```

### Setting

```php
protected $casts = [
    'is_public' => 'boolean',
];
```

### Announcement

```php
protected $casts = [
    'is_pinned' => 'boolean',
    'published_at' => 'datetime',
    'expires_at' => 'datetime',
];
```

### Document

```php
protected $casts = [
    'published_at' => 'datetime',
    'expires_at' => 'datetime',
];
```

### MembershipApplication

```php
protected $casts = [
    'date_of_birth' => 'date',
    'submitted_at' => 'datetime',
    'reviewed_at' => 'datetime',
    'metadata' => 'array',
];
```

---

## 26. Important Development Notes for Codex

When implementing migrations and models:

1. Use this schema as the source of truth.
2. Keep the database SQLite-compatible for demo.
3. Do not hardcode a real cooperative name.
4. Use dummy cooperative seed data only.
5. Do not add accounting, loan ledger, or payment tables yet.
6. Keep Package B focused on website, CMS, membership, documents, announcements, complaints, and member portal.
7. Keep Package C tables minimal and future-ready only.
8. Add indexes for common filters.
9. Use Laravel validation for enum-like statuses.
10. Prefer simple schema over overengineered schema.

---

## 27. Out of Scope for Initial Schema

Do not create these tables yet unless explicitly requested:

```txt
loan_accounts
loan_repayments
share_transactions
savings_accounts
savings_transactions
dividend_calculations
general_ledger
journal_entries
bank_reconciliations
inventory_items
pos_sales
payment_transactions
evoting_ballots
evoting_votes
```

These can be added later as separate modules.

---

## 28. Ansuran Mudah Module

Module instalment-based product purchase for cooperative members.

### ansuran_categories

Product categories.

| Column | Type | Description |
|---|---|---|
| id | bigint (PK) | Primary key |
| cooperative_id | bigint (FK) | Cooperative reference |
| name | string | Category name |
| slug | string (unique) | URL-friendly slug |
| description | text (nullable) | Category description |
| image_path | string (nullable) | Category image |
| sort_order | int | Ordering index |
| is_active | bool | Active flag |
| timestamps | - | created_at, updated_at |

### ansuran_products

Products available for installment.

| Column | Type | Description |
|---|---|---|
| id | bigint (PK) | Primary key |
| cooperative_id | bigint (FK) | Cooperative reference |
| ansuran_category_id | bigint (FK) | Category reference |
| name | string | Product name |
| slug | string (unique) | URL-friendly slug |
| description | text (nullable) | Rich text description |
| min_down_payment_percent | decimal(5,2) | Minimum down payment % |
| guarantor_count | int | 0, 1, or 2 guarantors required |
| status | string | draf/aktif/tidak_aktif |
| sort_order | int | Ordering index |
| created_by | bigint (nullable) | Creator user ID |
| updated_by | bigint (nullable) | Updater user ID |
| timestamps + softDeletes | - | - |

### ansuran_product_images

Product image gallery.

| Column | Type | Description |
|---|---|---|
| id | bigint (PK) | Primary key |
| ansuran_product_id | bigint (FK) | Product reference |
| path | string | Storage path |
| sort_order | int | Ordering index |
| is_primary | bool | Primary image flag |
| timestamps | - | - |

### ansuran_product_variants

Product variants (SKU, price, stock).

| Column | Type | Description |
|---|---|---|
| id | bigint (PK) | Primary key |
| ansuran_product_id | bigint (FK) | Product reference |
| name | string | Variant name (e.g. "65 inci") |
| sku | string (nullable) | Stock keeping unit |
| price | decimal(12,2) | Variant price |
| stock | int (nullable) | Available stock |
| attributes | json (nullable) | Custom attributes (size, color, etc.) |
| sort_order | int | Ordering index |
| is_active | bool | Active flag |
| timestamps | - | - |

### ansuran_tenure_options

Installment tenure options with interest rates.

| Column | Type | Description |
|---|---|---|
| id | bigint (PK) | Primary key |
| cooperative_id | bigint (FK) | Cooperative reference |
| months | int | Number of months |
| interest_rate_percent | decimal(5,2) | Flat interest rate % |
| label | string (nullable) | Display label |
| sort_order | int | Ordering index |
| is_active | bool | Active flag |
| timestamps | - | - |

### ansuran_agreement_templates

Agreement templates with placeholders.

| Column | Type | Description |
|---|---|---|
| id | bigint (PK) | Primary key |
| cooperative_id | bigint (FK) | Cooperative reference |
| name | string | Template name |
| content | text | Template content with {{placeholders}} |
| description | text (nullable) | Template description |
| is_active | bool | Active flag |
| created_by | bigint (nullable) | Creator user ID |
| updated_by | bigint (nullable) | Updater user ID |
| timestamps | - | - |

### ansuran_applications

Member installment applications.

| Column | Type | Description |
|---|---|---|
| id | bigint (PK) | Primary key |
| cooperative_id | bigint (FK) | Cooperative reference |
| member_id | bigint (FK) | Applicant member |
| ansuran_product_id | bigint (FK) | Product reference |
| ansuran_product_variant_id | bigint (FK) | Variant reference |
| ansuran_tenure_option_id | bigint (FK, nullable) | Tenure option reference |
| ansuran_agreement_template_id | bigint (FK, nullable) | Agreement template reference |
| application_no | string (unique) | ANSR-YYYYMMDD-XXXXXX |
| full_price | decimal(12,2) | Product price |
| down_payment | decimal(12,2) | Down payment amount |
| financed_amount | decimal(12,2) | Financed amount |
| interest_rate_percent | decimal(5,2) | Applied interest rate |
| tenure_months | int | Installment months |
| monthly_amount | decimal(12,2) | Monthly payment |
| total_payable | decimal(12,2) | Total amount to pay |
| status | string | Application status (enum) |
| delivery_method | string (nullable) | pickup/delivery |
| delivery_address | text (nullable) | Delivery address |
| delivery_status | string (nullable) | Fulfillment status |
| delivery_tracking_no | string (nullable) | Tracking number |
| agreement_content | text (nullable) | Generated agreement HTML |
| signed_agreement_content | text (nullable) | Signed agreement HTML |
| signed_at | timestamp (nullable) | Signature timestamp |
| notes, admin_notes | text (nullable) | Notes |
| rejection_reason | text (nullable) | Rejection reason |
| reviewed_by, approved_by, rejected_by, cancelled_by | bigint (nullable) | Actor IDs |
| timestamps + softDeletes | - | - |

Status flow: pending_guarantor → pending → under_review → approved → agreement_generated → signed → processing → completed. Can be rejected/cancelled at multiple points.

### ansuran_application_histories

| Column | Type | Description |
|---|---|---|
| id | bigint (PK) | Primary key |
| ansuran_application_id | bigint (FK) | Application reference |
| actor_id | bigint (nullable) | Actor user ID |
| action | string | Action description |
| from_status | string (nullable) | Previous status |
| to_status | string (nullable) | New status |
| notes | text (nullable) | Action notes |
| metadata | json (nullable) | Additional data |
| created_at | timestamp | Created timestamp |

### ansuran_application_payments

| Column | Type | Description |
|---|---|---|
| id | bigint (PK) | Primary key |
| ansuran_application_id | bigint (FK) | Application reference |
| month_number | int | Month number (1-N) |
| amount | decimal(12,2) | Monthly amount |
| due_date | date (nullable) | Payment due date |
| paid_amount | decimal(12,2) | Amount paid |
| paid_date | date (nullable) | Payment date |
| status | string | pending/paid/partial/overdue |
| payment_method | string (nullable) | Payment method |
| reference_no | string (nullable) | Payment reference |
| notes | text (nullable) | Notes |
| recorded_by | bigint (nullable) | Recorder user ID |
| timestamps | - | - |

### ansuran_application_guarantors

| Column | Type | Description |
|---|---|---|
| id | bigint (PK) | Primary key |
| ansuran_application_id | bigint (FK) | Application reference |
| guarantor_member_id | bigint (FK→members) | Guarantor member |
| status | string | pending/accepted/rejected |
| rejection_reason | text (nullable) | Rejection reason |
| responded_at | timestamp (nullable) | Response timestamp |
| timestamps | - | - |

## 29. Schema Principle

The schema should follow this principle:

```txt
Demo-friendly with SQLite.
Structured enough for real cooperative workflows.
Simple enough for fast development.
Flexible enough to migrate to production later.
```