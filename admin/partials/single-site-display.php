<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template variables included inside a class method, not true globals.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}



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

// Lite version: hardcode free tier since licensing is removed

$license_key = '';

$license_status = 'valid';

$user_level = 'free';

$fathom_api_key = abst_get_admin_setting('fathom_api_key');

$webhook_global = abst_get_admin_setting('webhook_global');

$post_types = get_post_types(array('public' => true), 'objects');

$selected_post_types = abst_get_admin_setting('selected_post_types');

$add_canonical = abst_get_admin_setting('ab_change_canonicals');

$use_uuid = '';
$uuid_length = abst_get_admin_setting('ab_uuid_length') ?: 30;

$enable_user_journeys = abst_get_admin_setting('abst_enable_user_journeys');

$enable_session_replays = abst_get_admin_setting('abst_enable_session_replays');

$abst_server_convert_woo = abst_get_admin_setting('abst_server_convert_woo');

$abst_server_convert_woo_status = abst_get_admin_setting('abst_server_convert_woo_status');

$abst_enable_logging = abst_get_admin_setting('abst_enable_logging');

$abst_enable_heatmaps = abst_get_admin_setting('abst_enable_heatmaps');

$heatmap_retention_length = abst_get_admin_setting('abst_heatmap_retention_length') ?: 30;

$abst_notification_emails = abst_get_admin_setting('abst_notification_emails');

$detected_caches = !empty(abst_get_detected_caches()) ? implode(', ', abst_get_detected_caches()) : 'None detected';

$wait_for_approval = abst_get_admin_setting('abst_wait_for_approval');

$abst_agency_mode_enabled = abst_get_admin_setting('abst_agency_mode_enabled') ?: 0;

$abst_test_ideas_enabled = abst_get_admin_setting('abst_test_ideas_enabled');

$abst_test_ideas_checked = ($abst_test_ideas_enabled === '0') ? '' : 'checked';

$abst_remote_access_enabled = abst_get_admin_setting('abst_remote_access_enabled') ?: 0;

	$abst_remote_access_enabled = '';





	$abst_agency_mode_enabled = '';





$mcpServerName = str_replace('.', '-', get_bloginfo('url'));

$mcpServerName = str_replace('http://', '', $mcpServerName);

$mcpServerName = str_replace('https://', '', $mcpServerName);

$mcpServerName = 'wordpress-' . $mcpServerName;

// Compute shareable site key if helper exists

$upgrade_link = '<p><a href="https://absplittest.com/pricing?ref=upgradefeaturelink" target="_blank" class="button button-secondary">Upgrade</a></p>';

$upgrade_link_teams = '<p><a href="https://absplittest.com/pricing?ref=upgradefeaturelink" target="_blank" class="button button-secondary">Upgrade</a></p>';


if($abst_server_convert_woo == true) {

  $abst_server_convert_woo = 'checked'; // Default to checked if not set

}

  $abst_enable_logging = 'checked'; 
  $abst_enable_heatmaps = 'checked'; 
  $enable_clear_cache = 'checked'; // Default to checked (cache clearing enabled)




//dont send events until cookie consent given. saves to session until approved

if($wait_for_approval == true) {

  $wait_for_approval = 'checked'; // checked if true

} else {

  $wait_for_approval = ''; // Otherwise/default to unchecked

}



$visit_on_visible = abst_get_admin_setting('abst_visit_on_visible');

if($visit_on_visible)

  $visit_on_visible = 'checked';



if($add_canonical)

  $add_canonical = 'checked';



// Heatmaps can now be enabled independently of advanced tracking

if($enable_user_journeys && $enable_user_journeys !== '0')

  $enable_user_journeys = 'checked';

else

  $enable_user_journeys = '';



// Session replays setting - defaults to ON when heatmaps are enabled

if($enable_session_replays === null || $enable_session_replays === '') {

  // Not set yet - default to ON if heatmaps are enabled

  $enable_session_replays = $enable_user_journeys ? 'checked' : '';

} elseif($enable_session_replays && $enable_session_replays !== '0') {

  $enable_session_replays = 'checked';

} else {

  $enable_session_replays = '';

}



// Get heatmap pages setting

$heatmap_pages = abst_get_admin_setting('abst_heatmap_pages');

if (!is_array($heatmap_pages)) {

  $heatmap_pages = array();

}

  $heatmap_all_pages = 'chosen';
  $user_level = 'free';


?>



<div id="fl-bt_bb_ab_test-form" class="fl-settings-form">

  

  <form id="bt-bb-ab-form" action="<?php echo esc_html(BT_BB_AB_Admin::get_current_settings_url()); ?>" method="post">

    <?php wp_nonce_field('bt-bb-ab-nonce', 'bt-bb-ab-nonce'); ?>





      <p>Need a hand? Watch the <a href="<?php echo esc_html(admin_url()) ?>options-general.php?page=bt_bb_ab_test&wizard=1">walkthrough video</a>  or check out the <a href="https://absplittest.com/documentation/" target="_blank">documentation</a>.</p>



      <div class="fl-settings-form-content">



        <script>

        jQuery(document).ready(function($) {

          var savedPages = <?php echo json_encode($heatmap_pages); ?>;

          

          $('#heatmap_pages').select2({

            multiple: true,

            ajax: {

              url: '<?php echo esc_url(admin_url('admin-ajax.php')); ?>',

              dataType: 'json',

              delay: 250,

              data: function(params) {

                return {

                  q: params.term || '',

                  action: 'ab_page_selector',

                  nonce: bt_exturl.page_selector_nonce

                };

              },

              processResults: function(data) {

                return {

                  results: $.map(data, function(page) {

                    return {

                      id: page[0],

                      text: page[1]

                    };

                  })

                };

              },

              cache: true

            },

            minimumInputLength: 0,

            placeholder: 'Only pages that have interacted with a test',

            allowClear: true,

            tags: false

          });

          

          // Set saved values

          if (savedPages && savedPages.length > 0) {

            $('#heatmap_pages').val(savedPages).trigger('change');

          }

        });

        </script>



      </div>






    <div class="abst-settings-container">

      <!-- Vertical Tabs Navigation -->

      <div class="abst-settings-tabs">

        <button type="button" class="abst-tab-btn active" data-tab="welcome">

          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>

          Welcome

        </button>

        <button type="button" class="abst-tab-btn" data-tab="general">

          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>

          General

        </button>

        <button type="button" class="abst-tab-btn" data-tab="data">

          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>

          Data Management

        </button>

        <button type="button" class="abst-tab-btn" data-tab="heatmaps">

          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><circle cx="15.5" cy="8.5" r="1.5"></circle><circle cx="8.5" cy="15.5" r="1.5"></circle><circle cx="15.5" cy="15.5" r="1.5"></circle></svg>

          Heatmaps

        </button>

        <button type="button" class="abst-tab-btn" data-tab="advanced">

          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 2 7 12 12 22 7 12 2"></polygon><polyline points="2 17 12 22 22 17"></polyline><polyline points="2 12 12 17 22 12"></polyline></svg>

          Advanced

        </button>

        <button type="button" class="abst-tab-btn" data-tab="mcp">

          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"></path></svg>

          API / MCP / CLI

        </button>

      </div>



      <!-- Tab Content Panels -->

      <div class="abst-settings-content">

        

        <!-- WELCOME TAB -->

        <div class="abst-tab-panel active" id="tab-welcome">

          <h2>Welcome to AB Split Test</h2>

          

          <style>

            .video-container {

              position: relative;

              padding-bottom: 28.125%; /* 16:9 aspect ratio, shrunk to 50% height */

              height: 0;

              overflow: hidden;

              margin-bottom: 20px;

              max-width: 640px;

            }

            .video-container iframe {

              position: absolute;

              top: 0;

              left: 0;

              width: 100%;

              height: 100%;

            }

          </style>

          <div class="ab-settings-subsection">

            <h3>Watch the quick video that explains everything.</h3>

            <div class="video-container">

              <iframe src="https://share.descript.com/embed/Vbz3Q3aJu05" width="100%" height="100%" frameborder="0" allowfullscreen></iframe>

            </div>

          </div>

          

          <div class="ab-settings-subsection">

            <p style="margin-top: 0.25rem; font-size: 12px; color: #666;">Version <?php echo esc_html( BT_AB_TEST_VERSION ); ?> </p>

            <p>Get started with A/B testing on your WordPress site.</p>

            <h3>Quick Start</h3>

            <p>1. <a href="#license">Activate your license</a></p>

            <p>2. Create your first test from any post or page</p>

            <p>3. Watch conversions roll in!</p>

            <p><a href="https://absplittest.com/documentation" target="_blank" class="button button-secondary">View Documentation</a></p>

          </div>

          <?php if ($user_level == 'pro') { ?>

            <div class="ab-settings-subsection">

              <p><a target="_blank" href='https://absplittest.com/pricing?utm_source=ug'>Upgrade your account</a> for Analytics integrations, White Label branding, Reporting, Webhooks and more.</p>

            </div>

          <?php } ?>

        </div>



        <!-- GENERAL TAB -->

        <div class="abst-tab-panel" id="tab-general">

          <h2>General Settings</h2>

          

          <div class="ab-settings-subsection ab-test-post-types <?php if ($user_level == 'free') { echo "freelimit"; } ?>">

        <label for="post_types"><strong>Post types:</strong></label>

        <p>Choose the post types that you would like the option of testing on.</p>

        <?php

      

        if (empty($selected_post_types))

          $selected_post_types = array_keys($post_types);



        foreach ($post_types as $post_type) {

          $checked = in_array($post_type->name, $selected_post_types) ? 'checked' : '';

        ?>

          <input type="checkbox" class="ab-toggle" id="post_type_<?php echo esc_attr($post_type->name); ?>" name="selected_post_types[]" value="<?php echo esc_attr($post_type->name); ?>" <?php echo esc_attr($checked); ?> />

          <label for="post_type_<?php echo esc_attr($post_type->name); ?>"><?php echo esc_html($post_type->label); ?></label><br>

        <?php

        }

        ?>

          </div>



          <div class="ab-settings-subsection ab-test-add-canonical">

            <label><strong>Add canonical links to page variations:</strong></label>

            <p>To avoid SEO duplicate content issues. Adds the default page from your split test as a canonical link to each variation page.</p>

            <p><a href="https://absplittest.com/pricing?ref=upgradefeaturelink" target="_blank" class="">Upgrade to enable canonical override for full page tests</a></p>

          </div>





          <div class="ab-settings-subsection ab-test-clear-cache">

            <label><strong>Cache Clearing</strong></label>

            <p>AB Split Test can automatically clear caches when a post or test is updated.</p>

            <p><a href="https://absplittest.com/pricing?ref=upgradefeaturelink" target="_blank" class="">Upgrade</a> for advanced cache controls</p>

            <p>Detected caches: <?php echo esc_html($detected_caches); ?></p>

          </div>



          <?php if (defined('WC_VERSION')) { ?>

          <div class="ab-settings-subsection ab-test-server-convert-woo">

            <label for="abst_server_convert_woo"><strong>WooCommerce Server-Side Conversions</strong></label>

            <p>Enables server side conversion tracking when the order changes status. Enable if your Woo conversions aren't tracking accurately.</p>

            <p>Required for WooFunnels or CartFlows conversion tracking.</p>

             <?php echo wp_kses_post($upgrade_link);  ?>

            </div>

          <?php } ?>






        </div><!-- end #tab-general -->



        <!-- DATA MANAGEMENT TAB -->

        <div class="abst-tab-panel" id="tab-data">

          <h2>Data Management</h2>



          <div class="ab-settings-subsection ab-test-wait-for-approval">

            <label for="wait_for_approval"><strong>Privacy - Cookie Consent</strong></label>

            <p>With this enabled, AB Split Test will wait for cookie consent before sending test data.</p>

            <p><input type="checkbox" class="ab-toggle" id="wait_for_approval" name="wait_for_approval" value="1" <?php echo esc_attr($wait_for_approval); ?> /> Enable wait for approval.</p>

            <div id="wait_for_approval_info_area">

              <div style="display: none;" id="wait_for_approval_info">

                <h4>Cookie Consent Information</h4>

                <p>Tests will run as expected, except that data will be stored in session storage and not transmitted or saved until cookie consent is given.</p>

                <p>Works automatically with: Borlabs v3, CookieBot, Cookie Consent (Orestbida), WP Consent API, CookieYES, Complianz, Termageddon, GDPR Cookie Consent (WebToffee)</p>

                <p>Developers can call <code>setAbstApprovalStatus(true)</code> when consent has been granted.</p>

              </div>

              <a href="#" id="wait_for_approval_info_toggle">More Information.</a>

              <script>

                document.getElementById('wait_for_approval_info_toggle').addEventListener('click', function(e) {

                  e.preventDefault();

                  document.getElementById('wait_for_approval_info').style.display = 'block';

                  document.getElementById('wait_for_approval_info_toggle').style.display = 'none';

                });

              </script>

            </div>

          </div>



          <div class="ab-settings-subsection ab-test-uuid">

            <label><strong>Advanced Tracking (UUID)</strong></label>

            <p>Creates a User identifier (UUID) for each visitor and saves that to a table in your database.</p>

            <p>Required for:</p>

            <ul>

              <li>- Server Side Conversions</li>

              <li>- Visitor Data Exports</li>

              <li>- WooFunnels & Cartflows Integrations</li>

              <li>- GTM Conversions</li>

            </ul>

            <?php if ($user_level == 'free') { echo wp_kses_post($upgrade_link); } else { ?>

            <p><input type="checkbox" class="ab-toggle" id="use_uuid" name="use_uuid" value="1" <?php echo esc_attr($use_uuid); ?> /> <strong>Enable advanced tracking</strong></p>

            <div id="uuid_settings_area" style="<?php echo empty($use_uuid) ? 'display:none;' : ''; ?>">

              <hr style="margin: 20px 0; border: none; border-top: 1px solid #e2e8f0;">

              

              <label for="uuid_length"><strong>Visitor Tracking Duration</strong></label>

              <p>The number of days to remember the visitor: <input type="number" id="uuid_length" name="uuid_length" style="width:60px;" value="<?php echo esc_attr($uuid_length); ?>" /> days</p>



            </div>

            <?php } ?>

          </div>



        </div><!-- end #tab-data -->



        <!-- HEATMAPS TAB -->

        <div class="abst-tab-panel" id="tab-heatmaps">

          <h2>Heatmaps & Session Recording</h2>



          <div class="ab-settings-subsection ab-test-user-journeys">

            <p>Track anonymized visitor journeys and key interactions like clicks and navigation to build click insights for your visitors.</p>

            <p>Generates heatmaps by page / test / variation / size.</p>

            <p><input type="checkbox" class="ab-toggle" id="abst_heatmap_enable_user_journeys" name="enable_user_journeys" value="1" <?php echo esc_attr($enable_user_journeys); ?> /> <strong>Enable heatmaps</strong></p>



            <div id="heatmap_settings_area" style="<?php echo empty($enable_user_journeys) ? 'display:none;' : ''; ?>">

              <hr style="margin: 20px 0; border: none; border-top: 1px solid #e2e8f0;">



              <label><strong>Session Replays</strong></label>

              <p>Watch recordings of user sessions to understand how visitors interact with your site.</p>

              <p><input type="checkbox" class="ab-toggle" id="abst_enable_session_replays" name="enable_session_replays" value="1" <?php echo esc_attr($enable_session_replays); ?> /> <strong>Enable session replays</strong></p>



              <hr style="margin: 20px 0; border: none; border-top: 1px solid #e2e8f0;">



              <label><strong>Page Selection</strong></label>

              <p>Choose the page you want to generate heatmaps for. Defaults to homepage.</p>
              <p><select id="heatmap_page_select" name="heatmap_pages[]" style="width: 25rem;"></select></p>
              <p><a href="https://absplittest.com/pricing?ref=upgradefeaturelink" target="_blank" class="">Upgrade to track heatmaps & session replays on any page.</a></p>

              <script>
              jQuery(document).ready(function($) {
                $('#heatmap_page_select').select2({
                  ajax: {
                    url: '<?php echo esc_url(admin_url('admin-ajax.php')); ?>',
                    dataType: 'json',
                    delay: 250,
                    data: function(params) {
                      return { q: params.term || '', action: 'ab_page_selector', nonce: bt_exturl.page_selector_nonce };
                    },
                    processResults: function(data) {
                      return {
                        results: $.map(data, function(page) {
                          return { id: page[0], text: page[1] };
                        })
                      };
                    },
                    cache: true
                  },
                  minimumInputLength: 0,
                  placeholder: 'Search for a page…',
                  allowClear: true
                });
                <?php
                $saved_heatmap_pages = abst_get_admin_setting('abst_heatmap_pages');
                if (!empty($saved_heatmap_pages) && is_array($saved_heatmap_pages)) {
                  $page_id = intval($saved_heatmap_pages[0]);
                  $page_title = get_the_title($page_id);
                  if ($page_title) {
                    echo "var opt = new Option(" . wp_json_encode($page_title) . ", " . wp_json_encode((string)$page_id) . ", true, true);";
                    echo "$('#heatmap_page_select').append(opt).trigger('change');";
                  }
                }
                ?>
              });
              </script>



              <hr style="margin: 20px 0; border: none; border-top: 1px solid #e2e8f0;">



              <label for="heatmap_retention_length"><strong>Data Retention</strong></label>

              <p>3 days <a href="https://absplittest.com/pricing?ref=upgradefeaturelink" target="_blank" class="">Upgrade to choose any data retention</a></p>



              <hr style="margin: 20px 0; border: none; border-top: 1px solid #e2e8f0;">



              <label><strong>Data Management</strong></label>

              <p><button id="remove_heatmap_data" class="button-secondary">Remove all Heatmap & Session Replay Data</button></p>

            </div>

          </div>

        </div><!-- end #tab-heatmaps -->



        <!-- ADVANCED TAB -->

        <div class="abst-tab-panel" id="tab-advanced">

          <h2>Advanced Settings</h2>



 

          <div class="ab-settings-subsection ab-settings-thompson-sampling">

            <label><strong>Dynamic Traffic Optimization (Multi Armed Bandit)</strong></label>

            <p>Automatically adjusts your test variations traffic on the fly towards the higher converting variations and away from the lower converting variations.</p>

            <p>When test has MAB enabled, 'confidence' metrics & autocomplete for tests are removed.</p>

            <?php echo wp_kses_post($upgrade_link); ?>

          </div>



          <div class="ab-settings-subsection ab-test-agency-hub">

            <label for="abst_remote_access_enabled"><strong>Share data to an Agency Hub</strong></label>

            <p>Enable secure sharing of your A/B test summaries with an external Agency.</p>

            <?php echo wp_kses_post($upgrade_link); ?>

          </div>



          <div class="ab-settings-subsection ab-test-agency-mode">

            <label><strong>Make this site an Agency Hub</strong></label>

            <p>View multiple site statistics from a dashboard on this site.</p>

            <?php echo wp_kses_post($upgrade_link); ?>

          </div>






          <div class="ab-settings-subsection ab-settings-webhooks">

            <label><strong>Webhooks</strong></label>

            <p>When your test is complete, send an HTTP POST containing the Test ID, name and other statistics to an endpoint of your choice.</p>

            <?php echo wp_kses_post($upgrade_link); ?>

          </div>



          <div class="ab-settings-subsection ab-settings-notification-emails">

            <label><strong>Test Completion Notification Email</strong></label>

            <p>When a test completes via autocomplete, a notification is sent to the admin email. Override that address here.</p>

            <?php echo wp_kses_post($upgrade_link); ?>

          </div>



        </div><!-- end #tab-advanced -->



        <!-- MCP SETTINGS TAB -->

        <div class="abst-tab-panel" id="tab-mcp">

          <h2>API / MCP / CLI</h2>

          

          <?php

          // Check if WordPress MCP Adapter plugin is installed

          $mcp_adapter_installed = class_exists('WP\\MCP\\Core\\McpAdapter');

          

          ?>

          <div class="ab-settings-subsection">

            <p><strong>AB Split Test integrates with anything via API, Command Line or MCP (Model Context Protocol).</strong></p>

            

            <h3>Available Tools</h3>

            <p>The following tools are available across API, CLI, and MCP:</p>

            <ul style="list-style: disc; margin-left: 20px;">

              <li><strong>create-test</strong> - Create new A/B tests (magic, ab_test, css_test, full_page)</li>

              <li><strong>list-tests</strong> - List all tests with their configurations</li>

              <li><strong>get-test-results</strong> - Get detailed results for a specific test</li>

              <li><strong>update-test-status</strong> - Change test status (publish, draft, pending, complete)</li>

              <li><strong>update-test-settings</strong> - Update conversion goals and other settings on an existing test</li>

            </ul>

          </div>



          <div class="ab-settings-subsection">

            <h3>REST API Endpoints</h3>

            <p>Access AB Split Test programmatically via REST API:</p>

            <ul style="list-style: disc; margin-left: 20px;">

              <li><strong>POST</strong> <code>/wp-json/bt-bb-ab/v1/create-test</code> - Create new tests</li>

              <li><strong>GET</strong> <code>/wp-json/bt-bb-ab/v1/list-tests</code> - List all tests</li>

              <li><strong>GET</strong> <code>/wp-json/bt-bb-ab/v1/test-results/{id}</code> - Get test results</li>

              <li><strong>POST</strong> <code>/wp-json/bt-bb-ab/v1/update-test-status</code> - Update test status</li>

              <li><strong>POST</strong> <code>/wp-json/bt-bb-ab/v1/update-test-settings</code> - Update conversion goals and settings</li>

            </ul>

            <p style="margin-top: 10px;"><small>Requires WordPress Application Password for authentication.</small></p>

            

            <details style="margin-top: 20px;">

              <summary style="cursor: pointer; font-weight: bold; padding: 10px; background: #f8fafc; border-radius: 5px;">📋 Example: Create a Magic Test via API</summary>

              <div style="margin-top: 15px; padding: 15px; background: #f8fafc; border-radius: 5px;">

                <p><strong>Scenario:</strong> Create a magic test for the H1 headline on page ID 12 with 2 variations, tracking WooCommerce checkout completion as the conversion.</p>

                

                <h4 style="margin-top: 15px;">cURL Example:</h4>

                <pre style="background: #2d2d2d; color: #f8f8f2; padding: 15px; border-radius: 5px; overflow-x: auto; font-size: 12px;"><code>curl -X POST "<?php echo esc_url(home_url('/wp-json/bt-bb-ab/v1/create-test')); ?>" \

  -u "your-username:your-application-password" \

  -H "Content-Type: application/json" \

  -d '{

    "test_title": "Homepage H1 Headline Test",

    "test_type": "magic",

    "status": "publish",

    "target_percentage": "50",

    "conversion_type": "woo-order-received",

    "magic_definition": [

      {

        "type": "text",

        "selector": ".page-id-12 h1",

        "scope": {

          "page_id": 12,

          "url": "homepage"

        },

        "variations": [

          "Original Headline",

          "New Compelling Headline",

          "Alternative Headline"

        ]

      }

    ]

  }'</code></pre>



                <h4 style="margin-top: 20px;">JavaScript (Fetch) Example:</h4>

                <pre style="background: #2d2d2d; color: #f8f8f2; padding: 15px; border-radius: 5px; overflow-x: auto; font-size: 12px;"><code>const credentials = btoa('your-username:your-application-password');



fetch('<?php echo esc_url(home_url('/wp-json/bt-bb-ab/v1/create-test')); ?>', {

  method: 'POST',

  headers: {

    'Content-Type': 'application/json',

    'Authorization': 'Basic ' + credentials

  },

  body: JSON.stringify({

    test_title: 'Homepage H1 Headline Test',

    test_type: 'magic',

    status: 'publish',

    target_percentage: '50',

    conversion_type: 'woo-order-received',

    magic_definition: [

      {

        type: 'text',

        selector: '.page-id-12 h1',

        scope: {

          page_id: 12,

          url: 'homepage'

        },

        variations: [

          'Original Headline',

          'New Compelling Headline',

          'Alternative Headline'

        ]

      }

    ]

  })

})

.then(response => response.json())

.then(data => console.log('Test created:', data));</code></pre>



                <h4 style="margin-top: 20px;">Key Parameters:</h4>

                <ul style="list-style: disc; margin-left: 20px;">

                  <li><strong>test_title:</strong> Test name for identification</li>

                  <li><strong>test_type:</strong> "magic" for magic tests</li>

                  <li><strong>status:</strong> "publish" (active), "draft" (inactive), "pending", or "complete"</li>

                  <li><strong>target_percentage:</strong> Percentage of visitors to include (0-100)</li>

                  <li><strong>conversion_type:</strong> "woo-order-received" for WooCommerce checkout completion</li>

                  <li><strong>magic_definition:</strong> Array of elements to test with their variations</li>

                  <li><strong>scope:</strong> Optional but recommended page targeting metadata. Use <code>page_id</code> (WordPress page/post ID) or <code>url</code> (path fragment) or both for precise targeting</li>

                  <li><strong>selector:</strong> CSS selector for the element to test</li>

                  <li><strong>variations:</strong> Array of test variation strings only — do NOT include the control/original (it is always implicit)</li>

                </ul>

                

                <p style="margin-top: 15px;"><small><strong>Note:</strong> Replace "your-username" and "your-application-password" with your WordPress credentials. The control is always implicit for magic tests — do not include it in the variations array. For backward compatibility, the API still accepts legacy aliases such as <code>name</code>, <code>conversion_page</code>, and underscore-based ecommerce conversion values, but new integrations should send the canonical fields shown above.</small></p>

              </div>

            </details>

          </div>






          <div class="ab-settings-subsection">

            <h3>MCP Integration (AI Assistants)</h3>

            <p>Connect AI assistants like Claude Desktop, OpenClaw, ChatGPT and more to create, manage and analyze your A/B tests directly.</p>

            

            <?php if (!$mcp_adapter_installed): ?>

            <div style="background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin-top: 15px; margin-bottom: 20px;">

              <h4 style="margin-top: 0;">⚠️ WordPress MCP Adapter Required</h4>

              <p><strong>The WordPress MCP Adapter plugin is not installed.</strong></p>

              <p>MCP adapter will be included in WordPress 7, but you are on an older version. To use the MCP integration with AB Split Test, you need to install the WordPress MCP Adapter plugin first.</p>

            </div>



            <h4 style="margin-top: 20px;">Step 1: Install WordPress MCP Adapter Plugin</h4>

            

            <?php if (isset($_GET['mcp_install_success'])): ?>

            <div style="background: #d4edda; border-left: 4px solid #28a745; padding: 15px; margin-bottom: 15px;">

              <strong>✅ WordPress MCP Adapter installed successfully!</strong> Please refresh this page.

            </div>

            <?php endif; ?>



            <?php if (isset($_GET['mcp_install_error'])): ?>

            <div style="background: #f8d7da; border-left: 4px solid #dc3545; padding: 15px; margin-bottom: 15px;">

              <strong>❌ Installation failed:</strong> <?php echo esc_html(urldecode(sanitize_text_field(wp_unslash($_GET['mcp_install_error'])))); ?>

            </div>

            <?php endif; ?>

            

            <p>Click the button below to automatically install and activate the latest WordPress MCP Adapter plugin:</p>

            <form method="post" action="" style="margin: 15px 0;">

              <?php wp_nonce_field('absplittest_install_mcp', 'absplittest_mcp_nonce'); ?>

              <button type="submit" name="install_mcp_adapter" class="button button-primary" style="height: auto; padding: 10px 20px;">

                🚀 Install WordPress MCP Adapter Now

              </button>

            </form>

            <p><strong>Requirements:</strong> WordPress 6.9 or higher</p>

            <?php else: ?>

            <div style="background: #d4edda; border-left: 4px solid #28a745; padding: 15px; margin-top: 15px; margin-bottom: 20px;">

              <p style="margin: 5px 0 0 0;">The WordPress MCP Adapter is active and ready to use. AB Split Test tools are now available via MCP.</p>

            </div>

            

            <h4 style="margin-top: 20px;">Step 1: Create WordPress Application Password</h4>

            <p>AI clients need authentication to access your WordPress site:</p>

            <ol style="margin-left: 20px;">

              <li>Go to <strong>Users → Profile</strong></li>

              <li>Scroll to <strong>Application Passwords</strong> section</li>

              <li>Enter a name (e.g., "Windsurf MCP")</li>

              <li>Click <strong>Add New Application Password</strong></li>

              <li>Copy the generated password (you won't see it again!)</li>

            </ol>

            

            <h4 style="margin-top: 20px;">Step 2: Configure Your AI Client</h4>

            

            <div style="background: #f8fafc; padding: 15px; border-radius: 5px; margin-bottom: 20px; margin-top: 15px;">

              <label for="abst_mcp_username"><strong>WordPress Username</strong></label>

              <input type="text" id="abst_mcp_username" class="regular-text" value="<?php echo esc_attr(wp_get_current_user()->user_login); ?>" style="width: 100%; margin-top: 5px;" readonly />

              <p class="description">Your WordPress username for MCP authentication</p>

              

              <label for="abst_mcp_password" style="margin-top: 15px; display: block;"><strong>Application Password</strong></label>

              <input type="text" id="abst_mcp_password" class="regular-text" placeholder="Paste your Application Password here" style="width: 100%; margin-top: 5px;" />

              <p class="description">Paste your Application Password - spaces will be automatically removed</p>

            </div>

            

            <h5 style="margin-top: 20px;">Windsurf IDE</h5>

            <p>Add to your <code>.windsurf/mcp_config.json</code>:</p>

            <pre style="background: #f5f5f5; padding: 15px; border-radius: 5px; overflow-x: auto;"><code id="abst_mcp_config_windsurf">{

  "mcpServers": {

    "<?php echo esc_html($mcpServerName); ?>": {

      "command": "npx",

      "args": ["-y", "@automattic/mcp-wordpress-remote@latest"],

      "env": {

        "WP_API_URL": "<?php echo esc_html(home_url('/wp-json/mcp/mcp-adapter-default-server')); ?>",

        "WP_API_USERNAME": "<span class="abst-mcp-username-placeholder"><?php echo esc_html(wp_get_current_user()->user_login); ?></span>",

        "WP_API_PASSWORD": "<span class="abst-mcp-password-placeholder">YOUR_APPLICATION_PASSWORD</span>"

      }

    }

  }

}</code></pre>



            <h5 style="margin-top: 20px;">Claude Desktop</h5>

            <p>Add to your Claude Desktop config file:</p>

            <p><strong>Windows:</strong> <code>%APPDATA%\Claude\claude_desktop_config.json</code></p>

            <p><strong>macOS:</strong> <code>~/Library/Application Support/Claude/claude_desktop_config.json</code></p>

            <pre style="background: #f5f5f5; padding: 15px; border-radius: 5px; overflow-x: auto;"><code id="abst_mcp_config_claude">{

  "mcpServers": {

    "<?php echo esc_html($mcpServerName); ?>": {

      "command": "npx",

      "args": ["-y", "@automattic/mcp-wordpress-remote@latest"],

      "env": {

        "WP_API_URL": "<?php echo esc_html(home_url('/wp-json/mcp/mcp-adapter-default-server')); ?>",

        "WP_API_USERNAME": "<span class="abst-mcp-username-placeholder"><?php echo esc_html(wp_get_current_user()->user_login); ?></span>",

        "WP_API_PASSWORD": "<span class="abst-mcp-password-placeholder">YOUR_APPLICATION_PASSWORD</span>"

      }

    }

  }

}</code></pre>



            <h5 style="margin-top: 20px;">Cline (VS Code Extension)</h5>

            <p>Add to VS Code settings or Cline MCP settings:</p>

            <pre style="background: #f5f5f5; padding: 15px; border-radius: 5px; overflow-x: auto;"><code id="abst_mcp_config_cline">{

  "cline.mcpServers": {

    "<?php echo esc_html($mcpServerName); ?>": {

      "command": "npx",

      "args": ["-y", "@automattic/mcp-wordpress-remote@latest"],

      "env": {

        "WP_API_URL": "<?php echo esc_html(home_url('/wp-json/mcp/mcp-adapter-default-server')); ?>",

        "WP_API_USERNAME": "<span class="abst-mcp-username-placeholder"><?php echo esc_html(wp_get_current_user()->user_login); ?></span>",

        "WP_API_PASSWORD": "<span class="abst-mcp-password-placeholder">YOUR_APPLICATION_PASSWORD</span>"

      }

    }

  }

}</code></pre>

            

            <h4 style="margin-top: 20px;">Step 3: Copy Configuration & Connect</h4>

            <p>Your configuration is ready to use:</p>

            <ol style="margin-left: 20px;">

              <li>Copy the configuration for your AI client (Claude Desktop, VScode, OpenClaw or Cline) from below</li>

              <li>Paste it into your MCP client's configuration file</li>

              <li>Restart your MCP client to load the new configuration. Load a new conversation, as old conversations generally cache the previous configuration</li>

              <li>The AB Split Test tools should appear in your AI assistant's available tools</li>

              <li>Try asking: "List all my A/B tests" or "Create a test of the home page against the new landing page we just generated, conversion is a purchase"</li>

            </ol>            

            <h4 style="margin-top: 20px;">Example: Ask Your AI Assistant</h4>

            <p><strong>Create a Magic Test:</strong></p>

            <p><em>"Create a magic A/B test called 'Homepage Headline Test' that tests the h1 element with two variations: 'New Headline A' and 'New Headline B'. Track clicks on .cta-button as the conversion."</em></p>

            

            <p><strong>List Tests:</strong></p>

            <p><em>"Show me all my A/B tests"</em></p>

            

            <p><strong>Get Results:</strong></p>

            <p><em>"Get the results for test ID 3504"</em></p>

            

            <h4 style="margin-top: 20px;">Troubleshooting</h4>

            <ul style="list-style: disc; margin-left: 20px;">

              <li><strong>Tools not appearing:</strong> Make sure WordPress MCP Adapter plugin is installed and activated</li>

              <li><strong>Authentication errors:</strong> Verify your Application Password is correct (no spaces)</li>

              <li><strong>Connection errors:</strong> Check that your WordPress site URL is correct and accessible</li>

              <li><strong>Minimum WordPress 6.9:</strong> The Abilities API is only available in WordPress 6.9+ and requires MCP adapter plugin to function.</li>

              <li><strong>Built for WordPress 7:</strong> No additional plugins etc needed if on WordPress 7.0 or later.</li>

            </ul>

          </div>



          <div class="ab-settings-subsection">

            <h3>Learn More</h3>

            <p><a href="https://developer.wordpress.org/news/2026/02/from-abilities-to-ai-agents-introducing-the-wordpress-mcp-adapter/" target="_blank">WordPress MCP Adapter Documentation</a></p>

            <p><a href="https://github.com/WordPress/mcp-adapter" target="_blank">WordPress MCP Adapter on GitHub</a></p>

          </div>

          <?php endif; ?>

        </div><!-- end #tab-mcp -->



        <script>

        (function() {

          // Update MCP configuration examples when username or password changes

          function updateMcpConfigs() {

            const username = document.getElementById('abst_mcp_username').value;

            const password = document.getElementById('abst_mcp_password').value;

            

            // Remove all spaces from password

            const sanitizedPassword = password.replace(/\s/g, '');

            

            // Update password input with sanitized value

            if (password !== sanitizedPassword) {

              document.getElementById('abst_mcp_password').value = sanitizedPassword;

            }

            

            // Update all username placeholders

            const usernamePlaceholders = document.querySelectorAll('.abst-mcp-username-placeholder');

            usernamePlaceholders.forEach(function(el) {

              el.textContent = username || 'YOUR_WORDPRESS_USERNAME';

            });

            

            // Update all password placeholders

            const passwordPlaceholders = document.querySelectorAll('.abst-mcp-password-placeholder');

            passwordPlaceholders.forEach(function(el) {

              el.textContent = sanitizedPassword || 'YOUR_APPLICATION_PASSWORD';

            });

          }

          

          // Add event listeners when DOM is ready

          document.addEventListener('DOMContentLoaded', function() {

            const usernameInput = document.getElementById('abst_mcp_username');

            const passwordInput = document.getElementById('abst_mcp_password');

            

            if (usernameInput) {

              usernameInput.addEventListener('input', updateMcpConfigs);

              usernameInput.addEventListener('change', updateMcpConfigs);

            }

            

            if (passwordInput) {

              passwordInput.addEventListener('input', updateMcpConfigs);

              passwordInput.addEventListener('paste', function() {

                // Use setTimeout to allow paste to complete before processing

                setTimeout(updateMcpConfigs, 10);

              });

            }

            

            // Initial update

            updateMcpConfigs();

          });

        })();

        </script>





        <div class="floating-save-button-row"><input type="submit" class="button-primary" name="bt_save" value="<?php esc_attr_e('Save Settings', 'ab-split-test-lite'); ?>" /></div>



      </div><!-- end .abst-settings-content -->

    </div><!-- end .abst-settings-container -->



    </form>

  </div>

</div>

<style> 

/* =====================================================

   FLOATING SAVE BUTTON

   ===================================================== */

.floating-save-button-row {

  display: block;

  position: sticky;

  bottom: 0;

  background: #fff;

  padding: 20px;

  margin-top: 24px;

  border: 1px solid #e2e8f0;

  border-radius: 8px;

  box-shadow: 0 1px 10px rgb(0 0 0 / 8%);

  z-index: 100;

}



.floating-save-button-row .button-primary {

  padding: 10px 24px;

  font-size: 14px;

  font-weight: 600;

}



/* =====================================================

   VERTICAL TABS LAYOUT

   ===================================================== */

.abst-settings-container {

  display: flex;

  gap: 24px;

  margin-top: 20px;

  overflow: auto;

  clear: both;

}



.abst-settings-tabs {

  flex: 0 0 200px;

  display: flex;

  flex-direction: column;

  gap: 4px;

  position: sticky;

  top: 32px;

  align-self: flex-start;

}



.abst-tab-btn {

  display: flex;

  align-items: center;

  gap: 10px;

  padding: 10px 14px;

  background: transparent;

  border: none;

  border-radius: 6px;

  cursor: pointer;

  font-size: 13px;

  font-weight: 400;

  color: #64748b;

  text-align: left;

  transition: all 0.15s ease;

}



.abst-tab-btn:hover {

  background: #f1f5f9;

  color: #334155;

}



.abst-tab-btn.active {

  background: #17A8E3;

  color: #fff;

  font-weight: 500;

}



.abst-tab-btn.active svg {

  stroke: #fff;

}



.abst-tab-btn svg {

  flex-shrink: 0;

  stroke: #94a3b8;

  width: 16px;

  height: 16px;

  transition: stroke 0.15s ease;

}



.abst-tab-btn:hover svg {

  stroke: #64748b;

}



.abst-settings-content {

  flex: 1;

  min-width: 0;

  width: 100%;

  max-width: 600px;

}



@media (max-width: 782px) {

  .abst-settings-container {

    flex-direction: column;

    gap: 16px;

  }

  

  .abst-settings-tabs {

    flex: none;

    width: 100%;

    flex-direction: row;

    flex-wrap: wrap;

    gap: 8px;

    position: static;

    background: #f8fafc;

    padding: 12px;

    border-radius: 8px;

    border: 1px solid #e2e8f0;

  }

  

  .abst-tab-btn {

    flex: 1 1 auto;

    min-width: calc(50% - 4px);

    justify-content: center;

    padding: 10px 12px;

    font-size: 12px;

  }

  

  .abst-tab-btn svg {

    width: 14px;

    height: 14px;

  }

  

  .abst-settings-content {

    max-width: 100%;

  }

  

  .ab-settings-subsection {

    max-width: 100%;

    padding: 16px;

  }

}



.abst-tab-panel {

  display: none;

}



.abst-tab-panel.active {

  display: block;

}



.abst-tab-panel h2 {

  font-size: 1.5em;

  font-weight: 600;

  color: #1e293b;

  margin: 0 0 20px 0;

}



/* Settings subsections within tabs */

.abst-tab-panel .ab-settings-subsection {

  max-width: 100%;

  margin-bottom: 16px;

}



#abst-insights-iframe {

  width: 100%;

  min-height: 100%;

  height: auto;

  border: thin solid whitesmoke;

  box-shadow: 0 1px 10px -3px grey;

  border-radius: 10px;

}

#wpfooter{

  position: static;

}

.ab-settings-subsection { 

  padding: 20px; 

  background: white; 

  max-width: 400px; 

  box-shadow: 0 1px 10px rgb(0 0 0 / 8%); 

  border-radius: 8px; 

  margin: 5px 0;

  border: 1px solid #e2e8f0;

}



/* Select dropdowns in settings */

.abst-settings-container select,

.ab-settings-subsection select {

  border: 1px solid #d1d5db;

  border-radius: 6px;

  padding: 8px 32px 8px 12px;

  font-size: 14px;

  background: #ffffff url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%236B7280' d='M2 4l4 4 4-4'/%3E%3C/svg%3E") no-repeat right 12px center;

  background-size: 12px;

  -webkit-appearance: none;

  -moz-appearance: none;

  appearance: none;

  cursor: pointer;

  transition: all 0.15s ease;

  color: #374151;

}



.abst-settings-container select:hover,

.ab-settings-subsection select:hover {

  border-color: #9ca3af;

}



.abst-settings-container select:focus,

.ab-settings-subsection select:focus {

  border-color: #17A8E3;

  box-shadow: 0 0 0 3px rgba(23, 168, 227, 0.1);

  outline: none;

}



/* Text inputs in settings */

.abst-settings-container input[type="text"],

.abst-settings-container input[type="password"],

.abst-settings-container input[type="number"],

.ab-settings-subsection input[type="text"],

.ab-settings-subsection input[type="password"],

.ab-settings-subsection input[type="number"] {

  border: 1px solid #d1d5db;

  border-radius: 6px;

  padding: 8px 12px;

  font-size: 14px;

  transition: all 0.15s ease;

}



.abst-settings-container input[type="text"]:focus,

.abst-settings-container input[type="password"]:focus,

.abst-settings-container input[type="number"]:focus,

.ab-settings-subsection input[type="text"]:focus,

.ab-settings-subsection input[type="password"]:focus,

.ab-settings-subsection input[type="number"]:focus {

  border-color: #17A8E3;

  box-shadow: 0 0 0 3px rgba(23, 168, 227, 0.1);

  outline: none;

}



/* Toggle switches for settings page */

.abst-settings-container input.ab-toggle,

.ab-settings-subsection input.ab-toggle {

  appearance: none !important;

  -webkit-appearance: none !important;

  width: 36px !important;

  height: 20px !important;

  min-width: 36px !important;

  min-height: 20px !important;

  background: #d1d5db !important;

  border: none !important;

  border-radius: 10px !important;

  cursor: pointer;

  transition: all 0.2s ease;

  position: relative;

  vertical-align: middle;

  margin: 0 10px 0 0 !important;

  padding: 0 !important;

  box-shadow: none !important;

  outline: none !important;

}



.abst-settings-container input.ab-toggle::before,

.ab-settings-subsection input.ab-toggle::before {

  content: "" !important;

  position: absolute !important;

  width: 16px !important;

  height: 16px !important;

  background: #ffffff !important;

  background-image: none !important;

  border-radius: 50% !important;

  top: 2px !important;

  left: 2px !important;

  transition: all 0.2s ease !important;

  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.2) !important;

  margin: 0 !important;

  float: none !important;

  display: block !important;

  pointer-events: none !important;

}



.abst-settings-container input.ab-toggle:checked,

.ab-settings-subsection input.ab-toggle:checked {

  background: #17A8E3 !important;

}



.abst-settings-container input.ab-toggle:checked::before,

.ab-settings-subsection input.ab-toggle:checked::before {

  left: 18px !important;

}



.abst-settings-container input.ab-toggle:hover,

.ab-settings-subsection input.ab-toggle:hover {

  background: #9ca3af !important;

}



.abst-settings-container input.ab-toggle:checked:hover,

.ab-settings-subsection input.ab-toggle:checked:hover {

  background: #1289ba !important;

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

// Tab switching functionality

document.addEventListener('DOMContentLoaded', function() {

  const tabButtons = document.querySelectorAll('.abst-tab-btn');

  const tabPanels = document.querySelectorAll('.abst-tab-panel');

  

  // Check for saved tab in URL hash or localStorage

  const savedTab = window.location.hash.replace('#', '') || localStorage.getItem('abst_settings_tab') || 'welcome';

  

  // Activate saved tab on load

  activateTab(savedTab);

  

  tabButtons.forEach(button => {

    button.addEventListener('click', function() {

      const tabId = this.getAttribute('data-tab');

      activateTab(tabId);

      

      // Save to localStorage and update URL

      localStorage.setItem('abst_settings_tab', tabId);

      history.replaceState(null, null, '#' + tabId);

    });

  });

  

  function activateTab(tabId) {

    // Remove active from all buttons and panels

    tabButtons.forEach(btn => btn.classList.remove('active'));

    tabPanels.forEach(panel => panel.classList.remove('active'));

    

    // Add active to selected

    const activeBtn = document.querySelector('.abst-tab-btn[data-tab="' + tabId + '"]');

    const activePanel = document.getElementById('tab-' + tabId);

    

    if (activeBtn && activePanel) {

      activeBtn.classList.add('active');

      activePanel.classList.add('active');

    } else {

      // Fallback to welcome tab

      document.querySelector('.abst-tab-btn[data-tab="welcome"]').classList.add('active');

      document.getElementById('tab-welcome').classList.add('active');

      tabId = 'welcome';

    }

    

    // Hide save button on license and welcome tabs

    const saveBtn = document.querySelector('.floating-save-button-row');

    if (saveBtn) {

      saveBtn.style.display = (tabId === 'license' || tabId === 'welcome') ? 'none' : 'block';

    }

  }

});






// Toggle handlers for settings with dependent options

document.addEventListener('DOMContentLoaded', function() {

  // Heatmaps toggle

  const heatmapToggle = document.getElementById('abst_heatmap_enable_user_journeys');

  const heatmapSettings = document.getElementById('heatmap_settings_area');

  if (heatmapToggle && heatmapSettings) {

    heatmapToggle.addEventListener('change', function() {

      heatmapSettings.style.display = this.checked ? 'block' : 'none';

    });

  }

  

  // UUID/Advanced Tracking toggle

  const uuidToggle = document.getElementById('use_uuid');

  const uuidSettings = document.getElementById('uuid_settings_area');

  if (uuidToggle && uuidSettings) {

    uuidToggle.addEventListener('change', function() {

      uuidSettings.style.display = this.checked ? 'block' : 'none';

    });

  }

  

});

</script>

<?php 

// thin air

