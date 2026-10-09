<?php

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       http://absplittest.com
 * @since      0.9.1
 *
 * @package    ABST_Tests
 * @subpackage ABST_Tests/admin/
 * @version    2.2.0
 */

class ABST_Admin {

  public static $menu_name  = 'AB Split Test Lite';
  public static $page_title = 'AB Split Test Lite Settings';
  public static $page_slug  = 'bt_bb_ab_test';

  public function __construct()
  {
    add_action( 'admin_menu', [$this, 'settings_menu']);  
    add_action( 'admin_menu', [$this, 'add_settings_shortcut_submenu'],99);
    add_action( 'admin_menu', [$this, 'reorder_experiments_submenu'],1000);
    
    add_action( 'admin_init', [$this, 'save_settings'], 1 );
    
  }


 
  public function save_settings()
  {
    // Only a settings-form POST from an admin, with a valid nonce, is processed.
    if ( ! isset( $_POST['bt-bb-ab-nonce'] ) ) {
      return false;
    }

    if ( ! current_user_can( 'manage_options' ) ) {
      return false;
    }

    if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['bt-bb-ab-nonce'] ) ), 'bt-bb-ab-nonce' ) ) {
      return false;
    }

    $selected_post_types = isset($_POST['selected_post_types']) ? array_map('sanitize_text_field', wp_unslash($_POST['selected_post_types'])) : array();
    // add the control page as the canonical link on full page test variations
    $change_canonicals = (isset($_POST['add_canonical']) && absint(wp_unslash($_POST['add_canonical'])) === 1) ? 1 : 0;
    // automatic cache clearing on test / post updates (on unless unticked)
    $dont_clear_cache = (isset($_POST['enable_clear_cache']) && absint(wp_unslash($_POST['enable_clear_cache'])) === 1) ? 0 : 1;
    // Debug logging always stays available so the Logs screen can be used for support.
    $abst_enable_logging = 1;
    $abst_enable_heatmaps = (isset($_POST['abst_enable_heatmaps']) && absint(wp_unslash($_POST['abst_enable_heatmaps'])) === 1) ? 1 : 0;
    $wait_for_approval = (isset($_POST['wait_for_approval']) && absint(wp_unslash($_POST['wait_for_approval'])) === 1) ? 1 : 0;
    // heatmap data retention, in days
    $heatmap_retention_length = isset($_POST['heatmap_retention_length']) ? max(1, intval($_POST['heatmap_retention_length'])) : 3;

    // store the user journey logging preference
    $enable_user_journeys = (isset($_POST['enable_user_journeys']) && absint(wp_unslash($_POST['enable_user_journeys'])) === 1) ? 1 : 0;


    // Heatmaps record on every page; the saved page is only the default
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
    $heatmap_all_pages = 'all'; // heatmaps record on every page

    $this->abst_update_admin_setting( 'selected_post_types', $selected_post_types );
    $this->abst_update_admin_setting( 'ab_change_canonicals', $change_canonicals );
    $this->abst_update_admin_setting( 'abst_enable_user_journeys', $enable_user_journeys );
    $this->abst_update_admin_setting( 'abst_heatmap_pages', $heatmap_pages );
    $this->abst_update_admin_setting( 'abst_heatmap_all_pages', $heatmap_all_pages );
    $this->abst_update_admin_setting( 'ab_dont_clear_cache_on_update', $dont_clear_cache );
    $this->abst_update_admin_setting( 'abst_enable_logging', $abst_enable_logging );
    $this->abst_update_admin_setting( 'abst_enable_heatmaps', $abst_enable_heatmaps );
    $this->abst_update_admin_setting( 'abst_wait_for_approval', $wait_for_approval );
    $this->abst_update_admin_setting( 'abst_heatmap_retention_length', $heatmap_retention_length );
    delete_option('abst_all_testable_posts');// refresh it
  }
  
  public function abst_update_admin_setting( $key, $value )
  {
    $network = is_plugin_active_for_network(ABST_PLUGIN_FOLDER.'/bt-bb-ab.php');
    // save only network admin settings.
    if( $network && is_network_admin() ) {
      delete_site_option($key);
      return update_site_option($key, $value);
    }
 
    return update_option($key, $value);
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
   * Extra shortcut: add Settings link under the ABSplitTest (abst_experiments) menu
   * The canonical settings page remains under Settings -> ABSplitTest.
   */
  public function add_settings_shortcut_submenu()
  {
    add_submenu_page(
      'edit.php?post_type=abst_experiments',
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

    $parent_slug = 'edit.php?post_type=abst_experiments';

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
      } elseif ( $item[2] === 'post-new.php?post_type=abst_experiments' ) {
        $item[0] = __( 'New Test', 'ab-split-test-lite' );
        if ( isset( $item[3] ) ) {
          $item[3] = __( 'New Test', 'ab-split-test-lite' );
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

    $desired_order = [
      'post-new.php?post_type=abst_experiments',
      $parent_slug,
      'abst-heatmaps',
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
    global $abst_btab;
    if ( $abst_btab instanceof ABST_Tests ) {
      $abst_btab->maybe_handle_plugin_version_change();
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
