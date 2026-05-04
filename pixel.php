<?php

// External conversion pixel
// As of v1.7.0
// Checks for matching test cookie data and sends convert request to wp

$path = dirname(__FILE__);
while ( !file_exists($path . '/wp-load.php') ) {
    $path = dirname($path);
    if ( $path == '/' || is_dir($path) === false ) {
        // Exit if we reach the root or can't find wp-load.php
    }
}
require_once($path . '/wp-load.php');

$eid = isset($_GET['eid']) ? sanitize_text_field($_GET['eid']) : null;
$orderValue = isset($_GET['value']) ? sanitize_text_field($_GET['value']) : null;

if (!function_exists('abst_get_root_domain')) {
    function abst_get_root_domain($host) {
        $host = preg_replace('/:\d+$/', '', $host); // strip port
        $host = preg_replace('/^www\./', '', $host);
        $parts = explode('.', $host);

        $multi_tlds = [
            ['com','au'], ['net','au'], ['edu','au'], ['gov','au'],
            ['co','uk'], ['org','uk'], ['ac','uk'], ['gov','uk'],
            ['co','nz'], ['org','nz'], ['net','nz'],
            ['co','za'], ['org','za'],
            ['co','jp'], ['ne','jp'], ['or','jp'],
            ['com','cn'], ['net','cn'], ['org','cn'],
            ['com','tw'], ['org','tw'], ['net','tw'],
            ['com','hk'], ['org','hk'],
            ['com','sg'], ['org','sg'],
            ['co','in'], ['org','in'],
            ['co','kr'], ['or','kr'],
            ['com','br'], ['org','br'], ['net','br'],
            ['com','mx'], ['org','mx'], ['net','mx'],
            ['co','il'], ['org','il'],
            ['co','th'], ['or','th'],
            ['com','my'], ['org','my'],
            ['com','ph'], ['org','ph'],
            ['co','id'], ['org','id'],
            ['co','ca'], ['org','ca'], ['net','ca'], ['gov','ca'],
            ['co','ae'], ['org','ae'], ['net','ae'], ['gov','ae'],
        ];

        if (count($parts) >= 3) {
            $last_two = [ $parts[count($parts)-2], $parts[count($parts)-1] ];
            foreach ($multi_tlds as $tld) {
                if ($tld[0] === $last_two[0] && $tld[1] === $last_two[1]) {
                    return '.' . implode('.', array_slice($parts, -3));
                }
            }
        }

        return '.' . implode('.', array_slice($parts, -2));
    }
}

function abst_ends_with($haystack, $needle) {
    $len = strlen($needle);
    if ($len === 0) return true;
    return substr($haystack, -$len) === $needle;
}


function convert_experiment($eid, $orderValue = 1) {

    if ($eid !== null && $eid !== 'all' && !is_numeric($eid)) {
        return;
    }
    
    if (isset($_COOKIE['btab_' . $eid])) {
        $cookie = json_decode(stripslashes($_COOKIE['btab_' . $eid]), true);
        if ($cookie['conversion'] == 0) { 
            $cookie['conversion'] = 1;
            $variation = $cookie['variation'] ?? '';
            // Determine cookie domain (use root domain on production)
            $host = $_SERVER['HTTP_HOST'];
            $is_localhost = in_array($host, ['localhost', '127.0.0.1']) ||
                strpos($host, 'localhost:') === 0 ||
                abst_ends_with($host, '.local') ||
                abst_ends_with($host, '.test');

            $domain = $is_localhost ? '' : abst_get_root_domain($host);

            setcookie(
                'btab_' . $eid,
                json_encode($cookie, JSON_UNESCAPED_SLASHES),
                array(
                    'expires' => time() + 60 * 60 * 24 * 1000,
                    'path' => '/',
                    'domain' => $domain,
                    // Adjust SameSite and Secure based on environment
                    'SameSite' => $is_localhost ? 'Lax' : 'None',
                    'Secure' => !$is_localhost,
                )
            );
            
            //get ajax url from wordpress
            // Get the WordPress admin-ajax.php URL
            // This is the URL that will process the experiment conversion
            $ajax_url = get_bloginfo('wpurl') . '/wp-admin/admin-ajax.php';

            $data = array(
                'action' => 'bt_experiment_w',
                'eid' => $eid,
                'variation' => $variation,
                'type' => 'conversion',
                'location' => 'pixel',
                'orderValue' => $orderValue,
            );

            // post data
            $ch = curl_init($ajax_url);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $response = curl_exec($ch);
            if ($response === false) {
                $error = curl_error($ch);
            }
            curl_close($ch);
            
            return true;
        }
    }
    return false;
}

if (!empty($eid)) { 
    $orderValue = 1;
    if (isset($_GET['value'])) {
        $orderValue = (float)$_GET['value'];
    }
    
    if ($eid === 'all') {
        // Find all BT experiment cookies and convert them
        $converted_count = 0;
        $eidList = [];
        foreach ($_COOKIE as $cookie_name => $cookie_value) {
            if (strpos($cookie_name, 'btab_') === 0) {
                $experiment_id = substr($cookie_name, 5); // Remove 'btab_' prefix
                $eidList[] = $experiment_id;
                if (convert_experiment($experiment_id, $orderValue)) {
                    $converted_count++;
                }
            }
        }
    } else if(is_numeric($eid)) {
        // Convert single experiment
        convert_experiment($eid, $orderValue);
    } else {
        // Invalid EID format do nada
    }
}

// SHOW GIF    
header("Content-Type: image/gif");
header("Access-Control-Allow-Origin: *");
$gif_pixel = base64_decode("R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7");
echo  $gif_pixel; // doesn't need sanitization as its a static img above

// fin

