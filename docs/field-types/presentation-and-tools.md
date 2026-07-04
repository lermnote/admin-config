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
    'label' => __( 'Display Settings', 'my-plugin' ),
),

array(
    'id'      => 'section_note',
    'type'    => 'content',
    'content' => '<p>' . esc_html__( 'These options only affect the public-facing archive.', 'my-plugin' ) . '</p>',
),
```

## notice

Same non-persisted display pattern as `content`, styled as an admin notice.

```php
array(
    'id'      => 'beta_notice',
    'type'    => 'notice',
    'content' => '<p>' . esc_html__( 'This section is in beta and may change.', 'my-plugin' ) . '</p>',
),
```

## code_editor

Plain-text code entry. `rows` controls the textarea height (defaults to 10).

```php
array(
    'id'          => 'custom_css',
    'type'        => 'code_editor',
    'label'       => __( 'Custom CSS', 'my-plugin' ),
    'rows'        => 14,
    'placeholder' => '.my-plugin { }',
    'default'     => '',
),
```

## wp_editor

Full `wp_editor()` rich text field.

```php
array(
    'id'      => 'welcome_message',
    'type'    => 'wp_editor',
    'label'   => __( 'Welcome message', 'my-plugin' ),
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
    'label'   => __( 'Backup', 'my-plugin' ),
    'save'    => false,
),
```
