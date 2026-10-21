# Mental model

## Public contract vs storage contract

ABSPLITTEST has two contracts that must stay aligned:

- the **public contract** used by REST, MCP, and CLI integrations
- the **storage contract** used by WordPress posts and post meta

The public contract should use canonical fields such as:

- `test_title`
- `test_type`
- `conversion_type`
- companion fields such as `conversion_selector`, `conversion_url`, and `conversion_page_id`

The storage contract still uses historical keys in places:

- `conversion_page` stores the primary conversion trigger
- string values in `conversion_page` represent conversion types
- numeric values in `conversion_page` represent page-ID goals

Because of that split, integrations should normalize on input and derive on read instead of inventing parallel storage keys.

## Backward compatibility model

Compatibility is additive.

Older aliases can be accepted at the integration edge, but the system should normalize them into the canonical public shape before validation and storage.

Examples:

- `name` → `test_title`
- `conversion_page` → `conversion_type` for incoming integration payloads
- `click` → `selector`

Frontend runtime follows the same pattern:

- localized config exposes canonical read fields such as `conversion_type` and `conversion_page_id`
- runtime reads canonical fields first
- runtime falls back to legacy `conversion_page` behavior for old saved tests

This allows plugin updates without rewriting all historical test meta.

## Test lifecycle model

`complete` is a real experiment lifecycle state, not just display text.

That means every management layer has to agree on the same status model:

- validation
- admin UI
- REST
- MCP
- CLI
- frontend/public reporting where relevant

If one layer drops `complete`, the system drifts from the actual experiment lifecycle.

WordPress admin save notices are part of that lifecycle model too.

- `bt_experiments` should show test-specific lifecycle messages rather than generic post wording
- `pending` maps to the paused lifecycle in the editor workflow
- save feedback should match the resulting state the user just set: running, paused, draft, or complete

## Idea model

Test ideas live on the `bt_experiments` post type as `abst_idea_*` post meta.

Two rules matter:

- `abst_idea_hypothesis` is the visibility gate for the editor's `Idea` tab
- when idea content exists but no runnable `test_type` has been configured yet, the editor should default to the `Idea` tab
- idea records should stay distinct from runnable tests in the default admin list

## Magic test model

Magic tests separate two concerns:

- **what changes**: selector, type, and the two versions (`variations: [original, variation]`)
- **where it applies**: optional scope metadata

Scope should be used when available for safer targeting, but older unscoped payloads must remain compatible.

## Device targeting model

Device targeting must stay aligned across UI, runtime, and integration schemas.

Canonical values are:

- `all`
- `desktop`
- `tablet`
- `mobile`
- `desktop_tablet`
- `tablet_mobile`

## Revenue-weighted conversion model

`Use Order Value` changes the question the test is answering.

- with it off, conversions are counted equally
- with it on, conversions are weighted by monetary value
- the admin UI should describe the outcome in business terms: which variation earns more revenue per visitor

## Editor workflow model

The experiment editor should group related actions into the same workflow.

In particular, reviewing results and sharing results belong in the same place rather than separate navigation destinations.

The editor sidebar is also part of that workflow model.

- the right-hand sortable sidebar stays sticky while editing
- the admin offset is intentionally `40px` from the top of the viewport
- spacing should leave the controls visible without crowding the WordPress admin bar

## Journey tracking identity model

Journey and heatmap tracking have a transport contract and a file-format contract.

- the transport payload can identify the visitor with `uuid`, `ab_advanced_id`, or `advancedId`
- the runtime now prefers the advanced ID generated from the `ab-advanced-id` cookie/session value
- the file-format metadata line still stores that normalized visitor identifier in the historical `uuid` field position for backward compatibility with existing readers

## Public report access model

Public report links and public report rendering are separate concerns.

- the plugin may create or expose a share link without requiring a Teams check at button-render time
- the actual public report request must enforce the license gate at render time
- when the site is not on a Teams-level license, the public URL should show an upgrade message instead of the full report

This keeps the frontend/public path independent from admin-only class loading while still enforcing the product entitlement where it matters.

## Agency Hub connection model

Agency Hub has two distinct roles that must agree on the same contract:

- the child site exposes remote summaries only when `abst_remote_access_enabled` is on
- the hub site stores a site key and uses it to fetch remote summaries on demand

The shareable site key is a transport wrapper for two pieces of data:

- the child site's base URL
- the child site's current Agency Hub secret

Connection should be treated as verified only after the hub successfully fetches `/wp-json/abst/v1/agency-summary` with the decoded secret.

Secret rotation is intentionally breaking for old keys:

- regenerating the child-site key changes the underlying secret
- previously saved hub connections using the old key will stop authenticating
- reconnecting requires pasting the fresh key into the hub again

## Two-way tests

Every Lite test compares the Original with exactly one Variation. That is the data model everywhere, not a setting:

- **Magic (point-and-click)**: each `magic_definition` item has `variations: [original, variation]`, exactly two strings. Validation rejects any other count with `invalid_magic_variations`.
- **Full-page**: `page_variations` holds one entry, the variation page. The test's own page is the original.
- **On-page (builders and Gutenberg)**: the `bt-variation` attribute is `original` or `variation`. `abst_normalize_onpage_variation()` reads `original` (or an empty value, where a builder leaves its default out of the saved data) as the Original and anything else as the Variation.

The rule: no code may exist that supports more versions and then restricts them (no "max variations" setting, no slicing arrays down, no hidden or disabled third-variation UI). WordPress.org treats locked or capped functionality as trialware (guideline 5). A/B/n tests with several variations live in the separate AB Split Test Pro plugin, and Lite only mentions that as information.

## Lite version architecture

AB Split Test Lite is a standalone plugin, not Pro with features switched off.

- Pro-only features (AI suggestions, revenue and form goals, sub-goals, audiences, webhooks, email reports, WP-CLI, A/B/n tests) are absent from the code, not stubbed, hidden or disabled.
- There is no licence check, no tier function and no "(upgrade)" control on a disabled field.
- Pro is mentioned only as information: the readme's Pro section and the notice on the settings page.
- Everything Lite does ship is fully working with no count or time limit: unlimited tests, heatmaps on every page with configurable retention.
