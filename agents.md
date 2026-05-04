# AB Split Test AI Agents Documentation



## Overview



The AB Split Test WordPress plugin includes integrated AI-powered features to help users create, optimize, and improve A/B test variations. The AI functionality leverages OpenAI's GPT models to provide intelligent suggestions for conversion rate optimization.



## AI Integration Architecture



### Configuration



- **AI Model**: Configurable via `BT_AB_TEST_WL_ABTEST` constant (defaults to OpenAI GPT models)

- **API Key**: Users can provide their own OpenAI API key in plugin settings

- **Hosted AI**: Fallback to hosted AI service at `absplittest.com` when no API key is provided

- **Disable Option**: Can be disabled via `abst_disable_ai` option or filter



### Files Involved



- **Main Handler**: `bt-bb-ab.php` (lines 6361-6700+)

  - `send_to_openai_callback()` - AJAX handler

  - `send_request_to_openai()` - Core API communication

  - `footer_ai_pieces()` - UI elements

  

- **Frontend Interface**: `js/highlighter.js`

  - Magic bar AI integration

  - Visual element selection

  - AI suggestion UI



## AI Agent Types



### 1. Text Rewrite Agent



**Purpose**: Rewrites individual text strings to make them more compelling and conversion-focused.



**Endpoint**: `wp_ajax_send_to_openai` with `type: 'rewrite'` or `type: 'magic'`



**System Prompts**:

- "You are a website conversion optimization specialist"

- "Trained to rewrite strings of text to make them more compelling and improve conversions"

- Focuses on casual, conversational tone

- Avoids salesy language and clichés



**Input**:

- Text string to rewrite

- Optional context (page content in markdown)



**Output** (JSON):

```json

{

  "suggestions": [

    "suggestion_1",

    "suggestion_2", 

    "suggestion_3",

    "suggestion_4",

    "suggestion_5"

  ]

}

```



**Parameters**:

- Temperature: 0.6

- Responses: 1

- Response format: JSON object



### 2. Page Analysis Agent



**Purpose**: Analyzes entire page content and provides conversion optimization suggestions.



**Endpoint**: `wp_ajax_send_to_openai` with `type: 'suggestions'`



**System Prompts**:

- Provides 3-5 quality suggestions per page

- Excludes imagery, page load time, live chat, visuals, mobile responsiveness

- Rates overall page conversion efficacy (0-100 scale)



**Input**:

- Full page HTML content

- Optional screenshot (base64 encoded or URL)



**Output** (JSON):

```json

{

  "overall_page_rating": "75",

  "missing_content": "content suggestions",

  "suggestions": [

    {

      "test_name": "Test name",

      "original_string": "Current text",

      "reason_why": "Explanation",

      "suggestions": ["var1", "var2", "var3", "var4", "var5"]

    }

  ]

}

```



### 3. Test Idea Generator Agent



**Purpose**: Generates high-impact A/B test ideas based on behavioral psychology and UX principles.



**Endpoint**: `wp_ajax_send_to_openai` with `type: 'test-idea'`



**Behavioral Principles Applied**:

- **Hick-Hyman Law**: Reduce choice to improve decision speed

- **Fitts's Law**: Make key actions larger and easier to reach

- **Jakob's Law**: Follow familiar UX patterns

- **Miller's Law**: Simplify information to avoid overload

- **Aesthetic-Usability Effect**: Improve visual appeal to boost trust

- **Von Restorff Effect**: Make the CTA visually stand out

- **Zeigarnik Effect**: Encourage task completion with progress cues

- **Peak-End Rule**: Optimize the most intense moment or final step

- **Cognitive Load Theory**: Minimize mental effort

- **Reciprocity Principle**: Offer value before asking for conversion

- **Loss Aversion**: Frame offers around what users might lose

- **Social Proof**: Show others' actions to build trust



**Input**:

- User prompt/optimization focus

- Page content/context

- Optional screenshot (base64 data URL)



**Output** (JSON):

```json

{

  "response": "Reasoning for suggested tests",

  "ideas": [

    {

      "testtitle": "Business benefit focused title",

      "theorytitle": "Principle name (e.g., Hick-Hyman Law)",

      "theory": "Explanation of principle application",

      "elements": [

        {

          "original": "Original text string",

          "variations": [

            "Variation A: Replacement text",

            "Variation B: Replacement text",

            "Variation C: Replacement text",

            "Variation D: Replacement text"

          ],

          "type": "text"

        }

      ]

    }

  ]

}

```



## Credit System



### License-Based Credits



**Function**: `getAiLicenceInfo($priceId)`



Credit allocation by license type:

- **Default/Legacy**: 30 requests/year

- **Free (ID: 11)**: 30 requests/month

- **Starter (ID: 12)**: 100 requests/month

- **Pro (ID: 13)**: 300 requests/month

- **Agency (ID: 14)**: 1000 requests/month



### Hosted AI Service



When no OpenAI API key is provided:

- Requests sent to: `https://absplittest.com/wp-json/bt-bb-ab/v1/aisuggest`

- Includes license key and domain for validation

- Credits tracked server-side

- Disabled via transient `abst_disable_hosted_ai` when credits exhausted



## User Interface



### Magic Bar Integration



**Location**: `footer_ai_pieces()` function (line 6703+)



**Features**:

- Radio button selection for AI action type:

  - Generate variations

  - Rewrite phrase

- Integrated into the "Magic Bar" visual editor

- Appears when `?abmagic` query parameter is present



### AI Request Flow



1. User selects element on page (via Magic Bar)

2. Chooses AI action type (generate/rewrite)

3. JavaScript sends AJAX request to `send_to_openai`

4. Backend processes request:

   - Validates AI not disabled

   - Checks for API key or hosted credits

   - Constructs appropriate prompts

   - Sends to OpenAI or hosted service

5. Response parsed and displayed to user

6. User can select variations to include in test



## Security & Validation



### Input Sanitization

- `wp_kses_post()` for HTML content

- `sanitize_text_field()` for simple strings

- Base64 validation for screenshots: `preg_replace('/[^A-Za-z0-9+\/=]/', '', $screenshot)`



### Nonce Verification

- AJAX requests should include WordPress nonces

- License key validation for hosted service



### Error Handling

- Returns JSON error messages

- Checks for empty inputs

- Validates API responses

- Transient-based rate limiting for hosted service



## API Communication



### OpenAI Direct

```php

$url = 'https://api.openai.com/v1/chat/completions';

$headers = [

  'Content-Type' => 'application/json',

  'Authorization' => 'Bearer ' . $api_key

];

```



### Hosted Service

```php

$url = 'https://absplittest.com/wp-json/bt-bb-ab/v1/aisuggest';

$request_body = [

  'ab_licence_key' => $ab_licence_key,

  'domain' => $domain,

  // ... other parameters

];

```



### Request Parameters

- **model**: AI model identifier (from `BT_AB_TEST_WL_ABTEST`)

- **messages**: Array of conversation messages

- **temperature**: 0.6 (controls randomness)

- **response_format**: `{ type: 'json_object' }`

- **n**: Number of responses (typically 1)

- **timeout**: 60 seconds



## Filters & Hooks



### Available Filters



- `abst_disable_ai` - Disable AI integration entirely

- `bb_bt_ab_licence_key` - Filter license key

- `abst_email_report_message` - Customize email reports



### AJAX Actions



- `wp_ajax_send_to_openai` - Main AI request handler



## Best Practices



### For Developers



1. **Always check AI status** before making requests

2. **Validate all inputs** before sending to AI

3. **Handle errors gracefully** with user-friendly messages

4. **Cache responses** when appropriate to save credits

5. **Provide context** for better AI suggestions



### For Users



1. **Provide clear context** - More page content = better suggestions

2. **Use screenshots** - Visual context improves recommendations

3. **Be specific** with prompts for test ideas

4. **Review suggestions** - AI provides starting points, not final copy

5. **Monitor credits** - Track usage if using hosted service



## Tone & Style Guidelines



The AI agents are configured to:

- Use **casual, conversational** language

- Speak like "a friend recommending to another friend"

- **Avoid salesy language** and clichés

- Use **direct language** for modern audiences

- **Never include** introductory text, prefixes, or quotation marks in rewrites



## Error Messages



Common error scenarios:

- `"AI integration is disabled"` - AI feature turned off

- `"Please add your OpenAI key in Settings"` - No API key configured

- `"Hosted AI is disabled, you probably exceeded your credits"` - Credit limit reached

- `"Please check you have included your text and AI Request Type"` - Missing required parameters



## Future Enhancements



Potential areas for expansion:

- Image variation generation

- A/B test result analysis and recommendations

- Automated winner selection suggestions

- Multi-language support

- Custom model fine-tuning

- Integration with additional AI providers (Claude, Gemini, etc.)



## Technical Notes



### Screenshot Handling



Screenshots can be provided in two formats:

1. **Base64 Data URL**: Direct from JavaScript canvas capture

2. **Remote URL**: Fetched server-side and converted to base64



### Context Formatting



When context is provided, it's wrapped in XML-like tags:

```

<split_text_input_text_to_create_alternatives>

[Text to rewrite]

</split_text_input_text_to_create_alternatives>



<website_content_for_context>

[Page content]

</website_content_for_context>

```



### Response Parsing



All AI responses are expected in valid JSON format. The plugin uses `json_decode()` to parse responses and handles malformed JSON gracefully.



---

## Magic Tests API & MCP Integration

### Overview

Magic Tests are a powerful feature for creating point-and-click A/B tests via multiple entry points with flexible scope control.

### Scope Configuration

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

### REST API: POST /wp-json/bt-bb-ab/v1/create-test

**Magic Test with page scope**:
```json
{
  "test_title": "Homepage CTA Test",
  "test_type": "magic",
  "magic_definition": [{
    "type": "text",
    "selector": ".cta-button",
    "scope": {"page_id": 42},
    "variations": ["Click Here", "Get Started", "Start Free"]
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
    "variations": ["$99", "$79", "$49"]
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
    "variations": ["Submit", "Continue", "Next Step"]
  }],
  "conversion_type": "selector",
  "conversion_selector": ".success"
}
```

### WP-CLI: wp absplittest create-test

**Create magic test**:
```bash
wp absplittest create-test \
  --name="Test Name" \
  --type=magic \
  --status=draft \
  --magic_definition='[{"type":"text","selector":"h1","scope":{"page_id":42},"variations":["A","B"]}]' \
  --conversion_type=selector \
  --conversion_selector=".btn"
```

**With wildcard scope**:
```bash
wp absplittest create-test \
  --name="Site-wide Test" \
  --type=magic \
  --magic_definition='[{"type":"text","selector":"button","scope":{"url":"*"},"variations":["Save","Continue"]}]' \
  --conversion_type=selector \
  --conversion_selector=".success"
```

### MCP Tool: create_ab_test

**Magic Test Parameters**:
- `test_title` (required): Test name
- `test_type` (required): "magic"
- `magic_definition` (required): Array of element definitions
  - `type`: "text", "html", or "image"
  - `selector`: CSS selector string
  - `scope`: Object with scoping rules (page_id, url, or wildcard)
  - `variations`: Array of plain strings (NOT objects)
- `conversion_type` (required): How conversions are tracked
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
      "variations": ["Welcome", "Hello", "Hi there"]
    }
  ],
  "conversion_type": "selector",
  "conversion_selector": ".success-message"
}
```

### Validation Rules

**Required for each magic definition item**:
1. `selector` - Valid CSS selector string
2. `type` - One of: "text", "html", "image"
3. `variations` - Non-empty array of plain strings
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
  "variations": ["Buy Now", "Get Started", "Start Free"]
}
```

### Error Messages

**Missing scope**:
```
missing_magic_scope: Each magic_definition item requires scope.page_id, scope.url, or use "*" wildcard to apply to all pages.
```

**Invalid variations**:
```
invalid_magic_variation_value: Magic definition variations must be plain non-empty strings.
```

### Multi-Element Tests

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
        "Ship Features Your Customers Love",
        "Turn Ideas Into Products in Days"
      ]
    },
    {
      "type": "text",
      "selector": "h2.hero-subtitle",
      "scope": {"page_id": 42},
      "variations": [
        "The all-in-one platform for modern product teams.",
        "From backlog to launch — without the busywork.",
        "Everything your team needs, nothing they don't."
      ]
    }
  ],
  "conversion_type": "selector",
  "conversion_selector": ".signup-btn"
}
```

Each visitor sees one variation slot (e.g. slot 1 = variation 1 for both elements, slot 2 = variation 2 for both), keeping the headline and sub-headline in sync.

**Complex test with different scopes per element**:

```json
[
  {
    "type": "text",
    "selector": ".header-title",
    "scope": {"page_id": 42},
    "variations": ["Original", "Variation A"]
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

---

**Version**: 1.8.15+  

**Last Updated**: 2025-12-19  

**Maintainer**: AB Split Test Development Team

