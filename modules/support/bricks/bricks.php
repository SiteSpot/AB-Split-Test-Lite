<?php
if ( ! defined( 'ABSPATH' ) ) exit;


class BT_BB_AB_Bricks
{
	public function __construct() 
	{
    
    //get all elements and add to their layout
    add_filter( 'bricks/builder/elements',array( $this,'bricks_filter_builder_elements'),10,1 );

    //render attributes to elements
    add_filter('bricks/element/render_attributes',[$this,'add_bricks_attributes'],10,3);

    //get experiments
    add_action( 'wp_ajax_all_experiments', [$this,'all_ab_tests_json'] );

    //add to all elements
    $this->addToBricks();

	}

function all_ab_tests_json( ) {
    check_ajax_referer('abst_bricks_nonce', 'nonce');
    if (!current_user_can('edit_posts')) {
        wp_send_json_error('Unauthorized');
    }
    wp_send_json($this->tests_with_id());
}

function tests_with_id(){
  $testTransient = get_transient('bt_bb_ab_bricks_tests');
  //if($testTransient)
    //return $testTransient;

  $posts = get_posts(array(
    'post_type'      => 'bt_experiments',
    'post_status'    => 'publish',
    'suppress_filters' => false,
    'posts_per_page' => -1,
    'orderby' => 'date',
    'order' => 'DESC',
    'meta_query'     => array(
        array(
            'key'   => 'test_type',
            'value' => 'ab_test',
            'compare' => '='
        ) 
      )
    ));
    $postlist = [];
    foreach ( $posts as $post ) {
      $postlist[$post->ID] = $post->post_title;
    }

    set_transient('bt_bb_ab_bricks_tests', $postlist,10);

    return $postlist;
}

function add_bricks_attributes($attributes, $key, $element){

      if(!empty($element->settings['bt_experiment']))
        $attributes[$key]['bt-eid'] = [$element->settings['bt_experiment']];
      if(!empty($element->settings['bt_var']))
        $attributes[$key]['bt-variation'] = [$element->settings['bt_var']];

    return $attributes;
}


  function bricks_filter_builder_elements( $elements ) {

    $stored = get_option('ab_bricks_elements');
    if ($stored !== $elements) {
        update_option('ab_bricks_elements', $elements);
    }
    return $elements;

  }


  function addToBricks(){

      $allBricks = get_option('ab_bricks_elements');

      if(!empty($allBricks))
      {
        foreach($allBricks as $brick)
        {

          add_filter( 'bricks/elements/'.$brick.'/control_groups', function( $control_groups ) {
                $control_groups['abst'] = [
                    'tab'      => 'style', // or 'style'
                    'title'    => esc_html__( 'AB Split Test', 'ab-split-test-lite' ),
                ];

                return $control_groups;
            } );



          add_filter( 'bricks/elements/'.$brick.'/controls', function( $controls ) {

            $controls['bt_info'] = [
              'tab' => 'style',
              'group' => 'abst',
              'content' =>  '<a class="new-on-page-test-button" href="' . admin_url( 'post-new.php?post_type=bt_experiments&test_type=ab_test' ) . '" target="_blank">Create a new test.</a><BR><BR>Or choose an existing test below.',
              'type' => 'info',
              'styles' => 'muted', 
            ];
            

            $controls['bt_experiment'] = [
              'tab' => 'style',
              'group' => 'abst',
              'label' => 'Split Test',
              'type' => 'select',
              'options' => $this->tests_with_id(),
              'inline' => true,
              'placeholder' => esc_html__( 'Choose Test', 'ab-split-test-lite' ),
            ];

            $controls['bt_var'] = [
              'tab' => 'style',
              'group' => 'abst',
              'label' => esc_html__( 'Variation Name', 'ab-split-test-lite' ),
              'type' => 'text',
              'inline' => true,
              'placeholder' => esc_html__( 'Your variation name...', 'ab-split-test-lite' ),
            ];

        
            return $controls;
          } );
        }
      }


  }
} // end class

$bt_bb_ab_bricks = new BT_BB_AB_Bricks;


add_filter( 'bricks/element/set_root_attributes', function( $attributes, $element ) {
      if(!empty($element->settings['bt_experiment']))
        $attributes['bt-eid'] = $element->settings['bt_experiment'];
      if(!empty($element->settings['bt_var']))
        $attributes['bt-variation'] = $element->settings['bt_var'];

    return $attributes;
}, 10, 2 );
