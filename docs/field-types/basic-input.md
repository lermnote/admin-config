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
    'label'       => __( 'Entry slug', 'my-plugin' ),
    'description' => __( 'A plain single-line text value.', 'my-plugin' ),
    'default'     => 'featured-entry',
    'placeholder' => 'featured-entry',
),
```

## url

```php
array(
    'id'          => 'template_endpoint',
    'type'        => 'url',
    'label'       => __( 'Template endpoint', 'my-plugin' ),
    'description' => __( 'Sanitized with esc_url_raw().', 'my-plugin' ),
    'default'     => 'https://example.com/templates.json',
),
```

## textarea

```php
array(
    'id'      => 'summary',
    'type'    => 'textarea',
    'label'   => __( 'Summary', 'my-plugin' ),
    'default' => __( 'Use one PHP schema to drive defaults, UI, and storage.', 'my-plugin' ),
),
```

## number

```php
array(
    'id'      => 'retry_limit',
    'type'    => 'number',
    'label'   => __( 'Retry limit', 'my-plugin' ),
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
    'label'       => __( 'Entry priority', 'my-plugin' ),
    'description' => __( 'Rendered as a range input.', 'my-plugin' ),
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
    'label'       => __( 'Entry score', 'my-plugin' ),
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
    'label'       => __( 'Entry review date', 'my-plugin' ),
    'default'     => '2026-04-26',
),
```

## color

```php
array(
    'id'          => 'accent_color',
    'type'        => 'color',
    'label'       => __( 'Accent color', 'my-plugin' ),
    'description' => __( 'Rendered with the WP color picker.', 'my-plugin' ),
    'default'     => '#2271b1',
),
```
