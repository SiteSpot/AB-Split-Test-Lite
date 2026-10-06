<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class ABST_Supports
{
	public static $shortcode_abtest_variation = 'abst_test';

	public function __construct()
	{
		add_shortcode( self::$shortcode_abtest_variation, [$this, 'support_ab_redirect_variation'] );

		add_filter( 'abst_experiments_get_items', [$this, 'get_experiments'], 10, 1 );


		$this->load_supports();
	}

	public function load_supports()
	{
                include_once plugin_dir_path( dirname(dirname(__FILE__)) ) .'/modules/support/gutenberg.php';
                include_once plugin_dir_path( dirname(dirname(__FILE__)) ) .'/modules/support/elementor.php';
                include_once plugin_dir_path( dirname(dirname(__FILE__)) ) .'/modules/support/breakdance.php';
                include_once plugin_dir_path( dirname(dirname(__FILE__)) ) .'/modules/support/bricks/bricks.php';
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
		// When another abst_experiments_get_items callback has already built the list, keep
		// it: treating that array as the $type returned the wrong shape, which emptied the
		// Elementor test picker.
		if ( is_array( $type ) ) {
			return $type;
		}

		$posts = get_posts([
			'post_type' 	 => 'abst_experiments',
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

$abst_support = new ABST_Supports;
