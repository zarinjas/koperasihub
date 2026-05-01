# cms_section_spec.md

# KoperasiHub — CMS Section Spec

Purpose: Define the section-based CMS used by the public website.

This file is intentionally compact. Read only when working on CMS pages, public website rendering, homepage/landing page sections, or admin CMS editor.

Related docs:
- `project_overview.md`
- `AGENTS.md`
- `docs/database_schema.md`
- `docs/module_spec.md`
- `docs/ui_ux_guidelines.md`

---

## 1. CMS Principle

KoperasiHub uses a controlled section-based CMS.

Developer controls:
- Design system
- Vue components
- Layout rules
- Responsive behavior
- Available section types
- Available variants

Admin controls:
- Text
- Images
- Buttons
- Links
- Visibility
- Sort order
- Predefined variants
- Public/member targeting where applicable

Do not build a free-form Elementor-style builder.

Do not allow raw CSS or arbitrary JavaScript from admin inputs.

---

## 2. Core Tables

Use these tables from `database_schema.md`:

```txt
pages
page_sections
media
settings
```

Expected minimal structure:

```txt
pages
- id
- title
- slug
- template
- status
- meta_title
- meta_description
- published_at

page_sections
- id
- page_id
- type
- name
- data_json
- settings_json
- sort_order
- is_active
```

`data_json` stores content fields.

`settings_json` stores display choices such as variant, background, spacing, alignment.

---

## 3. Rendering Rule

Public pages render active sections ordered by `sort_order`.

Frontend mapping:

```js
const sectionMap = {
  hero: HeroSection,
  stats: StatsSection,
  feature_grid: FeatureGridSection,
  service_grid: ServiceGridSection,
  business_units: BusinessUnitsSection,
  announcement_list: AnnouncementListSection,
  cta_banner: CtaBannerSection,
  faq: FaqSection,
  contact_block: ContactBlockSection,
  download_list: DownloadListSection,
  image_text: ImageTextSection,
  testimonial: TestimonialSection,
}
```

Vue render pattern:

```vue
<component
  v-for="section in sections"
  :key="section.id"
  :is="sectionMap[section.type]"
  :data="section.data"
  :settings="section.settings"
/>
```

Unknown section type:
- local/dev: show safe debug placeholder
- production: hide section silently

---

## 4. Common Section Fields

Every section should support:

```txt
id
type
name
sort_order
is_active
data_json
settings_json
```

Common `settings_json` keys:

```json
{
  "variant": "default",
  "background": "default",
  "spacing": "md",
  "alignment": "left",
  "container": "default"
}
```

Allowed common values:

```txt
variant: default | centered | split | compact
background: default | muted | primary | gradient | image
spacing: sm | md | lg | xl
alignment: left | center | right
container: default | narrow | wide | full
```

Do not allow arbitrary CSS classes from admin.

---

## 5. Section Types

## 5.1 Hero

Type: `hero`

Purpose: Main top section for homepage or landing page.

Component:
```txt
Public/Sections/HeroSection.vue
```

Data:

```json
{
  "badge": "Koperasi Demo Berhad",
  "title": "Platform digital koperasi moden",
  "subtitle": "Urus keahlian, pengumuman, dokumen dan perkhidmatan koperasi dalam satu sistem.",
  "primary_button_text": "Daftar Sebagai Ahli",
  "primary_button_url": "/member/register",
  "secondary_button_text": "Ketahui Lanjut",
  "secondary_button_url": "/tentang-kami",
  "image_id": null
}
```

Settings:

```json
{
  "variant": "image_right",
  "background": "default",
  "spacing": "xl",
  "alignment": "left"
}
```

Allowed variants:

```txt
centered
image_right
image_left
split
```

Validation:
- title: required string max 160
- subtitle: nullable string max 320
- button text: nullable string max 80
- button URL: nullable string max 255
- image_id: nullable existing media ID

---

## 5.2 Stats

Type: `stats`

Purpose: Show numeric highlights.

Component:
```txt
Public/Sections/StatsSection.vue
```

Data:

```json
{
  "items": [
    { "label": "Tahun beroperasi", "value": "25+" },
    { "label": "Anggota berdaftar", "value": "5,000+" },
    { "label": "Perkhidmatan", "value": "12" }
  ]
}
```

Settings:

```json
{
  "variant": "cards",
  "background": "muted",
  "columns": 3
}
```

Allowed variants:

```txt
simple
cards
bordered
```

Validation:
- items: array min 1 max 6
- item.label: required string max 80
- item.value: required string max 40
- columns: 2 | 3 | 4

---

## 5.3 Feature Grid

Type: `feature_grid`

Purpose: Highlight platform/service benefits.

Component:
```txt
Public/Sections/FeatureGridSection.vue
```

Data:

```json
{
  "eyebrow": "Kelebihan",
  "title": "Semua urusan koperasi dalam satu platform",
  "subtitle": "Direka untuk memudahkan anggota dan pentadbir koperasi.",
  "items": [
    {
      "title": "Portal Ahli",
      "description": "Ahli boleh semak maklumat dan status permohonan.",
      "icon": "users"
    },
    {
      "title": "CMS Website",
      "description": "Admin boleh kemaskini kandungan website tanpa developer.",
      "icon": "layout"
    }
  ]
}
```

Settings:

```json
{
  "variant": "cards",
  "columns": 3,
  "background": "default"
}
```

Allowed variants:

```txt
simple
cards
icons
```

Validation:
- title: required string max 160
- subtitle: nullable string max 320
- items: array min 1 max 9
- item.title: required string max 120
- item.description: nullable string max 260
- item.icon: nullable string max 60

---

## 5.4 Service Grid

Type: `service_grid`

Purpose: Display cooperative services from service records or manual items.

Component:
```txt
Public/Sections/ServiceGridSection.vue
```

Data:

```json
{
  "eyebrow": "Perkhidmatan",
  "title": "Perkhidmatan koperasi",
  "subtitle": "Akses perkhidmatan utama koperasi dengan lebih mudah.",
  "source": "services",
  "limit": 6,
  "items": []
}
```

Settings:

```json
{
  "variant": "cards",
  "columns": 3,
  "background": "default"
}
```

Source options:

```txt
services
manual
```

If `source = services`, load published/active services from `services` table.

If `source = manual`, use `items` from `data_json`.

Manual item shape:

```json
{
  "title": "Pembiayaan",
  "description": "Permohonan pembiayaan anggota.",
  "url": "/perkhidmatan/pembiayaan",
  "image_id": null
}
```

Validation:
- title: required string max 160
- source: required services | manual
- limit: nullable integer min 1 max 12
- items: required only when source=manual

---

## 5.5 Business Units

Type: `business_units`

Purpose: Show business/unit categories for a cooperative.

Component:
```txt
Public/Sections/BusinessUnitsSection.vue
```

Data:

```json
{
  "title": "Unit perniagaan koperasi",
  "subtitle": "Ketahui cabang perkhidmatan dan aktiviti koperasi.",
  "items": [
    {
      "title": "Kedai Koperasi",
      "description": "Peruncitan dan barangan keperluan anggota.",
      "url": "/perniagaan/kedai-koperasi",
      "image_id": null
    }
  ]
}
```

Settings:

```json
{
  "variant": "cards",
  "columns": 3,
  "background": "muted"
}
```

Validation:
- items: array min 1 max 12
- item.title: required string max 120
- item.description: nullable string max 260
- item.url: nullable string max 255
- item.image_id: nullable existing media ID

---

## 5.6 Announcement List

Type: `announcement_list`

Purpose: Display latest public announcements.

Component:
```txt
Public/Sections/AnnouncementListSection.vue
```

Data:

```json
{
  "title": "Pengumuman terkini",
  "subtitle": "Ikuti maklumat terbaru daripada koperasi.",
  "source": "latest",
  "limit": 3,
  "button_text": "Lihat Semua",
  "button_url": "/pengumuman"
}
```

Settings:

```json
{
  "variant": "cards",
  "background": "default"
}
```

Source options:

```txt
latest
pinned
manual
```

Validation:
- title: required string max 160
- limit: integer min 1 max 9
- button_text: nullable string max 80
- button_url: nullable string max 255

Only show:
- status = published
- audience = public
- published_at <= now
- expires_at is null or future

---

## 5.7 CTA Banner

Type: `cta_banner`

Purpose: Strong call-to-action block.

Component:
```txt
Public/Sections/CtaBannerSection.vue
```

Data:

```json
{
  "title": "Bersedia untuk menjadi ahli koperasi?",
  "subtitle": "Daftar minat anda secara online dan pihak koperasi akan menghubungi anda.",
  "primary_button_text": "Daftar Sekarang",
  "primary_button_url": "/member/register",
  "secondary_button_text": "Hubungi Kami",
  "secondary_button_url": "/hubungi"
}
```

Settings:

```json
{
  "variant": "solid",
  "background": "primary",
  "alignment": "center"
}
```

Allowed variants:

```txt
simple
solid
split
```

Validation:
- title: required string max 160
- subtitle: nullable string max 320
- button fields: nullable string max 255

---

## 5.8 FAQ

Type: `faq`

Purpose: Display frequently asked questions.

Component:
```txt
Public/Sections/FaqSection.vue
```

Data:

```json
{
  "title": "Soalan lazim",
  "subtitle": "Jawapan kepada soalan yang kerap ditanya.",
  "items": [
    {
      "question": "Siapa boleh menjadi ahli?",
      "answer": "Keahlian terbuka mengikut syarat yang ditetapkan oleh koperasi."
    }
  ]
}
```

Settings:

```json
{
  "variant": "accordion",
  "background": "default"
}
```

Allowed variants:

```txt
accordion
two_column
simple
```

Validation:
- items: array min 1 max 20
- item.question: required string max 180
- item.answer: required string max 1000

---

## 5.9 Contact Block

Type: `contact_block`

Purpose: Show contact information and inquiry CTA.

Component:
```txt
Public/Sections/ContactBlockSection.vue
```

Data:

```json
{
  "title": "Hubungi koperasi",
  "subtitle": "Ada pertanyaan? Hubungi pasukan kami.",
  "phone": "+603-0000 0000",
  "email": "hello@example.com",
  "whatsapp": "+6012-000 0000",
  "address": "Alamat koperasi demo",
  "map_url": "",
  "show_contact_form": true
}
```

Settings:

```json
{
  "variant": "split",
  "background": "muted"
}
```

Allowed variants:

```txt
simple
cards
split
```

Validation:
- title: required string max 160
- phone/email/whatsapp/address: nullable string max 255
- map_url: nullable string max 500
- show_contact_form: boolean

If fields are empty, frontend may fallback to site settings.

---

## 5.10 Download List

Type: `download_list`

Purpose: Display public downloadable documents/forms.

Component:
```txt
Public/Sections/DownloadListSection.vue
```

Data:

```json
{
  "title": "Borang dan dokumen",
  "subtitle": "Muat turun dokumen berkaitan koperasi.",
  "source": "documents",
  "category": "forms",
  "limit": 6
}
```

Settings:

```json
{
  "variant": "list",
  "background": "default"
}
```

Source options:

```txt
documents
manual
```

Only show public documents if source = documents.

Validation:
- title: required string max 160
- category: nullable string max 80
- limit: integer min 1 max 20

---

## 5.11 Image Text

Type: `image_text`

Purpose: Generic image and text section.

Component:
```txt
Public/Sections/ImageTextSection.vue
```

Data:

```json
{
  "eyebrow": "Tentang Kami",
  "title": "Koperasi yang memudahkan komuniti",
  "content": "Koperasi ini menyediakan perkhidmatan kepada ahli melalui platform digital yang mudah digunakan.",
  "button_text": "Ketahui Lanjut",
  "button_url": "/tentang-kami",
  "image_id": null
}
```

Settings:

```json
{
  "variant": "image_right",
  "background": "default",
  "spacing": "lg"
}
```

Allowed variants:

```txt
image_right
image_left
centered
```

Validation:
- title: required string max 160
- content: nullable string max 2000
- button fields: nullable string max 255
- image_id: nullable existing media ID

---

## 5.12 Testimonial

Type: `testimonial`

Purpose: Show quotes/testimonials if needed for demo.

Component:
```txt
Public/Sections/TestimonialSection.vue
```

Data:

```json
{
  "title": "Apa kata anggota",
  "items": [
    {
      "quote": "Portal ini memudahkan saya menyemak maklumat keahlian.",
      "name": "Ahli Demo",
      "role": "Anggota Koperasi",
      "avatar_id": null
    }
  ]
}
```

Settings:

```json
{
  "variant": "cards",
  "background": "muted"
}
```

Allowed variants:

```txt
single
cards
carousel
```

Validation:
- items: array min 1 max 6
- item.quote: required string max 400
- item.name: required string max 120
- item.role: nullable string max 120

Carousel behavior can be static for MVP. Do not add heavy carousel library unless necessary.

---

## 6. Admin CMS Editor Requirements

Admin pages:

```txt
/admin/cms/pages
/admin/cms/pages/create
/admin/cms/pages/{page}/edit
/admin/cms/pages/{page}/sections
```

Minimum actions:
- Create page
- Edit page metadata
- Publish/unpublish page
- Add section
- Edit section data
- Reorder sections
- Toggle section active/inactive
- Delete section
- Preview page if practical

Section editor UI:
- section type selector
- dynamic form based on section type
- data fields
- settings fields
- save button
- preview summary
- drag reorder

Do not build full live page builder for MVP.

---

## 7. Public Page Rules

Public routes:
- `/` renders homepage
- `/{slug}` renders published page by slug
- reserved routes must not be overridden by CMS slugs

Reserved slugs:

```txt
admin
member
api
login
register
dashboard
storage
assets
```

Page display rules:
- show only `status = published`
- show only active sections
- order by `sort_order`
- respect `published_at`
- 404 if page not published
- homepage can use slug `home`

---

## 8. SEO Rules

Each page should support:

```txt
meta_title
meta_description
og_image_id
canonical_url
```

Fallback:
- meta_title fallback to page title
- meta_description fallback to site setting
- og_image fallback to site logo or default image

Do not overbuild SEO in MVP.

---

## 9. Media Usage Rules

Section image fields should reference `media_files.id`.

Do not store raw uploaded file paths directly in section JSON unless unavoidable.

For demo, allow local storage.

For private/member files, do not use CMS media rules. Use document module rules.

---

## 10. Validation Strategy

Use backend validation for all CMS saves.

Recommended:
- `StorePageRequest`
- `UpdatePageRequest`
- `StorePageSectionRequest`
- `UpdatePageSectionRequest`

Implement section-specific validation by `type`.

Unknown section type should be rejected in admin.

---

## 11. Dummy Seed Content

Seeder may create:

```txt
Home page
About page
Services page
Contact page
```

Demo homepage sections:
1. hero
2. stats
3. feature_grid
4. service_grid
5. announcement_list
6. cta_banner
7. faq
8. contact_block

Use dummy cooperative content only.

Do not use real cooperative names unless explicitly requested.

---

## 12. MVP Scope

Build for MVP:
- pages CRUD
- section CRUD
- section reorder
- publish/unpublish
- public renderer
- core section components
- seed demo homepage

MVP section types:
```txt
hero
stats
feature_grid
service_grid
announcement_list
cta_banner
faq
contact_block
image_text
download_list
```

Can defer:
```txt
testimonial
carousel behavior
live drag-and-drop page preview
advanced SEO
A/B testing
form builder
custom themes
```

---

## 13. Do Not Build

Do not build:
- Elementor-style free builder
- arbitrary HTML/CSS/JS editor
- WordPress integration
- theme marketplace
- complex animation editor
- multi-language CMS unless requested
- approval workflow for CMS publishing unless requested
- version history unless requested
