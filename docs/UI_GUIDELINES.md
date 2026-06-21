# ERP UI Guidelines

This document is the visual contract for every screen added to the ERP. The supplied Pattern RMS dashboard screenshot is the primary design reference.

## Visual Direction

- Use a polished enterprise SaaS aesthetic: calm, precise, information-dense, and spacious enough to scan quickly.
- Use a deep navy fixed sidebar and a very light blue-gray application canvas.
- Use white cards with subtle cool-gray borders, soft shadows, and rounded corners.
- Blue is the primary action and active-state color. Cyan, violet, green, amber, and red are reserved for data categories and status feedback.
- Avoid decorative gradients, oversized illustrations, glass effects, and heavy shadows.

## Application Shell

- Desktop sidebar: approximately 280px wide, full height, independently scrollable, and fixed to the left.
- Topbar: white, approximately 72-80px high, with global search on the left and language, notifications, and user controls on the right.
- Main canvas: pale blue-gray background with 28-36px desktop padding.
- Sidebar header contains product identity; an organization/workspace selector sits below it.
- Use the official Pattern Vacation Homes Rental logo from `public/brand/pattern-logo.jpeg` for primary product branding.
- Navigation is grouped by uppercase section labels. Active links use a muted blue background with bright blue icon treatment.
- Keep sidebar entries role- and permission-aware. Do not show inaccessible or unbuilt modules.

## Typography

- Use a clean sans-serif family with strong legibility.
- Page titles are dark navy, bold, and compact rather than oversized.
- Body and supporting copy use muted slate-blue.
- Labels, metadata, and chart legends use smaller type with clear hierarchy.
- Numeric KPIs use bold tabular-looking figures and concise units.

## Components

- Cards use 14-18px corner radii, 1px cool-gray borders, and minimal elevation.
- KPI cards include a small tinted icon tile, label, strong value, trend/supporting text, and optional overflow menu.
- Inputs and buttons use 10-12px corner radii and visible focus rings.
- Data visualizations use clean axes, light grid lines, compact legends, and the established blue/cyan/violet palette.
- Tables use comfortable row height, subtle separators, sticky headers where useful, and responsive overflow.
- Empty, loading, error, and permission-denied states must visually match the same card system.

## Responsive Behavior

- Collapse the sidebar into an off-canvas drawer on tablet and mobile.
- Preserve a compact sticky topbar with a menu trigger and essential actions.
- Reflow four-column KPI areas to two columns and then one column.
- Stack chart and detail panels without horizontal page overflow.
- Keep touch targets at least 44px and ensure forms work comfortably on narrow screens.

## Accessibility And Consistency

- Maintain WCAG-compliant contrast and visible keyboard focus.
- Never communicate state with color alone.
- Use a consistent icon family and avoid text glyphs as production icons.
- Support both English and Arabic layouts when localization is introduced, including RTL shell mirroring.
- Reuse shared Blade components and design tokens instead of styling each module independently.

## Implementation Rule

Any new UI should be checked against this guide and the supplied reference before completion. New modules may extend the component library, but they should not introduce a separate visual language.
