# KoperasiHub Component System Spec

Purpose: define a shared UI component system so KoperasiHub screens stay consistent across Public, Admin, and Member areas.

Scope:

- Vue 3 with `<script setup>`
- Inertia.js pages
- Tailwind CSS styling
- shadcn-vue primitives
- `lucide-vue-next` icons
- UI copy primarily in Bahasa Malaysia

This file defines component intent, usage rules, and expected behavior only. It does not define full implementation code.

---

## 1. Principles

Use these rules across the app:

- Reuse existing shared components before creating new ones.
- If a new pattern repeats 2 or more times, extract it into `Shared`.
- Do not create custom button, card, table, or form styles inside pages.
- Wrap repeated shadcn-vue primitives with project components.
- Keep spacing, radius, shadows, and status colors aligned with `docs/ui_ux_guidelines.md`.
- Keep Public, Admin, and Member layouts separate, but reuse shared building blocks.
- Prefer composition with props and slots over one-off page variants.
- Keep UI copy clear, professional, and suitable for cooperative staff and older users.

Suggested structure:

```txt
resources/js/
├── Shared/Components
├── Shared/Layouts
├── Admin/Components
├── Admin/Layouts
├── Member/Components
├── Member/Layouts
└── Public/Components
```

---

## 2. Visual Base Rules

Use these defaults unless a component spec says otherwise:

- Container spacing: `px-4 sm:px-6 lg:px-8`
- Page/content gap: `gap-6`
- Standard card: `bg-white border border-slate-200 rounded-2xl shadow-sm`
- Feature card: soft gradient, `rounded-3xl`, restrained accent border
- Small controls: `rounded-lg`
- Modal/dialog: `rounded-2xl`
- Badges: `rounded-full`
- Status colors:
  - success: green
  - warning: amber
  - danger: red
  - info: blue
  - neutral: slate/gray

Do not introduce ad hoc radius, shadow, or color systems per page.

---

## 3. Shared Component Rules

### `AppLogo`

Use for brand identity in navbar, auth pages, sidebars, and footer.

Props/behavior:

- `name?`
- `logoUrl?`
- `href?`
- `size?: sm | md | lg`
- `showText?: boolean`
- Falls back to configurable text mark when no logo exists
- Must remain white-label safe

### `PageHeader`

Use at top of Admin and Member pages, and selected Public inner pages.

Props/behavior:

- `title`
- `description?`
- `breadcrumbs?`
- `actions?`
- `align?: start | between`
- Supports action slot for primary/secondary buttons
- Only one primary action per header

### `SectionHeader`

Use inside cards, public sections, forms, bento areas, and grouped content blocks.

Props/behavior:

- `title`
- `description?`
- `icon?`
- `actions?`
- `centered?: boolean`

### `DataTable`

Use for repeatable admin/member record listings, not hand-built tables per page.

Props/behavior:

- `columns`
- `rows`
- `loading?`
- `emptyState?`
- `pagination?`
- `rowKey`
- `rowActions?`
- Supports status badge cells, sortable headers, and mobile fallback
- Mobile behavior: horizontal scroll or stacked summary cards

When to use:

- Admin listing pages
- Member record history pages
- Never for simple 2 to 4 item summaries

### `FilterBar`

Use above tables or grids when a module has search, filters, sort, or bulk controls.

Props/behavior:

- `search?`
- `filters?`
- `sort?`
- `actions?`
- `collapsedOnMobile?: boolean`
- Supports responsive wrapping

### `SearchInput`

Use as the standard search field in tables, pickers, and module indexes.

Props/behavior:

- `modelValue`
- `placeholder?`
- `debounceMs?`
- `clearable?: boolean`
- `icon?: Search`

### `StatusBadge`

Use for workflow, publish, membership, and system statuses.

Props/behavior:

- `status`
- `label?`
- `variant?`
- Maps status values to shared color tokens
- Must always display text, not color only

Suggested mappings:

```txt
draft, archived, inactive, cancelled, closed -> neutral
published, active, approved, resolved -> success
pending -> warning
under_review, in_progress -> info
rejected, suspended -> danger
```

### `EmptyState`

Use for empty tables, zero-content modules, and first-time setup screens.

Props/behavior:

- `title`
- `description`
- `icon`
- `actionLabel?`
- `actionHref?`
- `compact?: boolean`

### `LoadingState`

Use for data fetch, section preview, tables, cards, and async module panels.

Props/behavior:

- `variant?: page | card | table | inline`
- `rows?`
- `message?`
- Prefer skeletons over full-page spinners

### `ConfirmDialog`

Use for destructive or high-impact actions only.

Props/behavior:

- `title`
- `description`
- `confirmLabel`
- `cancelLabel`
- `variant?: default | destructive`
- `loading?`
- Must support focus-safe keyboard interaction

Required for:

- Delete
- Reject
- Publish/unpublish when impact is material
- Sensitive admin actions

### `FormSection`

Use to group related fields in long or important forms.

Props/behavior:

- `title`
- `description?`
- `columns?: 1 | 2`
- `divider?: boolean`
- Default to 1 column for member-facing forms

### `FormActions`

Use as the standard footer for create/edit forms.

Props/behavior:

- `submitLabel`
- `cancelLabel?`
- `submitting?`
- `align?: start | end | between`
- Supports primary, secondary, and destructive actions

Rules:

- Submit button shows loading state
- Cancel action must be clear
- Do not handcraft page-specific action rows

### `FileUploader`

Use for document uploads and controlled file attachments.

Props/behavior:

- `accept?`
- `multiple?: boolean`
- `maxSizeMb?`
- `helperText?`
- `existingFiles?`
- `disabled?`
- Shows progress, validation feedback, and uploaded item list

### `MediaPicker`

Use in CMS and settings when selecting existing uploaded media or replacing assets.

Props/behavior:

- `modelValue`
- `multiple?: boolean`
- `allowedTypes?: image | document | mixed`
- `preview?: boolean`
- `librarySource?`
- Can open modal/drawer picker

### `StatCard`

Use for KPI numbers on Admin and Member dashboards.

Props/behavior:

- `title`
- `value`
- `description?`
- `icon?`
- `trend?`
- `href?`
- `tone?: default | success | warning | info`

Rules:

- Keep copy short
- Use at-a-glance numbers only
- Avoid dense explanatory text

### `BentoCard`

Use for feature highlights, dashboard summaries, and premium public sections.

Props/behavior:

- `title`
- `description`
- `icon?`
- `href?`
- `variant?: default | gradient | muted`
- `size?: sm | md | lg`

Rules:

- Use restrained gradients
- Must collapse cleanly to single column on mobile

### `QuickActionCard`

Use for high-priority user actions on dashboards.

Props/behavior:

- `title`
- `description?`
- `icon`
- `href` or `onClick`
- `disabled?`

Examples:

- Mohon Keanggotaan
- Lihat Pengumuman
- Muat Turun Dokumen

### `ModuleCard`

Use for module entry points, CMS section groups, and settings categories.

Props/behavior:

- `title`
- `description`
- `icon`
- `href`
- `stats?`
- `badge?`

### `Breadcrumbs`

Use for Admin and Member inner pages with depth beyond a top-level dashboard.

Props/behavior:

- `items`
- Each item: `label`, `href?`, `current?`
- Last item is non-clickable

Do not use breadcrumbs on small, shallow Public pages unless needed.

### `Toast` / FlashToast

Use `FlashToast` (resources/js/Shared/Components/FlashToast.vue) as the single notification system.

- Included in all three layouts (Public, Admin, Member) via `<FlashToast />`
- Reads `page.props.flash.status` (success/green) and `page.props.flash.error` (red) from Inertia shared props
- Auto-dismisses after 5 seconds
- Do NOT add inline `<div v-if="statusMessage">` banners — use FlashToast only

Backend controllers set flash messages:

```php
return redirect()->route('...')->with('status', 'Berjaya disimpan.');
return redirect()->back()->with('error', 'Ralat berlaku.');
```

Rules:

- Bahasa Malaysia copy by default
- Keep messages short and clear
- Use for submit success, failure, delete result, upload result, and permission feedback
- Do not use toast for critical confirmations that need explicit acknowledgement

For complete form submission standards (scroll-to-top, data preservation, processing state), see `docs/ui_ux_guidelines.md` section 30.

---

## 4. Layout Components

### `AdminLayout`

Use for all `/admin` pages.

Includes:

- `AdminSidebar`
- `AdminTopbar`
- main content container
- responsive mobile navigation drawer
- page width and spacing rules

Behavior:

- Role-aware navigation visibility
- Works with `PageHeader`, `Breadcrumbs`, and shared content cards

### `AdminSidebar`

Use for primary admin navigation.

Props/behavior:

- `items`
- `currentRoute`
- `collapsed?`
- `footerActions?`
- Supports grouped items with lucide icons
- Highlights active route

### `AdminTopbar`

Use for page context and global actions in admin.

Props/behavior:

- `title?`
- `search?`
- `actions?`
- `userMenu`
- `notifications?`

Rules:

- Keep compact
- Do not overload with module-specific filters when `FilterBar` is more suitable

### `MemberLayout`

Use for all `/member` pages.

Includes:

- simpler navigation than admin
- larger text and clearer labels where needed
- shared header/content/footer structure

Behavior:

- Prioritize readability
- Keep actions obvious

### `PublicNavbar`

Use for public-facing site navigation.

Props/behavior:

- `items`
- `cta?`
- `logo`
- `sticky?: boolean`
- `mobileMenu`

Rules:

- Keep top-level items limited
- Support configurable branding and links

### `PublicFooter`

Use for all public pages.

Props/behavior:

- `linkGroups`
- `contactInfo`
- `socialLinks?`
- `copyright`
- `logo?`

Rules:

- Corporate presentation
- White-label safe content only

---

## 5. When To Create New Components

Create a new shared component only when:

- the pattern repeats 2 or more times
- the behavior is stable enough to standardize
- a page becomes harder to maintain with inline markup
- the component improves consistency across areas

Do not create a new shared component when:

- the pattern is truly one-off
- the difference is only copy, icon, or slot content
- an existing shared component can support it with small prop/slot extension

Before adding a new component:

1. Check `Shared` first.
2. Extend the existing component if the visual pattern is the same.
3. Create a new shared wrapper only if the pattern is meaningfully distinct.

---

## 6. Non-Negotiable Rules

- No random page-by-page styling.
- No duplicate button, card, table, or form patterns.
- No emojis in UI.
- Use lucide icons only unless a later task approves another library.
- Use shared status mappings.
- Use shared loading, empty, confirmation, and toast patterns.
- Prefer Public, Admin, and Member composition from shared building blocks rather than isolated custom screens.
