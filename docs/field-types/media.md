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
    'label'       => 'Entry upload',
    'library'     => 'image',
    'button_text' => 'Choose uploaded file',
    'remove_text' => 'Remove file',
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
    'label'       => 'Entry media',
    'button_text' => 'Choose image',
    'remove_text' => 'Remove image',
    'default'     => array(),
),
```

## gallery

Ordered list of attachments.

```php
array(
    'id'          => 'entry_gallery',
    'type'        => 'gallery',
    'label'       => 'Entry gallery',
    'button_text' => 'Choose gallery images',
    'remove_text' => 'Clear gallery',
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
    'label'   => 'Entry image style',
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
    'label'   => 'Entry palette',
    'choices' => array(
        'cool' => array( '#0f172a', '#38bdf8', '#e0f2fe' ),
        'warm' => array( '#7c2d12', '#fb923c', '#fed7aa' ),
        'mono' => array( '#111827', '#6b7280', '#f9fafb' ),
    ),
    'default' => 'cool',
),
```
