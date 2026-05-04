# Gotchas

## Admin A/B test list: `idea` posts share the post type but should stay out of the default list

Test ideas are stored on the same `bt_experiments` post type as runnable tests.

### Symptom

- The main `AB Split Test` list shows rows labeled `Test Idea` mixed in with active or draft tests.
- The `Active Tests` count and table feel polluted by planning records that are not runnable experiments.

### Mitigation in ABSPLITTEST

Exclude `idea` from the default `bt_experiments` admin query. Keep ideas accessible only through:

- the `Ideas` status filter
- the dedicated `Test Ideas` admin page

## Experiment editor: only `abst_idea_hypothesis` should control the Idea tab

Experiment posts can carry partial `abst_idea_*` metadata over time, but the editor's extra Idea tab should only appear when `abst_idea_hypothesis` is populated.

### Symptom

- The editor shows an `Idea` tab for tests that only have a stray score or leftover partial idea meta.
- The tab appears inconsistently depending on which optional idea fields were saved.
- An idea-first record opens on `Configuration` even though there is no selected `test_type` yet.

### Mitigation in ABSPLITTEST

Use `abst_idea_hypothesis` as the single visibility gate for the editor tab, and read the remaining idea details from experiment post meta only after that gate passes.

When the hypothesis exists and no runnable test type is configured yet, default the editor to the `Idea` tab instead of `Configuration`.

## NitroPack: inline config/scripts can get optimized

NitroPack can defer/delay/optimize JavaScript in ways that can break A/B test initialization when configuration is embedded as inline scripts (for example, localized config blocks such as `ABST_CONFIG`, `btab_vars`, and `bt_experiments`).

### Symptom

- A/B tests fail to initialize or track consistently.
- Variation assignment/tracking appears intermittent, especially when NitroPack is enabled and aggressive JS optimizations are active.

### Mitigation in ABSPLITTEST

AB Split Test adds exclusion attributes to both:

- Enqueued script tags (via `script_loader_tag`)
- Inline script tags (via `wp_inline_script_attributes`)

So NitroPack sees `nitro-exclude` and skips optimizing these scripts.

## API integrations: `conversion_type` is public, but `conversion_page` is still the stored source of truth

The API, MCP, and CLI integrations expose `conversion_type` as the public field for callers, but ABSPLITTEST still stores the primary conversion trigger in the historical meta key `conversion_page`.

### Symptom

- REST or MCP list/results responses show blank or incorrect `conversion_type` values.
- Integrations appear to save a conversion correctly, but later reads do not reflect it.
- Developers mistakenly add or read a separate `conversion_type` meta field that is not authoritative.

### Mitigation in ABSPLITTEST

Integrations now normalize and validate public input through `bt-bb-ab-validation.php`, and list/results endpoints derive `conversion_type` from `conversion_page`.

The legacy alias `click` is normalized to the canonical `selector` value for integration payloads and responses.

## Frontend conversion compatibility: source and minified runtime must stay in sync

ABSPLITTEST now localizes canonical conversion read fields such as `conversion_type` and `conversion_page_id` for frontend runtime use while preserving historical `conversion_page` data for backward compatibility.

### Symptom

- A source-level fix in `js/bt_conversion.js` appears correct during review, but production pages still behave as if only legacy `conversion_page` is supported.
- Old tests keep working locally in debug/source mode, while production pages continue using stale conversion logic.

### Mitigation in ABSPLITTEST

- update `js/bt_conversion.js`
- regenerate `js/bt_conversion-min.js`
- verify the localized frontend config still exposes both `conversion_type`/`conversion_page_id` and legacy `conversion_page`

## Journey tracking: event rows do not need their own `uuid` when a batch metadata line already carries identity

Journey logs are written as a batch with one `meta` line followed by event rows. The file format stores visitor identity on the `meta` line, not on every event line.

### Symptom

- server logs show `Missing field uuid in journey record`
- journey or heatmap batches are rejected even though the metadata record contains the visitor identifier
- this is more likely when runtime tracking prefers `ab-advanced-id` and the receiver still hard-requires a literal `uuid` field everywhere

### Mitigation in ABSPLITTEST

- treat visitor identity as required only for `meta` records in `modules/journey.php`
- accept `uuid`, `ab_advanced_id`, or `advancedId` as transport aliases
- continue storing the normalized identifier in the metadata line's historical `uuid` slot for backward-compatible file parsing
- have `js/bt_conversion.js` include both `uuid` and `ab_advanced_id` on outbound journey payloads

## Magic tests: generic selectors must carry explicit page scope in integrations

Generic selectors such as `h1`, `.button`, or `a span` are not safe to treat as globally unique across a site.

### Symptom

- API-created or MCP-created magic tests apply on the wrong page.
- A selector works in the editor page where it was authored but matches extra elements elsewhere.
- Relying on body classes like `page-id-123` works on some themes/builders but not all of them.

### Mitigation in ABSPLITTEST

Magic-definition items created through integrations can include `scope.page_id` or `scope.url` for safer targeting.

- `scope.page_id` is preferred when available.
- `scope.url` is normalized to a path fragment and matched against the current pathname.
- Point-and-click creation auto-populates both when possible.
- Frontend runtime checks scope before applying the selector.

## Test status support: `complete` is a real lifecycle state and must stay aligned across integration layers

The plugin uses `complete` as a real post status when tests auto-finish or are manually marked done.

### Symptom

- A test can become `complete` in WordPress/admin flows, but an integration rejects the same status.
- List endpoints silently omit completed tests.
- CLI, REST, MCP, and ability schemas disagree on which statuses are valid.

### Mitigation in ABSPLITTEST

 The shared status allowlist again includes `complete`, and list-style filters normalize MCP-style `all` to the internal `any` semantics.

## Experiment editor notices: custom post types can fall back to generic WordPress save text

ABSPLITTEST uses a custom post type for experiments, so WordPress admin notices must be overridden on that post type's message map, not just the generic `post` map.

### Symptom

- pausing a test shows `Post submitted.` instead of a test-specific lifecycle notice
- running or draft saves sometimes show generic WordPress post wording rather than experiment wording

### Mitigation in ABSPLITTEST

- customize `post_updated_messages` for `bt_experiments`
- keep the notice text aligned with the resulting lifecycle state
- treat `pending` as the paused state in admin/editor notice text

## Order value help text: feature copy can explain mechanics before value

The `Use Order Value` help panel sits in the experiment editor, where users are deciding what success means for a test.

### Symptom

- the panel explains implementation details before the business benefit
- wording like `measure conversion value` or `stats based on revenue/person` feels awkward or unclear
- users may understand how values are detected without understanding why they should enable the option

### Mitigation in ABSPLITTEST

- lead with the decision: use it when revenue matters more than raw conversion count
- describe the outcome as revenue per visitor
- keep the value lookup order as supporting detail after the main benefit statement

## Device size targeting: server-side rendering cannot detect viewport width

Full-page SSR tests (`?ssr=1`) must approximate device category from the User-Agent string because PHP cannot access the browser viewport.

### Symptom

- A full-page SSR test targets "Desktop" but mobile visitors are still redirected into the test, or vice versa.
- Server logs show the detected category differs from what the visitor's actual viewport would suggest.

### Cause

- PHP runs before any JavaScript executes, so `window.innerWidth` is unavailable.
- User-Agent sniffing is a coarse proxy: a desktop browser window resized to 600px still reports as desktop, and some tablets report as mobile.

### How it works

| Visitor | User-Agent | SSR detection | JS detection (actual) |
|---------|-----------|---------------|----------------------|
| iPhone | iPhone | mobile | mobile |
| iPad | iPad | tablet | tablet (but landscape may be desktop-width) |
| Desktop browser at 600px | Desktop | desktop | mobile |
| Android tablet | Android without Mobile | tablet | tablet |
| Foldable phone (unfolded) | Mobile | mobile | desktop-width |

### Mitigation in ABSPLITTEST

- SSR assigns a coarse category from User-Agent and stores it in the tracking cookie as the device size.
- For accurate viewport-based filtering, rely on JavaScript mode (default for non-SSR tests) which uses `window.innerWidth`.
- Combined targeting values (`desktop_tablet`, `tablet_mobile`) work on SSR but still suffer from the same approximation.

### Where implemented

- `js/bt_conversion.js` (frontend viewport detection)
- `bt-bb-ab.php` `handle_abtest_query_param()` (SSR User-Agent approximation)

## Public reports: frontend/public requests must not call admin-only classes

Public report URLs are handled on frontend request flow, not inside the wp-admin bootstrap.

### Symptom

- opening a shareable report URL throws a fatal like `Class "BT_BB_AB_Admin" not found`
- normal admin results pages may still work because the admin class is loaded there

### Cause

- frontend/public report code directly calls an admin-only class for license gating
- the admin class is not guaranteed to be loaded on public requests

### Mitigation in ABSPLITTEST

- do not use `BT_BB_AB_Admin` as a hard dependency in `modules/public-reports/public-reports.php`
- use frontend-safe helpers such as `bt_bb_ab_licence_details()` and `btab_user_level()` instead
- enforce the Teams entitlement in `handle_public_report()` before rendering the template
- when entitlement fails, show `Please upgrade to enable shareable reports.` instead of fataling

## Experiment editor sidebar: sticky offset is tuned for the admin viewport

The experiment editor uses a sticky right-hand sidebar so save/status controls stay visible while scrolling.

### Symptom

- The sidebar appears too low and wastes vertical space.
- The sidebar sits too close to the top and feels cramped against the admin chrome.

### Mitigation in ABSPLITTEST

`admin/bt-bb-ab-admin.css` uses `div#side-sortables { position: sticky; top: 40px; }` as the current intended offset.

## Agency Hub: a decodable key is not enough to prove a real connection

An Agency Hub site key can be structurally valid while still being unusable in production.

### Symptom

- a site key pastes successfully but later refreshes fail
- users report that a site was added yet never syncs or updates
- the hub shows 401/403-style failures without obvious context

### Cause

- child-site remote sharing is disabled
- the child-site key was regenerated after the hub saved the old key
- a proxy, CDN, or security layer blocks the REST route or strips `X-ABST-Secret`
- the key decodes, but the child site is not actually reachable at the stored URL

### Mitigation in ABSPLITTEST

- the hub now verifies the remote summary endpoint before saving a new site connection
- sync responses now surface remote JSON errors such as `Remote access is not enabled on this site` and `Invalid authentication credentials`
- deleting a site also clears its cached remote data transient

### Operational note

If a child site regenerates its Agency Hub key, every hub must reconnect with the new key. Old saved keys are expected to stop working.

## Lite version: hard limits are enforced at save time, not read time

The free tier enforces 1 active test and 1 variation by intercepting `save_postdata`. If a user already has published tests from a previous Pro licence, those tests remain in the database until they are edited.

### Symptom

- A site that previously had a Pro licence still shows multiple active tests after switching to the free version.
- Attempting to edit an existing multi-variation test silently truncates variations to 1 on save.

### Mitigation in ABSPLITTEST Lite

- `save_test_config` forces additional published tests to `draft` status.
- Variation arrays are sliced to `array_slice(..., 0, 1)` for page, CSS, and magic tests.
- Subgoals (`goals` meta) are deleted and `autocomplete_on` is forced to `0`.
- `conversion_use_order_value` is forced to `0`.

## Lite version: orphaned module includes must be removed

Leaving `include_once` for modules that rely on Pro-only classes can cause fatal errors on the free version.

### Symptom

- `Class "ABST_Public_Reports" not found` or similar fatal on frontend requests.
- Agency Hub or page-redirect modules attempt to load even though their UI is disabled.

### Mitigation in ABSPLITTEST Lite

- `modules/public-reports/public-reports.php`, `modules/page-redirect/page-redirect.php`, and `modules/support/agency_hub.php` includes were removed from `bt_include_module()`.
- The `conversion.php` and journey modules remain because they are used by the free tier.
- Any code that references the removed classes (e.g. `$GLOBALS['abst_public_reports']`) must also be guarded or removed.
