# Quick reference

## Canonical fields

Use these for new integrations:

- `test_title`
- `test_type`
- `status`
- `conversion_type`
- `conversion_selector`
- `conversion_url`
- `conversion_page_id`
- `conversion_time`
- `conversion_scroll`
- `conversion_text`
- `conversion_link_pattern`
- `conversion_use_order_value`
- `magic_definition`

## Use Order Value

- turn on when you want to optimize for revenue instead of raw conversion count
- results are weighted by order value and interpreted as revenue per visitor
- value detection checks `window.abst.abConversionValue` first, then URL parameters, known order-total elements, visible total labels, and finally falls back to `1`

## Legacy aliases still accepted

- `name` → `test_title`
- `conversion_page` → `conversion_type`
- `click` → `selector`

## Supported statuses

- `draft`
- `publish`
- `pending`
- `complete`

## Editor status notices

- `publish` → `Split Test Running.`
- `pending` → `Split Test Paused.`
- `draft` → `Split Test Draft Saved.`
- `complete` → `Split Test Marked Complete.`

## Device values

- `all`
- `desktop`
- `tablet`
- `mobile`
- `desktop_tablet`
- `tablet_mobile`

## Journey tracking identifiers

- accepted transport fields: `uuid`, `ab_advanced_id`, `advancedId`
- `meta` records must include one of those identifiers
- regular journey event rows do not need their own visitor identifier when the batch metadata line is present
- file storage still normalizes the visitor identifier into the metadata line's historical `uuid` position

## Magic definition

```json
[
  {
    "type": "text",
    "selector": ".hero h1",
    "scope": { "page_id": 12 },
    "variations": ["Original", "Variant A", "Variant B"]
  }
]
```

Notes:

- `scope` is recommended for precise targeting
- unscoped payloads remain backward compatible
- page goals still store the numeric page ID in historical `conversion_page` storage
- point-and-click selector capture ignores magic-bar/admin-bar UI, including goal cards and `.remove-goal` controls
- AI suggestions panel uses fixed `height: 180px` on `ul#ai-suggestions-list` in CSS and shows shimmering skeleton placeholders while loading; closing the toggle suppresses auto-expand for the session

## Editor tabs

- `Idea` tab is shown when `abst_idea_hypothesis` exists
- editor defaults to `Idea` on load when idea content exists and no `test_type` is selected
- results still take priority as the default tab when result rows are present

## Public reports

- share links can still be generated without checking the Teams gate in the share-button UI
- public report rendering is gated at request time in `ABST_Public_Reports::handle_public_report()`
- non-Teams licenses should see: `Please upgrade to enable shareable reports.`

## Agency Hub

- child sites must enable `abst_remote_access_enabled` before a hub can connect
- hub-side add flow now verifies `/wp-json/abst/v1/agency-summary` before saving the site
- sync and key-regeneration AJAX requests require nonces
- regenerating the child-site key invalidates every previously saved hub connection that used the old key
- common remote errors now surface directly from the child response when available

## Experiment editor sidebar

- selector: `div#side-sortables`
- behavior: `position: sticky`
- top offset: `40px`

## WordPress.org compliance reminders

- enqueue bundled/local dependencies instead of CDN URLs for plugin-admin assets
- when embedding values into inline JavaScript, prefer `wp_json_encode()` / `esc_js()` to raw string concatenation
- when printing link-capable admin notices, use `wp_kses_post()` if HTML links are required
