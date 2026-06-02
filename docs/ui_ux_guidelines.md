# ui_ux_guidelines.md

# KoperasiHub UI/UX Guidelines

Purpose: keep KoperasiHub visually consistent, professional, modern, and easy to use.

This file defines the product design direction for:

- Public website
- Admin panel
- Member portal

Use this file when building layouts, components, pages, dashboards, and CMS-rendered sections.

Current MVP note:

- Design for web only
- Keep layouts responsive for desktop and mobile browsers
- Do not design native mobile app flows in the current MVP

---

## 1. Design Direction

KoperasiHub should look:

- Professional
- Trustworthy
- Clean
- Modern
- Mature
- Government/corporate friendly
- Easy for older users
- Polished enough for proposals and demos

Avoid designs that feel:

- Playful
- Cartoonish
- Too colorful
- Too futuristic
- Too startup-ish
- Too dark
- Too crowded
- Emoji-heavy
- Over-animated

Design goal:

```txt
Modern cooperative digital platform.
Corporate enough for management.
Simple enough for staff and members.
Premium enough for proposal demos.
```

---

## 2. Visual Style

Preferred style:

```txt
Clean corporate UI
Soft gradient accents
Bento grid layouts
Rounded cards
Subtle shadows
Clear typography
Icon-based navigation
Large readable forms
Responsive dashboard layout
```

Use modern design patterns, but keep them restrained.

The product should feel current without feeling experimental.

---

## 3. Color System

Use a professional blue/teal/emerald-based palette.

Recommended default palette:

```txt
Primary:        #0F766E
Primary Dark:   #115E59
Primary Soft:   #CCFBF1

Secondary:      #1D4ED8
Secondary Dark: #1E40AF
Secondary Soft: #DBEAFE

Accent:         #F59E0B
Accent Soft:    #FEF3C7

Background:     #F8FAFC
Surface:        #FFFFFF
Surface Muted:  #F1F5F9

Text Primary:   #0F172A
Text Secondary: #475569
Text Muted:     #64748B

Border:         #E2E8F0
Border Soft:    #F1F5F9

Success:        #16A34A
Warning:        #D97706
Danger:         #DC2626
Info:           #2563EB
```

Gradient usage:

```txt
Primary gradient:
from #0F766E to #1D4ED8

Soft background gradient:
from #ECFDF5 to #EFF6FF

CTA gradient:
from #0F766E via #0EA5E9 to #1D4ED8

Card accent gradient:
from #F0FDFA to #EFF6FF
```

Rules:

- Gradients must be soft and controlled.
- Do not use heavy neon gradients.
- Do not use too many gradients on one page.
- Use gradients mainly for hero sections, CTA banners, highlights, and stat cards.
- Keep most admin surfaces white or light neutral.
- Use accent color sparingly for important CTAs or status highlights.

---

## 4. Typography

Use a clean sans-serif font.

Recommended:

```txt
Primary font: Inter
Fallback: system-ui, sans-serif
```

Typography scale:

```txt
Page title:       text-2xl to text-3xl, font-semibold
Section title:    text-xl to text-2xl, font-semibold
Card title:       text-base to text-lg, font-semibold
Body text:        text-sm to text-base
Helper text:      text-xs to text-sm
Table text:       text-sm
Button text:      text-sm, font-medium
```

Rules:

- Avoid tiny text.
- Older users must be able to read comfortably.
- Use sufficient line height.
- Avoid overly thin font weights.
- Prefer `font-medium` and `font-semibold`.
- Avoid excessive uppercase text.

---

## 5. UI Language & Tone

Product UI and copywriting should primarily use Bahasa Malaysia.

Use Bahasa Malaysia for:

- Public website copywriting
- Admin UI labels
- Member portal labels
- Form labels and helper text
- Buttons
- Validation messages
- Empty states
- Dashboard copy
- Toasts and confirmation dialogs

English terms are acceptable when the Bahasa Malaysia version would sound awkward, uncommon, or overly formal.

Keep technical/internal naming in English:

- Code identifiers
- Database table names
- Route names
- Model names
- Component names
- API keys
- Package and library names

Tone rules:

- Write clearly and professionally.
- Use familiar wording suitable for cooperative staff and older members.
- Avoid slang.
- Avoid emojis.
- Avoid overly casual wording.
- Prefer short labels over long formal phrases.
- Make action buttons direct and understandable.

---

## 6. Spacing & Layout

Use generous spacing.

Recommended spacing:

```txt
Page container: px-4 sm:px-6 lg:px-8
Admin page gap: gap-6
Card padding: p-5 or p-6
Form gap: gap-4 to gap-6
Section padding public: py-16 to py-24
```

Rules:

- Do not cram information.
- Use clear visual grouping.
- Use cards for related content.
- Avoid dense tables without filters.
- Keep forms readable and grouped.
- Use whitespace to reduce cognitive load.

---

## 7. Border Radius & Shadows

Use soft rounded corners.

Recommended:

```txt
Small controls: rounded-lg
Cards: rounded-2xl
Hero/bento cards: rounded-3xl
Modals: rounded-2xl
Badges: rounded-full
```

Shadow rules:

```txt
Default cards: shadow-sm
Featured cards: shadow-md
Avoid heavy shadows
Use border + soft shadow for admin UI
```

Recommended card style:

```txt
bg-white
border border-slate-200
rounded-2xl
shadow-sm
```

---

## 8. Icons

Use icons instead of emojis.

Recommended icon library:

```txt
lucide-vue-next
```

Rules:

- Do not use emoji in UI.
- Use icons for navigation, cards, actions, and empty states.
- Keep icons line-based and simple.
- Icon size should usually be 16px, 18px, 20px, or 24px.
- Use icons to support meaning, not decorate randomly.

Common icon mapping:

```txt
Dashboard: LayoutDashboard
Members: Users
Applications: FileCheck
CMS Pages: PanelsTopLeft
Announcements: Megaphone
Documents: FileText
Complaints: MessageSquare
Settings: Settings
Audit Logs: History
Reports: BarChart3
Profile: User
Security: ShieldCheck
Downloads: Download
Services: BriefcaseBusiness
```

---

## 9. Bento Grid Style

KoperasiHub can use bento grid layouts, especially on:

- Public homepage
- Admin dashboard
- Member dashboard
- Feature overview sections
- Proposal demo pages

Bento grid characteristics:

```txt
Asymmetric but balanced
Large feature card + smaller supporting cards
Rounded cards
Soft gradient or subtle background
Icon + headline + short description
Clear CTA where needed
```

Use bento grids for:

```txt
Service highlights
Platform features
Dashboard statistics
Member quick actions
CMS overview
Business unit overview
```

Avoid:

- Too many card sizes
- Random card heights
- Overlapping elements
- Heavy animation
- Too many colors
- Complex layouts on mobile

Mobile rule:

```txt
Bento grid must collapse into a simple single-column card list.
```

Example bento pattern:

```txt
Desktop:
[ Large Feature Card ][ Small Card ]
[ Large Feature Card ][ Small Card ]
[ Small Card        ][ Small Card ]

Mobile:
[ Card ]
[ Card ]
[ Card ]
[ Card ]
```

---

## 10. Public Website Guidelines

Public website should feel premium and credible.

Main public pages:

```txt
Homepage
About
Services
Announcements
Downloads
Contact
Membership Registration
```

Homepage structure:

```txt
Navbar
Hero Section
Trust / Stats Section
Services Bento Grid
Membership CTA
Announcements
Downloads / Forms
Contact CTA
Footer
```

Hero section rules:

- Use strong headline.
- Use short supporting copy.
- Use one primary CTA and one secondary CTA.
- Use soft gradient background.
- Use professional image or abstract pattern.
- Avoid clutter.
- Avoid carousel unless explicitly needed.

Public page design:

```txt
Max width: 7xl
Section padding: py-16 or py-20
Cards: rounded-2xl or rounded-3xl
Background: mostly white/slate with soft gradient accents
```

Navigation:

- Simple top navbar.
- Clear CTA button.
- Mobile menu must be simple.
- Avoid too many top-level menu items.

Footer:

- Corporate footer.
- Contact info.
- Useful links.
- Social links.
- Copyright.
- Avoid visual clutter.

---

## 11. Admin Panel Guidelines

Admin panel should be efficient, clean, and professional.

Admin layout:

```txt
Left sidebar
Top header
Main content area
Responsive mobile drawer sidebar
```

Sidebar:

- Use icons and labels.
- Group related modules.
- Highlight active item.
- Keep navigation short.
- Use role-aware menu visibility.
- Assume only these active MVP roles: `super_admin`, `admin`, `member`.

Topbar:

- Page context
- Search if needed
- Notifications if available
- User menu

Admin page structure:

```txt
PageHeader
Optional description
Primary action button
Filter/search bar
Main content card/table/form
```

Page header pattern:

```txt
Title
Short description
Primary action on right
```

Admin dashboard should use:

- Stat cards
- Bento cards
- Recent activity
- Pending applications
- Quick actions
- System health or content summary

Admin dashboard should not be overloaded.

---

## 12. Member Portal Guidelines

Member portal should be simpler than admin.

Audience may include older members.

Rules:

- Use larger text than admin where appropriate.
- Use clear labels.
- Avoid technical terms.
- Keep navigation minimal.
- Use cards for important info.
- Make actions obvious.
- Use plain Malay-friendly wording where possible.

Member dashboard should show:

```txt
Membership status
Profile completion
Latest announcements
Documents
Application status
Quick actions
Complaint status
```

Recommended member portal navigation:

```txt
Dashboard
Profile
Applications
Documents
Announcements
Complaints
Settings
```

Avoid:

- Complex filters
- Too many nested menus
- Developer terminology
- Dense data tables
```

---

## 13. Forms

Forms are critical in KoperasiHub.

General rules:

- Use clear labels.
- Use helper text for confusing fields.
- Show required fields clearly.
- Show validation errors near fields.
- Disable submit button while processing.
- Show success/error toast after submit.
- Use sections for long forms.
- Use stepper only for truly long workflows.
- Do not put too many fields in one row.

Recommended form layout:

```txt
Card
  Section title
  Description/helper text
  Fields in one or two columns
  Action buttons aligned right
```

For older users:

- Prefer one-column layout on member-facing forms.
- Use clear field names.
- Avoid placeholder-only labels.
- Avoid small inputs.

Common field styles:

```txt
height: h-10 or h-11
rounded-lg
border-slate-300
focus:ring-primary
```

---

## 14. Tables

Admin will use tables frequently.

Table rules:

- Use search.
- Use filters.
- Use pagination.
- Use status badges.
- Keep columns limited.
- Use row actions menu for secondary actions.
- Use clear empty states.
- Avoid showing sensitive data unnecessarily.
- Use detail pages for full records.

Recommended table columns:

```txt
Name / Title
Status
Category / Type
Updated date
Actions
```

Avoid wide tables with too many fields.

For mobile:

- Use responsive stacked cards or horizontal scroll.
- Important actions must remain accessible.

---

## 15. Status Badges

Use badges consistently.

Common statuses:

```txt
draft: gray
published: green
archived: slate

active: green
inactive: gray
suspended: red

pending: amber
under_review: blue
approved: green
rejected: red
cancelled: gray

open: amber
in_progress: blue
resolved: green
closed: gray
```

Badge style:

```txt
rounded-full
text-xs
font-medium
px-2.5
py-0.5
```

Do not rely only on color. Use text label clearly.

---

## 15. Buttons

Button hierarchy:

```txt
Primary: main action
Secondary: alternative action
Outline: neutral action
Ghost: low-emphasis action
Destructive: delete/reject/danger
```

Rules:

- Only one primary button per major section.
- Destructive actions require confirmation.
- Buttons should include icons only when helpful.
- Loading state should show when submitting.

Primary button style direction:

```txt
bg-teal-700
hover:bg-teal-800
text-white
rounded-lg
font-medium
```

Gradient buttons can be used sparingly for public CTA, not everywhere.

---

## 16. Cards

Cards are the main building block.

Use cards for:

- Dashboard stats
- Quick actions
- Feature highlights
- Forms
- Tables
- Member summary
- Public content blocks

Standard card:

```txt
bg-white
border border-slate-200
rounded-2xl
shadow-sm
p-5 or p-6
```

Feature card:

```txt
bg-gradient-to-br from-teal-50 to-blue-50
border border-teal-100
rounded-3xl
p-6
```

Avoid:

- Heavy shadows
- Excessive gradients
- Too many nested cards
- Random card sizes without purpose

---

## 17. Empty States

Every list/table should have an empty state.

Empty state should include:

```txt
Icon
Title
Short description
Optional action button
```

Example:

```txt
No applications yet.
Membership applications will appear here once submitted.
```

Rules:

- No emojis.
- Use lucide icon.
- Keep copy simple.
- Offer next action if useful.

---

## 18. Loading States

Use loading states for:

- Page data fetch
- Table update
- Form submit
- File upload
- Section preview

Recommended:

```txt
Skeleton cards
Spinner inside buttons
Muted loading text
```

Avoid full-page spinners unless necessary.

---

## 19. Error States

Error messages should be clear and non-technical.

Examples:

```txt
Unable to load members. Please try again.
This field is required.
You do not have permission to perform this action.
```

Avoid:

```txt
SQLSTATE error
500 exception
Undefined variable
```

In production, never show stack traces to users.

---

## 20. Modals & Drawers

Use modals for:

```txt
Confirm delete
Confirm reject
Small forms
Quick previews
```

Use drawers for:

```txt
Record preview
Filter panels
CMS section editing
Activity details
```

Rules:

- Destructive actions require confirmation.
- Long forms should use full page, not modal.
- Mobile drawers should become full-screen if needed.

---

## 21. CMS Editor UI

CMS editor should be structured, not a free page builder.

Recommended CMS editor layout:

```txt
Left: section list
Center/right: section form
Optional: preview panel
```

Section list should allow:

```txt
Reorder
Hide/show
Rename section label
Add section
Delete section
```

Section form should allow:

```txt
Edit text
Upload/select image
Edit buttons
Choose variant
Choose background style
Edit visibility
```

Do not allow:

```txt
Raw CSS
Raw JavaScript
Unlimited HTML
Arbitrary drag/drop layout
```

CMS section editor should feel controlled and safe.

---

## 22. CMS Preview

If preview is implemented:

- Use real frontend components.
- Preview should reflect selected variant.
- Preview does not need to be perfect in MVP.
- Preview should not block saving.

MVP can ship without live preview if rendering is reliable.

---

## 23. Accessibility

Basic accessibility rules:

- Use semantic HTML.
- Inputs must have labels.
- Buttons must be keyboard reachable.
- Focus states must be visible.
- Do not rely on color alone.
- Use sufficient contrast.
- Add alt text for meaningful images.
- Use aria labels where needed for icon-only buttons.

Older users benefit from accessible design, so readability is important.

---

## 24. Responsive Rules

All areas must be responsive.

Breakpoints:

```txt
Mobile: < 640px
Tablet: 640px - 1024px
Desktop: > 1024px
```

Public website:

- Stack sections on mobile.
- Keep CTA visible.
- Avoid oversized hero text on small screens.

Admin:

- Sidebar becomes drawer on mobile.
- Tables should be usable on mobile.
- Forms should become one column on mobile.

Member portal:

- Prioritize mobile usability.
- Cards should stack.
- Important actions should be easy to tap.

---

## 25. Animation

Use animation lightly.

Allowed:

```txt
Small hover transitions
Accordion open/close
Modal fade/scale
Card hover lift
Subtle section reveal
```

Avoid:

```txt
Bouncy animation
Too many moving elements
Slow transitions
Distracting effects
Autoplay carousels
```

Recommended duration:

```txt
150ms to 300ms
```

Use animation to improve clarity, not decoration.

---

## 26. Language & Copy

Default UI copy should be Malay-friendly but professional.

Tone:

```txt
Clear
Respectful
Simple
Corporate
Non-technical
```

Avoid:

- Slang
- Jokes
- Emojis
- Developer jargon
- Overly casual wording

Examples:

```txt
Permohonan Keahlian
Senarai Anggota
Kemaskini Profil
Muat Turun Dokumen
Hantar Aduan
Tetapan Sistem
```

---

## 27. Component Naming

Use clear component names.

Public sections:

```txt
HeroSection.vue
StatsSection.vue
ServiceGridSection.vue
BentoFeatureSection.vue
AnnouncementListSection.vue
CtaBannerSection.vue
FaqSection.vue
ContactBlockSection.vue
DownloadListSection.vue
ImageTextSection.vue
```

Admin components:

```txt
AdminLayout.vue
AdminSidebar.vue
AdminTopbar.vue
AdminPageHeader.vue
AdminDataTable.vue
AdminStatCard.vue
AdminBentoCard.vue
AdminFilterBar.vue
```

Member components:

```txt
MemberLayout.vue
MemberSidebar.vue
MemberTopbar.vue
MemberSummaryCard.vue
MemberQuickAction.vue
```

Shared components:

```txt
StatusBadge.vue
EmptyState.vue
LoadingState.vue
ConfirmDialog.vue
FileUploader.vue
MediaPicker.vue
RichTextEditor.vue
```

---

## 28. shadcn-vue Usage

Use shadcn-vue for UI primitives.

Recommended primitives:

```txt
Button
Input
Textarea
Select
Checkbox
RadioGroup
Dialog
Sheet
DropdownMenu
Tabs
Card
Badge
Table
Avatar
Toast
Tooltip
Accordion
Command
```

Rules:

- Wrap primitives into project components when repeated.
- Keep styling consistent.
- Do not over-customize every primitive differently.
- Prefer composition over duplicated markup.

---

## 29. Demo Presentation Mode

Since this project may be used for client demos, the UI should look complete even with dummy data.

Demo data should include:

```txt
Professional cooperative name
Realistic service names
Realistic announcements
Realistic member counts
Realistic application statuses
Realistic downloads
Realistic complaints
```

Do not use joke content.

Use placeholder images that look corporate or abstract.

Use dummy data that feels like a real cooperative platform.

---

## 30. Form Submission & Feedback Standards

All forms across Public, Admin, and Member areas MUST follow these standards.

### 30.1 Notification System

Use `FlashToast` (resources/js/Shared/Components/FlashToast.vue) as the single notification system.

- `FlashToast` is already included in all three layouts (Public, Admin, Member)
- It reads `page.props.flash.status` (success) and `page.props.flash.error` from Inertia shared props
- Backend controllers set flash via `return redirect()->route('...')->with('status', 'Mesej berjaya')`
- Do NOT add inline `<div v-if="statusMessage">` banners — they duplicate FlashToast

### 30.2 Form Submit Behavior

Every form submission MUST:

| Behavior | Implementation |
|----------|---------------|
| Scroll to top on success | `window.scrollTo({ top: 0, behavior: 'smooth' })` in `onSuccess` |
| Scroll to top on error | `window.scrollTo({ top: 0, behavior: 'smooth' })` in `onError` |
| Preserve form data on failure | Inertia `useForm()` does this automatically |
| Show processing state | `form.processing` disables submit button |
| Show success/error toast | Backend sets session flash, displayed by FlashToast |
| Show field validation errors | `form.errors.fieldName` shown inline near each field |

### 30.3 Reusable Composable

Use `useFormSubmit` (resources/js/Shared/Composables/useFormSubmit.js) for NEW forms:

```js
import { useFormSubmit } from '@/Shared/Composables/useFormSubmit';

const form = useFormSubmit({ title: '', content: '' });

function submit() {
    form.submit('post', '/url', { forceFormData: true });
}
```

This composable wraps Inertia's `useForm` and auto-scrolls to top on both success and error.

For existing forms, apply the standard callbacks directly:

```js
const onSuccess = () => window.scrollTo({ top: 0, behavior: 'smooth' });
const onError = () => window.scrollTo({ top: 0, behavior: 'smooth' });

form.post('/url', { onSuccess, onError });
```

### 30.4 File Upload Forms

Forms with file/image uploads MUST use `forceFormData: true`:

```js
form.post('/url', { forceFormData: true, onSuccess, onError });
```

Without `forceFormData: true`, file data is not sent correctly.

### 30.5 What NOT To Do

- ❌ Do NOT add inline `<div v-if="statusMessage">` banners
- ❌ Do NOT use `preserveScroll: true` (prevents scroll-to-top feedback)
- ❌ Do NOT manually reset form fields on error (user data must be preserved)
- ❌ Do NOT use custom inline save/error indicators — use FlashToast
- ❌ Do NOT skip `onSuccess`/`onError` callbacks on form submissions

### 30.6 Success Message Examples (Backend)

```php
// Controller success
return redirect()->route('admin.services.index')
    ->with('status', 'Perkhidmatan berjaya disimpan.');

// Controller error (non-validation)
return redirect()->back()
    ->with('error', 'Ralat berlaku. Sila cuba lagi.');
```

Validation errors are handled automatically by Inertia — no manual flash needed.

---

## 31. Final UI Principle

Use this principle when making UI decisions:

```txt
Professional first.
Modern second.
Simple always.
```

KoperasiHub should impress clients without intimidating them.