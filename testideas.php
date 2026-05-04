<?php
if (!defined('ABSPATH')) exit;

function abst_ti_get_score($idea_id) {
    $scores = [];
    foreach (['impact', 'reach', 'confidence', 'effort'] as $key) {
        $value = get_post_meta($idea_id, 'abst_idea_' . $key, true);
        if ($value === '' || $value === null) {
            return null;
        }
        $scores[$key] = max(1, min(5, intval($value)));
    }

    return $scores['impact'] + $scores['reach'] + $scores['confidence'] + (6 - $scores['effort']);
}

function abst_ti_build_payload($idea_id) {
    $idea_post = get_post($idea_id);

    return [
        'id' => $idea_id,
        'title' => get_the_title($idea_id),
        'page' => get_post_meta($idea_id, 'abst_idea_page_flow', true),
        'problem' => get_post_meta($idea_id, 'abst_idea_observed_problem', true),
        'hypothesis' => get_post_meta($idea_id, 'abst_idea_hypothesis', true),
        'impact' => get_post_meta($idea_id, 'abst_idea_impact', true),
        'reach' => get_post_meta($idea_id, 'abst_idea_reach', true),
        'confidence' => get_post_meta($idea_id, 'abst_idea_confidence', true),
        'effort' => get_post_meta($idea_id, 'abst_idea_effort', true),
        'iceScore' => abst_ti_get_score($idea_id),
        'nextstep' => get_post_meta($idea_id, 'abst_idea_next_step', true),
        'edit_url' => admin_url('post.php?post=' . $idea_id . '&action=edit'),
        'created_at' => $idea_post ? $idea_post->post_date : '',
    ];
}

add_action('wp_ajax_abst_ti_save_idea', 'abst_ti_save_idea_ajax');
function abst_ti_save_idea_ajax() {
    $nonce = isset($_POST['nonce']) ? sanitize_text_field(wp_unslash($_POST['nonce'])) : '';
    if (!$nonce || !wp_verify_nonce($nonce, 'abst_ti_nonce') || !current_user_can('manage_options')) {
        wp_send_json_error('Unauthorized');
    }

    $idea_id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $title = sanitize_text_field($_POST['title'] ?? $_POST['page'] ?? '');
    $hypothesis = sanitize_textarea_field($_POST['hypothesis'] ?? '');

    if ($title === '') {
        $title = 'Test Idea';
    }

    if ($hypothesis === '') {
        wp_send_json_error('Hypothesis is required.');
    }

    $post_data = [
        'post_title' => $title,
        'post_type' => 'bt_experiments',
        'post_status' => 'idea',
    ];

    if ($idea_id > 0) {
        $post_data['ID'] = $idea_id;
        $result = wp_update_post($post_data, true);
    } else {
        $result = wp_insert_post($post_data, true);
        $idea_id = is_wp_error($result) ? 0 : intval($result);
    }

    if (is_wp_error($result) || !$idea_id) {
        wp_send_json_error('Failed to save idea.');
    }

    update_post_meta($idea_id, 'abst_idea_hypothesis', $hypothesis);
    update_post_meta($idea_id, 'abst_idea_page_flow', sanitize_textarea_field($_POST['page'] ?? ''));
    update_post_meta($idea_id, 'abst_idea_observed_problem', sanitize_textarea_field($_POST['problem'] ?? ''));
    update_post_meta($idea_id, 'abst_idea_next_step', sanitize_textarea_field($_POST['nextstep'] ?? ''));

    foreach (['impact', 'reach', 'confidence', 'effort'] as $score_key) {
        $raw = $_POST[$score_key] ?? '';
        $meta_key = 'abst_idea_' . $score_key;
        if ($raw === '' || $raw === null) {
            delete_post_meta($idea_id, $meta_key);
            continue;
        }
        update_post_meta($idea_id, $meta_key, max(1, min(5, intval($raw))));
    }

    wp_send_json_success(['idea' => abst_ti_build_payload($idea_id)]);
}

add_action('wp_ajax_abst_ti_delete_idea', 'abst_ti_delete_idea_ajax');
function abst_ti_delete_idea_ajax() {
    $nonce = isset($_POST['nonce']) ? sanitize_text_field(wp_unslash($_POST['nonce'])) : '';
    if (!$nonce || !wp_verify_nonce($nonce, 'abst_ti_nonce') || !current_user_can('manage_options')) {
        wp_send_json_error('Unauthorized');
    }

    $idea_id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    if (!$idea_id || get_post_type($idea_id) !== 'bt_experiments') {
        wp_send_json_error('Idea not found.');
    }

    wp_delete_post($idea_id, true);
    wp_send_json_success();
}

add_action('wp_ajax_abst_ti_convert_to_draft', 'abst_ti_convert_to_draft_ajax');
function abst_ti_convert_to_draft_ajax() {
    $nonce = isset($_POST['nonce']) ? sanitize_text_field(wp_unslash($_POST['nonce'])) : '';
    if (!$nonce || !wp_verify_nonce($nonce, 'abst_ti_nonce') || !current_user_can('manage_options')) {
        wp_send_json_error('Unauthorized');
    }

    $idea_id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    if (!$idea_id || get_post_type($idea_id) !== 'bt_experiments') {
        wp_send_json_error('Idea not found.');
    }

    $result = wp_update_post([
        'ID' => $idea_id,
        'post_status' => 'draft',
    ], true);

    if (is_wp_error($result)) {
        wp_send_json_error('Failed to convert idea.');
    }

    wp_send_json_success([
        'edit_url' => admin_url('post.php?post=' . $idea_id . '&action=edit&focus=settings'),
    ]);
}

function abst_test_ideas_page_content() {
    if (!current_user_can('manage_options')) return;
    ?>
    <div class="wrap">
        <h1>Test Ideas</h1>
        <p class="description">Capture rough experiment ideas first, then open them in the test editor when you are ready to choose a test type and configure details.</p>
        <p style="margin: 20px 0;">
            <a href="https://absplittest.com/pricing?ref=upgradefeaturelink" target="_blank" class="button button-primary">Upgrade to unlock Test Ideas</a>
        </p>
    </div>
    <?php
}
