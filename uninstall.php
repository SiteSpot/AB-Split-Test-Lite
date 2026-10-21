<?php
/**
 * Uninstall handler. Runs only when the plugin is deleted from the Plugins
 * screen (not on deactivation).
 *
 * Scheduled events are always removed — they are useless without the plugin.
 *
 * Everything else (tests, results, heatmap/journey data, settings) is treated as
 * the user's content and is KEPT unless they explicitly opted in via the
 * "abst_delete_data_on_uninstall" option. Default is to keep all data, so
 * deleting and reinstalling the plugin never loses a running test.
 *
 * If AB Split Test Pro (a separate plugin) is installed, nothing at all is removed:
 * it uses the same data and cron events.
 *
 * @package AB_Split_Test_Lite
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

/**
 * Run a callback once per site (network-aware).
 *
 * @param callable $callback Callback to run in each site's context.
 * @return void
 */
function abst_lite_uninstall_for_each_site( $callback ) {
	if ( is_multisite() && function_exists( 'get_sites' ) ) {
		$abst_site_ids = get_sites(
			array(
				'fields' => 'ids',
				'number' => 0,
			)
		);

		foreach ( $abst_site_ids as $abst_site_id ) {
			switch_to_blog( $abst_site_id );
			call_user_func( $callback );
			restore_current_blog();
		}

		return;
	}

	call_user_func( $callback );
}

/**
 * Remove the plugin's scheduled cron events.
 *
 * @return void
 */
function abst_lite_uninstall_clear_scheduled_hooks() {
	wp_clear_scheduled_hook( 'abst_trim_log' );
	wp_clear_scheduled_hook( 'abst_delete_journey_data' );
	wp_clear_scheduled_hook( 'abst_plugin_version_check' );
	wp_clear_scheduled_hook( 'abst_refresh_conversion_pages_deferred' );
	wp_clear_scheduled_hook( 'abst_winner_check' );
}

/**
 * Recursively delete a directory tree.
 *
 * @param string $dir Absolute path to remove.
 * @return void
 */
function abst_lite_uninstall_delete_dir( $dir ) {
	global $wp_filesystem;

	if ( ! is_dir( $dir ) ) {
		return;
	}

	if ( ! $wp_filesystem ) {
		require_once ABSPATH . 'wp-admin/includes/file.php';
		WP_Filesystem();
	}

	if ( $wp_filesystem ) {
		$wp_filesystem->delete( $dir, true );
	}
}

/**
 * Delete this site's plugin data. Only called when the user opted in.
 *
 * @return void
 */
function abst_lite_uninstall_cleanup_current_site() {
	global $wpdb;

	// Experiment posts in every status, including the Trash and the plugin's own
	// statuses, which are not registered while uninstalling (their post meta rows
	// go with them).
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery -- one-time lookup on uninstall.
	$abst_experiment_ids = $wpdb->get_col( $wpdb->prepare( "SELECT ID FROM {$wpdb->posts} WHERE post_type = %s", 'abst_experiments' ) );
	foreach ( $abst_experiment_ids as $abst_experiment_id ) {
		wp_delete_post( $abst_experiment_id, true );
	}

	// Plugin options: the named ones plus anything under our own prefix.
	$abst_option_names = array(
		'abst_plugin_version',
		'abst_delete_data_on_uninstall',
		'abst_conversion_pages',
		'abst_test_canonical',
		'abst_bricks_elements',
		'ab_change_canonicals', // settings saved without the abst_ prefix
		'ab_dont_clear_cache_on_update',
		'selected_post_types',
	);
	foreach ( $abst_option_names as $abst_option_name ) {
		delete_option( $abst_option_name );
	}

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery -- options table cleanup on uninstall.
	$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE 'abst\\_%'" );

	// Journey / heatmap files and the debug log: uploads/abst (abst/journeys, abst/logs).
	$abst_upload_base = trailingslashit( wp_upload_dir()['basedir'] );
	abst_lite_uninstall_delete_dir( $abst_upload_base . 'abst' );
}

/**
 * Is AB Split Test Pro installed (active or not)?
 *
 * Its main file is also bt-bb-ab.php, in whatever folder it was installed to; the
 * "bt-bb-ab" text domain tells it apart from Lite.
 *
 * @return bool
 */
function abst_lite_uninstall_pro_installed() {
	if ( ! function_exists( 'get_plugins' ) ) {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
	}
	foreach ( get_plugins() as $abst_plugin_file => $abst_plugin_data ) {
		if ( 'bt-bb-ab.php' === basename( $abst_plugin_file ) && isset( $abst_plugin_data['TextDomain'] ) && 'bt-bb-ab' === $abst_plugin_data['TextDomain'] ) {
			return true;
		}
	}
	return false;
}

// Pro uses the same tests, results, settings, uploads and cron events. If it is
// installed, remove nothing, whatever the opt-in says.
if ( abst_lite_uninstall_pro_installed() ) {
	// abst_log() only exists here when Pro is active.
	if ( function_exists( 'abst_log' ) ) {
		abst_log( 'Lite handoff: AB Split Test Lite deleted; AB Split Test Pro is installed, so no data or cron events were removed.' );
	}
	return;
}

// Cron events go unconditionally — they cannot run without the plugin.
abst_lite_uninstall_for_each_site( 'abst_lite_uninstall_clear_scheduled_hooks' );

// Opt-in check. Respects both single-site and network-activated storage.
$abst_delete_data = get_option( 'abst_delete_data_on_uninstall' );
if ( 1 != $abst_delete_data && is_multisite() ) {
	$abst_delete_data = get_site_option( 'abst_delete_data_on_uninstall' );
}
if ( 1 != $abst_delete_data ) {
	return; // Not opted in: keep all tests, results, heatmap data and settings.
}

abst_lite_uninstall_for_each_site( 'abst_lite_uninstall_cleanup_current_site' );

if ( is_multisite() ) {
	// Settings saved from the network admin when the plugin is network-activated.
	foreach ( array( 'abst_delete_data_on_uninstall', 'selected_post_types', 'ab_change_canonicals', 'abst_enable_user_journeys', 'abst_heatmap_pages', 'ab_dont_clear_cache_on_update', 'abst_wait_for_approval', 'abst_heatmap_retention_length' ) as $abst_site_option_name ) {
		delete_site_option( $abst_site_option_name );
	}
}
