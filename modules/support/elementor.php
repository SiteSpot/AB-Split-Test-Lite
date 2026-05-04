<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class BT_BB_AB_Elementor
{
	public function __construct() 
	{	
    if(class_exists('\Elementor\Widget_Base')) {
      
  		add_action( 'elementor/widgets/register', [ $this, 'init_widgets' ],10 );
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

	/**
	 * Init Widgets
	 */
	public function init_widgets( $widgets_manager) 
	{
		// Register conversion widget
    $widgets_manager->register( new \BT_Elementor_Conversion() );

    // Register ab test redirect widget
   // $widgets_manager->register( new \BT_Elementor_AB_Redirect() ); //OLD RETIRED WIDGET
	}

  public function enqueue_custom_script()
  {
    wp_enqueue_style( 'bt_elementor', BT_AB_TEST_PLUGIN_URI .'css/elementor.css' );
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
      $fields   = BtConversionModule::get_fields();

      $element->start_controls_section(
        'bt_experiment_section',
        [
          'tab' => \Elementor\Controls_Manager::TAB_ADVANCED,
          'label' => __( 'AB Split Test', 'ab-split-test-lite' ),
        ]
      );

      $experiments = apply_filters( 'bt_experiments_get_items', 'select' );

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

$bt_bb_ab_elementor = new BT_BB_AB_Elementor;


/**
 * Conversion Widget.
 */
if(class_exists('\Elementor\Widget_Base'))
{

  class BT_Elementor_Conversion extends \Elementor\Widget_Base 
  {
    /**
     * Get conversion widget name.
     */
    public function get_name(): string {
      return 'bt_conversion';
    }

    /**
     * Get CONVERSION widget title.
     */
    public function get_title() 
    {
      return __( 'Split Test conversion', 'ab-split-test-lite' );
    }

    /**
     * Get conversion widget icon.
     */
    public function get_icon() 
    {
      return 'eicon-plus';
    }

    /**
     * Get conversion widget categories.
     */
    public function get_categories() 
    {
      return [ 'general' ];
    }

    protected function register_control_elements()
    {
      $experiments = apply_filters( 'bt_experiments_get_items', 'select' );
      $fields      = BtConversionModule::get_fields();



      $this->add_control(
        'bt_experiment_id',
        [
          'label' => esc_html( BT_AB_TEST_WL_ABTEST ),
          'type' => \Elementor\Controls_Manager::SELECT2,
          'multiple' => false,
          'options' => $experiments,
          'default' => 0,
          'description' => $fields['bt_experiment']['description']
        ]
      );

      $this->add_control(
        'bt_experiment_type',
        [
          'label' => __( 'Conversion Type', 'ab-split-test-lite' ),
          'type' => \Elementor\Controls_Manager::SELECT,
          'options' => [
            'load'  => __( 'On Page Load', 'ab-split-test-lite' ),
            'click' => __( 'On Element Click', 'ab-split-test-lite' )
          ],
          'default' => 'load',
          'description' => $fields['bt_experiment_type']['description'],
        ]
      );

      $this->add_control(
        'bt_click_conversion_selector',
        [
          'label' => __( 'Selector', 'ab-split-test-lite' ),
          'type' => \Elementor\Controls_Manager::TEXT,
          'default' => '',
          'conditions' => [
            'relation' => 'or',
            'terms' => [
              [
                'name' => 'bt_experiment_type',
                'operator' => '=',
                'value' => 'click',
              ],
            ],
          ],
          'description' => $fields['bt_click_conversion_selector']['description']
        ]
      );

    }

    /**
     * Register Conversion widget controls.
     */
    protected function register_controls() 
    {
      $this->start_controls_section(
        'conversion_section',
        [
          'label' => esc_html( BT_AB_TEST_WL_ABTEST ) . ' ' . __( 'Conversion Module', 'ab-split-test-lite' ),
          'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
        ]
      );
      $this->register_control_elements();
      $this->end_controls_section();
    }

    /**
     * Render Conversion widget output on the frontend.
     */
    protected function render() 
    {
      $settings = $this->get_settings_for_display();
      $fields   = BtConversionModule::get_fields();
      $attr     = '';

      foreach ($fields as $key => $value) {
        if( $settings[$key] != '' || $settings[$key] != null ) {
          $attr .= ' '. $key .'='. $settings[$key];
        }
      }

      echo do_shortcode('['. BT_BB_AB_Supports::$shortcode_name . $attr .']');
    }	

  }
}


add_action( 'elementor/frontend/before_render', 'ab_add_attributes_to_element',9999 );
function ab_add_attributes_to_element( $element ) {

  if( $element->get_name() === 'bt_ab_redirect' ||  $element->get_name() === 'bt_conversion' )
    return;

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


/**
 * AB Test redirect widget.
 */
if(class_exists('\Elementor\Widget_Base'))
{

  class BT_Elementor_AB_Redirect extends \Elementor\Widget_Base 
  {
    /**
     * Get widget name.
     */
	public function get_name(): string {
      return 'bt_ab_redirect';
    }

    /**
     * Get widget title.
     */
    public function get_title() 
    {
      return __( 'LEGACY: AB Test Page Redirect', 'ab-split-test-lite' );
    }

    /**
     * Get widget icon.
     */
    public function get_icon() 
    {
      return 'eicon-plus';
    }

    /**
     * Get widget categories.
     */
    public function get_categories() 
    {
      return [ 'general' ];
    }

    protected function register_control_elements()  // redirect element RETIRED
    {
      $experiments = apply_filters( 'bt_experiments_get_items', 'select' );
      $fields      = BtConversionModule::get_fields();
      $options     = BT_BB_AB_PageRedirect::abst_get_all_posts_grouped();

      $opt_group = [];

      if( !empty($options) ) {
        foreach ($options as $key => $option) {

          $items = [];

          foreach ($option as $i => $item) {
            $items[$item['id']] = $item['post_title'];
          }

          $opt_group[] = [
            'label' => $key,
            'options' => $items
          ];
        }
      }

      
      $this->add_control(
        'bt_experiment',
        [
          'label' => esc_html( BT_AB_TEST_WL_ABTEST ),
          'type' => \Elementor\Controls_Manager::SELECT2,
          'multiple' => false,
          'options' => $experiments, 
          'default' => 0,
          'description' => $fields['bt_experiment']['description']
        ]
      );
      $this->add_control(
        'bt_variation',
        [
          'label' => __( 'Variation', 'ab-split-test-lite' ),
          'type' => \Elementor\Controls_Manager::TEXT,
          'default' => '',
          'description' => 'Using "default" will cause this version to run first, unless otherwise targeted. <a href="#">more info <img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAoAAAAKCAYAAACNMs+9AAAAQElEQVR42qXKwQkAIAxDUUdxtO6/RBQkQZvSi8I/pL4BoGw/XPkh4XigPmsUgh0626AjRsgxHTkUThsG2T/sIlzdTsp52kSS1wAAAABJRU5ErkJggg==" alt="opens in a new window"></a>'
          ]
        );
      
      $this->add_control(
        'redirect_url',
        [
          'label' => __( 'Redirect URL', 'ab-split-test-lite' ),
          'type' => \Elementor\Controls_Manager::SELECT,
          'groups' => $opt_group,
          'default' => '',
        ]
      );
    }

    /**
     * Register redirect widget controls.
     */
    protected function _register_controls() 
    {
      $this->start_controls_section(
        'general_section',
        [
          'label' => esc_html( BT_AB_TEST_WL_ABTEST ) . ' ' . __( 'Page Redirect', 'ab-split-test-lite' ),
          'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
        ]
      );
      $this->register_control_elements();
      $this->end_controls_section();
    }

    /**
     * Render redirect widget output on the frontend.
     */
    protected function render() 
    {
      $settings = $this->get_settings_for_display();

      $attr = ' bt_experiment='. $settings['bt_experiment'] .' bt_variation='. $settings['bt_variation'] .' redirect_url='. $settings['redirect_url'];

      echo do_shortcode('['. BT_BB_AB_Supports::$shortcode_ab_test_redirect . $attr .']');
    } 

  }

}
