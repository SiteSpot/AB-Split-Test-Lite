<?php

/** *
 * @link              https://absplittest.com
 * @package           Bt_Bb_Ab_Lite
 *
 * Plugin Name:       AB Split Test Lite
 * Plugin URI:        https://wordpress.org/plugins/ab-split-test/
 * Description:       A free AB Split testing plugin for WordPress - Test Pages, Blocks, Elementor, and more. Upgrade to Pro for advanced features like Bricks, Beaver Builder, Oxygen, Breakdance support.
 * Version:           1.0.0
 * Author:            AB Split Test
 * Author URI:        https://absplittest.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       ab-lite
 * 
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}


if ( class_exists( 'Bt_Bb_Ab' ) ) {
    add_action( 'admin_notices', function() {
        echo '<div class="notice notice-error"><p><strong>AB Split Test Lite:</strong> The Pro version of AB Split Test is already active. Please deactivate the Pro version before activating the Lite version.</p></div>';
    });
    
    // Deactivate this plugin
    deactivate_plugins( plugin_basename( __FILE__ ) );
    return;
}

define( 'BT_AB_TEST_LITE_PLUGIN_NAME', 'AB Split Test Lite' );  
define( 'BT_AB_TEST_LITE_VERSION', '1.0.0' );
define( 'BT_AB_TEST_LITE_PLUGIN_DIR', __FILE__ );
define( 'BT_AB_TEST_LITE_PLUGIN_PATH', plugin_dir_path( __FILE__ )  );
define( 'BT_AB_TEST_LITE_PLUGIN_URI', plugins_url('/', __FILE__) );
define( 'BT_AB_TEST_LITE_MERCHANT_URL', 'https://absplittest.com' );
$parts = explode('/', BT_AB_TEST_LITE_PLUGIN_PATH, -1);
$folder_path = end($parts);
define('BT_AB_LITE_PLUGIN_FOLDER', $folder_path);

if (!defined('BT_AB_TEST_LITE_WL_NAME')) define('BT_AB_TEST_LITE_WL_NAME', apply_filters('ab_lite_wl_name', 'AB Split Test Lite'));
if (!defined('BT_AB_TEST_LITE_WL_URL')) define('BT_AB_TEST_LITE_WL_URL', apply_filters('ab_lite_wl_url', 'https://absplittest.com'));
if (!defined('BT_AB_TEST_LITE_WL_ABTEST')) define('BT_AB_TEST_LITE_WL_ABTEST', apply_filters('ab_lite_wl_ab_test', 'AB Test'));


if(! class_exists ( 'Bt_Ab_Tests_Lite'))
{
  class Bt_Ab_Tests_Lite{
    
    function __construct(){

      add_filter( 'post_updated_messages', array( $this, 'abst_custom_post_updated_messages' ) );

      add_action( 'init', array( $this,'bt_experiments_post_register')); //register post type n status
      add_action( 'init', array( $this,'bt_include_module'),10, 2);
      
      add_action( 'init', array( $this, 'bt_include_support_module' ), 10, 2 );

      add_action('admin_head', array($this, 'bt_include_ajax_url'), 10);
      add_filter( 'post_row_actions', array($this,'remove_bulk_actions'),10,2); // remove quick edit
      add_filter( 'manage_bt_experiments_posts_columns', array($this,'experiments_posts_columns'), 10, 1 )  ; // add experiment data to columns in admin

      add_filter( 'body_class', [$this, 'updated_body_class'] );
      add_filter( 'bt_can_user_view_variations', [$this, 'can_user_view_variations'], 10, 1 );
      add_filter( 'display_post_states',  [$this, 'test_post_states'], 10, 2);

      add_action( 'wp_head', array($this,'render_experiment_config'),10);
      add_action( 'wp_head', [$this, 'header_style'] );
      //add canonical to page variations

      add_action( 'wp_enqueue_scripts', array($this,'include_conversion_scripts') );

      add_action( 'admin_enqueue_scripts', [$this, 'enqueue_experiment_scripts'] );
      add_action( 'wp_ajax_ab_page_selector', [$this,'get_posts_ajax_callback'] ); // wp_ajax_post_ac
      add_action( 'wp_ajax_blocks_experiment_list', [$this,'blocks_experiment_list'] ); // wp_ajax_post_ac
      add_action( 'wp_ajax_on_page_test_create', [$this,'on_page_test_create'] ); // wp_ajax_post_ac
      add_action( 'wp_ajax_create_new_on_page_test', [$this,'create_new_on_page_test'] ); // wp_ajax_post_ac
      //save_variation_label 
      add_action( 'wp_ajax_save_variation_label', [$this, 'save_variation_label'] ); // wp_ajax_post_ac

      add_action( 'wp_enqueue_scripts', [$this,'include_highlighter_scripts'],9999 );
      add_action( 'enqueue_block_assets', [$this,'include_highlighter_scripts'],9999 );
      add_action( 'elementor/editor/before_enqueue_scripts', [$this,'include_highlighter_scripts'],9999 );
      add_filter( 'render_block', [$this,'add_btvar_attribute_to_all_blocks'], 10, 3 );

      add_action( 'wp_ajax_bt_experiment_w',   array($this,'log_experiment_activity'), 10, 6 ); // render js to show tests and log interactions 
      add_action( 'wp_ajax_nopriv_bt_experiment_w',   array($this,'log_experiment_activity'), 10, 6 ); // render js to show tests and log interactions - logged out user
      add_action( 'wp_ajax_abst_event',   array($this,'log_experiment_activity'), 10, 6 ); // render js to show tests and log interactions 
      add_action( 'wp_ajax_nopriv_abst_event',   array($this,'log_experiment_activity'), 10, 6 ); // render js to show tests and log interactions - logged out user
      add_action( 'wp_ajax_abst_delete_variation',   array($this,'abst_delete_variation'), 10, 6 ); // render js to show tests and log interactions - logged out user

      add_action( 'bt_log_experiment_activity', [$this, 'log_experiment_activity'], 10, 6 );
      add_action( 'manage_bt_experiments_posts_custom_column', array($this,'manage_bt_experiments_posts_custom_column'),10,2); // add data to admin columns
      add_action( 'add_meta_boxes', array($this,'add_experiment_meta_box'),10,1 );  // meta boxes for experiments
      add_action( 'save_post_bt_experiments', array($this,'save_postdata'),10,1 ); // catch page save to get update conversion URL meta
      add_action( 'trashed_post', array($this,'experiment_trash_handler'),10,1 ); // trashed, refresh conversions pages
      add_action( 'untrash_post', array($this,'experiment_trash_handler'),10,1 ); // restore trashed post, refresh conversions pages
      add_action( 'pre_get_posts', array($this, 'abst_authors_see_own_posts_filter'));
      add_action( 'post_updated', array($this,'refresh_on_update'),10,3 );
      add_action( 'transition_post_status', array($this,'post_status_transition'), 10, 3);

      add_action( 'wp_head', array($this,'conversion_targeting'),10,1); // add conversion targeting page script
      add_action( 'wp_ajax_bt_clear_experiment_results',   array($this,'wp_ajax_bt_clear_results'), 10, 2 );  // render js to show tests and log interactions 
      add_action( 'edd_complete_purchase', [$this,'edd_trigger_conversion']); // edd order value as js variable 
      add_action( 'wp_ajax_bt_generate_embed_code', [$this, 'bt_generate_embed_code'] );

      add_shortcode('ab_split_test', [$this,'convert_ab_split_test_shortcode']);
    

      //admin 
      if(is_admin())
      {
        include_once( plugin_dir_path(__FILE__) . 'admin/bt-bb-ab-admin.php');
        $bt_bb_ab_admin = new BT_BB_AB_Lite_Admin;
        add_action('admin_enqueue_scripts', array($this,'render_admin_scripts_styles'));
      }

    } // end _construct


    /**
     * Restrict authors to only see their own bt_experiments posts in admin if the abst_authors_see_own_posts filter returns true.
     *
     * To enable, add this to your theme or a custom plugin:
     *     add_filter('abst_authors_see_own_posts', '__return_true');
     */
    public function abst_authors_see_own_posts_filter($query) {
        if (
            is_admin() &&
            $query->is_main_query() &&
            $query->get('post_type') === 'bt_experiments' &&
            apply_filters('abst_authors_see_own_posts', false) &&
            !current_user_can('manage_options') &&
            !current_user_can('edit_others_posts')
        ) {
            $query->set('author', get_current_user_id());
        }
    } // end abst_authors_see_own_posts_filter






    public function abtest_shortcode( $atts, $content = "" )
    {
      $attr = extract(shortcode_atts([
        'eid' => -1,
        'variation' => '',
        'class' => ''
      ], $atts));

      ob_start();

      echo '<div class="bt-abtest-wrap '. esc_attr($class) .'" bt-eid="'. intval($eid) .'" bt-variation="'. esc_attr($variation) .'">';
        echo wp_kses_post($content);
      echo '</div>';

      return ob_get_clean();
    }


    function remove_bulk_actions( $actions, $post ){
      if ( $post->post_type == "bt_experiments" ) {
        unset( $actions['inline hide-if-no-js'] );

        if( isset($actions['edit']) ) {
          $actions['edit'] = str_replace("Edit","View",$actions['edit']);  
        }        
      }
        return $actions;
    }
    
    function bt_include_ajax_url(){
      if(!wp_doing_ajax()){
        echo "<script data-cfasync='false' data-no-optimize='1' id='abst_ajax'>var bt_ajaxurl = '".admin_url( 'admin-ajax.php' )."';";
        echo "var bt_adminurl = '".admin_url()."';";
        //include plugin url
        echo "var bt_pluginurl = '".plugin_dir_url( __FILE__ )."';";
        echo "var bt_homeurl = '".home_url()."';</script>";
      }
    }
    

    function bt_include_module()
    {
      define( 'BT_CONVERSION_DIR', plugin_dir_path( __FILE__ ) );
      define( 'BT_CONVERSION_URL', plugins_url( '/', __FILE__ ) );

      define( 'BT_MODULES_DIR', plugin_dir_path( __FILE__ ) );
      define( 'BT_MODULES_URL', plugins_url( '/', __FILE__ ) );

      include_once ( plugin_dir_path( __FILE__ ) . 'modules/conversion/conversion.php');
    }
    
    function bt_include_support_module() {
      include_once( plugin_dir_path( __FILE__ ) . 'modules/support/cache.php' );
      include_once ( plugin_dir_path( __FILE__ ) . 'modules/support/support.php'); 
    }



    function conversion_targeting(){

      //bail on ajax
      if(wp_doing_ajax())
        return;
      
      $queried_object = get_queried_object();
      $target_post_id = [];
      $conversion_pages = get_option('bt_conversion_pages',false);

      // dont brick pages with no queried object (i.e. 404's)
      if(!empty($queried_object))
      {
        // get current Post ID, the WP way, then a backup way for woo archives etc.
        if (isset($queried_object->ID)){
          $target_post_id[] = $queried_object->ID;
        }



        if(is_archive())
        {
          $target_post_id[] = $queried_object->has_archive;
        }
        
        // Handle home and blog (posts index) pages specifically
        if (is_front_page()) {
          $target_post_id[] = get_option('page_on_front');
        }
        if (is_home()) {
          $blog_id = get_option('page_for_posts');
          if ($blog_id) {
            $target_post_id[] = $blog_id;
          } else {
            // If blog page is not a static page (home page set as blog page) use 'homeblog' as fallback identifier
            $target_post_id[] = 'homeblog';
          }
        }
      } // end if $queried_object
      //if its 404
      if(is_404())
      {
        $target_post_id[] = '404-not-found';
      }
      
      echo "<script data-cfasync='false' data-no-optimize='1' id='abst_conv_details'>
          var conversion_details = ".json_encode($conversion_pages).";
          var current_page = ".json_encode($target_post_id).";
        </script>";
    }
    function get_url_from_slug($slug) {

      //if its an int then get the post from id
      $post = false;
      if(is_numeric($slug))
        $post = get_post($slug);

      //when its not a post id it should get by slug
      if(empty($post))
        $post = get_page_by_path($slug, OBJECT, 'any');

      if(empty($post))
      {
        $post = get_posts(array
        (
            'name'   => $slug,
            'post_type'   => 'any',
            'post_per_page' => 1
        ));
        if(!empty($post))
        {
          $post = $post[0];
        }
      }

      if (!empty($post))
          return get_permalink($post->ID) ?? $slug;
      
      return $slug;
    }
  

  function save_test_config( $post_id, $data = array() ) {

    
    if(empty($data))
    {
    // wl('no data');
      $data = $_POST;
    }
    //count all custom posts bt_experiments
    $count = wp_count_posts('bt_experiments');
    if($count->publish > 1)
    {
      //change current post status to draft
      remove_action( 'save_post_bt_experiments', [$this,'save_postdata'], 10 );
      wp_update_post(['ID' => $post_id, 'post_status' => 'draft']);
      add_action( 'save_post_bt_experiments', [$this,'save_postdata'], 10, 1 );
      abst_log('Free licence limited to one active test. Upgrade https://absplittest.com/pricing?utm_source=ugrepolite');
      return;
    }
    
    //test type 
    
    $url_query = sanitize_text_field($data['test_type']); //ab_test full_page css_test magic
    update_post_meta( $post_id, 'test_type', $url_query );

    // Magic test functionality removed in Lite version
    
    // base page for full page split test
    // bt_experiments_full_page_default_page
    $url_query = $data['bt_experiments_full_page_default_page'] ?? '';
    update_post_meta( $post_id, 'bt_experiments_full_page_default_page', $url_query );

    //page_variations
    $page_variations = (array) ($data['page_variations'] ?? []);
    foreach($page_variations as $k => $v){
      $page_variations[$v] = $this->get_url_from_slug($v);
      unset($page_variations[$k]);
    }
    $page_variations = array_map( 'esc_attr', $page_variations );

    update_post_meta( $post_id, 'page_variations', $page_variations );

    //get variation meta
    $variation_meta = get_post_meta( $post_id, 'variation_meta', true );

    if(empty($variation_meta))
    {
      //variation meta
      // variation meta (labels and screenshots)
      $variation_labels = $data['variation_label'] ?? array();
      $variation_meta = array();
      foreach($page_variations as $key => $url){
        $variation_meta[$key] = array(
          'label' => sanitize_text_field($variation_labels[$key] ?? ''),
          'weight' => 1
        );
      }
      update_post_meta( $post_id, 'variation_meta', $variation_meta );
    }
    
    //url query
    $url_query = sanitize_text_field($data['bt_experiments_url_query']);
    update_post_meta( $post_id, 'url_query', $url_query );

    $allowed_roles = isset( $data['bt_allowed_roles'] ) ? (array) $data['bt_allowed_roles'] : [];
    $allowed_roles = array_map( 'esc_attr', $allowed_roles );
    update_post_meta( $post_id, 'bt_allowed_roles', $allowed_roles );

    $target_percentage = sanitize_text_field($data['bt_experiments_target_percentage']);
    if($target_percentage == '')
      $target_percentage = '100';
    update_post_meta($post_id,'target_percentage',$target_percentage);
    
    // new target option device size desktop tablet mobile
    $target_option_device_size = sanitize_text_field($data['bt_experiments_target_option_device_size'] ?? false);
    update_post_meta($post_id,'target_option_device_size',$target_option_device_size);

    $conversion_page = sanitize_text_field($data['bt_experiments_conversion_page']);
    $conversion_time = isset($data['bt_experiments_conversion_time']) ? intval($data['bt_experiments_conversion_time']) : 0;
    $conversion_scroll = isset($data['bt_experiments_conversion_scroll']) ? intval($data['bt_experiments_conversion_scroll']) : 0;
    $conversion_text = $data['bt_experiments_conversion_text'] ?? false;

    if( $conversion_page == '0' )
      $conversion_page = '';

    if($conversion_page == 'page') // if its a page the get the page int for back compat its one field
      $conversion_page = intval($data['bt_experiments_conversion_page_selector']);

    update_post_meta($post_id,'conversion_page', $conversion_page);
    update_post_meta($post_id,'conversion_time', $conversion_time);
    update_post_meta($post_id,'conversion_scroll', $conversion_scroll);
    update_post_meta($post_id,'conversion_text', $conversion_text);

    if( $conversion_page = 'url' ){
      $conversion_url = sanitize_text_field( $data['bt_experiments_conversion_url'] );
      $conversion_url = str_replace(site_url(),"",$conversion_url); // remove the site URL if entered in error
      $conversion_url = ltrim($conversion_url, '/');  //ditch the slashes
      $conversion_url = rtrim($conversion_url, '/'); 
    }
    else
    {
        $conversion_url = '';
    }
    
    update_post_meta( $post_id, 'conversion_url', $conversion_url );


    if( $conversion_page = 'selector' )
      $conversion_selector = sanitize_text_field( $data['bt_experiments_conversion_selector'] );
    else
      $conversion_selector = '';

    update_post_meta( $post_id, 'conversion_selector', $conversion_selector );

    // Save conversion_link_pattern (Link Click trigger)
    if (isset($data['bt_experiments_conversion_link_pattern'])) {
      $conversion_link_pattern = sanitize_text_field($data['bt_experiments_conversion_link_pattern']);
      if (!empty($conversion_link_pattern)) {
        update_post_meta($post_id, 'conversion_link_pattern', $conversion_link_pattern);
      } else {
        delete_post_meta($post_id, 'conversion_link_pattern');
      }
    }
    
  

    //autocomplete things
    $this->refresh_conversion_pages();

  }
    function save_postdata( $post_id ) {

      if (isset($_POST['original_post_status']) && $_POST['original_post_status'] === 'auto-draft') {
          update_post_meta($post_id, '_abst_is_new', 'true');
      }
      /*
       * We need to verify this came from the our screen and with proper authorization,
       * because save_post can be triggered at other times.
       */
      // Check if our nonce is set.
      if ( ! isset( $_POST['bt_experiments_inner_custom_box_nonce'] ) )
        return $post_id;
      $nonce = $_POST['bt_experiments_inner_custom_box_nonce'];
      // Verify that the nonce is valid.
      if ( ! wp_verify_nonce( $nonce, 'myplugin_inner_custom_box' ) )
          return $post_id;
      // If this is an autosave, our form has not been submitted, so we don't want to do anything.
      if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) 
          return $post_id;
      // Check the user's permissions.
      if ( ! current_user_can( 'edit_post', $post_id ) )
          return $post_id;
      
      /* OK, its safe for us to save the data now. */
      $this->save_test_config( $post_id, $_POST );

  }

    //refresh conversion pages on trashed/untrashed experiments
    
  function abst_custom_post_updated_messages( $messages ) {
    global $post;

    if ( ! is_object( $post ) || 'bt_experiments' !== get_post_type( $post->ID ) ) {
      return $messages;
    }
    //if post created was in the last minute its new
    $post_time = get_the_time('U', $post->ID);
    $is_new = time() - $post_time <= 60;

    if ($is_new) {
        $message_text = 'Split Test Created.';
    } else {
      $message_text = 'Split Test Updated.';
    }

    $cache_message = '';
    $detected_caches = abst_get_detected_caches();
    $cache_list = implode(', ', $detected_caches);
    if ( ab_get_admin_setting('ab_dont_clear_cache_on_update') == '1' ) {
        $cache_message = ' <strong>IMPORTANT: You\'ll need to manually clear your cache. </strong><BR> The following caches have been detected: ' . $cache_list;
    } else {
        if ( ! empty( $detected_caches ) ) {
            $cache_message = ' These caches have been cleared: ' . esc_html($cache_list) . '.';
        }
        $cache_message .= ' If you are running any other website caching, clear that now.';
    }

    $full_message = esc_html($message_text) . "<br/><br/>" . $cache_message ;

    $messages['post'][1] = $full_message; // Updated
    $messages['post'][4] = $full_message; // Post updated.
    $messages['post'][6] = $full_message; // Published

    return $messages;
  }

  function experiment_trash_handler( $post_id ) {
      //only on experiments
      if ( 'bt_experiments' != get_post_type( $post_id ))
        return;

       $this->refresh_conversion_pages();
    }

    
    function refresh_on_update($post_ID, $post_after, $post_before){

      $old_title = $post_before->post_title;
      $new_title = $post_after->post_title;

      // Check if the post title has changed
      if ($old_title !== $new_title) {
          delete_option('all_testable_posts'); //  refresh it
      }

     //    if ($post_after->post_type !== 'bt_experiments')
    //        return;
      
      $this->refresh_conversion_pages();
      
    } 

    function refresh_conversion_pages(){ // and canonicals

      delete_transient('ab_posts_cache');


      $post_ids = get_posts(array(
        'post_type' => 'bt_experiments',
        'post_status' => 'publish',
        'posts_per_page' => -1,
        'fields' => 'ids'
      ));

      $conversion_pages = [];
      $canonical_list = [];

      foreach ($post_ids as $post_id) {
        $conversion_page = get_post_meta($post_id,'conversion_page',true);
        $conversion_url = get_post_meta($post_id,'conversion_url',true);
        $conversion_time = get_post_meta($post_id,'conversion_time',true);
        $conversion_scroll = get_post_meta($post_id,'conversion_scroll',true);
        $target_percentage = get_post_meta($post_id,'target_percentage',true);     
        $url_query = get_post_meta($post_id,'url_query',true);        
        $conversion_text = get_post_meta($post_id,'conversion_text',true);        
        $test_type = get_post_meta($post_id,'test_type',true);
        $bt_experiments_full_page_default_page = get_post_meta($post_id,'bt_experiments_full_page_default_page',true);
              
        if($test_type == 'full_page' && ab_get_admin_setting('ab_change_canonicals') == 1)
        {
          $canonical = get_permalink($bt_experiments_full_page_default_page);// gets default page
          $variatons = get_post_meta($post_id,'page_variations',true);//gets variations
          foreach ($variatons as $key => $value) {//loop through variations
            $canonical_list[$key] = $canonical; //add to canonical list
          }
        }

        if($conversion_page == '0') {
          continue;
        }
          
        if($conversion_page == 'url' && !empty($conversion_url))
        {
          $conversion_pages[$post_id]['conversion_page_url'] = $conversion_url;
        }
        elseif($conversion_page !== 'url' && $conversion_page !== '' && $conversion_page !== '0')
        {
            $conversion_pages[$post_id]['conversion_page_id'] = $conversion_page;
        }

        // add targeting
        $conversion_pages[$post_id]['target_percentage'] = $target_percentage;
        $conversion_pages[$post_id]['time_active'] = $conversion_time;
        $conversion_pages[$post_id]['scroll_depth'] = $conversion_scroll;
        $conversion_pages[$post_id]['url_query'] = $url_query;
        $conversion_pages[$post_id]['conversion_text'] = $conversion_text;
      }

      update_option('bt_conversion_pages',$conversion_pages);
      update_option('ab_test_canonical',$canonical_list);


        if( class_exists('FLBuilderModel') ) 
        {
          abst_log('clearing BB cache');
          FLBuilderModel::delete_asset_cache_for_all_posts(); // clear cache  

        if (function_exists('sg_cachepress_purge_cache')){
          abst_log('clearing sg cache');
          sg_cachepress_purge_cache();
        }
      
        if(function_exists('nitropack_sdk_purge'))
        {
          abst_log('clearing nitropack cache');
          nitropack_sdk_purge(NULL, NULL, "ABST: Clear after test update");
        }
      
        if(class_exists('WP_Optimize'))
        {
          abst_log('clearing wp optimize cache');
          WP_Optimize()->get_page_cache()->purge();
        }
    
        if ( class_exists( '\FlyingPress\Purge' ) )
        {
          abst_log('clearing flyingpress cache');
          \FlyingPress\Purge::purge_everything();
        }

        if ( class_exists( 'autoptimizeCache' ) )
        {
          abst_log('clearing autoptimize cache');
          \autoptimizeCache::clearall();
        }

        do_action( 'breeze_clear_all_cache' );

        if ( class_exists( '\WebSharks\CometCache\Classes\ApiBase' ) )
        {
          abst_log('clearing comet cache');
          \WebSharks\CometCache\Classes\ApiBase::clear();
        }
        
        if ( class_exists( '\WebSharks\CometCache\Pro\Classes\ApiBase' ) )
        {
          abst_log('clearing comet pro cache');
          \WebSharks\CometCache\Pro\Classes\ApiBase::clear();
        }

        if ( class_exists( '\WPaaS\Cache' ) && function_exists( 'ccfm_godaddy_purge' ) ) {
          abst_log('clearing godaddy cache');
          ccfm_godaddy_purge();
        }
      
        if ( class_exists( '\Kinsta\Cache' ) && ! empty( $kinsta_cache ) ) 
        {
          abst_log('clearing kinsta cache');
          $kinsta_cache->kinsta_cache_purge->purge_complete_caches();
        }
      
        if (class_exists('\LiteSpeed\Purge')) {
          abst_log('clearing litespeed cache');
          \LiteSpeed\Purge::purge_all();
        }

        if ( class_exists( 'RapidLoad_Cache' ) && method_exists( 'RapidLoad_Cache', 'clear_site_cache' ) ) 
        {
          abst_log('clearing rapidload cache');
          \RapidLoad_Cache::clear_site_cache();
        }
    
        if ( class_exists( 'W3_Plugin_TotalCacheAdmin' ) ) {
          abst_log('clearing total cache');
          $plugin = & w3_instance( 'W3_Plugin_TotalCacheAdmin' );
          $plugin->flush_all();
        }
        if ( function_exists( 'wp_cache_flush' ) ) 
        {
          abst_log('clearing wp cache');
          wp_cache_flush();
        }
        global $wp_fastest_cache;
        if ( ! empty( $wp_fastest_cache ) && method_exists( $wp_fastest_cache, 'deleteCache' ) )
        {
          abst_log('clearing wp fastest cache');
          $wp_fastest_cache->deleteCache( true );
        }

        if ( function_exists( 'wp_cache_clean_cache' ) ) 
        {
          abst_log('clearing wp cache clean cache');
          global $file_prefix;
          wp_cache_clean_cache( $file_prefix, true );
        }
        
        if ( function_exists( 'flush_wp_rocket' ) ) 
        {
          abst_log('clearing wp rocket cache');
          flush_wp_rocket();
        }
        if ( function_exists( 'rocket_clean_domain' ) ) 
        {
          abst_log('clearing wp rocket domain cache');
          rocket_clean_domain();
        }
      
        if (  class_exists( 'WpeCommon' ) ) {
          if ( method_exists( 'WpeCommon', 'purge_memcached' ) ) 
            \WpeCommon::purge_memcached();
          if ( method_exists( 'WpeCommon', 'clear_maxcdn_cache' ) ) 
            \WpeCommon::clear_maxcdn_cache();
          if ( method_exists( 'WpeCommon', 'purge_varnish_cache' ) ) 
            \WpeCommon::purge_varnish_cache();
          abst_log('clearing wpe cache');
        }

      } // end wpe
        

    }

    
    function add_experiment_meta_box() {
          
      // one meta box style
      add_meta_box(
        'all_boxes',
        BT_AB_TEST_LITE_WL_ABTEST,
        array($this,'all_boxes'),
        'bt_experiments'
      );
    }
    
    //combines all the sections into one output;

    //get the output of all boxes in an iframe
    function get_all_boxes($post){
      ob_start();
      $this->all_boxes($post);
      $output = ob_get_contents();
      ob_end_clean();
      return $output;
    }

    function create_new_on_page_test(){

      if(!current_user_can('edit_posts'))
        wp_die('You do not have the correct permissions to create a test.');

      //recieve form data
      $data = $_POST;
      //sanitize it!
      if(empty($data))
        wp_die('No data received.');

      $createdNew = false;
      if($data['post_id'] == 'new')
      {
        $my_post = array(
          'post_title'   => sanitize_text_field($data['post_title']),
          'post_type'    => 'bt_experiments',
          'post_status'  => 'publish',
          'post_date'    => current_time('mysql'),
          'post_date_gmt'=> current_time('mysql', 1),
        );
        $data['post_id'] = wp_insert_post( $my_post );
        $createdNew = true;
      }
      else
      {
        $my_post = array(
          'ID'           => $data['post_id'],
          'post_title'   => sanitize_text_field($data['post_title']),
          'post_status'  => 'publish',
          'post_date'    => current_time('mysql'),
          'post_date_gmt'=> current_time('mysql', 1),
        );
        wp_update_post( $my_post );
      }
      
      echo $this->save_test_config( $data['post_id'], $data);

      if($createdNew)
      {
        echo json_encode($data);
      }
      else
      {
        echo "<body style='background: green; color: white; font-family: sans-serif; text-align: center; padding: 20px;'>";
        echo "<h1>Test ".$data['post_title']." Created</h1><BR><BR>";
        echo "<p>This modal will be closing any moment.</p>";  
      
        echo '<script>
        //send postmessage to parent iframe window
          // the format of the message is:
          // {
          //     "id": "the post id",
          //     "name": "the post title"
          // }
          window.parent.postMessage({
            "id":"'.$data['post_id'].'",
            "name":"'.$data['post_title'].'"
        },"*");

        //if the user clicks escape, then send postmessage abclosemodal
        window.addEventListener("keydown", function(event) {
          if (event.key === "Escape") {
            window.parent.postMessage("abclosemodal","*");
          }
        })
        </script></body>';
      }

      wp_die();
    }

    function save_variation_label(){

      $pid = intval($_POST['pid']);
      $variation_name = sanitize_text_field($_POST['variation_name']);
      $variation_id = sanitize_text_field($_POST['variation_id']);
      //sanitized
    
      //if test is experiment
      if(get_post_type($pid) == 'bt_experiments')
      {

        if (!current_user_can('edit_posts'))
          wp_die('You do not have the correct permissions to edit this label.');

        //get variation_meta from the test post, not variation post
        $variation_meta = get_post_meta($pid, 'variation_meta', true);
        //update variation_name meta
        if($variation_meta === false || !is_array($variation_meta))
        {
          $variation_meta = array();
        }
        
        // Set the label for this variation ID
        $variation_meta[$variation_id]['label'] = $variation_name;
        
        update_post_meta($pid, 'variation_meta', $variation_meta);
        abst_log('variation label saved: ' . $variation_name . ' for variation id ' . $variation_id . ' for test id ' . $pid);
        echo json_encode(array('success' => true, 'variation_meta' => $variation_meta));
      }
      else
      {
        echo json_encode(array('success' => false, 'error' => 'Invalid post type'));
      }
      wp_die();
    }

    // wp ajax call loaded into iframe to create form to create a new on page test
    function  on_page_test_create() {


      //echo minimal iframe headers
      echo '<!DOCTYPE html><html><head><meta name="viewport" content="width=device-width, initial-scale=1.0">';
      echo "<script type='text/javascript'> window.ajaxurl = '" . admin_url( 'admin-ajax.php' ) . "'; window.bt_homeurl = '" . home_url() . "';</script>";
      $jquery_src = '//ajax.googleapis.com/ajax/libs/jquery/1.10.2/jquery.min.js';
      echo "<script type='text/javascript' src='" . $jquery_src . "'></script>";
      echo "<script type='text/javascript' src='//cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js'></script>";
      echo "<link rel='stylesheet' href='//cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css'>";
      echo "<script type='text/javascript' src='" . plugin_dir_url( __FILE__ ) . "js/experiment.js'></script>";
      echo "<link rel='stylesheet' href='" . plugin_dir_url( __FILE__ ) . "css/experiment.css'>";
      echo "<link rel='stylesheet' href='" . plugin_dir_url( __FILE__ ) . "admin/bt-bb-ab-admin.css'>";
      echo "<script type='text/javascript'>
      jQuery(document).ready(function(){
        jQuery('body').on('click','.collapsed>h3, .expanded>h3',function(e){
        jQuery(this).parent().toggleClass('expanded').toggleClass('collapsed');

      }); 
      //select input post_title
        jQuery('#post_title').focus();
      });</script>";
      // include select2
      echo '<style>
            body {
                font-family: sans-serif;
                padding: 0 20px !important;
                margin: 0 !important;
                overflow-x: hidden;
                background: white;
            }

            .experiment_box, .title_box {
                background: white;
                padding: 20px;
                border-radius: 10px;
                box-shadow: inset 0 0 10px -3px #9b9b9b;
                margin-bottom: 10px;
            }

            div#submitpost {
                position: sticky;
                top: 0;
                left: 0;
            }

            .subgoal h4 {
                margin: 2px 0;
                font-weight: 200;
            }

            .goal {
                /* background: green; */
            }

            .subgoal input[type="text"] {
                margin-bottom: 5px;
                margin-top: 5px;
            }

            .subgoal {
                background: #ffffff;
                padding: 5px 10px 10px 10px;
                ~: 10px; border: thin solid #dfdfdf;
                margin-bottom: 5px;
                border-radius: 5px;
            }

            .results_variation.title {
                border-top: thin solid #e1e1e1;
            }

            #abwelcomearea p {
                font-size: 1.3em;
            }

            .ab-tab-button {
                display: none;
            }

            input#post_title {
                display: block;
                width: 95%;
            }

            h3 {
                background: white !important;
                padding: 5px 5px 5px 30px;
                border-radius: 5px;
                box-shadow: 0 1px 10px -3px #00000047;
                margin-left: -30px;
            }

            h3:hover {
                background: whitesmoke !important;
            }

            h4 {
                margin-top: 10px;
                margin-bottom: 10px;
            }

            body {
                padding: 0!important;
            }

            input {
                padding: 5px 10px;
            }

            select {
                padding: 5px 10px;
            }

            .experiment_box {
                position: sticky;
                display: block;
                bottom: 0;
            }

            button#submit_experiment {
                width: calc(100% - 40px);
                display: block;
                margin: 0 20px;
                background: green;
                border-radius: 5px;
                color: #e8e8e8;
                padding: 10px;
                font-size: 1.1em;
                font-weight: bold;
                letter-spacing: .5px;
                text-shadow: 0 1px 10px #00000063;
                border: none;
                cursor: pointer;
            }

            button#submit_experiment:hover {
                background: #009e00;
            }

            button#submit_experiment:active {
                background: #00638a;
            }

            .submit_box {
                position: fixed;
                bottom: 0;
                display: block;
                margin: 0 -20px 0 -20px;
                background: #f5f5f5;
                padding: 10px 0;
                box-shadow: 0 0 10px -3px #00000078;
                width: 100%;
            }

            form#post {
              padding: 20px;
            }

            button.button.button-small.add-goal {
              background: white;
              border: thin solid #bbbbbb;
              border-radius: 5px;
              padding: 3px 11px;
              cursor:pointer;
            }button.button.button-small.add-goal:hover {
              background: #ebebeb;
              border: thin solid #a0a0a0;
            }button.button.button-small.add-goal:active {
              background: #ffffff;
              border: thin solid #747474;
            }
      </style>';
      //form post to ajax url action name create_new_on_page_test
      echo '</head><body><form action="' . admin_url( 'admin-ajax.php' ) . '" method="post" id="post" enctype="multipart/form-data"><h4>Create new Split Test</h4><div class="title_box"> <h4><label for="post_title">Test Name</label></h4><input name="post_title" id="post_title" type="text" value="" placeholder="Test Name" size="30" class="regular-text" required="required"/>';
      echo '<input type="hidden"  name="test_type" value="ab_test"/>';
      echo '<input type="hidden"  name="css_test_variations" value="0"/>';
      echo '<input type="hidden"  name="bt_experiments_full_page_default_page" value="false"/>';
      echo '<input type="hidden" name="action" value="create_new_on_page_test" /></div><div class="experiment_box">';
      // Create a new draft bt_experiment and get the post
      $post_id = wp_insert_post(array(
        'post_type' => 'bt_experiments',
        'post_status' => 'auto-draft',
      ));
    
      if (is_wp_error($post_id)) {
        echo 'Error creating test post';
        echo '</div></div></form></body></html>';
        wp_die();
      }

      echo '<input type="hidden" name="post_id" value="' . $post_id . '" />';
      $post = get_post($post_id);

      $this->bt_experiments_inner_custom_box($post);// conversions / goals area
      echo "</div></div>";

      
        echo "<div class='show_goals'><div class='subgoal upgrade'> <h4>Upgrade  to add Subgoals</h4><p>Analyze each stage of your customer's journey, identify drop-off points, and optimize every part of your funnel for better performance</p><p>Upgrade also includes support for more sites, Custom Conversion Values (Order Value), Analytics integrations and more!</p><p><a href='https://absplittest.com/pricing' target='_blank'>Upgrade to a Pro Plan</a></p></div>";
      
      echo "</div>";
          echo '<input type="hidden"  name="conversion_style" value="bayesian"/>';
      

      echo "<div class='show_targeting_options collapsed'><h3>Test Visitor Segmentation</h3><p>Choose which visitors will be tested on. <BR/>Everyone else will see the default.<BR/>Filters apply in the execution order ↓</p>";
      $this->show_targeting_options($post);
      echo "<div class='show_autocomplete collapsed'><h3>Autocomplete</h3>";
      //if its on then show it
          echo "<p>Autocomplete available in pro.</p>";
      echo '</div><div class="submit_box"><button class="button button-primary button-large" id="submit_experiment">Save and Start Test</button></div></form></div></body></html>';

      // End wp ajax call
      wp_die();
    }

    function all_boxes($post){

      $pid = $post->ID;

      $cog = '<svg viewBox="0 0 20 20" fill="currentColor" id="cog" class="w-8 h-8 text-cool-gray-800 dark:text-cool-gray-200 group-hover:text-purple-600 group-focus:text-purple-600 dark:group-hover:text-purple-50 dark:group-focus:text-purple-50"><path fill-rule="evenodd" d="M11.49 3.17c-.38-1.56-2.6-1.56-2.98 0a1.532 1.532 0 01-2.286.948c-1.372-.836-2.942.734-2.106 2.106.54.886.061 2.042-.947 2.287-1.561.379-1.561 2.6 0 2.978a1.532 1.532 0 01.947 2.287c-.836 1.372.734 2.942 2.106 2.106a1.532 1.532 0 012.287.947c.379 1.561 2.6 1.561 2.978 0a1.533 1.533 0 012.287-.947c1.372.836 2.942-.734 2.106-2.106a1.533 1.533 0 01.947-2.287c1.561-.379 1.561-2.6 0-2.978a1.532 1.532 0 01-.947-2.287c.836-1.372-.734-2.942-2.106-2.106a1.532 1.532 0 01-2.287-.947zM10 13a3 3 0 100-6 3 3 0 000 6z" clip-rule="evenodd"></path></svg>';
      $graphicon = '<svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path d="M2 11a1 1 0 011-1h2a1 1 0 011 1v5a1 1 0 01-1 1H3a1 1 0 01-1-1v-5zM8 7a1 1 0 011-1h2a1 1 0 011 1v9a1 1 0 01-1 1H9a1 1 0 01-1-1V7zM14 4a1 1 0 011-1h2a1 1 0 011 1v12a1 1 0 01-1 1h-2a1 1 0 01-1-1V4z"></path></svg>';
      $webhook_url = get_post_meta($pid,'webhook_url',true); 
      $magic_definition = get_post_meta($pid,'magic_definition',true); 
        //if post status is publish
        $active_tests = wp_count_posts('bt_experiments');
        if($active_tests->publish > 0 && (get_post_status($pid) !== 'publish'))
          echo "<div class='free-notice'><h3>HEADS UP!</h3><h4>This version of AB Split Test is limited to one active test. Pause your other tests or upgrade to start this test. </h4><p><a href='https://absplittest.com/pricing?utm_source=ugrepolite' target='_blank'>Upgrade to pro.</a></p><p>Already upgraded? <?BR>Install the premium version available on the website.</p></div>";
        if($active_tests->publish > 1 && (get_post_status($pid) == 'publish'))
          echo "<div class='free-notice'><h3>HEADS UP!</h3><h4>This version of AB Split Test is limited to one active test. Upgrade to modify & activate this test. </h4><p><a href='https://absplittest.com/pricing?utm_source=ugrepolite' target='_blank'>Upgrade to pro.</a></p><p>Already upgraded? <?BR>Install the premium version available on the website.</p></div>";
      
      echo "<div class='ab-tabs'><button class='config-button ab-tab-button tab-active' href='#config'>".$cog." Settings</button><button class='ab-tab-results-button ab-tab-button' href='#results'>".$graphicon." Results</button></div>";
      echo "<div class='ab-panels'><div id='configuration_settings' class='ab-tab-content'><div class='show_test_type'><h3>Test Type</h3>";
      $this->show_test_type($post);
      echo "</div><div class='show_full_page_test'><h3>Page and Variations</h3>";
      $this->show_full_page_test($post);
      echo "</div></div>";  
      //if bricks, add bricks helper
      echo "<div class='show_css_classes expanded'><h3>On-Page Elements</h3>";
      echo "<p  class='show'>You can go straight to the block editor or your favourite page builder.<BR>Click the element you want to test, then create your test in the AB Split Test Section of your settings.</p>";
      if (wp_get_theme()->get('Name') == 'Bricks') {
        echo "<h4>Bricks Instructions.</h4>";
        echo "<p>Upgrade to Pro to test Bricks.</p>";
      }
      //elementor
      if (did_action( 'elementor/loaded')) {
        echo "<h4>Elementor Instructions.</h4>";
        echo "<ol>";
        echo "<li>Load the Elementor editor on the page or template you want to test in.</li>";
        echo "<li>Click any element or section to test.</li>";
        echo "<li>Click the Advanced Tab, then " . BT_AB_TEST_LITE_WL_ABTEST . ".</li>";
        echo "<li>Choose your test.</li>";
        echo "<li>Give your test variation a name.</li>";
        echo "<li>Create your alternate version in Elementor, and tag it with the same test name and a new variation name.</li>";
        echo "</ol>";
      }
      // beaver
      if (class_exists('FLBuilderModel')) {
        echo "<h4>Beaver Builder.</h4>";
        echo "<p>Upgrade to Pro to test Beaver Builder.</p>";
      }
      //Breakdance
      if (class_exists('Breakdance')) {
        echo "<h4>Breakdance.</h4>";
        echo "<p>Upgrade to Pro to test Breakdance.</p>";
      }      
      global $oxygen_vsb_components;
      if( !empty($oxygen_vsb_components)){
        echo "<h4>Oxygen</h4>";
        echo "<p>Upgrade to Pro to test Oxygen.</p>";
      }
      //Blocks
      echo "<h4>Blocks / Gutenberg.</h4>";
      echo "<ol>";
      echo "<li>Load the Block editor.</li>";
      echo "<li>Click any block you want to test.</li>";
      echo "<li>Click the Advanced Tab, then " . BT_AB_TEST_LITE_WL_ABTEST . ".</li>";
      echo "<li>Choose your test from the dropdown.</li>";
      echo "<li>Give your test variation a name.</li>";
      echo "<li>Create your alternate version in Blocks, and tag it the same test and a different variation name.</li>";
      echo "</ol>";
      //css classes / shortcodes
      echo "<h4>CSS Classes / Shortcodes.</h4>";
      echo "<ol>";
      echo "<li>Make note of your test classes below these instructions.</li>";
      echo "<li>Load and edit the page you want to test. You can do this on any page in anything that lets you apply CSS classes or edit HTML.</li>";
      echo "<li>Choose an element wrapping your test variation.</li><li>Add the CSS classes you copied from the test like 'ab-". $post->ID ." ab-var-{name}' to that element.</li>"; 
      echo "<li>Give your test variation a name.</li>";
      echo "<li>Create your alternate version on the same page and apply the same test classes, but with a different variation name.</li>";
      echo "</ol>";
      // show classes and shortcodes
      echo "<div class='test-class-info test-variation-info'>";
      echo "<label>HTML Classes</label>";
      echo "<input type='text' value='ab-".get_the_ID() ." ab-var-{name}'/>";
      echo "<p><small>Replace {name} with your variation name</small></p>";
      echo "</div>";
      //shortcodes
      echo "<label>Shortcode</label>";
      echo "<input type='text' value=\"[ab_split_test eid='".$post->ID."' var='{name}']Text One![/ab_split_test]\"/>";
      echo "</div><div class='bt_experiments_inner_custom_box'><div class='goal'><h3>Conversion / Goal Trigger</h3><div class='conversion-goal'>";
      $this->bt_experiments_inner_custom_box($post);// conversions / goals area
      echo "</div>";
      echo "<div class='upgrade'> <h4>Upgrade to add Subgoals</h4><p>Analyze each stage of your customer's journey, identify drop-off points, and optimize every part of your funnel for better performance</p><p>Agency upgrade also includes support for more sites, Custom Conversion Values (Order Value), Analytics integrations and more!</p><p><a href='https://absplittest.com/pricing' target='_blank'>Upgrade Plan</a></p></div>";
      echo "</div>";
      
      echo "<input type='hidden' name='conversion_style' value='bayesian'/>";

      echo "<div class='show_targeting_options collapsed'><h3>Test Visitor Segmentation</h3><p>Choose which visitors will be tested on. <BR/>Everyone else will see the default.<BR/>Filters apply in the execution order ↓</p>";
      $this->show_targeting_options($post);
      echo "</div>";
      
      echo "<div class='show_experiment_results ab-tab-content'>";
      $this->show_experiment_results($post);

      $test_winner = get_post_meta($pid,"test_winner",true);
          if(!empty($test_winner))
            echo '<script>
        var end = Date.now() + 1500;
        (function frame() {
          confetti({
            particleCount: 4,
            angle: 60,
            startVelocity: 90,
            spread: 77,
            origin: { x: 0,y:0.6 }
          });
          confetti({
            particleCount: 3,
            angle: 130,
            startVelocity: 80,
            spread: 100,
            origin: { x: 1,y:0.4 }
          });
          if (Date.now() < end) {
            requestAnimationFrame(frame);
          }
        }());
        </script>';

      echo "</div></div>";
    }


    function show_full_page_test($post){

      global $wp_post_types;
      $selected_page = get_post_meta($post->ID,'bt_experiments_full_page_default_page',true);
      $page_variations = get_post_meta($post->ID,'page_variations',true);
      $variation_meta = get_post_meta($post->ID,'variation_meta',true);
      $posts = abst_get_all_posts();
      $archives_taxonomies = $this->abst_get_archives();
      if(count($posts) < 2)
      { 
        echo "<p><strong>You need to have more than 1 page published to create a page split test.</strong></p><p><a href='/wp-admin/post-new.php?post_type=page' class='button'>Create A Page</a></p>";
        return;
      }
      echo "<div class='ab-test-full-page-start-page'><p>The start page for your test where you will send your traffic.</p>";
      echo "<p>This could be an existing page, or you could create new pages and send traffic to it.</p>";
      echo '<select name="bt_experiments_full_page_default_page" id="bt_experiments_full_page_default_page">';
      echo '<option value="" disabled ' . (empty($selected_page) ? 'selected' : '') . '>Select a page…</option>';
      if(!empty($selected_page))
      {
        echo '<option value="', $selected_page, '" selected="selected">', get_the_title($selected_page), '</option>';
      }
      
      foreach ($posts as $k => $post) {
                
        echo '<option value="', $post->ID, '"', $selected_page == $post->ID ? ' selected="selected"' : '', '>'. $post->post_title. '</option>';
        
      }
      //archives taxonomies
      if(!empty($archives_taxonomies))
      {
        foreach ($archives_taxonomies as $k => $tax) {
          echo '<option value="', $k, '"', $selected_page == $k ? ' selected="selected"' : '', '>'. $tax. '</option>';
        }
      }

      echo '</select>'; // end select
      $lastPostType = '';
      echo "<a href='' id='full-page-test-page-preview' target='_blank' style='display:none;'>view page »</a>";

      echo "</div><div class='page-variations-wrapper'><h4>Page Variations</h4>";
      echo "<p>We'll split your traffic evenly between the page you choose above, and any variations you add below.</p>";
      echo "<p><strong>NOTE: this free version is limited to one test variation. <a href='https://absplittest.com/pricing?utm_source=ugrepolite' target='_blank'>Upgrade for unlimited tests with unlimited variations, AI assistance and more.</a></strong></p>";
      $mypages = $posts;
      if(!empty($mypages) || !empty($page_variations))
      {
        echo "<div id='page-test-variations' class='choices-container'><select id='page_variations' name='page_variations[]' placeholder='Choose a Variation...' multiple data-placeholder='Choose a Variation...'>";
        
        //add in existing variations
        if(!empty($page_variations))
        {
          
          // add preselected options (page_variations)
          foreach($page_variations as $k => $page_variation)
          {

            $variation = false;
            $page_slug = $page_variation;
            // if is not number 
            if(is_numeric($k))
            {
              $variation = get_post($k);
              if(!empty($variation))
              {
                $page_title = $variation->post_title . ' | ' .$variation->post_type . ' | ' . $page_slug;
                $page_slug = $k;
              }
            }
            else
            {
              $variation = get_page_by_path($k, OBJECT, post_types_to_test());
              if(!empty($variation))
              {
                $page_title = $variation->post_title . ' | ' .$variation->post_type . ' | ' . $page_slug;
                $page_slug = $variation->ID;
              }
              else // didnt get from slug. wp query post_name
              {
                $qpo = get_posts(array
                (
                    'name'   => $page_slug,
                    'post_type'   => 'any',
                    'post_per_page' => 1
                ));
                if(!empty($qpo))
                {
                  $page_slug = $qpo[0]->ID;
                  $page_title = $qpo[0]->post_title . ' | ' .$qpo[0]->post_type . ' | ' . $k;

                }
                else
                {
                  $page_slug = $k;
                  $page_title = $k;
                }
              }
            }
            echo '<option value="' .$page_slug .'" selected="selected">', $page_title, '</option>';
          }
        }




        foreach( $mypages as $page )
        {
            $title = $page->post_title;
            $slug = basename(get_permalink($page->ID));
            $type = $page->post_type;
            $post_name = $page->post_name;
            $selected = false;


            $selected = (isset($page_variations[$slug]) && $page_variations[$slug] == get_permalink( $page->ID )) ? 'selected' : '';

            if(empty($title))
              $title = '#' . $page->ID . ' (no title)';

            if(empty($selected))
              echo "<option value='". $slug."' $selected>". $title ." | ". $page->post_type . " | ". $post_name ." </option>";
          }
          echo "</select></div>";
          if(!empty($page_variations)){
            //echo "<div id='variation-meta-wrapper'>";
            foreach($page_variations as $k => $page_variation){
              $label = $variation_meta[$k]['label'] ?? '';
            }
           // echo "</div>";
          }
      }
      else
        echo "<h5>Please add some pages then reload this page.</h5>";
    }

    function abst_get_archives(){
      $out = [];
      $post_types = get_post_types( array( 'public' => true ), 'objects' );

      foreach ( $post_types as $type ) {
        $out['post-type-archive-'.$type->name]=$type->label;
      }       

      $post_types = get_post_types( array( 'public' => true ), 'names' );
      $all_tags = array();

      foreach ( $post_types as $post_type ) {
          $taxonomies = get_object_taxonomies( $post_type, 'objects' );

          foreach( $taxonomies as $taxonomy ) {
              $terms = get_terms( array(
                  'taxonomy' => $taxonomy->name,
                  'hide_empty' => false,
              ) );

              if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
                  foreach ( $terms as $term ) {
                    if($taxonomy->publicly_queryable == true)
                      if($taxonomy->name == 'category')
                        $out['category-'.$term->slug] = $taxonomy->name . ': ' .$term->name;
                      else
                        $out['term-'.$term->slug] = $taxonomy->name . ': '.$term->name;
                  }
              }
          }
      }
      return $out;
    }


    function show_test_type($post){
      
      $test_type = get_post_meta($post->ID,'test_type',true);

      // output the radio boxes
      $full_page_test_selected = '';
      $ab_test_selected = '';

      if($test_type == 'ab_test')
      $ab_test_selected = 'checked';
      if($test_type == 'full_page')
        $full_page_test_selected = 'checked';

      echo '<div>
    <input type="radio" id="full_page" name="test_type" value="full_page" '. $full_page_test_selected.'>
    <label for="full_page" class="ab-shadow"><h5>Full Page</h5><p>Swap between unlimited pages or posts to see the best performer.</p></label>
  </div>
  <div>
    <input type="radio" id="ab_test" name="test_type" value="ab_test" '. $ab_test_selected.'>
    <label for="ab_test" class="ab-shadow"><h5>On Page Elements</h5><p>Compare different on-page elements to discover the best layout.</p></label>
  </div>
  <div>
    <p> <strong>Magic & Code tests available in Pro version</strong></p>
  </div>
  ';
    }

    function show_targeting_options($post){

      echo $this->show_login_targeting_options($post);

      $target_option_device_size = get_post_meta($post->ID,'target_option_device_size',true);
      $url_query = get_post_meta($post->ID,'url_query',true);
      $target_percentage = get_post_meta($post->ID,'target_percentage',true);
      
      echo '<h4><label for "bt_experiments_target_option_device_size">Device Size</strong></h4><select name="bt_experiments_target_option_device_size" id="bt_experiments_target_option_device_size">';
      echo '<option value="all">All Sizes</option>';
      echo '<option value="desktop" '. ((isset($target_option_device_size) && $target_option_device_size == 'desktop') ? 'selected' : '').'>Desktop (over 767px)</option>';
      echo '<option value="desktop_tablet" '. ((isset($target_option_device_size) && $target_option_device_size == 'desktop_tablet') ? 'selected' : '').'>Desktop + Tablet</option>';
      echo '<option value="tablet" '. ((isset($target_option_device_size) && $target_option_device_size == 'tablet') ? 'selected' : '').'>Tablet (between 479px and 767px)</option>';
      echo '<option value="tablet_mobile" '. ((isset($target_option_device_size) && $target_option_device_size == 'tablet_mobile') ? 'selected' : '').'>Tablet + Mobile</option>';
      echo '<option value="mobile" '. ((isset($target_option_device_size) && $target_option_device_size == 'mobile') ? 'selected' : '').'>Mobile (under 479px)</option>';
      echo '</select>';
      echo '<h4>URL filtering</h4><label for="bt_experiments_url_query">Query:</label>';
      echo '<BR><input type="text" id="bt_experiments_url_query" name="bt_experiments_url_query" style="width:100%;" placeholder="utm_source=Google" value="' . esc_attr( $url_query ) . '"  />';
      echo '<p>Test on traffic with matching URL query strings, use a * to search the entire URL for a specific string, start with "NOT " to exclude specific query strings</p><p class="small urlqueryexamples">EXAMPLES +</p><div class="target-example">*thanks will match any URL containing "thanks". Example matching values: */pl match any complete URL with the string /pl </div><div class="target-example">"utm_source" will match any URL query with the key "utm_source". Example matching values: ?utm_source ?utm_source=anything ?utm_source=somethingelse </div><div class="target-example">"utm_source=fb" will match only when the key and value is a match. Example matching values: ?utm_source=fb  </div><div class="target-example">"NOT ?licenceKey" will exclude any URL containing the string "?licenceKey" </div><BR>';
      echo '<div class="ab-target-percentage"><h4><label for="bt_experiments_target_percentage">Traffic allocation percentage</label></h4><p>Limit the number of site visitors that get tested by a percentage.</p><input type="number" min="1" max="100" id="bt_experiments_target_percentage" name="bt_experiments_target_percentage" style="width:100%;" placeholder="100" value="' . esc_attr( $target_percentage ) . '"  /><p id="percentage_description"></p></div>';
      echo '</div>';

    }

    function show_login_targeting_options( $post )
    {
      global $wp_roles;

      $allowed_roles = get_post_meta( $post->ID, 'bt_allowed_roles', true );
      $defaults = ['logout', 'subscriber','customer'];

      if( !is_array($allowed_roles) ) {
        $allowed_roles = $defaults;
      }

      echo '<div class="ab-targeting-roles"><h4>'. __('User roles', 'bt_experiment') .'</h4>';      
      echo "<p>Choose the user roles you want to test on. Usually best to just test on logged out (visitors)</p>";

      foreach ($wp_roles->roles as $role => $value) {
        echo '<label><input type="checkbox" '. ((in_array($role, $allowed_roles))? 'checked' : '') .' name="bt_allowed_roles[]" value="'. $role .'">'. $value['name'] .'</label><br>';
      }

      echo '<label><input type="checkbox" '. ((in_array('logout', $allowed_roles))? 'checked' : '') .' name="bt_allowed_roles[]" value="logout">Logged out users</label></div><BR><BR>';

    }
    
    
    function abst_delete_variation(){
      // if user has ability to edit bt_Experiments post type
      if (!current_user_can('edit_posts')) {
        return "You do not have opermission to edit.";
        die();
      }
      //get data
      $eid = intval($_POST['pid']);
      $variation = sanitize_text_field($_POST['variation']);
      //get observations
      $obs = maybe_unserialize(get_post_meta($eid, 'observations', true));
      if(!empty($obs)  && !empty($obs[$variation]))
      {
        unset($obs[$variation]);
        update_post_meta($eid, 'observations', $obs);
        echo "variation deleted";
        die();
      }
      else if (!empty($obs))
      {
        foreach ($obs as $key => $value) {
          if(is_int($key))
          {
            //get post title from id
            $title = get_the_title($key);
            if($title == $variation)
            {
              unset($obs[$key]);
              update_post_meta($eid, 'observations', $obs);
              echo "Variation deleted";
              //end ajax response
              die();
            }
          }
        }
      }
      else
      {
        echo "Error deleting variation. Could not find variation."; //print_r($obs, true);
      }
      
    }

    function count_active_experiments() {
      $count_posts = wp_count_posts('bt_experiments');    
      return $count_posts->publish;
    }
    
    
    
    function convert_ab_split_test_shortcode($atts, $content = null) {
      // Extract attributes from the shortcode
      $attributes = shortcode_atts(array(
        'eid' => '',
        'var' => '',
        'wrap' => 'span'
      ), $atts);
      
      // Create the class attributes
      $class_eid = 'ab-' . esc_attr($attributes['eid']);
      $class_var = 'ab-var-' . esc_attr($attributes['var']);
      
      // Construct and return the HTML string
      return '<' . esc_attr($attributes['wrap']) . ' class="' . $class_eid . ' ' . $class_var . '">' . do_shortcode($content) . '</' . esc_attr($attributes['wrap']) . '>';
    }
    
/**
 * Identify the control/original variation key based on test type and naming rules.
 * - For magic tests: 'magic-0'
 * - For CSS/on-page tests: match defaultNames
 * - For full page tests: 'bt_experiments_full_page_default_page' if present
 * @param array $variations
 * @return string|null
 */
/**
 * Identify the control/original variation key based on test type and naming rules.
 * - For magic tests: 'magic-0'
 * - For CSS/on-page tests: match defaultNames
 * - For full page tests: get control key from post meta 'bt_experiments_full_page_default_page'
 * @param array $variations
 * @param WP_Post|null $test (optional) - the experiment post object
 * @return string|null
 */
function identify_control_variation($variations, $test = null) {
    // Magic test: magic-0 always control
    

    // Full page test: get control key from post meta
    if ($test && get_post_meta($test->ID, 'test_type', true) === 'full_page') {
        $default_page_key = get_post_meta($test->ID, 'bt_experiments_full_page_default_page', true);
        if ($default_page_key && isset($variations[$default_page_key])) {
            return $default_page_key;
        }
    }

    // CSS/on-page: match defaultNames
    $defaultNames = ['original','one','1','default','standard','a','control'];
    foreach ($variations as $key => $data) {
        $variationName = strtolower($key);
        foreach ($defaultNames as $d) {
            if ($variationName === $d) return $key;
        }
    }
    // Fallback: return nothing so it doesnt display uplift downlift stuff
    return false;
}


function show_experiment_results($test,$asTable = false){

  $pid = $test->ID;
  
  $observations = get_post_meta($pid,'observations',true);

  if(!empty($observations) ) // not empty
  {
    //filter observations, remove unsanitized
    $dirtyInputs = false;
    $cleanInputs = intval($_GET['abstCleanInputs'] ?? 0);
    foreach($observations as $key => $value){
      if(abst_sanitize($key) != $key){
        if($cleanInputs == 1)
          unset($observations[$key]);
        $dirtyInputs = true;
      }
    }
    if($dirtyInputs){
      if($cleanInputs == 1)
      {
        update_post_meta($pid,'observations', $observations);
        echo('<h4>Unsanitized key removal finished, please refresh the page.</h4>');
      }
      else
      {
        //construct clean inputs href
        $cleanHref = esc_url($_SERVER['REQUEST_URI'] . "&abstCleanInputs=1");
        echo('<h4 class="error">Unsanitized keys found (disallowed characters are: @ # $ % ^ &amp; *). <BR/><a href="'.$cleanHref.'">Click here to remove them</a></h4>');
      }
    }
  }
  $titles = [];
  $percentage_target = apply_filters('ab_complete_confidence', 95);
  $test_type = get_post_meta($test->ID,'test_type',true);
  $goals = get_post_meta($test->ID,'goals',true);
  $test_winner = get_post_meta($test->ID,'test_winner',true);
  $test_age = intval((time() - get_post_time('U',true,$test))/60/60/24); 
  require_once 'includes/statistics.php';
  $observations = bt_bb_ab_split_test_analyzer($observations,$test_age);


  $conversion_text = 'Conversion Rate';
  $conversion_t = 'Conversions';
  
  $test_type_label = get_post_meta($test->ID,'test_type',true);
  if($test_type_label == 'full_page') $test_type_label = 'Full Page Test';
  if($test_type_label == 'magic') $test_type_label = 'Magic Test';
  if($test_type_label == 'ab_test') $test_type_label = 'On-Page Test';
  if($test_type_label == 'css_test') $test_type_label = 'Code Test';
  if(empty($test_type_label)) $test_type_label = 'Status';
  echo "<h3>" . $test_type_label . ": <span class='status'>";
  if($test->post_status == 'publish')
  {
      echo "Collecting data";
  }
  else if($test->post_status == 'complete')
    echo "Complete";
  else
    echo "Paused";
  echo "</span></h3>"; // close h3 here

  // Output experiment status and the rest of the block-level content outside the heading

  
  $experiment_status = "<p class='experiment-status'>You need more visits to your " . BT_AB_TEST_LITE_WL_ABTEST . " to find the winner. ";
  
  $remaining = '';
  if( !empty($observations['bt_bb_ab_stats']['likelyDuration'])){
    $remaining = "</p><p>About " . ($observations['bt_bb_ab_stats']['likelyDuration'] - $test_age) . " days remaining.</p>";
  }

  // Friendlier message for very new tests
  if ($test_age < 2) {
    $experiment_status = "<p class='experiment-status'>🧪 Your test is just getting started. Give it a day or so to collect more data and check back soon for insights!</p>";
  } else {
      $experiment_status = "<p class='experiment-status'>You need more visits to your " . BT_AB_TEST_LITE_WL_ABTEST . " to find the winner. " . $remaining . "</p>";
  }
  $goodStats = true;

  if( empty($observations) ) // empty
  {
    $goodStats = false;
  }
  else
  {
    //check results are decent
    foreach($observations as $m => $a)
    {
      if(!empty($a['visit']))
        if($a['visit'] < 1 || $a['conversion'] < 0)
        {
          $goodStats = false;
          break;
        }
    }
  }

  if($goodStats)
  {        
    //if its a full page test then loop through all observationbs
    if($test_type  == 'full_page')
    {
      foreach($observations as $key => $value)
      {
        if(is_numeric($key))
          continue;
        // its a text slug, get the post 
        $qpo = get_posts(array
          (
              'name'   => $key,
              'post_type'   => 'any',
              'post_per_page' => 1
          ));
        if(!empty($qpo)) // we found it, does it already exist?
        {
          $page_id = $qpo[0]->ID;
          if(!empty($observations[$page_id]))
          {
            (int)$observations[$page_id]['visit'] += (int)$observations[$key]['visit'];
            (float)$observations[$page_id]['conversion'] += (float)$observations[$key]['conversion'];
          }
          else
          {
            $observations[$page_id] = $value;
          }
          unset($observations[$key]);
        }
      }
    }

    require_once 'includes/statistics.php';
    $observations = bt_bb_ab_split_test_analyzer($observations,$test_age);
  }

  // if we have results
  if(is_array($observations))
  {
    if(isset($observations['bt_bb_ab_stats']))
    {
      //enough visits?
      $notenoughvisits = '';
      foreach($observations as $key => $var){
        if($key == "bt_bb_ab_stats" || $key == "likelyDuration")
          continue;

        $post_id_data = get_post($key);
        if( !is_null($post_id_data))
          $key = $post_id_data->post_title; 

        //if variation_meta has alabel for it
        $variation_meta = get_post_meta($test->ID,'variation_meta',true);
        if(isset($variation_meta[$key]['label'])){
          $key = $variation_meta[$key]['label'];
        }

      }

        $likeylwinner = $observations['bt_bb_ab_stats']['best'] ?? '';
        $likelyDuration = $observations['bt_bb_ab_stats']['likelyDuration'] ?? '0';
        $likelywinnerpercentage = $observations['bt_bb_ab_stats']['probability'];
      
      
      // Ensure likelyDuration is always a valid number, default to 0
      $likelyDuration = intval($likelyDuration) ?: 0;
      $timediff = human_time_diff( get_post_time('U',true,$test), time());
      
      // Output chart data as JavaScript variable
      echo '<script>var testAge = ' . $test_age . '; var likelyDuration = ' . $likelyDuration . ';</script>';
                
      if($notenoughvisits !== '')
        $experiment_status .= $notenoughvisits;
      
      if( ($likelywinnerpercentage >= $percentage_target) && ($notenoughvisits == '') )
      {
        // Calculate uplift and improvement metrics
        $winner_data = null;
        $control_data = null;
        $uplift_message = '';
        $avoided_loss_message = '';
        
        // Find winner and collect all variations
        $all_variations = [];
        foreach($observations as $key => $data) {
          if($key === 'bt_bb_ab_stats') continue;
          if($key === $likeylwinner) {
            $winner_data = $data;
          }
          $all_variations[$key] = $data;
        }
        // Identify the control variation using proper naming rules
        $control_variation_key = $this->identify_control_variation($all_variations, $test);
        $control_data = $all_variations[$control_variation_key] ?? null;
        $variation_meta = get_post_meta($test->ID,'variation_meta',true);

        // Skip uplift calculations if we can't identify control variation
        // But still show the winner announcement
        if($control_variation_key === false) {
          $uplift_message = '';
          $annual_impact_message = '';
          $avoided_loss_message = '';
          
          // Format winner label

          if(is_int($likeylwinner)) 
          {
            $winner_label = get_the_title($likeylwinner);
            if(!$winner_label) {
              $winner_label = $likeylwinner;
            }
          
         } 
         else if (strpos($likeylwinner, 'magic-') === 0) 
         {
            $winner_display = str_replace('magic-', '', $likeylwinner);
            $winner_label = "Variation " . ['A (original)','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z'][$winner_display];
          } 
          else 
          {
            $winner_label = $likeylwinner;
          }

          if(isset($variation_meta[$likeylwinner]['label']))
          {
            $winner_label = $variation_meta[$likeylwinner]['label'];
          }

          $experiment_status = "<h4 class='bt_ab_warning'><div class='bt_ab_success'>"
            . "🎉 <strong>{$winner_label}</strong> is the winner with <strong>{$likelywinnerpercentage}% confidence</strong>"
            . "</div></h4><hr/>";
        }
        // Calculate uplift if we have winner and control data
        elseif($winner_data && $control_data) {
          $winner_rate = $winner_data['rate'];
          $control_rate = $control_data['rate'];
          
          if($control_rate > 0) {
            $uplift_percent = (($winner_rate - $control_rate) / $control_rate) * 100;
            $relative_improvement = $winner_rate / $control_rate;
            
            // Calculate annual projections based on current traffic
            $daily_visits = max($winner_data['visit'], $control_data['visit']) / max($test_age, 1);
            $annual_visits = $daily_visits * 365;
            $improvement_per_visit = $winner_rate - $control_rate;
            $annual_improvement = $improvement_per_visit * $annual_visits;
            
              // Conversion projection
              $annual_extra_conversions = round($annual_improvement);
              $formatted_conversions = number_format($annual_extra_conversions);

              // Suppress $0 uplift and $0 annual impact if control is winner and uplift is zero or negative
              if ($likeylwinner === $control_variation_key && (!isset($uplift_percent) || $uplift_percent <= 0.1)) {
                $uplift_message = '';
                $annual_impact_message = '';
              } else if (isset($uplift_percent) && $uplift_percent > 0.1) {
                $uplift_message = sprintf(
                  '<br/>📈 <strong>%.1f%% increase in conversion rate</strong> <em>(%.1fx better performance)</em>',
                  $uplift_percent,
                  $relative_improvement
                );
              }

              $annual_impact_message = sprintf(
                '<br/>🎯 <strong>Projected annual impact:</strong> Extra <span style="color: #28a745; font-weight: bold; font-size: 1.1em;">%s conversions per year</span>',
                $formatted_conversions
              );
            

            if ($likeylwinner === $control_variation_key && count($all_variations) > 1) {
              $best_alt = null;
              foreach ($all_variations as $k => $obs) {
                if ($k === $control_variation_key || !isset($obs['rate'])) continue;
                $alt = (float)str_replace(['%', '$', ' ', ','], '', $obs['rate']);
                if ($best_alt === null || $alt > $best_alt) $best_alt = $alt;
              }
              if ($best_alt !== null) {
                $loss = ($control_rate > 0) ? (($control_rate - $best_alt) / $control_rate) * 100 : 0;
                $loss = max(0, $loss); // Never negative
                if ($loss > 0.1) {
                  $icon = '🛡️';
                  $avoided_loss_message = "<br/>$icon <span class='variation-loss' style='color:#0073aa;font-weight:bold;'>You avoided losing " . round($loss, 1) . "% conversions</span>";

                  // Calculate avoided loss for annual impact
                    $annual_avoided_loss = ($control_rate - $best_alt) * $annual_visits / 100;
                    $formatted_avoided_loss = number_format(round($annual_avoided_loss));
                    $annual_impact_message = sprintf(
                      '<br/>🎯 <strong>Projected annual impact:</strong> You avoided losing <span style="color: #28a745; font-weight: bold; font-size: 1.1em;">%s conversions per year</span>',
                      $formatted_avoided_loss
                    );                  
                }
              }
            }

            // Winner label and confidence
            if(is_int($likeylwinner)) {
              $winner_label = get_the_title($likeylwinner);
              if(!$winner_label) {
                $winner_label = $likeylwinner;
              }
            } else if (strpos($likeylwinner, 'magic-') === 0) {
              $winner_display = str_replace('magic-', '', $likeylwinner);
              $winner_label = "Variation " . ['A (original)','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z'][$winner_display];
            } else {
              $winner_label = $likeylwinner;
            }

            //if variation meta <label>
            if (isset($variation_meta) && isset($variation_meta[$likeylwinner]) && isset($variation_meta[$likeylwinner]['label'])) {
              $winner_label = $variation_meta[$likeylwinner]['label'];
            }
            
            // Custom combined message for control winner with no uplift
            if ($likeylwinner === $control_variation_key && (empty($uplift_message) || $uplift_percent <= 0.1)) {
              $avoided_loss_combined = '';
              // Try to extract percent and annual avoided loss for summary
              if (isset($loss) && $loss > 0.1) {
                $context_label = 'conversions';
                $icon = '✔️';
                $annual_loss_str = '';
                if (isset($annual_avoided_loss) && $annual_avoided_loss > 0) {
                  $formatted_avoided_loss = number_format(round($annual_avoided_loss));
                  $annual_loss_str = " ({$formatted_avoided_loss} conversions per year)";
                }
                $avoided_loss_combined = "$icon You avoided losing " . round($loss, 1) . "% $context_label" . $annual_loss_str;
              }
              $experiment_status = "<h4 class='bt_ab_warning'><div class='bt_ab_success'>"
                . "🎉 <strong>{$winner_label}</strong> is the winner with <strong>{$likelywinnerpercentage}% confidence</strong><br>"
                . "❌ No improvement from this test<br>"
                . ($avoided_loss_combined ? $avoided_loss_combined : '')
                . "</div></h4><hr/>";
            } else {
              // Default winner summary
              $experiment_status = "<h4 class='bt_ab_warning'><div class='bt_ab_success'>"
                . "🎉 <strong>{$winner_label}</strong> is the winner with <strong>{$likelywinnerpercentage}% confidence</strong>"
                . "$uplift_message"
                . "$annual_impact_message" // will be empty if avoided loss annual impact is set
                . "$avoided_loss_message"
                . "</div></h4><hr/>";
            }
          }
        }
        
      }
      else
      {
        // Enhanced messaging for non-winning variations (test still running)
        $leading_variation = '';
        $leading_rate = 0;
        $leading_confidence = 0;
        $total_visits = 0;
        $total_conversions = 0;
        $control_data = null;
        
        // Find current leading variation and collect all variations
        $all_variations_nonwinner = [];
        foreach($observations as $key => $data) {
          if($key === 'bt_bb_ab_stats') continue;
          $total_visits += $data['visit'];
          $total_conversions += $data['conversion'];
          $current_rate = $data['rate'];
          if($current_rate > $leading_rate) {
            $leading_rate = $current_rate;
            $leading_variation = $key;
            $leading_confidence = $data['probability'] ?? 0;
          }
          $all_variations_nonwinner[$key] = $data;
        }
        // Identify the control variation using proper naming rules
        $control_variation_key = $this->identify_control_variation($all_variations_nonwinner, $test);
        $control_data = $all_variations_nonwinner[$control_variation_key] ?? null;
        
        // Format leading variation name
        //if it copntains magic- then do the thiong else just use its nameSZ
          $leading_display = $leading_variation;
          if(is_int($leading_display)) {
            //get post name by id
            $leading_label = get_the_title($leading_display);
          } else {
            $leading_label = $leading_variation;
          }
        
        //if its set in variation meta then use that
        if($variation_meta)
        {
          foreach($variation_meta as $key => $value)
          {
            if($key == $leading_variation)
            {
              if(!empty($value['label']))
                $leading_label = $value['label'];
            }
          }
        }
        
        // Calculate uplift for leading variation vs control
        $uplift_message = '';
        if($control_data && $leading_rate > 0) {
          $control_rate = $control_data['rate'];
          
          if($control_rate > 0) {
            $uplift_percent = (($leading_rate - $control_rate) / $control_rate) * 100;
            if($uplift_percent > 0) {
                $uplift_message = sprintf(' with an uplift of %.1f%% in conversion rate.', $uplift_percent);
              
            }
          }
        }
        
        // Combine confidence and estimated time remaining into a single line
        $remaining_message = '';
        if($likelyDuration && $likelyDuration > $test_age) {
          $days_remaining = $likelyDuration - $test_age;
          $conf_target = round($percentage_target, 1);
          if($days_remaining > 1) {
            $remaining_message = "<br/>⏱️ Time remaining: About {$days_remaining} days to reach {$conf_target}% confidence.";
          } else {
            $remaining_message = "<br/>⏱️ Nearly complete! Results expected soon ({$conf_target}% confidence).";
          }
        }
        
        // Calculate projected impact potential (uplift vs control)
        $projection_message = '';
        if($total_visits > 0 && $test_age > 0 && $control_data) {
          $daily_visits = $total_visits / $test_age;
          $annual_visits = $daily_visits * 365;

          // DEBUG: Add debug info to see what's happening
          //$debug_info = "<br/><small style='color:#666;'>DEBUG: Leading='{$leading_variation}', Control='{$control_variation_key}', LeadingRate={$leading_rate}</small>";

          // Find second-best performing variation for "amount saved" calculation
          $all_rates = [];
          foreach($all_variations_nonwinner as $key => $data) {
            $current_rate = $data['rate'];
            $all_rates[$key] = $current_rate;
          }
          // Sort by rate descending to get best performers first
          arsort($all_rates);
          $sorted_variations = array_keys($all_rates);
          
          // Get second-best rate (index 1, since index 0 is the leading/best)
          $second_best_rate = 0;
          $second_best_variation = '';
          if(count($sorted_variations) >= 2) {
            $second_best_variation = $sorted_variations[1];
            $second_best_rate = $all_rates[$second_best_variation];
          }

          if($leading_rate > 0) {
            // Conversion uplift
            $control_rate = $control_data['rate'];
            $annual_leading_conversions = ($leading_rate / 100) * $annual_visits;
            $annual_control_conversions = ($control_rate / 100) * $annual_visits;
            $annual_uplift = $annual_leading_conversions - $annual_control_conversions;
            
            // Calculate amount saved vs second-best performer
            $annual_second_best_conversions = ($second_best_rate / 100) * $annual_visits;
            $annual_saved = $annual_leading_conversions - $annual_second_best_conversions;
            
            //$debug_info .= "<br/><small style='color:#666;'>DEBUG: AnnualUplift={$annual_uplift}, ControlRate={$control_rate}, SecondBestRate={$second_best_rate}, Saved={$annual_saved}</small>";
            
            if ($annual_uplift > 0) {
              $formatted_uplift = number_format(round($annual_uplift));
              $projection_message = "<br/>💡 <strong>Potential uplift:</strong> {$formatted_uplift} extra conversions per year vs original.";
            } elseif ($annual_saved > 0 && $leading_variation === $control_variation_key && $second_best_rate > 0) {
              // Amount saved by not switching (when control is leading and performing better than second-best)
              $formatted_avoided_loss = number_format(round($annual_saved));
              $projection_message = "<br/>💰 <strong>Amount saved by not switching:</strong> {$formatted_avoided_loss} conversions per year vs alternatives.";
            }
          }
          // Add debug info to projection message
          //$projection_message .= $debug_info;
        }
        
        // Enhanced status message for ongoing tests
        if(!empty($leading_label)){
          $experiment_status = sprintf(
            '<h4 class="bt_ab_warning">🧪 %s is leading%s%s%s</h4><hr/>',
            $leading_label,
            $uplift_message,
            $projection_message,
            $remaining_message
          );
        }
      }
      unset($observations['bt_bb_ab_stats']);
    }
      
    echo $experiment_status;

    // IF ANY GOALS ARE SET THEN add column/div for goals with a dropdown to choose between available goals
    $goalsHtml = '';
    $goalsTableHead = '';



    // Determine column header based on conversion style
    $chance_column_header ='Chance of winning';
    
    if($asTable)
      echo '<table style="width:100%; max-width:800px;" border="0" cellspacing="5px" cellpadding="5px"> <thead><tr>  <th style="text-align: center;">Version Title</th>  <th style="text-align: center;">'.$conversion_text.'</th>  <th style="text-align: center;">'.$chance_column_header.'</th> <th style="text-align: center;">Visits</th> <th style="text-align: center;">Conversions</th> </tr></thead><tbody>';

    $titles = array();
    $variation_meta = get_post_meta($pid,'variation_meta',true);

      if(is_array($observations))
      {
      //remove observations with key ''
        $saveFix = false;

        if( array_key_exists('', $observations))
        {
          abst_log('Removing observation with EMPTY key ""');
          unset($observations['']);
          $saveFix = true;
        }

        // if array key 0 exists and 0['visits' = 0 ten unset 
        if(array_key_exists(0, $observations) && $observations[0]['visit'] == 0)
        {
          abst_log('Removing observation with key "0" and no visits');
          unset($observations[0]);
          $saveFix = true;
        }

        // REMOVE CONVERSIONS WITH NO VISITS
        foreach($observations as $mk => $mv)
        {
          if($mv['conversion'] >= 0 && $mv['visit'] == 0)
          {
            abst_log('Removing observation with key "' . $mk . '" and no visits');
            unset($observations[$mk]);
            $saveFix = true;
          }
        }

        if($saveFix)
        {
          abst_log('Saving fixed observations - this should only fire 1 time per old test');
          update_post_meta($test->ID, 'observations', $observations);
        }
      }
    // END CLEAN DATA FROM WEIRD API CALLS ETCEND CLEAN DATA END CLEAN DATA END CLEAN DATA END CLEAN DATA END CLEAN DATA END CLEAN DATA END CLEAN DATA END CLEAN DATA END CLEAN DATA END 


    //sort results by conversion rate
    uasort($observations, array($this,"cmp_by_ConversionRate"));
    foreach($observations as $mk => $mv)
    {
      $okey = $mk;
      if( empty($mv['probability']) ) {
        $mv['probability'] = 0;
      }

      //skip if its the stats
      if($mk === 'bt_bb_ab_stats')
        continue;

      //if its an id
      if(is_numeric($mk))
      {
        $post_id_data = get_post($mk);

        if( !is_null($post_id_data))
        {
          $mk = $post_id_data->post_title;
          if(in_array($mk,$titles))
            $mk = $post_id_data->post_name;
          $titles[] = $mk;
        }

      }

      if(isset($variation_meta[$okey]['label']) && !empty($variation_meta[$okey]['label'])){
        $mk = $variation_meta[$okey]['label'];
      }
      


      if($mv['probability'] > apply_filters('ab_complete_confidence', 95) )
        $class = "testwinner";
      else
        $class = "no";


        if($mv['rate'] <= 0)
          $mv['rate'] = "0%";
        else
          $mv['rate'] = round($mv['rate'],1) . "%";
      

      if(!$asTable)
      {
        // Calculate uplift for this variation vs control
        $uplift_html = '';
        if($class === 'testwinner' && isset($observations['magic-0'])) {
          // Ensure numeric conversion for rate values
          $control_rate_raw = $observations['magic-0']['rate'];
          $this_rate_raw = $mv['rate'];
          $control_rate_clean = (float)str_replace(['%', '$', ','], '', $control_rate_raw);
          $this_rate_clean = (float)str_replace(['%', '$', ','], '', $this_rate_raw);
          $control_rate =  $control_rate_clean;
          $this_rate = $this_rate_clean;
          // If winner is not control, show uplift
          if($okey !== 'magic-0' && $control_rate > 0 && $this_rate > 0) {
            $uplift = (($this_rate - $control_rate) / $control_rate) * 100;
            if($uplift > 0) {
              $uplift_html = "<div class='variation-uplift'>+" . round($uplift, 1) . "% uplift</div>";
            }
          }
          // If control is winner, show avoided loss vs next best
          if ($okey === 'magic-0' && $control_rate > 0) {
            $best_alt = null;
            foreach ($observations as $k => $obs) {
              if ($k === 'magic-0' || !isset($obs['rate'])) continue;
              $alt = (float)str_replace(['%', '$', ' ', ','], '', $obs['rate']);
              if ($best_alt === null || $alt > $best_alt) $best_alt = $alt;
            }
            if ($best_alt !== null) {
              $loss = ($control_rate > 0) ? (($control_rate - $best_alt) / $control_rate) * 100 : 0;
              $loss = max(0, $loss); // Never negative
              $context_label = 'conversions';
              $annual_avoided_loss = 0;
              if ($total_visits > 0 && $test_age > 0) {
                $daily_visits = $total_visits / $test_age;
                $annual_visits = $daily_visits * 365;
                  $annual_control_conv = ($control_rate / 100) * $annual_visits;
                  $annual_bestalt_conv = ($best_alt / 100) * $annual_visits;
                  $annual_avoided_loss = $annual_control_conv - $annual_bestalt_conv;
                  if ($annual_avoided_loss > 0) {
                    $avoided_loss_str = "<br><span class='avoided-loss-annual'>(" . number_format(round($annual_avoided_loss)) . " annual conversions protected)</span>";
                  } else {
                    $avoided_loss_str = '';
                  }
                
              } else {
                $avoided_loss_str = '';
              }
              $uplift_html = "<div class='variation-loss'>You avoided losing " . round($loss, 1) . "% $context_label{$avoided_loss_str}</div>";
            }
          }
        }
         
      //   $image_html = '';
      //  if(isset($variation_meta[$okey]['image']) && !empty($variation_meta[$okey]['image'])){
      //    $image_html = "<div class='results-image'><img src='".esc_url($variation_meta[$okey]['image'])."' alt='".esc_attr($mk)."'></div>";
      //  } else {
      //    $image_html = "<div class='results-image'></div>";
      //  }
       // echo "<div class='results_variation ".$class."'><div class='title'>".$mk."{$uplift_html}</div>";
        //echo "<div class='results-visits'>".$mv['visit']."<span> ▾</span></div>";
        //echo "<div class='results-conversions'>".$mv['conversion']."<span> ▾</span></div>";

        //echo "<div class='results-conversion-rate'>".$mv['rate']."</div>";
        
//        $mv['probability'] = $mv['probability'] . "%";
        
        if(isset($mv['probability']) )  
        {
  //          echo "<div class='results-likely'>".$mv['probability']."</div>";
          
        }
    //    echo "</div>";

        //echo "<div class='seen-on'><div><p>Visits</p>";
        //if(@!empty($mv['location']['visit'])){
          //foreach($mv['location']['visit'] as $visit){
              //if(is_numeric($visit))
                //echo "<a target='_blank' href='".get_permalink($visit)."?abtv=".$mk."&abtid=".$pid."'>".get_the_title($visit)."</a>";
              //else
                //echo "<span ><small>".$visit."</small></span>";
              
          }
        }
        //echo "</div><div><p>Conversions</p>";
        //if(@!empty($mv['location']['conversion'])){
          //foreach($mv['location']['conversion'] as $visit){
              //if(is_numeric($visit))
                //echo "<a target='_blank' href='".get_permalink($visit)."?abtv=".$mk."&abtid=".$pid."'>".get_the_title($visit)."</a>";
              //else
                //echo "<span ><small>".$visit."</small></span>";
          //}  
        //}
          
        //echo "</div></div>";
//      }
      //else
      //{
        // Determine what to display in the "Chance of winning" column
          if(!intval($mv['visit'])) {
            $chance_of_winning = "✕";
          } else if(isset($mv['probability'])) {
            $chance_of_winning = $mv['probability'] . "%";
          } else {
            $chance_of_winning = "0%";
          }
        
        echo "<tr><td style='text-align: center;'>$mk</td>";
        echo "<td style='text-align: center;'>".$mv['rate']."</td>";
        echo "<td style='text-align: center;'>".$chance_of_winning."</td>";
        echo "<td style='text-align: center;'>".$mv['visit']."</td>";
        echo "<td style='text-align: center;'>".$mv['conversion']."</td></tr>";
        
      //}
    //} // end foreach meta
      

    //new table
    //echo a dropdown to select the goal
    echo '<div class="abst-goal-select-container"><h3>'.$this->get_experiment_conversion_summary($pid).'</h3></div>';
    echo '<div id="abst-results-table"></div>';
    //close table
    if($asTable)
      echo '</tbody></table>';
    // current date plus days remaining in dd month yyy
    // chart
    if(!empty($observations)  )// include chart of data exists
    {
      echo '<canvas id="abtestChart" width="500px" height="400px"></canvas>';
      
      if(!empty($likelyDuration) )
      {
        $expectedEnd = date('F jS Y', strtotime('+'. $likelyDuration . ' days'));
        $expectedEnd = 'Projected end date: ' . $expectedEnd;
        echo '<div id="expectedEnd">' . $expectedEnd . '</div>';
      }
      else
        $likelyDuration = 0;

      //chart data as json - for tables and charts
      $obs = $observations;
      $observations = [];
      $observations['observations'] = $obs;
      foreach($observations['observations'] as $key => $value) {

        if(!isset($observations['observations'][$key]['variation_meta'])) {
          $observations['observations'][$key]['variation_meta'] = [];
        }
        if(is_int($key)){
          //if no label then set it to the post title
          $observations['observations'][$key]['variation_meta']['label'] = get_the_title($key);
          $observations['observations'][$key]['variation_meta']['slug'] = get_post_field('post_name', $key);
        }

        // if 0 visits (legacy bug) then remove
        if($value['visit'] == 0)
          unset($observations['observations'][$key]);
        
        
        
        // Add statistical data
        if(isset($value['probability'])) {
          $observations['observations'][$key]['chance_of_winning'] = $value['probability'];
        }

        //add weight
        $meta = get_post_meta($pid, 'variation_meta', true);
        if(isset($meta[$key]['weight']))
          $observations['observations'][$key]['variation_meta']['weight'] = $meta[$key]['weight'];

      }

      
      //include variation_meta 
      $variation_meta = get_post_meta($pid, 'variation_meta', true);
      if(!empty($variation_meta)) {
        foreach($variation_meta as $key => $value) {
          
          if(!empty($value['label']))
            $observations['observations'][$key]['variation_meta']['label'] = $value['label'];
          if(!empty($value['slug']))
            $observations['observations'][$key]['variation_meta']['slug'] = $value['slug'];
          if(!empty($value['weight']))
            $observations['observations'][$key]['variation_meta']['weight'] = $value['weight'];
        }
      }
      
      // Add test winner information
      $test_winner = get_post_meta($pid, 'test_winner', true);
      if(!empty($test_winner)) {
        $observations['test_winner'] = $test_winner;
      }
      
      // Add test type information
      $test_type = get_post_meta($pid, 'test_type', true);
      if(!empty($test_type)) {
        $observations['test_type'] = $test_type;
      }
      
      // Add conversion style information
      $conversion_style = get_post_meta($pid, 'conversion_style', true);
      if(!empty($conversion_style)) {
        $observations['conversion_style'] = $conversion_style;
      }
      

      
      // Add additional relevant test configuration data
      $target_percentage = get_post_meta($pid, 'target_percentage', true);
      if(!empty($target_percentage)) {
        $observations['target_percentage'] = $target_percentage;
      }
      $control_variation = $this->identify_control_variation($observations['observations'], get_post($pid));
      $observations['control_variation'] = $control_variation;
      
      $conversion_text = get_post_meta($pid, 'conversion_text', true);
      if(!empty($conversion_text)) {
        $observations['conversion_text'] = $conversion_text;
      }
      
      $full_page_default_page = get_post_meta($pid, 'bt_experiments_full_page_default_page', true);
      if(!empty($full_page_default_page)) {
        $observations['full_page_default_page'] = $full_page_default_page;
      }
      
      $page_variations = get_post_meta($pid, 'page_variations', true);
      if(!empty($page_variations)) {
        $observations['page_variations'] = $page_variations;
      }

      
      $goals = get_post_meta($pid, 'goals', true);
      if(!empty($goals)) {
        $observations['goals'] = $goals;
      }
      
      $webhook_url = get_post_meta($pid, 'webhook_url', true);
      if(!empty($webhook_url)) {
        $observations['webhook_url'] = $webhook_url;
      }
      
      $magic_definition = get_post_meta($pid, 'magic_definition', true);
      if(!empty($magic_definition)) {
        $observations['magic_definition'] = $magic_definition;
      }
      
      
      // Add comprehensive statistical data from bt_bb_ab_stats
      if(isset($observations['bt_bb_ab_stats'])) {
        $observations['statistical_data'] = $observations['bt_bb_ab_stats'];
      }
      
      // Add control variation information
      if(!empty($test_type)) {
        
        //create new observations array without bt_bb_ab_stats
        $all_variations = [];
        foreach($observations as $key => $data) {
          if($key === 'bt_bb_ab_stats') continue;
          $all_variations[$key] = $data;
        }


        $control_variation_key = $this->identify_control_variation($all_variations, $test);
        if(!empty($control_variation_key)) {
          $observations['control_variation'] = $control_variation_key;
        }

        // Add variation titles, links and slugs for all variations
        foreach($observations['observations'] as $var_key => $var_data) {
          $post_data = get_post($var_key);
          $visit = $var_data['location']['visit'][0] ?? false;
          if($visit) {
            $link_url = '';

            if(is_numeric($visit)) {
              // It's a post ID
              $link_url = get_permalink($visit);
            } else {
              // It's a string (like 'homepage', 'product-page', etc.)
              $link_url = home_url('/' . $visit);
            }
            
            if($link_url) {
              $observations['observations'][$var_key]['variation_meta']['link'] = '<a target="_blank" href="'.$link_url."?abtv=".$var_key."&abtid=".$pid.'">🔗</a>';
            }
          }

          if($post_data) {
            if(empty($observations['observations'][$var_key]['variation_meta']['label']))
              $observations['observations'][$var_key]['variation_meta']['label'] = $post_data->post_title;
            $observations['observations'][$var_key]['variation_meta']['slug'] = $post_data->post_name;
          }
          
        }
      }
      
      // Add test age and likely duration to main data object
      $observations['test_age'] = $test_age;
      $observations['likely_duration'] = $likelyDuration;
            
      // Add confidence target percentage
      $observations['confidence_target'] = $percentage_target;
    }
    }
    else
    { // give em a guide for getting going
      echo "<p>In order for ".BT_AB_TEST_LITE_WL_NAME." to officially declare a winner, you'll need to run some more traffic to your test page. This is where your data will be after your test gets some traffic.</p>";
    }
    
    // Output chart data as JavaScript variable
    if(!empty($observations)) {
      echo "<script>var abtestChartData = " . json_encode($observations) . ";</script>";
    }
  }

    
function cmp_by_ConversionRate($a, $b) {
  $rateA = isset($a["rate"]) ? $a["rate"] : 0;
  $rateB = isset($b["rate"]) ? $b["rate"] : 0;
  return $rateB - $rateA;
}
    
    function bt_experiments_inner_custom_box( $post ) {


      $eid = $post->ID;
      global $wp_post_types;
      // Add an nonce field so we can check for it later.
      wp_nonce_field( 'myplugin_inner_custom_box', 'bt_experiments_inner_custom_box_nonce' );
      /*
       * Use get_post_meta() to retrieve an existing value
       * from the database and use the value for the form.
       */
      $conversion_page = get_post_meta($post->ID,'conversion_page',true); // conversioion page id or special name for custom val
      $conversion_url = get_post_meta( $post->ID, 'conversion_url', true );
      $conversion_time = get_post_meta($post->ID,'conversion_time',true);
      $conversion_scroll = get_post_meta($post->ID,'conversion_scroll',true);
      $conversion_selector = get_post_meta($post->ID,'conversion_selector',true);
      $conversion_link_pattern = get_post_meta($post->ID,'conversion_link_pattern',true);
      $conversion_text = get_post_meta($post->ID,'conversion_text',true);
      $allPublicPosts = abst_get_all_posts();

      $select = "<select id='bt_experiments_conversion_page' name='bt_experiments_conversion_page' class=''>";
      $select .= "<option value=''>Select Conversion Trigger...</option>";
      $select .="<option value='page'>Page or Post Visit</option>";
      $select .="<option value='text'>Text on Page</option>";
      $select .="<option value='selector'>Element Click</option>";
      $select .="<option value='link'>Link Click</option>";
      $select .="<option value='time'>Time Active</option>";
      $select .="<option value='scroll'>Scroll Depth</option>";
      $select .="<option value='url'>URL</option>";
      $select .="<option value='block'>Conversion Block / Module / Element Class</option>";

      //woo pizza
      $woo_opts = '';

      if (defined('WC_VERSION')){

        $woo_pages = $this->get_woo_pages();
        foreach($woo_pages as $name => $page)
        {
          if(!empty($page))
          {
            if(empty($woo_opts))      
              $woo_opts .= "</optgroup><optgroup label='UPGRADE TO PRO for WooCommerce'>";
          }
        }
        $woo_opts .= "</optgroup>";
        // end woo
      }

      //' if surecart exists
      if ( class_exists( 'SURECART' ) ) {

          $woo_opts .= "</optgroup><optgroup label='UPGRADE TO PRO for SureCart'>";
          $woo_opts .= "</optgroup>";
      }
      if(class_exists( 'Easy_Digital_Downloads' )){
          $woo_opts .= "</optgroup><optgroup label='UPGRADE TO PRO for Easy Digital Downloads'>";
          $woo_opts .= "</optgroup>";
      }


      if ( class_exists( 'WPPIZZA' ) ) {
              $woo_opts .= "</optgroup><optgroup label='UPGRADE TO PRO for WP Pizza'>";
          $woo_opts .= "</optgroup>";
      }

      $select .= $woo_opts;

      echo '<h4>Goal</h4>';
      echo '<p><label for="bt_experiments_conversion_url">';
      _e( "Define a successful conversion. Something like a thank-you page, or a checkout complete page.", 'bt-bb-ab' );
      echo '</label><p/>';

      $select .= "</select>";

      echo $select;

      // ------------------- on page elements mode --------------------------_______

    echo " <div class='test-conversion-tags-mode'><h4>Conversion Blocks, Classes, Modules</h4><ol><li>Edit the page you would like to create your test conversion goal.</li><li>Add the conversion classes below to your page, or use a Block/module to trigger a conversion on page load.</li></ol>";
      echo "<div class='test-class-info test-conversion-info'><input type='text' value='ab-".$post->ID ." ab-convert'/></div></div>";
      
      // ------------------- conversion page  mode --------------------------_______
      
      echo '<label class="conversion_page_selector" for="bt_experiments_conversion_page_selector"><strong>Conversion Page</strong><BR>Choose the page that will trigger your test conversion on page load.<BR>';
      echo '<select class="conversion_page_selector"  id="bt_experiments_conversion_page_selector" name="bt_experiments_conversion_page_selector" placeholder="Choose Page">';
      $select = '';
      $foundConversion = false;
      foreach($allPublicPosts as $post)
      {
        $selected = '';
        if($post->ID == $conversion_page){
          $selected = 'selected';
          $foundConversion = true;
        }
        $select .="<option value='".$post->ID."' ".$selected.">".$post->post_title.": " . $post->post_type . ' '.$post->post_name."</option>";
      }
      if(!$foundConversion)
      {
        $cPage = get_post($conversion_page);
        if(!empty($cPage)){
          $select .="<option value='".$conversion_page."' selected >".$cPage->post_title.": " . $cPage->post_type . ' '.$cPage->post_name."</option>";
        }
      }
      echo $select;
      echo '</select></label>';
      echo '<a href="" id="bt_experiments_conversion_page_preview" target="_blank">view page »</a>';

      // ------------------- conversion URL mode --------------------------_______

      echo '<label class="conversion_url_input" for="bt_experiments_conversion_url">Conversion URL contains. Does not require the complete URL<BR><small class="conversion_url_input">e.g. <code>thank-you</code> would convert on any URL containing it, such as <code>https://yourwebsite.com/thank-you/email</code></small></label><input class="conversion_url_input" type="text" id="bt_experiments_conversion_url" name="bt_experiments_conversion_url" placeholder="your-conversion-page?maybe=this#orthat" value="' . esc_attr( $conversion_url ) . '"  />';
     
      // ------------------- conversion click selector --------------------------_______
        // since in 1.3.2
      echo '<label class="conversion_selector_input" for="bt_experiments_conversion_selector">CSS Selector that when clicked will trigger the conversion. <br/>Add our class <code>ab-click-convert-'.$eid.'</code> or define your own below.</label><input class="conversion_selector_input" type="text" id="bt_experiments_conversion_selector" name="bt_experiments_conversion_selector" placeholder="#yourthing" value="' . esc_attr( $conversion_selector ) . '"  />';
    
      // ------------------- conversion link click pattern --------------------------_______
      echo '<label class="conversion_link_pattern_input" for="bt_experiments_conversion_link_pattern">Link: When a link (<code>&lt;a&gt;</code>) with an href containing this pattern is clicked, a conversion is triggered.<br/>Example: <code>thank-you</code> or <code>https://youtube.com/</code></label><input class="conversion_link_pattern_input" type="text" id="bt_experiments_conversion_link_pattern" name="bt_experiments_conversion_link_pattern" placeholder="thank-you" value="' . esc_attr( $conversion_link_pattern ) . '"  style="display:none;" />';

       // ------------------- conversion scroll depth --------------------------_______
        echo '<label class="conversion_scroll_input" for="bt_experiments_conversion_scroll">Scroll depth percentage before a conversion is triggered.</label><input class="conversion_scroll_input" type="number" id="bt_experiments_conversion_scroll" name="bt_experiments_conversion_scroll" placeholder="50" min="0" max="100" value="' . esc_attr( $conversion_scroll ) . '"  /><span class="conversion_scroll_input" > %</span>';

       // ------------------- conversion time on site --------------------------_______
        // since in 1.3.2
        echo '<label class="conversion_time_input" for="bt_experiments_conversion_time">Number of seconds user will be active on site before a conversion is triggered.</label><input class="conversion_time_input" type="number" id="bt_experiments_conversion_time" name="bt_experiments_conversion_time" placeholder="30" value="' . esc_attr( $conversion_time ) . '"  /><span class="conversion_time_input" > seconds</span>';

       // ------------------- conversion time on site --------------------------_______
        // since in 1.6.2
        echo '<label class="conversion_text_input" for="bt_experiments_conversion_text">Will convert when this text is visible on the page. Case sensitive.</label><input class="conversion_text_input" type="text" id="bt_experiments_conversion_text" name="bt_experiments_conversion_text" placeholder="" value="' . esc_attr( $conversion_text ) . '"  /></div>';

      //if conversion page is an integer
      echo "<script data-cfasync='false' data-no-optimize='1'>
      jQuery(document).ready(function() {  
        var selectval = '". $conversion_page ."';
        jQuery('#bt_experiments_conversion_page option[value=\'". $conversion_page ."\']').first().attr('selected', 'selected');";
        if(is_numeric($conversion_page))
          echo "jQuery('.conversion_page_selector').show();
          jQuery('#bt_experiments_conversion_page option[value=\'page\']').first().attr('selected', 'selected');";

echo "    if( selectval !== 'url' ) 
          {
            jQuery('.conversion_url_input').hide();            
          }
          else 
          {
            jQuery('.conversion_url_input').show();
            jQuery('#bt_experiments_conversion_page option[value=url]').attr('selected', true);
          }
          
          if(selectval == 'block' ){
            jQuery('.test-conversion-tags-mode').show();
          }
          else
          {
            jQuery('.test-conversion-tags-mode').hide();
          }


          if( selectval !== 'text' ) 
          {
            jQuery('.conversion_text_input').hide();            
          }
          else 
          {
            jQuery('.conversion_text_input').show();
            jQuery('#conversion_text option[value=text]').attr('selected', true);
          }

          if( selectval == 'time' ){
            jQuery('.conversion_time_input').show();
            jQuery('#bt_experiments_conversion_page option[value=time]').attr('selected', true);
          }
          else
          {
            jQuery('.conversion_time_input').hide();
          }

          //if link
          if( selectval == 'link' ){
            jQuery('.conversion_link_pattern_input').show();
            jQuery('#bt_experiments_conversion_page option[value=link]').attr('selected', true);
          }
          else{
            jQuery('.conversion_link_pattern_input').hide();
          }
          
          if( selectval == 'woo-order-received' ){
            jQuery('.conversion_order_value').show();
            jQuery('#bt_experiments_conversion_page option[value=conversion_order_value]').attr('selected', true);
          }
          else if( selectval == 'surecart-order-paid' ){
            jQuery('.conversion_order_value').show();
            jQuery('#bt_experiments_conversion_page option[value=conversion_order_value]').attr('selected', true);
          }
          else
          {
            jQuery('.conversion_order_value').hide();
          }
          
          if( selectval !== 'selector' )
          {
            jQuery('.conversion_selector_input').hide();            
          }
          else 
          {
            jQuery('.conversion_selector_input').show();
            jQuery('#bt_experiments_conversion_page option[value=selector]').attr('selected', true);
          }
          
          if( selectval == 'embed' )
          {
            jQuery('.embed-code-area').show();            
            jQuery('#bt_experiments_conversion_page option[value=embed]').attr('selected', true);
          }
          if( selectval == 'fingerprint' )
          {
            jQuery('.fingerprint-code-area').show();            
            jQuery('#bt_experiments_conversion_page option[value=fingerprint]').attr('selected', true);
          }
          else
          {
              jQuery('.fingerprint-code-area').hide();   
          }
       });
      
      </script>
      ";
    
    }

    
    function get_woo_pages(){
      if ( !class_exists( 'WooCommerce' ) )
        return false;

      return [ 
        'Shop' => get_option( 'woocommerce_shop_page_id' ),
        'Cart'=> get_option( 'woocommerce_cart_page_id' ),
        'Checkout'=> get_option( 'woocommerce_checkout_page_id' ),
        'Pay'=> get_option( 'woocommerce_pay_page_id' ),
        'Pay - Thanks'=> get_option( 'woocommerce_thanks_page_id' ),
        'My Account'=> get_option( 'woocommerce_myaccount_page_id' ),
        'Edit Address'=> get_option( 'woocommerce_edit_address_page_id' ),
        'View Order'=> get_option( 'woocommerce_view_order_page_id' ),
        'Terms'=> get_option( 'woocommerce_terms_page_id' ),
      ];
    }


    function manage_bt_experiments_posts_custom_column($column_name, $post_id){
      if( $column_name == 'visit' || $column_name == 'conversion' || $column_name == 'rate' || $column_name == 'screenshot' )
      {
        $observations = get_post_meta( $post_id, 'observations', true );
          
        if(is_array($observations))
        {
          foreach($observations as $variation => $v)
          {
          }
        }
        


        $out = "";
        
        if(is_array($observations))
        {
          uasort($observations, array($this,"cmp_by_ConversionRate"));

          foreach($observations as $variation => $v)
          { 
            //if the key is bt_bb_ab_stats then skip
            if($variation == 'bt_bb_ab_stats')
              continue;

            $variation_label = $variation;
            if(is_int($variation)){
              if ( get_post_status ( $variation ) ) {
                $variation_label = get_the_title($variation);
              }
            }

            //if variation meta for it
            $variation_meta = get_post_meta($post_id, 'variation_meta', true);
            if(is_array($variation_meta) && isset($variation_meta[$variation]) && !empty($variation_meta[$variation]['label']) ){
              $variation_label = $variation_meta[$variation]['label'];
            }
            
            if($v['rate'] == -1)
              $v['rate'] = '0%';
            else
              $v['rate'] = $v['rate'] . "%";
            
            if($column_name == 'visit')
            {
              $out .= "<div class='bt_variation_container'><span title='$variation_label' class='bt_variation'>$variation_label</span> ".$v['visit']."</div>";
            }
            if($column_name == 'conversion')
            {
              $out .= "<div class='bt_variation_container'><span title='$variation_label' class='bt_variation'>$variation_label</span> ".$v['conversion']."</div>";
            }
            if($column_name == 'rate')
            {
                $out .= "<div class='bt_variation_container'><span title='$variation_label' class='bt_variation'>$variation_label</span> ".$v['rate']."</div>";
            }
          }
        }
        else
        {
          echo "No data yet";
        }

        //todo clean up scripts
        echo $out;

      }
    }

    function add_experiment_row_attributes ($attrs, $row) {
      //ditch if not set
      if(!isset($row->settings->ab_test) || !isset($row->settings->bt_variation_name))
        return $attrs;
      
      //check the experiment still exists
      $test = get_post($row->settings->ab_test);
      
      if(isset($test) && $test->post_status == 'publish')
      {
        if ( isset($row->settings->ab_test)) 
        {
            $attrs['bt-variation'] = $row->settings->bt_variation_name;
            $attrs['bt-eid'] = $row->settings->ab_test;
        }
      }
      return $attrs;
    }
    
    function bt_experiments_post_register(){

    $labels = array(
      'name'                  => __( BT_AB_TEST_LITE_WL_NAME, 'Post Type General Name', 'bt-bb-ab' ),
      'singular_name'         => __( BT_AB_TEST_LITE_WL_NAME, 'Post Type Singular Name', 'bt-bb-ab' ),
      'menu_name'             => __( BT_AB_TEST_LITE_WL_NAME, 'bt-bb-ab' ),
      'name_admin_bar'        => __( BT_AB_TEST_LITE_WL_NAME, 'bt-bb-ab' ),
      'archives'              => __( BT_AB_TEST_LITE_WL_NAME.' Archives', 'bt-bb-ab' ),
      'attributes'            => __( BT_AB_TEST_LITE_WL_NAME.' Attributes', 'bt-bb-ab' ),
      'parent_item_colon'     => __( 'Parent '.BT_AB_TEST_LITE_WL_NAME, 'bt-bb-ab' ),
      'all_items'             => __( 'All '.BT_AB_TEST_LITE_WL_ABTEST, 'bt-bb-ab' ),
      'add_new_item'          => __( 'New '.BT_AB_TEST_LITE_WL_ABTEST, 'bt-bb-ab' ),
      'add_new'               => __( 'New '.BT_AB_TEST_LITE_WL_ABTEST, 'bt-bb-ab' ),
      'new_item'              => __( 'New '.BT_AB_TEST_LITE_WL_ABTEST, 'bt-bb-ab' ),
      'edit_item'             => __( 'Edit '.BT_AB_TEST_LITE_WL_ABTEST, 'bt-bb-ab' ),
      'update_item'           => __( 'Update ' .BT_AB_TEST_LITE_WL_ABTEST, 'bt-bb-ab' ),
      'view_item'             => __( 'View', 'bt-bb-ab' ),
      'view_items'            => __( 'View ' .BT_AB_TEST_LITE_WL_ABTEST, 'bt-bb-ab' ),
      'search_items'          => __( 'Search ' .BT_AB_TEST_LITE_WL_ABTEST, 'bt-bb-ab' ),
      'not_found'             => __( 'Not found', 'bt-bb-ab' ),
      'not_found_in_trash'    => __( 'Not found in Trash', 'bt-bb-ab' ),
      'featured_image'        => __( 'Featured Image', 'bt-bb-ab' ),
      'set_featured_image'    => __( 'Set featured image', 'bt-bb-ab' ),
      'remove_featured_image' => __( 'Remove featured image', 'bt-bb-ab' ),
      'use_featured_image'    => __( 'Use as featured image', 'bt-bb-ab' ),
      'insert_into_item'      => __( 'Insert into item', 'bt-bb-ab' ),
      'uploaded_to_this_item' => __( 'Uploaded to this item', 'bt-bb-ab' ),
      'items_list'            => __( BT_AB_TEST_LITE_WL_ABTEST.' list', 'bt-bb-ab' ),
      'items_list_navigation' => __( BT_AB_TEST_LITE_WL_ABTEST.' list navigation', 'bt-bb-ab' ),
      'filter_items_list'     => __( 'Filter '.BT_AB_TEST_LITE_WL_ABTEST.' list', 'bt-bb-ab' ),
    );
    $args = array(
      'label'                 => __( BT_AB_TEST_LITE_WL_ABTEST, 'bt-bb-ab' ),
      'description'           => __( 'Page Builder '.BT_AB_TEST_LITE_WL_ABTEST, 'bt-bb-ab' ),
      'labels'                => $labels,
      'supports'              => array( 'title' ),
      'taxonomies'            => array(),
      'hierarchical'          => false,
      'public'                => false,
      'show_ui'               => true,
      'show_in_menu'          => true,
      'menu_position'         => 30,
      'menu_icon'             => 'dashicons-star-half',
      'show_in_admin_bar'     => true,
      'show_in_nav_menus'     => true,
      'can_export'            => false,
      'has_archive'           => false,
      'exclude_from_search'   => true,
      'publicly_queryable'    => false,
      'capability_type'       => 'page',
    );
    register_post_type( 'bt_experiments', $args );
      
    register_post_status( 'complete', array(
        'label'                     => 'Test Complete',
        'public'                    => true,
        'post_type'                 => 'bt_experiments', // Define one or more post types the status can be applied to.
        'show_in_admin_all_list'    => true,
        'show_in_admin_status_list' => true,
        'show_in_metabox_dropdown'  => true,
        'show_in_inline_dropdown'   => true,
        'label_count'               => _n_noop( 'Test Complete <span class="count">(%s)</span>', 'Tests Complete <span class="count">(%s)</span>' ),
    ) );
      
    }



    function get_experiment_conversion_summary($experiment_id) {
      $conversion_page = get_post_meta($experiment_id, 'conversion_page', true);
      $conversion_url = get_post_meta($experiment_id, 'conversion_url', true);
      $conversion_selector = get_post_meta($experiment_id, 'conversion_selector', true);
      $conversion_text = get_post_meta($experiment_id, 'conversion_text', true);
      $conversion_link_pattern = get_post_meta($experiment_id, 'conversion_link_pattern', true);
      $conversion_time = get_post_meta($experiment_id, 'conversion_time', true);
      $conversion_scroll = get_post_meta($experiment_id, 'conversion_scroll', true);
      
      // Handle different conversion types
      if ($conversion_page === 'page' || is_numeric($conversion_page)) {
        // Page visit conversion
        $page_id = is_numeric($conversion_page) ? $conversion_page : $conversion_page;
        $page = get_post($page_id);
        if ($page) {
          return "Page visit: " . $page->post_title;
        } else {
          return "Page visit: Page ID " . $page_id;
        }
      } elseif ($conversion_page === 'url' && !empty($conversion_url)) {
        // URL conversion
        return "URL visit: " . $conversion_url;
      } elseif ($conversion_page === 'text' && !empty($conversion_text)) {
        // Text on page conversion
        $text_preview = strlen($conversion_text) > 50 ? substr($conversion_text, 0, 50) . '...' : $conversion_text;
        return "Text on page: \"" . $text_preview . "\"";
      } elseif ($conversion_page === 'selector' && !empty($conversion_selector)) {
        // Element click conversion
        return "Element click: " . $conversion_selector;
      } elseif ($conversion_page === 'link' && !empty($conversion_link_pattern)) {
        // Link click conversion
        return "Link click: " . $conversion_link_pattern;
      } elseif ($conversion_page === 'time' && !empty($conversion_time)) {
        // Time on page conversion
        return "Time on page: " . $conversion_time . " seconds";
      } elseif ($conversion_page === 'scroll' && !empty($conversion_scroll)) {
        // Scroll depth conversion
        return "Scroll depth: " . $conversion_scroll . "%";
      } elseif ($conversion_page === 'click') {
        // Generic click conversion
        return "Click tracking";
      } elseif (empty($conversion_page) || $conversion_page === '0') {
        // No conversion set
        return "No conversion trigger set";
      } else {
        // Fallback for unknown types
        return "Conversion: " . $conversion_page;
      }
    }

    function experiments_posts_columns($columns){
      //if is not localhosted
    //  $is_localhost = in_array($_SERVER['HTTP_HOST'], ['localhost', '127.0.0.1']) || strpos($_SERVER['HTTP_HOST'], 'localhost:') === 0 || substr($_SERVER['HTTP_HOST'], -5) === '.test'; 
      //if(!$is_localhost)
   //     $columns['screenshot']  = 'Screenshot'; todo soon
      $columns['visit']  = 'Visits';
      $columns['conversion']  = 'Conversions';
      $columns['rate']  = 'Conversion Rate';
      unset($columns['date']);
      $columns['date'] = " Test started on";
      return $columns;
    }

    function add_ab_settings( $form, $slug ) {

      $excluded_modules = array(
        'user_template', 
        'node_template', 
        'uabb-global',
        'global',
        'layout',
        'col',
        'row',
        'custom_post_layout',
        'content_slider_slide',
        'uabb_custom_post_layout'
      );

      if( !in_array($slug,$excluded_modules) && (substr($slug, -5) == "_form")) {
        return $form;
      }

      // add it to rows in the advanced section
      $abtest_fields = [
        'ab_test' => [
          'type'          => 'suggest',
          'label'         => __( 'Experiment', 'fl-builder' ),
          'action'        => 'fl_as_posts', // Search posts.
          'data'          => 'bt_experiments', // Slug of the post type to search.
          'limit'         => 1, // Limits the number of selections that can be made.
          'description'   => '<a id="" class="new-on-page-test-button" href="' . admin_url( 'edit.php?post_type=bt_experiments' ) . '" target="_blank">Create a new test. </a>',
        ],
        'bt_variation_name'  => [
          'type'        => 'text',
          'label'       => 'Variation Name',
          'description' => 'Using "default" will cause this version to run first, unless otherwise targeted. <a href="#">more info <img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAoAAAAKCAYAAACNMs+9AAAAQElEQVR42qXKwQkAIAxDUUdxtO6/RBQkQZvSi8I/pL4BoGw/XPkh4XigPmsUgh0626AjRsgxHTkUThsG2T/sIlzdTsp52kSS1wAAAABJRU5ErkJggg==" alt="opens in a new window"></a>',
        ]
      ];

      if( 'row' === $slug ) {
        $form[ 'tabs' ][ 'advanced' ][ 'sections' ]['ab_test_rows'] = [
          'title'   => BT_AB_TEST_LITE_WL_NAME,        
          'fields'  => $abtest_fields
        ];
      } elseif( 'module_advanced' === $slug ) {
        $form[ 'sections' ]['ab_test_rows'] = [
          'title'   => BT_AB_TEST_LITE_WL_NAME,        
          'fields'  => $abtest_fields
        ];
      }

      return $form;
    }

    function is_elementor_preview(){
      //if its activated, check if its a preview
  		if ( did_action( 'elementor/loaded' ) ) {
        return \Elementor\Plugin::$instance->preview->is_preview_mode();
      }

      return false; 

    }

    function add_oxy_split_options () {

    global $oxygen_vsb_components;
    if(!empty($oxygen_vsb_components)){

      //preload the tests dropdown
      $tests = get_posts([
        'post_type' => 'bt_experiments',
        'post_status' => 'any',
        'numberposts' => -1
      ]);

      $dropdown = [];
      $dropdown[] = '&nbsp';
      if(!empty($tests))
      {
        foreach($tests as $test){
          $dropdown[$test->ID] = $test->post_title;
        }
      }
      foreach($oxygen_vsb_components as $in => $component)
      {
          
          $oxygen_vsb_components[$in]->options['params'][] = [
              "type" => 'dropdown', // types: textfield, dropdown, checkbox, buttons-list, measurebox, slider-measurebox, colorpicker, icon_finder, mediaurl
              "name" => 'Test Experiment',
              "heading" =>  BT_AB_TEST_LITE_WL_ABTEST .' Name',
              "param_name" => 'btExperiment',				
              "hidden" 		=> false,
              "css" 			=> false,
              "value" 			=> $dropdown,
          ];
          $oxygen_vsb_components[$in]->options['params'][] = [
              "type" => 'textfield', // types: textfield, dropdown, checkbox, buttons-list, measurebox, slider-measurebox, colorpicker, icon_finder, mediaurl
              "name" => 'Variation Name',
              "heading" => 'Variation Name',
              "param_name" => 'btVar',				
              "hidden" 		=> false,
              "css" 			=> false,
              "value" 			=> '',
          ];
      }
    }

}

 function add_oxy_attrs($options, $tag){
  
    if(!empty($options['btVar']) && !empty($options['btExperiment']) )
      echo " bt-variation='".sanitize_text_field($options['btVar'])."' bt-eid='".intval($options['btExperiment'])."' ";
   }

    function include_highlighter_scripts(){
      
      if (current_user_can('upload_files') && !isset($_GET['fb-edit'])) { 
          wp_enqueue_media();
      }
      
      wp_enqueue_style('ab_test_styles',plugins_url( '/', __FILE__ ) . 'css/experiment-frontend.css', BT_AB_TEST_LITE_VERSION); 
      global $wp_roles;
      $roles_data = array();      
      foreach ($wp_roles->roles as $role => $details) {
        $roles_data[$role] = $details['name'];
      }
      
      $roles_data['logout'] = 'Logged out users';
      
      // Default selected roles, get all roles that cant edit or author posts
      $default_selected = array('subscriber', 'logout','customer','sc_customer');
      
      if(!current_user_can('edit_posts'))
        return;

      //only admins etc from here
      wp_enqueue_script( 'creator', plugins_url( '/', __FILE__ ) . 'js/creator.js', array( 'jquery' ), BT_AB_TEST_LITE_VERSION, false ); //modern screenshot, awesomplete, select2
      wp_enqueue_style('creator',plugins_url( '/', __FILE__ ) . 'css/creator.css', BT_AB_TEST_LITE_VERSION); //awesomplete, select2

      $highlighter_deps = array('creator');

      // Conditionally add block editor dependencies
      if (is_admin()) {
        $screen = get_current_screen();
        if ($screen && method_exists($screen, 'is_block_editor') && $screen->is_block_editor()) {
          $highlighter_deps = array_merge($highlighter_deps, array('wp-blocks', 'wp-element', 'wp-i18n', 'wp-components', 'wp-editor'));
        }
      }
      wp_enqueue_script('ab_test_highlighter', plugins_url('js/highlighter.js', __FILE__), $highlighter_deps, BT_AB_TEST_LITE_VERSION, true);
      wp_localize_script('ab_test_highlighter', 'abst_magic_data', array(
        'roles' => $roles_data,
        'defaults' => $default_selected,
        'ajax_url' => admin_url('admin-ajax.php'),
      ));
  
    //if has edit permissions
    if(current_user_can('edit_posts') && empty($_GET['elementor-preview']) && empty($_GET['brickspreview'])) // not inside elementor or bricks iframe
      wp_enqueue_script('ab_test_builder_helper',plugins_url( '/', __FILE__ ) . 'js/builderhelper.js', array('jquery'), BT_AB_TEST_LITE_VERSION, true);
    }


    function get_ab_posts_cache(){
      $posts = get_transient('ab_posts_cache');
      if (false === $posts) { // not a transient, create
        $posts = get_posts([
          'post_type' => 'bt_experiments',
          'post_status' => 'any',
          'numberposts' => -1
        ]);
        set_transient('ab_posts_cache', $posts, 60 * MINUTE_IN_SECONDS);
      }
      return $posts;
    }
    function get_all_goals(){

      $out = '';
      $out .= "<select class='goal-type' name='goal'>";
      $out .= "<option value=''>Select Goal Event...</option>";
      $out .= "<option value='page'>Page or Post Visit</option>";
      $out .= "<option value='text'>Text on Page</option>";
      $out .= "<option value='selector'>Element Click</option>";
      $out .= "<option value='link'>Link Click</option>";
      $out .= "<option value='time'>Time Active</option>";
      $out .= "<option value='scroll'>Scroll Depth</option>";
      if (defined('WC_VERSION')) {
        $out .= "<optgroup label='UPGRADE for WooCommerce'>";
        $out .= "</optgroup>";
      }
      
      // SureCart options
      if (class_exists('SURECART')) {
        $out .= "<optgroup label='UPGRADE for SureCart'>";
        $out .= "</optgroup>";
      }
      
      // Easy Digital Downloads options
      if(class_exists('Easy_Digital_Downloads')) {
        $out .= "<optgroup label='UPGRADE for Easy Digital Downloads'>";
        $out .= "</optgroup>";
      }
      
      // WP Pizza options
      if (class_exists('WPPIZZA')) {
        $out .= "<optgroup label='UPGRADE for WP Pizza'>";
        $out .= "</optgroup>";
      }
      
      $out .= "</select>";
      return $out;
    }

    function include_conversion_scripts()
    {
      // Enqueue both scripts in the head, add variables inline before each
      if(file_exists(plugin_dir_path(__FILE__) . 'js/bt_conversion-min.js'))
        $bt_conversion_js = plugins_url( '/', __FILE__ ) . 'js/bt_conversion-min.js';
      else
        $bt_conversion_js = plugins_url( '/', __FILE__ ) . 'js/bt_conversion.js';


      // Enqueue the main conversion script, dependent on the data script
      wp_enqueue_script('bt_conversion_scripts', $bt_conversion_js, [], BT_AB_TEST_LITE_VERSION, true);
    }


    function enqueue_experiment_scripts() {
      global $post_type, $post;

      // Enqueue Shepherd PRODUCT TOUR
      wp_enqueue_style('shepherd-css', '//cdn.jsdelivr.net/npm/shepherd.js/dist/css/shepherd.css');
      wp_enqueue_script('shepherd-js', '//cdn.jsdelivr.net/npm/shepherd.js/dist/js/shepherd.min.js', array('jquery'), BT_AB_TEST_LITE_VERSION, true);
      wp_enqueue_script('shepherd-tour', plugins_url( '/', __FILE__ ) . 'js/plugin-tour.js', array('shepherd-js'), BT_AB_TEST_LITE_VERSION, true);
      //dropdowns      
      wp_register_style( 'select2css', '//cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css', false, '4.1.0', 'all' );
      wp_register_script( 'select2', '//cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js', array( 'jquery' ), '4.1.0', true );
      wp_enqueue_style( 'select2css' );
      wp_enqueue_script( 'select2' );


          
      if( $post_type !== 'bt_experiments' ) 
        return;
      

      $eid = (isset($post->ID))? $post->ID : null;
      wp_enqueue_script( 'bt_experiment_scripts', plugins_url( '/', __FILE__ ) . 'js/experiment.js', ['jquery'], BT_AB_TEST_LITE_VERSION );
      wp_enqueue_script( 'bt_table', plugins_url( '/', __FILE__ ) . 'js/tabulator.js', ['jquery'], BT_AB_TEST_LITE_VERSION );
      wp_enqueue_style( 'bt_table', plugins_url( '/', __FILE__ ) . 'css/tabulator.css', array(), BT_AB_TEST_LITE_VERSION );
      wp_localize_script( 'bt_experiment_scripts', 'bt_exturl', [
        'ajax_url'  => admin_url( 'admin-ajax.php' ),
        'action'    => 'bt_generate_embed_code',
        'nonce'     => wp_create_nonce('btexperimentexturlnonce'),
        'eid'       => $eid
      ]);
      wp_enqueue_script( 'bt_ab_chart', plugins_url( '/', __FILE__ ) . 'js/chart.js', ['bt_experiment_scripts'], BT_AB_TEST_LITE_VERSION );
      wp_enqueue_style( 'bt_experiment_style', plugins_url( '/', __FILE__ ) . 'css/experiment.css',array(),BT_AB_TEST_LITE_VERSION);
      wp_enqueue_script( 'bt_confetti', plugins_url( '/', __FILE__ ) . 'js/confetti.js', ['jquery'], BT_AB_TEST_LITE_VERSION );
    
      
    }

    

    function get_posts_ajax_callback(){

      if(!current_user_can('edit_posts'))
      {
        wp_reset_postdata();
        echo json_encode( [] );
        die;
      }

        // type:'control', or variations
        $return = array();        
        $searchQuery = sanitize_text_field($_GET['q'] ?? '');
        if(is_numeric($searchQuery) && $searchQuery > 0 && get_post($searchQuery))
        {
          $return[] = array( $searchQuery, get_the_title($searchQuery) );
        }
        // Check if it's a URL and try to get post ID
        if(filter_var($searchQuery, FILTER_VALIDATE_URL)) {
          $url_post_id = url_to_postid($searchQuery);
          if($url_post_id > 0) {
            $post = get_post($url_post_id);
            if($post) {
              $return[] = array( $post->ID, $post->post_title . ' : [' .strtoupper($post->post_type) .'] '. $post->post_name );
            }
          }
        }

        $search_results = new WP_Query( array( 
          's'=> $searchQuery, // the search query
          'post_status' => 'publish', // if you don't want drafts to be returned
          'post_type' => post_types_to_test(),
          'orderby' => 'modified',
          'order' => 'DESC',
          'posts_per_page' => 50 // how much to show at once
        ) );
        if( $search_results->have_posts() ) :
          while( $search_results->have_posts() ) : $search_results->the_post();	
            // shorten the title a little
            $type = abst_sanitize($_GET['type'] ?? '');
            $title = ( mb_strlen( $search_results->post->post_title ) > 50 ) ? mb_substr( $search_results->post->post_title, 0, 49 ) . '...' : $search_results->post->post_title .' : [' .strtoupper($search_results->post->post_type) .'] '. $search_results->post->post_name;
            if($type == 'control') // ret page id and title
              $return[] = array( $search_results->post->ID, $title ); // array( Post ID, Post Title )
            else
            {
              //return page name and title
              $return[] = array( $search_results->post->ID, $title ); // array( Post ID, Post Title )
            }
          endwhile;
        endif;
        
        // if searchquery is a url or slug search for url or slug 
        $post = get_page_by_path($searchQuery, 'OBJECT', post_types_to_test());
        if($post)
        {
          $return[] = array( $post->ID, $post->post_title . ' : [' .strtoupper($post->post_type) .'] '. $post->post_name );
        }
        
        wp_reset_postdata();
        echo json_encode( $return );
        die;
      }




      function blocks_experiment_list(){
        $search_query = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';
        $exact_id = isset($_POST['exact_id']) ? sanitize_text_field($_POST['exact_id']) : '';
        if(!empty($exact_id))
        {
          $args = array(
              'p'              => $exact_id,
              'post_status'    => 'publish',  
              'post_type'      => 'bt_experiments',
              'orderby'        => 'modified'
          );
        }
        else
        {
          $args = array(
            's'              => $search_query,
            'post_status'    => 'publish',  
            'post_type'      => 'bt_experiments',
            'orderby'        => 'modified',
            'order'          => 'DESC',
            'posts_per_page' => 50,
            'meta_query'     => array(
                array(
                    'key'     => 'test_type',
                    'value'   => 'ab_test',
                    'compare' => '=',
                ),
            ),
          );
        }
    
        $search_results = new WP_Query($args);
        $return = array();
    
        if ( $search_results->have_posts() ) :
            while ( $search_results->have_posts() ) : $search_results->the_post();	
                // Shorten the title
                $title = ( mb_strlen( get_the_title() ) > 50 ) 
                    ? mb_substr( get_the_title(), 0, 49 ) . '...' 
                    : get_the_title();
                    
                // Return in label/value format for ComboboxControl
                $return[] = array(
                    'value' => (string) get_the_ID(),
                    'label' => $title
                );
            endwhile;
        endif;
            
        // Return JSON in the correct format
        wp_send_json($return);
        // No need for die; wp_send_json() ends execution.
    
      }


    function bt_generate_embed_code(){ // iframe embed code. make sure your server has x frame options to allow remote domain, or 'all' if you are feeling frisky
    if( ! wp_verify_nonce( $_POST['nonce'], 'btexperimentexturlnonce' ) ) { wp_die('sorry...'); }
    
      $plugin_url = get_admin_url().'admin-ajax.php?action=abtrk&eid='.intval($_POST['eid']);

      $embed_code = '<iframe src="'.$plugin_url.'" style="position: absolute;width:0;height:0;border:0;"></iframe>';

      echo $embed_code;
      wp_die();
    }

    function header_style()
    {
      echo '<style>[bt_hidden=true] { display: none !important; visibility: hidden !important; height: 0 !important; } </style>';  
    }

    function get_canonical_url($original_url, $post){
      $add_canonical = ab_get_admin_setting('ab_change_canonicals');
      if($add_canonical == 1){
        $canonicals = get_option('ab_test_canonical');
        if(isset($canonicals) && is_array($canonicals) && !empty($canonicals[$post->ID])){
          return $canonicals[$post->ID];
        }
      }
      return $original_url;
    }

    function is_tracking_allowed( $eid ) {

      $allowed = (array) get_post_meta( $eid, 'bt_allowed_roles', true );
      $user    = wp_get_current_user();
      
      $roles = ( array ) $user->roles;
  
      if ( ! is_user_logged_in() && in_array( 'logout', $allowed ) ) {
        $roles[] = 'logout';
      }
      
      if ( current_user_can( 'administrator' ) ) {
        $roles[] = 'administrator'; 
      }
    
      $is_allowed = ! empty( array_intersect( $allowed, $roles ) );
      $is_allowed = apply_filters( 'abst_is_tracking_allowed', $is_allowed, $eid );
    
      return $is_allowed;
    }



      function test_post_states( $states, $post ) {

          if ( 'bt_experiments' == get_post_type( $post->ID ) ) {
            $newstate = '';
            if($post->post_status == 'draft')
              $newstate = '<span>🔴 Not Started </span>';
            else if($post->post_status == 'publish')         
              $newstate = '<span>🟢 Running</span>';
            else if($post->post_status == 'complete')         
              $newstate = '<span>✔️ Complete</span>';
            else if($post->post_status == 'pending')         
              $newstate = '<span>⏸️ Paused</span>';

            if(empty($newstate))
              $newstate = implode(' ', $states);

            $test_type = get_post_meta( $post->ID, 'test_type', true );
            if($test_type == 'full_page')
              $test_type = 'Full Page Test';
            else if($test_type == 'ab_test')
                $test_type = 'On Page Test';
            else
              $test_type = 'Status';

            $newstate = "<span class='test-type'>".$test_type.":</span>" . $newstate;

            $states = array();
            $states[] = $newstate;

          }
          return $states;
      }


    function updated_body_class( $classes ) {
      $user = wp_get_current_user();
      
      $capabilities = apply_filters('bt_can_user_view_variations', [
        'edit_posts',
        'edit_pages'
      ]);

      if( !$capabilities ) {
        $classes[] = 'bt-hidevars';
      }

      return $classes;
    }

    /**
     * Check if current users can view all experiment variations 
     * depending on capabilities
     *
     * @param array $capabilities 
     * @return bool
     */
    function can_user_view_variations( $capabilities ) {

      if( !is_array($capabilities) || empty($capabilities) ) {
        return true;
      }

      $allowed = 0;

      foreach ($capabilities as $capability) {
        if( current_user_can( $capability ) ) {
          $allowed += 1;
        }
      }

      if( $allowed > 0 ) {
        return true;
      }

      return false;
    }
    
    function render_js( $js, $nodes, $global_settings ) {

      $experiments = [];
      
      foreach($nodes as $modules){

        foreach($modules as $module)
        {
          if(isset($module->settings->ab_test) && $module->settings->ab_test !== "")
          {
            $conversion_url = get_post_meta($module->settings->ab_test,"conversion_url",true);
            $target_percentage = get_post_meta($module->settings->ab_test,"target_percentage",true);
            $url_query = get_post_meta($module->settings->ab_test,"url_query",true);
            $target_option_device_size = get_post_meta($module->settings->ab_test,"target_option_device_size",true) || 'all';
            $ab_use_fingerprint = get_post_meta($module->settings->ab_test,"ab_use_fingerprint",true) || '';
            if($target_option_device_size == true)
              $target_option_device_size = 'all';
            // create if doesnt exist
            if(!isset($experiments[$module->settings->ab_test]))
               $experiments[$module->settings->ab_test] = [];
            if(!isset($experiments[$module->settings->ab_test]['variations']))
              $experiments[$module->settings->ab_test]['variations'] = [];

            $experiments[$module->settings->ab_test]['variations'][] =  $module->settings->bt_variation_name;
            $experiments[$module->settings->ab_test]['conversion_url'] = base64_encode($conversion_url);
            $experiments[$module->settings->ab_test]['target_percentage'] = $target_percentage;
            $experiments[$module->settings->ab_test]['url_query'] = $url_query;
            $experiments[$module->settings->ab_test]['target_option_device_size'] = $target_option_device_size;
            $experiments[$module->settings->ab_test]['ab_use_fingerprint'] = $ab_use_fingerprint;
            $experiments[$module->settings->ab_test]['meta'] = [];
            $experiments[$module->settings->ab_test]['meta']['weight'] = 0;
            $experiments[$module->settings->ab_test]['conversion_style'] = get_post_meta($module->settings->ab_test,"conversion_style",true);
            if(get_post_meta($module->settings->ab_test,"meta",true))
              $experiments[$module->settings->ab_test]['meta'] = get_post_meta($module->settings->ab_test,"meta",true);

          }
        }
      }
      
      if(!empty($experiments))
      {
        $js.= "
        if(typeof bt_experiments == 'undefined')
          var bt_experiments = {};          
        ";
        foreach($experiments as $k => $v)
        {
          $js.='
            bt_experiments["'.$k.'"] = '.json_encode($v).';
          ';
        }
        
      }
      
      return $js;
    }

    //called in wp_head 
    function render_experiment_config() {
      
      global $post;
      
      $is_preview = (is_preview() || (isset($_GET['fl_builder']) && is_user_logged_in()) ||  $this->is_elementor_preview() )  ? true : false;
      
      if(!empty($post))
        $post_id = $post->ID;
      else
        $post_id = '';

      $gqo = get_queried_object();

      if ($gqo instanceof WP_Post_Type) {
        $post_id =  'post-type-archive-'.$gqo->name;
      }
      if(is_category())
      {
        $post_id =  'category-'.$gqo->slug;
      }
      if(is_tag()  || is_tax())
      {
        $post_id =  'tax-'.$gqo->taxonomy.' term-'.$gqo->slug;
      }
      if(is_author())
      {
        $post_id =  'author-'.$gqo->user_login;
      } 

      $btab_vars =  [
        'is_admin' => current_user_can('manage_options'),
        'post_id' => $post_id,
        'is_preview' => $is_preview,
        'is_free' => '1',
        'plugins_uri' => BT_AB_TEST_LITE_PLUGIN_URI,
        'domain' => get_home_url(),
        'v' => BT_AB_TEST_LITE_VERSION
      ];
      
      $experiments = [];
      
      $posts = get_transient('ab_posts_cache');
      if (false === $posts) { // not a transient, create
        $posts = get_posts([
          'post_type' => 'bt_experiments',
          'post_status' => 'any',
          'numberposts' => -1
        ]);
        set_transient('ab_posts_cache', $posts, 60 * MINUTE_IN_SECONDS);
        wp_reset_postdata();
      }
      
      $style = '<style>';
    
      foreach($posts as $key => $val)
      {
        $conversion_page = get_post_meta($val->ID, 'conversion_page', true);
        if($conversion_page == 'block')
          $conversion_page = '';
        $conversion_url = get_post_meta($val->ID,"conversion_url",true);
        $conversion_style = get_post_meta($val->ID,"conversion_style",true) ?? 'bayesian';
        $conversion_time = get_post_meta($val->ID,"conversion_time",true);
        $conversion_scroll = get_post_meta($val->ID,"conversion_scroll",true);
        $conversion_selector = get_post_meta($val->ID,"conversion_selector",true);
        $conversion_text = get_post_meta($val->ID,"conversion_text",true) ?? false;
        $conversion_link_pattern = get_post_meta($val->ID,"conversion_link_pattern",true) ?? false;

        $goals = get_post_meta($val->ID,"goals",true);

        $target_percentage = get_post_meta($val->ID,"target_percentage",true);
        $url_query = get_post_meta($val->ID,"url_query",true);
        $test_type = get_post_meta($val->ID,"test_type",true);
        $full_page_default_page = get_post_meta($val->ID,"bt_experiments_full_page_default_page",true);
        $test_name = $val->post_title;
        $page_variations = get_post_meta($val->ID,"page_variations",true);
        $variation_meta = get_post_meta($val->ID,"variation_meta",true);
        $test_status = $val->post_status;
        $test_winner = get_post_meta($val->ID,"test_winner",true);
        $magic_definition = get_post_meta($val->ID,"magic_definition",true);
        $target_option_device_size = get_post_meta($val->ID,"target_option_device_size",true);

        if($target_option_device_size == '')
          $target_option_device_size = 'all';
        // create if doesnt exist
        if(!isset($experiments[$val->ID]))
          $experiments[$val->ID] = [];
        $experiments[$val->ID]['name'] = $test_name;
        $experiments[$val->ID]['target_percentage'] = $target_percentage;
        $experiments[$val->ID]['url_query'] = $url_query;
        $experiments[$val->ID]['conversion_page'] = $conversion_page;
        $experiments[$val->ID]['conversion_url'] = $conversion_url;
        $experiments[$val->ID]['conversion_link_pattern'] = $conversion_link_pattern;
        $experiments[$val->ID]['conversion_time'] = $conversion_time;
        $experiments[$val->ID]['conversion_scroll'] = $conversion_scroll;
        $experiments[$val->ID]['conversion_style'] = $conversion_style;
        $experiments[$val->ID]['conversion_selector'] = $conversion_selector;
        $experiments[$val->ID]['conversion_text'] = $conversion_text;
        $experiments[$val->ID]['goals'] = $goals;
        $experiments[$val->ID]['test_type'] = $test_type;
        $experiments[$val->ID]['is_current_user_track'] = $this->is_tracking_allowed( $val->ID );
        $experiments[$val->ID]['full_page_default_page'] = $full_page_default_page;
        $experiments[$val->ID]['page_variations'] = $page_variations;
        $experiments[$val->ID]['variation_meta'] = $variation_meta;
        $experiments[$val->ID]['magic_definition'] = $magic_definition;
        $experiments[$val->ID]['test_status'] = $test_status;
        $experiments[$val->ID]['test_winner'] = $test_winner;
        $experiments[$val->ID]['target_option_device_size'] = $target_option_device_size;

        if($test_type == 'full_page')
          $style .= ' .page-id-'.$full_page_default_page.',.postid-'.$full_page_default_page.'{display:none;}'; //hide the page to avoid flicker

        if($test_type == 'full_page' && !is_int($full_page_default_page))
          $style .= ' .'.$full_page_default_page.'{display:none;}'; //hide the page to avoid flicker


      }
      $style .= '</style>';
      $btab_reset = intval($_GET['btab_reset'] ?? 0);
      if( $btab_reset > 0 ) {
        echo '<script>
          function btab_reset_cookie(name) {
            document.cookie = name +"=; path=/; Expires=Thu, 01 Jan 1970 00:00:01 GMT;";
          }
          btab_reset_cookie("btab_". $btab_reset);
          console.log("reset:","btab_". $btab_reset);
        </script>';
      }

      echo "<script data-cfasync='false' data-no-optimize='1' id='abst_variables'>";
      echo "var bt_ajaxurl = '".admin_url( 'admin-ajax.php' )."';";
      echo "var bt_adminurl = '".admin_url()."';";
      echo "var bt_pluginurl = '".plugin_dir_url( __FILE__ )."';";
      echo "var bt_homeurl = '".home_url()."';";
      echo "var btab_vars = " . json_encode($btab_vars) . ";";
      if(!empty($experiments))
      {
        $js = "var bt_experiments = {};\n";
        foreach($experiments as $k => $v)
        {
          $js.='bt_experiments["'.$k.'"] = '.json_encode($v).';';
        }
        echo $js." bt_conversion_vars = [];</script>";
        echo $style . "<style id='absthide'>
/* Default hidden styles for all variations */
[bt-variation]:not(.bt-show-variation),
[data-bt-variation]:not(.bt-show-variation),
[class*='ab-var-'] {
    opacity: 0 !important;
    display: none !important;
}

/* First hidden element uses display: inherit */
[bt-variation]:not(.bt-show-variation):first-of-type,
[data-bt-variation]:not(.bt-show-variation):first-of-type,
[class*='ab-var-']:first-of-type {
    display: inherit !important; /* Ensure it still occupies layout space */
}

/* When the body has the ab-test-setup-complete class, revert to fully hidden */
body.ab-test-setup-complete [bt-variation]:not(.bt-show-variation),
body.ab-test-setup-complete [data-bt-variation]:not(.bt-show-variation),
body.ab-test-setup-complete [class*='ab-var-'] {
    display: none !important;
    opacity: 1 !important; /* Reset opacity just in case */
    visibility: visible !important; /* Reset visibility */
}

/* Don't apply variation hiding when Beaver Builder is active */
body.fl-builder-edit [bt-variation]:not(.bt-show-variation),
body.fl-builder-edit [data-bt-variation]:not(.bt-show-variation),
body.fl-builder-edit [class*='ab-var-'] {
    display: inherit !important;
    opacity: 1 !important;
    visibility: visible !important;
}

/* Ensure variations are visible in , Bakery  and Bricks builders */
#breakdance_canvas [bt-eid], #editor [data-bt-eid], body[data-builder-window='iframe'] .brx-body [bt-eid],  .vc_editor .vc_element [class*='ab-var-'] {
    display: inherit !important; /* Retain inherited display type */
    opacity: 1 !important; /* Fully visible */
    visibility: visible !important; /* Ensure it's interactable */
}
</style>";
      }
      echo "</script>";
    }

    
    function post_status_transition($new_status, $old_status, $post)
    {
      // Check if the post is transitioning from 'auto-draft' to 'publish'
      if ($old_status === 'auto-draft' && $new_status === 'publish') {
        delete_option('all_testable_posts');// better refresh it
      }

      // Check if the post is transitioning from 'publish' to 'trash' or 'delete'
      if (($old_status === 'publish' || $old_status === 'trash') && ($new_status === 'trash' || $new_status === 'delete')) {
        delete_option('all_testable_posts'); // better refresh it
      }
    }

    function render_admin_scripts_styles(){
      global $typenow;
      if( $typenow == 'bt_experiments' ){
        wp_enqueue_style( 'bt_bb_ab_admin_styles', plugins_url('admin/bt-bb-ab-admin.css', __FILE__), array(), BT_AB_TEST_LITE_VERSION );    
      }
      if (isset($_GET['page']) && $_GET['page'] === 'bt_bb_ab_insights') {
        // Insights feature not available in Lite version
        // Turndown feature not available in Lite version
      }
    }

    


      /**
       * Log experiment activity
       *
       * @param int $bt_eid The experiment ID
       * @param string $bt_variation The variation ID
       * @param string $bt_type The type of activity (visit|conversion|goal)
       * @param bool $from_api Whether the request is coming from a js navigator.beacon() call or the AB Test admin page
       * @param string $bt_location The location of the activity
       * @param int $abConversionValue The value of the conversion
       * @param string $uuid The user's UUID
       * @param string $size The size of the device (mobile|tablet|desktop)
       * @param string $advancedId The user's advanced ID
       *
       * @return void
       *
       * @since 1.0.0
       */
      function log_experiment_activity($bt_eid = null, $bt_variation = null, $bt_type = null, $from_api = false, $bt_location = false, $abConversionValue = false,$uuid = false,$size = false, $advancedId = false){
      
        $error = false;
  
        //is it coming from a js navigator.beacon() call?
        if(isset($_GET['method']) && ($_GET['method'] == 'beacon'))
        {
          $beaconData = file_get_contents("php://input");
          $decodedBeacon = json_decode($beaconData, true);
          $eid = (int)sanitize_text_field($decodedBeacon['eid'] ?? null);
          $variation = $decodedBeacon['variation'] ?? null;
          $type = sanitize_text_field($decodedBeacon['type'] ?? null); // 'conversion'|'visit'|goal(0-9)
          $location = $decodedBeacon['location'] ?? '';
          $size = $decodedBeacon['size'] ?? null;
          $abConversionValue = $decodedBeacon['orderValue'] ?? 1;
          $uuid = sanitize_text_field($decodedBeacon['uuid'] ?? null);
          $advancedId = sanitize_text_field($decodedBeacon['ab_advanced_id'] ?? null);
        }
        else
        {
          $eid                = sanitize_text_field(($bt_eid)? (int)$bt_eid : (int)$_POST['eid']);
          $variation          = $bt_variation ?? sanitize_text_field($_POST['variation'] ?? '');
          $type               = sanitize_text_field(($bt_type)? $bt_type : $_POST['type']);
          $location           = $bt_location;
          if($location === false)
            $location           = sanitize_text_field($_POST['location'] ?? '');
          $size               = $size ?? sanitize_text_field($_POST['size'] ?? '');
          $abConversionValue  = sanitize_text_field(($abConversionValue)? $abConversionValue : $_POST['orderValue'] ?? 1);
          $uuid               = sanitize_text_field(($uuid)? $uuid : ($_POST['uuid'] ?? null));
          $advancedId         = sanitize_text_field(($advancedId)? $advancedId : ($_POST['ab_advanced_id'] ?? null));
        }
        
        $allowAny = apply_filters( 'abst_allow_cors', false); // do cors if ppl are using subdomain multisite stuff
        if($allowAny)
          header("Access-Control-Allow-Origin: *");

            
        abst_log('Log event: eid:' . $eid . ' variation:' . $variation . ' type:' . $type . ' location:' . $location . ' conversion value:' . $abConversionValue . ' uuid:' . $uuid . ' size:' . $size . ' advancedId:' . $advancedId . ' from_api:' . $from_api);
        

        if(!empty($size)) // sanitize size
          if($size != 'mobile' && $size != 'tablet' && $size != 'desktop')
            $size = false;
        
        if(!$from_api && !$this->is_tracking_allowed( $eid ) )
          $error = 'Tracking not allowed..';
      
        if(!get_post_status ( $eid ))
          $error = 'Test ID ' . $eid . ' not found';
      
        if(!empty($variation) && is_string($variation) && strlen($variation) > 100)
          $error = 'Variation Name too long. 100 characters max.';
        
        if(is_string($location) && strlen($location) > 100)
          $error = 'Location Name too long. 100 characters max.';

        if(!empty($variation) && ((string)abst_sanitize((string)$variation) !== (string)$variation))
          $error = 'Variation Name contains invalid characters.';

        if((string)abst_sanitize((string)$location) !== (string)$location)
          $error = 'Location Name contains invalid characters. sanitized: ' . abst_sanitize($location);        

        // For UUID/AdvancedID-based events, update the fingerprint table and back-fill variation if needed.
        if((!empty($uuid) || !empty($advancedId)) && !$error)
        {

          //check with db if uuid + eid exists and has not already been created/converted 
          abst_log('UUID/ADV update begin: type:' . $type . ' eid:' . $eid . ' using:' . (!empty($advancedId) ? 'advancedId' : 'uuid'));
          if(!empty($advancedId))
            $fpresult = $this->update_fingerprint_db($advancedId,$type,$variation, $eid, null, $location, $size);          
          else if(!empty($uuid))
            $fpresult = $this->update_fingerprint_db($uuid,$type,$variation , $eid , null, $location, $size);

          // if no variation was found, return false
          
          if(empty($fpresult) || $fpresult == false)
          {
            $error = 'UUID/Advanced ID not updated (already logged) or does not exist';
            abst_log( 'Log event ERROR: ' . $error );
            if( $from_api ) {
              return new WP_REST_Response([
                'error'  => $error
              ], 200);
            } else {
              echo( json_encode(array('error'=>$error)) );
              die();
            }
          }
          else{
            if(!is_int($fpresult)) // visit or conversion
            {
              $variation = $fpresult;
            }
            else // goal
            {
              $variation = $variation;
            }
          }
        }

        //if no variation or variation is false
        if($variation === "" || $variation === false)
          $error = 'Variation name is required, blank or false given';

        if(!$error)
        {
          $test_meta = get_post_meta($eid);
          //get the experiment
          if(get_post_type($eid) == 'bt_experiments' && isset($test_meta['test_type'][0]))
          {
            //get all post meta
            $test_type = $test_meta['test_type'][0]; 
  
            if($test_type == 'full_page')
            {
              $location = (int)$location;
              $variation = (int)$variation;
              // page test check would be if the variation is in the default page or a variation page, then is ok.
            }

          } 
          else
          {
            $error = 'Test not found';
          }
        }
        

        if(!$error)
        {
          $obs = $test_meta['observations'][0] ?? null;
        
        // Unserialize the observations data if it's a string
        if(is_string($obs) && !empty($obs)) {
            $obs = maybe_unserialize($obs);
        }
        
        if(!isset($obs) || $obs == "" || !is_array($obs))
        {
          $obs = array(
            $variation => array(
              'visit' => 0,
              'conversion' => 0,
              'goals' => array(),
              'rate' => -1,
              'location' => array(
                'visit' => array(),
                'conversion' => array(),
              ),
            )
          );
        }
  
        if(!isset($obs[$variation]) || (isset($obs[$variation]) && !is_array($obs[$variation])))
        {
          $obs[$variation] = array(
            'visit' => 0,
            'conversion' => 0,
            'goals' => array(),
            'rate' => -1,
            'location' => array(
              'visit' => array(),
              'conversion' => array(),
            ),
          );
        }


          $abConversionValue = 1; // default to 1
        
  
        if($type == 'conversion')
        {
          abst_log('OBS: add conversion variation ' . $variation . ' += ' . floatval($abConversionValue));
          $obs[$variation][$type] = floatval($obs[$variation][$type]) + floatval($abConversionValue);
        }
        else if ($type == 'visit')
        {
          abst_log('OBS: add visit variation ' . $variation);
          ++$obs[$variation][$type];
        }
        else // goal
        {
          if((string)abst_sanitize($type) !== (string)$type)
          {
            $error = 'Goal Name contains invalid characters.';
            if( $from_api ) {
              return new WP_REST_Response([
                'error'  => $error
              ], 200);
            } else {
              abst_log( 'Log  ERROR: ' . $error );
              echo( json_encode(array('error'=>$error)) );
              die();
            }
          }
          else 
          {
            if(!isset($obs[$variation]['goals'][$type]))
            {
              $obs[$variation]['goals'][$type] = 0;
            }
            abst_log("OBS: add goal variation " . $variation . " goal '" . $type . "'++");
            ++$obs[$variation]['goals'][$type];
          }
        }
      }

      if($error)
      {
        abst_log( 'Log event ERROR: ' . $error );
        if( $from_api ) {
          return new WP_REST_Response([
            'error'  => $error
          ], 200);
        } else {
          echo( json_encode(array('error'=>$error)) );
          die();
        }
      }

      //update conversion rate if not zeros
      if(isset($obs[$variation]['visit']) && isset($obs[$variation]['conversion']) &&
          $obs[$variation]['visit'] !== 0 && $obs[$variation]['conversion'] !== 0 &&
          is_numeric($obs[$variation]['visit']) && is_numeric($obs[$variation]['conversion']))
      {
        $obs[$variation]['rate'] = round( ( ($obs[$variation]['conversion'] / $obs[$variation]['visit']) * 100), 2); // round it to 2 decimal places
      }

      if($type == 'visit' || $type == 'conversion' && !empty($location))
      {
        if(isset($obs[$variation]['location'][$type]) && is_array($obs[$variation]['location'][$type]))
        {
          if (!in_array($location, $obs[$variation]['location'][$type]))
          {
            abst_log('OBS: add location ' . $location . ' for variation ' . $variation . ' type ' . $type);
            $obs[$variation]['location'][$type][] = $location;
          }
        }
      }

            
      do_action('log_experiment_activity', $eid, $variation, $type, $location); // do ya thing vibe coders
      
      update_post_meta($eid,'observations',$obs);

      if( $from_api ) {
        return new WP_REST_Response(['success' => true], 200);        
      } else {
        echo( json_encode(['success' => true]) );
        die();
      }
    }
    
  

    function update_fingerprint_db($uuid = null,$type = null,$variation = null, $testId = null, $timestamp = null, $location = null, $device_size = null) {

      if(!$type || !$testId || !$uuid)
        return false;
    
      if(ab_get_admin_setting('ab_use_fingerprint') != '1' && ab_get_admin_setting('ab_use_uuid') != '1')
      {
        abst_log('update_fingerprint_db called ' . $type . ' ' . $testId . ' ' . $uuid . ' but ab_use_fingerprint or ab_use_uuid is not set');
        return false;
      }
      
      $uuid = sanitize_text_field($uuid);
      $type = sanitize_text_field($type);
      $variation = sanitize_text_field($variation);
      $testId = absint($testId); // Assuming testId is an integer
      $timestamp = sanitize_text_field($timestamp);
      $location = $location ?? sanitize_text_field($_GET['location'] ?? false);
      $device_size = $device_size ?? sanitize_text_field($_GET['device_size'] ?? false);
      $fingerprint_db = intval(ab_get_admin_setting('abst_fingerprint_table_ready'));
      if(!$timestamp) $timestamp = date('Y-m-d H:i:s'); 
      
      global $wpdb;
      $table_name = $wpdb->prefix . 'abst_fingerprints';
      
      //if test is not published or conversion page is not set to embed then we exit
      if(get_post_status($testId) !== 'publish')
        return false;      
      if((get_post_meta($testId, 'conversion_page', true) != 'fingerprint') && ab_get_admin_setting('ab_use_uuid') != 1)
        return false;
  
      abst_log('update_fingerprint_db running ' . $type . ' ' . $testId . ' ' . $uuid);
  
      $uuidLegacy = $uuid . $this->get_user_ip_hash();  // backward compat, remove after oct 25
      if($fingerprint_db < 2) {
        abst_log('upgrading fingerprint db to V2');
          
        // Ensure required columns exist for legacy installations
        $required_columns = [
          'uuid'      => "ALTER TABLE $table_name ADD COLUMN uuid varchar(255) NOT NULL",
          'type'      => "ALTER TABLE $table_name ADD COLUMN type varchar(255) NOT NULL",
          'variation' => "ALTER TABLE $table_name ADD COLUMN variation varchar(255) NOT NULL",
          'testId'    => "ALTER TABLE $table_name ADD COLUMN testId varchar(255) NOT NULL",
          'timestamp' => "ALTER TABLE $table_name ADD COLUMN timestamp timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP",
          'location'  => "ALTER TABLE $table_name ADD COLUMN location varchar(255) NOT NULL",
          'size'      => "ALTER TABLE $table_name ADD COLUMN size varchar(255) NOT NULL",
          'goals'     => "ALTER TABLE $table_name ADD COLUMN goals TEXT"
        ];
  
        $existing_columns = $wpdb->get_col("DESC $table_name", 0);
        foreach ( $required_columns as $column => $alter_sql ) {
          if ( ! in_array( $column, $existing_columns ) ) {
            $wpdb->query( $alter_sql );
          }
        }
        update_option( 'abst_fingerprint_table_ready', 2 );      
        abst_log('upgraded fingerprint db to V2');
      }


      //create initial view record
      if($type == 'visit') {
        $sql = $wpdb->prepare("SELECT * FROM $table_name WHERE uuid = %s AND testId = %d", $uuid, $testId);
        $result = $wpdb->get_row($sql);
        abst_log('visit check result ');
        abst_log($result);
        if (empty($result)) {
            $data = [
                'uuid' => $uuid,
                'type' => $type,
                'variation' => $variation,
                'testId' => $testId,
                'timestamp' => $timestamp,
                'location' => $location,
                'size' => $device_size
            ];
            $insertVisit = $wpdb->insert($table_name, $data);
            if($insertVisit) {
              return $variation; // continue logging
            }
            else
            {
              abst_log('insert failed');
              return false; // dont continue logging
            }
        } else {
          return false; // dont continue logging
        }        
      }
      else if($type == 'conversion') {
        $data = ['type' => 'conversion', 'timestamp' => $timestamp];
        
        // First try with the regular UUID
        $where = ['uuid' => $uuid, 'testId' => $testId, 'type' => 'visit'];
        $result = $wpdb->update($table_name, $data, $where);
        
        // If no rows were updated, try with the legacy UUID format (with IP hash) // backward compat, remove after oct 25
        if(!$result && isset($uuidLegacy)) {  // backward compat, remove after oct 25
          
          $where = ['uuid' => $uuidLegacy, 'testId' => $testId, 'type' => 'visit'];  // backward compat, remove after oct 25
          $data = ['type' => 'conversion', 'timestamp' => $timestamp, 'uuid' => $uuid]; // update uuid to new format
          $result = $wpdb->update($table_name, $data, $where); // backward compat, remove after oct 25
        } // backward compat, remove after oct 25
        
        if($result) 
        {
          //get the variation from the db
          $variation = $wpdb->get_row($wpdb->prepare("SELECT variation FROM $table_name WHERE uuid = %s AND testId = %d", $uuid, $testId));
          abst_log('===');
          abst_log($variation);
          return $variation->variation;
        }
        abst_log('UUID conversion not triggered: Never visited or already converted');
      }
      else //goal
      {
        abst_log('goal ' . $type . ' checking');
        //get goals for this eid uuid
        $sql = $wpdb->prepare("SELECT * FROM $table_name WHERE uuid = %s AND testId = %d AND type = 'visit'", $uuid, $testId); // get the variation thats a visit no conversiions no nothibgs
        $result = $wpdb->get_row($sql);
        //if none with a visit, return false
        abst_log($result);
        if (empty($result)) {
          abst_log('UUID conversion error: no visit found');
          return false;
        }
        //if has goals column, json decode it
        abst_log('goal cell found ');
        $goals = !empty($result->goals) ? json_decode($result->goals, true) : null;
        abst_log($goals);
        if(empty($goals))
        {
          abst_log('goal cell is empty, creating arry'); 
          $goals = [];
        }
        if (empty($goals[$type])) {
          //add it to the goals column
          $goals[$type] = 1;
          $data = ['goals' => json_encode($goals)];
          $wpdb->update($table_name, $data, ['uuid' => $uuid, 'testId' => $testId]);
          //return the goal value of 1 if success or 0 if no 
          abst_log('goal ' . $type . ' logged');
          return 1;
        }
        else
        {
          abst_log('goal ' . $type . ' already logged');
          return false;
        }
      }
      return false;
    }

    
    
    function clear_fingerprint_database() {
      global $wpdb;
      $table_name = $wpdb->prefix . 'abst_fingerprints';
      $days_to_keep = ab_get_admin_setting('ab_fingerprint_length') ?? 30; // Change this to the number of days you want to keep
      abst_log('clearing fingerprint db older than ' . $days_to_keep . ' days');
      // Delete rows older than X days
      $query = $wpdb->prepare(
        "DELETE FROM $table_name WHERE timestamp < DATE_SUB(CURRENT_DATE, INTERVAL %d DAY)",
        $days_to_keep
      );
      $result = $wpdb->query($query);
      if($result === false) {
        abst_log('Failed to delete fingerprint db older than ' . $days_to_keep . ' days');
      }
      abst_log('Deleted '  . $result . ' old fingerprint rows');
    }

    function wp_ajax_bt_clear_results(){
      
      $post = $_POST;
      $eid = $post['eid'];
      $response = array(
        'text' => '',
        'success' => false,
      );
      
      if(isset($eid) && $eid !== '' && $post['bt_action'] == 'clear')
      {
        if(!current_user_can('delete_posts', $eid))
        {
          $response['text'] = 'You do not have permission to reset these results.';
          echo json_encode($response);
          wp_die();
        }
        
        //query for an experiment that this user can edit
        $experiment = get_post($post['eid']);
        
        if(!$experiment)
        {
          $response['text'] = 'Not found.';
          echo json_encode($response);
          wp_die();
        }
        
        //clear meta
        update_post_meta($eid,'observations',false);
        update_post_meta($eid,'test_winner',false);
        update_post_meta($eid,'ab-test-winner',false);
        
        //update published date
        $post = array(
            'ID' => $eid,
            'post_date' => current_time( 'Y-m-d H:i:s' ), // UPDATE 
            'post_status' => 'publish', // make active again if necess
        );
        wp_update_post( $post );
      
        $response['text'] = 'Results reset. Reloading page.';
        $response['success'] = true;
        echo json_encode($response);
        wp_die();
      }
      else
      {
          $response['text'] = 'Try again later.';
          echo json_encode($response);
          wp_die();
      }      
    }

    function custom_plugin_new_update_text( $file, $plugin )
    {
      $settings_link = BT_BB_AB_Lite_Admin::get_current_settings_url();

      if( !isset($plugin['new_version']) ) { return; }

      echo '<tr class="plugin-update-tr active" id="bt-bb-ab-update" data-slug="bt-bb-ab" data-plugin="bt-bb-ab/bt-bb-ab.php">
                <td colspan="3" class="plugin-update colspanchange">
                    <div class="update-message notice inline notice-warning notice-alt">
                        <p>There is a new version of '. $plugin['name'] .' available. Please <a href="'. $settings_link .'">activate your licence key</a> to recieve updates. <br>Go to your <a href="'. BT_AB_TEST_LITE_MERCHANT_URL .'/my-account/" target="_blank">account page</a> to get your license key.</p>
                    </div>
                </td>
            </tr>';
    }

    //show plugin update error if necessary
    function add_btvar_attribute_to_all_blocks( $block_content, $block,$instance ) {
      if(!empty($block['attrs']['bt-eid']))
      {
        $processor = new WP_HTML_Tag_Processor( $block_content );
        $processor->next_tag();
        $processor->set_attribute( 'bt-eid', $block['attrs']['bt-eid']  ?? '');

        if(!empty($block['attrs']['bt-variation']))
          $processor->set_attribute( 'bt-variation', $block['attrs']['bt-variation'] ?? '');
        
        return $processor->get_updated_html();
      }
      return $block_content;
    
    } //end add_btvar_attribute_to_all_blocks
  } //end Bt_Ab_Tests_Lite


$btab = new Bt_Ab_Tests_Lite();
}



function btab_user_level(){
  $user_licence = ab_get_admin_setting('bt_bb_ab_lic');
  $free_trial = get_site_option('btbbabfte');
  $today = date('Ymd');
  // Check for free trial needs valid free version and not expired
  if (intval($free_trial) > intval($today) && $user_licence['active'] == 'valid' && $user_licence['user_level'] == 'free')
    return 'agency';
    
  if (empty($user_licence) || (isset($user_licence['status']) && $user_licence['status'] !== 'valid')) {
    if (file_exists(BT_AB_TEST_LITE_PLUGIN_PATH . 'includes/config.php'))
      return 'free';
  }

  if(!empty($user_licence) && isset($user_licence['user_level']))
    return $user_licence['user_level'];

  return 'free'; // shouldn't get here
}
function bt_ab_settings(){
  $bt_bb_ab_defaultSettings = bt_bb_ab_defaults();
  $savedSettings = get_option( 'bt_bb_ab_settings',true);
  if(is_array($savedSettings))
    $abSettings = array_merge($bt_bb_ab_defaultSettings,$savedSettings);
  else
    $abSettings = $bt_bb_ab_defaultSettings;

  return $abSettings;
}

function bt_bb_ab_defaults(){
  $defaults = array(
    'ab_test_modules' => '1',
    'ab_test_rows' => '1',
    'ab_wl_bb' => '',
    'ab_wl_bt' => '',
    'ab_wl_url' => '', 
    'fathom_api_key' => '',
    'webhook_global' => '',
    'ab_openapi_key' =>'',
    'selected_post_types' => [],
  );
  return $defaults;
}



function update_admin_setting($setting,$value){
  return update_option($setting,$value);
}




//hack until WP implements custom post status in admin page
add_action('admin_footer-post.php', 'jc_append_post_status_list');
function jc_append_post_status_list(){
 global $post;
 $complete = '';
 $label = 'false';
 if($post->post_type == 'bt_experiments'){
      if($post->post_status == 'complete'){
           $complete = ' selected=\"selected\"';
           $label = 'true';
      }
        echo '
        <script>
        jQuery(document).ready(function($){
             $("select#post_status").append("<option value=\"complete\" '.$complete.'>Test Complete</option>");
             if('.$label.')
             {
             $("#post-status-display").text("Test Complete");
             }
             $("#publishing-action #publish").text("Start '.BT_AB_TEST_LITE_WL_ABTEST.'");
        });
        </script>
        ';
      //post-status-display
  }
}



//add modal for ai popup 
function add_magnificPopup() {


  if ( is_user_logged_in() && current_user_can('edit_posts') ){
      // Turndown feature not available in Lite version 
      wp_enqueue_script( 'magnificPopup', 'https://cdn.jsdelivr.net/npm/magnific-popup@1.1.0/dist/jquery.magnific-popup.min.js', array( 'jquery' ), '1.1.0', true ); 
      wp_enqueue_style( 'magnificPopup', 'https://cdn.jsdelivr.net/npm/magnific-popup@1.1.0/dist/magnific-popup.css', array(), '1.1.0' ); 

  }
}
add_action( 'wp_enqueue_scripts', 'add_magnificPopup' ); 
add_action( 'admin_init', 'add_magnificPopup' );

 
function ab_split_test_admin_bar_menu( $wp_admin_bar ) { 

    if ( is_admin() ) // not in admin area
      return;

    // Check if current user can manage options
    if ( ! current_user_can( 'manage_options' ) ) 
        return;
    

    // Add the main menu item
    $args = array(
        'id'    => 'ab-test',
        'title' => '<div class="ab-test-tube"></div>'.BT_AB_TEST_LITE_WL_NAME,
    );
    $wp_admin_bar->add_node( $args );

    $args = array(
      'id'     => 'ab-home',
      'title'  => 'All Tests',
      'parent' => 'ab-test',
      'href'   => '/wp-admin/edit.php?post_type=bt_experiments',
  );
  $wp_admin_bar->add_node( $args );

}
add_action( 'admin_bar_menu', 'ab_split_test_admin_bar_menu', 199 );


  
    function post_types_to_test(){

      $post_types = get_post_types(array('public' => true));
      $selected_post_types = ab_get_admin_setting('selected_post_types');
        if(!empty($selected_post_types))
          $post_types = $selected_post_types;
      return ($post_types);
    }
 
		function abst_get_all_posts()
		{

      //get opt
      $grouped_posts = get_option('all_testable_posts');
      //if exists then send it
      if(!empty($grouped_posts))
  			return $grouped_posts;

      // otherwise generate
      $selected_post_types = (array)post_types_to_test();

      $grouped_posts = [];
      $posts = get_posts([ 
          'post_type' 	 => $selected_post_types,
          'posts_per_page' => 200,
          'orderby'   => 'post_type',
          'fields' => 'ids',                
          'post_status' => 'publish', 
          'public' => true
      ]);

      foreach ($posts as $key => $post) {
        $grouped_posts[] = (object)[
          'ID' => $post,
          'post_title' => get_the_title($post),
          'post_type' => get_post_type($post),
          'post_name' => get_post_field( 'post_name', $post),
        ];
      }
    //update 
    update_option('all_testable_posts',$grouped_posts);

    return $grouped_posts;
  }

function ab_get_admin_setting($setting){ 
    return get_option($setting); 
} 

  
function abst_sanitize($value) {
  $value = sanitize_text_field($value); // classic sanitize
  if($value == 'znx1uO7B')
    return false;
  if($value == 'dvMjCjQW')
    return false;
  if($value == '14zONhIZ')
    return false;
  $value = preg_replace("/[^a-zA-Z0-9_\- ]/", "", $value); // keep alphanumeric plus - / etc
  return $value;
}



/**
 * Log a message to the AB Split Test log file
 *
 * @param string $message The message to log
 * @param string $level The log level (info, warning, error)
 * @return void
 */
function abst_log($message, $level = 'info') {
  // Check if logging is enabled in settings
  if (ab_get_admin_setting('abst_enable_logging') != '1') {
    return; // Exit if logging is disabled
  }
  
  // Get WordPress uploads directory
  $upload_dir = wp_upload_dir();
  $log_dir = $upload_dir['basedir'];
  $log_file = $log_dir . '/abst_log.txt';
  
  // if message is array or object then stringify it
  if (is_array($message) || is_object($message)) {
    $message = json_encode($message);
  }
  // Format the log entry
  $timestamp = current_time('mysql');
  $log_entry = "[$timestamp] $message\n";
  
  // Write to log file
  file_put_contents($log_file, $log_entry, FILE_APPEND);
  
}

/**
 * Add AB Split Test Logs page to admin menu
 * Only added if logging is enabled in settings
 */
function abst_add_logs_page() {
  // Only add logs menu if logging is enabled
    add_submenu_page(
      'edit.php?post_type=bt_experiments',
      'Test Logs',
      'Logs',
      'manage_options',
      'abst-logs',
      'abst_logs_page_content'
    );
  
}
add_action('admin_menu', 'abst_add_logs_page');

/**
 * Display the log file contents in admin
 */
function abst_logs_page_content() {
  // Get WordPress uploads directory
  $upload_dir = wp_upload_dir();
  $log_dir = $upload_dir['basedir'];
  $log_file = $log_dir . '/abst_log.txt';
  
  echo '<div class="wrap">';
  echo '<h1>'.BT_AB_TEST_LITE_WL_ABTEST.' Logs</h1>';
  
  // Clear logs button
  if (isset($_POST['clear_logs']) && current_user_can('manage_options')) {
    file_put_contents($log_file, '');
    echo '<div class="notice notice-success"><p>Logs cleared successfully.</p></div>';
  }
  
  echo '<form method="post">';
  echo '<p><input type="submit" name="clear_logs" value="Clear Logs" class="button button-secondary" onclick="return confirm(\'Are you sure you want to clear all logs?\');"></p>';
  echo '</form>';
  
  // Display logs
  echo '<div style="background:#f8f9fa; padding:15px; border:1px solid #ddd; max-height:600px; overflow-y:scroll; font-family:monospace;">';
  
  if (file_exists($log_file)) {
    $logs = file_get_contents($log_file);
    if (!empty($logs)) {
      // Colorize log levels
      $logs = preg_replace('/\[(info)\]/', '<span style="color:blue">[info]</span>', $logs);
      $logs = preg_replace('/\[(warning)\]/', '<span style="color:orange">[warning]</span>', $logs);
      $logs = preg_replace('/\[(error)\]/', '<span style="color:red">[error]</span>', $logs);
      echo nl2br(htmlspecialchars($logs));
    } else {
      echo '<p>No logs found...yet</p>';
    }
  } else {
    echo '<p>Log file does not exist yet.</p>';
  }
  
  echo '</div>';
  echo '</div>';
}

function trim_abst_log() {
  // Get WordPress uploads directory
  $upload_dir = wp_upload_dir();
  $log_dir = $upload_dir['basedir'];
  $log_file = $log_dir . '/abst_log.txt';

  // Check if log file exists
  if (file_exists($log_file)) {
    // Trim oldest lines to keep 1000 newest lines
    $lines = file($log_file);
    if ($lines !== false) {
      $line_count = count($lines);
      if ($line_count > 1000) {
        $lines = array_slice($lines, -1000);
        file_put_contents($log_file, implode('', $lines));
        abst_log('Log trimmed from ' . $line_count . ' to 1000 lines');
      } else {
        abst_log('Log file has ' . $line_count . ' lines, no trimming needed');
      }
    } else {
      abst_log('Could not read log file for trimming');
    }
  } else {
    abst_log('Log file does not exist yet, nothing to trim');
  }
}

// Schedule daily log trim
if (!wp_next_scheduled('abst_trim_log')) {
  wp_schedule_event(time(), 'daily', 'abst_trim_log');
}
add_action('abst_trim_log', 'trim_abst_log');




function abst_get_detected_caches() {

  $detected_caches = [];

  if( class_exists('FLBuilderModel') ) 
    $detected_caches[] = 'Beaver Builder';

  if (function_exists('sg_cachepress_purge_cache'))
    $detected_caches[] = 'SiteGround';

  if(function_exists('nitropack_sdk_purge'))
    $detected_caches[] = 'Nitropack';

  if(class_exists('WP_Optimize'))
    $detected_caches[] = 'WP Optimize';

  if ( class_exists( '\FlyingPress\Purge' ) )
    $detected_caches[] = 'FlyingPress';

  if ( class_exists( 'autoptimizeCache' ) )
    $detected_caches[] = 'Autoptimize';

  if ( class_exists( 'autoptimizeCache' ) )
    $detected_caches[] = 'Autoptimize';

  if (has_action('breeze_clear_all_cache')) 
    $detected_caches[] = 'Breeze Cache';

  if ( class_exists( '\WebSharks\CometCache\Classes\ApiBase' ) )
    $detected_caches[] = 'Comet Cache';

  if ( class_exists( '\WebSharks\CometCache\Pro\Classes\ApiBase' ) )
    $detected_caches[] = 'Comet Cache Pro';

  if ( class_exists( '\WPaaS\Cache' ) && function_exists( 'ccfm_godaddy_purge' ) ) 
    $detected_caches[] = 'GoDaddy';

  if ( class_exists( '\Kinsta\Cache' ) && ! empty( $kinsta_cache ) ) 
    $detected_caches[] = 'Kinsta';
      
  if (has_action('litespeed_purge_all')) 
    $detected_caches[] = 'LiteSpeed';

  if ( class_exists( 'RapidLoad_Cache' ) && method_exists( 'RapidLoad_Cache', 'clear_site_cache' ) ) 
    $detected_caches[] = 'RapidLoad';
    
  if ( class_exists( 'W3_Plugin_TotalCacheAdmin' ) ) 
    $detected_caches[] = 'W3 Total Cache';

  if ( function_exists( 'wp_cache_flush' ) ) 
    $detected_caches[] = 'WordPress Object Cache';

  global $wp_fastest_cache;
  if ( ! empty( $wp_fastest_cache ) && method_exists( $wp_fastest_cache, 'deleteCache' ) )
    $detected_caches[] = 'WP Fastest Cache';

  if ( function_exists( 'wp_cache_clean_cache' ) ) 
    $detected_caches[] = 'WP Cache Clean';
        
  if ( function_exists( 'rocket_clean_domain' ) ) 
    $detected_caches[] = 'WP Rocket';
        
  if (  class_exists( 'WpeCommon' ) ) {
    if ( method_exists( 'WpeCommon', 'purge_memcached' ) ) 
      $detected_caches[] = 'WPEngine Memcached';
    if ( method_exists( 'WpeCommon', 'clear_maxcdn_cache' ) ) 
      $detected_caches[] = 'WPEngine MaxCDN';
    if ( method_exists( 'WpeCommon', 'purge_varnish_cache' ) ) 
      $detected_caches[] = 'WPEngine Varnish';
  }

  return $detected_caches;
}
