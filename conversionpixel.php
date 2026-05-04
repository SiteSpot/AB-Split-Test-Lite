<?php

// conversion iframe but browsers dont support this much anymore so use the image instead 
add_action( 'wp_ajax_nopriv_abtrk', 'get_ab_iframe' );
add_action( 'wp_ajax_abtrk', 'get_ab_iframe' );


function get_ab_iframe(){
  // cors header before any content
  // X-Frame-Options to allow any domain
  header("Content-Security-Policy: frame-ancestors *");
  header('Access-Control-Allow-Origin: *');
$path = plugins_url('js/bt_conversion.js', __FILE__);
$eid = intval($_GET['eid']);
if($eid < 1){
    wp_die();
}
    ?>
    <head>
<script
  src="https://code.jquery.com/jquery-3.6.0.min.js"
  integrity="sha256-/xUj+3OJU5yExlq6GSYGSHk7tPXikynS7ogEvDej/m4="
  crossorigin="anonymous"></script>

<script <?php echo esc_attr( ABST_CACHE_EXCLUDES ); ?> src="<?php echo esc_url( $path ); ?>"></script>
<script>
var bt_ajaxurl = "<?php echo esc_url( admin_url( 'admin-ajax.php', 'relative' ) ); ?>";
var btab_vars = {"is_preview":'',"post_id":<?php echo intval( $eid ); ?>,};
var btab_experiment_info = abstGetCookie("btab_<?php echo intval( $eid ); ?>");
if(typeof btab_experiment_info !== 'undefined')
{
  btab = JSON.parse(btab_experiment_info);
  if(btab.conversion == 0)
    bt_experiment_w(btab.eid,btab.variation,'conversion', 'ex'); // fire the conversion with the special "ex" url so thet no js fires after conversion
  else
    console.log('[AB Split Test: '+btab.eid+'] This visitor already converted');
}
else
{
  console.log('[AB Split Test] This visitor not seen on other site.');
}
</script>
</head><body><!-- AB SPLIT TEST CONVERSION --></body>
<?php
    wp_die();
}
