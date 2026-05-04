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
    const INDEX_TRANSIENT_KEY = 'abst_session_index';
    
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
        add_action('wp_ajax_abst_rebuild_session_index', [$this, 'ajax_rebuild_index']);
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
                'loading' => __('Loading...', 'bt-bb-ab'),
                'noSessions' => __('No sessions found matching your filters.', 'bt-bb-ab'),
                'loadingSession' => __('Loading session data...', 'bt-bb-ab'),
                'pageView' => __('Page View', 'bt-bb-ab'),
                'click' => __('Click', 'bt-bb-ab'),
                'rageClick' => __('Rage Click', 'bt-bb-ab'),
                'conversion' => __('Conversion', 'bt-bb-ab'),
            ]
        ]);
    }

    /**
     * Render the admin page
     */
    public function render_admin_page() {
        // Get available tests for filter dropdown
        $tests = get_posts([
            'post_type' => 'bt_experiments',
            'posts_per_page' => -1,
            'post_status' => ['publish', 'draft', 'complete'],
            'orderby' => 'title',
            'order' => 'ASC'
        ]);

        // Get all pages for filter dropdown
        $pages = get_posts([
            'post_type' => ['page', 'post'],
            'posts_per_page' => 100,
            'orderby' => 'title',
            'order' => 'ASC'
        ]);

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

        $filters = [
            'min_pages' => isset($_POST['min_pages']) ? intval($_POST['min_pages']) : 1,
            'page_id' => isset($_POST['page_id']) ? intval($_POST['page_id']) : 0,
            'test_id' => isset($_POST['test_id']) ? intval($_POST['test_id']) : 0,
            'converted' => isset($_POST['converted']) ? sanitize_text_field($_POST['converted']) : '',
            'has_rage_clicks' => isset($_POST['has_rage_clicks']) && $_POST['has_rage_clicks'] === 'true',
            'device' => isset($_POST['device']) ? sanitize_text_field($_POST['device']) : '',
            'date_from' => isset($_POST['date_from']) ? sanitize_text_field($_POST['date_from']) : '',
            'date_to' => isset($_POST['date_to']) ? sanitize_text_field($_POST['date_to']) : '',
            'referrer' => isset($_POST['referrer']) ? sanitize_text_field($_POST['referrer']) : '',
            'utm_source' => isset($_POST['utm_source']) ? sanitize_text_field($_POST['utm_source']) : '',
            'utm_medium' => isset($_POST['utm_medium']) ? sanitize_text_field($_POST['utm_medium']) : '',
            'utm_campaign' => isset($_POST['utm_campaign']) ? sanitize_text_field($_POST['utm_campaign']) : '',
        ];
        
        $page = isset($_POST['page']) ? max(1, intval($_POST['page'])) : 1;
        
        $index = $this->get_session_index();

        // Collect unique referrer/UTM values from full index (before filtering) for dynamic dropdowns
        $available_referrers = [];
        $available_utm_sources = [];
        $available_utm_mediums = [];
        $available_utm_campaigns = [];
        foreach ($index as $s) {
            if (!empty($s['referrer'])) $available_referrers[$s['referrer']] = ($available_referrers[$s['referrer']] ?? 0) + 1;
            if (!empty($s['utm_source'])) $available_utm_sources[$s['utm_source']] = ($available_utm_sources[$s['utm_source']] ?? 0) + 1;
            if (!empty($s['utm_medium'])) $available_utm_mediums[$s['utm_medium']] = ($available_utm_mediums[$s['utm_medium']] ?? 0) + 1;
            if (!empty($s['utm_campaign'])) $available_utm_campaigns[$s['utm_campaign']] = ($available_utm_campaigns[$s['utm_campaign']] ?? 0) + 1;
        }
        arsort($available_referrers);
        arsort($available_utm_sources);
        arsort($available_utm_mediums);
        arsort($available_utm_campaigns);

        $filtered = $this->filter_sessions($index, $filters);
        
        // Sort by start time descending (most recent first)
        usort($filtered, function($a, $b) {
            return strcmp($b['start_time'], $a['start_time']);
        });
        
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
            'available_filters' => [
                'referrers' => $available_referrers,
                'utm_sources' => $available_utm_sources,
                'utm_mediums' => $available_utm_mediums,
                'utm_campaigns' => $available_utm_campaigns,
            ]
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

        $uuid = isset($_POST['uuid']) ? sanitize_text_field($_POST['uuid']) : '';
        $dates = isset($_POST['dates']) ? array_map('sanitize_text_field', $_POST['dates']) : [];
        
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
     * AJAX: Force rebuild session index
     */
    public function ajax_rebuild_index() {
        check_ajax_referer('abst_session_replay', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        delete_transient(self::INDEX_TRANSIENT_KEY);
        $index = $this->build_session_index();
        
        wp_send_json_success([
            'session_count' => count($index),
            /* translators: %d is the number of sessions */
            'message' => sprintf(__('Index rebuilt with %d sessions', 'bt-bb-ab'), count($index))
        ]);
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
        $retention_days = ab_get_admin_setting('abst_heatmap_retention_length') ?? 30;
        
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
                    $ref_host = parse_url($record['referrer'], PHP_URL_HOST);
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

    /**
     * Filter sessions based on criteria
     */
    private function filter_sessions($sessions, $filters) {
        return array_filter($sessions, function($session) use ($filters) {
            // Min pages filter
            if ($filters['min_pages'] > 1 && $session['page_count'] < $filters['min_pages']) {
                return false;
            }
            
            // Specific page filter
            if ($filters['page_id'] > 0) {
                $page_ids = array_column($session['pages'], 'post_id');
                if (!in_array($filters['page_id'], $page_ids)) {
                    return false;
                }
            }
            
            // Test ID filter (seen)
            if ($filters['test_id'] > 0) {
                if (!in_array($filters['test_id'], $session['tests_seen'])) {
                    return false;
                }
            }
            
            // Conversion filter
            if ($filters['converted'] === 'yes' && empty($session['tests_converted'])) {
                return false;
            }
            if ($filters['converted'] === 'no' && !empty($session['tests_converted'])) {
                return false;
            }
            
            // Rage clicks filter
            if ($filters['has_rage_clicks'] && $session['rage_click_count'] === 0) {
                return false;
            }
            
            // Device filter
            if (!empty($filters['device']) && $session['device'] !== $filters['device']) {
                return false;
            }
            
            // Date range filter
            if (!empty($filters['date_from'])) {
                $from = strtotime($filters['date_from']);
                $session_start = strtotime($session['start_time']);
                if ($session_start < $from) {
                    return false;
                }
            }
            if (!empty($filters['date_to'])) {
                $to = strtotime($filters['date_to'] . ' 23:59:59');
                $session_start = strtotime($session['start_time']);
                if ($session_start > $to) {
                    return false;
                }
            }

            // Referrer filter
            if (!empty($filters['referrer']) && (empty($session['referrer']) || stripos($session['referrer'], $filters['referrer']) === false)) {
                return false;
            }

            // UTM filters
            if (!empty($filters['utm_source']) && strtolower($session['utm_source'] ?? '') !== strtolower($filters['utm_source'])) {
                return false;
            }
            if (!empty($filters['utm_medium']) && strtolower($session['utm_medium'] ?? '') !== strtolower($filters['utm_medium'])) {
                return false;
            }
            if (!empty($filters['utm_campaign']) && strtolower($session['utm_campaign'] ?? '') !== strtolower($filters['utm_campaign'])) {
                return false;
            }
            
            return true;
        });
    }
}

// Initialize
new ABST_Session_Replay();
