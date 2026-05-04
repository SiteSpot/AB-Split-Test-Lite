<?php
/**
 * Session Replay Admin Page Template
 */

if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap abst-session-replay-wrap">
    <h1><?php _e('Session Replay', 'bt-bb-ab'); ?></h1>
    <p class="description"><?php _e('Watch recordings of user sessions to understand how visitors interact with your site.', 'bt-bb-ab'); ?></p>
    
    <div class="abst-session-replay-container">
        <!-- Left Sidebar: Filters + Session List -->
        <div class="abst-session-sidebar">
            <!-- Filters -->
            <div class="abst-session-filters">
                <h3><?php _e('Filters', 'bt-bb-ab'); ?></h3>
                
                <p><strong>Advanced Filters Available:</strong></p>
                <ul style="margin-left: 20px; list-style-type: disc;">
                    <li><strong>Min Pages</strong> - Filter sessions by minimum number of pages visited</li>
                    <li><strong>Visited Page</strong> - Filter sessions that visited a specific page</li>
                    <li><strong>Saw Test</strong> - Filter sessions that viewed a specific A/B test</li>
                    <li><strong>Conversion</strong> - Filter by converted or not converted sessions</li>
                    <li><strong>Device</strong> - Filter by device type (Desktop, Tablet, Mobile)</li>
                    <li><strong>Has Rage Clicks</strong> - Filter sessions with rage click behavior</li>
                    <li><strong>Date Range</strong> - Filter sessions by date range</li>
                    <li><strong>Referrer</strong> - Filter by traffic source/referrer</li>
                    <li><strong>UTM Source</strong> - Filter by UTM source parameter</li>
                    <li><strong>UTM Medium</strong> - Filter by UTM medium parameter</li>
                    <li><strong>UTM Campaign</strong> - Filter by UTM campaign parameter</li>
                </ul>
                
                <p style="margin-top: 15px;">
                    <a href="https://absplittest.com/pricing?ref=upgradefeaturelink" target="_blank" class="button button-secondary">
                        <?php _e('Upgrade to enable advanced filters', 'bt-bb-ab'); ?>
                    </a>
                </p>
            </div>
            
            <!-- Session List -->
            <div class="abst-session-list">
                <h3>
                    <?php _e('Sessions', 'bt-bb-ab'); ?>
                    <span id="session-count" class="abst-session-count"></span>
                </h3>
                <div id="session-list-container">
                    <p class="abst-loading"><?php _e('Loading sessions...', 'bt-bb-ab'); ?></p>
                </div>
                <div id="session-pagination" class="abst-pagination"></div>
            </div>
        </div>
        
        <!-- Main Area: Replay -->
        <div class="abst-session-main">
            <!-- Empty State -->
            <div id="replay-empty-state" class="abst-replay-empty">
                <div class="abst-empty-icon">▶️</div>
                <h2><?php _e('Select a Session to Replay', 'bt-bb-ab'); ?></h2>
                <p><?php _e('Choose a session from the list on the left to watch how a visitor interacted with your site.', 'bt-bb-ab'); ?></p>
            </div>
            
            <!-- Replay Area (hidden until session selected) -->
            <div id="replay-area" class="abst-replay-area" style="display: none;">
                <!-- Session Info Bar -->
                <div class="abst-session-info-bar">
                    <span id="replay-session-info"></span>
                </div>
                
                <!-- Iframe Container -->
                <div class="abst-replay-iframe-wrapper">
                    <iframe id="replay-iframe" src="about:blank"></iframe>
                    <div id="replay-cursor" class="abst-replay-cursor"></div>
                    <div id="replay-overlay" class="abst-replay-overlay"></div>
                </div>
                
                <!-- Playback Controls -->
                <div class="abst-playback-controls">
                    <button type="button" id="btn-play" class="abst-btn-play" title="Play">
                        <span class="play-icon">▶</span>
                        <span class="pause-icon" style="display:none;">⏸</span>
                    </button>
                    <button type="button" id="btn-prev" class="abst-btn-nav" title="Previous Event">◀◀</button>
                    <button type="button" id="btn-next" class="abst-btn-nav" title="Next Event">▶▶</button>
                    
                    <div class="abst-speed-controls">
                        <button type="button" class="abst-btn-speed active" data-speed="1">1x</button>
                        <button type="button" class="abst-btn-speed" data-speed="2">2x</button>
                        <button type="button" class="abst-btn-speed" data-speed="4">4x</button>
                    </div>
                    
                    <div class="abst-time-display">
                        <span id="current-time">0:00</span>
                        <span>/</span>
                        <span id="total-time">0:00</span>
                    </div>
                </div>
                
                <!-- Timeline -->
                <div class="abst-timeline-container">
                    <div id="replay-timeline" class="abst-timeline">
                        <div class="abst-timeline-track"></div>
                        <div id="timeline-playhead" class="abst-timeline-playhead"></div>
                        <div id="timeline-events" class="abst-timeline-events"></div>
                    </div>
                    <div id="timeline-pages" class="abst-timeline-pages"></div>
                </div>
            </div>
        </div>
    </div>
</div>
