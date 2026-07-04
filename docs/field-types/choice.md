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
    'label'       => 'Entry layout',
    'choices'     => array(
        'compact' => 'Compact',
        'feature' => 'Feature',
        'wide'    => 'Wide',
    ),
    'default'     => 'compact',
),
```

Choices can also come from a runtime data source instead of a literal array:

```php
array(
    'id'      => 'tone_preset',
    'type'    => 'select',
    'label'   => 'Tone preset',
    'choices' => $runtime->resolve_data_source( 'tone_presets' ),
    'default' => 'calm',
),
```

## radio

```php
array(
    'id'      => 'entry_format',
    'type'    => 'radio',
    'label'   => 'Entry format',
    'choices' => array(
        'standard'  => 'Standard',
        'editorial' => 'Editorial',
        'alert'     => 'Alert',
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
    'label'   => 'Entry emphasis',
    'choices' => array(
        'normal'    => 'Normal',
        'spotlight' => 'Spotlight',
        'quiet'     => 'Quiet',
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
    'label'   => 'Entry channels',
    'choices' => array(
        'homepage'   => 'Homepage',
        'newsletter' => 'Newsletter',
        'rss'        => 'RSS',
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
    'label'   => 'Accept terms',
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
    'label'       => 'Enable demo feature',
    'description' => 'Turns the demo feature on or off.',
    'default'     => 1,
),
```
