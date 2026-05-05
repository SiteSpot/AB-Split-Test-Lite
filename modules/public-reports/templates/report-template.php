<?php
/**
 * Public Report Template
 * 
 * @package ABSPLITTEST
 * @since 2.5.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Include the variation label helper function
if (!function_exists('abst_get_variation_label')) {
    function abst_get_variation_label($variation, $variation_meta = null) {
        $variation = (string) $variation;
        $variation_label = $variation;
        $alphabet = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z'];
        
        // Check for custom label in variation_meta
        if ($variation_meta && isset($variation_meta[$variation]['label'])) {
            return $variation_meta[$variation]['label'];
        }
        
        // Handle magic test variations
        if (strpos($variation, 'magic-') === 0) {
            $index = (int)str_replace('magic-', '', $variation);
            if ($index === 0) {
                return "Variation A (Original)";
            } elseif (isset($alphabet[$index - 1])) {
                return "Variation " . $alphabet[$index - 1];
            }
        }
        
        // Handle CSS test variations
        if (strpos($variation, 'test-css-') === 0) {
            $parts = explode('-', $variation);
            if (count($parts) >= 3) {
                $index = (int)end($parts);
                if ($index === 1) {
                    return "Variation A (Original)";
                } elseif (isset($alphabet[$index - 2])) {
                    return "Variation " . $alphabet[$index - 2];
                }
            }
        }
        
        // Handle numeric post IDs (full page tests)
        if (is_numeric($variation)) {
            $post_title = get_the_title($variation);
            if ($post_title) {
                return $post_title;
            }
        }
        
        return $variation_label;
    }
}

// Extract data for easier access
$test_name = esc_html($data['test_name']);
$test_status = $data['test_status'];
$test_type = $data['test_type'];
$test_age = $data['test_age'];
$created_date = $data['created_date'];
$total_visits = $data['total_visits'];
$total_conversions = $data['total_conversions'];
$overall_rate = $data['overall_rate'];
$variations = $data['variations'];
$variation_count = $data['variation_count'];
$time_remaining = $data['time_remaining'];
$best_variation = $data['best_variation'];
$best_probability = $data['best_probability'];
$test_winner = $data['test_winner'];
$conversion_use_order_value = $data['conversion_use_order_value'];
$site_name = esc_html($data['site_name']);
$link_data = $data['link_data'];
$conversion_style = $data['conversion_style'];

// Determine status display
$status_class = 'status-running';
$status_text = 'Running';
if ($test_status === 'complete') {
    $status_class = 'status-complete';
    $status_text = 'Complete';
} elseif ($test_status === 'draft') {
    $status_class = 'status-paused';
    $status_text = 'Paused';
}

// Currency symbol for revenue tests
$currency_symbol = '$';
if (function_exists('get_woocommerce_currency_symbol')) {
    $currency_symbol = get_woocommerce_currency_symbol();
}

// Get control variation for uplift calculations using the identify_control_variation function
$control_key = null;
$control_rate = 0;

// Check if we have access to the main plugin class
if (class_exists('Bt_Ab_Tests')) {
    $bt_ab_test = new Bt_Ab_Tests();
    $control_key = $bt_ab_test->identify_control_variation($variations, get_post($data['test_id']));
} else {
    // Fallback to simpler logic if the main class isn't available
    // Magic test: magic-0 is always control
    if (isset($variations['magic-0'])) {
        $control_key = 'magic-0';
    } else {
        // For other test types, use first variation as fallback
        if (!empty($variations)) {
            $first_key = array_key_first($variations);
            $control_key = $first_key;
        }
    }
}

// Set control rate once we have the control key
if ($control_key && isset($variations[$control_key]['rate'])) {
    $control_rate = floatval($variations[$control_key]['rate']);
}

$control_key_cmp = ($control_key !== null && $control_key !== false) ? (string) $control_key : '';
$test_winner_cmp = ($test_winner !== null && $test_winner !== false) ? (string) $test_winner : '';
$best_variation_cmp = ($best_variation !== null && $best_variation !== false) ? (string) $best_variation : '';

// Build goals array for dropdown (used in both table and chart)
$found_goals = [];
if (!empty($data['goals'])) {
    foreach ($data['goals'] as $key => $value) {
        if (is_array($value) && !empty($value)) {
            $goal_label = '';
            if (key($value) == 'page') {
                $goal_label = get_the_title(reset($value));
            } elseif (is_int(key($value))) {
                $goal_label = get_the_title(key($value));
            } else {
                $goal_label = reset($value);
            }
            if (!empty(trim($goal_label))) {
                $found_goals[$key] = ucfirst(key($value)) . " " . $goal_label;
            }
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title><?php echo esc_html( $test_name ); ?> - A/B Test Report</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet"> <!-- phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedStylesheet -- Standalone report page template; wp_enqueue_style() not available here. -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script> <!-- phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedScript,PluginCheck.CodeAnalysis.Offloading.OffloadedContent -- Standalone report page template; wp_enqueue_script() not available here. -->
    <style>
        :root {
            --primary-color: #10b981;
            --primary-dark: #059669;
            --secondary-color: #6366f1;
            --danger-color: #ef4444;
            --warning-color: #f59e0b;
            --text-primary: #1f2937;
            --text-secondary: #6b7280;
            --text-muted: #9ca3af;
            --bg-primary: #ffffff;
            --bg-secondary: #f9fafb;
            --bg-tertiary: #f3f4f6;
            --border-color: #e5e7eb;
            --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
            --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
            --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
            --radius-sm: 6px;
            --radius-md: 8px;
            --radius-lg: 12px;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: var(--bg-secondary);
            color: var(--text-primary);
            line-height: 1.6;
            min-height: 100vh;
        }
        
        .report-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 24px;
        }
        
        /* Header */
        .report-header {
            background: var(--bg-primary);
            border-radius: var(--radius-lg);
            padding: 32px;
            margin-bottom: 24px;
            box-shadow: var(--shadow-md);
            border: 1px solid var(--border-color);
        }
        
        .header-top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 16px;
            flex-wrap: wrap;
            gap: 16px;
        }
        
        .site-badge {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            color: var(--text-secondary);
        }
        
        .site-badge svg {
            width: 20px;
            height: 20px;
        }
        
        .status-badge {
            display: inline-flex;
            align-items: center;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
        }
        
        .status-running {
            background: #dcfce7;
            color: #166534;
        }
        
        .status-complete {
            background: #dbeafe;
            color: #1e40af;
        }
        
        .status-paused {
            background: #fef3c7;
            color: #92400e;
        }
        
        .test-title {
            font-size: 28px;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 8px;
        }
        
        .test-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 16px;
            font-size: 14px;
            color: var(--text-secondary);
        }
        
        .test-meta span {
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }
        
        .stat-card {
            background: var(--bg-primary);
            border-radius: var(--radius-lg);
            padding: 24px;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border-color);
        }
        
        .stat-card-icon {
            width: 48px;
            height: 48px;
            border-radius: var(--radius-md);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 16px;
        }
        
        .stat-card-icon.visitors { background: #dbeafe; color: #2563eb; }
        .stat-card-icon.conversions { background: #dcfce7; color: #16a34a; }
        .stat-card-icon.rate { background: #fef3c7; color: #d97706; }
        .stat-card-icon.variants { background: #f3e8ff; color: #9333ea; }
        
        .stat-card-icon svg {
            width: 24px;
            height: 24px;
        }
        
        .stat-label {
            font-size: 13px;
            color: var(--text-secondary);
            margin-bottom: 4px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .stat-value {
            font-size: 32px;
            font-weight: 700;
            color: var(--text-primary);
        }
        
        /* Winner Banner */
        .winner-banner {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            border-radius: var(--radius-lg);
            padding: 24px 32px;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 16px;
        }
        
        .winner-banner svg {
            width: 48px;
            height: 48px;
            flex-shrink: 0;
        }
        
        .winner-content h3 {
            font-size: 20px;
            font-weight: 700;
            margin-bottom: 4px;
        }
        
        .winner-content p {
            opacity: 0.9;
            font-size: 15px;
        }
        
        /* In Progress Banner */
        .progress-banner {
            background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
            color: white;
            border-radius: var(--radius-lg);
            padding: 24px 32px;
            margin-bottom: 24px;
        }
        
        .progress-banner h3 {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        /* Test Status Container */
        .test-status-container {
            margin-bottom: 24px;
        }
        
        .test-status-bar {
            border-left: 4px solid #4f46e5;
            background: #f8f9fa;
            border-radius: 6px;
            padding: 16px 20px;
        }
        
        .test-status-bar.winner {
            border-left-color: #10b981;
        }
        
        .test-status-bar.running {
            border-left-color: #6366f1;
        }
        
        .status-content {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        
        .status-item {
            margin: 0;
            font-size: 14px;
            line-height: 1.5;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .status-icon {
            font-size: 16px;
            display: inline-block;
            min-width: 20px;
            text-align: center;
        }
        
        /* Variations Table */
        .variations-panel {
            background: var(--bg-primary);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-md);
            border: 1px solid var(--border-color);
            margin-bottom: 24px;
            overflow-x: auto;
        }
        
        .panel-header {
            padding: 20px 24px;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .panel-title {
            font-size: 18px;
            font-weight: 600;
        }
        
        .variations-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .variations-table th {
            text-align: left;
            padding: 14px 24px;
            font-size: 12px;
            font-weight: 600;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            background: var(--bg-secondary);
            border-bottom: 1px solid var(--border-color);
        }
        
        .variations-table td {
            padding: 16px 24px;
            border-bottom: 1px solid var(--border-color);
            font-size: 14px;
        }
        
        .variations-table tr:last-child td {
            border-bottom: none;
        }
        
        .variations-table tr:hover {
            background: var(--bg-secondary);
        }
        
        .variation-name {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .variation-badge {
            display: inline-flex;
            align-items: center;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .badge-control {
            background: #e0e7ff;
            color: #4338ca;
        }
        
        .badge-winner {
            background: #dcfce7;
            color: #166534;
        }
        
        .badge-leading {
            background: #fef3c7;
            color: #92400e;
        }
        
        .rate-value {
            font-weight: 600;
            font-size: 16px;
        }
        
        .uplift-positive {
            color: #16a34a;
            font-weight: 500;
        }
        
        .uplift-negative {
            color: #dc2626;
            font-weight: 500;
        }
        
        .confidence-bar {
            width: 60px;
            min-width: 60px;
            max-width: 60px;
            height: 8px;
            background: var(--bg-tertiary);
            border-radius: 4px;
            overflow: hidden;
            display: inline-block;
            vertical-align: middle;
            margin-right: 8px;
        }
        
        .confidence-fill {
            height: 100%;
            border-radius: 4px;
            transition: width 0.3s ease;
            display: block;
            max-width: 100%;
        }
        
        .confidence-high { background: #10b981; }
        .confidence-medium { background: #f59e0b; }
        .confidence-low { background: #6b7280; }

        .abst-underpowered-badge {
            display: inline-block;
            margin-left: 6px;
            padding: 1px 6px;
            font-size: 10px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            color: #92400e;
            background: #fef3c7;
            border: 1px solid #fde68a;
            border-radius: 10px;
            vertical-align: middle;
        }
        
        /* Chart Panel */
        .chart-panel {
            background: var(--bg-primary);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-md);
            border: 1px solid var(--border-color);
            padding: 24px;
            margin-bottom: 24px;
        }
        
        .chart-container {
            position: relative;
            height: 400px;
        }
        
        .goal-selector {
            padding: 8px 12px;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-sm);
            font-size: 14px;
            background: var(--bg-primary);
            cursor: pointer;
            min-width: 200px;
        }
        
        .goal-selector:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 2px rgba(16, 185, 129, 0.2);
        }
        
        .chart-projection {
            text-align: right;
            margin-top: 12px;
            font-size: 13px;
            color: var(--text-secondary);
            font-style: italic;
        }
        
        /* Heatmaps Section */
        .heatmaps-section {
            background: var(--bg-primary);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-md);
            border: 1px solid var(--border-color);
            margin-bottom: 24px;
            overflow: hidden;
        }
        
        .heatmaps-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 24px;
            padding: 24px;
        }
        
        .heatmap-card {
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            overflow: hidden;
        }
        
        .heatmap-title {
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-weight: 600;
            color: var(--text-primary);
        }
        
        .heatmap-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 24px;
            height: 24px;
            border-radius: 4px;
            text-decoration: none;
            opacity: 0.7;
            transition: all 0.2s ease;
            font-size: 14px;
        }
        
        .heatmap-link:hover {
            opacity: 1;
            background-color: var(--bg-secondary);
        }
        
        .heatmap-header {
            padding: 12px 16px;
            background: var(--bg-secondary);
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .heatmap-stats {
            font-weight: 400;
            font-size: 12px;
            color: var(--text-secondary);
        }
        
        .heatmap-iframe-container {
            position: relative;
            width: 100%;
            height: 250px;
            overflow: hidden;
            background: var(--bg-tertiary);
        }
        
        .heatmap-iframe {
            width: 100%;
            height: 100%;
            border: none;
            transform-origin: top left;
            transform: scale(0.35);
            width: 285.7%;
            height: 285.7%;
        }
        
        .heatmap-placeholder {
            height: 200px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-muted);
            font-size: 14px;
            background: var(--bg-tertiary);
        }
        
        /* Footer */
        .report-footer {
            text-align: center;
            padding: 24px;
            color: var(--text-muted);
            font-size: 13px;
        }
        
        .report-footer a {
            color: var(--primary-color);
            text-decoration: none;
        }
        
        .report-footer a:hover {
            text-decoration: underline;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .report-container {
                padding: 16px;
            }
            
            .report-header {
                padding: 20px;
            }
            
            .test-title {
                font-size: 22px;
            }
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .stat-value {
                font-size: 24px;
            }
            
            .variations-table th,
            .variations-table td {
                padding: 12px 16px;
            }
            
            .chart-container {
                height: 300px;
            }
            
            .heatmaps-grid {
                grid-template-columns: 1fr;
            }
        }
        
        @media (max-width: 480px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .header-top {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="report-container">
        <!-- Header -->
        <header class="report-header">
            <div class="header-top">
                <div class="site-badge">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.004 9.004 0 008.716-6.747M12 21a9.004 9.004 0 01-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 017.843 4.582M12 3a8.997 8.997 0 00-7.843 4.582m15.686 0A11.953 11.953 0 0112 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0121 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0112 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 013 12c0-1.605.42-3.113 1.157-4.418" />
                    </svg>
                    <?php echo esc_html( $site_name ); ?>
                </div>
                <span class="status-badge <?php echo esc_attr( $status_class ); ?>"><?php echo esc_html( $status_text ); ?></span>
            </div>
            
            <h1 class="test-title"><?php echo esc_html( $test_name ); ?></h1>
            
            <div class="test-meta">
                <span>
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" />
                    </svg>
                    Started <?php echo esc_html( gmdate('M j, Y', strtotime($created_date)) ); ?>
                </span>
                <span>
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Running for <?php echo esc_html( $test_age ); ?> days
                </span>
                <?php if ($link_data['expires']): ?>
                <span>
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                    </svg>
                    Link expires <?php echo esc_html( gmdate('M j, Y', strtotime($link_data['expires'])) ); ?>
                </span>
                <?php endif; ?>
            </div>
        </header>
        
        <?php if (!empty($data['experiment_status'])): ?>
            <div class="analysis-section" style="margin: 20px 0; background: #f8f9fa; padding: 15px; border-radius: 5px; border-left: 4px solid #28a745;">
                <?php echo wp_kses_post($data['experiment_status']); ?>
            
        <?php endif; ?>
        
        <!-- Stats Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-card-icon visitors">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
                    </svg>
                </div>
                <div class="stat-label">Total Visitors</div>
                <div class="stat-value"><?php echo number_format($total_visits); ?></div>
            </div>
            
            <div class="stat-card">
                <div class="stat-card-icon conversions">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div class="stat-label">Total <?php echo $conversion_use_order_value ? 'Revenue' : 'Conversions'; ?></div>
                <div class="stat-value">
                    <?php 
                    if ($conversion_use_order_value) {
                        echo esc_html( $currency_symbol ) . esc_html( number_format($total_conversions, 0) );
                    } else {
                        echo esc_html( number_format($total_conversions) );
                    }
                    ?>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-card-icon rate">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18L9 11.25l4.306 4.307a11.95 11.95 0 015.814-5.519l2.74-1.22m0 0l-5.94-2.28m5.94 2.28l-2.28 5.941" />
                    </svg>
                </div>
                <div class="stat-label">Overall <?php echo $conversion_use_order_value ? 'Rev/Visit' : 'Conv. Rate'; ?></div>
                <div class="stat-value">
                    <?php 
                    if ($conversion_use_order_value) {
                        // For revenue tests, calculate revenue per visit
                        $revenue_per_visit = $total_visits > 0 ? ($total_conversions / $total_visits) : 0;
                        echo esc_html( $currency_symbol ) . esc_html( number_format($revenue_per_visit, 2) );
                    } else {
                        // For regular tests, show percentage
                        echo esc_html( $overall_rate ) . '%';
                    }
                    ?>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-card-icon variants">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z" />
                    </svg>
                </div>
                <div class="stat-label">Variants</div>
                <div class="stat-value"><?php echo esc_html( $variation_count ); ?></div>
            </div>
        </div>
        
        <!-- Winner/Progress Banner -->
        <?php if (!empty($test_winner)): ?>
            <?php 
            $winner_label = abst_get_variation_label($test_winner, $data['variation_meta'] ?? null);
            $winner_rate = isset($variations[$test_winner]['rate']) ? $variations[$test_winner]['rate'] : 0;
            $winner_uplift = 0;
            $performance_multiplier = 0;
            if ($control_key && $control_key !== $test_winner && $control_rate > 0) {
                $winner_uplift = (($winner_rate - $control_rate) / $control_rate) * 100;
                $performance_multiplier = ($winner_rate / $control_rate);
            }
            
            // Calculate projected annual impact
            $projected_annual_impact = 0;
            $is_revenue = $conversion_use_order_value;
            if ($winner_uplift > 0 && $total_conversions > 0 && $total_visits > 0) {
                // Estimate annual visits based on current rate
                $daily_visits = $total_visits / max(1, $test_age);
                $annual_visits = $daily_visits * 365;
                
                // For revenue tests, use actual revenue values
                if ($conversion_use_order_value) {
                    $avg_order_value = $total_conversions / max(1, $total_visits);
                    $control_revenue = $annual_visits * $avg_order_value;
                    $projected_annual_impact = $control_revenue * ($winner_uplift / 100);
                } else {
                    // For non-revenue tests, calculate extra conversions per year
                    $daily_conversions = $total_conversions / max(1, $test_age);
                    $annual_conversions = $daily_conversions * 365;
                    $projected_annual_impact = round($annual_conversions * ($winner_uplift / 100));
                }
            }
            ?>
            <div class="test-status-container">
                <div class="test-status-bar winner">
                    <div class="status-content">
                        <p class="status-item">
                            <span class="status-icon">🎉</span> <strong><?php echo esc_html($winner_label); ?></strong> is the winner with <strong>100% confidence</strong>
                        </p>
                        <?php if ($winner_uplift > 0): ?>
                        <p class="status-item">
                            <span class="status-icon">📈</span> <span class="uplift-positive"><?php echo esc_html( round($winner_uplift, 1) ); ?>% increase</span> 
                            <?php if ($conversion_use_order_value): ?>in revenue per visit<?php else: ?>in conversion rate<?php endif; ?>
                            <?php if ($performance_multiplier > 1): ?>
                            (<?php echo esc_html(number_format($performance_multiplier, 1)); ?>x better performance)
                            <?php endif; ?>
                        </p>
                        <?php endif; ?>
                        <?php if ($projected_annual_impact > 0): ?>
                        <p class="status-item">
                            <span class="status-icon">💰</span> Projected annual impact: Extra <strong>
                            <?php if ($is_revenue): ?>
                                <?php echo esc_html($currency_symbol); ?><?php echo esc_html(number_format($projected_annual_impact)); ?>
                            <?php else: ?>
                                <?php echo esc_html(number_format($projected_annual_impact)); ?> conversions
                            <?php endif; ?>
                            per year</strong>
                        </p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php elseif ($test_status === 'publish'): ?>
            <?php 
            // Calculate potential uplift if there's a control
            $potential_uplift = 0;
            $performance_multiplier = 0;
            $leader_label = '';
            $leader_rate = 0;
            $leader_visits = 0;
            $leader_conversions = 0;
            
            if ($best_variation) {
                $leader_label = abst_get_variation_label($best_variation, $data['variation_meta'] ?? null);
                $leader_rate = isset($variations[$best_variation]['rate']) ? $variations[$best_variation]['rate'] : 0;
                $leader_visits = isset($variations[$best_variation]['visits']) ? $variations[$best_variation]['visits'] : 0;
                $leader_conversions = isset($variations[$best_variation]['conversions']) ? $variations[$best_variation]['conversions'] : 0;
                
                if ($control_key && $control_key !== $best_variation && $control_rate > 0) {
                    $potential_uplift = (($leader_rate - $control_rate) / $control_rate) * 100;
                    $performance_multiplier = ($leader_rate / $control_rate);
                }
            }
            
            // Calculate projected annual impact
            $projected_annual_impact = 0;
            $is_revenue = $conversion_use_order_value;
            if ($potential_uplift > 0 && $total_conversions > 0 && $total_visits > 0) {
                // Estimate annual visits based on current rate
                $daily_visits = $total_visits / max(1, $test_age);
                $annual_visits = $daily_visits * 365;
                
                // For revenue tests, use actual revenue values
                if ($conversion_use_order_value) {
                    $avg_order_value = $total_conversions / max(1, $total_visits);
                    $control_revenue = $annual_visits * $avg_order_value;
                    $projected_annual_impact = $control_revenue * ($potential_uplift / 100);
                } else {
                    // For non-revenue tests, calculate extra conversions per year
                    $daily_conversions = $total_conversions / max(1, $test_age);
                    $annual_conversions = $daily_conversions * 365;
                    $projected_annual_impact = round($annual_conversions * ($potential_uplift / 100));
                }
            }
            ?>
            <div class="test-status-container">
                <div class="test-status-bar running">
                    <div class="status-content">
                        <p class="status-item">
                            <span class="status-icon">⏳</span> <strong>Test is running</strong>
                        </p>
                        <?php if ($time_remaining > 0): ?>
                        <p class="status-item">
                            <span class="status-icon">🔍</span> Approximately <strong><?php echo esc_html($time_remaining); ?> days remaining</strong> to reach statistical significance
                        </p>
                        <?php endif; ?>
                        <?php if ($best_variation): ?>
                        <p class="status-item">
                            <span class="status-icon">🎉</span> <strong><?php echo esc_html($leader_label); ?></strong> is leading with <strong><?php echo esc_html(round($best_probability, 0)); ?>% confidence</strong>
                        </p>
                        <?php endif; ?>
                        <?php if ($potential_uplift > 0): ?>
                        <p class="status-item">
                            <span class="status-icon">📈</span> <span class="uplift-positive"><?php echo esc_html(round($potential_uplift, 1)); ?>% potential improvement</span> over control
                            <?php if ($performance_multiplier > 1): ?>
                            (<?php echo esc_html(number_format($performance_multiplier, 1)); ?>x better performance)
                            <?php endif; ?>
                        </p>
                        <?php endif; ?>
                        <?php if ($projected_annual_impact > 0): ?>
                        <p class="status-item">
                            <span class="status-icon">💰</span> Projected annual impact: Extra <strong>
                            <?php if ($is_revenue): ?>
                                <?php echo esc_html($currency_symbol); ?><?php echo esc_html(number_format($projected_annual_impact)); ?>
                            <?php else: ?>
                                <?php echo esc_html(number_format($projected_annual_impact)); ?> conversions
                            <?php endif; ?>
                            per year</strong>
                        </p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Variations Table -->
        <div class="variations-panel">
            <div class="panel-header">
                <h2 class="panel-title">Variation Performance</h2>
                <div style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
                    <select id="abst-device-size-select" class="goal-selector">
                        <option value="">All devices</option>
                        <option value="mobile">Mobile</option>
                        <option value="tablet">Tablet</option>
                        <option value="desktop">Desktop</option>
                    </select>
                    <?php if (!empty($found_goals)): ?>
                    <select id="table-goal-selector" class="goal-selector">
                        <option value="">Primary Conversions</option>
                        <?php foreach ($found_goals as $key => $goal): ?>
                            <option value="subgoal<?php echo esc_attr($key); ?>">Subgoal <?php echo esc_attr($key); ?>: <?php echo esc_html($goal); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php endif; ?>
                </div>
            </div>
            <div id="abst-device-warning" style="display: none; padding: 8px 12px; margin: 0 0 12px 0; background: #fef3c7; color: #92400e; border-radius: 6px; font-size: 13px;"></div>
            <table class="variations-table">
                <thead>
                    <tr>
                        <th>Variant</th>
                        <th>Visitors</th>
                        <th>Conversions</th>
                        <th><?php echo $conversion_use_order_value ? 'Revenue/Visit' : 'Conv. Rate'; ?></th>
                        <th>vs Control</th>
                        <th><?php echo $conversion_style === 'thompson' ? 'Weight' : 'Confidence'; ?></th>
                        <?php foreach ($found_goals as $goalNum => $goalLabel): ?>
                        <th class="subgoal-column subgoal-<?php echo esc_attr($goalNum); ?>-col" style="display: none;">Subgoal <?php echo esc_attr($goalNum); ?></th>
                        <th class="subgoal-column subgoal-<?php echo esc_attr($goalNum); ?>-col" style="display: none;">Subgoal Rate</th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($variations as $key => $var): ?>
                        <?php
                        $key_cmp = (string) $key;
                        $is_control = ($key_cmp === $control_key_cmp);
                        $is_winner = ($test_winner_cmp !== '' && $key_cmp === $test_winner_cmp);
                        $is_leading = ($best_variation_cmp !== '' && $key_cmp === $best_variation_cmp && $test_winner_cmp === '');
                        
                        // Calculate uplift
                        $uplift = 0;
                        if (!$is_control && $control_rate > 0) {
                            $uplift = (($var['rate'] - $control_rate) / $control_rate) * 100;
                        }
                        
                        // Confidence class - use weight for Thompson mode, probability for Bayesian
                        $conf_value = ($conversion_style === 'thompson') ? ($var['weight'] ?? 0) : $var['probability'];
                        $conf_class = 'confidence-low';
                        if ($conf_value >= 95) {
                            $conf_class = 'confidence-high';
                        } elseif ($conf_value >= 70) {
                            $conf_class = 'confidence-medium';
                        }
                        ?>
                        <tr data-variation-key="<?php echo esc_attr($key); ?>" data-is-control="<?php echo $is_control ? '1' : '0'; ?>">
                            <td class="cell-variation">
                                <div class="variation-name">
                                    <?php echo esc_html($var['label']); ?>
                                    <?php if ($is_control): ?>
                                        <span class="variation-badge badge-control">Control</span>
                                    <?php endif; ?>
                                    <span class="cell-winner-badge">
                                        <?php if ($is_winner): ?>
                                            <span class="variation-badge badge-winner">Winner</span>
                                        <?php elseif ($is_leading): ?>
                                            <span class="variation-badge badge-leading">Leading</span>
                                        <?php endif; ?>
                                    </span>
                                </div>
                            </td>
                            <td class="cell-visits"><?php echo esc_html( number_format($var['visits']) ); ?></td>
                            <td class="cell-conversions">
                                <?php
                                if ($conversion_use_order_value) {
                                    echo esc_html( $currency_symbol ) . esc_html( number_format($var['conversions'], 0) );
                                } else {
                                    echo esc_html( number_format($var['conversions']) );
                                }
                                ?>
                            </td>
                            <td class="cell-rate">
                                <span class="rate-value">
                                    <?php
                                    if ($conversion_use_order_value) {
                                        echo esc_html( $currency_symbol ) . esc_html( number_format($var['rate'] / 100, 2) );
                                    } else {
                                        echo esc_html( round($var['rate'], 2) ) . '%';
                                    }
                                    ?>
                                </span>
                            </td>
                            <td class="cell-uplift">
                                <?php if ($is_control): ?>
                                    <span style="color: var(--text-muted);">—</span>
                                <?php elseif ($control_rate == 0): ?>
                                    <span style="color: var(--text-muted);">—</span>
                                <?php elseif ($uplift > 0): ?>
                                    <span class="uplift-positive">+<?php echo esc_html( round($uplift, 1) ); ?>%</span>
                                <?php elseif ($uplift < 0): ?>
                                    <span class="uplift-negative"><?php echo esc_html( round($uplift, 1) ); ?>%</span>
                                <?php else: ?>
                                    <span style="color: var(--text-muted);">0%</span>
                                <?php endif; ?>
                            </td>
                            <td class="cell-confidence">
                                <?php
                                $display_value = ($conversion_style === 'thompson') ? ($var['weight'] ?? 0) : $var['probability'];
                                ?>
                                <span class="confidence-bar">
                                    <span class="confidence-fill <?php echo esc_attr( $conf_class ); ?>" style="width: <?php echo esc_attr( min(100, $display_value) ); ?>%"></span>
                                </span>
                                <?php echo esc_html( round($display_value, 1) ); ?>%
                            </td>
                            <?php 
                            // Subgoal columns - hidden by default, shown when subgoal selected
                            foreach ($found_goals as $goalNum => $goalLabel): 
                                $goalCount = isset($var['goals'][$goalNum]) ? $var['goals'][$goalNum] : 0;
                                $goalRate = $var['visits'] > 0 ? round(($goalCount / $var['visits']) * 100, 2) : 0;
                            ?>
                            <td class="subgoal-column subgoal-<?php echo esc_attr($goalNum); ?>-col" style="display: none;">
                                <?php echo esc_html(number_format($goalCount)); ?>
                            </td>
                            <td class="subgoal-column subgoal-<?php echo esc_attr($goalNum); ?>-col" style="display: none;">
                                <?php echo esc_html($goalRate); ?>%
                            </td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Chart -->
        <div class="chart-panel">
            <div class="panel-header" style="padding: 0; border: none; display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h2 class="panel-title">Visits vs Conversions</h2>
                <?php if (!empty($found_goals)): ?>
                <select id="goal-selector" class="goal-selector">
                    <option value="">Primary Conversions</option>
                    <?php foreach ($found_goals as $key => $goal): ?>
                        <option value="subgoal<?php echo esc_attr($key); ?>">Subgoal <?php echo esc_attr($key); ?>: <?php echo esc_html($goal); ?></option>
                    <?php endforeach; ?>
                </select>
                <?php endif; ?>
            </div>
            <div class="chart-container">
                <canvas id="abtestChart"></canvas>
            </div>
            <?php if ($data['likely_duration'] > 0 && $data['time_remaining'] > 0): ?>
            <div class="chart-projection">
                <?php if ($data['likely_duration'] >= 999 && $test_age >= 1): ?>
                    Variations are very close - test may take a long time to reach statistical significance
                <?php elseif ($data['likely_duration'] < 999): ?>
                    Projected end date: <?php echo esc_html( gmdate('F jS Y', strtotime('+' . $data['time_remaining'] . ' days')) ); ?>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Heatmaps Section -->
        <?php if (!empty($variations) && count($variations) > 0 && !empty($data['page_url'])): ?>
        <div class="heatmaps-section">
            <div class="panel-header">
                <h2 class="panel-title">Variation Previews</h2>
            </div>
            <div class="heatmaps-grid">
                <?php foreach ($variations as $key => $var): 
                    // Build iframe URL with variation preview parameters
                    $iframe_url = $data['page_url'];
                    $iframe_url = add_query_arg('abst_heatmap_view', '1', $iframe_url);
                    $iframe_url = add_query_arg('abtid', $data['test_id'], $iframe_url);
                    $iframe_url = add_query_arg('abtv', $key, $iframe_url);
                ?>
                <div class="heatmap-card">
                    <div class="heatmap-header">
                        <div class="heatmap-title">
                            <?php echo esc_html($var['label']); ?>
                            <?php 
                            // Add heatmap link if heatmaps are enabled
                            $heatmaps_enabled = abst_get_admin_setting('abst_enable_user_journeys') == '1';
                            if ($heatmaps_enabled && !empty($data['page_url'])):
                                // Build heatmap URL
                                $heatmap_url = admin_url('edit.php?post_type=bt_experiments&page=abst-heatmaps');
                                $heatmap_url = add_query_arg('post', $data['test_id'], $heatmap_url);
                                $heatmap_url = add_query_arg('eid', $data['test_id'], $heatmap_url);
                                $heatmap_url = add_query_arg('variation', $key, $heatmap_url);
                                $heatmap_url = add_query_arg('size', 'large', $heatmap_url);
                                $heatmap_url = add_query_arg('mode', 'clicks', $heatmap_url);
                            ?>
                                <a href="<?php echo esc_url($heatmap_url); ?>" class="heatmap-link" title="View Heatmap" target="_blank">
                                    🔥
                                </a>
                            <?php endif; ?>
                        </div>
                        <span class="heatmap-stats"><?php echo number_format($var['visits']); ?> visits • <?php echo esc_html(round($var['rate'], 2)); ?>%</span>
                    </div>
                    <div class="heatmap-iframe-container">
                        <iframe 
                            src="<?php echo esc_url($iframe_url); ?>" 
                            class="heatmap-iframe"
                            loading="lazy"
                            sandbox="allow-same-origin allow-scripts"
                        ></iframe>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Footer -->
        <footer class="report-footer">
            <p>Generated on <?php echo esc_html( gmdate('M j, Y') ); ?></p>
        </footer>
        </div>
    </div>
    
    <script>
        // Chart data from PHP
        var abtestChartData = <?php echo json_encode($data['observations_raw']); ?>;
        var testAge = <?php echo intval($test_age); ?>;
        var likelyDuration = <?php echo intval($data['likely_duration'] ?? 0); ?>;
        var abtestChart = null;
        var abstControlKey = <?php echo json_encode((string)$control_key); ?>;
        var abstUseOrderValue = <?php echo $conversion_use_order_value ? 'true' : 'false'; ?>;
        var abstCurrencySymbol = <?php echo json_encode($currency_symbol); ?>;
        var abstConversionStyle = <?php echo json_encode($conversion_style); ?>;
        var abstTestWinner = <?php echo json_encode((string)$test_winner); ?>;
        var abstMinVisitsPerVariation = 50; // below this, don't compute chance of winning
        var abstConfidenceThreshold = 95;   // matches includes/statistics.php winner threshold
        var abstCurrentDeviceSize = '';
        var abstDeviceSizeStats = (abtestChartData && abtestChartData.bt_bb_ab_stats && abtestChartData.bt_bb_ab_stats.device_size)
            ? abtestChartData.bt_bb_ab_stats.device_size
            : {};
        // Variation labels map (key => label) for chart legend
        var variationLabels = <?php
            $labels_map = [];
            foreach ($variations as $key => $var) {
                $labels_map[$key] = $var['label'];
            }
            echo json_encode($labels_map);
        ?>;

        function abstGetSliceWinnerKey(deviceSize, slice) {
            if (!deviceSize) {
                return abstTestWinner;
            }

            var sizeStats = abstDeviceSizeStats[deviceSize];
            if (!sizeStats || !sizeStats.best || slice.insufficient || slice.underpowered) {
                return '';
            }

            return sizeStats.best;
        }

        // Build a per-variation slice for the selected device size (or full data if none).
        // insufficient: any variation has <50 visits.
        // underpowered: all variations >=50 visits but top probability <95%.
        function abstBuildDeviceSlice(deviceSize) {
            var sliced = {};
            var insufficient = false;
            var maxProb = 0;
            for (var key in abtestChartData) {
                if (!abtestChartData.hasOwnProperty(key)) continue;
                if (key === 'bt_bb_ab_stats' || key === 'conversion_style' || key === 'test_type' || key === 'test_winner' || key === 'goals' || key === 'device_size_winners') continue;
                var v = abtestChartData[key];
                if (!v || typeof v.visit === 'undefined') continue;

                var row;
                if (deviceSize && v.device_size && v.device_size[deviceSize]) {
                    var ds = v.device_size[deviceSize];
                    row = {
                        visit: ds.visit || 0,
                        conversion: ds.conversion || 0,
                        rate: ds.rate || 0,
                        probability: ds.probability || 0,
                        goals: ds.goals || {}
                    };
                } else if (deviceSize) {
                    row = { visit: 0, conversion: 0, rate: 0, probability: 0, goals: {} };
                } else {
                    row = {
                        visit: v.visit || 0,
                        conversion: v.conversion || 0,
                        rate: v.rate || 0,
                        probability: v.probability || 0,
                        goals: v.goals || {}
                    };
                }
                sliced[key] = row;
                if (row.visit < abstMinVisitsPerVariation) {
                    insufficient = true;
                }
                if (row.probability > maxProb) {
                    maxProb = row.probability;
                }
            }
            var underpowered = (!insufficient && maxProb < abstConfidenceThreshold && abstConversionStyle !== 'thompson');
            return { sliced: sliced, insufficient: insufficient, underpowered: underpowered };
        }

        // Apply the selected device size to the table rows.
        function applyDeviceSizeToTable(deviceSize, goal) {
            var slice = abstBuildDeviceSlice(deviceSize);
            var sliced = slice.sliced;
            var insufficient = slice.insufficient;
            var underpowered = slice.underpowered;
            var goalNumber = (goal && goal.indexOf('subgoal') === 0) ? goal.replace('subgoal', '') : '';
            var controlRate = 0;
            if (abstControlKey && sliced[abstControlKey]) {
                controlRate = sliced[abstControlKey].rate || 0;
                if (goalNumber !== '') {
                    var controlVisits = sliced[abstControlKey].visit || 0;
                    var controlGoalConversions = (sliced[abstControlKey].goals && typeof sliced[abstControlKey].goals[goalNumber] !== 'undefined')
                        ? sliced[abstControlKey].goals[goalNumber]
                        : 0;
                    controlRate = controlVisits > 0 ? (controlGoalConversions / controlVisits) * 100 : 0;
                }
            }

            // Determine winner for this slice
            var sliceWinner = abstGetSliceWinnerKey(deviceSize, slice);

            // Find the leading key for "Leading" badge when no winner.
            // Match the server-side report logic by preferring the highest
            // probability-to-win; if probabilities are unavailable for the
            // current slice, fall back to the highest observed rate.
            var leadingKey = '';
            if (!sliceWinner) {
                var bestProbability = -Infinity;
                for (var k in sliced) {
                    if (!sliced.hasOwnProperty(k)) continue;
                    var probability = sliced[k].probability || 0;
                    if (probability > bestProbability) {
                        bestProbability = probability;
                        leadingKey = k;
                    }
                }

                if (!leadingKey || bestProbability <= 0) {
                    var bestRate = -Infinity;
                    for (var rateKey in sliced) {
                        if (!sliced.hasOwnProperty(rateKey)) continue;
                        if (sliced[rateKey].rate > bestRate) {
                            bestRate = sliced[rateKey].rate;
                            leadingKey = rateKey;
                        }
                    }
                }
            }

            var rows = document.querySelectorAll('tr[data-variation-key]');
            rows.forEach(function(row) {
                var key = row.getAttribute('data-variation-key');
                if (!sliced[key]) return;
                var s = sliced[key];
                var isControl = row.getAttribute('data-is-control') === '1';

                var visits = s.visit;
                var conversions = s.conversion;
                var rate = s.rate;
                if (goalNumber !== '' && s.goals && typeof s.goals[goalNumber] !== 'undefined') {
                    conversions = s.goals[goalNumber];
                    rate = visits > 0 ? (conversions / visits) * 100 : 0;
                }

                var visitsCell = row.querySelector('.cell-visits');
                if (visitsCell) visitsCell.textContent = visits.toLocaleString();

                var convCell = row.querySelector('.cell-conversions');
                if (convCell) {
                    if (abstUseOrderValue) {
                        convCell.textContent = abstCurrencySymbol + Math.round(conversions).toLocaleString();
                    } else {
                        convCell.textContent = conversions.toLocaleString();
                    }
                }

                var rateCell = row.querySelector('.cell-rate');
                if (rateCell) {
                    var rateText;
                    if (abstUseOrderValue) {
                        rateText = abstCurrencySymbol + (rate / 100).toFixed(2);
                    } else {
                        rateText = (Math.round(rate * 100) / 100) + '%';
                    }
                    rateCell.innerHTML = '<span class="rate-value">' + rateText + '</span>';
                }

                var upliftCell = row.querySelector('.cell-uplift');
                if (upliftCell) {
                    if (isControl) {
                        upliftCell.innerHTML = '<span style="color: var(--text-muted);">—</span>';
                    } else if (!controlRate || controlRate === 0) {
                        upliftCell.innerHTML = '<span style="color: var(--text-muted);">—</span>';
                    } else {
                        var uplift = ((rate - controlRate) / controlRate) * 100;
                        var upliftRounded = Math.round(uplift * 10) / 10;
                        if (uplift > 0) upliftCell.innerHTML = '<span class="uplift-positive">+' + upliftRounded + '%</span>';
                        else if (uplift < 0) upliftCell.innerHTML = '<span class="uplift-negative">' + upliftRounded + '%</span>';
                        else upliftCell.innerHTML = '<span style="color: var(--text-muted);">0%</span>';
                    }
                }

                var confCell = row.querySelector('.cell-confidence');
                if (confCell) {
                    // Thompson weight column represents traffic allocation, not confidence.
                    // Keep server-rendered value for "All devices"; blank it when filtering since
                    // Thompson weight is not tracked per size.
                    if (abstConversionStyle === 'thompson') {
                        if (deviceSize) {
                            confCell.innerHTML = '<span style="color: var(--text-muted);">—</span>';
                        }
                        // else: leave as server-rendered
                    } else if (insufficient) {
                        confCell.innerHTML = '<span style="color: var(--text-muted);">—</span>';
                    } else {
                        var confValue = s.probability || 0;
                        var confClass = 'confidence-low';
                        if (confValue >= 95) confClass = 'confidence-high';
                        else if (confValue >= 70) confClass = 'confidence-medium';
                        var confHtml = '<span class="confidence-bar"><span class="confidence-fill ' + confClass + '" style="width: ' + Math.min(100, confValue) + '%"></span></span> ' + (Math.round(confValue * 10) / 10) + '%';
                        if (underpowered) {
                            confHtml += ' <span class="abst-underpowered-badge" title="Below the ' + abstConfidenceThreshold + '% confidence threshold. Keep the test running.">Underpowered</span>';
                        }
                        confCell.innerHTML = confHtml;
                    }
                }

                // Update winner / leading badge
                var winnerBadge = row.querySelector('.cell-winner-badge');
                if (winnerBadge) {
                    if (sliceWinner && key === sliceWinner) {
                        winnerBadge.innerHTML = '<span class="variation-badge badge-winner">Winner</span>';
                    } else if (!sliceWinner && key === leadingKey) {
                        winnerBadge.innerHTML = '<span class="variation-badge badge-leading">Leading</span>';
                    } else {
                        winnerBadge.innerHTML = '';
                    }
                }
            });

            // Warning for insufficient data
            var warn = document.getElementById('abst-device-warning');
            if (warn) {
                if (insufficient && abstConversionStyle !== 'thompson') {
                    warn.textContent = 'Not enough data' + (deviceSize ? ' for this device size' : '') + ' (need ' + abstMinVisitsPerVariation + '+ visits per variation). Confidence hidden.';
                    warn.style.display = '';
                } else {
                    warn.style.display = 'none';
                }
            }
        }

        // Create the scatter chart (visits vs conversions)
        function createGraph(goal) {
            goal = goal || 0;
            if (!abtestChartData) return;

            if (abtestChart && typeof abtestChart.destroy === 'function') {
                abtestChart.destroy();
            }

            var slice = abstBuildDeviceSlice(abstCurrentDeviceSize);
            var observations = {};
            for (var k in slice.sliced) {
                observations[k] = {
                    visit: slice.sliced[k].visit,
                    conversion: slice.sliced[k].conversion,
                    goals: slice.sliced[k].goals
                };
            }
            
            // If goal selected, use goal conversions instead
            if (goal) {
                const goalNumber = goal.replace('subgoal', '');
                for (var key in observations) {
                    if (observations[key].goals && observations[key].goals[goalNumber]) {
                        observations[key].conversion = observations[key].goals[goalNumber];
                    }
                }
            }
            
            // Color palette
            var colorPalette = [
                'rgba(16, 185, 129, 1)',   // Green
                'rgba(99, 102, 241, 1)',   // Indigo
                'rgba(245, 158, 11, 1)',   // Amber
                'rgba(239, 68, 68, 1)',    // Red
                'rgba(139, 92, 246, 1)',   // Purple
                'rgba(236, 72, 153, 1)',   // Pink
                'rgba(6, 182, 212, 1)',    // Cyan
                'rgba(249, 115, 22, 1)'    // Orange
            ];
            
            var datasets = [];
            var colorIndex = 0;
            
            for (var key in observations) {
                if (key === 'bt_bb_ab_stats' || key === 'conversion_style' || key === 'test_type' || key === 'test_winner' || key === 'goals') continue;
                
                var variant = observations[key];
                if (!variant || typeof variant.visit === 'undefined') continue;
                
                var color = colorPalette[colorIndex % colorPalette.length];
                var projectedColor = color.replace('1)', '0.4)');
                colorIndex++;
                
                var currentVisits = variant.visit || 0;
                var currentConversions = variant.conversion || 0;
                
                // Get label from PHP-generated labels map (handles page titles for full page tests)
                var labelText = variationLabels[key] || key;
                if (!variationLabels[key]) {
                    // Fallback for magic tests if not in labels map
                    if (variant.variation_meta && variant.variation_meta.label) {
                        labelText = variant.variation_meta.label;
                    } else if (key.startsWith('magic-')) {
                        var letters = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J'];
                        var idx = parseInt(key.replace('magic-', ''));
                        labelText = 'Variation ' + (letters[idx] || idx);
                        if (idx === 0) labelText += ' (Original)';
                    }
                }
                
                // Historical data points (0,0 to current)
                var historicalDataPoints = [
                    { x: 0, y: 0 },
                    { x: currentVisits, y: currentConversions }
                ];
                
                datasets.push({
                    label: labelText,
                    data: historicalDataPoints,
                    showLine: true,
                    fill: false,
                    backgroundColor: color,
                    borderColor: color,
                    pointRadius: 6,
                    pointHoverRadius: 8,
                    tension: 0.1
                });
                
                // Projected data (if we have duration estimate)
                if (likelyDuration > 0 && testAge > 0) {
                    var avgDailyVisits = currentVisits / testAge;
                    var avgDailyConversions = currentConversions / testAge;
                    var projectedVisits = avgDailyVisits * likelyDuration;
                    var projectedConversions = avgDailyConversions * likelyDuration;
                    
                    datasets.push({
                        label: labelText + ' (Projected)',
                        data: [
                            { x: currentVisits, y: currentConversions },
                            { x: projectedVisits, y: projectedConversions }
                        ],
                        showLine: true,
                        fill: false,
                        backgroundColor: projectedColor,
                        borderColor: projectedColor,
                        borderDash: [5, 5],
                        pointRadius: 5,
                        tension: 0.1
                    });
                }
            }
            
            var ctx = document.getElementById('abtestChart').getContext('2d');
            
            abtestChart = new Chart(ctx, {
                type: 'scatter',
                data: { datasets: datasets },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top',
                            labels: {
                                usePointStyle: true,
                                padding: 15,
                                filter: function(item) {
                                    return !item.text.includes('(Projected)');
                                }
                            }
                        },
                        tooltip: {
                            backgroundColor: 'rgba(0, 0, 0, 0.8)',
                            padding: 12,
                            callbacks: {
                                label: function(context) {
                                    return context.dataset.label + ': ' + Math.round(context.parsed.x) + ' visits, ' + Math.round(context.parsed.y) + ' conversions';
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            type: 'linear',
                            position: 'bottom',
                            title: {
                                display: true,
                                text: 'Visits',
                                font: { weight: 'bold' }
                            },
                            beginAtZero: true,
                            grid: { color: '#f3f4f6' }
                        },
                        y: {
                            title: {
                                display: true,
                                text: goal ? 'Subgoal Conversions' : 'Conversions',
                                font: { weight: 'bold' }
                            },
                            beginAtZero: true,
                            grid: { color: '#f3f4f6' }
                        }
                    }
                }
            });
        }
        
        // Show/hide subgoal columns based on selected goal
        function updateTableForGoal(goal) {
            var isSubgoal = goal && goal !== '';
            var goalNumber = isSubgoal ? goal.replace('subgoal', '') : '';
            
            // Hide all subgoal columns first
            document.querySelectorAll('.subgoal-column').forEach(function(col) {
                col.style.display = 'none';
            });
            
            // Show the selected subgoal columns
            if (isSubgoal) {
                document.querySelectorAll('.subgoal-' + goalNumber + '-col').forEach(function(col) {
                    col.style.display = '';
                });
                
                // Update header text for the visible subgoal columns
                var headers = document.querySelectorAll('th.subgoal-' + goalNumber + '-col');
                if (headers.length >= 2) {
                    var selectedOption = document.querySelector('#table-goal-selector option:checked');
                    var goalLabel = selectedOption ? selectedOption.textContent : 'Subgoal ' + goalNumber;
                    headers[0].textContent = goalLabel;
                    headers[1].textContent = 'Subgoal Rate';
                }
            }
        }
        
        // Sync both selectors and update table + chart
        function handleGoalChange(selectedValue) {
            // Update both selectors to match
            var tableSelector = document.getElementById('table-goal-selector');
            var chartSelector = document.getElementById('goal-selector');

            if (tableSelector) tableSelector.value = selectedValue;
            if (chartSelector) chartSelector.value = selectedValue;

            // Update table and chart
            updateTableForGoal(selectedValue);
            applyDeviceSizeToTable(abstCurrentDeviceSize, selectedValue);
            createGraph(selectedValue);
        }

        function abstGetCurrentGoal() {
            var tableSelector = document.getElementById('table-goal-selector');
            if (tableSelector && tableSelector.value) return tableSelector.value;
            var chartSelector = document.getElementById('goal-selector');
            if (chartSelector && chartSelector.value) return chartSelector.value;
            return '';
        }

        function abstHandleDeviceSizeChange(newSize) {
            abstCurrentDeviceSize = newSize || '';

            // Update URL deep-link
            try {
                var url = new URL(window.location.href);
                if (abstCurrentDeviceSize) {
                    url.searchParams.set('abst_device_size', abstCurrentDeviceSize);
                } else {
                    url.searchParams.delete('abst_device_size');
                }
                history.replaceState(null, '', url.toString());
            } catch (e) { /* older browsers */ }

            applyDeviceSizeToTable(abstCurrentDeviceSize, abstGetCurrentGoal());
            createGraph(abstGetCurrentGoal());
        }

        // Initialize chart
        document.addEventListener('DOMContentLoaded', function() {
            // Read initial device size from URL
            try {
                var params = new URLSearchParams(window.location.search);
                var urlSize = params.get('abst_device_size');
                if (urlSize && ['mobile','tablet','desktop'].indexOf(urlSize) !== -1) {
                    abstCurrentDeviceSize = urlSize;
                    var sel = document.getElementById('abst-device-size-select');
                    if (sel) sel.value = urlSize;
                }
            } catch (e) { /* noop */ }

            // Always apply once on load so the gate + underpowered badge are in sync
            // with the server-rendered rows (handles the "All devices" case too).
            applyDeviceSizeToTable(abstCurrentDeviceSize, '');
            createGraph();

            // Table goal selector change handler
            var tableGoalSelector = document.getElementById('table-goal-selector');
            if (tableGoalSelector) {
                tableGoalSelector.addEventListener('change', function() {
                    handleGoalChange(this.value);
                });
            }

            // Chart goal selector change handler
            var chartGoalSelector = document.getElementById('goal-selector');
            if (chartGoalSelector) {
                chartGoalSelector.addEventListener('change', function() {
                    handleGoalChange(this.value);
                });
            }

            // Device size selector change handler
            var deviceSelector = document.getElementById('abst-device-size-select');
            if (deviceSelector) {
                deviceSelector.addEventListener('change', function() {
                    abstHandleDeviceSizeChange(this.value);
                });
            }
        });
    </script>
</body>
</html>
