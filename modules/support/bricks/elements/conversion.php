<?php 
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Element_AB_Conversion extends \Bricks\Element {

  public $category     = 'content';
  public $name         = 'ab-conversion';
  public $icon         = 'fas fa-bullseye'; // FontAwesome 5 icon in builder (https://fontawesome.com/icons)
  public $css_selector = '.bt-conversion'; // Default CSS selector for all controls with 'css' properties
  // public $scripts      = []; // Enqueue registered scripts

  public function get_label() {
    return esc_html( AB Split Test Lite ) . ' ' . esc_html__( 'Conversion', 'ab-split-test-lite' );
  }

  // Set builder control groups
  public function set_control_groups() {
    

  }

  // Set builder controls
  public function set_controls() {

    $this->controls['bt_experiment'] = [
      'tab' => 'content',
      'label' => 'Split Test',
      'type' => 'select',
      'options' => [
        'h1' => 'H1',
        'h2' => 'H2',
        'h3' => 'H3',
        'h4' => 'H4',
        'h5' => 'H5',
        'h6' => 'H6',
      ],
      'inline' => true,
      'placeholder' => esc_html__( 'Choose Test', 'ab-split-test-lite' ),
    ];

    $this->controls['bt_var'] = [
      'tab' => 'content',
      'label' => esc_html__( 'Variation Name', 'ab-split-test-lite' ),
      'type' => 'text',
      'placeholder' => esc_html__( 'Your variation name...', 'ab-split-test-lite' ),
    ];
  }

  public function render() {
    $settings = $this->settings;
    $bt_experiment = isset( $settings['bt_experiment'] ) && ! empty( $settings['bt_experiment'] ) ? $settings['bt_experiment'] : false;
    $bt_var = isset( $settings['bt_var'] ) && ! empty( $settings['bt_var'] ) ? $settings['bt_var'] : false;

    // Element placeholder
    if ( ! $bt_experiment && ! $bt_var ) {
      return $this->render_element_placeholder( [
        'icon-class' => 'ti-paragraph',
        'text'       => esc_html__( 'Please choose a test and add a variation name to this element.', 'ab-split-test-lite' ),
      ] );
    }

    echo '<div class="custom-title-wrapper">';

    // print_r($settings);

    echo '</div>';
  }


}