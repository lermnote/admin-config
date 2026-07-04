---
title: Choice
parent: Field Types
nav_order: 2
---

# Choice Field Types

`select`, `radio`, `button_set`, and `checkbox_list` all share the same
`choices` shape: an associative array of `value => label`.

## select

```php
array(
    'id'          => 'entry_layout',
    'type'        => 'select',
    'label'       => __( 'Entry layout', 'my-plugin' ),
    'choices'     => array(
        'compact' => __( 'Compact', 'my-plugin' ),
        'feature' => __( 'Feature', 'my-plugin' ),
        'wide'    => __( 'Wide', 'my-plugin' ),
    ),
    'default'     => 'compact',
),
```

Choices can also come from a runtime data source instead of a literal array:

```php
array(
    'id'      => 'tone_preset',
    'type'    => 'select',
    'label'   => __( 'Tone preset', 'my-plugin' ),
    'choices' => $runtime->resolve_data_source( 'tone_presets' ),
    'default' => 'calm',
),
```

## radio

```php
array(
    'id'      => 'entry_format',
    'type'    => 'radio',
    'label'   => __( 'Entry format', 'my-plugin' ),
    'choices' => array(
        'standard'  => __( 'Standard', 'my-plugin' ),
        'editorial' => __( 'Editorial', 'my-plugin' ),
        'alert'     => __( 'Alert', 'my-plugin' ),
    ),
    'default' => 'standard',
),
```

## button_set

Same shape as `radio`, rendered as a segmented control.

```php
array(
    'id'      => 'entry_emphasis',
    'type'    => 'button_set',
    'label'   => __( 'Entry emphasis', 'my-plugin' ),
    'choices' => array(
        'normal'    => __( 'Normal', 'my-plugin' ),
        'spotlight' => __( 'Spotlight', 'my-plugin' ),
        'quiet'     => __( 'Quiet', 'my-plugin' ),
    ),
    'default' => 'normal',
),
```

## checkbox_list

Multi-value choices. `default` is an array of selected keys.

```php
array(
    'id'      => 'entry_channels',
    'type'    => 'checkbox_list',
    'label'   => __( 'Entry channels', 'my-plugin' ),
    'choices' => array(
        'homepage'   => __( 'Homepage', 'my-plugin' ),
        'newsletter' => __( 'Newsletter', 'my-plugin' ),
        'rss'        => __( 'RSS', 'my-plugin' ),
    ),
    'default' => array( 'homepage' ),
),
```

## checkbox

Without `choices`, renders a single boolean checkbox. With `choices`, behaves
like `checkbox_list`.

```php
// Single boolean checkbox
array(
    'id'      => 'accept_terms',
    'type'    => 'checkbox',
    'label'   => __( 'Accept terms', 'my-plugin' ),
    'default' => 0,
),
```

## switcher

Boolean toggle, functionally equivalent to a single `checkbox` but rendered
as a switch.

```php
array(
    'id'          => 'feature_enabled',
    'type'        => 'switcher',
    'label'       => __( 'Enable demo feature', 'my-plugin' ),
    'description' => __( 'Turns the demo feature on or off.', 'my-plugin' ),
    'default'     => 1,
),
```
