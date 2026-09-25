<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/*
 * "How likely is each variation to be the best?", answered by Monte Carlo: draw
 * a plausible true conversion rate for every variation from its data, see who
 * wins, repeat.
 *
 * Each rate is drawn from Beta(conversions + 1, non-conversions + 1). The +1 (a
 * flat prior) matters at low numbers: without it a variation with 0 conversions
 * always drew 0%, so a rival with a single conversion won every draw and was
 * declared the winner with 100% confidence. Sites that count more than one
 * conversion per visitor get a Gamma model of conversions per visit instead of
 * no analysis at all.
 *
 * Draws are seeded from the data, so the same results always give the same
 * probabilities and a borderline test cannot flip between "winner" and "not
 * yet" from one page view to the next. A variation with no visits yet is left
 * out of the draws (and blocks a winner) instead of stopping the analysis.
 *
 * The helpers share their names with AB Split Test Pro, which ships the same
 * code, so each is only declared when Pro has not already declared it.
 */

if ( ! function_exists( 'abst_stats_seed' ) ) {
  /** Seed the generator from the data being analysed; abst_stats_unseed() restores randomness. */
  function abst_stats_seed( $data ) {
    // phpcs:ignore WordPress.WP.AlternativeFunctions.rand_seeding_mt_srand -- Deliberate: identical results must give identical probabilities.
    mt_srand( crc32( serialize( $data ) ) );
  }
}

if ( ! function_exists( 'abst_stats_unseed' ) ) {
  function abst_stats_unseed() {
    // phpcs:ignore WordPress.WP.AlternativeFunctions.rand_seeding_mt_srand -- Restores a random seed after the seeded analysis.
    mt_srand();
  }
}

if ( ! function_exists( 'abst_stats_uniform' ) ) {
  /** Uniform draw in (0, 1), never exactly 0 or 1. */
  function abst_stats_uniform() {
    // phpcs:ignore WordPress.WP.AlternativeFunctions.rand_mt_rand -- Needs the seeded generator for repeatable results.
    return ( mt_rand() + 0.5 ) / ( mt_getrandmax() + 1.0 );
  }
}

if ( ! function_exists( 'abst_stats_normal' ) ) {
  /** Standard normal draw (Box-Muller). */
  function abst_stats_normal() {
    return sqrt( -2 * log( abst_stats_uniform() ) ) * cos( 2 * M_PI * abst_stats_uniform() );
  }
}

if ( ! function_exists( 'abst_stats_gamma' ) ) {
  /** Gamma(shape, 1) draw (Marsaglia & Tsang); a normal approximation for large shapes. */
  function abst_stats_gamma( $shape ) {
    if ( $shape <= 0 ) return 0.0;
    if ( $shape > 1000 ) return max( 0.0, $shape + sqrt( $shape ) * abst_stats_normal() );
    if ( $shape < 1 ) return abst_stats_gamma( $shape + 1 ) * pow( abst_stats_uniform(), 1 / $shape );
    $d = $shape - 1 / 3;
    $c = 1 / sqrt( 9 * $d );
    while ( true ) {
      do {
        $x = abst_stats_normal();
        $v = 1 + $c * $x;
      } while ( $v <= 0 );
      $v = $v * $v * $v;
      $u = abst_stats_uniform();
      if ( $u < 1 - 0.0331 * $x * $x * $x * $x || log( $u ) < 0.5 * $x * $x + $d * ( 1 - $v + log( $v ) ) ) {
        return $d * $v;
      }
    }
  }
}

if ( ! function_exists( 'abst_stats_beta' ) ) {
  /** Beta(a, b) draw; a normal approximation once both shapes are large. */
  function abst_stats_beta( $a, $b ) {
    if ( $a > 1000 && $b > 1000 ) {
      $n = $a + $b;
      return $a / $n + sqrt( $a * $b / ( $n * $n * ( $n + 1 ) ) ) * abst_stats_normal();
    }
    $x = abst_stats_gamma( $a );
    $y = abst_stats_gamma( $b );
    return ( $x + $y ) > 0 ? $x / ( $x + $y ) : 0.0;
  }
}

if ( ! function_exists( 'abst_stats_probability_best' ) ) {
  /**
   * Percentage of draws (rounded) in which each variation's sampled value is highest.
   *
   * @param callable[] $samplers Variation key => function returning one draw.
   */
  function abst_stats_probability_best( array $samplers, $draws ) {
    $wins = array_fill_keys( array_keys( $samplers ), 0 );
    for ( $i = 0; $i < $draws; $i++ ) {
      $best = null;
      $best_value = -INF;
      foreach ( $samplers as $key => $draw ) {
        $x = $draw();
        if ( $x > $best_value ) {
          $best_value = $x;
          $best = $key;
        }
      }
      if ( $best !== null ) $wins[ $best ]++;
    }
    $out = array();
    foreach ( $wins as $key => $count ) {
      $out[ $key ] = round( $count / $draws * 100 );
    }
    return $out;
  }
}

if ( ! function_exists( 'abst_stats_collect_variations' ) ) {
  /**
   * Variations that have visits (keyed as in $data), and whether every variation in
   * the test has enough visits for a winner to be called.
   */
  function abst_stats_collect_variations( $data, $min_visits ) {
    $with_visits = array();
    $variation_count = 0;
    $enough = true;
    foreach ( $data as $key => $v ) {
      if ( $key === 'bt_bb_ab_stats' || ! is_array( $v ) ) continue;
      $variation_count++;
      $visits = isset( $v['visit'] ) ? (int) $v['visit'] : 0;
      if ( $visits < $min_visits ) $enough = false;
      if ( $visits <= 0 ) continue;
      $v['visit'] = $visits;
      $v['conversion'] = isset( $v['conversion'] ) ? (float) $v['conversion'] : 0.0;
      $with_visits[ $key ] = $v;
    }
    return array( $with_visits, $enough && $variation_count >= 2 );
  }
}

if ( ! function_exists( 'abst_stats_no_verdict' ) ) {
  /**
   * Record "no verdict": nothing to compare yet. Overwrites any verdict carried in
   * from storage so an old result is never shown as current.
   */
  function abst_stats_no_verdict( $data, $likely_duration = 0 ) {
    if ( ! isset( $data['bt_bb_ab_stats'] ) || ! is_array( $data['bt_bb_ab_stats'] ) ) $data['bt_bb_ab_stats'] = array();
    $data['bt_bb_ab_stats']['best'] = false;
    $data['bt_bb_ab_stats']['probability'] = 0;
    $data['bt_bb_ab_stats']['winner'] = false;
    $data['bt_bb_ab_stats']['blocked_by'] = $likely_duration === 999 ? 'conversions' : 'visits';
    $data['bt_bb_ab_stats']['likelyDuration'] = $likely_duration;
    $data['bt_bb_ab_stats']['likelyVisitors'] = 0;
    foreach ( $data as $key => $v ) {
      if ( $key !== 'bt_bb_ab_stats' && is_array( $v ) ) unset( $data[ $key ]['probability'] );
    }
    return $data;
  }
}

if ( ! function_exists( 'abst_stats_conversion_sampler' ) ) {
  /** One draw of a conversion rate: Beta with a flat prior, or conversions per visit when counts exceed visits. */
  function abst_stats_conversion_sampler( $visits, $conversions ) {
    $conversions = max( 0.0, (float) $conversions );
    if ( $conversions <= $visits ) {
      $a = $conversions + 1;
      $b = $visits - $conversions + 1;
      return function () use ( $a, $b ) { return abst_stats_beta( $a, $b ); };
    }
    $shape = $conversions + 1;
    return function () use ( $shape, $visits ) { return abst_stats_gamma( $shape ) / $visits; };
  }
}

if ( ! function_exists( 'abst_stats_draw_verdict' ) ) {
/**
 * Chance each variation is best, and the expected loss of choosing each one,
 * from one set of draws (the same draws abst_stats_probability_best() makes).
 *
 * Expected loss of choosing k = the average, over all draws, of (the best value
 * in that draw minus k's value). It is what you give up on average if k is not
 * really the best: small when k is almost surely best, and also small when the
 * variations that might beat it would only beat it by a hair.
 *
 * @param callable[] $samplers Variation key => function returning one draw.
 * @return array ['probability' => key => %, 'loss' => key => value, 'mean' => key => value]
 */
function abst_stats_draw_verdict(array $samplers, $draws) {
  $keys = array_keys($samplers);
  $wins = array_fill_keys($keys, 0);
  $loss = array_fill_keys($keys, 0.0);
  $sum = array_fill_keys($keys, 0.0);
  for ($i = 0; $i < $draws; $i++) {
    $values = array();
    $best = null;
    $best_value = -INF;
    foreach ($samplers as $key => $draw) {
      $x = $draw();
      $values[$key] = $x;
      $sum[$key] += $x;
      if ($x > $best_value) {
        $best_value = $x;
        $best = $key;
      }
    }
    if ($best !== null) $wins[$best]++;
    foreach ($values as $key => $x) $loss[$key] += $best_value - $x;
  }
  $out = array('probability' => array(), 'loss' => array(), 'mean' => array());
  foreach ($keys as $key) {
    $out['probability'][$key] = round($wins[$key] / $draws * 100);
    $out['loss'][$key] = $loss[$key] / $draws;
    $out['mean'][$key] = $sum[$key] / $draws;
  }
  return $out;
}

/** Expected loss as a share of the variation's own expected value (0.01 = 1%). */
function abst_stats_relative_loss($verdict, $key) {
  $loss = (float) ($verdict['loss'][$key] ?? 0);
  $mean = (float) ($verdict['mean'][$key] ?? 0);
  if ($loss <= 0) return 0.0;
  return $mean > 0 ? $loss / $mean : INF;
}

/**
 * Conversions (orders, for revenue tests) the whole test needs before a winner
 * can be called: 25 per variation by default, counted across the test so a
 * variation that truly converts close to 0 can still lose.
 */
function abst_stats_min_conversions($variation_count) {
  return max(0, (int) apply_filters('abst_min_conversions_for_winner', 25)) * max(2, (int) $variation_count);
}

/**
 * Optional extra rule: the largest expected loss, relative to the leader's own
 * rate, at which it can be called the winner (0.01 = choosing it costs under 1%
 * of its conversion rate if it is not really the best).
 *
 * Off by default. At the 95% threshold the leader's expected loss is already a
 * fraction of a percent, so it would never decide anything; it only matters
 * when a site lowers the confidence threshold and wants a floor under it.
 * Expected loss is always recorded in the verdict either way.
 */
function abst_stats_max_expected_loss() {
  $threshold = apply_filters('abst_expected_loss_threshold', null);
  return (is_numeric($threshold) && $threshold > 0) ? (float) $threshold : INF;
}

/**
 * Why the leader is not (yet) the winner, in the order a user would fix it:
 * 'visits', 'conversions', 'confidence', 'loss'; '' when it is the winner.
 */
function abst_stats_blocked_by($has_min_visits, $events, $min_events, $probability, $threshold, $relative_loss, $max_loss) {
  if (!$has_min_visits) return 'visits';
  if ($events < $min_events) return 'conversions';
  if ($probability < $threshold) return 'confidence';
  if ($relative_loss > $max_loss) return 'loss';
  return '';
}

/** Record the verdict fields every screen reads. */
function abst_stats_store_verdict($data, $best, $verdict, $blocked_by, $events, $min_events) {
  if (!isset($data['bt_bb_ab_stats']) || !is_array($data['bt_bb_ab_stats'])) $data['bt_bb_ab_stats'] = array();
  $data['bt_bb_ab_stats']['best'] = $best;
  $data['bt_bb_ab_stats']['probability'] = $verdict['probability'][$best];
  // The one winner rule every screen should use.
  $data['bt_bb_ab_stats']['winner'] = ($blocked_by === '');
  $data['bt_bb_ab_stats']['blocked_by'] = $blocked_by;
  $data['bt_bb_ab_stats']['conversions'] = (int) round($events); // counts, stored whole
  $data['bt_bb_ab_stats']['min_conversions'] = $min_events;
  $data['bt_bb_ab_stats']['expected_loss'] = $verdict['loss'][$best];
  $relative = abst_stats_relative_loss($verdict, $best);
  $data['bt_bb_ab_stats']['expected_loss_pct'] = is_finite($relative) ? round($relative * 100, 2) : null;
  return $data;
}
}

if ( ! function_exists( 'abst_stats_project_duration' ) ) {
  /**
   * Days (and total visits) until the leader would be the winner if every
   * variation keeps its current daily traffic and results, by the same rule as
   * today (visits, conversions, confidence, expected loss). 999 days = not within
   * 500 days at this traffic.
   *
   * @param callable $samplers_at Given a projected age in days, returns the samplers,
   *                              the smallest projected visit count, the total
   *                              visits and the total conversions.
   */
  function abst_stats_project_duration( $test_age, $threshold, $min_visits, callable $samplers_at, $min_events = 0, $max_loss = INF ) {
    $age = (int) $test_age;
    while ( $age < 500 ) {
      $age += $age > 120 ? 30 : 7;
      $projected = $samplers_at( $age );
      list( $samplers, $fewest_visits, $total_visits ) = $projected;
      $events = isset( $projected[3] ) ? $projected[3] : INF;
      if ( count( $samplers ) < 2 ) break;
      if ( $fewest_visits < $min_visits || $events < $min_events ) continue;
      $verdict = abst_stats_draw_verdict( $samplers, 1000 );
      $best = array_keys( $verdict['probability'], max( $verdict['probability'] ) )[0];
      if ( $verdict['probability'][ $best ] >= $threshold && abst_stats_relative_loss( $verdict, $best ) <= $max_loss ) {
        return array( $age, $total_visits );
      }
    }
    return array( 999, 0 );
  }
}

/**
 * The fewest visits every variation needs before a winner can be called: the
 * site-wide minimum (50, filterable) unless the test asks for more.
 */
function abst_min_visits_floor() {
  // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Backward compatibility for legacy public filter.
  return max( 1, (int) apply_filters( 'abst_min_visits_for_winner', apply_filters( 'ab_min_visits_for_winner', 50 ) ) );
}

/**
 * Bayesian verdict on a test's observations.
 *
 * @param array    $data       Observations keyed by variation.
 * @param int      $test_age   Days since the test started (0 skips the duration projection).
 * @param int|null $min_visits Per-test minimum visits; never below the site-wide floor.
 * @return array Observations with per-variation 'probability' and a 'bt_bb_ab_stats'
 *               block (best, probability, winner, likelyDuration, likelyVisitors).
 */
function abst_split_test_analyzer( $data = array(), $test_age = 0, $min_visits = null ) {
  if ( empty( $data ) || ! is_array( $data ) ) return $data;

  $test_age = intval( $test_age );
  $min_visits_for_winner = max( abst_min_visits_floor(), (int) $min_visits );
  // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Backward compatibility for legacy public filter.
  $percentage_target = apply_filters( 'abst_complete_confidence', apply_filters( 'ab_complete_confidence', 95 ) );
  list( $variations, $has_min_visits ) = abst_stats_collect_variations( $data, $min_visits_for_winner );
  if ( count( $variations ) < 2 ) return abst_stats_no_verdict( $data );

  $total_conversions = 0;
  foreach ( $variations as $v ) $total_conversions += max( 0, $v['conversion'] );
  if ( $total_conversions <= 0 ) {
    // Traffic but no conversions anywhere: nothing to call, and at this rate never.
    return abst_stats_no_verdict( $data, $test_age > 0 ? 999 : 0 );
  }

  abst_stats_seed( $variations );
  $samplers = array();
  foreach ( $variations as $key => $v ) {
    $samplers[ $key ] = abst_stats_conversion_sampler( $v['visit'], $v['conversion'] );
  }
  $verdict = abst_stats_draw_verdict( $samplers, 5000 );
  foreach ( $verdict['probability'] as $key => $probability ) {
    $data[ $key ]['probability'] = $probability;
  }
  $best = array_keys( $verdict['probability'], max( $verdict['probability'] ) )[0];
  // A winner needs every variation past the minimum visits, enough conversions in
  // the test, the confidence threshold, and (if a site opts in) a small expected loss.
  $min_events = abst_stats_min_conversions( count( $variations ) );
  $max_loss = abst_stats_max_expected_loss();
  $blocked_by = abst_stats_blocked_by( $has_min_visits, $total_conversions, $min_events, $verdict['probability'][ $best ], $percentage_target, abst_stats_relative_loss( $verdict, $best ), $max_loss );
  $data = abst_stats_store_verdict( $data, $best, $verdict, $blocked_by, $total_conversions, $min_events );

  if ( $data['bt_bb_ab_stats']['winner'] ) {
    $data['bt_bb_ab_stats']['likelyDuration'] = false; // Winner found
    $data['bt_bb_ab_stats']['likelyVisitors'] = false;
  } elseif ( $test_age > 0 ) {
    // Project each variation forward at its current daily visits and conversions.
    list( $days, $visits ) = abst_stats_project_duration( $test_age, $percentage_target, $min_visits_for_winner, function ( $age ) use ( $variations, $test_age ) {
      $samplers = array();
      $fewest = PHP_INT_MAX;
      $total = 0;
      $events = 0;
      foreach ( $variations as $key => $v ) {
        $n = (int) ( $v['visit'] / $test_age * $age );
        if ( $n <= 0 ) continue;
        $c = (int) ( $v['conversion'] / $test_age * $age );
        $fewest = min( $fewest, $n );
        $total += $n;
        $events += $c;
        $samplers[ $key ] = abst_stats_conversion_sampler( $n, $c );
      }
      return array( $samplers, $fewest, $total, $events );
    }, $min_events, $max_loss );
    $data['bt_bb_ab_stats']['likelyDuration'] = $days;
    $data['bt_bb_ab_stats']['likelyVisitors'] = $visits;
  }
  abst_stats_unseed();

  return $data;
}

/**
 * Full analysis of a test's observations: the verdict plus the device-size slices.
 *
 * Memoised per request on the exact input. One admin test screen analyses the
 * same results more than once (the status sidebar and the results panel), and
 * the duration projection is the slow part of the page.
 */
function abst_analyze_observations( $observations, $test_age, $min_visits = null ) {
  static $cache = array();
  $key = md5( serialize( array( $observations, (int) $test_age, $min_visits ) ) );
  if ( array_key_exists( $key, $cache ) ) {
    return $cache[ $key ];
  }
  $observations = abst_split_test_analyzer( $observations, $test_age, $min_visits );
  $observations = abst_analyze_device_sizes( $observations, $test_age, $min_visits );
  if ( count( $cache ) >= 20 ) {
    $cache = array();
  }
  $cache[ $key ] = $observations;
  return $observations;
}

/**
 * Run the analyzer on each device_size slice of an observations array and stamp
 * the per-size probability (plus derived stats block) back onto each variation's
 * device_size bucket. Does not mutate top-level probabilities.
 *
 * Sizes processed: mobile, tablet, desktop.
 */
function abst_analyze_device_sizes( $data, $test_age = 0, $min_visits = null ) {
  if ( ! is_array( $data ) || empty( $data ) ) return $data;

  $sizes = array( 'mobile', 'tablet', 'desktop' );

  foreach ( $sizes as $size ) {
    // Build a slice where each variation's top-level fields come from device_size[$size]
    $slice = array();
    foreach ( $data as $vkey => $v ) {
      if ( $vkey === 'bt_bb_ab_stats' ) continue;
      if ( ! is_array( $v ) ) continue;
      if ( ! isset( $v['device_size'][ $size ] ) || ! is_array( $v['device_size'][ $size ] ) ) continue;
      $ds = $v['device_size'][ $size ];
      $visits = isset( $ds['visit'] ) ? (int) $ds['visit'] : 0;
      $conversions = isset( $ds['conversion'] ) ? (float) $ds['conversion'] : 0;
      if ( $visits <= 0 ) continue;
      $slice[ $vkey ] = array(
        'visit' => $visits,
        'conversion' => $conversions,
        'rate' => isset( $ds['rate'] ) ? $ds['rate'] : round( ( ( $conversions / $visits ) * 100 ), 2 ),
      );
    }

    if ( count( $slice ) < 2 ) continue; // analyzer needs >= 2 variations

    // test_age=0 skips the duration projection, which would be noisy per device.
    $analyzed = abst_split_test_analyzer( $slice, 0, $min_visits );

    if ( ! is_array( $analyzed ) ) continue;

    foreach ( $analyzed as $vkey => $v ) {
      if ( $vkey === 'bt_bb_ab_stats' ) continue;
      if ( ! isset( $data[ $vkey ]['device_size'][ $size ] ) ) continue;
      if ( isset( $v['probability'] ) ) {
        $data[ $vkey ]['device_size'][ $size ]['probability'] = $v['probability'];
      }
    }
    if ( isset( $analyzed['bt_bb_ab_stats'] ) ) {
      if ( ! isset( $data['bt_bb_ab_stats'] ) ) $data['bt_bb_ab_stats'] = array();
      if ( ! isset( $data['bt_bb_ab_stats']['device_size'] ) ) $data['bt_bb_ab_stats']['device_size'] = array();
      $data['bt_bb_ab_stats']['device_size'][ $size ] = $analyzed['bt_bb_ab_stats'];
    }
  }

  return $data;
}

if ( ! function_exists( 'bt_bb_ab_split_test_analyzer' ) ) {
  // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound -- Backward compatibility for older integrations.
  function bt_bb_ab_split_test_analyzer( $data = array(), $test_age = 0, $min_visits = null ) {
    return abst_split_test_analyzer( $data, $test_age, $min_visits );
  }
}

if ( ! function_exists( 'bt_bb_ab_analyze_device_sizes' ) ) {
  // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound -- Backward compatibility for older integrations.
  function bt_bb_ab_analyze_device_sizes( $data, $test_age = 0, $min_visits = null ) {
    return abst_analyze_device_sizes( $data, $test_age, $min_visits );
  }
}
