---
name: salesflow-ui-consistency
description: Keep SalesFlow CRM UI consistent when Codex designs, reviews, fixes, or implements screens, app shell, navigation, list/detail/edit flows, tables, filters, modals, forms, charts, empty/loading/error states, responsive behavior, dark mode, accessibility, and icon usage. Use together with salesflow-feature-development for SalesFlow features that touch Blade, Livewire, Flux UI, Tailwind CSS, or user-facing Vietnamese interface.
---

# SalesFlow UI Consistency

Use this skill to keep SalesFlow screens visually and behaviorally consistent. This skill does not replace `salesflow-feature-development`; use both when a SalesFlow feature changes UI.

## Load context

1. Read `docs/requirements.md`, especially sections 5, 11, 15, and 16.
2. Read the active feature entry in `PROJECT_PHASES.md` or `docs/EXTENSION_FEATURES_P1_P8.md`.
3. Inspect at least one existing comparable screen before editing:
   - List/table screen for list changes.
   - Detail screen for detail changes.
   - Editor/form screen for create/edit changes.
   - Modal/drawer screen for quick actions.
4. Preserve the current technical-layer tree and Flux UI Free-first rule.

## UI direction

Build a quiet operational CRM interface:

- Professional, dense enough for data work, but readable.
- Vietnamese labels/messages for users; clear English identifiers in code.
- Card borders and shadows should be subtle; avoid marketing-style hero blocks, decorative gradients, oversized emoji, and “AI-looking” icon clutter.
- Icons must support recognition or action meaning. Do not add icons to every button, metric card, heading, or empty state.
- Use existing project components/classes first, then Flux UI, then Blade + Tailwind + Alpine when Flux Free lacks a component.
- Do not install a UI package unless the active feature proves the existing stack cannot satisfy the requirement.

## Standard page structure

Every main page should follow:

```text
Sidebar | Topbar
        | Breadcrumb
        | Page title + primary actions
        | Filters / context actions
        | Main content
```

Keep this order stable across modules. Do not invent a new layout for one module unless the feature explicitly requires it.

## List/table checklist

For list pages, verify:

- Search, filters, allowlisted sort, pagination, and per-page are consistent.
- Filter state belongs to the current screen; it must not pollute other tabs/pages.
- URL state works for reload/back/forward where the feature requires it.
- Empty state, empty-filter state, reset filter button, loading/skeleton, and error/retry are present where relevant.
- Bulk selection only works for records the actor can see; backend re-authorizes every mutation.
- Mobile layout is usable: table can scroll, collapse, or switch to cards without losing actions.
- Table text, status badges, dates, money, and user names use Vietnamese/business-friendly formatting.

## Detail page checklist

For detail pages, verify:

- Header shows identity, status, owner/department, and primary actions.
- Sections/tabs are predictable: overview, related records, timeline, files, audit/history when applicable.
- Dangerous actions are separated and confirmed.
- Data outside the actor scope is not shown through related records, duplicate warnings, timeline, or audit snippets.
- Empty related sections explain what is missing and what action can create data.

## Editor/form checklist

For create/edit pages or modals, verify:

- Field grouping follows business flow, not database column order.
- Required fields, validation errors, helper text, and disabled/processing states are visible near the field.
- Submit is protected from duplicate click.
- Long forms can use full-page flow; short quick actions can use modal.
- Modal must have title, description, focus behavior, reset-on-close, loading state, and mobile layout.
- Password/demo defaults only appear in local environment when requirement allows it.

## Dashboard/chart checklist

For dashboard/report screens, verify:

- Filters are scoped per screen/tab and reset predictably.
- Chart labels, legends, tooltips, empty messages, and headings are in Vietnamese.
- Loading/empty/error states exist for every chart region.
- Animation is subtle and helpful; do not make charts feel stiff or jumpy.
- Dark mode colors remain readable.
- Drill-down or links must apply the same data scope as the source metric.

## Navigation and app shell checklist

Verify:

- Scan the complete sidebar and complete authenticated route list whenever any navigation or permission rule changes; do not limit the review to the active feature.
- Every sidebar link uses the same permission or Policy ability as its target route, and every referenced permission exists in the canonical `config/crm.php` catalog.
- Direct URL access returns `403` when the actor cannot use a hidden menu item; personal routes are owner-scoped and record routes authorize through Policy.
- Sidebar active state is correct after navigation.
- Menu labels are consistent with module names in Vietnamese.
- Breadcrumb and page title match the current screen.
- Topbar slots for global search, quick create, notifications, user menu, and theme toggle remain stable.
- Navigation should not visibly flash or cause avoidable layout shift.

## Accessibility and responsive checklist

Verify:

- Semantic HTML and labels are present for form controls.
- Keyboard tab order is logical; focus visible is not removed.
- Buttons/links have accessible names.
- Status color is not the only meaning; text label is also present.
- Mobile/tablet/desktop breakpoints preserve critical actions.
- Light/dark/system theme do not flicker on load.

## Implementation workflow

Before editing UI, state:

- Target screen(s).
- Current inconsistency or UX issue.
- UI standard that will be applied.
- Requirement/GAP IDs affected.

While editing:

- Keep business logic out of Blade and JavaScript.
- Keep Livewire focused on UI state and delegate domain logic to Service/Action/Repository.
- Reuse existing components and style tokens/classes.
- Prefer small, local changes over broad visual rewrites.

After editing:

- Run `docker compose exec app php artisan view:clear` or `view:cache` when Blade changes need verification.
- Run targeted Livewire/feature tests for changed screens.
- Run frontend build only when JavaScript/CSS/Vite assets changed.
- Provide manual checks covering desktop, mobile, dark mode, loading/empty/error, and permission visibility.
