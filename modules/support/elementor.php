<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class ABST_Elementor
{
	public function __construct() 
	{	
    if(class_exists('\Elementor\Widget_Base')) {
      
      add_action( 'elementor/element/after_section_end', [$this, 'experiment_controls'], 99999999, 3 );
      add_action( 'elementor/editor/after_enqueue_scripts', [$this, 'enqueue_custom_script'] );
      add_action( 'elementor/editor/after_save', [$this, 'add_elementor_exp_meta'], 10, 2 );
    }
	}
  public function add_elementor_exp_meta( $post_id, $editor_data )
  {
    $content = get_post_meta($post_id, '_elementor_data', true);
    $experiments = [];

    preg_match_all('/"bt_experiment":"([1-9]+)","bt_variation":"(.*?)(?:")/', $content, $exp);      

    if( isset($exp[1]) && !empty($exp[1]) ) {
      foreach ($exp[1] as $key => $eid) {
        $experiments[] = [
          'eid' => $eid,
          'variation' => $exp[2][$key]
        ];
      }
      update_post_meta($post_id, 'bt_post_experiments', $experiments); // save EL modules to DB
    } else {
      delete_post_meta($post_id, 'bt_post_experiments'); // remove meta if post/page don't have experiment modules
    }
    
    update_post_meta($post_id, 'bt_post_experiments_editor', 'elementor');
  }

  public function enqueue_custom_script()
  {
    wp_enqueue_style( 'bt_elementor', BT_AB_TEST_PLUGIN_URI .'css/elementor.css', array(), BT_AB_TEST_VERSION );
  }



  /**
   * Add experiment controls to all widgets
   */
  public function experiment_controls( $element, $section_id, $args )
  {    
    if ('_section_responsive' === $section_id ) {
      
      if( !isset($args['tab'])){
        $args['tab'] = '';
      }
      $element->start_controls_section(
        'bt_experiment_section',
        [
          'tab' => \Elementor\Controls_Manager::TAB_ADVANCED,
          'label' => __( 'AB Split Test', 'ab-split-test-lite' ),
        ]
      );

      $experiments = apply_filters( 'abst_experiments_get_items', 'select' );

      $element->add_control(
        'bt_experiment',
        [
          'label' => __( 'Experiment', 'ab-split-test-lite' ),
          'type' => \Elementor\Controls_Manager::SELECT2,
          'multiple' => false,
          'options' => $experiments,
          'default' => 0,
          'description' => __( 'Select a test or ', 'ab-split-test-lite' ) . '<a class="new-on-page-test-button" href="' . esc_url( admin_url( 'edit.php?post_type=bt_experiments' ) ) . '" target="_blank">' . __( 'Create one here.', 'ab-split-test-lite' ) . '</a>'
        ]
      );
      
      $element->add_control(
        'bt_variation',
        [
          'label' => __( 'Variation Name', 'ab-split-test-lite' ),
          'type' => \Elementor\Controls_Manager::TEXT,
          'default' => '',
          'description' => __('Using "default" will cause this version to run first, unless otherwise targeted. <a href="#">more info <img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAoAAAAKCAYAAACNMs+9AAAAQElEQVR42qXKwQkAIAxDUUdxtO6/RBQkQZvSi8I/pL4BoGw/XPkh4XigPmsUgh0626AjRsgxHTkUThsG2T/sIlzdTsp52kSS1wAAAABJRU5ErkJggg==" alt="opens in a new window"></a>', 'ab-split-test-lite')
        ]
      );

      $element->add_control(
        'bt_hidden',
        [
          'label' => __( 'Variation Hidden', 'ab-split-test-lite' ),
          'type' => \Elementor\Controls_Manager::TEXT,
          'default' => 'false'
        ]
      );

      $element->end_controls_section();
    }  
  }

} // end class

$abst_elementor = new ABST_Elementor;


add_action( 'elementor/frontend/before_render', 'abst_add_attributes_to_element',9999 );
function abst_add_attributes_to_element( $element ) {


  // Get the settings
  $settings = $element->get_settings();

  // if there are settings, then render the attributes
  if( !empty($settings['bt_variation']) && !empty($settings['bt_experiment']) )
  {
      // Adding our type as a class to the element
      $element->add_render_attribute( '_wrapper', [
        'bt-eid' => $settings['bt_experiment'],
        'bt_hidden' => $settings['bt_hidden'],
        'bt-variation' =>  $settings['bt_variation'],
      ] );
	}
}


