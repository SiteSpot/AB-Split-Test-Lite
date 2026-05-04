<?php 
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

require_once(__DIR__ . "/vendor/autoload.php"); 

use BenTools\SplitTestAnalyzer\SplitTestAnalyzer;
use BenTools\SplitTestAnalyzer\Variation;

function bt_bb_ab_split_test_analyzer($data = array(),$test_age = 0){

  if(empty($data))
    return $data;
  
  if(sizeof($data) < 2)
    return $data;
  
  $variations = [];
  $winnerFound = false;
  $test_age = intval($test_age);
  // Minimum visits per variation before a winner can be declared. Keeps the
  // server-side winner flag in sync with the UI's underpowered gate.
  $min_visits_for_winner = apply_filters('ab_min_visits_for_winner', 50);
  $has_min_visits = true;
  foreach ($data as $n => $d){
    if ($n === 'bt_bb_ab_stats') continue;
    $conversions = isset($d['conversion']) ? (int)$d['conversion'] : 0;
    $visits = isset($d['visit']) ? (int)$d['visit'] : 0;
    if($visits <= $conversions)
      return $data; // visits must exceed conversions for beta distribution
    if($visits < $min_visits_for_winner)
      $has_min_visits = false;

    $variations[] = new Variation($n, $visits, $conversions);
  }

  $predictor = SplitTestAnalyzer::create()->withVariations(...$variations);
  $percentage_target = apply_filters('ab_complete_confidence', 95);
  foreach ($predictor->getResult() as $key => $value) {
    $data[$key]['probability'] = $value;
    if($value > $percentage_target && $has_min_visits)
      $winnerFound = true;
  }
  if(!$winnerFound && $test_age > 0){ // guess how long it's going to be
    //get the per day data
    
    $durationFinder = [];
    
    foreach ($data as $n => $d){
      if(isset($d['bt_bb_ab_stats']))
        continue;
      $durationFinder[$n] = [];
      $durationFinder[$n]['conversion'] = $d['conversion'] / $test_age;
      $durationFinder[$n]['visit'] = $d['visit'] / $test_age;
    }
    // add 7 days to days running (loop) and test to see if theres a winner
  while(!$winnerFound && $test_age < 500){
    $newVariations = [];
    if($test_age > 120)
      $test_age += 30;
    else
      $test_age += 7;

    foreach ($durationFinder as $n => $d){
      $newVariations[] = new Variation(
        $n, 
        intval($d['visit'] * $test_age), 
        intval($d['conversion'] * $test_age)
      );      
    }
    $futurePredictor = SplitTestAnalyzer::create()->withVariations(...$newVariations);
    $futureBest = $futurePredictor->getBestVariation();
    $fbest = $futurePredictor->getResult();
    //find higest number
    $best_rate = intval($fbest[array_keys($fbest, max($fbest))[0]]);
        
    if(!isset($data['bt_bb_ab_stats']))
      $data['bt_bb_ab_stats'] = [];
  
      if(!empty($best_rate) && $best_rate > $percentage_target){
        $winnerFound = true;
        $data['bt_bb_ab_stats']['likelyDuration'] = $test_age;
      }
      else
      {
        // 999 = "very long time" - difference too small to detect with current traffic
        $data['bt_bb_ab_stats']['likelyDuration'] = 999;
      }
    }
  }

  $bestVariation = $predictor->getBestVariation();
  
  if($bestVariation)
  {
    $data['bt_bb_ab_stats']['best'] = $predictor->getBestVariation()->getKey();
    $data['bt_bb_ab_stats']['probability'] = $data[$data['bt_bb_ab_stats']['best']]['probability'];
  }
  else
  {
    $data['bt_bb_ab_stats']['best'] = false;
    $data['bt_bb_ab_stats']['probability'] = 0;    
  }

  return $data;

}

/**
 * Welch's T-Test for revenue-based A/B testing
 * Uses summary statistics (n, mean, estimated std dev)
 * 
 * Note: The 'rate' field in observations stores (total_revenue / visits) * 100
 * So rate=664.7 means $6.647 revenue per visit
 */
function bt_bb_ab_revenue_analyzer($data = array(), $test_age = 0) {
  if (empty($data) || sizeof($data) < 2) {
    return $data;
  }
  
  // Convert data to array for easier processing
  $variations = [];
  foreach ($data as $key => $variation) {
    if ($key === 'bt_bb_ab_stats') continue;
    
    $visits = isset($variation['visit']) ? (int)$variation['visit'] : 0;
    // Rate is stored as (revenue/visits)*100, so divide by 100 to get actual revenue per visit
    $rate = isset($variation['rate']) ? (float)$variation['rate'] / 100 : 0;

    if ($visits > 0 && $rate > 0) {
      // Use Welford running variance if available (accurate), otherwise estimate
      if (isset($variation['_rv_count']) && $variation['_rv_count'] > 1 && isset($variation['_rv_m2'])) {
        $rv_std = sqrt((float)$variation['_rv_m2'] / ($variation['_rv_count'] - 1));
      } else {
        // Fallback for tests started before Welford tracking: estimate as 1.5x the mean
        $rv_std = $rate * 1.5;
      }

      $variations[$key] = [
        'n' => $visits,
        'mean' => $rate,
        'std' => $rv_std
      ];
    }
  }
  
  if (count($variations) < 2) {
    return $data;
  }
  
  // Find the best performing variation (highest mean revenue)
  $best_key = '';
  $best_mean = 0;
  foreach ($variations as $key => $stats) {
    if ($stats['mean'] > $best_mean) {
      $best_mean = $stats['mean'];
      $best_key = $key;
    }
  }
  
  // Use Monte Carlo simulation to calculate "probability of being best" for each variation
  // Same approach as the Bayesian analyzer - run many simulations and count wins
  $confidence_threshold = 95;
  $winner_found = false;
  $num_samples = 2000;

  // Minimum visits per variation before a winner can be declared. Mirrors the
  // UI's underpowered gate so a freak small-sample win can't trip autocomplete.
  $min_visits_for_winner = apply_filters('ab_min_visits_for_winner', 50);
  $has_min_visits = true;
  foreach ($variations as $stats) {
    if ($stats['n'] < $min_visits_for_winner) {
      $has_min_visits = false;
      break;
    }
  }
  
  // Initialize win counts
  $win_count = [];
  foreach ($variations as $key => $stats) {
    $win_count[$key] = 0;
  }
  
  // Run Monte Carlo simulation
  for ($i = 0; $i < $num_samples; $i++) {
    $winner_key = null;
    $winner_value = -PHP_INT_MAX;
    
    // Sample from each variation's distribution and find the winner
    foreach ($variations as $key => $stats) {
      // Sample from normal distribution with mean and std
      $sample = random_normal($stats['mean'], $stats['std'] / sqrt($stats['n']));
      if ($sample > $winner_value) {
        $winner_value = $sample;
        $winner_key = $key;
      }
    }
    
    if ($winner_key !== null) {
      $win_count[$winner_key]++;
    }
  }
  
  // Calculate probabilities (percentage of wins)
  foreach ($variations as $key => $stats) {
    $probability = round(($win_count[$key] / $num_samples) * 100);
    $data[$key]['probability'] = $probability;
    
    if ($key === $best_key && $probability >= $confidence_threshold && $has_min_visits) {
      $winner_found = true;
    }
  }
  
  // Set up bt_bb_ab_stats
  if (!isset($data['bt_bb_ab_stats'])) {
    $data['bt_bb_ab_stats'] = [];
  }
  
  if ($winner_found) {
    $data['bt_bb_ab_stats']['best'] = $best_key;
    $data['bt_bb_ab_stats']['probability'] = $data[$best_key]['probability'];
    $data['bt_bb_ab_stats']['likelyDuration'] = false; // Test is complete
  } else {
    $data['bt_bb_ab_stats']['best'] = $best_key;
    $data['bt_bb_ab_stats']['probability'] = $data[$best_key]['probability'];
    
    // Estimate time remaining by projecting forward
    // Calculate daily rates
    if ($test_age > 0) {
      $daily_rates = [];
      foreach ($variations as $key => $stats) {
        $daily_rates[$key] = [
          'n_per_day' => $stats['n'] / $test_age,
          'mean' => $stats['mean'],
          'std' => $stats['std']
        ];
      }
      
      // Project forward until we find a winner (or give up at 500 days)
      $projected_age = $test_age;
      $projected_winner_found = false;
      
      while (!$projected_winner_found && $projected_age < 500) {
        // Increment by 7 days (or 30 if past 120 days)
        if ($projected_age > 120) {
          $projected_age += 30;
        } else {
          $projected_age += 7;
        }
        
        // Build projected variations
        $projected_variations = [];
        foreach ($daily_rates as $key => $rates) {
          $projected_n = intval($rates['n_per_day'] * $projected_age);
          if ($projected_n > 0) {
            $projected_variations[$key] = [
              'n' => $projected_n,
              'mean' => $rates['mean'],
              'std' => $rates['std']
            ];
          }
        }
        
        if (count($projected_variations) < 2) break;
        
        // Run Monte Carlo on projected data
        $projected_win_count = array_fill_keys(array_keys($projected_variations), 0);
        
        for ($i = 0; $i < 1000; $i++) { // Fewer samples for speed
          $winner_key = null;
          $winner_value = -PHP_INT_MAX;
          
          foreach ($projected_variations as $key => $stats) {
            $sample = random_normal($stats['mean'], $stats['std'] / sqrt($stats['n']));
            if ($sample > $winner_value) {
              $winner_value = $sample;
              $winner_key = $key;
            }
          }
          
          if ($winner_key !== null) {
            $projected_win_count[$winner_key]++;
          }
        }
        
        // Check if any variation reaches 95%
        $max_prob = max($projected_win_count) / 10; // /1000 * 100
        if ($max_prob >= 95) {
          $projected_winner_found = true;
        }
      }
      
      // 999 = "very long time" - difference too small to detect with current traffic
      $data['bt_bb_ab_stats']['likelyDuration'] = $projected_winner_found ? $projected_age : 999;
    } else {
      // No test age yet, can't estimate
      $data['bt_bb_ab_stats']['likelyDuration'] = 0;
    }
  }
  
  return $data;
}


/**
 * Generate a random sample from a normal distribution
 * Uses Box-Muller transform
 */
function random_normal($mean = 0, $std = 1) {
  $u1 = mt_rand() / mt_getrandmax();
  $u2 = mt_rand() / mt_getrandmax();
  
  // Box-Muller transform
  $z = sqrt(-2 * log($u1)) * cos(2 * M_PI * $u2);
  
  return $mean + $std * $z;
}

/**
 * Run the appropriate analyzer on each device_size slice of an observations array
 * and stamp the per-size probability (plus derived stats block) back onto each
 * variation's device_size bucket. Does not mutate top-level probabilities.
 *
 * Sizes processed: mobile, tablet, desktop.
 * Revenue path is used when $use_revenue is truthy (test has order-value tracking).
 */
function bt_bb_ab_analyze_device_sizes($data, $test_age = 0, $use_revenue = false) {
  if (!is_array($data) || empty($data)) return $data;

  $sizes = array('mobile', 'tablet', 'desktop');

  foreach ($sizes as $size) {
    // Build a slice where each variation's top-level fields come from device_size[$size]
    $slice = array();
    foreach ($data as $vkey => $v) {
      if ($vkey === 'bt_bb_ab_stats') continue;
      if (!is_array($v)) continue;
      if (!isset($v['device_size'][$size]) || !is_array($v['device_size'][$size])) continue;
      $ds = $v['device_size'][$size];
      $visits = isset($ds['visit']) ? (int)$ds['visit'] : 0;
      $conversions = isset($ds['conversion']) ? (float)$ds['conversion'] : 0;
      if ($visits <= 0) continue;
      $entry = array(
        'visit' => $visits,
        'conversion' => $conversions,
        'rate' => isset($ds['rate']) ? $ds['rate'] : round((($conversions / $visits) * 100), 2),
      );
      if (isset($ds['_rv_count'])) $entry['_rv_count'] = $ds['_rv_count'];
      if (isset($ds['_rv_mean']))  $entry['_rv_mean']  = $ds['_rv_mean'];
      if (isset($ds['_rv_m2']))    $entry['_rv_m2']    = $ds['_rv_m2'];
      $slice[$vkey] = $entry;
    }

    if (count($slice) < 2) continue; // analyzer needs >= 2 variations

    if ($use_revenue) {
      // test_age=0 skips the expensive projection/likelyDuration loop inside the analyzer.
      // Per-size duration estimation would be noisy and ~5-20s per admin render.
      $analyzed = bt_bb_ab_revenue_analyzer($slice, 0);
    } else {
      $analyzed = bt_bb_ab_split_test_analyzer($slice, 0);
    }

    if (!is_array($analyzed)) continue;

    foreach ($analyzed as $vkey => $v) {
      if ($vkey === 'bt_bb_ab_stats') continue;
      if (!isset($data[$vkey]['device_size'][$size])) continue;
      if (isset($v['probability'])) {
        $data[$vkey]['device_size'][$size]['probability'] = $v['probability'];
      }
    }
    if (isset($analyzed['bt_bb_ab_stats'])) {
      if (!isset($data['bt_bb_ab_stats'])) $data['bt_bb_ab_stats'] = array();
      if (!isset($data['bt_bb_ab_stats']['device_size'])) $data['bt_bb_ab_stats']['device_size'] = array();
      $data['bt_bb_ab_stats']['device_size'][$size] = $analyzed['bt_bb_ab_stats'];
    }
  }

  return $data;
}

/**
 * Standard normal distribution CDF approximation
 */
function normal_cdf($x) {
  // Abramowitz and Stegun approximation
  $sign = $x >= 0 ? 1 : -1;
  $x = abs($x) / sqrt(2);
  
  // Constants
  $a1 =  0.254829592;
  $a2 = -0.284496736;
  $a3 =  1.421413741;
  $a4 = -1.453152027;
  $a5 =  1.061405429;
  $p  =  0.3275911;
  
  $t = 1.0 / (1.0 + $p * $x);
  $y = 1.0 - ((((($a5 * $t + $a4) * $t) + $a3) * $t + $a2) * $t + $a1) * $t * exp(-$x * $x);
  
  return 0.5 * (1.0 + $sign * $y);
}





