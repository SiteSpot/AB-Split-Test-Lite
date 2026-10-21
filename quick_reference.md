# Quick reference

## Canonical fields

Use these for new integrations:

- `test_title`
- `test_type`
- `status`
- `conversion_type` (`page` or `selector`)
- `conversion_selector`
- `conversion_page_id`
- `magic_definition`

## Legacy aliases still accepted

- `name` → `test_title`
- `conversion_page` → `conversion_type`

## Supported statuses (API values)

- `idea` (stored as `abst_idea`)
- `draft`
- `publish`
- `pending` (paused)
- `complete` (stored as `abst_complete`)

## Editor status notices

- `publish` → `Split Test Running.`
- `pending` → `Split Test Paused.`
- `draft` → `Split Test Draft Saved.`
- `abst_complete` → `Split Test Marked Complete.`

## Device values

- `all`
- `desktop`
- `tablet`
- `mobile`
- `desktop_tablet`
- `tablet_mobile`

## Journey tracking identifiers

- every record sent to `modules/journey.php` must carry `uuid`; `meta` records also need `screen_size`
- `js/bt_conversion.js` sends `uuid` and `ab_advanced_id` (same value) on every record
- file storage keeps the visitor identifier in the metadata line's `uuid` position

## Magic definition

```json
[
  {
    "type": "text",
    "selector": ".hero h1",
    "scope": { "page_id": 12 },
    "variations": ["Original headline", "Variation headline"]
  }
]
```

Notes:

- `variations` holds exactly two strings: the original, then the variation (validation rejects any other count with `invalid_magic_variations`)
- `scope` is required for REST/MCP payloads (`missing_magic_scope` otherwise); editor saves without it default to all pages
- page goals store the numeric page ID in historical `conversion_page` storage
- point-and-click selector capture ignores Magic Bar and admin-bar UI
- Lite: the Magic Bar has no AI panel and no AI code; AI suggestions are an AB Split Test Pro feature

## Editor tabs

- `Idea` tab is shown when `abst_idea_hypothesis` exists
- editor defaults to `Idea` on load when idea content exists and no `test_type` is selected
- results take priority as the default tab when result rows are present
- `?focus=settings|results|idea` forces a tab

## Experiment editor sidebar

- selector: `div#side-sortables`
- behavior: `position: sticky` (851px and wider)
- top offset: `40px`

## WordPress.org compliance reminders

- enqueue bundled/local dependencies instead of CDN URLs for plugin-admin assets
- when embedding values into inline JavaScript, prefer `wp_json_encode()` / `esc_js()` to raw string concatenation
- when printing link-capable admin notices, use `wp_kses_post()` if HTML links are required
