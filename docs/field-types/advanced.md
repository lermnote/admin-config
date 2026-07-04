---
title: Advanced
parent: Field Types
nav_order: 5
---

# Advanced Field Types

## typography

Composite font object. `style`, `letter_spacing`, `align`, and `units`
toggle which sub-controls are rendered; `default` must supply every key the
enabled sub-controls expect.

```php
array(
    'id'             => 'brand_typography',
    'type'           => 'typography',
    'label'          => __( 'Brand Typography', 'my-plugin' ),
    'style'          => true,
    'letter_spacing' => true,
    'align'          => true,
    'units'          => array( 'px', 'rem' ),
    'default'        => array(
        'font-family'    => 'Inter, system-ui, sans-serif',
        'font-weight'    => '700',
        'font-style'     => 'normal',
        'font-size'      => '2.25',
        'unit'           => 'rem',
        'line-height'    => '1.15',
        'letter-spacing' => '0',
        'text-align'     => 'left',
        'color'          => '#0f172a',
    ),
),
```

## background

Composite object covering color, gradient, image, and positioning.
Individual capability flags (`background_gradient`, `background_origin`,
`background_clip`, `background_blend_mode`) opt into extra sub-controls; only
enable the ones you actually use to keep the UI compact.

```php
array(
    'id'                           => 'entry_background',
    'type'                         => 'background',
    'label'                        => __( 'Entry background', 'my-plugin' ),
    'background_gradient'          => true,
    'background_origin'            => true,
    'background_clip'              => true,
    'background_blend_mode'        => true,
    'background_image_button_text' => __( 'Choose background image', 'my-plugin' ),
    'default'                      => array(
        'background-color'              => '#f8fafc',
        'background-gradient-color'     => '#e0f2fe',
        'background-gradient-direction' => 'to right',
        'background-image'              => array(),
        'background-position'           => 'center center',
        'background-repeat'             => 'no-repeat',
        'background-attachment'         => 'scroll',
        'background-size'               => 'cover',
        'background-origin'             => 'padding-box',
        'background-clip'               => 'border-box',
        'background-blend-mode'         => 'normal',
    ),
),
```

## icon

Choice field rendered as a curated Dashicons picker. `choices` maps a
`dashicons-*` class name to a label.

```php
array(
    'id'      => 'feature_icon',
    'type'    => 'icon',
    'label'   => __( 'Feature Icon', 'my-plugin' ),
    'choices' => array(
        'dashicons-lightbulb' => __( 'Idea', 'my-plugin' ),
        'dashicons-megaphone' => __( 'Launch', 'my-plugin' ),
        'dashicons-chart-bar' => __( 'Analytics', 'my-plugin' ),
    ),
    'default' => 'dashicons-lightbulb',
),
```
