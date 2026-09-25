<?php

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       http://absplittest.com
 * @since      0.9.1
 *
 * @package    Bt_Ab_Tests
 * @subpackage Bt_Ab_Tests/admin/
 * @version    2.2.0
 */

class ABST_Admin {

  public static $menu_name  = 'AB Split Test Lite';
  public static $page_title = 'AB Split Test Lite Settings';
  public static $page_slug  = 'bt_bb_ab_test';

  public function __construct()
  {
    add_action( 'admin_menu', [$this, 'settings_menu']);  
    add_action( 'network_admin_menu', [$this, 'settings_menu_multisite']);  
    add_action( 'admin_menu', [$this, 'add_insights_submenu']);
    add_action( 'admin_menu', [$this, 'add_pro_feature_submenus']);
    add_action( 'admin_menu', [$this, 'add_settings_shortcut_submenu'],99);
    add_action( 'admin_menu', [$this, 'reorder_experiments_submenu'],1000);
    
    add_action( 'admin_init', [$this, 'save_settings'], 1 );
    
  }


 
  public function save_settings()
  {
    //only for admins
    if(!current_user_can( 'manage_options' ))
      return false;
          
    if ( isset( $_POST['bt-bb-ab-nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['bt-bb-ab-nonce'] ) ), 'bt-bb-ab-nonce' ) )
    {


      // remove transient abst_disable_hosted_ai
      delete_transient('abst_disable_hosted_ai');

      $fathom_api_key = sanitize_text_field(wp_unslash($_POST['fathom_api_key'] ?? ''));
      $webhook_global = sanitize_text_field(wp_unslash($_POST['webhook_global'] ?? ''));
      $abst_notification_emails = sanitize_text_field(wp_unslash($_POST['abst_notification_emails'] ?? ''));

      $selected_post_types = isset($_POST['selected_post_types']) ? array_map('sanitize_text_field', wp_unslash($_POST['selected_post_types'])) : array();
      // update canonical url
      $change_canonicals = (isset($_POST['add_canonical']) && absint(wp_unslash($_POST['add_canonical'])) === 1) ? 1 : 0;
      // Cache clearing stays enabled in lite to avoid stale-cache support issues;
      // only advanced cache controls are PRO.
      $dont_clear_cache = 0;
      // Lite always keeps debug logging available so the Logs screen can be used for support.
      $abst_enable_logging = 1;
      $abst_enable_heatmaps = (isset($_POST['abst_enable_heatmaps']) && absint(wp_unslash($_POST['abst_enable_heatmaps'])) === 1) ? 1 : 0;
      $uuid_length = isset($_POST['uuid_length']) ? intval($_POST['uuid_length']) : 30;
      $wait_for_approval = (isset($_POST['wait_for_approval']) && absint(wp_unslash($_POST['wait_for_approval'])) === 1) ? 1 : 0;
      $heatmap_retention_length = isset($_POST['heatmap_retention_length']) ? min(3, intval($_POST['heatmap_retention_length'])) : 3; // free: max 3 days
      // Lite version ignores premium-only toggles (MAB, agency, AI, webhooks, UUID)
      $abst_agency_mode_enabled = 0;
      $abst_remote_access_enabled = 0;
      $abst_thompson_sampling_enabled = 0;
      $abst_test_ideas_enabled = 0;
      $use_uuid = 0;
      $webhook_global = '';
      $abst_notification_emails = '';
      $abst_server_convert_woo = 0;
      $woo_server_convert_status = array();

      // store the user journey logging preference
      $enable_user_journeys = (isset($_POST['enable_user_journeys']) && absint(wp_unslash($_POST['enable_user_journeys'])) === 1) ? 1 : 0;

      // store session replays preference
      $enable_session_replays = (isset($_POST['enable_session_replays']) && absint(wp_unslash($_POST['enable_session_replays'])) === 1) ? 1 : 0;

      // Lite records heatmaps on every page; the saved page is only the default
      // page shown when the heatmap viewer opens.
      $heatmap_pages = array();
      if (isset($_POST['heatmap_pages'])) {
        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitized immediately below for array and scalar inputs.
        $pages = wp_unslash($_POST['heatmap_pages']);
        if (is_array($pages)) {
          $heatmap_pages = array_map('sanitize_text_field', $pages);
        } elseif (is_string($pages) && !empty($pages)) {
          $heatmap_pages = array(sanitize_text_field($pages));
        }
      }
      // default to homepage if none selected
      if (empty($heatmap_pages)) {
        $homepage_id = get_option('page_on_front') ?: 0;
        if ($homepage_id) {
          $heatmap_pages = array($homepage_id);
        }
      }
      $heatmap_all_pages = 'all'; // Lite: heatmaps free on every page, 3-day retention

      $this->abst_update_admin_setting( 'fathom_api_key', $fathom_api_key );
      $this->abst_update_admin_setting( 'webhook_global', $webhook_global );
      $this->abst_update_admin_setting( 'selected_post_types', $selected_post_types );
      $this->abst_update_admin_setting( 'ab_change_canonicals', $change_canonicals );
      $this->abst_update_admin_setting( 'ab_use_uuid', $use_uuid );
      $this->abst_update_admin_setting( 'ab_uuid_length', $uuid_length );
      $this->abst_update_admin_setting( 'abst_enable_user_journeys', $enable_user_journeys );
      $this->abst_update_admin_setting( 'abst_enable_session_replays', $enable_session_replays );
      $this->abst_update_admin_setting( 'abst_heatmap_pages', $heatmap_pages );
      $this->abst_update_admin_setting( 'abst_heatmap_all_pages', $heatmap_all_pages );
      $this->abst_update_admin_setting( 'ab_dont_clear_cache_on_update', $dont_clear_cache );
      $this->abst_update_admin_setting( 'abst_server_convert_woo', $abst_server_convert_woo );
      $this->abst_update_admin_setting( 'abst_server_convert_woo_status', $woo_server_convert_status );
      $this->abst_update_admin_setting( 'abst_enable_logging', $abst_enable_logging );
      $this->abst_update_admin_setting( 'abst_enable_heatmaps', $abst_enable_heatmaps );
      $this->abst_update_admin_setting( 'abst_notification_emails', $abst_notification_emails );
      $this->abst_update_admin_setting( 'abst_thompson_sampling_enabled', $abst_thompson_sampling_enabled );
      $this->abst_update_admin_setting( 'abst_test_ideas_enabled', $abst_test_ideas_enabled );
      $this->abst_update_admin_setting( 'abst_wait_for_approval', $wait_for_approval );
      $this->abst_update_admin_setting( 'abst_heatmap_retention_length', $heatmap_retention_length );
      $this->abst_update_admin_setting( 'abst_remote_access_enabled', $abst_remote_access_enabled );
      $this->abst_update_admin_setting( 'abst_agency_mode_enabled', $abst_agency_mode_enabled );
      delete_option('all_testable_posts');// refresh it
    }
  }
  
  public function abst_update_admin_setting( $key, $value )
  {
    $network = is_plugin_active_for_network(BT_AB_PLUGIN_FOLDER.'/bt-bb-ab.php');
    // save only network admin settings.
    if( $network && is_network_admin() ) {
      delete_site_option($key);
      return update_site_option($key, $value);
    }
 
    return update_option($key, $value);
  }

  public function settings_menu_multisite()
  {
    if( $this->should_show_license_field() ) {
      add_submenu_page(
        'settings.php',
        self::$page_title,
        self::$menu_name,
        'manage_options',
        self::$page_slug,
        [$this, 'settings_page']
      );
    }
  }

  public function settings_menu()
  {
    add_submenu_page(
      'options-general.php',
      self::$page_title,
      self::$menu_name,
      'manage_options',
      self::$page_slug,
      [$this, 'settings_page'] 
    );
    
  }

  /**
   * Extra shortcut: add Settings link under the ABSplitTest (bt_experiments) menu
   * The canonical settings page remains under Settings -> ABSplitTest.
   */
  public function add_settings_shortcut_submenu()
  {
    add_submenu_page(
      'edit.php?post_type=bt_experiments',
      self::$page_title,
      __( 'Settings', 'ab-split-test-lite' ),
      'manage_options',
      self::$page_slug,
      [$this, 'settings_page']
    );
  }

  public function reorder_experiments_submenu()
  {
    global $submenu;

    $parent_slug = 'edit.php?post_type=bt_experiments';

    if ( empty( $submenu[ $parent_slug ] ) || ! is_array( $submenu[ $parent_slug ] ) ) {
      return;
    }

    foreach ( $submenu[ $parent_slug ] as &$item ) {
      if ( empty( $item[2] ) ) {
        continue;
      }

      if ( $item[2] === $parent_slug ) {
        $item[0] = __( 'All Tests', 'ab-split-test-lite' );
        if ( isset( $item[3] ) ) {
          $item[3] = __( 'All Tests', 'ab-split-test-lite' );
        }
      } elseif ( $item[2] === 'post-new.php?post_type=bt_experiments' ) {
        $item[0] = __( 'New Test', 'ab-split-test-lite' );
        if ( isset( $item[3] ) ) {
          $item[3] = __( 'New Test', 'ab-split-test-lite' );
        }
      } elseif ( $item[2] === 'bt_bb_ab_insights' ) {
        $item[0] = __( 'CRO Hub', 'ab-split-test-lite' );
        if ( isset( $item[3] ) ) {
          $item[3] = __( 'CRO Hub', 'ab-split-test-lite' );
        }
      }
    }
    unset( $item );

    $menu_by_slug = [];
    foreach ( $submenu[ $parent_slug ] as $item ) {
      if ( ! empty( $item[2] ) ) {
        $menu_by_slug[ $item[2] ] = $item;
      }
    }

    // Pro-only upsell screens are interleaved where the real feature lives in
    // Pro, so the sidebar reads the same in both versions.
    $desired_order = [
      'abst-optimization-hub',
      'post-new.php?post_type=bt_experiments',
      $parent_slug,
      'abst-test-ideas',
      'bt_bb_ab_insights',
      'abst-page-analytics',
      'abst-heatmaps',
      'abst-session-replay',
      'abst-audiences',
      'abst-logs',
      self::$page_slug,
    ];

    $reordered = [];

    foreach ( $desired_order as $slug ) {
      if ( isset( $menu_by_slug[ $slug ] ) ) {
        $reordered[] = $menu_by_slug[ $slug ];
        unset( $menu_by_slug[ $slug ] );
      }
    }

    foreach ( $submenu[ $parent_slug ] as $item ) {
      if ( ! empty( $item[2] ) && isset( $menu_by_slug[ $item[2] ] ) ) {
        $reordered[] = $item;
        unset( $menu_by_slug[ $item[2] ] );
      }
    }

    if ( ! empty( $reordered ) ) {
      $submenu[ $parent_slug ] = $reordered;
    }
  }

  public function settings_page()
  {
    global $btab;
    if ( $btab instanceof Bt_Ab_Tests ) {
      $btab->maybe_handle_plugin_version_change();
    }

    echo '<div class="wrap">';
      echo '<h1>' . esc_html( get_admin_page_title() ) . '</h1>'; 

      $data = [];

      $this->view( 'single-site-display', $data );

    echo '</div>';
  }

  public function should_show_settings_field()
  {
    return ((is_multisite() && !is_network_admin()) || (!is_multisite() && current_user_can( 'manage_options' ) ));
  }



  public function should_show_license_field()
  {
    
    return false;
  }

  
  public function should_show_white_label()
  {
    
    return false;
  }

  public static function get_current_settings_url()
  {
    return (is_network_admin())? self::get_network_admin_url() : self::get_admin_url();
  }

  public static function get_admin_url()
  {
    return admin_url( 'options-general.php?page='. self::$page_slug );
  }

  public static function get_network_admin_url()
  {
    return network_admin_url( 'settings.php?page='. self::$page_slug );
  }


  public function view( $file, $data = [] ) 
  {
    // $data available to the included template via the local scope
    include plugin_dir_path( dirname( __FILE__ ) ) .'admin/partials/'. $file .'.php';
  }

  public function get_protocol()
  {
    $abst_https = isset($_SERVER['HTTPS']) ? sanitize_text_field( wp_unslash( $_SERVER['HTTPS'] ) ) : '';
    $abst_forwarded_proto = isset($_SERVER['HTTP_X_FORWARDED_PROTO']) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FORWARDED_PROTO'] ) ) : '';
    if (
        ( $abst_https === 'on' || $abst_https === '1' ) ||
        $abst_forwarded_proto === 'https'
    ) {
      $protocol = 'https://';
    }
    else {
      $protocol = 'http://';
    }   
    
    return $protocol; 
  }

  /**
   * Add Insights submenu under bt_experiments post type menu
   */
  public function add_insights_submenu() {
    add_submenu_page(
      'edit.php?post_type=bt_experiments',
      __('CRO Hub', 'ab-split-test-lite'),
      __('CRO Hub', 'ab-split-test-lite'),
      'edit_posts',
      'bt_bb_ab_insights',
      [$this, 'insights_page']
    );
  }

  /**
   * Render the Insights page
   */
  public function insights_page() {
    include plugin_dir_path( dirname( __FILE__ ) ) . 'admin/partials/insights-page.php';
  }

  /**
   * Pro-only screens, registered in Lite as upsell pages.
   *
   * Without these the sidebar simply has no entry, so a Lite user never learns
   * the feature exists. Each renders the shared pro-feature partial.
   */
  public static function pro_feature_pages() {
    return array(
      'abst-optimization-hub' => array(
        'menu'  => __( 'Optimization Hub', 'ab-split-test-lite' ),
        'title' => __( 'Optimization Hub', 'ab-split-test-lite' ),
        'intro' => __( 'One dashboard for the whole optimization programme - every test, every learning, every site, scored and ranked in one place.', 'ab-split-test-lite' ),
        'points' => array(
          __( 'See every running and completed test across your site at a glance', 'ab-split-test-lite' ),
          __( 'Keep a searchable record of what won, what lost, and why', 'ab-split-test-lite' ),
          __( 'Track cumulative uplift over time instead of test by test', 'ab-split-test-lite' ),
        ),
      ),
      'abst-test-ideas' => array(
        'menu'  => __( 'Test Ideas', 'ab-split-test-lite' ),
        'title' => __( 'Test Ideas', 'ab-split-test-lite' ),
        'intro' => __( 'Capture, score and prioritise what to test next - or let the AI analyse your site and suggest improvements for you.', 'ab-split-test-lite' ),
        'points' => array(
          __( 'Brainstorm and rank ideas by expected impact and effort', 'ab-split-test-lite' ),
          __( 'Have the agent analyse your pages, heatmaps and competitors and propose tests', 'ab-split-test-lite' ),
          __( 'Turn any approved idea straight into a running test', 'ab-split-test-lite' ),
        ),
      ),
      'abst-audiences' => array(
        'menu'  => __( 'Audiences', 'ab-split-test-lite' ),
        'title' => __( 'Audiences', 'ab-split-test-lite' ),
        'intro' => __( 'Build reusable visitor segments once, then target any test at them - and break your results down by who converted.', 'ab-split-test-lite' ),
        'points' => array(
          __( 'Segment by referrer, UTM campaign, device, location and returning visitors', 'ab-split-test-lite' ),
          __( 'Reuse the same audience across many tests instead of rebuilding filters', 'ab-split-test-lite' ),
          __( 'Manage audiences over the REST API and from AI assistants via MCP', 'ab-split-test-lite' ),
        ),
      ),
      'abst-page-analytics' => array(
        'menu'  => __( 'Page Analytics', 'ab-split-test-lite' ),
        'title' => __( 'Page Analytics', 'ab-split-test-lite' ),
        'intro' => __( 'Lightweight, privacy-friendly engagement analytics for every page - no third-party script, no cookie banner.', 'ab-split-test-lite' ),
        'points' => array(
          __( 'Visits, active time on page and scroll depth per page', 'ab-split-test-lite' ),
          __( 'Spot the pages losing attention before you decide what to test', 'ab-split-test-lite' ),
          __( 'Query the same data over REST or from an AI assistant', 'ab-split-test-lite' ),
        ),
      ),
    );
  }

  /**
   * Register the Pro-only screens as submenu upsell pages.
   */
  public function add_pro_feature_submenus() {
    foreach ( self::pro_feature_pages() as $slug => $page ) {
      add_submenu_page(
        'edit.php?post_type=bt_experiments',
        $page['title'],
        $page['menu'],
        'edit_posts',
        $slug,
        [$this, 'render_pro_feature_page']
      );
    }
  }

  /**
   * Render whichever Pro upsell screen is being requested.
   */
  public function render_pro_feature_page() {
    // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only screen routing; no state is changed.
    $slug = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '';
    $pages = self::pro_feature_pages();
    if ( ! isset( $pages[ $slug ] ) ) {
      return;
    }
    $abst_pro_page = $pages[ $slug ];
    include plugin_dir_path( dirname( __FILE__ ) ) . 'admin/partials/pro-feature-page.php';
  }

} // end class



function abst_get_main_site_url() {

	// This is the current network's information; 'site' is old terminology.
	global $current_site;
	if ( is_multisite() && $current_site ) {
		$main_site_blog_id = $current_site->blog_id;
		return get_home_url( $main_site_blog_id );
	}

	return home_url();
}
