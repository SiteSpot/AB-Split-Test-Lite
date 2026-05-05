# Implementation details

## Cache / optimization compatibility

### NitroPack

AB Split Test marks its scripts as excluded from NitroPack optimizations by adding NitroPack’s `nitro-exclude` attribute to:

- Enqueued script tags (via `script_loader_tag` in `modules/support/cache.php`)
- Inline script tags (via `wp_inline_script_attributes` in `modules/support/cache.php`)

This is important for inline/localized config blocks (e.g. `ABST_CONFIG`, `btab_vars`, `bt_experiments`), because `script_loader_tag` does not affect inline scripts.

The exclusion attributes are added using `ABST_CACHE_EXCLUDES` (defined in `bt-bb-ab.php`), which includes:

- `nitro-exclude`
- `data-no-optimize="1"`
- `data-no-defer="1"`
- `data-no-minify="1"`
- `nowprocket`

The exclusion list can be customized via the `abst_exclude_js` filter.

Example:

```php
add_filter('abst_exclude_js', function($excludes) {
  $excludes[] = 'my-custom-handle';
  return $excludes;
});
```

## Test ideas

Test idea data lives on the `bt_experiments` post type as `abst_idea_*` post meta.

The durable rules are:

- `abst_idea_hypothesis` gates visibility of the editor's `Idea` tab
- if `abst_idea_hypothesis` is present and no `test_type` radio is selected yet, `js/experiment.js` defaults the editor to the `Idea` tab on load
- idea records share the post type with experiments but should stay out of the default runnable-test list
- idea editing remains an admin concern

## API, MCP, and CLI validation

REST, MCP, and WP-CLI test management flows now share the same normalization and validation helpers in `bt-bb-ab-validation.php`.

- `abst_normalize_api_input_params()` sanitizes and normalizes incoming integration payloads.
- `abst_validate_test_payload()` validates create/update payloads consistently.
- `abst_validate_conversion_parameters()` validates canonical conversion settings and companion fields.
- `abst_validate_magic_definition()` validates the structure of `magic_definition` payloads before they are stored.

Compatibility aliases are normalized before validation so older integrations continue to work while new integrations can target the canonical contract.

- `name` is mapped to `test_title`.
- `conversion_page` is mapped to `conversion_type` for integration payloads.
- Legacy underscore-style ecommerce values such as `woo_order_received` are normalized to canonical hyphenated values such as `woo-order-received`.

For magic tests, `magic_definition` items can carry explicit page scope metadata.

- `scope.page_id` and `scope.url` are recommended for precise targeting
- unscoped definitions remain valid for legacy integrations
- point-and-click creation should auto-populate scope when possible
- frontend runtime should check scope before applying a magic selector

This reduces drift between:

- `Bt_Ab_Tests::rest_create_test()`
- `abst_rest_update_test_settings()`
- `ABSPLITTEST_MCP_Server` tool handlers
- `ABSPLITTEST_CLI` commands

## Canonical conversion type behavior

Internally, ABSPLITTEST still stores the primary conversion trigger in the historical meta key `conversion_page`.

- For string-based conversion triggers, the stored value is the canonical conversion type such as `selector`, `url`, `time`, or `woo-order-received`.
- For page-visit conversions, the stored value is the numeric page ID.

Integration layers should treat `conversion_type` as the public API field and normalize values before storing them.

- The legacy alias `click` is normalized to `selector`.
- New integrations should send `selector`, not `click`.
- New integrations should send `test_title` and `conversion_type`, not the older `name` and `conversion_page` aliases.
- List/results endpoints should derive `conversion_type` from `conversion_page` rather than reading a separate `conversion_type` meta key.

Frontend config and runtime should mirror that compatibility model.

- localized config should expose both historical storage data and derived canonical read fields
- runtime should read canonical fields first and fall back to historical values
- page-goal updates must preserve the public `conversion_type = page` contract while still storing the numeric page ID historically

## Frontend experiment cache scope

ABSPLITTEST uses a cached post list for frontend experiment bootstrapping, but that cache must not mirror broad admin query semantics.

- Admin/reporting helpers can still use `post_status = any` when they intentionally need draft and pending tests.
- Frontend config building should cache only visitor-relevant experiment posts: `publish` and `complete`.
- The frontend cache is stored separately from the broader admin cache so public pages do not accidentally inherit draft/pending experiment data.
- Cache refresh paths clear both transients together to avoid stale cross-cache mismatches after edits.

## Public reports entitlement enforcement

The public reports module should not depend on `BT_BB_AB_Admin` for frontend/public requests.

- `modules/public-reports/public-reports.php` uses frontend-safe global helpers such as `bt_bb_ab_licence_details()` and `btab_user_level()` to evaluate entitlement
- share-link creation and share-button rendering can remain generally available
- the actual gate is enforced in `ABST_Public_Reports::handle_public_report()` after token validation but before template rendering
- non-Teams sites render an upgrade message instead of the report body

This avoids public fatals caused by admin-only class availability while preserving Teams-only access to the report content itself.

## Structured integration responses

REST and MCP responses should include normalized machine-usable fields in addition to human-readable summaries.

Important fields include:

- `conversion_type`
- `normalized`
- `applied_settings`
- `validation_warnings`

## Agency Hub connection handling

Agency Hub connection and sync flows are split between:

- `modules/support/agency_hub.php` for secret handling, AJAX sync, REST summary serving, and remote fetches
- `bt-bb-ab.php` for the Agency Hub admin page and connected-site management
- `js/plugin-tour.js` for child-site key regeneration in the settings UI

## Journey tracking identifier normalization

Journey tracking still writes file-based batches using a metadata line followed by event lines.

- `modules/journey.php` treats visitor identity as required only for `meta` records, because regular event rows inherit identity from the most recent metadata line in the batch
- the receiver normalizes visitor identity from `uuid`, `ab_advanced_id`, or `advancedId`
- `js/bt_conversion.js` now sends both the historical `uuid` field and the explicit `ab_advanced_id` alias on journey records for transport compatibility
- when the file is written, the normalized visitor identifier is still stored in the metadata line's historical `uuid` slot so existing readers and summaries keep working without a file-format migration

## Experiment editor sidebar behavior

The experiment editor keeps the right-hand WordPress sidebar (`div#side-sortables`) sticky in `admin/bt-bb-ab-admin.css`.

- `position: sticky`
- `top: 40px`

The `40px` top offset is the current intended spacing so the panel remains visible while leaving room near the top of the admin viewport.

## Experiment editor status notices

The experiment editor uses WordPress post-updated notices, but `bt_experiments` should not expose generic post wording such as `Post submitted.` for lifecycle actions.

- `bt-bb-ab.php` customizes the `post_updated_messages` output for the `bt_experiments` post type directly
- the notice text should reflect the effective lifecycle state after save
- `pending` is the paused state in the current admin/editor workflow and should display as `Split Test Paused.`
- `publish` should display as `Split Test Running.`
- `draft` should display as `Split Test Draft Saved.`
- `complete` should display as `Split Test Marked Complete.`

## Order value conversion help copy

The experiment editor's `Use Order Value` help panel should explain the benefit before the implementation details.

- frame the feature as optimizing for revenue, not just raw conversions
- describe the outcome as revenue per visitor rather than vague "value" wording
- explain the value-detection order only after the user understands why they would enable it

The hardened flow now enforces these rules:

- add/delete site admin posts must pass `abst_agency_hub_save`
- AJAX key regeneration must pass `abst_agency_regenerate_key`
- AJAX sync must pass `abst_agency_sync_site`
- hub-side add flow validates the remote site by calling `BT_BB_AB_Agency_Hub::request_remote_summary()` before saving the connection
- duplicate connections are rejected by either identical saved key or identical normalized site URL

`BT_BB_AB_Agency_Hub::request_remote_summary()` is the shared fetch path for both initial verification and later manual syncs.

It is responsible for:

- building the remote `wp-json/abst/v1/agency-summary` URL
- sending the `X-ABST-Secret` header
- parsing JSON responses
- extracting remote `error` or `message` payloads for clearer diagnostics
- caching successful sync responses in the site-key transient

This keeps the connection test path and refresh path behavior aligned instead of letting add and sync drift apart.

## Lite Version Conversion (Free Tier)

The following features have been disabled/stubbed for the lite (free) version:

### Backend Hard Limits
- **1 active test maximum**: `save_test_config` enforces a hard limit of 1 published `bt_experiments` post. Additional tests are forced to `draft` status.
- **1 variation maximum**: All test types (page, CSS, magic) are limited to exactly 1 variation.
- **No subgoals**: `goals` post meta is deleted on save; only the primary conversion trigger is retained.
- **No autocomplete**: `autocomplete_on` is forced to `0`.
- **No revenue tracking**: `conversion_use_order_value` is forced to `0`.

### Disabled Premium Features
- **AI/OpenAI integration**: All AJAX handlers, prompt construction, and UI hooks stubbed or removed.
- **Webhooks**: `btab_send_webhook()` returns `false` unconditionally.
- **Weekly reports**: Cron scheduling functions are no-ops.
- **Multi-armed bandit (MAB)**: `thompson_select_variation_for_query` and `weighted_random_selection` fall back to random selection.
- **Agency Hub**: Admin page content function shows an upgrade message.
- **Public reports**: Module include removed; REST API returns `report_url: null`.
- **Fingerprint/UUID tracking**: AJAX hooks commented out; backend handlers are no-ops.
- **Form-plugin conversions**: WooCommerce `checkout_update_order_meta` hook removed.

### Heatmap Limits
- **1 page maximum**: `save_settings` caps `heatmap_pages` to 1 entry.
- **3-day retention**: `save_settings` caps `heatmap_retention_length` to 3 days.

### UI Changes
- Premium settings in `admin/partials/single-site-display.php` are visually disabled with an "(upgrade)" button/link instead of being removed.
- AI-related settings are hidden.
- Trial/upsell banners are replaced with a single free-version notice.
- In the experiment editor, free-tier users now see upgrade CTAs for **Sub Goals** and **Autocomplete** instead of interactive controls that only work in higher tiers.
- In Magic point-and-click (`js/highlighter.js`), AI panels are now upgrade-only placeholders:
  - selecting an element no longer triggers AI requests for `type: magic`
  - the **AI Suggestions** accordion stays user-toggleable and shows an upgrade CTA when opened
  - **ChatCRO** is rendered as disabled/grayed and shows `Subscribe for AI features.` with an upgrade link

### bt-bb-ab.php selective recovery notes
- Keep the **Primary Conversion** block fully functional in the create-test modal, and keep subgoal/autocomplete restrictions additive (upsell messaging) rather than destructive UI behavior.
- In the create-test modal for free-tier users, **Sub Goals** and **Autocomplete** render upgrade CTAs instead of interactive controls.
- Frontend localized settings (`$btab_vars`) should continue reading from stored settings/filters (for compatibility) and should not be hardcoded globally to fixed values.

### Plugin Header
- Plugin name updated to `AB Split Test Lite`.
- Description updated to reflect free-tier limitations.
- Trial-related wording removed to comply with WordPress.org rules.

## WordPress.org plugin-check compliance (public reports + readme)

To satisfy WordPress.org free-plugin checks for bundled assets and metadata consistency:

- `modules/public-reports/templates/error-template.php` no longer loads remote Google Fonts.
- `modules/public-reports/templates/report-template.php` no longer loads remote Google Fonts or CDN Chart.js directly.
- `modules/public-reports/public-reports.php` enqueues local `js/chart.js` as `abst-public-report-chart` before rendering the standalone report template.
- `README.md` stable tag now matches the main plugin header version (`1.0.0`).

This keeps public templates self-contained while avoiding remote offloading and direct non-enqueued external asset tags that WordPress.org plugin checks flag.
