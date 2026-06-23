<?php 
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

require_once(__DIR__ . "/vendor/autoload.php"); 

use BenTools\SplitTestAnalyzer\SplitTestAnalyzer;
use BenTools\SplitTestAnalyzer\Variation;

function abst_split_test_analyzer($data = array(),$test_age = 0){

  if(empty($data))
    return $data;
  
  if(sizeof($data) < 2)
    return $data;
  
  $variations = [];
  $winnerFound = false;
  $test_age = intval($test_age);
  // Minimum visits per variation before a winner can be declared. Keeps the
  // server-side winner flag in sync with the UI's underpowered gate.
  // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Backward compatibility for legacy public filter.
  $min_visits_for_winner = apply_filters('abst_min_visits_for_winner', apply_filters('ab_min_visits_for_winner', 50));
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
  // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Backward compatibility for legacy public filter.
  $percentage_target = apply_filters('abst_complete_confidence', apply_filters('ab_complete_confidence', 95));
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
      $durationFinder[$n]['conversion'] = ($d['conversion'] ?? 0) / $test_age;
      $durationFinder[$n]['visit'] = ($d['visit'] ?? 0) / $test_age;
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
 * Run the appropriate analyzer on each device_size slice of an observations array
 * and stamp the per-size probability (plus derived stats block) back onto each
 * variation's device_size bucket. Does not mutate top-level probabilities.
 *
 * Sizes processed: mobile, tablet, desktop.
 */
function abst_analyze_device_sizes($data, $test_age = 0) {
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
      $slice[$vkey] = $entry;
    }

    if (count($slice) < 2) continue; // analyzer needs >= 2 variations

    $analyzed = abst_split_test_analyzer($slice, 0);

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

// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound -- Backward compatibility for older integrations.
function bt_bb_ab_split_test_analyzer($data = array(), $test_age = 0) {
  return abst_split_test_analyzer($data, $test_age);
}

// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound -- Backward compatibility for older integrations.
function bt_bb_ab_analyze_device_sizes($data, $test_age = 0) {
  return abst_analyze_device_sizes($data, $test_age);
}
