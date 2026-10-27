<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Main class for gutenberg support
 */
class ABST_Gutenberg
{
	public function __construct()
	{
		add_action( 'enqueue_block_editor_assets', [$this, 'enqueue_scripts'] );
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
	
	/**
	 * Enqueue gutenberg support JS
	 */
	public function enqueue_scripts()
	{
		wp_register_script(
	 		'abst-gutenberg',
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
			ABST_VERSION,
			true
		);
    
    	wp_localize_script( 'abst-gutenberg', 'abst_gutenberg', [
			'experiments' 		=> json_encode(apply_filters( 'abst_experiments_get_items', 'all' )),
			'ajax_url'			=> admin_url( "admin-ajax.php" ),
			'admin_url'			=> get_admin_url(),
			'blocks_list_nonce'	=> wp_create_nonce('abst_blocks_experiment_list'),
			'version_label'		=> __( 'Version', 'ab-split-test-lite' ),
			'variation_choices'	=> array_map(
				function ( $value, $label ) {
					return [ 'value' => $value, 'label' => $label ];
				},
				array_keys( abst_onpage_variation_choices() ),
				array_values( abst_onpage_variation_choices() )
			),
		]);

		wp_enqueue_script( 'abst-gutenberg' );

	}

} // end class

$abst_gutenberg = new ABST_Gutenberg;
