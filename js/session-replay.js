/**
 * Session Replay JavaScript
 * Handles session list, playback controls, timeline, and replay visualization
 */

(function($) {
    'use strict';

    // Configuration
    const config = {
        idleSkipThreshold: 10000, // Skip idle time > 10 seconds
        minIdleDisplay: 1200,     // Show at least 1.2s for skipped idle
        maxIdleDisplay: 3500,     // Show up to 3.5s for very long idle gaps
        cursorTransitionMs: 300,  // Cursor movement animation
        cursorLeadMs: 120,        // Cursor should arrive slightly before a click
        scrollDuration: 500,      // Smooth scroll duration
        iframeLoadTimeout: 10000, // Max wait for iframe load
        // Device sizes (width x height)
        deviceSizes: {
            'l': { width: 1280, height: 800 },   // Desktop 16:10
            'm': { width: 768, height: 1024 },   // Tablet portrait
            's': { width: 375, height: 667 }     // Mobile iPhone
        }
    };

    // State
    let state = {
        sessions: [],
        currentSession: null,
        events: [],
        currentEventIndex: 0,
        isPlaying: false,
        playbackSpeed: 1,
        playbackTimer: null,
        totalDuration: 0,
        currentTime: 0,
        currentPage: 1,
        iframeReady: false,
        waitingForIframe: false,
        iframeLoadTimeout: null,
        delayedTimeouts: []
    };

    // DOM Elements
    let $sessionList, $replayArea, $emptyState, $iframe, $cursor, $overlay;
    let $timeline, $playhead, $timelineEvents, $timelinePages;
    let $btnPlay, $currentTime, $totalTime, $sessionInfo;

    /**
     * Initialize the session replay module
     */
    function init() {
        cacheElements();
        bindEvents();
        loadSessions();
        
        // Check for UUID in URL parameter to auto-load a session
        checkUrlForSession();
    }

    /**
     * Check URL for uuid parameter and auto-load that session
     */
    function checkUrlForSession() {
        const urlParams = new URLSearchParams(window.location.search);
        const uuid = urlParams.get('uuid');
        
        if (uuid) {
            // We need to find this session's dates - fetch from server
            loadSessionByUuid(uuid);
        }
    }

    /**
     * Load a specific session by UUID (from URL parameter)
     */
    function loadSessionByUuid(uuid) {
        // Show loading state
        $emptyState.hide();
        $replayArea.show();
        showLoading(true);
        $sessionInfo.html('Loading session ' + uuid.substring(0, 8) + '...');

        // First get the session index to find this UUID's dates
        $.ajax({
            url: abstSessionReplay.ajaxUrl,
            type: 'POST',
            data: {
                action: 'abst_get_sessions',
                nonce: abstSessionReplay.nonce,
                min_pages: 1, // Get all sessions
                page: 1
            },
            success: function(response) {
                if (response.success) {
                    // Find the session with matching UUID
                    const session = response.data.sessions.find(s => s.uuid === uuid);
                    
                    if (session) {
                        state.sessions = response.data.sessions;
                        // Highlight in list if visible
                        setTimeout(() => {
                            $(`.abst-session-card[data-uuid="${uuid}"]`).addClass('active');
                        }, 100);
                        // Load the session
                        selectSession(uuid, session.dates);
                    } else {
                        showError('Session not found: ' + uuid.substring(0, 8) + '...');
                    }
                } else {
                    showError('Error loading sessions');
                }
            },
            error: function() {
                showError('Error loading sessions');
            }
        });
    }

    /**
     * Cache DOM elements
     */
    function cacheElements() {
        $sessionList = $('#session-list-container');
        $replayArea = $('#replay-area');
        $emptyState = $('#replay-empty-state');
        $iframe = $('#replay-iframe');
        $cursor = $('#replay-cursor');
        $overlay = $('#replay-overlay');
        $timeline = $('#replay-timeline');
        $playhead = $('#timeline-playhead');
        $timelineEvents = $('#timeline-events');
        $timelinePages = $('#timeline-pages');
        $btnPlay = $('#btn-play');
        $currentTime = $('#current-time');
        $totalTime = $('#total-time');
        $sessionInfo = $('#replay-session-info');
    }

    /**
     * Track replay-related timeouts so pause/seek/session switches can cancel them all.
     */
    function scheduleReplayTimeout(callback, delay) {
        const timeoutId = setTimeout(function() {
            state.delayedTimeouts = state.delayedTimeouts.filter(id => id !== timeoutId);
            callback();
        }, delay);

        state.delayedTimeouts.push(timeoutId);
        return timeoutId;
    }

    /**
     * Clear delayed replay actions and transient visuals.
     */
    function clearReplayDelays() {
        if (state.delayedTimeouts && state.delayedTimeouts.length) {
            state.delayedTimeouts.forEach(clearTimeout);
        }
        state.delayedTimeouts = [];
        $('.abst-click-pulse').remove();
    }

    /**
     * Compress long idle gaps without flattening them all to the same 1 second.
     */
    function getPlaybackDelay(rawDurationMs) {
        let duration = Math.max(0, rawDurationMs || 0);

        if (duration > config.idleSkipThreshold) {
            const ratio = duration / config.idleSkipThreshold;
            duration = config.minIdleDisplay + (Math.log(ratio) / Math.log(2)) * 400;
            duration = Math.min(config.maxIdleDisplay, Math.max(config.minIdleDisplay, duration));
        }

        duration = duration / state.playbackSpeed;
        return Math.max(100, duration);
    }

    /**
     * Bind event handlers
     */
    function bindEvents() {
        // Filters
        $('#apply-filters').on('click', function() {
            state.currentPage = 1;
            loadSessions();
        });

        $('#rebuild-index').on('click', rebuildIndex);

        // Session list click
        $sessionList.on('click', '.abst-session-card', function() {
            const uuid = $(this).data('uuid');
            const dates = $(this).data('dates');
            selectSession(uuid, dates);
        });

        // Pagination
        $('#session-pagination').on('click', 'button', function() {
            const page = $(this).data('page');
            if (page && page !== state.currentPage) {
                state.currentPage = page;
                loadSessions();
            }
        });

        // Playback controls
        $btnPlay.on('click', togglePlayback);
        $('#btn-prev').on('click', prevEvent);
        $('#btn-next').on('click', nextEvent);

        // Speed controls
        $('.abst-btn-speed').on('click', function() {
            const speed = parseFloat($(this).data('speed'));
            setPlaybackSpeed(speed);
        });

        // Timeline click
        $timeline.on('click', function(e) {
            const rect = this.getBoundingClientRect();
            const percent = (e.clientX - rect.left) / rect.width;
            jumpToPercent(percent);
        });

        // Timeline event click
        $timelineEvents.on('click', '.abst-timeline-event', function(e) {
            e.stopPropagation();
            const index = $(this).data('index');
            jumpToEvent(index);
        });

        // Iframe load handler
        $iframe.on('load', onIframeLoad);
    }

    /**
     * Populate referrer/UTM filter dropdowns from AJAX response data
     */
    function populateFilterDropdowns(available) {
        const dropdowns = [
            { id: '#filter-referrer', data: available.referrers, defaultLabel: 'All Referrers' },
            { id: '#filter-utm-source', data: available.utm_sources, defaultLabel: 'All Sources' },
            { id: '#filter-utm-medium', data: available.utm_mediums, defaultLabel: 'All Mediums' },
            { id: '#filter-utm-campaign', data: available.utm_campaigns, defaultLabel: 'All Campaigns' },
        ];

        dropdowns.forEach(function(dd) {
            const $el = $(dd.id);
            const currentVal = $el.val();
            $el.empty().append('<option value="">' + dd.defaultLabel + '</option>');
            if (dd.data && typeof dd.data === 'object') {
                Object.keys(dd.data).forEach(function(key) {
                    $el.append('<option value="' + $('<span>').text(key).html() + '">' + $('<span>').text(key).html() + ' (' + dd.data[key] + ')</option>');
                });
            }
            if (currentVal) {
                $el.val(currentVal);
            }
        });
    }

    /**
     * Load sessions from server
     */
    function loadSessions() {
        const filters = {
            min_pages: $('#filter-min-pages').val(),
            page_id: $('#filter-page').val(),
            test_id: $('#filter-test').val(),
            converted: $('#filter-converted').val(),
            has_rage_clicks: $('#filter-rage-clicks').is(':checked'),
            device: $('#filter-device').val(),
            date_from: $('#filter-date-from').val(),
            date_to: $('#filter-date-to').val(),
            referrer: $('#filter-referrer').val(),
            utm_source: $('#filter-utm-source').val(),
            utm_medium: $('#filter-utm-medium').val(),
            utm_campaign: $('#filter-utm-campaign').val(),
            page: state.currentPage
        };

        $sessionList.html('<p class="abst-loading">Loading sessions...</p>');

        $.ajax({
            url: abstSessionReplay.ajaxUrl,
            type: 'POST',
            data: {
                action: 'abst_get_sessions',
                nonce: abstSessionReplay.nonce,
                ...filters
            },
            success: function(response) {
                if (response.success) {
                    state.sessions = response.data.sessions;
                    if (response.data.available_filters) {
                        populateFilterDropdowns(response.data.available_filters);
                    }
                    renderSessionList(response.data);
                } else {
                    $sessionList.html('<p class="abst-error">Error loading sessions</p>');
                }
            },
            error: function() {
                $sessionList.html('<p class="abst-error">Error loading sessions</p>');
            }
        });
    }

    /**
     * Render session list
     */
    function renderSessionList(data) {
        const { sessions, total, total_pages, current_page } = data;

        $('#session-count').text(`(${total} total)`);

        if (sessions.length === 0) {
            $sessionList.html(`<p class="abst-no-results">${abstSessionReplay.strings.noSessions}</p>`);
            $('#session-pagination').empty();
            return;
        }

        let html = '';
        sessions.forEach(session => {
            const startDate = new Date(session.start_time);
            const dateStr = startDate.toLocaleDateString();
            const timeStr = startDate.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
            const duration = formatDuration(session.duration_seconds);
            const pages = session.page_titles.slice(0, 3).join(' → ');
            const morePages = session.page_count > 3 ? ` +${session.page_count - 3} more` : '';
            
            const deviceIcon = session.device === 'l' ? 'desktop' : 
                              session.device === 'm' ? 'tablet' : 'mobile';

            html += `
                <div class="abst-session-card" data-uuid="${session.uuid}" data-dates='${JSON.stringify(session.dates)}'>
                    <div class="abst-session-card-header">
                        <span class="abst-session-time">${dateStr} ${timeStr}</span>
                        <span class="abst-session-duration">${duration}</span>
                    </div>
                    <div class="abst-session-pages">${pages}${morePages}</div>
                    <div class="abst-session-meta">
                        <span class="device-icon ${deviceIcon}"></span>
                        <span>${session.page_count} pages</span>
                        <span>${session.click_count} clicks</span>
                        ${session.tests_converted.length > 0 ? '<span class="converted">✓ Converted</span>' : ''}
                        ${session.rage_click_count > 0 ? '<span class="rage">🔥 ' + session.rage_click_count + ' rage</span>' : ''}
                    </div>
                </div>
            `;
        });

        $sessionList.html(html);

        // Render pagination
        renderPagination(total_pages, current_page);
    }

    /**
     * Render pagination controls
     */
    function renderPagination(totalPages, currentPage) {
        if (totalPages <= 1) {
            $('#session-pagination').empty();
            return;
        }

        let html = '';
        
        // Previous button
        html += `<button data-page="${currentPage - 1}" ${currentPage === 1 ? 'disabled' : ''}>‹</button>`;
        
        // Page numbers
        for (let i = 1; i <= totalPages; i++) {
            if (i === 1 || i === totalPages || (i >= currentPage - 1 && i <= currentPage + 1)) {
                html += `<button data-page="${i}" class="${i === currentPage ? 'active' : ''}">${i}</button>`;
            } else if (i === currentPage - 2 || i === currentPage + 2) {
                html += '<span>...</span>';
            }
        }
        
        // Next button
        html += `<button data-page="${currentPage + 1}" ${currentPage === totalPages ? 'disabled' : ''}>›</button>`;

        $('#session-pagination').html(html);
    }

    /**
     * Select a session and load its events
     */
    function selectSession(uuid, dates) {
        // Update UI
        $('.abst-session-card').removeClass('active');
        $(`.abst-session-card[data-uuid="${uuid}"]`).addClass('active');

        // Stop any current playback
        stopPlayback();

        // Show loading state
        $emptyState.hide();
        $replayArea.show();
        showLoading(true);

        // Load events
        $.ajax({
            url: abstSessionReplay.ajaxUrl,
            type: 'POST',
            data: {
                action: 'abst_get_session_events',
                nonce: abstSessionReplay.nonce,
                uuid: uuid,
                dates: dates
            },
            success: function(response) {
                if (response.success) {
                    state.events = response.data;
                    state.currentSession = state.sessions.find(s => s.uuid === uuid);
                    state.currentEventIndex = 0;
                    state.currentTime = 0;
                    
                    initializeReplay();
                    showLoading(false);
                } else {
                    showError('Error loading session events');
                }
            },
            error: function() {
                showError('Error loading session events');
            }
        });
    }

    /**
     * Initialize replay with loaded events
     */
    function initializeReplay() {
        if (state.events.length === 0) {
            showError('No events in this session');
            return;
        }

        // Calculate total duration
        const firstTime = new Date(state.events[0].timestamp).getTime();
        const lastTime = new Date(state.events[state.events.length - 1].timestamp).getTime();
        state.totalDuration = lastTime - firstTime;

        // Update session info
        const session = state.currentSession;
        $sessionInfo.html(`
            <strong>${session.page_count} pages</strong> · 
            ${session.click_count} clicks · 
            ${formatDuration(session.duration_seconds)} duration
            ${session.tests_converted.length > 0 ? ' · <span style="color:#00a32a">✓ Converted</span>' : ''}
        `);

        // Update time display
        $totalTime.text(formatTime(state.totalDuration));
        $currentTime.text('0:00');
        updatePlayhead(0);

        // Set iframe size based on device
        setIframeSize(session.device || 'l');

        // Build timeline
        buildTimeline();

        // Load first page and auto-start after 1 second delay
        state.iframeReady = false;
        loadEventPage(0);

        // Show cursor at initial position (70% from left, 20% from top)
        const wrapper = $('.abst-replay-iframe-wrapper');
        // Use clientWidth/clientHeight for visible viewport, not scrollable content
        const initialX = wrapper[0].clientWidth * 0.7;
        const initialY = wrapper[0].clientHeight * 0.2;
        $cursor.css({
            'transition': 'none',
            'transform': `translate(${initialX}px, ${initialY}px)`,
            'display': 'block'
        });
        $cursor.show();
    }

    /**
     * Build the timeline visualization
     */
    function buildTimeline() {
        $timelineEvents.empty();
        $timelinePages.empty();

        if (state.events.length === 0) return;

        const firstTime = new Date(state.events[0].timestamp).getTime();
        const duration = state.totalDuration || 1;

        // Track page segments
        const pageSegments = [];
        let currentPageId = null;
        let segmentStart = 0;

        state.events.forEach((event, index) => {
            const eventTime = new Date(event.timestamp).getTime();
            const percent = ((eventTime - firstTime) / duration) * 100;

            // Determine event type for styling
            let eventClass = 'click';
            if (event.type === 'pv') {
                eventClass = 'pv';
                
                // Track page segment
                if (currentPageId !== null) {
                    pageSegments.push({
                        post_id: currentPageId,
                        title: event.page_title || 'Page',
                        start: segmentStart,
                        end: percent
                    });
                }
                currentPageId = event.post_id;
                segmentStart = percent;
            } else if (event.type === 'rc') {
                eventClass = 'rage';
            } else if (event.type.startsWith('tc-')) {
                eventClass = 'conversion';
            }

            // Add event dot
            const $dot = $(`<div class="abst-timeline-event ${eventClass}" data-index="${index}" title="${getEventTitle(event)}"></div>`);
            $dot.css('left', `${percent}%`);
            $timelineEvents.append($dot);
        });

        // Add final page segment
        if (currentPageId !== null) {
            pageSegments.push({
                post_id: currentPageId,
                title: state.events.find(e => e.post_id === currentPageId)?.page_title || 'Page',
                start: segmentStart,
                end: 100
            });
        }

        // Render page labels
        pageSegments.forEach(segment => {
            const width = segment.end - segment.start;
            const $label = $(`<div class="abst-timeline-page" title="${segment.title}">${segment.title}</div>`);
            $label.css('flex', `0 0 ${width}%`);
            $timelinePages.append($label);
        });
    }

    /**
     * Get tooltip title for timeline event
     */
    function getEventTitle(event) {
        if (event.type === 'pv') {
            return `Page View: ${event.page_title || 'Page ' + event.post_id}`;
        } else if (event.type === 'c') {
            return `Click: ${event.element_id_or_selector || 'element'}`;
        } else if (event.type === 'rc') {
            return `Rage Click: ${event.element_id_or_selector || 'element'}`;
        } else if (event.type.startsWith('tc-')) {
            return 'Conversion';
        } else if (event.type.startsWith('tv-')) {
            return 'Test View';
        }
        return event.type;
    }

    /**
     * Load the page for a specific event
     */
    function loadEventPage(eventIndex) {
        const event = state.events[eventIndex];
        if (!event) return;

        // Find the page view event for this page
        let pageUrl = null;
        for (let i = eventIndex; i >= 0; i--) {
            if (state.events[i].type === 'pv' && state.events[i].page_url) {
                pageUrl = state.events[i].page_url;
                break;
            }
        }

        if (!pageUrl) {
            // Fallback: construct URL from post_id
            const postId = event.post_id;
            pageUrl = abstSessionReplay.homeUrl + '?p=' + postId;
        }

        // Add heatmap view parameter to prevent tracking
        const separator = pageUrl.includes('?') ? '&' : '?';
        const iframeUrl = pageUrl + separator + 'abst_heatmap_view=1';

        // Check if we need to load a new page
        const currentSrc = $iframe.attr('src');
        if (currentSrc !== iframeUrl) {
            showLoading(true);
            $iframe.attr('src', iframeUrl);
            
            // Fallback timeout in case load event doesn't fire
            if (state.iframeLoadTimeout) {
                clearTimeout(state.iframeLoadTimeout);
            }
            state.iframeLoadTimeout = scheduleReplayTimeout(() => {
                console.log('Iframe load timeout - forcing continue');
                if (state.waitingForIframe) {
                    onIframeLoad();
                }
            }, config.iframeLoadTimeout);
        } else {
            // Same page, just trigger load complete immediately
            if (state.waitingForIframe) {
                state.waitingForIframe = false;
                state.iframeReady = true;
                showLoading(false);
                scheduleReplayTimeout(playNextEvent, 100);
            } else {
                positionCursorForEvent(event);
            }
        }
    }

    /**
     * Handle iframe load complete
     */
    function onIframeLoad() {
        showLoading(false);
        state.iframeReady = true;
        
        // Clear any fallback timeout
        if (state.iframeLoadTimeout) {
            clearTimeout(state.iframeLoadTimeout);
            state.iframeLoadTimeout = null;
        }
        
        // Scroll iframe content to top so replay starts from page top
        try {
            const iframeDoc = $iframe[0].contentDocument || $iframe[0].contentWindow.document;
            if (iframeDoc && iframeDoc.documentElement) {
                iframeDoc.documentElement.scrollTop = 0;
            }
        } catch (e) { /* cross-origin */ }
        
        // Ensure cursor is visible after page load
        $cursor.css('display', 'block');
        
        // Position cursor for new page (top-right area)
        const wrapper = $('.abst-replay-iframe-wrapper');
        const pixelX = wrapper[0].clientWidth * 0.7;
        const pixelY = wrapper[0].clientHeight * 0.2;
        $cursor.css({
            'transition': 'none',
            'transform': `translate(${pixelX}px, ${pixelY}px)`
        });

        // If this is the first load (index 0), auto-start playback after 1 second
        if (state.currentEventIndex === 0 && !state.isPlaying) {
            scheduleReplayTimeout(() => {
                if (!state.isPlaying && state.events.length > 0) {
                    startPlayback();
                }
            }, 1000);
        }

        // If we were waiting for iframe to load during playback, continue
        if (state.waitingForIframe && state.isPlaying) {
            state.waitingForIframe = false;
            // Calculate actual time to wait before first event after page load
            const nextEvent = state.events[state.currentEventIndex];
            if (nextEvent) {
                // Find the previous page view event to get the actual time difference
                const pvIndex = state.currentEventIndex - 1;
                if (pvIndex >= 0 && state.events[pvIndex].type === 'pv') {
                    const pvTime = new Date(state.events[pvIndex].timestamp).getTime();
                    const nextTime = new Date(nextEvent.timestamp).getTime();
                    let duration = nextTime - pvTime;
                    
                    duration = getPlaybackDelay(duration);
                    
                    // Add a small buffer for dynamic content to render
                    const renderBuffer = 500;
                    
                    scheduleReplayTimeout(() => {
                        if (nextEvent.type === 'c' || nextEvent.type === 'rc') {
                            // Animate cursor to click position
                            const dwellTime = getDwellTime(nextEvent);
                            const travelDuration = Math.min(duration - renderBuffer - dwellTime, config.scrollDuration + 300);
                            animateToPosition(nextEvent, travelDuration);
                            scheduleReplayTimeout(playNextEvent, travelDuration);
                        } else {
                            playNextEvent();
                        }
                    }, renderBuffer);
                } else {
                    // No page view found, use default timing
                    scheduleReplayTimeout(() => {
                        if (nextEvent.type === 'c' || nextEvent.type === 'rc') {
                            const dwellTime = getDwellTime(nextEvent);
                            const totalAnimTime = config.scrollDuration + 300;
                            animateToPosition(nextEvent, totalAnimTime);
                            scheduleReplayTimeout(playNextEvent, totalAnimTime + dwellTime);
                        } else {
                            playNextEvent();
                        }
                    }, 1000);
                }
            } else {
                playNextEvent();
            }
        }
    }

    /**
     * Try to get element position from iframe
     * Returns wrapper-relative {x, y} in pixels, or null if element not found
     */
    function getElementPosition(selector, percentX, percentY) {
        if (!state.iframeReady || !$iframe || !$iframe[0]) return null;
        
        try {
            const iframeDoc = $iframe[0].contentDocument || $iframe[0].contentWindow.document;
            const iframeWin = $iframe[0].contentWindow;
            
            // Validate selector - must be a non-empty string that looks like a CSS selector
            if (!selector || typeof selector !== 'string' || !iframeDoc || !iframeDoc.body) return null;
            
            // Skip invalid selectors (just numbers, empty, etc.)
            if (/^\d+$/.test(selector) || selector.trim() === '') return null;
            
            let el = iframeDoc.querySelector(selector);
            
            // If not found, try without the page-id prefix (element may exist with different page class)
            if (!el && selector.includes('.page-id-')) {
                const selectorWithoutPageId = selector.replace(/\.page-id-\d+\s*/, '');
                el = iframeDoc.querySelector(selectorWithoutPageId);
            }
            
            if (!el) return null;
            
            const rect = el.getBoundingClientRect();
            if (rect.width <= 0 || rect.height <= 0) return null;
            
            // rect coordinates are relative to iframe viewport
            // Calculate click position within element
            const clickX = rect.left + rect.width * percentX;
            const clickY = rect.top + rect.height * percentY;
            
            // Scale if iframe content is different size than wrapper
            const wrapper = $('.abst-replay-iframe-wrapper');
            const iframeWidth = $iframe[0].clientWidth;
            const iframeHeight = $iframe[0].clientHeight;
            const wrapperWidth = wrapper[0].clientWidth;
            const wrapperHeight = wrapper[0].clientHeight;
            
            // Apply scaling if needed
            const scaleX = wrapperWidth / iframeWidth;
            const scaleY = wrapperHeight / iframeHeight;
            
            const x = clickX * scaleX;
            const y = clickY * scaleY;
            
            return { x, y };
        } catch (e) {
            return null;
        }
    }

    /**
     * Position cursor for an event (instant, no animation)
     */
    function positionCursorForEvent(event) {
        const wrapper = $('.abst-replay-iframe-wrapper');
        // Use the CSS-defined dimensions, not the scrollable content height
        const wrapperWidth = wrapper[0].clientWidth;
        const wrapperHeight = wrapper[0].clientHeight;
        
        if (event.type === 'pv') {
            // Page view - position cursor at 70% X, 20% Y (top-right area)
            const pixelX = wrapperWidth * 0.7;
            const pixelY = wrapperHeight * 0.2;
            $cursor.css({
                'transition': 'none',
                'transform': `translate(${pixelX}px, ${pixelY}px)`,
                'display': 'block'
            });
            return;
        }

        // Get click coordinates (stored as percentages relative to element)
        const percentX = parseFloat(event.click_x) || 0.5;
        const percentY = parseFloat(event.click_y) || 0.5;
        const selector = event.element_id_or_selector;

        let pixelX, pixelY;
        
        // First scroll to make element visible (smooth animated)
        const scrollDuration = config.scrollDuration;
        scrollToElement(selector, percentY, scrollDuration);
        
        // Wait for scroll animation to complete, then position cursor
        setTimeout(() => {
            const pos = getElementPosition(selector, percentX, percentY);
            if (pos) {
                pixelX = pos.x;
                pixelY = pos.y;
            } else {
                // Fallback: use wrapper percentages
                pixelX = percentX * wrapperWidth;
                pixelY = percentY * wrapperHeight;
            }

            // Position cursor instantly (no transition)
            $cursor.css({
                'transition': 'none',
                'transform': `translate(${pixelX}px, ${pixelY}px)`,
                'display': 'block'
            });
        }, scrollDuration);
    }
    
    /**
     * Scroll iframe to make an element visible (smooth animated)
     * Returns the target scroll position, or -1 if no scroll was needed/possible
     */
    function scrollToElement(selector, percentY, scrollDurationMs) {
        if (!$iframe || !$iframe[0] || !state.iframeReady) return -1;
        
        try {
            const iframeDoc = $iframe[0].contentDocument || $iframe[0].contentWindow.document;
            const iframeWin = $iframe[0].contentWindow;
            
            if (!selector || typeof selector !== 'string' || !iframeDoc) return -1;
            
            // Skip invalid selectors
            if (/^\d+$/.test(selector) || selector.trim() === '') return -1;
            
            let el = iframeDoc.querySelector(selector);
            
            // Fallback: try without page-id prefix
            if (!el && selector.includes('.page-id-')) {
                const selectorWithoutPageId = selector.replace(/\.page-id-\d+\s*/, '');
                el = iframeDoc.querySelector(selectorWithoutPageId);
            }
            
            if (!el) return -1;
            
            const rect = el.getBoundingClientRect();
            const clickY = rect.top + rect.height * percentY + iframeWin.scrollY;
            const viewportHeight = $iframe.height();
            
            // Scroll to center the click point in viewport
            const targetScroll = Math.max(0, clickY - viewportHeight / 2);
            animateIframeScrollTo(targetScroll, scrollDurationMs || config.scrollDuration);
            return targetScroll;
        } catch (e) {
            // Cross-origin or error
            return -1;
        }
    }

    /**
     * Set iframe size based on device type
     */
    function setIframeSize(device) {
        const size = config.deviceSizes[device] || config.deviceSizes['l'];
        const $wrapper = $('.abst-replay-iframe-wrapper');
        
        $wrapper.css({
            'width': size.width + 'px',
            'height': size.height + 'px',
            'max-width': '100%'
        });
        
        // Add device class for styling
        $wrapper.removeClass('device-desktop device-tablet device-mobile');
        if (device === 's') {
            $wrapper.addClass('device-mobile');
        } else if (device === 'm') {
            $wrapper.addClass('device-tablet');
        } else {
            $wrapper.addClass('device-desktop');
        }
    }

    /**
     * Detect if events are part of an autofill burst
     * Returns array of events in the burst, or empty array if not a burst
     */
    function detectAutofillBurst(startIndex) {
        if (startIndex >= state.events.length) return [];
        
        const firstEvent = state.events[startIndex];
        if (firstEvent.type !== 'c') return [];
        
        const burstEvents = [firstEvent];
        const firstTime = new Date(firstEvent.timestamp).getTime();
        
        // Check if selector indicates form input
        const selector = firstEvent.element_id_or_selector || '';
        const isFormInput = selector.includes('input') || 
                           selector.includes('#reg_') || 
                           selector.includes('#affwp-') ||
                           selector.match(/#[a-z_]+_\d+_\d+/); // Gravity Forms pattern
        
        if (!isFormInput) return [];
        
        // Look ahead for more clicks within 50ms
        for (let i = startIndex + 1; i < state.events.length; i++) {
            const event = state.events[i];
            if (event.type !== 'c') break;
            
            const eventTime = new Date(event.timestamp).getTime();
            const timeDiff = eventTime - firstTime;
            
            if (timeDiff > 50) break; // End of burst
            
            // Check if this is also a form input
            const eventSelector = event.element_id_or_selector || '';
            const eventIsFormInput = eventSelector.includes('input') || 
                                    eventSelector.includes('#reg_') || 
                                    eventSelector.includes('#affwp-') ||
                                    eventSelector.match(/#[a-z_]+_\d+_\d+/);
            
            if (eventIsFormInput) {
                burstEvents.push(event);
            }
        }
        
        // Only consider it a burst if there are 2+ rapid clicks
        return burstEvents.length >= 2 ? burstEvents : [];
    }

    /**
     * Get realistic dwell time before click based on element type
     */
    function getDwellTime(event) {
        const selector = event.element_id_or_selector || '';
        
        // Determine element type from selector
        let elementType = 'unknown';
        if (selector.includes('button') || selector.includes('btn') || selector.includes('[type="submit"]')) {
            elementType = 'button';
        } else if (selector.includes('a') || selector.includes('link')) {
            elementType = 'link';
        } else if (selector.includes('input') || selector.includes('textarea')) {
            elementType = 'input';
        } else if (selector.includes('select')) {
            elementType = 'dropdown';
        } else if (selector.includes('img')) {
            elementType = 'image';
        } else if (selector.includes('h1') || selector.includes('h2') || selector.includes('h3')) {
            elementType = 'heading';
        }
        
        // Base dwell times by element type (in ms)
        const baseTimes = {
            'button': 150,      // Quick decisions on buttons
            'link': 200,        // Slightly longer for links
            'input': 300,       // Longer for form inputs
            'dropdown': 350,    // Longest for dropdowns
            'image': 180,       // Medium for images
            'heading': 250,     // Reading headings
            'unknown': 200      // Default
        };
        
        const baseTime = baseTimes[elementType] || baseTimes['unknown'];
        
        // Add randomization (±30%)
        const randomization = (Math.random() - 0.5) * 0.6; // -0.3 to +0.3
        const dwellTime = Math.round(baseTime * (1 + randomization));
        
        // Ensure minimum and maximum bounds
        return Math.max(100, Math.min(500, dwellTime));
    }

    /**
     * Show click pulse animation
     */
    function showClickPulse(event, isRage, isAutofill) {
        const percentX = parseFloat(event.click_x) || 0.5;
        const percentY = parseFloat(event.click_y) || 0.5;
        const selector = event.element_id_or_selector;
        
        const wrapper = $('.abst-replay-iframe-wrapper');
        let pixelX, pixelY;
        
        // Try element-based positioning
        const pos = getElementPosition(selector, percentX, percentY);
        if (pos) {
            pixelX = pos.x;
            pixelY = pos.y;
        } else {
            // Fallback
            pixelX = percentX * wrapper.width();
            pixelY = percentY * wrapper.height();
        }

        let pulseClass = '';
        if (isRage) {
            pulseClass = 'rage';
        } else if (isAutofill) {
            pulseClass = 'autofill';
        }
        
        const $pulse = $(`<div class="abst-click-pulse ${pulseClass}"></div>`);
        $pulse.css({
            left: pixelX + 'px',
            top: pixelY + 'px'
        });

        wrapper.append($pulse);

        // Remove after animation
        setTimeout(() => $pulse.remove(), 500);
    }

    /**
     * Snap cursor to the exact position for an event
     */
    function snapCursorToEvent(event) {
        if (!event) return;

        const wrapper = $('.abst-replay-iframe-wrapper');
        if (!wrapper.length) return;

        let pixelX;
        let pixelY;

        if (event.type === 'pv') {
            pixelX = wrapper[0].clientWidth * 0.7;
            pixelY = wrapper[0].clientHeight * 0.2;
        } else {
            const percentX = parseFloat(event.click_x) || 0.5;
            const percentY = parseFloat(event.click_y) || 0.5;
            const selector = event.element_id_or_selector;
            const pos = getElementPosition(selector, percentX, percentY);

            if (pos) {
                pixelX = pos.x;
                pixelY = pos.y;
            } else {
                pixelX = percentX * wrapper[0].clientWidth;
                pixelY = percentY * wrapper[0].clientHeight;
            }
        }

        $cursor.css({
            'transition': 'none',
            'transform': `translate(${pixelX}px, ${pixelY}px)`,
            'display': 'block'
        });
    }

    /**
     * Toggle playback
     */
    function togglePlayback() {
        if (state.isPlaying) {
            stopPlayback();
        } else {
            startPlayback();
        }
    }

    /**
     * Start playback
     */
    function startPlayback() {
        if (state.events.length === 0) return;
        
        state.isPlaying = true;
        $btnPlay.find('.play-icon').hide();
        $btnPlay.find('.pause-icon').show();

        playNextEvent();
    }

    /**
     * Stop playback
     */
    function stopPlayback() {
        state.isPlaying = false;
        $btnPlay.find('.play-icon').show();
        $btnPlay.find('.pause-icon').hide();

        clearReplayDelays();

        if (state.playbackTimer) {
            clearTimeout(state.playbackTimer);
            state.playbackTimer = null;
        }

        if (state.iframeLoadTimeout) {
            clearTimeout(state.iframeLoadTimeout);
            state.iframeLoadTimeout = null;
        }
    }

    /**
     * Play the next event
     */
    function playNextEvent() {
        if (!state.isPlaying || state.currentEventIndex >= state.events.length) {
            stopPlayback();
            return;
        }

        const event = state.events[state.currentEventIndex];
        
        // Update current time
        const firstTime = new Date(state.events[0].timestamp).getTime();
        const eventTime = new Date(event.timestamp).getTime();
        state.currentTime = eventTime - firstTime;
        updateTimeDisplay();
        updatePlayhead(state.currentTime);

        // Check if we need to load a new page
        if (event.type === 'pv') {
            state.iframeReady = false;
            state.waitingForIframe = true;
            
            // Increment index BEFORE loading page so we don't re-process this pv event
            state.currentEventIndex++;
            
            loadEventPage(state.currentEventIndex - 1); // Load the page for the pv event we just processed
            // onIframeLoad will call playNextEvent when ready
            return;
        }

        // Check for autofill burst
        const burstEvents = detectAutofillBurst(state.currentEventIndex);
        
        if (burstEvents.length > 0) {
            // Handle autofill burst - show all clicks rapidly
            burstEvents.forEach((burstEvent, index) => {
                scheduleReplayTimeout(() => {
                    snapCursorToEvent(burstEvent);
                    showClickPulse(burstEvent, false, true); // true = autofill style
                }, index * 30); // 30ms between each autofill click
            });
            
            // Skip ahead past the burst
            state.currentEventIndex += burstEvents.length;
            
            // Schedule next event after burst animation completes
            const burstDuration = burstEvents.length * 30;
            state.playbackTimer = setTimeout(playNextEvent, burstDuration + 100);
            return;
        }
        
        // Store dwell time for this click event (if applicable)
        let currentDwellTime = 0;
        
        // For click events: show pulse at current position (cursor should already be here)
        if (event.type === 'c' || event.type === 'rc') {
            snapCursorToEvent(event);
            
            // Add dwell time before click for realism
            currentDwellTime = getDwellTime(event);
            scheduleReplayTimeout(() => {
                showClickPulse(event, event.type === 'rc', false); // false = not autofill
            }, currentDwellTime);
        }

        // Look ahead to next event to calculate timing
        const nextIndex = state.currentEventIndex + 1;
        if (nextIndex < state.events.length) {
            const nextEvent = state.events[nextIndex];
            
            // Calculate time until next event
            const nextTime = new Date(nextEvent.timestamp).getTime();
            let duration = nextTime - eventTime;
            
            // Subtract dwell time from duration (it's already accounted for in the setTimeout above)
            duration -= currentDwellTime;
            
            duration = getPlaybackDelay(duration);
            
            // If next event is a click, animate cursor TOWARD it over the duration
            // The cursor moves during the gap between events, arriving just before the click
            if (nextEvent.type === 'c' || nextEvent.type === 'rc') {
                const leadTime = Math.min(config.cursorLeadMs, Math.max(30, duration * 0.35));
                const travelDuration = Math.max(80, duration - leadTime);
                animateToPosition(nextEvent, travelDuration);
            }
            // If next event is a page view, just wait (cursor will reset on new page)

            const nextRelativeTime = nextTime - firstTime;
            updatePlayhead(nextRelativeTime, duration);
            
            // Schedule next event after the duration
            state.currentEventIndex++;
            state.playbackTimer = setTimeout(playNextEvent, duration);
        } else {
            // No more events
            state.currentEventIndex++;
            stopPlayback();
        }
    }

    /**
     * Aggressive ease-in-out function
     * Slow start, fast middle, slow end
     */
    function easeInOutCubic(t) {
        return t < 0.5 
            ? 4 * t * t * t 
            : 1 - Math.pow(-2 * t + 2, 3) / 2;
    }

    /**
     * Animate cursor and scroll to position over a duration
     */
    function animateToPosition(event, durationMs) {
        const percentX = parseFloat(event.click_x) || 0.5;
        const percentY = parseFloat(event.click_y) || 0.5;
        const selector = event.element_id_or_selector;
        
        const wrapper = $('.abst-replay-iframe-wrapper');
        const wrapperWidth = wrapper[0].clientWidth;
        const wrapperHeight = wrapper[0].clientHeight;

        const arrivalLead = Math.min(config.cursorLeadMs, Math.max(30, durationMs * 0.25));
        const availableMotionTime = Math.max(80, durationMs - arrivalLead);
        const scrollDuration = Math.min(config.scrollDuration, Math.max(60, availableMotionTime * 0.55));
        const moveDuration = Math.max(80, availableMotionTime - scrollDuration);

        scrollToElement(selector, percentY, scrollDuration);

        scheduleReplayTimeout(() => {
            const finalPos = getElementPosition(selector, percentX, percentY);
            const targetPixelX = finalPos ? finalPos.x : percentX * wrapperWidth;
            const targetPixelY = finalPos ? finalPos.y : percentY * wrapperHeight;
            animateCursorWithWobble(targetPixelX, targetPixelY, moveDuration);
        }, scrollDuration + 20);
    }
    
    /**
     * Animate cursor with natural wobble effect
     */
    function animateCursorWithWobble(targetX, targetY, durationMs) {
        // Ensure cursor is visible
        $cursor.css('display', 'block');
        
        // Sanity check duration
        if (!durationMs || durationMs < 50) durationMs = 300;
        
        const startX = parseFloat($cursor.css('--cursor-x')) || 0;
        const startY = parseFloat($cursor.css('--cursor-y')) || 0;
        
        // Get current position from transform
        const currentTransform = $cursor.css('transform');
        let currentX = 0, currentY = 0;
        if (currentTransform && currentTransform !== 'none') {
            const matrix = currentTransform.match(/matrix.*\((.+)\)/);
            if (matrix) {
                const values = matrix[1].split(', ');
                currentX = parseFloat(values[4]) || 0;
                currentY = parseFloat(values[5]) || 0;
            }
        }
        
        const deltaX = targetX - currentX;
        const deltaY = targetY - currentY;
        const startTime = performance.now();
        
        // Track hovered elements for hover simulation
        const hoveredElements = new Set();
        
        function step(currentTime) {
            if (!state.isPlaying) return;
            
            const elapsed = currentTime - startTime;
            const progress = Math.min(elapsed / durationMs, 1);
            const eased = easeInOutCubic(progress);
            
            // Base position
            let x = currentX + deltaX * eased;
            let y = currentY + deltaY * eased;
            
            // Add wobble only during movement (not at the end)
            if (progress < 0.95) {
                const wobbleStrength = (1 - progress) * 2;
                const wobbleX = Math.sin(elapsed * 0.008) * wobbleStrength;
                const wobbleY = Math.cos(elapsed * 0.011) * wobbleStrength * 0.5;
                x += wobbleX;
                y += wobbleY;
            } else {
                // Snap to exact target at the end
                x = targetX;
                y = targetY;
            }
            
            $cursor.css({
                'transition': 'none',
                'transform': `translate(${x}px, ${y}px)`,
                'display': 'block'
            });
            
            // Hover simulation - trigger events on elements under cursor
            if (progress < 0.95 && state.iframeReady) {
                simulateHoverEvents(x, y, hoveredElements);
            }
            
            if (progress < 1) {
                requestAnimationFrame(step);
            } else {
                // Clear hover states at end
                clearHoverStates(hoveredElements);
            }
        }
        
        requestAnimationFrame(step);
    }
    
    /**
     * Simulate hover events on elements under cursor position
     */
    function simulateHoverEvents(cursorX, cursorY, hoveredElements) {
        if (!$iframe || !$iframe[0]) return;
        
        try {
            const iframeDoc = $iframe[0].contentDocument || $iframe[0].contentWindow.document;
            if (!iframeDoc || !iframeDoc.elementsFromPoint) return;

            const wrapper = $('.abst-replay-iframe-wrapper');
            if (!wrapper.length) return;

            const wrapperWidth = wrapper[0].clientWidth;
            const wrapperHeight = wrapper[0].clientHeight;
            const iframeWidth = $iframe[0].clientWidth;
            const iframeHeight = $iframe[0].clientHeight;

            if (!wrapperWidth || !wrapperHeight || !iframeWidth || !iframeHeight) return;

            const iframeX = cursorX * (iframeWidth / wrapperWidth);
            const iframeY = cursorY * (iframeHeight / wrapperHeight);
            
            // Get elements under cursor position
            const elements = iframeDoc.elementsFromPoint(iframeX, iframeY);
            const currentHovered = new Set(elements);
            
            // Trigger mouseenter on newly hovered elements
            elements.forEach(el => {
                if (!hoveredElements.has(el)) {
                    try {
                        el.dispatchEvent(new MouseEvent('mouseenter', { bubbles: true, cancelable: true }));
                        el.dispatchEvent(new MouseEvent('mouseover', { bubbles: true, cancelable: true }));
                    } catch (e) {
                        // Element might not support events
                    }
                }
            });
            
            // Trigger mouseleave on elements no longer hovered
            hoveredElements.forEach(el => {
                if (!currentHovered.has(el)) {
                    try {
                        el.dispatchEvent(new MouseEvent('mouseleave', { bubbles: true, cancelable: true }));
                        el.dispatchEvent(new MouseEvent('mouseout', { bubbles: true, cancelable: true }));
                    } catch (e) {
                        // Element might not support events
                    }
                }
            });
            
            // Update tracked set
            hoveredElements.clear();
            currentHovered.forEach(el => hoveredElements.add(el));
        } catch (e) {
            // Cross-origin or error
        }
    }
    
    /**
     * Clear hover states from all tracked elements
     */
    function clearHoverStates(hoveredElements) {
        hoveredElements.forEach(el => {
            try {
                el.dispatchEvent(new MouseEvent('mouseleave', { bubbles: true, cancelable: true }));
                el.dispatchEvent(new MouseEvent('mouseout', { bubbles: true, cancelable: true }));
            } catch (e) {
                // Element might not support events
            }
        });
        hoveredElements.clear();
    }

    /**
     * Animate iframe scroll to absolute Y over a duration
     */
    function animateIframeScrollTo(targetScrollY, durationMs) {
        if (!$iframe || !$iframe[0]) return;
        
        try {
            const iframeDoc = $iframe[0].contentDocument || $iframe[0].contentWindow.document;
            if (!iframeDoc || !iframeDoc.documentElement) return;
            
            const startScrollY = iframeDoc.documentElement.scrollTop;
            const scrollDelta = targetScrollY - startScrollY;
            
            // No scrolling needed
            if (Math.abs(scrollDelta) < 1) return;
            
            const startTime = performance.now();
            
            function step(currentTime) {
                if (!state.isPlaying) return;
                
                const elapsed = currentTime - startTime;
                const progress = Math.min(elapsed / durationMs, 1);
                const eased = easeInOutCubic(progress);
                
                iframeDoc.documentElement.scrollTop = startScrollY + (scrollDelta * eased);
                
                if (progress < 1) {
                    requestAnimationFrame(step);
                }
            }
            
            requestAnimationFrame(step);
        } catch (e) {
            // Cross-origin
        }
    }

    /**
     * Go to previous event
     */
    function prevEvent() {
        if (state.currentEventIndex > 0) {
            state.currentEventIndex--;
            jumpToEvent(state.currentEventIndex);
        }
    }

    /**
     * Go to next event
     */
    function nextEvent() {
        if (state.currentEventIndex < state.events.length - 1) {
            state.currentEventIndex++;
            jumpToEvent(state.currentEventIndex);
        }
    }

    /**
     * Jump to a specific event
     */
    function jumpToEvent(index) {
        stopPlayback();
        state.currentEventIndex = index;

        const event = state.events[index];
        if (!event) return;

        // Update time
        const firstTime = new Date(state.events[0].timestamp).getTime();
        const eventTime = new Date(event.timestamp).getTime();
        state.currentTime = eventTime - firstTime;
        updateTimeDisplay();
        updatePlayhead(state.currentTime);

        // Load page and position cursor
        loadEventPage(index);
    }

    /**
     * Jump to a percentage of the timeline
     */
    function jumpToPercent(percent) {
        const targetTime = percent * state.totalDuration;
        const firstTime = new Date(state.events[0].timestamp).getTime();

        // Find the event closest to this time
        let closestIndex = 0;
        let closestDiff = Infinity;

        state.events.forEach((event, index) => {
            const eventTime = new Date(event.timestamp).getTime() - firstTime;
            const diff = Math.abs(eventTime - targetTime);
            if (diff < closestDiff) {
                closestDiff = diff;
                closestIndex = index;
            }
        });

        jumpToEvent(closestIndex);
    }

    /**
     * Set playback speed
     */
    function setPlaybackSpeed(speed) {
        state.playbackSpeed = speed;
        $('.abst-btn-speed').removeClass('active');
        $(`.abst-btn-speed[data-speed="${speed}"]`).addClass('active');
    }

    /**
     * Update time display
     */
    function updateTimeDisplay() {
        $currentTime.text(formatTime(state.currentTime));
    }

    /**
     * Update playhead position
     */
    function updatePlayhead(timeMs, transitionMs) {
        const effectiveTime = typeof timeMs === 'number' ? timeMs : state.currentTime;
        const percent = state.totalDuration > 0 
            ? (effectiveTime / state.totalDuration) * 100 
            : 0;

        if (typeof transitionMs === 'number') {
            $playhead.css('transition', `left ${Math.max(0, transitionMs)}ms linear`);
        } else {
            $playhead.css('transition', 'none');
        }

        $playhead.css('left', `${percent}%`);
    }

    /**
     * Show/hide loading overlay
     */
    function showLoading(show) {
        if (show) {
            if (!$('.abst-replay-loading').length) {
                $('.abst-replay-iframe-wrapper').append('<div class="abst-replay-loading"></div>');
            }
        } else {
            $('.abst-replay-loading').remove();
        }
    }

    /**
     * Show error message
     */
    function showError(message) {
        showLoading(false);
        $sessionInfo.html(`<span style="color:#d63638">${message}</span>`);
    }

    /**
     * Rebuild session index
     */
    function rebuildIndex() {
        $('#rebuild-index').prop('disabled', true).text('Rebuilding...');

        $.ajax({
            url: abstSessionReplay.ajaxUrl,
            type: 'POST',
            data: {
                action: 'abst_rebuild_session_index',
                nonce: abstSessionReplay.nonce
            },
            success: function(response) {
                $('#rebuild-index').prop('disabled', false).text('Rebuild Index');
                if (response.success) {
                    alert(response.data.message);
                    loadSessions();
                } else {
                    alert('Error rebuilding index');
                }
            },
            error: function() {
                $('#rebuild-index').prop('disabled', false).text('Rebuild Index');
                alert('Error rebuilding index');
            }
        });
    }

    /**
     * Format duration in seconds to human readable
     */
    function formatDuration(seconds) {
        if (seconds < 60) {
            return seconds + 's';
        } else if (seconds < 3600) {
            const mins = Math.floor(seconds / 60);
            const secs = seconds % 60;
            return mins + 'm ' + secs + 's';
        } else {
            const hours = Math.floor(seconds / 3600);
            const mins = Math.floor((seconds % 3600) / 60);
            return hours + 'h ' + mins + 'm';
        }
    }

    /**
     * Format milliseconds to mm:ss
     */
    function formatTime(ms) {
        const totalSeconds = Math.floor(ms / 1000);
        const mins = Math.floor(totalSeconds / 60);
        const secs = totalSeconds % 60;
        return mins + ':' + (secs < 10 ? '0' : '') + secs;
    }

    // Initialize on document ready
    $(document).ready(init);

})(jQuery);
