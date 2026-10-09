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

 * - Generate heatmaps from visitor interactions

 * - Keep plugin lightweight and privacy-friendly by avoiding real-time database writes

 *

 * Features:

 * ---------

 * ✅ Lightweight, file-based logging (no database bloat)

 * ✅ Easy admin toggle to enable or disable journey tracking

 * ✅ Logs include: timestamp, page URL, test variation UUID, element info (selector/text), and click coordinates

 * ✅ Stores daily logs in the uploads directory (`uploads/abst/journeys/`)

 * ✅ Filters out non-meaningful interactions with front-end logic (class or data attribute check)

 * ✅ JS collects clicks into buffer and sends via `navigator.sendBeacon()` or AJAX on blur/unload

 * ✅ Optional admin heatmap view highlighting clicked elements

 * ✅ Supports filtering by test variation

 * ✅ Future-ready: logs can be parsed into heatmaps via heatmap.js

 * ✅ Background cron job purges old logs after user-defined retention (e.g. 30 days)

 *

 * Usage Notes:

 * ------------

 * - Files are delimited (CSV-style or pipe |) for easier parsing

 * - WordPress filesystem API used for maximum compatibility across hosts

 * - Text logs can be parsed and summarized on-demand via AJAX

 * - Server-side rate limiting prevents excessive logging from same user/session

 * - Visitor identifiers are pseudonymous; collecting data requires enabled tracking and any configured consent

 * - Front-end code adds data only when journey tracking is enabled and consent permits it

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

     // Journey logs live in a dedicated folder inside the uploads directory
     // (protected by index.php + .htaccess). Normally already defined by the core file.
     define( 'ABST_JOURNEY_DIR', trailingslashit( wp_upload_dir()['basedir'] ) . 'abst/journeys' );

 }

/**
 * Write the files that stop the journey folder being listed or read over the web.
 *
 * @return void
 */
function abst_protect_journey_dir() {
    // Prevent directory listing with an empty index.html. No PHP files are written to
    // uploads: security plugins flag them. Earlier versions wrote a one-line index.php.
    foreach ( array( ABST_JOURNEY_DIR, dirname( ABST_JOURNEY_DIR ) ) as $dir ) {
        if ( ! file_exists( $dir . '/index.html' ) ) {
            abst_put_contents( $dir . '/index.html', '' );
        }
        $old_index = $dir . '/index.php';
        if ( file_exists( $old_index ) && trim( (string) @file_get_contents( $old_index ) ) === '<?php //silence is golden ?>' ) {
            wp_delete_file( $old_index );
        }
    }

    // Block direct file access (Apache/LiteSpeed). Nginx sites require a
    // server-level rule: location ~* /abst/journeys/ { deny all; }
    if (!file_exists(ABST_JOURNEY_DIR . '/.htaccess')) {
        abst_put_contents( ABST_JOURNEY_DIR . '/.htaccess', 'Deny from all' );
    }
}

/**
 * Give journey files written under an earlier naming scheme their current name:
 * unhashed (abst_journeys_YYYYMMDD.txt) or hashed with a previous key
 * (abst_journeys_<12 hex>_YYYYMMDD.txt). A day with both keeps one file: the old
 * lines are appended.
 */
function abst_hash_legacy_journey_files() {
    $current = abst_file_hash();
    foreach ( glob( ABST_JOURNEY_DIR . '/abst_journeys_*' ) ?: array() as $file ) {
        if ( ! preg_match( '/^abst_journeys_(?:([0-9a-f]{12})_)?(\d{8})\.txt(\.gz)?$/', basename( $file ), $m ) ) {
            continue;
        }
        if ( $m[1] === $current ) {
            continue;
        }
        $compressed = ! empty( $m[3] );
        $target     = abst_journey_file( $m[2], $compressed );
        if ( ! file_exists( $target ) ) {
            $content = @file_get_contents( $file );
            if ( $content !== false && abst_put_contents( $target, $content ) ) {
                wp_delete_file( $file );
            }
        } elseif ( ! $compressed ) {
            $content = @file_get_contents( $file );
            if ( $content !== false && abst_put_contents( $target, $content, true ) ) {
                wp_delete_file( $file );
            }
        }
        // A compressed day that already has a hashed file is left for retention to delete.
    }
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



        // Once per upgrade: salt-keyed journey file names and no index.php in uploads.
        if ( file_exists( ABST_JOURNEY_DIR ) && get_option( 'abst_journey_storage_version' ) !== '3' ) {
            abst_protect_journey_dir();
            abst_hash_legacy_journey_files();
            update_option( 'abst_journey_storage_version', '3', false );
            // Right after an update the server can keep running the previous version from
            // PHP's opcode cache (re-checked every minute on many hosts), and that code still
            // writes the old names. Rename once more when it has gone, not at 3 AM.
            if ( ! wp_next_scheduled( 'abst_rehash_journey_files' ) ) {
                wp_schedule_single_event( time() + 10 * MINUTE_IN_SECONDS, 'abst_rehash_journey_files' );
            }
        }
        add_action( 'abst_rehash_journey_files', 'abst_hash_legacy_journey_files' );



        //create journey dir if it doesn't exist

        if (!file_exists(ABST_JOURNEY_DIR)) {

            if(wp_mkdir_p(ABST_JOURNEY_DIR)){

                abst_log('Created journey directory');

                abst_protect_journey_dir();

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

        if ($hook !== 'abst_experiments_page_abst-heatmaps') {

            return;

        }



        wp_enqueue_script(

            'abst-heatmap-lib',

            plugins_url('../js/heatmap.src.js', __FILE__),

            array(),

            ABST_VERSION,

            true

        );



        wp_enqueue_script(

            'abst-journeys',

            ABST_PLUGIN_URI . 'js/journey.js',

            array('jquery', 'abst-heatmap-lib'),

            ABST_VERSION,

            true

        );

        wp_localize_script('abst-journeys', 'abst_journey_data', array(

            'page_selector_nonce' => wp_create_nonce('abst_page_selector')

        ));

    }



    function ajax_clear_heatmap_data() {

        if(!current_user_can('manage_options')){

            wp_send_json_error('Unauthorized');

            return;

        }

        if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'abst_clear_heatmap_data')) {

            wp_send_json_error('Security check failed');

            return;

        }

        

        abst_log('Clearing all heatmap data');

        $this->delete_journey_data(0);

        wp_send_json_success('Journey data cleared successfully');

    }   

    



    public function delete_journey_data($retention_days =  false) {

        //file format abst_journeys_<hash>_yyyymmdd.txt

        //delete all journey data older than retention_days

        abst_log('Deleting old journey data');

        $delete_all = ($retention_days === 0 || $retention_days === '0');

        if($retention_days === false || $retention_days === null || $retention_days === '')

            $retention_days = abst_get_admin_setting('abst_heatmap_retention_length');

        abst_hash_legacy_journey_files(); // older unhashed names, e.g. written by another version

        $journey_files = glob(ABST_JOURNEY_DIR . '/*.txt'); // abst_journeys_<hash>_20251009.txt

        $journey_files_gz = glob(ABST_JOURNEY_DIR . '/*.gz'); // abst_journeys_<hash>_20251009.txt.gz

        foreach ($journey_files as $journey_file) {

            $file_date = substr(basename($journey_file, '.txt'), -8);

            if ($delete_all || strtotime($file_date) < strtotime('-' . $retention_days . ' days')) {

                wp_delete_file($journey_file);

                $file_date = gmdate('Y-m-d', strtotime($file_date));

                abst_log('Deleted old journey data file for day ' . $file_date);

            }

        }

        foreach ($journey_files_gz as $journey_file) {

            $file_date = substr(basename($journey_file, '.txt.gz'), -8);

            if ($delete_all || strtotime($file_date) < strtotime('-' . $retention_days . ' days')) {

                wp_delete_file($journey_file);

                $file_date = gmdate('Y-m-d', strtotime($file_date));

                abst_log('Deleted old journey data file for day gz ' . $file_date);

            }

        }



        //if gzip functions exist, get yesterdays file and gzip it

        if (!$delete_all && function_exists('gzencode') && function_exists('gzdecode')) {

            $yesterday = gmdate('Ymd', strtotime('-1 day')); // ✅ Fixed format

            $yesterday_file = abst_journey_file($yesterday);

            $gz_file = abst_journey_file($yesterday, true);

            

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

                    $written = abst_put_contents($gz_file, $gz_data);

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

        // Guard against path traversal: the date must be an 8-digit Ymd string.
        if ( ! preg_match( '/^\d{8}$/', (string) $date ) ) {

            return [];

        }

        $file_txt = abst_journey_file($date);

        $file_gz = abst_journey_file($date, true);

        

        $journey_file = '';

        

        // Check for gzipped file first (older data)

        if (file_exists($file_gz)) {

            $journey_file = $file_gz;

        }

        // Fall back to uncompressed file (current day)

        elseif (file_exists($file_txt)) {

            $journey_file = $file_txt;

        }

        else {

            // No file found for this date

            return [];

        }

        

        // Parse into array

        // Streamed rather than read whole: the old path held the compressed bytes, the
        // decompressed string and the exploded array simultaneously.

        $lines = abst_journey_file_lines_iter($journey_file);

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

        // Abuse protection for this public endpoint: 120 requests a minute per IP unless filtered.
        $ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '0.0.0.0';
        $rate_key = 'abst_jr_' . md5($ip);
        $rate_count = (int) get_transient($rate_key);
        if ($rate_count > (int) apply_filters( 'abst_journey_requests_per_minute', 120 )) {
            wp_send_json_error('Rate limit exceeded', 429);
        }
        set_transient($rate_key, $rate_count + 1, MINUTE_IN_SECONDS);

        // Public journey collection endpoint; payload is sanitized below and rate limited by IP.
        // phpcs:disable WordPress.Security.NonceVerification.Missing
        // Bound work before JSON decoding. Normal clients flush at 30,000 JS characters.
        $payload_limit = max( 1, (int) apply_filters( 'abst_journey_payload_max_bytes', 256 * 1024 ) );
        if ( isset( $_POST['data'] ) ) {
            // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash -- Length check only; decoded and sanitized below.
            if ( ! is_string( $_POST['data'] ) || strlen( $_POST['data'] ) > 2 * $payload_limit ) {
                wp_send_json_error( 'Journey payload is too large', 413 );
            }
            // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- JSON payload decoded and sanitized below.
            $raw_payload = wp_unslash( $_POST['data'] );
        } else {
            $raw_payload = file_get_contents( 'php://input', false, null, 0, $payload_limit + 1 );
        }
        if ( is_string( $raw_payload ) && strlen( $raw_payload ) > $payload_limit ) {
            wp_send_json_error( 'Journey payload is too large', 413 );
        }
        if ( ! is_string( $raw_payload ) || $raw_payload === '' ) {
            wp_send_json_error('Invalid data');
            return;
        }
        // phpcs:enable WordPress.Security.NonceVerification.Missing




        $records = json_decode($raw_payload, true);



        if (empty($records) || !is_array($records)) {

            abst_log('Invalid data payload');

            wp_send_json_error('Invalid data');

        }



        if ( count( $records ) > max( 1, (int) apply_filters( 'abst_journey_payload_max_records', 2048 ) ) ) {
            wp_send_json_error( 'Too many journey records', 413 );
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

                if (!isset($record[$field]) || !is_scalar($record[$field]) || $record[$field] === '') {

                    abst_log('Missing field ' . $field . ' in journey record');
                    $record_valid = false;
                    break;

                }
                $sanitized[$field] = str_replace('|', '', sanitize_text_field($record[$field]));
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

                        'scroll_depth' => '',

                        'viewport_height' => ''

                    ];

                }



                $experiments = isset($record['experiments']) ? str_replace('|', '', sanitize_text_field($record['experiments'])) : '';

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



                $viewport_height = '';

                if (isset($record['viewport_height']) && $record['viewport_height'] !== '') {

                    $vh_candidate = floatval($record['viewport_height']);

                    if (is_finite($vh_candidate) && $vh_candidate > 0) {

                        $viewport_height = (string) round($vh_candidate);

                    }

                }



                $existing_scroll = $uuid_metadata_written[$uuid]['scroll_depth'];

                $has_written = $uuid_metadata_written[$uuid]['written'];

                $existing_viewport = isset($uuid_metadata_written[$uuid]['viewport_height']) ? $uuid_metadata_written[$uuid]['viewport_height'] : '';

                if ($viewport_height === '' && $existing_viewport !== '') {

                    $viewport_height = $existing_viewport;

                }



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

                        $referrer,

                        $viewport_height

                    ];



                    $journey_data_string .= implode('|', $meta_line) . PHP_EOL;

                    $uuid_metadata_written[$uuid]['written'] = true;

                    $uuid_metadata_written[$uuid]['scroll_depth'] = $scroll_depth;

                    $uuid_metadata_written[$uuid]['viewport_height'] = $viewport_height;

                } elseif ($scroll_depth !== '' && ($existing_scroll === '' || floatval($scroll_depth) > floatval($existing_scroll))) {

                    // Track the highest observed scroll depth even if we didn't rewrite the metadata line

                    $uuid_metadata_written[$uuid]['scroll_depth'] = $scroll_depth;

                }



                // Skip writing the metadata event as a regular event

                continue;

            }



            // Write regular event line (without UUID and screen_size - they're in metadata)

            $meta_value = isset($record['meta']) ? sanitize_text_field($record['meta']) : '';

            // Strip the log delimiter and line breaks so a label can never

            // corrupt the pipe-delimited journey log format.

            $meta_value = str_replace(['|', "\r", "\n"], '', $meta_value);

            // Sanitize click coordinates: must be numeric floats in 0-1 range, strip pipes/newlines

            $click_x = isset($record['click_x']) && is_numeric($record['click_x']) ? max(0, min(1, round((float) $record['click_x'], 4))) : '';

            $click_y = isset($record['click_y']) && is_numeric($record['click_y']) ? max(0, min(1, round((float) $record['click_y'], 4))) : '';

            // Use custom sanitizer for CSS selectors to preserve > combinators and [] attribute syntax

            // sanitize_text_field() encodes > to &gt; which breaks descendant selectors

            $element_id_or_selector = isset($record['element_id_or_selector']) ? self::sanitize_css_selector($record['element_id_or_selector']) : '';

            $url = isset($record['url']) ? str_replace('|', '', sanitize_text_field($record['url'])) : '';



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

        //file format abst_journeys_<hash>_yyyymmdd.txt

        $journey_file = abst_journey_file(gmdate('Ymd'));



        // Disk protection against runaway or spam traffic: stop writing today's file once it
        // reaches the daily size (5 MB unless filtered).
        $journey_daily_bytes = (int) apply_filters( 'abst_journey_daily_max_bytes', 5 * 1024 * 1024 );
        if (file_exists($journey_file) && filesize($journey_file) > $journey_daily_bytes) {
            abst_log('Journey log file for today has reached its size limit, not adding more');
            wp_send_json_error('Journey log file for today has reached its size limit');
        }

        $written = false;

        $maxAttempts = 10; // 200ms total retry window (20 * 10ms)

        for ($attempt = 0; $attempt < $maxAttempts; $attempt++) {

            $written = abst_put_contents($journey_file, $journey_data_string, true);

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
