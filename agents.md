# AB Split Test Lite: Magic Tests API and MCP

Notes for agents and developers creating Magic (point-and-click) tests over the REST API (`/wp-json/bt-bb-ab/v1/`) or the `absplittest/*` abilities exposed as MCP tools. Lite has no AI suggestion features and no WP-CLI commands (both are AB Split Test Pro).




## Overview

Magic Tests are a powerful feature for creating point-and-click A/B tests via multiple entry points with flexible scope control.

Every magic test compares the original with one variation, so each `variations` array holds exactly two strings: the original first, then the variation.

## Scope Configuration

Magic test definitions require explicit scope to control where tests are displayed:

**Scope Types**:
- `{"page_id": 123}` - Show only on page with ID 123
- `{"url": "pricing"}` - Show on pages containing "pricing" in URL
- `{"page_id": "*"}` or `{"url": "*"}` - Show on ALL pages (wildcard)

**Example**:
```json
{
  "type": "text",
  "selector": ".button-text",
  "scope": {"page_id": 42},
  "variations": ["Buy Now", "Start Free"]
}
```

## REST API: POST /wp-json/bt-bb-ab/v1/create-test

**Magic Test with page scope**:
```json
{
  "test_title": "Homepage CTA Test",
  "test_type": "magic",
  "magic_definition": [{
    "type": "text",
    "selector": ".cta-button",
    "scope": {"page_id": 42},
    "variations": ["Click Here", "Get Started"]
  }],
  "conversion_type": "selector",
  "conversion_selector": ".checkout-btn"
}
```

**Magic Test with URL scope**:
```json
{
  "test_title": "Pricing Page Test",
  "test_type": "magic",
  "magic_definition": [{
    "type": "text",
    "selector": ".price-text",
    "scope": {"url": "pricing"},
    "variations": ["$99", "$79"]
  }],
  "conversion_type": "selector",
  "conversion_selector": ".buy-button"
}
```

**Magic Test with wildcard scope (all pages)**:
```json
{
  "test_title": "Site-wide Button Test",
  "test_type": "magic",
  "magic_definition": [{
    "type": "text",
    "selector": "button.primary",
    "scope": {"url": "*"},
    "variations": ["Submit", "Continue"]
  }],
  "conversion_type": "selector",
  "conversion_selector": ".success"
}
```

## MCP ability: absplittest/create-test

**Magic Test Parameters**:
- `test_title` (required): Test name
- `test_type`: "magic"
- `magic_definition` (required): Array of element definitions
  - `type`: "text", "html", or "image"
  - `selector`: CSS selector string
  - `scope`: Object with scoping rules (page_id, url, or wildcard)
  - `variations`: Two plain strings (NOT objects): the original, then the variation
- `conversion_type`: `page` (a visit to `conversion_page_id`) or `selector` (a click on `conversion_selector`)
- Additional conversion parameters (varies by type)

**Example**:
```json
{
  "test_type": "magic",
  "test_title": "Header Optimization",
  "magic_definition": [
    {
      "type": "text",
      "selector": "header h1",
      "scope": {"page_id": "*"},
      "variations": ["Welcome", "Hello"]
    }
  ],
  "conversion_type": "selector",
  "conversion_selector": ".success-message"
}
```

## Validation Rules

**Required for each magic definition item**:
1. `selector` - Valid CSS selector string
2. `type` - One of: "text", "html", "image"
3. `variations` - Exactly two plain non-empty strings: the original, then the variation
4. `scope` - Object with at least one of:
   - `page_id`: Integer > 0 OR "*" wildcard
   - `url`: Non-empty string OR "*" wildcard

**Invalid (rejected)**:
```json
{
  "variations": [
    {"label": "Option A", "text": "Buy Now"},
    {"label": "Option B", "text": "Get Started"}
  ]
}
```

**Correct**:
```json
{
  "variations": ["Buy Now", "Get Started"]
}
```

## Error Messages

**Missing scope**:
```
missing_magic_scope: Each magic_definition item requires scope.page_id, scope.url, or "*" wildcard to define where the test should run.
```

**Wrong number of variations**:
```
invalid_magic_variations: A Magic test compares the original with one variation: variations must hold two strings, the original then the variation.
```

**Invalid variations**:
```
invalid_magic_variation_value: Magic definition variations must be plain non-empty strings.
```

## Multi-Element Tests

You can test multiple elements together in a single magic test by adding more items to the `magic_definition` array.

**Headline + Sub-headline Test**:

A common use case is testing a headline and sub-headline together so they stay contextually consistent:

```json
{
  "test_title": "Homepage Headline + Sub-headline Test",
  "test_type": "magic",
  "magic_definition": [
    {
      "type": "text",
      "selector": "h1.hero-title",
      "scope": {"page_id": 42},
      "variations": [
        "Build Better Products Faster",
        "Ship Features Your Customers Love"
      ]
    },
    {
      "type": "text",
      "selector": "h2.hero-subtitle",
      "scope": {"page_id": 42},
      "variations": [
        "The all-in-one platform for modern product teams.",
        "From backlog to launch — without the busywork."
      ]
    }
  ],
  "conversion_type": "selector",
  "conversion_selector": ".signup-btn"
}
```

Each visitor sees either the original of every element or the variation of every element, keeping the headline and sub-headline in sync.

**Complex test with different scopes per element**:

```json
[
  {
    "type": "text",
    "selector": ".header-title",
    "scope": {"page_id": 42},
    "variations": ["Original", "Variation"]
  },
  {
    "type": "text",
    "selector": ".cta-button",
    "scope": {"url": "*"},
    "variations": ["Click", "Tap"]
  },
  {
    "type": "image",
    "selector": ".hero-image",
    "scope": {"url": "pricing"},
    "variations": ["https://example.com/img1.jpg", "https://example.com/img2.jpg"]
  }
]
```

