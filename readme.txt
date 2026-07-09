=== Lerm Admin Config ===
Contributors: lermnote
Donate link: https://lerm.net
Tags: admin, options, schema, fields
Requires at least: 6.6
Requires PHP: 8.0
Tested up to: 6.8
Stable tag: 0.5.1
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Schema-driven WordPress admin configuration infrastructure for options pages,
metaboxes, taxonomy fields, profile screens, and comment meta.

== Description ==

Lerm Admin Config is a schema-driven WordPress admin configuration
infrastructure. Define your admin options pages, metaboxes, taxonomy fields,
and user profile panels entirely in PHP arrays — the runtime handles rendering,
validation, persistence, and REST API exposure.

Supports plugin-install mode (standalone plugin) and embedded mode (bundled
in a theme or another plugin).

== Installation ==

= Plugin mode =
1. Upload the plugin files to `/wp-content/plugins/lerm-admin-config/`.
2. Activate the plugin through the 'Plugins' screen.
3. Use `PluginBootstrap::boot()` to register your schemas.

= Embedded mode (Composer) =
1. `composer require lerm/admin-config`
2. Call `EmbeddedBootstrap::boot($assets_url)` from your theme or plugin.

== Frequently Asked Questions ==

= What field types are supported? =

Text, URL, textarea, number, color, switcher, select, radio, button set,
checkbox, checkbox list, media, gallery, code editor, WP editor, sorter,
image select, palette, slider, spinner, date, group, fieldset, notice,
heading, subheading, content, background, border, spacing, typography,
upload, backup, export, import, and custom types via the registry API.

= Does it work with the block editor? =

Yes, metaboxes registered via the schema render in both classic and block
editor contexts. A block-editor sidebar panel is also supported.

= Can I extend it with custom field types? =

Yes, use `FieldTypeRegistry::register()` to add custom types with render,
sanitize, validate, and serialize callbacks.

== Changelog ==

= 0.5.1 =
* `Framework::render_options_page()` factory so containers stop hand-constructing `OptionsPage` individually.

= 0.5.0 =
* Decomposed the ~1500-line `OptionsPage` god class into six focused classes (rendering, dependency evaluation, submission state, debug panel, lifecycle).
* Extracted a shared `FieldAttributeHelpers` trait across the field type classes.

= 0.4.2 =
* Added `docs/field-types/` usage examples for every built-in field type.
* Added `.gitattributes` so dev-only directories are excluded from the Composer distribution archive.
* Added `MAINTAINING.md` for maintainer-only release/regression checklists.

= 0.4.0 =
* First standalone-package release: REST contract browser coverage, package-local test bootstrap, and CI entry points for the extracted package.
* Async `ajax_select` fields backed by the REST data-source registry.
* Debug-mode runtime panel with schema, store, module, and data-source summaries.
* Block editor panel coverage across basic, choice, media, structured, design, typography, and background field types.
* Schema protocol v1 documents at `/schemas` and `/schemas/{schema_id}`.
* `wp-env` fixtures, multisite coverage, and Playwright smoke specs for plugin mode and embedded mode.
* Contributor-facing extension recipes for custom fields, validators, and data sources.

= 0.3.0 =
* Initial extraction slice: compiler, registry, framework, WordPress runtime.
* Field type registry with built-in, extended, async, design, and advanced catalogs.
* REST API endpoints for schemas and values.
* Plugin and embedded bootstrap modes.
* WordPress Playground compatibility.
