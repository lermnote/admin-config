# Maintaining AdminConfig

Internal checklists for cutting a release. Not part of the published
documentation site — this file targets maintainers, not library consumers.

## Smoke Checklist

Use this checklist before tagging an alpha or merging larger Admin Config changes.

### Options page

Example: `examples/schema-demo-plugin`

- Open the demo options page.
- Switch between tabs and subsection groups.
- Save a normal field change and confirm the success notice and status pill update.
- Change a field in one tab, switch to another tab without saving, save there, and confirm both tabs persist.
- Search and save an `ajax_select` field, then re-open the page and confirm its hydrated label still renders correctly.
- Open the runtime debug panel and confirm the schema ID, store type, field modules, and data sources match the current page.
- Enter an invalid `release_slug` value such as `a` and save.
- Confirm the save is blocked, the field row is highlighted, and the inline error renders.
- Clear a multi-value field such as a multi-select, checkbox list, or empty `group`, trigger a validation error elsewhere, and confirm the cleared state is preserved after the redirect.
- Enter an invalid nested value inside `typography`, `accordion`, or `tabbed`, and confirm the exact nested control is highlighted while its containing panel opens automatically.
- Fix the slug and save again.
- Use reset for the current page and for all tabs.
- Export a snapshot, then import it back.

### Post metabox container

- Open a post or page edit screen with the demo plugin active.
- Change the demo metabox fields and save the entry.
- Re-open the entry and confirm the post meta persisted.
- Enter an invalid nested `entry_badge.slug` value such as `a` and confirm the edit screen comes back with an inline metabox notice and the nested control highlighted.

### Comment container

- Open a comment edit screen with the demo plugin active.
- Change one of the comment meta fields and save.
- Re-open the comment and confirm the value persisted.
- Enter an invalid nested `review_badge.slug` value and confirm the redirect comes back with an inline notice and the nested control highlighted.

### Profile container

- Open a user profile screen.
- Change the demo profile fields and save.
- Re-open the profile and confirm the value persisted.
- Enter an invalid nested `profile_badge.slug` value and verify the save does not overwrite the previous stored value.
- Confirm the profile screen shows an inline notice, preserves the submitted value, and highlights the exact nested control.

### Taxonomy container

- Open category create and edit screens.
- Save the demo taxonomy fields on an existing category.
- Re-open the category and confirm the term meta persisted.
- Enter an invalid nested `category_badge.slug` value and verify the save does not overwrite the previous stored value.
- Confirm both add/edit term forms replay submitted values and show an inline notice on the exact nested control after validation failure.

### Network options page

- In multisite, open the network demo settings page.
- Save a normal field change and confirm the value persists network-wide.
- Enter an invalid nested `shared_library.feed_slug` value and confirm the network save is blocked with inline validation feedback.

### Embedded mode

Example: `examples/embedded-theme-demo`

- Open the embedded theme options page.
- Save advanced fields such as `typography`, `icon`, `accordion`, and `tabbed`.
- Search and save the embedded `ajax_select` demo field and confirm the selected label rehydrates after reload.
- Open the demo metabox and confirm post meta persists.
- Enter an invalid metabox value and confirm the post edit screen re-renders the metabox with a validation notice.

### Regression notes

- Run `composer ci` before the manual pass.
- Run `composer test:integration` when a local WordPress install is available.
- Run `npm run test:integration` and `npm run test:e2e` when Docker / `wp-env` is available.
- Validation errors should block persistence for the affected save request.
- AJAX saves should return field-level errors without reloading the page.
- Non-JS options-page saves should show a flash notice and preserve submitted values for the active tab.
- Full-screen native containers should validate the whole submitted schema before any meta write occurs.

## Release Checklist

Use this checklist before cutting an alpha, beta, or stable package tag.

### Local Quality Gate

Run from the package root:

```bash
composer validate --strict
composer ci
npm ci
npm run check
composer test:integration
```

When Docker is available, also run:

```bash
npm run test:integration
npm run test:e2e
npm run test:wp:multisite
npm run test:wp:rest-contract
npm run test:e2e:block-editor
```

### Manual Sanity Pass

- Confirm the example plugin still boots in plugin-install mode.
- Confirm the embedded fixture theme still boots in embedded mode.
- Confirm WordPress loads `assets/build/admin-config.js` and falls back only
  when the built asset is intentionally absent in a source checkout.
- Confirm the release archive or GitHub Release attachment includes
  `assets/build/admin-config.js`, `assets/build/admin-config.asset.php`,
  `assets/build/block-panel.js`, and `assets/build/block-panel.asset.php`.
- Confirm the built asset metadata includes `wp-api-fetch` after transport
  changes.
- Confirm `npm run test:js-runtime` covers block-panel runtime load/save
  behavior when front-end runtime helpers change.
- Check options-page global save across multiple sections.
- Check reset current page and reset all tabs.
- Check import/export on the schema demo plugin.
- Check one validation failure path each for metabox, profile, taxonomy, comment, and network settings.
- Check at least one async field, one typography field, one accordion field, and one tabbed field.
- Confirm browser traces contain no AdminConfig `admin-ajax.php` requests.
- Confirm schema protocol examples still match `GET /schemas`,
  `GET /schemas/{schema_id}`, and `GET|POST /schemas/{schema_id}/values`.

### Docs and Examples

- Update `README.md` when public behavior, scripts, or support expectations change.
- Update `docs/support-matrix.md` when CI coverage or compatibility guarantees change.
- Update `CHANGELOG.md` with user-visible behavior changes.
- Keep `examples/schema-demo-plugin/` and `examples/embedded-theme-demo/` aligned with the supported onboarding path.

### Release Notes

- Mark the release channel clearly (`alpha`, `beta`, or `stable`).
- Call out any schema-facing or runtime-facing breaking changes.
- Link migration guidance when behavior or naming changed.
- Mention known limitations that remain intentionally out of scope for the release.
- For 0.3.0 and later, call out that AdminConfig `admin-ajax.php` transport
  actions were removed and JavaScript clients must use REST.
- For schema protocol changes, call out the protocol version, route aliases,
  and any field payload additions.
- For source releases, call out that contributors must run `npm ci` and
  `npm run build` before using block-editor panel assets locally.
