<?php

/**
 * Main class for gutenberg support
 */
class BT_BB_AB_Gutenberg
{
	public function __construct()
	{
		add_action( 'enqueue_block_editor_assets', [$this, 'enqueue_scripts'] );
		add_action( 'save_post', [$this, 'add_gutenberg_exp_meta'], 10, 3 );
		add_filter( 'wp_kses_allowed_html', [$this, 'allow_bt_attributes'] );
		add_filter( 'wp_loaded',[$this,'register_attribute_for_blocks'], 999 );
	}
	public function allow_bt_attributes( $allowed )
	{
		// Only process if not already done (prevents timeout on large arrays)
		static $processed = false;
		if ($processed) {
			return $allowed;
		}
		$processed = true;
		
		foreach ($allowed as $tag => &$args) {
			if (is_array($args)) {
				$args['bt-eid'] = true;
				$args['bt-variation'] = true;
				$args['bt-url'] = true;
			}
		}
		unset($args); // Break reference
		return $allowed;
	}
	
	function register_attribute_for_blocks() {
		$registered_blocks = \WP_Block_Type_Registry::get_instance()->get_all_registered();
	
		foreach ( $registered_blocks as $name => $block ) {
			$block->attributes['bt-eid'] = array( 'type' => 'string' );
			$block->attributes['bt-variation'] = array( 'type' => 'string' );
			$block->attributes['bt-url'] = array( 'type' => 'string' );
		}
	}
	
    public function add_gutenberg_exp_meta( $post_id, $post, $update )
    {
      $experiments = [];

      $is_gutenberg = (false !== strpos( $post->post_content, '<!-- wp:' ));

      if( $is_gutenberg ) {

        preg_match_all('/<[a-z].*(?=bt-eid)(?:.*?(?:bt-eid)="([^"]+)")?(?:.*?(?:bt-variation)="([^"]+)")?[^>]*>/', $post->post_content, $exp);  

        if( isset($exp[1]) && !empty($exp[1]) ) {
          foreach ($exp[1] as $key => $eid) {
            $experiments[] = [
            	'eid' => $eid,
            	'variation' => $exp[2][$key]
            ];
          }        
          update_post_meta($post_id, 'bt_post_experiments', $experiments); // save GB modules to DB
        } else {
          delete_post_meta($post_id, 'bt_post_experiments'); // remove meta if post/page don't have experiment modules
        }   

        update_post_meta($post_id, 'bt_post_experiments_editor', 'gutenberg');
      }

      if( class_exists('\Elementor\Widget_Base') ) {
        if( !\Elementor\Plugin::$instance->db->is_built_with_elementor($post_id) && !$is_gutenberg ) {
          delete_post_meta($post_id, 'bt_post_experiments_editor');
        }
      }
    }

	/**
	 * Enqueue gutenberg support JS
	 */
	public function enqueue_scripts()
	{
		wp_register_script(
	 		'bt-gutenberg',
			plugins_url('js/gutenberg.js', dirname(dirname(__FILE__)) ),
			[
				'wp-blocks',
				'wp-block-editor',
				'wp-components',
				'wp-compose',
				'wp-dom-ready',
				'wp-editor',
				'wp-element',
				'wp-hooks'
			],
			BT_AB_TEST_VERSION
		);
    
    	wp_localize_script( 'bt-gutenberg', 'bt_gutenberg', [
			'experiments' 		=> json_encode(apply_filters( 'bt_experiments_get_items', 'all' )),
			'editor_html' 		=> apply_filters( 'bt_experiments_conversion_html', '' ),
			'conversion_fields' => json_encode( BtConversionModule::get_fields() ),
			'nonce' 			=> wp_create_nonce('bt_gutenberg_ab_test_html'),
			'shortcode_name'	=> BT_BB_AB_Supports::$shortcode_name,
			'actions' 			=> [
				'render_ab_test_html' => 'render_ab_test_html'
			],
			'ajax_url'			=> admin_url( "admin-ajax.php" ),
			'admin_url'			=> get_admin_url()
		]);

		wp_enqueue_script( 'bt-gutenberg' );

		wp_enqueue_script( 'bt-gutenberg-ab-redirect', 
			plugins_url('js/gutenberg-ab-redirect.js', dirname(dirname(__FILE__)) ),
			[
				'wp-blocks',
				'wp-block-editor',
				'wp-components',
				'wp-compose',
				'wp-dom-ready',
				'wp-editor',
				'wp-element',
				'wp-hooks'
			],			
			BT_AB_TEST_VERSION
		);

    	wp_localize_script( 'bt-gutenberg-ab-redirect', 'bt_gutenberg_ab_redirect', [
			'experiments' 		=> json_encode(apply_filters( 'bt_experiments_get_items', 'all' )),
			'editor_html' 		=> apply_filters( 'bt_experiments_ab_page_redirect_html', '' ),
			'conversion_fields' => json_encode( BtConversionModule::get_fields() ),
			'nonce' 			=> wp_create_nonce('bt_gutenberg_ab_test_redirect_html'),
			'redirect_list'		=> BT_BB_AB_PageRedirect::abst_get_all_posts_grouped(),
			'shortcode_name'	=> BT_BB_AB_Supports::$shortcode_ab_test_redirect,
			'actions' 			=> [
				'render_html' => 'render_ab_test_redirect_html'
			],
			'ajax_url'			=> admin_url( "admin-ajax.php" ),
			'admin_url'			=> get_admin_url(),
			'option_html'		=> ''
		]);

	}

} // end class

$bt_bb_ab_gutenberg = new BT_BB_AB_Gutenberg;