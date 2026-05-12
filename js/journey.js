(function() {
    if (window.abstConsoleGateLoaded) return;
    window.abstConsoleGateLoaded = true;

    try {
        var params = new URLSearchParams(window.location.search);
        if (params.get('abstdebug') === '1') {
            localStorage.setItem('debug', 'true');
        }
    } catch (e) {}

    var originalLog = console.log ? console.log.bind(console) : function() {};
    console.log = function() {
        try {
            if (localStorage.getItem('debug') !== 'true') return;
        } catch (e) {
            return;
        }

        var args = Array.prototype.slice.call(arguments);
        if (typeof args[0] === 'string') {
            args[0] = args[0].replace(/^\s*ABST(?:\s+AI)?\s*:\s*/i, '');
            args[0] = 'ABST: ' + args[0];
        } else {
            args.unshift('ABST:');
        }
        originalLog.apply(console, args);
    };
})();

jQuery(document).ready(function($) {
    // Initialize Select2 for page selector with AJAX search (same as full page test selector)
    const heatmapPageAttrs = {
        dropdownAutoWidth: true,
        width: '25rem',
        placeholder: 'Please choose a page…',
        allowClear: true,
        ajax: {
            url: ajaxurl, // AJAX URL is predefined in WordPress admin
            dataType: 'json',
            delay: 250, // delay in ms while typing when to perform a AJAX search
            data: function (params) {
                return {
                    q: params.term, // search query
                    type: 'control', // 'control' or 'variations'
                    action: 'ab_page_selector', // AJAX action for admin-ajax.php
                    nonce: abst_journey_data.page_selector_nonce
                };
            },
            processResults: function( data ) {
                var options = [];
                if ( data ) {
                    // data is the array of arrays, and each of them contains ID and the Label of the option
                    $.each( data, function( index, text ) {
                        options.push( { id: text[0], text: text[1] } );
                    });
                }
                return {
                    results: options
                };
            },
            cache: true,
        },
        templateResult: function(data) {
            // Show only the label text
            if (!data || data.loading) return data && data.text ? data.text : '';
            return data.text || '';
        },
        templateSelection: function(data) {
            // Show only the selected label text; fallback to placeholder when empty
            if (!data || data.loading) return data && data.text ? data.text : '';
            return data.text || '';
        },
    };
    
    // Initialize Select2 on the page selector
    $('#abst-heatmaps-page-selector').select2(heatmapPageAttrs);
    
    // Centralized URL builder that preserves ALL parameters
    function buildHeatmapUrl() {
        const urlParams = new URLSearchParams(window.location.search);
        
        // Get current values from selectors
        const post = $('#abst-heatmaps-page-selector').val() || urlParams.get('post') || '';
        const eid = $('#abst-heatmaps-experiment-selector').val() || '';
        const variation = $('#abst-heatmaps-variation-selector').val() || '';
        const size = $('#abst-heatmaps-size-selector').val() || urlParams.get('size') || '';
        const mode = $('#abst-heatmaps-mode-selector').val() || urlParams.get('mode') || '';
        const days = $('#abst-heatmaps-days-selector').val() || urlParams.get('days') || '7';
        const conversionOnly = $('#show-conversion-traffic-only').val() || urlParams.get('cto') || '0';
        const referrer = $('#abst-heatmaps-referrer-selector').val() || urlParams.get('referrer') || '';
        const utmSource = $('#abst-heatmaps-utm-source').val() || '';
        const utmMedium = $('#abst-heatmaps-utm-medium').val() || '';
        const utmCampaign = $('#abst-heatmaps-utm-campaign').val() || '';
        
        // Build URL with all parameters
        // Note: When "All Experiments" is selected (eid is empty), we exclude both eid and variation
        let url = '?post_type=bt_experiments&page=abst-heatmaps';
        if (post) url += '&post=' + encodeURIComponent(post);
        if (eid) {
            url += '&eid=' + encodeURIComponent(eid);
            // Only include variation if experiment is selected
            if (variation) url += '&variation=' + encodeURIComponent(variation);
        }
        if (size) url += '&size=' + encodeURIComponent(size);
        if (mode) url += '&mode=' + encodeURIComponent(mode);
        if (days) url += '&days=' + encodeURIComponent(days);
        if (conversionOnly) url += '&cto=' + encodeURIComponent(conversionOnly);
        if (referrer) url += '&referrer=' + encodeURIComponent(referrer);
        if (utmSource) url += '&utm_source=' + encodeURIComponent(utmSource);
        if (utmMedium) url += '&utm_medium=' + encodeURIComponent(utmMedium);
        if (utmCampaign) url += '&utm_campaign=' + encodeURIComponent(utmCampaign);
        
        return url;
    }
    
    // Attach change handlers to all selectors
    $('#abst-heatmaps-page-selector, #abst-heatmaps-experiment-selector, #abst-heatmaps-variation-selector, #abst-heatmaps-size-selector, #abst-heatmaps-mode-selector, #abst-heatmaps-days-selector, #show-conversion-traffic-only, #abst-heatmaps-referrer-selector, #abst-heatmaps-utm-source, #abst-heatmaps-utm-medium, #abst-heatmaps-utm-campaign').on('change', function() {
        const newUrl = buildHeatmapUrl();
        console.log('Heatmap selector changed, navigating to:', newUrl);
        window.location.href = newUrl;
    });
    
    // Initialize selectors from URL parameters on page load
    const urlParams = new URLSearchParams(window.location.search);
    
    // Set size selector (default to 'large' if not specified)
    const size = urlParams.get('size') || 'large';
    $('#abst-heatmaps-size-selector').val(size);
    
    // Set mode selector (default to 'clicks' if not specified)
    const mode = urlParams.get('mode') || 'clicks';
    $('#abst-heatmaps-mode-selector').val(mode);
    
    // Set days selector (default to '7' if not specified)
    // Only set if not already selected by PHP
    const daysSelector = $('#abst-heatmaps-days-selector');
    if (daysSelector.length && !daysSelector.val()) {
        const days = urlParams.get('days') || '7';
        daysSelector.val(days);
    }
    
    // Set experiment selector if present
    const eid = urlParams.get('eid');
    if (eid) {
        $('#abst-heatmaps-experiment-selector').val(eid);
    }
    
    // Set variation selector if present
    const variation = urlParams.get('variation');
    if (variation) {
        $('#abst-heatmaps-variation-selector').val(variation);
    }
    
    // Set referrer selector if present
    const referrer = urlParams.get('referrer');
    if (referrer) {
        $('#abst-heatmaps-referrer-selector').val(referrer);
    }
    
    
    // Set page selector if present (trigger change for Select2)
    const post = urlParams.get('post');
    if (post) {
        $('#abst-heatmaps-page-selector').val(post).trigger('change.select2');
    }
});



// Heatmap rendering - overlay on iframe
document.addEventListener('DOMContentLoaded', () => {
  const wrapper = document.querySelector('.abst-heatmap-wrapper');
  const iframe = document.getElementById('abst-heatmaps-iframe');
  const heatmapContainer = document.getElementById('heatmap-container');
  const heatmapRecords = Array.isArray(window.heatmapRecords) ? window.heatmapRecords : [];
  const scrollMap = (window.scrollMap && typeof window.scrollMap === 'object') ? window.scrollMap : {};
  const modeSelectEl = document.getElementById('abst-heatmaps-mode-selector');
  const activeMode = typeof window.abstHeatmapMode === 'string' ? window.abstHeatmapMode : (modeSelectEl ? modeSelectEl.value : '');
  const isScrollMode = activeMode === 'scroll';
  const hasHeatmapLibrary = typeof h337 !== 'undefined';
  const autoRerenderToggle = document.getElementById('abst-rerender-auto');
  let autoRerenderTimer = null;
  let lastAutoRenderSize = {
    width: window.innerWidth,
    height: window.innerHeight
  };

  const updateAutoRenderSize = () => {
    lastAutoRenderSize = {
      width: window.innerWidth,
      height: window.innerHeight
    };
  };

  const isAutoRerenderEnabled = () => {
    return autoRerenderToggle && autoRerenderToggle.getAttribute('aria-pressed') === 'true';
  };

  const runAutoRerender = async () => {
    if (typeof window.abstRerenderHeatmap !== 'function') return;
    await window.abstRerenderHeatmap({ skipAnimations: true });
    updateAutoRenderSize();
  };

  const scheduleAutoRerender = (reason) => {
    if (!isAutoRerenderEnabled()) return;

    if (reason === 'resize') {
      const sizeChanged = window.innerWidth !== lastAutoRenderSize.width || window.innerHeight !== lastAutoRenderSize.height;
      if (!sizeChanged) return;
    }

    clearTimeout(autoRerenderTimer);
    autoRerenderTimer = setTimeout(async () => {
      if (!isAutoRerenderEnabled()) return;
      await runAutoRerender();
    }, 1000);
  };

  if (autoRerenderToggle) {
    autoRerenderToggle.addEventListener('click', () => {
      const enabled = !isAutoRerenderEnabled();
      autoRerenderToggle.setAttribute('aria-pressed', enabled ? 'true' : 'false');
      updateAutoRenderSize();
    });
    window.addEventListener('scroll', () => scheduleAutoRerender('scroll'), { passive: true });
    window.addEventListener('resize', () => scheduleAutoRerender('resize'), { passive: true });
  }

  if (!wrapper || !iframe || !heatmapContainer) {
    console.log('Heatmap init aborted - missing required elements');
    return;
  }

  if (!isScrollMode && (!hasHeatmapLibrary || heatmapRecords.length === 0)) {
    console.log('Heatmap init aborted - missing library or no click data', {
      hasHeatmapLibrary,
      recordCount: heatmapRecords.length
    });
    return;
  }

  const renderScrollOverlay = (pageHeight) => {
    const distribution = Array.isArray(scrollMap.distribution) ? scrollMap.distribution : [];
    if (!distribution.length) {
      console.log('Scroll map rendering skipped - no distribution data');
      return;
    }

    heatmapContainer.innerHTML = '';
    
    // Calculate viewport offset with pixel constraints (400px min, 1000px max)
    const minOffsetPx = 400;
    const maxOffsetPx = 1000;
    const targetOffsetPx = Math.max(minOffsetPx, Math.min(maxOffsetPx, pageHeight * 0.10));
    const viewportOffsetPercent = (targetOffsetPx / pageHeight) * 100;
    
    // Build lookup map from distribution buckets
    const viewerMap = new Map();
    distribution.forEach((bucket) => {
      const start = Number.isFinite(bucket.start) ? bucket.start : 0;
      const end = Number.isFinite(bucket.end) ? bucket.end : start;
      const viewers = Number(bucket.percent);
      
      if (Number.isFinite(viewers)) {
        for (let pos = start; pos <= end; pos++) {
          viewerMap.set(pos, viewers);
        }
      }
    });
    
    // Helper function to interpolate between color stops
    const getColorForViewers = (viewers) => {
      // Color scale: 100% (blue) → 75% (cyan) → 50% (green) → 25% (yellow) → 12.5% (orange) → 0% (red)
      const colorStops = [
        { viewers: 1.00, r: 107, g: 141, b: 214 }, // #6B8DD6 - Blue (100%)
        { viewers: 0.75, r: 79,  g: 195, b: 220 }, // #4FC3DC - Cyan (75%)
        { viewers: 0.50, r: 95,  g: 211, b: 141 }, // #5FD38D - Green (50%)
        { viewers: 0.25, r: 249, g: 230, b: 92  }, // #F9E65C - Yellow (25%)
        { viewers: 0.125, r: 255, g: 154, b: 86 }, // #FF9A56 - Orange (12.5%)
        { viewers: 0.00, r: 255, g: 107, b: 107 }  // #FF6B6B - Red (0%)
      ];
      
      // Find the two color stops to interpolate between
      let lower = colorStops[colorStops.length - 1];
      let upper = colorStops[0];
      
      for (let i = 0; i < colorStops.length - 1; i++) {
        if (viewers >= colorStops[i + 1].viewers && viewers <= colorStops[i].viewers) {
          upper = colorStops[i];
          lower = colorStops[i + 1];
          break;
        }
      }
      
      // Interpolate between the two colors
      const range = upper.viewers - lower.viewers;
      const t = range === 0 ? 0 : (viewers - lower.viewers) / range;
      
      const r = Math.round(lower.r + (upper.r - lower.r) * t);
      const g = Math.round(lower.g + (upper.g - lower.g) * t);
      const b = Math.round(lower.b + (upper.b - lower.b) * t);
      
      return `rgba(${r}, ${g}, ${b}, 0.7)`;
    };
    
    // Sample at 3% intervals for smooth gradient
    // Offset the gradient start to account for initial viewport (0% scroll = page load)
    // Viewport offset is calculated with 400px min, 1000px max constraints
    const gradientStops = [];
    
    // Add solid color from top to viewport offset (representing 0% scroll position)
    const zeroScrollViewers = viewerMap.get(0) !== undefined ? viewerMap.get(0) : 1.0;
    const zeroScrollColor = getColorForViewers(zeroScrollViewers);
    gradientStops.push(`${zeroScrollColor} 0%`);
    gradientStops.push(`${zeroScrollColor} ${viewportOffsetPercent}%`);
    
    for (let pos = 0; pos <= 100; pos += 3) {
      // Find closest viewer percentage
      let viewers = viewerMap.get(pos);

      if (viewers !== undefined) {
        const color = getColorForViewers(viewers);
        // Shift all positions down by viewport offset
        const adjustedPos = viewportOffsetPercent + (pos * (100 - viewportOffsetPercent) / 100);
        gradientStops.push(`${color} ${adjustedPos}%`);
      }
    }
    
    // Apply CSS gradient to container
    if (gradientStops.length > 0) {
      heatmapContainer.style.background = `linear-gradient(to bottom, ${gradientStops.join(', ')})`;
      heatmapContainer.style.pointerEvents = 'none';
    }
  };

  // Trigger scroll-based animations in iframe (GSAP, Elementor, Beaver, etc.)
  // Scrolls to bottom and back to trigger IntersectionObserver and scroll listeners
  // Returns the final page height after animations complete
  const triggerAnimations = async (iframeWin, iframeDoc) => {
    // Use a very large scroll target to ensure we reach the true bottom
    // even if content expands during animation
    const initialHeight = iframeDoc.body.scrollHeight;
    const scrollTarget = Math.max(initialHeight, 50000); // At least 50000px to catch everything
    const scrollTime = Math.ceil(initialHeight / 1000) * 1000; // 1s per 1000px of initial height
    
    console.log(`Triggering animations: scrolling to ${scrollTarget}px over ${scrollTime}ms`);
    
    // Smooth scroll to bottom - triggers all scroll-based animations
    iframeWin.scrollTo({ top: scrollTarget, behavior: 'smooth' });
    await new Promise(r => setTimeout(r, scrollTime));

    try {
      const scrollTrigger = iframeWin.ScrollTrigger;
      const triggers = scrollTrigger && typeof scrollTrigger.getAll === 'function'
        ? scrollTrigger.getAll()
        : [];
      if (Array.isArray(triggers) && triggers.length > 0) {
        triggers.forEach(trigger => {
          if (trigger && typeof trigger.disable === 'function') {
            trigger.disable(false);
          }
        });
      }
    } catch (e) {
      console.warn('Could not freeze GSAP ScrollTrigger state for heatmap preview:', e);
    }
    
    // Back to top
    iframeWin.scrollTo({ top: 0, behavior: 'smooth' });
    await new Promise(r => setTimeout(r, 500));
    
    // Return the new height after animations have completed
    return Math.max(
      iframeDoc.body.scrollHeight,
      iframeDoc.body.offsetHeight,
      iframeDoc.documentElement.clientHeight,
      iframeDoc.documentElement.scrollHeight,
      iframeDoc.documentElement.offsetHeight
    );
  };

  iframe.addEventListener('load', async () => {
    const doc = iframe.contentDocument;
    const win = iframe.contentWindow;

    if (!doc || !doc.body) {
      console.warn('Iframe loaded but document not ready');
      return;
    }

    const body = doc.body;
    const html = doc.documentElement;
    
    // Aggressively fix viewport-relative units (vh, vw, vmin, vmax) before measuring
    // These cause infinite expansion when iframe height is dynamic
    // We need to convert ALL vh/vw units to fixed pixel values
    const viewportHeight = window.innerHeight * 0.8; // Use 80% of parent window's viewport for vh calculations
    const viewportWidth = window.innerWidth * 0.8; // Use 80% for vw as well
    const style = doc.createElement('style');
    style.id = 'abst-vh-fix';
    
    // Override vh/vw with CSS custom properties and force common patterns to use fixed heights
    style.textContent = `
      /* CSS custom properties for viewport units */
      :root {
        --abst-vh: ${viewportHeight / 100}px;
        --abst-vw: ${viewportWidth / 100}px;
        --abst-100vh: ${viewportHeight}px;
        --abst-100vw: ${viewportWidth}px;
      }
      
      /* Force elements with inline vh/vw styles to use auto height */
      [style*="vh"], [style*="vw"], [style*="vmin"], [style*="vmax"] {
        height: auto !important;
        min-height: 0 !important;
        max-height: none !important;
      }
      
    `;
    doc.head.appendChild(style);
    
    // Also walk through all stylesheets and try to neutralize vh rules
    // This catches CSS-defined vh values, not just inline styles
    try {
      const sheets = doc.styleSheets;
      for (let i = 0; i < sheets.length; i++) {
        try {
          const rules = sheets[i].cssRules || sheets[i].rules;
          if (!rules) continue;
          
          for (let j = 0; j < rules.length; j++) {
            const rule = rules[j];
            if (rule.style) {
              // Check common height properties for vh units
              ['height', 'minHeight', 'maxHeight'].forEach(prop => {
                const val = rule.style[prop];
                if (val && /\d(vh|vw|vmin|vmax|svh|svw|dvh|dvw|lvh|lvw)\b/.test(val)) {
                  // Convert all viewport units to px (including svh/dvh/lvh variants)
                  const converted = val
                    .replace(/(\d+(?:\.\d+)?)(svh|dvh|lvh|vh)/g, (m, n) => (parseFloat(n) * viewportHeight / 100) + 'px')
                    .replace(/(\d+(?:\.\d+)?)(svw|dvw|lvw|vw)/g, (m, n) => (parseFloat(n) * viewportWidth / 100) + 'px')
                    .replace(/(\d+(?:\.\d+)?)vmin/g, (m, n) => (parseFloat(n) * Math.min(viewportHeight, viewportWidth) / 100) + 'px')
                    .replace(/(\d+(?:\.\d+)?)vmax/g, (m, n) => (parseFloat(n) * Math.max(viewportHeight, viewportWidth) / 100) + 'px');
                  rule.style[prop] = converted;
                }
              });
            }
          }
        } catch (e) {
          // Cross-origin stylesheets will throw - ignore them
        }
      }
    } catch (e) {
      console.warn('Could not process stylesheets for vh fix:', e);
    }
    
    // Force reflow to apply styles
    void html.offsetHeight;

    let cleanupRender = null;
    let renderPromise = null;
    let animationsPrimed = false;
    let mutationTimer = null;

    const measurePageHeight = () => Math.max(
      body.scrollHeight,
      body.offsetHeight,
      html.clientHeight,
      html.scrollHeight,
      html.offsetHeight
    );

    const doRender = async (options = {}) => {
      const skipAnimations = typeof options === 'boolean' ? options : !!options.skipAnimations;

      if (cleanupRender) {
        cleanupRender();
        cleanupRender = null;
      }

      // For click-based heatmaps, trigger animations once before measuring.
      let height;
      if (!isScrollMode && !skipAnimations && !animationsPrimed) {
        height = await triggerAnimations(win, doc);
        animationsPrimed = true;
        console.log('Post-animation height:', height);
      } else {
        height = measurePageHeight();
      }

    iframe.style.height = height + 'px';
    wrapper.style.height = height + 'px';
    heatmapContainer.style.height = height + 'px';
    heatmapContainer.style.position = 'absolute';
    heatmapContainer.style.top = '0';
    heatmapContainer.style.left = '0';

    if (isScrollMode) {
      console.log('Rendering scroll map overlay');
      renderScrollOverlay(height);
      return;
    }

    if (!hasHeatmapLibrary) {
      console.warn('heatmap.js not loaded - cannot render heatmap');
      return;
    }

    heatmapContainer.innerHTML = '';

    let radius = 80;
    let maxOpacity = 0.8;
    let minOpacity = 0;
    let blur = 0.75;
    console.log('Heatmap mode:', activeMode);
    if (activeMode === 'confetti') {
      radius = 8;
      maxOpacity = 0.9;
      minOpacity = 0;
      blur = 0.1;
    }

    // Rage click mode: Use red gradient to highlight frustration points
    // Dead click mode: Use orange gradient to highlight confusion points
    let gradient = null;
    if (activeMode === 'rage') {
      gradient = {
        0.0: 'rgba(255, 0, 0, 0)',
        0.3: 'rgba(255, 100, 100, 0.5)',
        0.6: 'rgba(255, 50, 50, 0.7)',
        1.0: 'rgba(255, 0, 0, 0.9)'
      };
      radius = 35; // Larger radius for rage clicks
      maxOpacity = 0.9;
    } else if (activeMode === 'dead') {
      gradient = {
        0.0: 'rgba(255, 165, 0, 0)',      // Transparent orange
        0.3: 'rgba(255, 165, 0, 0.5)',    // Light orange
        0.6: 'rgba(255, 140, 0, 0.7)',    // Medium orange
        1.0: 'rgba(255, 120, 0, 0.9)'     // Dark orange
      };
      radius = 30; // Slightly larger radius for dead clicks
      maxOpacity = 0.85;
    }

    const heatmap = h337.create({
      container: heatmapContainer,
      radius: radius,
      maxOpacity: maxOpacity,
      minOpacity: minOpacity,
      blur: blur,
      dotColor: '#FF6B6B',
      gradient: gradient // Only set for rage mode
    });

    if (activeMode === 'predictiveclicks' || activeMode === 'predictivefocus') {
      console.log('Taking screenshot for predictive heatmap - waiting for iframe to fully render...');
      if (typeof modernScreenshot !== 'undefined') {
        setTimeout(async () => {
          const triggerAnimations = async () => {
            const scrollHeight = wrapper.scrollHeight;

            window.scrollTo({
              top: scrollHeight,
              behavior: 'smooth'
            });

            await new Promise((resolve) => setTimeout(resolve, 3000));

            window.scrollTo({
              top: 0,
              behavior: 'smooth'
            });

            await new Promise((resolve) => setTimeout(resolve, 3000));
          };

          await triggerAnimations();

          const screenshotOptions = {
            quality: 0.8,
            width: iframe.offsetWidth,
            height: height,
            debug: true,
            timeout: 4000,
            skipAutoScale: true,
            fetchRequestTimeout: 3000,
            filter: (node) => {
              const exclusionClasses = ['admin-bar', 'abst-magic-bar'];
              return !exclusionClasses.some((classname) => node.classList?.contains(classname));
            }
          };

          modernScreenshot.domToPng(doc.body, screenshotOptions)
            .then((dataUrl) => {
              window.abstHeatmapScreenshot = dataUrl;

              let points = [
                { x: 25, y: 12, value: 1.0 }, { x: 27, y: 11, value: 0.95 }, { x: 23, y: 13, value: 0.92 },
                { x: 70, y: 12, value: 0.95 }, { x: 68, y: 11, value: 0.9 }, { x: 72, y: 13, value: 0.88 },
                { x: 95, y: 3, value: 0.8 }, { x: 93, y: 5, value: 0.75 }, { x: 97, y: 2, value: 0.7 },
                { x: 5, y: 3, value: 0.65 }, { x: 8, y: 5, value: 0.6 }
              ];

              points = points.map((point) => ({
                x: (point.x / 100) * iframe.offsetWidth,
                y: (point.y / 100) * iframe.offsetHeight,
                value: point.value
              }));

              heatmap.setData({ max: 1, data: points });
            })
            .catch((error) => {
              console.error('Error capturing screenshot for predictive heatmap:', error);
            });
        }, 1000);
      } else {
        console.warn('modernScreenshot library not loaded - cannot capture screenshot');
      }
      return;
    }

    const points = [];
    let skippedCount = 0;
    const isConfetti = activeMode === 'confetti';

    heatmapRecords.forEach((record) => {
      const selector = record.selector;
      const percentX = parseFloat(record.percentX);
      const percentY = parseFloat(record.percentY);

      if (!selector || !Number.isFinite(percentX) || !Number.isFinite(percentY)) {
        skippedCount++;
        return;
      }

      try {
        const el = doc.querySelector(selector);
        if (!el) {
          skippedCount++;
          return;
        }
        const rect = el.getBoundingClientRect();
        if (rect.width > 0 && rect.height > 0) {
          const x = Math.round(rect.left + rect.width * percentX + win.scrollX);
          const y = Math.round(rect.top + rect.height * percentY + win.scrollY);

          if (Number.isFinite(x) && Number.isFinite(y) && x >= 0 && y >= 0) {
            points.push({ x, y, value: isConfetti ? 1 : 0.5 });
          } else {
            skippedCount++;
          }
        } else {
          skippedCount++;
        }
      } catch (e) {
        skippedCount++;
      }
    });

    console.log(`Heatmap: ${points.length} points computed`);

    if (points.length === 0) {
      console.log('No valid points - heatmap skipped');
      return;
    }

    heatmap.setData({ max: 1, data: points });

    const canvas = heatmapContainer.querySelector('canvas');
    if (canvas) {
      canvas.style.width = '100%';
      canvas.style.height = '100%';
      canvas.style.position = 'absolute';
      canvas.style.top = '0';
      canvas.style.left = '0';
    }

    console.log('✅ Heatmap rendered:', points.length, 'points');

    // --- Hover tooltip: show click count per element ---
    const clickCounts = window.heatmapClickCounts || {};
    const selectors = Object.keys(clickCounts);
    if (selectors.length === 0) return;

    // Compute total clicks across all selectors for percentage display
    var totalAllClicks = 0;
    for (var tk = 0; tk < selectors.length; tk++) {
      totalAllClicks += clickCounts[selectors[tk]] || 0;
    }

    // Build a pre-validated list of selectors (skip any that are invalid CSS)
    const validSelectors = [];
    selectors.forEach(function(sel) {
      try {
        doc.querySelector(sel); // test validity
        validSelectors.push(sel);
      } catch (e) {
        // invalid selector, skip
      }
    });
    if (validSelectors.length === 0) return;

    // Pre-cache bounding rects for all elements matching click selectors.
    // Used as fallback when elementFromPoint returns body/html (scrolled area).
    var cachedRects = [];
    var iframeScrollXInit = win.scrollX || 0;
    var iframeScrollYInit = win.scrollY || 0;
    for (var vi = 0; vi < validSelectors.length; vi++) {
      try {
        var matchedEls = doc.querySelectorAll(validSelectors[vi]);
        for (var mi = 0; mi < matchedEls.length; mi++) {
          var r = matchedEls[mi].getBoundingClientRect();
          if (r.width > 0 && r.height > 0) {
            cachedRects.push({
              el: matchedEls[mi],
              left: r.left + iframeScrollXInit,
              top: r.top + iframeScrollYInit,
              right: r.left + iframeScrollXInit + r.width,
              bottom: r.top + iframeScrollYInit + r.height,
              area: r.width * r.height
            });
          }
        }
      } catch (e) { /* skip invalid */ }
    }

    // Create transparent interaction layer on top of everything
    const interactionLayer = document.createElement('div');
    interactionLayer.id = 'abst-heatmap-interaction';
    interactionLayer.style.cssText = 'position:absolute;top:0;left:0;width:100%;height:100%;z-index:10;cursor:crosshair;';
    wrapper.appendChild(interactionLayer);

    // Create tooltip element with styled inner structure
    const tooltip = document.createElement('div');
    tooltip.id = 'abst-heatmap-tooltip';
    tooltip.style.cssText = 'position:fixed;z-index:100000;pointer-events:none;background:rgba(30,30,30,0.92);color:#fff;padding:8px 14px;border-radius:6px;font-size:13px;font-family:-apple-system,BlinkMacSystemFont,sans-serif;line-height:1.5;white-space:nowrap;display:none;box-shadow:0 2px 12px rgba(0,0,0,0.35);max-width:420px;';
    var tipCount = document.createElement('span');
    tipCount.style.cssText = 'font-weight:700;font-size:15px;';
    var tipPercent = document.createElement('span');
    tipPercent.style.cssText = 'color:rgba(255,255,255,0.6);margin-left:6px;font-size:12px;';
    var tipDesc = document.createElement('div');
    tipDesc.style.cssText = 'color:rgba(255,255,255,0.75);font-size:12px;margin-top:2px;overflow:hidden;text-overflow:ellipsis;';
    tooltip.appendChild(tipCount);
    tooltip.appendChild(tipPercent);
    tooltip.appendChild(tipDesc);
    document.body.appendChild(tooltip);

    // Create highlight overlay inside iframe document
    var highlightEl = null;
    try {
      highlightEl = doc.createElement('div');
      highlightEl.id = 'abst-heatmap-highlight';
      highlightEl.style.cssText = 'position:absolute;pointer-events:none;z-index:2147483647;border:2px solid rgba(59,130,246,0.8);background:rgba(59,130,246,0.08);border-radius:3px;display:none;transition:all 0.1s ease;box-shadow:0 0 0 1px rgba(59,130,246,0.3);';
      doc.body.appendChild(highlightEl);
    } catch (e) {
      highlightEl = null;
    }

    let lastHoveredEl = null;
    let lastHighlightedEl = null;
    let throttleTimer = null;

    // Get a human-readable description of an element
    function describeElement(el) {
      if (!el || !el.tagName) return 'element';
      var tag = el.tagName.toLowerCase();
      var desc = tag;
      try {
        if (el.id) desc = tag + '#' + el.id;
        else if (el.className && typeof el.className === 'string') {
          var cls = el.className.trim().split(/\s+/).slice(0, 2).join('.');
          if (cls) desc = tag + '.' + cls;
        }
        // Add text hint for links/buttons
        var text = (el.textContent || '').trim();
        if (text.length > 0 && text.length <= 40) {
          desc += ' ("' + text + '")';
        } else if (text.length > 40) {
          desc += ' ("' + text.substring(0, 37) + '\u2026")';
        }
      } catch (e) {
        // SVG elements or other edge cases
      }
      return desc;
    }

    // Find total clicks for an element by testing which selectors match it
    function getClicksForElement(el) {
      if (!el || typeof el.matches !== 'function') return { total: 0, selectors: [] };
      var totalClicks = 0;
      var matchedSelectors = [];

      for (var i = 0; i < validSelectors.length; i++) {
        var sel = validSelectors[i];
        try {
          if (el.matches(sel)) {
            totalClicks += clickCounts[sel];
            matchedSelectors.push(sel);
          }
        } catch (e) {
          // skip invalid selectors for matches()
        }
      }
      return { total: totalClicks, selectors: matchedSelectors };
    }

    interactionLayer.addEventListener('mousemove', function(e) {
      if (throttleTimer) return;
      throttleTimer = setTimeout(function() { throttleTimer = null; }, 80);

      // Calculate position relative to the iframe's content
      // The iframe is stretched to full page height with no internal scrollbar,
      // so we need to find the absolute position within the iframe document.
      var iframeRect = iframe.getBoundingClientRect();
      var absX = e.clientX - iframeRect.left;
      var absY = e.clientY - iframeRect.top;

      // elementFromPoint uses viewport-relative coordinates within the iframe.
      // Since the iframe is fully expanded (height = page height), its internal
      // viewport matches its full content. But elementFromPoint still uses the
      // iframe's own viewport coords, so we need to account for the iframe's
      // scroll position (which should be 0 since we scrolled back to top).
      var iframeScrollX = win.scrollX || 0;
      var iframeScrollY = win.scrollY || 0;
      var pointX = absX + iframeScrollX;
      var pointY = absY + iframeScrollY;

      // Find element at this position in the iframe document
      var hoveredEl = null;
      try {
        hoveredEl = doc.elementFromPoint(absX, absY);
        
        // If elementFromPoint returns body/html/null (point outside visible area),
        // fall back to cached element rects for hit-testing
        if (!hoveredEl || hoveredEl === doc.body || hoveredEl === doc.documentElement) {
          var bestMatch = null;
          var bestArea = Infinity;
          for (var ci = 0; ci < cachedRects.length; ci++) {
            var cr = cachedRects[ci];
            if (pointX >= cr.left && pointX <= cr.right && pointY >= cr.top && pointY <= cr.bottom) {
              if (cr.area < bestArea) {
                bestArea = cr.area;
                bestMatch = cr.el;
              }
            }
          }
          if (bestMatch) {
            hoveredEl = bestMatch;
          } else {
            // No clickable element found at this position — hide tooltip
            hoveredEl = null;
          }
        }
      } catch (ex) {
        // cross-origin or other error
      }

      if (!hoveredEl) {
        tooltip.style.display = 'none';
        if (highlightEl) highlightEl.style.display = 'none';
        lastHoveredEl = null;
        lastHighlightedEl = null;
        return;
      }
      if (hoveredEl === lastHoveredEl) return;
      lastHoveredEl = hoveredEl;

      // Walk up from the hovered element to find the best match
      // Start with the exact element, then try parents up to 5 levels
      var bestResult = null;
      var bestEl = null;
      var current = hoveredEl;
      var depth = 0;

      while (current && current !== doc.body && current !== doc.documentElement && depth < 6) {
        var result = getClicksForElement(current);
        if (result.total > 0) {
          bestResult = result;
          bestEl = current;
          break; // Use the most specific (deepest) match
        }
        current = current.parentElement;
        depth++;
      }

      if (!bestResult || bestResult.total === 0) {
        tooltip.style.display = 'none';
        if (highlightEl) highlightEl.style.display = 'none';
        lastHighlightedEl = null;
        return;
      }

      // Build tooltip content using DOM nodes (safe, no XSS risk)
      var desc = describeElement(bestEl);
      var clickLabel = bestResult.total === 1 ? 'click' : 'clicks';
      tipCount.textContent = bestResult.total + ' ' + clickLabel;
      if (totalAllClicks > 0) {
        var pct = ((bestResult.total / totalAllClicks) * 100).toFixed(1);
        tipPercent.textContent = '(' + pct + '% of all clicks)';
      } else {
        tipPercent.textContent = '';
      }
      tipDesc.textContent = desc;

      // Highlight the matched element in the iframe
      if (highlightEl && bestEl !== lastHighlightedEl) {
        try {
          var elRect = bestEl.getBoundingClientRect();
          var scrollX = win.scrollX || 0;
          var scrollY = win.scrollY || 0;
          highlightEl.style.left = (elRect.left + scrollX - 2) + 'px';
          highlightEl.style.top = (elRect.top + scrollY - 2) + 'px';
          highlightEl.style.width = (elRect.width + 4) + 'px';
          highlightEl.style.height = (elRect.height + 4) + 'px';
          highlightEl.style.display = 'block';
          lastHighlightedEl = bestEl;
        } catch (e) {
          highlightEl.style.display = 'none';
        }
      }

      // Position tooltip near cursor
      var tipX = e.clientX + 14;
      var tipY = e.clientY - 30;

      // Keep tooltip on screen
      tooltip.style.display = 'block';
      var tipRect = tooltip.getBoundingClientRect();
      if (tipX + tipRect.width > window.innerWidth - 10) {
        tipX = e.clientX - tipRect.width - 14;
      }
      if (tipY < 10) {
        tipY = e.clientY + 20;
      }

      tooltip.style.left = tipX + 'px';
      tooltip.style.top = tipY + 'px';
    });

    interactionLayer.addEventListener('mouseleave', function() {
      tooltip.style.display = 'none';
      if (highlightEl) highlightEl.style.display = 'none';
      lastHoveredEl = null;
      lastHighlightedEl = null;
    });

    cleanupRender = () => {
      interactionLayer.remove();
      tooltip.remove();
      try { if (highlightEl) highlightEl.remove(); } catch (e) {}
    };
    }; // end doRender

    const requestRender = (options = {}) => {
      if (renderPromise) {
        return renderPromise;
      }

      renderPromise = doRender(options).finally(() => {
        renderPromise = null;
      });

      return renderPromise;
    };

    window.abstRerenderHeatmap = requestRender;

    const rerenderBtn = document.getElementById('abst-rerender-btn');

    if (rerenderBtn) {
      rerenderBtn.addEventListener('click', async () => {
        rerenderBtn.disabled = true;
        rerenderBtn.textContent = 'Re-rendering...';
        try {
          await requestRender({ skipAnimations: true });
          updateAutoRenderSize();
        } finally {
          rerenderBtn.disabled = false;
          rerenderBtn.textContent = '\u21ba Re-render';
        }
      });
    }

    await requestRender();
    updateAutoRenderSize();

    // Fallback for non-GSAP animations: debounce late style/class changes on
    // tracked elements and redraw after the layout has settled.
    const scheduleMutationRender = () => {
      clearTimeout(mutationTimer);
      mutationTimer = setTimeout(async () => {
        heatmapContainer.style.transition = 'opacity 0.2s';
        heatmapContainer.style.opacity = '0';
        await new Promise(resolve => setTimeout(resolve, 200));
        try {
          await requestRender({ skipAnimations: true });
        } finally {
          heatmapContainer.style.opacity = '1';
        }
      }, 800);
    };

    if (!isScrollMode && heatmapRecords.length > 0) {
      const mutationObserver = new MutationObserver(scheduleMutationRender);
      const seenSelectors = new Set();
      heatmapRecords.forEach(record => {
        if (!record.selector || seenSelectors.has(record.selector)) return;
        seenSelectors.add(record.selector);
        try {
          const el = doc.querySelector(record.selector);
          if (el) mutationObserver.observe(el, { attributes: true, attributeFilter: ['style', 'class'] });
        } catch (e) {}
      });
    }

  }, { once: true });
});
