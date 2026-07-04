---
title: Structural
parent: Field Types
nav_order: 4
---

# Structural Field Types

## fieldset

Nests a fixed set of named sub-fields under one parent key. `default` mirrors
the nested field IDs. (`badge_text` below is a custom type, not a built-in
one — see [Extension Recipes](/extension-recipes) for how it's registered.)

```php
array(
    'id'          => 'entry_badge',
    'type'        => 'fieldset',
    'label'       => 'Entry badge',
    'description' => 'Nested validation blocks the parent save if a nested value is invalid.',
    'fields'      => array(
        array(
            'id'      => 'label',
            'type'    => 'text',
            'label'   => 'Label',
            'default' => 'Featured',
        ),
        array(
            'id'      => 'slug',
            'type'    => 'badge_text',
            'label'   => 'Badge slug',
            'default' => 'featured-entry',
        ),
    ),
    'default'     => array(
        'label' => 'Featured',
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
    'label'       => 'Entry links',
    'fields'      => array(
        array(
            'id'      => 'label',
            'type'    => 'text',
            'label'   => 'Link label',
            'default' => 'Read more',
        ),
        array(
            'id'      => 'url',
            'type'    => 'url',
            'label'   => 'Link URL',
            'default' => 'https://example.test/read-more',
        ),
    ),
    'default'     => array(
        array(
            'label' => 'Read more',
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
    'label'   => 'Launch Accordion',
    'items'   => array(
        array(
            'id'     => 'intro',
            'title'  => 'Intro Panel',
            'open'   => true,
            'fields' => array(
                array(
                    'id'      => 'eyebrow',
                    'type'    => 'text',
                    'label'   => 'Eyebrow',
                    'default' => 'New release',
                ),
            ),
        ),
        array(
            'id'     => 'cta',
            'title'  => 'CTA Panel',
            'fields' => array(
                array(
                    'id'      => 'button_label',
                    'type'    => 'text',
                    'label'   => 'Button Label',
                    'default' => 'Try the demo',
                ),
            ),
        ),
    ),
    'default' => array(
        'intro' => array( 'eyebrow' => 'New release' ),
        'cta'   => array( 'button_label' => 'Try the demo' ),
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
    'label'       => 'Card Tabs',
    'default_tab' => 'primary',
    'items'       => array(
        array(
            'id'     => 'primary',
            'title'  => 'Primary Card',
            'fields' => array(
                array(
                    'id'      => 'title',
                    'type'    => 'text',
                    'label'   => 'Title',
                    'default' => 'Fast setup',
                ),
            ),
        ),
    ),
    'default'     => array(
        'primary' => array( 'title' => 'Fast setup' ),
    ),
),
```

## dimensions

Nested width/height object with a shared unit.

```php
array(
    'id'      => 'entry_dimensions',
    'type'    => 'dimensions',
    'label'   => 'Entry card size',
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
    'label'   => 'Entry card spacing',
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
    'label'   => 'Entry card border',
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
    'label'   => 'Entry link colors',
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
    'label'   => 'Display order',
    'choices' => array(
        'hero'     => 'Hero',
        'features' => 'Features',
        'pricing'  => 'Pricing',
    ),
    'default' => array( 'hero', 'features', 'pricing' ),
),
```
