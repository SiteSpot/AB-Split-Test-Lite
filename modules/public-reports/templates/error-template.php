<?php
/**
 * Error Template for Public Reports
 * 
 * @package ABSPLITTEST
 * @since 2.5.0
 */

if (!defined('ABSPATH')) {
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Report Not Available</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f9fafb;
            color: #1f2937;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }
        
        .error-container {
            max-width: 480px;
            text-align: center;
        }
        
        .error-icon {
            width: 80px;
            height: 80px;
            background: #fef2f2;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 24px;
        }
        
        .error-icon svg {
            width: 40px;
            height: 40px;
            color: #ef4444;
        }
        
        h1 {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 12px;
            color: #1f2937;
        }
        
        p {
            font-size: 16px;
            color: #6b7280;
            line-height: 1.6;
            margin-bottom: 24px;
        }
        
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #6366f1;
            text-decoration: none;
            font-weight: 500;
            font-size: 14px;
        }
        
        .back-link:hover {
            text-decoration: underline;
        }
        
        .footer {
            margin-top: 48px;
            font-size: 13px;
            color: #9ca3af;
        }
        
        .footer a {
            color: #10b981;
            text-decoration: none;
        }
        
        .footer a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-icon">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
            </svg>
        </div>
        
        <h1>Report Not Available</h1>
        <p><?php echo esc_html($message); ?></p>
        
        <a href="<?php echo esc_url(home_url()); ?>" class="back-link">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
            </svg>
            Go to homepage
        </a>
        
        <div class="footer">
            <p>Powered by <a href="https://absplittest.com" target="_blank" rel="noopener">AB Split Test</a></p>
        </div>
    </div>
</body>
</html>
