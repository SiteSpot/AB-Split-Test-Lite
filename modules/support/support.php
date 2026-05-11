<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class BT_BB_AB_Supports
{
	public static $shortcode_name = 'abtest_conversion';
	public static $shortcode_abtest_variation = 'test';

	public function __construct()
	{
		add_shortcode( self::$shortcode_name, [$this, 'support_conversion_shortcode'] );
		add_shortcode( self::$shortcode_abtest_variation, [$this, 'support_ab_redirect_variation'] );

		add_filter( 'bt_experiments_get_items', [$this, 'get_experiments'], 10, 1 );
		add_filter( 'bt_experiments_conversion_html', [$this, 'get_conversion_html'], 10, 1 );

		add_action( 'wp_ajax_render_ab_test_html', [$this, 'render_ab_test_html'] );
		add_action( 'wp_ajax_nopriv_render_ab_test_html', [$this, 'render_ab_test_html'] );

		$this->load_supports();
	}

	public function load_supports()
	{
                include_once plugin_dir_path( dirname(dirname(__FILE__)) ) .'/modules/support/gutenberg.php';
                include_once plugin_dir_path( dirname(dirname(__FILE__)) ) .'/modules/support/elementor.php';
                include_once plugin_dir_path( dirname(dirname(__FILE__)) ) .'/modules/support/breakdance.php';
                include_once plugin_dir_path( dirname(dirname(__FILE__)) ) .'/modules/support/bricks/bricks.php';
	}

	public function get_conversion_html( $param = [] )
	{
		if( !empty($param) ) {
			$settings = (object) $param;
		}

		ob_start();

		include plugin_dir_path( dirname(dirname(__FILE__)) ) .'/modules/conversion/includes/frontend.php';

		return ob_get_clean();
	}

	public function render_ab_test_html()
	{
		if( ! isset($_POST['nonce']) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'bt_gutenberg_ab_test_html' ) ) { wp_die('sorry..'); }

		$attr = '';

		foreach ($_POST['data'] as $key => $value) {
			if( $value != '' ) {
				$attr .= ' '. $key .'='. sanitize_text_field( $value );
			}			
		}

		echo do_shortcode('['. self::$shortcode_name .' '. $attr .']');

		wp_die();		
	}

	public static function get_shortcode_args()
	{
		$fields = BtConversionModule::get_fields();
		$new_fields = [];

		foreach ($fields as $key => $value) {
			$default_val = (isset($value['default']))? $value['default'] : '';
			$new_fields[$key] = $default_val;
		}

		return $new_fields;
	}

	public function support_conversion_shortcode( $atts )
	{
		$fields = self::get_shortcode_args();
		$attr   = shortcode_atts($fields, $atts);

		return $this->get_conversion_html( $attr );
	}

	public function support_ab_redirect_variation( $atts,$content )
	{

		$attr = shortcode_atts([
        'eid' => -1,
		'id' => -1,
        'variation' => '',
        'class' => ''
      ], $atts);

		$eid = $attr['eid'];
		$id = $attr['id'];
		$variation = $attr['variation'];
		$class = $attr['class'];

		if(empty($eid) || $eid == -1)
			$eid = $id;

      ob_start();

      echo '<div class="bt-abtest-wrap ' . esc_attr( $class ) . '" bt-eid="' . esc_attr( $eid ) . '" bt-variation="' . esc_attr( $variation ) . '">';
        echo wp_kses_post( $content );
      echo '</div>';

      return ob_get_clean();
	}

	/**
	 * Get all published experiments
	 */
	public function get_experiments( $type )
	{
		$posts = get_posts([
			'post_type' 	 => 'bt_experiments',
			'post_status' 	 => 'publish',
			'posts_per_page' => -1
		]);

		$experiments = [];
		$experiments_select = [];
		$experiments[] = ['label' => 'None', 'value' => ''];
		$experiments_select = [0 => 'None'];

		foreach ($posts as $key => $item) {
			$experiments[] = [
				'label' => $item->post_title,
				'value' => $item->ID
			];

			$experiments_select[$item->ID] = $item->post_title;
		}

		$arr = [];

		switch ($type) {
			case 'experiments':
				$arr = $experiments;
				break;
			case 'select':
				$arr = $experiments_select;
				break;
			default:
				$arr = [
					'experiments' => $experiments,
					'experiments_select' => $experiments_select
				];
				break;
		}

		return $arr;
	}

} // end class

$bt_bb_ab_support = new BT_BB_AB_Supports;