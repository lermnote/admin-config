---
title: Field Types
nav_order: 8
has_children: true
---

# Field Types

Usage examples for every built-in field type, grouped by purpose. Each
example is a single field definition you can drop into a `sections.*.fields`
array — see [Quick Start](quick-start) for the surrounding schema
boilerplate (`id`, `container`, `store`, `sections`).

All examples on these pages are adapted from the working demo plugin at
`examples/schema-demo-plugin/`, so every snippet is exercised by the
project's own smoke tests.

- [Basic Input](field-types/basic-input) — text, url, textarea, number, slider, spinner, date, color
- [Choice](field-types/choice) — select, radio, button_set, checkbox, checkbox_list, switcher
- [Media](field-types/media) — upload, media, gallery, image_select, palette
- [Structural](field-types/structural) — fieldset, group, accordion, tabbed, dimensions, spacing, border, link_color, sorter
- [Advanced](field-types/advanced) — typography, background, icon
- [Async](field-types/async) — ajax_select
- [Presentation & Tools](field-types/presentation-and-tools) — heading, subheading, content, notice, code_editor, wp_editor, backup_tools

For registering an entirely new field type (not listed here), see
[Extension Recipes](extension-recipes).
