<?php
//autocomplete add on
class Ab_Tests_Autocomplete {
    function __construct() {
        add_action('admin_notices', array($this, 'add_scheduled_event'), 10);
        add_action('bt_maybe_implement_winner', array($this, 'autocomplete_check'), 10, 2);
    }

    public static function get_data($id) {
        return ['autocomplete_on' => get_post_meta($id, 'autocomplete_on', true), 'min_days' => get_post_meta($id, 'ac_min_days', true), 'min_views' => get_post_meta($id, 'ac_min_views', true) ];
    }
    
    function add_scheduled_event() {
        if (!wp_next_scheduled('bt_maybe_implement_winner')) {
            wp_schedule_event(strtotime('01:00am'), 'daily', 'bt_maybe_implement_winner');
        }
    }
    function autocomplete_check() {
        $args = array(
            'post_type' => array('bt_experiments'), // experiments
            'post_status' => array('publish'), // only active ones
            'posts_per_page' => - 1, // all of em
        );
        $experiments = get_posts($args);
        if (!empty($experiments)) {
            foreach ($experiments as $experiment) {
                $experimentName = $experiment->post_title;
                $conversion_style = get_post_meta($experiment->ID, 'conversion_style', true);
                if($conversion_style == 'thompson') continue;
                $autocomplete_on = get_post_meta($experiment->ID, 'autocomplete_on', true);
                $min_days = get_post_meta($experiment->ID, 'ac_min_days', true);
                $min_views = get_post_meta($experiment->ID, 'ac_min_views', true);
                $webhook_url = get_post_meta($experiment->ID, 'webhook_url', true);
                $conversion_use_order_value = get_post_meta($experiment->ID, 'conversion_use_order_value', true);
                $experiment_start = strtotime($experiment->post_date_gmt);
                $min_age = strtotime('-' . $min_days . ' days');

                if ($autocomplete_on != '1') continue;
                // if its too soon, then bail
                if ($experiment_start > $min_age) continue;
                // all is ok, get the experiment data
                $observations = get_post_meta($experiment->ID, 'observations', true);
                $goodStats = true;
                if (empty($observations)) // empty
                $goodStats = false;
                else {
                    //check results are decent
                    foreach ($observations as $m => $a) {
                        if ($m === 'bt_bb_ab_stats') continue;
                        if ((isset($a['visit']) && $a['visit'] < 1) || (isset($a['conversion']) && $a['conversion'] < 0)) {
                            $goodStats = false;
                            break;
                        }
                    }
                }
                if ($goodStats) {
                    require_once 'includes/statistics.php';
                    // analyze
                    $test_age = (time() - get_post_time('U',true,$experiment->ID))/60/60/24; 

                    if($conversion_use_order_value == '1') {
                        // Use Welch's T-Test for revenue/order value data (continuous values)
                        $observations = bt_bb_ab_revenue_analyzer($observations, $test_age);
                    }
                    else
                    {
                        // Use Bayesian analyzer for binary conversion data
                        $observations = bt_bb_ab_split_test_analyzer($observations,$test_age);
                    }
                    
                } else {
                    continue; // not enough data, bail
                    
                }
                // if we have results
                if (is_array($observations)) {
                    if (isset($observations['bt_bb_ab_stats'])) {
                        //enough visits?
                        $enoughvisits = true;
                        foreach ($observations as $key => $var) {
                            if ($key == "bt_bb_ab_stats") continue;
                            if (isset($var['visit']) && $var['visit'] < $min_views) $enoughvisits = false;
                        }
                        if (!$enoughvisits) {
                            continue; // not enough visits, dont do anything
                        }
                        $likelywinner = $observations['bt_bb_ab_stats']['best'];
                        $likelywinnerpercentage = $observations['bt_bb_ab_stats']['probability'];
                        $percentage_target = apply_filters('ab_complete_confidence', 95);
                        if ($likelywinnerpercentage >= $percentage_target) {
                            //hides the losers
                            //$this->hide_losing_variations($experiment->ID, $likelywinner);
                            //$this->hide_losing_variations_gb_el($experiment->ID, $likelywinner);
                            //makes the post apending revision
                            wp_update_post(array('ID' => $experiment->ID, 'post_status' => 'complete'));
                            update_post_meta($experiment->ID, 'test_winner', $likelywinner);
                            //mail the user
                            $abst_notification_emails = ab_get_admin_setting('abst_notification_emails');
                            $notify_to = !empty($abst_notification_emails) ? array_map('trim', explode(',', $abst_notification_emails)) : get_option('admin_email');
                            wp_mail($notify_to, BT_AB_TEST_WL_NAME . ": $experimentName, Complete.", BT_AB_TEST_WL_ABTEST . ": $experimentName \nWinner: $likelywinner \nProbability: $likelywinnerpercentage%");
                            //webhook hook
                            btab_send_webhook($experiment->ID, $likelywinner, $likelywinnerpercentage);
                        }
                    }
                }
            }
        }
    }
    function show_experiment_results($post) {
        //get options
        $pid = $post->ID;
        $autocomplete_on = get_post_meta($pid, 'autocomplete_on', true);
        $min_days = get_post_meta($pid, 'ac_min_days', true);
        $min_views = get_post_meta($pid, 'ac_min_views', true);
        $observations = get_post_meta($pid, 'observations', true);
        $conversion_style = get_post_meta($pid, 'conversion_style', true);
        $goodStats = true;
        $test_age = (time() - get_post_time('U',true,$pid))/60/60/24; 

        if (empty($observations)) // empty
        {
            $goodStats = false;
        } else {
            //check results are decent
            foreach ($observations as $m => $a) {
                if ($m === 'bt_bb_ab_stats') continue;
                if ((isset($a['visit']) && $a['visit'] < 1) || (isset($a['conversion']) && $a['conversion'] < 0)) {
                    $goodStats = false;
                    break;
                }
            }
        }
        if ($goodStats && $conversion_style != 'thompson') {
            require_once 'includes/statistics.php';
            $conversion_use_order_value = get_post_meta($pid, 'conversion_use_order_value', true);
            if ($conversion_use_order_value == '1' && is_array($observations)) {
                // Use Welch's T-Test for revenue/order value data (continuous values)
                $observations = bt_bb_ab_revenue_analyzer($observations, $test_age);
            } else {
                // Use Bayesian analyzer for binary conversion data
                $observations = bt_bb_ab_split_test_analyzer($observations, $test_age);
            }
        }
        // if we have results
        if (is_array($observations)) {
            if (isset($observations['bt_bb_ab_stats'])) {
                //enough visits?
                $notenoughvisits = '';
                foreach ($observations as $key => $var) {
                    if ($key == "bt_bb_ab_stats") continue; 
                    if (isset($var['visit']) && $var['visit'] < $min_views) $notenoughvisits.= "<h4>Variation <code>$key</code> needs more views</h4>"; 
                }
            }
        } else { // give em a guide for getting going
            echo "<h3>Not enough data yet</h3><p>Create some variations and let it run for some time to see results here.</p>";
        }
    }
    function hide_losing_variations_gb_el($eid, $variation) {
        $args = array('post_type' => 'any', 'meta_query' => [['key' => 'bt_post_experiments', 'value' => '', 'compare' => '!=', ]], 'suppress_filters' => true, 'posts_per_page' => - 1);
        $query = new WP_Query($args);
        $posts = $query->posts;
        //loop through posts
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $post_id = get_the_ID();
                $experiments = get_post_meta($post_id, 'bt_post_experiments', true);
                if (!empty($experiments)) {
                    foreach ($experiments as $key => $exp) {
                        if ($exp['eid'] == $eid && $exp['variation'] == $variation) {
                            $experiments[$key]['status'] = 'hidden';
                        }
                    }
                    update_post_meta($post_id, 'bt_post_experiments', $experiments);
                    $editor = get_post_meta($post_id, 'bt_post_experiments_editor', true);
                    if ($editor == 'gutenberg') { // update gutenberg block hidden attribute to true
                        $content = get_the_content();
                        $content = preg_replace('/bt-eid="' . preg_quote($eid, '/') . '.*bt-variation="' . preg_quote($variation, '/') . '"|bt-variation="' . preg_quote($variation, '/') . '".*bt-eid="' . preg_quote($eid, '/') . '/', 'bt_hidden="true" $0', $content);
                        wp_update_post(['ID' => $post_id, 'post_content' => $content]);
                    } elseif ($editor == 'elementor') { // update elementor element hidden attribute to true
                        $el_meta = get_post_meta($post_id, '_elementor_data', true);
                        $content = preg_replace('/("bt_experiment":"' . preg_quote($eid, '/') . '","bt_variation":"' . preg_quote($variation, '/') . '".*?bt_hidden":").*?(")/', '$1true$2', $el_meta);
                        update_post_meta($post_id, '_elementor_data', $content);
                    }
                }
            }
        }
        wp_reset_postdata();
    }
    // search entire site for variations
    function hide_losing_variations($eid, $variation) {
        // get all BB posts
        $args = array('post_type' => 'any', 'meta_query' => array(array('key' => '_fl_builder_enabled', 'value' => 1, 'compare' => '=',)), 'suppress_filters' => true, 'posts_per_page' => - 1);
        $query = new WP_Query($args);
        $posts = $query->posts;
        //loop through posts
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $post_id = get_the_ID();
                $slug = get_post_field('post_name', get_the_ID());
                $builderData['_fl_builder_data'] = get_post_meta($post_id, "_fl_builder_data");
                //do it for the drafts to if they exist
                if (get_post_meta($post_id, "_fl_builder_draft") !== null) // if they exist
                $builderData['_fl_builder_draft'] = get_post_meta($post_id, "_fl_builder_draft"); // add to the array
                foreach ($builderData as $k => $v) {
                    $newBuilderData = $v;
                    $makeChanges = false;
                    foreach ($newBuilderData[0] as $key => $obj) {
                        if (!empty($obj->settings->ab_test) && !empty($obj->settings->bt_variation_name)) {
                            if ($obj->settings->ab_test == $eid && $obj->settings->bt_variation_name !== $variation) // its the experiment, but not the winner
                            {
                                $builderData[$k][0][$key]->settings->visibility_display = "0"; // set visibility to 'never'
                                $makeChanges = true;
                            }
                        }
                    }
                    if ($makeChanges) // save the changes to the DB
                    {
                        update_post_meta($post_id, $k, $builderData[$k][0]); // save changes... drafts to drafts, live as live
                        if (class_exists('FLBuilderModel')) {
                            FLBuilderModel::delete_asset_cache_for_all_posts(); // clear bb cache
                        }
                        if (class_exists('FLBuilder')) {
                            FLBuilder::render_assets(); // rebuild css js
                        }
                        // delete node?
                        // FLBuilderModel::delete_node ?????
                    }
                }
            }
        }
        wp_reset_postdata();
    }
    //add meta box autocomplete options
    //legacy retired in 1.0.14
    function add_experiment_ac_meta_box() {
        $screens = array('bt_experiments');
        foreach ($screens as $screen) {
            add_meta_box('show_autocomplete', 'Autocomplete', array($this, 'show_autocomplete'), $screen);
        }
    }
    function show_autocomplete($post){
        $autocomplete_on = get_post_meta($post->ID, 'autocomplete_on', true);
        $min_days = get_post_meta($post->ID, 'ac_min_days', true);
        $min_views = get_post_meta($post->ID, 'ac_min_views', true);
?>  
    <p>Autocomplete analyses user interactions until it has enough data to declare a test winner. After this, every visitor to your website will see the winning version.</p>
    <BR>
    <input type="checkbox" name="autocomplete_on" id="autocomplete_on" class="ab-toggle" value="1" <?php checked($autocomplete_on); ?> /><label for="autocomplete_on">Enable Autocomplete</label></input><BR><BR>
    <div class="ac_options"><label for="ac_min_days">Test will run for at least this many days.</label>
        <BR><input type="number" id="ac_min_days" name="ac_min_days" style="width:100%;"   placeholder="7" value="<?php echo intval($min_days ?: 7); ?>"  /><BR><BR>
        <label for="ac_min_views">Autocomplete requires this many visits for each variation.</label>
        <BR><input type="number" id="ac_min_views" name="ac_min_views" style="width:100%;" placeholder="50" value="<?php echo intval($min_views ?: 100); ?>"  />
    </div>
<?php
    }
}
$btac = new Ab_Tests_Autocomplete();
//fire it up
