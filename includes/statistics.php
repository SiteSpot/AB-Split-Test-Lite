<?php 

require_once("vendor/autoload.php"); 

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
  foreach ($data as $n => $d){
    $conversions = isset($d['conversion']) ? (int)$d['conversion'] : 0;
    $visits = isset($d['visit']) ? (int)$d['visit'] : 0;
    if($visits <= $conversions)
      return $data; // something is weird, bail out

    $variations[] = new Variation($n, $visits, $conversions);
  }

  $predictor = SplitTestAnalyzer::create()->withVariations(...$variations);
  $percentage_target = apply_filters('ab_complete_confidence', 95);
  foreach ($predictor->getResult() as $key => $value) {    
    $data[$key]['probability'] = $value;
    if($value > $percentage_target)
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
        $data['bt_bb_ab_stats']['likelyDuration'] = false;
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
    $rate = isset($variation['rate']) ? (float)$variation['rate'] : 0;
    
    if ($visits > 0 && $rate > 0) {
      // Estimate standard deviation as 1.5x the mean (typical for revenue data)
      $estimated_std = $rate * 1.5;
      
      $variations[$key] = [
        'n' => $visits,
        'mean' => $rate,
        'std' => $estimated_std
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
  
  // Compare each variation against the best one
  $confidence_threshold = 95; // 95% confidence level
  $winner_found = false;
  
  foreach ($variations as $key => $stats) {
    if ($key === $best_key) {
      // Compare best against others to get its confidence
      $max_p_value = 0;
      foreach ($variations as $other_key => $other_stats) {
        if ($other_key !== $key) {
          $t_result = welch_t_test(
            $stats['n'], $stats['mean'], $stats['std'],
            $other_stats['n'], $other_stats['mean'], $other_stats['std']
          );
          $max_p_value = max($max_p_value, $t_result['p_value']);
        }
      }
      $confidence = (1 - $max_p_value) * 100;
      $data[$key]['probability'] = round($confidence, 2);
      
      if ($confidence >= $confidence_threshold) {
        $winner_found = true;
      }
    } else {
      // Compare this variation against the best
      $t_result = welch_t_test(
        $variations[$best_key]['n'], $variations[$best_key]['mean'], $variations[$best_key]['std'],
        $stats['n'], $stats['mean'], $stats['std']
      );
      $confidence = (1 - $t_result['p_value']) * 100;
      $data[$key]['probability'] = round($confidence, 2);
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
    $data['bt_bb_ab_stats']['best'] = '';
    $data['bt_bb_ab_stats']['probability'] = 0;
    // Estimate duration (simplified)
    $data['bt_bb_ab_stats']['likelyDuration'] = 30; // Estimate 30 more days
  }
  
  return $data;
}

/**
 * Welch's T-Test implementation for two samples with summary statistics
 */
function welch_t_test($n1, $mean1, $std1, $n2, $mean2, $std2) {
  // Welch's T-Test formula
  $s1_squared = $std1 * $std1;
  $s2_squared = $std2 * $std2;
  
  // Standard error of difference
  $se = sqrt(($s1_squared / $n1) + ($s2_squared / $n2));
  
  // T-statistic
  $t = ($mean1 - $mean2) / $se;
  
  // Degrees of freedom (Welch-Satterthwaite equation)
  $numerator = pow(($s1_squared / $n1) + ($s2_squared / $n2), 2);
  $denominator = (pow($s1_squared / $n1, 2) / ($n1 - 1)) + (pow($s2_squared / $n2, 2) / ($n2 - 1));
  $df = $numerator / $denominator;
  
  // Calculate p-value (two-tailed test)
  $p_value = 2 * (1 - t_distribution_cdf(abs($t), $df));
  
  return [
    't_statistic' => $t,
    'degrees_of_freedom' => $df,
    'p_value' => $p_value
  ];
}

/**
 * Approximation of t-distribution CDF using Wilson-Hilferty transformation
 */
function t_distribution_cdf($t, $df) {
  // For large df, t-distribution approaches normal distribution
  if ($df > 100) {
    return normal_cdf($t);
  }
  
  // Wilson-Hilferty approximation for t-distribution
  $h = 4 * $df + 1;
  $x = $t / sqrt($df);
  $z = (pow(1 + ($x * $x) / $df, 3/2) - 1) * sqrt($h) / (3 * $x);
  
  return normal_cdf($z);
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





