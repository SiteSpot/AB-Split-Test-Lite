<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$title = "NONE";
$eid = -1;

if(isset($settings->experiment) && $settings->experiment !== '' && $settings->experiment) {
  $experiment = get_post($settings->experiment);
  $redirect = ($settings->redirect_url)? get_the_permalink($settings->redirect_url) : false;
}

if(isset($experiment) && $redirect)
{
  $title = $experiment->post_title;
  $eid = $settings->experiment;
?>
<!-- bt-start-pageredirect-module -->
<div class="ab-test-page-redirect">
  <h5>
    AB test page redirect for experiment: <strong><?php echo esc_html( $title ); ?></strong>
  </h5>
  <h6>
    This page will be redirected to: <strong><?php echo esc_url( $redirect ); ?></strong>
  </h6>  
  <h5>
    <small>Only visible while page builder is active - not visible to logged out visitors</small>
  </h5>
</div>
<!-- bt-end-pageredirect-module -->
<div class="bt-redirect-handle" style="display: none !important" bt-variation="<?php echo esc_attr( $settings->variation ); ?>" bt-eid="<?php echo esc_attr( $settings->experiment ); ?>" bt-url="<?php echo esc_url( $redirect ); ?>"></div>
  <?php }else{
  
  echo '<!-- bt-start-pageredirect-module --><div class="ab-test-page-redirect"><h5>AB TEST PAGE REDIRECT</H5><H6>Choose an experiment, a variation and the redirect page to complete the setup.</h6></div><!-- bt-end-pageredirect-module -->';
  
}

 ?>

<style>
    /* hide it unless in fl builder */
  .fl-module-conversion,
  .wp-block-bt-experiments-gutenberg-conversion { 
    display:none;
  }
  .fl-builder-edit .fl-module-conversion {
    display:block;
  }
  
  .fl-builder-edit .ab-test-page-redirect,
  .block-editor-page .ab-test-page-redirect,
  .elementor-widget-container .ab-test-page-redirect {
    padding:10px;
    border: thin solid whitesmoke;
    background: repeating-linear-gradient(
      45deg,
      whitesmoke,
      whitesmoke 10px,
      white 10px,
      white 20px
    );

  }
  .ab-test-page-redirect *{
    text-align:center;
    color:#525252 !important;
  }
  body:not(.fl-builder-edit) .ab-test-page-redirect {
      display: none !important;
  }
  body.wp-admin .ab-test-page-redirect,
  body.elementor-editor-active .ab-test-page-redirect {
    display: block !important;
  }
</style>