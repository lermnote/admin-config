---
title: Basic Input
parent: Field Types
nav_order: 1
---

# Basic Input Field Types

## text

```php
array(
    'id'          => 'entry_slug',
    'type'        => 'text',
    'label'       => 'Entry slug',
    'description' => 'A plain single-line text value.',
    'default'     => 'featured-entry',
    'placeholder' => 'featured-entry',
),
```

## url

```php
array(
    'id'          => 'template_endpoint',
    'type'        => 'url',
    'label'       => 'Template endpoint',
    'description' => 'Sanitized with esc_url_raw().',
    'default'     => 'https://example.com/templates.json',
),
```

## textarea

```php
array(
    'id'      => 'summary',
    'type'    => 'textarea',
    'label'   => 'Summary',
    'default' => 'Use one PHP schema to drive defaults, UI, and storage.',
),
```

## number

```php
array(
    'id'      => 'retry_limit',
    'type'    => 'number',
    'label'   => 'Retry limit',
    'min'     => 0,
    'max'     => 10,
    'step'    => 1,
    'default' => 3,
),
```

## slider

Range input backed by `min` / `max` / `step`, same options shape as `number`.

```php
array(
    'id'          => 'entry_priority',
    'type'        => 'slider',
    'label'       => 'Entry priority',
    'description' => 'Rendered as a range input.',
    'min'         => 1,
    'max'         => 5,
    'step'        => 1,
    'default'     => 3,
),
```

## spinner

Stepper input, same `min` / `max` / `step` options as `slider`.

```php
array(
    'id'          => 'entry_score',
    'type'        => 'spinner',
    'label'       => 'Entry score',
    'min'         => 0,
    'max'         => 10,
    'step'        => 1,
    'default'     => 2,
),
```

## date

```php
array(
    'id'          => 'entry_review_date',
    'type'        => 'date',
    'label'       => 'Entry review date',
    'default'     => '2026-04-26',
),
```

## color

```php
array(
    'id'          => 'accent_color',
    'type'        => 'color',
    'label'       => 'Accent color',
    'description' => 'Rendered with the WP color picker.',
    'default'     => '#2271b1',
),
```
