<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if( !class_exists('BT_BB_AB_PageRedirect') )
{
	class BT_BB_AB_PageRedirect
	{
		public function __construct()
		{
			add_action( 'enqueue_block_editor_assets', [$this, 'load_scripts'] );
			add_filter('content_save_pre', [$this, 'fix_post_content'], 999999);
		}

		public function load_scripts()
		{
			wp_enqueue_script(
		 		'bt-gutenberg-ab-redirect-misc',
				BT_AB_TEST_PLUGIN_URI . 'modules/page-redirect/js/jquery.onmutate.min.js',
				[],
				BT_AB_TEST_VERSION,
				true
			);			
		}

	    public function fix_post_content( $content )
	    {
	      $content = preg_replace('/<!-- bt-start-pageredirect-module -->.*?<!-- bt-end-pageredirect-module -->/s', '', $content);

	      return $content;
	    }

		public static function abst_get_all_posts_grouped()
		{

			$grouped_posts = get_transient('abst_all_posts'); // save it for a day to save server
			if(empty($grouped_posts))
			{
				$selected_post_types = post_types_to_test();

				$grouped_posts = [];
				$posts = new WP_Query([
					'post_type' 	 => $selected_post_types,
					'posts_per_page' => 200,
					'fields' => 'ids',
					'public' => true
				]);

				foreach ($posts->posts as $key => $post) {
					$post_type = strtoupper(get_post_type($post));

					if( trim(get_the_title($post)) != '' ) {
						$grouped_posts[$post_type][] = [
							'id' => $post,
							'post_title' => get_the_title($post)
						];
					}
				}
				set_transient( 'abst_all_posts', $grouped_posts, DAY_IN_SECONDS );
			}

			return $grouped_posts;
		}
	
	}

	$BT_BB_AB_PageRedirect = new BT_BB_AB_PageRedirect;


if( class_exists('FLBuilderModule') ) {

	class BT_BB_AB_PageRedirectModule extends FLBuilderModule 
	{
		public function __construct()
		{
			parent::__construct(array(
				'name'          => __('LEGACY, DONT USE. AB Test Page Redirect', 'ab-split-test-lite'),
				'description'   => __('This module has now been replaced. Remove this module and update your AB test settings.', 'ab-split-test-lite'),
				'category'      => apply_filters( 'bt_bb_ab_page_redirect_category','Utilities'),
				'group'         => apply_filters( 'bt_bb_ab_page_redirect_group', AB Split Test Lite),
				'dir'           => BT_MODULES_DIR . 'modules/page-redirect',
				'url'           => BT_MODULES_URL . 'modules/page-redirect',
			));  

			add_action( 'wp_enqueue_scripts', [$this, 'enqueue_scripts'] );	      
			add_filter( 'fl_builder_custom_fields', [$this, 'add_custom_setting_fields'] );
		}  

		public function add_custom_setting_fields( $fields ) 
		{
			$fields['bt-bb-ab-redirect_url'] = BT_MODULES_DIR .'/modules/page-redirect/fields/optgroup.php';
			return $fields;
		}

		public function enqueue_scripts()
		{
			if( class_exists('FLBuilderModel') && FLBuilderModel::is_builder_active() ) 
			{
				$posts = BT_BB_AB_PageRedirect::abst_get_all_posts_grouped();

				wp_enqueue_script( 'ab-page-redirect', BT_AB_TEST_PLUGIN_URI . 'modules/page-redirect/js/page-redirect.js', array(), '', true );  
				wp_localize_script( 'ab-page-redirect', 'bt_bb_ab_predirect_vars', [
					'posts' => json_encode($posts),
					'select' => '<option></option>'
				]);		
			}		    
		}    
	}

  /**
   * Register the module and its form settings.
   */
  FLBuilder::register_module('BT_BB_AB_PageRedirectModule', array(
      'general'       => array( // Tab
        'title'         => __('General', 'ab-split-test-lite'), // Tab title
        'sections'      => array( // Tab Sections
          'general'       => array( // Section
            'title'         => BT_AB_TEST_WL_ABTEST . ' ' . __( 'Page Redirect Module', 'ab-split-test-lite' ), // Section Title
            'fields'        => [
				'experiment' => [
					'type'          => 'suggest',
					'label'         => __( 'Experiment', 'ab-split-test-lite' ),
					'action'        => 'fl_as_posts', // Search posts.
					'data'          => 'bt_experiments', // Slug of the post type to search.
					'limit'         => 1, // Limits the number of selections that can be made.
					'description'   => '<a href="' . admin_url( 'edit.php?post_type=bt_experiments' ) . '" target="_blank">View or create experiments. <img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAoAAAAKCAYAAACNMs+9AAAAQElEQVR42qXKwQkAIAxDUUdxtO6/RBQkQZvSi8I/pL4BoGw/XPkh4XigPmsUgh0626AjRsgxHTkUThsG2T/sIlzdTsp52kSS1wAAAABJRU5ErkJggg==" alt="opens in a new window" ></a>',
				],
				'variation' => [
					'type'        => 'text',
					'label'       => 'Variation Name',
					'description' => 'Using "default" will cause this version to run first, unless otherwise targeted. <a href="#">more info <img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAoAAAAKCAYAAACNMs+9AAAAQElEQVR42qXKwQkAIAxDUUdxtO6/RBQkQZvSi8I/pL4BoGw/XPkh4XigPmsUgh0626AjRsgxHTkUThsG2T/sIlzdTsp52kSS1wAAAABJRU5ErkJggg==" alt="opens in a new window"></a>',
				],
				'redirect_url'	=> [
					'type'  => 'bt-bb-ab-redirect_url',
					'label' => 'Redirect URL',
				],
				'script' => array(
					'type'    => 'raw',
					'content' => '<script '.ABST_CACHE_EXCLUDES.' type="text/javascript">console.log(jQuery("#bt-bb_redirect_url_handle").val()); var bt_bb_ab_redirect_val = jQuery("#bt-bb_redirect_url_handle").val(); jQuery("#bb_redirect_url").html(bt_bb_ab_predirect_vars.select).val(bt_bb_ab_redirect_val);</script>',
				),				
            ]
          ),
        )
      )
  ));  
}	
}