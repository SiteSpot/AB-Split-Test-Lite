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
        <!-- Left Sidebar: Filters + Session List -->
        <div class="abst-session-sidebar">
            <!-- Filters -->
            <details class="abst-session-filters">
                <summary>
                    <span class="abst-session-filters-title"><?php esc_html_e('Filters', 'ab-split-test-lite'); ?></span>
                    <span id="session-filter-summary" class="abst-session-filter-summary"><?php esc_html_e('Min 3 pages, Min 1 click', 'ab-split-test-lite'); ?></span>
                </summary>

                <div class="abst-session-filter-controls">
                    <div class="abst-filter-group">
                        <label for="filter-min-pages"><?php esc_html_e('Min Pages', 'ab-split-test-lite'); ?></label>
                        <select id="filter-min-pages">
                            <option value="1">1+</option>
                            <option value="2">2+</option>
                            <option value="3" selected>3+</option>
                            <option value="5">5+</option>
                            <option value="10">10+</option>
                        </select>
                    </div>

                    <div class="abst-filter-group">
                        <label for="filter-min-clicks"><?php esc_html_e('Min Clicks', 'ab-split-test-lite'); ?></label>
                        <select id="filter-min-clicks">
                            <option value="0">0+</option>
                            <option value="1" selected>1+</option>
                            <option value="2">2+</option>
                            <option value="3">3+</option>
                            <option value="5">5+</option>
                            <option value="10">10+</option>
                        </select>
                    </div>

                    <div class="abst-filter-group">
                        <label for="filter-page"><?php esc_html_e('Visited Page', 'ab-split-test-lite'); ?></label>
                        <select id="filter-page">
                            <option value=""><?php esc_html_e('Any Page', 'ab-split-test-lite'); ?></option>
                            <?php foreach ($pages as $page): ?>
                                <option value="<?php echo esc_attr($page->ID); ?>">
                                    <?php echo esc_html($page->post_title); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="abst-filter-group">
                        <label for="filter-exit-page"><?php esc_html_e('Exit Page', 'ab-split-test-lite'); ?></label>
                        <select id="filter-exit-page">
                            <option value=""><?php esc_html_e('Any Exit Page', 'ab-split-test-lite'); ?></option>
                            <?php foreach ($pages as $page): ?>
                                <option value="<?php echo esc_attr($page->ID); ?>">
                                    <?php echo esc_html($page->post_title); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="abst-filter-group">
                        <label for="filter-test"><?php esc_html_e('Saw Test', 'ab-split-test-lite'); ?></label>
                        <select id="filter-test">
                            <option value=""><?php esc_html_e('Any Test', 'ab-split-test-lite'); ?></option>
                            <?php foreach ($tests as $test): ?>
                                <option value="<?php echo esc_attr($test->ID); ?>">
                                    <?php echo esc_html($test->post_title); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="abst-filter-group">
                        <label for="filter-converted"><?php esc_html_e('Conversion', 'ab-split-test-lite'); ?></label>
                        <select id="filter-converted">
                            <option value=""><?php esc_html_e('Any', 'ab-split-test-lite'); ?></option>
                            <option value="yes"><?php esc_html_e('Converted', 'ab-split-test-lite'); ?></option>
                            <option value="no"><?php esc_html_e('Not Converted', 'ab-split-test-lite'); ?></option>
                        </select>
                    </div>

                    <div class="abst-filter-group">
                        <label for="filter-device"><?php esc_html_e('Device', 'ab-split-test-lite'); ?></label>
                        <select id="filter-device">
                            <option value=""><?php esc_html_e('All Devices', 'ab-split-test-lite'); ?></option>
                            <option value="l"><?php esc_html_e('Desktop', 'ab-split-test-lite'); ?></option>
                            <option value="m"><?php esc_html_e('Tablet', 'ab-split-test-lite'); ?></option>
                            <option value="s"><?php esc_html_e('Mobile', 'ab-split-test-lite'); ?></option>
                        </select>
                    </div>

                    <div class="abst-filter-group">
                        <label>
                            <input type="checkbox" id="filter-rage-clicks">
                            <?php esc_html_e('Has Rage Clicks', 'ab-split-test-lite'); ?>
                        </label>
                    </div>

                    <div class="abst-filter-group">
                        <label for="filter-date-from"><?php esc_html_e('Date Range', 'ab-split-test-lite'); ?></label>
                        <div class="abst-date-range">
                            <input type="date" id="filter-date-from" placeholder="From">
                            <span><?php esc_html_e('to', 'ab-split-test-lite'); ?></span>
                            <input type="date" id="filter-date-to" placeholder="To">
                        </div>
                    </div>

                    <div class="abst-filter-group">
                        <label for="filter-referrer"><?php esc_html_e('Referrer', 'ab-split-test-lite'); ?></label>
                        <select id="filter-referrer">
                            <option value=""><?php esc_html_e('All Referrers', 'ab-split-test-lite'); ?></option>
                        </select>
                    </div>

                    <div class="abst-filter-group">
                        <label for="filter-utm-source"><?php esc_html_e('UTM Source', 'ab-split-test-lite'); ?></label>
                        <select id="filter-utm-source">
                            <option value=""><?php esc_html_e('All Sources', 'ab-split-test-lite'); ?></option>
                        </select>
                    </div>

                    <div class="abst-filter-group">
                        <label for="filter-utm-medium"><?php esc_html_e('UTM Medium', 'ab-split-test-lite'); ?></label>
                        <select id="filter-utm-medium">
                            <option value=""><?php esc_html_e('All Mediums', 'ab-split-test-lite'); ?></option>
                        </select>
                    </div>

                    <div class="abst-filter-group">
                        <label for="filter-utm-campaign"><?php esc_html_e('UTM Campaign', 'ab-split-test-lite'); ?></label>
                        <select id="filter-utm-campaign">
                            <option value=""><?php esc_html_e('All Campaigns', 'ab-split-test-lite'); ?></option>
                        </select>
                    </div>

                    <button type="button" id="apply-filters" class="button button-primary">
                        <?php esc_html_e('Apply Filters', 'ab-split-test-lite'); ?>
                    </button>

                    <button type="button" id="rebuild-index" class="button">
                        <?php esc_html_e('Rebuild Index', 'ab-split-test-lite'); ?>
                    </button>
                </div>
            </details>
            
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
