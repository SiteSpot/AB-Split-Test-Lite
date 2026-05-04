<?php

/**
 * $module An instance of your module class.
 * $settings The module's settings.
 *
 */


$title = "NONE";
$eid = -1;
$type = false;
$selector = '';

if(isset($settings->bt_experiment) && $settings->bt_experiment !== '' && $settings->bt_experiment)
  $experiment = get_post($settings->bt_experiment);

if(isset($experiment))
{
  $title = $experiment->post_title;
  $eid = $settings->bt_experiment;
  $type = $settings->bt_experiment_type;
  $selector = $settings->bt_click_conversion_selector;
?>
<div class="conversion-module">
  <h5>
    <span style="display: none;">{{</span>AB test conversion for experiment: <strong><?php echo esc_html( $title ); ?></strong>
  </h5>
  
  <h6>
    A conversion will trigger on: <strong><?php echo esc_html( $type ); ?></strong>
  </h6>
  
  <?php if($type == 'click'){ ?>
  <h6>
    Selector: "<?php echo esc_html( $selector ); ?>"
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

  $bt_conversion_vars = json_encode([
    'eid'       => $eid,
    'title'     => esc_html( $title ),
    'type'      => esc_html( $type ),
    'selector'  => esc_html( $selector )
  ]);

?>
<script <?php echo esc_attr( ABST_CACHE_EXCLUDES ); ?> type="text/javascript">
if(window.bt_conversion_vars)
  bt_conversion_vars.push(<?php echo wp_json_encode( json_decode( $bt_conversion_vars ) ); ?>);
</script>