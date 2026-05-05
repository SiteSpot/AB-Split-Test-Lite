<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**

 * Journey Tracking and Click Logging Module

 * -----------------------------------------

 * This module powers the optional journey logging and heatmap tracking features

 * within the AB Split Test plugin. When enabled via plugin settings, this script

 * captures and logs meaningful user interactions (such as clicks on key elements)

 * to daily text log files stored in the WordPress uploads directory.

 *

 * Purpose:

 * - Track session-based user journeys through anonymous UUIDs

 * - Log interactions such as button clicks, accordion opens, and form engagements

 * - Enable click journey playback and eventual heatmap generation

 * - Keep plugin lightweight and privacy-friendly by avoiding real-time database writes

 *

 * Features:

 * ---------

 * ✅ Lightweight, file-based logging (no database bloat)

 * ✅ Easy admin toggle to enable or disable journey tracking

 * ✅ Logs include: timestamp, page URL, test variation UUID, element info (selector/text), and click coordinates

 * ✅ Stores daily logs in `/wp-content/uploads/ab-split-test/journeys/`

 * ✅ Filters out non-meaningful interactions with front-end logic (class or data attribute check)

 * ✅ JS collects clicks into buffer and sends via `navigator.sendBeacon()` or AJAX on blur/unload

 * ✅ Optional admin view with iframe + journey playback highlighting clicked elements

 * ✅ Supports filtering by test variation for agency analysis

 * ✅ Future-ready: logs can be parsed into heatmaps via heatmap.js

 * ✅ Background cron job purges old logs after user-defined retention (e.g. 30 days)

 *

 * Usage Notes:

 * ------------

 * - Files are delimited (CSV-style or pipe |) for easier parsing

 * - WordPress filesystem API used for maximum compatibility across hosts

 * - Text logs can be parsed and summarized on-demand via AJAX

 * - Server-side rate limiting prevents excessive logging from same user/session

 * - All data is anonymous and only tracked when a test UUID is active

 * - Front-end code adds data only if the page is part of a running split test and tracking is enabled

 *

 * Typical Log Entry Format (Inline Metadata):

 * --------------------------------------------

 * Metadata line (once per batch): meta | uuid | experiments | screen_size | user_id

 * Event line: timestamp | type | post_id | url | element_id_or_selector | click_x | click_y | meta

 *

 * Note: The same UUID may have multiple metadata lines per day (one per batch sent).

 * Each batch is self-contained with its own metadata header followed by events.

 * JavaScript sends batches on page blur, unload, or when buffer is full.

 *

 * Example:

 * meta | uuid-abc123 | 1234:varA,5678:varB | l | 0

 * 1696238000 | pv | 5678 | ?ref=homepage | 0 | 0 | 0 | 

 * 1696238015 | c | 5678 | ?ref=homepage | .button1 | 0.5 | 0.5 | 

 * 1696238030 | c | 5678 | ?ref=homepage | .button2 | 0.3 | 0.7 | 

 * meta | uuid-abc123 | 1234:varA,5678:varB | l | 0

 * 1696242000 | c | 5678 | ?ref=homepage | .button3 | 0.2 | 0.8 | 

 * 1696242015 | pv | 1234 | ?utm_source=google | 0 | 0 | 0 | 

 *

 * Metadata line fields:

 * index 0: 'meta' (identifier)

 * index 1: uuid

 * index 2: experiments (format: "eid:variation,eid:variation")

 * index 3: screen_size (s/m/l)

 * index 4: user_id

 * index 5: max_scroll_depth (0-100, optional)

 * index 6: referrer (full URL of referring page, optional)

 *

 * Event line fields:

 * index 0: timestamp

 * index 1: type

 * index 2: post_id

 * index 3: url query string

 * index 4: element_id_or_selector

 * index 5: click_x

 * index 6: click_y

 * index 7: meta



 */



 if ( ! defined( 'ABST_JOURNEY_DIR' ) ) {

     // Store journey logs in wp-content (protected by .htaccess)

     // This is the WordPress standard approach used by WooCommerce, Wordfence, etc.

     define( 'ABST_JOURNEY_DIR', WP_CONTENT_DIR . '/abst-journeys' );

 }

 

class ABST_Journeys {



    public function __construct() {

        //admin assets        

        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));



        //ajax endpoint to receive journey data

        add_action('wp_ajax_abst_receive_journey_data', array($this, 'receive_journey_data'));

        add_action('wp_ajax_nopriv_abst_receive_journey_data', array($this, 'receive_journey_data'));



        //ajax endpoint to clear heatmap data

        add_action('wp_ajax_abst_remove_heatmap_data', array($this, 'ajax_clear_heatmap_data'));



        //create journey dir if it doesn't exist

        if (!file_exists(ABST_JOURNEY_DIR)) {

            if(wp_mkdir_p(ABST_JOURNEY_DIR)){

                abst_log('Created journey directory');

                // Prevent directory listing
                file_put_contents(ABST_JOURNEY_DIR . '/index.php', '<?php //silence is golden ?>');

                // Block direct file access (Apache/LiteSpeed). Nginx sites require a
                // server-level rule: location ~* /abst-journeys/ { deny all; }
                file_put_contents(ABST_JOURNEY_DIR . '/.htaccess', 'Deny from all');

            }

            else{

                abst_log('Failed to create journey directory');

            }

        }

        

        //schedule cron job to delete old journey data (runs at 3 AM daily)

        if (!wp_next_scheduled('abst_delete_journey_data')) {

            wp_schedule_event(strtotime('tomorrow 3:00 AM'), 'daily', 'abst_delete_journey_data');

        }

        

        //hook the deletion function

        add_action('abst_delete_journey_data', array($this, 'delete_journey_data'));

        

    }



    /**

     * Sanitize a CSS selector string for safe storage in pipe-delimited log files.

     * Unlike sanitize_text_field(), this preserves CSS-valid characters: > [] = " ' . # : 

     * Strips: pipes (|), newlines, null bytes, and other log-corrupting characters.

     */

    public static function sanitize_css_selector($selector) {

        if (!is_string($selector) || $selector === '') {

            return '';

        }

        // Strip null bytes

        $selector = str_replace("\0", '', $selector);

        // Strip pipes (would corrupt our pipe-delimited format)

        $selector = str_replace('|', '', $selector);

        // Strip newlines and carriage returns

        $selector = preg_replace('/[\r\n]+/', '', $selector);

        // Strip HTML tags

        $selector = wp_strip_all_tags($selector);

        // Limit length to prevent abuse (selectors shouldn't be longer than ~500 chars)

        if (strlen($selector) > 500) {

            $selector = substr($selector, 0, 500);

        }

        return trim($selector);

    }



    public function enqueue_admin_assets($hook) {

        if ($hook !== 'bt_experiments_page_abst-heatmaps') {

            return;

        }



        wp_enqueue_script(

            'abst-heatmap-lib',

            plugins_url('../js/heatmap.min.js', __FILE__),

            array(),

            BT_AB_TEST_VERSION,

            true

        );



        wp_enqueue_script(

            'abst-journeys',

            BT_AB_TEST_PLUGIN_URI . 'js/journey.js',

            array('jquery', 'abst-heatmap-lib'),

            BT_AB_TEST_VERSION,

            true

        );

    }



    function ajax_clear_heatmap_data() {

        if(!current_user_can('manage_options')){

            wp_send_json_error('Unauthorized');

            return;

        }

        

        abst_log('Clearing all heatmap data');

        $this->delete_journey_data(0);

        wp_send_json_success('Journey data cleared successfully');

    }   

    



    public function delete_journey_data($retention_days =  false) {

        //file format abst_journeys_yyyymmdd.txt

        //delete all journey data older than retention_days

        abst_log('Deleting old journey data');

        $delete_all = ($retention_days === 0 || $retention_days === '0');

        if($retention_days === false || $retention_days === null || $retention_days === '')

            $retention_days = abst_get_admin_setting('abst_heatmap_retention_length') ?? 30;

        $journey_files = glob(ABST_JOURNEY_DIR . '/*.txt'); // get all txt files in format abst_journeys_20251009.txt

        $journey_files_gz = glob(ABST_JOURNEY_DIR . '/*.gz'); // get all gz files in formaT abst_journeys_20251009.txt.gz

        foreach ($journey_files as $journey_file) {

            $file_date = str_replace('abst_journeys_', '', basename($journey_file));

            $file_date = str_replace('.txt', '', $file_date);

            if ($delete_all || strtotime($file_date) < strtotime('-' . $retention_days . ' days')) {

                wp_delete_file($journey_file);

                $file_date = gmdate('Y-m-d', strtotime($file_date));

                abst_log('Deleted old journey data file for day ' . $file_date);

            }

        }

        foreach ($journey_files_gz as $journey_file) {

            $file_date = str_replace('abst_journeys_', '', basename($journey_file));

            $file_date = str_replace('.txt.gz', '', $file_date);

            if ($delete_all || strtotime($file_date) < strtotime('-' . $retention_days . ' days')) {

                wp_delete_file($journey_file);

                $file_date = gmdate('Y-m-d', strtotime($file_date));

                abst_log('Deleted old journey data file for day gz ' . $file_date);

            }

        }



        //if gzip functions exist, get yesterdays file and gzip it

        if (!$delete_all && function_exists('gzencode') && function_exists('gzdecode')) {

            $yesterday = gmdate('Ymd', strtotime('-1 day')); // ✅ Fixed format

            $yesterday_file = trailingslashit(ABST_JOURNEY_DIR) . 'abst_journeys_' . $yesterday . '.txt'; // ✅ Fixed path

            $gz_file = trailingslashit( ABST_JOURNEY_DIR) . 'abst_journeys_' . $yesterday . '.txt.gz'; // ✅ .txt.gz extension

            

            // Check if uncompressed file exists and compressed doesn't

            if (file_exists($yesterday_file) && !file_exists($gz_file)) {

                $file_size = @filesize($yesterday_file);

                

                // Only compress if file exists, has content, and is under 10MB

                if ($file_size !== false && $file_size > 0 && $file_size < 10485760) {

                    // Read original file

                    $content = @file_get_contents($yesterday_file);

                    if ($content === false) {

                        abst_log('Failed to read journey file for compression: ' . $yesterday);

                        return;

                    }

                    

                    // Compress with level 9 (maximum compression)

                    $gz_data = @gzencode($content, 9);

                    if ($gz_data === false) {

                        abst_log('Failed to compress journey file: ' . $yesterday);

                        return;

                    }

                    

                    // Write compressed file with exclusive lock

                    $written = @file_put_contents($gz_file, $gz_data, LOCK_EX);

                    if ($written === false) {

                        abst_log('Failed to write compressed journey file: ' . $yesterday);

                        return;

                    }

                    

                    // Verify compressed file is readable before deleting original

                    $verify = @gzdecode(@file_get_contents($gz_file));

                    if ($verify === false || $verify !== $content) {

                        abst_log('Compressed file verification failed, keeping original: ' . $yesterday);

                        wp_delete_file($gz_file); // Remove bad compressed file

                        return;

                    }

                    

                    // Safe to delete original

                    wp_delete_file($yesterday_file);

                    if (!file_exists($yesterday_file)) {

                        $saved_bytes = $file_size - filesize($gz_file);

                        $saved_percent = round(($saved_bytes / $file_size) * 100, 1);

                        abst_log('Compressed journey file ' . $yesterday . ' - saved ' . $saved_percent . '% (' . round($saved_bytes / 1024, 1) . ' KB)');

                    } else {

                        abst_log('Failed to delete original journey file after compression: ' . $yesterday);

                    }

                }

            }

        }



    }



    /**

     * Read journey data for a specific date

     * Handles both compressed (.txt.gz) and uncompressed (.txt) files

     * 

     * @param string $date Date in Ymd format (e.g., '20250109')

     * @return array Array of journey records

     */

    public function read_journey_file($date) {

        $file_txt = trailingslashit(ABST_JOURNEY_DIR) . 'abst_journeys_' . $date . '.txt';

        $file_gz = trailingslashit(ABST_JOURNEY_DIR) . 'abst_journeys_' . $date . '.txt.gz';

        

        $content = '';

        

        // Check for gzipped file first (older data)

        if (file_exists($file_gz)) {

            $compressed = @file_get_contents($file_gz);

            if ($compressed !== false && function_exists('gzdecode')) {

                $content = @gzdecode($compressed);

                if ($content === false) {

                    abst_log('Failed to decompress journey file: ' . $date);

                    return [];

                }

            } else {

                abst_log('Failed to read compressed journey file: ' . $date);

                return [];

            }

        }

        // Fall back to uncompressed file (current day)

        elseif (file_exists($file_txt)) {

            $content = @file_get_contents($file_txt);

            if ($content === false) {

                abst_log('Failed to read journey file: ' . $date);

                return [];

            }

        }

        else {

            // No file found for this date

            return [];

        }

        

        // Parse into array

        $lines = explode(PHP_EOL, trim($content));

        $data = [];

        $current_metadata = null;

        

        foreach ($lines as $line) {

            if (empty($line)) continue;

            

            $fields = explode('|', $line);

            

            // Check if this is a metadata line

            if (!empty($fields[0]) && $fields[0] === 'meta') {

                // Store metadata: meta|uuid|experiments|screen_size|user_id

                if (count($fields) >= 5) {

                    $current_metadata = [

                        'uuid' => $fields[1],

                        'experiments' => $fields[2],

                        'screen_size' => $fields[3],

                        'user_id' => $fields[4],

                        'referrer' => isset($fields[6]) ? $fields[6] : '',

                    ];

                }

                continue; // Don't add metadata lines to results

            }

            

            // Event line: timestamp|type|post_id|url|element|click_x|click_y|meta

            if (count($fields) >= 8 && $current_metadata) {

                $data[] = [

                    'timestamp' => $fields[0],

                    'type' => $fields[1],

                    'post_id' => $fields[2],

                    'uuid' => $current_metadata['uuid'],

                    'url' => $fields[3],

                    'element_id_or_selector' => $fields[4],

                    'click_x' => $fields[5],

                    'click_y' => $fields[6],

                    'screen_size' => $current_metadata['screen_size'],

                    'meta' => $fields[7],

                    'referrer' => $current_metadata['referrer'] ?? '',

                ];

            }

        }

        

        return $data;

    }



    /**

     * Check if a URL should be ignored for journey tracking

     * 

     * @param string $url The URL to check

     * @return bool True if URL should be ignored, false otherwise

     */

    private function journey_ignore_url($url) {

        if (empty($url)) {

            return false;

        }

        

        $ignore_strings = ['abst_heatmap_view', 'elementor-preview'];

        

        foreach ($ignore_strings as $ignore_string) {

            if (strpos($url, $ignore_string) !== false) {

                return true;

            }

        }

        

        return false;

    }



    public function receive_journey_data() {



        //check referrer

        //abst_log('Journey data received');

        //if (!isset($_SERVER['HTTP_REFERER']) || !preg_match('/^https?:\/\/[^/]+\//', $_SERVER['HTTP_REFERER'])) {

        //    abst_log('Invalid referrer');

        //    wp_send_json_error('Invalid referrer');

        //}

        if ( ! isset( $_POST['data'] ) ) {
            abst_log('Missing data payload');
            wp_send_json_error('Invalid data');
            return;
        }

        $raw_payload = sanitize_text_field( wp_unslash( $_POST['data'] ) );



        $licence = trim(apply_filters( 'abst_licence_key', abst_get_admin_setting( 'bt_bb_ab_licence' )) );

        $records = json_decode($raw_payload, true);



        if (empty($records) || !is_array($records)) {

            abst_log('Invalid data payload');

            wp_send_json_error('Invalid data');

        }



        $journey_data_string = '';

        $uuid_metadata_written = []; // Track which UUIDs we've written metadata for



        foreach ($records as $record_key => $record) {

            if (!is_array($record)) {

                abst_log('Invalid record structure for key ' . $record_key);

                continue;

            }



            $record_type = isset($record['type']) ? sanitize_text_field($record['type']) : '';
            $required_fields = ['type', 'timestamp', 'post_id', 'uuid'];
            if ($record_type === 'meta') {
                $required_fields[] = 'screen_size';
            }

            $sanitized = [];
            $record_valid = true;
            foreach ($required_fields as $field) {

                if (!isset($record[$field]) || $record[$field] === '' || $record[$field] === null) {

                    abst_log('Missing field ' . $field . ' in journey record');
                    $record_valid = false;
                    break;

                }
                $sanitized[$field] = sanitize_text_field($record[$field]);
            }

            if (!$record_valid) {
                continue;
            }

            // Skip records from preview/editor modes
            if (isset($record['url']) && $this->journey_ignore_url($record['url'])) {
                continue;
            }

            // Get UUID from record (only set for meta records)
            $uuid = $sanitized['uuid'] ?? '';

            

            // Check if this is a metadata event

            if ($sanitized['type'] === 'meta') {

                if (!isset($uuid_metadata_written[$uuid])) {

                    $uuid_metadata_written[$uuid] = [

                        'written'      => false,

                        'scroll_depth' => ''

                    ];

                }



                $experiments = isset($record['experiments']) ? sanitize_text_field($record['experiments']) : '';

                $user_id = isset($record['user_id']) ? intval($record['user_id']) : 0;

                $referrer = isset($record['referrer']) ? sanitize_text_field($record['referrer']) : '';

                $referrer = str_replace(['|', "\r", "\n"], '', $referrer); // Strip pipes/newlines to prevent log corruption



                $scroll_depth = '';

                if (isset($record['meta']) && $record['meta'] !== '') {

                    $scroll_candidate = floatval($record['meta']);

                    if (is_finite($scroll_candidate)) {

                        $scroll_candidate = max(0, min(100, $scroll_candidate));

                        $scroll_depth = (string)round($scroll_candidate, 2);

                    }

                }



                $existing_scroll = $uuid_metadata_written[$uuid]['scroll_depth'];

                $has_written = $uuid_metadata_written[$uuid]['written'];



                $should_write_meta = !$has_written;

                if (!$should_write_meta && $scroll_depth !== '') {

                    if ($existing_scroll === '' || floatval($scroll_depth) > floatval($existing_scroll)) {

                        $should_write_meta = true;

                    }

                }



                if ($should_write_meta) {

                    $meta_line = [

                        'meta',

                        $uuid,

                        $experiments,

                        $sanitized['screen_size'],

                        $user_id,

                        $scroll_depth,

                        $referrer

                    ];



                    $journey_data_string .= implode('|', $meta_line) . PHP_EOL;

                    $uuid_metadata_written[$uuid]['written'] = true;

                    $uuid_metadata_written[$uuid]['scroll_depth'] = $scroll_depth;

                } elseif ($scroll_depth !== '' && ($existing_scroll === '' || floatval($scroll_depth) > floatval($existing_scroll))) {

                    // Track the highest observed scroll depth even if we didn't rewrite the metadata line

                    $uuid_metadata_written[$uuid]['scroll_depth'] = $scroll_depth;

                }



                // Skip writing the metadata event as a regular event

                continue;

            }



            // Write regular event line (without UUID and screen_size - they're in metadata)

            $meta_value = isset($record['meta']) ? sanitize_text_field($record['meta']) : '';

            // Sanitize click coordinates: must be numeric floats in 0-1 range, strip pipes/newlines

            $click_x = isset($record['click_x']) && is_numeric($record['click_x']) ? max(0, min(1, round((float) $record['click_x'], 4))) : '';

            $click_y = isset($record['click_y']) && is_numeric($record['click_y']) ? max(0, min(1, round((float) $record['click_y'], 4))) : '';

            // Use custom sanitizer for CSS selectors to preserve > combinators and [] attribute syntax

            // sanitize_text_field() encodes > to &gt; which breaks descendant selectors

            $element_id_or_selector = isset($record['element_id_or_selector']) ? self::sanitize_css_selector($record['element_id_or_selector']) : '';

            $url = isset($record['url']) ? sanitize_text_field($record['url']) : '';



            $line_fields = [

                $sanitized['timestamp'],

                $sanitized['type'],

                $sanitized['post_id'],

                $url,  

                $element_id_or_selector,

                $click_x,

                $click_y,

                $meta_value

            ];



            $journey_data_string .= implode('|', $line_fields) . PHP_EOL;

        }



        if ($journey_data_string === '') {

            abst_log('No valid journey records after sanitization');

            wp_send_json_error('Invalid data');

        }



        //append to file , create folder file if it doesn't exist

        //file format abst_journeys_yyyymmdd.txt

        $journey_file = ABST_JOURNEY_DIR . '/abst_journeys_' . gmdate('Ymd') . '.txt';



        // if the file is over 5mb, stop writing to it. safety net for spam todo manage another way

        if (file_exists($journey_file) && filesize($journey_file) > 5 * 1024 * 1024) {

            abst_log('Journey log file for today is 5MB, not adding more');

            wp_send_json_error('Journey log file for today is 5MB');

        }

        $written = false;

        $maxAttempts = 10; // 200ms total retry window (20 * 10ms)

        for ($attempt = 0; $attempt < $maxAttempts; $attempt++) {

            $written = file_put_contents($journey_file, $journey_data_string, FILE_APPEND | LOCK_EX);

            if ($written !== false) {

                break;

            }

            usleep(10000); // wait 10ms before retry

        }





        if ($written === false) {

            wp_send_json_error('Unable to write journey log');

        }

        wp_send_json_success('Journey data received');

    }

}



new ABST_Journeys();



