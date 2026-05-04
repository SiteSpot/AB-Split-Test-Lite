<?php

/* exclude our scripts from caching plugins so it doesnt get defered or excluided */
// scripts to exclude: 
    
function ab_exclude_js() {
    $excludes = array(
        "bt_conversion",
        "abst_ajax",
        "abst_variables",
        "bt-bb-ab",
        "bt_conversion_scripts",
        "bt_conversion_scripts-js",
        "abst",
        "ABST_CONFIG",      // wp_localize_script variable name - LiteSpeed matches this in inline scripts
        "btab_vars",        // Another localized variable
        "bt_experiments",   // Experiment config variable
    );

    return apply_filters( 'abst_exclude_js', $excludes );
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
    $exclude_keywords = array_merge($exclude_keywords, ab_exclude_js());
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
    $scripts_to_exclude = ab_exclude_js();

    // Use strpos to allow partial handle matches (e.g., 'bt_conversion' matches 'bt_conversion_data-js-before')
    foreach ($scripts_to_exclude as $needle) {
        if (strpos($handle, $needle) !== false) {
            return str_replace('<script', '<script '.ABST_CACHE_EXCLUDES.'', $tag);
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


// Page Optimize
function abst_page_optimize_exclude( $do_concat, $handle ) {
    if ( ! $do_concat ) {
        return $do_concat;
    }
    
    global $wp_scripts;
    if ( isset( $wp_scripts->registered[ $handle ] ) ) {
        $src = $wp_scripts->registered[ $handle ]->src;
        // Check for plugin folder name or text domain in the script source
        if ( strpos( $src, 'bt-bb-ab' ) !== false || strpos( $src, 'ABSPLITTEST' ) !== false ) {
            return false;
        }
    }
    
    return $do_concat;
}
add_filter( 'js_do_concat', 'abst_page_optimize_exclude', 10, 2 );

function abst_nitropack_inline_script_attributes( $attr, $js ) {
    if ( ! is_array( $attr ) ) {
        return $attr;
    }

    if ( empty( $attr['id'] ) || ! is_string( $attr['id'] ) ) {
        return $attr;
    }

    $id = $attr['id'];

    foreach ( ab_exclude_js() as $needle ) {
        if ( $needle !== '' && strpos( $id, $needle ) !== false ) {
            $attr['nitro-exclude']       = true;
            $attr['data-cfasync']        = 'false';
            $attr['data-no-optimize']    = '1';
            $attr['data-no-defer']       = '1';
            $attr['data-no-minify']      = '1';
            $attr['nowprocket']          = true;
            break;
        }
    }

    return $attr;
}
add_filter( 'wp_inline_script_attributes', 'abst_nitropack_inline_script_attributes', 10, 2 );
