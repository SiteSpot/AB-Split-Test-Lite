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

// Saved settings shown on this screen. Styles and scripts for the page live in
// css/abst-settings.css and js/abst-settings.js (enqueued from admin_enqueue_scripts).

$post_types = get_post_types(array('public' => true), 'objects');

$selected_post_types = abst_get_admin_setting('selected_post_types');

$add_canonical = abst_get_admin_setting('ab_change_canonicals') ? 'checked' : '';

// Cache clearing is on unless the user has turned it off.
$enable_clear_cache = (abst_get_admin_setting('ab_dont_clear_cache_on_update') == '1') ? '' : 'checked';

$detected_caches = !empty(abst_get_detected_caches()) ? implode(', ', abst_get_detected_caches()) : 'None detected';

// Don't send events until cookie consent is given; saves to session storage until approved.
$wait_for_approval = abst_get_admin_setting('abst_wait_for_approval') ? 'checked' : '';

// Heatmap data retention, in days (minimum 1, default 3).
$heatmap_retention_length = max(1, intval(abst_get_admin_setting('abst_heatmap_retention_length')));

$enable_user_journeys = abst_get_admin_setting('abst_enable_user_journeys');
$enable_user_journeys = ($enable_user_journeys && $enable_user_journeys !== '0') ? 'checked' : '';

$mcpServerName = str_replace('.', '-', get_bloginfo('url'));
$mcpServerName = str_replace(array('http://', 'https://'), '', $mcpServerName);
$mcpServerName = 'wordpress-' . $mcpServerName;

?>



<div id="fl-bt_bb_ab_test-form" class="fl-settings-form">

  

  <form id="bt-bb-ab-form" action="<?php echo esc_url(ABST_Admin::get_current_settings_url()); ?>" method="post">

    <?php wp_nonce_field('bt-bb-ab-nonce', 'bt-bb-ab-nonce'); ?>





      <p>Need a hand? Watch the <a href="<?php echo esc_url(admin_url('options-general.php?page=bt_bb_ab_test&wizard=1')); ?>">walkthrough video</a>  or check out the <a href="https://absplittest.com/documentation/" target="_blank">documentation</a>.</p>



      <div class="fl-settings-form-content"></div>






    <div class="abst-settings-container">

      <!-- Vertical Tabs Navigation -->

      <div class="abst-settings-tabs">

        <button type="button" class="abst-tab-btn active" data-tab="account">

          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>

          Account

        </button>

        <button type="button" class="abst-tab-btn" data-tab="testing">

          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>

          Testing

        </button>

        <button type="button" class="abst-tab-btn" data-tab="conversions">

          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg>

          Conversions

        </button>

        <button type="button" class="abst-tab-btn" data-tab="tracking">

          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>

          Tracking & Privacy

        </button>

        <button type="button" class="abst-tab-btn" data-tab="heatmaps">

          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><circle cx="15.5" cy="8.5" r="1.5"></circle><circle cx="8.5" cy="15.5" r="1.5"></circle><circle cx="15.5" cy="15.5" r="1.5"></circle></svg>

          Heatmaps

        </button>


        <button type="button" class="abst-tab-btn" data-tab="developer">

          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"></path></svg>

          Developer

        </button>

        <button type="button" class="abst-tab-btn" data-tab="danger">

          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>

          Danger Zone

        </button>

      </div>



      <!-- Tab Content Panels -->

      <div class="abst-settings-content">

        

        <!-- ACCOUNT TAB -->

        <div class="abst-tab-panel active" id="tab-account">

          <h2>Welcome to AB Split Test</h2>

          


          <div class="ab-settings-subsection">

            <h3>Watch the quick video that explains everything.</h3>

            <div class="video-container">

              <a href="https://share.descript.com/view/Vbz3Q3aJu05" target="_blank" class="button button-secondary">▶ Watch Video</a>

            </div>

          </div>

          

          <div class="ab-settings-subsection">

            <p style="margin-top: 0.25rem; font-size: 12px; color: #666;">Version <?php echo esc_html( BT_AB_TEST_VERSION ); ?> </p>

            <p>Get started with A/B testing on your WordPress site.</p>

            <h3>Quick Start</h3>

            <p>1. Install and activate the plugin</p>

            <p>2. Create your first test from any post or page</p>

            <p>3. Watch conversions roll in!</p>

            <p><a href="https://absplittest.com/documentation" target="_blank" class="button button-secondary">View Documentation</a></p>

          </div>

          <p class="abst-pro-notice"><?php esc_html_e( 'AB Split Test Pro is a separate plugin that adds revenue and form goals, sub-goals, audiences, AI test ideas and more.', 'ab-split-test-lite' ); ?> <a href="https://absplittest.com/?utm_source=wporg-lite&amp;utm_medium=plugin&amp;utm_campaign=settings-notice" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Learn more', 'ab-split-test-lite' ); ?></a></p>

        </div>



        <!-- TESTING TAB -->

        <div class="abst-tab-panel" id="tab-testing">

          <h2>Testing</h2>

          

          <div class="ab-settings-subsection ab-test-post-types">

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

            <label for="add_canonical"><strong>Add canonical links to page variations:</strong></label>

            <p>To avoid SEO duplicate content issues. Adds the default page from your full page split test as a canonical link to each variation page.</p>

            <p><input type="checkbox" class="ab-toggle" id="add_canonical" name="add_canonical" value="1" <?php echo esc_attr($add_canonical); ?> /> Add canonical links.</p>

          </div>

        </div><!-- end #tab-testing -->



        <div class="abst-tab-panel" id="tab-conversions">

          <h2>Conversions</h2>

          <div class="ab-settings-subsection ab-settings-conversion-triggers">
            <label><strong>Conversion Goal</strong></label>
            <p>A test counts a conversion when a visitor does one of these:</p>
            <ul class="abst-trigger-list">
              <li class="abst-trigger-on"><span class="abst-trigger-check">&#10003;</span> Page or Post Visit</li>
              <li class="abst-trigger-on"><span class="abst-trigger-check">&#10003;</span> Element Click (any CSS selector)</li>
            </ul>
            <p>Choose the goal on each test.</p>
          </div>

        </div><!-- end #tab-conversions -->



        <!-- TRACKING TAB -->

        <div class="abst-tab-panel" id="tab-tracking">

          <h2>Tracking & Privacy</h2>



          <div class="ab-settings-subsection ab-test-wait-for-approval">

            <label for="wait_for_approval"><strong>Privacy - Cookie Consent</strong></label>

            <p>With this enabled, AB Split Test will wait for cookie consent before sending test data.</p>

            <p><input type="checkbox" class="ab-toggle" id="wait_for_approval" name="wait_for_approval" value="1" <?php echo esc_attr($wait_for_approval); ?> /> Enable wait for approval.</p>

            <div id="wait_for_approval_info_area">

              <div style="display: none;" id="wait_for_approval_info">

                <h4>Cookie Consent Information</h4>

                <p>Tests still run. Pending assignments stay in page memory; tracking cookies, browser storage and data transmission wait for consent. Withdrawing consent clears tracking identifiers and history.</p>

                <p>Works automatically with Cookiebot, CookieConsent (Orestbida), Usercentrics (including Termageddon), WP Consent API, CookieYes, Complianz, and Cookies and Content Security Policy. In Usercentrics, a service named "AB Split Test" controls it directly; otherwise it follows the statistics category, or marketing if there is none.</p>

                <p>Custom banners call <code>setAbstApprovalStatus(true)</code> when the visitor accepts (it is remembered on later pages) and <code>setAbstApprovalStatus(false)</code> when consent is withdrawn.</p>

              </div>

              <a href="#" id="wait_for_approval_info_toggle">More Information.</a>


            </div>

          </div>






        </div><!-- end #tab-tracking -->



        <!-- HEATMAPS TAB -->

        <div class="abst-tab-panel" id="tab-heatmaps">

          <h2>Heatmaps</h2>



          <div class="ab-settings-subsection ab-test-user-journeys">

            <p>Track anonymized visitor journeys and key interactions like clicks and navigation to build click insights for your visitors.</p>

            <p>Generates heatmaps by page / test / variation / size.</p>

            <p><input type="checkbox" class="ab-toggle" id="abst_heatmap_enable_user_journeys" name="enable_user_journeys" value="1" <?php echo esc_attr($enable_user_journeys); ?> /> <strong>Enable heatmaps</strong></p>



            <div id="heatmap_settings_area" style="<?php echo empty($enable_user_journeys) ? 'display:none;' : ''; ?>">

              <hr style="margin: 20px 0; border: none; border-top: 1px solid #e2e8f0;">



              <label><strong>Default Viewer Page</strong></label>

              <p>Heatmaps are recorded anonymously on every page of your site. Choose the page shown first when you open the heatmap viewer.</p>
              <p><select id="heatmap_page_select" name="heatmap_pages[]" style="width: 25rem;"></select></p>




              <hr style="margin: 20px 0; border: none; border-top: 1px solid #e2e8f0;">



              <strong>Data Retention</strong>

              <p><label for="heatmap_retention_length">Days to keep heatmap data</label></p>

              <p><input type="number" name="heatmap_retention_length" id="heatmap_retention_length" min="1" step="1" value="<?php echo esc_attr($heatmap_retention_length); ?>" style="width: 6rem;" /> days</p>



              <hr style="margin: 20px 0; border: none; border-top: 1px solid #e2e8f0;">



              <label><strong>Data Management</strong></label>

              <p><button type="button" id="remove_heatmap_data" class="button-secondary">Remove all Heatmap Data</button></p>

            </div>

          </div>

        </div><!-- end #tab-heatmaps -->


        <!-- MCP SETTINGS TAB -->

        <div class="abst-tab-panel" id="tab-developer">

          <h2>Developer</h2>

          <?php

          // Check if WordPress MCP Adapter plugin is installed

          $mcp_adapter_installed = class_exists('WP\\MCP\\Core\\McpAdapter');

          

          ?>

          <div class="ab-settings-subsection">

            <p><strong>AB Split Test integrates with anything via the REST API or MCP (Model Context Protocol).</strong></p>

            

            <h3>Available Tools</h3>

            <p>The following tools are available over the REST API and MCP:</p>

            <ul style="list-style: disc; margin-left: 20px;">

              <li><strong>create-test</strong> - Create new A/B tests (magic, ab_test, css_test, full_page)</li>

              <li><strong>list-tests</strong> - List all tests with their configurations</li>

              <li><strong>get-test-results</strong> - Get detailed results for a specific test</li>

              <li><strong>update-test-status</strong> - Change test status (publish, draft, pending, complete)</li>

              <li><strong>update-test-settings</strong> - Update the conversion goal and other settings on an existing test</li>
              <li><strong>get-test-details</strong> - Get the full configuration of a specific test</li>

              <li><strong>get-heatmap-data</strong> - Get click / scroll heatmap data for a page</li>

              <li><strong>list-heatmap-pages</strong> - List the pages that have heatmap data</li>

            </ul>


          </div>



          <div class="ab-settings-subsection">

            <h3>REST API Endpoints</h3>

            <p>Access AB Split Test programmatically via REST API. Base URL: <code><?php echo esc_url(rest_url('bt-bb-ab/v1')); ?></code></p>

            <ul style="list-style: disc; margin-left: 20px;">

              <li><strong>POST</strong> <code>/create-test</code> - Create new tests</li>

              <li><strong>GET</strong> <code>/list-tests</code> - List all tests</li>

              <li><strong>GET</strong> <code>/test-results/{id}</code> - Get test results</li>

              <li><strong>POST</strong> <code>/update-test-status</code> - Update test status</li>

              <li><strong>POST</strong> <code>/update-test-settings</code> - Update the conversion goal and settings</li>
              <li><strong>GET</strong> <code>/test-details/{id}</code> - Get a test's full configuration</li>

              <li><strong>GET</strong> <code>/heatmap-data</code> - Aggregated heatmap / click / scroll data for a page</li>

              <li><strong>GET</strong> <code>/heatmap-pages</code> - Pages that have recorded heatmap data</li>

            </ul>


            <p style="margin-top: 10px;"><small>Requires WordPress Application Password for authentication.</small></p>

            

            <details style="margin-top: 20px;">

              <summary style="cursor: pointer; font-weight: bold; padding: 10px; background: #f8fafc; border-radius: 5px;">📋 Example: Create a Magic Test via API</summary>

              <div style="margin-top: 15px; padding: 15px; background: #f8fafc; border-radius: 5px;">

                <p><strong>Scenario:</strong> Create a magic test for the H1 headline on page ID 12 with one variation, counting a visit to the thank-you page (page ID 34) as the conversion.</p>

                

                <h4 style="margin-top: 15px;">cURL Example:</h4>

                <pre style="background: #2d2d2d; color: #f8f8f2; padding: 15px; border-radius: 5px; overflow-x: auto; font-size: 12px;"><code>curl -X POST "<?php echo esc_url(rest_url('bt-bb-ab/v1/create-test')); ?>" \

  -u "your-username:your-application-password" \

  -H "Content-Type: application/json" \

  -d '{

    "test_title": "Homepage H1 Headline Test",

    "test_type": "magic",

    "status": "publish",

    "target_percentage": "50",

    "conversion_type": "page",

    "conversion_page_id": 34,

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

          "New Compelling Headline"

        ]

      }

    ]

  }'</code></pre>



                <h4 style="margin-top: 20px;">JavaScript (Fetch) Example:</h4>

                <pre style="background: #2d2d2d; color: #f8f8f2; padding: 15px; border-radius: 5px; overflow-x: auto; font-size: 12px;"><code>const credentials = btoa('your-username:your-application-password');



fetch('<?php echo esc_url(rest_url('bt-bb-ab/v1/create-test')); ?>', {

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

    conversion_type: 'page',

    conversion_page_id: 34,

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

          'New Compelling Headline'

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

                  <li><strong>conversion_type:</strong> "page" - a conversion is counted when a visitor reaches the page in <strong>conversion_page_id</strong>; "selector" - when a visitor clicks an element matching <strong>conversion_selector</strong></li>

                  <li><strong>conversion_page_id:</strong> WordPress ID of the conversion page (page goal)</li>

                  <li><strong>conversion_selector:</strong> CSS selector of the element to click, e.g. <code>.buy-button</code> (selector goal)</li>

                  <li><strong>magic_definition:</strong> Array of elements to test with their variations</li>

                  <li><strong>scope:</strong> Optional but recommended page targeting metadata. Use <code>page_id</code> (WordPress page/post ID) or <code>url</code> (path fragment) or both for precise targeting</li>

                  <li><strong>selector:</strong> CSS selector for the element to test</li>

                  <li><strong>variations:</strong> Two strings: the original text first, then the variation</li>

                </ul>

                

                <p style="margin-top: 15px;"><small><strong>Note:</strong> Replace "your-username" and "your-application-password" with your WordPress credentials. For magic tests, the first entry in variations is the original text and the second is the variation. For backward compatibility, the API still accepts legacy aliases such as <code>name</code> and <code>conversion_page</code>, but new integrations should send the canonical fields shown above.</small></p>

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

            <ol style="margin-left: 20px;">
              <li><a href="https://github.com/WordPress/mcp-adapter/releases/latest/download/mcp-adapter.zip" target="_blank" rel="noopener noreferrer">Download the WordPress MCP Adapter</a> (a zip file from WordPress on GitHub)</li>
              <li>Go to <strong>Plugins &rarr; Add New Plugin</strong> and click <strong>Upload Plugin</strong></li>
              <li>Choose the zip, click <strong>Install Now</strong>, then <strong>Activate Plugin</strong></li>
              <li>Come back to this tab for the connection steps</li>
            </ol>


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

            

            <?php
            // Each client config is assembled here and echoed inside a whitespace-
            // significant <pre>; fragments are escaped as they are concatenated. The
            // only raw HTML is the two placeholder spans the config JS rewrites live.
            $abst_mcp_url  = rest_url( 'mcp/mcp-adapter-default-server' );
            $abst_mcp_user_span = '<span class="abst-mcp-username-placeholder">' . esc_html( wp_get_current_user()->user_login ) . '</span>';
            $abst_mcp_pass_span = '<span class="abst-mcp-password-placeholder">YOUR_APPLICATION_PASSWORD</span>';
            $abst_mcp_json = function( $root ) use ( $abst_mcp_url, $abst_mcp_user_span, $abst_mcp_pass_span, $mcpServerName ) {
              return "{\n"
                . '  "' . esc_html( $root ) . "\": {\n"
                . '    "' . esc_html( $mcpServerName ) . "\": {\n"
                . "      \"command\": \"npx\",\n"
                . "      \"args\": [\"-y\", \"@automattic/mcp-wordpress-remote@latest\"],\n"
                . "      \"env\": {\n"
                . '        "WP_API_URL": "' . esc_html( $abst_mcp_url ) . "\",\n"
                . '        "WP_API_USERNAME": "' . $abst_mcp_user_span . "\",\n"
                . '        "WP_API_PASSWORD": "' . $abst_mcp_pass_span . "\"\n"
                . "      }\n    }\n  }\n}";
            };
            $abst_mcp_clients = array(
              'windsurf' => array(
                'title' => 'Windsurf IDE',
                'intro' => 'Add to your <code>.windsurf/mcp_config.json</code>:',
                'code'  => $abst_mcp_json( 'mcpServers' ),
              ),
              'claude' => array(
                'title' => 'Claude Desktop',
                'intro' => 'Add to your Claude Desktop config file.<br><strong>Windows:</strong> <code>%APPDATA%\Claude\claude_desktop_config.json</code><br><strong>macOS:</strong> <code>~/Library/Application Support/Claude/claude_desktop_config.json</code>',
                'code'  => $abst_mcp_json( 'mcpServers' ),
              ),
              'claude_code' => array(
                'title' => 'Claude Code',
                'intro' => 'Run once in your terminal (from any directory):',
                'code'  => 'claude mcp add ' . esc_html( $mcpServerName ) . ' \\' . "\n"
                  . '  --env WP_API_URL=' . esc_html( $abst_mcp_url ) . ' \\' . "\n"
                  . '  --env WP_API_USERNAME=' . $abst_mcp_user_span . ' \\' . "\n"
                  . '  --env WP_API_PASSWORD=' . $abst_mcp_pass_span . ' \\' . "\n"
                  . '  -- npx -y @automattic/mcp-wordpress-remote@latest',
              ),
              'codex' => array(
                'title' => 'Codex CLI (OpenAI)',
                'intro' => 'Add to <code>~/.codex/config.toml</code>:',
                'code'  => '[mcp_servers.' . esc_html( str_replace( '-', '_', $mcpServerName ) ) . "]\n"
                  . "command = \"npx\"\n"
                  . "args = [\"-y\", \"@automattic/mcp-wordpress-remote@latest\"]\n"
                  . "\n"
                  . '[mcp_servers.' . esc_html( str_replace( '-', '_', $mcpServerName ) ) . ".env]\n"
                  . 'WP_API_URL = "' . esc_html( $abst_mcp_url ) . "\"\n"
                  . 'WP_API_USERNAME = "' . $abst_mcp_user_span . "\"\n"
                  . 'WP_API_PASSWORD = "' . $abst_mcp_pass_span . '"',
              ),
              'cursor' => array(
                'title' => 'Cursor',
                'intro' => 'Add to <code>~/.cursor/mcp.json</code> (or <code>.cursor/mcp.json</code> inside a project):',
                'code'  => $abst_mcp_json( 'mcpServers' ),
              ),
              'cline' => array(
                'title' => 'Cline (VS Code Extension)',
                'intro' => 'Add to Cline\'s MCP settings file (<code>~/.cline/mcp.json</code>, or the MCP settings JSON in the extension):',
                'code'  => $abst_mcp_json( 'mcpServers' ),
              ),
              'openclaw' => array(
                'title' => 'OpenClaw',
                'intro' => 'Add to <code>~/.openclaw/openclaw.json</code> (note the nested <code>mcp.servers</code> shape), then restart the gateway:',
                'code'  => "{\n"
                  . "  \"mcp\": {\n"
                  . "    \"servers\": {\n"
                  . '      "' . esc_html( $mcpServerName ) . "\": {\n"
                  . "        \"command\": \"npx\",\n"
                  . "        \"args\": [\"-y\", \"@automattic/mcp-wordpress-remote@latest\"],\n"
                  . "        \"env\": {\n"
                  . '          "WP_API_URL": "' . esc_html( $abst_mcp_url ) . "\",\n"
                  . '          "WP_API_USERNAME": "' . $abst_mcp_user_span . "\",\n"
                  . '          "WP_API_PASSWORD": "' . $abst_mcp_pass_span . "\"\n"
                  . "        }\n      }\n    }\n  }\n}",
              ),
              'hermes' => array(
                'title' => 'Hermes Agent (Nous Research)',
                'intro' => 'Add under the top-level <code>mcp_servers</code> key in <code>~/.hermes/config.yaml</code>, then run <code>/reload-mcp</code> or restart:',
                'code'  => "mcp_servers:\n"
                  . '  ' . esc_html( $mcpServerName ) . ":\n"
                  . "    command: \"npx\"\n"
                  . "    args: [\"-y\", \"@automattic/mcp-wordpress-remote@latest\"]\n"
                  . "    env:\n"
                  . '      WP_API_URL: "' . esc_html( $abst_mcp_url ) . "\"\n"
                  . '      WP_API_USERNAME: "' . $abst_mcp_user_span . "\"\n"
                  . '      WP_API_PASSWORD: "' . $abst_mcp_pass_span . '"',
              ),
            );
            foreach ( $abst_mcp_clients as $abst_mcp_key => $abst_mcp_client ) :
              ?>
              <details class="abst-mcp-client">
                <summary><?php echo esc_html( $abst_mcp_client['title'] ); ?></summary>
                <div class="abst-mcp-client-body">
                  <p><?php echo wp_kses_post( $abst_mcp_client['intro'] ); ?></p>
                  <div class="abst-mcp-config-wrap">
                    <button type="button" class="button button-small abst-mcp-copy" data-target="abst_mcp_config_<?php echo esc_attr( $abst_mcp_key ); ?>"><?php echo esc_html( 'Copy' ); ?></button>
                    <pre class="abst-mcp-config"><code id="abst_mcp_config_<?php echo esc_attr( $abst_mcp_key ); ?>"><?php
                      // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Assembled above from individually escaped parts plus two known placeholder spans.
                      echo $abst_mcp_client['code'];
                    ?></code></pre>
                  </div>
                </div>
              </details>
            <?php
            endforeach;
            ?>


            

            <h4 style="margin-top: 20px;">Step 3: Copy Configuration & Connect</h4>

            <p>Your configuration is ready to use:</p>

            <ol style="margin-left: 20px;">

              <li>Copy the configuration for your AI client (Windsurf, Claude Desktop, Claude Code, Codex, Cursor, Cline, OpenClaw or Hermes) from below</li>

              <li>Paste it into your MCP client's configuration file</li>

              <li>Restart your MCP client to load the new configuration. Load a new conversation, as old conversations generally cache the previous configuration</li>

              <li>The AB Split Test tools should appear in your AI assistant's available tools</li>

              <li>Try asking: "List all my A/B tests" or "Create a test of the home page against the new landing page we just generated, conversion is reaching the thank-you page"</li>

            </ol>            

            <h4 style="margin-top: 20px;">Example: Ask Your AI Assistant</h4>

            <p><strong>Create a Magic Test:</strong></p>

            <p><em>"Create a magic A/B test called 'Homepage Headline Test' that tests the h1 element against a new headline, 'Start Free Today'. Count a visit to the thank-you page as the conversion."</em></p>

            

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

        </div><!-- end #tab-developer -->

        <div class="abst-tab-panel" id="tab-danger">

          <h2>Danger Zone</h2>

          <div class="ab-settings-subsection ab-test-clear-cache">

            <label for="enable_clear_cache"><strong>Cache Clearing</strong></label>

            <p>AB Split Test can automatically clear caches when a post or test is updated. Leave this on unless you manage cache clearing yourself - stale caches can show visitors outdated test variations.</p>

            <p><input type="checkbox" class="ab-toggle" id="enable_clear_cache" name="enable_clear_cache" value="1" <?php echo esc_attr($enable_clear_cache); ?> /> Clear caches when tests or tested posts are updated.</p>

            <p>Detected caches: <?php echo esc_html($detected_caches); ?></p>

          </div>

        </div>





        <div class="floating-save-button-row"><input type="submit" class="button-primary" name="bt_save" value="<?php esc_attr_e('Save Settings', 'ab-split-test-lite'); ?>" /></div>



      </div><!-- end .abst-settings-content -->

    </div><!-- end .abst-settings-container -->



    </form>

  </div>

</div>
<?php 

// thin air
