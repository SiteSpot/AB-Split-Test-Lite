<?php
/**
 * Session Replay Module
 * ---------------------
 * Provides session replay functionality for viewing user journeys.
 * Uses existing journey data from heatmap tracking to replay user sessions
 * with cursor movement and click visualization.
 *
 * Features:
 * - Session index with filtering (pages, tests, conversions, rage clicks)
 * - Timeline visualization of user journey
 * - Iframe-based page replay with cursor and click animations
 * - Playback controls (play/pause/speed/jump)
 */

if (!defined('ABSPATH')) {
    exit;
}

class ABST_Session_Replay {

    /**
     * Transient key for session index
     */
    const INDEX_TRANSIENT_KEY = 'abst_session_index_v3';
    
    /**
     * Transient expiry in seconds (1 hour)
     */
    const INDEX_TRANSIENT_EXPIRY = 3600;

    /**
     * Sessions per page for pagination
     */
    const SESSIONS_PER_PAGE = 25;

    /**
     * Extract query parameters from a raw journey URL or query-string fragment.
     *
     * Supports full URLs, relative paths, and raw query strings.
     *
     * @param string $raw_url
     * @return array
     */
    private function parse_journey_query_params($raw_url) {
        if (!is_string($raw_url) || $raw_url === '') {
            return [];
        }

        $query = wp_parse_url($raw_url, PHP_URL_QUERY);

        if ($query === null || $query === false) {
            if (strpos($raw_url, '?') === 0) {
                $query = ltrim($raw_url, '?');
            } else {
                return [];
            }
        }

        if (!is_string($query) || $query === '') {
            return [];
        }

        $params = [];
        parse_str($query, $params);

        return is_array($params) ? $params : [];
    }

    public function __construct() {
        // Menu is registered in bt-bb-ab.php abst_add_logs_page() for proper ordering
        
        // Enqueue assets
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
        
        // AJAX endpoints
        add_action('wp_ajax_abst_get_sessions', [$this, 'ajax_get_sessions']);
        add_action('wp_ajax_abst_get_session_events', [$this, 'ajax_get_session_events']);
    }

    /**
     * Enqueue scripts and styles for session replay page
     */
    public function enqueue_assets($hook) {
        if ($hook !== 'bt_experiments_page_abst-session-replay') {
            return;
        }

        wp_enqueue_style(
            'abst-session-replay',
            plugins_url('css/session-replay.css', dirname(dirname(__FILE__))),
            [],
            BT_AB_TEST_VERSION
        );

        wp_enqueue_script(
            'abst-session-replay',
            plugins_url('js/session-replay.js', dirname(dirname(__FILE__))),
            ['jquery'],
            BT_AB_TEST_VERSION,
            true
        );

        wp_localize_script('abst-session-replay', 'abstSessionReplay', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('abst_session_replay'),
            'homeUrl' => home_url(),
            'strings' => [
                'loading' => __('Loading...', 'ab-split-test-lite'),
                'noSessions' => __('No sessions found.', 'ab-split-test-lite'),
                'loadingSession' => __('Loading session data...', 'ab-split-test-lite'),
                'pageView' => __('Page View', 'ab-split-test-lite'),
                'click' => __('Click', 'ab-split-test-lite'),
                'rageClick' => __('Rage Click', 'ab-split-test-lite'),
                'conversion' => __('Conversion', 'ab-split-test-lite'),
            ]
        ]);
    }

    /**
     * Render the admin page
     */
    public function render_admin_page() {
        include plugin_dir_path(__FILE__) . 'session-page.php';
    }

    /**
     * AJAX: Get filtered session list
     */
    public function ajax_get_sessions() {
        check_ajax_referer('abst_session_replay', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        $page = isset($_POST['page']) ? max(1, intval($_POST['page'])) : 1;

        $index = $this->get_session_index();

        // Sort by start time descending (most recent first)
        usort($index, function($a, $b) {
            return strcmp($b['start_time'], $a['start_time']);
        });

        $filtered = $index;
        
        // Paginate
        $total = count($filtered);
        $total_pages = ceil($total / self::SESSIONS_PER_PAGE);
        $offset = ($page - 1) * self::SESSIONS_PER_PAGE;
        $sessions = array_slice($filtered, $offset, self::SESSIONS_PER_PAGE);
        
        // Enrich with page titles
        foreach ($sessions as &$session) {
            $session['page_titles'] = [];
            foreach ($session['pages'] as $page_info) {
                $title = get_the_title($page_info['post_id']);
                $session['page_titles'][] = $title ?: 'Page ' . $page_info['post_id'];
            }
        }
        
        wp_send_json_success([
            'sessions' => $sessions,
            'total' => $total,
            'total_pages' => $total_pages,
            'current_page' => $page,
        ]);
    }

    /**
     * AJAX: Get full events for a specific session
     */
    public function ajax_get_session_events() {
        check_ajax_referer('abst_session_replay', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        $uuid = isset($_POST['uuid']) ? sanitize_text_field(wp_unslash($_POST['uuid'])) : '';
        $dates_raw = isset($_POST['dates']) && is_array($_POST['dates']) ? wp_unslash($_POST['dates']) : [];
        $dates = array_values(array_filter(array_map(function($date) {
            $date = sanitize_text_field($date);
            // Only accept an 8-digit Ymd string; reject anything that could traverse the path.
            return preg_match('/^\d{8}$/', $date) ? $date : null;
        }, $dates_raw)));
        
        if (empty($uuid) || empty($dates)) {
            wp_send_json_error('Missing required parameters');
        }

        $journeys = new ABST_Journeys();
        $events = [];
        
        foreach ($dates as $date) {
            $journey_data = $journeys->read_journey_file($date);
            foreach ($journey_data as $record) {
                if ($record['uuid'] === $uuid) {
                    $events[] = $record;
                }
            }
        }
        
        // Sort by timestamp
        usort($events, function($a, $b) {
            return strcmp($a['timestamp'], $b['timestamp']);
        });
        
        // Enrich events with page URLs
        foreach ($events as &$event) {
            if ($event['type'] === 'pv' && !empty($event['post_id'])) {
                // Use abst_resolve_page_url to handle archives, categories, taxonomies
                $event['page_url'] = abst_resolve_page_url($event['post_id']);
                $event['page_title'] = abst_resolve_page_title($event['post_id']);
            }
        }
        
        wp_send_json_success($events);
    }

    /**
     * Get session index from transient or build it
     */
    public function get_session_index() {
        $index = get_transient(self::INDEX_TRANSIENT_KEY);
        
        if ($index === false) {
            $index = $this->build_session_index();
            set_transient(self::INDEX_TRANSIENT_KEY, $index, self::INDEX_TRANSIENT_EXPIRY);
        }
        
        return $index;
    }

    /**
     * Build session index from journey files
     */
    public function build_session_index() {
        $journeys = new ABST_Journeys();
        $sessions = [];
        
        // Get retention period
        $retention_days = abst_get_admin_setting('abst_heatmap_retention_length') ?? 30;
        
        // Scan journey files for the retention period
        for ($i = 0; $i < $retention_days; $i++) {
            $date = gmdate('Ymd', strtotime("-{$i} days"));
            $journey_data = $journeys->read_journey_file($date);
            
            if (empty($journey_data)) {
                continue;
            }
            
            // Group events by UUID
            foreach ($journey_data as $record) {
                $uuid = $record['uuid'];
                
                if (!isset($sessions[$uuid])) {
                    $sessions[$uuid] = [
                        'uuid' => $uuid,
                        'start_time' => null,
                        'end_time' => null,
                        'duration_seconds' => 0,
                        'device' => $record['screen_size'] ?? 'l',
                        'user_id' => 0,
                        'max_scroll_depth' => 0,
                        'page_count' => 0,
                        'click_count' => 0,
                        'rage_click_count' => 0,
                        'dead_click_count' => 0,
                        'pages' => [],
                        'tests_seen' => [],
                        'tests_converted' => [],
                        'variations_seen' => [],
                        'dates' => [],
                        'exit_page_id' => 0,
                        'referrer' => '',
                        'utm_source' => '',
                        'utm_medium' => '',
                        'utm_campaign' => '',
                    ];
                }
                
                $session = &$sessions[$uuid];
                
                // Track which dates contain this session
                if (!in_array($date, $session['dates'])) {
                    $session['dates'][] = $date;
                }
                
                // Update timestamps
                $timestamp = $record['timestamp'];
                if ($session['start_time'] === null || $timestamp < $session['start_time']) {
                    $session['start_time'] = $timestamp;
                }
                if ($session['end_time'] === null || $timestamp > $session['end_time']) {
                    $session['end_time'] = $timestamp;
                }
                
                // Capture referrer from metadata (first occurrence wins)
                if (empty($session['referrer']) && !empty($record['referrer'])) {
                    $ref_host = wp_parse_url($record['referrer'], PHP_URL_HOST);
                    $session['referrer'] = $ref_host ?: '';
                }

                // Capture UTM params from event URL/query string (first occurrence wins)
                if (!empty($record['url']) && (empty($session['utm_source']) || empty($session['utm_medium']) || empty($session['utm_campaign']))) {
                    $qp = $this->parse_journey_query_params($record['url']);
                    if (empty($session['utm_source']) && !empty($qp['utm_source'])) $session['utm_source'] = $qp['utm_source'];
                    if (empty($session['utm_medium']) && !empty($qp['utm_medium'])) $session['utm_medium'] = $qp['utm_medium'];
                    if (empty($session['utm_campaign']) && !empty($qp['utm_campaign'])) $session['utm_campaign'] = $qp['utm_campaign'];
                }

                // Process by event type
                $type = $record['type'];
                
                if ($type === 'pv') {
                    // Page view
                    $post_id = intval($record['post_id']);
                    $session['pages'][] = [
                        'post_id' => $post_id,
                        'time' => $timestamp
                    ];
                    $session['page_count']++;
                    $session['exit_page_id'] = $post_id;
                }
                elseif ($type === 'c') {
                    // Click
                    $session['click_count']++;
                    if (isset($record['meta']) && $record['meta'] === 'dead_click') {
                        $session['dead_click_count']++;
                    }
                }
                elseif ($type === 'rc') {
                    // Rage click
                    $session['rage_click_count']++;
                }
                elseif (strpos($type, 'tv-') === 0) {
                    // Test view: tv-{test_id}
                    $test_id = intval(substr($type, 3));
                    if (!in_array($test_id, $session['tests_seen'])) {
                        $session['tests_seen'][] = $test_id;
                    }
                    // Store variation from meta
                    if (!empty($record['meta'])) {
                        $session['variations_seen'][$test_id] = $record['meta'];
                    }
                }
                elseif (strpos($type, 'tc-') === 0) {
                    // Test conversion: tc-{test_id}
                    $test_id = intval(substr($type, 3));
                    if (!in_array($test_id, $session['tests_converted'])) {
                        $session['tests_converted'][] = $test_id;
                    }
                }
            }
        }
        
        // Calculate durations
        foreach ($sessions as &$session) {
            if ($session['start_time'] && $session['end_time']) {
                $start = strtotime($session['start_time']);
                $end = strtotime($session['end_time']);
                $session['duration_seconds'] = max(0, $end - $start);
            }
        }
        
        return array_values($sessions);
    }

}

// Initialize
new ABST_Session_Replay();
