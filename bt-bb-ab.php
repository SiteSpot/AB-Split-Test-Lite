<?php



/**

 * Plugin Name:       AB Split Test Lite

 * Plugin URI:        https://absplittest.com

 * Description:       A/B Split testing for WordPress - Test Pages, Blocks, Elementor, Bricks, Beaver Builder, Oxygen, Breakdance, WP Bakery and more. Free version limited to 1 active test with 1 variation (plus the control).

 * Version:           1.0.0

 * Requires at least: 6.9

 * Requires PHP:      7.4

 * Author:            AB Split Test

 * License:           GPLv2 or later

 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt

 * Text Domain:       ab-split-test-lite

 * Domain Path:       /languages

 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Hand off to the full version of AB Split Test.
 *
 * Lite and the full plugin are the same engine: they share constants, top-level
 * functions and class names, so PHP cannot load both. Activating the full plugin
 * while Lite was active used to die with "Plugin could not be activated because it
 * triggered a fatal error".
 *
 * WHY THIS FILE IS ONLY A LOADER: PHP registers top-level functions when a file is
 * COMPILED, before a single line of it runs, so a guard at the top of the real
 * plugin file cannot help. The plugin code lives in bt-bb-ab-core.php so that, when
 * the full plugin is active or is being activated, Lite is never compiled at all.
 *
 * Nothing is deleted here. Tests, settings and results stay in the database for the
 * full plugin to pick up; data removal only ever happens in uninstall.php.
 */

/**
 * Plugin files (relative to the plugins folder) that are the full AB Split Test.
 * The full plugin's main file is also called bt-bb-ab.php, in whatever folder it was
 * installed to; its "bt-bb-ab" text domain tells it apart from a copy of Lite.
 *
 * @param string[] $abst_lite_plugins Plugin basenames to check.
 * @return bool
 */
function abst_lite_has_full_plugin( $abst_lite_plugins ) {
    $abst_lite_self = plugin_basename( __FILE__ );
    foreach ( (array) $abst_lite_plugins as $abst_lite_plugin ) {
        if ( ! is_string( $abst_lite_plugin ) || $abst_lite_plugin === $abst_lite_self || 'bt-bb-ab.php' !== basename( $abst_lite_plugin ) ) {
            continue;
        }
        if ( 0 !== validate_file( $abst_lite_plugin ) ) {
            continue;
        }
        $abst_lite_file = WP_PLUGIN_DIR . '/' . $abst_lite_plugin;
        if ( ! is_readable( $abst_lite_file ) ) {
            continue;
        }
        $abst_lite_data = get_file_data( $abst_lite_file, array( 'domain' => 'Text Domain' ) );
        if ( 'bt-bb-ab' === $abst_lite_data['domain'] ) {
            return true;
        }
    }
    return false;
}

/**
 * Plugins this request is about to activate (Plugins screen or WP-CLI). They are
 * compiled later in this same request, so Lite has to stay out of the way now.
 * Read-only detection: WordPress core verifies the nonce before it activates anything.
 *
 * @return string[]
 */
function abst_lite_plugins_being_activated() {
    $abst_lite_plugins = array();
    // phpcs:disable WordPress.Security.NonceVerification.Recommended, WordPress.Security.NonceVerification.Missing -- Detection only; core checks the nonce before activating.
    if ( is_admin() ) {
        $abst_lite_action = '';
        foreach ( array( 'action', 'action2' ) as $abst_lite_key ) {
            if ( isset( $_REQUEST[ $abst_lite_key ] ) && '-1' !== $_REQUEST[ $abst_lite_key ] ) {
                $abst_lite_action = sanitize_key( wp_unslash( $_REQUEST[ $abst_lite_key ] ) );
                break;
            }
        }
        if ( 'activate' === $abst_lite_action && isset( $_REQUEST['plugin'] ) ) {
            $abst_lite_plugins[] = sanitize_text_field( wp_unslash( $_REQUEST['plugin'] ) );
        } elseif ( 'activate-selected' === $abst_lite_action && isset( $_POST['checked'] ) && is_array( $_POST['checked'] ) ) {
            $abst_lite_plugins = array_map( 'sanitize_text_field', wp_unslash( $_POST['checked'] ) );
        }
    }
    // phpcs:enable
    if ( defined( 'WP_CLI' ) && WP_CLI && ! empty( $GLOBALS['argv'] ) && is_array( $GLOBALS['argv'] ) ) {
        $abst_lite_argv = array_map( 'strval', $GLOBALS['argv'] );
        if ( in_array( 'plugin', $abst_lite_argv, true ) && in_array( 'activate', $abst_lite_argv, true ) ) {
            foreach ( $abst_lite_argv as $abst_lite_arg ) {
                // WP-CLI takes the folder slug; --all could include the full plugin too.
                if ( '--all' === $abst_lite_arg ) {
                    foreach ( (array) glob( WP_PLUGIN_DIR . '/*/bt-bb-ab.php' ) as $abst_lite_found ) {
                        $abst_lite_plugins[] = plugin_basename( $abst_lite_found );
                    }
                } elseif ( '' !== $abst_lite_arg && '-' !== $abst_lite_arg[0] ) {
                    $abst_lite_arg       = sanitize_text_field( $abst_lite_arg );
                    $abst_lite_plugins[] = '.php' === substr( $abst_lite_arg, -4 ) ? $abst_lite_arg : $abst_lite_arg . '/bt-bb-ab.php';
                }
            }
        }
    }
    return $abst_lite_plugins;
}

// Stay out of the way when the full plugin is already running (it loaded first), is
// active on this site or network, or is being activated in this request. Lite does
// nothing else here: the full plugin switches Lite off when it is activated, and if
// the full plugin is later removed Lite simply starts working again.
$abst_lite_active = (array) get_option( 'active_plugins', array() );
if ( is_multisite() ) {
    $abst_lite_active = array_merge( $abst_lite_active, array_keys( (array) get_site_option( 'active_sitewide_plugins', array() ) ) );
}
$abst_lite_reason = '';
if ( defined( 'BT_AB_TEST_ITEM_ID' ) ) {
    $abst_lite_reason = 'the full plugin is already loaded';
} elseif ( abst_lite_has_full_plugin( $abst_lite_active ) ) {
    $abst_lite_reason = 'the full plugin is active';
} elseif ( abst_lite_has_full_plugin( abst_lite_plugins_being_activated() ) ) {
    $abst_lite_reason = 'the full plugin is being activated in this request';
}
if ( '' !== $abst_lite_reason ) {
    // Trace it in the AB Split Test log. Lite's own logger is in the core, which is
    // not loaded, so this uses the full plugin's once everything has loaded - and
    // only once a day for the steady state, so the log is not flooded.
    add_action(
        'plugins_loaded',
        function () use ( $abst_lite_reason ) {
            if ( ! function_exists( 'abst_log' ) ) {
                return;
            }
            $abst_lite_steady = false === strpos( $abst_lite_reason, 'being activated' );
            if ( $abst_lite_steady ) {
                if ( get_transient( 'abst_lite_stepped_aside_logged' ) ) {
                    return;
                }
                set_transient( 'abst_lite_stepped_aside_logged', 1, DAY_IN_SECONDS );
            }
            abst_log( 'Lite handoff: AB Split Test Lite did not load because ' . $abst_lite_reason . '.' );
        }
    );
    return;
}

define( 'ABST_LITE_MAIN_FILE', __FILE__ );

require_once __DIR__ . '/bt-bb-ab-core.php';
