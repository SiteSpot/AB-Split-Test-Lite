<?php



/**

 * Plugin Name:       AB Split Test Lite

 * Plugin URI:        https://absplittest.com

 * Description:       A/B Split testing for WordPress - Test Pages, Blocks, Elementor, Bricks, Beaver Builder, Oxygen, Breakdance, WP Bakery and more. Free version limited to 1 active test and 1 variation.

 * Version:           1.0.0

 * Author:            AB Split Test

 * Author URI:        https://absplittest.com

 * License:           GPL-2.0+

 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt

 * Text Domain:       ab-split-test-lite

 * Domain Path:       /languages

 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'BT_AB_TEST_PLUGIN_NAME', 'AB Split Test' );  

define( 'BT_AB_TEST_VERSION', '1.0.0' );

define( 'BT_AB_TEST_PLUGIN_DIR', __FILE__ );

define( 'BT_AB_TEST_PLUGIN_PATH', plugin_dir_path( __FILE__ )  );



define( 'BT_AB_TEST_PLUGIN_URI', plugins_url('/', __FILE__) ); 

$abst_parts = explode('/', BT_AB_TEST_PLUGIN_PATH, -1);

$abst_folder_path = end($abst_parts);

define('BT_AB_PLUGIN_FOLDER', $abst_folder_path);


if (!defined('BT_AB_TEST_WL_ABTEST')) {
  $wl_ab_test = apply_filters('abst_wl_ab_test', 'Split Test');
  // Backward compatibility: also allow old hook name (new hook takes precedence)
  $wl_ab_test = apply_filters('ab_wl_ab_test', $wl_ab_test);
  define('BT_AB_TEST_WL_ABTEST', $wl_ab_test);
}

if (!defined('ABST_JOURNEY_DIR')) define( 'ABST_JOURNEY_DIR', trailingslashit( wp_upload_dir()['basedir'] ) . 'abst/journeys' );

if (!defined('ABST_CACHE_EXCLUDES')) define( 'ABST_CACHE_EXCLUDES', " data-cfasync='false' nitro-exclude data-no-optimize='1' data-no-defer='1' data-no-minify='1' nowprocket " );





if ( ! function_exists( 'is_plugin_active_for_network' ) ) {

    require_once( ABSPATH . '/wp-admin/includes/plugin.php' );

}



// WP-CLI commands are PRO only

// if (defined('WP_CLI') && WP_CLI) {

//   require_once plugin_dir_path(__FILE__) . 'includes/class-absplittest-cli.php';

// }







if(! class_exists ( 'Bt_Ab_Tests'))

{

  class Bt_Ab_Tests{

    

    function __construct(){



      add_filter( 'post_updated_messages', array( $this, 'abst_custom_post_updated_messages' ) );



      add_filter( 'init', array( $this,'bt_experiments_post_register'),99, 2); //register post type n status

      add_filter( 'init', array( $this,'bt_include_module'),10, 2);

      

      add_filter( 'init', array( $this, 'bt_include_support_module' ), 10, 2 );



      add_action('admin_head', array($this, 'bt_include_ajax_url'), 10);

      add_filter( 'post_row_actions', array($this,'remove_bulk_actions'),10,2); // remove quick edit

      add_filter( 'fl_builder_register_settings_form' , array($this,'add_ab_settings'),9999999,2 ); // register settings forms 

      add_filter( 'fl_builder_row_attributes', array($this,'add_experiment_row_attributes'),10,2 ); // add ab settings to row

      add_filter( 'fl_builder_module_attributes', array($this,'add_experiment_row_attributes'),10,2 ); // add ab settings to module

      add_filter( 'manage_bt_experiments_posts_columns', array($this,'experiments_posts_columns'), 10, 1 ); // add experiment data to columns in admin



      add_filter( 'body_class', [$this, 'updated_body_class'] );

      add_filter( 'bt_can_user_view_variations', [$this, 'can_user_view_variations'], 10, 1 );

      add_filter( 'display_post_states',  [$this, 'test_post_states'], 10, 2);

      add_action( 'post_submitbox_misc_actions', [$this, 'render_status_lifecycle_panel'], 20, 1 );



      add_action( 'wp_head', array($this,'render_experiment_config'),10);

      add_action( 'wp_head', [$this, 'header_style'] );

      // Premium hooks removed in lite version:

      // add_action( 'fingerprint_cleanup_event', [$this,'clear_fingerprint_database']);

      // add_action( 'admin_init', [$this, 'handle_ab251_upgrade']);

      // add_action( 'admin_init', [$this, 'cleanup_fullpage_observations']);

      // add_action( 'admin_init', 'abst_handle_trial_extension');      

      // add_action( 'admin_init', [$this, 'handle_sample_data_request']);

      add_action( 'admin_init', [$this, 'handle_mcp_adapter_install']);

      add_action( 'rest_api_init', [$this, 'register_rest_routes']);

      add_action( 'wp_abilities_api_init', [$this, 'register_abilities']);

      add_filter( 'views_edit-bt_experiments', [$this, 'rename_all_to_active_tests']);

      //add canonical to page variations

      add_filter('get_canonical_url', [$this, 'get_canonical_url'], 99, 2 );



      add_action( 'wp_enqueue_scripts', array($this,'include_conversion_scripts') );



      add_action( 'admin_enqueue_scripts', [$this, 'enqueue_experiment_scripts'] );

      add_action( 'wp_ajax_ab_page_selector', [$this,'get_posts_ajax_callback'] ); // wp_ajax_post_ac

      add_action( 'wp_ajax_blocks_experiment_list', [$this,'blocks_experiment_list'] ); // wp_ajax_post_ac

      add_action( 'wp_ajax_on_page_test_create', [$this,'on_page_test_create'] ); // wp_ajax_post_ac

      add_action( 'wp_ajax_create_new_on_page_test', [$this,'create_new_on_page_test'] ); // wp_ajax_post_ac

      // AI insight hooks removed in lite version (no calls home to absplittest.com)

      // Export data is a Pro feature — handler removed in Lite

      //save_variation_label 

      add_action( 'wp_ajax_save_variation_label', [$this, 'save_variation_label'] ); // wp_ajax_post_ac



      add_action( 'activated_plugin', [$this,'activate_plugin']);

      add_action( 'wp_enqueue_scripts', [$this,'include_highlighter_scripts'],9999 );

      add_action( 'enqueue_block_assets', [$this,'include_highlighter_scripts'],9999 );

      add_action( 'elementor/editor/before_enqueue_scripts', [$this,'include_highlighter_scripts'],9999 );

      add_action( 'oxygen_after_add_components', [$this,'add_oxy_split_options']);

      add_filter( 'render_block', [$this,'add_btvar_attribute_to_all_blocks'], 10, 3 );

      add_filter( 'op3_script_is_allowed_in_blank_template', [$this, 'allowInOptimizePress'], 10, 2); // optimizepress dont minify us

      add_filter( 'cmplz_whitelisted_script_tags', [$this, 'allowInComplianz'], 10, 1 ); // complianz: don't block our scripts



      register_deactivation_hook(__FILE__, array($this, 'bt_ab_uninstall'));

      

      // Also hook into plugin update process to catch automatic/remote updates

      add_action('upgrader_process_complete', array($this, 'on_plugin_update'), 10, 2);

      add_action( 'wp_ajax_bt_experiment_w', array($this,'abst_log_experiment_activity'), 10, 6 ); // render js to show tests and log interactions 

      add_action( 'wp_ajax_nopriv_bt_experiment_w', array($this,'abst_log_experiment_activity'), 10, 6 ); // render js to show tests and log interactions - logged out user

      add_action( 'wp_ajax_abstdata', array($this,'process_batch_data'), 10, 6 ); // render js to show tests and log interactions 

      add_action( 'wp_ajax_nopriv_abstdata', array($this,'process_batch_data'), 10, 6 ); // render js to show tests and log interactions - logged out user

      add_action( 'wp_ajax_abst_event', array($this,'abst_log_experiment_activity'), 10, 6 ); // render js to show tests and log interactions 

      add_action( 'wp_ajax_nopriv_abst_event', array($this,'abst_log_experiment_activity'), 10, 6 ); // render js to show tests and log interactions - logged out user

      add_action( 'wp_ajax_abst_delete_variation', array($this,'abst_delete_variation'), 10, 6 ); // render js to show tests and log interactions - logged out user

      // Fingerprint/UUID tracking removed in lite version

      // add_action( 'wp_ajax_nopriv_ab_fp', array($this,'ab_fingerprint_event' ));

      // add_action( 'wp_ajax_ab_fp', array($this,'ab_fingerprint_event' ));

      // add_action( 'wp_ajax_ab_fp_event', array($this,'fingerprint_event'), 10, 0);

      // add_action( 'wp_ajax_nopriv_ab_fp_event', array($this,'fingerprint_event'), 10, 0);



      // Weekly reports stripped in lite version

      // add_action( 'wp_ajax_abst_send_test_email', [$this, 'abst_send_test_email'], 10, 0);



      add_action( 'bt_log_experiment_activity', [$this, 'abst_log_experiment_activity'], 10, 9 );

      add_action( 'manage_bt_experiments_posts_custom_column', array($this,'manage_bt_experiments_posts_custom_column'),10,2); // add data to admin columns

      add_action( 'add_meta_boxes', array($this,'add_experiment_meta_box'),10,1 );  // meta boxes for experiments

      add_action( 'save_post_bt_experiments', array($this,'save_postdata'),10,1 ); // catch page save to get update conversion URL meta

      add_action( 'trashed_post', array($this,'experiment_trash_handler'),10,1 ); // trashed, refresh conversions pages

      add_action( 'untrash_post', array($this,'experiment_trash_handler'),10,1 ); // restore trashed post, refresh conversions pages

      add_action( 'pre_get_posts', array($this, 'abst_authors_see_own_posts_filter'));

      add_action( 'post_updated', array($this,'refresh_on_update'),10,3 );

      add_action( 'transition_post_status', array($this,'post_status_transition'), 10, 3);



      add_action( 'wp_head', array($this,'conversion_targeting'),10,1); // add conversion targeting page script

      add_action( 'wp_head', [$this, 'fl_preview_script'] );

      add_action( 'after_setup_theme', [$this, 'hide_admin_bar_in_heatmap_iframe'] ); // hide admin bar in heatmap iframe

      add_action( 'wp_ajax_bt_clear_experiment_results',   array($this,'wp_ajax_bt_clear_results'), 10, 2 );  // render js to show tests and log interactions 

      // Premium conversion integrations removed in lite version:

      // add_action( 'edd_complete_purchase', [$this,'edd_trigger_conversion']);

      // add_action('fluent_cart/order_paid', [$this, 'abst_fluent_cart_order_paid'], 10, 1);

      // add_action( 'woocommerce_thankyou', [$this,'enqueue_order_total_script']);

      // add_action( 'woocommerce_order_status_changed', [$this,'woo_convert_on_checkout'], 10, 3 );

      // add_action( 'load-plugins.php', [$this, 'wp_plugin_update_rows'], 30 );

      add_action( 'wp_ajax_bt_generate_embed_code', [$this, 'bt_generate_embed_code'] );



      //bakery

      add_filter('vc_shortcodes_css_class', [$this, 'add_split_test_attributes_to_bakery'], 11, 3);

      add_action('vc_after_init', [$this, 'add_split_test_params_to_bakery']);

      add_filter( 'vc_autocomplete_vc_row_test_id_callback', [$this, 'bakery_autocomplete'], 10, 3 ); 

      add_filter( 'vc_autocomplete_vc_row_test_id_render', [$this, 'bakery_autocomplete_render'], 10, 3 ); 

      add_filter( 'vc_autocomplete_vc_section_test_id_callback', [$this, 'bakery_autocomplete'], 10, 3 ); 

      add_filter( 'vc_autocomplete_vc_section_test_id_render', [$this, 'bakery_autocomplete_render'], 10, 3 ); 



      add_shortcode( 'ab_split_test_report', [$this,'ab_split_test_report'] );

      add_shortcode('ab_split_test', [$this,'convert_ab_split_test_shortcode']);

      


      // fix for bad oxy code update in 4.3 where second argument sometimes doesnt exist

      add_action('oxygen_vsb_component_attr', function ($options){

        if(!empty($options['btVar']) && !empty($options['btExperiment']) )

          echo " bt-variation='".esc_attr($options['btVar'])."' bt-eid='".esc_attr($options['btExperiment'])."' ";

      });



      //admin 

      if(is_admin())

      {

        include_once( plugin_dir_path(__FILE__) . 'admin/bt-bb-ab-admin.php');

        $bt_bb_ab_admin = new BT_BB_AB_Admin;

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

            $query->get('post_type') === 'bt_experiments'

        ) {

            // Exclude completed tests from default view unless specifically filtered

            if (!isset($_GET['post_status'])) {

                $query->set('post_status', array('publish', 'draft', 'pending', 'private'));

            }

            

            // Author filtering for non-admin users

            if (

                apply_filters('abst_authors_see_own_posts', false) &&

                !current_user_can('manage_options') &&

                !current_user_can('edit_others_posts')

            ) {

                $query->set('author', get_current_user_id());

            }

        }

    }



    /**

     * Rename "All" to "Active Tests" and "Published" to "Running" in the post status views

     * since the default view now excludes completed tests

     */

    public function rename_all_to_active_tests($views) {

        if (isset($views['all'])) {

            $views['all'] = str_replace('All', 'Active Tests', $views['all']);

        }

        if (isset($views['publish'])) {

            $views['publish'] = str_replace('Published', 'Running', $views['publish']);

        }

        return $views;

    }



    function add_split_test_params_to_bakery() {

        // Fetch published bt_experiments for dropdown

        vc_add_param('vc_row', array(

            'type' => 'autocomplete',

            'heading' => __('Choose a Test or  <a class="new-on-page-test-button" style="display:inline-block;margin-bottom:10px;" href="javascript:void(0);"> Create a new test</a>', 'ab-split-test-lite'),

            'param_name' => 'test_id',

            'description' => __('Choose an existing split test', 'ab-split-test-lite'),

            'group' => __('Split Test Settings', 'ab-split-test-lite'),

        ));

        vc_add_param('vc_row', array(

            'type' => 'textfield',

            'heading' => __('Test Variation', 'ab-split-test-lite'),

            'param_name' => 'test_variation',

            'description' => __('Enter the test variation name.', 'ab-split-test-lite'),

            'group' => __('Split Test Settings', 'ab-split-test-lite'),

        ));



        //do the same for vc_section

        vc_add_param('vc_section', array(

            'type' => 'autocomplete',

            'heading' => __('Choose a Test or  <a class="new-on-page-test-button" style="display:inline-block;margin-bottom:10px;" href="javascript:void(0);"> Create a new test</a>', 'ab-split-test-lite'),

            'param_name' => 'test_id',

            'description' => __('Choose an existing split test', 'ab-split-test-lite'),

            'group' => __('BT_AB_TEST_WL_ABTEST Settings', 'ab-split-test-lite'),

        ));

        vc_add_param('vc_section', array(

            'type' => 'textfield',

            'heading' => __('Test Variation', 'ab-split-test-lite'),

            'param_name' => 'test_variation',

            'description' => __('Enter the test variation name.', 'ab-split-test-lite'),

            'group' => __('Split Test Settings', 'ab-split-test-lite'),

        ));



    }





    // Autocomplete callback for Test ID (bt_experiment posts with test_type=on_page)

    function bakery_autocomplete( $query, $tag, $param_name ) {



      //if qiery is id

      if(is_numeric($query))

      {

        $post = get_post($query);

        if($post && $post->post_type == 'bt_experiments' && $post->post_status == 'publish')

        {

          return array(

              'value' => $post->ID,

              'label' => $post->ID . ' - ' . $post->post_title . ' [' . $post->post_name . ']',

          );

        }

      }



      $args = array(

          'post_type'      => 'bt_experiments',

          'post_status'    => 'publish',

          'posts_per_page' => 10,

          'meta_query'     => array(

              array(

                  'key'   => 'test_type',

                  'value' => 'ab_test',

              ),

          ),

          's'              => $query,

      );

      $posts = get_posts( $args );

      $results = array();

      foreach ( $posts as $post ) {

          $results[] = array(

              'value' => $post->ID,

              'label' => $post->ID . ' - ' . $post->post_title . ' [' . $post->post_name . ']',

          );

      }

      return $results;

    }

    

    function bakery_autocomplete_render( $value, $settings, $tag ) {

      $post = get_post( $value['value'] ?? $value );

      if ( $post && $post->post_type === 'bt_experiments' ) {

          return array(

              'value' => $post->ID,

              'label' => $post->ID . ' - ' . $post->post_title . ' [' . $post->post_name . ']',

          );

      }

      return $value;

    }





    function add_split_test_attributes_to_bakery($class_string, $tag, $atts = null) {

      // Early return if no atts provided or not the right tag

      if (!$atts || !is_array($atts) || !in_array($tag, ['vc_row', 'vc_section'])) {

          return $class_string;

      }

      

      if (isset($atts['test_id']) && !empty($atts['test_id'])) {

          $class_string .= ' ab-'. sanitize_html_class($atts['test_id']);

      }

      if (isset($atts['test_variation']) && !empty($atts['test_variation'])) {

          $class_string .= ' ab-var-'. sanitize_html_class($atts['test_variation']);

      }

      

      return $class_string;

  }

  



  

    function add_canonical_to_page_variations() {

      // add rel="canonical" to variation pages pointing to default page

      //get current page id

      $pageID = get_the_ID();

      //<link rel="canonical" href="https://example.com/preferred-url-here/" />

    }





    function activate_plugin($plugin) {

      if($plugin == plugin_basename(__FILE__)) {

          if(is_plugin_active_for_network(BT_AB_PLUGIN_FOLDER.'/bt-bb-ab.php')) {

              wp_safe_redirect(network_admin_url('admin.php?page=bt_bb_ab_test&wizard=1'));

          } else {

              wp_safe_redirect(admin_url('options-general.php?page=bt_bb_ab_test&wizard=1'));

          }

          exit;

      }

    }



     function bt_ab_uninstall() {

      abst_log('bt_ab_uninstall');

    }













    /**

     * Fires immediately after plugin update (automatic, manual, or remote)

     * This catches updates that happen via WP Cron, MainWP, ManageWP, etc.

     * 

     * @param WP_Upgrader $upgrader_object

     * @param array $options

     */

    function on_plugin_update($upgrader_object, $options) {

      // Check if this is a plugin update

      if (!isset($options['action']) || $options['action'] !== 'update') {

        return;

      }

      if (!isset($options['type']) || $options['type'] !== 'plugin') {

        return;

      }

      

      // Check if our plugin was updated

      $our_plugin = plugin_basename(__FILE__);

      

      if (isset($options['plugins'])) {

        // Bulk update

        foreach ($options['plugins'] as $plugin) {

          if ($plugin === $our_plugin) {

            $this->clear_cache_on_update();

            break;

          }

        }

      } elseif (isset($options['plugin']) && $options['plugin'] === $our_plugin) {

        // Single plugin update

        $this->clear_cache_on_update();

      }

    }



    /**

     * Clear cache and update version number on plugin update

     * Uses existing comprehensive cache clearing function

     */

    function clear_cache_on_update() {

      $stored_version = get_option('abst_plugin_version');

      $current_version = BT_AB_TEST_VERSION;

      

      abst_log('Plugin updated from ' . ($stored_version ?: 'unknown') . ' to ' . $current_version . ' - clearing all caches if allowed');

      

      $this->refresh_conversion_pages();

      

      // Run version-specific upgrades

      if (!$stored_version || version_compare($stored_version, '2.4.1', '<')) {

        $this->upgrade_to_241();

        update_option('abst_upgrade_241_complete', true);

      }



      // 2.5.0: Clean up orphaned magic test variations from 2.4.1-2.4.3 bug

      if (!$stored_version || version_compare($stored_version, '2.5.0', '<')) {

        $this->upgrade_to_250();

      }



      // 2.5.1: Backfill wildcard scope on legacy Magic Tests that predate scope support.

      if (!$stored_version || version_compare($stored_version, '2.5.1', '<')) {

        $this->upgrade_to_251();

      }



      $this->refresh_conversion_pages();

      

      // Update the stored version

      update_option('abst_plugin_version', $current_version, false);

      

      abst_log('All caches cleared and version updated to ' . $current_version);

    }



    /**

     * Upgrade to 2.4.1: Set log_on_visible=1 for existing tests

     * Preserves original visibility-based behavior for tests created before this version

     */

    function upgrade_to_241() {

      abst_log('Plugin upgrade_to_241');

      $experiments = get_posts([

        'post_type' => 'bt_experiments',

        'posts_per_page' => -1,

        'post_status' => 'any',

        'fields' => 'ids',

        'meta_query' => [

          [

            'key' => 'log_on_visible',

            'compare' => 'NOT EXISTS'

          ]

        ]

      ]);



      if (empty($experiments)) {

        abst_log('No experiments found needing upgrade');

        return;

      }



      $upgraded_count = 0;

      foreach ($experiments as $exp_id) {

        abst_log('Upgrading experiment ' . $exp_id);

        update_post_meta($exp_id, 'log_on_visible', '1');

        $upgraded_count++;

      }



      abst_log('Upgrade to 2.4.1: Set log_on_visible=1 for ' . $upgraded_count . ' existing tests');

    }



    /**

     * Upgrade to 2.5.0: Clean up orphaned magic test variations

     * Removes extra variations created by the 2.4.1-2.4.3 bug

     */

    function upgrade_to_250() {



      $tests = get_posts(array(

        'post_type'      => 'bt_experiments',

        'posts_per_page' => -1,

        'meta_key'       => 'test_type',

        'meta_value'     => 'magic'

      ));



      $removed = 0;

      foreach ($tests as $test) {

        $magic_def = json_decode(get_post_meta($test->ID, 'magic_definition', true), true);

        if (!is_array($magic_def) || empty($magic_def[0]['variations'])) continue;



        $expected = count($magic_def[0]['variations']); // variations includes original at index 0



        $obs = get_post_meta($test->ID, 'observations', true);

        if (!is_array($obs)) continue;



        $test_removed = 0;

        foreach ($obs as $key => $data) {

          if (strpos($key, 'magic-') !== 0) continue;



          // Support both legacy keys (magic-0) and tracked keys (magic-{testId}-{index}).

          if (!preg_match('/^magic-(?:\d+-)?(\d+)$/', (string) $key, $matches)) {

            continue;

          }



          $variation_index = intval($matches[1]);

          if ($variation_index >= $expected) {

            abst_log('upgrade_to_250: Removing orphaned variation ' . $key . ' from "' . $test->post_title . '" (#' . $test->ID . ')');

            unset($obs[$key]);

            $test_removed++;

          }

        }



        if ($test_removed > 0) {

          update_post_meta($test->ID, 'observations', $obs);

          $removed += $test_removed;

        }

      }

      if($removed > 0) {

        abst_log('upgraded to v2.5.0: Removed ' . $removed . ' orphaned variation(s)');

      }

      else

      {

        abst_log('upgraded to v2.5.0');

      }

    }



    function upgrade_to_251() {

      abst_log('upgrading to v2.5.1');

      require_once plugin_dir_path(__FILE__) . 'bt-bb-ab-validation.php';



      $tests = get_posts(array(

        'post_type'      => 'bt_experiments',

        'posts_per_page' => -1,

        'post_status'    => 'any',

        'meta_key'       => 'test_type',

        'meta_value'     => 'magic'

      ));



      $updated = 0;

      foreach ($tests as $test) {

        $raw_magic_def = get_post_meta($test->ID, 'magic_definition', true);

        if (empty($raw_magic_def)) continue;



        $magic_def = json_decode($raw_magic_def, true);

        if (json_last_error() !== JSON_ERROR_NONE || !is_array($magic_def)) continue;



        $needs_update = false;

        foreach ($magic_def as $index => $element) {

          if (!is_array($element)) continue;



          $scope = isset($element['scope']) && is_array($element['scope']) ? $element['scope'] : [];

          $has_scope_page_id = isset($scope['page_id']) && $scope['page_id'] !== '';

          $has_scope_url = isset($scope['url']) && trim((string) $scope['url']) !== '';



          if (!$has_scope_page_id && !$has_scope_url) {

            $magic_def[$index]['scope'] = ['page_id' => '*'];

            $needs_update = true;

          }

        }



        if (!$needs_update) continue;



        $magic_def = abst_normalize_magic_definition($magic_def);

        update_post_meta($test->ID, 'magic_definition', wp_json_encode($magic_def, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

        $updated++;

      }



      abst_log('upgraded to v2.5.1: Backfilled wildcard scope for ' . $updated . ' legacy magic test(s)');



      $legacy_ideas = json_decode(get_option('abst_plugin_test_ideas', '[]'), true);

      if (!is_array($legacy_ideas)) {

        $legacy_ideas = [];

      }



      $migrated_ideas = 0;

      foreach ($legacy_ideas as $legacy_idea) {

        if (!is_array($legacy_idea)) {

          continue;

        }



        $title = sanitize_text_field($legacy_idea['title'] ?? '');

        $page = sanitize_text_field($legacy_idea['page'] ?? '');

        $problem = sanitize_text_field($legacy_idea['problem'] ?? '');

        $hypothesis = sanitize_textarea_field($legacy_idea['hypothesis'] ?? '');

        $next_step = sanitize_textarea_field($legacy_idea['nextstep'] ?? '');



        if ($title === '') {

          if ($page !== '') {

            $title = $page;

          } elseif ($problem !== '') {

            $title = wp_trim_words($problem, 6, '');

          } elseif ($hypothesis !== '') {

            $title = wp_trim_words($hypothesis, 6, '');

          } else {

            $title = 'Test Idea';

          }

        }



        if ($hypothesis === '') {

          continue;

        }



        $post_data = [

          'post_title' => $title,

          'post_type' => 'bt_experiments',

          'post_status' => 'idea',

        ];



        $created_at = sanitize_text_field($legacy_idea['created_at'] ?? '');

        $created_timestamp = $created_at ? strtotime($created_at) : false;

        if ($created_timestamp) {

          $post_data['post_date'] = wp_date('Y-m-d H:i:s', $created_timestamp);

          $post_data['post_date_gmt'] = gmdate('Y-m-d H:i:s', $created_timestamp);

        }



        $idea_post_id = wp_insert_post($post_data, true);

        if (is_wp_error($idea_post_id) || !$idea_post_id) {

          continue;

        }



        update_post_meta($idea_post_id, 'abst_idea_hypothesis', $hypothesis);

        update_post_meta($idea_post_id, 'abst_idea_page_flow', $page);

        update_post_meta($idea_post_id, 'abst_idea_observed_problem', $problem);

        update_post_meta($idea_post_id, 'abst_idea_next_step', $next_step);



        foreach (['impact', 'reach', 'confidence', 'effort'] as $idea_score_key) {

          if (!isset($legacy_idea[$idea_score_key]) || $legacy_idea[$idea_score_key] === '') {

            continue;

          }



          update_post_meta($idea_post_id, 'abst_idea_' . $idea_score_key, max(1, min(5, intval($legacy_idea[$idea_score_key]))));

        }



        $migrated_ideas++;

      }



      delete_option('abst_plugin_test_ideas');

      abst_log('upgraded to v2.5.1: Migrated ' . $migrated_ideas . ' legacy test idea(s)');

    }



    function save_idea_config($post_id, $data = array()) {

      if (array_key_exists('abst_idea_hypothesis', $data) || array_key_exists('hypothesis', $data)) {

        $idea_hypothesis = sanitize_textarea_field($data['abst_idea_hypothesis'] ?? $data['hypothesis'] ?? '');

        update_post_meta($post_id, 'abst_idea_hypothesis', $idea_hypothesis);

      }



      if (array_key_exists('abst_idea_page_flow', $data) || array_key_exists('page', $data)) {

        $idea_page_flow = sanitize_textarea_field($data['abst_idea_page_flow'] ?? $data['page'] ?? '');

        update_post_meta($post_id, 'abst_idea_page_flow', $idea_page_flow);

      }



      if (array_key_exists('abst_idea_observed_problem', $data) || array_key_exists('problem', $data)) {

        $idea_problem = sanitize_textarea_field($data['abst_idea_observed_problem'] ?? $data['problem'] ?? '');

        update_post_meta($post_id, 'abst_idea_observed_problem', $idea_problem);

      }



      if (array_key_exists('abst_idea_next_step', $data) || array_key_exists('nextstep', $data)) {

        $idea_next_step = sanitize_textarea_field($data['abst_idea_next_step'] ?? $data['nextstep'] ?? '');

        update_post_meta($post_id, 'abst_idea_next_step', $idea_next_step);

      }



      foreach (['impact', 'reach', 'confidence', 'effort'] as $idea_score_key) {

        $meta_key = 'abst_idea_' . $idea_score_key;

        $has_value = array_key_exists($meta_key, $data) || array_key_exists($idea_score_key, $data);

        if (!$has_value) {

          continue;

        }



        $raw_value = $data[$meta_key] ?? $data[$idea_score_key] ?? '';



        if ($raw_value === '' || $raw_value === null) {

          delete_post_meta($post_id, $meta_key);

          continue;

        }



        update_post_meta($post_id, $meta_key, max(1, min(5, intval($raw_value))));

      }

    }






    public function abtest_shortcode( $atts, $content = "" )
    {
      $attr = shortcode_atts([
        'eid' => -1,
        'variation' => '',
        'class' => ''
      ], $atts);
      
      ob_start();



      echo '<div class="bt-abtest-wrap '. esc_attr($attr['class']) .'" bt-eid="'. intval($attr['eid']) .'" bt-variation="'. esc_attr($attr['variation']) .'">';

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

        echo "<script  id='abst_ajax'>var bt_ajaxurl = '".esc_url(admin_url( 'admin-ajax.php' ))."';";

        echo "var bt_adminurl = '".esc_url(admin_url())."';";

        //include plugin url

        echo "var bt_pluginurl = '".esc_url(plugin_dir_url( __FILE__ ))."';";

        echo "var bt_homeurl = '".esc_url(home_url())."';</script>";

      }

    }



    



    function value_currency_symbol(){



      if ( class_exists( 'woocommerce' ) )

        return get_woocommerce_currency_symbol(); // woostuffs

      

      return '$';//default

    }

    

    function bt_include_module()

    {

      define( 'BT_CONVERSION_DIR', plugin_dir_path( __FILE__ ) );

      define( 'BT_CONVERSION_URL', plugins_url( '/', __FILE__ ) );



      define( 'BT_MODULES_DIR', plugin_dir_path( __FILE__ ) );

      define( 'BT_MODULES_URL', plugins_url( '/', __FILE__ ) );



      // Lite: only include core modules (conversion, journey)

      include_once ( plugin_dir_path( __FILE__ ) . 'modules/conversion/conversion.php');

      $this->maybe_include_journey_module();

    }



    function maybe_include_journey_module() {

      if(abst_get_admin_setting('abst_enable_user_journeys') == 1) {

        include_once ( plugin_dir_path( __FILE__ ) . 'modules/journey.php');

        // Session replay depends on journey module AND session replays being enabled

        if(abst_get_admin_setting('abst_enable_session_replays') == 1) {

          include_once ( plugin_dir_path( __FILE__ ) . 'modules/session-replay/session-replay.php');

        }

      }

    }



    function bt_include_support_module() {

      include_once( plugin_dir_path( __FILE__ ) . 'modules/support/cache.php' );

      include_once ( plugin_dir_path( __FILE__ ) . 'modules/support/support.php'); 

    }



    function fl_preview_script()

    {

      if( class_exists('FLBuilderModel') && FLBuilderModel::is_builder_active() ) :

      ?>

        <style type="text/css">

          form[data-form-id="conversion"] #fl-builder-settings-section-ab_test_rows {

            display: none !important;

          }

        </style>

      <?php

      endif;

    }



    /**

     * Hide admin bar when page is loaded inside heatmap iframe

     */

    function hide_admin_bar_in_heatmap_iframe() {

      //if logged in and can edit posts

      if ( current_user_can('edit_posts') ) {

        if ( isset( $_GET['abst_heatmap_view'] ) && $_GET['abst_heatmap_view'] == '1' ) {

          show_admin_bar( false );

        }

      }

    }









    function conversion_targeting(){



      //legacy remove in 2026



      //bail on ajax

      if(wp_doing_ajax())

        return;

      

      $queried_object = get_queried_object();

      $target_post_id = [];

      $conversion_pages = get_option('bt_conversion_pages',false);



      // dont brick pages with no queried object (i.e. 404's)

      if(!empty($queried_object))

      {

        if (is_archive()) {

            // 1. Post Type Archives

            if (is_post_type_archive()) {

                $post_type = is_string($queried_object) ? $queried_object : $queried_object->name;

                $target_post_id[] = 'post-type-archive-' . $post_type;

                $target_post_id[] = 'archive-' . $post_type; // Backwards compat for tests created before 2.4.2

            } 

            // 2. Taxonomy Archives (category, tag, custom taxonomy)

            elseif (is_tax() || is_category() || is_tag()) {

                $taxonomy = $queried_object->taxonomy;

                $term_id = $queried_object->term_id;

                $target_post_id[] = 'taxonomy-' . $taxonomy . '-' . $term_id;

                

                // Also include the general taxonomy archive

                $target_post_id[] = 'taxonomy-' . $taxonomy;

            }

            // 3. Author Archives

            elseif (is_author()) {

                $target_post_id[] = 'author-' . get_queried_object_id();

            }

            // 4. Date-based Archives

            elseif (is_date()) {

                $date_parts = [get_query_var('year')];

                if (get_query_var('monthnum')) {

                    $date_parts[] = get_query_var('monthnum');

                }

                if (get_query_var('day')) {

                    $date_parts[] = get_query_var('day');

                }

                $target_post_id[] = 'date-' . implode('-', $date_parts);

            }

        }

        // Handle singular posts/pages

        elseif (isset($queried_object->ID)) {

            $target_post_id[] = $queried_object->ID;

        }

      }

        

      // Handle home and blog (posts index) pages specifically

      if (is_front_page()) {

        $target_post_id[] = get_option('page_on_front');

      }

      if (is_home()) {

        $blog_id = get_option('page_for_posts');

        if ($blog_id) {

          // Add both the page ID and a special archive identifier for the blog

          $target_post_id[] = $blog_id;

          $target_post_id[] = 'post-type-archive-post';

          $target_post_id[] = 'archive-post'; // Backwards compat

        } else {

          // If blog page is not a static page (home page set as blog page) use 'homeblog' as fallback identifier

          $target_post_id[] = 'homeblog';

          $target_post_id[] = 'post-type-archive-post';

          $target_post_id[] = 'archive-post'; // Backwards compat

        }

      }

    

      //woo 

      if ( class_exists( 'WooCommerce' ) ) {

        if(is_shop()){

          $target_post_id[] = wc_get_page_id('shop');

        }

        if(is_wc_endpoint_url( 'order-pay' )){

          $target_post_id[] = 'woo-order-pay';

        }

        if(is_wc_endpoint_url( 'order-received' ) && (abst_get_admin_setting( 'abst_server_convert_woo' ) !== '1')){ // if its order recieved and we arent doing it server side

          $target_post_id[] = 'woo-order-received';

        }

      }

      //end woo



      //WP Pizza

      if ( class_exists( 'WPPIZZA' ) ) {

        if(wppizza_is_checkout()){

          $target_post_id[] = 'wp-pizza-is-checkout';

        }

        if(wppizza_is_orderhistory()){

          $target_post_id[] = 'wp-pizza-is-order-history';

        }

      }

      //end pizza





      

      //if its 404

      if(is_404())

      {

        $target_post_id[] = '404-not-found';

      }

      

      echo "<script ".esc_attr(ABST_CACHE_EXCLUDES)." id='abst_conv_details'>

          var conversion_details = ".wp_json_encode($conversion_pages).";

          var current_page = ".wp_json_encode($target_post_id).";

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

      // get user level


      //count all custom posts bt_experiments

      require_once plugin_dir_path(__FILE__) . 'bt-bb-ab-validation.php';

      if(abst_lite_active_test_count($post_id) >= 1)

      {

        //change current post status to draft

        remove_action( 'save_post_bt_experiments', [$this,'save_postdata'], 10 );

        wp_update_post(['ID' => $post_id, 'post_status' => 'draft']);

        add_action( 'save_post_bt_experiments', [$this,'save_postdata'], 10, 1 );

        abst_log('Free licence limited to one active non-sample test. Upgrade https://absplittest.com/pricing?utm_source=ug');

        return;

      }

      // Lite: limit to 1 variation maximum across all test types

      if (!empty($data['page_variations'])) {

        $data['page_variations'] = array_slice((array)$data['page_variations'], 0, 1, true);

      }

      if (!empty($data['css_test_variations']) && intval($data['css_test_variations']) > 1) {

        $data['css_test_variations'] = 1;

      }

      if (!empty($data['magic_definition'])) {

        $magic_def = json_decode($data['magic_definition'], true);

        if (is_array($magic_def)) {

          foreach ($magic_def as &$element) {

            if (isset($element['variations']) && is_array($element['variations']) && count($element['variations']) > 2) {

              $element['variations'] = array_slice($element['variations'], 0, 2);

            }

          }

          $data['magic_definition'] = wp_json_encode($magic_def);

        }

      }

    

    $requested_status = sanitize_text_field((string) ($data['post_status'] ?? get_post_status($post_id)));

    if($requested_status === 'idea')

    {

      $this->save_idea_config($post_id, $data);

      return;

    }



    if(

      isset($data['abst_idea_hypothesis']) ||

      isset($data['abst_idea_page_flow']) ||

      isset($data['abst_idea_observed_problem']) ||

      isset($data['abst_idea_next_step']) ||

      isset($data['abst_idea_impact']) ||

      isset($data['abst_idea_reach']) ||

      isset($data['abst_idea_confidence']) ||

      isset($data['abst_idea_effort'])

    ) {

      $this->save_idea_config($post_id, $data);

    }

    //test type 

    

    $url_query = sanitize_text_field($data['test_type'] ?? ''); //ab_test full_page css_test magic

    update_post_meta( $post_id, 'test_type', $url_query );



    //get json magic definition

    // magic test

    $magic_definition = $data['magic_definition'] ?? '';

    if (!empty($magic_definition)) {

      require_once plugin_dir_path(__FILE__) . 'bt-bb-ab-validation.php';

      

      $decoded = json_decode($magic_definition, true);

      

      if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {

          $existing_magic_definition = get_post_meta($post_id, 'magic_definition', true);

          $is_legacy_magic_update = !empty($existing_magic_definition);



          if ($is_legacy_magic_update) {

              // Backfill wildcard scope for pre-2.5 magic tests so legacy configs stay editable.

              foreach ($decoded as $index => $element) {

                  if (!is_array($element)) {

                      continue;

                  }



                  $scope = isset($element['scope']) && is_array($element['scope']) ? $element['scope'] : [];

                  $has_scope_page_id = isset($scope['page_id']) && $scope['page_id'] !== '';

                  $has_scope_url = isset($scope['url']) && trim((string) $scope['url']) !== '';



                  if (!$has_scope_page_id && !$has_scope_url) {

                      $decoded[$index]['scope'] = ['page_id' => '*'];

                  }

              }

          }



          // Normalize scope (sanitize and format)

          $decoded = abst_normalize_magic_definition($decoded);

          

          // Validate magic definition including scope requirement

          $validation_result = abst_validate_magic_definition($decoded);

          if (is_wp_error($validation_result)) {

              abst_log('ERROR: Magic definition validation failed: ' . $validation_result->get_error_message());

              // Don't save invalid data - keep existing value

              return;

          }

          

          // Sanitize each element - but only for untrusted input

          foreach ($decoded as $index => $element) {

              if (isset($element['selector'])) {

                  $decoded[$index]['selector'] = sanitize_text_field($element['selector']);

              }

              if (isset($element['type'])) {

                  $decoded[$index]['type'] = sanitize_text_field($element['type']);

              }

          }

          

          // Re-encode as JSON

          $magic_definition = wp_json_encode($decoded, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

      } else {

          // Don't wipe data on JSON error - preserve what was submitted

          // Admin is trying to save something, even if malformed

          abst_log('WARNING: magic_definition JSON decode failed. Error: ' . json_last_error_msg());

          $magic_definition = wp_kses_post($magic_definition);

      }

  }



    update_post_meta( $post_id, 'magic_definition', $magic_definition );

    

    // base page for full page split test

    // bt_experiments_full_page_default_page

    $url_query = absint($data['bt_experiments_full_page_default_page'] ?? 0);

    update_post_meta( $post_id, 'bt_experiments_full_page_default_page', $url_query );



    //page_variations

    $page_variations = (array) ($data['page_variations'] ?? []);

    foreach($page_variations as $k => $v){

      $page_variations[$v] = $this->get_url_from_slug($v);

      unset($page_variations[$k]);

    }

    $page_variations = array_map( 'sanitize_text_field', $page_variations );



    update_post_meta( $post_id, 'page_variations', $page_variations );



    //get variation meta

    $variation_meta = get_post_meta( $post_id, 'variation_meta', true );



    if(empty($variation_meta))

    {

      //variation meta

      // variation meta (labels and screenshots)

      $variation_labels = $data['variation_label'] ?? array();

      $variation_images = $data['variation_image'] ?? array();

      $variation_meta = array();

      foreach($page_variations as $key => $url){

        $variation_meta[$key] = array(

          'label' => sanitize_text_field($variation_labels[$key] ?? ''),

          'image' => esc_url_raw($variation_images[$key] ?? ''),

          'weight' => 1

        );

      }

      update_post_meta( $post_id, 'variation_meta', $variation_meta );

    }



    //css test

    $css_test_variations = intval($data['css_test_variations'] ?? 0);

    update_post_meta( $post_id, 'css_test_variations', $css_test_variations );



    // optimization type conversion_style bayesian or thompson(multi-armed-bandit) its a radio input

    $optimization_type = sanitize_text_field($data['conversion_style'] ?? 'bayesian');

    update_post_meta( $post_id, 'conversion_style', $optimization_type );

    

    // Clear weights if conversion style is being changed to non-thompson

    if($optimization_type != 'thompson') {

      $this->clear_test_variation_weights($post_id);

    }



    //url query

    $url_query = sanitize_textarea_field($data['bt_experiments_url_query'] ?? '');

    update_post_meta( $post_id, 'url_query', $url_query );



    //webhook url

    $webhook_url = isset($data['bt_webhook_url']) ? esc_url($data['bt_webhook_url']) : false;      

    update_post_meta( $post_id, 'webhook_url', $webhook_url );



    $allowed_roles = isset( $data['bt_allowed_roles'] ) ? (array) $data['bt_allowed_roles'] : [];

    $allowed_roles = array_map( 'sanitize_text_field', $allowed_roles );

    update_post_meta( $post_id, 'bt_allowed_roles', $allowed_roles );



    $target_percentage = sanitize_text_field($data['bt_experiments_target_percentage'] ?? '100');

    if($target_percentage == '')

      $target_percentage = '100';

    update_post_meta($post_id,'target_percentage',$target_percentage);

    

    // new target option device size desktop tablet mobile

    $target_option_device_size = sanitize_text_field($data['bt_experiments_target_option_device_size'] ?? false);

    update_post_meta($post_id,'target_option_device_size',$target_option_device_size);



    // log on visible - when enabled, visits are only logged when element becomes visible

    $log_on_visible = isset($data['bt_experiments_log_on_visible']) ? '1' : '0';

    update_post_meta($post_id,'log_on_visible',$log_on_visible);



    $conversion_page = sanitize_text_field($data['bt_experiments_conversion_page'] ?? '');

    $conversion_time = isset($data['bt_experiments_conversion_time']) ? intval($data['bt_experiments_conversion_time']) : 0;

    $conversion_scroll = isset($data['bt_experiments_conversion_scroll']) ? intval($data['bt_experiments_conversion_scroll']) : 0;

    $conversion_use_order_value = 0; // Lite: disable revenue tracking

    $conversion_text = sanitize_text_field($data['bt_experiments_conversion_text'] ?? false);



    if( $conversion_page == '0' )

      $conversion_page = '';



    if($conversion_page == 'page') // if its a page the get the page int for back compat its one field

      $conversion_page = intval($data['bt_experiments_conversion_page_selector'] ?? 0);



    update_post_meta($post_id,'conversion_page', $conversion_page);

    update_post_meta($post_id,'conversion_time', $conversion_time);

    update_post_meta($post_id,'conversion_scroll', $conversion_scroll);

    update_post_meta($post_id,'conversion_text', $conversion_text);



    update_post_meta($post_id,'conversion_use_order_value', 0);



    if( $conversion_page == 'url' ){

      $conversion_url = sanitize_text_field( $data['bt_experiments_conversion_url'] ?? '' );

      $conversion_url = str_replace(site_url(),"",$conversion_url); // remove the site URL if entered in error

      $conversion_url = ltrim($conversion_url, '/');  //ditch the slashes

      $conversion_url = rtrim($conversion_url, '/'); 

    }

    else

    {

        $conversion_url = '';

    }

    

    update_post_meta( $post_id, 'conversion_url', $conversion_url );





    if( $conversion_page == 'selector' )

      $conversion_selector = sanitize_text_field( $data['bt_experiments_conversion_selector'] ?? '' );

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

    

    // Lite: subgoals disabled

    delete_post_meta($post_id, 'goals');

    update_post_meta($post_id, 'autocomplete_on', 0);

    update_post_meta($post_id, 'ac_min_days', 7);

    update_post_meta($post_id, 'ac_min_views', 50);

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

      $nonce = isset( $_POST['bt_experiments_inner_custom_box_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['bt_experiments_inner_custom_box_nonce'] ) ) : '';

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

 $status = get_post_status($post->ID);



 if ($is_new) {

     $message_text = 'Split Test Created.';

 } else if ($status === 'publish') {

     $message_text = 'Split Test Running.';

 } else if ($status === 'pending') {

     $message_text = 'Split Test Paused.';

 } else if ($status === 'complete') {

     $message_text = 'Split Test Marked Complete.';

 } else if ($status === 'draft') {

     $message_text = 'Split Test Draft Saved.';

 } else {

     $message_text = 'Split Test Updated.';

   }



    $cache_message = '';

    $detected_caches = abst_get_detected_caches();

    $cache_list = implode(', ', $detected_caches);

    if ( abst_get_admin_setting('ab_dont_clear_cache_on_update') == '1' ) {

        $cache_message = ' <strong>IMPORTANT: You\'ll need to manually clear your cache. </strong><BR> The following caches have been detected: ' . $cache_list;

    } else {

        if ( ! empty( $detected_caches ) ) {

            $cache_message = ' These caches have been cleared: ' . esc_html($cache_list) . '.';

        }

        $cache_message .= ' If you are running any other website caching, clear that now.';

    }



    $full_message = esc_html($message_text) . "<br/><br/>" . $cache_message ;



    $custom_messages = array(

      1  => $full_message,

      4  => $full_message,

      6  => $status === 'publish' ? $full_message : esc_html('Split Test Running.') . "<br/><br/>" . $cache_message,

      8  => $status === 'pending' ? $full_message : esc_html('Split Test Submitted.') . "<br/><br/>" . $cache_message,

      9  => esc_html('Split Test Scheduled.') . "<br/><br/>" . $cache_message,

      10 => $status === 'draft' ? $full_message : esc_html('Split Test Draft Saved.') . "<br/><br/>" . $cache_message,

    );



    $messages['bt_experiments'] = isset($messages['bt_experiments']) && is_array($messages['bt_experiments'])

      ? array_replace($messages['bt_experiments'], $custom_messages)

      : $custom_messages;



    $messages['post'][1] = $custom_messages[1]; // Updated

    $messages['post'][4] = $custom_messages[4]; // Post updated.

    $messages['post'][6] = $custom_messages[6]; // Published

    $messages['post'][8] = $custom_messages[8]; // Submitted

    $messages['post'][9] = $custom_messages[9]; // Scheduled

    $messages['post'][10] = $custom_messages[10]; // Draft updated



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

      delete_transient('ab_posts_frontend_cache');



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

        $use_order_value = get_post_meta($post_id,'conversion_use_order_value',true);     

        $conversion_text = get_post_meta($post_id,'conversion_text',true);        

        $test_type = get_post_meta($post_id,'test_type',true);

        $bt_experiments_full_page_default_page = get_post_meta($post_id,'bt_experiments_full_page_default_page',true);

              

        if($test_type == 'full_page' && abst_get_admin_setting('ab_change_canonicals') == 1)

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

        $conversion_pages[$post_id]['use_order_value'] = $use_order_value;

      }



      update_option('bt_conversion_pages',$conversion_pages);

      update_option('ab_test_canonical',$canonical_list);



      abst_log('refresh_conversion_pages');



      // add_filter('abst_clear_caches', '__return_false'); //disable cache clearing on page and test update

      if( apply_filters('abst_clear_caches', true) && (abst_get_admin_setting('ab_dont_clear_cache_on_update') !== '1') )

      {

        if( class_exists('FLBuilderModel') ) 

        {

          abst_log('clearing BB cache');

          FLBuilderModel::delete_asset_cache_for_all_posts(); // clear cache  

        }



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



        global $nginx_purger;

        if (  isset( $nginx_purger ) && method_exists( $nginx_purger, 'purge_all' ) ) {

          abst_log('purged nginx');

          $nginx_purger->purge_all();

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



      }// end if clear cache is yes

    }



    

    function add_experiment_meta_box() {

          

      // one meta box style

      add_meta_box(

        'all_boxes',

        'Split Test',

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

      // ONLY FOR LOGGD IN USERS

      // if user has edittor role, then allow to create experiment

      if(!current_user_can('edit_posts'))

        wp_die('You do not have the correct permissions to create a test.');

      $nonce = isset($_POST['nonce']) ? sanitize_text_field(wp_unslash($_POST['nonce'])) : '';
      if (!$nonce || !wp_verify_nonce($nonce, 'abst_create_new_on_page_test')) {
        wp_die('Security check failed');
      }


      //recieve form data

      $data = $_POST;

      //sanitize it!

      if(empty($data))

        wp_die('No data received.');



      $expects_json = !empty($data['abst_magic_mode']) || (isset($_SERVER['HTTP_ACCEPT']) && strpos( sanitize_text_field( wp_unslash( $_SERVER['HTTP_ACCEPT'] ) ), 'application/json') !== false);



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

        );

        wp_update_post( $my_post );

      }

      

      $this->save_test_config( $data['post_id'], $data);



      if($expects_json)

      {

        wp_send_json([

          'post_id' => $data['post_id'],

          'post_title' => $data['post_title'],

          'created' => $createdNew,

          'updated' => !$createdNew,

        ]);

      }



      if($createdNew)

      {

        echo wp_json_encode($data);

      }

      else

      {

        echo "<body style='background: #ffffff; font-family: -apple-system, BlinkMacSystemFont, \"Segoe UI\", Roboto, sans-serif; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0;'>";

        echo "<div style='text-align: center; padding: 40px;'>";

        echo "<div style='width: 64px; height: 64px; background: #ecfdf5; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 24px;'>";

        echo "<svg width='32' height='32' viewBox='0 0 24 24' fill='none' stroke='#10b981' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round'><polyline points='20 6 9 17 4 12'></polyline></svg>";

        echo "</div>";

        echo "<h1 style='font-size: 24px; font-weight: 600; color: #1e293b; margin: 0 0 8px 0;'>Test Created!</h1>";

        echo "<p style='font-size: 16px; color: #64748b; margin: 0 0 4px 0;'>".esc_html($data['post_title'])."</p>";

        echo "<p style='font-size: 13px; color: #94a3b8; margin: 0;'>Closing automatically...</p>";

        echo "</div>";

      

        echo '<script>

        //send postmessage to parent iframe window

          // the format of the message is:

          // {

          //     "id": "the post id",

          //     "name": "the post title"

          // }

          window.parent.postMessage(

            '.wp_json_encode(array('id' => $data['post_id'], 'name' => $data['post_title'])).',

            window.location.origin

        );



        //if the user clicks escape, then send postmessage abclosemodal

        window.addEventListener("keydown", function(event) {

          if (event.key === "Escape") {

            window.parent.postMessage("abclosemodal", window.location.origin);

          }

        })

        </script></body>';

      }



      wp_die();

    }



    function magic_test_render_goals_inputs(){

      

    }



    function save_variation_label(){



      if (!current_user_can('edit_posts'))

        wp_die('You do not have the correct permissions to edit this label.');

      if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'abst_save_variation_label')) {

        wp_die('Security check failed');

      }



      $pid = isset( $_POST['pid'] ) ? intval( wp_unslash( $_POST['pid'] ) ) : 0;

      $variation_name = isset( $_POST['variation_name'] ) ? sanitize_text_field( wp_unslash( $_POST['variation_name'] ) ) : '';

      $variation_id = isset( $_POST['variation_id'] ) ? sanitize_text_field( wp_unslash( $_POST['variation_id'] ) ) : '';

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

        wp_send_json(array('success' => true, 'variation_meta' => $variation_meta));

      }

      else

      {

        wp_send_json(array('success' => false, 'error' => 'Invalid post type'));

      }

    }



    // wp ajax call loaded into iframe to create form to create a new on page test

    function  on_page_test_create() {

      if (!current_user_can('edit_posts'))

      {

        wp_die('You do not have the correct permissions to create a test.');

      }



      $thompson_sampling_enabled = abst_get_admin_setting('abst_thompson_sampling_enabled');





      //echo minimal iframe headers

      echo '<!DOCTYPE html><html><head><meta name="viewport" content="width=device-width, initial-scale=1.0">';

      echo "<script type='text/javascript'> window.ajaxurl = '" . esc_url(admin_url('admin-ajax.php')) . "'; window.bt_homeurl = '" . esc_url(home_url()) . "';</script>";

      // Enqueue jQuery (WordPress provides it)
      wp_enqueue_script('jquery');

      // Enqueue custom scripts
      wp_enqueue_script('select2-js', plugin_dir_url(__FILE__) . 'js/select2.js', array('jquery'), null, true);
      wp_enqueue_script('experiment-js', plugin_dir_url(__FILE__) . 'js/experiment.js', array('jquery'), null, true);

      // Enqueue custom styles
      wp_enqueue_style('select2-css', plugin_dir_url(__FILE__) . 'css/select2.css');
      wp_enqueue_style('experiment-css', plugin_dir_url(__FILE__) . 'css/experiment.css');
      wp_enqueue_style('bt-bb-ab-admin-css', plugin_dir_url(__FILE__) . 'admin/bt-bb-ab-admin.css');

      // Print enqueued styles and scripts for AJAX context
      wp_print_styles();
      wp_print_scripts();

      echo "<script type='text/javascript'>

      jQuery(document).ready(function(){

        jQuery('body').on('click','.collapsed>h3, .expanded>h3',function(e){

          if (jQuery(this).closest('#configuration_settings').length) {

            return;

          }

          jQuery(this).parent().toggleClass('expanded').toggleClass('collapsed');



      }); 

      //select input post_title

        jQuery('#post_title').focus();

      });</script>";

      // include select2

      echo '<style>

            :root {

                --abst-primary: #17A8E3;

                --abst-text: #1e293b;

                --abst-text-secondary: #64748b;

                --abst-border: #e2e8f0;

                --abst-bg: #f8fafc;

            }



            body {

                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;

                padding: 0 !important;

                margin: 0 !important;

                overflow-x: hidden;

                background: #ffffff;

                font-size: 14px;

                color: var(--abst-text);

                line-height: 1.5;

            }



            form#post {

                padding: 24px;

                padding-bottom: 80px;

            }



            /* Main title */

            form#post > h4:first-child {

                font-size: 18px;

                font-weight: 600;

                color: var(--abst-text);

                margin: 0 0 24px 0;

                padding-bottom: 16px;

                border-bottom: 1px solid var(--abst-border);

            }



            /* Section boxes */

            .experiment_box, .title_box {

                background: #ffffff;

                padding: 0;

                margin-bottom: 24px;

            }



            /* Section titles (h4) */

            h4 {

                font-size: 14px;

                font-weight: 600;

                color: var(--abst-text) !important;

                margin: 16px 0 8px 0;

                text-decoration: none !important;

            }



            h4:first-child {

                margin-top: 0;

            }



            /* Labels */

            label {

                font-size: 13px;

                font-weight: 500;

                color: var(--abst-text-secondary);

                display: block;

                margin-bottom: 4px;

            }



            /* Accordion headers (h3) - collapsed state */

            .collapsed > h3 {

                font-size: 14px;

                font-weight: 600;

                color: var(--abst-text);

                background: #ffffff !important;

                padding: 14px 16px;

                border: 1px solid var(--abst-border);

                border-radius: 6px;

                box-shadow: none;

                margin: 8px 0;

                cursor: pointer;

                display: flex;

                justify-content: space-between;

                align-items: center;

            }



            .collapsed > h3:hover {

                border-color: var(--abst-primary);

                color: var(--abst-primary);

            }



            .collapsed > h3::after {

                content: "▸";

                color: #94a3b8;

                font-weight: 400;

                font-size: 12px;

            }



            /* Accordion headers (h3) - expanded state */

            .expanded > h3 {

                font-size: 14px;

                font-weight: 600;

                color: var(--abst-text);

                background: #ffffff !important;

                padding: 14px 16px;

                border: 1px solid var(--abst-border);

                border-bottom: none;

                border-radius: 6px 6px 0 0;

                box-shadow: none;

                margin: 8px 0 0 0;

                cursor: pointer;

                display: flex;

                justify-content: space-between;

                align-items: center;

            }



            .expanded > h3:hover {

                color: var(--abst-primary);

            }



            .expanded > h3::after {

                content: "▾";

                color: #94a3b8;

                font-weight: 400;

                font-size: 12px;

            }



            /* Expanded section - wrap all content */

            .expanded {

                background: #ffffff;

                border: 1px solid var(--abst-border);

                border-radius: 6px;

                margin: 8px 0;

                overflow: hidden;

            }



            .expanded > h3 {

                margin: 0 !important;

                border: none !important;

                border-bottom: 1px solid var(--abst-border) !important;

                border-radius: 0 !important;

            }



            .expanded > *:not(h3) {

                padding: 0 16px;

            }



            .expanded > *:last-child {

                padding-bottom: 16px;

            }



            .expanded h4 {

                margin-top: 16px;

            }



            /* Generic h3 fallback */

            h3 {

                font-size: 14px;

                font-weight: 600;

                color: var(--abst-text);

                margin: 16px 0 8px 0;

            }



            /* Description text */

            p {

                font-size: 13px;

                color: var(--abst-text-secondary);

                margin: 0 0 12px 0;

                line-height: 1.5;

            }



            /* Form inputs */

            input[type="text"], input[type="number"] {

                width: 100%;

                padding: 10px 14px;

                border: 1px solid var(--abst-border);

                border-radius: 6px;

                font-size: 14px;

                background: #ffffff;

                box-sizing: border-box;

                transition: border-color 0.15s ease;

            }



            input[type="text"]:focus, input[type="number"]:focus {

                border-color: var(--abst-primary);

                outline: none;

                box-shadow: 0 0 0 3px rgba(23, 168, 227, 0.1);

            }



            /* Selects */

            select {

                width: 100%;

                padding: 10px 14px;

                border: 1px solid var(--abst-border);

                border-radius: 6px;

                font-size: 14px;

                background: #ffffff;

                cursor: pointer;

                box-sizing: border-box;

                margin-bottom: 16px;

            }



            select:focus {

                border-color: var(--abst-primary);

                outline: none;

            }



            /* Device Size select */

            #bt_experiments_target_option_device_size {

                margin-top: 4px;

            }

            .show_goals {

                margin-bottom: 30px;

            }

            

            /* Primary conversion goal card - prominent green accent */

            .conversion-goal {

                background: linear-gradient(135deg, #f0fdf4 0%, #ffffff 100%);

                padding: 20px;

                border: 1px solid #bbf7d0;

                border-left: 4px solid #22c55e;

                border-radius: 8px;

                margin-bottom: 16px;

                position: relative;

                box-shadow: 0 2px 8px rgba(34, 197, 94, 0.08);

            }

            

            .conversion-goal h4 {

                font-size: 11px;

                font-weight: 700;

                color: #15803d;

                text-transform: uppercase;

                letter-spacing: 0.5px;

                margin: 0 0 12px 0;

                display: flex;

                align-items: center;

                gap: 8px;

            }

            

            .conversion-goal h4::before {

                content: "🎯";

                font-size: 14px;

            }



            .conversion-goal p {

                margin-bottom: 12px;

                color: var(--abst-text);

            }

            

            /* Subgoal cards - blue accent */

            .subgoal {

                background: linear-gradient(135deg, #eff6ff 0%, #ffffff 100%);

                padding: 16px 20px;

                border: 1px solid #bfdbfe;

                border-left: 4px solid #3b82f6;

                border-radius: 8px;

                margin-bottom: 12px;

                position: relative;

                box-shadow: 0 2px 8px rgba(59, 130, 246, 0.08);

            }

            

            .subgoal h4 {

                font-size: 11px;

                font-weight: 700;

                color: #1d4ed8;

                text-transform: uppercase;

                letter-spacing: 0.5px;

                margin: 0 0 12px 0;

                display: flex;

                align-items: center;

                gap: 8px;

            }

            

            .subgoal h4::before {

                content: "📊";

                font-size: 13px;

            }

            

            .subgoal label {

                display: block;

                font-size: 13px;

                font-weight: 500;

                color: var(--abst-text);

                margin-bottom: 6px;

            }

            

            .subgoal p {

                font-size: 12px;

                color: var(--abst-text-secondary);

                margin: 8px 0;

                line-height: 1.5;

            }



            .subgoal input[type="text"] {

                margin-top: 4px;

                width: 100%;

            }

            

            .subgoal select {

                width: 100%;

                max-width: 300px;

            }



            /* Close button on subgoals */

            .subgoal .close-goal {

                position: absolute;

                top: 12px;

                right: 12px;

                width: 22px;

                height: 22px;

                border-radius: 4px;

                background: #f1f5f9;

                color: #94a3b8;

                border: 1px solid #e2e8f0;

                font-size: 12px;

                font-weight: 500;

                cursor: pointer;

                display: flex;

                align-items: center;

                justify-content: center;

                transition: all 0.15s ease;

                line-height: 1;

            }



            .subgoal .close-goal:hover {

                background: #fee2e2;

                border-color: #fecaca;

                color: #dc2626;

            }

            

            /* Add Goal button */

            .show_goals .add-goal {

                background: #f0fdf4;

                color: #15803d;

                border: 1px solid #bbf7d0;

                border-radius: 6px;

                padding: 8px 16px;

                font-size: 13px;

                font-weight: 500;

                cursor: pointer;

                transition: all 0.15s ease;

            }

            

            .show_goals .add-goal:hover {

                background: #dcfce7;

                border-color: #86efac;

            }



            /* Checkboxes - inline pills */

            .ab-targeting-roles label,

            label:has(input[type="checkbox"]) {

                display: inline-flex;

                align-items: center;

                background: #f9fafb;

                border: 1px solid var(--abst-border);

                border-radius: 20px;

                padding: 6px 12px;

                margin: 4px 4px 4px 0;

                font-size: 12px;

                cursor: pointer;

                transition: all 0.15s ease;

            }



            .ab-targeting-roles label:hover,

            label:has(input[type="checkbox"]):hover {

                background: #eff6ff;

                border-color: #93c5fd;

            }



            .ab-targeting-roles label:has(input:checked),

            label:has(input[type="checkbox"]:checked) {

                background: #dbeafe;

                border-color: #3b82f6;

                color: #1e40af;

            }



            input[type="checkbox"] {

                width: 14px;

                height: 14px;

                margin-right: 6px;

                border-radius: 3px;

            }



            /* Add Goal button */

            button.button.button-small.add-goal,

            .add-goal {

                background: #ffffff;

                border: 1px solid var(--abst-border);

                border-radius: 6px;

                padding: 10px 16px;

                cursor: pointer;

                font-size: 13px;

                font-weight: 500;

                color: #374151;

                transition: all 0.15s ease;

                margin-top: 8px;

            }



            button.button.button-small.add-goal:hover,

            .add-goal:hover {

                background: var(--abst-bg);

                border-color: var(--abst-primary);

                color: var(--abst-primary);

            }



            /* Submit button */

            .submit_box {

                position: fixed;

                bottom: 0;

                left: 0;

                right: 0;

                background: #ffffff;

                padding: 16px 24px;

                box-shadow: 0 -4px 12px rgba(0, 0, 0, 0.1);

                border-top: 1px solid var(--abst-border);

            }



            button#submit_experiment {

                width: 100%;

                display: block;

                background: #10b981;

                border-radius: 6px;

                color: #ffffff;

                padding: 14px 24px;

                font-size: 15px;

                font-weight: 600;

                border: none;

                cursor: pointer;

                transition: background 0.15s ease;

            }



            button#submit_experiment:hover {

                background: #059669;

            }



            /* Links */

            a {

                color: var(--abst-primary);

                text-decoration: none;

            }



            a:hover {

                text-decoration: underline;

            }



            /* Hide elements */

            .ab-tab-button { display: none; }



            /* Select2 overrides */

            .select2-container { 

                width: 100% !important; 

                max-width: 100% !important;

                box-sizing: border-box !important;

            }

            .select2-container--default .select2-selection--single {

                border: 1px solid var(--abst-border) !important;

                border-radius: 6px !important;

                height: 42px !important;

                padding: 6px 12px !important;

            }

            .select2-container--default .select2-selection--single .select2-selection__rendered {

                line-height: 28px !important;

            }

            

            /* Select2 dropdown - contain within iframe */

            .select2-dropdown {

                max-width: calc(100vw - 48px) !important;

                border: 1px solid var(--abst-border) !important;

                border-radius: 6px !important;

                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15) !important;

            }

            

            .select2-results__options {

                max-height: 200px !important;

            }

            

            .select2-results__option {

                padding: 10px 14px !important;

                font-size: 13px !important;

            }

            

            .select2-results__option--highlighted {

                background: var(--abst-primary) !important;

            }

      </style>';

      //form post to ajax url action name create_new_on_page_test

      echo '</head><body><form action="' . esc_url(admin_url('admin-ajax.php')) . '" method="post" id="post" enctype="multipart/form-data"><h4 style="font-size:1.1em;">Create new Split Test</h4><div class="title_box"> <h4><label for="post_title">Test Name</label></h4><input name="post_title" id="post_title" type="text" value="" placeholder="Test Name" size="30" class="regular-text" required="required"/>';

      echo '<input type="hidden"  name="test_type" value="ab_test"/>';

      // Check if Thompson Sampling is enabled globally

      echo '<input type="hidden"  name="css_test_variations" value="0"/>';

      echo '<input type="hidden"  name="bt_experiments_full_page_default_page" value="false"/>';

      echo '<input type="hidden" name="action" value="create_new_on_page_test" />';
      echo '<input type="hidden" name="nonce" value="' . esc_attr(wp_create_nonce('abst_create_new_on_page_test')) . '" /></div><div class="experiment_box">';

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



      echo '<input type="hidden" name="post_id" value="' . esc_attr($post_id) . '" />';

      $post = get_post($post_id);



      echo '<div class="conversion-goal">';

      $this->bt_experiments_inner_custom_box($post);// conversions / goals area

      echo '</div>';

      echo "</div></div>";



      

      if( abst_user_level()  == 'agency'){

        $max_goals = 10;

        echo "<div class='show_goals'>";

        $subgoals = range(1,$max_goals);

        

        foreach($subgoals as $i){



          echo "<div class='subgoal hidden test-goal-".esc_attr($i)."'>";

          echo "<span class='close-goal'>x</span>";

          echo "<h4>Sub Goal ".esc_html($i)."</h4>";

          echo "<select class='goal-type' name='goal[".esc_attr($i)."]'>";

          echo "<option value=''>Select Goal Trigger...</option>";

          echo "<option value='page'>Page or Post Visit</option>";

          echo "<option value='url'>URL Visited</option>";

          echo "<option value='text'>Text Visible on Page</option>";

          echo "<option value='selector'>Element Clicked</option>";

          echo "<option value='link'>Link Clicked</option>";

          echo "<option value='time'>Time Active</option>";

          echo "<option value='scroll'>Scroll Depth</option>";

          echo "<option value='block'>Conversion Element Visible</option>";

          echo "<option value='javascript'>JavaScript</option>";

          // Form submission conversions

          if (function_exists('abst_get_form_optgroups')) {

            echo wp_kses_post( abst_get_form_optgroups() );

          }

          

          echo "</select>";

          echo "<label class='goal-value-label' for='goal_value[".esc_attr($i)."]'>Goal Value</label>";

          echo "<input class='goal-value' type='text' name='goal_value[".esc_attr($i)."]' placeholder='' />";

          echo "<select class='goal-page' name='goal_page[".esc_attr($i)."]'>";

          echo "</select></div>";

          

        }

        echo "<button class='button button-small add-goal'>+ Add Goal</button>";

        echo "</div>";

      }

      else

      {

        echo "<div class='show_goals'><div class='subgoal upgrade'> <h4>Upgrade to add Sub Goals</h4><p>Analyze each stage of your customer's journey, identify drop-off points, and optimize every part of your funnel for better performance.</p><p>Upgrade also includes support for more sites, custom conversion values (order value), analytics integrations, and more.</p><p><a href='https://absplittest.com/pricing' target='_blank'>Upgrade to a Pro Plan</a></p></div>";

        echo "<p><a class='button button-small' href='https://absplittest.com/pricing' target='_blank'>Upgrade to unlock Sub Goals</a></p></div>";

      }

      echo "</div>";

      if($thompson_sampling_enabled) {

        echo '<div class="show_conversion_style"><h3>Winning Mode:</h3><select id="conversion_style" name="conversion_style"><option value="bayesian">Standard - Bayesian</option><option value="thompson">Dynamic - Multi Armed Bandit</option></select></div>';

      }

      else

      {

          echo '<input type="hidden"  name="conversion_style" value="bayesian"/>';

      }



      echo "<div class='show_targeting_options collapsed'><h3>Test Visitor Segmentation</h3><p>Choose which visitors will be tested on. <BR/>Everyone else will see the default.<BR/>Filters apply in the execution order ↓</p>";

      $this->show_targeting_options($post);


      echo '</div><div class="submit_box"><button class="button button-primary button-large" id="submit_experiment">Save and Start Test</button></div></form></div></body></html>';



      // End wp ajax call

      wp_die();

    }



    function get_magic_scope_summary($magic_definition) {

      if (empty($magic_definition)) {

        return '';

      }



      $decoded = json_decode($magic_definition, true);

      if (!is_array($decoded) || empty($decoded)) {

        return '';

      }



      $labels = [];



      foreach ($decoded as $item) {

        if (!is_array($item) || empty($item['scope']) || !is_array($item['scope'])) {

          continue;

        }



        $scope = $item['scope'];

        $label = '';



        if (isset($scope['page_id'])) {

          if ((string) $scope['page_id'] === '*') {

            $label = 'All pages';

          } else {

            $page_ids = is_array($scope['page_id']) ? $scope['page_id'] : [$scope['page_id']];

            $page_ids = array_values(array_filter(array_map('absint', $page_ids)));

            if (!empty($page_ids)) {

              if (count($page_ids) > 1) {

                $label = count($page_ids) . ' specific pages';

              } else {

                $page_id = $page_ids[0];

                if ((int) get_option('page_on_front') === $page_id) {

                  $label = 'Home page';

                } else {

                  $page_title = get_the_title($page_id);

                  $label = !empty($page_title) ? $page_title : 'Page ID ' . $page_id;

                }

              }

            }

          }

        }



        if (empty($label) && isset($scope['url'])) {

          $scope_url = trim((string) $scope['url']);

          if ($scope_url === '*') {

            $label = 'All pages';

          } elseif ($scope_url !== '') {

            $label = 'URL contains "' . $scope_url . '"';

          }

        }



        if (!empty($label)) {

          $labels[] = $label;

        }

      }



      $labels = array_values(array_unique($labels));



      if (empty($labels)) {

        return '';

      }



      if (count($labels) === 1) {

        return $labels[0];

      }



      return 'Mixed scope (' . count($labels) . ' rules)';

    }



    function get_magic_edit_url($magic_definition, $fallback_scope_hint = '') {

      $target_url = get_home_url();



      if (!empty($magic_definition)) {

        $decoded = json_decode($magic_definition, true);



        if (is_array($decoded)) {

          foreach ($decoded as $item) {

            if (!is_array($item) || empty($item['scope']) || !is_array($item['scope'])) {

              continue;

            }



            $scope = $item['scope'];



            if (isset($scope['page_id']) && (string) $scope['page_id'] !== '*') {

              $page_ids = is_array($scope['page_id']) ? $scope['page_id'] : [$scope['page_id']];

              $page_ids = array_values(array_filter(array_map('absint', $page_ids)));

              foreach ($page_ids as $page_id) {

                if ($page_id > 0) {

                  $permalink = get_permalink($page_id);

                  if (!empty($permalink)) {

                    $target_url = $permalink;

                    break 2;

                  }

                }

              }

            }



            if (isset($scope['url'])) {

              $scope_url = trim((string) $scope['url']);

              if ($scope_url === '' || $scope_url === '*') {

                continue;

              }



              if (preg_match('#^https?://#i', $scope_url)) {

                $target_url = $scope_url;

              } else {

                $target_url = home_url('/' . ltrim($scope_url, '/'));

              }

              break;

            }

          }

        }

      } else {

        $scope_hint = trim((string) $fallback_scope_hint);

        if ($scope_hint !== '') {

          if (preg_match('#^https?://#i', $scope_hint)) {

            $target_url = $scope_hint;

          } elseif ($scope_hint[0] === '/') {

            $target_url = home_url($scope_hint);

          } else {

            $slug_candidate = sanitize_title($scope_hint);

            if ($slug_candidate !== '') {

              $page_by_path = get_page_by_path($slug_candidate, OBJECT, ['page', 'post']);

              if ($page_by_path instanceof WP_Post) {

                $permalink = get_permalink($page_by_path->ID);

                if (!empty($permalink)) {

                  $target_url = $permalink;

                }

              } elseif (preg_match('#^[a-z0-9/_-]+$#i', $scope_hint)) {

                $target_url = home_url('/' . ltrim($scope_hint, '/'));

              }

            }

          }

        }

      }



      return add_query_arg('abmagic', '1', $target_url);

    }



    function get_magic_variation_count($magic_definition) {

      if (empty($magic_definition)) {

        return 0;

      }



      $decoded = json_decode($magic_definition, true);

      if (!is_array($decoded) || empty($decoded)) {

        return 0;

      }



      $max_variations = 0;

      foreach ($decoded as $item) {

        if (!is_array($item) || empty($item['variations']) || !is_array($item['variations'])) {

          continue;

        }



        $count = count($item['variations']);

        if ($count > $max_variations) {

          $max_variations = $count;

        }

      }



      return $max_variations;

    }



    function format_status_lifecycle_date($timestamp, $include_time = false) {

      if (empty($timestamp)) {

        return '-';

      }



      $format = $include_time ? 'M j, Y \a\t H:i' : 'M j, Y';

      return wp_date($format, intval($timestamp));

    }



    function get_status_lifecycle_variation_count($post, $stats = null) {

      if (is_array($stats) && !empty($stats['variations']) && is_array($stats['variations'])) {

        return count($stats['variations']);

      }



      $pid = $post->ID;

      $test_type = get_post_meta($pid, 'test_type', true);



      if ($test_type === 'magic') {

        return max(0, intval($this->get_magic_variation_count(get_post_meta($pid, 'magic_definition', true))));

      }



      if ($test_type === 'full_page') {

        $page_variations = get_post_meta($pid, 'page_variations', true);

        return is_array($page_variations) ? count($page_variations) : 0;

      }



      if ($test_type === 'css_test') {

        return max(0, intval(get_post_meta($pid, 'css_test_variations', true)));

      }



      $variation_meta = get_post_meta($pid, 'variation_meta', true);

      return is_array($variation_meta) ? count($variation_meta) : 0;

    }



    function get_status_lifecycle_total_visits($stats) {

      if (!is_array($stats) || empty($stats['variations']) || !is_array($stats['variations'])) {

        return 0;

      }



      $total_visits = 0;

      foreach ($stats['variations'] as $variation) {

        $total_visits += intval($variation['visits'] ?? 0);

      }



      return $total_visits;

    }



    function get_status_lifecycle_winner_summary($post, $stats) {

      if (!is_array($stats)) {

        return '-';

      }



      $winner_key = $stats['winner_key'] ?? '';

      if (empty($winner_key)) {

        return '-';

      }



      $variation_meta = get_post_meta($post->ID, 'variation_meta', true);

      $winner_label = abst_get_variation_label($winner_key, $variation_meta);

      $winner_label = $winner_label !== '' ? $winner_label : $winner_key;



      $winner_data = $stats['variations'][$winner_key] ?? null;

      if (is_array($winner_data) && isset($winner_data['uplift_vs_control'])) {

        $uplift = round(floatval($winner_data['uplift_vs_control']) * 100);

        if ($uplift > 0) {

          return $winner_label . ' +' . $uplift . '%';

        }

        if ($uplift < 0) {

          return $winner_label . ' ' . $uplift . '%';

        }

      }



      return $winner_label;

    }



    function build_status_lifecycle_panel($post) {

      if (!$post || $post->post_type !== 'bt_experiments') {

        return null;

      }



      $status = get_post_status($post);

      if (!$status || $status === 'auto-draft') {

        $status = 'draft';

      }



      $stats = $status === 'idea' ? null : $this->get_experiment_stats_array($post);

      $visits = $this->get_status_lifecycle_total_visits($stats);

      $variation_count = $this->get_status_lifecycle_variation_count($post, $stats);

      $created_at = get_post_time('U', true, $post);

      $modified_at = get_post_modified_time('U', true, $post);

      $trash_link = get_delete_post_link($post->ID);

      $confidence = is_array($stats) ? intval(round(floatval($stats['winner_conf'] ?? 0))) : 0;

      $time_remaining = is_array($stats) ? trim((string) ($stats['time_remaining'] ?? '')) : '';

      $duration = is_array($stats) ? trim((string) ($stats['duration_so_far'] ?? '')) : '';

      $winner_summary = $this->get_status_lifecycle_winner_summary($post, $stats);



      $panel = [

        'status' => $status,

        'eyebrow' => strtoupper($status === 'publish' ? 'Running' : ($status === 'pending' ? 'Paused' : ($status === 'complete' ? 'Complete' : ($status === 'idea' ? 'Idea' : 'Draft')))),

        'rows' => [],

        'progress' => null,

        'primary_action' => null,

        'secondary_actions' => [],

        'footer_links' => [],

        'header_badge' => '',

      ];



      if ($status === 'idea') {

        // Lite: Idea status disabled - Test Ideas is now Pro-only feature

        $panel['rows'] = [

          ['icon' => 'lock', 'label' => 'Test Ideas', 'value' => 'Pro feature'],

        ];

        $panel['primary_action'] = ['label' => 'Upgrade to Pro', 'action' => 'upgrade-pro', 'class' => 'button button-primary button-large'];

        $panel['primary_action']['href'] = 'https://absplittest.com/pricing?ref=upgradefeaturelink';

        if ($trash_link) {

          $panel['footer_links'][] = ['label' => 'Delete', 'href' => $trash_link, 'class' => 'submitdelete deletion'];

        }

      } elseif ($status === 'publish') {

        $panel['rows'] = [

          ['icon' => 'calendar-alt', 'label' => 'Started', 'value' => $this->format_status_lifecycle_date($created_at)],

          ['icon' => 'groups', 'label' => 'Visitors', 'value' => $visits > 0 ? number_format_i18n($visits) : '-'],

          ['icon' => 'clock', 'label' => 'Duration', 'value' => $duration !== '' ? $duration : '-'],

        ];

        $panel['progress'] = [

          'value' => max(0, min(100, $confidence)),

          'left' => $confidence > 0 ? $confidence . '% to significance' : 'Collecting data',

          'right' => ($time_remaining !== '' && $time_remaining !== 'Paused' && $time_remaining !== 'Complete') ? $time_remaining . ' left' : '',

        ];

        $panel['secondary_actions'][] = ['label' => 'Pause Test', 'action' => 'pause-test', 'class' => 'button button-secondary'];

        $panel['primary_action'] = ['label' => 'Update Test', 'action' => 'update-test', 'class' => 'button button-primary'];

        if ($trash_link) {

          $panel['footer_links'][] = ['label' => 'Cancel test', 'href' => $trash_link, 'class' => 'submitdelete deletion'];

        }

      } elseif ($status === 'pending') {

        $panel['rows'] = [

          ['icon' => 'calendar-alt', 'label' => 'Paused on', 'value' => $this->format_status_lifecycle_date($modified_at)],

          ['icon' => 'groups', 'label' => 'Visitors', 'value' => $visits > 0 ? number_format_i18n($visits) : '-'],

        ];

        $panel['progress'] = [

          'value' => max(0, min(100, $confidence)),

          'left' => $confidence > 0 ? $confidence . '% to significance' : 'Paused',

          'right' => 'Paused',

        ];

        $panel['primary_action'] = ['label' => 'Resume Test', 'action' => 'resume-test', 'class' => 'button button-primary button-large'];

        if ($trash_link) {

          $panel['footer_links'][] = ['label' => 'Cancel test', 'href' => $trash_link, 'class' => 'submitdelete deletion'];

        }

      } elseif ($status === 'complete') {

        $panel['rows'] = [

          ['icon' => 'calendar-alt', 'label' => 'Ran', 'value' => $this->format_status_lifecycle_date($created_at) . ' - ' . $this->format_status_lifecycle_date($modified_at)],

          ['icon' => 'groups', 'label' => 'Visitors', 'value' => $visits > 0 ? number_format_i18n($visits) : '-'],

          ['icon' => 'chart-line', 'label' => 'Winner', 'value' => $winner_summary],

        ];

        if ($confidence > 0) {

          $panel['header_badge'] = $confidence . '% confidence';

        }

        $panel['primary_action'] = ['label' => 'View Results', 'action' => 'view-results', 'class' => 'button button-primary button-large'];

        if ($trash_link) {

          $panel['footer_links'][] = ['label' => 'Archive', 'href' => $trash_link, 'class' => 'submitdelete deletion'];

        }

      } else {

        $variant_value = $variation_count > 0 ? number_format_i18n($variation_count) . ' configured' : 'Not configured yet';

        $panel['rows'] = [

          ['icon' => 'edit', 'label' => 'Not live', 'value' => ''],

          ['icon' => 'images-alt2', 'label' => 'Variants', 'value' => $variant_value],

          ['icon' => 'calendar-alt', 'label' => 'Modified', 'value' => $this->format_status_lifecycle_date($modified_at)],

        ];

        $panel['secondary_actions'][] = ['label' => 'Save Draft', 'action' => 'save-draft', 'class' => 'button button-secondary button-large'];

        $panel['primary_action'] = ['label' => 'Launch Test', 'action' => 'launch-test', 'class' => 'button button-primary button-large'];

        if ($trash_link) {

          $panel['footer_links'][] = ['label' => 'Delete draft', 'href' => $trash_link, 'class' => 'submitdelete deletion'];

        }

      }



      return $panel;

    }



    function render_status_lifecycle_panel($post) {

      $panel = $this->build_status_lifecycle_panel($post);

      if (empty($panel)) {

        return;

      }



      echo '<div id="abst-status-lifecycle" class="misc-pub-section abst-status-lifecycle abst-status-' . esc_attr($panel['status']) . '" data-status="' . esc_attr($panel['status']) . '">';

      echo '<div class="abst-status-lifecycle__card">';

      echo '<div class="abst-status-lifecycle__header">';

      echo '<div class="abst-status-lifecycle__eyebrow"><span class="abst-status-lifecycle__dot"></span>' . esc_html($panel['eyebrow']) . '</div>';

      if (!empty($panel['header_badge'])) {

        echo '<span class="abst-status-lifecycle__badge">' . esc_html($panel['header_badge']) . '</span>';

      }

      echo '</div>';

      echo '<div class="abst-status-lifecycle__body">';



      foreach ($panel['rows'] as $row) {

        echo '<div class="abst-status-lifecycle__row">';

        echo '<span class="dashicons dashicons-' . esc_attr($row['icon']) . '" aria-hidden="true"></span>';

        echo '<span class="abst-status-lifecycle__label">' . esc_html($row['label']) . '</span>';

        if ($row['value'] !== '') {

          echo '<strong class="abst-status-lifecycle__value">' . esc_html($row['value']) . '</strong>';

        }

        echo '</div>';

      }



      if (!empty($panel['progress'])) {

        echo '<div class="abst-status-lifecycle__progress">';

        echo '<div class="abst-status-lifecycle__progress-bar"><span style="width:' . esc_attr(intval($panel['progress']['value'])) . '%;"></span></div>';

        echo '<div class="abst-status-lifecycle__progress-meta">';

        echo '<span>' . esc_html($panel['progress']['left']) . '</span>';

        echo '<span>' . esc_html($panel['progress']['right']) . '</span>';

        echo '</div>';

        echo '</div>';

      }



      echo '</div>';

      echo '<div class="abst-status-lifecycle__actions">';



      $render_primary_with_secondary = !empty($panel['secondary_actions']) && !empty($panel['primary_action']) && $panel['status'] === 'publish';



      if (!empty($panel['secondary_actions'])) {

        $secondary_row_class = 'abst-status-lifecycle__actions-row';

        if ($render_primary_with_secondary) {

          $secondary_row_class .= ' abst-status-lifecycle__actions-row--split';

        }

        echo '<div class="' . esc_attr($secondary_row_class) . '">';

        foreach ($panel['secondary_actions'] as $action) {

          echo '<button type="button" class="' . esc_attr($action['class']) . ' abst-lifecycle-action" data-action="' . esc_attr($action['action']) . '">' . esc_html($action['label']) . '</button>';

        }

        if ($render_primary_with_secondary) {

          echo '<button type="button" class="' . esc_attr($panel['primary_action']['class']) . ' abst-lifecycle-action" data-action="' . esc_attr($panel['primary_action']['action']) . '">' . esc_html($panel['primary_action']['label']) . '</button>';

        }

        echo '</div>';

      }



      if (!empty($panel['primary_action']) && !$render_primary_with_secondary) {

        echo '<div class="abst-status-lifecycle__actions-row">';

        if (!empty($panel['primary_action']['href'])) {

          echo '<a href="' . esc_url($panel['primary_action']['href']) . '" target="_blank" class="' . esc_attr($panel['primary_action']['class']) . '">' . esc_html($panel['primary_action']['label']) . '</a>';

        } else {

          echo '<button type="button" class="' . esc_attr($panel['primary_action']['class']) . ' abst-lifecycle-action" data-action="' . esc_attr($panel['primary_action']['action']) . '">' . esc_html($panel['primary_action']['label']) . '</button>';

        }

        echo '</div>';

      }



      if (!empty($panel['footer_links'])) {

        echo '<div class="abst-status-lifecycle__footer">';

        foreach ($panel['footer_links'] as $link) {

          echo '<a class="' . esc_attr($link['class']) . '" href="' . esc_url($link['href']) . '">' . esc_html($link['label']) . '</a>';

        }

        echo '</div>';

      }



      echo '</div>';

      echo '</div>';

      echo '</div>';

    }



    function all_boxes($post){



      $pid = $post->ID;



      $cog = '<svg viewBox="0 0 20 20" fill="currentColor" id="cog" class="w-8 h-8 text-cool-gray-800 dark:text-cool-gray-200 group-hover:text-purple-600 group-focus:text-purple-600 dark:group-hover:text-purple-50 dark:group-focus:text-purple-50"><path fill-rule="evenodd" d="M11.49 3.17c-.38-1.56-2.6-1.56-2.98 0a1.532 1.532 0 01-2.286.948c-1.372-.836-2.942.734-2.106 2.106.54.886.061 2.042-.947 2.287-1.561.379-1.561 2.6 0 2.978a1.532 1.532 0 01.947 2.287c-.836 1.372.734 2.942 2.106 2.106a1.532 1.532 0 012.287.947c.379 1.561 2.6 1.561 2.978 0a1.533 1.533 0 012.287-.947c1.372.836 2.942-.734 2.106-2.106a1.533 1.533 0 01.947-2.287c1.561-.379 1.561-2.6 0-2.978a1.532 1.532 0 01-.947-2.287c.836-1.372-.734-2.942-2.106-2.106a1.532 1.532 0 01-2.287-.947zM10 13a3 3 0 100-6 3 3 0 000 6z" clip-rule="evenodd"></path></svg>';

      $graphicon = '<svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path d="M2 11a1 1 0 011-1h2a1 1 0 011 1v5a1 1 0 01-1 1H3a1 1 0 01-1-1v-5zM8 7a1 1 0 011-1h2a1 1 0 011 1v9a1 1 0 01-1 1H9a1 1 0 01-1-1V7zM14 4a1 1 0 011-1h2a1 1 0 011 1v12a1 1 0 01-1 1h-2a1 1 0 01-1-1V4z"></path></svg>';

      $webhook_url = get_post_meta($pid,'webhook_url',true); 

      $magic_definition = get_post_meta($pid,'magic_definition',true); 

      if( abst_user_level() == 'free' )

      {

        //if post status is publish

        require_once plugin_dir_path(__FILE__) . 'bt-bb-ab-validation.php';
        $active_tests_count = abst_lite_active_test_count($pid);

        if($active_tests_count > 0 && (get_post_status($pid) !== 'publish'))

          echo "<div class='free-notice'><h3>HEADS UP!</h3><h4>This free version of AB Split Test is limited to one active test. Cancel your other tests or upgrade to start this test. </h4><p><a href='https://absplittest.com/pricing?utm_source=ug' target='_blank'>Upgrade to pro.</a></p></div>";

        if($active_tests_count > 0 && (get_post_status($pid) == 'publish') && !abst_lite_is_sample_test($pid))

          echo "<div class='free-notice'><h3>HEADS UP!</h3><h4>This free version of AB Split Test is limited to one active test. Upgrade to modify this test. </h4><p><a href='https://absplittest.com/pricing?utm_source=ug' target='_blank'>Upgrade to pro.</a></p></div>";

      }

      echo "<div class='ab-layout-wrapper'>";

      echo "<div class='ab-sidebar-nav'>";

      $idea_hypothesis = get_post_meta($pid, 'abst_idea_hypothesis', true);

      $idea_page_flow = get_post_meta($pid, 'abst_idea_page_flow', true);

      $idea_problem = get_post_meta($pid, 'abst_idea_observed_problem', true);

      $idea_next_step = get_post_meta($pid, 'abst_idea_next_step', true);

      $idea_impact = get_post_meta($pid, 'abst_idea_impact', true);

      $idea_reach = get_post_meta($pid, 'abst_idea_reach', true);

      $idea_confidence = get_post_meta($pid, 'abst_idea_confidence', true);

      $idea_effort = get_post_meta($pid, 'abst_idea_effort', true);

      // Lite: Idea tab removed - Test Ideas is now Pro-only feature

      $show_idea_tab = false;

      if($show_idea_tab)

        echo "<button class='ab-tab-idea-button ab-nav-pill' href='#idea'><svg class='w-6 h-6' fill='currentColor' viewBox='0 0 20 20' xmlns='http://www.w3.org/2000/svg'><path d='M4 3a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V9.414A2 2 0 0013.414 8L9 3.586A2 2 0 007.586 3H4zm5 1.5L13.5 9H10a1 1 0 01-1-1V4.5zM6 11a1 1 0 011-1h4a1 1 0 110 2H7a1 1 0 01-1-1zm1 2a1 1 0 100 2h6a1 1 0 100-2H7z'></path></svg> Idea</button>";

      echo "<button class='config-button ab-nav-pill tab-active' href='#config'><svg viewBox='0 0 20 20' fill='currentColor' id='cog' class='w-8 h-8 text-cool-gray-800 dark:text-cool-gray-200 group-hover:text-purple-600 group-focus:text-purple-600 dark:group-hover:text-purple-50 dark:group-focus:text-purple-50'><path fill-rule='evenodd' d='M11.49 3.17c-.38-1.56-2.6-1.56-2.98 0a1.532 1.532 0 01-2.286.948c-1.372-.836-2.942.734-2.106 2.106.54.886.061 2.042-.947 2.287-1.561.379-1.561 2.6 0 2.978a1.532 1.532 0 01.947 2.287c-.836 1.372.734 2.942 2.106 2.106a1.532 1.532 0 012.287.947c.379 1.561 2.6 1.561 2.978 0a1.533 1.532 0 012.287-.947c1.372.836 2.942-.734 2.106-2.106a1.533 1.532 0 01.947-2.287c1.561-.379 1.561-2.6 0-2.978a1.532 1.532 0 01-.947-2.287c.836-1.372-.734-2.942-2.106-2.106a1.532 1.532 0 01-2.287-.947zM10 13a3 3 0 100-6 3 3 0 000 6z' clip-rule='evenodd'></path></svg> Settings</button>";

      echo "<button class='ab-tab-results-button ab-nav-pill' href='#results'><svg class='w-6 h-6' fill='currentColor' viewBox='0 0 20 20' xmlns='http://www.w3.org/2000/svg'><path d='M2 11a1 1 0 011-1h2a1 1 0 011 1v5a1 1 0 01-1 1H3a1 1 0 01-1-1v-5zM8 7a1 1 0 011-1h2a1 1 0 011 1v9a1 1 0 01-1 1H9a1 1 0 01-1-1V7zM14 4a1 1 0 011-1h2a1 1 0 011 1v12a1 1 0 01-1 1h-2a1 1 0 01-1-1V4z'></path></svg> Results</button>";

      

      // Hook for adding additional navigation buttons

      

      echo "</div>";

      echo "<div class='ab-panels'><div id='configuration_settings' class='ab-tab-content'><div class='show_test_type'><h3>Test Type</h3>";

      $this->show_test_type($post);

      echo "</div><div class='show_full_page_test'><h3>Page and Variations</h3>";

      $this->show_full_page_test($post);

      echo "</div></div><div id='magic_settings' class='magic_definition'><h3 class='magic_definition'>Magic Test</h3>";

      if(!empty($magic_definition)) {

        $scope_summary = $this->get_magic_scope_summary($magic_definition);

        $return_to_url = admin_url('post.php?post=' . $pid . '&action=edit');

        $edit_magic_url = add_query_arg([

          'testid' => $pid,

          'return_to' => rawurlencode($return_to_url),

        ], $this->get_magic_edit_url($magic_definition));

        $decoded_array = json_decode($magic_definition, true);

        $elements_in_test = is_array($decoded_array) ? count($decoded_array) : 0;

        $variation_count = $this->get_magic_variation_count($magic_definition);



        // Try to decode the JSON to ensure it's valid

        $decoded = json_decode($magic_definition);

        if (json_last_error() === JSON_ERROR_NONE) {

          // If valid JSON, pretty print it for better readability

          $formatted_json = wp_json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

          echo "<div class='abst-magic-summary'>";

          echo "<div class='abst-magic-summary-row'><span class='abst-magic-summary-label'>Scope</span><strong>" . esc_html($scope_summary ?: 'Not set') . "</strong></div>";

          echo "<div class='abst-magic-summary-row'><span class='abst-magic-summary-label'>Elements in test</span><strong>" . esc_html($elements_in_test . ' element' . ($elements_in_test === 1 ? '' : 's')) . "</strong></div>";

          echo "<div class='abst-magic-summary-row'><span class='abst-magic-summary-label'>Variations</span><strong>" . esc_html($variation_count . ' variation' . ($variation_count === 1 ? '' : 's')) . "</strong></div>";

          echo "<div class='abst-magic-summary-actions'><a href='" . esc_url($edit_magic_url) . "' class='button button-primary'>Edit test elements</a></div>";

          echo "<details class='abst-magic-advanced'><summary>View advanced config</summary><p>Magic test configuration:</p><textarea id='magic_definition' name='magic_definition' style='width:100%' rows='12' class='code'>" . esc_textarea($formatted_json) . "</textarea></details>";

          echo "</div>";

        } else {

          // If not valid JSON, display as is with a warning

          echo "<p id='magicjsonerror'>Warning: The magic test configuration contains invalid JSON. Please review and correct it.</p>"; // this on page triggers a cleanup function

          echo "<details class='abst-magic-advanced' open><summary>View advanced config</summary><p>Magic test configuration (raw):</p><textarea id='magic_definition' name='magic_definition' style='width:100%' rows='12' class='code'>" . esc_textarea($magic_definition) . "</textarea></details>";

        }

      } else {

        $return_to_url = admin_url('post.php?post=' . $pid . '&action=edit');

        $start_magic_url = add_query_arg([

          'testid' => $pid,

          'return_to' => rawurlencode($return_to_url),

        ], $this->get_magic_edit_url('', $idea_page_flow));

        echo "<p>To create a magic test, visit the page you want to test then click on ". 'Split Test' ." > New Magic Test in the Admin Bar.</p>";

        if (!empty($idea_page_flow)) {

          echo "<p class='description'>Suggested page/flow: <strong>" . esc_html($idea_page_flow) . "</strong></p>";

        }
        echo "<p>Jump to <a href='".esc_url(get_home_url())."?abmagic' class=''>create a test on home page</a></p>";

        echo "<p><img src='data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAecAAACPCAYAAADA3RPXAAAAAXNSR0IArs4c6QAAAARnQU1BAACxjwv8YQUAAAAJcEhZcwAADsMAAA7DAcdvqGQAAByvSURBVHhe7Z17bFTXncf93+4faFWp0qpaqVW1q24jbZVWW6WkVamyUViltJulpaXZbJJSorbQBAykBAIYYjAYjDHEdmxjjG1sXuEVwvv9SHi0JAG6CU2UtEooiCBQSkIUAmna3+p7xnfm3N99eB537Dtzvx/pKzP3PXMP9zPn3HPPVFRW1QjDMAzDMPFJxef/5cvCMAxTLvnK1+9kSjz6nCYxlDPDMGUVfaFnSi/6nCYxlDPDMGUVfaFnSi/6nCYxlDPDMGUVfaFnSi/6nCYxlDPDMGUVfaFnSi/6nCYxlDPDMGUVfaFnSi/6nCYxlDPDMGUVfaFnSi/6nCYxvnIeP2WSnDv/hly6fnnAg/1i//qYGIZhsom+0DOlF31OkxhfOQ+WmJ1g//qYosrd4xbI4s510tHTJTPHeuczDFPa0Rd6pvSiz2kS4ytnLcvBiD6mgjPsQams75KOHog5ldpKn+WYyPONUeOlsrpBahu7pLWtRWrr62TaY2Nk2B3eZeOeux9rkObuTBkKTXeP1E75vmcbTHGjL/S55hePTZbGlg7f/OThRzzLM9FHn9MkJhFyztSWrbRUy30+yzIRZtiD8viyHq+0nHS2yMxxpSSvCTIvWzE76W6QsZ7txC1TpTZ9zI3yK8/80oq+0Oeab939n0bEU2fMMTJG8O/uNRvMPL08E330OU1iylzO35ex1SukVV8w0aT90FCf5Us/nWc+kPfP9HimD3welGkt6nPv7JHWDi3rUqpd2hLLNqUgO8pZR9eS8W9M08vFP73y8JY/yMjRzr8vycMT9TLxiz6nSUxucn69Rr40fIj8YL97+ttnmqRy7W55Qy9fQPQx5Zw7xsi0Zi2CVJqrx8jtevnQ9Mjp9z+Q99M5K52eZbIPBHrhwFzP9PLJUPnGsKFy95RG88WotXmB/HLUXZn5d/xIxla3WF+aWuTxEXob/aXvnKgvIrMOXIzsPHlTfDnf893vS3VNrSxYVC81tXUy6if/61km+kQl57my78IH8v6FgzJLzcN5Gagyry/0+SRKOQ+d9weZsuWU3OM7/VImvXtkqM/6Tu5pspZNC7e/2HJWmXgqcJ+eY8vyGP2C4x4/r9YzPSz6nCYxkcj5aPtXpWL0JNmuly8g+phyyh1jZGabvkj2pX2BjM7lXmfPWXOhP91jTas6KKcLuNCUu5yNlDsb5Vcjhsro8RNkRMDn7cjbnJe6yVl/YUoJ+Kzsw1+XnOfKvgOZ19G3IhRXzncO+w+ZNWeuPDz25+Y1/kLUELZeNtpEKeeLcuGCt3yXopx1s3Z+coYcT8nDTd4aKwRoSytMYkbMTb2ZaaP3yMisasD5yTmTWhnZ6z32XBL2voKiz2kSE4mcixF9TLlkdLW741cmuTZno3Z2UfZV6en2fHftDELIiNyucWM7fTULn5qdu8Zn77PvGA6kviSkvyj0fWnQXxzS4q86KBdctX13jcaIq296lBdNl3Db+vsidK9ULutbNo97s+YzC5MvPqOw+TmnuHJGLXn6rDlG0nitZV28RCvnfVXe/zceObvKZ9//A0yza92mjFv/v7I8n/pCn08gYtxjtpOXnCFASNX5a83Tcja1VbWMEwguSJAp+fUaiXpr1bpZO/VvXTMO2ravnEfvkfHpde0WgVSzeeYYUutm9uNtPQiKPqdJTCRy7q0eIhWP1Mhvru+Wn40cIl9oPd4377w88+QQqXiyXd6+flnO7vmZfOW/hkjF8CHyd49Mkt7zPvsoVM53TA7stLN4eo41EFwMfJroMgmTc0rErhq3tYzrQqUvQubC5bxWzbeOlF2vM+t6tp3eRuZi6ZZa8HHmmtsfWiDN6jNvba72EfQ9MnZBl7n/3GqdK/O6rU7Gfkcv75/+5Oz+ohRFiitnSHjq9FmuaXj9aOUUz7L5p5jvwZGz9/+OW85K3ullrfXNOmflgvU62/OpL/T5RIs5XzlnpOqtwbrlDJEF1HARyD2gBpxq7rbmmWUdEfrLOb1czjXnoG3o5TJhzTm/RCzny3K09TapeOAJOYp5V9plxL2flZ8duyyXToyTfxj1gHRAyNfelI45n01L27OfQuT8SJ1PB7B10lo/Ve7Wy/aXfr+ph8k5SJR6ur8cM9vRtZDw1377dE/T6+sLZ575zgSZ06Y6fHWnXjcvmCDf1Mv79gnIrWXDV84BLQrRpJhi+7KRsJYzppWknFW5s8uY97xl/h9l1sG2Urcu7NfZ9CHQF/pcg2ZsLWYnOT1KZWqYmdqilpSuvU4JqDW7t6drxt7tuoUarZy9tftUsz3eo/c4UgmaHhZ9TpOYyOV86XdPyBeGf1Vmv35ZLu1/QCpGjpMN1y5Lb02qxuyKs45P9DFlncpGz8WleeHk3MWMFFRzRjLN2Lb8vHL2Np1nLmZapuGvPXL2fMHoq4nraMnlHetxo8aq8M/d1Tcg917b3ou8O/gsws9frimm2Mqs5mxeZ8qmR866/Dll2CmvaD3Sf7M8l/pCn0vwqJRdU9ZyxrRsH6fyiEzJUDdr6/mB6ZO0I0w/+fnX2COSs/2FwvVlIdOMne299KDoc5rERC/n66/K/EeHyJdWHjfTnSZuI+eJTXJWbzMg+phCc8eP5KeVM6Sycrzcd88YebyhS1rbV0htTZX81O4lnHO0CHX6k7MTd+3YK2fvOtHUnL3H5z8tygyVX9Y5F/UQ4aLH9vgxcvuIqVLbGbJcSPqTc/TvtbjPOUPOuMdc+vecrWl9X3A7lZyDW2r6zlnPWav8p14Hr+OOvtDnknmLlqRFjNcYkEQLGsvo9bzR91szsUXnllamFurdnop1D9srP7uJPHo59y9a9zre4+s/+pwmMXnJecTmV+Xs+VTeuKLlfFnOrh0mFROHy7fv7atB43Gr7aOkYvg/yQ92vWqast9+vUkqN7/k3UeuctY9s9EJSS9TQFLf8tUFJ91bW4m1r0lVixaxpalrt6l9hN1zDpaxfq33E3Qs4VIrMCOqZHH6Yt8jS6sny33DMvO/MWqyzDRN2ikp3z7srqx7atvxyLnqoOyz3q+Zn2VtK9vkNkJYl8x7LPsvHVrGWtbFSxHl7JQ3u/XIlG/vcvY23Pea3a/7i77QZxstYqeHtpYzgmX1+q4EiM+uTXtEF7COkV2Te7rdezt1zzkjdbOP9HailXOq1u5/79uOLWTKOb/kJWe7afpLK1/yyPnS+Sb5NuY/Wm/VlN+U7StHyT9+r69D2EPDZdqJ89595Cjn+2avUBfFLpn5oHe5gmLdxzTx9CjNNA1nhJhp0vY0G6d7qmaE7G7qs2t74TLWr9Ny1sfsOm51bIEXyvzj6rXdF9Phy2ekth/6rJ9NPHI2n4Xf+y1WvE3EuT9D7w56bOP5ZjznPDCPUSHFlbNzXjy3W+xzZZ1Hz5cq3WGyn+gLfTb57n//2CNgXVu270VD2lhHb8dJoJAsuXmbiENqzaaTl7Ws1Vye2tceq6e07kEdIGerdu/XkctexjXf91hUS4F67Ct1rzzk/anoc5rE5CbnAYw+Jv/8XOZ0qIt9MeTM5BXfYVNtWS+rkh9aNerSi1vOhYp58BKVnOMRfaHPJnZztl+cjmB2TTqf3tvFSOAXgRKOPqdJTEnL+e5ft3gu+B3NVRwzO1a5S374WLXMqW+UxW090tzYKPOqZxTYFyAuyUitdMWMJFvOYb2zbRH7CTys9jxQoZzLM75yLoWfjBw+sUGa7VpZZ5fUzg4ejYphok9KaqUt5vKLvtD3l/6atMOS06NVRQrlXJ7xlfP4KZMGTdDYL/avj4lh4pepMpNijl30hb6/4NEodPBCzRg15GzTb6cwJu/oc5rE+MqZYRimVKMv9EzpRZ/TJIZyZhimrKIv9EzpRZ/TJIZyZhimrKIv9EzpRZ/TJIZyZhimrKIv9EzpRZ/TJIZyZhimrKIv9EzpRZ/TJIZyZhimrKIv9EzpRZ/TJIZyZhimrIKL+7/9+1CmhPP5f/7X0opPOSw0FUIIISXM3/72N5O//vWv8umnn3pqYQwzkEEZRFl0ymW+UM6EkJLGEfNf/vIXuXXrludiyTADGZRBlEVH0PlCORNCSha7xoyL4o0bNzwXS4YZyKAMoizaNeh8oJwJISWLI+dPPvlEPv74Y/nwww89F0uGGcigDKIsokxSzoSQRIILn1Nr/uijj+TatWueiyXDDGRQBlEWndoz5UwISRR2k/bNmzdNjeW9997zXCwZZiCDMoiyiDJZSNM25UwIKUnsjmBoRrx+/bpcvXrVc7FkmIEMyiDKIspkIR3DKGdCSEniJ+crV654LpYMM5BBGaScCSGJRcv5gw8+oJyZQQ/KIMoi5UwISSSUMxPHUM6EkERDOTNxDOVMCEk0lDMTx1DOhJBEQzkzcQzlTAhJNJQzE8dQzoSQREM5h+d/5q6XX3a8Il+/a4RnHlO8UM6EkERTqJwhLcjLT2AQG6LXiSL3TVwsU7ZcMn/1PAT7nbD2Tfnm9x7wzCtWnM8Cx6WTz+cwtvFIXuuVQyhnQkiiiULOP289IRPWvOURSbHljH1i3/pLAYQMMQ+0nO3c8/BUeXTVawXtn3KmnAkhCSUqOUOWkBGk5MzTcsa8SRveMTVJR5yYZgvWSNeSKl5DUnq/mI71EHufzn7HNOwxtVhnO/a+dY3bnod1Hlq4NX3c+Gvv36mx2+9BH5uzTS1nv/eP6c6XCUzH/O/+otpVAw/bT7mGciaEJJqo5AzxQFx287YtZ8jFlrez7Le+94BLsD+e2elaDmL0a7p2pK3l7ezH2b4jNWzX3rcjPH1cjkD95Ky/OPxgakugNLWc9X7s94+/fu+RNWfKmRCSUKKUM17bQrHlrGugkJUjT2cdbAv/Rq3Xfu0nQEfKftJzpttytmPP08cVdtxBXxT8ouWs9+P3/vU2gqYnIZQzISTRRC1nW5ZacrqTFGqoTo0bIsK/IWb7r989ZcSuMTv7sY9Fyxl/naZju6nYPkYn+rixH/0++4ufnIPev92RzD4WyplyJoQklKjljDhNtvrebZBoHJGi6Rnr2q+D1rHlbC+vp+Gv/YVBz3Pka2/blqIt56DmZ7/4yTnovTjR+6CcKWdCSEIphpwRiMWuCWI+elfr5ext2AJFzVl3MLOj7zU7+3PEZgtYi9K+d6yPC3+D7jnj3/nec9b7CYotZMqZciaEJJRiyRlSgshsuUCKdrOuLVcsB5k6Tdi2QPU+nfn2+ti/3QRuy9nZvrNfTLfn2cflV+PXx2kv69fk7hyPLWe9H+f9203a+jNxviiEfQ7lGsqZEJJoCpVzOQaCzLb5milOKGdCSKKhnN3pr8bODEwoZ0JIokm6nJ2mY6dZ2elBrZdjBjaUMyEk0SRdzkw8QzkTQhIN5czEMZQzISTRUM5MHEM5E0ISDeXMxDGUMyEk0VDOTBxDORNCEg3lzMQxlDMhJNFQzkwcQzkTQhIN5czEMZQzISTRUM5MHEM5E0ISjZbz9evXKWdm0IMyiLJIORNCEomfnK9eveq5WDLMQAZlkHImhCQWR86ffvqp3Lx5Uz788EN577335NKlS/LOO+/IW2+9JW+88Yb8/ve/l3Pnzslrr73GMJEFZQplC2UMZQ1lDmUPZRBlEWUSZZNyJoSUJ2fOiFy7pqcacNHDBfDWrVvy0UcfybVr10yz4sWLF83F8o9//KO5cCJvvvkmw0QWp1yhjKGsocyh7KEMoiyiTKJs5iNmQDkTQuIDRNzVJTJpkshdd4lUVIiMGaOXSuPUnj/55BPTjIgay5///GdzkUQtBhfMCxcuyJ/+9CeT8+fPM0zBccoTyhbKGMoayhzKHsogyiLKZL61ZkA5E0IGBz8R64SIGdhN26ip3Lhxw1wcUXtB8yLu/+GiyTDFCsoYyhrKHMoeyqBTa6acCSHxZ/fucBHr9CNmB7tjGC6KqLWgWREXSnTMQfBoC8NEHad8oayhzKHsoQwW0hHMgXImhAwMzz3nFXBQshQzwAXQrkGjOREXSHTIwcWSYYodlDWUOZQ9u8ZMOffDhXevyOlzb8mxl1+VF176P6YIwWeLzxifNSGBfO1rXhHr5CBmB1vQjqQR1GAYpthxyptT/goVMyhrOd+4ecsIQ4uEKW7wmeOzJ8TFu++KjBzplXGBYrZxLoq2qBlmIGKXvSgoazlTzIMXfPaEGPAY1Jw5Ip/5jFfGEYpZY18sGabYiZqylTOaV7UwmIENm7gTzscfiyxd2r+UiyBmQkqdspUza82DH9aeE0xrq8jnPueVMO45Hz7sbt6mmAnxULZyZuevwQ/OAUkY69aJfPGLXinfdluqt7YDnnGmmAkJpGzlrEXBDE5IQoB4/Xpio/aMgUb8gMgJIb5QzkxRQ8ocNFH7DSoCKeN+M+47E0JyhnIOydFTv5PDvzkjh06eNsG/j5w661mOCQ4pU9Asfe+9Ximj8xd6Zgf8UAUhJDso55AcOP6ybNhxQLo3bjNZvWWn7Dh03LMcExxSZrz9tsj993ul/Pd/nxqak1ImJBIo55BsO/CiLHi6XSZMn2vyRHWddD37vGc5JjikTMAAIuPGeaWMoFMX5hNCIoNyDgnlXHhIiYOaMGrEqBlrKeNxKNSkCSGRQzmHhHIuPKRECRvVC/eacc+ZEFI0KOeQRCXnI789K/uPvSQ7D5+QHQePy56jvzEdzNDhTC9rB/MPnnglvd7eF0+ZbWG600nNdFT7rX8ntdR+X855v1GGlBjOqF5+A4jceWeqdzYhpOhQziEpVM6Q4PaDx6R3805p7X5W6p7plNrGFbK0vUdWrt0i67ftM/LV6yHoGb7++b3SvmazLH6mUxY2rpCmlWvNtp7f/6J0rN1i0rl+q2zadci1LqSMjms9m3ZI66oN6fWXtfeadTZs3y/7XnzJs89ihJQQeB7ZT8p4ftkeQIQQUnQo55AUImeIefOuQ7KwqUMmz1qQ3oadJ56qk/bVm0yt2l4Xcu3esF1+PWehZ51JM+e7jgnbfnrF6vS6kPqW3YfNF4HKGTWe9ZEZNQ3S3rtJdh856TnuqENKAIjXb1QvTAsaQIQQUlQo55AUImfUbmsa2tKCnGjEWmNkOvHJeeltYv6KNVuMVJ11N+086BE6lsM0LVxbzvhCsG3/i7Jg2fL0/IlPpoQ+edZ8qZyR2e/UpxbJ8p6Nni8GUYfEGDRRB43qhaZtQsigQTmHJF85Hzp5RhpXrpEpVbXpdasXPyONHavlma710tC2Sh6fnakVT32qTp7f94KRKzKvodUl4NkLG02Tdkv3s7K0vVemzV3sK2c8l728d1N6HkTurIv9onl7WnVm3aqFT5umdX38UYbEkJMn/Uf1cgYQ4ahehAw6lHNI8pUzas2QsS3ftVv3pGvHuM+8qKnD1KSdZdp6Npr5aGpGTdeZjn/bAsW6kLufnLcfOCZP1TWn502fVy/dG7al10WHMNx3dmrukDfuSePLhH4PUYXECPSwtn8Nygkek5o+nQOIEBIjKOeQ5CtndNp6sqYhvd6CZe2y89AJ1zKrNuKe8qL0MjVL20xP6jXP7XY1XaN2a/euxjLLezK1Y0fOWGbTLndzeE1Dq+e+MjqJ2fey61u6ZNfh4t17JjEAzyJjoBAtZQTPMHMAEUJiB+UcknzljB7R6OzlrIfa6t4XfutaZvPuQ/KE1cSMTlqoFWNd+570kpYu13pBckYnsnVb96SnI6idY7q9PmrhMxcsTS+D+9NoUtfvIaqQQQTShXy1kBEMwckBRAiJLZRzSPKVM3pgT7VqxY0da8wzyvYyW/cddd3/nVZdb+S8vHejS85Pr+h1rRckZzzrvHrLLpec65pXeo5tw479pjbuLIMa+3N7j3iWiypkEEDzNJqpg0b14gAihMQeyjkk+cp55brnXLXipct7ZI+qOePZZGzPWQa1WcgZ69pyRicue70gOZua8/N7XXLGs812L3AEz07PmJ+pOdc+3S7P72fNuSxAR67aWv9RvdABjAOIEFIyUM4hyVfOuG880xLgvCUtZoQuexkMHmL32MbgJBDv+m17XfecIVK7aTpIzpiH55sfn53pIT4X+7V+RQv3pdFBzO5F3tDWbTqK6fcQVcgAETSqFwcQIaQkoZxDouWMXtd4JvnA8VcCA5FiuMx5SzKPQ0HCkLozGhg6ac1fmnkGGlm5fqtZd+8Lp1zSxjL2F4J9L54ytV0/OUPEeLbamZca5GRzuva8/eBxWdS80rUumuBxPOig1tS5Vpav3hTpz2KSIoNBQoIGEFm3Ti9NCCkRKOeQaDnjsaY5i5pkcUuXb5a0dpvaKyTbtmqDqzf2rAXLzCNQeP4Zo4ZNqcr0qkaT9o5DJ9K9she3dKbnIXgk6umO1ebeNXpX2+vacj544rSpkTuPYqF5fMb8BjNcKNaF1G3xz61vMaOYQcxP1iwxy6PmjcerohrekxQJ1IaDBhBpbdVLE0JKDMo5JFrO/QW1XDxGhXXx6FR9S6fr0SbID8vY95TxWBPWsZuusV/03ra37bcuYssZ2XXkpGmqtpfzWxfb7352m6ntN3eudW0TtWvdDJ9vSMTgvjF+gEJLGfeZcb+ZA4gQUhZQziEpRM5HX8JQmi9IS9d6V69sO7gnjOeOcR9Z7/vZbftMzdZeHnJFLXr+0szwnFrO5kcvDh6X5s51rk5p9jFiu6s37zS1Y9TWUXOePm+JmY+BUdp6Nphfs9LHlE9IRKCHtd+oXuiRjVG9OIAIIWUF5RwSDIe5cccBWbVhW3bZuF12Hc4MNgJB4x4xRItaKp5hXrFms+mRvWrjDvM4le5NnV731O/MfGzXWQ/3ntEjG8OAOrJF5y7UfPW6zn67rP12rnvOfBnYuveo6wsBlsXzz9h+76Yd5j1E9bOSpEBefz14VC8OIEJI2UI59xPURHOJn9QwDRI+ePK06RQGMdrN2H7Bo1erNmw3y2F5rIdtYLr9nDKep8Z9Zr1+rvs90rds0Px8Q/IE0g0a1QvTOYAIIWUN5RzDQKro+DV9br3pRNa7aafpuNW9cbt5dtnu5Y1HtqK6P1yMkBxxRvXiACKEJBrKOYZB07XTkQw9ryFp9PaeNrfeJWb0vEZzddS13ShDsgT3jHHvOGgAEfySFCEkMVDOMQwGBVli/fKUX/DMNe4hF/v3mAsN6Qf0rsYAIn5SxqNSHNWLkERCOcc0+KEMDLWJZ47x+854Fnr2oiape6bT3GPesudwelCTOIeEgOeR/Ub1wgAiHNWLkERDOcc4aK5Gj3GM4LXz0HEz8hikjY5dfh3P4hjiA0bu8hvVC6LGiF+EkMRDOTNFDbEIG9ULTdscQIQQ0gflzBQ1pG9UL78BRHCfmQOIEEJ8KFs5H3v5VY8omIENzkGiwWNPHECEEJIHZSvn0+fe8siCGdjgHCQSDBBy//1eKSMYQIRSJoT0Q9nK+cK7VzyyYAY2OAeJAtIdN84rZAQ1aAzFSQghWVC2cgasPQ9eElVrxj3joFG9cK+Zo3oRQnKkrOV84+YtCnoQgs8cn30i2L3bfwAR/KwjBxAhhORJWcvZ4eLlq3Lm9T/IiTPn5NgrrzFFCD5bfMb4rBOF7oV9220cQIQQUjCJkDMhRQOPQkHKGFSEA4gQQiKCciaEEEJiBuVMCCGExAzKmRBCCIkZlDMhhBASMyhnQgghJGZQzoQQQkjMoJwJIYSQmEE5E0IIITGDciaEEEJiBuVMCCGExAzKmRBCCIkZlDMhhBASMyhnQgghJGZQzoQQQkjMoJwJIYSQmEE5E0IIITGDciaEEEJiBuVMCCGExAzKmRBCCIkZlDMhhBASMyhnQgghJGZQzoQQQkjM+H8UMmORjp9rhwAAAABJRU5ErkJggg=='>";

      }

      echo "<style>.code { font-family: monospace; white-space: pre; }.abst-magic-summary{display:flex;flex-direction:column;gap:16px;}.abst-magic-summary-row{display:flex;flex-direction:column;gap:4px;padding:12px 14px;background:#f8fafc;border:1px solid #dcdcde;border-radius:8px;}.abst-magic-summary-label{font-size:12px;font-weight:600;letter-spacing:.04em;text-transform:uppercase;color:#646970;}.abst-magic-summary-actions{margin-top:4px;}.abst-magic-advanced{margin-top:6px;}.abst-magic-advanced summary{cursor:pointer;font-weight:500;color:#2271b1;}.abst-magic-advanced[open] summary{margin-bottom:12px;}</style>";

      echo "</div><div class='css_test_variations show_css_test'><h3 class='css_test_variations'>Code Test</h3>";



      // css test vars

      $css_test_variations  = get_post_meta($pid,'css_test_variations',true);

      if(empty($css_test_variations))

        $css_test_variations = '2';      //not a test w 1

      echo '<label class="css_test_variations" for="css_test_variations">Number of variations you want to test.</label><input class="css_test_variations" type="number" id="css_test_variations" min="1" max = "999" name="css_test_variations" placeholder="2" value="' . esc_attr( $css_test_variations ) . '"  /><span class="css_test_variations" > variations</span>';



      echo "<p class='css_test_variations'>The body will have one of the the following classes added. </p>";

      if( abst_user_level()== 'free' )

        echo "<p class='show'>NOTE: AB Split Test Free is limited to 2 CSS variations.</p>";

      echo "<div class='css-test-helper-zone css_test_variations' style='display:flex; flex-wrap:wrap; gap:8px;'><code style='background:#f1f5f9; padding:6px 12px; border-radius:4px; font-size:13px;'><span style='color:#64748b;'>body.</span>test-css-TESTID-1</code><code style='background:#f1f5f9; padding:6px 12px; border-radius:4px; font-size:13px;'><span style='color:#64748b;'>body.</span>test-css-TESTID-2</code><code style='background:#f1f5f9; padding:6px 12px; border-radius:4px; font-size:13px;'><span style='color:#64748b;'>body.</span>test-css-TESTID-3</code></div>";



      echo "</div>";

      echo "<div class='show_css_classes expanded'><h3>On-Page Elements</h3>";

      echo "<p  class='show'>You can go straight to the block editor or your favourite page builder.<BR>Click the element you want to test, then create your test in the AB Split Test Section of your settings.</p>";

      

      //if bricks, add bricks helper

      if (wp_get_theme()->get('Name') == 'Bricks') {

        echo "<h4>Bricks Instructions.</h4>";

        echo "<ol>";

        echo "<li>Load the Bricks editor.</li>";

        echo "<li>Click any Bricks element you want to test.</li>";

        echo "<li>Click the Style Tab, then " . 'Split Test' . ".</li>";

        echo "<li>Choose your test.</li>";

        echo "<li>Give your test variation a name.</li>";

        echo "<li>Create your alternate version in Bricks, then tag it with the same test name and a new variation name.</li>";

        echo "</ol>";

      }

      //elementor

      if (did_action( 'elementor/loaded')) {

        echo "<h4>Elementor Instructions.</h4>";

        echo "<ol>";

        echo "<li>Load the Elementor editor on the page or template you want to test in.</li>";

        echo "<li>Click any element or section to test.</li>";

        echo "<li>Click the Advanced Tab, then " . 'Split Test' . ".</li>";

        echo "<li>Choose your test.</li>";

        echo "<li>Give your test variation a name.</li>";

        echo "<li>Create your alternate version in Elementor, and tag it with the same test name and a new variation name.</li>";

        echo "</ol>";

      }

      // beaver

      if (class_exists('FLBuilderModel')) {

        echo "<h4>Beaver Builder.</h4>";

        echo "<ol>";

        echo "<li>Load the Beaver Builder editor.</li>";

        echo "<li>Click any Beaver Builder element you want to test.</li>";

        echo "<li>Click the Advanced Tab, then " . 'Split Test' . ".</li>";

        echo "<li>Choose your test.</li>";

        echo "<li>Give your test variation a name.</li>";

        echo "<li>Create your alternate version in Beaver Builder, and tag it with the same test name and a new variation name.</li>";  

        echo "</ol>";

      }

      //Breakdance

      if (class_exists('Breakdance')) {

        echo "<h4>Breakdance.</h4>";

        echo "<ol>";

        echo "<li>Load the Breakdance editor.</li>";

        echo "<li>Click any Breakdance element you want to test.</li>";

        echo "<li>Click the Advanced Tab, then " . 'Split Test' . ".</li>";

        echo "<li>Choose your test.</li>";

        echo "<li>Give your test variation a name.</li>";

        echo "<li>Create your alternate version in Breakdance, and tag it with the same test name and a new variation name.</li>";

        echo "</ol>";

      }      

      global $oxygen_vsb_components;

      if( !empty($oxygen_vsb_components)){

        echo "<h4>Oxygen</h4>";

        echo "<ol>";

        echo "<li>Load the Oxygen editor.</li>";

        echo "<li>Click any Oxygen element you want to test.</li>";

        echo "<li>Click the Advanced Tab, then " . 'Split Test' . ".</li>";

        echo "<li>Choose your test.</li>";

        echo "<li>Give your test variation a name.</li>";

        echo "<li>Create your alternate version in Oxygen, and tag it with the same test name and a new variation name.</li>";

        echo "</ol>";

      }

      //Blocks

      echo "<h4>Blocks / Gutenberg.</h4>";

      echo "<ol>";

      echo "<li>Load the Block editor.</li>";

      echo "<li>Click any block you want to test.</li>";

      echo "<li>Click the Advanced Tab, then " . esc_html( BT_AB_TEST_WL_ABTEST ) . ".</li>";

      echo "<li>Choose your test from the dropdown.</li>";

      echo "<li>Give your test variation a name.</li>";

      echo "<li>Create your alternate version in Blocks, and tag it the same test and a different variation name.</li>";

      echo "</ol>";

      //css classes / shortcodes

      echo "<h4>CSS Classes / Shortcodes.</h4>";

      echo "<ol>";

      echo "<li>Make note of your test classes below these instructions.</li>";

      echo "<li>Load and edit the page you want to test. You can do this on any page in anything that lets you apply CSS classes or edit HTML.</li>";

      echo "<li>Choose an element wrapping your test variation.</li><li>Add the CSS classes you copied from the test like 'ab-". esc_html( $post->ID ) ." ab-var-{name}' to that element.</li>"; 

      echo "<li>Give your test variation a name.</li>";

      echo "<li>Create your alternate version on the same page and apply the same test classes, but with a different variation name.</li>";

      echo "</ol>";

      // show classes and shortcodes

      echo "<div class='test-class-info test-variation-info'>";

      echo "<label>HTML Classes</label>";

      echo "<input type='text' value='ab-". esc_attr( get_the_ID() ) ." ab-var-{name}'/>";

      echo "<p><small>Replace {name} with your variation name</small></p>";

      //shortcodes

      echo "<label>Shortcode</label>";

      echo "<input type='text' value=\"[ab_split_test eid='". esc_attr( $post->ID ) ."' var='{name}']Text One![/ab_split_test]\"/>";

      echo "</div></div><div class='bt_experiments_inner_custom_box'><h3>Goals</h3><div class='goal'><div class='conversion-goal'>";

      $this->bt_experiments_inner_custom_box($post);// conversions / goals Primary conversion goal

      echo "</div></div>";



      

      if( abst_user_level()  == 'agency'){

        $max_goals = 10;

        $goals = get_post_meta($post->ID,'goals',true);

        echo "<div class='show_goals'>";

        $subgoals = range(1,$max_goals);

        

        foreach($subgoals as $i){

          $goal_key = false;

          if(isset($goals[$i]))

          {

            $goal = $goals[$i];

            //if goal is array

            if(is_array($goal)){            

              $goal_key = array_keys($goal)[0];

              $goal = $goal[$goal_key];

            }

          }



          if(!empty($goal_key)){

            echo "<div class='subgoal test-goal-" . esc_attr( $i ) . "'>";

            echo "<span class='close-goal'>x</span>";

            echo "<h4>Sub Goal " . esc_html( $i ) . "</h4> ";

            echo "<label>Trigger Type</label>";

            echo "<select class='goal-type' name='goal[" . esc_attr( $i ) . "]'>";

            echo "<option value=''>Select Goal Trigger...</option>";

            echo "<option value='page' ".($goal_key == 'page' ? "selected='selected'" : "") .">Page or Post Visit</option>";

            echo "<option value='url' ".($goal_key == 'url' ?  "selected='selected'" : "").">URL Visited</option>";

            echo "<option value='text' ".($goal_key == 'text' ? "selected='selected'" : "").">Text Visible on Page</option>";

            echo "<option value='selector' ".($goal_key == 'selector' ?  "selected='selected'" : "").">Element Clicked</option>";

            echo "<option value='link' ".($goal_key == 'link' ? "selected='selected'" : "").">Link Clicked</option>";

            echo "<option value='time' ".($goal_key == 'time' ? "selected='selected'" : "").">Time Active</option>";

            echo "<option value='scroll' ".($goal_key == 'scroll' ? "selected='selected'" : "").">Scroll Depth</option>";

            echo "<option value='block' ".($goal_key == 'block' ? "selected='selected'" : "") .">Conversion Element Visible</option>";

            echo "<option value='javascript' ".($goal_key == 'javascript' ?  "selected='selected'" : "").">JavaScript Event</option>";

            if (defined('WC_VERSION')) {

              echo "<optgroup label='WooCommerce'>";

              $woo_pages = $this->get_woo_pages();

              foreach($woo_pages as $name => $page) {

                if(!empty($page)) {

                  echo "<option value=\"" . esc_attr( $page ) . "\">" . esc_html( $name ) . "</option>";

                }

              }

              echo "<option value=\"woo-order-pay\" ".($goal_key == 'woo-order-pay' ? "selected='selected'" : "").">Checkout Payment Page</option>";

              echo "<option value=\"woo-order-received\" ".($goal_key == 'woo-order-received' ? "selected='selected'" : "").">Checkout Order Received (Thank You Page)</option>";

              echo "</optgroup>";

            }

            

            // SureCart options

            if (class_exists('SURECART')) {

              echo "<optgroup label='SureCart'>";

              echo "<option value=\"surecart-order-paid\" ".($goal_key == 'surecart-order-paid' ? "selected='selected'" : "").">Order Paid</option>";

              echo "</optgroup>";

            }



            if (function_exists('fluent_cart_scheduler_register')) {

              echo "<optgroup label='FluentCart'>";

              echo "<option value=\"fluentcart-order-paid\" ".($goal_key == 'fluentcart-order-paid' ? "selected='selected'" : "").">Order Paid</option>";

              echo "</optgroup>";

            }

            // Easy Digital Downloads options

            if(class_exists('Easy_Digital_Downloads')) {

              echo "<optgroup label='Easy Digital Downloads'>";

              echo "<option value=\"edd-purchase\" ".($goal_key == 'edd-purchase' ? "selected='selected'" : "").">Purchase</option>";

              echo "</optgroup>";

            }

            

            // WP Pizza options

            if (class_exists('WPPIZZA')) {

              echo "<optgroup label='WP Pizza'>";

              echo "<option value=\"wp-pizza-is-checkout\" ".($goal_key == 'wp-pizza-is-checkout' ? "selected='selected'" : "").">Checkout Page</option>";

              echo "<option value=\"wp-pizza-is-order-history\" ".($goal_key == 'wp-pizza-is-order-history' ? "selected='selected'" : "").">Order History Page</option>";

              echo "</optgroup>";

            }



            // Form submission conversions

            if (function_exists('abst_get_form_optgroups')) {

              echo wp_kses_post( abst_get_form_optgroups($goal_key) );

            }



            echo "</select>";

            echo "<label class='goal-value-label' for='goal_value[" . esc_attr( $i ) . "]'>Goal Value</label>";

            echo "<input class='goal-value' type='text' name='goal_value[" . esc_attr( $i ) . "]' value='" . esc_attr($goal) . "' placeholder='' />";

            echo "<select class='goal-page' name='goal_page[" . esc_attr( $i ) . "]'>";

            $page_title = get_the_title($goal);

            if(!empty($page_title))

              echo "<option value='" . esc_attr( $goal ) . "'>" . esc_html( $page_title ) . "</option>";

            echo "</select></div>";

          }

          else

          {

            echo "<div class='subgoal hidden test-goal-" . esc_attr( $i ) . "'>";

            echo "<span class='close-goal'>x</span>";

            echo "<h4>Sub Goal " . esc_html( $i ) . "</h4>";

            echo "<select class='goal-type' name='goal[" . esc_attr( $i ) . "]'>";

            echo "<option value=''>Select Goal Trigger...</option>";

            echo "<option value='page'>Page or Post Visit</option>";

            echo "<option value='url'>URL Visited</option>";

            echo "<option value='text'>Text Visible on Page</option>";

            echo "<option value='selector'>Element Clicked</option>";

            echo "<option value='link'>Link Clicked</option>";

            echo "<option value='time'>Time Active</option>";

            echo "<option value='scroll'>Scroll Depth</option>";

            echo "<option value='block'>Conversion Element Visible</option>";

            echo "<option value='javascript'>JavaScript Event</option>";

            if (defined('WC_VERSION')) {

              echo "<optgroup label='WooCommerce'>";

              $woo_pages = $this->get_woo_pages();

              foreach($woo_pages as $name => $page) {

                if(!empty($page)) {

                  echo "<option value=\"" . esc_attr( $page ) . "\">" . esc_html( $name ) . "</option>";

                }

              }

              echo "<option value=\"woo-order-pay\">Checkout Payment Page</option>";

              echo "<option value=\"woo-order-received\">Checkout Order Received (Thank You Page)</option>";

              echo "</optgroup>";

            }

            

            // SureCart options

            if (class_exists('SURECART')) {

              echo "<optgroup label='SureCart'>";

              echo "<option value=\"surecart-order-paid\">Order Paid</option>";

              echo "</optgroup>";

            }



 if (function_exists('fluent_cart_scheduler_register')) {

              echo "<optgroup label='FluentCart'>";

              echo "<option value=\"fluentcart-order-paid\">Order Paid</option>";

              echo "</optgroup>";

            }

            

            // Easy Digital Downloads options

            if(class_exists('Easy_Digital_Downloads')) {

              echo "<optgroup label='Easy Digital Downloads'>";

              echo "<option value=\"edd-purchase\">Purchase</option>";

              echo "</optgroup>";

            }

            

            // WP Pizza options

            if (class_exists('WPPIZZA')) {

              echo "<optgroup label='WP Pizza'>";

              echo "<option value=\"wp-pizza-is-checkout\">Checkout Page</option>";

              echo "<option value=\"wp-pizza-is-order-history\">Order History Page</option>";

              echo "</optgroup>";

            }

            

            // Form submission conversions

            if (function_exists('abst_get_form_optgroups')) {

              echo wp_kses_post( abst_get_form_optgroups() );

            }

            

            echo "</select>";

            echo "<label class='goal-value-label' for='goal_value[" . esc_attr( $i ) . "]'>Goal Value</label>";

            echo "<input class='goal-value' type='text' name='goal_value[" . esc_attr( $i ) . "]' placeholder='' />";

            echo "<select class='goal-page' name='goal_page[" . esc_attr( $i ) . "]'>";

            echo "</select></div>";

          } 

        }

        echo "<button class='button button-small add-goal'>+ Add Goal</button>";

        echo "</div>";

      }

      else

      {

        echo "<div class='show_goals'><p>Add subgoals, integrate with Woo and other ex-commerce tools, and so much more. </p><p><a href='https://absplittest.com/pricing' target='_blank'>Try pro free for 7 days</a></p></div>";


      }

      echo "</div>";

      

      //coming soon



// Check if Thompson Sampling is enabled globally

      $thompson_sampling_enabled = abst_get_admin_setting('abst_thompson_sampling_enabled');

      

      if(abst_user_level() == 'agency' && $thompson_sampling_enabled){

        echo "<div class='test_conversion_styles'><h3>Winning Mode</h3>";

        echo "<p>Choose how traffic is distributed between variations.</p>";

        echo "<div class='conversion_style'>Optimization Style: <select id='conversion_style' name='conversion_style'>";

        $conversion_style = get_post_meta($pid,'conversion_style',true);

        if($conversion_style == 'thompson'){

          echo "<option value='bayesian'>Standard - Bayesian</option>";

          echo "<option value='thompson' selected>Dynamic - Multi Armed Bandit</option>";

        }else{

          echo "<option value='bayesian' selected>Standard - Bayesian</option>";

          echo "<option value='thompson'>Dynamic - Multi Armed Bandit</option>";

        }

        echo "</select><p>Standard - Bayesian evenly splits traffic between variations and observes traffic until statistical winner is found.<BR> Dynamic - Multi Armed Bandit mode is a continual optimization mode, where traffic is shifted to the best performing variation over time. No 'winner found' or autocomplete as traffic is shifted to the best performing variation over time.</p></div></div>";

      }else{

        echo "<input type='hidden' name='conversion_style' value='bayesian'/>";

      }



      echo "<div class='show_targeting_options collapsed'><h3>Test Visitor Segmentation</h3><p>Choose which visitors will be tested on. <BR/>Everyone else will see the default.<BR/>Filters apply in the execution order ↓</p>";

      $this->show_targeting_options($post);



      //webhook stuff

      $agency = abst_user_level() == 'agency';

      if($agency){

        echo "<div class='webhooks_settings collapsed'><h3>Webhooks</h3><p>Send a POST of test result data when a test is complete.</p><p><input type='text' id='bt_webhook_url' name='bt_webhook_url' style='width:100%;' placeholder='Enter a complete URL e.g. https://you.com/thing' value='" . esc_attr( $webhook_url ) . "'></p><p>Sample JSON</p>";

        $webhook_sample = array(

          'event' => 'complete',

          'testId' => $pid,

          'testName' => get_the_title($pid) ? get_the_title($pid) : 'New Test',

          'winningVariation' => 'variation2',

          'winningVariationLabel' => 'Variation 2',

          'winnerPercentage' => 95,

          'observations' => array(

            'variation1' => array(

              'visit' => 1500,

              'conversion' => 120,

              'device_size' => array(

                'desktop' => array(

                  'visit' => 700,

                  'conversion' => 58,

                ),

                'tablet' => array(

                  'visit' => 300,

                  'conversion' => 24,

                ),

                'mobile' => array(

                  'visit' => 500,

                  'conversion' => 38,

                ),

              ),

            ),

            'variation2' => array(

              'visit' => 1600,

              'conversion' => 180,

              'device_size' => array(

                'desktop' => array(

                  'visit' => 720,

                  'conversion' => 82,

                ),

                'tablet' => array(

                  'visit' => 310,

                  'conversion' => 35,

                ),

                'mobile' => array(

                  'visit' => 570,

                  'conversion' => 63,

                ),

              ),

            ),

            'variation3' => array(

              'visit' => 1400,

              'conversion' => 130,

              'device_size' => array(

                'desktop' => array(

                  'visit' => 650,

                  'conversion' => 61,

                ),

                'tablet' => array(

                  'visit' => 260,

                  'conversion' => 21,

                ),

                'mobile' => array(

                  'visit' => 490,

                  'conversion' => 48,

                ),

              ),

            ),

          ),

        );

        echo '<code><pre>' . esc_html(wp_json_encode($webhook_sample, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) . '</pre></code></div>';

      }

      

      echo "<div class='restart_test collapsed'><h3>Restart Test</h3><p>Made some changes and want to restart the test?</p>";

      echo "<p>Type <code>DELETE</code> into the box to confirm.</p>";

      echo '<input type="text" id="restart-confirm"/>';

      echo "<p><strong>Caution</strong>, this is not reversible</p><button class='button' id='bt_clear_experiment_results' eid='" . esc_attr( $pid ) . "'>Clear Results & Restart Test</button>";

      

      echo "</div></div>";

      if($show_idea_tab) {

        $idea_total = null;

        if ($idea_impact !== '' && $idea_reach !== '' && $idea_confidence !== '' && $idea_effort !== '') {

          $idea_total = intval($idea_impact) + intval($idea_reach) + intval($idea_confidence) + (6 - intval($idea_effort));

        }

        echo "<div id='idea_settings' class='ab-tab-content' style='display:none;'>";

        echo "<div class='show_test_type'><h3>Idea Details</h3>";

        echo "<div class='abst-idea-panel'>";

        echo "<div class='abst-idea-field abst-idea-field-lg'><label for='abst_idea_hypothesis'>Hypothesis</label><textarea id='abst_idea_hypothesis' name='abst_idea_hypothesis' rows='4'>" . esc_textarea($idea_hypothesis) . "</textarea></div>";

        echo "<div class='abst-idea-field'><label for='abst_idea_page_flow'>Page / Flow</label><input id='abst_idea_page_flow' type='text' name='abst_idea_page_flow' value='" . esc_attr($idea_page_flow) . "'></div>";

        echo "<div class='abst-idea-field abst-idea-field-lg'><label for='abst_idea_observed_problem'>Observed Problem</label><textarea id='abst_idea_observed_problem' name='abst_idea_observed_problem' rows='4'>" . esc_textarea($idea_problem) . "</textarea></div>";

        echo "<div class='abst-idea-field'><label for='abst_idea_next_step'>Next Step</label><input id='abst_idea_next_step' type='text' name='abst_idea_next_step' value='" . esc_attr($idea_next_step) . "'></div>";

        echo "<div class='abst-idea-score-grid'>";

        echo "<div class='abst-idea-score-card' style='display:grid;gap:6px;'><label style='font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:#64748b;'>Impact</label><select name='abst_idea_impact' class='abst-idea-score-select' style='font-weight:600;'><option value=''>—</option><option value='1'" . selected($idea_impact, '1', false) . ">1 - Low</option><option value='2'" . selected($idea_impact, '2', false) . ">2 - Limited</option><option value='3'" . selected($idea_impact, '3', false) . ">3 - Moderate</option><option value='4'" . selected($idea_impact, '4', false) . ">4 - High</option><option value='5'" . selected($idea_impact, '5', false) . ">5 - Very high</option></select></div>";

        echo "<div class='abst-idea-score-card' style='display:grid;gap:6px;'><label style='font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:#64748b;'>Reach</label><select name='abst_idea_reach' class='abst-idea-score-select' style='font-weight:600;'><option value=''>—</option><option value='1'" . selected($idea_reach, '1', false) . ">1 - Few users</option><option value='2'" . selected($idea_reach, '2', false) . ">2 - Small segment</option><option value='3'" . selected($idea_reach, '3', false) . ">3 - Good segment</option><option value='4'" . selected($idea_reach, '4', false) . ">4 - Broad reach</option><option value='5'" . selected($idea_reach, '5', false) . ">5 - Most users</option></select></div>";

        echo "<div class='abst-idea-score-card' style='display:grid;gap:6px;'><label style='font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:#64748b;'>Confidence</label><select name='abst_idea_confidence' class='abst-idea-score-select' style='font-weight:600;'><option value=''>—</option><option value='1'" . selected($idea_confidence, '1', false) . ">1 - Low proof</option><option value='2'" . selected($idea_confidence, '2', false) . ">2 - Light signal</option><option value='3'" . selected($idea_confidence, '3', false) . ">3 - Moderate</option><option value='4'" . selected($idea_confidence, '4', false) . ">4 - Good signal</option><option value='5'" . selected($idea_confidence, '5', false) . ">5 - Strong proof</option></select></div>";

        echo "<div class='abst-idea-score-card' style='display:grid;gap:6px;'><label style='font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:#64748b;'>Effort</label><select name='abst_idea_effort' class='abst-idea-score-select' style='font-weight:600;'><option value=''>—</option><option value='1'" . selected($idea_effort, '1', false) . ">1 - Very easy</option><option value='2'" . selected($idea_effort, '2', false) . ">2 - Easy</option><option value='3'" . selected($idea_effort, '3', false) . ">3 - Moderate</option><option value='4'" . selected($idea_effort, '4', false) . ">4 - Some setup</option><option value='5'" . selected($idea_effort, '5', false) . ">5 - Complex</option></select></div>";

        echo "<div class='abst-idea-score-card abst-idea-score-total' style='display:grid;gap:6px;'><label style='font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:#64748b;'>Total</label><input type='text' readonly class='abst-idea-total-field' value='" . esc_attr($idea_total !== null ? $idea_total : '—') . "' style='text-align:center;font-weight:700;'></div>";

        echo "</div>";

        echo "</div></div></div>";

      }

      

      echo "<div class='abst_show_experiment_results ab-tab-content'>";

      $this->abst_show_experiment_results($post);



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

      echo "</div>";

      

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

      echo "<div class='ab-test-full-page-start-page'><p>Choose the page where your test begins.</p>";



      echo '<select name="bt_experiments_full_page_default_page" id="bt_experiments_full_page_default_page">';

      echo '<option value="" disabled ' . (empty($selected_page) ? 'selected' : '') . '>Select a page…</option>';

      if(!empty($selected_page))

      {

        echo '<option value="', esc_attr( $selected_page ), '" selected="selected">', esc_html( get_the_title($selected_page) ), '</option>';

      }

      

      foreach ($posts as $k => $post) {

                

        echo '<option value="', esc_attr( $post->ID ), '"', $selected_page == $post->ID ? ' selected="selected"' : '', '>', esc_html( $post->post_title ), '</option>';

        

      }

      //archives taxonomies

      if(!empty($archives_taxonomies))

      {

        foreach ($archives_taxonomies as $k => $tax) {

          echo '<option value="', esc_attr( $k ), '"', $selected_page == $k ? ' selected="selected"' : '', '>', esc_html( $tax ), '</option>';

        }

      }



      echo '</select>'; // end select

      $lastPostType = '';

      echo "<a href='' id='full-page-test-page-preview' target='_blank' style='display:none;'>view page →</a>";



      echo "</div><div class='page-variations-wrapper'><p>Traffic will be split evenly across this page and any variations you add.</><h4>Page Variations</h4>";

      echo "<p>We'll split your traffic evenly between the page you choose above, and any variations you add below.</p>";

      if($this->isf())

        echo "<p><strong>NOTE: this free version is limited to one test variation. <a href='https://absplittest.com/pricing?utm_source=ug' target='_blank'>Upgrade for unlimited tests with unlimited variations, AI assistance and more.</a></strong></p>";

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

              $variation = get_page_by_path($k, OBJECT, abst_post_types_to_test());

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

            echo '<option value="', esc_attr( $page_slug ), '" selected="selected">', esc_html( $page_title ), '</option>';

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

              echo "<option value='", esc_attr( $slug ), "' ", esc_attr( $selected ), ">", esc_html( $title ), " | ", esc_html( $page->post_type ), " | ", esc_html( $post_name ), " </option>";

          }

          echo "</select></div>";

          if(!empty($page_variations)){

            //echo "<div id='variation-meta-wrapper'>";

            foreach($page_variations as $k => $page_variation){

              $label = $variation_meta[$k]['label'] ?? '';

              $image = $variation_meta[$k]['image'] ?? '';

              //echo "<div class='variation-meta-item'>";

              //echo "<label>Label for variation $k</label>";

              ////echo "<input type='text' name='variation_label[$k]' value='".esc_attr($label)."' />";

              //echo "<label>Screenshot URL</label>";

              //echo "<input type='text' name='variation_image[$k]' value='".esc_attr($image)."' placeholder='https://...' />";

              //echo "</div>";

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

      $css_test_selected = '';

      $magic_selected = '';



      if($test_type == 'ab_test')

      $ab_test_selected = 'checked';

      if($test_type == 'full_page')

        $full_page_test_selected = 'checked';

      if($test_type == 'css_test')

        $css_test_selected = 'checked';

      if($test_type == 'magic')

        $magic_selected = 'checked';



      echo '<div>

    <input type="radio" id="magic" name="test_type" value="magic" '. esc_attr( $magic_selected ).'>

    <label for="magic">

      <h5>Magic - Point & Click</h5>

      <p>Click anything on your site to test it, and let the magic AI guide you. ✨</p>

    </label>  

  </div>

  <div>

    <input type="radio" id="full_page" name="test_type" value="full_page" '. esc_attr( $full_page_test_selected ).'>

    <label for="full_page"><h5>Full Page</h5><p>Swap between unlimited pages or posts to see the best performer.</p></label>

  </div>

  <div>

    <input type="radio" id="ab_test" name="test_type" value="ab_test" '. esc_attr( $ab_test_selected ).'>

    <label for="ab_test"><h5>On Page Elements</h5><p>Compare different on-page elements to discover the best layout.</p></label>

  </div>

  <div>

    <input type="radio" id="css_test" name="test_type" value="css_test" '. esc_attr( $css_test_selected ).'>

    <label for="css_test">

      <h5>Test Code</h5>

      <p>Test CSS styles or JavaScript.</p>

    </label>

  </div>

  ';

          }



    function show_targeting_options($post){



      echo wp_kses_post( $this->show_login_targeting_options($post) );



      $target_option_device_size = get_post_meta($post->ID,'target_option_device_size',true);

      $url_query = get_post_meta($post->ID,'url_query',true);

      $target_percentage = get_post_meta($post->ID,'target_percentage',true);

      $log_on_visible = get_post_meta($post->ID,'log_on_visible',true);

      

      echo '<h4><label for="bt_experiments_target_option_device_size">Device Size</strong></h4><select name="bt_experiments_target_option_device_size" id="bt_experiments_target_option_device_size">';

      echo '<option value="all">All Sizes</option>';

      echo '<option value="desktop" '. ((isset($target_option_device_size) && $target_option_device_size == 'desktop') ? 'selected' : '').'>Desktop (1024px and above)</option>';

      echo '<option value="desktop_tablet" '. ((isset($target_option_device_size) && $target_option_device_size == 'desktop_tablet') ? 'selected' : '').'>Desktop + Tablet</option>';

      echo '<option value="tablet" '. ((isset($target_option_device_size) && $target_option_device_size == 'tablet') ? 'selected' : '').'>Tablet (768px - 1023px)</option>';

      echo '<option value="tablet_mobile" '. ((isset($target_option_device_size) && $target_option_device_size == 'tablet_mobile') ? 'selected' : '').'>Tablet + Mobile</option>';

      echo '<option value="mobile" '. ((isset($target_option_device_size) && $target_option_device_size == 'mobile') ? 'selected' : '').'>Mobile (under 768px)</option>';

      echo '</select>';

      echo '<h4>URL filtering</h4><label for="bt_experiments_url_query">Query:</label>';

      echo '<BR><textarea id="bt_experiments_url_query" name="bt_experiments_url_query" style="width:100%;min-height:72px;" placeholder="utm_source=Google&#10;utm_source=Facebook">' . esc_textarea( $url_query ) . '</textarea>';

      echo '<p>Test on traffic with matching URL query strings. Use one rule per line (recommended), or separate OR rules with <code>|</code> or commas. Use <code>*</code> for URL contains, and start a rule with <code>NOT </code> to exclude.</p><p class="small urlqueryexamples">EXAMPLES +</p><div class="target-example">*thanks will match any URL containing "thanks". Example matching values: */pl match any complete URL with the string /pl </div><div class="target-example">"utm_source" will match any URL query with the key "utm_source". Example matching values: ?utm_source ?utm_source=anything ?utm_source=somethingelse </div><div class="target-example">"utm_source=fb" will match only when the key and value is a match. Example matching values: ?utm_source=fb  </div><div class="target-example">"NOT ?licenceKey" will exclude any URL containing the string "?licenceKey" </div><div class="target-example">Multiple OR rules: <code>utm_source=fb | utm_source=google | *pricing*</code></div><BR>';

      echo '<div class="ab-target-percentage"><h4><label for="bt_experiments_target_percentage">Traffic allocation percentage</label></h4><p>Limit the number of site visitors that get tested by a percentage.</p><input type="number" min="1" max="100" id="bt_experiments_target_percentage" name="bt_experiments_target_percentage" style="width:100%;" placeholder="100" value="' . esc_attr( $target_percentage ) . '"  /><p id="percentage_description"></p></div>';

      echo '<div class="ab-log-on-visible"><h4>Visit Tracking</h4><label for="bt_experiments_log_on_visible"><input type="checkbox" class="ab-toggle" id="bt_experiments_log_on_visible" name="bt_experiments_log_on_visible" value="1" ' . checked( $log_on_visible, '1', false ) . '> Wait until element is visible to start tracking visits</label><p>When enabled, visits are only counted when the test element becomes visible on screen. Useful for dynamic content or elements below the fold. When disabled (default), visits are logged immediately on page load.</p></div>';

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



      echo '<div class="ab-targeting-roles"><h4>'. esc_html__('User roles', 'ab-split-test-lite') .'</h4>';      

      echo "<p>Choose the user roles you want to test on. Usually best to just test on logged out (visitors)</p>";



      foreach ($wp_roles->roles as $role => $value) {

        echo '<label><input type="checkbox" ', ((in_array($role, $allowed_roles))? 'checked' : ''), ' name="bt_allowed_roles[]" value="', esc_attr( $role ), '">', esc_html( $value['name'] ), '</label>';

      }



      echo '<label><input type="checkbox" '. ((in_array('logout', $allowed_roles))? 'checked' : '') .' name="bt_allowed_roles[]" value="logout">Logged out users</label></div><BR><BR>';



    }

    

    

    function abst_delete_variation(){

      // if user has ability to edit bt_Experiments post type

      if (!current_user_can('edit_posts')) {

        wp_die('You do not have the correct permissions to delete this variation.');

      }

      if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'abst_delete_variation')) {

        wp_die('Security check failed');

      }

      //get data

      $eid = isset( $_POST['pid'] ) ? intval( wp_unslash( $_POST['pid'] ) ) : 0;

      $variation = isset( $_POST['variation'] ) ? sanitize_text_field( wp_unslash( $_POST['variation'] ) ) : '';

      //get observations

      $obs = maybe_unserialize(get_post_meta($eid, 'observations', true));

      if(!empty($obs)  && !empty($obs[$variation]))

      {

        unset($obs[$variation]);

        update_post_meta($eid, 'observations', $obs);

        echo "variation deleted";

        wp_die();

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

              wp_die();

            }

          }

        }

      }

      else

      {

        abst_log("Error deleting variation. Could not find variation.");

        abst_log(wp_json_encode($obs));

        echo "Error deleting variation. Could not find variation."; //print_r($obs, true);

      }

    }



    function count_active_experiments() {

      $count_posts = wp_count_posts('bt_experiments');    

      return $count_posts->publish;

    }

    

    function get_ac_data( $id )

    {

      if( class_exists('Ab_Tests_Autocomplete') )  {

        $ac_data = Ab_Tests_Autocomplete::get_data($id);  

      } else {

        $ac_data = [

          'autocomplete_on' => false,

          'min_days'  => 1,

          'min_views' => 10

        ];

      }

      

      return $ac_data;

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

    

    

    

    //shortcode report function

    function ab_split_test_report($atts){

      $atts = shortcode_atts(array(

        'emailreport' => false

      ), $atts);

      

      //check if disabled
      $shortcode_enabled = apply_filters('abst_shortcode', true);
      // Backward compatibility: also allow old hook name (new hook takes precedence)
      $shortcode_enabled = apply_filters("abst_shortcode", $shortcode_enabled);
      if(!$shortcode_enabled)

        return "AB Split Test Lite: Shortcode Disabled.";

    

      //agency only soz

      $agency = abst_user_level() == 'agency';

      if(!$agency)

        return "AB Split Test Lite: Upgrade to use reports.";

  

      return $this->abst_all_experiments_shortcode( $atts['emailreport'] );

    }



    /**

     * AJAX handler for sending test emails

     */

function abst_all_experiments_shortcode($emailreport = false){

  

  // get all active and complete experiments

  

  if(!$emailreport)

    $posts = get_posts([

      'post_type' => 'bt_experiments',

      'post_status' => 'any',

      'numberposts' => -1

    ]);

  else

    $posts = get_posts([

      'post_type' => 'bt_experiments',

      'post_status' => ['publish', 'complete'],

      'numberposts' => -1,

      ]);

  

  $out = '';

  foreach ($posts as $post){

    $out = $out . "<h1><small>Test:</small> ".$post->post_title."</h1>";

    $out = $out . $this->abst_get_experiment_results( $post );

  }

  

  return $out;

}





function abst_get_experiment_results( $post){

  ob_start(); 

  $this->abst_show_experiment_results($post, true);

  $list = ob_get_contents(); 

  ob_end_clean(); 

  return $list; 

  

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

public function identify_control_variation($variations, $test = null) {

    // Magic test: magic-0 always control

    if (isset($variations['magic-0'])) return 'magic-0';

    

    if ($test && isset($variations['test-css-' . $test->ID . '-1'])) return 'test-css-' . $test->ID . '-1';



    // Full page test: get control key from post meta

    if ($test && get_post_meta($test->ID, 'test_type', true) === 'full_page') {

        $default_page_key = get_post_meta($test->ID, 'bt_experiments_full_page_default_page', true);

        if ($default_page_key) {

            return $default_page_key; // Always return default page as control, even if no observations yet

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



public function get_experiment_stats_array( $test ){

  $pid = $test->ID;

  $observations = get_post_meta($pid,'observations',true);

  //abst_log($observations);



  if(empty($observations) || !is_array($observations))

    return null;



  $percentage_target         = apply_filters('abst_complete_confidence', 95);
  // Backward compatibility: also allow old hook name (new hook takes precedence)
  $percentage_target         = apply_filters("abst_complete_confidence", $percentage_target);

  $min_visits_for_winner     = apply_filters('abst_min_visits_for_winner', 50);
  // Backward compatibility: also allow old hook name (new hook takes precedence)
  $min_visits_for_winner     = apply_filters("abst_min_visits_for_winner", $min_visits_for_winner);

  $conversion_use_order_value= get_post_meta($test->ID,'conversion_use_order_value',true);

  $conversion_style          = get_post_meta($test->ID,'conversion_style',true);

  $test_type                 = get_post_meta($test->ID,'test_type',true);



  $test_age = intval((time() - get_post_time('U',true,$test))/60/60/24);



  // Keep a copy of raw observations before statistical analysis

  $raw_observations = $observations;

  

  //wp_send_json_success($observations);



  if($conversion_style != 'thompson'){

    require_once 'includes/statistics.php';

    if($conversion_use_order_value == '1' && is_array($observations)){

      // Use Welch's T-Test for revenue/order value data (continuous values)

      $observations = bt_bb_ab_revenue_analyzer($observations, $test_age);

      $observations = bt_bb_ab_analyze_device_sizes($observations, $test_age, true);

    } else {

      // Use Bayesian analyzer for binary conversion data

      $observations = bt_bb_ab_split_test_analyzer($observations,$test_age);

      $observations = bt_bb_ab_analyze_device_sizes($observations, $test_age, false);

    }

  }



  // Get stats block if available, or use defaults

  $has_stats = is_array($observations) && !empty($observations['bt_bb_ab_stats']);

  $stats_block    = $has_stats ? $observations['bt_bb_ab_stats'] : [];

  $likelyDuration = isset($stats_block['likelyDuration']) ? intval($stats_block['likelyDuration']) : 0;

  $winner_key     = isset($stats_block['best']) ? $stats_block['best'] : '';

  $winner_conf    = isset($stats_block['probability']) ? floatval($stats_block['probability']) : 0.0;



  $duration_so_far_days = max($test_age, 0);

  $time_remaining_days  = max($likelyDuration - $test_age, 0);



  $duration_so_far  = $duration_so_far_days . ' days';

  

  // Adjust time remaining based on test status

  $test_status = get_post_status($pid);

  if ($test_status === 'complete') {

    $time_remaining = 'Complete';

  } elseif ($test_status === 'draft') {

    $time_remaining = 'Not started';

  } elseif ($test_status === 'publish') {

    $time_remaining = $time_remaining_days . ' days';

  } else {

    // Paused or other status

    $time_remaining = 'Paused';

  }



  $variations = [];

  

  // First, get all configured variations based on test type

  $all_variation_keys = [];

  

  if ($test_type === 'full_page') {

    // For full page tests, get from page_variations meta

    $page_variations = get_post_meta($pid, 'page_variations', true);

    if (is_array($page_variations)) {

      $all_variation_keys = array_keys($page_variations);

    }

  } elseif ($test_type === 'magic') {

    // For magic tests, parse magic_definition to get variation count

    $magic_definition = get_post_meta($pid, 'magic_definition', true);

    if (!empty($magic_definition)) {

      $decoded = json_decode($magic_definition, true);

      if (is_array($decoded)) {

        // Count variations in magic definition

        $variation_count = 0;

        foreach ($decoded as $element) {

          if (isset($element['variations']) && is_array($element['variations'])) {

            $variation_count = max($variation_count, count($element['variations'])); // variations array includes original

          }

        }

        // Generate magic-0, magic-1, etc.

        for ($i = 0; $i < $variation_count; $i++) {

          $all_variation_keys[] = 'magic-' . $i;

        }

      }

    }

  }

  

  // Add variations from observations (these have data)

  // Use analyzed observations if available, otherwise use raw observations

  $obs_to_use = $has_stats ? $observations : $raw_observations;

  

  foreach($obs_to_use as $key => $data) {

    if($key === 'bt_bb_ab_stats' || !is_array($data))

      continue;



    $visits      = isset($data['visit']) ? intval($data['visit']) : 0;

    $conversions = isset($data['conversion']) ? floatval($data['conversion']) : 0.0;



    $conversion_rate = 0.0;

    if($visits > 0) {

      if($conversion_use_order_value) {

        $conversion_rate = isset($data['rate']) ? floatval($data['rate'])/100.0 : 0.0;

      } else {

        $conversion_rate = ($conversions / max($visits,1));

      }

    }



    $likelihood = isset($data['probability']) ? floatval($data['probability'])/100.0 : 0.0;



    // Derive page_id from location data (same as admin results)

    $page_id = 0;

    if (isset($data['location']['visit'][0])) {

      $visit_location = $data['location']['visit'][0];

      if (is_numeric($visit_location)) {

        $page_id = (int) $visit_location;

      }

    }



    // Flatten device_size into a REST-friendly shape so API/MCP/CLI consumers can filter.

    $device_size_payload = null;

    if (isset($data['device_size']) && is_array($data['device_size'])) {

      $device_size_payload = [];

      foreach ($data['device_size'] as $sk => $sb) {

        if (!is_array($sb)) continue;

        $sv = isset($sb['visit']) ? intval($sb['visit']) : 0;

        $sc = isset($sb['conversion']) ? floatval($sb['conversion']) : 0.0;

        $srate = 0.0;

        if ($sv > 0) {

          $srate = $conversion_use_order_value

            ? (isset($sb['rate']) ? floatval($sb['rate'])/100.0 : 0.0)

            : ($sc / max($sv, 1));

        }

        $device_size_payload[$sk] = [

          'visits'                => $sv,

          'conversions'           => $sc,

          'conversion_rate'       => $srate,

          'likelihood_of_winning' => isset($sb['probability']) ? floatval($sb['probability'])/100.0 : 0.0,

        ];

      }

    }



    $variations[$key] = [

      'label'                 => (string)$key,

      'visits'                => $visits,

      'conversions'           => $conversions,

      'conversion_rate'       => $conversion_rate,

      'likelihood_of_winning' => $likelihood,

      'page_id'               => $page_id,

      'device_size'           => $device_size_payload,

    ];

  }



  // Add configured variations that don't have data yet

  foreach ($all_variation_keys as $var_key) {

    if (!isset($variations[$var_key])) {

      $variations[$var_key] = [

        'label'                 => (string)$var_key,

        'visits'                => 0,

        'conversions'           => 0.0,

        'conversion_rate'       => 0.0,

        'likelihood_of_winning' => 0.0,

        'page_id'               => 0,

        'device_size'           => null,

      ];

    }

  }



  global $btab;

  $control_key = null;

  if( is_object($btab) && method_exists($btab, 'identify_control_variation') ){

    $control_key = $btab->identify_control_variation($variations, $test);

  }

  

  if( $control_key && isset($variations[$control_key]) ){

    $control_rate = $variations[$control_key]['conversion_rate'];

    if($control_rate > 0){

      foreach($variations as $vk => &$vd){

        $vd['uplift_vs_control'] = ($vd['conversion_rate'] - $control_rate) / $control_rate;

      }

    } else {

      foreach($variations as $vk => &$vd){

        $vd['uplift_vs_control'] = 0.0;

      }

    }

    unset($vd);

  }



  // ISO8601 start date for Agency Hub UI (parsed in JS)

  $start_date_iso = get_post_time('c', true, $pid);



  // Per-size winner map so consumers can show per-device winners without re-running analyzers

  $device_size_winners = null;

  if (is_array($observations) && isset($observations['bt_bb_ab_stats']['device_size']) && is_array($observations['bt_bb_ab_stats']['device_size'])) {

    $device_size_winners = [];

    foreach ($observations['bt_bb_ab_stats']['device_size'] as $sk => $sb) {

      // Per-size underpowered: any variation below the visit floor on this size,

      // or best probability is below the confidence target.

      $size_min_visits_ok = true;

      $size_max_visits = 0;

      if (is_array($observations)) {

        foreach ($observations as $vk => $vd) {

          if ($vk === 'bt_bb_ab_stats' || !is_array($vd)) continue;

          $sv = isset($vd['device_size'][$sk]['visit']) ? intval($vd['device_size'][$sk]['visit']) : 0;

          if ($sv < $min_visits_for_winner) $size_min_visits_ok = false;

          if ($sv > $size_max_visits) $size_max_visits = $sv;

        }

      }

      $size_prob = isset($sb['probability']) ? intval($sb['probability']) : 0;

      $size_is_underpowered = (!$size_min_visits_ok || $size_prob < $percentage_target);

      $device_size_winners[$sk] = [

        'winner_key'      => (!$size_is_underpowered && isset($sb['best']) && $sb['best'] !== false) ? $sb['best'] : null,

        'winner_conf'     => $size_prob,

        'is_underpowered' => $size_is_underpowered,

      ];

    }

  }



  // Sample-size signals for programmatic consumers (REST/MCP/CLI).

  // Matches the UI's Underpowered gate so clients can make the same decisions.

  $has_sufficient_data = true;

  foreach ($variations as $vd) {

    if (intval($vd['visits']) < $min_visits_for_winner) {

      $has_sufficient_data = false;

      break;

    }

  }

  $is_underpowered = (!$has_sufficient_data || $winner_conf < $percentage_target);



  return [

    'id'                      => $pid,

    'name'                    => get_the_title($pid),

    'status'                  => get_post_status($pid),

    'test_age_days'           => $duration_so_far_days,

    'likely_duration'         => $likelyDuration,

    'time_remaining'          => $time_remaining,

    'duration_so_far'         => $duration_so_far,

    'conversion_style'        => $conversion_style,

    'test_type'               => $test_type,

    'winner_key'              => $winner_key,

    'winner_conf'             => $winner_conf,

    'confidence_target'       => $percentage_target,

    'min_visits_for_winner'   => $min_visits_for_winner,

    'has_sufficient_data'     => $has_sufficient_data,

    'is_underpowered'         => $is_underpowered,

    'start_date'              => $start_date_iso,

    'variations'              => $variations,

    'device_size_winners'     => $device_size_winners,

  ];

}







function abst_show_experiment_results($test,$asTable = false){



  $ac_data = $this->get_ac_data($test->ID);

  $pid = $test->ID;

  

  $observations = get_post_meta($pid,'observations',true);

  

  // For magic tests, ensure all configured variations exist in observations (even with zero data)

  $test_type = get_post_meta($pid, 'test_type', true);

  if($test_type === 'magic') {

    $magic_definition = get_post_meta($pid, 'magic_definition', true);

    if(!empty($magic_definition)) {

      $magic_def = json_decode($magic_definition, true);

      if(is_array($magic_def) && isset($magic_def[0]['variations']) && is_array($magic_def[0]['variations'])) {

        // Ensure observations array exists

        if(!is_array($observations)) {

          $observations = [];

        }

        

        // Ensure all variations have observation entries (variations[0] = original, [1..N] = test)

        for($i = 0; $i < count($magic_def[0]['variations']); $i++) {

          $var_key = 'magic-' . $i;

          if(!isset($observations[$var_key])) {

            $observations[$var_key] = ['visit' => 0, 'conversion' => 0, 'rate' => 0];

          }

        }

      }

    }

  }



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

        $cleanHref = esc_url( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) . "&abstCleanInputs=1" );

        echo '<h4 class="error">Unsanitized keys found (disallowed characters are: @ # $ % ^ &amp; *). <BR/><a href="' . esc_attr( $cleanHref ) . '">Click here to remove them</a></h4>';

      }

    }

  }

  

  // Check for invalid variation keys in full page tests

  $invalid_variations_check = $this->detect_invalid_fullpage_variations($pid);

  if ($invalid_variations_check['has_invalid']) {

    $cleanupConfirm = intval($_GET['abstCleanupVariations'] ?? 0);

    if ($cleanupConfirm == 1) {

      $cleanup_result = $this->cleanup_single_fullpage_test($pid);

      if ($cleanup_result['success'] && !empty($cleanup_result['removed'])) {

        echo '<div class="notice notice-success" style="padding: 12px; margin: 10px 0; border-left: 4px solid #46b450;">';

        echo '<strong>✅ Cleanup Complete!</strong> Removed ' . count($cleanup_result['removed']) . ' invalid variation keys: <code>' . esc_html(implode(', ', $cleanup_result['removed'])) . '</code>';

        echo '<br><em>Please refresh the page to see updated results.</em>';

        echo '</div>';

      }

    } else {

      // Show warning with cleanup button

      $invalid_count = count($invalid_variations_check['invalid_keys']);

      $valid_ids = implode(', ', $invalid_variations_check['valid_variations']);

      $invalid_ids = implode(', ', $invalid_variations_check['invalid_keys']);

      $cleanupHref = esc_url( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) . "&abstCleanupVariations=1" );

      

      echo '<div class="notice notice-warning" style="padding: 12px; margin: 10px 0; border-left: 4px solid #ffb900;">';

      echo '<strong>⚠️ Invalid Variation Data Detected</strong><br>';

      echo 'Found <strong>' . esc_html( $invalid_count ) . '</strong> observation key(s) that don\'t match any configured page in this test.<br>';

      echo '<small style="color: #666;">Valid page IDs: <code>' . esc_html($valid_ids) . '</code> | Invalid keys: <code>' . esc_html($invalid_ids) . '</code></small><br><br>';

      echo '<a href="' . esc_attr( $cleanupHref ) . '" class="button button-secondary" onclick="return confirm(\'This will remove data for variation keys: ' . esc_attr($invalid_ids) . '. This cannot be undone. Continue?\');">🧹 Remove Invalid Data</a>';

      echo '</div>';

    }

  }

  

  $titles = [];

  $percentage_target = apply_filters('abst_complete_confidence', 95);
  // Backward compatibility: also allow old hook name (new hook takes precedence)
  $percentage_target = apply_filters("abst_complete_confidence", $percentage_target);

  $test_type = get_post_meta($test->ID,'test_type',true);

  $goals = get_post_meta($test->ID,'goals',true);

  $test_winner = get_post_meta($test->ID,'test_winner',true);

  $webhook_url = get_post_meta($test->ID,'webhook_url',true);

  $conversion_use_order_value = get_post_meta($test->ID,'conversion_use_order_value',true);

  $autocomplete_on = get_post_meta($test->ID,'autocomplete_on',true);

  $conversion_style = get_post_meta($test->ID,'conversion_style',true);

  if($conversion_style == 'thompson') $autocomplete_on = 0;

  $test_age = intval((time() - get_post_time('U',true,$test))/60/60/24); 

  if($conversion_style != 'thompson'){

    require_once 'includes/statistics.php';

    if($conversion_use_order_value == '1' && is_array($observations)){

      // Use Welch's T-Test for revenue/order value data (continuous values)

      $observations = bt_bb_ab_revenue_analyzer($observations, $test_age);

      $observations = bt_bb_ab_analyze_device_sizes($observations, $test_age, true);

    } else {

      // Use Bayesian analyzer for binary conversion data

      $observations = bt_bb_ab_split_test_analyzer($observations,$test_age);

      $observations = bt_bb_ab_analyze_device_sizes($observations, $test_age, false);

    }

  }



 if($conversion_use_order_value){

    $conversion_text = 'Revenue / visit';

    $conversion_t = 'Test Revenue';

  }

  else

  {

    $conversion_text = 'Conversion Rate';

    $conversion_t = 'Conversions';

  }

  $foundGoals = [];

  //check if goals is an array

  if(!empty($goals))

    foreach ($goals as $key => $value) {    //get first item in array

      if(is_array($value) && !empty($value)) {

        if(key($value) == 'page')

          $val = get_the_title(reset($value));

        elseif(is_int(key($value)))

          $val = get_the_title(key($value));

        else

          $val = reset($value);



        if(is_array($value))

          $foundGoals[$key] = ucfirst(key($value)) . " " . $val;

        }

    }

  $test_type_label = get_post_meta($test->ID,'test_type',true);

  if($test_type_label == 'full_page') $test_type_label = 'Full Page Test';

  else if($test_type_label == 'magic') $test_type_label = 'Magic Test';

  else if($test_type_label == 'ab_test') $test_type_label = 'On-Page Test';

  else if($test_type_label == 'css_test') $test_type_label = 'Code Test';

  else

    $test_type_label = 'Start your test to collect results.';

  

  // Start Status Panel

  echo "<div class='abst-results-panel abst-status-panel'>";

  

  echo "<div class='abst-results-header'>";

  echo "<h2>" . esc_html( $test_type_label ) . ": <span class='status'>";

  if($test->post_status == 'publish')

  {

    if($conversion_style == 'thompson')

    {

      echo "Monitoring & Allocating Traffic";

    }

    else  

    {

      echo "Collecting data";

    }

  }

  else if($test->post_status == 'complete')

    echo "Complete";

  else

    echo "Paused";

  echo "</span></h2>";

  echo "</div>"; // close abst-results-header



  // Output experiment status and the rest of the block-level content outside the heading

  if($conversion_style == 'thompson')

  {

    $experiment_status = "<p class='experiment-status'>Your test is running and allocating traffic to variations.</p>";

  }

  else

  {

    $experiment_status = "<p class='experiment-status'>You need more visits to your " . BT_AB_TEST_WL_ABTEST . " to find the winner. ";

  }

  $remaining = '';

  if($conversion_use_order_value && !empty($aovobs['bt_bb_ab_stats']['likelyDuration'])){

    $remaining = "</p><p>About " . ($aovobs['bt_bb_ab_stats']['likelyDuration'] - $test_age) . " days remaining.</p>";

  } elseif( !empty($observations['bt_bb_ab_stats']['likelyDuration'])){

    $remaining = "</p><p>About " . ($observations['bt_bb_ab_stats']['likelyDuration'] - $test_age) . " days remaining.</p>";

  }



  // Friendlier message for very new tests

  if ($test_age < 2) {

    $experiment_status = "<p class='experiment-status'>🧪 Your test is just getting started. Give it a day or so to collect more data and check back soon for insights!</p>";

  } else {

    if($conversion_style == 'thompson')

    {

      $experiment_status = "<p class='experiment-status'>Your test is running and will dynamically allocate traffic to variations after its collected enough inital data.<BR>Traffic is split evenly until you have recorded " . apply_filters('abst_min_conversions_for_mab', 40) . " conversions, then traffic will be weighted based on the relative uplift of each variation.</p>";

    }

    else

    {

      $experiment_status = "<p class='experiment-status'>You need more visits to your " . BT_AB_TEST_WL_ABTEST . " to find the winner. " . $remaining . "</p>";

    }

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



    if($conversion_style != 'thompson'){

      require_once 'includes/statistics.php';

      if($conversion_use_order_value == '1' && is_array($observations)){

        // Use Welch's T-Test for revenue/order value data (continuous values)

        $observations = bt_bb_ab_revenue_analyzer($observations, $test_age);

        $observations = bt_bb_ab_analyze_device_sizes($observations, $test_age, true);

      } else {

        // Use Bayesian analyzer for binary conversion data

        $observations = bt_bb_ab_split_test_analyzer($observations,$test_age);

        $observations = bt_bb_ab_analyze_device_sizes($observations, $test_age, false);

      }

    }

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

        // IF ITS MAGIC with no label

        if($test_type == 'magic' && strpos($key, 'magic-') === 0){

          $variation_number = intval(str_replace('magic-', '', $key));

          $key = 'Variation ' . chr(65 + $variation_number);

        }



        if($var['visit'] < $ac_data['min_views'])

          $notenoughvisits .= "<h4>Variation <code>$key</code> needs more views</h4>";

      }

      if($conversion_use_order_value){

        $likeylwinner = $aovobs['bt_bb_ab_stats']['best'] ?? '';

        $likelyDuration = $aovobs['bt_bb_ab_stats']['likelyDuration'] ?? '0';

        $likelywinnerpercentage = $aovobs['bt_bb_ab_stats']['probability'];

      }else{

        $likeylwinner = $observations['bt_bb_ab_stats']['best'] ?? '';

        $likelyDuration = $observations['bt_bb_ab_stats']['likelyDuration'] ?? '0';

        $likelywinnerpercentage = $observations['bt_bb_ab_stats']['probability'];

      }

      

      // Ensure likelyDuration is always a valid number, default to 0

      $likelyDuration = intval($likelyDuration) ?: 0;

      $timediff = human_time_diff( get_post_time('U',true,$test), time());

      

      // Output chart data as JavaScript variable

      echo '<script>var testAge = ' . intval( $test_age ) . '; var likelyDuration = ' . intval( $likelyDuration ) . ';</script>';

                

      if($test_age < $ac_data['min_days'] )

        $experiment_status .= "<h4> This test has run for ". $timediff . " - it requires at least ". $ac_data['min_days'] ." days.</h4>";

      if($notenoughvisits !== '')

        $experiment_status .= $notenoughvisits;

      

      if( ($likelywinnerpercentage >= $percentage_target) && ($test_age >= $ac_data['min_days']) && ($notenoughvisits == '') )

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



          $experiment_status = "<div class='bt_ab_success'>"

            . "🎉 <strong>{$winner_label}</strong> is the winner with <strong>{$likelywinnerpercentage}% confidence</strong>"

            . "</div>";

        }

        // Calculate uplift if we have winner and control data

        elseif($winner_data && $control_data) {

          $winner_rate = $conversion_use_order_value ? ($winner_data['rate']/100) : $winner_data['rate'];

          $control_rate = $conversion_use_order_value ? ($control_data['rate']/100) : $control_data['rate'];

          

          if($control_rate > 0) {

            $uplift_percent = (($winner_rate - $control_rate) / $control_rate) * 100;

            $relative_improvement = $winner_rate / $control_rate;

            

            // Calculate annual projections based on current traffic

            $daily_visits = max($winner_data['visit'], $control_data['visit']) / max($test_age, 1);

            $annual_visits = $daily_visits * 365;

            $improvement_per_visit = $winner_rate - $control_rate;

            $annual_improvement = $improvement_per_visit * $annual_visits;

            

            if($conversion_use_order_value) {

              // Revenue projection

              $currency_symbol = $this->value_currency_symbol();

              $annual_extra_revenue = $annual_improvement;

              $formatted_annual = number_format($annual_extra_revenue, 0);

              

              // Only show uplift/annual impact if winner is NOT control or if control is winner but no avoided loss impact

              // Suppress $0 uplift and $0 annual impact if control is winner and uplift is zero or negative

              if ($likeylwinner === $control_variation_key && $uplift_percent <= 0.1) {

                $uplift_message = '';

                $annual_impact_message = '';

              } else if ($likeylwinner !== $control_variation_key || (empty($annual_impact_message) && empty($avoided_loss_message))) {

                $uplift_message = sprintf(

                  '<br/>📈 <strong>%.1f%% increase</strong> in revenue per visit <em>(%.1fx better performance)</em><br/>'

                  . '💰 <strong>Projected annual impact:</strong> Extra <span style="color: #28a745; font-weight: bold; font-size: 1.1em;">%s%s per year</span>',

                  $uplift_percent,

                  $relative_improvement,

                  $currency_symbol,

                  $formatted_annual

                );

              }

              $annual_impact_message = '';

            } else {

              // Conversion projection

              $annual_extra_conversions = round($annual_improvement);

              $formatted_conversions = number_format($annual_extra_conversions);



              // Suppress $0 uplift and $0 annual impact if control is winner and uplift is zero or negative

              if ($likeylwinner === $control_variation_key && (!isset($uplift_percent) || $uplift_percent <= 0.1)) {

                $uplift_message = '';

                $annual_impact_message = '';

              } else if (isset($uplift_percent) && $uplift_percent > 0.1) {

                $uplift_message = sprintf(

                  '<br/>📈 <strong>%.1f%% increase in %s</strong> <em>(%.1fx better performance)</em>',

                  $uplift_percent,

                  $conversion_use_order_value ? 'revenue per visit' : 'conversion rate',

                  $relative_improvement

                );

              }



              $annual_impact_message = sprintf(

                '<br/>🎯 <strong>Projected annual impact:</strong> Extra <span style="color: #28a745; font-weight: bold; font-size: 1.1em;">%s conversions per year</span>',

                $formatted_conversions

              );

            }



            if ($likeylwinner === $control_variation_key && count($all_variations) > 1) {

              $best_alt = null;

              foreach ($all_variations as $k => $obs) {

                if ($k === $control_variation_key || !isset($obs['rate'])) continue;

                $alt = $conversion_use_order_value

                  ? ((float)str_replace(['%', '$', ' ', ','], '', $obs['rate'])/100)

                  : (float)str_replace(['%', '$', ' ', ','], '', $obs['rate']);

                if ($best_alt === null || $alt > $best_alt) $best_alt = $alt;

              }

              if ($best_alt !== null) {

                $loss = ($control_rate > 0) ? (($control_rate - $best_alt) / $control_rate) * 100 : 0;

                $loss = max(0, $loss); // Never negative

                if ($loss > 0.1) {

                  $context_label = $conversion_use_order_value ? 'revenue per visit' : 'conversions';

                  $icon = $conversion_use_order_value ? '🛡️' : '🛡️';

                  $avoided_loss_message = "<br/>$icon <span class='variation-loss' style='color:#0073aa;font-weight:bold;'>You avoided losing " . round($loss, 1) . "% $context_label</span>";



                  // Calculate avoided loss for annual impact

                  if ($conversion_use_order_value) {

                    $annual_avoided_loss = ($control_rate - $best_alt) * $annual_visits;

                    $currency_symbol = $this->value_currency_symbol();

                    $formatted_avoided_loss = number_format($annual_avoided_loss, 0);

                    $annual_impact_message = sprintf(

                      '<br/>💰 <strong>Projected annual impact:</strong> You avoided losing <span style="color: #28a745; font-weight: bold; font-size: 1.1em;">%s%s per year</span>',

                      $currency_symbol,

                      $formatted_avoided_loss

                    );

                  } else {

                    $annual_avoided_loss = ($control_rate - $best_alt) * $annual_visits / 100;

                    $formatted_avoided_loss = number_format(round($annual_avoided_loss));

                    $annual_impact_message = sprintf(

                      '<br/>🎯 <strong>Projected annual impact:</strong> You avoided losing <span style="color: #28a745; font-weight: bold; font-size: 1.1em;">%s conversions per year</span>',

                      $formatted_avoided_loss

                    );

                  }

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

                $context_label = $conversion_use_order_value ? 'revenue per visit' : 'conversions';

                $icon = '✔️';

                $annual_loss_str = '';

                if (isset($annual_avoided_loss) && $annual_avoided_loss > 0) {

                  if ($conversion_use_order_value) {

                    $currency_symbol = $this->value_currency_symbol();

                    $formatted_avoided_loss = number_format($annual_avoided_loss, 0);

                    $annual_loss_str = " ({$currency_symbol}{$formatted_avoided_loss} per year)";

                  } else {

                    $formatted_avoided_loss = number_format(round($annual_avoided_loss));

                    $annual_loss_str = " ({$formatted_avoided_loss} conversions per year)";

                  }

                }

                $avoided_loss_combined = "$icon You avoided losing " . round($loss, 1) . "% $context_label" . $annual_loss_str;

              }

              $experiment_status = "<div class='bt_ab_success'>"

                . "🎉 <strong>{$winner_label}</strong> is the winner with <strong>{$likelywinnerpercentage}% confidence</strong><br>"

                . "❌ No improvement from this test<br>"

                . ($avoided_loss_combined ? $avoided_loss_combined : '')

                . "</div>";

            } else {

              // Default winner summary

              $experiment_status = "<div class='bt_ab_success'>"

                . "🎉 <strong>{$winner_label}</strong> is the winner with <strong>{$likelywinnerpercentage}% confidence</strong>"

                . "$uplift_message"

                . "$annual_impact_message" // will be empty if avoided loss annual impact is set

                . "$avoided_loss_message"

                . "</div>";

            }

          }

        }

        

        //save winner test_winner if autocomplete is on

        //is this the first time its been observed?

        if(empty($test_winner) && $autocomplete_on)

        {

          wp_update_post(array('ID' => $test->ID, 'post_status' => 'complete')); // upd status

          update_post_meta($test->ID,'test_winner',$likeylwinner); // save it!

          abst_send_webhook($test->ID, $likeylwinner, $likelywinnerpercentage); // send it!

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

          $current_rate = $conversion_use_order_value ? ($data['rate']/100) : $data['rate'];

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

        if($leading_variation && strpos($leading_variation, 'magic-') !== false) {

          $leading_display = str_replace('magic-', '', $leading_variation);

          $leading_label = "Variation " . ['A (original)','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z'][$leading_display];

        } else {

          $leading_display = $leading_variation;

          if(is_int($leading_display)) {

            //get post name by id

            $leading_label = get_the_title($leading_display);

          } else {

            $leading_label = $leading_variation;

          }

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

          $control_rate = $conversion_use_order_value ? ($control_data['rate']/100) : $control_data['rate'];

          

          if($control_rate > 0) {

            $uplift_percent = (($leading_rate - $control_rate) / $control_rate) * 100;

            if($uplift_percent > 0) {

              if($conversion_use_order_value) {

                $uplift_message = sprintf(' with an uplift of %.1f%% in revenue per visit.', $uplift_percent);

              } else {

                $uplift_message = sprintf(' with an uplift of %.1f%% in conversion rate.', $uplift_percent);

              }

            }

          }

        }

        

        // Combine confidence and estimated time remaining into a single line

        $remaining_message = '';

        if($likelyDuration && $likelyDuration > $test_age) {

          $conf_target = round($percentage_target, 1);

          if($likelyDuration >= 999 && $test_age >= 1) {

            // 999 = very long time - difference too small to detect (only show after 1+ days of data)

            $remaining_message = "<br/>The variations are very close. This test may take a long time to reach {$conf_target}% confidence, or consider increasing traffic.";

          } elseif($likelyDuration < 999) {

            $days_remaining = $likelyDuration - $test_age;

            if($days_remaining > 1) {

              $remaining_message = "<br/>Time remaining: About {$days_remaining} days to reach {$conf_target}% confidence.";

            } else {

              $remaining_message = "<br/>Nearly complete! Results expected soon ({$conf_target}% confidence).";

            }

          }

        }

        

        // Calculate projected impact potential (uplift vs control)

        $projection_message = '';

        if($total_visits > 0 && $test_age > 0 && $control_data) {

          $daily_visits = $total_visits / $test_age;

          $annual_visits = $daily_visits * 365;

          $currency_symbol = $this->value_currency_symbol();



          // DEBUG: Add debug info to see what's happening

          //$debug_info = "<br/><small style='color:#666;'>DEBUG: Leading='{$leading_variation}', Control='{$control_variation_key}', LeadingRate={$leading_rate}</small>";



          // Find second-best performing variation for "amount saved" calculation

          $all_rates = [];

          foreach($all_variations_nonwinner as $key => $data) {

            $current_rate = $conversion_use_order_value ? ($data['rate']/100) : $data['rate'];

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



          if($conversion_use_order_value && $leading_rate > 0) {

            // Revenue uplift

            $control_rate = $control_data['rate'] / 100;

            $annual_leading_revenue = $leading_rate * $annual_visits;

            $annual_control_revenue = $control_rate * $annual_visits;

            $annual_uplift = $annual_leading_revenue - $annual_control_revenue;

            

            // Calculate amount saved vs second-best performer

            $annual_second_best_revenue = $second_best_rate * $annual_visits;

            $annual_saved = $annual_leading_revenue - $annual_second_best_revenue;

            

            //$debug_info .= "<br/><small style='color:#666;'>DEBUG: AnnualUplift={$annual_uplift}, ControlRate={$control_rate}, SecondBestRate={$second_best_rate}, Saved={$annual_saved}</small>";

            

            if ($annual_uplift > 0) {

              $formatted_uplift = number_format($annual_uplift, 0);

              $projection_message = "<br/>💡 <strong>Potential uplift:</strong> {$currency_symbol}{$formatted_uplift} additional revenue per year vs original.";

            } elseif ($annual_saved > 0 && $leading_variation === $control_variation_key && $second_best_rate > 0) {

              // Amount saved by not switching (when control is leading and performing better than second-best)

              $formatted_avoided_loss = number_format($annual_saved, 0);

              $projection_message = "<br/>💰 <strong>Amount saved by not switching:</strong> {$currency_symbol}{$formatted_avoided_loss} per year vs alternatives.";

            }

          } elseif($leading_rate > 0) {

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

            '<h4 class="bt_ab_warning">🧪 %s is leading%s%s%s</h4>',

            $leading_label,

            $uplift_message,

            $projection_message,

            $remaining_message

          );

        }

      }

      unset($observations['bt_bb_ab_stats']);

    }

      

    echo wp_kses_post( $experiment_status );

    

    // Share Report URL - displayed as standalone row below status

    do_action('abst_after_experiment_status', $pid);

    

    echo "</div>"; // Close Status Panel



    // IF ANY GOALS ARE SET THEN add column/div for goals with a dropdown to choose between available goals

    $goalsHtml = '';

    $goalsTableHead = '';

    $emptyGoals = true;

    if(!empty($foundGoals))

    {

      foreach($foundGoals as $key => $value) {

        if(!empty(trim($value))){

          $emptyGoals = false;

          break;

        }

      }

    }



    if(!$emptyGoals)

    {

      $goalSelect = '<div>Subgoals</div><select class="goal-select">';

      foreach($foundGoals as $key => $goal)

      {

        if(!empty(trim($goal)))

          $goalSelect .= '<option value="'.$key.'">'.$goal.'</option>';

      }

      $goalSelect .= '</select>';

      $goalsHtml = '<div class="results-goals">'.$goalSelect.'</div>';

      $goalsTableHead = '<th>Sub-goals</th>';

    }





    // Determine column header based on conversion style

    $chance_column_header = ($conversion_style == 'thompson') ? 'Weight' : 'Confidence';

    

    if($asTable)

      echo '<table style="width:100%; max-width:800px;" border="0" cellspacing="5px" cellpadding="5px"> <thead><tr>  <th style="text-align: center;">Version Title</th>  <th style="text-align: center;">', esc_html( $conversion_text ), '</th>  <th style="text-align: center;">', esc_html( $chance_column_header ), '</th> <th style="text-align: center;">Visits</th> <th style="text-align: center;">Conversions</th> </tr></thead><tbody>';

    else

      echo '<div class="results_variation title"><div class="title">Variation</div><div class="results-visits">Visits</div>', wp_kses_post( $goalsHtml ), '<div class="results-conversions">Conversions</div><div class="results-conversion-rate">', esc_html( $conversion_text ), '</div><div class="results-likely">', esc_html( $chance_column_header ), '</div></div>';

    //hide old results remove soon

    echo '<style>.results_variation { display: none; }</style>';



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

    uasort($observations, array($this,"abst_cmp_by_conversion_rate"));

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



      //if is magic if mk starts with magic-

      if(stripos($mk, 'magic-') === 0)

      {

        $mk = substr($mk, 6);

        //0= variation a 1= variation b etc

        $mk = "Variation " . ['A (original)','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z'][$mk];

      }



      if(isset($variation_meta[$okey]['label']) && !empty($variation_meta[$okey]['label'])){

        $mk = $variation_meta[$okey]['label'];

      }

      



      if(intval($mv['visit']) < $ac_data['min_views'])

      {

        $class = "na";

      }

      else if($mv['probability'] > apply_filters('abst_complete_confidence', 95) || $mv['probability'] > apply_filters("abst_complete_confidence", 95) )

        $class = "testwinner";

      else

        $class = "no";





      if($conversion_use_order_value)

      {

        $mv['rate'] = round($mv['rate']/100,2);

        $mv['conversion'] = $this->value_currency_symbol() . round($mv['conversion'],0);



        if($mv['rate'] <= 0)

          $mv['rate'] = $this->value_currency_symbol()."0";

        else

          $mv['rate'] = $this->value_currency_symbol() . round($mv['rate'],2);

      }

      else

      {

        if($mv['rate'] <= 0)

          $mv['rate'] = "0%";

        else

          $mv['rate'] = round($mv['rate'],1) . "%";

      }



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

          $control_rate = $conversion_use_order_value ? ($control_rate_clean/100) : $control_rate_clean;

          $this_rate = $conversion_use_order_value ? ($this_rate_clean/100) : $this_rate_clean;

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

              $context_label = $conversion_use_order_value ? 'revenue per visit' : 'conversions';

              $annual_avoided_loss = 0;

              if ($total_visits > 0 && $test_age > 0) {

                $daily_visits = $total_visits / $test_age;

                $annual_visits = $daily_visits * 365;

                if ($conversion_use_order_value) {

                  $annual_control_revenue = $control_rate * $annual_visits;

                  $annual_bestalt_revenue = ($best_alt / 100) * $annual_visits;

                  $annual_avoided_loss = $annual_control_revenue - $annual_bestalt_revenue;

                  if ($annual_avoided_loss > 0) {

                    $currency_symbol = $this->value_currency_symbol();

                    $avoided_loss_str = "<br><span class='avoided-loss-annual'>({$currency_symbol}" . number_format($annual_avoided_loss, 0) . " annual revenue protected)</span>";

                  } else {

                    $avoided_loss_str = '';

                  }

                } else {

                  $annual_control_conv = ($control_rate / 100) * $annual_visits;

                  $annual_bestalt_conv = ($best_alt / 100) * $annual_visits;

                  $annual_avoided_loss = $annual_control_conv - $annual_bestalt_conv;

                  if ($annual_avoided_loss > 0) {

                    $avoided_loss_str = "<br><span class='avoided-loss-annual'>(" . number_format(round($annual_avoided_loss)) . " annual conversions protected)</span>";

                  } else {

                    $avoided_loss_str = '';

                  }

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

        echo "<div class='results_variation " . esc_attr( $class ) . "'><div class='title'>" . esc_html( $mk ) . "" . wp_kses_post( $uplift_html ) . "</div>";

        echo "<div class='results-visits'>" . esc_html( $mv['visit'] ) . "<span> ▾</span></div>";

        if(!empty($mv['goals'])){

          echo "<div class='results-goals'>";

          foreach($foundGoals as $goal_key => $goal_name) {

            if(!empty(trim($goal_name))){

              $goal_value = isset($mv['goals'][$goal_key]) ? $mv['goals'][$goal_key] : 0;

              echo "<div class='results-goal' data-goal='", esc_attr( $goal_key ), "' > ", esc_html( $goal_value ), "</div>";

            }

          }

          echo "</div>";

        } else {

          // If this variation has no goals but others do, show zeros for all defined goals

          if(!$emptyGoals) {

            echo "<div class='results-goals'>";

            foreach($foundGoals as $goal_key => $goal_name) {

              if(!empty(trim($goal_name))){

                echo "<div class='results-goal' data-goal='", esc_attr( $goal_key ), "' > 0 </div>";

              }

            }

            echo "</div>";

          } else {

            //echo "<div class='results-goal'>No goals</div>";

          }

        }

        echo "<div class='results-conversions'>", esc_html( $mv['conversion'] ), "<span> ▾</span></div>";



        echo "<div class='results-conversion-rate'>", esc_html( $mv['rate'] ), "</div>";

        

        $mv['probability'] = $mv['probability'] . "%";

        

        if($conversion_use_order_value && isset($aovobs[$okey]['probability']) )

        {

          echo "<div class='results-likely'>", esc_html( $aovobs[$okey]['probability'] ), "%</div>";  

        }

        else if(isset($mv['probability']) )  

        {

          if($conversion_style == 'thompson' && isset($variation_meta[$okey]['weight'])) {

            echo "<div class='results-likely'>", esc_html( round( floatval($variation_meta[$okey]['weight'])*100,1) ), "%</div>";

          } else {

            echo "<div class='results-likely'>", esc_html( $mv['probability'] ), "</div>";

          }

        }

        echo "</div>";



        echo "<div class='seen-on'><div><p>Visits</p>";

        if(@!empty($mv['location']['visit'])){

          foreach($mv['location']['visit'] as $visit){

              if(is_numeric($visit))

                echo "<a target='_blank' href='", esc_url( get_permalink($visit) . '?abtv=' . $mk . '&abtid=' . $pid ), "'>", esc_html( get_the_title($visit) ), "</a>";

              else

                echo "<span ><small>", esc_html( $visit ), "</small></span>";

              

          }

        }

        echo "</div><div><p>Conversions</p>";

        if(@!empty($mv['location']['conversion'])){

          foreach($mv['location']['conversion'] as $visit){

              if(is_numeric($visit))

                echo "<a target='_blank' href='", esc_url( get_permalink($visit) . '?abtv=' . $mk . '&abtid=' . $pid ), "'>", esc_html( get_the_title($visit) ), "</a>";

              else

                echo "<span ><small>", esc_html( $visit ), "</small></span>";

          }  

        }

          

        echo "</div></div>";

      }

      else

      {

        // Determine what to display in the "confidence" column

        if($conversion_style == 'thompson') {

          if(isset($variation_meta[$okey]['weight'])) {

            $chance_of_winning = round(floatval($variation_meta[$okey]['weight'])*100,1);

          } else {

            $chance_of_winning = "0%";

          }

        } else {

          if(!intval($mv['visit'])) {

            $chance_of_winning = "✕";

          } else if($conversion_use_order_value && isset($aovobs[$okey]['probability'])) {

            $chance_of_winning = $aovobs[$okey]['probability'];

           } else if(isset($mv['probability'])) {

            $chance_of_winning = $mv['probability'] . "%";

          } else {

            $chance_of_winning = "0%";

          }

        }

        echo "<tr><td style='text-align: center;'>" . esc_html( $mk ) . "</td>";

        echo "<td style='text-align: center;'>" . esc_html( $mv['rate'] ) . "</td>";

        echo "<td style='text-align: center;'>" . esc_html( $chance_of_winning ) . "</td>";

        echo "<td style='text-align: center;'>" . esc_html( $mv['visit'] ) . "</td>";

        echo "<td style='text-align: center;'>" . esc_html( $mv['conversion'] ) . "</td></tr>";

        

      }

    } // end foreach meta

      



    //new table

    //echo dropdowns to filter the results table

    echo '<div class="abst-results-panel abst-result-filters-panel abst-data-panel">';

    echo '<h3>Results</h3>';

    echo '<div class="abst-result-filter-grid">';

    echo '<div class="abst-result-filter-field">';

    echo '<label for="abst-goal-select">Goal</label>';

    echo '<select id="abst-goal-select">';

    $conversion_page = get_post_meta($pid, 'conversion_page', true);

    echo '<option value="">Primary: '.esc_html($this->get_experiment_conversion_summary($pid)).'</option>';

    foreach($foundGoals as $key => $goal)

    {

      if(!empty(trim($goal)))

        echo '<option value="subgoal'.esc_attr($key).'">Subgoal '.esc_html($key).': '.esc_html($goal).'</option>';

    }

    echo '</select>';

    echo '</div>';

    echo '<div class="abst-result-filter-field abst-result-filter-field-device">';

    echo '<label for="abst-device-size-select">Device</label>';

    echo '<select id="abst-device-size-select">';

    echo '<option value="">All devices</option>';

    echo '<option value="desktop">Desktop</option>';

    echo '<option value="mobile">Mobile</option>';

    echo '<option value="tablet">Tablet</option>';

    echo '</select>';

    echo '</div>';

    echo '</div>';

    echo '</div>'; // Close Result Filters Panel

    

    // Results Table Panel

    echo '<div class="abst-results-panel abst-data-panel table-panel">';

    echo '<div id="abst-results-table"></div>';

    echo '<div id="abst-sample-size-warning" class="abst-sample-size-warning" style="display:none;"></div>';

    echo '</div>';

    //close table

    if($asTable)

      echo '</tbody></table>';

    // current date plus days remaining in dd month yyy

    // chart

    if(!empty($observations)  )// include chart of data exists

    {

      echo '<div class="abst-results-panel abst-data-panel chart-panel"><canvas id="abtestChart" width="500px" height="400px"></canvas>';

      

      if(!empty($likelyDuration) )

      {

        if($likelyDuration >= 999 && $test_age >= 1) {

          $expectedEnd = 'Variations are very close - test may take a long time';

          echo '<div id="expectedEnd">', esc_html( $expectedEnd ), '</div>';

        } elseif($likelyDuration < 999) {

          $expectedEnd = wp_date('F jS Y', strtotime('+'. $likelyDuration . ' days'));

          $expectedEnd = 'Projected end date: ' . $expectedEnd;

          echo '<div id="expectedEnd">', esc_html( $expectedEnd ), '</div>';

        }

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



      // Per-size winners (from bt_bb_ab_analyze_device_sizes) for row highlight when filtering

      if (isset($obs['bt_bb_ab_stats']['device_size']) && is_array($obs['bt_bb_ab_stats']['device_size'])) {

        $observations['device_size_winners'] = [];

        $min_visits_for_winner = apply_filters('abst_min_visits_for_winner', 50);
        // Backward compatibility: also allow old hook name (new hook takes precedence)
        $min_visits_for_winner = apply_filters("abst_min_visits_for_winner", $min_visits_for_winner);

        foreach ($obs['bt_bb_ab_stats']['device_size'] as $sk => $sb) {

          $size_min_visits_ok = true;

          foreach ($obs as $vk => $vd) {

            if ($vk === 'bt_bb_ab_stats' || !is_array($vd)) continue;

            $sv = isset($vd['device_size'][$sk]['visit']) ? intval($vd['device_size'][$sk]['visit']) : 0;

            if ($sv < $min_visits_for_winner) {

              $size_min_visits_ok = false;

              break;

            }

          }



          $size_prob = isset($sb['probability']) ? intval($sb['probability']) : 0;

          if ($size_min_visits_ok && $size_prob >= $percentage_target && isset($sb['best']) && $sb['best'] !== false) {

            $observations['device_size_winners'][$sk] = $sb['best'];

          }

        }

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

      

      // Add conversion use order value information

      if(!empty($conversion_use_order_value)) {

        $observations['conversion_use_order_value'] = $conversion_use_order_value;

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

      

      $css_test_variations = get_post_meta($pid, 'css_test_variations', true);

      if(!empty($css_test_variations)) {

        $observations['css_test_variations'] = $css_test_variations;

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

          

          // Add experiment ID and variation key for heatmap links (for all variations)

          $observations['observations'][$var_key]['variation_meta']['eid'] = $pid;

          $observations['observations'][$var_key]['variation_meta']['variation'] = $var_key;

          

          $visit = $var_data['location']['visit'][0] ?? false;

          if($visit) {

            $link_url = '';



            if(is_numeric($visit)) {

              // It's a post ID

              $link_url = get_permalink($visit);

              // Store page ID for heatmap links

              $observations['observations'][$var_key]['variation_meta']['page_id'] = $visit;

            } else {

              // It's a string (like 'homepage', 'product-page', etc.)

              $link_url = home_url('/' . $visit);

            }

            

            if($link_url) {

              $observations['observations'][$var_key]['variation_meta']['link'] = '<a target="_blank" title="View this variation in a a new tab" href="'.$link_url."?abtv=".$var_key."&abtid=".$pid.'">🔗</a>';

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

      

      // Add minimum requirements data

      $observations['min_views'] = $ac_data['min_views'];

      $observations['min_days'] = $ac_data['min_days'];

      

      // Add confidence target percentage

      $observations['confidence_target'] = $percentage_target;

      

      // Add currency symbol for revenue-based tests

      if($conversion_use_order_value) {

        $observations['currency_symbol'] = $this->value_currency_symbol();

      }

    }

    echo "</div>"; // Close Data Panel



    // Public reports are PRO only — disabled in lite version

    }

    else

    { // give em a guide for getting going

      $empty_state_heading = '🟢 Waiting for visitors...';

      $empty_state_message = 'Your test is live! Data will appear here within 15 seconds of your first visitor. Refresh this page to check for updates.';



      if($test->post_status !== 'publish') {

        $empty_state_heading = '⏸️ This test is paused';

        $empty_state_message = 'Publish the test to start collecting visitors and showing results here.';

      }



      echo "<div class='abst-results-panel abst-empty-panel'>";

      echo "<h3>" . esc_html($empty_state_heading) . "</h3>";

      echo "<p>" . esc_html($empty_state_message) . "</p>";

      echo "<h4>Still no data after a few minutes?</h4>";

      echo "<p>Your website's cache may be serving an old version of the page. Try these steps:</p>";

      echo "<ul style='margin-left: 20px;'>";

      echo "<li>Clear your caching plugin (we have detected and auto-cleared: " . esc_html( implode(', ', abst_get_detected_caches()) ) . ")</li>";

      echo "<li>Purge your CDN cache (Cloudflare, Sucuri, etc.)</li>";

      echo "<li>Check your hosting provider's cache settings</li>";

      echo "<li>Visit your test page in an incognito/private browser window</li>";

      echo "</ul>";

      echo "</div>";

    }

    

    // Output chart data as JavaScript variable

    if(!empty($observations)) {

      echo "<script>var abtestChartData = " . wp_json_encode($observations) . ";</script>";



      //if heatmaps enabled add heatmaps data

      if(abst_get_admin_setting('abst_enable_user_journeys') == '1') {

        echo "<script>window.abTestShowheatmapLinks = true;</script>";

      }



    }

  }



    

function abst_cmp_by_conversion_rate($a, $b) {

  $rateA = isset($a["rate"]) ? $a["rate"] : 0;

  $rateB = isset($b["rate"]) ? $b["rate"] : 0;

  return $rateB - $rateA;

}

    

    function bt_experiments_inner_custom_box( $post ) {





      $eid = $post->ID;

      $plugins_folder_url = plugin_dir_url( __FILE__ );

      $pixel_url = esc_url($plugins_folder_url . 'pixel.php?eid=' . esc_attr(get_the_ID())); 



          // generate pixel html

      $pixel_html = '<img src="' . esc_url($pixel_url) . '" width="1" height="1" alt=""/>';

      echo "<script>

      window.abstpid = " . intval( $eid ) . ";

      window.abembedimg = '" . esc_js( $pixel_html ) . "';

      </script>";



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

      $conversion_use_order_value = get_post_meta($post->ID,'conversion_use_order_value',true);

      $conversion_text = get_post_meta($post->ID,'conversion_text',true);

      $currency = $this->value_currency_symbol();

      $allPublicPosts = abst_get_all_posts();





      $conversion_type_options = array(
        ''           => 'Select Conversion Trigger...',
        'page'       => 'Page or Post Visit',
        'text'       => 'Text on Page',
        'selector'   => 'Element Click',
        'link'       => 'Link Click',
        'time'       => 'Time Active',
        'scroll'     => 'Scroll Depth',
        'url'        => 'URL',
        'block'      => 'Conversion Block / Module / Element Class',
        'javascript' => 'JavaScript',
      );

      $select = "<select id='bt_experiments_conversion_page' name='bt_experiments_conversion_page' class=''>";

      foreach ($conversion_type_options as $option_value => $option_label) {
        $select .= sprintf(
          "<option value='%s'>%s</option>",
          esc_attr($option_value),
          esc_html($option_label)
        );
      }






      // Form submission conversions

      $woo_opts = '';

      if (function_exists('abst_get_form_optgroups')) {

          $woo_opts .= abst_get_form_optgroups($conversion_page);

      }



      $select .= $woo_opts;



      echo '<h4>Primary Conversion</h4>';

      echo '<p><label for="bt_experiments_conversion_url">';

      esc_html_e( "Define a successful conversion. Something like a thank-you page, or a checkout complete page.", 'ab-split-test-lite' );

      echo '</label></p>';



      $select .= "</select>";



      echo wp_kses(
        $select,
        array(
          'select' => array(
            'id'    => true,
            'name'  => true,
            'class' => true,
          ),
          'option' => array(
            'value'    => true,
            'selected' => true,
          ),
          'optgroup' => array(
            'label' => true,
          ),
        )
      );



      // ------------------- on page elements mode --------------------------_______



    echo " <div class='test-conversion-tags-mode'><h4>Conversion Blocks, Classes, Modules</h4><ol><li>Edit the page you would like to create your test conversion goal.</li><li>Add the conversion classes below to your page, or use a Block/module to trigger a conversion on page load.</li></ol>";

      echo "<div class='test-class-info test-conversion-info'><input type='text' value='ab-" . esc_attr( $post->ID ) . " ab-convert'/></div></div>";

      

      // ------------------- conversion page  mode --------------------------_______

      

      echo '<label class="conversion_page_selector" for="bt_experiments_conversion_page_selector"><strong>Conversion Page</strong><BR>Choose the page that will trigger your test conversion on page load.<BR>';

      echo '<select class="conversion_page_selector"  id="bt_experiments_conversion_page_selector" name="bt_experiments_conversion_page_selector" placeholder="Choose Page">';

      $select = '';

      $foundConversion = false;

      foreach($allPublicPosts as $publicPost)

      {

        $selected = '';

        if($publicPost->ID == $conversion_page){

          $selected = 'selected';

          $foundConversion = true;

        }

        $select .="<option value='" . esc_attr($publicPost->ID) . "' " . $selected . ">" . esc_html($publicPost->post_title) . ": " . esc_html($publicPost->post_type) . ' ' . esc_html($publicPost->post_name) . "</option>";

      }

      if(!$foundConversion)

      {

        $cPage = get_post($conversion_page);

        if(!empty($cPage)){

          $select .="<option value='" . esc_attr($conversion_page) . "' selected >" . esc_html($cPage->post_title) . ": " . esc_html($cPage->post_type) . ' ' . esc_html($cPage->post_name) . "</option>";

        }

      }

      echo wp_kses_post( $select );

      echo '</select></label>';

      echo '<a href="" id="bt_experiments_conversion_page_preview" target="_blank">view page →</a>';



      // ------------------- conversion URL mode --------------------------_______



      echo '<label class="conversion_url_input" for="bt_experiments_conversion_url">Conversion URL contains. Does not require the complete URL<BR><small class="conversion_url_input">e.g. <code>thank-you</code> would convert on any URL containing it, such as <code>https://yourwebsite.com/thank-you/email</code></small></label><input class="conversion_url_input" type="text" id="bt_experiments_conversion_url" name="bt_experiments_conversion_url" placeholder="your-conversion-page?maybe=this#orthat" value="' . esc_attr( $conversion_url ) . '"  />';

     

      // ------------------- conversion click selector --------------------------_______

        // since in 1.3.2

      echo '<label class="conversion_selector_input" for="bt_experiments_conversion_selector">CSS Selector that when clicked will trigger the conversion. <br/>Add our class <code>ab-click-convert-', esc_attr( $eid ), '</code> or define your own below.</label><input class="conversion_selector_input" type="text" id="bt_experiments_conversion_selector" name="bt_experiments_conversion_selector" placeholder="#yourthing" value="' . esc_attr( $conversion_selector ) . '"  />';

    

      // ------------------- conversion link click pattern --------------------------_______

      echo '<label class="conversion_link_pattern_input" for="bt_experiments_conversion_link_pattern">Link: When a link (<code>&lt;a&gt;</code>) with an href containing this pattern is clicked, a conversion is triggered.<br/>Example: <code>thank-you</code> or <code>https://youtube.com/</code></label><input class="conversion_link_pattern_input" type="text" id="bt_experiments_conversion_link_pattern" name="bt_experiments_conversion_link_pattern" placeholder="thank-you" value="' . esc_attr( $conversion_link_pattern ) . '"  style="display:none;" />';



       // ------------------- conversion scroll depth --------------------------_______

        echo '<label class="conversion_scroll_input" for="bt_experiments_conversion_scroll">Scroll depth percentage before a conversion is triggered.</label><input class="conversion_scroll_input" type="number" id="bt_experiments_conversion_scroll" name="bt_experiments_conversion_scroll" placeholder="50" min="0" max="100" value="' . esc_attr( $conversion_scroll ) . '"  /><span class="conversion_scroll_input" > %</span>';



       // ------------------- conversion time on site --------------------------_______

        // since in 1.3.2

        echo '<label class="conversion_time_input" for="bt_experiments_conversion_time">Number of seconds user will be active on site before a conversion is triggered.</label><input class="conversion_time_input" type="number" id="bt_experiments_conversion_time" name="bt_experiments_conversion_time" placeholder="30" value="' . esc_attr( $conversion_time ) . '"  /><span class="conversion_time_input" > seconds</span>';



       // ------------------- conversion time on site --------------------------_______

        // since in 1.6.2

        echo '<label class="conversion_text_input" for="bt_experiments_conversion_text">Will convert when this text is visible on the page. Case sensitive.</label><input class="conversion_text_input" type="text" id="bt_experiments_conversion_text" name="bt_experiments_conversion_text" placeholder="" value="' . esc_attr( $conversion_text ) . '"  />';

     


      // ------------------- conversion javascript snippet --------------------------_______

        // since in 1.3.2

        //add document.body.addEventListener('ab-test-setup-complete', function() {

//abstConvert(1234); // replace with your ID

//});



      $conversionScript = '<script>

  window.abst.abConversionValue = 1; // optional, change if you are using \'use order value\'

  (window.abConvert = window.abConvert || []).push('.esc_js($eid).');

  processAbstConvert?.();

</script>';

      echo '<div id="conversion_javascript"><textarea id="conversion_javascript_area" rows="5">',esc_textarea($conversionScript),'</textarea><div id="conversion_order_value_javascript_slot"></div><BR>

      <button class="bt_js_copy button">copy</button><span class="bt_js_copied"> Copied!</span><p>Place this code anywhere, at any time when you want to trigger a conversion. You may not need the script tags depending on where you place it.</p></div>';



      // --------------------- generate conversion module -----------------------

 //     $pixel_url = esc_url(admin_url('admin-ajax.php') . "?action=abst_pixel&eid={$eid}"); 

      //wp content url wp-content/plugins/bt-bb-ab/pixel.php?eid=20895

      $plugins_folder_url = plugin_dir_url( __FILE__ );

      $pixel_url = esc_url($plugins_folder_url . 'pixel.php?eid=' . $eid); 



    

      echo '<div class="embed-code-area">';

      // generate pixel html

      $pixel_html = esc_html("<img src='{$pixel_url}' width='1' height='1' alt=''/>");

      $pixel_html_with_value = esc_html("<img src='{$pixel_url}&value=129.99' width='1' height='1' alt=''/>");

      echo "<input type='text' readonly='readonly' onclick='this.select()' value='", esc_attr($pixel_html), "'>";

      echo "<input type='text' readonly='readonly' onclick='this.select()' value='", esc_attr($pixel_html_with_value), "' style='margin-top:8px;'>";

      echo '<div id="conversion_order_value_embed_slot"></div>';

      

      echo '<p>Paste this image HTML snippet into any website that you want to trigger a conversion when the image is loaded. <small>It is an invisible 1px image, so it will not be visible on the converting website.</small></p><p><small>Add <code>&value=129.99</code> to pass a conversion amount. This is only used when <strong>Use order value</strong> is enabled for the test.</small></p></div>';





      echo '<div class="fingerprint-code-area">';



      $plugin_url = esc_url(get_admin_url().'admin-ajax.php?action=ab_fp&eid=' . intval($eid));

      $fingerprint_script_html = '<script type="text/javascript" charset="utf-8" src="' . $plugin_url . '"></script>';

      echo "<input type='text' readonly='readonly' onclick='this.select()' value='" . esc_attr($fingerprint_script_html) . "'>";

     

      echo '<p>Paste this script into any website that you want to trigger a conversion when the script is loaded. </p><p><small>Uses fingerprinting to detect the unique user. It is an invisible piece of JavaScript, it will not be visible on the converting website.</small></p></div>';

      echo '<div id="conversion_order_value_bottom_slot"></div>';



      //      echo '<textarea style=" width: 100%; height: 100px; " id="bt-embed-code" readonly="true" onclick="this.select()"></textarea>';

  //    echo '<button type="button" class="button bt-generate-code">Show Conversion Pixel</button><em class="bt-copied">Copied!</em></div>';



      // --------------------------------------------

      //todo clean up



      //if conversion page is an integer

      echo "<script " . esc_attr( ABST_CACHE_EXCLUDES ) . ">

      jQuery(document).ready(function() {  

        function abstPositionAdminOrderValueSettings() {

          var \$orderValue = jQuery('#conversion_order_value');

          var targetSelector = '#conversion_order_value_bottom_slot';



          if (\$orderValue.length && jQuery(targetSelector).length) {

            \$orderValue.appendTo(targetSelector);

          }

        }



        function abstToggleAdminOrderValueSettings() {

          var selectedType = jQuery('#bt_experiments_conversion_page').val() || '';

          abstPositionAdminOrderValueSettings();

          jQuery('#conversion_order_value').toggle(selectedType !== '');

        }



        var selectval = '". esc_js($conversion_page) ."';

        jQuery('#bt_experiments_conversion_page option[value=\'". esc_js($conversion_page) ."\']').first().attr('selected', 'selected');";

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

          

          if( selectval !== 'javascript' ) 

          {

            jQuery('#conversion_javascript').hide();            

          }

          else 

          {

            jQuery('#conversion_javascript').show();

            jQuery('#bt_experiments_conversion_page option[value=javascript]').attr('selected', true);

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

          

          abstToggleAdminOrderValueSettings();

          

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



          jQuery('#bt_experiments_conversion_page, #bt_experiments_conversion_order_value').on('change', function(){

            abstToggleAdminOrderValueSettings();

          });

       });

      

      </script>

      ";

    

    }





    function get_user_ip_hash() {

        $ipaddress = '';

        if (isset($_SERVER['HTTP_CLIENT_IP']))

            $ipaddress = sanitize_text_field( wp_unslash( $_SERVER['HTTP_CLIENT_IP'] ) );

        else if(isset($_SERVER['HTTP_X_FORWARDED_FOR']))

            $ipaddress = sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ) );

        else if(isset($_SERVER['HTTP_X_FORWARDED']))

            $ipaddress = sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FORWARDED'] ) );

        else if(isset($_SERVER['HTTP_FORWARDED_FOR']))

            $ipaddress = sanitize_text_field( wp_unslash( $_SERVER['HTTP_FORWARDED_FOR'] ) );

        else if(isset($_SERVER['HTTP_FORWARDED']))

            $ipaddress = sanitize_text_field( wp_unslash( $_SERVER['HTTP_FORWARDED'] ) );

        else if(isset($_SERVER['REMOTE_ADDR']))

            $ipaddress = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) );

        else

            $ipaddress = 'UNKNOWN';

        

        $ipaddress = hash('sha256', $ipaddress);

        return $ipaddress;

    }



    // Pro-only payment integrations (FluentCart, EDD, WooCommerce) removed in lite version.





    



    function set_server_event_cookie($eid, $variation, $type){

      if(empty($eid) || empty($variation) || empty($type))

        return;



      if(isset($_COOKIE['abst_server_events']))

      {

        $events = json_decode( wp_unslash( $_COOKIE['abst_server_events'] ), true );

      }

      else

      {

        $events = [];

      }

      $events[] = ['eid' => $eid, 'variation' => $variation, 'type' => $type];

      

      // Use fallback strategy for server events cookie (2 days expiry)

      abst_set_cookie_with_fallback('abst_server_events', wp_json_encode($events), 2);

      abst_log('set server event cookie for ' . $eid . ' with variation ' . $variation . ' and type ' . $type);

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

        $conversion_use_order_value = get_post_meta($post_id,'conversion_use_order_value',true);



        $conversionPrefix = '';

        if($conversion_use_order_value)

          $conversionPrefix = $this->value_currency_symbol();



        $out = "";

        

        if(is_array($observations))

        {

          uasort($observations, array($this,"abst_cmp_by_conversion_rate"));



          foreach($observations as $variation => $v)

          { 

            //if the key is bt_bb_ab_stats then skip

            if($variation == 'bt_bb_ab_stats')

              continue;



            // Use centralized variation label function

            $variation_meta = get_post_meta($post_id, 'variation_meta', true);

            $variation_label = abst_get_variation_label($variation, $variation_meta);

            

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

              $out .= "<div class='bt_variation_container'><span title='$variation_label' class='bt_variation'>$variation_label</span> ".$conversionPrefix.$v['conversion']."</div>";

            }

            if($column_name == 'rate')

            {

              if($conversion_use_order_value && $v['conversion'] > 0 && $v['visit'] > 0)

                $out .= "<div class='bt_variation_container'><span title='$variation_label' class='bt_variation'>$variation_label</span> ".$conversionPrefix.round($v['conversion']/$v['visit'],2)." / visit</div>";

              else

                $out .= "<div class='bt_variation_container'><span title='$variation_label' class='bt_variation'>$variation_label</span> ".$v['rate']."</div>";

            }

          }

        }

        else

        {

          echo "No data yet";

        }



        //todo clean up scripts

        echo wp_kses_post( $out );



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

      'name'                  => __( 'AB Split Test Lite', 'ab-split-test-lite' ),

      'singular_name'         => __( 'AB Split Test', 'ab-split-test-lite' ),

      'menu_name'             => __( 'AB Split Test Lite', 'ab-split-test-lite' ),

      'name_admin_bar'        => __( 'AB Split Test Lite', 'ab-split-test-lite' ),

      'archives'              => __( 'AB Split Test  Archives', 'ab-split-test-lite' ),

      'attributes'            => __( 'AB Split Test  Attributes', 'ab-split-test-lite' ),

      'parent_item_colon'     => __( 'Parent  AB Split Test Lite', 'ab-split-test-lite' ),

      'all_items'             => __( 'All AB Split Tests ', 'ab-split-test-lite' ),

      'add_new_item'          => __( 'New Split Test', 'ab-split-test-lite' ),

      'add_new'               => __( 'New Split Test ', 'ab-split-test-lite' ),

      'new_item'              => __( 'New Split Test ', 'ab-split-test-lite' ),

      'edit_item'             => __( 'Edit  plit Test ', 'ab-split-test-lite' ),

      'update_item'           => __( 'Update AB Split Test ', 'ab-split-test-lite' ),

      'view_item'             => __( 'View', 'ab-split-test-lite' ),

      'view_items'            => __( 'View Tests', 'ab-split-test-lite' ),

      'search_items'          => __( 'Search Tests' , 'ab-split-test-lite' ),

      'not_found'             => __( 'Not found', 'ab-split-test-lite' ),

      'not_found_in_trash'    => __( 'Not found in Trash', 'ab-split-test-lite' ),

      'featured_image'        => __( 'Featured Image', 'ab-split-test-lite' ),

      'set_featured_image'    => __( 'Set featured image', 'ab-split-test-lite' ),

      'remove_featured_image' => __( 'Remove featured image', 'ab-split-test-lite' ),

      'use_featured_image'    => __( 'Use as featured image', 'ab-split-test-lite' ),

      'insert_into_item'      => __( 'Insert into item', 'ab-split-test-lite' ),

      'uploaded_to_this_item' => __( 'Uploaded to this item', 'ab-split-test-lite' ),

      'items_list'            => __( 'Test list', 'ab-split-test-lite' ),

      'items_list_navigation' => __( 'Test list navigation', 'ab-split-test-lite' ),

      'filter_items_list'     => __( 'Filter Test list', 'ab-split-test-lite' ),

    );

    $args = array(

      'label'                 => __( 'AB Split Test Lite', 'ab-split-test-lite' ),

      'description'           => __( 'Page Builder AB Split Test Lite', 'ab-split-test-lite' ),

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



    register_post_status( 'idea', array(

        'label'                     => 'Idea',

        'public'                    => false,

        'post_type'                 => 'bt_experiments',

        'show_in_admin_all_list'    => true,

        'show_in_admin_status_list' => true,

        'show_in_metabox_dropdown'  => true,

        'show_in_inline_dropdown'   => true,

        'label_count'               => _n_noop( 'Idea <span class="count">(%s)</span>', 'Ideas <span class="count">(%s)</span>', 'ab-split-test-lite' ),

    ) );

      

    register_post_status( 'complete', array(

        'label'                     => 'Test Complete',

        'public'                    => true,

        'post_type'                 => 'bt_experiments', // Define one or more post types the status can be applied to.

        'show_in_admin_all_list'    => true,

        'show_in_admin_status_list' => true,

        'show_in_metabox_dropdown'  => true,

        'show_in_inline_dropdown'   => true,

        'label_count'               => _n_noop( 'Test Complete <span class="count">(%s)</span>', 'Tests Complete <span class="count">(%s)</span>', 'ab-split-test-lite' ),

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

          'label'         => BT_AB_TEST_WL_ABTEST,

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

          'title'   => 'AB Split Test Lite',        

          'fields'  => $abtest_fields

        ];

      } elseif( 'module_advanced' === $slug ) {

        $form[ 'sections' ]['ab_test_rows'] = [

          'title'   => 'AB Split Test Lite',        

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

              "heading" =>  BT_AB_TEST_WL_ABTEST .' Name',

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

      echo " bt-variation='" . esc_attr( sanitize_text_field( $options['btVar'] ) ) . "' bt-eid='" . intval( $options['btExperiment'] ) . "' ";

   }



    function include_highlighter_scripts(){

      

      if (current_user_can('upload_files') && !isset($_GET['fb-edit'])) { 

          wp_enqueue_media();

      }

      

      wp_enqueue_style('ab_test_styles', plugins_url( '/', __FILE__ ) . 'css/experiment-frontend.css', array(), BT_AB_TEST_VERSION); 

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

      wp_enqueue_script( 'select2', plugins_url( '/', __FILE__ ) . 'js/select2.js', array( 'jquery' ), BT_AB_TEST_VERSION, false ); //  select2   

      wp_enqueue_style( 'select2', plugins_url( '/', __FILE__ ) . 'css/select2.css', false, '4.1.0', 'all' );

      wp_enqueue_script( 'creator', plugins_url( '/', __FILE__ ) . 'js/creator.js', array( 'jquery' ), BT_AB_TEST_VERSION, false ); //modern screenshot

      wp_enqueue_style('creator', plugins_url( '/', __FILE__ ) . 'css/creator.css', array(), BT_AB_TEST_VERSION); //awesomplete, modern screenshot



      $highlighter_deps = array('creator');



      // Conditionally add block editor dependencies

      if (is_admin()) {

        $screen = get_current_screen();

        if ($screen && method_exists($screen, 'is_block_editor') && $screen->is_block_editor()) {

          $highlighter_deps = array_merge($highlighter_deps, array('wp-blocks', 'wp-element', 'wp-i18n', 'wp-components', 'wp-editor'));

        }

      }

      $agency = abst_user_level() == 'agency';

      wp_enqueue_script('ab_test_highlighter', plugins_url('js/highlighter.js', __FILE__), $highlighter_deps, BT_AB_TEST_VERSION, true);

      wp_localize_script('ab_test_highlighter', 'abst_magic_data', array(

        'roles' => $roles_data,

        'defaults' => $default_selected,

        'goals' => $this->get_all_goals(),

        'ajax_url' => admin_url('admin-ajax.php'),

        'is_agency' => $agency,

        'page_selector_nonce' => wp_create_nonce('abst_page_selector')

      ));

  

    //if has edit permissions

    if(current_user_can('edit_posts') && empty($_GET['elementor-preview']) && empty($_GET['brickspreview'])) // not inside elementor or bricks iframe
      wp_enqueue_script('ab_test_builder_helper',plugins_url( '/', __FILE__ ) . 'js/builderhelper.js', array('jquery'), BT_AB_TEST_VERSION, true);

    // Enqueue Shepherd for Magic Bar tour (always load when highlighter loads, since magic bar can be loaded dynamically)
    wp_enqueue_style('shepherd-css', plugins_url('css/shepherd.css', __FILE__));
    wp_enqueue_script('shepherd-js', plugins_url('js/shepherd.min.js', __FILE__), array('jquery'), BT_AB_TEST_VERSION, true);
    wp_enqueue_script('ab-magic-tour', plugins_url('js/magic-tour.js', __FILE__), array('shepherd-js', 'ab_test_highlighter'), BT_AB_TEST_VERSION, true);

    }



    function allowInOptimizePress($value, $handle) {

      $ourScriptHandles = ['bt_experiment_scripts', 'bt_conversion_scripts', 'ab_test_highlighter', 'ab_test_builder_helper'];
      if (in_array($handle, $ourScriptHandles)) {
          return true; // giv er
      }
      return $value;
    }



    function allowInComplianz($whitelisted_tags) {
      $whitelisted_tags[] = 'bt-bb-ab';
      $whitelisted_tags[] = 'bt_conversion';
      $whitelisted_tags[] = 'ABST_CONFIG';
      return $whitelisted_tags;
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
      $out .= "<option value='url'>URL</option>";
      $out .= "<option value='block'>Conversion Element Class</option>";
      $out .= "<option value='javascript'>JavaScript</option>";
      if (defined('WC_VERSION')) {
        $out .= "<optgroup label='WooCommerce'>";
        $woo_pages = $this->get_woo_pages();
        foreach($woo_pages as $name => $page) {
          if(!empty($page)) {
            $out .= "<option value=\"$page\">$name</option>";
          }
        }

        $out .= "<option value=\"woo-order-pay\">Checkout Payment Page</option>";

        $out .= "<option value=\"woo-order-received\">Checkout Order Received (Thank You Page)</option>";

        $out .= "</optgroup>";

      }

      

      // SureCart options

      if (class_exists('SURECART')) {

        $out .= "<optgroup label='SureCart'>";

        $out .= "<option value=\"surecart-order-paid\">Order Paid</option>";

        $out .= "</optgroup>";

      }



      //fluentcart

      if (function_exists('fluent_cart_scheduler_register')) {

        $out .= "<optgroup label='FluentCart'>";

        $out .= "<option value=\"fluentcart-order-paid\">Order Paid</option>";

        $out .= "</optgroup>";

      }

      

      // Easy Digital Downloads options

      if(class_exists('Easy_Digital_Downloads')) {

        $out .= "<optgroup label='Easy Digital Downloads'>";

        $out .= "<option value=\"edd-purchase\">Purchase</option>";

        $out .= "</optgroup>";

      }

      

      // WP Pizza options

      if (class_exists('WPPIZZA')) {

        $out .= "<optgroup label='WP Pizza'>";

        $out .= "<option value=\"wp-pizza-is-checkout\">Checkout Page</option>";

        $out .= "<option value=\"wp-pizza-is-order-history\">Order History Page</option>";

        $out .= "</optgroup>";

      }

      

      // Form submission conversions

      if (function_exists('abst_get_form_optgroups')) {

        $out .= abst_get_form_optgroups();

      }

      

      $out .= "</select>";

      return $out;

    }











    function isf(){

      //get licence status

      return abst_user_level() == 'free';

    }



    function include_conversion_scripts()

    {    

      // Bail on AJAX requests

      if(wp_doing_ajax())

        return;

      

      if(class_exists('FLBuilderModel') && FLBuilderModel::is_builder_active()){

        wp_enqueue_script('ab_test_builder_helper',plugins_url( '/', __FILE__ ) . 'js/builderhelper.js', array('jquery'), BT_AB_TEST_VERSION, true);

      }



      if (file_exists(plugin_dir_path(__FILE__) . 'js/bt_conversion-min.js')) {

        $bt_conversion_js = plugins_url('js/bt_conversion-min.js', __FILE__);

      } else {

        $bt_conversion_js = plugins_url('js/bt_conversion.js', __FILE__);

      }



      // Enqueue the main conversion script

      wp_enqueue_script('bt_conversion_scripts', $bt_conversion_js, [], BT_AB_TEST_VERSION, false);

      

      // Build and localize all frontend config data

      // This outputs BEFORE the script tag, guaranteeing variables are available

      $config = $this->build_frontend_config();

      wp_localize_script('bt_conversion_scripts', 'ABST_CONFIG', $config);

    }





    function enqueue_experiment_scripts() {

      global $post_type, $post;



      // Enqueue Shepherd PRODUCT TOUR

      wp_enqueue_style('shepherd-css', plugins_url('css/shepherd.css', __FILE__));

      wp_enqueue_script('shepherd-js', plugins_url('js/shepherd.min.js', __FILE__), array('jquery'), BT_AB_TEST_VERSION, true);

      wp_enqueue_script('shepherd-tour', plugins_url('js/plugin-tour.js', __FILE__), array('shepherd-js'), BT_AB_TEST_VERSION, true);

      wp_localize_script( 'shepherd-tour', 'abstAgencyHubVars', [

        'regenerateNonce' => wp_create_nonce( 'abst_agency_regenerate_key' ),

        'syncNonce'       => wp_create_nonce( 'abst_agency_sync_site' ),

        'clearHeatmapNonce' => wp_create_nonce( 'abst_clear_heatmap_data' ),

      ] );

      //dropdowns      

      wp_register_style( 'select2', plugins_url('css/select2.css', __FILE__), false, '4.1.0', 'all' );

      wp_register_script( 'select2', plugins_url('js/select2.js', __FILE__), array( 'jquery' ), '4.1.0', true );

      wp_enqueue_style( 'select2' );

      wp_enqueue_script( 'select2' );





      //if wpbakery is active and is edit/new post page

      if(class_exists('Vc_Manager') ) {

        wp_enqueue_script('ab_test_builder_helper',plugins_url( '/', __FILE__ ) . 'js/builderhelper.js', array('jquery'), BT_AB_TEST_VERSION, true);

        wp_enqueue_style('ab_test_styles', plugins_url( '/', __FILE__ ) . 'css/experiment-frontend.css', array(), BT_AB_TEST_VERSION);

      }

          

      if( $post_type !== 'bt_experiments' ) 

        return;

      



      $eid = (isset($post->ID))? $post->ID : null;

      wp_enqueue_script( 'bt_experiment_scripts', plugins_url('js/experiment.js', __FILE__), ['jquery'], BT_AB_TEST_VERSION );

      wp_enqueue_script( 'bt_table', plugins_url('js/tabulator.js', __FILE__), ['jquery'], BT_AB_TEST_VERSION );

      wp_enqueue_style( 'bt_table', plugins_url('css/tabulator.css', __FILE__), array(), BT_AB_TEST_VERSION );

      wp_localize_script( 'bt_experiment_scripts', 'bt_exturl', [

        'ajax_url'  => admin_url( 'admin-ajax.php' ),

        'action'    => 'bt_generate_embed_code',

        'nonce'     => wp_create_nonce('btexperimentexturlnonce'),

        'export_nonce' => wp_create_nonce('abst_export_data_nonce'),

        'delete_variation_nonce' => wp_create_nonce('abst_delete_variation'),

        'clear_results_nonce' => wp_create_nonce('abst_clear_results'),

        'save_label_nonce' => wp_create_nonce('abst_save_variation_label'),

        'page_selector_nonce' => wp_create_nonce('abst_page_selector'),

        'eid'       => $eid

      ]);

      wp_enqueue_script( 'bt_ab_chart', plugins_url('js/chart.js', __FILE__), ['bt_experiment_scripts'], BT_AB_TEST_VERSION );

      wp_enqueue_style( 'bt_experiment_style', plugins_url('css/experiment.css', __FILE__),array(),BT_AB_TEST_VERSION);

      wp_enqueue_script( 'bt_confetti', plugins_url('js/confetti.js', __FILE__), ['jquery'], BT_AB_TEST_VERSION );    

      

    }



    



    function get_posts_ajax_callback(){



      if(!current_user_can('edit_posts'))

      {

        wp_reset_postdata();

        wp_send_json( [] );

      }

      if (!isset($_GET['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['nonce'])), 'abst_page_selector')) {

        wp_send_json( [] );

      }



        $searchQuery = sanitize_text_field( wp_unslash( $_GET['q'] ?? '' ) );

        

        // Use post ID as key to prevent duplicates

        $results = array();

        

        // Initialize arrays for different types of results

        $taxonomy_results = array();

        $archive_results = array();

        $all_archive_taxonomies = array();

        

        // Get and process archive pages

        foreach (get_post_types(['public' => true], 'objects') as $post_type) {

            if ($post_type->has_archive || $post_type->name === 'post') {

                $archive_item = [

                    'post-type-archive-' . $post_type->name, 

                    'Archive: ' . $post_type->labels->name

                ];

                $archive_results[] = $archive_item;

                $all_archive_taxonomies[] = $archive_item;

            }

        }

        

        // Get and process taxonomies

        $taxonomy_objects = get_taxonomies(['public' => true], 'objects');

        foreach ($taxonomy_objects as $taxonomy) {

            $tax_item = [

                'taxonomy-' . $taxonomy->name,

                'Taxonomy: ' . $taxonomy->labels->name

            ];

            $taxonomy_results[] = $tax_item;

            $all_archive_taxonomies[] = $tax_item;

        }

        //get categories

        $categories = get_categories();

        foreach ($categories as $category) {

            $category_item = [

                'category-' . $category->term_id,

                'Category: ' . $category->name

            ];

            $taxonomy_results[] = $category_item;

            $all_archive_taxonomies[] = $category_item;

        }







        //if no query then return home page id and 9 most recenly edited pages/posts

        if(empty($searchQuery)) {

          $home_page_id = get_option('page_on_front');

          $post = get_post($home_page_id);

          if($post && in_array($post->post_type, abst_post_types_to_test())) {

            $results[$post->ID] = $this->format_search_result($post);

          }

          $recent_posts = get_posts([

            'numberposts' => 9,

            'orderby' => 'modified',

            'order' => 'DESC',

            'post_type' => abst_post_types_to_test()

          ]);

          foreach($recent_posts as $post) {

            $results[$post->ID] = $this->format_search_result($post);

          }



          // Add all archive/taxonomy items to results

          foreach ($all_archive_taxonomies as $item) {

              $results[] = $item;

          }



          wp_reset_postdata();

          wp_send_json( array_values($results) );

        }



        // Return empty if search is too short (less than 2 characters)

        if(strlen($searchQuery) < 2) {

          wp_reset_postdata();

          wp_send_json( [] );

        }

        

        // 1. Check if it's a numeric post ID

        if(is_numeric($searchQuery) && $searchQuery > 0) {

          $post = get_post($searchQuery);

          if($post && in_array($post->post_type, abst_post_types_to_test())) {

            $results[$post->ID] = $this->format_search_result($post);

          }

        }

        

        // 2. Check if it's a URL and try to get post ID

        if(filter_var($searchQuery, FILTER_VALIDATE_URL)) {

          $url_post_id = url_to_postid($searchQuery);

          if($url_post_id > 0) {

            $post = get_post($url_post_id);

            if($post && in_array($post->post_type, abst_post_types_to_test())) {

              $results[$post->ID] = $this->format_search_result($post);

            }

          }

        }

        

        // 3. Try exact slug match (case-insensitive)

        $slug_post = get_page_by_path(strtolower($searchQuery), 'OBJECT', abst_post_types_to_test());

        if($slug_post) {

          $results[$slug_post->ID] = $this->format_search_result($slug_post);

        }

        

        // 4. Full text search with relevance ordering

        $search_results = new WP_Query( array( 

          's'=> $searchQuery,

          'post_status' => 'publish',

          'post_type' => abst_post_types_to_test(),

          'orderby' => 'relevance', // Order by search relevance first

          'order' => 'DESC',

          'posts_per_page' => 50

        ) );

        

        if( $search_results->have_posts() ) {

          while( $search_results->have_posts() ) {

            $search_results->the_post();

            $post_id = get_the_ID();

            // Only add if not already in results (prevents duplicates)

            if(!isset($results[$post_id])) {

              $results[$post_id] = $this->format_search_result($search_results->post);

            }

          }

        }



        // Search in archive/taxonomy names and labels

        foreach ($all_archive_taxonomies as $item) {

            if (stripos($item[1], $searchQuery) !== false || 

                stripos($item[0], $searchQuery) !== false) {

                $results[] = $item; // Add as array to maintain consistent structure

            }

        }



        //remove duplicates

        $results = array_unique($results, SORT_REGULAR);

        

        wp_reset_postdata();

        

        // Convert associative array to indexed array for JSON output

        wp_send_json( array_values($results) );

      }

      

      /**

       * Format a post object into search result array

       * @param WP_Post $post

       * @return array [post_id, formatted_title]

       */

      private function format_search_result($post) {

        $title = $post->post_title;

        

        // Shorten title if too long

        if(mb_strlen($title) > 50) {

          $title = mb_substr($title, 0, 49) . '...';

        }

        

        // Add post type and slug for clarity

        $formatted_title = $title . ' [' . strtoupper($post->post_type) . '] ' . $post->post_name;

        

        return array($post->ID, $formatted_title);

      }



      function blocks_experiment_list(){

        if (!current_user_can('edit_posts'))

          wp_send_json_error(array('error' => 'You do not have the correct permissions to get test data.'));

        if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'abst_blocks_experiment_list')) {

          wp_send_json_error(array('error' => 'Security check failed'));

        }



        $search_query = isset($_POST['search']) ? sanitize_text_field( wp_unslash( $_POST['search'] ) ) : '';

        $exact_id = isset($_POST['exact_id']) ? sanitize_text_field( wp_unslash( $_POST['exact_id'] ) ) : '';

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

    if (!current_user_can('edit_posts')) { wp_die('Unauthorized'); }

    if( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'btexperimentexturlnonce' ) ) { wp_die('sorry...'); }

    

      $plugin_url = get_admin_url().'admin-ajax.php?action=abtrk&eid='.intval($_POST['eid']);



      $embed_code = '<iframe src="'.$plugin_url.'" style="position: absolute;width:0;height:0;border:0;"></iframe>';



      echo wp_kses_post( $embed_code );

      wp_die();

    }



    function header_style()

    {

      // Handle btab_reset cookie clearing via URL parameter

      $btab_reset = intval($_GET['btab_reset'] ?? 0);

      if( $btab_reset > 0 ) {

        echo '<script ' . esc_attr( ABST_CACHE_EXCLUDES ) . '>

          (function(){

            document.cookie = "btab_'. intval($btab_reset) .'=; path=/; Expires=Thu, 01 Jan 1970 00:00:01 GMT;";

            console.log("ABST: Reset cookie btab_'. intval($btab_reset) .'");

          })();

        </script>';

      }

      

      // Dynamic hide CSS is now generated in build_frontend_config() and output via wp_localize_script

      // This avoids duplicate database queries

      

      echo "<style id='absthide'>

      [bt_hidden=true] { 

        display: none !important; 

        visibility: hidden !important; 

        height: 0 !important; 

      }

/* Hide all variations by default - prevents flicker */

[bt-variation]:not(.bt-show-variation),

[data-bt-variation]:not(.bt-show-variation),

[class*='ab-var-']:not(.bt-show-variation) {

    opacity: 0 !important;

    pointer-events: none !important;

}



/* Show the first variation of each experiment - prevents CLS */

[bt-variation].bt-first-variation:not(.bt-show-variation),

[data-bt-variation].bt-first-variation:not(.bt-show-variation),

[class*='ab-var-'].bt-first-variation {

    opacity: 1 !important;

    pointer-events: auto !important;

}



/* After test setup complete, fully remove non-selected variations from DOM */

body.ab-test-setup-complete [bt-variation]:not(.bt-show-variation),

body.ab-test-setup-complete [data-bt-variation]:not(.bt-show-variation),

body.ab-test-setup-complete [class*='ab-var-']:not(.bt-show-variation) {

    display: none !important;

}



/* Show selected variation */

.bt-show-variation {

    opacity: 1 !important;

    pointer-events: auto !important;

}



      </style>";

    }



    function get_canonical_url($original_url, $post){

      $add_canonical = abst_get_admin_setting('ab_change_canonicals');

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

            if($post->post_status == 'idea')

              $newstate = '<span>⬆️ Upgrade Required</span>';

            else if($post->post_status == 'draft')

              $newstate = '<span>🔴 Not Started </span>';

            else if($post->post_status == 'publish')

              $newstate = '<span>🟢 Running</span>';

            else if($post->post_status == 'complete')

              $newstate = '<span>✅ Complete</span>';

            else if($post->post_status == 'pending')

              $newstate = '<span>⏸️ Paused</span>';



            if(empty($newstate))

              $newstate = implode(' ', $states);



            $test_type = get_post_meta( $post->ID, 'test_type', true );

            if($post->post_status == 'idea' || empty($test_type))

              $test_type = 'Pro Feature';

            else if($test_type == 'full_page')

              $test_type = 'Full Page Test';

            else if($test_type == 'magic')

              $test_type = 'Magic Test';

            else if($test_type == 'ab_test')

                $test_type = 'On Page Test';

            else

              $test_type = 'Code Test';



            $newstate = "<span class='test-type'>".$test_type."</span>" . $newstate;



            $conversion_style = get_post_meta($post->ID, 'conversion_style', true);

            if($conversion_style == 'thompson') {

              $newstate .= ' <span class="thompson">Dynamic Traffic</span>';

            }





            $states = array();

            $states[] = $newstate;



            //if its a thompson test add a new state 





          }

          return $states;

      }





    function updated_body_class( $classes ) {

      $user = wp_get_current_user();

      

      $capabilities = apply_filters('abst_bt_can_user_view_variations', [

        'edit_posts',

        'edit_pages'

      ]);
      // Backward compatibility: also allow old hook name (new hook takes precedence)
      $capabilities = apply_filters("abst_bt_can_user_view_variations", $capabilities);



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

            $css_test_variations = get_post_meta($module->settings->css_test_variations,"url_query",true);

            $target_option_device_size = get_post_meta($module->settings->ab_test, "target_option_device_size", true);

            if ($target_option_device_size === '') {

              $target_option_device_size = 'all';

            }

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

            $experiments[$module->settings->ab_test]['log_on_visible'] = get_post_meta($module->settings->ab_test, 'log_on_visible', true) === '1';

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

            bt_experiments["'.$k.'"] = '.wp_json_encode($v).';

          ';

        }

        

      }

      

      return $js;

    }



    /**

     * Safely convert PHP serialized data for JavaScript use

     * Converts serialized arrays to JSON-compatible format or returns safe string

     */

    private function maybe_unserialize_for_js($data) {

      if (empty($data)) {

        return '';

      }

      

      // Try to unserialize if it looks like serialized PHP data

      if (is_string($data) && preg_match('/^[a]:\d+:{/', $data)) {

        $unserialized = @unserialize($data);

        if ($unserialized !== false) {

          // Return unserialized array/object - wp_localize_script will handle JSON encoding

          return $unserialized;

        }

      }

      

      // If unserialization fails or doesn't look like serialized data, return as-is

      return $data;

    }



    /**

     * Build all frontend configuration data

     * Consolidates data from render_experiment_config() and conversion_targeting()

     * Returns array for wp_localize_script()

     */

    function build_frontend_config() {

      $user_level = abst_user_level();

      global $post;

      

      // Build btab_vars

      $is_preview = (is_preview() || (isset($_GET['fl_builder']) && is_user_logged_in()) ||  $this->is_elementor_preview() )  ? true : false;

      

      $post_id = abst_get_current_post_id();



      $btab_vars =  [
        'is_admin' => current_user_can('manage_options'),
        'post_id' => $post_id,
        'is_preview' => $is_preview,
        'is_agency' => false,
        'is_free' => '1',
        'tagging' => apply_filters( 'bt_ab_tagging', true ) ? '1' : '0',
        'abst_server_convert_woo' => abst_get_admin_setting( 'abst_server_convert_woo' ) ? '1' : '0',
        'abst_enable_user_journeys' => abst_get_admin_setting( 'abst_enable_user_journeys' ) ? '1' : '0',
        'abst_disable_ai' => '1',
        'magic_nonce' => current_user_can('edit_posts') ? wp_create_nonce('abst_create_new_on_page_test') : '',
        'plugins_uri' => BT_AB_TEST_PLUGIN_URI,
        'domain' => get_home_url(),
        'v' => BT_AB_TEST_VERSION,
        'wait_for_approval' => abst_get_admin_setting( 'abst_wait_for_approval' ) ? '1' : '0',
        'heatmap_pages' => abst_get_admin_setting( 'abst_heatmap_pages' ),
        'heatmap_all_pages' => abst_get_admin_setting( 'abst_heatmap_all_pages' ),
        'geo' => abst_get_admin_setting( 'abst_geo_targeting' ) ? '1' : '0',
      ];

      // Build experiments array

      $experiments = [];

      $posts = get_transient('ab_posts_frontend_cache');

      if (false === $posts) {

        $posts = get_posts([

          'post_type' => 'bt_experiments',

          'post_status' => ['draft','publish', 'complete'],

          'numberposts' => -1

        ]);

        set_transient('ab_posts_frontend_cache', $posts, 60 * MINUTE_IN_SECONDS);

        wp_reset_postdata();

      }

      

      // Build dynamic hide CSS while iterating experiments (avoids duplicate queries)

      $hide_css = '';

      $noscript_css = '';

      

      foreach($posts as $val) {

        $meta = get_post_meta($val->ID);

        $test_type = $meta['test_type'][0] ?? '';

        $full_page_default_page = $meta['bt_experiments_full_page_default_page'][0] ?? '';

        $test_winner = $meta['test_winner'][0] ?? '';

        

        $experiments[$val->ID] = [

          'name' => $val->post_title,

          'target_percentage' => $meta['target_percentage'][0] ?? '',

          'url_query' => $meta['url_query'][0] ?? '',

          'conversion_page' => ($meta['conversion_page'][0] ?? '') == 'block' ? '' : ($meta['conversion_page'][0] ?? ''),

          'conversion_url' => $meta['conversion_url'][0] ?? '',

          'conversion_link_pattern' => $meta['conversion_link_pattern'][0] ?? false,

          'conversion_time' => $meta['conversion_time'][0] ?? '',

          'conversion_scroll' => $meta['conversion_scroll'][0] ?? '',

          'conversion_style' => $meta['conversion_style'][0] ?? 'bayesian',

          'conversion_selector' => $meta['conversion_selector'][0] ?? '',

          'conversion_text' => $meta['conversion_text'][0] ?? '',

          'goals' => $this->maybe_unserialize_for_js($meta['goals'][0] ?? ''),

          'allowed_roles' => $this->maybe_unserialize_for_js($meta['bt_allowed_roles'][0] ?? ''),

          'test_type' => $test_type,

          'is_current_user_track' => $this->is_tracking_allowed( $val->ID ),

          'full_page_default_page' => $full_page_default_page,

          'page_variations' => $this->maybe_unserialize_for_js($meta['page_variations'][0] ?? ''),

          'variation_meta' => $this->maybe_unserialize_for_js($meta['variation_meta'][0] ?? ''),

          'use_order_value' => $meta['conversion_use_order_value'][0] ?? '',

          'magic_definition' => $meta['magic_definition'][0] ?? '',

          'css_test_variations' => $meta['css_test_variations'][0] ?? '',

          'test_status' => $val->post_status,

          'test_winner' => $test_winner,

          'target_option_device_size' => ($meta['target_option_device_size'][0] ?? '') ?: 'all',

          'log_on_visible' => ($meta['log_on_visible'][0] ?? '0') === '1',

        ];

        

        // Generate hide CSS for published tests, and for completed full-page tests that have a

        // winner set (autocomplete declared a winner). The JS winner-redirect fires when

        // test_winner is non-empty, so those pages need hiding regardless of status.

        $is_complete_with_winner = ($val->post_status === 'complete' && $test_type === 'full_page' && !empty($test_winner));

        if($val->post_status !== 'publish' && !$is_complete_with_winner) continue;

        

        // Hide full-page test pages before JS redirect (but allow body.abst-show-page to override)

        if($test_type == 'full_page' && $full_page_default_page) {

          if(is_numeric($full_page_default_page)) {

            $hide_css .= 'body:not(.abst-show-page).page-id-'.$full_page_default_page.',body:not(.abst-show-page).postid-'.$full_page_default_page.'{opacity:0;pointer-events:none;}';

            $noscript_css .= 'body:not(.abst-show-page).page-id-'.$full_page_default_page.',body:not(.abst-show-page).postid-'.$full_page_default_page.'{opacity:1;pointer-events:auto;}';

          } else {

            $hide_css .= 'body:not(.abst-show-page).'.sanitize_html_class($full_page_default_page).' {opacity:0;pointer-events:none;}';

            $noscript_css .= 'body:not(.abst-show-page).'.sanitize_html_class($full_page_default_page).' {opacity:1;pointer-events:auto;}';

          }

        }

        

        // Show winner variation for on-page tests via CSS (autocomplete)

        // revert = browser default for element type (block for div, inline for span, etc.)

        if($test_winner && $test_type == 'ab_test') {

          $hide_css .= '[bt-variation="'.$test_winner.'"][bt-eid="'.$val->ID.'"]{display:revert !important;}';

        }

      }

      

      // Output the dynamic hide CSS inline (runs before JS)

      if(!empty($hide_css)) {

        // Backwards compat display:revert removed Feb 2026 - was breaking themes using display:flex/grid on body

        echo '<style id="abst-dynamic-hide">' . wp_kses_post( $hide_css ) . '</style>';

        // noscript: if JS disabled, override the hide CSS so page is visible

        if(!empty($noscript_css)) {

          echo '<noscript><style>' . wp_kses_post( $noscript_css ) . '</style></noscript>';

        }

      }

      

      // Build conversion_details (from conversion_targeting function)

      $conversion_pages = get_option('bt_conversion_pages',false);

      

      // Build current_page array (from conversion_targeting function)

      $queried_object = get_queried_object();

      $target_post_id = [];

      

      if(!empty($queried_object)) {

        if (is_archive()) {

          if (is_post_type_archive()) {

            $post_type = is_string($queried_object) ? $queried_object : $queried_object->name;

            $target_post_id[] = 'post-type-archive-' . $post_type;

            $target_post_id[] = 'archive-' . $post_type; // Backwards compat for tests created before 2.4.2

          } 

          elseif (is_tax() || is_category() || is_tag()) {

            $taxonomy = $queried_object->taxonomy;

            $term_id = $queried_object->term_id;

            $target_post_id[] = 'taxonomy-' . $taxonomy . '-' . $term_id;

            $target_post_id[] = 'taxonomy-' . $taxonomy;

          }

          elseif (is_author()) {

            $target_post_id[] = 'author-' . get_queried_object_id();

          }

          elseif (is_date()) {

            $date_parts = [get_query_var('year')];

            if (get_query_var('monthnum')) {

              $date_parts[] = get_query_var('monthnum');

            }

            if (get_query_var('day')) {

              $date_parts[] = get_query_var('day');

            }

            $target_post_id[] = 'date-' . implode('-', $date_parts);

          }

        }

        elseif (isset($queried_object->ID)) {

          $target_post_id[] = $queried_object->ID;

        }

      }

      

      if (is_front_page()) {

        $target_post_id[] = get_option('page_on_front');

      }

      if (is_home()) {

        $blog_id = get_option('page_for_posts');

        if ($blog_id) {

          $target_post_id[] = $blog_id;

          $target_post_id[] = 'post-type-archive-post';

          $target_post_id[] = 'archive-post'; // Backwards compat

        } else {

          $target_post_id[] = 'homeblog';

          $target_post_id[] = 'post-type-archive-post';

          $target_post_id[] = 'archive-post'; // Backwards compat

        }

      }

      

      if ( class_exists( 'WooCommerce' ) ) {

        if(is_shop()){

          $target_post_id[] = wc_get_page_id('shop');

        }

        if(is_wc_endpoint_url( 'order-pay' )){

          $target_post_id[] = 'woo-order-pay';

        }

        if(is_wc_endpoint_url( 'order-received' ) && (abst_get_admin_setting( 'abst_server_convert_woo' ) !== '1')){

          $target_post_id[] = 'woo-order-received';

        }

      }

      

      if ( class_exists( 'WPPIZZA' ) ) {

        if(wppizza_is_checkout()){

          $target_post_id[] = 'wp-pizza-is-checkout';

        }

        if(wppizza_is_orderhistory()){

          $target_post_id[] = 'wp-pizza-is-order-history';

        }

      }

      

      if(is_404()) {

        $target_post_id[] = '404-not-found';

      }

      

      // Return consolidated config

      return [

        'ajaxurl' => admin_url('admin-ajax.php'),

        'adminurl' => admin_url(),

        'pluginurl' => plugin_dir_url(__FILE__),

        'homeurl' => home_url(),

        'btab_vars' => $btab_vars,

        'bt_experiments' => $experiments,

        'conversion_details' => $conversion_pages,

        'current_page' => $target_post_id,

      ];

    }



    //called in wp_head 

    function render_experiment_config() {







      







      //legacy remove in 2026

      return; 

      

      $user_level = abst_user_level();

      global $post;

      $agency = false;

      $free = true;

      

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

      $abst_server_convert_woo = abst_get_admin_setting( 'abst_server_convert_woo' );


      $abst_enable_user_journeys = abst_get_admin_setting( 'abst_enable_user_journeys' );

      $wait_for_approval = abst_get_admin_setting( 'abst_wait_for_approval' );

      $wait_for_approval = apply_filters( 'abst_wait_for_approval', $wait_for_approval );

      $heatmap_pages = abst_get_admin_setting( 'abst_heatmap_pages' );

      $heatmap_all_pages = abst_get_admin_setting( 'abst_heatmap_all_pages' );



      $btab_vars =  [

        'is_admin' => current_user_can('manage_options'),

        'post_id' => $post_id,

        'is_preview' => $is_preview,

        'is_agency' => $agency,

        'is_free' => true,

        'tagging' => apply_filters( 'bt_ab_tagging', true ) ? '1' : '0',

        'abst_server_convert_woo' => $abst_server_convert_woo ? '1' : '0',

        'abst_enable_user_journeys' => $abst_enable_user_journeys ? '1' : '0',

        'abst_disable_ai' => '1',
        'magic_nonce' => current_user_can('edit_posts') ? wp_create_nonce('abst_create_new_on_page_test') : '',

        'plugins_uri' => BT_AB_TEST_PLUGIN_URI,

        'domain' => get_home_url(),

        'v' => BT_AB_TEST_VERSION,

        'wait_for_approval' => $wait_for_approval ? '1' : '0',

        'heatmap_pages' => $heatmap_pages,

        'heatmap_all_pages' => $heatmap_all_pages,

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

        $meta = get_post_meta($val->ID);

        $conversion_page = $meta['conversion_page'][0];

        if($conversion_page == 'block')

          $conversion_page = '';

        $conversion_url = $meta['conversion_url'][0];

        $conversion_style = $meta['conversion_style'][0] ?? 'bayesian';

        $conversion_time = $meta['conversion_time'][0];

        $conversion_scroll = $meta['conversion_scroll'][0];

        $conversion_selector = $meta['conversion_selector'][0];

        $conversion_text = $meta['conversion_text'][0] ?? false;

        $conversion_link_pattern = $meta['conversion_link_pattern'][0] ?? false;



        $goals = $meta['goals'][0];



        $target_percentage = $meta['target_percentage'][0];

        $url_query = $meta['url_query'][0];

        $test_type = $meta['test_type'][0];

        $full_page_default_page = $meta['bt_experiments_full_page_default_page'][0];

        $test_name = $val->post_title;

        $page_variations = $meta['page_variations'][0];

        $variation_meta = $meta['variation_meta'][0];

        $use_order_value = $meta['conversion_use_order_value'][0];

        $test_status = $val->post_status;

        $test_winner = isset($meta['test_winner'][0]) ? $meta['test_winner'][0] : '';

        $magic_definition = $meta['magic_definition'][0];

        $css_test_variations = $meta['css_test_variations'][0];

        $target_option_device_size = $meta['target_option_device_size'][0];



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

        $experiments[$val->ID]['goals'] = $this->maybe_unserialize_for_js($goals);

        $experiments[$val->ID]['test_type'] = $test_type;

        $experiments[$val->ID]['is_current_user_track'] = $this->is_tracking_allowed( $val->ID );

        $experiments[$val->ID]['full_page_default_page'] = $full_page_default_page;

        $experiments[$val->ID]['page_variations'] = $this->maybe_unserialize_for_js($page_variations);

        $experiments[$val->ID]['variation_meta'] = $this->maybe_unserialize_for_js($variation_meta);

        $experiments[$val->ID]['use_order_value'] = $use_order_value;

        $experiments[$val->ID]['magic_definition'] = $magic_definition;

        $experiments[$val->ID]['css_test_variations'] = $css_test_variations;

        $experiments[$val->ID]['test_status'] = $test_status;

        $experiments[$val->ID]['test_winner'] = $test_winner;

        $experiments[$val->ID]['target_option_device_size'] = $target_option_device_size;

        $experiments[$val->ID]['log_on_visible'] = ($meta['log_on_visible'][0] ?? '0') === '1';



        if($test_type == 'full_page')

          $style .= ' .page-id-'.$full_page_default_page.',.postid-'.$full_page_default_page.'{display:none;}'; //hide the page to avoid flicker



        if($test_type == 'full_page' && !is_int($full_page_default_page))

          $style .= ' .'.$full_page_default_page.'{display:none;}'; //hide the page to avoid flicker



        if($test_type == 'magic' && is_int($full_page_default_page))

          $style .= ' .page-id-'.$full_page_default_page.',.postid-'.$full_page_default_page.'{display:none;}'; //hide the page to avoid flicker



        // if test winner and autocomplete is on and its an on page elements, then make it visible with css

        if($test_winner && $test_type == 'ab_test')

        {

          //css to select by attribute bt-variation and bt-eid

          $style .= '[bt-variation="'.$test_winner.'"][bt-eid="'.$val->ID.'"]{display:revert !important;}';



        }



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



      echo "<script " . esc_attr( ABST_CACHE_EXCLUDES ) . " id='abst_variables'>";

      echo "var bt_ajaxurl = '".esc_url(admin_url( 'admin-ajax.php' ))."';";

      echo "var bt_adminurl = '".esc_url(admin_url())."';";

      echo "var bt_pluginurl = '" . esc_js( plugin_dir_url( __FILE__ ) ) . "';";

      echo "var bt_homeurl = '" . esc_js( home_url() ) . "';";

      echo "window.btab_vars = Object.assign(window.btab_vars || {}, " . wp_json_encode($btab_vars) . ");";

      $js = "var bt_experiments = {};

      var bt_conversion_vars = [];";

      if(!empty($experiments))

      {

        foreach($experiments as $k => $v)

        {

          $js.='bt_experiments["'.$k.'"] = '.wp_json_encode($v).';';

        }

      }

      echo wp_kses_post( $js );

      echo "</script>";

      echo wp_kses_post( $style );

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

      $screen = get_current_screen();

      

      // Check if we're on any bt_experiments screen (list, edit, add new)

      // Use both screen check and URL parameter check for reliability

      $is_experiments_screen = false;

      

      if( $screen && isset($screen->post_type) && $screen->post_type == 'bt_experiments' ){

        $is_experiments_screen = true;

      }

      

      // Fallback: check URL parameters if screen check fails

      if( !$is_experiments_screen && isset($_GET['post_type']) && $_GET['post_type'] === 'bt_experiments' ){

        $is_experiments_screen = true;

      }

      

      // Enqueue admin styles for experiments screen and custom admin pages

      $abst_admin_pages = ['abst-heatmaps', 'bt_bb_ab_insights', 'abst-session-replay'];

      $is_abst_page = isset($_GET['page']) && in_array($_GET['page'], $abst_admin_pages);

      

      if( $is_experiments_screen || $is_abst_page ){

        wp_enqueue_style( 'bt_bb_ab_admin_styles', plugins_url('admin/bt-bb-ab-admin.css', __FILE__), array(), BT_AB_TEST_VERSION );    

      }


      // Enqueue modernScreenshot for heatmaps page

      if (isset($_GET['page']) && $_GET['page'] === 'abst-heatmaps') {

        wp_enqueue_script('creator', plugins_url('js/creator.js', __FILE__), array('jquery'), BT_AB_TEST_VERSION, true);

      }

    }

    





 function process_batch_data() {

    // CORS for subdomain multisite setups (only if headers not already sent)

    $allowAny = apply_filters( 'abst_allow_cors', false);

    if($allowAny && !headers_sent())

      header("Access-Control-Allow-Origin: *");



    // Rate limit by IP: max 60 batch requests per minute
    $ip = sanitize_text_field($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
    $rate_key = 'abst_rate_' . md5($ip);
    $rate_count = (int) get_transient($rate_key);
    if ($rate_count > 60) {
        wp_send_json_error('Rate limit exceeded', 429);
    }
    set_transient($rate_key, $rate_count + 1, MINUTE_IN_SECONDS);

    // Read raw JSON body

    $raw = file_get_contents('php://input');

    if (!$raw) {

        wp_send_json_error('Empty request body', 400);

    }

    // Limit payload size to 50KB
    if (strlen($raw) > 51200) {
        wp_send_json_error('Payload too large', 413);
    }

    $events = json_decode($raw, true);

    if (!is_array($events)) {

        wp_send_json_error('Invalid JSON payload', 400);

    }

    // Limit number of events per batch
    if (count($events) > 50) {
        $events = array_slice($events, 0, 50);
    }



    $processed = 0;

    $errors    = [];



    foreach ($events as $idx => $event) {

        if (!is_array($event)) {

            $errors[] = "Event {$idx} is not an object";

            continue;

        }



        $eid       = isset($event['eid']) ? (int)$event['eid'] : 0;

        $variation = sanitize_text_field($event['variation'] ?? '');

        $type      = sanitize_text_field($event['type'] ?? '');

        $location  = sanitize_text_field($event['location'] ?? '');

        $orderVal  = floatval($event['orderValue'] ?? 1);

        $uuid      = sanitize_text_field($event['uuid'] ?? null);

        $size      = sanitize_text_field($event['size'] ?? null);

        $advId     = sanitize_text_field($event['ab_advanced_id'] ?? null);



        if (!$eid || !$type) {

            $errors[] = "Event {$idx} missing eid or type";

            continue;

        }



        // Call existing per‑event handler.

        // Set $from_api = true so it knows this is programmatic.

        $this->abst_log_experiment_activity(

            $eid,

            $variation,

            $type,

            true,          // from_api

            $location,

            $orderVal,

            $uuid,

            $size,

            $advId

        );



        $processed++;

    }



    wp_send_json_success([

        'processed' => $processed,

        'errors'    => $errors,

    ]);

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

      function abst_log_experiment_activity($bt_eid = null, $bt_variation = null, $bt_type = null, $from_api = false, $bt_location = false, $abConversionValue = false,$uuid = false,$size = false, $advancedId = false){

        // Rate limit direct AJAX calls (batch path has its own limiter)
        if (!$from_api) {
            $ip = sanitize_text_field($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
            $rate_key = 'abst_ev_' . md5($ip);
            $rate_count = (int) get_transient($rate_key);
            if ($rate_count > 120) {
                wp_send_json_error('Rate limit exceeded');
            }
            set_transient($rate_key, $rate_count + 1, MINUTE_IN_SECONDS);
        }

        $error = false;

  

        //is it coming from a js navigator.beacon() call?

        if(isset($_GET['method']) && ($_GET['method'] == 'beacon'))

        {

          $beaconData = file_get_contents("php://input");

          $decodedBeacon = json_decode($beaconData, true);

          $eid = (int)($decodedBeacon['eid'] ?? 0);

          $variation = sanitize_text_field($decodedBeacon['variation'] ?? '');

          $type = sanitize_text_field($decodedBeacon['type'] ?? ''); // 'conversion'|'visit'|goal(0-9)

          $location = sanitize_text_field($decodedBeacon['location'] ?? '');

          $size = sanitize_text_field($decodedBeacon['size'] ?? null);

          $abConversionValue = floatval($decodedBeacon['orderValue'] ?? 1);

          $uuid = sanitize_text_field($decodedBeacon['uuid'] ?? null);

          $advancedId = sanitize_text_field($decodedBeacon['ab_advanced_id'] ?? null);

        }

        else

        {

          $eid                = sanitize_text_field(($bt_eid)? (int)$bt_eid : (int)wp_unslash( $_POST['eid'] ?? 0 ));

          $variation          = $bt_variation ?? sanitize_text_field( wp_unslash( $_POST['variation'] ?? '' ) );

          $type               = sanitize_text_field(($bt_type)? $bt_type : wp_unslash( $_POST['type'] ?? '' ));

          $location           = $bt_location;

          if($location === false)

            $location           = sanitize_text_field( wp_unslash( $_POST['location'] ?? '' ) );

          $size               = $size ?? sanitize_text_field( wp_unslash( $_POST['size'] ?? '' ) );

          $abConversionValue  = sanitize_text_field(($abConversionValue)? $abConversionValue : wp_unslash( $_POST['orderValue'] ?? 1 ));

          $uuid               = sanitize_text_field(($uuid)? $uuid : wp_unslash( $_POST['uuid'] ?? null ));

          $advancedId         = sanitize_text_field(($advancedId)? $advancedId : wp_unslash( $_POST['ab_advanced_id'] ?? null ));

        }

        

        // conversion-delayed is now just conversion - JS flushes every 2s so data should be there

        if ($type == 'conversion-delayed') {

          $type = 'conversion';

        }



        $allowAny = apply_filters( 'abst_allow_cors', false); // do cors if ppl are using subdomain multisite stuff

        if($allowAny && !headers_sent())

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

              

              // Validate that variation is actually part of this test

              $default_page = isset($test_meta['bt_experiments_full_page_default_page'][0]) ? (int)$test_meta['bt_experiments_full_page_default_page'][0] : 0;

              $page_variations = isset($test_meta['page_variations'][0]) ? maybe_unserialize($test_meta['page_variations'][0]) : [];

              

              // Build list of valid variation IDs (default page + all variation page IDs)

              $valid_variations = [$default_page];

              if(is_array($page_variations)) {

                $valid_variations = array_merge($valid_variations, array_map('intval', array_keys($page_variations)));

              }

              

              // Check if the submitted variation is valid

              if(!in_array($variation, $valid_variations, true)) {

                $error = 'Invalid variation ID ' . $variation . ' for full page test. Valid IDs: ' . implode(', ', $valid_variations);

                abst_log('Full page test validation failed: ' . $error);

              }

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

              'device_size' => array(

                'mobile'  => array('visit' => 0, 'conversion' => 0, 'goals' => array()),

                'tablet'  => array('visit' => 0, 'conversion' => 0, 'goals' => array()),

                'desktop' => array('visit' => 0, 'conversion' => 0, 'goals' => array()),

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

            'device_size' => array(

              'mobile'  => array('visit' => 0, 'conversion' => 0, 'goals' => array()),

              'tablet'  => array('visit' => 0, 'conversion' => 0, 'goals' => array()),

              'desktop' => array('visit' => 0, 'conversion' => 0, 'goals' => array()),

            ),

          );

        }



        // Back-fill device_size for legacy observations created before this structure existed

        if(!isset($obs[$variation]['device_size']) || !is_array($obs[$variation]['device_size']))

        {

          $obs[$variation]['device_size'] = array(

            'mobile'  => array('visit' => 0, 'conversion' => 0, 'goals' => array()),

            'tablet'  => array('visit' => 0, 'conversion' => 0, 'goals' => array()),

            'desktop' => array('visit' => 0, 'conversion' => 0, 'goals' => array()),

          );

        }

        else

        {

          foreach(array('mobile','tablet','desktop') as $__s)

          {

            if(!isset($obs[$variation]['device_size'][$__s]) || !is_array($obs[$variation]['device_size'][$__s]))

              $obs[$variation]['device_size'][$__s] = array('visit' => 0, 'conversion' => 0, 'goals' => array());

            if(!isset($obs[$variation]['device_size'][$__s]['goals']) || !is_array($obs[$variation]['device_size'][$__s]['goals']))

              $obs[$variation]['device_size'][$__s]['goals'] = array();

          }

        }



        if( isset($test_meta['conversion_use_order_value'][0]) && $test_meta['conversion_use_order_value'][0] != '1' ) // if not using order value

        {

          $abConversionValue = 1; // default to 1

        }

  

        if($type == 'conversion')

        {

          abst_log('OBS: add conversion variation ' . $variation . ' += ' . floatval($abConversionValue));

          $obs[$variation][$type] = floatval($obs[$variation][$type]) + floatval($abConversionValue);

          if($size && isset($obs[$variation]['device_size'][$size])) {

            $obs[$variation]['device_size'][$size]['conversion'] = floatval($obs[$variation]['device_size'][$size]['conversion']) + floatval($abConversionValue);

          }



          // Welford's online algorithm for running variance (used by revenue analyzer)

          if( isset($test_meta['conversion_use_order_value'][0]) && $test_meta['conversion_use_order_value'][0] == '1' ) {

            $val = floatval($abConversionValue);

            $n = isset($obs[$variation]['_rv_count']) ? $obs[$variation]['_rv_count'] + 1 : 1;

            $old_mean = isset($obs[$variation]['_rv_mean']) ? (float)$obs[$variation]['_rv_mean'] : 0.0;

            $old_m2 = isset($obs[$variation]['_rv_m2']) ? (float)$obs[$variation]['_rv_m2'] : 0.0;



            $delta = $val - $old_mean;

            $new_mean = $old_mean + $delta / $n;

            $delta2 = $val - $new_mean;

            $new_m2 = $old_m2 + $delta * $delta2;



            $obs[$variation]['_rv_count'] = $n;

            $obs[$variation]['_rv_mean'] = $new_mean;

            $obs[$variation]['_rv_m2'] = $new_m2;



            // Mirror Welford per device size so the revenue analyzer can run on slices

            if($size && isset($obs[$variation]['device_size'][$size])) {

              $ds =& $obs[$variation]['device_size'][$size];

              $ds_n = isset($ds['_rv_count']) ? $ds['_rv_count'] + 1 : 1;

              $ds_old_mean = isset($ds['_rv_mean']) ? (float)$ds['_rv_mean'] : 0.0;

              $ds_old_m2 = isset($ds['_rv_m2']) ? (float)$ds['_rv_m2'] : 0.0;

              $ds_delta = $val - $ds_old_mean;

              $ds_new_mean = $ds_old_mean + $ds_delta / $ds_n;

              $ds_delta2 = $val - $ds_new_mean;

              $ds['_rv_count'] = $ds_n;

              $ds['_rv_mean'] = $ds_new_mean;

              $ds['_rv_m2'] = $ds_old_m2 + $ds_delta * $ds_delta2;

              unset($ds);

            }

          }

        }

        else if ($type == 'visit')

        {

          abst_log('OBS: add visit variation ' . $variation);

          ++$obs[$variation][$type];

          if($size && isset($obs[$variation]['device_size'][$size])) {

            ++$obs[$variation]['device_size'][$size]['visit'];

          }

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

              echo( wp_json_encode(array('error'=>$error)) );

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

            if($size && isset($obs[$variation]['device_size'][$size])) {

              if(!isset($obs[$variation]['device_size'][$size]['goals'][$type]))

                $obs[$variation]['device_size'][$size]['goals'][$type] = 0;

              ++$obs[$variation]['device_size'][$size]['goals'][$type];

            }

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

            echo( wp_json_encode(array('error'=>$error)) );

            wp_die();

          }

        }



        //update conversion rate if we have visits (conversions can be 0 for 0% rate)

        if(isset($obs[$variation]['visit']) && isset($obs[$variation]['conversion']) &&

           $obs[$variation]['visit'] > 0 &&

           is_numeric($obs[$variation]['visit']) && is_numeric($obs[$variation]['conversion']))

        {

          $obs[$variation]['rate'] = round( ( ($obs[$variation]['conversion'] / $obs[$variation]['visit']) * 100), 2); // round it to 2 decimal places

        }



        // Mirror rate per device_size so per-size analyzers get the same shape

        if(isset($obs[$variation]['device_size']) && is_array($obs[$variation]['device_size']))

        {

          foreach($obs[$variation]['device_size'] as $__sk => &$__sb)

          {

            if(is_array($__sb) && isset($__sb['visit']) && isset($__sb['conversion']) &&

               $__sb['visit'] > 0 && is_numeric($__sb['visit']) && is_numeric($__sb['conversion']))

            {

              $__sb['rate'] = round((($__sb['conversion'] / $__sb['visit']) * 100), 2);

            }

            else if(is_array($__sb))

            {

              $__sb['rate'] = -1;

            }

          }

          unset($__sb);

        }



        if(($type == 'visit' || $type == 'conversion') && !empty($location))

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



        do_action('abst_log_experiment_activity', $eid, $variation, $type, $location); // do ya thing vibe coders

        

        update_post_meta($eid,'observations',$obs);

  

        if( $from_api ) {

          return new WP_REST_Response(['success' => true], 200);

        } else {

          echo( wp_json_encode(['success' => true]) );

          wp_die();

        }

      }

  








    /**

     * Detect invalid variation keys in full page test observations

     * Returns array of invalid keys found, or empty array if none

     * 

     * @param int $test_id The test post ID

     * @return array ['invalid_keys' => [...], 'valid_variations' => [...], 'observations' => [...]]

     */

    function detect_invalid_fullpage_variations($test_id) {

      $result = [

        'invalid_keys' => [],

        'valid_variations' => [],

        'observations' => [],

        'has_invalid' => false

      ];

      

      $test_type = get_post_meta($test_id, 'test_type', true);

      if ($test_type !== 'full_page') {

        return $result;

      }

      

      $observations = get_post_meta($test_id, 'observations', true);

      

      // Handle both serialized and JSON formats

      if (is_string($observations) && !empty($observations)) {

        $decoded = maybe_unserialize($observations);

        if (!is_array($decoded)) {

          $decoded = json_decode($observations, true);

        }

        $observations = $decoded;

      }

      

      if (empty($observations) || !is_array($observations)) {

        return $result;

      }

      

      $result['observations'] = $observations;

      

      // Get valid variation IDs

      $default_page = (int) get_post_meta($test_id, 'bt_experiments_full_page_default_page', true);

      $page_variations = get_post_meta($test_id, 'page_variations', true);

      if (is_string($page_variations) && !empty($page_variations)) {

        $decoded = maybe_unserialize($page_variations);

        if (!is_array($decoded)) {

          $decoded = json_decode($page_variations, true);

        }

        $page_variations = $decoded;

      }

      

      // Build valid variations list

      if ($default_page > 0) {

        $result['valid_variations'][] = $default_page;

      }

      if (is_array($page_variations) && !empty($page_variations)) {

        foreach (array_keys($page_variations) as $var_id) {

          $int_id = (int) $var_id;

          if ($int_id > 0) {

            $result['valid_variations'][] = $int_id;

          }

        }

      }

      

      // Skip if no valid variations found (can't determine what's invalid)

      if (empty($result['valid_variations'])) {

        return $result;

      }

      

      // Find invalid keys

      foreach ($observations as $var_key => $var_data) {

        // Skip stats key

        if ($var_key === 'bt_bb_ab_stats') {

          continue;

        }

        $int_key = (int) $var_key;

        if ($int_key <= 0 || !in_array($int_key, $result['valid_variations'], true)) {

          $result['invalid_keys'][] = $var_key;

        }

      }

      

      $result['has_invalid'] = !empty($result['invalid_keys']);

      return $result;

    }

    

    /**

     * Clean invalid variations from a single full page test

     * 

     * @param int $test_id The test post ID

     * @return array ['success' => bool, 'removed' => [...], 'message' => string]

     */

    function cleanup_single_fullpage_test($test_id) {

      $detection = $this->detect_invalid_fullpage_variations($test_id);

      

      if (!$detection['has_invalid']) {

        return [

          'success' => true,

          'removed' => [],

          'message' => 'No invalid variation keys found.'

        ];

      }

      

      // Build cleaned observations

      $cleaned_obs = [];

      foreach ($detection['observations'] as $var_key => $var_data) {

        if (!in_array($var_key, $detection['invalid_keys'], true)) {

          $cleaned_obs[$var_key] = $var_data;

        }

      }

      

      // Save cleaned observations

      update_post_meta($test_id, 'observations', $cleaned_obs);

      abst_log("Cleanup: Removed invalid variation keys [" . implode(', ', $detection['invalid_keys']) . "] from test {$test_id}");

      

      return [

        'success' => true,

        'removed' => $detection['invalid_keys'],

        'message' => 'Removed ' . count($detection['invalid_keys']) . ' invalid variation keys.'

      ];

    }






    /**

     * Handle manual sample data loading via URL parameter

     * Triggered via ?addtestdata=1 (admin only)

     */

    /**

     * Register WordPress Abilities for MCP integration

     * These abilities will be automatically exposed as MCP tools when the WordPress MCP Adapter plugin is installed

     */

    function register_abilities() {

      if (!function_exists('wp_register_ability')) {

        return;

      }



      require_once plugin_dir_path(__FILE__) . 'bt-bb-ab-validation.php';



      // Build dynamic conversion type list from plugins installed on this site

      $supported = abst_get_supported_conversion_types();

      $conversion_types = array_values(array_intersect($supported, $supported)); // start with full list



      // Filter to only types active on this site

      $active_types = ['selector', 'link', 'url', 'page', 'time', 'scroll', 'text', 'block', 'javascript'];

      if (class_exists('FluentForm\\Framework\\Foundation\\Application'))       { $active_types[] = 'form-fluentform'; }

      if (defined('WPCF7_VERSION'))                                             { $active_types[] = 'form-cf7'; }

      if (class_exists('WPForms'))                                              { $active_types[] = 'form-wpforms'; }

      if (class_exists('GFForms'))                                              { $active_types[] = 'form-gravity'; }

      if (class_exists('Ninja_Forms'))                                          { $active_types[] = 'form-ninjaforms'; }

      if (class_exists('FrmForm'))                                              { $active_types[] = 'form-formidable'; }

      if (class_exists('Forminator'))                                           { $active_types[] = 'form-forminator'; }

      if (defined('ELEMENTOR_PRO_VERSION'))                                     { $active_types[] = 'form-elementor'; }

      if (class_exists('Jet_Form_Builder'))                                     { $active_types[] = 'form-jetformbuilder'; }

      if (class_exists('SureForms'))                                            { $active_types[] = 'form-sureforms'; }

      if (defined('BRICKS_VERSION'))                                            { $active_types[] = 'form-bricks'; }

      if (class_exists('Breakdance\\Forms\\Actions\\Actions'))                  { $active_types[] = 'form-breakdance'; }

      if (class_exists('FLBuilder'))                                            { $active_types[] = 'form-beaver'; }

      if (class_exists('MailPoet\\Config\\Initializer'))                        { $active_types[] = 'form-mailpoet'; }

      $active_types = array_values(array_intersect($active_types, $supported));



      $conversion_type_description =

        'Conversion goal type. Only use values listed in the enum — they reflect integrations active on this site. ' .

        'IMPORTANT: always ask the user what their conversion goal is before creating a test. ' .

        'Common mappings: "button click" → selector; "thank-you page" → url or page; ' .

        '"form submission" → form-[plugin]; "purchase" → woo-order-received or edd-purchase if available, otherwise url/page.';



      $meta = ['show_in_rest' => true, 'mcp' => ['public' => true]];

      $permission = function() { return current_user_can('edit_posts'); };

      $permission_write = function() { return current_user_can('edit_posts'); };



      // ┄┄ Create A/B Test ┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄

      wp_register_ability('absplittest/create-test', [

        'label'       => 'Create A/B Test',

        'category'    => 'site',

        'description' => 'Create a new A/B test or lightweight test idea. IMPORTANT: always ask the user what their conversion goal is before creating a runnable test. ' .

                         'If status is "idea", only test_title and abst_idea_hypothesis are required. Ideas are lightweight placeholders that can be converted into draft tests later. ' .

                         'Supports magic (visual element), ab_test (on-page variations), css_test, and full_page (split URL) types. ' .

                         'Conversion type options are generated from integrations active on this site — do not assume any ecommerce or form plugin is installed. ' .

                         'The response includes preview_urls for magic, css_test, and full_page tests — visit each URL after creation to verify the test renders correctly. ab_test preview URLs are not available at creation time (variations are defined in page content). ' .

                         'For magic tests: before choosing selectors, browse the target page with ?abhash=1 appended to the URL (use &abhash=1 if the URL already has query params) — a table will appear at the bottom of the page listing every visible text element and its unique CSS selector; use those selector values in magic_definition.',

        'input_schema' => [

          'type' => 'object',

          'properties' => [

            'test_title'    => ['type' => 'string', 'description' => 'Name of the test'],

            'test_type'     => ['type' => 'string', 'enum' => ['magic', 'ab_test', 'css_test', 'full_page'], 'description' => 'Type of test to create'],

            'description'   => ['type' => 'string', 'description' => 'Optional test description'],

            'status'        => ['type' => 'string', 'enum' => ['idea', 'draft', 'publish', 'pending', 'complete'], 'description' => 'Initial status (default: draft)'],

            'abst_idea_hypothesis' => ['type' => 'string', 'description' => 'For status=idea: required hypothesis describing what you want to test and expected outcome.'],

            'abst_idea_page_flow' => ['type' => 'string', 'description' => 'For status=idea: optional page or flow this idea relates to.'],

            'abst_idea_observed_problem' => ['type' => 'string', 'description' => 'For status=idea: optional observed problem, evidence, or context.'],

            'abst_idea_impact' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 5, 'description' => 'For status=idea: optional impact score from 1-5.'],

            'abst_idea_reach' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 5, 'description' => 'For status=idea: optional reach score from 1-5.'],

            'abst_idea_confidence' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 5, 'description' => 'For status=idea: optional confidence score from 1-5.'],

            'abst_idea_effort' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 5, 'description' => 'For status=idea: optional effort score from 1-5.'],

            'abst_idea_next_step' => ['type' => 'string', 'description' => 'For status=idea: optional suggested next step.'],

            'magic_definition' => [

              'type' => 'array',

              'description' => 'For magic tests: array of element definitions. Each item must have selector, type, variations (plain strings), and scope. ' .

                               'scope examples: {"page_id": 42}, {"url": "pricing"}, {"page_id": "*"} or {"url": "*"} for all pages. ' .

                               'variations must be a plain array of strings — NOT objects. ' .

                               'You can test multiple elements together (e.g. headline and sub-headline) by adding more than one item to the array — each visitor sees the same variation slot across all elements, keeping them in sync. ' .

                               'Example multi-element test (headline + sub-headline): [{"type":"text","selector":"h1.hero-title","scope":{"page_id":42},"variations":["Original headline","Variation B headline","Variation C headline"]},{"type":"text","selector":"h2.hero-subtitle","scope":{"page_id":42},"variations":["Original subtitle","Variation B subtitle","Variation C subtitle"]}]',

              'items' => [

                'type' => 'object',

                'properties' => [

                  'selector'   => ['type' => 'string', 'description' => 'CSS selector for the element. To find reliable selectors, browse the page with ?abhash=1 appended to the URL — a table appears at the bottom listing each visible element\'s unique selector.'],

                  'type'       => ['type' => 'string', 'enum' => ['text', 'html', 'image'], 'description' => 'Element content type'],

                  'scope'      => ['type' => 'object', 'description' => 'Page scope: {"page_id": 42} or {"url": "path"} or wildcard {"page_id": "*"}'],

                  'variations' => ['type' => 'array', 'items' => ['type' => 'string'], 'description' => 'Array of variation strings. The first entry (index 0) must be the original/control text. Subsequent entries are test variations. e.g. ["Original headline", "Variation B", "Variation C"]'],

                ],

                'required' => ['selector', 'type', 'scope', 'variations'],

              ],

            ],

            'default_page'     => ['type' => ['integer', 'string'], 'description' => 'For full_page tests: default page ID'],

            'variations'       => ['type' => 'array', 'description' => 'For full_page tests: array of variation page IDs', 'items' => ['type' => ['integer', 'string']]],

            'variation_labels' => ['type' => 'array', 'items' => ['type' => 'string'], 'description' => 'Optional labels for full_page variations, aligned by index with variations'],

            'variation_images' => ['type' => 'array', 'items' => ['type' => 'string'], 'description' => 'Optional screenshot URLs for full_page variations, aligned by index with variations'],

            'css_variations'   => ['type' => 'integer', 'description' => 'For css_test: number of variations (default: 2)'],

            'conversion_type'  => ['type' => 'string', 'enum' => $active_types, 'description' => $conversion_type_description],

            'conversion_selector'     => ['type' => 'string', 'description' => 'CSS selector for selector/click conversion. Example: ".buy-button"'],

            'conversion_url'          => ['type' => 'string', 'description' => 'URL path for url conversion (no domain). Example: "thank-you"'],

            'conversion_page_id'      => ['type' => 'integer', 'description' => 'WordPress page ID for page conversion type'],

            'conversion_time'         => ['type' => 'integer', 'description' => 'Seconds on page for time conversion type'],

            'conversion_scroll'       => ['type' => 'integer', 'description' => 'Scroll depth percentage (0–100) for scroll conversion type'],

            'conversion_text'         => ['type' => 'string', 'description' => 'Text to detect on page for text conversion type'],

            'conversion_link_pattern' => ['type' => 'string', 'description' => 'Link pattern for link conversion type'],

            'conversion_use_order_value' => ['type' => 'boolean', 'description' => 'Use order value instead of a simple conversion count. If window.abst.abConversionValue is set on the page, it is used first; otherwise the frontend auto-detects a likely total.'],

            'target_percentage' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 100, 'description' => 'Percentage of traffic to include (default: 100)'],

            'target_device'     => ['type' => 'string', 'enum' => ['all', 'desktop', 'mobile', 'tablet', 'desktop_tablet', 'tablet_mobile'], 'description' => 'Device targeting (default: all)'],

            'allowed_roles'     => ['type' => 'array', 'items' => ['type' => 'string'], 'description' => 'User roles that should see the test. Use "logout" for logged-out visitors.'],

            'url_query'         => ['type' => 'string', 'description' => 'Optional URL query targeting rule. Example: "utm_source=google" or "NOT utm_source=internal".'],

            'webhook_url'       => ['type' => 'string', 'description' => 'Optional webhook URL to receive test completion payloads.'],

            'log_on_visible'    => ['type' => 'boolean', 'description' => 'Only count visits after the tested element becomes visible.'],

            'optimization_type' => ['type' => 'string', 'enum' => ['bayesian', 'thompson'], 'description' => 'Algorithm: bayesian (standard) or thompson (multi-armed bandit)'],

            'autocomplete_on'   => ['type' => 'boolean', 'description' => 'Enable automatic winner selection when thresholds are met.'],

            'ac_min_days'       => ['type' => 'integer', 'description' => 'Minimum test age in days before autocomplete can act (default: 7).'],

            'ac_min_views'      => ['type' => 'integer', 'description' => 'Minimum visits per variation before autocomplete can act (default: 50).'],

            'subgoals' => [

              'type' => 'array',

              'description' => 'Secondary conversion goals to track alongside the primary goal. ' .

                               'When omitted, no subgoals are created. ' .

                               'Pass an empty array [] to explicitly create the test with no subgoals. ' .

                               'Each item needs a "type" (same values as conversion_type) and a "value". ' .

                               'Examples: {"type":"scroll","value":"50"}, {"type":"url","value":"thank-you"}, {"type":"page","value":"123"}',

              'items' => [

                'type' => 'object',

                'properties' => [

                  'type'  => ['type' => 'string', 'description' => 'Goal trigger type (same options as conversion_type)'],

                  'value' => ['type' => 'string', 'description' => 'Goal value — scroll %, URL path, page ID, CSS selector, seconds, etc.'],

                ],

                'required' => ['type'],

              ],

            ],

          ],

          'required' => ['test_title'],

        ],

        'output_schema' => [

          'type' => 'object',

          'properties' => [

            'success'      => ['type' => 'boolean'],

            'test_id'      => ['type' => 'integer'],

            'test_type'    => ['type' => 'string'],

            'subgoals'     => ['type' => 'array', 'description' => 'Subgoals saved for this test'],

            'preview_urls' => ['type' => 'object', 'description' => 'Variation preview URLs (magic, css_test, full_page). Keys are variation identifiers, values are URLs. Visit each to verify the test renders correctly.'],

            'edit_url'     => ['type' => 'string'],

            'message'      => ['type' => 'string'],

          ],

        ],

        'permission_callback' => $permission_write,

        'execute_callback'    => [$this, 'ability_create_test'],

        'meta'                => $meta,

      ]);



      // ┄┄ List A/B Tests ┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄

      wp_register_ability('absplittest/list-tests', [

        'label'       => 'List A/B Tests',

        'category'    => 'site',

        'description' => 'List all A/B tests with their current status, type, and conversion configuration',

        'input_schema' => [

          'type' => 'object',

          'properties' => [

            'status'    => ['type' => 'string', 'enum' => ['any', 'idea', 'publish', 'draft', 'pending', 'complete'], 'description' => 'Filter by status (default: any)'],

            'test_type' => ['type' => 'string', 'enum' => ['magic', 'ab_test', 'css_test', 'full_page'], 'description' => 'Filter by test type'],

          ],

        ],

        'output_schema' => [

          'type' => 'object',

          'properties' => [

            'success' => ['type' => 'boolean'],

            'count'   => ['type' => 'integer'],

            'tests'   => ['type' => 'array'],

          ],

        ],

        'permission_callback' => $permission,

        'execute_callback'    => [$this, 'ability_list_tests'],

        'meta'                => $meta,

      ]);



      // ┄┄ Get Test Results ┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄

      wp_register_ability('absplittest/get-test-results', [

        'label'       => 'Get Test Results',

        'category'    => 'site',

        'description' => 'Get full results for a specific A/B test including visits, conversions, conversion rate, ' .

                         'likelihood of winning, uplift vs control, time remaining, winner confidence, ' .

                         'variation preview URLs, and a shareable public report URL',

        'input_schema' => [

          'type' => 'object',

          'properties' => [

            'test_id' => ['type' => 'integer', 'description' => 'ID of the test to retrieve results for'],

          ],

          'required' => ['test_id'],

        ],

        'output_schema' => [

          'type' => 'object',

          'properties' => [

            'success' => ['type' => 'boolean'],

            'test'    => ['type' => 'object'],

          ],

        ],

        'permission_callback' => $permission,

        'execute_callback'    => [$this, 'ability_get_test_results'],

        'meta'                => $meta,

      ]);



      // ┄┄ Update Test Status ┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄

      wp_register_ability('absplittest/get-test-details', [

        'label'       => 'Get Test Details',

        'category'    => 'site',

        'description' => 'Get the editable configuration for a specific A/B test, including targeting, optimization, conversion, and type-specific settings.',

        'input_schema' => [

          'type' => 'object',

          'properties' => [

            'test_id' => ['type' => 'integer', 'description' => 'ID of the test to retrieve configuration for'],

          ],

          'required' => ['test_id'],

        ],

        'output_schema' => [

          'type' => 'object',

          'properties' => [

            'success' => ['type' => 'boolean'],

            'test'    => ['type' => 'object'],

          ],

        ],

        'permission_callback' => $permission,

        'execute_callback'    => [$this, 'ability_get_test_details'],

        'meta'                => $meta,

      ]);



      wp_register_ability('absplittest/update-test-status', [

        'label'       => 'Update Test Status',

        'category'    => 'site',

        'description' => 'Change the status of an A/B test. Use "publish" to start/resume, "draft" to pause, "complete" to end it.',

        'input_schema' => [

          'type' => 'object',

          'properties' => [

            'test_id' => ['type' => 'integer', 'description' => 'ID of the test to update'],

            'status'  => ['type' => 'string', 'enum' => ['idea', 'publish', 'draft', 'pending', 'complete'], 'description' => 'New status'],

          ],

          'required' => ['test_id', 'status'],

        ],

        'output_schema' => [

          'type' => 'object',

          'properties' => [

            'success'  => ['type' => 'boolean'],

            'test_id'  => ['type' => 'integer'],

            'status'   => ['type' => 'string'],

            'message'  => ['type' => 'string'],

          ],

        ],

        'permission_callback' => $permission_write,

        'execute_callback'    => [$this, 'ability_update_test_status'],

        'meta'                => $meta,

      ]);



      // ┄┄ Update Test Settings ┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄

      wp_register_ability('absplittest/update-test-settings', [

        'label'       => 'Update Test Settings',

        'category'    => 'site',

        'description' => 'Update conversion goals and other settings for an existing test. ' .

                         'Use this when a test was created without proper conversion settings or needs to be reconfigured. ' .

                         'Conversion type options reflect integrations active on this site.',

        'input_schema' => [

          'type' => 'object',

          'properties' => [

            'test_id'         => ['type' => 'integer', 'description' => 'ID of the test to update'],

            'conversion_type' => ['type' => 'string', 'enum' => $active_types, 'description' => $conversion_type_description],

            'conversion_selector'        => ['type' => 'string', 'description' => 'CSS selector for selector/click conversion type'],

            'conversion_url'             => ['type' => 'string', 'description' => 'URL path for url conversion type'],

            'conversion_page_id'         => ['type' => 'integer', 'description' => 'Page ID for page conversion type'],

            'conversion_time'            => ['type' => 'integer', 'description' => 'Seconds on page for time conversion type'],

            'conversion_scroll'          => ['type' => 'integer', 'description' => 'Scroll depth percentage for scroll conversion type'],

            'conversion_text'            => ['type' => 'string', 'description' => 'Text to detect for text conversion type'],

            'conversion_link_pattern'    => ['type' => 'string', 'description' => 'Link pattern for link conversion type'],

            'conversion_use_order_value' => ['type' => 'boolean', 'description' => 'Use order value instead of a simple conversion count. If window.abst.abConversionValue is set on the page, it is used first; otherwise the frontend auto-detects a likely total.'],

            'target_percentage'          => ['type' => 'integer', 'minimum' => 1, 'maximum' => 100, 'description' => 'Traffic allocation percentage'],

            'target_device'              => ['type' => 'string', 'enum' => ['all', 'desktop', 'mobile', 'tablet', 'desktop_tablet', 'tablet_mobile'], 'description' => 'Device targeting'],

            'allowed_roles'              => ['type' => 'array', 'items' => ['type' => 'string'], 'description' => 'User roles that should see the test. Use "logout" for logged-out visitors.'],

            'url_query'                  => ['type' => 'string', 'description' => 'Optional URL query targeting rule'],

            'webhook_url'                => ['type' => 'string', 'description' => 'Optional webhook URL for test completion payloads'],

            'log_on_visible'             => ['type' => 'boolean', 'description' => 'Only count visits after the tested element becomes visible'],

            'autocomplete_on'            => ['type' => 'boolean', 'description' => 'Enable automatic winner selection when thresholds are met'],

            'ac_min_days'                => ['type' => 'integer', 'description' => 'Minimum test age in days before autocomplete can act'],

            'ac_min_views'               => ['type' => 'integer', 'description' => 'Minimum visits per variation before autocomplete can act'],

            'optimization_type'          => ['type' => 'string', 'enum' => ['bayesian', 'thompson'], 'description' => 'Algorithm: bayesian (standard) or thompson (multi-armed bandit)'],

            'magic_definition'           => ['type' => 'array', 'description' => 'Replace the full magic test definition for magic tests'],

            'css_variations'             => ['type' => 'integer', 'description' => 'Number of CSS variations for css_test'],

            'default_page'               => ['type' => ['integer', 'string'], 'description' => 'Control page ID or slug for full_page tests'],

            'variations'                 => ['type' => 'array', 'items' => ['type' => ['integer', 'string']], 'description' => 'Variation page IDs or slugs for full_page tests'],

            'variation_labels'           => ['type' => 'array', 'items' => ['type' => 'string'], 'description' => 'Labels for full_page variations, aligned by index with variations'],

            'variation_images'           => ['type' => 'array', 'items' => ['type' => 'string'], 'description' => 'Screenshot URLs for full_page variations, aligned by index with variations'],

            'subgoals' => [

              'type' => 'array',

              'description' => 'Replace all subgoals for this test. Pass an empty array [] to remove all subgoals. ' .

                               'Each item needs a "type" and "value". ' .

                               'Examples: {"type":"scroll","value":"50"}, {"type":"url","value":"thank-you"}',

              'items' => [

                'type' => 'object',

                'properties' => [

                  'type'  => ['type' => 'string'],

                  'value' => ['type' => 'string'],

                ],

                'required' => ['type'],

              ],

            ],

          ],

          'required' => ['test_id'],

        ],

        'output_schema' => [

          'type' => 'object',

          'properties' => [

            'success'          => ['type' => 'boolean'],

            'test_id'          => ['type' => 'integer'],

            'conversion_type'  => ['type' => 'string'],

            'subgoals'         => ['type' => 'array', 'description' => 'Current subgoals after update'],

            'applied_settings' => ['type' => 'object'],

            'preview_urls'     => ['type' => 'object', 'description' => 'Variation preview URLs (magic, css_test, full_page). Keys are variation identifiers, values are URLs. Visit each to verify changes look correct.'],

            'message'          => ['type' => 'string'],

          ],

        ],

        'permission_callback' => $permission_write,

        'execute_callback'    => [$this, 'ability_update_test_settings'],

        'meta'                => $meta,

      ]);

    }



    /**

     * Build variation preview URLs for a test by reading post meta directly.

     * Works immediately after creation (no observations needed).

     *

     * Variation key formats (must match the JS tracker):

     *   magic:     magic-0 (original/variations[0]), magic-1..N (test variations)

     *   css_test:  test-css-{id}-1..N

     *   full_page: each variation page's own permalink (no query params needed)

     *   ab_test:   skipped — variation keys come from shortcode attributes, unknown at creation time

     *

     * Returns an associative array: [ variation_key => url ]

     */

    private function build_preview_urls($test_id) {

      $test_type = get_post_meta($test_id, 'test_type', true);

      $preview_urls = [];



      if ($test_type === 'magic') {

        $raw = get_post_meta($test_id, 'magic_definition', true);

        if (empty($raw)) return [];

        $items = is_string($raw) ? json_decode($raw, true) : $raw;

        if (!is_array($items) || empty($items)) return [];



        $first = $items[0];

        if (empty($first['variations']) || !is_array($first['variations'])) return [];



        // Determine the page this test runs on from scope

        $scope = $first['scope'] ?? [];

        if (!empty($scope['page_id']) && $scope['page_id'] !== '*') {

          $page_ids = is_array($scope['page_id']) ? $scope['page_id'] : [$scope['page_id']];

          $page_ids = array_values(array_filter(array_map('absint', $page_ids)));

          $base = null;

          foreach ($page_ids as $page_id) {

            $candidate = get_permalink($page_id);

            if (!empty($candidate)) {

              $base = $candidate;

              break;

            }

          }

          $base = $base ? trailingslashit($base) : null;

        } elseif (!empty($scope['url']) && $scope['url'] !== '*') {

          $base = trailingslashit(home_url('/' . ltrim($scope['url'], '/')));

        } else {

          $base = home_url('/'); // wildcard scope: use homepage as preview base

        }

        if (!$base) return [];



        // Variations: magic-0 = original (variations[0]), magic-1..N = test variations

        $count = count($first['variations']);

        for ($i = 0; $i < $count; $i++) {

          $k = 'magic-' . $i;

          $preview_urls[$k] = add_query_arg(['abtv' => $k, 'abtid' => $test_id], $base);

        }



      } elseif ($test_type === 'css_test') {

        // CSS variations: test-css-{id}-1, test-css-{id}-2, ...

        // CSS applies body classes site-wide; homepage is the most useful preview base

        $count = intval(get_post_meta($test_id, 'css_test_variations', true)) ?: 2;

        $base  = home_url('/');

        for ($i = 1; $i <= $count; $i++) {

          $k = 'test-css-' . $test_id . '-' . $i;

          $preview_urls[$k] = add_query_arg(['abtv' => $k, 'abtid' => $test_id], $base);

        }



      } elseif ($test_type === 'full_page') {

        // Control page — just visit it directly (no forced-variation param needed)

        $default = get_post_meta($test_id, 'bt_experiments_full_page_default_page', true);

        if ($default) {

          $url = is_numeric($default) ? get_permalink(intval($default)) : home_url('/' . ltrim($default, '/'));

          if ($url) $preview_urls['control'] = $url;

        }



        // Variation pages — each is its own standalone page

        $page_variations = get_post_meta($test_id, 'page_variations', true);

        if (is_array($page_variations)) {

          foreach ($page_variations as $page_id => $page_url) {

            $url = is_numeric($page_id) ? get_permalink(intval($page_id)) : ($page_url ?: null);

            if ($url) $preview_urls[(string) $page_id] = $url;

          }

        }

      }

      // ab_test: variation keys come from [bt-variation="..."] shortcode attributes in page

      // content, which are set by the user in the editor — not knowable at API creation time.



      return $preview_urls;

    }



    public function clear_test_variation_weights($test_id) {

      $variation_meta = get_post_meta($test_id, 'variation_meta', true);

      if (empty($variation_meta) || !is_array($variation_meta)) {

        return;

      }



      foreach ($variation_meta as $key => $meta) {

        if (isset($variation_meta[$key]['weight'])) {

          unset($variation_meta[$key]['weight']);

        }

      }



      update_post_meta($test_id, 'variation_meta', $variation_meta);

    }



    public function get_test_details_payload($test_id) {

      require_once plugin_dir_path(__FILE__) . 'bt-bb-ab-validation.php';



      $test = get_post($test_id);

      if (!$test || $test->post_type !== 'bt_experiments') {

        return null;

      }



      $test_type = get_post_meta($test_id, 'test_type', true);

      $conversion_trigger = get_post_meta($test_id, 'conversion_page', true);

      $conversion_type = is_numeric($conversion_trigger) ? 'page' : abst_normalize_conversion_type($conversion_trigger);

      $allowed_roles = get_post_meta($test_id, 'bt_allowed_roles', true);

      if (!is_array($allowed_roles)) {

        $allowed_roles = [];

      }



      $magic_definition = get_post_meta($test_id, 'magic_definition', true);

      $magic_definition = $magic_definition ? json_decode($magic_definition, true) : null;



      $page_variations = get_post_meta($test_id, 'page_variations', true);

      if (!is_array($page_variations)) {

        $page_variations = [];

      }



      $variation_meta = get_post_meta($test_id, 'variation_meta', true);

      if (!is_array($variation_meta)) {

        $variation_meta = [];

      }



      $full_page_variations = [];

      foreach ($page_variations as $variation_key => $variation_url) {

        $meta = $variation_meta[$variation_key] ?? [];

        $full_page_variations[] = [

          'id' => is_numeric($variation_key) ? intval($variation_key) : (string) $variation_key,

          'url' => $variation_url,

          'label' => isset($meta['label']) ? (string) $meta['label'] : '',

          'image' => isset($meta['image']) ? (string) $meta['image'] : '',

          'weight' => isset($meta['weight']) ? $meta['weight'] : null,

        ];

      }



      $details = [

        'id' => $test->ID,

        'title' => $test->post_title,

        'description' => $test->post_content,

        'status' => $test->post_status,

        'test_type' => $test_type,

        'created' => $test->post_date,

        'modified' => $test->post_modified,

        'conversion' => [

          'type' => $conversion_type,

          'selector' => get_post_meta($test_id, 'conversion_selector', true),

          'url' => get_post_meta($test_id, 'conversion_url', true),

          'page_id' => is_numeric($conversion_trigger) ? intval($conversion_trigger) : 0,

          'time' => intval(get_post_meta($test_id, 'conversion_time', true)),

          'scroll' => intval(get_post_meta($test_id, 'conversion_scroll', true)),

          'text' => get_post_meta($test_id, 'conversion_text', true),

          'link_pattern' => get_post_meta($test_id, 'conversion_link_pattern', true),

          'use_order_value' => get_post_meta($test_id, 'conversion_use_order_value', true) == '1',

        ],

        'targeting' => [

          'percentage' => intval(get_post_meta($test_id, 'target_percentage', true) ?: 100),

          'device' => get_post_meta($test_id, 'target_option_device_size', true) ?: 'all',

          'allowed_roles' => array_values($allowed_roles),

          'url_query' => get_post_meta($test_id, 'url_query', true),

          'log_on_visible' => get_post_meta($test_id, 'log_on_visible', true) === '1',

        ],

        'optimization' => [

          'type' => get_post_meta($test_id, 'conversion_style', true) ?: 'bayesian',

          'autocomplete_on' => get_post_meta($test_id, 'autocomplete_on', true) == '1',

          'ac_min_days' => intval(get_post_meta($test_id, 'ac_min_days', true)),

          'ac_min_views' => intval(get_post_meta($test_id, 'ac_min_views', true)),

        ],

        'subgoals' => abst_storage_subgoals_to_api(get_post_meta($test_id, 'goals', true)),

        'idea' => [

          'hypothesis' => get_post_meta($test_id, 'abst_idea_hypothesis', true),

          'page_flow' => get_post_meta($test_id, 'abst_idea_page_flow', true),

          'observed_problem' => get_post_meta($test_id, 'abst_idea_observed_problem', true),

          'impact' => get_post_meta($test_id, 'abst_idea_impact', true),

          'reach' => get_post_meta($test_id, 'abst_idea_reach', true),

          'confidence' => get_post_meta($test_id, 'abst_idea_confidence', true),

          'effort' => get_post_meta($test_id, 'abst_idea_effort', true),

          'next_step' => get_post_meta($test_id, 'abst_idea_next_step', true),

        ],

        'webhook_url' => get_post_meta($test_id, 'webhook_url', true),

        'magic_definition' => $magic_definition,

        'css_variations' => intval(get_post_meta($test_id, 'css_test_variations', true) ?: 0),

        'full_page' => [

          'default_page' => get_post_meta($test_id, 'bt_experiments_full_page_default_page', true),

          'variations' => $full_page_variations,

        ],

        'preview_urls' => $this->build_preview_urls($test_id),

        'edit_url' => admin_url('post.php?post=' . $test_id . '&action=edit'),

      ];



      return $details;

    }



    /**

     * Wrapper methods for WordPress Abilities API

     * These convert the input array format to WP_REST_Request objects

     * and extract data from WP_REST_Response objects

     */

    function ability_list_tests($input) {

      $request = new WP_REST_Request('GET', '/bt-bb-ab/v1/list-tests');

      if (!empty($input['status'])) {

        $request->set_param('status', $input['status']);

      }

      if (!empty($input['test_type'])) {

        $request->set_param('test_type', $input['test_type']);

      }

      $response = $this->rest_list_tests($request);

      

      // Extract data from WP_REST_Response or return WP_Error as-is

      if (is_wp_error($response)) {

        return $response;

      }

      return $response->get_data();

    }



    function ability_create_test($input) {

      $request = new WP_REST_Request('POST', '/bt-bb-ab/v1/create-test');

      $request->set_header('Content-Type', 'application/json');

      $request->set_body(wp_json_encode($input));

      $response = $this->rest_create_test($request);

      

      if (is_wp_error($response)) {

        return $response;

      }

      return $response->get_data();

    }



    function ability_get_test_results($input) {

      $request = new WP_REST_Request('GET', '/bt-bb-ab/v1/test-results/' . ($input['test_id'] ?? 0));

      $request->set_param('id', $input['test_id'] ?? 0);

      $response = $this->rest_get_test_results($request);

      

      if (is_wp_error($response)) {

        return $response;

      }

      return $response->get_data();

    }



    function ability_get_test_details($input) {

      $request = new WP_REST_Request('GET', '/bt-bb-ab/v1/test-details/' . ($input['test_id'] ?? 0));

      $request->set_param('id', $input['test_id'] ?? 0);

      $response = $this->rest_get_test_details($request);



      if (is_wp_error($response)) {

        return $response;

      }

      return $response->get_data();

    }



    function ability_update_test_status($input) {

      $request = new WP_REST_Request('POST', '/bt-bb-ab/v1/update-test-status');

      $request->set_header('Content-Type', 'application/json');

      $request->set_body(wp_json_encode($input));

      $response = $this->rest_update_test_status($request);



      if (is_wp_error($response)) {

        return $response;

      }

      return $response->get_data();

    }



    function ability_update_test_settings($input) {

      $request = new WP_REST_Request('POST', '/bt-bb-ab/v1/update-test-settings');

      $request->set_header('Content-Type', 'application/json');

      $request->set_body(wp_json_encode($input));

      $response = $this->rest_update_test_settings($request);



      if (is_wp_error($response)) {

        return $response;

      }

      $data = $response->get_data();

      // Append preview URLs so the AI can verify the test looks correct

      if (!empty($data['test_id'])) {

        $data['preview_urls'] = $this->build_preview_urls($data['test_id']);

      }

      return $data;

    }



    /**

     * Register REST API routes

     */

    function register_rest_routes() {

      // Generic endpoint for all test types

      register_rest_route('bt-bb-ab/v1', '/create-test', [

        'methods' => 'POST',

        'callback' => [$this, 'rest_create_test'],

        'permission_callback' => function() {

          return current_user_can('edit_posts');

        }

      ]);



      // List all tests

      register_rest_route('bt-bb-ab/v1', '/list-tests', [

        'methods' => 'GET',

        'callback' => [$this, 'rest_list_tests'],

        'permission_callback' => function() {

          return current_user_can('edit_posts');

        }

      ]);



      // Get test results

      register_rest_route('bt-bb-ab/v1', '/test-results/(?P<id>\d+)', [

        'methods' => 'GET',

        'callback' => [$this, 'rest_get_test_results'],

        'permission_callback' => function() {

          return current_user_can('edit_posts');

        },

        'args' => [

          'id' => [

            'validate_callback' => function($param) {

              return is_numeric($param);

            }

          ]

        ]

      ]);



      register_rest_route('bt-bb-ab/v1', '/test-details/(?P<id>\d+)', [

        'methods' => 'GET',

        'callback' => [$this, 'rest_get_test_details'],

        'permission_callback' => function() {

          return current_user_can('edit_posts');

        },

        'args' => [

          'id' => [

            'validate_callback' => function($param) {

              return is_numeric($param);

            }

          ]

        ]

      ]);



      // Update test status

      register_rest_route('bt-bb-ab/v1', '/update-test-status', [

        'methods' => 'POST',

        'callback' => [$this, 'rest_update_test_status'],

        'permission_callback' => function() {

          return current_user_can('edit_posts');

        }

      ]);



      // Update test settings (conversion goals, etc.)

      register_rest_route('bt-bb-ab/v1', '/update-test-settings', [

        'methods' => 'POST',

        'callback' => [$this, 'rest_update_test_settings'],

        'permission_callback' => function() {

          return current_user_can('edit_posts');

        }

      ]);



    }



    /**

     * REST API endpoint to update test settings

     * 

     * POST /wp-json/bt-bb-ab/v1/update-test-settings

     * 

     * @param WP_REST_Request $request

     * @return WP_REST_Response|WP_Error

     */

    function rest_update_test_settings($request) {

      require_once plugin_dir_path(__FILE__) . 'bt-bb-ab-rest-update-settings.php';

      return abst_rest_update_test_settings($request);

    }



    function rest_get_test_details($request) {

      $test_id = intval($request->get_param('id'));

      $post = get_post($test_id);



      if (!$post || $post->post_type !== 'bt_experiments') {

        return new WP_Error('test_not_found', 'Test not found', ['status' => 404]);

      }



      if (!(defined('WP_CLI') && WP_CLI) && !current_user_can('edit_post', $test_id)) {

        return new WP_Error('forbidden', 'You do not have permission to view this test.', ['status' => 403]);

      }



      $details = $this->get_test_details_payload($test_id);

      return new WP_REST_Response([

        'success' => true,

        'test' => $details,

      ], 200);

    }



    /**

     * REST API endpoint to update test status

     * 

     * POST /wp-json/bt-bb-ab/v1/update-test-status

     * 

     * @param WP_REST_Request $request

     * @return WP_REST_Response|WP_Error

     */

    function rest_update_test_status($request) {

      require_once plugin_dir_path(__FILE__) . 'bt-bb-ab-validation.php';

      $params = $request->get_json_params();

      

      if (empty($params['test_id'])) {

        return new WP_Error('missing_test_id', 'Test ID is required', ['status' => 400]);

      }

      

      if (empty($params['status'])) {

        return new WP_Error('missing_status', 'Status is required', ['status' => 400]);

      }

      

      $test_id = intval($params['test_id']);

      $status = abst_normalize_test_status($params['status']);

      

      // Validate status

      if (!in_array($status, abst_get_supported_test_statuses(), true)) {

        return new WP_Error('invalid_status', 'Status must be one of: ' . implode(', ', abst_get_supported_test_statuses()), ['status' => 400]);

      }

      

      // Check if test exists

      $test = get_post($test_id);

      if (!$test || $test->post_type !== 'bt_experiments') {

        return new WP_Error('test_not_found', 'Test not found', ['status' => 404]);

      }



      if (!(defined('WP_CLI') && WP_CLI) && !current_user_can('edit_post', $test_id)) {

        return new WP_Error('forbidden', 'You do not have permission to update this test.', ['status' => 403]);

      }

      $active_limit = abst_lite_validate_active_test_limit($status, $test_id);
      if (is_wp_error($active_limit)) {
        return $active_limit;
      }

      

      // Update the post status

      $result = wp_update_post([

        'ID' => $test_id,

        'post_status' => $status

      ]);

      

      if (is_wp_error($result)) {

        return new WP_Error('update_failed', 'Failed to update test status: ' . $result->get_error_message(), ['status' => 500]);

      }

      

      // Refresh conversion pages cache if publishing or unpublishing

      $this->refresh_conversion_pages();

      

      return new WP_REST_Response([

        'success' => true,

        'test_id' => $test_id,

        'status' => $status,

        'message' => 'Test status updated to ' . $status

      ], 200);

    }



    /**

     * REST API endpoint to list all A/B tests

     * 

     * GET /wp-json/bt-bb-ab/v1/list-tests

     * 

     * @param WP_REST_Request $request

     * @return WP_REST_Response

     */

    function rest_list_tests($request) {

      require_once plugin_dir_path(__FILE__) . 'bt-bb-ab-validation.php';



      $requested_status = abst_normalize_test_status($request->get_param('status'), 'list');

      $requested_test_type = sanitize_text_field((string) $request->get_param('test_type'));

      $args = [

        'post_type' => 'bt_experiments',

        'posts_per_page' => -1,

        'post_status' => abst_user_level() == 'pro' ? ['idea', 'publish', 'draft', 'pending', 'complete'] : ['publish', 'draft', 'pending', 'complete'],

        'orderby' => 'date',

        'order' => 'DESC'

      ];



      if (!empty($requested_status) && $requested_status !== 'any') {

        if (!in_array($requested_status, abst_get_supported_test_statuses(), true)) {

          return new WP_Error('invalid_status', 'Status must be one of: any, ' . implode(', ', abst_get_supported_test_statuses()), ['status' => 400]);

        }

        $args['post_status'] = $requested_status;

      }



      if ($requested_test_type !== '') {

        if (!in_array($requested_test_type, abst_get_supported_test_types(), true)) {

          return new WP_Error('invalid_test_type', 'Test type must be one of: ' . implode(', ', abst_get_supported_test_types()), ['status' => 400]);

        }

        $args['meta_query'] = [[

          'key' => 'test_type',

          'value' => $requested_test_type,

          'compare' => '=',

        ]];

      }



      $tests = get_posts($args);

      $results = [];



      foreach ($tests as $test) {

        $test_type = get_post_meta($test->ID, 'test_type', true);

        $magic_definition = get_post_meta($test->ID, 'magic_definition', true);

        $conversion_trigger = get_post_meta($test->ID, 'conversion_page', true);

        $conversion_type = is_numeric($conversion_trigger) ? 'page' : abst_normalize_conversion_type($conversion_trigger);

        $test_status = get_post_meta($test->ID, 'test_status', true);

        $conversion_value = '';



        if ($conversion_type === 'selector') {

          $conversion_value = get_post_meta($test->ID, 'conversion_selector', true);

        } elseif ($conversion_type === 'url') {

          $conversion_value = get_post_meta($test->ID, 'conversion_url', true);

        } elseif ($conversion_type === 'text') {

          $conversion_value = get_post_meta($test->ID, 'conversion_text', true);

        } elseif ($conversion_type === 'link') {

          $conversion_value = get_post_meta($test->ID, 'conversion_link_pattern', true);

        } elseif ($conversion_type === 'page') {

          $conversion_value = is_numeric($conversion_trigger) ? intval($conversion_trigger) : 0;

        }



        $results[] = [

          'id' => $test->ID,

          'title' => $test->post_title,

          'type' => $test_type,

          'status' => $test->post_status,

          'test_status' => $test_status,

          'conversion_type' => $conversion_type,

          'conversion_value' => $conversion_value,

          'subgoals' => abst_storage_subgoals_to_api(get_post_meta($test->ID, 'goals', true)),

          'created' => $test->post_date,

          'modified' => $test->post_modified,

          'edit_url' => admin_url('post.php?post=' . $test->ID . '&action=edit'),

          'magic_definition' => $magic_definition ? json_decode($magic_definition, true) : null,

          'idea' => [

            'hypothesis' => get_post_meta($test->ID, 'abst_idea_hypothesis', true),

            'page_flow' => get_post_meta($test->ID, 'abst_idea_page_flow', true),

            'observed_problem' => get_post_meta($test->ID, 'abst_idea_observed_problem', true),

            'impact' => get_post_meta($test->ID, 'abst_idea_impact', true),

            'reach' => get_post_meta($test->ID, 'abst_idea_reach', true),

            'confidence' => get_post_meta($test->ID, 'abst_idea_confidence', true),

            'effort' => get_post_meta($test->ID, 'abst_idea_effort', true),

            'next_step' => get_post_meta($test->ID, 'abst_idea_next_step', true),

          ],

        ];

      }



      return new WP_REST_Response([

        'success' => true,

        'count' => count($results),

        'tests' => $results

      ], 200);

    }



    /**

     * REST API endpoint to get test results

     * 

     * GET /wp-json/bt-bb-ab/v1/test-results/{id}

     * 

     * @param WP_REST_Request $request

     * @return WP_REST_Response|WP_Error

     */

    function rest_get_test_results($request) {

      require_once plugin_dir_path(__FILE__) . 'bt-bb-ab-validation.php';



      $test_id = $request->get_param('id');

      $post = get_post($test_id);



      if (!$post || $post->post_type !== 'bt_experiments') {

        return new WP_Error('test_not_found', 'Test not found', ['status' => 404]);

      }



      // Use the same stats computation the admin UI uses — this gives time remaining,

      // confidence, uplift vs control, per-variation conversion rates, etc.

      $stats = $this->get_experiment_stats_array($post);



      $conversion_trigger = get_post_meta($test_id, 'conversion_page', true);

      $conversion_type = is_numeric($conversion_trigger) ? 'page' : abst_normalize_conversion_type($conversion_trigger);

      $test_type = $stats['test_type'] ?? get_post_meta($test_id, 'test_type', true);



      // Build variation preview URLs.

      // Full-page tests: each variation is its own page so the preview is just that page's URL.

      // All other types: the variation runs on the page where visits were recorded, identified

      // via ?abtv={key}&abtid={test_id} so the frontend can force-show the right variation.

      if (isset($stats['variations']) && is_array($stats['variations'])) {

        foreach ($stats['variations'] as $key => &$variation) {

          if ($test_type === 'full_page') {

            $variation['preview_url'] = $variation['page_id'] ? get_permalink($variation['page_id']) : null;

          } else {

            $base = $variation['page_id'] ? trailingslashit(get_permalink($variation['page_id'])) : null;

            // Stored keys are 'magic-{id}-{n}'; abtv must be 'magic-{n}' for the JS preview handler

            $abtv = preg_match('/^magic-\d+-(\d+)$/', $key, $m) ? 'magic-' . $m[1] : $key;

            $variation['preview_url'] = $base

              ? add_query_arg(['abtv' => $abtv, 'abtid' => $test_id], $base)

              : null;

          }

        }

        unset($variation);

      }



      // Get or create a shareable report link. The token keeps it non-guessable

      // Public reports are PRO only — disabled in lite version

      $report_url = null;



      return new WP_REST_Response([

        'success' => true,

        'test' => array_merge($stats ?? [], [

          'title'           => $post->post_title,

          'conversion_type' => $conversion_type,

          'subgoals'        => abst_storage_subgoals_to_api(get_post_meta($test_id, 'goals', true)),

          'report_url'      => null,

          'edit_url'        => admin_url('post.php?post=' . $test_id . '&action=edit'),

        ])

      ], 200);

    }



    /**

     * REST API endpoint to programmatically create any type of A/B test

     * 

     * POST /wp-json/bt-bb-ab/v1/create-test

     * 

     * @param WP_REST_Request $request

     * @return WP_REST_Response|WP_Error

     */

    function rest_create_test($request) {

      require_once plugin_dir_path(__FILE__) . 'bt-bb-ab-validation.php';

      $params = abst_normalize_api_input_params($request->get_json_params());
      $params = abst_lite_apply_test_limits($params);

      $guard_result = abst_apply_conversion_order_value_guard($params);

      $params = $guard_result['params'];

      $validation_warnings = $guard_result['warnings'];

      

      $validation_error = abst_validate_test_payload($params, 'create');

      if (is_wp_error($validation_error)) {

        return $validation_error;

      }

      $active_limit = abst_lite_validate_active_test_limit($params['status'] ?? 'draft');
      if (is_wp_error($active_limit)) {
        return $active_limit;
      }



      if (($params['status'] ?? 'draft') === 'idea') {

        // Lite: Test Ideas are Pro-only

        if (abst_user_level() !== 'pro') {

          return new WP_Error('pro_feature', 'Test Ideas are only available in the Pro version. Upgrade to create test ideas.', ['status' => 403]);

        }

        $post_data = [

          'post_title' => $params['test_title'],

          'post_type' => 'bt_experiments',

          'post_status' => 'idea',

          'post_content' => isset($params['description']) ? wp_kses_post($params['description']) : ''

        ];



        $test_id = wp_insert_post($post_data);

        if (is_wp_error($test_id)) {

          return new WP_Error('creation_failed', 'Failed to create idea: ' . $test_id->get_error_message(), ['status' => 500]);

        }



        $this->save_idea_config($test_id, $params);



        return new WP_REST_Response([

          'success' => true,

          'test_id' => $test_id,

          'test_type' => '',

          'conversion_type' => '',

          'subgoals' => [],

          'preview_urls' => [],

          'normalized' => [

            'status' => 'idea',

          ],

          'validation_warnings' => $validation_warnings,

          'edit_url' => admin_url('post.php?post=' . $test_id . '&action=edit'),

          'message' => 'Idea created successfully'

        ], 201);

      }



      $test_type = $params['test_type'];

      

      // Create the test post

      $post_data = [

        'post_title' => $params['test_title'],

        'post_type' => 'bt_experiments',

        'post_status' => isset($params['status']) ? $params['status'] : 'draft',

        'post_content' => isset($params['description']) ? wp_kses_post($params['description']) : ''

      ];

      

      $test_id = wp_insert_post($post_data);

      

      if (is_wp_error($test_id)) {

        return new WP_Error('creation_failed', 'Failed to create test: ' . $test_id->get_error_message(), ['status' => 500]);

      }

      

      // Set test type

      update_post_meta($test_id, 'test_type', $test_type);

      

      // Configure test based on type

      $type_config_result = true;

      switch ($test_type) {

        case 'magic':

          $type_config_result = $this->configure_magic_test($test_id, $params);

          break;

        case 'ab_test':

          $type_config_result = $this->configure_ab_test($test_id, $params);

          break;

        case 'css_test':

          $type_config_result = $this->configure_css_test($test_id, $params);

          break;

        case 'full_page':

          $type_config_result = $this->configure_full_page_test($test_id, $params);

          break;

      }



      if (is_wp_error($type_config_result)) {

        wp_delete_post($test_id, true);

        return $type_config_result;

      }

      

      // Set common configuration (conversion goals, targeting, etc.)

      $config_result = $this->configure_common_test_settings($test_id, $params);

      if (is_wp_error($config_result)) {

        wp_delete_post($test_id, true);

        return $config_result;

      }



      // Refresh conversion pages cache

      $this->refresh_conversion_pages();



      $saved_subgoals = abst_storage_subgoals_to_api(get_post_meta($test_id, 'goals', true));



      return new WP_REST_Response([

        'success' => true,

        'test_id' => $test_id,

        'test_type' => $test_type,

        'conversion_type' => $params['conversion_type'],

        'subgoals' => $saved_subgoals,

        'preview_urls' => $this->build_preview_urls($test_id),

        'normalized' => [

          'status' => $post_data['post_status'],

          'target_percentage' => isset($params['target_percentage']) ? intval($params['target_percentage']) : 100,

          'conversion_type' => $params['conversion_type'],

          'conversion_url' => $params['conversion_url'] ?? '',

          'conversion_page_id' => $params['conversion_page_id'] ?? 0,

          'conversion_selector' => $params['conversion_selector'] ?? '',

          'conversion_link_pattern' => $params['conversion_link_pattern'] ?? '',

          'conversion_time' => $params['conversion_time'] ?? 0,

          'conversion_scroll' => $params['conversion_scroll'] ?? 0,

          'conversion_text' => $params['conversion_text'] ?? '',

          'conversion_use_order_value' => !empty($params['conversion_use_order_value'])

        ],

        'validation_warnings' => $validation_warnings,

        'edit_url' => admin_url('post.php?post=' . $test_id . '&action=edit'),

        'message' => ucfirst($test_type) . ' test created successfully'

      ], 201);

    }

    

    /**

     * Configure magic test specific settings

     */

    private function configure_magic_test($test_id, $params) {

      require_once plugin_dir_path(__FILE__) . 'bt-bb-ab-validation.php';



      if (!empty($params['magic_definition'])) {

        $magic_def = abst_lite_limit_magic_definition($params['magic_definition']);



        $magic_validation = abst_validate_magic_definition($magic_def);

        if (is_wp_error($magic_validation)) {

          return $magic_validation;

        }

        

        // If it's already JSON string, validate it

        if (is_string($magic_def)) {

          $decoded = json_decode($magic_def, true);

          if (json_last_error() === JSON_ERROR_NONE) {

            $magic_def = wp_json_encode($decoded, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

          }

        } else if (is_array($magic_def)) {

          // If it's an array, encode it

          $magic_def = wp_json_encode($magic_def, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        }

        

        update_post_meta($test_id, 'magic_definition', $magic_def);

      }



      return true;

    }

    

    /**

     * Configure on-page element test specific settings

     */

    private function configure_ab_test($test_id, $params) {

      // On-page tests don't have specific meta fields

      // They use shortcodes/attributes added to page content

      // Configuration is handled via common settings

      return true;

    }

    

    /**

     * Configure CSS test specific settings

     */

    private function configure_css_test($test_id, $params) {

      update_post_meta($test_id, 'css_test_variations', 1);



      return true;

    }

    

    /**

     * Configure full page test specific settings

     */

    private function configure_full_page_test($test_id, $params) {

      if (empty($params['default_page'])) {

        return new WP_Error('missing_default_page', 'Default page is required for full page tests', ['status' => 400]);

      }

      

      if (empty($params['variations']) || !is_array($params['variations'])) {

        return new WP_Error('missing_variations', 'At least one variation page is required for full page tests', ['status' => 400]);

      }

      

      // Set default page

      $default_page = is_numeric($params['default_page']) ? intval($params['default_page']) : sanitize_text_field($params['default_page']);

      update_post_meta($test_id, 'bt_experiments_full_page_default_page', $default_page);

      

      // Set page variations

      $page_variations = [];

      $variation_meta = [];

      

      $variations = array_slice(array_values($params['variations']), 0, 1);

      foreach ($variations as $index => $variation) {

        $var_id = is_numeric($variation) ? intval($variation) : sanitize_text_field($variation);

        $page_variations[$var_id] = $this->get_url_from_slug($var_id);

        

        // Set variation metadata if provided

        $variation_meta[$var_id] = [

          'label' => isset($params['variation_labels'][$index]) ? sanitize_text_field($params['variation_labels'][$index]) : '',

          'image' => isset($params['variation_images'][$index]) ? esc_url_raw($params['variation_images'][$index]) : '',

          'weight' => 1

        ];

      }

      

      update_post_meta($test_id, 'page_variations', $page_variations);

      update_post_meta($test_id, 'variation_meta', $variation_meta);



      return true;

    }

    

    /**

     * Configure common test settings (conversion goals, targeting, etc.)

     */

    private function configure_common_test_settings($test_id, $params) {

      require_once plugin_dir_path(__FILE__) . 'bt-bb-ab-validation.php';

      $params = abst_normalize_api_input_params($params);
      $params = abst_lite_apply_test_limits($params);

      $guard_result = abst_apply_conversion_order_value_guard($params);

      $params = $guard_result['params'];



      // Set conversion goal

      if (!empty($params['conversion_type'])) {

        $conversion_type = $params['conversion_type'];

        update_post_meta($test_id, 'conversion_page', $conversion_type);

        

        // Handle different conversion types

        switch ($conversion_type) {

          case 'selector':

            if (!empty($params['conversion_selector'])) {

              update_post_meta($test_id, 'conversion_selector', $params['conversion_selector']);

            }

            break;

          case 'link':

            if (!empty($params['conversion_link_pattern'])) {

              update_post_meta($test_id, 'conversion_link_pattern', $params['conversion_link_pattern']);

            }

            break;

          case 'url':

            if (!empty($params['conversion_url'])) {

              update_post_meta($test_id, 'conversion_url', $params['conversion_url']);

            }

            break;

          case 'page':

            if (!empty($params['conversion_page_id'])) {

              update_post_meta($test_id, 'conversion_page', intval($params['conversion_page_id']));

            }

            break;

          case 'time':

            if (!empty($params['conversion_time'])) {

              update_post_meta($test_id, 'conversion_time', intval($params['conversion_time']));

            }

            break;

          case 'scroll':

            if (!empty($params['conversion_scroll'])) {

              update_post_meta($test_id, 'conversion_scroll', intval($params['conversion_scroll']));

            }

            break;

          case 'text':

            if (!empty($params['conversion_text'])) {

              update_post_meta($test_id, 'conversion_text', sanitize_text_field($params['conversion_text']));

            }

            break;

        }

      }

      

      if (isset($params['conversion_use_order_value'])) {

        update_post_meta($test_id, 'conversion_use_order_value', $params['conversion_use_order_value'] ? '1' : '0');

      }



      // Set targeting options

      $target_percentage = isset($params['target_percentage']) ? intval($params['target_percentage']) : 100;

      update_post_meta($test_id, 'target_percentage', $target_percentage);

      

      if (!empty($params['target_device'])) {

        update_post_meta($test_id, 'target_option_device_size', sanitize_text_field($params['target_device']));

      }

      

      if (!empty($params['allowed_roles']) && is_array($params['allowed_roles'])) {

        $allowed_roles = array_map('sanitize_text_field', $params['allowed_roles']);

        update_post_meta($test_id, 'bt_allowed_roles', $allowed_roles);

      } else {

        // Default to logged out, subscribers, and customers only (excludes admin and editor roles)

        $default_roles = ['subscriber', 'customer', 'logout'];

        update_post_meta($test_id, 'bt_allowed_roles', $default_roles);

      }

      

      // Set optimization type

      $optimization_type = isset($params['optimization_type']) ? sanitize_text_field($params['optimization_type']) : 'bayesian';

      update_post_meta($test_id, 'conversion_style', $optimization_type);

      if ($optimization_type !== 'thompson') {

        $this->clear_test_variation_weights($test_id);

      }

      

      // Set URL query parameter filter

      if (!empty($params['url_query'])) {

        update_post_meta($test_id, 'url_query', sanitize_textarea_field($params['url_query']));

      }

      

      // Set webhook URL

      if (!empty($params['webhook_url'])) {

        update_post_meta($test_id, 'webhook_url', esc_url($params['webhook_url']));

      }

      

      // Lite supports one primary conversion only.
      delete_post_meta($test_id, 'goals');

      

      // Set log on visible

      if (isset($params['log_on_visible'])) {

        update_post_meta($test_id, 'log_on_visible', $params['log_on_visible'] ? '1' : '0');

      }

      

      // Set conversion order value tracking

      if (isset($params['conversion_use_order_value'])) {

        update_post_meta($test_id, 'conversion_use_order_value', $params['conversion_use_order_value'] ? '1' : '0');

      }

      

      // Set autocomplete settings

      if (isset($params['autocomplete_on'])) {

        update_post_meta($test_id, 'autocomplete_on', intval($params['autocomplete_on']));

        if ($params['autocomplete_on']) {

          $min_days = isset($params['ac_min_days']) ? absint($params['ac_min_days']) : 7;

          $min_views = isset($params['ac_min_views']) ? absint($params['ac_min_views']) : 50;

          update_post_meta($test_id, 'ac_min_days', $min_days);

          update_post_meta($test_id, 'ac_min_views', $min_views);

        }

      }

      return true;

    }



    function handle_mcp_adapter_install() {

      // Check if install button was clicked

      if (!isset($_POST['install_mcp_adapter'])) {

        return;

      }

      

      // Verify nonce

      if (!isset($_POST['absplittest_mcp_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['absplittest_mcp_nonce'])), 'absplittest_install_mcp')) {

        wp_die('Security check failed');

      }

      

      // Check user permissions

      if (!current_user_can('install_plugins')) {

        wp_die('You do not have permission to install plugins.');

      }

      

      // Include required WordPress files

      require_once ABSPATH . 'wp-admin/includes/plugin-install.php';

      require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';

      require_once ABSPATH . 'wp-admin/includes/file.php';

      

      // GitHub release URL for WordPress MCP Adapter

      $plugin_zip = 'https://github.com/WordPress/mcp-adapter/releases/latest/download/mcp-adapter.zip';

      

      // Create upgrader instance with silent skin

      $upgrader = new Plugin_Upgrader(new WP_Ajax_Upgrader_Skin());

      

      // Install the plugin

      $result = $upgrader->install($plugin_zip);

      

      // Check if installation was successful

      if (is_wp_error($result)) {

        $error_message = $result->get_error_message();

        wp_safe_redirect(admin_url('options-general.php?page=bt_bb_ab_test&tab=mcp&mcp_install_error=' . urlencode($error_message)));

        exit;

      }

      

      if ($result === false) {

        wp_safe_redirect(admin_url('options-general.php?page=bt_bb_ab_test&tab=mcp&mcp_install_error=' . urlencode('Installation failed. Please try manual installation.')));

        exit;

      }

      

      // Activate the plugin

      $plugin_file = 'mcp-adapter/mcp-adapter.php';

      $activate_result = activate_plugin($plugin_file);

      

      if (is_wp_error($activate_result)) {

        $error_message = $activate_result->get_error_message();

        wp_safe_redirect(admin_url('options-general.php?page=bt_bb_ab_test&tab=mcp&mcp_install_error=' . urlencode('Plugin installed but activation failed: ' . $error_message)));

        exit;

      }

      

      // Success - redirect with success message

      wp_safe_redirect(admin_url('options-general.php?page=bt_bb_ab_test&tab=mcp&mcp_install_success=1'));

      exit;

    }



    function wp_ajax_bt_clear_results(){

      if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'abst_clear_results')) {

        wp_die('Security check failed');

      }

      $post = $_POST;

      $eid = intval($post['eid']);

      $response = array(

        'text' => '',

        'success' => false,

      );

      

      if(isset($eid) && $eid !== '' && $post['bt_action'] == 'clear')

      {

        if(!current_user_can('delete_posts', $eid))

        {

          $response['text'] = 'You do not have permission to reset these results.';

          wp_send_json($response);

        }



        //query for an experiment that this user can edit

        $experiment = get_post($eid);



        if(!$experiment)

        {

          $response['text'] = 'Not found.';

          wp_send_json($response);

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

          wp_send_json($response);

      }

      else

      {

          $response['text'] = 'Try again later.';

          wp_send_json($response);

      }

    }



    function add_btvar_attribute_to_all_blocks( $block_content, $block,$instance ) {

      if(!empty($block['attrs']['bt-eid']))

      {

        $ignored = ['style','script','link','meta'];



        $processor = new WP_HTML_Tag_Processor( $block_content );

        while ( $processor->next_tag() ) {

            $tag_name = strtolower( $processor->get_tag() );

            if ( in_array( $tag_name, $ignored, true ) ) {

                continue;

            }

            // set attributes here, then return updated HTML

            $processor->set_attribute( 'bt-eid', $block['attrs']['bt-eid']  ?? '');

            if(!empty($block['attrs']['bt-variation']))

              $processor->set_attribute( 'bt-variation', $block['attrs']['bt-variation'] ?? '');

            

            return $processor->get_updated_html();

        }

        

      }

      return $block_content;

    

    }




    /**

     * Get experiment status/analysis without echoing the full admin interface

     * Used for public reports to extract just the analysis summary

     * 

     * @param WP_Post $test The test post object

     * @return string The experiment status HTML

     */

    public function get_experiment_status_only($test) {

        // Capture the experiment status by temporarily modifying output buffering

        ob_start();

        

        // We need to extract just the experiment_status variable from abst_show_experiment_results

        // Let's call it but capture only the status part

        $this->abst_show_experiment_results($test, false);

        $full_output = ob_get_clean();

        

        // Extract just the experiment_status content (look for the analysis div)

        $experiment_status = '';

        

        // Find the main analysis content - look for bt_ab_success divs

        if (preg_match_all('/<div class="bt_ab_success[^>]*>(.*?)<\/div>/s', $full_output, $matches)) {

            // Join all success messages with line breaks

            $experiment_status = implode('<br>', array_map(function($match) {

                return '<div class="bt_ab_success">' . trim($match) . '</div>';

            }, $matches[1]));

        }

        

        // If no success divs found, try to find any content with winner/analysis patterns

        if (empty($experiment_status)) {

            // Look for winner announcements or test progress messages

            if (preg_match('/🎉.*?winner.*?<\/div>/s', $full_output, $winner_match)) {

                $experiment_status = $winner_match[0];

            } elseif (preg_match('/🧪.*?Test in Progress.*?<\/div>/s', $full_output, $progress_match)) {

                $experiment_status = $progress_match[0];

            }

        }

        

        if (empty($experiment_status)) {

        }

        

        return $experiment_status;

    }

}

global $btab;



$abst_btab = new Bt_Ab_Tests();

}



function abst_user_level(){

  // Lite version is always free tier

  return 'free';

}

function abst_settings(){

  $bt_bb_ab_defaultSettings = abst_defaults();

  $savedSettings = get_option( 'bt_bb_ab_settings',true);

  if(is_array($savedSettings))

    $abSettings = array_merge($bt_bb_ab_defaultSettings,$savedSettings);

  else

    $abSettings = $bt_bb_ab_defaultSettings;



  return $abSettings;

}



function abst_defaults(){

  $defaults = array(

    'bt_bb_ab_licence' => '',

    'ab_test_modules' => '1',

    'ab_test_rows' => '1',

    'ab_wl_bb' => '',

    'ab_wl_bt' => '',

    'ab_wl_url' => '', 

    'fathom_api_key' => '',

    'webhook_global' => '',

    'selected_post_types' => [],

  );

  return $defaults;

}



function abst_split_test_admin_bar_menu( $wp_admin_bar ) { 



    if ( is_admin() ) // not in admin area

      return;



    // Check if current user can manage options

    if ( ! current_user_can( 'manage_options' ) ) 

        return;

    




    // Add the main menu item

    $args = array(

        'id'    => 'ab-test',

        'title' => '<div class="ab-test-tube"></div>'.esc_html('AB Split Test Lite'),

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

add_action( 'admin_bar_menu', 'abst_split_test_admin_bar_menu', 199 );





  

    function abst_post_types_to_test(){



      $post_types = get_post_types(array('public' => true));

      $selected_post_types = abst_get_admin_setting('selected_post_types');

        if(!empty($selected_post_types))

          $post_types = $selected_post_types;

      return ($post_types);

    }

 

		function abst_get_all_posts()

		{

      //if being called from network admin

      if(is_network_admin())

        return get_option('all_testable_posts');



      //get opt

      $grouped_posts = get_option('all_testable_posts');

      //if exists then send it

      if(!empty($grouped_posts))

  			return $grouped_posts;



      // otherwise generate

      $selected_post_types = (array)abst_post_types_to_test();



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



function abst_get_admin_setting($setting){ 

  if(is_plugin_active_for_network(BT_AB_PLUGIN_FOLDER.'/bt-bb-ab.php'))

    return get_site_option($setting);

  else // single site or not network activated

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

  if (abst_get_admin_setting('abst_enable_logging') != '1') {

    return; // Exit if logging is disabled

  }

  

  // Get WordPress uploads directory

  $upload_dir = wp_upload_dir();

  $log_dir = $upload_dir['basedir'];

  $hash = substr(md5(defined('AUTH_KEY') ? AUTH_KEY : 'abst'), 0, 12);
  $log_file = $log_dir . '/abst_log_' . $hash . '.log';

  

  // if message is array or object then stringify it

  if (is_array($message) || is_object($message)) {

    $message = wp_json_encode($message);

  }

  // Format the log entry

  $timestamp = current_time('mysql');

  $log_entry = "[$timestamp] $message\n";

  

  // Write to log file

  abst_put_contents($log_file, $log_entry, true);



}

function abst_put_contents($file, $content, $append = false) {
  global $wp_filesystem;
  if (empty($wp_filesystem)) {
    require_once ABSPATH . 'wp-admin/includes/file.php';
    WP_Filesystem();
  }
  if ($append && $wp_filesystem->exists($file)) {
    $existing = $wp_filesystem->get_contents($file);
    $content = $existing . $content;
  }
  return $wp_filesystem->put_contents($file, $content, FS_CHMOD_FILE);
}



/**

 * Add AB Split Test Logs page to admin menu

 * Only added if logging is enabled in settings

 */

function abst_add_logs_page() {

  // Only add logs menu if logging is enabled

  //add heatmaps

  if (abst_get_admin_setting('abst_enable_user_journeys') == '1'){

    add_submenu_page(

      'edit.php?post_type=bt_experiments',

      'Heatmaps',

      'Heatmaps',

      'manage_options',

      'abst-heatmaps',

      'abst_heatmaps_page_content'

    );

    

    // Session Replay - only if both journeys AND session replays are enabled

    if (abst_get_admin_setting('abst_enable_user_journeys') == '1' && abst_get_admin_setting('abst_enable_session_replays') == '1') {

      add_submenu_page(

        'edit.php?post_type=bt_experiments',

        'Session Replays',

        'Session Replays',

        'manage_options',

        'abst-session-replay',

        'abst_session_replay_page_content'

      );

    }

  }




  if (abst_get_admin_setting('abst_enable_logging') == '1') {

    add_submenu_page(

      'edit.php?post_type=bt_experiments',

      'Test Logs',

      'Logs',

      'manage_options',

      'abst-logs',

      'abst_logs_page_content'

    );

  }



  // Lite: Agency Hub removed - Pro only feature

} 

add_action('admin_menu', 'abst_add_logs_page');






/**

 * Session Replay page content callback

 * Delegates to the ABST_Session_Replay class

 */

function abst_session_replay_page_content() {

  if (class_exists('ABST_Session_Replay')) {

    $replay = new ABST_Session_Replay();

    $replay->render_admin_page();

  }

}



/**

 * Resolve archive page identifier to URL

 * 

 * @param string|int $page_id Page ID or archive identifier

 * @return string Page URL

 */

function abst_resolve_page_url($page_id) {

  // Handle numeric IDs (int or numeric string)

  if (is_numeric($page_id)) {

    $url = get_permalink((int)$page_id);

    return $url ? $url : '/?p=' . $page_id;

  }

  

  if (strpos($page_id, 'post-type-archive-') === 0) {

    $post_type = str_replace('post-type-archive-', '', $page_id);

    $url = get_post_type_archive_link($post_type);

    return $url ? $url : home_url('/');

  }

  

  if (strpos($page_id, 'category-') === 0) {

    $category_slug = str_replace('category-', '', $page_id);

    $category = get_category_by_slug($category_slug);

    if ($category) {

      return get_category_link($category->term_id);

    }

    return home_url('/');

  }

  

  if (strpos($page_id, 'tax-') === 0) {

    preg_match('/tax-(\S+)\s+term-(.+)/', $page_id, $matches);

    if (count($matches) === 3) {

      $taxonomy = $matches[1];

      $term_slug = $matches[2];

      $term = get_term_by('slug', $term_slug, $taxonomy);

      if ($term) {

        return get_term_link($term->term_id, $taxonomy);

      }

    }

    return home_url('/');

  }

  

  if (strpos($page_id, 'term-') === 0) {

    $term_slug = str_replace('term-', '', $page_id);

    $term = get_term_by('slug', $term_slug, 'category');

    if (!$term) {

      $taxonomies = get_taxonomies(['public' => true]);

      foreach ($taxonomies as $taxonomy) {

        $term = get_term_by('slug', $term_slug, $taxonomy);

        if ($term) break;

      }

    }

    if ($term) {

      return get_term_link($term);

    }

    return home_url('/');

  }

  

  if (strpos($page_id, 'author-') === 0) {

    $author_login = str_replace('author-', '', $page_id);

    $author = get_user_by('login', $author_login);

    if ($author) {

      return get_author_posts_url($author->ID);

    }

    return home_url('/');

  }

  

  $url = get_permalink($page_id);

  return $url ? $url : '/?p=' . $page_id;

}



/**

 * Resolve archive page identifier to human-readable title

 * 

 * @param string|int $page_id Page ID or archive identifier

 * @return string Human-readable page title

 */

function abst_resolve_page_title($page_id) {

  // Handle numeric IDs (int or numeric string)

  if (is_numeric($page_id)) {

    return get_the_title((int)$page_id);

  }

  

  if (strpos($page_id, 'post-type-archive-') === 0) {

    $post_type = str_replace('post-type-archive-', '', $page_id);

    $post_type_obj = get_post_type_object($post_type);

    return $post_type_obj ? $post_type_obj->labels->name . ' Archive' : $page_id;

  }

  

  if (strpos($page_id, 'category-') === 0) {

    $category_slug = str_replace('category-', '', $page_id);

    $category = get_category_by_slug($category_slug);

    return $category ? $category->name . ' Category' : $page_id;

  }

  

  if (strpos($page_id, 'tax-') === 0) {

    preg_match('/tax-(\S+)\s+term-(.+)/', $page_id, $matches);

    if (count($matches) === 3) {

      $taxonomy = $matches[1];

      $term_slug = $matches[2];

      $term = get_term_by('slug', $term_slug, $taxonomy);

      return $term ? $term->name . ' (' . $taxonomy . ')' : $page_id;

    }

    return $page_id;

  }

  

  if (strpos($page_id, 'term-') === 0) {

    $term_slug = str_replace('term-', '', $page_id);

    $term = get_term_by('slug', $term_slug, 'category');

    if (!$term) {

      $taxonomies = get_taxonomies(['public' => true]);

      foreach ($taxonomies as $taxonomy) {

        $term = get_term_by('slug', $term_slug, $taxonomy);

        if ($term) break;

      }

    }

    return $term ? $term->name : $page_id;

  }

  

  if (strpos($page_id, 'author-') === 0) {

    $author_login = str_replace('author-', '', $page_id);

    $author = get_user_by('login', $author_login);

    return $author ? 'Author: ' . $author->display_name : $page_id;

  }

  

  return get_the_title($page_id);

}



function abst_heatmaps_page_content() {



  echo '<div class="wrap abst-heatmaps-wrap">';

  echo '<div class="abst-page-header">';

  echo '<h1>'.BT_AB_TEST_WL_ABTEST.' Heatmaps & Scrollmaps</h1>';

  echo '<span class="abst-beta-badge">Beta <a href="https://absplittest.com/support" target="_blank">Report issues</a></span>';

  echo '</div>';

  // Read all query parameters

  // Handle both numeric post IDs and string identifiers (post-type-archive-product, category-news, etc.)

  $selected_post = isset($_GET['post']) ? sanitize_text_field($_GET['post']) : 0;

  // Convert to int only if it's a numeric string

  if (is_numeric($selected_post)) {

    $selected_post = intval($selected_post);

  }

  // Lite: only one heatmap page, auto-select it so the heatmap loads immediately
  if (empty($selected_post)) {
    $saved_pages = abst_get_admin_setting('abst_heatmap_pages');
    if (!empty($saved_pages) && is_array($saved_pages)) {
      $selected_post = intval($saved_pages[0]);
    } else {
      $front_page_id = get_option('page_on_front');
      if ($front_page_id) {
        $selected_post = intval($front_page_id);
      }
    }
  }



  $selected_eid = isset($_GET['eid']) ? intval($_GET['eid']) : 0;

  $selected_variation = isset($_GET['variation']) ? sanitize_text_field($_GET['variation']) : '';

  $selected_size = isset($_GET['size']) ? sanitize_text_field($_GET['size']) : 'large';

  $selected_mode = isset($_GET['mode']) ? sanitize_text_field($_GET['mode']) : 'clicks';

  $selected_days = '3';

  $show_conversion_traffic_only = isset($_GET['cto']) ? sanitize_text_field($_GET['cto']) : '0';

  $selected_referrer = isset($_GET['referrer']) ? sanitize_text_field($_GET['referrer']) : '';

  $selected_utm_source = isset($_GET['utm_source']) ? sanitize_text_field($_GET['utm_source']) : '';

  $selected_utm_medium = isset($_GET['utm_medium']) ? sanitize_text_field($_GET['utm_medium']) : '';

  $selected_utm_campaign = isset($_GET['utm_campaign']) ? sanitize_text_field($_GET['utm_campaign']) : '';



  // Lite: heatmap trial removed - heatmaps are free with limits



  // Auto-select first variation if experiment is selected but no variation specified



  // Page selector (static for lite version)

  $heatmap_page_title = 'Homepage';

  $saved_pages = abst_get_admin_setting('abst_heatmap_pages');

  if (!empty($saved_pages) && is_array($saved_pages)) {

    $title = get_the_title(intval($saved_pages[0]));

    if ($title) {

      $heatmap_page_title = $title;

    }

  }

  

  echo '<div class="abst-heatmaps-filters" style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 20px;">';

  

  // Left column

  echo '<div>';

  echo '<div class="abst-filter-group" style="margin-bottom: 15px;"><label>Page</label><div class="abst-filter-row" style="display: flex; align-items: center;">';

  echo '<span style="margin-right: 10px;">' . esc_html($heatmap_page_title) . '</span>';

  $settings_page_slug = class_exists('BT_BB_AB_Admin') ? BT_BB_AB_Admin::$page_slug : 'bt_bb_ab_test';
  echo '<a href="' . esc_url(admin_url('options-general.php?page=' . $settings_page_slug . '#heatmaps')) . '" class="button button-secondary">Change page</a>';

  echo '</div></div>';

  


  echo '</div>';

  

  // Right column

  echo '<div>';

  echo '<div class="abst-filter-group" style="margin-bottom: 15px;"><label>Screen Size</label><div class="abst-filter-row"><select id="abst-heatmaps-size-selector">';

  echo '<option value="">Select a screen size</option>';

  echo '<option value="small"' . ($selected_size === 'small' ? ' selected' : '') . '>Small Screen</option>';

  echo '<option value="medium"' . ($selected_size === 'medium' ? ' selected' : '') . '>Medium Screen</option>';

  echo '<option value="large"' . ($selected_size === 'large' ? ' selected' : '') . '>Large Screen</option>';

  echo '</select></div></div>';

  


  echo '</div>';

  

  echo '</div>'; // Close grid

  

  if ($selected_post) {

    // Display filter context

    $page_title = abst_resolve_page_title($selected_post);

    $context_parts = [

      'page' => $page_title,

      'size' => '',

      'eid' => '',

      'variation' => ''

    ];

    $filters = [];



    

    if ($selected_size) {

      $context_parts['size'] = ucfirst($selected_size) . ' Screen';

      $filters['size'] = $selected_size;

    }



    if ($selected_eid) {

      $context_parts['eid'] = get_the_title($selected_eid);

      $filters['eid'] = $selected_eid;

      

      if ($selected_variation) {

        $filters['variation'] = $selected_variation;

        $var_name = abst_resolve_variation_name($selected_eid, $selected_variation);

        $context_parts['variation'] = $var_name;

      }

    }

    

    

    

    $screenWidth = $selected_size;

    $screenSize = $screenWidth;

    if($screenWidth == 'small')

      $screenWidth = 400;

    else if($screenWidth == 'medium')

      $screenWidth = 800;

    else if($screenWidth == 'large')

      $screenWidth = 1200;

    else

      $screenWidth = 1200;



    $filters['cto'] = $show_conversion_traffic_only;

    $filters['screen'] = $screenSize;

    $filters['mode'] = $selected_mode;

    $filters['days'] = $selected_days;

    $filters['referrer'] = $selected_referrer;

    $filters['utm_source'] = $selected_utm_source;

    $filters['utm_medium'] = $selected_utm_medium;

    $filters['utm_campaign'] = $selected_utm_campaign;



    $data = [];

    $scroll_distribution = [];

    $scroll_total_sessions = 0;



    if ($selected_mode === 'scroll') {

      $scroll_result = abst_get_scroll_data($selected_post, $filters);

      $experiments = isset($scroll_result['experiments']) ? $scroll_result['experiments'] : [];

      $variations = !empty($experiments) ? $experiments : [];

      $scroll_distribution = isset($scroll_result['distribution']) ? $scroll_result['distribution'] : [];

      $scroll_total_sessions = isset($scroll_result['totalSessions']) ? (int) $scroll_result['totalSessions'] : 0;

    } else {

      $journey_logs = abst_search_all_journey_logs($selected_post, $filters);

      $journeyData = $journey_logs['logs'];

      $experiments = $journey_logs['experiments'];

      $foundReferrers = isset($journey_logs['referrers']) ? $journey_logs['referrers'] : [];

      $foundUtmSources = isset($journey_logs['utm_sources']) ? $journey_logs['utm_sources'] : [];

      $foundUtmMediums = isset($journey_logs['utm_mediums']) ? $journey_logs['utm_mediums'] : [];

      $foundUtmCampaigns = isset($journey_logs['utm_campaigns']) ? $journey_logs['utm_campaigns'] : [];



      // Initialize arrays

      $variations = !empty($experiments) ? $experiments : [];



      foreach ($journeyData as $journey_log) {

        $event_type = isset($journey_log[1]) ? strtolower(trim($journey_log[1])) : '';



        //if its meta get info for filtering

        if ($event_type === 'meta') {

          $meta = isset($journey_log[2]) ? trim($journey_log[2]) : '';

          $meta = explode('|', $meta);

          $eid = $meta[0];

          $variation = $meta[1];

          $variations[$eid][$variation] = true;

        }



        // Filter based on selected mode

        if ($selected_mode === 'rage') {

          // Rage mode: only show rage clicks (rc)

          if ($event_type !== 'rc') {

            continue;

          }

        } else if ($selected_mode === 'dead') {

          // Dead click mode: only show normal clicks with 'dead_click' meta

          if ($event_type !== 'c') {

            continue;

          }

          $meta = isset($journey_log[9]) ? trim($journey_log[9]) : '';

          if ($meta !== 'dead_click') {

            continue;

          }

        } else {

          // Normal click modes: show all clicks not rage clicks

          if ($event_type !== 'c') {

            continue; //skip non-click events

          }

        }



        $selector = isset($journey_log[5]) ? trim($journey_log[5]) : '';

        $raw_percent_x = $journey_log[6] ?? null;

        $raw_percent_y = $journey_log[7] ?? null;



        $percent_x = is_numeric($raw_percent_x) ? max(0, min(1, (float) $raw_percent_x)) : null;

        $percent_y = is_numeric($raw_percent_y) ? max(0, min(1, (float) $raw_percent_y)) : null;



        if ($selector === '' || $percent_x === null || $percent_y === null) {

          continue;

        }



        $data[] = [

          'selector' => $selector,

          'percentX' => round($percent_x, 4),

          'percentY' => round($percent_y, 4),

          'value' => 1,

        ];

      }

    }





    













      // Experiment selector (only show if page is selected)

  if($selected_post && !empty($variations)) {

    echo '<div class="abst-filter-group"><label>Test</label><div class="abst-filter-row"><select id="abst-heatmaps-experiment-selector">';

    echo '<option value="">No Test Filter</option>';

    // $variations is an array with experiment IDs as keys

    foreach ($variations as $eid => $variation_list) {

      // Get the actual experiment post

      $experiment_post = get_post($eid);

      if (!$experiment_post) continue;

      

      $selected_attr = ($selected_eid == $eid) ? 'selected' : '';

      echo '<option value="' . esc_attr($eid) . '" ' . esc_attr($selected_attr) . '>' . esc_html($experiment_post->post_title) . '</option>';

    }

    echo '</select>';

    

    // Variation selector (only show if experiment is selected)

    if($selected_eid && isset($variations[$selected_eid])) {

      echo '<select id="abst-heatmaps-variation-selector">';

      

      $experiment_variations = $variations[$selected_eid];

      if (!empty($experiment_variations) && is_array($experiment_variations)) {

        foreach ($experiment_variations as $var_name => $count) {

          // Resolve variation name (handles numeric post IDs for full-page tests)

          $display_name = abst_resolve_variation_name($selected_eid, $var_name);

          $selected_attr = ($selected_variation === $var_name) ? 'selected' : '';

          echo '<option value="' . esc_attr($var_name) . '" ' . esc_attr($selected_attr) . '>' . esc_html($display_name) . '</option>';

        }

      }

      echo '</select>';

    }

    echo '</div></div>';

  }



  // Traffic source filters (referrer + UTM) - show whenever a page is selected

  if ($selected_post) {

    echo '<div class="abst-filter-group"><label>Traffic Source</label><div class="abst-filter-row">';

    echo '<select id="abst-heatmaps-referrer-selector">';

    echo '<option value="">All Referrers</option>';

    $dynamic_referrers = isset($foundReferrers) ? $foundReferrers : [];

    if (!empty($dynamic_referrers)) {

      foreach ($dynamic_referrers as $ref_domain => $ref_count) {

        $selected_attr = ($selected_referrer === $ref_domain) ? ' selected' : '';

        echo '<option value="' . esc_attr($ref_domain) . '"' . esc_attr($selected_attr) . '>' . esc_html($ref_domain) . ' (' . intval($ref_count) . ')</option>';

      }

    }

    echo '</select>';



    // UTM Source dropdown

    echo '<select id="abst-heatmaps-utm-source">';

    echo '<option value="">All Sources</option>';

    $dynamic_utm_sources = isset($foundUtmSources) ? $foundUtmSources : [];

    foreach ($dynamic_utm_sources as $val => $cnt) {

      $selected_attr = ($selected_utm_source === $val) ? ' selected' : '';

      echo '<option value="' . esc_attr($val) . '"' . esc_attr($selected_attr) . '>' . esc_html($val) . ' (' . intval($cnt) . ')</option>';

    }

    echo '</select>';



    // UTM Medium dropdown

    echo '<select id="abst-heatmaps-utm-medium">';

    echo '<option value="">All Mediums</option>';

    $dynamic_utm_mediums = isset($foundUtmMediums) ? $foundUtmMediums : [];

    foreach ($dynamic_utm_mediums as $val => $cnt) {

      $selected_attr = ($selected_utm_medium === $val) ? ' selected' : '';

      echo '<option value="' . esc_attr($val) . '"' . esc_attr($selected_attr) . '>' . esc_html($val) . ' (' . intval($cnt) . ')</option>';

    }

    echo '</select>';



    // UTM Campaign dropdown

    echo '<select id="abst-heatmaps-utm-campaign">';

    echo '<option value="">All Campaigns</option>';

    $dynamic_utm_campaigns = isset($foundUtmCampaigns) ? $foundUtmCampaigns : [];

    foreach ($dynamic_utm_campaigns as $val => $cnt) {

      $selected_attr = ($selected_utm_campaign === $val) ? ' selected' : '';

      echo '<option value="' . esc_attr($val) . '"' . esc_attr($selected_attr) . '>' . esc_html($val) . ' (' . intval($cnt) . ')</option>';

    }

    echo '</select>';



    echo '</div></div>';

  }

  

  echo '</div>'; // Close abst-heatmaps-filters









    $has_scroll_data = ($selected_mode === 'scroll' && !empty($scroll_distribution));

    $has_click_data = ($selected_mode !== 'scroll' && !empty($data));

    $has_rage_data = ($selected_mode === 'rage' && !empty($data));

    $has_dead_data = ($selected_mode === 'dead' && !empty($data));

    if($selected_mode === 'scroll'){

      $modeNice = 'Scroll Map';

    }else if($selected_mode === 'clicks'){

      $modeNice = 'Heatmap';

    }else if($selected_mode === 'rage'){

      $modeNice = 'Rage Click Map';

    }else if($selected_mode === 'dead'){

      $modeNice = 'Dead Click Map';

    }else{

      $modeNice = 'Click Map';

    }

    



    if (!$has_scroll_data && !$has_click_data && !$has_rage_data && !$has_dead_data) {

      //if its rage show rage map

      if($selected_mode === 'rage'){

        echo '<p><strong>No rage detected</strong> - how very zen.</p>';

      }

      else if($selected_mode === 'dead'){

        echo '<p><strong>No dead clicks detected</strong> - meeting requirements.</p>';

      }

      else{

        // Check settings dynamically

        $journeys_enabled = !empty(abst_get_admin_setting('abst_enable_user_journeys'));

        

        // heatmap_all_pages is stored as 'all' or 'chosen', not boolean

        $heatmap_all_pages_setting = abst_get_admin_setting('abst_heatmap_all_pages');

        $heatmap_all_pages = ($heatmap_all_pages_setting === 'all' || empty($heatmap_all_pages_setting));

        

        $heatmap_pages = abst_get_admin_setting('abst_heatmap_pages');

        if (!is_array($heatmap_pages)) $heatmap_pages = array();

        $page_in_list = in_array($selected_post, $heatmap_pages) || in_array((string)$selected_post, $heatmap_pages) || in_array((int)$selected_post, $heatmap_pages);

        $page_tracked = $heatmap_all_pages || $page_in_list;

        

        echo '<div class="abst-empty-state" style="text-align:left; max-width:600px;">';

        echo '<h3 style="margin-top:0;">No data for this selection</h3>';

        echo '<p style="color:#64748b; margin-bottom:16px;">Data typically appears within 1 minute of a visitor viewing your page.</p>';

        

        // Check for issues

        $has_issues = !$journeys_enabled || !$page_tracked;

        

        if ($has_issues) {

          echo '<p style="margin-bottom:12px; color:#dc2626;"><strong>⚠️ Issues detected:</strong></p>';

          echo '<ul style="color:#475569; margin:0 0 16px 20px; line-height:1.8;">';

          

          if (!$journeys_enabled) {

            echo '<li style="color:#dc2626;">❌ Heatmaps & Journeys is <strong>disabled</strong> - <a href="' . esc_url(admin_url('edit.php?post_type=bt_experiments&page=bt_bb_ab_admin#heatmaps')) . '">Enable it in Settings</a></li>';

          } else {

            echo '<li style="color:#16a34a;">✓ Heatmaps & Journeys is enabled</li>';

          }

          

          if (!$page_tracked) {

            echo '<li style="color:#dc2626;">❌ This page is <strong>not being tracked</strong> - add it to your tracked pages or enable "All Pages"</li>';

          } else {

            echo '<li style="color:#16a34a;">✓ This page is being tracked' . ($heatmap_all_pages ? ' (All Pages enabled)' : '') . '</li>';

          }

          

          echo '</ul>';

        } else {

          echo '<p style="margin-bottom:12px; color:#16a34a;"><strong>✓ Settings look good!</strong></p>';

          echo '<p style="margin-bottom:8px;"><strong>Other things to check:</strong></p>';

          echo '<ul style="color:#475569; margin:0 0 16px 20px; line-height:1.8;">';

          echo '<li>Clear all caches - your site may not have updated since changing settings</li>';

          echo '<li>A real visitor (not you as admin) has viewed the page</li>';

          echo '<li>The visitor\'s browser allows JavaScript and isn\'t blocking tracking</li>';

          echo '<li>If filtering by test/variation, ensure that test is active</li>';

          echo '</ul>';

        }

        

        echo '<p style="font-size:13px; color:#64748b;"><a href="' . esc_url(admin_url('edit.php?post_type=bt_experiments&page=bt_bb_ab_test#heatmaps')) . '">Check your Settings →</a></p>';

        echo '</div>';

      }

    } else {

      // Build context strings

      $context_eid = !empty($context_parts['eid']) ? ' <span class="abst-context-sep">›</span> Test: ' . esc_html($context_parts['eid']) : '';

      $context_variation = !empty($context_parts['variation']) ? ' <span class="abst-context-sep">›</span> Variation: ' . esc_html($context_parts['variation']) : '';

      

      // Build description based on mode

      $description = '';

      if ($selected_mode === 'scroll') {

        $session_label = $scroll_total_sessions === 1 ? 'session' : 'sessions';

        $days_label = $selected_days == '365' ? 'all time' : 'the last ' . $selected_days . ' days';

        $description = 'Scroll map covers <strong>' . intval($scroll_total_sessions) . '</strong> ' . $session_label . ' from ' . $days_label . '. Each band shows the share of visitors who reached that depth.';

      } elseif ($selected_mode === 'clicks') {

        $click_count = count($data);

        $click_label = $click_count === 1 ? 'click' : 'clicks';

        $days_label = $selected_days == '365' ? 'all time' : 'the last ' . $selected_days . ' days';

        $description = 'Click heatmap shows <strong>' . intval($click_count) . '</strong> ' . $click_label . ' from ' . $days_label . '. Warmer colors indicate areas with more clicks.';

      } elseif ($selected_mode === 'confetti') {

        $click_count = count($data);

        $click_label = $click_count === 1 ? 'click' : 'clicks';

        $days_label = $selected_days == '365' ? 'all time' : 'the last ' . $selected_days . ' days';

        $description = 'Confetti map shows <strong>' . intval($click_count) . '</strong> individual ' . $click_label . ' from ' . $days_label . '. Each dot represents one visitor interaction.';

      } elseif ($selected_mode === 'rage') {

        $rage_count = count($data);

        $rage_label = $rage_count === 1 ? 'rage click' : 'rage clicks';

        $days_label = $selected_days == '365' ? 'all time' : 'the last ' . $selected_days . ' days';

        $description = 'Rage map shows <strong>' . intval($rage_count) . '</strong> ' . $rage_label . ' from ' . $days_label . '. Red areas indicate where users clicked repeatedly in frustration.';

      } elseif ($selected_mode === 'dead') {

        $dead_count = count($data);

        $dead_label = $dead_count === 1 ? 'dead click' : 'dead clicks';

        $days_label = $selected_days == '365' ? 'all time' : 'the last ' . $selected_days . ' days';

        $description = 'Dead click map shows <strong>' . intval($dead_count) . '</strong> ' . $dead_label . ' from ' . $days_label . '. Orange areas show clicks on non-interactive elements.';

      }

      

      

      // Build iframe URL with proper preview parameters

      $iframe_url = abst_resolve_page_url($selected_post);

      

      // Add heatmap view parameter

      $iframe_url = add_query_arg('abst_heatmap_view', '1', $iframe_url);

      

      // If experiment and variation are selected, use preview URL format

      if ($selected_eid && $selected_variation) {

        $iframe_url = add_query_arg('abtid', $selected_eid, $iframe_url);

        $iframe_url = add_query_arg('abtv', $selected_variation, $iframe_url);

      }



      // Context and description outside preview

      echo '<div class="abst-heatmap-context"><div><span class="abst-context-icon">📊</span><strong>'. esc_html($modeNice) . ':</strong> ' . esc_html($context_parts['page']) . ' <span class="abst-context-sep">›</span> Size: '  . esc_html($context_parts['size']) . wp_kses_post($context_eid) . wp_kses_post($context_variation) . '</div>';

      echo '<p class="abst-heatmap-description">' . wp_kses_post($description) . '</p>';
      echo '</div>';

      

      // Add legend for scroll mode

      if ($selected_mode === 'scroll') {

        echo '<div class="abst-scroll-legend" style="position: sticky; top: 40px; z-index: 1000; width: '.intval($screenWidth).'px; max-width:90%; margin: 20px auto 10px; padding: 20px 10px; background: #f5f5f5; border-radius: 8px; display: flex; align-items: center; gap: 15px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">';

        echo '<div style="font-weight: 600; color: #333; font-size: 14px;">Scroll Depth:</div>';

        echo '<div style="flex: 1; height: 40px; background: linear-gradient(to right, #6B8DD6 0%, #4FC3DC 25%, #5FD38D 50%, #F9E65C 75%, #FF9A56 87.5%, #FF6B6B 100%); border-radius: 4px; position: relative; box-shadow: inset 0 1px 3px rgba(0,0,0,0.1);">';

        echo '<div style="position: absolute; left: 0; top: -16px; font-size: 11px; color: #666;">100%<br><span style="font-size: 9px; color: black; padding:3px;">Always seen</span></div>';

        echo '<div style="position: absolute; left: 25%; top: -16px; font-size: 11px; color: #666; transform: translateX(-50%);">75%</div>';

        echo '<div style="position: absolute; left: 50%; top: -16px; font-size: 11px; color: #666; transform: translateX(-50%);">50%</div>';

        echo '<div style="position: absolute; left: 75%; top: -16px; font-size: 11px; color: #666; transform: translateX(-50%);">25%</div>';

        echo '<div style="position: absolute; right: 0; top: -16px; font-size: 11px; color: #666; text-align: right;">0%<br><span style="font-size: 9px; color: black; padding:3px;">Almost never seen</span></div>';

        echo '</div>';

        echo '</div>';

      }

      else

        echo '<p class="previewwiderthan">This preview is wider than your viewport. Don\'t forget to scroll ← →</p>';

      

      echo '<div class="abst-heatmap-toolbar" style="max-width: '.intval($screenWidth).'px; width:calc(100% - 20px);">';

      echo '<button id="abst-rerender-btn" class="abst-rerender-floating-btn" type="button" title="Re-render heatmap after resizing or animated elements move">&#8635; Re-render</button>';

      echo '<button id="abst-rerender-auto" class="abst-rerender-auto-label" type="button" aria-pressed="false" title="Automatically re-render after scrolling stops or the window is resized"><span class="abst-rerender-auto-box" aria-hidden="true"></span><span>Auto</span></button>';

      echo '</div>';

      echo '<div class="abst-heatmap-wrapper" style="position: relative; max-width: '.intval($screenWidth).'px; width:calc(100% - 20px); margin: 0 auto 40px; border: 10px solid #d9d9d9; box-shadow: 0 1px 10px -4px black;">';

      echo '<iframe id="abst-heatmaps-iframe" src="' . esc_url($iframe_url) . '" style="display: block; position: absolute; top: 0; left: 0; width: 100%; height: 100%; border: 0; z-index: 0; pointer-events: none;"></iframe>';

      echo '<div id="heatmap-container" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; z-index: 1; pointer-events: none;"></div>';

      echo '</div>';

    }

    echo '<script>';

    echo 'window.heatmapRecords = ' . wp_json_encode($data) . ';';

    // Aggregate click counts per selector for hover tooltip

    $click_counts = [];

    if (!empty($data) && is_array($data)) {

      foreach ($data as $point) {

        if (!empty($point['selector'])) {

          $sel = $point['selector'];

          $click_counts[$sel] = isset($click_counts[$sel]) ? $click_counts[$sel] + 1 : 1;

        }

      }

    }

    echo 'window.heatmapClickCounts = ' . wp_json_encode($click_counts) . ';';

    echo 'window.variations = ' . wp_json_encode($variations) . ';';

    echo 'window.scrollMap = ' . wp_json_encode([

      'distribution' => $scroll_distribution,

      'totalSessions' => $scroll_total_sessions,

      'bucketSize' => 5,

    ]) . ';';

    echo 'window.abstHeatmapMode = ' . wp_json_encode($selected_mode) . ';';

    echo '

    (function() {

      function checkPreviewWidth() {

        var wrapper = document.querySelector(".abst-heatmap-wrapper");

        var notice = document.querySelector(".previewwiderthan");

        if (wrapper && notice) {

          var parent = wrapper.parentElement;

          if (wrapper.offsetWidth > parent.offsetWidth) {

            notice.style.display = "block";

            document.body.style.overflowX = "scroll";

          } else {

            notice.style.display = "none";

            document.body.style.overflowX = "";

          }

        }

      }

      document.addEventListener("DOMContentLoaded", checkPreviewWidth);

      window.addEventListener("resize", checkPreviewWidth);

    })();

    ';

    echo '</script>';

  } else {

    echo '<div class="abst-empty-state" style="text-align:left; max-width:600px;">';

    echo '<h3 style="margin-top:0;">Choose a page to get started</h3>';

    echo '<p style="color:#64748b; margin-bottom:16px;">Select a page from the dropdown above to view its heatmap, scroll map, or click data.</p>';

    echo '</div>';

  }



  echo '</div>';

}



function abst_read_journey_file_lines($journey_file) {

  $is_gzip = substr($journey_file, -3) === '.gz';



  if ($is_gzip) {

    if (!function_exists('gzdecode')) {

      return [];

    }



    $raw_contents = @file_get_contents($journey_file);

    if ($raw_contents === false) {

      return [];

    }



    $decoded = @gzdecode($raw_contents);

    if ($decoded === false) {

      return [];

    }



    $content = $decoded;

  } else {

    $content = @file_get_contents($journey_file);

    if ($content === false) {

      return [];

    }

  }



  $lines = preg_split('/\r\n|\r|\n/', $content);

  if ($lines === false) {

    return [];

  }



  return array_values(array_filter(array_map('trim', $lines), 'strlen'));

}



function abst_get_scroll_data($post_id, $filters) {

  $journey_files = glob(ABST_JOURNEY_DIR . '/*.txt');

  $journey_files_gz = glob(ABST_JOURNEY_DIR . '/*.gz');



  $all_files = array_merge($journey_files ?: [], $journey_files_gz ?: []);

  rsort($all_files);



  $days = 7;

  // Keep post_id as-is to support archive pages (post-type-archive-product, category-news, etc.)

  // Only convert to int if it's numeric

  if (is_numeric($post_id)) {

    $post_id = (int) $post_id;

  }

  $scrollEvents = [];

  $foundExperiments = [];

  $cutoff = time() - ($days * DAY_IN_SECONDS);



  $screen_map = ['small' => 's', 'medium' => 'm', 'large' => 'l'];

  $filter_screen = !empty($filters['screen']) ? ($screen_map[$filters['screen']] ?? $filters['screen']) : '';

  $filter_eid = !empty($filters['eid']) ? (string) $filters['eid'] : '';

  $filter_variation = !empty($filters['variation']) ? (string) $filters['variation'] : '';



  foreach ($all_files as $journey_file) {

    $basename = basename($journey_file);

    if (!preg_match('/(\d{8})/', $basename, $matches)) {

      continue;

    }



    $file_date = strtotime($matches[1]);

    if ($file_date === false || $file_date < $cutoff) {

      continue;

    }



    $lines = abst_read_journey_file_lines($journey_file);

    if (empty($lines)) {

      continue;

    }



    $current_metadata = null;

    $metadata_matches_filter = false;



    foreach ($lines as $line) {

      $parts = array_map('trim', explode('|', $line));

      if (empty($parts[0])) {

        continue;

      }



      if ($parts[0] === 'meta') {

        $scroll_depth = '';

        if (isset($parts[5]) && $parts[5] !== '') {

          $candidate = floatval($parts[5]);

          if (is_finite($candidate)) {

            $candidate = max(0, min(100, $candidate));

            $scroll_depth = (string) round($candidate);

          }

        }



        $meta_experiments = [];

        if (!empty($parts[2])) {

          $raw_meta_experiments = array_map('trim', explode(',', $parts[2]));

          foreach ($raw_meta_experiments as $experiment) {

            if ($experiment === '') {

              continue;

            }

            $exp_parts = explode(':', $experiment);

            if (count($exp_parts) >= 2) {

              $eid = $exp_parts[0];

              $variation = $exp_parts[1];

              if (!isset($foundExperiments[$eid])) {

                $foundExperiments[$eid] = [];

              }

              $foundExperiments[$eid][$variation] = true;

              $meta_experiments[] = [

                'eid' => $eid,

                'variation' => $variation,

              ];

            }

          }

        }



        $metadata_matches_filter = ($scroll_depth !== '');



        if ($metadata_matches_filter && $filter_screen !== '') {

          $meta_screen = isset($parts[3]) ? (string) $parts[3] : '';

          if ($meta_screen !== $filter_screen) {

            $metadata_matches_filter = false;

          }

        }



        if ($metadata_matches_filter && $filter_eid !== '') {

          $has_matching_experiment = false;

          foreach ($meta_experiments as $meta_experiment) {

            if ((string) $meta_experiment['eid'] !== $filter_eid) {

              continue;

            }

            if ($filter_variation === '' || (string) $meta_experiment['variation'] === $filter_variation) {

              $has_matching_experiment = true;

              break;

            }

          }



          if (!$has_matching_experiment) {

            $metadata_matches_filter = false;

          }

        }



        // Filter by referrer domain if specified

        if ($metadata_matches_filter && !empty($filters['referrer'])) {

          $meta_referrer = isset($parts[6]) ? $parts[6] : '';

          $ref_host = wp_parse_url($meta_referrer, PHP_URL_HOST);

          if (!$ref_host || stripos($ref_host, $filters['referrer']) === false) {

            $metadata_matches_filter = false;

          }

        }



        $current_metadata = [

          'scroll_depth' => $scroll_depth,

        ];

        continue;

      }



      if (!$metadata_matches_filter) {

        continue;

      }



      if ($current_metadata === null || $current_metadata['scroll_depth'] === '') {

        continue;

      }



      // Support both numeric IDs and string identifiers (archive pages)

      $event_post_id = isset($parts[2]) ? (is_numeric($parts[2]) ? intval($parts[2]) : trim($parts[2])) : 0;

      if ($event_post_id !== $post_id) {

        continue;

      }



      $scroll_value = floatval($current_metadata['scroll_depth']);

      if (!is_finite($scroll_value)) {

        $current_metadata['scroll_depth'] = '';

        continue;

      }



      $scroll_value = max(0, min(100, $scroll_value));

      $scrollEvents[] = (int) round($scroll_value);

      $current_metadata['scroll_depth'] = '';

    }

  }



  if (empty($scrollEvents)) {

    return [

      'distribution' => [],

      'totalSessions' => 0,

      'experiments' => $foundExperiments,

    ];

  }



  $gradientMap = array_count_values($scrollEvents);

  ksort($gradientMap);



  $totalSessions = array_sum($gradientMap);

  $cumulative = [];

  $running = 0;

  for ($depth = 100; $depth >= 0; $depth--) {

    if (isset($gradientMap[$depth])) {

      $running += $gradientMap[$depth];

    }

    $cumulative[$depth] = $running;

  }



  $bucket_size = 5;

  $distribution = [];

  for ($start = 0; $start < 100; $start += $bucket_size) {

    $end = min(100, $start + $bucket_size);

    $reached = $cumulative[$start] ?? 0;

    $percent = ($totalSessions > 0) ? round($reached / $totalSessions, 4) : 0;

    $distribution[] = [

      'start' => $start,

      'end' => $end,

      'percent' => $percent,

      'count' => $reached,

    ];

  }



  return [

    'distribution' => $distribution,

    'totalSessions' => $totalSessions,

    'experiments' => $foundExperiments,

  ];

}



function abst_logs_by_screen_size($screenWidth,$journey_logs) {

  $return_object = [];

  if($screenWidth == 'small')

    $screenWidth = 's';

  else if($screenWidth == 'medium')

    $screenWidth = 'm';

  else

    $screenWidth = 'l';



  //start at the oldest logs

  abst_log('before filter by screen size ' . $screenWidth . " " . sizeof($journey_logs));

  foreach ($journey_logs as $journey_log) {

    $screen_size = $journey_log[8]; //s,m,l 

    //change to size

    if($screen_size == $screenWidth) { // if the width matches

      $return_object[] = $journey_log; // add to return object

    }

  }

  abst_log('after filter by screen size ' . $screenWidth . " " . sizeof($return_object));

  return $return_object;

}



/**

 * Resolve variation name - if numeric and full-page test, get post title

 * 

 * @param int $eid Experiment ID

 * @param string $variation Variation identifier (e.g., "magic-1" or "123")

 * @return string Resolved variation name

 */

function abst_resolve_variation_name($eid, $variation) {

  // If variation is numeric, it's likely a post ID (full-page test)

  if (is_numeric($variation)) {

    $post_id = intval($variation);

    $post_title = get_the_title($post_id);

    

    if ($post_title && $post_title !== '') {

      return $post_title . ' (ID: ' . $post_id . ')';

    }

  }

  

  // Otherwise return as-is (e.g., "magic-1", "magic-0")

  return $variation;

}







function abst_search_all_journey_logs($post_id, $filters = []) {



// attributes will be one of the journey 

  //load all the files in /journey_logs



  //get all txts inside ABST_JOURNEY_DIR

  $journey_files = glob(ABST_JOURNEY_DIR . '/*.txt');

  $journey_files_gz = glob(ABST_JOURNEY_DIR . '/*.gz');



  // Sort newest first (reverse order so 20250109 comes before 20250108)

  rsort($journey_files);

  rsort($journey_files_gz);



  $days = isset($filters['days']) ? intval($filters['days']) : 7; // last 7 days maybe increase to 30?

  $foundExperiments = [];

  $foundReferrers = []; // Track unique referrer domains for dynamic dropdown

  $foundUtmSources = []; // Track unique UTM source values

  $foundUtmMediums = []; // Track unique UTM medium values

  $foundUtmCampaigns = []; // Track unique UTM campaign values

  //loop through files and search for $attributes

  $return_object = [];

  //loop through files and search for $attributes

  // Keep post_id as-is to support archive pages (post-type-archive-product, category-news, etc.)

  // Only convert to int if it's numeric

  if (is_numeric($post_id)) {

    $post_id = (int) $post_id;

  }



  abst_log('searching for ' . $post_id . ' in ' . sizeof($journey_files_gz) . ' files');

  abst_log('Current server date: ' . wp_date('Y-m-d H:i:s') . ' | Cutoff timestamp: ' . (time() - ($days * 24 * 60 * 60)) . ' | Current timestamp: ' . time());



  foreach ($journey_files as $journey_file) {

    // Extract date from filename: abst_journeys_20250109.txt -> 20250109

    $filename = pathinfo($journey_file, PATHINFO_FILENAME);

    $date_str = substr($filename, -8); // Last 8 chars: YYYYMMDD

    

    // Format date string properly: 20250109 -> 2025-01-09

    $formatted_date = substr($date_str, 0, 4) . '-' . substr($date_str, 4, 2) . '-' . substr($date_str, 6, 2);

    $file_date = strtotime($formatted_date);

    

    if ($file_date === false || $file_date < time() - ($days * 24 * 60 * 60)) {// the last x days

      continue;

    }

    $lines = @file($journey_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    if (empty($lines)) {

      continue;

    }



    $current_metadata = null;

    $metadata_matches_filter = false;

    

    foreach ($lines as $line) {

      $parts = array_map('trim', explode('|', $line));

      

      // Check if this is a metadata line

      if (!empty($parts[0]) && $parts[0] === 'meta') {

        // Store metadata: meta|uuid|experiments|screen_size|user_id

        if (count($parts) >= 5) {

          $current_metadata = [

            'uuid' => $parts[1],

            'experiments' => $parts[2],

            'screen_size' => $parts[3],

            'user_id' => $parts[4],

            'scroll_depth' => $parts[5],

            'referrer' => isset($parts[6]) ? $parts[6] : '',

          ];

        }

        

        // Check if this metadata matches our filters

        $metadata_matches_filter = true;

        

        // Filter by screen size if specified 

        if (!empty($filters['screen'])) {

          $screen_map = ['small' => 's', 'medium' => 'm', 'large' => 'l'];

          $filter_screen = $screen_map[$filters['screen']] ?? $filters['screen'];

          if ($current_metadata['screen_size'] !== $filter_screen) {

            $metadata_matches_filter = false;

          }

        }

        

        // Filter by experiment ID and variation if specified

        if (!empty($filters['eid'])) {

          $metaExperiments = explode(',', $current_metadata['experiments']);

          $has_matching_experiment = false;

          

          foreach($metaExperiments as $experiment) {

            $exp_parts = explode(':', $experiment);

            if (count($exp_parts) >= 2) {

              $eid = $exp_parts[0];

              $variation = $exp_parts[1];

              

              // Track all experiments/variations for dropdown

              if (!isset($foundExperiments[$eid])) {

                $foundExperiments[$eid] = [];

              }

              $foundExperiments[$eid][$variation] = true;

              

              // Check if this matches our filter

              if ($eid == $filters['eid']) {

                if (empty($filters['variation']) || $variation === $filters['variation']) {

                  $has_matching_experiment = true;

                }

              }

            }

          }

          

          if (!$has_matching_experiment) {

            $metadata_matches_filter = false;

          }

        } else {

          // No eid filter, just track all experiments

          $metaExperiments = explode(',', $current_metadata['experiments']);

          foreach($metaExperiments as $experiment) {

            $exp_parts = explode(':', $experiment);

            if (count($exp_parts) >= 2) {

              $eid = $exp_parts[0];

              $variation = $exp_parts[1];

              if (!isset($foundExperiments[$eid])) {

                $foundExperiments[$eid] = [];

              }

              $foundExperiments[$eid][$variation] = true;

            }

          }

        }



        $scroll_depth = $current_metadata['scroll_depth'];



        // Track unique referrer domains for dynamic dropdown (before filtering)

        if (!empty($current_metadata['referrer'])) {

          $ref_host = wp_parse_url($current_metadata['referrer'], PHP_URL_HOST);

          if ($ref_host) {

            $foundReferrers[$ref_host] = ($foundReferrers[$ref_host] ?? 0) + 1;

          }

        }



        // Filter by referrer domain if specified

        if ($metadata_matches_filter && !empty($filters['referrer'])) {

          $ref_host = wp_parse_url($current_metadata['referrer'], PHP_URL_HOST);

          if (!$ref_host || stripos($ref_host, $filters['referrer']) === false) {

            $metadata_matches_filter = false;

          }

        }

        

        continue; // Don't add metadata lines to results

      }

      

      // Event line: timestamp|type|post_id|url|element|click_x|click_y|meta

      // Only process if metadata matches filter

      if (count($parts) >= 8 && $current_metadata && $metadata_matches_filter) {

        // Check if this event is for our page

        // Support both numeric IDs and string identifiers (archive pages)

        $event_post_id = is_numeric($parts[2]) ? (int) $parts[2] : trim($parts[2]);

        if ($event_post_id === $post_id) {

          // Parse UTM params from event URL and collect for dynamic dropdowns (before filtering)

          parse_str(ltrim($parts[3], '?'), $query_params);

          if (!empty($query_params['utm_source'])) $foundUtmSources[$query_params['utm_source']] = ($foundUtmSources[$query_params['utm_source']] ?? 0) + 1;

          if (!empty($query_params['utm_medium'])) $foundUtmMediums[$query_params['utm_medium']] = ($foundUtmMediums[$query_params['utm_medium']] ?? 0) + 1;

          if (!empty($query_params['utm_campaign'])) $foundUtmCampaigns[$query_params['utm_campaign']] = ($foundUtmCampaigns[$query_params['utm_campaign']] ?? 0) + 1;



          // Filter by UTM parameters

          if (!empty($filters['utm_source']) && strtolower($query_params['utm_source'] ?? '') !== strtolower($filters['utm_source'])) continue;

          if (!empty($filters['utm_medium']) && strtolower($query_params['utm_medium'] ?? '') !== strtolower($filters['utm_medium'])) continue;

          if (!empty($filters['utm_campaign']) && strtolower($query_params['utm_campaign'] ?? '') !== strtolower($filters['utm_campaign'])) continue;



          // Reconstruct full event with metadata

          $full_event = [

            $parts[0], // timestamp

            $parts[1], // type

            $parts[2], // post_id

            $current_metadata['uuid'], // uuid from metadata

            $parts[3], // url

            $parts[4], // element_id_or_selector

            $parts[5], // click_x

            $parts[6], // click_y

            $current_metadata['screen_size'], // screen_size from metadata

            $parts[7]  // meta

          ];

          $return_object[] = $full_event;

        }

      }

    }

  }



  foreach ($journey_files_gz as $journey_file) {

    // Extract date from filename: abst_journeys_20250109.txt.gz -> 20250109

    $filename = basename($journey_file, '.txt.gz');

    $date_str = substr($filename, -8); // Last 8 chars: YYYYMMDD

    

    // Format date string properly: 20250109 -> 2025-01-09

    $formatted_date = substr($date_str, 0, 4) . '-' . substr($date_str, 4, 2) . '-' . substr($date_str, 6, 2);

    $file_date = strtotime($formatted_date);

    

    if ($file_date === false || $file_date < time() - ($days * 24 * 60 * 60)) { // the last x days

      continue;

    }

    

    //unzip from gz

    if (!function_exists('gzdecode')) {

      abst_log('gzdecode not available');

      continue; // gzdecode not available

    }



    $compressed = @file_get_contents($journey_file);

    if ($compressed === false) {

      abst_log('Failed to read file: ' . $journey_file);

      continue; // Failed to read file

    }



    $unzipped_content = @gzdecode($compressed);

    if ($unzipped_content === false) {

      abst_log('Failed to decompress file: ' . $journey_file);

      continue; // Failed to decompress

    } 

    $lines = explode("\n", $unzipped_content);

    $lines = array_filter(array_map('trim', $lines)); // Remove empty lines

    

    if (empty($lines)) {

      abst_log('No data found in file: ' . $journey_file);

      continue;

    }



    $current_metadata = null;

    $metadata_matches_filter = false;

    

    foreach ($lines as $line) {

      $parts = array_map('trim', explode('|', $line));

      

      // Check if this is a metadata line

      if (!empty($parts[0]) && $parts[0] === 'meta') {

        // Store metadata: meta|uuid|experiments|screen_size|user_id

        if (count($parts) >= 5) {

          $current_metadata = [

            'uuid' => $parts[1],

            'experiments' => $parts[2],

            'screen_size' => $parts[3],

            'user_id' => $parts[4],

            'scroll_depth' => isset($parts[5]) ? $parts[5] : '',

            'referrer' => isset($parts[6]) ? $parts[6] : ''

          ];

        }

        

        // Check if this metadata matches our filters

        $metadata_matches_filter = true;

        

        // Filter by screen size if specified

        if (!empty($filters['screen'])) {

          $screen_map = ['small' => 's', 'medium' => 'm', 'large' => 'l'];

          $filter_screen = $screen_map[$filters['screen']] ?? $filters['screen'];

          if ($current_metadata['screen_size'] !== $filter_screen) {

            $metadata_matches_filter = false;

          }

        }

        

        // Filter by experiment ID and variation if specified

        if (!empty($filters['eid'])) {

          $metaExperiments = explode(',', $current_metadata['experiments']);

          $has_matching_experiment = false;

          

          foreach($metaExperiments as $experiment) {

            $exp_parts = explode(':', $experiment);

            if (count($exp_parts) >= 2) {

              $eid = $exp_parts[0];

              $variation = $exp_parts[1];

              

              // Track all experiments/variations for dropdown

              if (!isset($foundExperiments[$eid])) {

                $foundExperiments[$eid] = [];

              }

              $foundExperiments[$eid][$variation] = true;

              

              // Check if this matches our filter

              if ($eid == $filters['eid']) {

                if (empty($filters['variation']) || $variation === $filters['variation']) {

                  $has_matching_experiment = true;

                }

              }

            }

          }

          

          if (!$has_matching_experiment) {

            $metadata_matches_filter = false;

          }

        } else {

          // No eid filter, just track all experiments

          $metaExperiments = explode(',', $current_metadata['experiments']);

          foreach($metaExperiments as $experiment) {

            $exp_parts = explode(':', $experiment);

            if (count($exp_parts) >= 2) {

              $eid = $exp_parts[0];

              $variation = $exp_parts[1];

              if (!isset($foundExperiments[$eid])) {

                $foundExperiments[$eid] = [];

              }

              $foundExperiments[$eid][$variation] = true;

            }

          }

        }



        // Track unique referrer domains for dynamic dropdown (before filtering)

        if (!empty($current_metadata['referrer'])) {

          $ref_host = wp_parse_url($current_metadata['referrer'], PHP_URL_HOST);

          if ($ref_host) {

            $foundReferrers[$ref_host] = ($foundReferrers[$ref_host] ?? 0) + 1;

          }

        }



        // Filter by referrer domain if specified

        if ($metadata_matches_filter && !empty($filters['referrer'])) {

          $ref_host = wp_parse_url($current_metadata['referrer'], PHP_URL_HOST);

          if (!$ref_host || stripos($ref_host, $filters['referrer']) === false) {

            $metadata_matches_filter = false;

          }

        }

        

        continue; // Don't add metadata lines to results

      }

      

      // Event line: timestamp|type|post_id|url|element|click_x|click_y|meta

      // Only process if metadata matches filter

      if (count($parts) >= 8 && $current_metadata && $metadata_matches_filter) {

        // Check if this event is for our page

        // Support both numeric IDs and string identifiers (archive pages)

        $event_post_id = is_numeric($parts[2]) ? (int) $parts[2] : trim($parts[2]);

        if ($event_post_id === $post_id) {

          // Parse UTM params from event URL and collect for dynamic dropdowns (before filtering)

          parse_str(ltrim($parts[3], '?'), $query_params);

          if (!empty($query_params['utm_source'])) $foundUtmSources[$query_params['utm_source']] = ($foundUtmSources[$query_params['utm_source']] ?? 0) + 1;

          if (!empty($query_params['utm_medium'])) $foundUtmMediums[$query_params['utm_medium']] = ($foundUtmMediums[$query_params['utm_medium']] ?? 0) + 1;

          if (!empty($query_params['utm_campaign'])) $foundUtmCampaigns[$query_params['utm_campaign']] = ($foundUtmCampaigns[$query_params['utm_campaign']] ?? 0) + 1;



          // Filter by UTM parameters

          if (!empty($filters['utm_source']) && strtolower($query_params['utm_source'] ?? '') !== strtolower($filters['utm_source'])) continue;

          if (!empty($filters['utm_medium']) && strtolower($query_params['utm_medium'] ?? '') !== strtolower($filters['utm_medium'])) continue;

          if (!empty($filters['utm_campaign']) && strtolower($query_params['utm_campaign'] ?? '') !== strtolower($filters['utm_campaign'])) continue;



          // Reconstruct full event with metadata

          $full_event = [

            $parts[0], // timestamp

            $parts[1], // type

            $parts[2], // post_id

            $current_metadata['uuid'], // uuid from metadata

            $parts[3], // url

            $parts[4], // element_id_or_selector

            $parts[5], // click_x

            $parts[6], // click_y

            $current_metadata['screen_size'], // screen_size from metadata

            $parts[7]  // meta

          ];

          $return_object[] = $full_event;

        }

      }

    }

  }

  abst_log('returning ' . count($foundExperiments) . ' experiments and ' . count($return_object) . ' events');

  // Sort all dynamic filter arrays by count (most common first)

  arsort($foundReferrers);

  arsort($foundUtmSources);

  arsort($foundUtmMediums);

  arsort($foundUtmCampaigns);

  return [

    'logs' => $return_object,

    'experiments' => $foundExperiments,

    'referrers' => $foundReferrers,

    'utm_sources' => $foundUtmSources,

    'utm_mediums' => $foundUtmMediums,

    'utm_campaigns' => $foundUtmCampaigns,

  ];

}



// Lite: Agency Hub function removed - Pro only feature



function abst_logs_page_content() {

  // Get WordPress uploads directory

  $upload_dir = wp_upload_dir();

  $log_dir = $upload_dir['basedir'];

  $hash = substr(md5(defined('AUTH_KEY') ? AUTH_KEY : 'abst'), 0, 12);
  $log_file = $log_dir . '/abst_log_' . $hash . '.log';

  

  // Get retention settings

  $heatmap_retention = abst_get_admin_setting('abst_heatmap_retention_length') ?: 30;

  

  // Clear logs button

  if (isset($_POST['clear_logs']) && current_user_can('manage_options')) {

    abst_put_contents($log_file, '');

    echo '<div class="notice notice-success"><p>Logs cleared successfully.</p></div>';

  }

  

  echo '<div class="wrap abst-logs-wrap">';

  echo '<div class="abst-page-header">';

  echo '<h1>'.'Split Test'.' Debug Logs</h1>';

  echo '</div>';

  

  echo '<div class="abst-logs-info">';

  echo '<p>Debug logs help troubleshoot issues with your A/B tests. Logs are automatically trimmed to the most recent 500 lines.</p>';

  echo '</div>';

  

  echo '<div class="abst-logs-actions">';

  echo '<form method="post" style="display:inline;">';

  echo '<input type="submit" name="clear_logs" value="Clear Logs" class="button button-secondary" onclick="return confirm(\'Are you sure you want to clear all logs?\');">';

  echo '</form>';

  echo '<button type="button" id="abst-copy-logs" class="button button-secondary">Copy to Clipboard</button>';

  echo '</div>';

  

  // Display logs

  echo '<div class="abst-logs-container">';

  

  if (file_exists($log_file)) {

    $logs = file_get_contents($log_file);

    if (!empty($logs)) {

      // Escape HTML first, then colorize log levels

      $logs = esc_html($logs);

      $logs = preg_replace('/\[(info)\]/', '<span class="log-info">[info]</span>', $logs);

      $logs = preg_replace('/\[(warning)\]/', '<span class="log-warning">[warning]</span>', $logs);

      $logs = preg_replace('/\[(error)\]/', '<span class="log-error">[error]</span>', $logs);

      echo '<pre>' . wp_kses_post($logs) . '</pre>';

    } else {

      echo '<p class="abst-logs-empty">No logs found yet. Logs will appear here once debug logging captures activity.</p>';

    }

  } else {

    echo '<p class="abst-logs-empty">Log file does not exist yet. Enable logging in Settings to start capturing debug information.</p>';

  }

  

  echo '</div>';

  echo '</div>';

  

  // Inline styles to match the admin style guide

  echo '<style>

  .abst-logs-wrap {

    max-width: 1200px;

  }

  .abst-logs-wrap .abst-page-header {

    display: flex;

    align-items: center;

    gap: 12px;

    margin-bottom: 16px;

  }

  .abst-logs-wrap .abst-page-header h1 {

    font-size: 24px;

    font-weight: 600;

    color: #1e293b;

    margin: 0;

  }

  .abst-logs-info {

    background: #f8fafc;

    border: 1px solid #e2e8f0;

    border-radius: 8px;

    padding: 16px 20px;

    margin-bottom: 16px;

  }

  .abst-logs-info p {

    margin: 0 0 8px 0;

    color: #475569;

    font-size: 14px;

  }

  .abst-logs-info p:last-child {

    margin-bottom: 0;

  }

  .abst-logs-info a {

    color: #3b82f6;

    text-decoration: none;

  }

  .abst-logs-info a:hover {

    text-decoration: underline;

  }

  .abst-logs-actions {

    margin-bottom: 16px;

    display: flex;

    gap: 8px;

  }

  .abst-logs-container {

    background: #1e293b;

    border-radius: 8px;

    padding: 20px;

    max-height: 600px;

    overflow-y: auto;

  }

  .abst-logs-container pre {

    margin: 0;

    font-family: "SF Mono", Monaco, "Cascadia Code", "Roboto Mono", Consolas, monospace;

    font-size: 12px;

    line-height: 1.6;

    color: #e2e8f0;

    white-space: pre-wrap;

    word-wrap: break-word;

  }

  .abst-logs-container .log-info {

    color: #60a5fa;

  }

  .abst-logs-container .log-warning {

    color: #fbbf24;

  }

  .abst-logs-container .log-error {

    color: #f87171;

  }

  .abst-logs-empty {

    color: #94a3b8;

    font-style: italic;

    margin: 0;

  }

  </style>';

  

  // JavaScript for copy functionality

  echo '<script>

  document.getElementById("abst-copy-logs").addEventListener("click", function() {

    var logContent = document.querySelector(".abst-logs-container pre");

    if (!logContent) {

      alert("No logs to copy.");

      return;

    }

    

    // Check if clipboard API is available (requires HTTPS or localhost)

    if (!navigator.clipboard) {

      alert("Cannot copy on localhost. Please select the logs manually and use Ctrl+C.");

      return;

    }

    

    navigator.clipboard.writeText(logContent.textContent).then(function() {

      var btn = document.getElementById("abst-copy-logs");

      var originalText = btn.textContent;

      btn.textContent = "Copied!";

      setTimeout(function() { btn.textContent = originalText; }, 2000);

    }).catch(function() { 

      alert("SORRY, clipboard requires HTTPS. Please select the logs manually and use Ctrl+C.");

    });

  });

  </script>';

}



function abst_trim_abst_log() {

  // Get WordPress uploads directory

  $upload_dir = wp_upload_dir();

  $log_dir = $upload_dir['basedir'];

  $hash = substr(md5(defined('AUTH_KEY') ? AUTH_KEY : 'abst'), 0, 12);
  $log_file = $log_dir . '/abst_log_' . $hash . '.log';



  // Check if log file exists

  if (file_exists($log_file)) {

    // Trim oldest lines to keep 500 newest lines

    $lines = file($log_file);

    if ($lines !== false) {

      $line_count = count($lines);

      if ($line_count > 500) {

        $lines = array_slice($lines, -500);

        abst_put_contents($log_file, implode('', $lines));

        abst_log('Log trimmed from ' . $line_count . ' to 500 lines');

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

add_action('abst_trim_log', 'abst_trim_abst_log');







// Form-plugin conversions (WooCommerce) are PRO only — disabled in lite version 





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

        



  global $nginx_purger;

  if (  isset( $nginx_purger ) && method_exists( $nginx_purger, 'purge_all' ) ) {

      $detected_caches[] = 'nginx';

  }



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



/**
 * Determine whether a test is bundled demo data and should not consume Lite limits.
 */
function abst_lite_is_sample_test($post_id) {
  $post_id = intval($post_id);
  if ($post_id <= 0) {
    return false;
  }

  if (get_post_meta($post_id, '_abst_is_sample_test', true)) {
    return true;
  }

  $post = get_post($post_id);
  if (!$post || $post->post_type !== 'bt_experiments') {
    return false;
  }

  $title = strtolower((string) $post->post_title);
  $content = strtolower((string) $post->post_content);

  return strpos($title, 'sample test') === 0 || strpos($content, 'this is a sample') !== false;
}



/**

 * Create sample tests on plugin activation

 */

function abst_create_sample_tests_on_activation() {

  return abst_load_sample_tests_from_json(false); // Don't force on activation

}



/**

 * Load sample tests from JSON files

 * @param bool $force Whether to force creation even if tests exist

 * @return array Results of the operation

 */

function abst_load_sample_tests_from_json($force = false) {

  $results = ['created' => 0, 'skipped' => 0, 'errors' => []];

  

  // Check if any tests already exist (unless forced)

  if (!$force) {

    $existing_tests = get_posts([

      'post_type' => 'bt_experiments',

      'post_status' => 'any',

      'numberposts' => 1

    ]);

    

    if (!empty($existing_tests)) {

      $results['skipped'] = 'Tests already exist';

      return $results;

    }

  }

  

  // Get sample data directory

  $sample_dir = plugin_dir_path(__FILE__) . 'includes/sampledata/';

  

  if (!is_dir($sample_dir)) {

    $results['errors'][] = 'Sample data directory not found: ' . $sample_dir;

    return $results;

  }

  

  // Scan for JSON files

  $json_files = glob($sample_dir . '*.json');

  

  // Reverse order so newest/alphabetically last is imported first

  $json_files = array_reverse($json_files);

  

  if (empty($json_files)) {

    $results['errors'][] = 'No JSON sample files found in: ' . $sample_dir;

    return $results;

  }

  

  foreach ($json_files as $json_file) {

    $filename = basename($json_file);

    

    // Read and decode JSON

    $json_content = file_get_contents($json_file);

    if ($json_content === false) {

      $results['errors'][] = "Could not read file: {$filename}";

      continue;

    }

    

    $sample_data = json_decode($json_content, true);

    if ($sample_data === null) {

      $results['errors'][] = "Invalid JSON in file: {$filename}";

      continue;

    }

    

    // Create test based on filename and data

    $test_created = abst_create_test_from_json_data($filename, $sample_data);

    

    if ($test_created) {

      $results['created']++;

    } else {

      $results['errors'][] = "Failed to create test from: {$filename}";

    }

  }

  

  // Clear any cached posts

  delete_transient('ab_posts_cache');

  

  return $results;

}



/**

 * Create a test from JSON data

 * @param string $filename The JSON filename

 * @param array $data The decoded JSON data

 * @return int|false The post ID or false on failure

 */

function abst_create_test_from_json_data($filename, $data) {

  // Handle the current format (test results data)

  if (isset($data['magic-1']) || isset($data['magic-2'])) {

    return abst_create_test_from_results_data($filename, $data);

  }

  

  // Handle structured format (post_data, test_config, sample_results)

  if (isset($data['post_data']) && isset($data['test_config'])) {

    return abst_create_test_from_structured_data($data);

  }

  

  return false;

}



/**

 * Create test from results data format

 */

function abst_create_test_from_results_data($filename, $data) {

  // Extract test name from filename

  $test_name = 'Sample: ' . ucwords(str_replace(['-', '.json'], [' ', ''], basename($filename, '.json')));

  

  // Create the post

  $sample_test = [

    'post_title' => $test_name,

    'post_type' => 'bt_experiments',

    'post_status' => 'draft',

    'post_content' => 'This is a sample test with realistic data loaded from JSON.',

    'post_date' => current_time('mysql'),

    'post_date_gmt' => current_time('mysql', 1),

  ];

  

  $test_id = wp_insert_post($sample_test);

  

  if ($test_id) {

    update_post_meta($test_id, '_abst_is_sample_test', 1);

    // Basic test configuration

    update_post_meta($test_id, 'test_type', 'magic');

    update_post_meta($test_id, 'target_percentage', '50');

    update_post_meta($test_id, 'conversion_page', 'time');

    update_post_meta($test_id, 'conversion_time', '30');

    

    // Create sample magic definition based on filename

    $magic_definition = abst_create_sample_magic_definition($filename);

    update_post_meta($test_id, 'magic_definition', wp_json_encode($magic_definition));

    

    // Store the sample results data

    update_post_meta($test_id, 'sample_results', wp_json_encode($data));

    

    return $test_id;

  }

  

  return false;

}



/**

 * Create test from structured data format

 */

function abst_create_test_from_structured_data($data) {

  abst_log('abst_create_test_from_structured_data');

  $post_data = $data['post_data'];

  $test_config = $data['test_config'];

  

  // Create the post with date set to 14 days ago

  $two_weeks_ago = wp_date('Y-m-d H:i:s', strtotime('-14 days'));

  $two_weeks_ago_gmt = gmdate('Y-m-d H:i:s', strtotime('-14 days'));

  

  $sample_test = [

    'post_title' => $post_data['title'],

    'post_type' => 'bt_experiments',

    'post_status' => $post_data['status'] ?? 'publish',

    'post_content' => $post_data['content'] ?? '',

    'post_date' => $two_weeks_ago,

    'post_date_gmt' => $two_weeks_ago_gmt,

  ];

  

  $test_id = wp_insert_post($sample_test);

  

  if ($test_id) {

    update_post_meta($test_id, '_abst_is_sample_test', 1);

    // Apply test configuration with proper meta key mapping

    $meta_mapping = [

      'test_type' => 'test_type', // Add test_type mapping

      'magic_definition' => 'magic_definition', // Special handling below

      'target_percentage' => 'target_percentage',

      'target_option_device_size' => 'target_option_device_size',

      'conversion_page' => 'conversion_page',

      'conversion_selector' => 'conversion_selector',

      'conversion_time' => 'conversion_time',

      'conversion_text' => 'conversion_text',

      'conversion_use_order_value' => 'conversion_use_order_value',

      'conversion_url' => 'conversion_url',

      'conversion_link_pattern' => 'conversion_link_pattern',

      'url_query' => 'url_query',

      'webhook_url' => 'webhook_url',

      'bt_allowed_roles' => 'bt_allowed_roles',

      'css_test_variations' => 'css_test_variations',

      'conversion_style' => 'conversion_style',

      'goals' => 'goals',

      'autocomplete_on' => 'autocomplete_on',

      'ac_min_days' => 'ac_min_days',

      'ac_min_views' => 'ac_min_views',

      'status' => 'post_status',

      'observations' => 'observations',

      'page_variations' => 'page_variations',

      'variation_meta' => 'variation_meta',

      'bt_experiments_full_page_default_page' => 'bt_experiments_full_page_default_page',

      'test_winner' => 'test_winner'

    ];

    

    foreach ($test_config as $key => $value) {

      if (isset($meta_mapping[$key])) {

        $meta_key = $meta_mapping[$key];

        

        // Special handling for certain fields

        if ($key === 'magic_definition') {

          $value = wp_json_encode($value);

        } elseif ($key === 'observations' && is_array($value)) {

          // Observations are stored as array, no JSON encoding needed

        } elseif ($key === 'bt_allowed_roles' && is_array($value)) {

          $value = array_map('sanitize_text_field', $value);

        } elseif ($key === 'goals' && is_array($value)) {

          // Goals are stored as array, no JSON encoding needed

        } elseif ($key === 'page_variations' && is_array($value)) {

          // Page variations stored as array

        } elseif ($key === 'variation_meta' && is_array($value)) {

          // Variation meta stored as array

        }

        

        update_post_meta($test_id, $meta_key, $value);

      }

    }

    

    // Store observations if provided (updated from sample_results)

    if (isset($data['observations'])) {

      update_post_meta($test_id, 'observations', $data['observations']);

      

      // Run statistical analysis on the observations data

      $test_age = 14; // Test was created 14 days ago

      require_once plugin_dir_path(__FILE__) . 'includes/statistics.php';

      $conversion_use_order_value = isset($test_config['conversion_use_order_value']) ? $test_config['conversion_use_order_value'] : get_post_meta($test_id, 'conversion_use_order_value', true);

      if ($conversion_use_order_value == '1' && is_array($data['observations'])) {

        // Use Welch's T-Test for revenue/order value data (continuous values)

        $analyzed_observations = bt_bb_ab_revenue_analyzer($data['observations'], $test_age);

      } else {

        // Use Bayesian analyzer for binary conversion data

        $analyzed_observations = bt_bb_ab_split_test_analyzer($data['observations'], $test_age);

      }

      

      // Update with analyzed data including statistical calculations

      update_post_meta($test_id, 'observations', $analyzed_observations);

      

      abst_log('Statistical analysis completed for test ' . $test_id);

    }

    

    return $test_id;

  }

  

  return false;

}



/**

 * Create sample magic definition based on filename

 */

function abst_create_sample_magic_definition($filename) {

  $name = strtolower($filename);

  

  if (strpos($name, 'button') !== false || strpos($name, 'cta') !== false) {

    return [

      'original' => [

        'selector' => 'button, .btn, input[type="submit"]',

        'type' => 'style',

        'property' => 'background-color',

        'value' => '#007cba'

      ],

      'variations' => [

        'Green Button' => [

          'selector' => 'button, .btn, input[type="submit"]',

          'type' => 'style',

          'property' => 'background-color',

          'value' => '#46b450'

        ],

        'Red Button' => [

          'selector' => 'button, .btn, input[type="submit"]',

          'type' => 'style',

          'property' => 'background-color',

          'value' => '#dc3232'

        ]

      ]

    ];

  }

  

  // Default to headline test

  return [

    'original' => [

      'selector' => 'h1, h2.entry-title, .page-title',

      'type' => 'text',

      'value' => 'Original Headline'

    ],

    'variations' => [

      'Compelling Headline' => [

        'selector' => 'h1, h2.entry-title, .page-title',

        'type' => 'text',

        'value' => 'Discover Amazing Results in Just 30 Days'

      ],

      'Question Headline' => [

        'selector' => 'h1, h2.entry-title, .page-title',

        'type' => 'text',

        'value' => 'Ready to Transform Your Business?'

      ]

    ]

  ];

}



// Register activation hook

register_activation_hook(__FILE__, 'abst_create_sample_tests_on_activation');



/**

 * Run a weekly report every Monday at 8am.

 */



function abst_monday_morning_report() {



  if(!abst_get_admin_setting('abst_send_weekly_reports'))

    return false;



  abst_send_report_email(abst_get_admin_setting('abst_weekly_report_emails'));



}



function abst_send_report_email($emails){

  abst_log('Attempting to send Email Report');



  //csv to array

  //if contains comma then explode 

  if(strpos($emails, ',') !== false){

    $emails = explode(',', $emails);

  }

  

  $email_subject = 'AB Split Test Lite Weekly Report';

  $email_subject = apply_filters('abst_email_report_subject', $email_subject);

  //send email to admin

  // Get report content - use shortcode function as fallback

  $report_content = do_shortcode('[ab_split_test_report emailreport="true"]');

  if(empty($report_content)) {

    $report_content = 'No active tests.';

  }



  //replace all h2 elements with h3 in report_content

  $report_content = str_replace('<h1>', '<hr/><h2>', $report_content);

  $report_content = str_replace('</h1>', '</h2>', $report_content);

  

  $email_message = '<html><body style="font-family: Arial, Helvetica, sans-serif; font-size: 12px; padding: 20px;">';

  $email_message .= '<h1 style="font-size: 24px;">Your AB Split Test Lite Weekly Report</h1>';

  $email_message .= '<div>' . $report_content . '</div>';

  $email_message .= '<br><br>';

  $email_message .= '<h2 style="font-size: 20px;">What should you test next?</h2>';

  $email_message .= '<p>Need high impact optimization suggestions? Login to your site to generate your CRO analysis. <a href="' . admin_url('edit.php?post_type=bt_experiments&page=bt_bb_ab_insights') . '">Click here to generate CRO analysis one-click test ideas</a> that you can run in seconds.</p>';

  $email_message .= '<p>Turn off these emails from your <a href="' . admin_url('options-general.php?page=bt_bb_ab_test') . '">Split Test Settings</a>.</p>';

  $email_message .= '</body></html>';

  $email_message = apply_filters('abst_email_report_message', $email_message);

  

  // Set HTML headers for email

  $headers = array('Content-Type: text/html; charset=UTF-8');

 

  if(wp_mail($emails, $email_subject, $email_message, $headers)) // send email

    abst_log('Email Report Sent to ' . $emails);

  else

    abst_log('Email Report Failed to ' . $emails);    

}



/**

 * Register a two-hour cron schedule for MAB (Thompson Sampling) weight updates.

 */

function abst_add_two_hour_schedule( $schedules ) {

  if ( ! isset( $schedules['ab_two_hours'] ) ) {

    $schedules['ab_two_hours'] = [

      'interval' => 2 * HOUR_IN_SECONDS,

      'display'  => 'Every 2 Hours',

    ];

  }

  return $schedules;

}

add_filter( 'cron_schedules', 'abst_add_two_hour_schedule' );



// MAB cron removed in lite version — abst_update_thompson_weights is a no-op stub kept for backwards compatibility if event fires during upgrade.
function abst_update_thompson_weights() { return; }



function abst_beta_random( $alpha, $beta ) {

  $x = abst_gamma_random( $alpha );

  $y = abst_gamma_random( $beta );

  return $x / ( $x + $y );

}



function abst_gamma_random( $shape ) {

  if ( $shape < 1 ) {

    $u = wp_rand(0, PHP_INT_MAX) / PHP_INT_MAX;

    return abst_gamma_random( 1 + $shape ) * pow( $u, 1 / $shape );

  }

  $d = $shape - 1/3;

  $c = 1 / sqrt( 9 * $d );

  while ( true ) {

    $x = abst_normal_random();

    $v = pow( 1 + $c * $x, 3 );

    if ( $v <= 0 ) {

      continue;

    }

    $u = wp_rand(0, PHP_INT_MAX) / PHP_INT_MAX;

    if ( $u < 1 - 0.331 * pow( $x, 4 ) || log( $u ) < 0.5 * $x * $x + $d * ( 1 - $v + log( $v ) ) ) {

      return $d * $v;

    }

  }

}



function abst_normal_random() {

  $u = $v = 0;

  while ( $u === 0 ) {

    $u = wp_rand(0, PHP_INT_MAX) / PHP_INT_MAX;

  }

  while ( $v === 0 ) {

    $v = wp_rand(0, PHP_INT_MAX) / PHP_INT_MAX;

  }

  return sqrt( -2 * log( $u ) ) * cos( 2 * M_PI * $v );

}



/**

 * Add a weekly cron schedule if not already present.

 */

function abst_add_weekly_schedule( $schedules ) {

  if ( ! isset( $schedules['ab_weekly'] ) ) {

    $schedules['ab_weekly'] = [

      'interval' => WEEK_IN_SECONDS,

      'display'  => 'Once Weekly',

    ];

  }

  return $schedules;

}

add_filter( 'cron_schedules', 'abst_add_weekly_schedule' );



/**

 * Weekly reports are PRO only — disabled in lite version.

 */

function abst_schedule_weekly_report_cron() {

  // Lite version: weekly reports disabled

  return;

}



function abst_clear_weekly_report_cron() {

  // Lite version: weekly reports disabled

  $timestamp = wp_next_scheduled( 'ab_weekly_monday_report' );

  if ( $timestamp ) {

    wp_unschedule_event( $timestamp, 'ab_weekly_monday_report' );

  }

}

add_action( 'ab_weekly_monday_report', 'abst_monday_morning_report' );

/**

 * Set cookie with fallback strategy matching JavaScript implementation

 * Tries root domain first, then subdomain, then minimal options

 */

function abst_set_cookie_with_fallback($name, $value, $days, $host = null) {

    if ($host === null) {

        $host = sanitize_text_field($_SERVER['HTTP_HOST'] ?? '');

    }

    

    // Check if localhost

    $is_localhost = (

        in_array($host, ['localhost', '127.0.0.1'], true) ||

        strpos($host, 'localhost:') === 0 ||

        substr($host, -6) === '.test' ||

        substr($host, -6) === '.local' ||

        substr($host, -4) === '.dev' ||

        filter_var(preg_replace('/:\d+$/', '', $host), FILTER_VALIDATE_IP)

    );

    if ($is_localhost) {

        return setcookie($name, $value, [

            'expires' => time() + ($days * 86400),

            'path' => '/',

            'SameSite' => 'Lax',

            'Secure' => false,

        ]);

    }

    

    // Get root domain

    $domain = abst_get_root_domain($host);

    $expiry = time() + ($days * 86400);

    $same_site = (is_ssl() ? 'None' : 'Lax');

    $secure = is_ssl();

    

    // Strategy 1: Try main domain first

    $options = [

        'expires' => $expiry,

        'path' => '/',

        'domain' => $domain,

        'SameSite' => $same_site,

        'Secure' => $secure,

    ];

    

    if (setcookie($name, $value, $options)) {

        return true;

    }

    

    // Strategy 2: Fallback to current subdomain only

    $options['domain'] = ''; // No domain = current host only

    

    if (setcookie($name, $value, $options)) {

        return true;

    }

    

    // Strategy 3: Last resort - minimal cookie

    $minimal_options = [

        'expires' => $expiry,

        'path' => '/',

    ];

    

    if (setcookie($name, $value, $minimal_options)) {

        return true;

    }

    

    return false;

}




/**

 * Get root domain handling multi-part TLDs like .co.uk, .com.au, etc.

 * Matches the JavaScript logic in bt_conversion.js

 */

function abst_get_root_domain($host) {

    // Remove www prefix

    $host = preg_replace('/^www\./', '', $host);

    $parts = explode('.', $host);

    

    // Multi-part TLDs that need 3 parts instead of 2

    $multiPartTlds = [

        // Australia

        'com.au', 'net.au', 'edu.au', 'gov.au', 'org.au', 'asn.au',

        // United Kingdom

        'co.uk', 'org.uk', 'ac.uk', 'gov.uk', 'me.uk', 'ltd.uk', 'plc.uk',

        // New Zealand

        'co.nz', 'org.nz', 'net.nz', 'govt.nz', 'ac.nz',

        // South Africa

        'co.za', 'org.za', 'net.za', 'gov.za', 'ac.za',

        // Japan

        'co.jp', 'ne.jp', 'or.jp', 'go.jp', 'ac.jp',

        // China

        'com.cn', 'net.cn', 'org.cn', 'gov.cn', 'ac.cn',

        // Taiwan

        'com.tw', 'org.tw', 'net.tw', 'gov.tw', 'edu.tw',

        // Hong Kong

        'com.hk', 'org.hk', 'net.hk', 'gov.hk', 'edu.hk',

        // Singapore

        'com.sg', 'org.sg', 'net.sg', 'gov.sg', 'edu.sg',

        // India

        'co.in', 'org.in', 'net.in', 'gov.in', 'ac.in',

        // South Korea

        'co.kr', 'or.kr', 'ne.kr', 'go.kr', 'ac.kr',

        // Brazil

        'com.br', 'org.br', 'net.br', 'gov.br', 'edu.br',

        // Mexico

        'com.mx', 'org.mx', 'net.mx', 'gov.mx', 'edu.mx',

        // Israel

        'co.il', 'org.il', 'net.il', 'gov.il', 'ac.il',

        // Thailand

        'co.th', 'or.th', 'net.th', 'go.th', 'ac.th',

        // Malaysia

        'com.my', 'org.my', 'net.my', 'gov.my', 'edu.my',

        // Philippines

        'com.ph', 'org.ph', 'net.ph', 'gov.ph', 'edu.ph',

        // Indonesia

        'co.id', 'org.id', 'net.id', 'go.id', 'ac.id',

        // Canada

        'co.ca', 'org.ca', 'net.ca', 'gov.ca', 'edu.ca',

        // UAE

        'co.ae', 'org.ae', 'net.ae', 'gov.ae', 'edu.ae',

        // Pakistan

        'com.pk', 'org.pk', 'net.pk', 'gov.pk', 'edu.pk',

        // Bangladesh

        'com.bd', 'org.bd', 'net.bd', 'gov.bd', 'edu.bd',

        // Sri Lanka

        'com.lk', 'org.lk', 'net.lk', 'gov.lk', 'ac.lk',

        // Vietnam

        'com.vn', 'org.vn', 'net.vn', 'gov.vn', 'edu.vn',

        // Thailand

        'com.tn', 'org.tn', 'net.tn', 'gov.tn',

        // Egypt

        'com.eg', 'org.eg', 'net.eg', 'gov.eg', 'edu.eg',

        // Nigeria

        'com.ng', 'org.ng', 'net.ng', 'gov.ng', 'edu.ng',

        // Kenya

        'co.ke', 'or.ke', 'ne.ke', 'go.ke', 'ac.ke',

        // Argentina

        'com.ar', 'org.ar', 'net.ar', 'gov.ar', 'edu.ar',

        // Chile

        'com.cl', 'org.cl', 'net.cl', 'gov.cl', 'edu.cl',

        // Colombia

        'com.co', 'org.co', 'net.co', 'gov.co', 'edu.co',

        // Peru

        'com.pe', 'org.pe', 'net.pe', 'gov.pe', 'edu.pe',

        // Venezuela

        'com.ve', 'org.ve', 'net.ve', 'gov.ve', 'edu.ve',

        // Ecuador

        'com.ec', 'org.ec', 'net.ec', 'gov.ec', 'edu.ec',

        // Russia

        'com.ru', 'org.ru', 'net.ru', 'gov.ru', 'edu.ru',

        // Ukraine

        'com.ua', 'org.ua', 'net.ua', 'gov.ua', 'edu.ua',

        // Turkey

        'com.tr', 'org.tr', 'net.tr', 'gov.tr', 'edu.tr',

        // Greece

        'com.gr', 'org.gr', 'net.gr', 'gov.gr', 'edu.gr',

        // Poland

        'com.pl', 'org.pl', 'net.pl', 'gov.pl', 'edu.pl',

        // Czech Republic

        'com.cz', 'org.cz', 'net.cz', 'gov.cz', 'edu.cz',

        // Hungary

        'com.hu', 'org.hu', 'net.hu', 'gov.hu', 'edu.hu',

        // Romania

        'com.ro', 'org.ro', 'net.ro', 'gov.ro', 'edu.ro',

        // Serbia

        'com.rs', 'org.rs', 'net.rs', 'gov.rs', 'edu.rs',

        // Croatia

        'com.hr', 'org.hr', 'net.hr', 'gov.hr', 'edu.hr',

        // Slovenia

        'com.si', 'org.si', 'net.si', 'gov.si', 'edu.si',

        // Slovakia

        'com.sk', 'org.sk', 'net.sk', 'gov.sk', 'edu.sk',

        // Bulgaria

        'com.bg', 'org.bg', 'net.bg', 'gov.bg', 'edu.bg',

        // Portugal

        'com.pt', 'org.pt', 'net.pt', 'gov.pt', 'edu.pt',

        // Belgium

        'com.be', 'org.be', 'net.be', 'gov.be', 'edu.be',

        // Netherlands

        'com.nl', 'org.nl', 'net.nl', 'gov.nl', 'edu.nl',

        // Austria

        'com.at', 'org.at', 'net.at', 'gov.at', 'edu.at',

        // Switzerland

        'com.ch', 'org.ch', 'net.ch', 'gov.ch', 'edu.ch',

        // Germany

        'com.de', 'org.de', 'net.de', 'gov.de', 'edu.de',

        // France

        'com.fr', 'org.fr', 'net.fr', 'gov.fr', 'edu.fr',

        // Spain

        'com.es', 'org.es', 'net.es', 'gov.es', 'edu.es',

        // Italy

        'com.it', 'org.it', 'net.it', 'gov.it', 'edu.it',

        // Sweden

        'com.se', 'org.se', 'net.se', 'gov.se', 'edu.se',

        // Norway

        'com.no', 'org.no', 'net.no', 'gov.no', 'edu.no',

        // Denmark

        'com.dk', 'org.dk', 'net.dk', 'gov.dk', 'edu.dk',

        // Finland

        'com.fi', 'org.fi', 'net.fi', 'gov.fi', 'edu.fi',

        // Iceland

        'com.is', 'org.is', 'net.is', 'gov.is', 'edu.is',

        // Ireland

        'com.ie', 'org.ie', 'net.ie', 'gov.ie', 'edu.ie',

        // Luxembourg

        'com.lu', 'org.lu', 'net.lu', 'gov.lu', 'edu.lu',

        // Malta

        'com.mt', 'org.mt', 'net.mt', 'gov.mt', 'edu.mt',

        // Cyprus

        'com.cy', 'org.cy', 'net.cy', 'gov.cy', 'edu.cy',

        // Lithuania

        'com.lt', 'org.lt', 'net.lt', 'gov.lt', 'edu.lt',

        // Latvia

        'com.lv', 'org.lv', 'net.lv', 'gov.lv', 'edu.lv',

        // Estonia

        'com.ee', 'org.ee', 'net.ee', 'gov.ee', 'edu.ee',

        // Bosnia and Herzegovina

        'com.ba', 'org.ba', 'net.ba', 'gov.ba', 'edu.ba',

        // Montenegro

        'com.me', 'org.me', 'net.me', 'gov.me', 'edu.me',

        // Macedonia

        'com.mk', 'org.mk', 'net.mk', 'gov.mk', 'edu.mk',

        // Albania

        'com.al', 'org.al', 'net.al', 'gov.al', 'edu.al',

        // Moldova

        'com.md', 'org.md', 'net.md', 'gov.md', 'edu.md',

        // Belarus

        'com.by', 'org.by', 'net.by', 'gov.by', 'edu.by',

        // Kazakhstan

        'com.kz', 'org.kz', 'net.kz', 'gov.kz', 'edu.kz',

        // Uzbekistan

        'com.uz', 'org.uz', 'net.uz', 'gov.uz', 'edu.uz',

        // Tajikistan

        'com.tj', 'org.tj', 'net.tj', 'gov.tj', 'edu.tj',

        // Kyrgyzstan

        'com.kg', 'org.kg', 'net.kg', 'gov.kg', 'edu.kg',

        // Turkmenistan

        'com.tm', 'org.tm', 'net.tm', 'gov.tm', 'edu.tm',

        // Afghanistan

        'com.af', 'org.af', 'net.af', 'gov.af', 'edu.af',

        // Iran

        'com.ir', 'org.ir', 'net.ir', 'gov.ir', 'ac.ir',

        // Iraq

        'com.iq', 'org.iq', 'net.iq', 'gov.iq', 'edu.iq',

        // Saudi Arabia

        'com.sa', 'org.sa', 'net.sa', 'gov.sa', 'edu.sa',

        // Yemen

        'com.ye', 'org.ye', 'net.ye', 'gov.ye', 'edu.ye',

        // Oman

        'com.om', 'org.om', 'net.om', 'gov.om', 'edu.om',

        // Qatar

        'com.qa', 'org.qa', 'net.qa', 'gov.qa', 'edu.qa',

        // Bahrain

        'com.bh', 'org.bh', 'net.bh', 'gov.bh', 'edu.bh',

        // Kuwait

        'com.kw', 'org.kw', 'net.kw', 'gov.kw', 'edu.kw',

        // Jordan

        'com.jo', 'org.jo', 'net.jo', 'gov.jo', 'edu.jo',

        // Lebanon

        'com.lb', 'org.lb', 'net.lb', 'gov.lb', 'edu.lb',

        // Syria

        'com.sy', 'org.sy', 'net.sy', 'gov.sy', 'edu.sy',

        // Palestine

        'com.ps', 'org.ps', 'net.ps', 'gov.ps', 'edu.ps',

        // Myanmar

        'com.mm', 'org.mm', 'net.mm', 'gov.mm', 'edu.mm',

        // Cambodia

        'com.kh', 'org.kh', 'net.kh', 'gov.kh', 'edu.kh',

        // Laos

        'com.la', 'org.la', 'net.la', 'gov.la', 'edu.la',

        // Mongolia

        'com.mn', 'org.mn', 'net.mn', 'gov.mn', 'edu.mn',

        // Nepal

        'com.np', 'org.np', 'net.np',

        // Bhutan

        'com.bt', 'org.bt', 'net.bt', 'gov.bt', 'edu.bt',

        // Maldives

        'com.mv', 'org.mv', 'net.mv', 'gov.mv', 'edu.mv',

        // Thailand (additional)

        'com.th', 'org.th', 'net.th', 'go.th', 'ac.th',

        // USA

        'com.us', 'org.us', 'net.us', 'gov.us', 'edu.us',

        // Canada (additional)

        'com.ca', 'org.ca', 'net.ca', 'gov.ca', 'edu.ca',

        // Mexico (additional)

        'com.mx', 'org.mx', 'net.mx', 'gov.mx', 'edu.mx',

        // Belize

        'com.bz', 'org.bz', 'net.bz',

        // Costa Rica

        'co.cr', 'org.cr', 'net.cr',

        // Panama

        'com.pa', 'org.pa', 'net.pa',

        // Dominican Republic

        'com.do', 'org.do', 'net.do',

        // Puerto Rico

        'com.pr', 'org.pr', 'net.pr',

        // Cuba

        'com.cu', 'org.cu', 'net.cu',

        // Jamaica

        'com.jm', 'org.jm', 'net.jm',

        // Trinidad and Tobago

        'com.tt', 'org.tt', 'net.tt',

        // Suriname

        'com.sr', 'org.sr', 'net.sr',

        // Guyana

        'com.gy', 'org.gy', 'net.gy',

        // Bolivia

        'com.bo', 'org.bo', 'net.bo',

        // Paraguay

        'com.py', 'org.py', 'net.py',

        // Uruguay

        'com.uy', 'org.uy', 'net.uy',

        // Ethiopia

        'com.et', 'org.et', 'net.et', 'gov.et', 'edu.et',

        // Ghana

        'com.gh', 'org.gh', 'net.gh', 'gov.gh', 'edu.gh',

        // Uganda

        'com.ug', 'org.ug', 'net.ug', 'gov.ug', 'edu.ug',

        // Tanzania

        'com.tz', 'org.tz', 'net.tz', 'gov.tz', 'edu.tz',

        // Zambia

        'com.zm', 'org.zm', 'net.zm', 'gov.zm', 'edu.zm',

        // Zimbabwe

        'com.zw', 'org.zw', 'net.zw', 'gov.zw', 'edu.zw',

        // Botswana

        'co.bw', 'org.bw', 'net.bw', 'gov.bw', 'edu.bw',

        // Namibia

        'com.na', 'org.na', 'net.na', 'gov.na', 'edu.na',

        // Mauritius

        'com.mu', 'org.mu', 'net.mu', 'gov.mu', 'edu.mu',

        // Seychelles

        'com.sc', 'org.sc', 'net.sc', 'gov.sc', 'edu.sc',

        // Morocco

        'com.ma', 'org.ma', 'net.ma', 'gov.ma', 'edu.ma',

        // Algeria

        'com.dz', 'org.dz', 'net.dz', 'gov.dz', 'edu.dz',

        // Tunisia

        'com.tn', 'org.tn', 'net.tn', 'gov.tn', 'edu.tn',

        // Libya

        'com.ly', 'org.ly', 'net.ly', 'gov.ly', 'edu.ly',

        // Sudan

        'com.sd', 'org.sd', 'net.sd', 'gov.sd', 'edu.sd',

        // Senegal

        'com.sn', 'org.sn', 'net.sn', 'gov.sn', 'edu.sn',

        // Ivory Coast

        'com.ci', 'org.ci', 'net.ci', 'gov.ci', 'edu.ci',

        // Cameroon

        'com.cm', 'org.cm', 'net.cm', 'gov.cm', 'edu.cm',

        // Angola

        'com.ao', 'org.ao', 'net.ao', 'gov.ao', 'edu.ao',

        // Mozambique

        'com.mz', 'org.mz', 'net.mz', 'gov.mz', 'edu.mz',

        // Madagascar

        'com.mg', 'org.mg', 'net.mg', 'gov.mg', 'edu.mg',

        // Fiji

        'com.fj', 'org.fj', 'net.fj', 'gov.fj', 'edu.fj',

        // New Caledonia

        'com.nc', 'org.nc', 'net.nc', 'gov.nc', 'edu.nc',

        // Papua New Guinea

        'com.pg', 'org.pg', 'net.pg', 'gov.pg', 'edu.pg',

        // Solomon Islands

        'com.sb', 'org.sb', 'net.sb', 'gov.sb', 'edu.sb',

        // Samoa

        'com.ws', 'org.ws', 'net.ws', 'gov.ws', 'edu.ws',

        // Tonga

        'com.to', 'org.to', 'net.to', 'gov.to', 'edu.to',

        // Kiribati

        'com.ki', 'org.ki', 'net.ki', 'gov.ki', 'edu.ki',

        // Marshall Islands

        'com.mh', 'org.mh', 'net.mh', 'gov.mh', 'edu.mh',

        // Palau

        'com.pw', 'org.pw', 'net.pw', 'gov.pw', 'edu.pw',

        // Micronesia

        'com.fm', 'org.fm', 'net.fm', 'gov.fm', 'edu.fm',

        // Nauru

        'com.nr', 'org.nr', 'net.nr', 'gov.nr', 'edu.nr',

        // Tuvalu

        'com.tv', 'org.tv', 'net.tv', 'gov.tv', 'edu.tv',

    ];

    

    // Check if last 2 parts match a multi-part TLD

    if (count($parts) >= 3) {

        $potential_tld = implode('.', array_slice($parts, -2));

        if (in_array($potential_tld, $multiPartTlds)) {

            // Use last 3 parts for multi-part TLDs

            return '.' . implode('.', array_slice($parts, -3));

        }

    }

    

    // Default: use last 2 parts for standard TLDs

    return '.' . implode('.', array_slice($parts, -2));

}



/**

 * Get a human-readable label for a variation key.

 * Centralized function used by both main plugin and Agency Hub.

 *

 * @param string|int $variation Variation key (e.g., 'magic-0', 'test-css-123-1', post ID, or custom string).

 * @param array|null $variation_meta Optional variation metadata containing custom labels.

 * @return string Human-readable variation label.

 */

function abst_get_variation_label( $variation, $variation_meta = null ) {

    $variation = (string) $variation;

    $variation_label = $variation;

    $alphabet = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z'];

    

    // Check for custom label in variation_meta first (highest priority)

    if ( is_array( $variation_meta ) && isset( $variation_meta[ $variation ]['label'] ) && ! empty( $variation_meta[ $variation ]['label'] ) ) {

        return $variation_meta[ $variation ]['label'];

    }

    

    // Handle magic test variations (magic-0, magic-1, etc.)

    if ( substr( $variation, 0, 6 ) === 'magic-' ) {

        $index = (int) substr( $variation, 6 );

        if ( $index === 0 ) {

            return 'Variation ' . $alphabet[0] . ' (Original)';

        }

        return 'Variation ' . ( isset( $alphabet[ $index ] ) ? $alphabet[ $index ] : $index );

    }

    

    // Handle CSS test variations (test-css-TESTID-N)

    if ( preg_match( '/^test-css-(\d+)-(\d+)$/', $variation, $matches ) ) {

        $test_id = $matches[1];

        $var_num = (int) $matches[2];

        $index = $var_num - 1; // Convert 1-based to 0-based for alphabet

        if ( $index === 0 ) {

            return 'Variation ' . $alphabet[0] . ' (Original)';

        }

        return 'Variation ' . ( isset( $alphabet[ $index ] ) ? $alphabet[ $index ] : $var_num );

    }

    

    // Handle numeric post IDs (full page tests)

    if ( is_numeric( $variation ) ) {

        $post_id = (int) $variation;

        if ( get_post_status( $post_id ) ) {

            return get_the_title( $post_id );

        }

    }

    

    // Fallback: return the variation key as-is

    return $variation_label;

}




/**

 * Get current post/page identifier for location tracking.

 *

 * Returns an integer post ID for singular pages, or a descriptive string for

 * archives, taxonomies, and special pages (404, search, home).

 *

 * @return int|string

 */

function abst_get_current_post_id() {

    global $post;



    $gqo = get_queried_object();



    if ($gqo instanceof WP_Post_Type) {

        return 'post-type-archive-' . $gqo->name;

    }

    if (is_category()) {

        return 'category-' . $gqo->slug;

    }

    if (is_tag() || is_tax()) {

        return 'tax-' . $gqo->taxonomy . ' term-' . $gqo->slug;

    }

    if (is_author()) {

        return 'author-' . $gqo->user_login;

    }

    if (!empty($post)) {

        return $post->ID;

    }

    if (is_home() || is_front_page()) {

        $page_on_front = get_option('page_on_front');

        return $page_on_front ? (int) $page_on_front : 'home';

    }

    if (is_404()) {

        return '404-not-found';

    }

    if (is_search()) {

        return 'search';

    }



    return 0;

}



if (!function_exists('abst_get_current_post_id')) {

    function abst_get_current_post_id() { return abst_get_current_post_id(); }

}
