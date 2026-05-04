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
    return ['idea', 'draft', 'publish', 'pending', 'complete'];
}

function abst_get_supported_conversion_types() {
    return [
        'selector', 'link', 'url', 'page', 'time', 'scroll', 'text', 'block', 'javascript',
        'fingerprint', 'advanced',
        'form-fluentform', 'form-cf7', 'form-wpforms', 'form-gravity', 'form-ninjaforms',
        'form-formidable', 'form-forminator', 'form-elementor', 'form-jetformbuilder',
        'form-metform', 'form-mwwpform', 'form-sureforms', 'form-formcraft',
        'form-bricks', 'form-breakdance', 'form-beaver', 'form-mailpoet'
    ];
}

function abst_get_value_capable_conversion_types() {
    return [
        'javascript',
        'fingerprint',

        'advanced',
    ];
}

function abst_conversion_type_supports_order_value($conversion_type) {
    $conversion_type = abst_normalize_conversion_type($conversion_type);
    return in_array($conversion_type, abst_get_value_capable_conversion_types(), true);
}

function abst_apply_conversion_order_value_guard($params) {
    $warnings = [];

    if (!is_array($params)) {
        return [
            'params' => [],
            'warnings' => $warnings,
        ];
    }

    if (!isset($params['conversion_use_order_value'])) {
        return [
            'params' => $params,
            'warnings' => $warnings,
        ];
    }

    $requested_order_value = !empty($params['conversion_use_order_value']);
    if (!$requested_order_value) {
        $params['conversion_use_order_value'] = false;

        return [
            'params' => $params,
            'warnings' => $warnings,
        ];
    }

    return [
        'params' => $params,
        'warnings' => $warnings,
    ];
}

function abst_normalize_conversion_type($conversion_type) {
    $conversion_type = sanitize_text_field((string) $conversion_type);

    if ($conversion_type === 'click') {
        $conversion_type = 'selector';
    }

    $legacy_map = [
        'woo_order_received' => 'woo-order-received',
        'woo_order_pay' => 'woo-order-pay',
        'edd_purchase' => 'edd-purchase',
        'surecart_order_paid' => 'surecart-order-paid',
        'fluentcart_order_paid' => 'fluentcart-order-paid',
        'wp_pizza_is_checkout' => 'wp-pizza-is-checkout',
        'wp_pizza_is_order_history' => 'wp-pizza-is-order-history',
    ];

    if (isset($legacy_map[$conversion_type])) {
        $conversion_type = $legacy_map[$conversion_type];
    }

    return $conversion_type;
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

    foreach (['conversion_use_order_value', 'log_on_visible', 'autocomplete_on'] as $bool_key) {
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

    if (isset($params['webhook_url'])) {
        $params['webhook_url'] = esc_url_raw((string) $params['webhook_url']);
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
            return new WP_Error('missing_magic_selector', 'Each magic_definition item requires a selector.', ['status' => 400, 'field' => 'magic_definition.' . $index . '.selector']);
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

        foreach ($definition['variations'] as $variation_index => $variation) {
            if (!is_string($variation) || trim($variation) === '') {
                return new WP_Error('invalid_magic_variation_value', 'Magic definition variations must be plain non-empty strings.', ['status' => 400, 'field' => 'magic_definition.' . $index . '.variations.' . $variation_index]);
            }
        }
    }

    return true;
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

    if (isset($params['css_variations']) && $params['css_variations'] < 2) {
        return new WP_Error('invalid_css_variations', 'css_variations must be 2 or greater.', ['status' => 400, 'field' => 'css_variations']);
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
    }

    return true;
}

/**
 * Validation helper for conversion parameters.
 *
 * @param string $conversion_type The conversion type being validated.
 * @param array  $params          The parameters array.
 * @return true|WP_Error True if valid, WP_Error if validation fails.
 */
/**
 * Convert API subgoals format to internal storage format.
 * Input:  [ ['type' => 'scroll', 'value' => '50'], ... ]
 * Output: [ 1 => ['scroll' => '50'], ... ]
 *
 * @param array $subgoals
 * @return array
 */
function abst_normalize_subgoals_to_storage($subgoals) {
    if (!is_array($subgoals)) {
        return [];
    }
    $goals = [];
    $i = 1;
    foreach ($subgoals as $subgoal) {
        if (!is_array($subgoal) || empty($subgoal['type'])) {
            continue;
        }
        $type  = sanitize_text_field((string) $subgoal['type']);
        $value = isset($subgoal['value']) ? sanitize_text_field((string) $subgoal['value']) : '';
        $goals[$i] = [$type => $value];
        $i++;
    }
    return $goals;
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

/**
 * Validate a subgoals array from API input.
 * Returns true on success, WP_Error on failure.
 *
 * @param mixed $subgoals
 * @return true|WP_Error
 */
function abst_validate_subgoals($subgoals) {
    if (!is_array($subgoals)) {
        return new WP_Error('invalid_subgoals', 'subgoals must be an array of subgoal objects.', ['status' => 400, 'field' => 'subgoals']);
    }
    $supported = abst_get_supported_conversion_types();
    foreach ($subgoals as $index => $subgoal) {
        if (!is_array($subgoal)) {
            return new WP_Error('invalid_subgoal_item', 'Each subgoal must be an object with a "type" field.', ['status' => 400, 'field' => 'subgoals.' . $index]);
        }
        if (empty($subgoal['type'])) {
            return new WP_Error('missing_subgoal_type', 'Each subgoal requires a "type" field.', ['status' => 400, 'field' => 'subgoals.' . $index . '.type']);
        }
        $type = sanitize_text_field((string) $subgoal['type']);
        if (!in_array($type, $supported, true)) {
            return new WP_Error(
                'invalid_subgoal_type',
                'Subgoal type "' . $type . '" is not supported. Must be one of: ' . implode(', ', $supported),
                ['status' => 400, 'field' => 'subgoals.' . $index . '.type']
            );
        }
        // Require a value for types that need one
        $needs_value = in_array($type, ['scroll', 'url', 'page', 'text', 'selector', 'link', 'time'], true);
        if ($needs_value && (!isset($subgoal['value']) || $subgoal['value'] === '')) {
            return new WP_Error(
                'missing_subgoal_value',
                'Subgoal type "' . $type . '" requires a "value" field.',
                ['status' => 400, 'field' => 'subgoals.' . $index . '.value']
            );
        }
        if ($type === 'scroll') {
            $val = intval($subgoal['value']);
            if ($val < 1 || $val > 100) {
                return new WP_Error('invalid_subgoal_scroll', 'Scroll subgoal value must be between 1 and 100.', ['status' => 400, 'field' => 'subgoals.' . $index . '.value']);
            }
        }
    }
    return true;
}

function abst_validate_conversion_parameters($conversion_type, $params) {
    $params = abst_normalize_api_input_params($params);
    $conversion_type = abst_normalize_conversion_type($conversion_type);

    if (!in_array($conversion_type, abst_get_supported_conversion_types(), true)) {
        return new WP_Error(
            'invalid_conversion_type',
            'conversion_type must be one of: ' . implode(', ', abst_get_supported_conversion_types()) . '. Use "selector" instead of "click".',
            ['status' => 400, 'field' => 'conversion_type']
        );
    }

    switch ($conversion_type) {
        case 'selector':
            if (empty($params['conversion_selector'])) {
                return new WP_Error(
                    'missing_conversion_selector',
                    'conversion_selector is required when using "selector" conversion type. Please provide a CSS selector for the element to track (e.g., ".buy-button", "#checkout-btn").',
                    ['status' => 400, 'field' => 'conversion_selector']
                );
            }
            break;

        case 'link':
            if (empty($params['conversion_link_pattern'])) {
                return new WP_Error(
                    'missing_conversion_link_pattern',
                    'conversion_link_pattern is required when using "link" conversion type. Please provide a link pattern to match (e.g., "checkout", "buy-now").',
                    ['status' => 400, 'field' => 'conversion_link_pattern']
                );
            }
            break;

        case 'url':
            if (empty($params['conversion_url'])) {
                return new WP_Error(
                    'missing_conversion_url',
                    'conversion_url is required when using "url" conversion type. Please provide the URL path to track (e.g., "thank-you", "success"). This should be a relative path without the domain.',
                    ['status' => 400, 'field' => 'conversion_url']
                );
            }
            break;

        case 'page':
            if (empty($params['conversion_page_id'])) {
                return new WP_Error(
                    'missing_conversion_page_id',
                    'conversion_page_id is required when using "page" conversion type. Please provide the WordPress page ID to track.',
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
            break;

        case 'time':
            if (empty($params['conversion_time'])) {
                return new WP_Error(
                    'missing_conversion_time',
                    'conversion_time is required when using "time" conversion type. Please provide the number of seconds (e.g., 30 for 30 seconds, 120 for 2 minutes).',
                    ['status' => 400, 'field' => 'conversion_time']
                );
            }
            if (!is_numeric($params['conversion_time']) || intval($params['conversion_time']) <= 0) {
                return new WP_Error(
                    'invalid_conversion_time',
                    'conversion_time must be a positive integer number of seconds.',
                    ['status' => 400, 'field' => 'conversion_time']
                );
            }
            break;

        case 'scroll':
            if (empty($params['conversion_scroll'])) {
                return new WP_Error(
                    'missing_conversion_scroll',
                    'conversion_scroll is required when using "scroll" conversion type. Please provide the scroll percentage (0-100, e.g., 75 for 75% scroll depth).',
                    ['status' => 400, 'field' => 'conversion_scroll']
                );
            }
            if (!is_numeric($params['conversion_scroll']) || intval($params['conversion_scroll']) < 0 || intval($params['conversion_scroll']) > 100) {
                return new WP_Error(
                    'invalid_conversion_scroll',
                    'conversion_scroll must be an integer between 0 and 100.',
                    ['status' => 400, 'field' => 'conversion_scroll']
                );
            }
            break;

        case 'text':
            if (empty($params['conversion_text'])) {
                return new WP_Error(
                    'missing_conversion_text',
                    'conversion_text is required when using "text" conversion type. Please provide the text string to detect (e.g., "Thank you for your purchase", "Order confirmed").',
                    ['status' => 400, 'field' => 'conversion_text']
                );
            }
            break;
    }

    return true;
}
