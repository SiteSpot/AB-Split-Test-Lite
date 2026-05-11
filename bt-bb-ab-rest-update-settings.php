<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
/**
 * REST API endpoint to update test settings
 * 
 * POST /wp-json/bt-bb-ab/v1/update-test-settings
 * 
 * @param WP_REST_Request $request
 * @return WP_REST_Response|WP_Error
 */
function abst_rest_update_test_settings($request) {
    require_once plugin_dir_path(__FILE__) . 'bt-bb-ab-validation.php';
    global $btab;

    $params = abst_normalize_api_input_params($request->get_json_params());
    $params = abst_lite_apply_test_limits($params);
    $guard_result = abst_apply_conversion_order_value_guard($params);
    $params = $guard_result['params'];
    $validation_warnings = $guard_result['warnings'];
    
    if (empty($params['test_id'])) {
        return new WP_Error('missing_test_id', 'Test ID is required', ['status' => 400, 'field' => 'test_id']);
    }
    
    $test_id = intval($params['test_id']);
    $test = get_post($test_id);
    
    if (!$test || $test->post_type !== 'bt_experiments') {
        return new WP_Error('test_not_found', 'Test not found', ['status' => 404]);
    }

    if (!(defined('WP_CLI') && WP_CLI) && !current_user_can('edit_post', $test_id)) {
        return new WP_Error('forbidden', 'You do not have permission to update this test.', ['status' => 403]);
    }

    $test_type = get_post_meta($test_id, 'test_type', true);

    $has_param = static function($key) use ($params) {
        return array_key_exists($key, $params);
    };

    $normalize_variation_id = static function($value) {
        if (is_numeric($value)) {
            return intval($value);
        }
        return sanitize_text_field((string) $value);
    };

    $get_variation_url = static function($variation) use ($btab) {
        if (isset($btab) && method_exists($btab, 'get_url_from_slug')) {
            return $btab->get_url_from_slug($variation);
        }
        return (string) $variation;
    };

    $conversion_fields = [
        'conversion_type',
        'conversion_selector',
        'conversion_url',
        'conversion_page_id',
        'conversion_time',
        'conversion_scroll',
        'conversion_text',
        'conversion_link_pattern',
        'conversion_use_order_value',
    ];

    $full_page_fields = ['default_page', 'variations', 'variation_labels', 'variation_images'];
    $has_full_page_update = false;
    foreach ($full_page_fields as $field) {
        if ($has_param($field)) {
            $has_full_page_update = true;
            break;
        }
    }

    $has_conversion_update = false;
    foreach ($conversion_fields as $field) {
        if ($has_param($field)) {
            $has_conversion_update = true;
            break;
        }
    }

    if ($has_param('magic_definition') && $test_type !== 'magic') {
        return new WP_Error('invalid_test_type_update', 'magic_definition can only be updated on magic tests.', ['status' => 400, 'field' => 'magic_definition']);
    }

    if ($has_param('css_variations') && $test_type !== 'css_test') {
        return new WP_Error('invalid_test_type_update', 'css_variations can only be updated on css_test tests.', ['status' => 400, 'field' => 'css_variations']);
    }

    if ($has_full_page_update && $test_type !== 'full_page') {
        return new WP_Error('invalid_test_type_update', 'default_page, variations, variation_labels, and variation_images can only be updated on full_page tests.', ['status' => 400, 'field' => 'default_page']);
    }

    if ($has_conversion_update) {
        $stored_conversion_page = get_post_meta($test_id, 'conversion_page', true);
        $effective_conversion_type = $has_param('conversion_type')
            ? $params['conversion_type']
            : (is_numeric($stored_conversion_page) ? 'page' : abst_normalize_conversion_type($stored_conversion_page));

        $effective_conversion_page_id = $has_param('conversion_page_id')
            ? intval($params['conversion_page_id'])
            : (is_numeric($stored_conversion_page) ? intval($stored_conversion_page) : 0);

        $validation_params = [
            'conversion_type' => $effective_conversion_type,
            'conversion_selector' => $has_param('conversion_selector') ? $params['conversion_selector'] : get_post_meta($test_id, 'conversion_selector', true),
            'conversion_url' => $has_param('conversion_url') ? $params['conversion_url'] : get_post_meta($test_id, 'conversion_url', true),
            'conversion_page_id' => $effective_conversion_page_id,
            'conversion_time' => $has_param('conversion_time') ? $params['conversion_time'] : intval(get_post_meta($test_id, 'conversion_time', true)),
            'conversion_scroll' => $has_param('conversion_scroll') ? $params['conversion_scroll'] : intval(get_post_meta($test_id, 'conversion_scroll', true)),
            'conversion_text' => $has_param('conversion_text') ? $params['conversion_text'] : get_post_meta($test_id, 'conversion_text', true),
            'conversion_link_pattern' => $has_param('conversion_link_pattern') ? $params['conversion_link_pattern'] : get_post_meta($test_id, 'conversion_link_pattern', true),
            'conversion_use_order_value' => $has_param('conversion_use_order_value') ? $params['conversion_use_order_value'] : (get_post_meta($test_id, 'conversion_use_order_value', true) == '1'),
        ];

        $validation_error = abst_validate_test_payload($validation_params, 'update');
        if (is_wp_error($validation_error)) {
            return $validation_error;
        }
    }

    if ($has_param('magic_definition')) {
        $magic_validation = abst_validate_magic_definition($params['magic_definition']);
        if (is_wp_error($magic_validation)) {
            return $magic_validation;
        }
    }

    if ($has_param('css_variations') && intval($params['css_variations']) < 1) {
        return new WP_Error('invalid_css_variations', 'css_variations must be 1 or greater.', ['status' => 400, 'field' => 'css_variations']);
    }

    $general_validation = abst_validate_test_payload(array_intersect_key($params, array_flip([
        'target_percentage',
        'target_device',
        'optimization_type',
        'css_variations',
    ])), 'update');
    if (is_wp_error($general_validation)) {
        return $general_validation;
    }

    if ($has_full_page_update) {
        $existing_page_variations = get_post_meta($test_id, 'page_variations', true);
        if (!is_array($existing_page_variations)) {
            $existing_page_variations = [];
        }

        $effective_default_page = $has_param('default_page')
            ? $normalize_variation_id($params['default_page'])
            : get_post_meta($test_id, 'bt_experiments_full_page_default_page', true);

        $effective_variations = $has_param('variations')
            ? (array) $params['variations']
            : array_keys($existing_page_variations);

        if (empty($effective_default_page)) {
            return new WP_Error('missing_default_page', 'Default page is required for full_page tests.', ['status' => 400, 'field' => 'default_page']);
        }

        if (empty($effective_variations)) {
            return new WP_Error('missing_variations', 'At least one variation page is required for full_page tests.', ['status' => 400, 'field' => 'variations']);
        }
    }

    if ($has_conversion_update) {
        $stored_conversion_page = get_post_meta($test_id, 'conversion_page', true);
        $conversion_type = $has_param('conversion_type')
            ? $params['conversion_type']
            : (is_numeric($stored_conversion_page) ? 'page' : abst_normalize_conversion_type($stored_conversion_page));

        if ($has_param('conversion_type')) {
            if ($conversion_type === 'page') {
                $page_id_to_store = $has_param('conversion_page_id')
                    ? intval($params['conversion_page_id'])
                    : (is_numeric($stored_conversion_page) ? intval($stored_conversion_page) : 0);

                if ($page_id_to_store > 0) {
                    update_post_meta($test_id, 'conversion_page', $page_id_to_store);
                }
            } else {
                update_post_meta($test_id, 'conversion_page', $conversion_type);
            }
        } elseif ($has_param('conversion_page_id') && is_numeric($stored_conversion_page)) {
            update_post_meta($test_id, 'conversion_page', intval($params['conversion_page_id']));
        } elseif (!is_numeric($stored_conversion_page) && $conversion_type !== '') {
            update_post_meta($test_id, 'conversion_page', $conversion_type);
        }

        if ($has_param('conversion_selector')) {
            update_post_meta($test_id, 'conversion_selector', sanitize_text_field($params['conversion_selector']));
        }
        if ($has_param('conversion_url')) {
            update_post_meta($test_id, 'conversion_url', sanitize_text_field($params['conversion_url']));
        }
        if ($has_param('conversion_time')) {
            update_post_meta($test_id, 'conversion_time', intval($params['conversion_time']));
        }
        if ($has_param('conversion_scroll')) {
            update_post_meta($test_id, 'conversion_scroll', intval($params['conversion_scroll']));
        }
        if ($has_param('conversion_text')) {
            update_post_meta($test_id, 'conversion_text', sanitize_text_field($params['conversion_text']));
        }
        if ($has_param('conversion_link_pattern')) {
            update_post_meta($test_id, 'conversion_link_pattern', sanitize_text_field($params['conversion_link_pattern']));
        }
    }
    
    // Update conversion_use_order_value if provided
    if (isset($params['conversion_use_order_value'])) {
        update_post_meta($test_id, 'conversion_use_order_value', $params['conversion_use_order_value'] ? '1' : '0');
    }

    if ($has_param('target_percentage')) {
        update_post_meta($test_id, 'target_percentage', intval($params['target_percentage']));
    }

    if ($has_param('target_device')) {
        update_post_meta($test_id, 'target_option_device_size', sanitize_text_field($params['target_device']));
    }

    if ($has_param('allowed_roles')) {
        $allowed_roles = array_values(array_map('esc_attr', (array) $params['allowed_roles']));
        update_post_meta($test_id, 'bt_allowed_roles', $allowed_roles);
    }

    if ($has_param('url_query')) {
        $url_query = sanitize_textarea_field((string) $params['url_query']);
        if ($url_query === '') {
            delete_post_meta($test_id, 'url_query');
        } else {
            update_post_meta($test_id, 'url_query', $url_query);
        }
    }

    if ($has_param('webhook_url')) {
        $webhook_url = esc_url_raw((string) $params['webhook_url']);
        if ($webhook_url === '') {
            delete_post_meta($test_id, 'webhook_url');
        } else {
            update_post_meta($test_id, 'webhook_url', $webhook_url);
        }
    }

    if ($has_param('log_on_visible')) {
        update_post_meta($test_id, 'log_on_visible', !empty($params['log_on_visible']) ? '1' : '0');
    }
    
    // Lite supports one primary conversion only.
    delete_post_meta($test_id, 'goals');

    if ($has_param('autocomplete_on')) {
        update_post_meta($test_id, 'autocomplete_on', !empty($params['autocomplete_on']) ? 1 : 0);
    }

    if ($has_param('autocomplete_on') || $has_param('ac_min_days') || $has_param('ac_min_views')) {
        $autocomplete_enabled = $has_param('autocomplete_on')
            ? !empty($params['autocomplete_on'])
            : (get_post_meta($test_id, 'autocomplete_on', true) == '1');

        $min_days = $has_param('ac_min_days')
            ? absint($params['ac_min_days'])
            : intval(get_post_meta($test_id, 'ac_min_days', true));
        $min_views = $has_param('ac_min_views')
            ? absint($params['ac_min_views'])
            : intval(get_post_meta($test_id, 'ac_min_views', true));

        if ($autocomplete_enabled) {
            if ($min_days <= 0) {
                $min_days = 7;
            }
            if ($min_views <= 0) {
                $min_views = 50;
            }
        }

        update_post_meta($test_id, 'ac_min_days', $min_days);
        update_post_meta($test_id, 'ac_min_views', $min_views);
    }

    if ($has_param('optimization_type')) {
        $optimization_type = sanitize_text_field((string) $params['optimization_type']);
        update_post_meta($test_id, 'conversion_style', $optimization_type);
        if ($optimization_type !== 'thompson' && isset($btab) && method_exists($btab, 'clear_test_variation_weights')) {
            $btab->clear_test_variation_weights($test_id);
        }
    }

    if ($has_param('magic_definition')) {
        $magic_definition = $params['magic_definition'];
        if (is_string($magic_definition)) {
            $magic_definition = json_decode($magic_definition, true);
        }
        update_post_meta($test_id, 'magic_definition', wp_json_encode($magic_definition, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }

    if ($has_param('css_variations')) {
        update_post_meta($test_id, 'css_test_variations', intval($params['css_variations']));
    }

    if ($has_full_page_update) {
        $existing_page_variations = get_post_meta($test_id, 'page_variations', true);
        if (!is_array($existing_page_variations)) {
            $existing_page_variations = [];
        }

        $existing_variation_meta = get_post_meta($test_id, 'variation_meta', true);
        if (!is_array($existing_variation_meta)) {
            $existing_variation_meta = [];
        }

        $default_page = $has_param('default_page')
            ? $normalize_variation_id($params['default_page'])
            : get_post_meta($test_id, 'bt_experiments_full_page_default_page', true);

        $variation_ids = $has_param('variations')
            ? array_values(array_map($normalize_variation_id, (array) $params['variations']))
            : array_keys($existing_page_variations);

        update_post_meta($test_id, 'bt_experiments_full_page_default_page', $default_page);

        if ($has_param('variations')) {
            $page_variations = [];
            foreach ($variation_ids as $variation_id) {
                $page_variations[$variation_id] = $get_variation_url($variation_id);
            }
            update_post_meta($test_id, 'page_variations', $page_variations);
        } else {
            $page_variations = $existing_page_variations;
        }

        if ($has_param('variations') || $has_param('variation_labels') || $has_param('variation_images')) {
            $labels = ($has_param('variation_labels') && is_array($params['variation_labels'])) ? array_values($params['variation_labels']) : null;
            $images = ($has_param('variation_images') && is_array($params['variation_images'])) ? array_values($params['variation_images']) : null;

            $variation_meta = [];
            foreach ($variation_ids as $index => $variation_id) {
                $existing_meta = $existing_variation_meta[$variation_id] ?? [];
                $variation_meta[$variation_id] = [
                    'label' => is_array($labels)
                        ? sanitize_text_field((string) ($labels[$index] ?? ''))
                        : sanitize_text_field((string) ($existing_meta['label'] ?? '')),
                    'image' => is_array($images)
                        ? esc_url_raw((string) ($images[$index] ?? ''))
                        : esc_url_raw((string) ($existing_meta['image'] ?? '')),
                    'weight' => $existing_meta['weight'] ?? 1,
                ];
            }

            update_post_meta($test_id, 'variation_meta', $variation_meta);
        }
    }

    // Refresh conversion pages cache
    if (isset($btab) && method_exists($btab, 'refresh_conversion_pages')) {
        $btab->refresh_conversion_pages();
    }

    $details = (isset($btab) && method_exists($btab, 'get_test_details_payload'))
        ? $btab->get_test_details_payload($test_id)
        : null;
    $conversion_summary = get_post_meta($test_id, 'conversion_page', true);
    $canonical_conversion_type = is_numeric($conversion_summary) ? 'page' : abst_normalize_conversion_type($conversion_summary);

    $applied_settings = [
        'conversion_type' => $details['conversion']['type'] ?? $canonical_conversion_type,
        'conversion_selector' => $details['conversion']['selector'] ?? get_post_meta($test_id, 'conversion_selector', true),
        'conversion_url' => $details['conversion']['url'] ?? get_post_meta($test_id, 'conversion_url', true),
        'conversion_page_id' => $details['conversion']['page_id'] ?? (is_numeric($conversion_summary) ? intval($conversion_summary) : 0),
        'conversion_time' => $details['conversion']['time'] ?? intval(get_post_meta($test_id, 'conversion_time', true)),
        'conversion_scroll' => $details['conversion']['scroll'] ?? intval(get_post_meta($test_id, 'conversion_scroll', true)),
        'conversion_text' => $details['conversion']['text'] ?? get_post_meta($test_id, 'conversion_text', true),
        'conversion_link_pattern' => $details['conversion']['link_pattern'] ?? get_post_meta($test_id, 'conversion_link_pattern', true),
        'conversion_use_order_value' => $details['conversion']['use_order_value'] ?? (get_post_meta($test_id, 'conversion_use_order_value', true) == '1'),
        'target_percentage' => $details['targeting']['percentage'] ?? intval(get_post_meta($test_id, 'target_percentage', true) ?: 100),
        'target_device' => $details['targeting']['device'] ?? (get_post_meta($test_id, 'target_option_device_size', true) ?: 'all'),
        'allowed_roles' => $details['targeting']['allowed_roles'] ?? (array) get_post_meta($test_id, 'bt_allowed_roles', true),
        'url_query' => $details['targeting']['url_query'] ?? get_post_meta($test_id, 'url_query', true),
        'webhook_url' => $details['webhook_url'] ?? get_post_meta($test_id, 'webhook_url', true),
        'log_on_visible' => $details['targeting']['log_on_visible'] ?? (get_post_meta($test_id, 'log_on_visible', true) === '1'),
        'optimization_type' => $details['optimization']['type'] ?? (get_post_meta($test_id, 'conversion_style', true) ?: 'bayesian'),
        'autocomplete_on' => $details['optimization']['autocomplete_on'] ?? (get_post_meta($test_id, 'autocomplete_on', true) == '1'),
        'ac_min_days' => $details['optimization']['ac_min_days'] ?? intval(get_post_meta($test_id, 'ac_min_days', true)),
        'ac_min_views' => $details['optimization']['ac_min_views'] ?? intval(get_post_meta($test_id, 'ac_min_views', true)),
        'magic_definition' => $details['magic_definition'] ?? null,
        'css_variations' => $details['css_variations'] ?? intval(get_post_meta($test_id, 'css_test_variations', true) ?: 0),
        'default_page' => $details['full_page']['default_page'] ?? get_post_meta($test_id, 'bt_experiments_full_page_default_page', true),
        'variations' => isset($details['full_page']['variations']) ? array_values(array_map(static function($variation) {
            return $variation['id'] ?? null;
        }, $details['full_page']['variations'])) : [],
        'variation_labels' => isset($details['full_page']['variations']) ? array_values(array_map(static function($variation) {
            return $variation['label'] ?? '';
        }, $details['full_page']['variations'])) : [],
        'variation_images' => isset($details['full_page']['variations']) ? array_values(array_map(static function($variation) {
            return $variation['image'] ?? '';
        }, $details['full_page']['variations'])) : [],
    ];

    return new WP_REST_Response([
        'success' => true,
        'test_id' => $test_id,
        'conversion_type' => $canonical_conversion_type,
        'subgoals' => abst_storage_subgoals_to_api(get_post_meta($test_id, 'goals', true)),
        'applied_settings' => $applied_settings,
        'preview_urls' => $details['preview_urls'] ?? [],
        'test' => $details,
        'validation_warnings' => $validation_warnings,
        'message' => 'Test settings updated successfully'
    ], 200);
}
