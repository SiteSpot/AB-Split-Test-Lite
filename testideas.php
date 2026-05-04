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

    $nonce = wp_create_nonce('abst_ti_nonce');
    $ideas = get_posts([
        'post_type' => 'bt_experiments',
        'post_status' => 'idea',
        'posts_per_page' => -1,
        'orderby' => 'date',
        'order' => 'DESC',
    ]);

    $ideas = array_map(static function($idea_post) {
        return abst_ti_build_payload($idea_post->ID);
    }, $ideas);

    usort($ideas, static function($a, $b) {
        $a_score = $a['iceScore'];
        $b_score = $b['iceScore'];
        if ($a_score === null && $b_score === null) {
            return strcmp((string) $b['created_at'], (string) $a['created_at']);
        }
        if ($a_score === null) {
            return 1;
        }
        if ($b_score === null) {
            return -1;
        }
        if ($a_score === $b_score) {
            return strcmp((string) $b['created_at'], (string) $a['created_at']);
        }
        return $b_score - $a_score;
    });
    ?>
    <div class="wrap abst-ti-wrap">
    <h1>Test Ideas</h1>
    <p class="description">Capture rough experiment ideas first, then open them in the test editor when you are ready to choose a test type and configure details.</p>

    <div class="abst-ti-form-box">
        <h2 id="abst-ti-form-title">Add New Test Idea</h2>
        <input type="hidden" id="abst-ti-edit-id" value="">
        <div class="abst-ti-grid2">
            <div class="abst-ti-field">
                <label>Title *</label>
                <input type="text" id="abst-ti-title" placeholder="e.g. Homepage hero idea">
            </div>
            <div class="abst-ti-field">
                <label>Page / Flow</label>
                <input type="text" id="abst-ti-page" placeholder="e.g. Homepage Hero">
            </div>
        </div>
        <div class="abst-ti-field">
            <label>Hypothesis *</label>
            <textarea id="abst-ti-hypothesis" rows="3" placeholder="If we... then more visitors will..."></textarea>
        </div>
        <div class="abst-ti-field">
            <label>Observed Problem</label>
            <textarea id="abst-ti-problem" rows="2" placeholder="Optional context or evidence"></textarea>
        </div>
        <div class="abst-ti-grid4">
            <div class="abst-ti-field">
                <label>Impact</label>
                <input type="number" min="1" max="5" id="abst-ti-impact" placeholder="1-5">
            </div>
            <div class="abst-ti-field">
                <label>Reach</label>
                <input type="number" min="1" max="5" id="abst-ti-reach" placeholder="1-5">
            </div>
            <div class="abst-ti-field">
                <label>Confidence</label>
                <input type="number" min="1" max="5" id="abst-ti-confidence" placeholder="1-5">
            </div>
            <div class="abst-ti-field">
                <label>Effort</label>
                <input type="number" min="1" max="5" id="abst-ti-effort" placeholder="1-5">
            </div>
        </div>
        <div class="abst-ti-field">
            <label>Next Step</label>
            <input type="text" id="abst-ti-nextstep" placeholder="Optional next action">
        </div>
        <div style="margin-top:10px;display:flex;gap:10px;align-items:center;">
            <button type="button" id="abst-ti-submit-btn" class="button button-primary">Add Idea</button>
            <button type="button" id="abst-ti-cancel-btn" class="button" style="display:none;">Cancel</button>
        </div>
    </div>

    <div class="abst-ti-table-wrap">
    <div style="overflow-x:auto;">
    <table class="abst-ti-table">
        <thead><tr>
            <th class="abst-ti-c">Score</th>
            <th>Title</th>
            <th>Page / Flow</th>
            <th>Observed Problem</th>
            <th>Hypothesis</th>
            <th>Next Step</th>
            <th>Actions</th>
        </tr></thead>
        <tbody id="abst-ti-tbody">
        <?php if (empty($ideas)): ?>
            <tr>
                <td colspan="7" style="text-align:center;color:#6b7280;padding:24px 14px !important;">No ideas yet. Add a title and hypothesis to start building your backlog.</td>
            </tr>
        <?php endif; ?>
        <?php foreach ($ideas as $i => $idea): ?>
            <tr class="abst-ti-row"
                data-id="<?php echo intval($idea['id']); ?>"
                data-title="<?php echo esc_attr($idea['title']); ?>"
                data-page="<?php echo esc_attr($idea['page']); ?>"
                data-problem="<?php echo esc_attr($idea['problem']); ?>"
                data-hypothesis="<?php echo esc_attr($idea['hypothesis']); ?>"
                data-impact="<?php echo esc_attr((string) $idea['impact']); ?>"
                data-reach="<?php echo esc_attr((string) $idea['reach']); ?>"
                data-confidence="<?php echo esc_attr((string) $idea['confidence']); ?>"
                data-effort="<?php echo esc_attr((string) $idea['effort']); ?>"
                data-nextstep="<?php echo esc_attr($idea['nextstep']); ?>">
                <td class="abst-ti-c"><span class="abst-ti-score<?php echo $idea['iceScore'] === null ? ' abst-ti-score-muted' : ''; ?>"
                    <?php if ($idea['iceScore'] !== null): ?>
                    data-tooltip="Impact: <?php echo intval($idea['impact']); ?>  Reach: <?php echo intval($idea['reach']); ?>  Confidence: <?php echo intval($idea['confidence']); ?>  Effort: <?php echo intval($idea['effort']); ?>"
                    <?php endif; ?>
                    ><?php echo $idea['iceScore'] === null ? '—' : intval($idea['iceScore']); ?></span></td>
                <td><strong><?php echo esc_html($idea['title']); ?></strong></td>
                <td class="abst-ti-sm"><?php echo esc_html($idea['page'] ?: '—'); ?></td>
                <td class="abst-ti-sm"><?php echo esc_html(wp_trim_words($idea['problem'], 12)); ?></td>
                <td class="abst-ti-sm"><?php echo esc_html(wp_trim_words($idea['hypothesis'], 16)); ?></td>
                <td class="abst-ti-sm"><?php echo esc_html($idea['nextstep'] ?: '—'); ?></td>
                <td style="white-space:nowrap;">
                    <button class="button button-small abst-ti-edit-btn" data-id="<?php echo intval($idea['id']); ?>">Edit</button>
                    <button class="button button-small abst-ti-convert-btn" data-id="<?php echo intval($idea['id']); ?>">Set Up Test</button>
                    <button class="button button-small abst-ti-delete-btn" data-id="<?php echo intval($idea['id']); ?>">Delete</button>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    </div>

    <p class="abst-ti-legend">Only title and hypothesis are required. ICE score appears when all four ratings are filled. Formula: Impact + Reach + Confidence + (6 − Effort).</p>

    </div>

    <style>
    .abst-ti-wrap { max-width: 100%; }
    .abst-ti-wrap h1 { font-size: 23px; font-weight: 400; margin: 0 0 8px; color: #1d2327; }
    .abst-ti-wrap .description { color: #646970; margin: 0 0 20px; font-size: 13px; }
    .abst-ti-form-box,
    .abst-ti-table-wrap {
        background: #fff;
        border-radius: 8px;
        padding: 20px 22px;
        margin-bottom: 20px;
        box-shadow: 0 1px 4px rgba(0,0,0,0.07), 0 0 0 1px rgba(0,0,0,0.05);
    }
    .abst-ti-table-wrap { padding: 0; overflow: hidden; }
    .abst-ti-form-box h2 {
        margin: 0 0 16px;
        padding-bottom: 12px;
        border-bottom: 1px solid #f3f4f6;
        font-size: 13px;
        font-weight: 700;
        color: #111827;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .abst-ti-grid2 { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 12px; }
    .abst-ti-grid4 { display: grid; grid-template-columns: repeat(4,1fr); gap: 14px; margin-bottom: 12px; }
    .abst-ti-field label {
        display: block;
        font-weight: 700;
        font-size: 10px;
        text-transform: uppercase;
        letter-spacing: 0.8px;
        color: #6b7280;
        margin-bottom: 5px;
    }
    .abst-ti-field input[type="text"],
    .abst-ti-field input[type="number"],
    .abst-ti-field textarea {
        width: 100%;
        padding: 8px 10px;
        border: 1px solid #e5e7eb;
        border-radius: 6px;
        font-size: 13px;
        box-sizing: border-box;
        background: #fff;
        color: #111827;
    }
    .abst-ti-table { width: 100%; border-collapse: collapse; margin: 0 !important; }
    .abst-ti-table thead tr { background: #f7f8fa; border-bottom: 2px solid #eaecef; }
    .abst-ti-table thead th {
        color: #6b7280 !important;
        font-size: 10px !important;
        font-weight: 700 !important;
        text-transform: uppercase;
        letter-spacing: 0.8px;
        padding: 10px 14px !important;
        border: none !important;
    }
    .abst-ti-table tbody tr { border-bottom: 1px solid #f3f4f6; }
    .abst-ti-table td {
        padding: 12px 14px !important;
        vertical-align: middle;
        font-size: 13px;
        border: none !important;
        color: #111827;
    }
    .abst-ti-c { text-align: center !important; }
    .abst-ti-sm { font-size: 12px; color: #6b7280; max-width: 180px; line-height: 1.5; }
    .abst-ti-rank {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 26px; height: 26px;
        border-radius: 50%;
        font-weight: 700;
        font-size: 11px;
        background: #f3f4f6;
        color: #6b7280;
    }
    .abst-ti-score {
        display: inline-block;
        background: #2271b1;
        color: #fff;
        font-weight: 700;
        border-radius: 20px;
        padding: 2px 10px;
        font-size: 12px;
        position: relative;
        cursor: default;
    }
    .abst-ti-score-muted { background: #94a3b8; }
    .abst-ti-score[data-tooltip]:hover::after {
        content: attr(data-tooltip);
        position: absolute;
        bottom: calc(100% + 6px);
        left: 50%;
        transform: translateX(-50%);
        background: #1d2327;
        color: #fff;
        font-size: 11px;
        font-weight: 400;
        white-space: nowrap;
        padding: 5px 9px;
        border-radius: 5px;
        pointer-events: none;
        z-index: 99;
        line-height: 1.5;
    }
    .abst-ti-score[data-tooltip]:hover::before {
        content: '';
        position: absolute;
        bottom: calc(100% + 1px);
        left: 50%;
        transform: translateX(-50%);
        border: 5px solid transparent;
        border-top-color: #1d2327;
        pointer-events: none;
        z-index: 99;
    }
    .abst-ti-legend { color: #9ca3af; font-size: 11.5px; margin-top: 12px; }
    </style>

    <script>
    jQuery(function($) {
        var nonce = '<?php echo esc_js($nonce); ?>';

        function resetForm() {
            $('#abst-ti-edit-id').val('');
            $('#abst-ti-title, #abst-ti-page, #abst-ti-nextstep').val('');
            $('#abst-ti-hypothesis, #abst-ti-problem').val('');
            $('#abst-ti-impact, #abst-ti-reach, #abst-ti-confidence, #abst-ti-effort').val('');
            $('#abst-ti-form-title').text('Add New Test Idea');
            $('#abst-ti-submit-btn').text('Add Idea');
            $('#abst-ti-cancel-btn').hide();
        }

        $(document).on('click', '.abst-ti-edit-btn', function() {
            var row = $(this).closest('tr');
            $('#abst-ti-edit-id').val(row.data('id'));
            $('#abst-ti-title').val(row.data('title'));
            $('#abst-ti-page').val(row.data('page'));
            $('#abst-ti-problem').val(row.data('problem'));
            $('#abst-ti-hypothesis').val(row.data('hypothesis'));
            $('#abst-ti-impact').val(row.data('impact'));
            $('#abst-ti-reach').val(row.data('reach'));
            $('#abst-ti-confidence').val(row.data('confidence'));
            $('#abst-ti-effort').val(row.data('effort'));
            $('#abst-ti-nextstep').val(row.data('nextstep'));
            $('#abst-ti-form-title').text('Edit Test Idea');
            $('#abst-ti-submit-btn').text('Update Idea');
            $('#abst-ti-cancel-btn').show();
            $('html,body').animate({scrollTop: $('.abst-ti-form-box').offset().top - 40}, 300);
        });

        $('#abst-ti-cancel-btn').on('click', resetForm);

        $('#abst-ti-submit-btn').on('click', function() {
            $.post(ajaxurl, {
                action: 'abst_ti_save_idea',
                nonce: nonce,
                id: $('#abst-ti-edit-id').val(),
                title: $('#abst-ti-title').val(),
                page: $('#abst-ti-page').val(),
                problem: $('#abst-ti-problem').val(),
                hypothesis: $('#abst-ti-hypothesis').val(),
                impact: $('#abst-ti-impact').val(),
                reach: $('#abst-ti-reach').val(),
                confidence: $('#abst-ti-confidence').val(),
                effort: $('#abst-ti-effort').val(),
                nextstep: $('#abst-ti-nextstep').val()
            }, function(res) {
                if (res.success) {
                    resetForm();
                    location.reload();
                } else if (res.data) {
                    alert(res.data);
                }
            });
        });

        $(document).on('click', '.abst-ti-delete-btn', function() {
            if (!confirm('Delete this idea?')) return;
            $.post(ajaxurl, {
                action: 'abst_ti_delete_idea',
                nonce: nonce,
                id: $(this).data('id')
            }, function(res) {
                if (res.success) location.reload();
            });
        });

        $(document).on('click', '.abst-ti-convert-btn', function() {
            var id = $(this).data('id');
            $.post(ajaxurl, {
                action: 'abst_ti_convert_to_draft',
                nonce: nonce,
                id: id
            }, function(res) {
                if (res.success && res.data && res.data.edit_url) {
                    window.location.href = res.data.edit_url;
                }
            });
        });
    });
    </script>
    <?php
}
