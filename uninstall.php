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

	// Experiment posts (their post meta rows go with them).
	$abst_experiment_ids = get_posts(
		array(
			'post_type'      => 'bt_experiments',
			'post_status'    => 'any',
			'posts_per_page' => -1,
			'fields'         => 'ids',
		)
	);
	foreach ( $abst_experiment_ids as $abst_experiment_id ) {
		wp_delete_post( $abst_experiment_id, true );
	}

	// Experiment assignments stored on regular posts/pages.
	delete_post_meta_by_key( 'bt_post_experiments' );
	delete_post_meta_by_key( 'bt_post_experiments_editor' );

	// Plugin options: the named ones plus anything under our own prefix.
	$abst_option_names = array(
		'abst_plugin_version',
		'abst_plugin_test_ideas',
		'abst_delete_data_on_uninstall',
		'bt_conversion_pages',
		'bt_bb_ab_settings',
		'ab_test_canonical',
		'ab_bricks_elements',
	);
	foreach ( $abst_option_names as $abst_option_name ) {
		delete_option( $abst_option_name );
	}

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery -- options table cleanup on uninstall.
	$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE 'abst\\_%'" );

	// Journey / heatmap / session-replay files. Both locations the plugin may use.
	$abst_upload_base = trailingslashit( wp_upload_dir()['basedir'] );
	abst_lite_uninstall_delete_dir( $abst_upload_base . 'abst' );
	if ( defined( 'WP_CONTENT_DIR' ) ) {
		abst_lite_uninstall_delete_dir( WP_CONTENT_DIR . '/abst-journeys' );
	}

	// Debug log file, which lives outside the abst directory.
	$abst_log_file = $abst_upload_base . 'abst_log.txt';
	if ( file_exists( $abst_log_file ) ) {
		wp_delete_file( $abst_log_file );
	}
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
	delete_site_option( 'abst_delete_data_on_uninstall' );
}
