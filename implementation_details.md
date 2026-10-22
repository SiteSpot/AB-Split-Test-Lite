# Implementation details

## Cache / optimization compatibility

### NitroPack

AB Split Test marks its scripts as excluded from NitroPack optimizations by adding NitroPack’s `nitro-exclude` attribute to:

- Enqueued script tags (via `script_loader_tag` in `modules/support/cache.php`)
- Inline script tags (via `wp_inline_script_attributes` in `modules/support/cache.php`)

This is important for inline/localized config blocks (e.g. `ABST_CONFIG`, `btab_vars`, `bt_experiments`), because `script_loader_tag` does not affect inline scripts.

The exclusion attributes come from `ABST_CACHE_EXCLUDES` (defined in `bt-bb-ab-core.php`), which includes:

- `data-cfasync="false"`
- `nitro-exclude`
- `data-no-optimize="1"`
- `data-no-defer="1"`
- `data-no-minify="1"`
- `nowprocket`

The list of script handles and variable names excluded from WP Rocket, LiteSpeed Cache and SiteGround Optimizer (`abst_exclude_js()` in `modules/support/cache.php`) can be customized via the `abst_exclude_js` filter.

Example:

```php
add_filter('abst_exclude_js', function($excludes) {
  $excludes[] = 'my-custom-handle';
  return $excludes;
});
```

## Test ideas

Test idea data lives on the `abst_experiments` post type (post status `abst_idea`) as `abst_idea_*` post meta.

The durable rules are:

- `abst_idea_hypothesis` gates visibility of the editor's `Idea` tab
- if `abst_idea_hypothesis` is present and no `test_type` radio is selected yet, `js/experiment.js` defaults the editor to the `Idea` tab on load
- idea editing remains an admin concern

## API and MCP validation

REST and MCP (Abilities API) test management flows share the same normalization and validation helpers in `bt-bb-ab-validation.php`.

- `abst_normalize_api_input_params()` sanitizes and normalizes incoming integration payloads.
- `abst_validate_test_payload()` validates create/update payloads consistently.
- `abst_validate_conversion_parameters()` validates conversion settings and companion fields.
- `abst_validate_magic_definition()` validates the structure of `magic_definition` payloads before they are stored.

Compatibility aliases are normalized before validation so older integrations continue to work:

- `name` is mapped to `test_title`.
- `conversion_page` is mapped to `conversion_type` for integration payloads.

For magic tests, every `magic_definition` item sent by an integration must carry scope (`scope.page_id`, `scope.url` or `"*"`); validation rejects it otherwise. Editor saves without scope default to `{"page_id": "*"}`. The frontend runtime checks scope before applying a magic selector.

This reduces drift between:

- `Bt_Ab_Tests::rest_create_test()`
- `abst_rest_update_test_settings()` (`bt-bb-ab-rest-update-settings.php`)
- the `absplittest/*` ability callbacks

## Canonical conversion type behavior

Lite supports two conversion types (`abst_get_supported_conversion_types()`): `page` (a visit to a page) and `selector` (a click on an element). Internally, the trigger is still stored in the historical meta key `conversion_page`.

- For a page-visit goal, the stored value is the numeric page ID.
- For an element-click goal, the stored value is `selector`, with the selector in `conversion_selector`.

Integration layers should treat `conversion_type` as the public API field.

- New integrations should send `test_title` and `conversion_type`, not the older `name` and `conversion_page` aliases.
- List/results endpoints should derive `conversion_type` from `conversion_page` rather than reading a separate `conversion_type` meta key.
- On the frontend, page goals are matched through `conversion_details[...].conversion_page_id` in `js/bt_conversion.js`.

## Frontend experiment cache scope

The plugin uses a cached post list for frontend experiment bootstrapping, but that cache must not mirror broad admin query semantics.

- Admin/reporting helpers can still use `post_status = any` when they intentionally need draft and pending tests (`abst_posts_cache`).
- Frontend config building caches only `draft`, `publish` and `abst_complete` experiment posts (`abst_posts_frontend_cache`); drafts are then skipped unless the current user can edit them, so that permission check stays outside the shared cache.
- The frontend cache is stored separately from the broader admin cache so public pages do not accidentally inherit draft/pending experiment data.
- Cache refresh paths clear both transients together to avoid stale cross-cache mismatches after edits.

## Structured integration responses

REST and MCP responses should include normalized machine-usable fields in addition to human-readable summaries.

Important fields include:

- `conversion_type`
- `normalized`
- `applied_settings`
- `validation_warnings`

## Journey tracking identity

Journey tracking writes file-based batches using a metadata line followed by event lines.

- `modules/journey.php` requires `uuid` on every record it receives (and `screen_size` on `meta` records)
- `js/bt_conversion.js` sends both `uuid` and `ab_advanced_id` (same value) on every journey record
- the visitor identifier is stored in the metadata line's `uuid` slot

## Experiment editor sidebar behavior

The experiment editor keeps the right-hand WordPress sidebar (`div#side-sortables`) sticky in `admin/bt-bb-ab-admin.css`, at 851px and wider only.

- `position: sticky`
- `top: 40px`

## Experiment editor status notices

The experiment editor uses WordPress post-updated notices, but `abst_experiments` should not expose generic post wording such as `Post submitted.` for lifecycle actions.

- `abst_custom_post_updated_messages()` in `bt-bb-ab-core.php` customizes the `post_updated_messages` output for the `abst_experiments` post type
- the notice text should reflect the effective lifecycle state after save
- `pending` is the paused state and displays as `Split Test Paused.`
- `publish` displays as `Split Test Running.`
- `draft` displays as `Split Test Draft Saved.`
- `abst_complete` displays as `Split Test Marked Complete.`

## Lite version (standalone, two-way tests)

AB Split Test Lite ships only the features it runs; Pro-only features are absent from the code rather than stubbed, hidden or capped. See mental_model.md, "Two-way tests" and "Lite version architecture".

- **Two versions per test, as named fields**: each magic element has `original` and `variation` strings (validated in `bt-bb-ab-validation.php`, errors `missing_magic_original` / `missing_magic_variation`), a full-page test stores one `variation_page` page ID (single `<select>` in the editor, `variation_page` in the API), and on-page elements carry `bt-variation="original|variation"` via `abst_normalize_onpage_variation()`. No list of variations is stored, accepted or sent to the front end.
- **No limits on what ships**: tests are unlimited; nothing forces tests to draft or caps settings on save.
- **Upsell**: Pro (including A/B/n tests with several variations) is mentioned only as information in the readme and the settings-page notice.
