# Gotchas

## Experiment editor: only `abst_idea_hypothesis` should control the Idea tab

Tests live on the `abst_experiments` post type; ideas use the `abst_idea` post status. Experiment posts can carry partial `abst_idea_*` metadata over time, but the editor's extra Idea tab should only appear when `abst_idea_hypothesis` is populated.

### Symptom

- The editor shows an `Idea` tab for tests that only have a stray score or leftover partial idea meta.
- The tab appears inconsistently depending on which optional idea fields were saved.
- An idea-first record opens on `Configuration` even though there is no selected `test_type` yet.

### Mitigation in ABSPLITTEST

Use `abst_idea_hypothesis` as the single visibility gate for the editor tab, and read the remaining idea details from experiment post meta only after that gate passes.

When the hypothesis exists and no runnable test type is configured yet, default the editor to the `Idea` tab instead of `Configuration` (`js/experiment.js`).

## NitroPack: inline config/scripts can get optimized

NitroPack can defer/delay/optimize JavaScript in ways that can break A/B test initialization when configuration is embedded as inline scripts (for example, localized config blocks such as `btab_vars` and `bt_experiments`).

### Symptom

- A/B tests fail to initialize or track consistently.
- Variation assignment/tracking appears intermittent, especially when NitroPack is enabled and aggressive JS optimizations are active.

### Mitigation in ABSPLITTEST

`modules/support/cache.php` adds exclusion attributes to both:

- Enqueued script tags (via `script_loader_tag`)
- Inline script tags (via `wp_inline_script_attributes`)

So NitroPack sees `nitro-exclude` and skips optimizing these scripts.

## API integrations: `conversion_type` is public, but `conversion_page` is still the stored source of truth

The REST API and MCP abilities expose `conversion_type` (`page` or `selector`, see `abst_get_supported_conversion_types()`) as the public field for callers, but the plugin still stores the conversion trigger in the historical meta key `conversion_page` (a page ID for a page-visit goal, or `selector` for an element click).

### Symptom

- REST or MCP list/results responses show blank or incorrect `conversion_type` values.
- Integrations appear to save a conversion correctly, but later reads do not reflect it.
- Developers mistakenly add or read a separate `conversion_type` meta field that is not authoritative.

### Mitigation in ABSPLITTEST

Integrations validate public input through `bt-bb-ab-validation.php`, and list/results endpoints derive `conversion_type` from `conversion_page`.

## Tracking script: source and minified runtime must stay in sync

Pages load `js/bt_conversion-min.js` whenever it exists; `js/bt_conversion.js` is only loaded if the minified file is missing.

### Symptom

- A source-level fix in `js/bt_conversion.js` appears correct during review, but production pages still behave the old way.

### Mitigation in ABSPLITTEST

- update `js/bt_conversion.js`
- regenerate `js/bt_conversion-min.js` (the `.github/workflows/minify-bt-conversion.yml` workflow does this on push)

## Journey tracking: every record carries `uuid`

Journey logs are written as a batch with one `meta` line followed by event rows. The receiver in `modules/journey.php` requires `type`, `timestamp`, `post_id` and `uuid` on every record (plus `screen_size` on `meta` records) and logs `Missing field uuid in journey record` when one is absent.

### Mitigation in ABSPLITTEST

`js/bt_conversion.js` sends both `uuid` and `ab_advanced_id` (the same value, from `abstGetAdvancedId()`) on every outbound record. Keep the two sides in step if either changes.

## Magic tests: generic selectors must carry explicit page scope in integrations

Generic selectors such as `h1`, `.button`, or `a span` are not safe to treat as globally unique across a site.

### Symptom

- API-created or MCP-created magic tests apply on the wrong page.
- A selector works in the editor page where it was authored but matches extra elements elsewhere.
- Relying on body classes like `page-id-123` works on some themes/builders but not all of them.

### Mitigation in ABSPLITTEST

Magic-definition items created through integrations must include `scope.page_id` or `scope.url` (or `"*"`); validation rejects them otherwise (`missing_magic_scope`).

- `scope.page_id` is preferred when available.
- `scope.url` is normalized to a path fragment and matched against the current pathname.
- Point-and-click creation auto-populates both when possible.
- Frontend runtime checks scope before applying the selector.

## Test status support: `complete` is a real lifecycle state and must stay aligned across integration layers

Completed tests use the `abst_complete` post status (ideas use `abst_idea`). API callers read and write them as `complete` and `idea`; `abst_status_from_api()` and `abst_status_for_api()` map between the two.

### Symptom

- A test can become complete in WordPress/admin flows, but an integration rejects the same status.
- List endpoints silently omit completed tests.
- REST, MCP and ability schemas disagree on which statuses are valid.

### Mitigation in ABSPLITTEST

The shared status allowlist includes `complete`, and list-style filters normalize MCP-style `all` to the internal `any` semantics.

## Experiment editor notices: custom post types can fall back to generic WordPress save text

The plugin uses a custom post type for experiments, so WordPress admin notices must be overridden on that post type's message map, not just the generic `post` map.

### Symptom

- pausing a test shows `Post submitted.` instead of a test-specific lifecycle notice
- running or draft saves sometimes show generic WordPress post wording rather than experiment wording

### Mitigation in ABSPLITTEST

- customize `post_updated_messages` for `abst_experiments` (`abst_custom_post_updated_messages()`)
- keep the notice text aligned with the resulting lifecycle state
- treat `pending` as the paused state in admin/editor notice text

## Experiment editor sidebar: sticky offset is tuned for the admin viewport

The experiment editor uses a sticky right-hand sidebar so save/status controls stay visible while scrolling.

### Symptom

- The sidebar appears too low and wastes vertical space.
- The sidebar sits too close to the top and feels cramped against the admin chrome.

### Mitigation in ABSPLITTEST

`admin/bt-bb-ab-admin.css` uses `div#side-sortables { position: sticky; top: 40px; }`, only at 851px and wider (WordPress's two-column edit layout). Below that the sidebar is stacked above the content and must not be sticky.

## WordPress.org checks: avoid remote assets and unescaped dynamic JS strings in admin output

WordPress.org plugin checks can flag both remote script/style sources and dynamic values interpolated into inline JavaScript output.

### Symptom

- `PluginCheck.CodeAnalysis.EnqueuedResourceOffloading.OffloadedContent` for CDN assets.
- `WordPress.WP.EnqueuedResources.NonEnqueuedScript` for script-tag snippets in output strings.
- `WordPress.Security.EscapeOutput.OutputNotEscaped` in inline admin JS blocks where variables/constants are concatenated into quoted JavaScript.

### Mitigation in ABSPLITTEST Lite

- Bundle third-party assets locally and enqueue them with `plugins_url(...)`.
- For JS snippet examples shown in admin fields, avoid direct `<script ...>` literals in source code by splitting the tag string and escaping for attribute output.
- For inline JavaScript values, use `wp_json_encode()` / `esc_js()` instead of raw concatenation.
