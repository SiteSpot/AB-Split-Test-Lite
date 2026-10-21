# Mental model

## Public contract vs storage contract

ABSPLITTEST has two contracts that must stay aligned:

- the **public contract** used by REST and MCP (Abilities API) integrations
- the **storage contract** used by WordPress posts and post meta

The public contract should use canonical fields such as:

- `test_title`
- `test_type`
- `conversion_type` (`page` or `selector`)
- companion fields such as `conversion_selector` and `conversion_page_id`

The storage contract still uses historical keys in places:

- `conversion_page` stores the primary conversion trigger
- the string `selector` in `conversion_page` means an element-click goal
- numeric values in `conversion_page` represent page-ID goals

Because of that split, integrations should normalize on input and derive on read instead of inventing parallel storage keys.

## Backward compatibility model

Compatibility is additive.

Older aliases can be accepted at the integration edge, but the system should normalize them into the canonical public shape before validation and storage.

Examples:

- `name` → `test_title`
- `conversion_page` → `conversion_type` for incoming integration payloads

This allows plugin updates without rewriting all historical test meta.

## Test lifecycle model

Complete is a real experiment lifecycle state (post status `abst_complete`, `complete` in the API), not just display text.

That means every management layer has to agree on the same status model:

- validation
- admin UI
- REST
- MCP

If one layer drops `complete`, the system drifts from the actual experiment lifecycle.

WordPress admin save notices are part of that lifecycle model too.

- `abst_experiments` posts show test-specific lifecycle messages rather than generic post wording
- `pending` maps to the paused lifecycle in the editor workflow
- save feedback should match the resulting state the user just set: running, paused, draft, or complete

## Idea model

Test ideas live on the `abst_experiments` post type (post status `abst_idea`) as `abst_idea_*` post meta.

Two rules matter:

- `abst_idea_hypothesis` is the visibility gate for the editor's `Idea` tab
- when idea content exists but no runnable `test_type` has been configured yet, the editor should default to the `Idea` tab

## Magic test model

Magic tests separate two concerns:

- **what changes**: selector, type, and the two versions (`variations: [original, variation]`)
- **where it applies**: scope metadata (`scope.page_id`, `scope.url` or `"*"`)

Integrations must send scope (validation rejects items without it). Editor saves without scope default to all pages.

## Device targeting model

Device targeting must stay aligned across UI, runtime, and integration schemas.

Canonical values are:

- `all`
- `desktop`
- `tablet`
- `mobile`
- `desktop_tablet`
- `tablet_mobile`

## Editor sidebar

- the right-hand sortable sidebar stays sticky while editing (851px and wider)
- the admin offset is intentionally `40px` from the top of the viewport
- spacing should leave the controls visible without crowding the WordPress admin bar

## Journey tracking identity model

Journey and heatmap tracking have a transport contract and a file-format contract.

- every transport record carries `uuid` (required by `modules/journey.php`) and `ab_advanced_id`, both set from `abstGetAdvancedId()`
- the file-format metadata line stores the visitor identifier in the `uuid` field position

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
