---
title: Home
layout: home
nav_order: 1
description: Schema-driven WordPress admin configuration infrastructure.
permalink: /
---

# Lerm AdminConfig

{: .fs-6 }

Schema-driven WordPress admin configuration infrastructure. Define options pages, metaboxes, profile screens, taxonomy screens, comment screens, and block editor panels — all from declarative PHP schemas.

{: .fs-3 .fw-300 }

[Get started now](#quick-start){: .btn .btn-primary .fs-5 .mb-4 .mb-md-0 .mr-2 }
[View on GitHub](https://github.com/lermnote/admin-config){: .btn .fs-5 .mb-4 .mb-md-0 }

---

## Features

- **Schema-driven** — Declare fields, sections, and containers in PHP arrays; the runtime compiles them into WordPress admin UI
- **Plugin and embedded modes** — Ship as a standalone plugin or embed inside a theme
- **Multiple container types** — Options pages, metaboxes, profile screens, taxonomy screens, comment screens, block editor panels
- **30+ built-in field types** — Text, textarea, switcher, select, checkbox, radio, color, media, typography, icon, accordion, tabbed, and more
- **REST API transport** — Save, import, export, and reset via `lerm-admin-config/v1` namespace
- **PHP 8.0+ and WordPress 6.5+** — Modern requirements, clean architecture

## Quick Start

The smallest copyable setup:

```php
use Lerm\AdminConfig\WordPress\PluginBootstrap;
use Lerm\AdminConfig\WordPress\Runtime;

PluginBootstrap::boot(
    __FILE__,
    static function ( Runtime $runtime ): void {
        $runtime->register( array(
            'id'       => 'my-options',
            'store'    => array( 'key' => 'my_plugin_options' ),
            'sections' => array(
                'general' => array(
                    'fields' => array(
                        array(
                            'id'      => 'site_name',
                            'type'    => 'text',
                            'default' => '',
                        ),
                    ),
                ),
            ),
        ) );
    }
);
```

## Documentation

Browse the full documentation:

- [Quick Start](quick-start) — Copyable onboarding path
- [Schema Protocol](schema-protocol) — Schema structure and compilation rules
- [Extension API](extension-api) — Custom field types, validators, data sources
- [Extension Recipes](extension-recipes) — Minimal extension snippets
- [Field Types](field-types) — Usage examples for every built-in field type
- [REST API](rest-api) — REST transport contract
- [Support Matrix](support-matrix) — Compatibility snapshot
- [Block Editor Field Matrix](block-editor-field-matrix) — Block-panel field type support
