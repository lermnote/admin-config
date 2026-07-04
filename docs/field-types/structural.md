---
title: Structural
parent: Field Types
nav_order: 4
---

# Structural Field Types

## fieldset

Nests a fixed set of named sub-fields under one parent key. `default` mirrors
the nested field IDs.

```php
array(
    'id'          => 'entry_badge',
    'type'        => 'fieldset',
    'label'       => __( 'Entry badge', 'my-plugin' ),
    'description' => __( 'Nested validation blocks the parent save if a nested value is invalid.', 'my-plugin' ),
    'fields'      => array(
        array(
            'id'      => 'label',
            'type'    => 'text',
            'label'   => __( 'Label', 'my-plugin' ),
            'default' => __( 'Featured', 'my-plugin' ),
        ),
        array(
            'id'      => 'slug',
            'type'    => 'slug_text',
            'label'   => __( 'Badge slug', 'my-plugin' ),
            'default' => 'featured-entry',
        ),
    ),
    'default'     => array(
        'label' => __( 'Featured', 'my-plugin' ),
        'slug'  => 'featured-entry',
    ),
),
```

## group

Repeatable list of the same sub-field set — the user can add/remove rows.
`default` is an array of rows, each shaped like `fields`.

```php
array(
    'id'          => 'entry_links',
    'type'        => 'group',
    'label'       => __( 'Entry links', 'my-plugin' ),
    'fields'      => array(
        array(
            'id'      => 'label',
            'type'    => 'text',
            'label'   => __( 'Link label', 'my-plugin' ),
            'default' => __( 'Read more', 'my-plugin' ),
        ),
        array(
            'id'      => 'url',
            'type'    => 'url',
            'label'   => __( 'Link URL', 'my-plugin' ),
            'default' => 'https://example.test/read-more',
        ),
    ),
    'default'     => array(
        array(
            'label' => __( 'Read more', 'my-plugin' ),
            'url'   => 'https://example.test/read-more',
        ),
    ),
),
```

## accordion

Groups related fields into collapsible panels within one field slot. Each
`items` entry is its own mini fieldset with `id`, `title`, optional `open`,
and its own `fields`.

```php
array(
    'id'      => 'launch_accordion',
    'type'    => 'accordion',
    'label'   => __( 'Launch Accordion', 'my-plugin' ),
    'items'   => array(
        array(
            'id'     => 'intro',
            'title'  => __( 'Intro Panel', 'my-plugin' ),
            'open'   => true,
            'fields' => array(
                array(
                    'id'      => 'eyebrow',
                    'type'    => 'text',
                    'label'   => __( 'Eyebrow', 'my-plugin' ),
                    'default' => __( 'New release', 'my-plugin' ),
                ),
            ),
        ),
        array(
            'id'     => 'cta',
            'title'  => __( 'CTA Panel', 'my-plugin' ),
            'fields' => array(
                array(
                    'id'      => 'button_label',
                    'type'    => 'text',
                    'label'   => __( 'Button Label', 'my-plugin' ),
                    'default' => __( 'Try the demo', 'my-plugin' ),
                ),
            ),
        ),
    ),
    'default' => array(
        'intro' => array( 'eyebrow' => __( 'New release', 'my-plugin' ) ),
        'cta'   => array( 'button_label' => __( 'Try the demo', 'my-plugin' ) ),
    ),
),
```

## tabbed

Same shape as `accordion`, rendered as tabs instead of collapsible panels.
Add `default_tab` to control which tab opens first.

```php
array(
    'id'          => 'card_tabs',
    'type'        => 'tabbed',
    'label'       => __( 'Card Tabs', 'my-plugin' ),
    'default_tab' => 'primary',
    'items'       => array(
        array(
            'id'     => 'primary',
            'title'  => __( 'Primary Card', 'my-plugin' ),
            'fields' => array(
                array(
                    'id'      => 'title',
                    'type'    => 'text',
                    'label'   => __( 'Title', 'my-plugin' ),
                    'default' => __( 'Fast setup', 'my-plugin' ),
                ),
            ),
        ),
    ),
    'default'     => array(
        'primary' => array( 'title' => __( 'Fast setup', 'my-plugin' ) ),
    ),
),
```

## dimensions

Nested width/height object with a shared unit.

```php
array(
    'id'      => 'entry_dimensions',
    'type'    => 'dimensions',
    'label'   => __( 'Entry card size', 'my-plugin' ),
    'units'   => array( 'px', '%', 'rem' ),
    'default' => array(
        'width'  => '320',
        'height' => '180',
        'unit'   => 'px',
    ),
),
```

## spacing

Nested top/right/bottom/left object with a shared unit — same shape used for
margin/padding-style controls.

```php
array(
    'id'      => 'entry_spacing',
    'type'    => 'spacing',
    'label'   => __( 'Entry card spacing', 'my-plugin' ),
    'units'   => array( 'px', 'rem' ),
    'default' => array(
        'top'    => '8',
        'right'  => '12',
        'bottom' => '8',
        'left'   => '12',
        'unit'   => 'px',
    ),
),
```

## border

Composite width/style/color object per side, plus a shared style and color.

```php
array(
    'id'      => 'entry_border',
    'type'    => 'border',
    'label'   => __( 'Entry card border', 'my-plugin' ),
    'default' => array(
        'top'    => '1',
        'right'  => '1',
        'bottom' => '1',
        'left'   => '1',
        'style'  => 'solid',
        'color'  => '#2271b1',
    ),
),
```

## link_color

Normal and hover color pair.

```php
array(
    'id'      => 'entry_link_colors',
    'type'    => 'link_color',
    'label'   => __( 'Entry link colors', 'my-plugin' ),
    'default' => array(
        'color' => '#2271b1',
        'hover' => '#135e96',
    ),
),
```

## sorter

Drag-to-reorder list. Cannot be nested inside a `fieldset` or `group` — it
must be a top-level field in a section.

```php
array(
    'id'      => 'display_order',
    'type'    => 'sorter',
    'label'   => __( 'Display order', 'my-plugin' ),
    'choices' => array(
        'hero'     => __( 'Hero', 'my-plugin' ),
        'features' => __( 'Features', 'my-plugin' ),
        'pricing'  => __( 'Pricing', 'my-plugin' ),
    ),
    'default' => array( 'hero', 'features', 'pricing' ),
),
```
