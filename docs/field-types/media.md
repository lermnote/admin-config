---
title: Media
parent: Field Types
nav_order: 3
---

# Media Field Types

## upload

URL-based upload field backed by the WP media picker. `library` restricts
the media modal to a mime type group (`image`, `video`, `audio`, or omit for
any file type).

```php
array(
    'id'          => 'entry_upload',
    'type'        => 'upload',
    'label'       => __( 'Entry upload', 'my-plugin' ),
    'library'     => 'image',
    'button_text' => __( 'Choose uploaded file', 'my-plugin' ),
    'remove_text' => __( 'Remove file', 'my-plugin' ),
    'default'     => '',
),
```

## media

Single attachment field. Stores an attachment array (id, url, sizes) rather
than a raw URL string.

```php
array(
    'id'          => 'entry_media',
    'type'        => 'media',
    'label'       => __( 'Entry media', 'my-plugin' ),
    'button_text' => __( 'Choose image', 'my-plugin' ),
    'remove_text' => __( 'Remove image', 'my-plugin' ),
    'default'     => array(),
),
```

## gallery

Ordered list of attachments.

```php
array(
    'id'          => 'entry_gallery',
    'type'        => 'gallery',
    'label'       => __( 'Entry gallery', 'my-plugin' ),
    'button_text' => __( 'Choose gallery images', 'my-plugin' ),
    'remove_text' => __( 'Clear gallery', 'my-plugin' ),
    'default'     => array(),
),
```

## image_select

Choice field where each option is illustrated with an image instead of text.
`choices` maps `value => image URL`.

```php
array(
    'id'      => 'entry_image_style',
    'type'    => 'image_select',
    'label'   => __( 'Entry image style', 'my-plugin' ),
    'choices' => array(
        'cover'  => 'https://example.test/cover.png',
        'split'  => 'https://example.test/split.png',
        'poster' => 'https://example.test/poster.png',
    ),
    'default' => 'cover',
),
```

## palette

Choice field where each option is a small set of swatch colors instead of a
single value. `choices` maps `value => array of hex colors`.

```php
array(
    'id'      => 'entry_palette',
    'type'    => 'palette',
    'label'   => __( 'Entry palette', 'my-plugin' ),
    'choices' => array(
        'cool' => array( '#0f172a', '#38bdf8', '#e0f2fe' ),
        'warm' => array( '#7c2d12', '#fb923c', '#fed7aa' ),
        'mono' => array( '#111827', '#6b7280', '#f9fafb' ),
    ),
    'default' => 'cool',
),
```
