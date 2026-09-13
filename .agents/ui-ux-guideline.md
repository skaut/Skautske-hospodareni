# UI and UX guideline

Apply these rules to every new or modified user-facing route. Use the dashboard, `/platby/skupiny`, payment-group editing, and invoice settings as references for current behavior, not as HTML to copy.

## Page structure

- Use one global layout: navigation, optional submenu, breadcrumbs, `page-heading`, page content, and footer. Do not add module layouts for local variation.
- Put one `h1` and an icon-led `page-lead` in `page-heading`. The lead is a short, complete description of the page's job. End informational sentences with a period; do not add periods to button labels or short list items.
- On lists, keep filters left and primary actions right in one wrapping toolbar. On details and forms, place back navigation below the heading and the primary save action inside the form.
- Breadcrumbs are global navigation, not a replacement for the heading. Show them from the second meaningful level; render the current page as the last, unlinked item. Omit them on the `Přehled` hub.
- Every working, form, detail, and list page needs contextual help using the existing `data-help-*` attributes. Put it in the right sticky panel on desktop and after main content on mobile; respect the user's hidden-help preference. A pure hub may use only `page-lead`.

## Actions, forms, and messages

- Use existing Tabler/Bootstrap components, tokens, and icons. Use `btn-primary` for the primary action, `btn-outline-secondary` or `btn-light` for secondary actions, and confirmed `btn-outline-danger` for destructive actions.
- Keep every action available at 375 px. Use `d-flex flex-wrap gap-2` and give peer actions in one toolbar the same size. Compact `btn-sm` controls are appropriate for desktop toolbars; at 375 px, non-inline controls need a 44 × 44 px target unless embedded in normal text.
- New or modernized Nette forms use `class="inline-errors"`. Render field errors directly below their field with `.is-invalid` and `.invalid-feedback`; use a summary only for errors without a field. Client and server validation share the same Nette rules.
- Report completed actions and general failures through global `success`, `info`, `warning`, or `danger` flash messages. Do not add local toasts, `alert()`, duplicate field-error flashes, or HTML in new flash messages.

## Grids and tables

- Use `GridFactory::create()` for collections that users search, filter, sort, manage, or that can grow. Configure 20-item pagination, default sorting, and relevant search and filters. `/platby/skupiny` is the reference list.
- Use `createSimpleGrid()` only for small, fixed summaries without operational management. Use a manual HTML table only for static comparison or summary content without navigation, actions, or search.
- Include a column only when it supports identification, a decision, sorting, filtering, or context. Do not truncate long variable text with ellipsis solely to fit the grid; move it to the detail or expandable detail.
- Make the item name the primary navigation. Keep the `Akce` column last; render its buttons in one horizontal wrapping row, never as a vertical stack. Always put a grid in its controlled responsive wrapper.

## Accessibility, themes, and markup

- Maintain WCAG AA contrast: 4.5:1 for normal text and 3:1 for large text and controls. Every interactive element needs visible `:focus-visible`, hover, disabled, and error states in light and dark themes.
- At 375 px, do not cause horizontal page overflow; only `.table-responsive` inside a grid is exempt. No action may depend on hover or be outside the viewport.
- Use semantic HTML (`main`, `nav`, `section`, `aside`, correct heading hierarchy). Avoid inline styles, JavaScript hacks, unnecessary wrappers, and duplicate classes. When modifying a page, assess and clean up the entire template and related local CSS where needed.

## Environments and required review

- Use `APP_ENV` as the sole environment marker: `dev`, `test`, `beta`, or `prod`. Non-production environments show a prominent textual navigation badge with their own colour; production has no badge.
- After a UI change, run relevant tests and checks. Then review the complete changed route against this guideline at desktop (at least 1440 px) and mobile (375 px), in light and dark modes, including keyboard focus, navigation, flash messages, forms, help, and grids.
