<?php

/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       http://absplittest.com
 * @since      0.9.1
 *
 * @package    Bt_Ab_Tests
 * @subpackage Bt_Ab_Tests/admin/partials
 */
//get default and saved settings

$post_types = get_post_types(array('public' => true), 'objects');
$selected_post_types = ab_get_admin_setting('selected_post_types');
$detected_caches = !empty(abst_get_detected_caches()) ? implode(', ', abst_get_detected_caches()) : 'None detected';


// Set delete_fingerprint_db_on_uninstall to be checked by 
// welcome zone
if(!empty($_GET['wizard']) && current_user_can('edit_posts')) {
//get username of curent wordpres user
  $currentusername = wp_get_current_user()->user_login;

  echo "<style>
  h1{display:none;}

  div#abwelcomearea {
    background: white;
    padding: 20px;
    max-width: 900px;
  }
  h2{font-size:1.8em;}
  .video-container {
    position: relative;
    padding-bottom: 56.25%; /* 16:9 aspect ratio */
    height: 0;
    overflow: hidden;
  }
  .video-container iframe {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
  }
  /* make features list 50% width*/
  .features ul li {
    width: 45%;   
    display: inline-block;
    text-align: left;
  }
  .features{
    background: #f7f7f7;
    margin-top:20px;
    margin-bottom:20px;
    padding:20px;
  }

  </style>
  <div id='abwelcomearea'>
  <img src='" . BT_AB_TEST_LITE_PLUGIN_URI ."/includes/img/logo.svg' style='max-width: 150px;' />
  <h2>AB Split Test is activated, ".esc_html($currentusername)."</h2>
<!--  <div class='tour-info'>
  <h3>Create your first test in 2 minutes.</h3>
  <p>Our guided set up will walk you through setting up your first test.</p>
      <a href='". esc_html(admin_url()) . "post-new.php?post_type=bt_experiments&wizard=3' class='button button-large'>Create a Test</a>  <BR><BR>
      or
  </div>-->
  <h3>Watch the quick video that explains everything.</h3>
  <div class='video-container'>
    <iframe src='https://share.descript.com/embed/Vbz3Q3aJu05' width='640' height='360' frameborder='0' allowfullscreen></iframe>
  </div>
  </div>";
  }

?>

<div id="fl-bt_bb_ab_test-form" class="fl-settings-form">
  
  <form id="bt-bb-ab-form" action="<?php echo esc_html(BT_BB_AB_Lite_Admin::get_current_settings_url()); ?>" method="post">
    <?php wp_nonce_field('bt-bb-ab-nonce', 'bt-bb-ab-nonce'); ?>


      <p>Need a hand? Watch the <a href="<?php echo esc_html(admin_url()) ?>options-general.php?page=bt_bb_ab_test&wizard=1">walkthrough video</a>  or check out the <a href="https://absplittest.com/documentation/" target="_blank">documentation</a>.</p>

      <div class="fl-settings-form-content">



      <p><a target="_blank" href='https://absplittest.com/pricing?utm_source=ug'>Upgrade your account</a> for Analytics integrations, White Label branding, Reporting, Webhooks and more.</p>


    <h3>Plugin Settings</h3> 
    
    <div class="ab-settings-subsection ab-test-post-types freelimit">
      <label for="post_types"><strong>Post types:</strong></label>
      <p>Choose the post types that you would like the option of testing on.</p>
      <?php
    
      if (empty($selected_post_types))
        $selected_post_types = array_keys($post_types);

      foreach ($post_types as $post_type) {
        $checked = in_array($post_type->name, $selected_post_types) ? 'checked' : '';
      ?>
        <input type="checkbox" id="post_type_<?php echo $post_type->name; ?>" name="selected_post_types[]" value="<?php echo $post_type->name; ?>" <?php echo $checked; ?> />
        <label for="post_type_<?php echo $post_type->name; ?>"><?php echo $post_type->label; ?></label><br>
      <?php
      }
      ?>
    </div>   
    <div class="ab-settings-subsection ab-test-add-canonical">
      <label for="add_canonical"><strong>Add canonical links to page variations:</strong></label>
      <p>To avoid SEO duplicate content issues. Adds the default page from your split test as a canonical link to each variation page.</p>
      <p><input type="checkbox" id="add_canonical" name="add_canonical" value="1" <?php echo $add_canonical; ?> /> Enable canonical override.</p>
    </div>
      
    <div class="ab-settings-subsection ab-test-enable-logging">
        <label for="abst_enable_logging"><strong>Debug Logging</strong></label>
        <p>Enable detailed logging for debugging purposes. Logs are stored in the WordPress uploads directory and can be viewed in the Logs section when enabled.</p>
        <p>
          <input type="checkbox" id="abst_enable_logging" name="abst_enable_logging" value="1" <?php echo $abst_enable_logging; ?> /> Enable detailed logging
        </p>
      </div>

      <div class="ab-settings-subsection ab-test-uuid">
        <p>Creates a User identifier (UUID) for each visitor and saves that to a table in your database.</p>
        <?php  echo $upgrade_link; ?>
      </div>
      <div class="ab-settings-subsection ab-test-fingerprint">
        <label for="use_fingerprint"><strong>Enable fingerprint conversion type</strong></label>
        <p>A JavaScript snippet that triggers a test conversion on any remote website. </p>
        <p>Fingerprint conversion tests take longer and use the visitors hashed IP address to track them. Ensure you have permission.</p>
        <?php echo $upgrade_link; ?>
      </div>
      <div class="ab-settings-subsection ab-test-clear-cache">
        <label for="dont_clear_cache"><strong>Cache Clearing</strong></label>
        <p>By default, AB Split Test will clear all caches when a post or test is updated. <BR>If you have a large website or complex caching strategy, you may want to disable automatic cache clearing and manually clear any caching on your test and conversion pages.<BR></p>
        <?php echo $upgrade_link; ?>
      </div>


      <?php if (defined('WC_VERSION')) { ?>
      <div class="ab-settings-subsection ab-test-server-convert-woo">
        <label for="abst_server_convert_woo"><strong>WooCommerce orders - Use alternate server side conversions.</strong></label>
        <p>Enables server side conversion tracking when the order changes to status 'complete' or 'processing'. Enable if your Woo conversions aren't tracking accurately or you arent using the default 'thankyou' page.</p>
        <p>Required for WooFunnels or CartFlows conversion tracking.</p>
        <?php echo $upgrade_link; ?>
      </div>
      <?php } ?>

      <h3>AI Assist</h3>
      <div class="ab-settings-subsection ab-settings-open-ai">
        <p>AI Assist gives you access to automatic test suggestions, and CRO website insights.</p>
        <p>Your plan includes <?php echo isset($aiCreditAmount) ? $aiCreditAmount : ''; ?> AI requests,  <?php echo (isset($aiCreditFrequency) ? $aiCreditFrequency : '') ?>.</p>
        <p><?php echo ab_get_admin_setting('abst_remaining_calls'); ?> remaining</p>
        <p>If you need more requests, please add your OpenAI key below.</p>
        <?php echo $upgrade_link; ?>
      </div>

      <h3>Webhooks</h3>
      <div class="ab-settings-subsection ab-settings-webhooks">
        <p>When your test is complete, send an HTTP POST containing the Test ID, name and other statistics to an endpoint of your choice.</p>
        <?php echo $upgrade_link; ?>
      </div>
      <h3>Dynamic Traffic Optimization</h3>
      <div class="ab-settings-subsection ab-settings-thompson-sampling">
      <p><strong>Multi Armed Bandit</strong> automatically adjusts your test variations traffic on the fly towards the higher converting variations and away from the lower converting variations.</p>
      <p>When test has MAB enabled, 'chance of winning' metrics & autocomplete for tests are removed.</p>
        <?php echo $upgrade_link_teams; ?>
      </div>
      <h3>Weekly Reports</h3>
      <div class="ab-settings-subsection ab-settings-webhooks">
        <p>Send weekly reports with test results and analysis.<br/>
        <small>Sends on Monday morning, website time.</small></p>
        <?php echo $upgrade_link; ?>
      </div>
    <BR><input type="submit" class="button-primary" name="bt_save" value="<?php _e('Save Settings'); ?>" />

  </form>
</div>
<style> 


#abst-insights-iframe {
    width: 100%;
    min-height: 100%;
    height: auto;
    border: thin solid whitesmoke;
    box-shadow:0 1px 10px -3px grey;
    border-radius: 10px;
}
  .ab-settings-subsection { 
    padding: 20px; 
    background: white; 
    max-width: 400px; 
    box-shadow: 0 1px 10px rgb(0 0 0 / 8%); 
    border-radius: 3px; 
    margin: 5px 0;
  } 
 
 
  h3 small { 
    text-transform: capitalize; 
  } 
  .free-notice { 
    background-color: #FF887B; 
    padding: 20px; 
    border-radius: 10px; 
    margin-bottom: 20px; 
} 
 
.free-trial-notice {
    background-color: #a2ffb0;
    padding: 20px;
    border-radius: 10px;
    margin-bottom: 20px;
}

.free-notice a, .free-trial-notice a {
    font-weight: bold;
    color: #323232;
    background: white;
    padding: 10px;
    border-radius: 5px;
    display: inline-block;
}

 
 
.shepherd-content { 
    border: 2px solid black; 
    box-shadow: 0 1px 20px rgb(0 0 0 / 47%); 
    background: white; 
} 
 
.shepherd-header { 
    border-bottom: 2px solid black; 
    font-weight:600; 
} 
 
.shepherd-header .shepherd-title{ 
    font-weight:bold; 
} 
.shepherd-arrow:before { 
    background-color: black !important; 
} 
.shepherd-text { 
    font-weight: 500; 
} 
 
button.shepherd-button { 
    font-weight: bold; 
    color: white; 
    letter-spacing: 1px; 
    text-shadow: 0 1px 4px #00000057; 
    text-transform: uppercase; 
} 
.shepherd-target{ 
    background-color: rgba(255, 255, 0, 0.5); 
} 
</style> 
<script>
//when iframe #abst-insights-iframe posts message back                 window.parent.postMessage('aidone', '*'); then replace iframe with <h3>WEbsite trained</h3>
window.addEventListener('message', function(event) {
    if (event.data === 'aidone') {
        const iframe = document.getElementById('abst-insights-iframe');
        iframe.remove();
        jQuery('#abwelcomearea h3').remove();
        jQuery('#abwelcomearea h2').after('<h3>Website analysed</h3><p>To view your insights <a href="admin.php?page=abst-insights">click here</a></p>');
    }
});
  </script>
<?php 
// thin air
