<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
/**
 * Shared validation and normalization helpers for REST, MCP, CLI, and abilities integrations.
 */
function abst_get_supported_test_types() {
    return ['magic', 'ab_test', 'css_test', 'full_page'];
}

function abst_get_supported_test_statuses() {
    return ['draft', 'publish', 'pending', 'complete'];
}

function abst_get_supported_conversion_types() {
    return ['page', 'selector'];
}





function abst_apply_conversion_order_value_guard($params) {
    // Conversions are counted, not valued, so an order-value flag is dropped.
    if (!is_array($params)) {
        $params = [];
    }
    unset($params['conversion_use_order_value']);

    return [
        'params' => $params,
        'warnings' => [],
    ];
}

function abst_normalize_conversion_type($conversion_type) {
    return sanitize_text_field((string) $conversion_type);
}

function abst_normalize_test_status($status, $context = 'write') {
    $status = sanitize_text_field((string) $status);

    if ($context === 'list' && $status === 'all') {
        return 'any';
    }

    return $status;
}

function abst_normalize_api_input_params($params) {
    if (!is_array($params)) {
        $params = [];
    }

    if (!isset($params['test_title']) && isset($params['name'])) {
        $params['test_title'] = $params['name'];
    }

    if (!isset($params['conversion_type']) && isset($params['conversion_page'])) {
        $params['conversion_type'] = $params['conversion_page'];
    }

    if (isset($params['test_type'])) {
        $params['test_type'] = sanitize_text_field((string) $params['test_type']);
    }

    if (isset($params['magic_definition'])) {
        $params['magic_definition'] = abst_normalize_magic_definition($params['magic_definition']);
    }

    if (isset($params['status'])) {
        $params['status'] = abst_normalize_test_status($params['status']);
    }

    if (isset($params['test_title'])) {
        $params['test_title'] = sanitize_text_field((string) $params['test_title']);
    }

    foreach ([
        'abst_idea_hypothesis',
        'abst_idea_page_flow',
        'abst_idea_observed_problem',
        'abst_idea_next_step',
    ] as $idea_text_key) {
        if (isset($params[$idea_text_key])) {
            $params[$idea_text_key] = sanitize_textarea_field((string) $params[$idea_text_key]);
        }
    }

    if (isset($params['conversion_type'])) {
        $params['conversion_type'] = abst_normalize_conversion_type($params['conversion_type']);
    }

    if (isset($params['conversion_selector'])) {
        $params['conversion_selector'] = sanitize_text_field((string) $params['conversion_selector']);
    }

    if (isset($params['conversion_link_pattern'])) {
        $params['conversion_link_pattern'] = sanitize_text_field((string) $params['conversion_link_pattern']);
    }

    if (isset($params['conversion_text'])) {
        $params['conversion_text'] = sanitize_text_field((string) $params['conversion_text']);
    }

    if (isset($params['conversion_url'])) {
        $url = sanitize_text_field((string) $params['conversion_url']);
        if ($url !== '') {
            $url = str_replace(site_url(), '', $url);
            $url = ltrim($url, '/');
            $url = rtrim($url, '/');
        }
        $params['conversion_url'] = $url;
    }

    foreach (['conversion_page_id', 'conversion_time', 'conversion_scroll', 'target_percentage', 'css_variations', 'test_id', 'ac_min_days', 'ac_min_views'] as $int_key) {
        if (isset($params[$int_key]) && $params[$int_key] !== '') {
            $params[$int_key] = intval($params[$int_key]);
        }
    }

    foreach (['conversion_use_order_value', 'log_on_visible'] as $bool_key) {
        if (isset($params[$bool_key])) {
            $value = filter_var($params[$bool_key], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            $params[$bool_key] = $value === null ? false : $value;
        }
    }

    if (isset($params['optimization_type'])) {
        $params['optimization_type'] = sanitize_text_field((string) $params['optimization_type']);
    }

    if (isset($params['target_device'])) {
        $params['target_device'] = sanitize_text_field((string) $params['target_device']);
    }

    if (isset($params['url_query'])) {
        $params['url_query'] = sanitize_textarea_field((string) $params['url_query']);
    }

    $decode_array_param = static function($value) {
        if (is_array($value)) {
            return $value;
        }

        if (!is_string($value)) {
            return [$value];
        }

        $trimmed = trim($value);
        if ($trimmed === '') {
            return [];
        }

        $decoded = json_decode($trimmed, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return $decoded;
        }

        return array_values(array_filter(array_map('trim', explode(',', $trimmed)), static function($item) {
            return $item !== '';
        }));
    };

    if (isset($params['allowed_roles'])) {
        $params['allowed_roles'] = array_values(array_map('sanitize_text_field', $decode_array_param($params['allowed_roles'])));
    }

    if (isset($params['variations'])) {
        $params['variations'] = array_values(array_map(static function($variation) {
            if (is_numeric($variation)) {
                return intval($variation);
            }
            return sanitize_text_field((string) $variation);
        }, $decode_array_param($params['variations'])));
    }

    foreach (['variation_labels', 'variation_images'] as $array_key) {
        if (isset($params[$array_key])) {
            $params[$array_key] = array_values(array_map(static function($value) use ($array_key) {
                return $array_key === 'variation_images'
                    ? esc_url_raw((string) $value)
                    : sanitize_text_field((string) $value);
            }, $decode_array_param($params[$array_key])));
        }
    }

    return $params;
}

function abst_normalize_magic_definition($magic_definition) {
    if (is_string($magic_definition)) {
        $decoded = json_decode($magic_definition, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            $magic_definition = $decoded;
        } else {
            return $magic_definition;
        }
    }

    if (!is_array($magic_definition)) {
        return $magic_definition;
    }

    foreach ($magic_definition as $index => $definition) {
        if (!is_array($definition)) {
            continue;
        }

        $scope = [];
        if (isset($definition['scope']) && is_array($definition['scope'])) {
            $scope = $definition['scope'];
        }

        if (isset($scope['page_id']) && $scope['page_id'] !== '') {
            // Preserve wildcard, normalize others to int or int array for comma-separated inputs.
            if ($scope['page_id'] === '*') {
                $scope['page_id'] = '*';
            } else {
                $page_ids = [];

                if (is_array($scope['page_id'])) {
                    foreach ($scope['page_id'] as $raw_page_id) {
                        $raw_page_id = trim((string) $raw_page_id);
                        if (preg_match('/^[1-9]\d*$/', $raw_page_id)) {
                            $page_ids[] = intval($raw_page_id);
                        }
                    }
                } else {
                    $raw_page_id = trim((string) $scope['page_id']);
                    if (strpos($raw_page_id, ',') !== false) {
                        $parts = array_map('trim', explode(',', $raw_page_id));
                        foreach ($parts as $part) {
                            if (preg_match('/^[1-9]\d*$/', $part)) {
                                $page_ids[] = intval($part);
                            }
                        }
                    } elseif (preg_match('/^[1-9]\d*$/', $raw_page_id)) {
                        $page_ids[] = intval($raw_page_id);
                    }
                }

                $page_ids = array_values(array_unique(array_filter($page_ids, function($id) {
                    return intval($id) > 0;
                })));

                if (count($page_ids) === 1) {
                    $scope['page_id'] = $page_ids[0];
                } elseif (count($page_ids) > 1) {
                    $scope['page_id'] = $page_ids;
                } else {
                    $scope['page_id'] = '';
                }
            }
        }

        if (isset($scope['url'])) {
            $url_value = (string) $scope['url'];
            // Preserve wildcard, normalize others
            if ($url_value !== '*') {
                $url_value = sanitize_text_field($url_value);
                $url_value = wp_parse_url($url_value, PHP_URL_PATH) ?: $url_value;
                $url_value = strtolower(trim($url_value));
                $url_value = trim($url_value, '/');
            }
            $scope['url'] = $url_value;
        }

        $definition['scope'] = $scope;
        $magic_definition[$index] = $definition;
    }

    return $magic_definition;
}

/** Balance selector syntax before placing it inside :is(). */
function abst_magic_selector_is_balanced($selector) {
    if (!is_string($selector) || $selector === '' || strpos($selector, '/*') !== false || strpos($selector, '*/') !== false) {
        return false;
    }
    $stack = [];
    $quote = '';
    for ($i = 0, $length = strlen($selector); $i < $length; $i++) {
        $char = $selector[$i];
        if ($char === '\\') {
            if (++$i >= $length) { return false; }
            continue;
        }
        if ($quote !== '') {
            if ($char === "\n" || $char === "\r" || $char === "\f") { return false; }
            if ($char === $quote) { $quote = ''; }
            continue;
        }
        if ($char === '"' || $char === "'") { $quote = $char; continue; }
        if ($char === '(' || $char === '[') { $stack[] = $char; }
        if ($char === ')' || $char === ']') {
            if (array_pop($stack) !== ($char === ')' ? '(' : '[')) { return false; }
        }
        if ($char === '{' || $char === '}' || $char === ';' || ord($char) === 0) { return false; }
    }
    return $quote === '' && empty($stack);
}

/** The target guards a stored selector can carry: [0] is the older, looser guard,
 * [1] the one applied now; both are listed so the older one can be unwrapped. */
function abst_magic_selector_target_guards($type, $property) {
    $guards = [
        ':not(script,svg,math):not(script *,svg *,math *)',
        ':not(script,style,iframe,object,embed,link,meta,base,svg,math,template,noscript):not(script *,style *,iframe *,object *,svg *,math *,template *,noscript *)',
    ];
    $sink = '';
    if ($type === 'image' || ($type === 'attribute' && in_array($property, ['src', 'srcset'], true))) {
        $sink = ':is(img,source)';
    } elseif ($type === 'attribute' && $property === 'href') {
        $sink = ':is(a,area)';
    }
    return [$guards[0] . $sink, $guards[1] . $sink];
}

/** Peel off guards this plugin wrote so the current author's guard replaces them. */
function abst_magic_selector_unwrap($selector, $known_guards) {
    for ($depth = 0; $depth < 10; $depth++) {
        $unwrapped = false;
        foreach ($known_guards as $guard) {
            $inner_length = strlen($selector) - strlen($guard) - 5;
            if ($inner_length > 0
                && strpos($selector, ':is(') === 0
                && substr($selector, -strlen($guard) - 1) === ')' . $guard
                && abst_magic_selector_is_balanced(substr($selector, 4, $inner_length))) {
                $selector = substr($selector, 4, $inner_length);
                $unwrapped = true;
                break;
            }
        }
        if (!$unwrapped) {
            break;
        }
    }
    return $selector;
}

/** Write-only preparation: never call this during rendering/embed regeneration.
 * Constrains the stored selector so every renderer observes the same safe DOM
 * target boundary. */
function abst_prepare_magic_definition_for_write($definition) {
    if (is_string($definition)) {
        $decoded = json_decode($definition, true);
        if (is_array($decoded)) {
            $definition = $decoded;
        }
    }
    if (is_array($definition)) {
        foreach ($definition as &$element) {
            if (is_array($element)) {
                unset($element['selector_rejected']);
                // Hide never uses a caller-controlled HTML/URL sink on the node.
                if (($element['type'] ?? '') === 'hide') {
                    continue;
                }
                $selector = isset($element['selector']) && is_string($element['selector']) ? sanitize_text_field($element['selector']) : '';
                if (!abst_magic_selector_is_balanced($selector)) {
                    $element['selector'] = '';
                    if ($selector !== '') {
                        $element['selector_rejected'] = $selector;
                    }
                    continue;
                }
                $type = $element['type'] ?? '';
                $property = $element['property'] ?? '';
                $guards = abst_magic_selector_target_guards($type, $property);
                $safe_targets = $guards[1];
                $element['selector'] = ':is(' . abst_magic_selector_unwrap($selector, $guards) . ')' . $safe_targets;
            }
        }
        unset($element);
    }
    return $definition;
}

/** Quote a bounded snippet of the author's own input back to them. */
function abst_magic_quote_for_message($value) {
    $value = trim(preg_replace('/\s+/', ' ', (string) $value));
    if (strlen($value) > 80) {
        $value = rtrim(substr($value, 0, 77)) . '...';
    }
    return '"' . $value . '"';
}

/** Name the change by the selector the author typed (guards unwrapped). */
function abst_magic_element_label($definition, $index, $variation_index = null) {
    $selector = isset($definition['selector']) && is_string($definition['selector']) ? $definition['selector'] : '';
    if ($selector !== '') {
        $selector = abst_magic_selector_unwrap(
            $selector,
            abst_magic_selector_target_guards($definition['type'] ?? '', $definition['property'] ?? '')
        );
    }
    $label = $selector === ''
        ? 'Element ' . ($index + 1)
        : 'The change to ' . abst_magic_quote_for_message($selector);
    return $label . ($variation_index === null ? '' : ' (variation ' . ($variation_index + 1) . ')');
}

/** Name what KSES removed so the author can fix that one tag or attribute. */
function abst_magic_removed_markup_hint($original, $filtered) {
    $names = function($html, $pattern) {
        preg_match_all($pattern, (string) $html, $matches);
        return array_map('strtolower', $matches[1]);
    };
    $tags = array_diff(
        $names($original, '/<([a-zA-Z][a-zA-Z0-9:-]*)/'),
        $names($filtered, '/<([a-zA-Z][a-zA-Z0-9:-]*)/')
    );
    if (!empty($tags)) {
        return 'the <' . reset($tags) . '> element is';
    }
    $attributes = array_diff(
        $names($original, '/[\s"\']([a-zA-Z_:][a-zA-Z0-9:._-]*)\s*=/'),
        $names($filtered, '/[\s"\']([a-zA-Z_:][a-zA-Z0-9:._-]*)\s*=/')
    );
    if (!empty($attributes)) {
        return 'the ' . reset($attributes) . ' attribute is';
    }
    return 'some of that markup is';
}

if (!function_exists('abst_magic_html_shape')) {
/** Tags, attributes and text a browser would build from $html. Null when the
 * markup cannot be tokenized with certainty. */
function abst_magic_html_shape($html) {
    $processor = new WP_HTML_Tag_Processor((string) $html);
    $shape = [];
    while ($processor->next_token()) {
        $token = $processor->get_token_type();
        if ($token === '#text') {
            $shape[] = '#' . $processor->get_modifiable_text();
            continue;
        }
        if ($token !== '#tag') { continue; }
        if ($processor->is_tag_closer()) {
            $shape[] = '/' . $processor->get_tag();
            continue;
        }
        $attributes = [];
        foreach ((array) $processor->get_attribute_names_with_prefix('') as $name) {
            $attributes[strtolower($name)] = $processor->get_attribute($name);
        }
        ksort($attributes);
        $shape[] = $processor->get_tag() . ' ' . wp_json_encode($attributes);
        if (in_array($processor->get_tag(), ['SCRIPT', 'STYLE', 'TEXTAREA', 'TITLE', 'XMP', 'IFRAME', 'NOEMBED', 'NOFRAMES'], true)) {
            $shape[] = '#' . $processor->get_modifiable_text();
        }
    }
    if ($processor->paused_at_incomplete_token()) {
        return null;
    }
    return $shape;
}
}

if (!function_exists('abst_magic_html_equivalent')) {
/** True when $filtered (KSES output) only re-encoded $original, e.g. a bare
 * "&" written as "&amp;" or attribute quotes normalised, and removed or
 * changed nothing a browser would render. Such content is as safe as the
 * filtered copy, so it is stored exactly as the author wrote it. */
function abst_magic_html_equivalent($original, $filtered) {
    $original_shape = abst_magic_html_shape($original);
    return $original_shape !== null && $original_shape === abst_magic_html_shape($filtered);
}
}

if (!function_exists('abst_magic_kses_allowed_html')) {
/** The post-content HTML allowlist, plus the plain SVG drawing elements icons
 * use. Scripts, event handlers, <use>, <foreignObject> and animation stay out:
 * KSES removes them, which changes the markup, so the content is refused. */
function abst_magic_kses_allowed_html() {
    $allowed = wp_kses_allowed_html('post');
    $svg_attributes = array_fill_keys([
        'class', 'id', 'role', 'aria-hidden', 'aria-label', 'focusable', 'xmlns', 'version', 'viewbox', 'width', 'height',
        'preserveaspectratio', 'fill', 'fill-rule', 'fill-opacity', 'stroke', 'stroke-width', 'stroke-linecap',
        'stroke-linejoin', 'stroke-miterlimit', 'stroke-opacity', 'clip-rule', 'opacity', 'transform', 'd', 'cx', 'cy',
        'r', 'rx', 'ry', 'x', 'y', 'x1', 'y1', 'x2', 'y2', 'points',
    ], true);
    foreach (['svg', 'g', 'path', 'circle', 'ellipse', 'rect', 'line', 'polyline', 'polygon', 'title', 'desc'] as $tag) {
        $allowed[$tag] = isset($allowed[$tag]) && is_array($allowed[$tag]) ? array_merge($allowed[$tag], $svg_attributes) : $svg_attributes;
    }
    return $allowed;
}
}

/** Content policy for every Magic test, whoever saves it: variations may only hold
 * the HTML WordPress allows in post content (no scripts, event handlers or iframes). */
function abst_validate_magic_write_content($definition, $index) {
    $type = $definition['type'];
    $property = $definition['property'] ?? '';
    $error = function($reason, $variation_index = null) use ($definition, $index) {
        return new WP_Error(
            'magic_content_not_allowed',
            abst_magic_element_label($definition, $index, $variation_index) . ': ' . $reason
                . ' Nothing was saved; change it and save again.',
            ['status' => 400, 'field' => 'magic_definition.' . $index]
        );
    };
    if (!abst_magic_selector_is_balanced($definition['selector'])) {
        return $error('the selector ' . abst_magic_quote_for_message($definition['selector']) . ' could not be used.');
    }
    if (!in_array($type, ['text', 'html', 'image', 'hide', 'style', 'attribute'], true)) {
        // Moving arbitrary existing DOM can activate previously inert scripts.
        return $error('the ' . abst_magic_quote_for_message($type) . ' change type rearranges existing page elements.');
    }
    if ($type === 'attribute' && (!is_string($property) || !preg_match('/^(?:aria-[a-z-]+|title|alt|role|class|id|href|src|srcset|width|height|placeholder|value|disabled|checked|rel|target)$/D', $property))) {
        return $error('the ' . abst_magic_quote_for_message($property) . ' attribute is not one Magic tests can set'
            . ' (aria-*, title, alt, role, class, id, href, src, srcset, width, height, placeholder, value, disabled, checked, rel, target).');
    }
    if ($type === 'style' && (!is_string($property) || !preg_match('/^[a-zA-Z][a-zA-Z-]*$/D', $property) || $property === 'cssText')) {
        return $error('the ' . abst_magic_quote_for_message($property) . ' style property is not a single CSS property Magic tests can set.');
    }
    foreach ($definition['variations'] as $variation_index => $value) {
        if ($value === 'original') { continue; }
        $safe_html = wp_kses($value, abst_magic_kses_allowed_html());
        // Refuse only when KSES would remove or change something a browser
        // renders. Pure re-encoding ("Fish & Chips" -> "Fish &amp; Chips",
        // attribute quoting) leaves the content exactly as safe as the filtered copy.
        if ($safe_html !== $value && !abst_magic_html_equivalent($value, $safe_html)) {
            return $error(abst_magic_removed_markup_hint($value, $safe_html) . ' not HTML WordPress allows in Magic tests.', $variation_index);
        }
        if (($type === 'image' || ($type === 'attribute' && in_array($property, ['href', 'src', 'srcset'], true)))
            && wp_kses_bad_protocol($value, $property === 'href' ? ['http', 'https', 'mailto', 'tel'] : ['http', 'https']) !== $value) {
            return $error(abst_magic_quote_for_message($value) . ' is not an ordinary web'
                . ($property === 'href' ? ', email or telephone' : '') . ' address.', $variation_index);
        }
        if ($type === 'style') {
            $css_property = strtolower(preg_replace('/([a-z])([A-Z])/', '$1-$2', $property));
            $rule = $css_property . ':' . $value;
            if (trim(safecss_filter_attr($rule)) !== trim($rule)) {
                return $error(abst_magic_quote_for_message($rule) . ' is not a style declaration WordPress accepts.', $variation_index);
            }
        }
    }
    return true;
}

/** Raw metadata tools must obey the same policy as the Magic editor. */
function abst_prepare_magic_meta_write($value) {
    $definition = abst_prepare_magic_definition_for_write($value);
    $error = abst_validate_magic_definition($definition);
    if (is_wp_error($error)) { return $error; }
    return is_string($value) ? wp_json_encode($definition, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : $definition;
}

function abst_validate_magic_definition($magic_definition) {
    if (empty($magic_definition)) {
        return new WP_Error('missing_magic_definition', 'magic_definition is required for magic tests.', ['status' => 400, 'field' => 'magic_definition']);
    }

    if (is_string($magic_definition)) {
        $decoded = json_decode($magic_definition, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return new WP_Error('invalid_magic_definition_json', 'magic_definition must be valid JSON: ' . json_last_error_msg(), ['status' => 400, 'field' => 'magic_definition']);
        }
        $magic_definition = $decoded;
    }

    if (!is_array($magic_definition) || empty($magic_definition)) {
        return new WP_Error('invalid_magic_definition_structure', 'magic_definition must be a non-empty array of element definitions.', ['status' => 400, 'field' => 'magic_definition']);
    }

    foreach ($magic_definition as $index => $definition) {
        if (!is_array($definition)) {
            return new WP_Error('invalid_magic_definition_item', 'Each magic_definition item must be an object/array.', ['status' => 400, 'field' => 'magic_definition.' . $index]);
        }

        if (empty($definition['selector'])) {
            $rejected = isset($definition['selector_rejected']) && is_string($definition['selector_rejected'])
                ? $definition['selector_rejected'] : '';
            $message = $rejected === ''
                ? 'Element ' . ($index + 1) . ' needs a selector so we know which element to change.'
                : 'Element ' . ($index + 1) . ': the selector ' . abst_magic_quote_for_message($rejected)
                  . ' could not be used. Check for an unbalanced bracket, parenthesis or quote, and remove any comment or semicolon.';
            return new WP_Error('missing_magic_selector', $message, ['status' => 400, 'field' => 'magic_definition.' . $index . '.selector']);
        }

        if (empty($definition['type'])) {
            return new WP_Error('missing_magic_type', 'Each magic_definition item requires a type.', ['status' => 400, 'field' => 'magic_definition.' . $index . '.type']);
        }

        if (!array_key_exists('scope', $definition) || !is_array($definition['scope'])) {
            return new WP_Error(
                'missing_magic_scope',
                'Each magic_definition item requires a scope object with scope.page_id, scope.url, or "*" wildcard.',
                ['status' => 400, 'field' => 'magic_definition.' . $index . '.scope']
            );
        }

        $scope = $definition['scope'];
        $has_scope_page_id = false;
        if (isset($scope['page_id'])) {
            if (is_array($scope['page_id'])) {
                $has_scope_page_id = !empty($scope['page_id']);
            } else {
                $has_scope_page_id = $scope['page_id'] !== '';
            }
        }
        $has_scope_url = isset($scope['url']) && is_string($scope['url']) && trim($scope['url']) !== '';

        if (!$has_scope_page_id && !$has_scope_url) {
            return new WP_Error(
                'missing_magic_scope',
                'Each magic_definition item requires scope.page_id, scope.url, or "*" wildcard to define where the test should run.',
                ['status' => 400, 'field' => 'magic_definition.' . $index . '.scope']
            );
        }

        if (empty($definition['variations']) || !is_array($definition['variations'])) {
            return new WP_Error('missing_magic_variations', 'Each magic_definition item requires a non-empty variations array.', ['status' => 400, 'field' => 'magic_definition.' . $index . '.variations']);
        }

        if (count($definition['variations']) > 2) {
            return new WP_Error('invalid_magic_variation_count', 'Each magic_definition item compares the original with one variation: variations takes two entries, the original first.', ['status' => 400, 'field' => 'magic_definition.' . $index . '.variations']);
        }

        foreach ($definition['variations'] as $variation_index => $variation) {
            if (!is_string($variation) || trim($variation) === '') {
                return new WP_Error('invalid_magic_variation_value', 'Magic definition variations must be plain non-empty strings.', ['status' => 400, 'field' => 'magic_definition.' . $index . '.variations.' . $variation_index]);
            }
        }

        $content_validation = abst_validate_magic_write_content($definition, $index);
        if (is_wp_error($content_validation)) {
            return $content_validation;
        }
    }

    return true;
}

/** Drop settings this plugin has no code for (sub-goals, revenue weighting,
 * auto-completion, webhooks, goal types other than a page visit or element click, and
 * a CSS test's class count, which is always two), so API callers can't store them. */
function abst_drop_unsupported_test_params($params) {
    if (!is_array($params)) {
        return $params;
    }

    unset($params['subgoals'], $params['goals'], $params['autocomplete_on'], $params['webhook_url'], $params['conversion_use_order_value'],
        $params['conversion_url'], $params['conversion_time'], $params['conversion_scroll'],
        $params['conversion_text'], $params['conversion_link_pattern'], $params['css_variations']);

    return $params;
}

function abst_validate_test_payload($params, $mode = 'create') {
    $params = abst_normalize_api_input_params($params);
    $requested_status = $params['status'] ?? 'draft';
    $is_idea = ($requested_status === 'idea');

    $guard_result = abst_apply_conversion_order_value_guard($params);
    $params = $guard_result['params'];

    if ($mode === 'create') {
        if (empty($params['test_title'])) {
            return new WP_Error('missing_title', 'Test title is required.', ['status' => 400, 'field' => 'test_title']);
        }

        if ($is_idea && empty($params['abst_idea_hypothesis'])) {
            return new WP_Error('missing_hypothesis', 'Hypothesis is required for idea status.', ['status' => 400, 'field' => 'abst_idea_hypothesis']);
        }

        if (!$is_idea && empty($params['test_type'])) {
            return new WP_Error('missing_test_type', 'Test type is required (magic, ab_test, css_test, full_page).', ['status' => 400, 'field' => 'test_type']);
        }
    }

    if (!empty($params['test_type']) && !in_array($params['test_type'], abst_get_supported_test_types(), true)) {
        return new WP_Error('invalid_test_type', 'Test type must be one of: ' . implode(', ', abst_get_supported_test_types()), ['status' => 400, 'field' => 'test_type']);
    }

    if (!empty($params['status']) && !in_array($params['status'], abst_get_supported_test_statuses(), true)) {
        return new WP_Error('invalid_status', 'Status must be one of: ' . implode(', ', abst_get_supported_test_statuses()), ['status' => 400, 'field' => 'status']);
    }

    if ($mode === 'create' && !$is_idea && empty($params['conversion_type'])) {
        return new WP_Error('missing_conversion_type', 'Conversion type is required. Please specify what action counts as a conversion.', ['status' => 400, 'field' => 'conversion_type']);
    }

    if (!empty($params['conversion_type'])) {
        $conversion_validation = abst_validate_conversion_parameters($params['conversion_type'], $params);
        if (is_wp_error($conversion_validation)) {
            return $conversion_validation;
        }
    }

    if (isset($params['target_percentage']) && ($params['target_percentage'] < 1 || $params['target_percentage'] > 100)) {
        return new WP_Error('invalid_target_percentage', 'target_percentage must be between 1 and 100.', ['status' => 400, 'field' => 'target_percentage']);
    }

    if (isset($params['target_device']) && !in_array($params['target_device'], ['all', 'desktop', 'mobile', 'tablet', 'desktop_tablet', 'tablet_mobile'], true)) {
        return new WP_Error('invalid_target_device', 'target_device must be one of: all, desktop, mobile, tablet, desktop_tablet, tablet_mobile.', ['status' => 400, 'field' => 'target_device']);
    }

    if (isset($params['optimization_type']) && !in_array($params['optimization_type'], ['bayesian', 'thompson'], true)) {
        return new WP_Error('invalid_optimization_type', 'optimization_type must be one of: bayesian, thompson.', ['status' => 400, 'field' => 'optimization_type']);
    }

    if (($params['test_type'] ?? '') === 'magic') {
        $magic_validation = abst_validate_magic_definition($params['magic_definition'] ?? null);
        if (is_wp_error($magic_validation)) {
            return $magic_validation;
        }
    }

    if (($params['test_type'] ?? '') === 'full_page') {
        if (empty($params['default_page'])) {
            return new WP_Error('missing_default_page', 'Default page is required for full page tests.', ['status' => 400, 'field' => 'default_page']);
        }

        if (empty($params['variations']) || !is_array($params['variations'])) {
            return new WP_Error('missing_variations', 'At least one variation page is required for full page tests.', ['status' => 400, 'field' => 'variations']);
        }

        if (count($params['variations']) > 1) {
            return new WP_Error('invalid_variation_count', 'A full page test compares the default page with one variation page: pass one page in variations.', ['status' => 400, 'field' => 'variations']);
        }
    }

    return true;
}



/**
 * Convert internal storage format back to the API subgoals format.
 * Input:  [ 1 => ['scroll' => '50'], ... ]
 * Output: [ ['type' => 'scroll', 'value' => '50'], ... ]
 *
 * @param mixed $goals
 * @return array
 */
function abst_storage_subgoals_to_api($goals) {
    if (!is_array($goals)) {
        return [];
    }
    $result = [];
    foreach ($goals as $goal) {
        if (!is_array($goal) || empty($goal)) {
            continue;
        }
        $type  = array_key_first($goal);
        $value = $goal[$type] ?? '';
        $result[] = ['type' => $type, 'value' => $value];
    }
    return $result;
}



function abst_validate_conversion_parameters($conversion_type, $params) {
    $params = abst_normalize_api_input_params($params);
    $conversion_type = abst_normalize_conversion_type($conversion_type);

    if (!in_array($conversion_type, abst_get_supported_conversion_types(), true)) {
        return new WP_Error(
            'invalid_conversion_type',
            'conversion_type must be "page" (a visitor reaches conversion_page_id) or "selector" (a visitor clicks an element matching conversion_selector).',
            ['status' => 400, 'field' => 'conversion_type']
        );
    }

    if ($conversion_type === 'selector') {
        if (!isset($params['conversion_selector']) || trim((string) $params['conversion_selector']) === '') {
            return new WP_Error(
                'missing_conversion_selector',
                'conversion_selector is required for the "selector" goal: the CSS selector of the element whose click counts as a conversion (e.g. ".buy-button", "#signup").',
                ['status' => 400, 'field' => 'conversion_selector']
            );
        }
        return true;
    }

    if (empty($params['conversion_page_id'])) {
        return new WP_Error(
            'missing_conversion_page_id',
            'conversion_page_id is required for the "page" goal. Please provide the WordPress page ID to track.',
            ['status' => 400, 'field' => 'conversion_page_id']
        );
    }
    if (!is_numeric($params['conversion_page_id']) || intval($params['conversion_page_id']) <= 0) {
        return new WP_Error(
            'invalid_conversion_page_id',
            'conversion_page_id must be a positive integer WordPress page ID.',
            ['status' => 400, 'field' => 'conversion_page_id']
        );
    }

    return true;
}
