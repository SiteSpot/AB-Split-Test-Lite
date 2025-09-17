<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       http://absplittest.com
 * @since      0.9.1
 *
 * @package    Bt_Ab_Tests
 * @subpackage Bt_Ab_Tests/admin/
 */

class BT_BB_AB_Lite_Admin {

  public static $menu_name  = BT_AB_TEST_LITE_WL_NAME;
  public static $page_title = BT_AB_TEST_LITE_WL_NAME . ' Settings';
  public static $page_slug  = 'bt_bb_ab_test';

  public function __construct()
  {
    add_action( 'admin_menu', [$this, 'settings_menu']);  
    add_action( 'network_admin_menu', [$this, 'settings_menu_multisite']);      
  
    add_action( 'admin_init', [$this, 'save_settings'], 1 );
  }


 
  public function save_settings()
  {
    //only for admins
    if(!current_user_can( 'manage_options' ))
      return false;
          
    if ( isset( $_POST['bt-bb-ab-nonce'] ) && wp_verify_nonce( $_POST['bt-bb-ab-nonce'], 'bt-bb-ab-nonce' ) ) 
    {

      

      $selected_post_types = isset($_POST['selected_post_types']) ? array_map('sanitize_text_field', $_POST['selected_post_types']) : array();
      update_admin_setting( 'selected_post_types', $selected_post_types, false );
      delete_option('all_testable_posts');// refresh it
    }
  }


  
  public function update_admin_setting( $key, $value )
  {
    return update_site_option($key, $value);
  }


  public function settings_menu() 
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

  public function settings_page()
  {

    echo '<div class="wrap">';
      echo '<h1>'. esc_html( get_admin_page_title() ) .'</h1>';      


      $this->view( 'single-site-display', [] );

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

