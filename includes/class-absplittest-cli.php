<?php
/**
 * WP-CLI commands for ABSPLITTEST
 *
 * @package    Bt_Ab_Tests
 * @subpackage Bt_Ab_Tests/includes
 */

if (!defined('WP_CLI') || !WP_CLI) {
    return;
}

class ABSPLITTEST_CLI {

    /**
     * Create a new A/B test or test idea
     *
     * ## OPTIONS
     *
     * --name=<name>
     * : The name/title of the test
     *
     * [--type=<type>]
     * : Test type: magic, ab_test, css_test, or full_page. Required for runnable tests, optional for ideas.
     *
     * [--status=<status>]
     * : Post status: idea, publish, draft, pending, or complete
     * ---
     * default: draft
     * ---
     *
     * [--abst_idea_hypothesis=<text>]
     * : For --status=idea, the required hypothesis. Ideas only require --name and --abst_idea_hypothesis.
     *
     * [--abst_idea_page_flow=<text>]
     * : Optional page or flow for an idea.
     *
     * [--abst_idea_observed_problem=<text>]
     * : Optional observed problem or evidence for an idea.
     *
     * [--abst_idea_impact=<1-5>]
     * : Optional impact score for an idea.
     *
     * [--abst_idea_reach=<1-5>]
     * : Optional reach score for an idea.
     *
     * [--abst_idea_confidence=<1-5>]
     * : Optional confidence score for an idea.
     *
     * [--abst_idea_effort=<1-5>]
     * : Optional effort score for an idea.
     *
     * [--abst_idea_next_step=<text>]
     * : Optional next step for an idea.
     *
     * [--magic_definition=<json>]
     * : JSON string for magic test definition (required for magic tests)
     *
     * [--conversion_page=<page_id>]
     * : Conversion page ID or trigger type
     *
     * [--conversion_use_order_value]
     * : Use order value instead of a simple conversion count
     *
     * [--target_percentage=<percentage>]
     * : Percentage of traffic to include in test (1-100)
     * ---
     * default: 100
     * ---
     *
     * [--subgoals=<json>]
     * : JSON array of subgoal objects. Each needs "type" and "value".
     *   Types: scroll, url, page, selector, link, time, text, etc.
     *   Example: '[{"type":"scroll","value":"50"},{"type":"scroll","value":"80"}]'
     *   When omitted, no subgoals are created.
     *   Pass '[]' to explicitly create the test with no subgoals.
     *
     * ## EXAMPLES
     *
     *     # Create a lightweight idea
     *     wp absplittest create_test --name="Homepage hero idea" --status=idea --abst_idea_hypothesis="If we clarify the value proposition, more visitors will click through."
     *
     *     # Create a draft magic test with explicit scope and no subgoals
     *     wp absplittest create_test --name="My Magic Test" --type=magic --magic_definition='[{"type":"text","selector":"h1","scope":{"page_id":42},"variations":["Old Text","New Text"]}]'
     *
     *     # Create a test with custom subgoals
     *     wp absplittest create_test --name="Homepage Test" --type=full_page --status=publish --conversion_page=123 --subgoals='[{"type":"scroll","value":"50"},{"type":"url","value":"thank-you"}]'
     *
     *     # Create a test with no subgoals
     *     wp absplittest create_test --name="Simple Test" --type=ab_test --conversion_type=url --conversion_url=thanks --subgoals='[]'
     *
     * @when after_wp_load
     */
    public function create_test($args, $assoc_args) {
        global $btab;

        if (!isset($btab) || !method_exists($btab, 'rest_create_test')) {
            WP_CLI::error('ABSPLITTEST REST create handler is not available.');
        }

        $payload = $assoc_args;
        $payload['test_title'] = $assoc_args['name'] ?? '';
        $payload['test_type'] = $assoc_args['type'] ?? '';
        unset($payload['name'], $payload['type']);

        if (!empty($assoc_args['conversion_page']) && empty($payload['conversion_type'])) {
            $payload['conversion_type'] = $assoc_args['conversion_page'];
        }

        // Parse subgoals JSON if provided
        if (isset($payload['subgoals'])) {
            $decoded = json_decode($payload['subgoals'], true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                WP_CLI::error('Invalid JSON for --subgoals: ' . json_last_error_msg());
            }
            $payload['subgoals'] = $decoded;
        }

        $request = new WP_REST_Request('POST', '/bt-bb-ab/v1/create-test');
        $request->set_header('Content-Type', 'application/json');
        $request->set_body(wp_json_encode($payload));

        $response = $btab->rest_create_test($request);
        if (is_wp_error($response)) {
            WP_CLI::error($response->get_error_message());
        }

        $data = $response->get_data();
        $created_status = $data['normalized']['status'] ?? ($payload['status'] ?? 'draft');
        $created_type = $data['test_type'] ?? '';
        $type_label = $created_type !== '' ? $created_type : 'idea';
        WP_CLI::success("Created test #{$data['test_id']}: {$payload['test_title']} (type: {$type_label}, status: {$created_status})");
        if ($created_status !== 'idea') {
            WP_CLI::line('Conversion Type: ' . ($data['conversion_type'] ?? 'not set'));
        }
        if (!empty($data['subgoals'])) {
            WP_CLI::line('Subgoals: ' . wp_json_encode($data['subgoals']));
        }
        WP_CLI::line('Edit URL: ' . $data['edit_url']);
    }

    /**
     * List all A/B tests
     *
     * ## OPTIONS
     *
     * [--status=<status>]
     * : Filter by post status: idea, publish, draft, pending, complete, or any
     * ---
     * default: any
     * ---
     *
     * [--type=<type>]
     * : Filter by test type: magic, ab_test, css_test, or full_page
     *
     * [--format=<format>]
     * : Output format
     * ---
     * default: table
     * options:
     *   - table
     *   - csv
     *   - json
     *   - yaml
     * ---
     *
     * ## EXAMPLES
     *
     *     # List all tests
     *     wp absplittest list_tests
     *
     *     # List only published magic tests
     *     wp absplittest list_tests --status=publish --type=magic
     *
     *     # Export to JSON
     *     wp absplittest list_tests --format=json
     *
     * @when after_wp_load
     */
    public function list_tests($args, $assoc_args) {
        require_once plugin_dir_path(dirname(__FILE__)) . 'bt-bb-ab-validation.php';

        $status = isset($assoc_args['status']) ? $assoc_args['status'] : 'any';
        $status = abst_normalize_test_status($status, 'list');
        $format = isset($assoc_args['format']) ? $assoc_args['format'] : 'table';

        if ($status !== 'any' && !in_array($status, abst_get_supported_test_statuses(), true)) {
            WP_CLI::error('Invalid status. Must be one of: any, ' . implode(', ', abst_get_supported_test_statuses()));
        }
        
        $query_args = array(
            'post_type'      => 'bt_experiments',
            'post_status'    => $status === 'any' ? (btab_user_level() == 'pro' ? array('idea', 'publish', 'draft', 'pending', 'complete') : array('publish', 'draft', 'pending', 'complete')) : $status,
            'posts_per_page' => -1,
            'orderby'        => 'date',
            'order'          => 'DESC',
        );
        
        // Add type filter if specified
        if (isset($assoc_args['type'])) {
            $query_args['meta_query'] = array(
                array(
                    'key'   => 'test_type',
                    'value' => $assoc_args['type'],
                ),
            );
        }
        
        $tests = get_posts($query_args);
        
        if (empty($tests)) {
            WP_CLI::warning("No tests found.");
            return;
        }
        
        $output = array();
        foreach ($tests as $test) {
            $test_type = get_post_meta($test->ID, 'test_type', true);
            $observations = get_post_meta($test->ID, 'observations', true);
            $obs = maybe_unserialize($observations);

            $visits = 0;
            $conversions = 0;
            if (is_array($obs)) {
                foreach ($obs as $var_key => $var_data) {
                    if ($var_key === 'bt_bb_ab_stats') continue;
                    $visits += isset($var_data['visit']) ? intval($var_data['visit']) : 0;
                    $conversions += isset($var_data['conversion']) ? intval($var_data['conversion']) : 0;
                }
            }
            $conversion_rate = $visits > 0 ? round(($conversions / $visits) * 100, 2) : 0;

            $output[] = array(
                'ID'              => $test->ID,
                'Title'           => $test->post_title,
                'Type'            => $test_type,
                'Status'          => $test->post_status,
                'Visits'          => $visits,
                'Conversions'     => $conversions,
                'Conversion Rate' => $conversion_rate . '%',
                'Date'            => $test->post_date,
            );
        }
        
        WP_CLI\Utils\format_items($format, $output, array('ID', 'Title', 'Type', 'Status', 'Visits', 'Conversions', 'Conversion Rate', 'Date'));
    }

    /**
     * Get detailed results for a specific test
     *
     * ## OPTIONS
     *
     * <id>
     * : The test ID
     *
     * [--format=<format>]
     * : Output format
     * ---
     * default: table
     * options:
     *   - table
     *   - json
     *   - yaml
     * ---
     *
     * ## EXAMPLES
     *
     *     # Get results for test #123
     *     wp absplittest get_results 123
     *
     *     # Get results as JSON
     *     wp absplittest get_results 123 --format=json
     *
     * @when after_wp_load
     */
    public function get_results($args, $assoc_args) {
        $test_id = intval($args[0]);
        $format = isset($assoc_args['format']) ? $assoc_args['format'] : 'table';
        
        $test = get_post($test_id);
        
        if (!$test || $test->post_type !== 'bt_experiments') {
            WP_CLI::error("Test #{$test_id} not found.");
        }
        
        WP_CLI::line("Test: {$test->post_title} (#{$test_id})");
        $test_type = get_post_meta($test_id, 'test_type', true);
        WP_CLI::line("Type: " . ($test_type ?: 'idea'));
        WP_CLI::line("Status: {$test->post_status}");
        WP_CLI::line("");

        if ($test->post_status === 'idea') {
            WP_CLI::line("Idea Details:");
            WP_CLI::line("  Hypothesis: " . get_post_meta($test_id, 'abst_idea_hypothesis', true));
            WP_CLI::line("  Page / Flow: " . (get_post_meta($test_id, 'abst_idea_page_flow', true) ?: '-'));
            WP_CLI::line("  Observed Problem: " . (get_post_meta($test_id, 'abst_idea_observed_problem', true) ?: '-'));
            WP_CLI::line("  Impact: " . (get_post_meta($test_id, 'abst_idea_impact', true) ?: '-'));
            WP_CLI::line("  Reach: " . (get_post_meta($test_id, 'abst_idea_reach', true) ?: '-'));
            WP_CLI::line("  Confidence: " . (get_post_meta($test_id, 'abst_idea_confidence', true) ?: '-'));
            WP_CLI::line("  Effort: " . (get_post_meta($test_id, 'abst_idea_effort', true) ?: '-'));
            WP_CLI::line("  Next Step: " . (get_post_meta($test_id, 'abst_idea_next_step', true) ?: '-'));
            return;
        }

        // Get stats from observations (source of truth)
        $observations = get_post_meta($test_id, 'observations', true);
        $obs = maybe_unserialize($observations);

        $total_visits = 0;
        $total_conversions = 0;
        $variation_output = array();
        $min_visits_for_winner = apply_filters('ab_min_visits_for_winner', 50);
        $underpowered_variations = array();

        if (is_array($obs)) {
            $winner = get_post_meta($test_id, 'test_winner', true);

            foreach ($obs as $var_key => $var_data) {
                if ($var_key === 'bt_bb_ab_stats') continue;

                $var_visits = isset($var_data['visit']) ? intval($var_data['visit']) : 0;
                $var_conversions = isset($var_data['conversion']) ? intval($var_data['conversion']) : 0;
                $var_rate = isset($var_data['rate']) ? floatval($var_data['rate']) : 0;

                $total_visits += $var_visits;
                $total_conversions += $var_conversions;

                $label = $var_key;
                if ($winner === $var_key) {
                    $label .= ' (winner)';
                }
                if ($var_visits < $min_visits_for_winner) {
                    $underpowered_variations[] = $var_key;
                    $label .= ' [underpowered]';
                }

                $variation_output[] = array(
                    'Variation'       => $label,
                    'Visits'          => $var_visits,
                    'Conversions'     => $var_conversions,
                    'Conversion Rate' => round($var_rate, 2) . '%',
                );
            }
        }

        $total_rate = $total_visits > 0 ? round(($total_conversions / $total_visits) * 100, 2) : 0;

        WP_CLI::line("Overall Stats:");
        WP_CLI::line("  Visits: {$total_visits}");
        WP_CLI::line("  Conversions: {$total_conversions}");
        WP_CLI::line("  Conversion Rate: {$total_rate}%");
        WP_CLI::line("");

        if (!empty($variation_output)) {
            WP_CLI::line("Variation Results:");
            WP_CLI\Utils\format_items($format, $variation_output, array('Variation', 'Visits', 'Conversions', 'Conversion Rate'));
            if (!empty($underpowered_variations)) {
                WP_CLI::line("");
                WP_CLI::warning("Underpowered: need at least {$min_visits_for_winner} visits per variation before confidence can be trusted.");
            }
        }
    }

    /**
     * Update test settings (conversion goals, etc.)
     *
     * ## OPTIONS
     *
     * <id>
     * : The test ID
     *
     * [--conversion_type=<type>]
     * : Conversion type (e.g., edd-purchase, woo-order-received, url, selector)
     *
     * [--conversion_selector=<selector>]
     * : CSS selector for selector/click conversion type
     *
     * [--conversion_url=<url>]
     * : URL path for url conversion type
     *
     * [--conversion_page_id=<id>]
     * : Page ID for page conversion type
     *
     * [--conversion_time=<seconds>]
     * : Seconds for time conversion type
     *
     * [--conversion_scroll=<percentage>]
     * : Scroll percentage for scroll conversion type
     *
     * [--conversion_text=<text>]
     * : Text string for text conversion type
     *
     * [--conversion_link_pattern=<pattern>]
     * : Link pattern for link conversion type
     *
     * [--conversion_use_order_value]
     * : Use order value instead of a simple conversion count
     *
     * [--subgoals=<json>]
     * : JSON array of subgoal objects to replace existing subgoals.
     *   Each needs "type" and "value".
     *   Example: '[{"type":"scroll","value":"50"},{"type":"url","value":"thank-you"}]'
     *   Pass '[]' to remove all subgoals.
     *
     * ## EXAMPLES
     *
     *     # Update conversion to EDD purchase
     *     wp absplittest update_settings 123 --conversion_type=edd-purchase --conversion_use_order_value
     *
     *     # Update conversion to URL
     *     wp absplittest update_settings 123 --conversion_type=url --conversion_url=thank-you
     *
     *     # Update subgoals only
     *     wp absplittest update_settings 123 --subgoals='[{"type":"scroll","value":"50"},{"type":"scroll","value":"80"}]'
     *
     *     # Remove all subgoals
     *     wp absplittest update_settings 123 --subgoals='[]'
     *
     * @when after_wp_load
     */
    public function update_settings($args, $assoc_args) {
        global $btab;

        $test_id = intval($args[0]);
        
        $test = get_post($test_id);
        
        if (!$test || $test->post_type !== 'bt_experiments') {
            WP_CLI::error("Test #{$test_id} not found.");
        }

        if (!isset($btab) || !method_exists($btab, 'rest_update_test_settings')) {
            WP_CLI::error('ABSPLITTEST REST update settings handler is not available.');
        }

        if (empty($assoc_args)) {
            WP_CLI::warning("No settings were updated. Please provide at least one setting to update.");
            return;
        }

        $payload = $assoc_args;
        $payload['test_id'] = $test_id;

        // Parse subgoals JSON if provided
        if (isset($payload['subgoals'])) {
            $decoded = json_decode($payload['subgoals'], true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                WP_CLI::error('Invalid JSON for --subgoals: ' . json_last_error_msg());
            }
            $payload['subgoals'] = $decoded;
        }

        $request = new WP_REST_Request('POST', '/bt-bb-ab/v1/update-test-settings');
        $request->set_header('Content-Type', 'application/json');
        $request->set_body(wp_json_encode($payload));

        $response = $btab->rest_update_test_settings($request);
        if (is_wp_error($response)) {
            WP_CLI::error($response->get_error_message());
        }

        $data = $response->get_data();
        WP_CLI::success("Updated test #{$test_id} settings.");
        foreach (($data['applied_settings'] ?? []) as $key => $value) {
            if ($value === '' || $value === null || $value === false) {
                continue;
            }
            if (is_array($value)) {
                $value = wp_json_encode($value);
            }
            if (is_bool($value)) {
                $value = $value ? 'true' : 'false';
            }
            WP_CLI::line("  - {$key}: {$value}");
        }
        if (array_key_exists('subgoals', $data)) {
            WP_CLI::line('  - subgoals: ' . wp_json_encode($data['subgoals']));
        }
    }

    /**
     * Update test status
     *
     * ## OPTIONS
     *
     * <id>
     * : The test ID
     *
     * <status>
     * : New status: publish, draft, pending, or complete
     *
     * ## EXAMPLES
     *
     *     # Publish a test
     *     wp absplittest update_status 123 publish
     *
     *     # Set test to draft
     *     wp absplittest update_status 123 draft
     *
     * @when after_wp_load
     */
    public function update_status($args, $assoc_args) {
        require_once plugin_dir_path(dirname(__FILE__)) . 'bt-bb-ab-validation.php';
        global $btab;

        $test_id = intval($args[0]);
        $status = abst_normalize_test_status($args[1]);
        
        $valid_statuses = abst_get_supported_test_statuses();
        if (!in_array($status, $valid_statuses)) {
            WP_CLI::error("Invalid status. Must be one of: " . implode(', ', $valid_statuses));
        }
        
        $test = get_post($test_id);
        
        if (!$test || $test->post_type !== 'bt_experiments') {
            WP_CLI::error("Test #{$test_id} not found.");
        }

        if (!isset($btab) || !method_exists($btab, 'rest_update_test_status')) {
            WP_CLI::error('ABSPLITTEST REST update status handler is not available.');
        }

        $request = new WP_REST_Request('POST', '/bt-bb-ab/v1/update-test-status');
        $request->set_header('Content-Type', 'application/json');
        $request->set_body(wp_json_encode([
            'test_id' => $test_id,
            'status' => $status,
        ]));

        $response = $btab->rest_update_test_status($request);
        if (is_wp_error($response)) {
            WP_CLI::error($response->get_error_message());
        }

        $data = $response->get_data();
        WP_CLI::success("Updated test #{$test_id} status to: {$data['status']}");
    }

    /**
     * Get subgoals for a specific test
     *
     * ## OPTIONS
     *
     * <id>
     * : The test ID
     *
     * [--format=<format>]
     * : Output format
     * ---
     * default: table
     * options:
     *   - table
     *   - json
     *   - yaml
     * ---
     *
     * ## EXAMPLES
     *
     *     # Get subgoals for test #123
     *     wp absplittest get_subgoals 123
     *
     *     # Get subgoals as JSON
     *     wp absplittest get_subgoals 123 --format=json
     *
     * @when after_wp_load
     */
    public function get_subgoals($args, $assoc_args) {
        require_once plugin_dir_path(dirname(__FILE__)) . 'bt-bb-ab-validation.php';

        $test_id = intval($args[0]);
        $format  = $assoc_args['format'] ?? 'table';

        $test = get_post($test_id);
        if (!$test || $test->post_type !== 'bt_experiments') {
            WP_CLI::error("Test #{$test_id} not found.");
        }

        $goals    = get_post_meta($test_id, 'goals', true);
        $subgoals = abst_storage_subgoals_to_api($goals);

        if (empty($subgoals)) {
            WP_CLI::line("Test #{$test_id} has no subgoals.");
            return;
        }

        WP_CLI::line("Subgoals for test #{$test_id} \"{$test->post_title}\":");

        $rows = [];
        foreach ($subgoals as $index => $sg) {
            $rows[] = [
                '#'     => $index + 1,
                'Type'  => $sg['type'],
                'Value' => $sg['value'],
            ];
        }

        WP_CLI\Utils\format_items($format, $rows, ['#', 'Type', 'Value']);
    }

    /**
     * Update (replace) subgoals for a specific test
     *
     * ## OPTIONS
     *
     * <id>
     * : The test ID
     *
     * <subgoals>
     * : JSON array of subgoal objects. Each needs "type" and "value".
     *   Types: scroll, url, page, selector, link, time, text, and any active conversion type.
     *   Pass '[]' to remove all subgoals.
     *
     * ## EXAMPLES
     *
     *     # Set default scroll depth subgoals
     *     wp absplittest update_subgoals 123 '[{"type":"scroll","value":"50"},{"type":"scroll","value":"80"}]'
     *
     *     # Set a URL and scroll subgoal
     *     wp absplittest update_subgoals 123 '[{"type":"url","value":"thank-you"},{"type":"scroll","value":"75"}]'
     *
     *     # Remove all subgoals
     *     wp absplittest update_subgoals 123 '[]'
     *
     * @when after_wp_load
     */
    public function update_subgoals($args, $assoc_args) {
        require_once plugin_dir_path(dirname(__FILE__)) . 'bt-bb-ab-validation.php';
        global $btab;

        $test_id      = intval($args[0]);
        $subgoals_raw = $args[1] ?? '[]';

        $test = get_post($test_id);
        if (!$test || $test->post_type !== 'bt_experiments') {
            WP_CLI::error("Test #{$test_id} not found.");
        }

        $subgoals = json_decode($subgoals_raw, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            WP_CLI::error('Invalid JSON: ' . json_last_error_msg());
        }

        $validation = abst_validate_subgoals($subgoals);
        if (is_wp_error($validation)) {
            WP_CLI::error($validation->get_error_message());
        }

        update_post_meta($test_id, 'goals', abst_normalize_subgoals_to_storage($subgoals));

        $saved = abst_storage_subgoals_to_api(get_post_meta($test_id, 'goals', true));

        if (empty($saved)) {
            WP_CLI::success("Removed all subgoals from test #{$test_id}.");
            return;
        }

        WP_CLI::success("Updated subgoals for test #{$test_id}:");

        $rows = [];
        foreach ($saved as $index => $sg) {
            $rows[] = [
                '#'     => $index + 1,
                'Type'  => $sg['type'],
                'Value' => $sg['value'],
            ];
        }

        WP_CLI\Utils\format_items('table', $rows, ['#', 'Type', 'Value']);
    }

}

WP_CLI::add_command('absplittest', 'ABSPLITTEST_CLI');
