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
    global $abst_btab;

    $params = abst_normalize_api_input_params($request->get_json_params());
    if (empty($params['test_id'])) {
        return new WP_Error('missing_test_id', 'Test ID is required', ['status' => 400, 'field' => 'test_id']);
    }
    
    $test_id = intval($params['test_id']);
    $test = get_post($test_id);
    
    if (!$test || $test->post_type !== 'abst_experiments') {
        return new WP_Error('test_not_found', 'Test not found', ['status' => 404]);
    }

    if (!(defined('WP_CLI') && WP_CLI) && !current_user_can('edit_post', $test_id)) {
        return new WP_Error('forbidden', 'You do not have permission to update this test.', ['status' => 403]);
    }

    $test_type = get_post_meta($test_id, 'test_type', true);

    $has_param = static function($key) use ($params) {
        return array_key_exists($key, $params);
    };

    $normalize_default_page = static function($value) {
        if (is_numeric($value)) {
            return intval($value);
        }
        return sanitize_text_field((string) $value);
    };

    $conversion_fields = [
        'conversion_type',
        'conversion_selector',
        'conversion_page_id',
    ];

    $full_page_fields = ['default_page', 'variation_page', 'variation_label', 'variation_image'];
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

    if ($has_full_page_update && $test_type !== 'full_page') {
        return new WP_Error('invalid_test_type_update', 'default_page, variation_page, variation_label, and variation_image can only be updated on full_page tests.', ['status' => 400, 'field' => 'default_page']);
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
            'conversion_page_id' => $effective_conversion_page_id,
        ];

        $validation_error = abst_validate_test_payload($validation_params, 'update');
        if (is_wp_error($validation_error)) {
            return $validation_error;
        }
    }

    if ($has_param('magic_definition')) {
        // Decode once, here, so the value that gets validated is exactly the value that gets
        // stored further down. Previously each site decoded the payload independently, so the
        // validated shape and the persisted shape could drift apart.
        $magic_definition_for_validation = $params['magic_definition'];
        if (is_string($magic_definition_for_validation)) {
            $decoded_magic_definition = json_decode($magic_definition_for_validation, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $magic_definition_for_validation = $decoded_magic_definition;
            }
        }
        $magic_definition_for_validation = abst_prepare_magic_definition_for_write($magic_definition_for_validation);
        $magic_validation = abst_validate_magic_definition($magic_definition_for_validation);
        if (is_wp_error($magic_validation)) {
            return $magic_validation;
        }
        $params['magic_definition'] = $magic_definition_for_validation;
    }

    $general_validation = abst_validate_test_payload(array_intersect_key($params, array_flip([
        'target_percentage',
        'target_device',
    ])), 'update');
    if (is_wp_error($general_validation)) {
        return $general_validation;
    }

    if ($has_full_page_update) {
        $effective_default_page = $has_param('default_page')
            ? $normalize_default_page($params['default_page'])
            : get_post_meta($test_id, 'bt_experiments_full_page_default_page', true);

        if (empty($effective_default_page)) {
            return new WP_Error('missing_default_page', 'Default page is required for full_page tests.', ['status' => 400, 'field' => 'default_page']);
        }

        // The variation page given (a page ID or slug, stored by ID), or the one already saved.
        $variation_id = $has_param('variation_page')
            ? $abst_btab->resolve_variation_page_param($params['variation_page'])
            : absint(get_post_meta($test_id, 'variation_page', true));

        if (is_wp_error($variation_id)) {
            return $variation_id;
        }

        if (empty($variation_id)) {
            return new WP_Error('missing_variation_page', 'variation_page is required for full_page tests: the ID or slug of the variation page.', ['status' => 400, 'field' => 'variation_page']);
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
    }

    if ($has_param('target_percentage')) {
        update_post_meta($test_id, 'target_percentage', intval($params['target_percentage']));
    }

    if ($has_param('target_device')) {
        update_post_meta($test_id, 'target_option_device_size', sanitize_text_field($params['target_device']));
    }

    if ($has_param('allowed_roles')) {
        $allowed_roles = array_values(array_map('sanitize_text_field', (array) $params['allowed_roles']));
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

    if ($has_param('log_on_visible')) {
        update_post_meta($test_id, 'log_on_visible', !empty($params['log_on_visible']) ? '1' : '0');
    }
    

    // Minimum days and visits before a winner is called (and emailed about).
    if ($has_param('ac_min_days') || $has_param('ac_min_views')) {
        $min_days = $has_param('ac_min_days')
            ? absint($params['ac_min_days'])
            : intval(get_post_meta($test_id, 'ac_min_days', true));
        $min_views = $has_param('ac_min_views')
            ? absint($params['ac_min_views'])
            : intval(get_post_meta($test_id, 'ac_min_views', true));

        update_post_meta($test_id, 'ac_min_days', $min_days);
        update_post_meta($test_id, 'ac_min_views', $min_views);
    }

    if ($has_param('magic_definition')) {
        // Already decoded and validated at the top of this function.
        $magic_definition = $params['magic_definition'];
        // wp_slash() because update_post_meta() runs wp_unslash() and would otherwise
        // strip the backslashes from JSON-escaped quotes, corrupting the stored JSON.
        update_post_meta($test_id, 'magic_definition', wp_slash(wp_json_encode($magic_definition, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)));
    }

    if ($has_full_page_update) {
        // $effective_default_page and $variation_id were validated above. A new variation
        // page drops the old page's label and screenshot unless new ones are given.
        $abst_btab->save_full_page_pages($test_id, $effective_default_page, $variation_id);

        if ($has_param('variation_label')) {
            update_post_meta($test_id, 'variation_label', sanitize_text_field((string) $params['variation_label']));
        }

        if ($has_param('variation_image')) {
            update_post_meta($test_id, 'variation_page_image', esc_url_raw((string) $params['variation_image']));
        }
    }

    abst_log('Test #' . $test_id . ' settings updated via API (' . ($test_type ?: 'no type') . '): ' . implode(', ', array_keys(array_diff_key($params, ['test_id' => true]))));

    // Refresh conversion pages cache
    if (isset($abst_btab) && method_exists($abst_btab, 'refresh_conversion_pages')) {
        $abst_btab->refresh_conversion_pages();
    }

    $details = (isset($abst_btab) && method_exists($abst_btab, 'get_test_details_payload'))
        ? $abst_btab->get_test_details_payload($test_id)
        : null;
    $conversion_summary = get_post_meta($test_id, 'conversion_page', true);
    $canonical_conversion_type = is_numeric($conversion_summary) ? 'page' : abst_normalize_conversion_type($conversion_summary);

    $applied_settings = [
        'conversion_type' => $details['conversion']['type'] ?? $canonical_conversion_type,
        'conversion_selector' => $details['conversion']['selector'] ?? get_post_meta($test_id, 'conversion_selector', true),
        'conversion_page_id' => $details['conversion']['page_id'] ?? (is_numeric($conversion_summary) ? intval($conversion_summary) : 0),
        'target_percentage' => $details['targeting']['percentage'] ?? intval(get_post_meta($test_id, 'target_percentage', true) ?: 100),
        'target_device' => $details['targeting']['device'] ?? (get_post_meta($test_id, 'target_option_device_size', true) ?: 'all'),
        'allowed_roles' => $details['targeting']['allowed_roles'] ?? (array) get_post_meta($test_id, 'bt_allowed_roles', true),
        'url_query' => $details['targeting']['url_query'] ?? get_post_meta($test_id, 'url_query', true),
        'log_on_visible' => $details['targeting']['log_on_visible'] ?? (get_post_meta($test_id, 'log_on_visible', true) === '1'),
        'ac_min_days' => $details['optimization']['ac_min_days'] ?? intval(get_post_meta($test_id, 'ac_min_days', true)),
        'ac_min_views' => $details['optimization']['ac_min_views'] ?? intval(get_post_meta($test_id, 'ac_min_views', true)),
        'magic_definition' => $details['magic_definition'] ?? null,
        'default_page' => $details['full_page']['default_page'] ?? get_post_meta($test_id, 'bt_experiments_full_page_default_page', true),
        'variation_page' => $details['full_page']['variation_page']['id'] ?? 0,
        'variation_label' => $details['full_page']['variation_page']['label'] ?? '',
        'variation_image' => $details['full_page']['variation_page']['image'] ?? '',
    ];

    return new WP_REST_Response([
        'success' => true,
        'test_id' => $test_id,
        'conversion_type' => $canonical_conversion_type,
        'applied_settings' => $applied_settings,
        'preview_urls' => $details['preview_urls'] ?? [],
        'test' => $details,
        'validation_warnings' => [],
        'message' => 'Test settings updated successfully'
    ], 200);
}
