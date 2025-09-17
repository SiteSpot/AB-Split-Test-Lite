<?php

/* this is a test file to exclude our scripts from caching plugins so it doesnt get defered or excluided */

// scripts to exclude: 
add_filter( 'autoptimize_filter_js_exclude', 'autooptimize', 10, 1 );
add_filter( 'autoptimize_filter_js_minify_excluded', '__return_false' );

        
function ab_exclude_js() {
    return array("bt_conversion","jquery", "abst_ajax","abst_variables" ,"bt-bb-ab","bt_conversion_scripts","bt_conversion_scripts-js","abst");
}
    

//autooptimize exclude files
function abst_override_js_exclude( $exclude ) {
    $excludes = implode( ',', ab_exclude_js() );
    return $exclude . ', ' . $excludes;
}
add_filter( 'autoptimize_filter_js_exclude', 'abst_override_js_exclude', 10, 1 );
add_filter( 'autoptimize_filter_js_minify_excluded', '__return_false' );


//flyingpress
function abst_flying_exclude_resources( $exclude_keywords ) {
    $exclude_keywords = array_merge($exclude_keywords, ["bt_conversion","jquery", "abst_ajax","abst_variables" ,"bt-bb-ab","abst"]);
    return $exclude_keywords;
}
add_action( 'flying_press_exclude_from_minify:css', 'abst_flying_exclude_resources' );
add_action( 'flying_press_exclude_from_minify:js', 'abst_flying_exclude_resources' );
add_filter('flying_press_exclude_from_defer:js', 'abst_flying_exclude_resources');
add_filter('flying_press_exclude_from_delay:js', 'abst_flying_exclude_resources');

function abst_lscwp_custom_excludes( $excludes ) {
    return array_merge( $excludes, ab_exclude_js() );
}

add_filter( 'litespeed_optimize_js_excludes', 'abst_lscwp_custom_excludes' );
add_filter( 'litespeed_optm_js_defer_exc', 'abst_lscwp_custom_excludes' );
add_filter( 'litespeed_optm_gm_js_exc', 'abst_lscwp_custom_excludes' );


//perfmatters
function abst_perfmatters_override_js_exclude( $exclusions ) {
    return array_merge( $exclusions, ab_exclude_js() );
}
add_filter( 'perfmatters_delay_js_exclusions', 'abst_perfmatters_override_js_exclude' );


function bt_ab_add_cfasync_to_script($tag, $handle, $src) {
    // List of script handles that should have data-cfasync="false"
    $scripts_to_exclude = array(
        'ab_',
        'bt_conversion',
        'abst',
        'magnificPopup',
    );

    // Use strpos to allow partial handle matches (e.g., 'bt_conversion' matches 'bt_conversion_data-js-before')
    foreach ($scripts_to_exclude as $needle) {
        if (strpos($handle, $needle) !== false) {
            // Add both Cloudflare and LiteSpeed cache prevention attributes
            return str_replace('<script', '<script data-cfasync="false" data-no-optimize="1"', $tag);
        }
    }
    
    return $tag;
}
add_filter('script_loader_tag', 'bt_ab_add_cfasync_to_script', 10, 3);



function abst_rapidload_exclude_files( $excluded_files = array() ) {
    // Merge custom exclusions from ab_exclude_js() with existing exclusions
    return array_merge( $excluded_files, ab_exclude_js() );
}
add_filter( 'rapidload/defer/exclusions/js', 'abst_rapidload_exclude_files', 10, 1 );
add_filter( 'rapidload/defer/exclusions/inline_js', 'abst_rapidload_exclude_files', 10, 1 );


function abst_sgo_js_exclude( $exclude_list ) {
    // Merge custom exclusions from ab_exclude_js() with the existing list
    return array_merge( $exclude_list, ab_exclude_js() );
}
add_filter( 'sgo_js_minify_exclude', 'abst_sgo_js_exclude' );
add_filter( 'sgo_javascript_combine_exclude', 'abst_sgo_js_exclude' );
add_filter( 'sgo_js_async_exclude', 'abst_sgo_js_exclude' );

//wp rocket
function abst_rocket_exclude_files( $excluded_files = array() ) {
    // Merge custom exclusions from ab_exclude_js() with the existing exclusions
    return array_merge( $excluded_files, ab_exclude_js() );
}
add_filter( 'rocket_delay_js_exclusions', 'abst_rocket_exclude_files', 10, 1 );
add_filter( 'rocket_exclude_defer_js', 'abst_rocket_exclude_files', 10, 1 );
add_filter( 'rocket_exclude_async_css', 'abst_rocket_exclude_files', 10, 1 );
add_filter( 'rocket_exclude_js', 'abst_rocket_exclude_files', 10, 1 );

