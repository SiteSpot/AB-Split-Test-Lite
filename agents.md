# AB Split Test Lite: Magic Tests API and MCP

Notes for agents and developers creating Magic (point-and-click) tests over the REST API (`/wp-json/bt-bb-ab/v1/`) or the `absplittest/*` abilities exposed as MCP tools. Lite has no AI suggestion features and no WP-CLI commands (both are AB Split Test Pro).




## Overview

Magic Tests are a powerful feature for creating point-and-click A/B tests via multiple entry points with flexible scope control.

Every magic test compares the original with one variation, so each element has two named strings: `original` (the element's current content) and `variation` (the content tested against it).

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
  "original": "Buy Now",
  "variation": "Start Free"
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
    "original": "Click Here",
    "variation": "Get Started"
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
    "original": "$99",
    "variation": "$79"
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
    "original": "Submit",
    "variation": "Continue"
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
  - `type`: "text", "html", "image", "style" or "attribute"
  - `property`: for "style" one CSS property (e.g. "color"), for "attribute" one of aria-*, title, alt, role, class, id, href, src, srcset, width, height, placeholder, value, disabled, checked, rel, target
  - `selector`: CSS selector string
  - `scope`: Object with scoping rules (page_id, url, or wildcard)
  - `original`: Plain string (NOT an object): the element's current content
  - `variation`: Plain string (NOT an object): the content to test against the original
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
      "original": "Welcome",
      "variation": "Hello"
    }
  ],
  "conversion_type": "selector",
  "conversion_selector": ".success-message"
}
```

## Validation Rules

**Required for each magic definition item**:
1. `selector` - Valid CSS selector string
2. `type` - One of: "text", "html", "image", "style", "attribute" ("style" and "attribute" also need `property`)
3. `original` and `variation` - Two plain non-empty strings: the element's current content, and the content to test against it
4. `scope` - Object with at least one of:
   - `page_id`: Integer > 0 OR "*" wildcard
   - `url`: Non-empty string OR "*" wildcard

**Invalid (rejected)**:
```json
{
  "original": {"label": "Option A", "text": "Buy Now"},
  "variation": {"label": "Option B", "text": "Get Started"}
}
```

**Correct**:
```json
{
  "original": "Buy Now",
  "variation": "Get Started"
}
```

## Error Messages

**Missing scope**:
```
missing_magic_scope: Each magic_definition item requires scope.page_id, scope.url, or "*" wildcard to define where the test should run.
```

**Missing original**:
```
missing_magic_original: Each magic_definition item requires original: the element's current content, as a plain non-empty string.
```

**Missing variation**:
```
missing_magic_variation: Each magic_definition item requires variation: the content to test against the original, as a plain non-empty string.
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
      "original": "Build Better Products Faster",
      "variation": "Ship Features Your Customers Love"
    },
    {
      "type": "text",
      "selector": "h2.hero-subtitle",
      "scope": {"page_id": 42},
      "original": "The all-in-one platform for modern product teams.",
      "variation": "From backlog to launch — without the busywork."
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
    "original": "Original",
    "variation": "Variation"
  },
  {
    "type": "text",
    "selector": ".cta-button",
    "scope": {"url": "*"},
    "original": "Click",
    "variation": "Tap"
  },
  {
    "type": "image",
    "selector": ".hero-image",
    "scope": {"url": "pricing"},
    "original": "https://example.com/img1.jpg",
    "variation": "https://example.com/img2.jpg"
  }
]
```

