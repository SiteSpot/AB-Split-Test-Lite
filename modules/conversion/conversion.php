<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if(! class_exists ( 'BtConversionModule'))
{
  class BtConversionModule {

    /** 
     * Constructor function for the module. You must pass the
     * name, description, dir and url in an array to the parent class.
     *
     * @method __construct
     */  
    public function __construct() 
    {
      add_action( 'rest_api_init', [$this, 'register_route'] ); 
      add_filter('content_save_pre', [$this, 'fix_post_content'], 999999);
    }

    public function fix_post_content( $content )
    {
      $content = preg_replace('/<!-- bt-start-conversion-module -->.*?<!-- bt-end-conversion-module -->/s', '', $content);

      return $content;
    }

    public function register_route()
    {
      register_rest_route( 'bt_bb_ab_conversion/v1', '/add', [
        'methods' => 'POST',
        'callback'  => [$this, 'add_conversion'],
        'permission_callback' => '__return_true',
      ]);
    }

    public function add_conversion()
    {
      $eid = absint($_POST['eid']);
      $variation = sanitize_text_field($_POST['variation']);

      $exp_data = (array) get_post_meta($eid,'observations',true);

      if( array_key_exists($variation, $exp_data) ) { // check if variation exists
        do_action('bt_log_experiment_activity', $eid, $variation, 'conversion', true);
        return new WP_REST_Response([
          'status'  => 1
        ], 200);        
      }      

      return new WP_REST_Response([
        'status'  => 0,
        'message' => 'AB BT_AB_TEST_WL_ABTEST Lite' . ': ' . __( 'Variation name does not exist', 'ab-split-test-lite' )
      ], 200);
    }

    public static function get_fields()
    {
        return array( // Section Fields
        'bt_experiment'     => array(
          'type'          => 'suggest',
          'label'         => BT_AB_TEST_WL_ABTEST,
          'action'        => 'fl_as_posts', // Search posts.
          'data'          => 'bt_experiments', // Slug of the post type to search.
          'limit'         => 1, // Limits the number of selections that can be made.
          'description'   => 'Select a test',
          'bt_gutenberg_type' => 'string', // set type for gutenberg support field mapping
        ),
        'bt_experiment_type'     => array(
          'type'          => 'select',
          'label'         => __( 'Conversion Type', 'ab-split-test-lite' ),
          'options'       => array(                    
            'load'          => 'On Page Load',
            'click'         => 'On Element Click',
          ),
          'toggle'        => array(
            'click'         => array(
              'fields'        => array( 'bt_click_conversion_selector'),
            ),
          ),
          'default'       => 'load',
          'description'   => 'Convert on page load or on click',
          'bt_gutenberg_type' => 'string', // set type for gutenberg support field mapping
        ),
        'bt_click_conversion_selector'  => array(
          'type'          => 'text',
          'label'         => __( 'Selector', 'ab-split-test-lite' ),
          'description'   => 'Selector for element that will trigger a conversion on click. <a href="https://www.w3schools.com/cssref/css_selectors.asp" target="_blank">More info on selectors. <img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAoAAAAKCAYAAACNMs+9AAAAQElEQVR42qXKwQkAIAxDUUdxtO6/RBQkQZvSi8I/pL4BoGw/XPkh4XigPmsUgh0626AjRsgxHTkUThsG2T/sIlzdTsp52kSS1wAAAABJRU5ErkJggg==" alt="opens in a new window" ></a>',
          'bt_gutenberg_type' => 'string', // set type for gutenberg support field mapping
        ),
      );
    }

    public static function encode($id) 
    {
      $key = self::key();
      $iv = self::_getIv();
      $ciphertext = openssl_encrypt($id, 'aes-128-cbc', $key, $options = OPENSSL_RAW_DATA, $iv);
      $encryptedSessionId = base64_encode($ciphertext);

      return $encryptedSessionId;
    }

    public static function decode($encryptedSessionId) 
    {
      $key = self::key();
      $iv = self::_getIv();
      $decoded = base64_decode($encryptedSessionId, TRUE);
      $decryptedSessionId = openssl_decrypt($decoded, 'aes-128-cbc', $key, $options = OPENSSL_RAW_DATA, $iv);
      $id = rtrim($decryptedSessionId);

      return $id;
    }

    public static function _getIv() 
    {
      $ivlen = openssl_cipher_iv_length('aes-128-cbc');
      return substr(md5(self::key()), 0, $ivlen);
    } 

    private static function key()
    {
      return ' -Z#GJX11`v*q.0}eI{ky~=~u6jAm+&f~{WG9H2Xi%^4w=,4*GAi0)P7Kl6Ki7|2';
    }

  } // end class

  $bt_conversion_module = new BtConversionModule;
}


if( class_exists('FLBuilderModule') ) {

  class Bt_BB_ConversionModule extends FLBuilderModule 
  {
    public function __construct()
    {
      parent::__construct(array(
        'name'          => __('AB test conversion', 'ab-split-test-lite'),
        'description'   => __('Trigger the conversion event of your AB test when this module is loaded. Does not display anything.', 'ab-split-test-lite'),
        'category'      => apply_filters( 'bt_bb_ab_conversion_category','Utilities'),
        'group'         => apply_filters( 'bt_bb_ab_conversion_group', 'AB BT_AB_TEST_WL_ABTEST Lite'),
        'dir'           => BT_CONVERSION_DIR . 'modules/conversion',
        'url'           => BT_CONVERSION_URL . 'modules/conversion',
      ));  
    }  
  }

  /**
   * Register the module and its form settings.
   */
  FLBuilder::register_module('Bt_BB_ConversionModule', array(
      'general'       => array( // Tab
        'title'         => __('General', 'ab-split-test-lite'), // Tab title
        'sections'      => array( // Tab Sections
          'general'       => array( // Section
            'title'         => BT_AB_TEST_WL_ABTEST . ' ' . __( 'Conversion Module', 'ab-split-test-lite' ), // Section Title
            'fields'        => BtConversionModule::get_fields()
          ),
        )
      )
  ));  
}