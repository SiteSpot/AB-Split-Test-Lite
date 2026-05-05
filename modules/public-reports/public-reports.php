<?php
/**
 * Public Reports Module
 * 
 * Generates shareable, public-facing reports for A/B tests.
 * No login required - uses secure tokens for access.
 * 
 * @package ABSPLITTEST
 * @since 2.5.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class ABST_Public_Reports {
    
    /**
     * Token length for share URLs
     */
    const TOKEN_LENGTH = 32;
    
    /**
     * Default expiration in days (0 = never expires)
     */
    const DEFAULT_EXPIRATION_DAYS = 120;
    
    /**
     * Constructor
     */
    public function __construct() {
        // Register rewrite rules for public report URLs
        add_action('init', [$this, 'register_rewrite_rules']);
        add_filter('query_vars', [$this, 'add_query_vars']);
        add_action('template_redirect', [$this, 'handle_public_report']);
        
        // AJAX handlers for generating/managing share links
        add_action('wp_ajax_abst_generate_share_link', [$this, 'ajax_generate_share_link']);
        add_action('wp_ajax_abst_revoke_share_link', [$this, 'ajax_revoke_share_link']);
        add_action('wp_ajax_abst_get_share_links', [$this, 'ajax_get_share_links']);
        
        // Check if we need to flush rewrite rules
        add_action('init', [$this, 'maybe_flush_rewrite_rules'], 20);
        add_action('admin_footer', [$this, 'render_admin_footer_script']);
    }
    
    /**
     * Flush rewrite rules if needed (on first run or after update)
     */
    public function maybe_flush_rewrite_rules() {
        if (get_option('abst_public_reports_rewrite_version') !== '1.1') {
            flush_rewrite_rules();
            update_option('abst_public_reports_rewrite_version', '1.1');
        }
    }
    
    /**
     * Register rewrite rules for clean URLs
     */
    public function register_rewrite_rules() {
        add_rewrite_rule(
            '^abst-report/([a-zA-Z0-9]+)/?$',
            'index.php?abst_report_token=$matches[1]',
            'top'
        );
    }
    
    /**
     * Add query vars
     */
    public function add_query_vars($vars) {
        $vars[] = 'abst_report_token';
        return $vars;
    }
    
    /**
     * Handle public report display
     */
    public function handle_public_report() {
        // Check for token in query var (from rewrite rule) or GET parameter (fallback)
        $token = get_query_var('abst_report_token');
        
        // Fallback: check for ?abst_report query parameter
        if (empty($token) && isset($_GET['abst_report'])) {
            $token = sanitize_text_field($_GET['abst_report']);
        }
        
        if (empty($token)) {
            return;
        }
        
        // Validate token and get test data
        $report_data = $this->validate_token($token);
        
        if (is_wp_error($report_data)) {
            $this->render_error_page($report_data->get_error_message());
            exit;
        }

        if (!$this->is_shareable_reports_enabled()) {
            $this->render_error_page(__('Please upgrade to enable shareable reports.', 'ab-split-test-lite'));
            exit;
        }
        
        // Render the public report
        $this->render_public_report($report_data);
        exit;
    }
    
    /**
     * Generate a secure share token
     */
    public function generate_token() {
        return bin2hex(random_bytes(self::TOKEN_LENGTH / 2));
    }
    
    /**
     * Create a share link for a test
     */
    public function create_share_link($test_id, $expiration_days = null) {
        if ($expiration_days === null) {
            $expiration_days = self::DEFAULT_EXPIRATION_DAYS;
        }
        
        $token = $this->generate_token();
        $created = current_time('mysql');
        $expires = $expiration_days > 0 
            ? gmdate('Y-m-d H:i:s', strtotime("+{$expiration_days} days")) 
            : null;
        
        // Get existing share links
        $share_links = get_post_meta($test_id, '_abst_share_links', true);
        if (!is_array($share_links)) {
            $share_links = [];
        }
        
        // Add new share link
        $share_links[$token] = [
            'token' => $token,
            'created' => $created,
            'expires' => $expires,
            'created_by' => get_current_user_id(),
            'views' => 0,
            'last_viewed' => null,
        ];
        
        update_post_meta($test_id, '_abst_share_links', $share_links);
        
        // Return the full URL
        return [
            'token' => $token,
            'url' => $this->get_share_url($token),
            'expires' => $expires,
        ];
    }
    
    /**
     * Get the share URL for a token
     */
    public function get_share_url($token) {
        // Use query parameter format for maximum compatibility
        return home_url('/?abst_report=' . $token);
    }
    
    /**
     * Validate a share token
     */
    public function validate_token($token) {
        global $wpdb;
        
        // Sanitize token
        $token = sanitize_text_field($token);
        
        if (strlen($token) !== self::TOKEN_LENGTH) {
            return new WP_Error('invalid_token', __('Invalid report link.', 'ab-split-test-lite'));
        }
        
        // Find the test with this token
        $meta_key = '_abst_share_links';
        $posts = $wpdb->get_results($wpdb->prepare(
            "SELECT post_id, meta_value FROM {$wpdb->postmeta} WHERE meta_key = %s",
            $meta_key
        ));
        
        foreach ($posts as $post) {
            $share_links = maybe_unserialize($post->meta_value);
            
            if (is_array($share_links) && isset($share_links[$token])) {
                $link_data = $share_links[$token];
                
                // Check expiration
                if (!empty($link_data['expires']) && strtotime($link_data['expires']) < time()) {
                    return new WP_Error('expired', __('This report link has expired.', 'ab-split-test-lite'));
                }
                
                // Update view count
                $share_links[$token]['views']++;
                $share_links[$token]['last_viewed'] = current_time('mysql');
                update_post_meta($post->post_id, $meta_key, $share_links);
                
                // Return test data
                return $this->get_report_data($post->post_id, $link_data);
            }
        }
        
        return new WP_Error('not_found', __('Report not found.', 'ab-split-test-lite'));
    }
    
    /**
     * Get report data for a test
     */
    public function get_report_data($test_id, $link_data) {
        $test = get_post($test_id);
        
        if (!$test || $test->post_type !== 'bt_experiments') {
            return new WP_Error('not_found', __('Test not found.', 'ab-split-test-lite'));
        }
        
        // Get observations
        $observations = get_post_meta($test_id, 'observations', true);
        
        // Run through statistics analyzer
        $test_age = intval((time() - get_post_time('U', true, $test)) / 60 / 60 / 24);
        $conversion_style = get_post_meta($test_id, 'conversion_style', true);
        $conversion_use_order_value = get_post_meta($test_id, 'conversion_use_order_value', true);
        
        if ($conversion_style !== 'thompson' && !empty($observations)) {
            require_once BT_AB_TEST_PLUGIN_PATH . 'includes/statistics.php';
            if ($conversion_use_order_value == '1' && is_array($observations)) {
                // Use Welch's T-Test for revenue/order value data (continuous values)
                $observations = bt_bb_ab_revenue_analyzer($observations, $test_age);
                $observations = bt_bb_ab_analyze_device_sizes($observations, $test_age, true);
            } else {
                // Use Bayesian analyzer for binary conversion data
                $observations = bt_bb_ab_split_test_analyzer($observations, $test_age);
                $observations = bt_bb_ab_analyze_device_sizes($observations, $test_age, false);
            }
        }
        
        // Get variation meta
        $variation_meta = get_post_meta($test_id, 'variation_meta', true);
        
        // Get test configuration
        $test_type = get_post_meta($test_id, 'test_type', true);
        
        // Get page URL for heatmaps
        $page_url = '';
        $conversion_page = get_post_meta($test_id, 'conversion_page', true);
        if (!empty($conversion_page)) {
            $page_url = get_permalink($conversion_page);
        }
        // For full page tests, get the default page
        if (empty($page_url) && $test_type === 'full_page') {
            $default_page = get_post_meta($test_id, 'bt_experiments_full_page_default_page', true);
            if (!empty($default_page)) {
                $page_url = get_permalink($default_page);
            }
        }
        // Fallback to site URL
        if (empty($page_url)) {
            $page_url = home_url('/');
        }
        $conversion_use_order_value = get_post_meta($test_id, 'conversion_use_order_value', true);
        $test_winner = get_post_meta($test_id, 'test_winner', true);
        $goals = get_post_meta($test_id, 'goals', true);
        $magic_definition = get_post_meta($test_id, 'magic_definition', true);
        
        // Calculate totals
        $total_visits = 0;
        $total_conversions = 0;
        $variations = [];
        
        if (is_array($observations)) {
            foreach ($observations as $key => $data) {
                if ($key === 'bt_bb_ab_stats') continue;
                
                $total_visits += $data['visit'] ?? 0;
                $total_conversions += $data['conversion'] ?? 0;
                
                // Get variation label
                $label = abst_get_variation_label($key, $variation_meta);
                
                $variations[$key] = [
                    'key' => $key,
                    'label' => $label,
                    'visits' => $data['visit'] ?? 0,
                    'conversions' => $data['conversion'] ?? 0,
                    'rate' => $data['rate'] ?? 0,
                    'probability' => $data['probability'] ?? 0,
                    'weight' => isset($variation_meta[$key]['weight']) ? floatval($variation_meta[$key]['weight']) * 100 : 0,
                    'goals' => $data['goals'] ?? [],
                ];
            }
        }
        
        // Sort by rate descending
        uasort($variations, function($a, $b) {
            return $b['rate'] <=> $a['rate'];
        });
        
        // Get statistical data
        $stats = $observations['bt_bb_ab_stats'] ?? [];
        $likely_duration = $stats['likelyDuration'] ?? 0;
        $best_variation = $stats['best'] ?? '';
        $best_probability = $stats['probability'] ?? 0;
        
        // Calculate time remaining
        $time_remaining = max(0, $likely_duration - $test_age);
        
        // Get site name
        $site_name = get_bloginfo('name');
        
        // Generate experiment status/analysis only (not full admin interface)
        $bt_ab_test = new Bt_Ab_Tests();
        $experiment_status = $bt_ab_test->get_experiment_status_only($test);
                
        return [
            'test_id' => $test_id,
            'test_name' => $test->post_title,
            'test_status' => $test->post_status,
            'test_type' => $test_type,
            'test_age' => $test_age,
            'created_date' => get_post_time('Y-m-d', false, $test),
            'conversion_style' => $conversion_style,
            'conversion_use_order_value' => $conversion_use_order_value,
            'test_winner' => $test_winner,
            'total_visits' => $total_visits,
            'total_conversions' => $total_conversions,
            'overall_rate' => $total_visits > 0 ? round(($total_conversions / $total_visits) * 100, 2) : 0,
            'variations' => $variations,
            'variation_count' => count($variations),
            'likely_duration' => $likely_duration,
            'time_remaining' => $time_remaining,
            'best_variation' => $best_variation,
            'best_probability' => $best_probability,
            'goals' => $goals,
            'magic_definition' => $magic_definition,
            'link_data' => $link_data,
            'site_name' => $site_name,
            'site_url' => home_url(),
            'page_url' => $page_url,
            'variation_meta' => $variation_meta,
            'observations_raw' => $observations,
            'likely_duration' => $likely_duration,
            'experiment_status' => $experiment_status,
        ];
    }
    
    /**
     * Render the public report page
     */
    public function render_public_report($data) {
        // Set headers
        header('Content-Type: text/html; charset=utf-8');
        header('X-Robots-Tag: noindex, nofollow');

        wp_enqueue_script(
            'abst-public-report-chart',
            BT_AB_TEST_PLUGIN_URI . 'js/chart.js',
            array(),
            BT_AB_TEST_VERSION,
            false
        );
        
        // Include the template
        include BT_AB_TEST_PLUGIN_PATH . 'modules/public-reports/templates/report-template.php';
    }
    
    /**
     * Render error page
     */
    public function render_error_page($message) {
        header('Content-Type: text/html; charset=utf-8');
        header('X-Robots-Tag: noindex, nofollow');
        
        include BT_AB_TEST_PLUGIN_PATH . 'modules/public-reports/templates/error-template.php';
    }
    
    /**
     * AJAX: Generate share link
     */
    public function ajax_generate_share_link() {
        check_ajax_referer('abst_share_nonce', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(['message' => __('Permission denied.', 'ab-split-test-lite')]);
        }
        
        $test_id = intval($_POST['test_id'] ?? 0);
        $expiration_days = intval($_POST['expiration_days'] ?? self::DEFAULT_EXPIRATION_DAYS);
        
        if (!$test_id) {
            wp_send_json_error(['message' => __('Invalid test ID.', 'ab-split-test-lite')]);
        }
        
        $result = $this->create_share_link($test_id, $expiration_days);
        
        wp_send_json_success($result);
    }
    
    /**
     * AJAX: Revoke share link
     */
    public function ajax_revoke_share_link() {
        check_ajax_referer('abst_share_nonce', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(['message' => __('Permission denied.', 'ab-split-test-lite')]);
        }
        
        $test_id = intval($_POST['test_id'] ?? 0);
        $token = sanitize_text_field($_POST['token'] ?? '');
        
        if (!$test_id || !$token) {
            wp_send_json_error(['message' => __('Invalid parameters.', 'ab-split-test-lite')]);
        }
        
        $share_links = get_post_meta($test_id, '_abst_share_links', true);
        
        if (is_array($share_links) && isset($share_links[$token])) {
            unset($share_links[$token]);
            update_post_meta($test_id, '_abst_share_links', $share_links);
            wp_send_json_success(['message' => __('Share link revoked.', 'ab-split-test-lite')]);
        }
        
        wp_send_json_error(['message' => __('Share link not found.', 'ab-split-test-lite')]);
    }
    
    /**
     * AJAX: Get share links for a test
     */
    public function ajax_get_share_links() {
        check_ajax_referer('abst_share_nonce', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(['message' => __('Permission denied.', 'ab-split-test-lite')]);
        }
        
        $test_id = intval($_POST['test_id'] ?? 0);
        
        if (!$test_id) {
            wp_send_json_error(['message' => __('Invalid test ID.', 'ab-split-test-lite')]);
        }
        
        $share_links = get_post_meta($test_id, '_abst_share_links', true);
        
        if (!is_array($share_links)) {
            $share_links = [];
        }
        
        // Add URLs to each link
        foreach ($share_links as $token => &$link) {
            $link['url'] = $this->get_share_url($token);
            $link['is_expired'] = !empty($link['expires']) && strtotime($link['expires']) < time();
        }
        
        wp_send_json_success(['links' => array_values($share_links)]);
    }
    
    /**
     * Get or create a share link for a test (simplified - one link per test)
     */
    public function get_or_create_share_link($test_id) {
        $share_links = get_post_meta($test_id, '_abst_share_links', true);
        
        // If we have an existing non-expired link, use it
        if (is_array($share_links) && !empty($share_links)) {
            foreach ($share_links as $token => $link_data) {
                // Check if not expired
                if (empty($link_data['expires']) || strtotime($link_data['expires']) > time()) {
                    return [
                        'token' => $token,
                        'url' => $this->get_share_url($token),
                        'expires' => $link_data['expires'] ?? null,
                    ];
                }
            }
        }
        
        // No valid link exists, create one (never expires for simplicity)
        return $this->create_share_link($test_id, 0);
    }

    public function is_shareable_reports_enabled() {

        return false;
        

    }
    
    /**
     * Render share button on test results page
     * Now adds a Share Report button to the sidebar navigation
     */
    public function get_share_button_html($test_id) {
        ob_start();
        $share_data = $this->get_or_create_share_link($test_id);
        $url = $share_data['url'];
        ?>
        <div id="share-report" class="abst-results-panel abst-data-panel">
            <h3>Share Test Report</h3>
            <p>Share this URL with anyone to let them view the test results without requiring a WordPress login.</p>
            <div style="display: flex; align-items: center; gap: 10px; margin-top: 15px;">
                <input type="text" value="<?php echo esc_url($url); ?>" readonly onclick="this.select(); document.execCommand('copy');" 
                    style="flex: 1; font-size: 13px; padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 4px; background: #fff; cursor: pointer;" 
                    title="Click to copy">
                <a href="<?php echo esc_url($url); ?>" target="_blank" class="share-button button button-secondary">
                    <span class="dashicons dashicons-external" style="font-size: 16px; height: 16px; width: 16px; margin-right: 4px; margin-top: 3px;"></span>
                    <?php esc_html_e('Open', 'ab-split-test-lite'); ?>
                </a>
            </div>
            <p class="description" style="margin-top: 10px;">
                This link never expires. 
                <button type="button" class="button button-link abst-regenerate-share-link" data-test-id="<?php echo esc_attr($test_id); ?>" data-token="<?php echo esc_attr($share_data['token']); ?>" style="padding: 4px 9px; vertical-align: baseline;">
                    Regenerate link
                </button>
            </p>
        </div>
        <?php
        return ob_get_clean();
    }

    public function render_share_button($test_id) {
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Output is already escaped in get_share_button_html
        echo $this->get_share_button_html($test_id); // Output is already escaped in get_share_button_html
    }

    public function render_admin_footer_script() {
        ?>
        <script>
        jQuery(document).ready(function($) {
            $(document).on('click', '.abst-regenerate-share-link', function(e) {
                e.preventDefault();
                
                if (!confirm('Are you sure you want to regenerate the share link?\n\nThe current URL will stop working immediately and anyone with the old link will no longer be able to access the report.')) {
                    return;
                }
                
                var $button = $(this);
                var testId = $button.data('test-id');
                var oldToken = $button.data('token');
                
                $button.prop('disabled', true).text('Regenerating...');
                
                $.post(ajaxurl, {
                    action: 'abst_revoke_share_link',
                    nonce: '<?php echo esc_attr(wp_create_nonce('abst_share_nonce')); ?>',
                    test_id: testId,
                    token: oldToken
                }).done(function() {
                    $.post(ajaxurl, {
                        action: 'abst_generate_share_link',
                        nonce: '<?php echo esc_attr(wp_create_nonce('abst_share_nonce')); ?>',
                        test_id: testId,
                        expiration_days: 0
                    }).done(function(response) {
                        if (response.success && response.data.url) {
                            $('#share-report input[type="text"]').val(response.data.url);
                            $('#share-report a.share-button').attr('href', response.data.url);
                            $button.data('token', response.data.token);
                            alert('Share link regenerated successfully!');
                        } else {
                            alert('Error generating new link. Please refresh the page and try again.');
                        }
                    }).fail(function() {
                        alert('Error generating new link. Please refresh the page and try again.');
                    }).always(function() {
                        $button.prop('disabled', false).text('Regenerate link');
                    });
                }).fail(function() {
                    alert('Error revoking old link. Please try again.');
                    $button.prop('disabled', false).text('Regenerate link');
                });
            });
        });
        </script>
        <?php
    }
}

// Initialize the module
$GLOBALS['abst_public_reports'] = new ABST_Public_Reports();
