<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * $module An instance of your module class.
 * $settings The module's settings.
 *
 */


$abst_title = "NONE";
$abst_eid = -1;
$abst_type = false;
$abst_selector = '';

if(isset($settings->bt_experiment) && $settings->bt_experiment !== '' && $settings->bt_experiment)
  $abst_experiment = get_post($settings->bt_experiment);

if(isset($abst_experiment))
{
  $abst_title = $abst_experiment->post_title;
  $abst_eid = $settings->bt_experiment;
  $abst_type = $settings->bt_experiment_type;
  $abst_selector = $settings->bt_click_conversion_selector;
?>
<div class="conversion-module">
  <h5>
    <span style="display: none;">{{</span>AB test conversion for experiment: <strong><?php echo esc_html( $abst_title ); ?></strong>
  </h5>
  
  <h6>
    A conversion will trigger on: <strong><?php echo esc_html( $abst_type ); ?></strong>
  </h6>
  
  <?php if($abst_type == 'click'){ ?>
  <h6>
    Selector: "<?php echo esc_html( $abst_selector ); ?>"
  </h6>
  
  <?php } ?>
  <h5>
    <small>Only visible while page builder is active - not visible to logged out visitors<span style="display: none;">}}</span></small>
  </h5>
</div>
<!-- bt-end-conversion-module -->
  <?php }else{
  
  echo '<!-- bt-start-conversion-module --><div class="conversion-module"><h5>AB TEST CONVERSION</H5><H6>Choose an experiment to complete setup.</h6></div><!-- bt-end-conversion-module -->';
  
}

 ?>


<?php 

  $abst_conversion_vars = json_encode([
    'eid'       => $abst_eid,
    'title'     => esc_html( $abst_title ),
    'type'      => esc_html( $abst_type ),
    'selector'  => esc_html( $abst_selector )
  ]);

?>
<script <?php echo esc_attr( ABST_CACHE_EXCLUDES ); ?> type="text/javascript">
if(window.bt_conversion_vars)
  bt_conversion_vars.push(<?php echo wp_json_encode( json_decode( $abst_conversion_vars ) ); ?>);
</script>
