<?php
/**
 * Session Replay Admin Page Template
 */

if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap abst-session-replay-wrap">
    <h1><?php esc_html_e('Session Replay', 'ab-split-test-lite'); ?></h1>
    <p class="description"><?php esc_html_e('Watch recordings of user sessions to understand how visitors interact with your site.', 'ab-split-test-lite'); ?></p>
    
    <div class="abst-session-replay-container">
        <!-- Left Sidebar: Session List -->
        <div class="abst-session-sidebar">
            <!-- Upgrade Nudge -->
            <div class="abst-session-upgrade-nudge">
                <span><?php esc_html_e('Upgrade for session filters and multi-page replay', 'ab-split-test-lite'); ?></span>
                <a href="https://absplittest.com/pricing?ref=upgradefeaturelink" target="_blank"><?php esc_html_e('Try now', 'ab-split-test-lite'); ?> &rarr;</a>
            </div>

            <!-- Session List -->
            <div class="abst-session-list">
                <h3>
                    <?php esc_html_e('Sessions', 'ab-split-test-lite'); ?>
                    <span id="session-count" class="abst-session-count"></span>
                </h3>
                <div id="session-list-container">
                    <p class="abst-loading"><?php esc_html_e('Loading sessions...', 'ab-split-test-lite'); ?></p>
                </div>
                <div id="session-pagination" class="abst-pagination"></div>
            </div>
        </div>
        
        <!-- Main Area: Replay -->
        <div class="abst-session-main">
            <!-- Empty State -->
            <div id="replay-empty-state" class="abst-replay-empty">
                <div class="abst-empty-icon">▶️</div>
                <h2><?php esc_html_e('Select a Session to Replay', 'ab-split-test-lite'); ?></h2>
                <p><?php esc_html_e('Choose a session from the list on the left to watch how a visitor interacted with your site.', 'ab-split-test-lite'); ?></p>
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
