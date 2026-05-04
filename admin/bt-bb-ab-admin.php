<?php

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

class BT_BB_AB_Admin {

  public static $menu_name  = 'AB Split Test Lite';
  public static $page_title = 'AB Split Test Lite' . ' Settings';
  public static $page_slug  = 'bt_bb_ab_test';

  public function __construct()
  {
    add_action( 'admin_menu', [$this, 'settings_menu']);  
    add_action( 'network_admin_menu', [$this, 'settings_menu_multisite']);  
    add_action( 'admin_menu', [$this, 'add_insights_submenu']);
    add_action( 'admin_menu', [$this, 'add_settings_shortcut_submenu'],99);
    add_action( 'admin_menu', [$this, 'reorder_experiments_submenu'],1000);
    
  
    add_action( 'admin_notices', [$this, 'admin_notice'] );  
    add_action( 'admin_notices', [$this, 'license_admin_notices'] );  
    add_action( 'network_admin_notices', [$this, 'admin_notice'] );  
    add_action( 'network_admin_notices', [$this, 'license_admin_notices'] );  

    add_action( 'admin_init', [$this, 'save_settings'], 1 );
    add_action( 'admin_init', [$this, 'activate_license'], 20 );
    add_action( 'admin_init', [$this, 'deactivate_license'], 20 );
    
    // Check license status on admin init using a transient
    add_action( 'admin_init', [$this, 'maybe_check_license_status'] );
  }


 
  public function save_settings()
  {
    //only for admins
    if(!current_user_can( 'manage_options' ))
      return false;
          
    if ( isset( $_POST['bt-bb-ab-nonce'] ) && wp_verify_nonce( $_POST['bt-bb-ab-nonce'], 'bt-bb-ab-nonce' ) ) 
    {


      // remove transient abst_disable_hosted_ai
      delete_transient('abst_disable_hosted_ai');
      
      if( isset($_POST['bt_bb_ab_licence']) ) {
        $bt_bb_ab_licence = sanitize_text_field(wp_unslash($_POST['bt_bb_ab_licence'] ?? ''));
        update_admin_setting( 'bt_bb_ab_licence', $bt_bb_ab_licence );
      }

      $fathom_api_key = sanitize_text_field(wp_unslash($_POST['fathom_api_key'] ?? ''));
      $webhook_global = sanitize_text_field(wp_unslash($_POST['webhook_global'] ?? ''));
      $abst_notification_emails = sanitize_text_field(wp_unslash($_POST['abst_notification_emails'] ?? ''));
      $ab_openapi_key = sanitize_text_field(wp_unslash($_POST['ab_openapi_key'] ?? ''));

      $selected_post_types = isset($_POST['selected_post_types']) ? array_map('sanitize_text_field', $_POST['selected_post_types']) : array();
      // update canonical url
      $change_canonicals = (isset($_POST['add_canonical']) && $_POST['add_canonical'] == 1) ? 1 : 0;
      // Cache clearing stays enabled in lite to avoid stale-cache support issues;
      // only advanced cache controls are PRO.
      $dont_clear_cache = 0;
      $use_fingerprint = (isset($_POST['use_fingerprint']) && $_POST['use_fingerprint'] == 1) ? 1 : 0;
      $abst_server_convert_woo = (isset($_POST['abst_server_convert_woo']) && $_POST['abst_server_convert_woo'] == 1) ? 1 : 0;
      $abst_enable_logging = (isset($_POST['abst_enable_logging']) && $_POST['abst_enable_logging'] == 1) ? 1 : 0;
      $abst_enable_heatmaps = (isset($_POST['abst_enable_heatmaps']) && $_POST['abst_enable_heatmaps'] == 1) ? 1 : 0;
      $ab_fingerprint_length = isset($_POST['fingerprint_length']) ? intval($_POST['fingerprint_length']) : 30; // days default 30 
      $uuid_length = isset($_POST['uuid_length']) ? intval($_POST['uuid_length']) : 30; // days default 30 
      $wait_for_approval = (isset($_POST['wait_for_approval']) && $_POST['wait_for_approval'] == 1) ? 1 : 0;
      $heatmap_retention_length = isset($_POST['heatmap_retention_length']) ? min(3, intval($_POST['heatmap_retention_length'])) : 3; // free: max 3 days
      // Lite version ignores premium-only toggles (MAB, agency, weekly reports, AI, webhooks, fingerprint, UUID)
      $abst_agency_mode_enabled = 0;
      $abst_remote_access_enabled = 0;
      $abst_disable_ai = 1;
      $abst_send_weekly_reports = 0;
      $abst_weekly_report_emails = '';
      $abst_thompson_sampling_enabled = 0;
      $abst_test_ideas_enabled = 0;
      $use_fingerprint = 0;
      $use_uuid = 0;
      $ab_openapi_key = '';
      $webhook_global = '';
      $abst_notification_emails = '';
      $abst_server_convert_woo = 0;
      $woo_server_convert_status = array();

      // store the user journey logging preference
      $enable_user_journeys = (isset($_POST['enable_user_journeys']) && $_POST['enable_user_journeys'] == 1) ? 1 : 0;

      // store session replays preference
      $enable_session_replays = (isset($_POST['enable_session_replays']) && $_POST['enable_session_replays'] == 1) ? 1 : 0;

      // store heatmap pages selection (free: max 1 page)
      $heatmap_pages = array();
      if (isset($_POST['heatmap_pages'])) {
        $pages = $_POST['heatmap_pages'];
        if (is_array($pages)) {
          $heatmap_pages = array_slice(array_map('sanitize_text_field', $pages), 0, 1);
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
      $heatmap_all_pages = 'chosen'; // free always uses chosen pages (max 1)

      update_admin_setting('abst_disable_ai', $abst_disable_ai, false);
      update_admin_setting( 'fathom_api_key', $fathom_api_key, false );
      update_admin_setting( 'webhook_global', $webhook_global, false );
      update_admin_setting( 'ab_openapi_key', $ab_openapi_key, false );
      update_admin_setting( 'ab_openapi_model', 'gpt-4o', false );
      update_admin_setting( 'selected_post_types', $selected_post_types, false );
      update_admin_setting( 'ab_change_canonicals', $change_canonicals, false );
      update_admin_setting( 'ab_use_fingerprint', $use_fingerprint, false );
      update_admin_setting( 'ab_fingerprint_length', $ab_fingerprint_length );
      update_admin_setting( 'ab_use_uuid', $use_uuid, false );
      update_admin_setting( 'ab_uuid_length', $uuid_length );
      update_admin_setting( 'abst_enable_user_journeys', $enable_user_journeys, false );
      update_admin_setting( 'abst_enable_session_replays', $enable_session_replays, false );
      update_admin_setting( 'abst_heatmap_pages', $heatmap_pages, false );
      update_admin_setting( 'abst_heatmap_all_pages', $heatmap_all_pages, false );
      update_admin_setting( 'ab_dont_clear_cache_on_update', $dont_clear_cache, false );
      update_admin_setting( 'abst_server_convert_woo', $abst_server_convert_woo, false );
      update_admin_setting( 'abst_server_convert_woo_status', $woo_server_convert_status, false );
      update_admin_setting( 'abst_enable_logging', $abst_enable_logging, false );
      update_admin_setting( 'abst_enable_heatmaps', $abst_enable_heatmaps, false );
      update_admin_setting( 'abst_send_weekly_reports', $abst_send_weekly_reports, false );
      update_admin_setting( 'abst_weekly_report_emails', $abst_weekly_report_emails, false );
      update_admin_setting( 'abst_notification_emails', $abst_notification_emails, false );
      update_admin_setting( 'abst_thompson_sampling_enabled', $abst_thompson_sampling_enabled, false );
      update_admin_setting( 'abst_test_ideas_enabled', $abst_test_ideas_enabled, false );
      update_admin_setting( 'abst_wait_for_approval', $wait_for_approval, false );
      update_admin_setting( 'abst_heatmap_retention_length', $heatmap_retention_length, false );
      update_admin_setting( 'abst_remote_access_enabled', $abst_remote_access_enabled, false );
      update_admin_setting( 'abst_agency_mode_enabled', $abst_agency_mode_enabled, false );
      //check db exists
      if($use_fingerprint || $use_uuid)
      {
        self::initialize_fingerprint_database();
        if (!wp_next_scheduled('fingerprint_cleanup_event')) {
          abst_log('scheduling fingerprint cleanup nightly');
          // Schedule the event to run daily at midnight
          wp_schedule_event(time(), 'daily', 'fingerprint_cleanup_event');
        }
      }

      delete_option('all_testable_posts');// refresh it
    }
  }


  public static function initialize_fingerprint_database() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'abst_fingerprints';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `uuid` varchar(255) NOT NULL,
        `type` varchar(255) NOT NULL,
        `variation` varchar(255) NOT NULL,
        `testId` varchar(255) NOT NULL,
        `location` varchar(255) NOT NULL,
        `size` varchar(255) NOT NULL,
        `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        `goals` TEXT NULL,
        PRIMARY KEY (`id`),
        KEY `idx_uuid` (`uuid`),
        KEY `idx_testId` (`testId`),
        KEY `idx_variation` (`variation`)
      ) $charset_collate;";

    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $sql );

    update_option('abst_fingerprint_table_ready', 2);
    abst_log('fingerprint table checked/updated');
  }
  
  public function update_admin_setting( $key, $value )
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
        'administrator',
        self::$page_slug,
        [$this, 'settings_page'] 
      );  
    }        
  }

  public function settings_menu() 
  {
    if( true)//;is_multisite() )
    {
      add_submenu_page(
        'options-general.php',
        self::$page_title,
        self::$menu_name,
        'administrator',
        self::$page_slug,
        [$this, 'settings_page'] 
      );
    }
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

    $desired_order = [
      'post-new.php?post_type=bt_experiments',
      $parent_slug,
      'abst-test-ideas',
      'bt_bb_ab_insights',
      'abst-heatmaps',
      'abst-session-replay',
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
    $send_weekly = ab_get_admin_setting( 'abst_send_weekly_reports' );
    if ( $send_weekly === '' || $send_weekly === false ) {
      $send_weekly = 1;
      $this->update_admin_setting( 'abst_send_weekly_reports', 1 );
    }
    if ( $send_weekly == 1 && function_exists( 'abst_schedule_weekly_report_cron' ) ) {
      abst_schedule_weekly_report_cron();
    }

    echo '<div class="wrap">';
      echo '<h1>' . esc_html( get_admin_page_title() ) . '</h1>'; 

      $data = $this->get_page_data();

      $this->view( 'single-site-display', $data );

    echo '</div>';
  }

  public function get_page_data()
  {
    $license = bt_bb_ab_licence_details();

    $data = [
      'license_key'     => apply_filters( 'bb_bt_ab_licence_key',ab_get_admin_setting( 'bt_bb_ab_licence')),
      'license_status'  => $license->active,
      'user_level'   => $license->user_level,
      'price_id'      => $license->price_id
      
    ];  

    return $data;  
  }



  public function check_license( $license )
  {
      $item_id = BT_AB_TEST_ITEM_ID;
      $api_params = array(
        'edd_action' => 'check_license',
        'license'    => $license,
        'item_id'    => $item_id, // the name of our product in EDD
        'url'        => home_url(),
        'environment' => function_exists( 'wp_get_environment_type' ) ? wp_get_environment_type() : 'production',
      );    

      $response = wp_remote_post( BT_BB_AB_EDD_SL_STORE_URL, array( 'timeout' => 15, 'sslverify' => false, 'body' => $api_params ) );

      return $response;
  }

  public function activate_license() 
  {
    // listen for our activate button to be clicked
    if( isset( $_POST['bt_license_activate'] ) ) {

      // run a quick security check
      if( ! check_admin_referer( 'bt_activate_license_nonce', 'bt_activate_license_nonce' ) )
        return; // get out if we didn't click the Activate button

      // remove transient abst_disable_hosted_ai
      delete_transient('abst_disable_hosted_ai');

      // If license key was provided in POST, save it first before activating
      if( isset($_POST['bt_bb_ab_licence']) ) {
        $bt_bb_ab_licence = sanitize_text_field(wp_unslash($_POST['bt_bb_ab_licence'] ?? ''));
        update_admin_setting( 'bt_bb_ab_licence', $bt_bb_ab_licence );
      }

      $this->send_licence_activate();
      //wp_redirect( self::get_current_settings_url() );
      //exit();
    }
  }  
  public function send_licence_activate($shouldredirect = true){
    // retrieve the license from the database (or POST if just updated)
    $license = trim(apply_filters( 'bb_bt_ab_licence_key', ab_get_admin_setting( 'bt_bb_ab_licence' )) );
    
    // Validate we have a license key
    if(empty($license)) {
      abst_log('License activation failed: No license key provided');
      return false;
    }
    $item_id = BT_AB_TEST_ITEM_ID;
    $base_url = self::get_current_settings_url();

    // data to send in our API request
    $api_params = array(
      'edd_action' => 'activate_license',
      'license'    => $license,
      'item_id'    => $item_id,
      'url'        => home_url(),
      'environment' => function_exists( 'wp_get_environment_type' ) ? wp_get_environment_type() : 'production',
    );

    // Call the custom API.
    $response = wp_remote_post( BT_BB_AB_EDD_SL_STORE_URL, array( 'timeout' => 15, 'sslverify' => false, 'body' => $api_params ) );
    // make sure the response came back okay
    $message = $this->get_activation_messages( $response );

    // Check if anything passed on a message constituting a failure
    if ( ! empty( $message ) && $shouldredirect ) {
      $redirect = add_query_arg( array( 'sl_activation' => 'false', 'message' => urlencode( $message ) ), $base_url );

      wp_redirect( $redirect );
      exit();
    }

    // $license_data->license will be either "valid" or "invalid"
    $license_data = json_decode( wp_remote_retrieve_body( $response ) );
    // Validate license data before using it
    if ( empty( $license_data ) || ! is_object( $license_data ) ) {
      abst_log( 'License activation: Invalid license data object after successful response' );
      if ( $shouldredirect ) {
        $redirect = add_query_arg( array( 'sl_activation' => 'false', 'message' => urlencode( __( 'Invalid license data received.', 'ab-split-test-lite' ) ) ), $base_url );
        wp_redirect( $redirect );
        exit();
      }
      return;
    }
    
    //if its upgrade from f2p 
    if( isset( $license_data->license ) && $license_data->license == 'valid' ){
      if(isset($license_data->price_id) && $license_data->price_id !== '11'){
        if(file_exists(BT_AB_TEST_PLUGIN_PATH . 'includes/config.php'))
            wp_delete_file( BT_AB_TEST_PLUGIN_PATH . 'includes/config.php' );
      }
    }

    $this->update_license_status( $license_data );
  }

  /**
   * Check license status once daily using a transient
   */
  public function maybe_check_license_status() {
    // Only run for editors
    $monthly_levels = ['26','25','10'];
    $saved_license_data = bt_bb_ab_licence_details();
    $saved_price_id = isset($saved_license_data->price_id) ? $saved_license_data->price_id : 0;
    if (!current_user_can('manage_options')) {
      return;
    }
    
    // Check if we've done a license check in the last 24 hours
    $last_checked = get_transient('bt_bb_ab_license_last_checked');
    
    if (false === $last_checked) {
      // Check if this is an annual license (not in monthly levels)
      if(!in_array($saved_price_id, $monthly_levels)){
        abst_log('License level is annual check in 7 days');
        set_transient('bt_bb_ab_license_last_checked', time(), DAY_IN_SECONDS * 7);
        return;
      }

      // Process monthly licenses
      $license = trim(apply_filters('bb_bt_ab_licence_key', ab_get_admin_setting('bt_bb_ab_licence')));
      
      if (!empty($license)) {
        $response = $this->check_license($license);
        
        if (!is_wp_error($response) && 200 === wp_remote_retrieve_response_code($response)) {
          $license_data = json_decode(wp_remote_retrieve_body($response));
          abst_log('License check response');
          abst_log($license_data);
          // Only update if we got valid license data
          if (!empty($license_data) && isset($license_data->license)) {
            $this->update_license_status($license_data);
          }
        }
      }
      
      // Set 1-day transient for monthly licenses
      set_transient('bt_bb_ab_license_last_checked', time(), DAY_IN_SECONDS);
    }
  }

  public function update_license_status( $license_data )
  {
    // Validate license data object
    if ( empty( $license_data ) || ! is_object( $license_data ) ) {
      abst_log( 'update_license_status: Invalid license data object provided' );
      return;
    }
    
    $is_multisite = is_multisite();
    $license_limit = (isset($license_data->license_limit))? $license_data->license_limit : 0;
    
    if(empty($license_limit))
      $all_licence_data = wp_remote_retrieve_body($this->check_license(ab_get_admin_setting('bt_bb_ab_licence')));
    if(!empty($all_licence_data)){
      $all_licence_data = json_decode($all_licence_data);
      $license_limit = isset($all_licence_data->license_limit) ? $all_licence_data->license_limit : 0;
    }
    $price_id = isset($license_data->price_id) ? $license_data->price_id : 0;

    if(in_array($price_id,['1','2','6','13','14','21']))
      $user_level = "pro";
    else if(in_array($price_id,['11']))
      $user_level = "free";
    else
      $user_level = "agency";

    $monthly_levels = ['26','25','10'];


    // If it's a monthly plan and the license is not valid, downgrade to free
    if(in_array($price_id, $monthly_levels) && isset($license_data->license) && $license_data->license !== 'valid') {
      $user_level = 'free';

    }
    
    $status = [
      'active'    => isset($license_data->license) ? $license_data->license : 'invalid',
      'sites'     => $license_limit,
      'multisite' => $is_multisite,
      'user_level' => $user_level,
      'price_id' => $price_id
    ];
    
    update_admin_setting( 'bt_bb_ab_lic', $status );
  }

  public function get_activation_messages( $response )
  {
    $message = '';

    if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {

      if ( is_wp_error( $response ) ) {
        $message = $response->get_error_message();
      } else {
        $message = __( 'An error occurred, please try again.', 'ab-split-test-lite' );
      }

    } else {

      $license_data = json_decode( wp_remote_retrieve_body( $response ) );
      
      // Check if JSON decode was successful and we have valid data
      if ( empty( $license_data ) || ! is_object( $license_data ) ) {
        abst_log( 'License activation failed: Invalid JSON response' );
        abst_log( wp_remote_retrieve_body( $response ) );
        $message = __( 'An error occurred, please try again. Invalid response from license server.' , 'ab-split-test-lite' );
        return $message;
      }
      
      // Check if success property exists
      if ( ! isset( $license_data->success ) ) {
        abst_log( 'License activation failed: Missing success property in response' );
        abst_log( $license_data );
        $message = __( 'An error occurred, please try again. Unexpected response format.' , 'ab-split-test-lite' );
        return $message;
      }

      //iof licence data licence invalid license  [2025-10-22 13:42:40] {"item_id":9487,"item_name":"AB Split Test","license":"invalid","success":false}

      if($license_data->license == 'invalid'){
        abst_log('License activation failed: Invalid license key');
        $message = __( 'Invalid license key. Please check and try again.', 'ab-split-test-lite' );
        return $message;
      }
      
      if ( false === $license_data->success ) {

        abst_log( 'License activation failed with error: ' . ( isset( $license_data->error ) ? $license_data->error : 'unknown' ) );

        switch( isset( $license_data->error ) ? $license_data->error : '' ) {


          case 'expired' :

            /* translators: %s is the expiration date */
            $message = sprintf(
              __( 'Your license key expired on %s.', 'ab-split-test-lite' ),
              date_i18n( get_option( 'date_format' ), strtotime( $license_data->expires, current_time( 'timestamp' ) ) )
            );
            break;

          case 'disabled' :
          case 'revoked' :

            $message = __( 'Your license key has been disabled.', 'ab-split-test-lite' );
            break;

          case 'missing' :

            $message = __( 'Invalid license.', 'ab-split-test-lite' );
            break;

          case 'invalid' :
          case 'site_inactive' :

            $message = __( 'Your license is not active for this URL.', 'ab-split-test-lite' );
            break;

          case 'item_name_mismatch' :

            /* translators: %s is the product name */
            $message = sprintf( __( 'This appears to be an invalid license key for %s.' , 'ab-split-test-lite' ), BT_BB_AB_EDD_ITEM_NAME );
            break;

          case 'no_activations_left':

            $message = __( 'Your license key has reached its activation limit.', 'ab-split-test-lite' );
            break;

          default :

            $message = __( 'An error occurred, please try again.', 'ab-split-test-lite' );
            break;
        }        
      }
    }        

    return $message;
  }


  public function deactivate_license() 
  {
    // listen for our activate button to be clicked
    if( isset( $_POST['bt_license_deactivate'] ) ) {

      // run a quick security check
      if( ! check_admin_referer( 'bt_activate_license_nonce', 'bt_activate_license_nonce' ) )
        return; // get out if we didn't click the Deactivate button

      // retrieve the license from the database only - user cannot change it while activated
      $license = trim(apply_filters( 'bb_bt_ab_licence_key', ab_get_admin_setting( 'bt_bb_ab_licence' )) );

      // Validate we have a license key to deactivate
      if(empty($license)) {
        abst_log('License deactivation failed: No active license key found in database');
        return false;
      }

      // data to send in our API request
      $api_params = array(
        'edd_action' => 'deactivate_license',
        'license'    => $license,
        'item_id'    => BT_AB_TEST_ITEM_ID,
        'url'        => home_url(),
        'environment' => function_exists( 'wp_get_environment_type' ) ? wp_get_environment_type() : 'production',
      );

      // Call the custom API.
      $response = wp_remote_post( BT_BB_AB_EDD_SL_STORE_URL, array( 'timeout' => 15, 'sslverify' => false, 'body' => $api_params ) );

      // make sure the response came back okay
      if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {

        $message = '';

        if ( is_wp_error( $response ) ) {
          $message = $response->get_error_message();
        } else {
          $message = __( 'An error occurred, please try again.', 'ab-split-test-lite' );
        }

        $base_url = self::get_current_settings_url();
        $redirect = add_query_arg( array( 'sl_activation' => 'false', 'message' => urlencode( $message ) ), $base_url );

        wp_redirect( $redirect );
        exit();
      }

      // decode the license data
      $license_data = json_decode( wp_remote_retrieve_body( $response ) );
      
      // $license_data->license will be either "deactivated" or "failed"
      $this->update_license_status($license_data);

      wp_redirect( self::get_current_settings_url() );
      exit();

    }
  }  

  public function admin_notice() {
    
    $message = null;
    //ony show messages to priveliged few
    if ( ! is_admin()   || ! is_user_logged_in() || ! current_user_can( 'update_core' ) || (is_network_admin() && !is_plugin_active_for_network(BT_AB_PLUGIN_FOLDER.'/bt-bb-ab.php')) ) {
      return;
    }

    $screen = get_current_screen();

    if( $screen->id == 'settings_page_bt_bb_ab_test-network' || $screen->id == 'settings_page_bt_bb_ab_test' ) {
      return;
    }

    $license = bt_bb_ab_licence_details();

    if($license->active != 'valid')
    {
      if(is_plugin_active_for_network(BT_AB_PLUGIN_FOLDER.'/bt-bb-ab.php'))
        $url = self::get_network_admin_url();
      else
        $url = self::get_admin_url();

      $message = "Enable automatic updates to AB Split Test by <a href='" . esc_url($url) . "'>activating your licence key.</a>";
      
      echo '<div class="error bt-pro-error">';
      echo '<p>' . wp_kses_post($message) . '</p>';
      echo '</div>';
    }
  }

  public function license_admin_notices() {
    if ( isset( $_GET['sl_activation'] ) && ! empty( $_GET['message'] ) ) {

      switch( $_GET['sl_activation'] ) {

        case 'false':
          $message = urldecode( $_GET['message'] );
          ?>
          <div class="error">
            <p><?php echo esc_html($message); ?></p>
          </div>
          <?php
          break;

        case 'true':
        default:
          // Developers can put a custom success message here for when activation is successful if they way.
          break;

      }
    }
  }  

  public function should_show_settings_field()
  {
    return ((is_multisite() && !is_network_admin()) || (!is_multisite() && current_user_can( 'manage_options' ) ));
  }

  public static function is_abst_teams(){
    
      
    return false;
  }

  public function should_show_license_field()
  {

    $btbbabNetworkActive = is_plugin_active_for_network(BT_AB_PLUGIN_FOLDER.'/bt-bb-ab.php');
    
    //not if administrator
    if(!is_super_admin())
      return false;
    
    if(is_network_admin() && $btbbabNetworkActive)
      return true;
    
    if(is_admin() && !is_network_admin()  && !$btbbabNetworkActive)
      return true;
    
    return false;
  }

  
  public function should_show_white_label()
  {
    $licenceDetails = bt_bb_ab_licence_details();
    $btbbabNetworkActive = is_plugin_active_for_network(BT_AB_PLUGIN_FOLDER.'/bt-bb-ab.php');
    if(!is_super_admin() || $licenceDetails->active != 'active')
      return false;
    
    if($licenceDetails->sites < 40)
      return false;
    
    if(is_network_admin() && $btbbabNetworkActive)
      return true;
    
    if(is_admin() && !is_network_admin()  && !$btbbabNetworkActive)
      return true;
    
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
    extract($data);

    include plugin_dir_path( dirname( __FILE__ ) ) .'admin/partials/'. $file .'.php';
  }

  public function get_protocol()
  {
    if (isset($_SERVER['HTTPS']) &&
        ($_SERVER['HTTPS'] == 'on' || $_SERVER['HTTPS'] == 1) ||
        isset($_SERVER['HTTP_X_FORWARDED_PROTO']) &&
        $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https') {
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
    if(get_option('abst_disable_ai') || apply_filters('abst_disable_ai', false))
      return;

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

} // end class



// CLI CLI  CLI CLI  CLI CLI  CLI CLI  CLI CLI  CLI CLI  CLI CLI  CLI CLI  CLI CLI  CLI CLI 
// Register a custom 'activate-abst' command to output a supplied positional param.
//
// $ wp activate-abst licenceKey
// Success: AB Split Test activated on: {domain}

/**
 *
 * @when before_wp_load
 */
$activateAbSplitTest = function( $args, $assoc_args ) {

  $licenceKey = sanitize_text_field($args[0]);
  
  $plugin = "bt-bb-ab";

  // Update license key setting
  update_admin_setting('bt_bb_ab_licence', $licenceKey);
  
  // Create admin instance and activate license
  $admin_instance = new BT_BB_AB_Admin();
  $admin_instance->send_licence_activate(false); // Don't redirect in CLI context
  
  // Get updated license details
  $license_data = bt_bb_ab_licence_details();
  $domain = get_home_url();
  
  if (!empty($license_data)) {
    if ($license_data->active == 'valid') {
      WP_CLI::success('AB Split Test activated on: ' . $domain);
    } else {
      WP_CLI::error('Error activating AB Split Test on ' . $domain . '. Status: License not valid', false);
    }
  }

};

if ( class_exists( 'WP_CLI' ) ) {
  WP_CLI::add_command( 'activate-abst', $activateAbSplitTest );
}

function abst_get_main_site_url() {

	// This is the current network's information; 'site' is old terminology.
	global $current_site;
	if ( is_multisite() && $current_site ) {
		$main_site_blog_id = $current_site->blog_id;
		return get_home_url( $main_site_blog_id );
	}

	return home_url();
}

