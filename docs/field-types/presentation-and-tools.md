---
title: Presentation & Tools
parent: Field Types
nav_order: 7
---

# Presentation & Tools Field Types

## heading / subheading / content

Display-only fields — nothing is persisted (`persist: false`), so no
`default` is needed. Supply either `label` (auto-wrapped in `<h3>`/`<h4>`/`<p>`
depending on variant) or a raw `content` HTML string.

```php
array(
    'id'    => 'section_intro',
    'type'  => 'heading',
    'label' => 'Display Settings',
),

array(
    'id'      => 'section_note',
    'type'    => 'content',
    'content' => '<p>These options only affect the public-facing archive.</p>',
),
```

## notice

Same non-persisted display pattern as `content`, styled as an admin notice.

```php
array(
    'id'      => 'beta_notice',
    'type'    => 'notice',
    'content' => '<p>This section is in beta and may change.</p>',
),
```

## code_editor

Plain-text code entry. `rows` controls the textarea height (defaults to 10).

```php
array(
    'id'          => 'custom_css',
    'type'        => 'code_editor',
    'label'       => 'Custom CSS',
    'rows'        => 14,
    'placeholder' => '.acme-widget { }',
    'default'     => '',
),
```

## wp_editor

Full `wp_editor()` rich text field.

```php
array(
    'id'      => 'welcome_message',
    'type'    => 'wp_editor',
    'label'   => 'Welcome message',
    'default' => '',
),
```

## backup_tools

Renders export/import controls for the current schema's stored values. Set
`'save' => false` to omit backup_tools' own save button when it shares a
section with fields that already have one.

```php
array(
    'id'      => 'backup',
    'type'    => 'backup_tools',
    'label'   => 'Backup',
    'save'    => false,
),
```
