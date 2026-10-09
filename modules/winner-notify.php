<?php
/**
 * Winner emails.
 *
 * Once a day every running test is checked with the same rules as its results screen.
 * The first time a test has a winner, the site admin gets one email about it. Nothing
 * else changes: the test keeps running and visitors keep seeing every version until
 * the site owner ends it.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'admin_init', 'abst_schedule_winner_check' );
add_action( 'abst_winner_check', 'abst_run_winner_check' );

function abst_schedule_winner_check() {
	if ( ! wp_next_scheduled( 'abst_winner_check' ) ) {
		wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', 'abst_winner_check' );
	}
}

/**
 * The winner of a running test, or null while there isn't one. Same conditions as the
 * results screen's winner banner: the analyser calls a winner at the confidence target,
 * the test has run for its minimum days, and every variation has its minimum visits.
 *
 * @param WP_Post $test
 * @return array|null ['variation' => winning key, 'observations' => analysed observations]
 */
function abst_find_test_winner( $test ) {
	global $abst_btab;

	$observations = get_post_meta( $test->ID, 'observations', true );
	if ( empty( $observations ) || ! is_array( $observations ) ) {
		return null;
	}
	foreach ( $observations as $row ) {
		if ( is_array( $row ) && ! empty( $row['visit'] ) && ( $row['visit'] < 1 || ( $row['conversion'] ?? 0 ) < 0 ) ) {
			return null;
		}
	}

	$test_age = intval( ( time() - get_post_time( 'U', true, $test ) ) / DAY_IN_SECONDS );
	$ac_data  = $abst_btab->get_ac_data( $test->ID );
	if ( $test_age < $ac_data['min_days'] ) {
		return null;
	}

	require_once dirname( __DIR__ ) . '/includes/statistics.php';
	$observations = abst_analyze_observations( $observations, $test_age, $ac_data['min_views'] );
	$stats        = $observations['bt_bb_ab_stats'] ?? array();
	if ( empty( $stats['winner'] ) || empty( $stats['best'] ) ) {
		return null;
	}
	if ( (float) ( $stats['probability'] ?? 0 ) < apply_filters( 'abst_complete_confidence', 95 ) ) {
		return null;
	}
	foreach ( $observations as $key => $row ) {
		if ( 'bt_bb_ab_stats' === $key || 'likelyDuration' === $key ) {
			continue;
		}
		if ( ! is_array( $row ) || ( $row['visit'] ?? 0 ) < $ac_data['min_views'] ) {
			return null;
		}
	}

	return array(
		'variation'    => (string) $stats['best'],
		'observations' => $observations,
	);
}

/**
 * Daily check: email the admin about each running test that has a new winner. Only
 * the "already emailed about this winner" marker is stored; the test is not changed.
 */
function abst_run_winner_check() {
	$tests = get_posts(
		array(
			'post_type'   => 'abst_experiments',
			'post_status' => 'publish',
			'numberposts' => -1,
		)
	);

	foreach ( $tests as $test ) {
		if ( abst_lite_is_sample_test( $test->ID ) ) {
			continue;
		}
		$winner = abst_find_test_winner( $test );
		if ( ! $winner || get_post_meta( $test->ID, 'abst_winner_found_notified', true ) === $winner['variation'] ) {
			continue;
		}
		require_once dirname( __DIR__ ) . '/includes/email-winner-found.php';
		// Mark it only once the email is sent, so a mail failure is retried tomorrow.
		if ( abst_send_winner_found_email( $test, $winner['observations'] ) ) {
			update_post_meta( $test->ID, 'abst_winner_found_notified', $winner['variation'] );
		}
	}
}
