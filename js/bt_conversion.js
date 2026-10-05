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

// Mark first variation of each experiment to prevent CLS (Cumulative Layout Shift)
// This runs immediately during parse, before DOMContentLoaded
(function() {
  'use strict';
  
  // Track which experiments we've seen
  var seenExperiments = {};
  
  // Function to mark first variations
  function markFirstVariations() {
    function getExperimentId(el) {
      var eid = el.getAttribute('bt-eid') || el.getAttribute('data-bt-eid');

      if (eid) {
        return eid;
      }

      var className = typeof el.className === 'string' ? el.className : '';
      var match = className.match(/(?:^|\s)ab-(\d+)(?:\s|$)/);

      if (match && match[1]) {
        return match[1];
      }

      return '';
    }
 
    // Get all elements with variation attributes
    var elements = document.querySelectorAll('[bt-variation], [data-bt-variation], [class*="ab-var-"]');
    
    elements.forEach(function(el) {
      // Get experiment ID
      var eid = getExperimentId(el);
      
      if (!eid) return;
      
      // If this is the first time we've seen this experiment ID
      if (!seenExperiments[eid]) {
        el.classList.add('bt-first-variation'); 
        seenExperiments[eid] = true;
      }
    });
  }
  
  // Run immediately if DOM is already parsed
  if (document.readyState === 'loading') {
    // DOM still loading - run as soon as it's interactive
    document.addEventListener('DOMContentLoaded', markFirstVariations);
  } else {
    // DOM already loaded - run now
    markFirstVariations();
  }
})();

// Poll for ABST_CONFIG if cache plugins defer/delay our inline scripts
// This handles LiteSpeed, WP Rocket delay, etc. that may load config after this script
(function() {
  var maxWait = 15000; // Script-delay plugins commonly fire at 3-10s - outlast them
  var interval = 50;   // Check every 50ms
  var waited = 0;
  
  // Only the wp_localize_script payload counts. The legacy abst_variables
  // inline also sets window.btab_vars, and when an optimizer (Flying Scripts,
  // WP Rocket delay, NitroPack...) delays ONE of the two scripts, the legacy
  // globals made this report "ready" while bt_experiments was still empty -
  // the poll stopped, the empty run latched ab-test-setup-complete, and tests
  // never processed when the real config arrived. Legacy-only installs set
  // all their globals at parse time and never needed the poll.
  function configReady() {
    return !!(window.ABST_CONFIG && window.ABST_CONFIG.btab_vars);
  }
  
  function hasExperiments() {
    return window.bt_experiments && Object.keys(window.bt_experiments).length > 0;
  }
  
  if (!configReady()) {
    console.log('ABST: Config not ready, polling...');
    var poll = setInterval(function() {
      waited += interval;
      if (configReady() || waited >= maxWait) {
        clearInterval(poll);
        if (configReady()) {
          console.log('ABST: Config loaded after ' + waited + 'ms, reinitializing...');
          // Re-initialize config variables
          window.abstInitConfig();
          
          // If DOMContentLoaded already fired and we now have experiments,
          // trigger the experiment setup that was missed. The DOM-ready run may
          // already have executed with ZERO experiments and latched the
          // setup-complete class - that run did no test work, so lift the latch
          // and let the init process for real this time.
          if (document.readyState !== 'loading' && hasExperiments()) {
            console.log('ABST: Running delayed experiment initialization...');
            if (document.body) {
              document.body.classList.remove('ab-test-setup-complete');
            }
            // Dispatch a custom event that our DOMContentLoaded handler can listen for
            document.dispatchEvent(new Event('abst-config-ready'));
          }
        } else {
          console.warn('ABST: Config not found after ' + maxWait + 'ms timeout');
        }
      }
    }, interval);
  }
})();

// Function to initialize/reinitialize config (called immediately and after polling)
window.abstInitConfig = function() {
  window.ABST_CONFIG = window.ABST_CONFIG || {};
  window.btab_vars = window.ABST_CONFIG.btab_vars || window.btab_vars || {};
  window.bt_experiments = window.ABST_CONFIG.bt_experiments || window.bt_experiments || {};
  window.conversion_details = window.ABST_CONFIG.conversion_details || window.conversion_details || {};
  window.current_page = window.ABST_CONFIG.current_page || window.current_page || [];
  window.bt_ajaxurl = window.ABST_CONFIG.ajaxurl || window.bt_ajaxurl || '';
  window.bt_adminurl = window.ABST_CONFIG.adminurl || window.bt_adminurl || '';
  window.bt_pluginurl = window.ABST_CONFIG.pluginurl || window.bt_pluginurl || '';
  window.bt_homeurl = window.ABST_CONFIG.homeurl || window.bt_homeurl || '';
  // Update local aliases so code using var references sees the new data
  ABST_CONFIG = window.ABST_CONFIG;
  btab_vars = window.btab_vars;
  bt_experiments = window.bt_experiments;
  conversion_details = window.conversion_details;
  current_page = window.current_page;
  bt_ajaxurl = window.bt_ajaxurl;
  bt_adminurl = window.bt_adminurl;
  bt_pluginurl = window.bt_pluginurl;
  bt_homeurl = window.bt_homeurl;
};

// Extract config from wp_localize_script output (new method)
// Falls back to legacy inline script variables for backwards compatibility
// IMPORTANT: Use window.* for all config so late-loading deferred scripts can update them
window.ABST_CONFIG = window.ABST_CONFIG || {};
window.btab_vars = window.ABST_CONFIG.btab_vars || window.btab_vars || {};
window.bt_experiments = window.ABST_CONFIG.bt_experiments || window.bt_experiments || {};
window.conversion_details = window.ABST_CONFIG.conversion_details || window.conversion_details || {};
window.current_page = window.ABST_CONFIG.current_page || window.current_page || [];
window.bt_ajaxurl = window.ABST_CONFIG.ajaxurl || window.bt_ajaxurl || '';
window.bt_adminurl = window.ABST_CONFIG.adminurl || window.bt_adminurl || '';
window.bt_pluginurl = window.ABST_CONFIG.pluginurl || window.bt_pluginurl || '';
window.bt_homeurl = window.ABST_CONFIG.homeurl || window.bt_homeurl || '';

// Local aliases for backwards compatibility with existing code
var ABST_CONFIG = window.ABST_CONFIG;
var btab_vars = window.btab_vars;
var bt_experiments = window.bt_experiments;
var conversion_details = window.conversion_details;
var current_page = window.current_page;
var bt_ajaxurl = window.bt_ajaxurl;
var bt_adminurl = window.bt_adminurl;
var bt_pluginurl = window.bt_pluginurl;
var bt_homeurl = window.bt_homeurl;

// global vars
window.abst = window.abst || {};
window.abst.ignoreSelectorPrefixes = ['abst-variation','stk-'];
window.abst.clickRegister = window.abst.clickRegister || {};
window.abst.heatScrollLastSent = window.abst.heatScrollLastSent || 0;
window.abst.invalidVariationWarnings = window.abst.invalidVariationWarnings || {};
window.abst.invalidExperimentIdWarnings = window.abst.invalidExperimentIdWarnings || {};

function abstHasSafeVariationCharacters(value) {
  return typeof value === 'string' && /^[A-Za-z0-9 _-]+$/.test(value);
}

function abstHasSafeExperimentId(value) {
  return typeof value === 'string' && /^[0-9]+$/.test(value);
}

function abstWarnOnUnsafeVariationNames() {
  document.querySelectorAll('[bt-variation]:not([bt-variation=""])').forEach(function (el) {
    var variation = el.getAttribute('bt-variation');
    if (!variation || abstHasSafeVariationCharacters(variation) || window.abst.invalidVariationWarnings[variation]) {
      return;
    }

    window.abst.invalidVariationWarnings[variation] = true;
    console.warn('ABST: WARNING: Use of unallowed characters in bt_variation "' + variation + '". Use only letters, numbers, spaces, underscores, and hyphens. Other characters may not match correctly in monitoring or server-side logging.', el);
  });
}

function abstWarnOnUnsafeExperimentIds() {
  document.querySelectorAll('[bt-eid]:not([bt-eid=""])').forEach(function (el) {
    var experimentId = el.getAttribute('bt-eid');
    if (!experimentId || abstHasSafeExperimentId(experimentId) || window.abst.invalidExperimentIdWarnings[experimentId]) {
      return;
    }

    window.abst.invalidExperimentIdWarnings[experimentId] = true;
    console.warn('ABST: WARNING: Use of invalid bt_eid "' + experimentId + '". The bt_eid value should be an integer experiment ID. Non-integer values may not match correctly in monitoring or server-side logging.', el);
  });
}

if(btab_vars && btab_vars.wait_for_approval == '1') {
  window.abst.hasApproval = localStorage.getItem('abstApprovalStatus') === 'approved';
}
else {
  window.abst.hasApproval = true;
}

function setAbCrypto() {
  if (!abstGetAdvancedId()) {
    let fp;
    if(crypto.randomUUID) {
      try {
        fp = crypto.randomUUID();
      } catch (e) { // localhost, http
        fp = "10000000-1000-4000-8000-100000000000".replace(/[018]/g, c => (+c ^ crypto.getRandomValues(new Uint8Array(1))[0] & 15 >> +c / 4).toString(16));
      }
    }
    else {
      fp = "10000000-1000-4000-8000-100000000000".replace(/[018]/g, c => (+c ^ crypto.getRandomValues(new Uint8Array(1))[0] & 15 >> +c / 4).toString(16));
    }
    window.abst.visitorId = fp;
    if (window.abst.hasApproval) {
      abstSetCookie("ab-advanced-id", fp, 365);
    }
    else {
      //session it
      sessionStorage.setItem("ab-advanced-id", fp);
    }
  }
}

// Helper function to set approval status
function setAbstApprovalStatus(approved) {
  window.abst.hasApproval = approved;
  if (window.abst.hasApproval) {
    localStorage.setItem('abstApprovalStatus', 'approved');
    
    // Migrate ab-advanced-id from sessionStorage to cookie when consent is given
    var sessionId = sessionStorage.getItem('ab-advanced-id');
    if (sessionId && !abstGetCookie('ab-advanced-id')) {
      abstSetCookie('ab-advanced-id', sessionId, 365);
      sessionStorage.removeItem('ab-advanced-id'); // Clean up sessionStorage
      console.log('ABST: Migrated UUID from session to cookie after consent');
    }
    
    // Process any queued events now that we have approval
    abst_process_approved_events();
  } else {
    localStorage.removeItem('abstApprovalStatus');
  }
}
  
// Only set to true if undefined, respecting any intentional false value
if(window.abst.isTrackingAllowed === undefined) 
  window.abst.isTrackingAllowed = true;

//what size, mobile, tablet or desktop 
var viewportWidth = window.innerWidth || document.documentElement.clientWidth || 0;
var dpr = window.devicePixelRatio || 1;
var screenWidth = window.screen && window.screen.width ? Math.round(window.screen.width / dpr) : 0;
var effectiveWidth = viewportWidth || screenWidth;
if (viewportWidth && screenWidth) {
  effectiveWidth = Math.min(viewportWidth, screenWidth);
}

if (effectiveWidth < 768) {
  window.abst.size = 'mobile';
}
else if (effectiveWidth < 1024) {
  window.abst.size = 'tablet';
}
else {
  window.abst.size = 'desktop';
}

if (window.btab_vars && window.btab_vars.advanced_tracking && window.btab_vars.advanced_tracking == '1') {
    setAbCrypto();
  }

function setupConsentPartners() {
  if(window.btab_vars.wait_for_approval == '1' && window.abst.hasApproval == false) {
    console.log('ABST: Setting up cookie consent partners');

    // Cookiebot
    if(window.Cookiebot && window.Cookiebot.consent && window.Cookiebot.consent.statistics) {
      console.log('ABST: Cookiebot consent granted for statistics');
      setAbstApprovalStatus(true);
    }
    // Always listen for consent accept event (works even if Cookiebot loads later)
    window.addEventListener('CookiebotOnAccept', function() {
      if (window.Cookiebot && window.Cookiebot.consent && window.Cookiebot.consent.statistics) {
        console.log('ABST: Cookiebot consent granted (after accept)');
        setAbstApprovalStatus(true);
      }
    });


    // CookieConsent (Orestbida)
    if(window.CookieConsent && window.CookieConsent.acceptedCategory) {
      if (window.CookieConsent.acceptedCategory('analytics')) {
        console.log('ABST: CookieConsent consent granted for analytics');
        setAbstApprovalStatus(true);
      }
    }
    // Always listen for consent changes
    document.addEventListener('cc:onConsent', function(event) {
      if (event.detail && event.detail.cookie && event.detail.cookie.acceptedCategory) {
        if (event.detail.cookie.acceptedCategory('analytics')) {
          console.log('ABST: CookieConsent consent granted for analytics (after change)');
          setAbstApprovalStatus(true);
        }
      }
    });


    // WP Consent API
    if (typeof wp_has_consent !== 'undefined' && wp_has_consent('statistics')){
      console.log('ABST: WP consent api consent granted saving stats');
      setAbstApprovalStatus(true);
    }
    // Always listen to consent change event
    document.addEventListener("wp_listen_for_consent_change", function (e) {
      var changedConsentCategory = e.detail;
      for (var key in changedConsentCategory) {
        if (changedConsentCategory.hasOwnProperty(key)) {
          if (key === 'statistics' && changedConsentCategory[key] === 'allow') {
            console.log("ABST: WP consent api consent granted (after change)");
            setAbstApprovalStatus(true);
          }
        }
      }
    });


    // CookieYes
    if (window.getCkyCConsent) {
      const consent = getCkyCConsent();
      if (consent && consent.categories && consent.categories.analytics) {
        console.log('ABST: CookieYes consent granted for analytics');
        setAbstApprovalStatus(true);
      }
    }
    
    // Listen for consent updates
    document.addEventListener('cookieyes_consent_update', function(e) {
      if (window.getCkyCConsent) {
        const consent = getCkyCConsent();
        if (consent && consent.categories && consent.categories.analytics) {
          console.log('ABST: CookieYes consent granted for analytics (after update)');
          setAbstApprovalStatus(true);
        }
      }
    });
    

    //complianz
    if(typeof cmplz_has_consent === 'function') {
      if(cmplz_has_consent('statistics')) {
        console.log('ABST: Complianz consent granted for statistics');
        setAbstApprovalStatus(true);
      }
    }
	

    // Cookies and Content Security Policy plugin reloads after granting concent so check once on load
    if (typeof Cookies !== 'undefined') {
      // Check for main cookie name
      let cacspCookie = Cookies.get('cookies_and_content_security_policy');
      
      // Check for WP Engine compatibility mode
      if (!cacspCookie) {
        cacspCookie = Cookies.get('wpe-us');
      }
      
      if (cacspCookie) {
        try {
          const acceptedCookies = JSON.parse(cacspCookie);
          if (acceptedCookies.includes('statistics')) {
            console.log('ABST: Cookies and Content Security Policy plugin consent granted for statistics');
            setAbstApprovalStatus(true);
          }
        } catch (e) {
          console.warn('ABST: Error parsing Cookies and Content Security Policy consent cookie:', e);
        }
      }
    }
  }
}
  

// Complianz: always register these listeners unconditionally so they fire for returning
// visitors too (Complianz fires cmplz_enable_category before DOMContentLoaded for users
// who already have consent stored, which would miss the listener if it were inside
// setupConsentPartners' hasApproval==false guard).
if(window.btab_vars && window.btab_vars.wait_for_approval == '1') {
  document.addEventListener("cmplz_enable_category", function(consentData) {
    if (!consentData.detail) return;
    let category = consentData.detail.category;
    let acceptedCategories = consentData.detail.categories;
    // category is 'statistics' on direct grant; also check acceptedCategories array
    // for cases where category is null (service-only consent path)
    let statisticsGranted = category === 'statistics' ||
      (Array.isArray(acceptedCategories) && acceptedCategories.indexOf('statistics') !== -1);
    if (statisticsGranted) {
      console.log('ABST: Complianz consent granted for statistics');
      setAbstApprovalStatus(true);
    }
  });

  document.addEventListener("cmplz_revoke", function() {
    console.log('ABST: Complianz consent revoked');
    setAbstApprovalStatus(false);
  });
}

// Main initialization function - can be called on DOMContentLoaded or when deferred config loads
function abstMainInit() {
  // Prevent running twice
  if (document.body && document.body.classList.contains('ab-test-setup-complete')) {
    return;
  }
  setupConsentPartners();
  // Server-side redirect events are not replayed to analytics; just clear the cookie.
  if (abstGetCookie('abst_server_events')) {
    abstDeleteCookie('abst_server_events');
  }

  if (window.btab_vars && !window.btab_vars.is_preview) {
    var abTestRedirects = document.querySelectorAll('.ab-test-page-redirect');
    abTestRedirects.forEach(function (el) { el.remove(); });
  }

  var btHiddenEls = document.querySelectorAll('[bt_hidden="true"]');
  btHiddenEls.forEach(function (el) { el.remove(); });


  // page goal: the visitor landed on a test's goal page
  if (typeof conversion_details !== 'undefined' && conversion_details) {
    Object.entries(conversion_details).forEach(function ([key, detail]) {
      if (!detail || detail.conversion_page_id === undefined || detail.conversion_page_id === null || detail.conversion_page_id === '') {
        return true; // skip to the next one
      }

      if (typeof current_page !== 'undefined' && Array.isArray(current_page) && (current_page.includes(detail.conversion_page_id) || current_page.includes(parseInt(detail.conversion_page_id)))) {
        abstRecordConversion(key);
      }
    });
  }

  //foreach experiment
  if (typeof bt_experiments !== 'undefined') {

    // check for css classes, then add attributes
    document.querySelectorAll("[class^='ab-'],[class*=' ab-']").forEach(function (el, e) {
      if (el.className.includes('ab-var-')) {
        var allClasses = el.className;
        allClasses = allClasses.split(" "); // into an array
        var thisTestVar = false;
        var thisTestId = false;
        allClasses.forEach(function (element) {

          if (element.startsWith('ab-var-'))
            thisTestVar = element;
          else if (/^ab-\d+$/.test(element)) // the test ID class only; a theme's ab-hero is not a test ID
            thisTestId = element;

        });

        if (thisTestVar !== false && thisTestId !== false) {
          //we've got variations, do ya thing!
          el.setAttribute('bt-eid', thisTestId.replace("ab-", ""));
          el.setAttribute('bt-variation', thisTestVar.replace("ab-var-", ""));
          //remove classes after adding attributes
          allClasses.forEach(function (className) {
            if (className.startsWith('ab-var-') || className === thisTestId) {
              el.classList.remove(className);
            }
          });
        }
      }
    });

    // legacy probably can be removed, check bricks 
    document.querySelectorAll('[data-bt-variation]').forEach(function (el) {
      el.setAttribute('bt-variation', el.getAttribute('data-bt-variation'));
      el.setAttribute('bt-eid', el.getAttribute('data-bt-eid'));
    });

    //fix bricks child attributes
    //fix bricks, move attr's up one level       
    document.querySelectorAll(".bricks-element [bt-eid]").forEach((el) => {
      let parent = el.closest('.bricks-element');
      parent.setAttribute('bt-eid', el.getAttribute('bt-eid'));
      parent.setAttribute('bt-variation', el.getAttribute('bt-variation'));
      el.removeAttribute('bt-eid');
      el.removeAttribute('bt-variation');
    });

    abstWarnOnUnsafeVariationNames();
    abstWarnOnUnsafeExperimentIds();

    let searchParams = new URLSearchParams(window.location.search)
    const abtv = searchParams.get("abtv");
    const abtid = searchParams.get("abtid");

    if (abtv && abtid && bt_experiments[abtid]) {
      console.log('AB Split Test: URL variables detected. Skipping user.');
      //do we need to scroll and flash the element? probably

      showSkippedVisitorDefault(abtid, 'preview', abtv);
      document.body.classList.add('ab-test-setup-complete');
      return true;
    }

    //sort experiments by bt_experiments.test_type = full_page, then the rest
    bt_experiments = Object.entries(bt_experiments).sort((a, b) => {
      if (a[1].test_type == "full_page") {
        return -1;
      }
      if (b[1].test_type == "full_page") {
        return 1;
      }
      return 0;
    }).reduce((r, a) => Object.assign(r, { [a[0]]: a[1] }), {});



    Object.entries(bt_experiments).forEach((([experimentId, experiment]) => {
      try {

      if (experiment.test_type == "css_test") {
        for (var i = 0; i < experiment.css_test_variations; i++) {
          // Code to be executed for each element
          var script = document.createElement('script');
          script.className = 'bt-css-scripts';
          script.setAttribute('bt-variation', 'test-css-' + experimentId + '-' + (i + 1));
          script.setAttribute('bt-eid', experimentId);
          document.body.appendChild(script);
        }
      }

      // Element-click goal: a click on the goal selector, or on an element with the
      // ab-click-convert-{test id} class, records the conversion.
      if (experiment.conversion_page === 'selector') {
        abClickListener(experimentId, experiment.conversion_selector || '');
      }

      //full page test handler //if experiment.full_page_default_page in array current_page
      if (experiment.test_type == 'full_page' && typeof current_page !== 'undefined' && Array.isArray(current_page) && current_page.some(page => String(page) === String(experiment.full_page_default_page))) {
        //console.log('Full Page Test: ' + experimentId);
        //add original do nothing variation
        var div = document.createElement('div');
        div.className = 'bt-redirect-handle';
        div.style.display = 'none';
        div.setAttribute('bt-variation', experiment.full_page_default_page);
        div.setAttribute('bt-eid', experimentId);
        document.body.appendChild(div);
        //foreach variation
        Object.entries(experiment.page_variations).forEach(function([varId, variation]) {
          var div = document.createElement('div');
          div.className = 'bt-redirect-handle';
          div.style.display = 'none';
          div.setAttribute('bt-variation', varId);
          div.setAttribute('bt-eid', experimentId);
          div.setAttribute('bt-url', variation);
          document.body.appendChild(div);
        });
      }



      } catch (e) {
        console.error('ABST: Error processing experiment ' + experimentId + ':', e);
        // Continue to next experiment - don't let one bad experiment break all tests
      }
    }));


    var experiments_el = document.querySelectorAll('[bt-eid]:not([bt-eid=""])[bt-variation]:not([bt-variation=""])');
    var current_exp = {};
    var exp_redirect = {};

    experiments_el.forEach(function (el) {
      var experimentId = el.getAttribute('bt-eid');
      var variation = el.getAttribute('bt-variation');
      var redirect_url = el.getAttribute('bt-url');

      if (current_exp[experimentId] === undefined) {
        current_exp[experimentId] = [];
        exp_redirect[experimentId] = [];
      }
      if (!current_exp[experimentId].includes(variation)) {
        current_exp[experimentId].push(variation);
        exp_redirect[experimentId][variation] = redirect_url;
      }

    });

    // add css tests to current exp
    Object.keys(bt_experiments).forEach(function (experimentId) {
      // Code to be executed for each element
      if (bt_experiments[experimentId]['test_type'] == 'css_test') {
        current_exp[experimentId] = []; // create
        exp_redirect[experimentId] = [];

        for (var i = 1; i <= parseInt(bt_experiments[experimentId]['css_test_variations']); i++) {
          current_exp[experimentId].push('test-css-' + experimentId + '-' + i);
          exp_redirect[experimentId]['test-css-' + experimentId + '-' + i] = '';
        }
      }
      else if (bt_experiments[experimentId]['test_type'] == 'magic' && bt_experiments[experimentId]['magic_definition'] && bt_experiments[experimentId]['magic_definition'].length > 0) {
        var magic_definition = parseMagicTestDefinition(bt_experiments[experimentId]['magic_definition']);
        // An unreadable definition would throw below and stop every later test from being set up.
        if (!Array.isArray(magic_definition) || !magic_definition.length || !magic_definition[0] || !Array.isArray(magic_definition[0].variations)) {
          console.warn('ABST: magic_definition unavailable for experiment ' + experimentId + '. Skipping magic variation registration.');
          return;
        }
        current_exp[experimentId] = []; // create
        exp_redirect[experimentId] = [];
        
        // Register all variations: magic-0 is original (variations[0]), magic-1..N are test variations
        for (var i = 0; i < magic_definition[0].variations.length; i++) {
          current_exp[experimentId].push('magic-' + experimentId + '-' + i);
          exp_redirect[experimentId]['magic-' + experimentId + '-' + i] = '';
        }
      }
    });

    // A test compares the original with one variation: the first two versions found.
    Object.keys(current_exp).forEach(function (experimentId) {
      current_exp[experimentId] = current_exp[experimentId].slice(0, 2);
    });

    // Sort so full_page experiments are processed first, and within full_page, published (active)
    // tests come before completed ones. This ensures window.abstRedirecting is set before any
    // other experiment's bt_experiment_w() / showSkippedVisitorDefault() can call abstShowPage(),
    // which would briefly reveal the page before the redirect fires.
    // Order: full_page+publish (0) → full_page+complete (1) → full_page+other (2) → all else (3)
    Object.keys(current_exp).sort((a, b) => {
      const expA = bt_experiments[a];
      const expB = bt_experiments[b];
      const typeA = expA ? expA.test_type : '';
      const typeB = expB ? expB.test_type : '';
      const statusA = expA ? expA.test_status : '';
      const statusB = expB ? expB.test_status : '';
      const rankA = typeA === 'full_page' ? (statusA === 'publish' ? 0 : statusA === 'complete' ? 1 : 2) : 3;
      const rankB = typeB === 'full_page' ? (statusB === 'publish' ? 0 : statusB === 'complete' ? 1 : 2) : 3;
      return rankA - rankB;
    }).forEach((function (experimentId) {
      // A full_page redirect has fired - don't process remaining experiments.
      // Setting cookies or logging visits for tests the user never sees skews their data.
      if (window.abstRedirecting) return true;

      //check it exists
      if (bt_experiments[experimentId] === undefined) {
        console.info("ABST: " + 'Test ID ' + experimentId + ' does not exist.');
        showSkippedVisitorDefault(experimentId);
        return true; // continue to next exp
      }


      if (bt_experiments[experimentId]['is_current_user_track'] == false || window.abst.isTrackingAllowed === false ) { // if we arent tracking the user show default
        showSkippedVisitorDefault(experimentId);
        return true; // continue to next exp
      }
      else // we are tracking the user so check if previously skipped and remove cookie
      {
        var btab = abstGetCookie('btab_' + experimentId);
        try {
          var skippedAs = btab ? JSON.parse(btab).skipped : false;
          if (skippedAs && skippedAs !== 'pct') { // 'preview', or an older skipped:1 cookie; a traffic % skip is kept
            abstDeleteCookie('btab_' + experimentId);
            console.info('ABST: previously skipped experiment will begin ' + experimentId);
          }
        } catch (e) {
          // Corrupted cookie, delete it
          abstDeleteCookie('btab_' + experimentId);
        }
      }

      // if the test is not published
      if (bt_experiments[experimentId]['test_status'] !== 'publish') {
        showSkippedVisitorDefault(experimentId);
        return true; // continue to next exp
      }

      var targetVisitor = true;

      var btab = abstGetCookie('btab_' + experimentId);

      var experimentVariation = '';

      if (!btab) // no existing data, create
      {
        if (bt_experiments[experimentId]['test_type'] == 'css_test') {
          var randVar = getRandomInt(1, parseInt(bt_experiments[experimentId]['css_test_variations'])) - 1;
          experimentVariation = current_exp[experimentId][randVar];
        }
        else if (bt_experiments[experimentId]['test_type'] == 'full_page') {
          // For full page tests: if user landed on a variation page, assign them to that variation
          var pageVariations = bt_experiments[experimentId]['page_variations'] || {};
          var currentPageId = String(btab_vars.post_id);
          if (Object.keys(pageVariations).includes(currentPageId)) {
            // User landed on a variation page - assign them to this variation
            experimentVariation = currentPageId;
            console.log('ABST: Full page test - user on variation page, assigning to:', experimentVariation);
          } else {
            // User is on default page - randomly select a variation
            var variations = current_exp[experimentId];
            var randVar = getRandomInt(0, variations.length - 1);
            experimentVariation = variations[randVar];
            console.log('ABST: Full page test - user on default page, randomly selected:', experimentVariation);
          }
        }
        else {
          var variations = current_exp[experimentId];
          var randVar = getRandomInt(0, variations.length - 1);
          experimentVariation = variations[randVar];
        }
      }
      else //parse existing data
      {

        try {
          var btab_cookie = JSON.parse(btab);
          experimentVariation = btab_cookie.variation;
        } catch (err) {
          console.log('Error parsing cookie data:', err);
        }
      }

      var variation_element = false;
      if (bt_experiments[experimentId]['test_type'] == 'css_test') {
        document.body.classList.add(experimentVariation);
      }
      else if (bt_experiments[experimentId]['test_type'] == 'magic') {
      }
      else // on page tests
      {
        variation_element = document.querySelectorAll('[bt-eid="' + experimentId + '"][bt-variation="' + experimentVariation + '"]');
      }

      if (btab) { // if we have a cookie
        try {
          btab = JSON.parse(btab);
        } catch (e) {
          console.error('ABST: Error parsing cookie for experiment', experimentId);
          return true; // skip - corrupted cookie
        }
        var redirect_url = exp_redirect[experimentId];
        redirect_url = redirect_url[experimentVariation];
        if (redirect_url && !btab_vars.is_preview) {
          abstRedirect(redirect_url);
          return true; // finished
        }
        else {
          // Don't call abstShowPage() here - wait until all experiments processed
          // to avoid showing page before a redirect experiment runs
          if (variation_element && variation_element.length > 0)
            variation_element.forEach(function (el) { el.classList.add('bt-show-variation'); });

          if (bt_experiments[experimentId]['test_type'] == 'magic' && 
            bt_experiments[experimentId]['magic_definition'] && 
            bt_experiments[experimentId]['magic_definition'].length > 0) {
            // Extract the numeric index from the variation string (format: magic-{index})
            var vartn = 0;
            if (btab.variation && typeof btab.variation === 'string') {
              // Handle format: magic-2
              vartn = parseInt(btab.variation.split('-').pop());
            }
            showMagicTest(experimentId, vartn);
          }
        }
        return true; // continue to next exp
      }

      if (!btab) { // new user, check targeting
        var targetPercentage = bt_experiments[experimentId].target_percentage;

        // Strict checks only: with loose equality (0 == '') a 0% allocation would
        // silently become 100%. Missing/empty means "everyone"; an explicit 0 means "nobody".
        if (targetPercentage === '' || targetPercentage === undefined || targetPercentage === null)
          targetPercentage = 100;

        var url_query = bt_experiments[experimentId].url_query;

        function matchesUrlQueryRule(rule) {
          var normalizedRule = (rule || '').trim();
          if (normalizedRule === '') {
            return true;
          }

          var isNot = false;
          if (normalizedRule.startsWith('NOT')) {
            isNot = true;
            normalizedRule = normalizedRule.replace(/^NOT\s*/i, '').trim();
          }

          var isMatch = false;
          if (normalizedRule.includes('*')) {
            var wildcardSearch = normalizedRule.replace(/\*/g, '');
            isMatch = wildcardSearch === '' ? true : window.location.href.includes(wildcardSearch);
          } else {
            var exploded_query = normalizedRule.split('=');
            if (exploded_query.length === 1) {
              isMatch = !!bt_getQueryVariable(exploded_query[0]);
            } else if (exploded_query.length === 2) {
              var urlQueryResult = bt_getQueryVariable(exploded_query[0]);
              isMatch = exploded_query[1] == urlQueryResult;
            }
          }

          return isNot ? !isMatch : isMatch;
        }

        // supports OR targeting with | separator, e.g. "utm_source=fb|utm_source=google|*pricing*"
        if (url_query !== '') {
          var urlRules = url_query.split(/[\n\r|,]+/).map(function (rule) {
            return rule.trim();
          }).filter(Boolean);

          if (urlRules.length === 0) {
            targetVisitor = true;
          } else {
            targetVisitor = urlRules.some(function (rule) {
              return matchesUrlQueryRule(rule);
            });
          }
        }

        var target_option_device_size = bt_experiments[experimentId].target_option_device_size;

        if (targetVisitor && target_option_device_size != 'all') {
          var device_size = window.abst.size;

          targetVisitor = target_option_device_size.includes(device_size);

        }

        if (!targetVisitor) {
          showSkippedVisitorDefault(experimentId);
          return true;  // continue to next exp
        }
        // randomly target users according to percentage
        var percentage = getRandomInt(1, 100);
        if (targetPercentage < percentage) {
          showSkippedVisitorDefault(experimentId, true);
          console.log('ABST ' + experimentId + ' skipped not in percentage target');
          return true;  // continue to next exp
        }

        // no experiment cookie set, calculate and create        
        bt_experiments[experimentId].variations = bt_get_variations(experimentId);
      }

      if (variation_element && !variation_element.length) {
        showSkippedVisitorDefault(experimentId);
        console.log('ABST variation doesnt exist, or doesnt match. ');
        return true;  // continue to next exp
      }

      if (Object.keys(exp_redirect).length > 0) {
        redirect_url = exp_redirect[experimentId];
        redirect_url = redirect_url[experimentVariation];
      }
      else
        redirect_url = '';

      // if its css, add it to body   

      if (bt_experiments[experimentId]['test_type'] == 'magic') {
        // randvar is the int after the last - in experimentVariation
        randVar = experimentVariation.split('-').pop();
        showMagicTest(experimentId, randVar);
      }

      if (variation_element && variation_element.length > 0) {
        variation_element.forEach(function (el) { 
          if (el) el.classList.add('bt-show-variation'); 
        });
      }


      /// if its magic or on page, check log_on_visible setting
      var logOnVisible = bt_experiments[experimentId]['log_on_visible'] === true;
      
      if (bt_experiments[experimentId]['test_type'] == 'ab_test') {
        if (logOnVisible) {
          // Log when element becomes visible (for dynamic content)
          watch_for_tag_event(experimentId, undefined, experimentVariation);
        } else {
          // Log immediately on page load (default behavior)
          bt_experiment_w(experimentId, experimentVariation, 'visit', redirect_url);
        }
      }
      else if (bt_experiments[experimentId]['test_type'] == 'magic') {
        if (logOnVisible) {
          // Log when element becomes visible (for dynamic content)
          magic_definition.forEach((element, index) => {
            watch_for_tag_event(experimentId, element.selector, experimentVariation, element.scope || null);
          });
        } else {
          var hasMatchingMagicScope = Array.isArray(magic_definition) && magic_definition.some(function(element) {
            if (!element || typeof element.selector !== 'string') {
              return false;
            }

            if (!matchesMagicScope(element.scope)) {
              return false;
            }

            try {
              return document.querySelectorAll(element.selector).length > 0;
            } catch (e) {
              return false;
            }
          });

          if (hasMatchingMagicScope) {
            bt_experiment_w(experimentId, experimentVariation, 'visit', redirect_url);
          }
        }
      }
      else {
        bt_experiment_w(experimentId, experimentVariation, 'visit', redirect_url);
      }
    }));
    abstShowPage();    
  }
  else // no bt_conversion date so add classes complete.
  {
    abstShowPage();
    document.body.classList.add('ab-test-setup-complete');
    window.dataLayer = window.dataLayer || [];
    window.dataLayer.push({ 'event': 'ab-test-setup-complete' }); // gtm trigger - always fire even if no tests
    return;
  }

  // warn users on localhost
  if (btIsLocalhost())
    console.info("AB Split Test: It looks like you're on a localhost, using local storage instead of cookies. External Conversion Pixels and server side conversions will not work on local web servers.");

  window.dispatchEvent(new Event('resize')); // trigger a window resize event. Useful for sliders etc. that dynamically resize
  var event = new Event('ab-test-setup-complete' , {bubbles: true}); 
  //example usage
  //document.addEventListener('ab-test-setup-complete', function() {
  //  console.log('ab-test-setup-complete');
  //});
  document.body.dispatchEvent(event);
  //add class ab-test-setup-complete to body
  document.body.classList.add('ab-test-setup-complete');
  window.dataLayer = window.dataLayer || [];
  window.dataLayer.push({ 'event': 'ab-test-setup-complete' }); // gtm trigger to get all data on page_view

  check_heatmap_tracking();
  
  // Initialize mutation observer for dynamically created test elements
  initAbstDynamicElementObserver();
}

// Run on DOMContentLoaded
document.addEventListener('DOMContentLoaded', abstMainInit);

// Also run if config loads late (deferred by cache plugins like LiteSpeed)
document.addEventListener('abst-config-ready', abstMainInit);

// Record the conversion (page visit or element click) for a test the visitor is already in.
function abstRecordConversion(testId) {
  if (!testId || !window.bt_experiments || !window.bt_experiments[testId]) {
    return false;
  }

  var btab = abstGetCookie('btab_' + testId);
  try {
    btab = JSON.parse(btab);
  } catch (e) {
    btab = null;
  }

  if (!btab) {
    return false;
  }

  if (btab.conversion != 0) {
    console.log("ABST: " + bt_experiments[testId].name + ': Visitor has already converted');
    return false;
  }

  if (bt_experiments[testId].is_current_user_track == false || window.abst.isTrackingAllowed === false) {
    return false;
  }

  bt_experiment_w(testId, btab.variation, 'conversion', false);
  btab.conversion = 1;
  abstSetCookie('btab_' + testId, JSON.stringify(btab), 1000);
  return true;
}

// Element-click goal. One delegated listener per test; a selector may end in |eventname
// (e.g. ".signup|submit") to listen for another event instead of click.
function abClickListener(experimentId, conversionSelector) {
  var eventType = 'click';
  if (conversionSelector.indexOf('|') !== -1) {
    var parts = conversionSelector.split('|');
    conversionSelector = parts[0];
    eventType = parts[1] || 'click';
  }
  var classSelector = '.ab-click-convert-' + experimentId;

  window.abst = window.abst || {};
  window.abst._abstClickListenerRegistry = window.abst._abstClickListenerRegistry || {};
  var registryKey = experimentId + '|' + eventType + '|' + conversionSelector;
  if (window.abst._abstClickListenerRegistry[registryKey]) {
    return;
  }
  window.abst._abstClickListenerRegistry[registryKey] = true;

  document.addEventListener(eventType, function (event) {
    var target = event.target;
    while (target && target !== document) {
      if (target instanceof Element) {
        var matched = false;
        try {
          matched = (conversionSelector.trim() !== '' && target.matches(conversionSelector)) || target.matches(classSelector);
        } catch (e) {
          console.warn('ABST: invalid goal selector for test ' + experimentId + ': ' + conversionSelector);
          return;
        }
        if (matched) {
          console.log('ABST: ' + eventType + ' conversion on ' + (conversionSelector || classSelector));
          abstRecordConversion(experimentId);
          return;
        }
      }
      target = target.parentNode;
    }
  }, true);
}

function showSkippedVisitorDefault(eid, createCookie = false, variation = false, scrollto=false) {
  if (!window.bt_experiments[eid]) { // if no experiment, show first variations

    btv = (function () {
      var el = document.querySelector('[bt-eid="' + eid + '"]');
      return el ? el.getAttribute('bt-variation') : null;
    })();
    document.querySelectorAll('[bt-eid="' + eid + '"][bt-variation="' + btv + '"]').forEach(function (el) { el.classList.add('bt-show-variation'); });
    return true;
  }

  if (variation && eid) // if we have a variation passed, just do it
  {
    if (bt_experiments[eid].test_type == "css_test") // css version 1
    {
      document.body.classList.add(variation);
    }
    else if (bt_experiments[eid].test_type == "full_page") // full page
    {

      //if the variation page matches the current page, then no redirect we are here already
      if (variation == btab_vars.post_id) {
        return true;
      }
      url = bt_experiments[eid].page_variations[variation];
      if (url !== undefined) {
        abstRedirect(url); // follow the link w search params
        return true;
      }
      else {
        //add show class to page
        abstShowPage();
        return false;
      }
    }
    else if (bt_experiments[eid].test_type == "magic") {
      //console.log('magic test, showing ver ' + variation);
      // Clean up the variation string and get the letter
      const letter = variation.replace('Variation ', '').replace('(original)', '').replace('(Original)', '').replace('magic-', '').trim().toLowerCase();

      let magVar;
      if (!isNaN(letter) && !isNaN(parseInt(letter))) {
        // If it's a number, use it directly
        magVar = parseInt(letter);
      }
      else
      {
        // Safety check - make sure we have a single letter
        if (letter.length !== 1 || !/[a-z]/.test(letter)) {
          console.error('Invalid variation format:', variation);
          return false;
        }
        // Convert letter to index (a=0, b=1, etc)
        magVar = letter.charCodeAt(0) - 'a'.charCodeAt(0);
      }


      // Verify the index is in valid range
      if (magVar < 0 || magVar > 25) {
        console.error('Invalid variation letter:', letter);
        return false;
      }

      showMagicTest(eid, magVar, true);
    }
    else // on page
    {
      // First hide all variations for this experiment
      document.querySelectorAll('[bt-eid="' + eid + '"]').forEach(function (el) { el.classList.remove('bt-show-variation'); });
      // Then show only the specific variation
      document.querySelectorAll('[bt-eid="' + eid + '"][bt-variation="' + variation + '"]').forEach(function (el) { el.classList.add('bt-show-variation'); });
      scrollAndHighlightElement('[bt-eid="' + eid + '"][bt-variation="' + variation + '"]');
    }
    abstShowPage();
    if (createCookie) {
      skippedCookie(eid, variation, createCookie);
    }
    return true;
  }


  if (bt_experiments[eid].test_type == "full_page") {
    abstShowPage();
    if (createCookie)
      skippedCookie(eid, bt_experiments[eid].full_page_default_page, createCookie);

    return true; // next
  }

  if (bt_experiments[eid].test_type == "css_test") // css version 1
  {
    document.body.classList.add('test-css-' + eid + '-1');
    if (createCookie)
      skippedCookie(eid, 'test-css-' + eid + '-1', createCookie);
    return true; // next
  }

  //on page tests only from here

  if (!eid)
    return;
  var foundSpecial = false;
  document.querySelectorAll('[bt-eid="' + eid + '"]').forEach((function (element, index) {
    var variationName = element.getAttribute('bt-variation') || '';
    var defaultNames = ["original", "one", "1", "default", "standard", "a", "control"];
    if (defaultNames.includes(variationName.toLowerCase())) {
      btv = variationName; // keep the attribute's case - the cookie is matched against it exactly
      element.classList.add('bt-show-variation');
      foundSpecial = true;
    }
  }));
  if (!foundSpecial) {
    btv = (function () {
      var el = document.querySelector('[bt-eid="' + eid + '"]');
      return el ? el.getAttribute('bt-variation') : null;
    })();
    document.querySelectorAll('[bt-eid="' + eid + '"][bt-variation="' + btv + '"]').forEach(function (el) { el.classList.add('bt-show-variation'); });
  }

  if (createCookie)
    skippedCookie(eid, btv, createCookie);

} 
// reason: 'preview' (?abtid/abtv link - cleared on the next tracked load so the visitor joins the test)
// or anything else = 'pct' (outside the traffic % - sticky, keeps showing the default)
function skippedCookie(eid, btv, reason) {
  var experiment_vars = {
    eid: eid,
    variation: btv,
    conversion: 1,
    skipped: reason === 'preview' ? 'preview' : 'pct'
  };
  experiment_vars = JSON.stringify(experiment_vars);
  abstSetCookie('btab_' + eid, experiment_vars, 1000);
  return true;
}

// Merge the visitor's query string into a redirect target: parameters already on the
// target win, every other visitor parameter is carried over. Plugin control
// parameters (ssr, abst_pin, abst_uuid) are never carried.
function abstMergeRedirectQuery(targetUrl, visitorSearch, visitorHash) {
  var target;
  try {
    target = new URL(targetUrl, window.location.href);
  } catch (e) {
    return targetUrl;
  }
  var visitor = new URLSearchParams(visitorSearch || '');
  var carried = false;
  visitor.forEach(function (value, key) {
    if (key === 'ssr' || key === 'abst_pin' || key === 'abst_uuid') return;
    if (!target.searchParams.has(key)) {
      target.searchParams.append(key, value);
      carried = true;
    }
  });
  if (!target.hash && visitorHash) {
    target.hash = visitorHash;
  }
  if (!carried && !visitorHash) {
    return targetUrl; // untouched: keep whatever form the caller passed
  }
  return target.toString();
}

//takes input slug or url and ends url suitable for window/replace
function abRedirectUrl(url) {
  // if it starts with http/s do nothing
  if (!(url.startsWith('http') || url.startsWith('/')))
    url = '/' + url;

  // Carry the visitor's query string + hash; the target's own parameters win.
  return abstMergeRedirectQuery(url, window.location.search, window.location.hash);
}

function getRandomInt(min, max) {
  min = Math.ceil(min);
  max = Math.floor(max);
  return Math.floor(Math.random() * (max - min + 1)) + min;
}


function abstSetCookie(c_name, value, exdays) {
  if (btIsLocalhost())
    return btSetLocal(c_name, value);
 
  var hostname = window.location.hostname;
  var parts = hostname.replace(/^www\./, '').split('.');
  
  // Handle multi-part TLDs like .com.au, .co.uk, .org.uk, etc.
  var mainDomain;
  if (parts.length >= 3 && 
      ((parts[parts.length-2] === 'com' && parts[parts.length-1] === 'au') ||
       (parts[parts.length-2] === 'net' && parts[parts.length-1] === 'au') ||
       (parts[parts.length-2] === 'edu' && parts[parts.length-1] === 'au') ||
       (parts[parts.length-2] === 'gov' && parts[parts.length-1] === 'au') ||
       (parts[parts.length-2] === 'co' && parts[parts.length-1] === 'uk') ||
       (parts[parts.length-2] === 'org' && parts[parts.length-1] === 'uk') ||
       (parts[parts.length-2] === 'ac' && parts[parts.length-1] === 'uk') ||
       (parts[parts.length-2] === 'gov' && parts[parts.length-1] === 'uk') ||
       (parts[parts.length-2] === 'co' && parts[parts.length-1] === 'nz') ||
       (parts[parts.length-2] === 'org' && parts[parts.length-1] === 'nz') ||
       (parts[parts.length-2] === 'net' && parts[parts.length-1] === 'nz') ||
       (parts[parts.length-2] === 'co' && parts[parts.length-1] === 'za') ||
       (parts[parts.length-2] === 'org' && parts[parts.length-1] === 'za') ||
       (parts[parts.length-2] === 'co' && parts[parts.length-1] === 'jp') ||
       (parts[parts.length-2] === 'ne' && parts[parts.length-1] === 'jp') ||
       (parts[parts.length-2] === 'or' && parts[parts.length-1] === 'jp') ||
       (parts[parts.length-2] === 'com' && parts[parts.length-1] === 'cn') ||
       (parts[parts.length-2] === 'net' && parts[parts.length-1] === 'cn') ||
       (parts[parts.length-2] === 'org' && parts[parts.length-1] === 'cn') ||
       (parts[parts.length-2] === 'com' && parts[parts.length-1] === 'tw') ||
       (parts[parts.length-2] === 'org' && parts[parts.length-1] === 'tw') ||
       (parts[parts.length-2] === 'net' && parts[parts.length-1] === 'tw') ||
       (parts[parts.length-2] === 'com' && parts[parts.length-1] === 'hk') ||
       (parts[parts.length-2] === 'org' && parts[parts.length-1] === 'hk') ||
       (parts[parts.length-2] === 'com' && parts[parts.length-1] === 'sg') ||
       (parts[parts.length-2] === 'org' && parts[parts.length-1] === 'sg') ||
       (parts[parts.length-2] === 'co' && parts[parts.length-1] === 'in') ||
       (parts[parts.length-2] === 'org' && parts[parts.length-1] === 'in') ||
       (parts[parts.length-2] === 'co' && parts[parts.length-1] === 'kr') ||
       (parts[parts.length-2] === 'or' && parts[parts.length-1] === 'kr') ||
       (parts[parts.length-2] === 'com' && parts[parts.length-1] === 'br') ||
       (parts[parts.length-2] === 'org' && parts[parts.length-1] === 'br') ||
       (parts[parts.length-2] === 'net' && parts[parts.length-1] === 'br') ||
       (parts[parts.length-2] === 'com' && parts[parts.length-1] === 'mx') ||
       (parts[parts.length-2] === 'org' && parts[parts.length-1] === 'mx') ||
       (parts[parts.length-2] === 'net' && parts[parts.length-1] === 'mx') ||
       (parts[parts.length-2] === 'co' && parts[parts.length-1] === 'il') ||
       (parts[parts.length-2] === 'org' && parts[parts.length-1] === 'il') ||
       (parts[parts.length-2] === 'co' && parts[parts.length-1] === 'th') ||
       (parts[parts.length-2] === 'or' && parts[parts.length-1] === 'th') ||
       (parts[parts.length-2] === 'com' && parts[parts.length-1] === 'my') ||
       (parts[parts.length-2] === 'org' && parts[parts.length-1] === 'my') ||
       (parts[parts.length-2] === 'com' && parts[parts.length-1] === 'ph') ||
       (parts[parts.length-2] === 'org' && parts[parts.length-1] === 'ph') ||
       (parts[parts.length-2] === 'co' && parts[parts.length-1] === 'id') ||
       (parts[parts.length-2] === 'org' && parts[parts.length-1] === 'id') ||
       (parts[parts.length-2] === 'co' && parts[parts.length-1] === 'ca') ||
       (parts[parts.length-2] === 'org' && parts[parts.length-1] === 'ca') ||
       (parts[parts.length-2] === 'net' && parts[parts.length-1] === 'ca') ||
       (parts[parts.length-2] === 'gov' && parts[parts.length-1] === 'ca') ||
       (parts[parts.length-2] === 'co' && parts[parts.length-1] === 'ae') ||
       (parts[parts.length-2] === 'org' && parts[parts.length-1] === 'ae') ||
       (parts[parts.length-2] === 'net' && parts[parts.length-1] === 'ae') ||
       (parts[parts.length-2] === 'gov' && parts[parts.length-1] === 'ae'))) {
    // For multi-part TLDs, take last 3 parts (E.G. domain.com.au)
    mainDomain = '.' + parts.slice(-3).join('.');
  } else {
    // For regular TLDs, take last 2 parts (domain.com)
    mainDomain = '.' + parts.slice(-2).join('.');
  }

  var exdate = new Date();
  exdate.setDate(exdate.getDate() + exdays);
  var expiryString = ((exdays == null) ? '' : ';path=/; expires=' + exdate.toUTCString());
  var sameSiteAttrs = (window.location.protocol === 'https:' ? '; SameSite=None; Secure' : '; SameSite=Lax');
  
  // Strategy 1: Try main domain first (works across all subdomains)
  var mainDomainCookie = escape(value) + expiryString + sameSiteAttrs + '; domain=' + mainDomain;
  document.cookie = c_name + '=' + mainDomainCookie;
  
  // Verify the cookie VALUE was actually set (not just that the name exists)
  var readBack = abstGetCookie(c_name);
  if (readBack && readBack === value) {
    return true;
  }
  
  // Strategy 2: Fallback to current subdomain only
  console.log('ABST: Main domain failed, trying subdomain fallback');
  var subdomainCookie = escape(value) + expiryString + sameSiteAttrs;
  document.cookie = c_name + '=' + subdomainCookie;
  
  // Verify the cookie VALUE was actually set
  readBack = abstGetCookie(c_name);
  if (readBack && readBack === value) {
    console.log('ABST Cookie set on subdomain:', hostname);
    return true;
  }
  
  // Strategy 3: Last resort - minimal cookie
  console.log('ABST: Subdomain failed, trying minimal cookie');
  var minimalCookie = escape(value) + ';path=/';
  document.cookie = c_name + '=' + minimalCookie;
  
  readBack = abstGetCookie(c_name);
  if (readBack && readBack === value) {
    console.log('ABST Cookie set on minimal cookie');
    return true;
  }

  console.log('ABST: Cookie set on localStorage backup. ALERT COOKIES ARE BEING BLOCKED.');
  console.log('ABST: Server side conversions will not work. Client side conversions will work.');
  
  // All failed - use localStorage or session if not approved
  return btSetLocal(c_name, value);
}

function abstDeleteCookie(c_name) {
  if (!c_name)
    return;

  var expiredCookie = c_name + '=; expires=Thu, 01 Jan 1970 00:00:00 GMT; Max-Age=0; path=/';

  // Cookies may have been created as host-only or on any parent domain.
  // Expire every scope accessible from this host.
  try {
    document.cookie = expiredCookie;

    var hostnameParts = window.location.hostname.replace(/^www\./, '').split('.');
    for (var i = 0; i < hostnameParts.length - 1; i++) {
      document.cookie = expiredCookie + '; domain=.' + hostnameParts.slice(i).join('.');
    }
  } catch (e) { }

  // abstSetCookie falls back to browser storage when cookies are unavailable.
  // Remove both copies regardless of the current consent state.
  try {
    localStorage.removeItem(c_name);
  } catch (e) { }
  try {
    sessionStorage.removeItem(c_name);
  } catch (e) { }
}

function abstGetCookie(c_name) {
  if (!c_name)
    return false;

  var i, x, y, ARRcookies = document.cookie.split(';');
  for (i = 0; i < ARRcookies.length; i++) {
    x = ARRcookies[i].substr(0, ARRcookies[i].indexOf('='));
    y = ARRcookies[i].substr(ARRcookies[i].indexOf('=') + 1);
    x = x.replace(/^\s+|\s+$/g, '');
    if (x == c_name) {
      return unescape(y);
    }
  }

  //try local
  var localValue = btGetLocal(c_name);
  if (localValue) {
    return localValue;
  } 

  return false;
} 

function abstShowPage(force = false) {
  if(window.abstRedirecting && !force) // if we're redirecting and dont need to foerce it to
    return;

  document.body.classList.add('abst-show-page');
  // Only reset inline styles that WE set (in abstRedirect) - don't touch theme styles
  document.documentElement.style.transition = '';
  document.documentElement.style.opacity = '';
  try {
    parent.window.document.body.classList.add('abst-show-page');
  } catch (e) { } // ignore if not allowed
}

function btSetLocal(c_name, value) {
  //session if not approved
  if (window.abst.hasApproval == false) {
    sessionStorage.setItem(c_name, value);
    return;
  }
  localStorage.setItem(c_name, value);
}

function btGetLocal(c_name) {
  //session if not approved
  if (window.abst.hasApproval == false) {
    return sessionStorage.getItem(c_name);
  }
  return localStorage.getItem(c_name);
}

function btDeleteLocal(c_name) {
  //session if not approved
  if (window.abst.hasApproval == false) {
    sessionStorage.removeItem(c_name);
    return;
  }
  localStorage.removeItem(c_name);
}

function btIsLocalhost() {
  return (location.hostname === "localhost" || location.hostname === "127.0.0.1" || location.hostname.endsWith(".local") || location.hostname.endsWith(".test"));
}

function bt_get_variations(eid) {
  let variation = [];
  
  // Standard element-based variations
  document.querySelectorAll('[bt-eid="' + eid + '"]').forEach(function(el) {
    var newVariation = el.getAttribute('bt-variation');
    // Check if the variation already exists in the array
    if (variation.indexOf(newVariation) === -1) {
      variation.push(newVariation);
      }
    });
  

  return variation;
}

function bt_experiment_w(eid, variation, type, url) {

  // dont log it if its a skipper or malformed
  if (variation == '_bt_skip_' || btab_vars.is_preview || !eid || !variation) {
    return true;
  }

  if(!bt_experiments[eid].is_current_user_track || window.abst.isTrackingAllowed === false) {
    console.log('ABST: ignoring ' + eid + ' because user is not tracked');
    return true;
  }

  //if its magic get the value after the last dash
  if (bt_experiments[eid].test_type == 'magic' && variation.includes('-')) {
    variation = 'magic-' + variation.split('-').pop();
  }
  
  console.log('ABST: bt_experiment_w',eid,variation,type,url);

  var data = {
    'action': 'abst_experiment_w',
    'eid': eid,
    'variation': variation,
    'type': type,
    'size': abst.size,
    'location': btab_vars.post_id
  };

  var experiment_vars = {
    eid: eid,
    variation: variation,
    conversion: 0,
    size: abst.size,
    location: btab_vars.post_id,
  };


  // set up conversion
  if (type == 'conversion')
    experiment_vars.conversion = 1;

  //add advanced id if necessary
  if (btab_vars.advanced_tracking == '1')
    data.ab_advanced_id = abstGetAdvancedId();

  experiment_vars = JSON.stringify(experiment_vars);
  
  // Queue the event (global link click handler will flush before navigation)
  queueEventData(data, url);
  
  abstSetCookie('btab_' + eid, experiment_vars, 1000);


  if (url && url !== "ex") {
    abstRedirect(url);
  }
  else {
    abstShowPage(); // show the page
  }

  return true;
}

/**
 * Helper function to queue event data in localStorage
 */
function queueEventData(data, url) {
  // Add to queue in localStorage
  const queueData = {
    data: data,
    timestamp: new Date().getTime(),
    url: url
  };

  // Get existing queue or initialize empty array
  let queue = [];
  try {
    const queueString = sessionStorage.getItem('abstTestDataQueue');
    if (queueString) {
      queue = JSON.parse(queueString);
    }
  } catch (e) {
    console.error('Error parsing abstTestDataQueue', e);
  }

  if(queue.length < 30 )
    queue.push(queueData);
  else
    console.warn('abst test data queue full');
 
  // Save updated queue
  try {
    sessionStorage.setItem('abstTestDataQueue', JSON.stringify(queue));
  } catch (e) {
    console.warn('ABST: Unable to persist event queue', e);
  }
}
 
/**
 * Process events when approval is given
 * This function should be called when cookie consent or other approval is given
 */
function abst_process_approved_events() {

  // Get queued events
  let queue = [];
  try {
    const queueString = sessionStorage.getItem('abstTestDataQueue');
    if (queueString) {
      queue = JSON.parse(queueString);
    }
  } catch (e) {
    console.error('Error parsing abstTestDataQueue', e);
    return false;
  }

  if (!queue.length) {
    //console.log('No queued events to process');
    return true;
  }

  const batch = queue.map((queueItem) => queueItem.data);

  try {
    const ok = navigator.sendBeacon(
      bt_ajaxurl + '?action=abst_data',
      JSON.stringify(batch)
    );
    
    if (ok) {
      sessionStorage.removeItem('abstTestDataQueue');
      //console.log('ABST: Batch sent successfully');
    } else {
      console.info('ABST: Beacon rejected by browser, will retry');
    }
  } catch (e) {
    console.warn('ABST: batch beacon failed', e);
  }

  return true;
}

// check for a full page test visit cookie
function bt_getQueryVariable(variable) {
  var query = window.location.search.substring(1);
  var vars = query.split("&");
  for (var i = 0; i < vars.length; i++) {
    var pair = vars[i].split("=");
    if (pair[0] == variable) {
      if (pair[1] == null)
        return true;
      return pair[1];
    }
  }
  return (false);
}

if (!String.prototype.endsWith) {
  String.prototype.endsWith = function (search, this_len) {
    if (this_len === undefined || this_len > this.length) {
      this_len = this.length;
    }
    return this.substring(this_len - search.length, this_len) === search;
  };
}



//function to find and replace strings uin document, including strings with formatting like strong em, etc
function bt_replace_all(find, replace, location = 'body') {
  // Get the element by the provided location
  const element = document.querySelector(location);

  // Function to recursively replace text
  function replaceText(node) {
    if (node.nodeType === Node.TEXT_NODE) { // Check if it's a text node
      // Replace text, preserving HTML entities by decoding and encoding
      let text = node.textContent;
      let div = document.createElement('div');
      div.innerHTML = text.replace(new RegExp(find, 'gi'), replace);
      node.textContent = div.textContent || div.innerText || "";
    } else {
      // Otherwise, handle all its children nodes
      node.childNodes.forEach(replaceText);
    }
  }

  // Start the text replacement process from the chosen element
  replaceText(element);
}

// Usage example:
//bt_replace_all('find text', 'replace text');

function bt_replace_all_html(find, replace, location = 'body') {
  // Get the element by the provided location
  const element = document.querySelector(location);

  // Function to recursively replace HTML
  function replaceHTML(node) {
    if (node.nodeType === Node.ELEMENT_NODE) { // Check if it's an element node
      // Replace HTML, preserving the node structure
      node.innerHTML = node.innerHTML.split(find).join(replace);
    } else {
      // Otherwise, handle all its children nodes
      node.childNodes.forEach(replaceHTML);
    }
  }

  // Start the HTML replacement process from the chosen element
  replaceHTML(element);
}

/**
 * Revoke approval for tracking (for testing or when user revokes consent)
 */
function abst_revoke_approval() {
  setAbstApprovalStatus(false);
  console.log('ABST: Approval revoked, events will be queued until approval is given again');
  return true;
}

function repairMagicDefinitionJson(def) {
  if (typeof def !== 'string') {
    return def;
  }

  return def
    // Repair stored JSON where update_post_meta stripped escapes from HTML attributes.
    .replace(/(\s[A-Za-z_:][-A-Za-z0-9_:.]*=)"([^"]*)"/g, '$1\\"$2\\"')
    // Repair quoted CSS attribute selectors inside JSON strings, e.g. a[href="/x"].
    .replace(/(\[[^\]"=]+[*^$|~]?=)"([^"]*)"/g, '$1\\"$2\\"');
}

function parseMagicTestDefinition(def){
  try {
    // First try to parse the JSON directly
    magic_definition = JSON.parse(def);
  } catch (e) {
    // If parsing fails, try to fix common JSON formatting issues
    try {
      const fixedJson = def.replace(/(?<=[a-zA-Z])"(?=[a-zA-Z])/g, '\\"');
      magic_definition = JSON.parse(fixedJson);
      console.log('ABST: Successfully fixed magic_definition quotes issue. Edit your split test in the WordPress admin to remove this console log.');
    } catch (e2) {
      try {
        const repairedJson = repairMagicDefinitionJson(def);
        magic_definition = JSON.parse(repairedJson);
        console.warn('ABST: Repaired corrupt magic_definition at runtime. Saved test config was not changed; edit and resave the split test to persist clean JSON.');
        console.log('ABST: Repaired corrupt magic_definition at runtime. Saved test config was not changed; edit and resave the split test to persist clean JSON.');
      } catch (e3) {
        console.warn('ABST: Failed to parse magic_definition after runtime repair. Saved test config is corrupt; variation will be skipped.', e3);
        return null;
      }
    }
  }
  return magic_definition;
}

function getCurrentPagePath() {
  var path = window.location && window.location.pathname ? window.location.pathname.toLowerCase() : '';
  return path.replace(/^\/+|\/+$/g, '');
}

function matchesMagicScope(scope) {
  if (!scope || typeof scope !== 'object') {
    return true;
  }

  var scopePageIds = [];
  if (Array.isArray(scope.page_id)) {
    scopePageIds = scope.page_id.map(function(id) {
      return String(id).trim();
    }).filter(function(id) {
      return /^[1-9]\d*$/.test(id);
    });
  } else if (scope.page_id !== undefined && scope.page_id !== null && scope.page_id !== '' && scope.page_id !== '*') {
    scopePageIds = String(scope.page_id).split(',').map(function(id) {
      return id.trim();
    }).filter(function(id) {
      return /^[1-9]\d*$/.test(id);
    });
  }

  var hasScopePageId = scopePageIds.length > 0 || scope.page_id === '*';
  var hasScopeUrl = typeof scope.url === 'string' && scope.url.trim() !== '';

  // Check for wildcard (apply to all pages)
  if (scope.page_id === '*' || scope.url === '*') {
    return true;
  }

  if (!hasScopePageId && !hasScopeUrl) {
    return true;
  }

  if (hasScopePageId) {
    if (!Array.isArray(window.current_page)) {
      return false;
    }

    return window.current_page.some(function(page) {
      return scopePageIds.indexOf(String(page)) !== -1;
    });
  }

  if (hasScopeUrl) {
    return getCurrentPagePath().indexOf(String(scope.url).toLowerCase().replace(/^\/+|\/+$/g, '')) !== -1;
  }

  return true;
}

function showMagicTest(eid, index,scroll = false) { // called from magic bar so we arent logging anything
  var magic_definition = parseMagicTestDefinition(bt_experiments[eid].magic_definition);
  if (!Array.isArray(magic_definition) || !magic_definition.length) {
    console.warn('ABST: magic_definition parsing failed for experiment', eid, '- Variation will not be displayed. Please recreate this split test.');
    return;
  }
  // foreach swapElement in magic_definition
  magic_definition.forEach(function (swapElement) {
    if (!swapElement || typeof swapElement.selector !== 'string'){
      console.error('ABST: Invalid swapElement in showMagicTest:', swapElement);
      return;
    }
    if (!matchesMagicScope(swapElement.scope)) {
      return;
    }
    var elements = [];
    try {
      elements = document.querySelectorAll(swapElement.selector);
    } catch (e) {
      console.error('ABST: Malformed selector in showMagicTest:', swapElement.selector, '-', e);
      return; // skip malformed selectors
    }
    if (!elements.length){
      return; // no match, skip
    } // no match, skip
    // index 0 = original — page already shows it, nothing to do
    if (index === 0) return;

    var variation;

    if (Array.isArray(swapElement.variations)) {
        variation = swapElement.variations[index];
        // If variation is null, undefined, or empty string, fall back to original (no-op effectively)
        if (variation === null || variation === undefined || variation === '') {
            return;
        }
    } else {
      variation = undefined;
    }
    
    if (variation === undefined){
      //console.error('ABST: No variation found for selector in showMagicTest:', swapElement.selector);
      return; // no match, skip
    }
    if (swapElement.type === 'text' || swapElement.type === 'html') {
      elements.forEach(function(el) {
        try {
          el.innerHTML = variation;
        } catch (e) {
          console.error('ABST: Error setting innerHTML in showMagicTest:', el, '-', e);
        }
      });
    }
    if (swapElement.type === 'image') {
      elements.forEach(function(el) {
        try { el.setAttribute('src', variation); } catch (e) { console.error('ABST: Error setting src in showMagicTest:', el, '-', e); }
        try { el.setAttribute('srcset', variation); } catch (e) { console.error('ABST: Error setting srcset in showMagicTest:', el, '-', e); }
      });
    }
    // Saved style/attribute changes were only applied to elements added after load.
    if (swapElement.type === 'style' && swapElement.property) {
      elements.forEach(function(el) { try { el.style[swapElement.property] = variation; } catch (e) {} });
    }
    if (swapElement.type === 'attribute' && swapElement.property) {
      elements.forEach(function(el) { try { el.setAttribute(swapElement.property, variation); } catch (e) {} });
    }
  });
  // scroll into view first element (if any)
  if (scroll) {
    var scopedDefinition = magic_definition.find(function(def) {
      return def && typeof def.selector === 'string' && matchesMagicScope(def.scope);
    });
    selector = scopedDefinition && typeof scopedDefinition.selector === 'string' ? scopedDefinition.selector : null;
    scrollAndHighlightElement(selector);
  }
}


function scrollAndHighlightElement(selector) {
  setTimeout(function(){
    
  //console.log('scrollAndHighlightElement',selector);
  var firstElements = [];
  if (selector) {
    try {
      firstElements = document.querySelectorAll(selector);
      //console.log('scrollAndHighlightElement: firstElements',firstElements);
    } catch (e) {
      console.error('ABST: Malformed selector in scrollAndHighlightElement:', selector, '-', e);
      firstElements = [];
    }
  }
  if (firstElements.length) {
    try {
      var rect = firstElements[0].getBoundingClientRect();
      var scrollTop = window.pageYOffset + rect.top - window.innerHeight / 3;
      window.scrollTo({ top: scrollTop, behavior: 'smooth' });
    } catch (e) {
      console.error('ABST: Error in scrollTo in scrollAndHighlightElement:', e);
    }
    // flash a box around the swapped element for 4 seconds
    firstElements.forEach(function(el) {
      try { el.classList.add('ab-highlight'); } catch (e) { console.error('ABST: Error adding ab-highlight class in scrollAndHighlightElement:', el, '-', e); }
    });
    setTimeout(function() {
      firstElements.forEach(function(el) {
        try { el.classList.remove('ab-highlight'); } catch (e) { console.error('ABST: Error removing ab-highlight class in scrollAndHighlightElement:', el, '-', e); }
      });
    }, 4000);
  }
},2000);
}

window.btab_vars = window.btab_vars || {};

// For backward compatibility, restore original abtracker function
window.btab_vars.abtracker = function(eid, selector, variation) {
  const tracker = ensureTrackerInitialized();
  
  // If called with parameters, add the element to tracker
  if (eid && selector && variation) {
    tracker.addElement(eid, selector, variation);
  }
  
  // If called without parameters, trigger immediate rescan (legacy behavior)
  if (!eid && !selector && !variation) {
    tracker.checkVisibility();
  }
  
  // Always start the tracker (this is what legacy code expects)
  if (!tracker.active) {
    tracker.start();
  }
};

// Initialize the unified tracker object
function ensureTrackerInitialized() {
  if (!window.btab_vars.tracker) {
    window.btab_vars.tracker = {
      elements: {},   // keyed by `${eid}_${selector}`
      active: false,
      interval: null,
      trackedElements: new Set(),  // Track which elements have fired events

      // Add an element to be tracked for visibility
      addElement: function(eid, selector, variation, scope) {
        const key = `${eid}_${selector}`;
        if (!this.elements[key]) {
          this.elements[key] = { eid, selector, variation, scope };
        }
      },
      
      // Check visibility and fire events
      checkVisibility: function() {
        // Process tracked elements first
        for (const key in this.elements) {
          if (!this.elements.hasOwnProperty(key)) continue;
          
          const { eid, selector, variation, scope } = this.elements[key];

          if (!matchesMagicScope(scope)) {
            continue;
          }
          
          // Skip if this specific element selector has already been tracked
          if (this.trackedElements.has(key)) continue;
          
          // Validate selector and query elements
          let els = [];
          try {
            els = document.querySelectorAll(selector);
          } catch (e) {
            console.error('ABST: Invalid selector for experiment', eid, ':', selector, e);
            // Mark as tracked to prevent repeated errors
            this.trackedElements.add(key);
            continue;
          }
          
          if (els.length === 0) {
            // Element not on page yet, will check again next interval
            continue;
          }
          
          
          
          // Check if any element is visible (simplified and more reliable)
          const isAnyVisible = Array.from(els).some(el => {
            if (!el) return false;
            
            // Check basic visibility - skip hidden elements
            const style = window.getComputedStyle(el);
            if (style.display === 'none' || 
                style.visibility === 'hidden' ||
                parseFloat(style.opacity || 1) === 0) {
              return false;
            } 

            if (style.position !== 'fixed' && style.position !== 'sticky' && el.offsetParent === null) {
              return false;
            }
            
            // Check if element is in viewport (at least partially)
            const rect = el.getBoundingClientRect();
            const viewportHeight = window.innerHeight || document.documentElement.clientHeight;
            const viewportWidth = window.innerWidth || document.documentElement.clientWidth;
            
            const inViewport = (
              rect.bottom > 0 && 
              rect.top < viewportHeight && 
              rect.right > 0 && 
              rect.left < viewportWidth
            );
            
            return inViewport;
          });
          
          // If element is visible and not tracked yet
          if (isAnyVisible) {
            //console.log('ABST: Element is visible, checking if should fire visit for', eid, variation);
            let shouldFireVisit = false;
            
            // Check cookie to see if we should fire visit event
            const cookieVal = abstGetCookie('btab_' + eid);
            if (cookieVal) {
              try {
                // If we have a valid cookie, don't fire visit event
                JSON.parse(cookieVal);
                shouldFireVisit = false;
                //console.log('ABST: Cookie exists, not firing visit for', eid, variation);
              } catch (e) {
                console.error('Error parsing cookie for ' + eid, e);
                shouldFireVisit = true;
              }
            } else {
              // Check if we've already fired a visit for this experiment (regardless of selector)
              const experimentKey = `${eid}_${variation}`;
              if (this.trackedElements.has(experimentKey)) {
                shouldFireVisit = false;
                //console.log('ABST: Already fired visit for experiment', eid, variation, 'from different element');
              } else {
                shouldFireVisit = true;
                //console.log('ABST: No cookie found, will fire visit for', eid, variation);
              }
            }
            
            if (shouldFireVisit) {
              try {
                //console.log('ABST: Firing visit event for', eid, variation);
                bt_experiment_w(eid, variation, 'visit', false);
                this.trackedElements.add(key);
                // Also track the experiment+variation to prevent duplicate visits
                this.trackedElements.add(`${eid}_${variation}`);
              } catch (e) {
                console.error('Error in bt_experiment_w:', e);
              }
            }
          } else {
            //  console.log('ABST: Element not visible for', eid, variation);
          }
        }
      },
      
      // Start interval checking
      start: function() {
        if (!this.active) {
          this.interval = setInterval(() => this.checkVisibility(), 500);
          this.active = true;
        }
      },
      
      // Stop interval checking
      stop: function() {
        if (this.active && this.interval) {
          clearInterval(this.interval);
          this.interval = null;
          this.active = false;
        }
      },
      
      // Reset tracked elements (useful for testing)
      reset: function() {
        this.trackedElements.clear();
      }
    };
  }
  
  return window.btab_vars.tracker;
}

// Use this function in watch_for_tag_event and anywhere else you initialize the tracker
function watch_for_tag_event(eid, selector = '[bt-eid="' + eid + '"]', variation = null) {
  if (!variation) return;
  const tracker = ensureTrackerInitialized();
  var scope = arguments.length > 3 ? arguments[3] : null;
  tracker.addElement(eid, selector, variation, scope);
  if (!tracker.active) {
    tracker.start();
  }
}

function abstContainsHtml(str) {
  if (!str || typeof str !== 'string') {
    return false;
  }
  // This regex looks for a pattern that starts with '<', is followed by a letter,
  // and eventually has a '>' character. This is a much more reliable
  // indicator of an HTML tag than just checking for the brackets separately.
  return /<[a-z][\s\S]*>/i.test(str);
}

function abstRedirect(url) {

  console.log('ABST: Redirecting to ' + url);
  // Don't redirect if we're in server-side rendering mode (check current page URL)
  if (window.location.search.indexOf('ssr=1') > -1) {
    console.log('ABST: Not redirecting - server-side rendering mode active ?ssr=1');
    window.abstRedirecting = false;
    return;
  }

  window.abstRedirecting = true;
  document.documentElement.style.transition = 'none';
  try {
    window.location.replace(abRedirectUrl(url));
  } catch(e) {
    // Navigation blocked (sandboxed iframe, CSP, etc.) - reset so fallbacks and
    // remaining experiments can still run normally.
    console.error('ABST: Redirect failed', e);
    window.abstRedirecting = false;
  }
}

/* fallback to ensure nobody is ever left with a blank page */
setTimeout(function() {
  if (!window.abstRedirecting && !document.body.classList.contains('abst-show-page') ) {
    abstShowPage(); // Use the function instead of direct manipulation
  } 
}, 2000);

/* fallback to ensure nobody is ever left with a blank page if redirect fails.
   Does NOT force-show during an active redirect — the CSS abst-force-show animation (4s) is
   the true last resort for that case, so no flash occurs on slow-but-successful redirects. */
setTimeout(function() {
  if (!document.body.classList.contains('abst-show-page') ) {
    abstShowPage(); // respects abstRedirecting; CSS abst-force-show animation is the absolute last resort
  }
}, 4000);

/**
 * Check if a newly added DOM node (or its descendants) matches any Magic Test selectors
 * If so, apply the variation swap immediately
 * @param {Node} node - The newly added DOM node
 */
function checkMagicTestSelectors(node) {
  // Skip if no experiments or node can't be queried
  if (!window.bt_experiments || !node.querySelectorAll) return;
  
  // Loop through all magic tests
  for (var eid in window.bt_experiments) {
    var exp = window.bt_experiments[eid];
    if (exp.test_type !== 'magic') continue;
    
    // Get the user's assigned variation from cookie
    var cookieValue = abstGetCookie('btab_' + eid);
    if (!cookieValue) continue;
    
    try {
      var testData = JSON.parse(cookieValue);
      if (!testData.variation || testData.skipped) continue;
      
      // Parse magic definition
      var magic_definition = parseMagicTestDefinition(exp.magic_definition);
      if (!Array.isArray(magic_definition)) continue;
      
      // Get variation index (e.g., "magic-2" -> 2)
      var varIndex = parseInt(testData.variation.split('-').pop());
      
      // Check each selector in the magic definition
      magic_definition.forEach(function(swapElement, defIdx) {
        if (!swapElement || !swapElement.selector) return;
        if (!matchesMagicScope(swapElement.scope)) return;
        
        try {
          // Check if the new node itself matches
          var matches = [];
          if (node.matches && node.matches(swapElement.selector)) {
            matches.push(node);
          }
          // Check descendants
          var descendants = node.querySelectorAll(swapElement.selector);
          descendants.forEach(function(el) { matches.push(el); });
          
          if (matches.length === 0) return;
          
          // Get the variation value
          var variation = swapElement.variations ? swapElement.variations[varIndex] : undefined;
          if (variation === undefined || variation === null || variation === '') {
            variation = swapElement.variations ? swapElement.variations[0] : undefined;
          }
          if (variation === undefined) return;
          
          // Apply the swap
          matches.forEach(function(el) {
            // Skip if THIS def already processed this node. The marker accumulates one token
            // per applied def (eid + def index) — a bare per-element flag would let the first
            // matching def block every other def targeting the same element (e.g. a text swap
            // and a hide on one node, or a second experiment's def).
            var swapToken = eid + ':' + defIdx;
            var swapDone = el.getAttribute('data-abst-swapped') || '';
            if ((' ' + swapDone + ' ').indexOf(' ' + swapToken + ' ') !== -1) return;
            el.setAttribute('data-abst-swapped', swapDone ? swapDone + ' ' + swapToken : swapToken);

            if (swapElement.type === 'text' || swapElement.type === 'html') {
              el.innerHTML = variation;
            } else if (swapElement.type === 'image') {
              el.setAttribute('src', variation);
              el.setAttribute('srcset', variation);
            } else if (swapElement.type === 'style') {
              el.style[swapElement.property] = variation;
            } else if (swapElement.type === 'attribute') {
              el.setAttribute(swapElement.property, variation);
            }
            //console.log('ABST: Dynamic Magic swap applied:', swapElement.selector, '->', variation);
          });
        } catch (e) {
          // Selector error, skip
        }
      });
    } catch (e) {
      // Cookie parse error, skip
    }
  }
}

/**
 * Automatically monitors DOM for new A/B test elements using MutationObserver.
 * This provides automatic detection with minimal overhead.
 */
function initAbstDomObserver() {
  // Only initialize if MutationObserver is supported
  if (!window.MutationObserver) {
    return;
  }

  // Avoid duplicate observers
  if (window.abstDomObserver) {
    return;
  }

  // If bt_experiments isn't available yet, we'll still initialize the observer
  // It will gracefully handle cases where experiments aren't loaded yet

  const tracker = ensureTrackerInitialized();
  
  // Create observer to watch for new elements with bt-eid attributes
  window.abstDomObserver = new MutationObserver(function(mutations) {
    let foundNewElements = false;
    
    mutations.forEach(function(mutation) {
      // Only process added nodes
      if (mutation.type === 'childList' && mutation.addedNodes.length > 0) {
        mutation.addedNodes.forEach(function(node) {
          // Skip text nodes and other non-element nodes
          if (node.nodeType !== Node.ELEMENT_NODE) return;
          
          // Check if the added node itself has bt-eid
          if (node.hasAttribute && node.hasAttribute('bt-eid')) {
            //console.log('ABST: Found new A/B test element (direct):', node.getAttribute('bt-eid'), node.getAttribute('bt-variation'));
            processNewAbTestElement(node);
            foundNewElements = true;
          }
          
          // Check if any descendants have bt-eid (for when containers are added)
          if (node.querySelectorAll) {
            const abElements = node.querySelectorAll('[bt-eid]:not([bt-eid=""])');
            if (abElements.length > 0) {
              //console.log('ABST: Found', abElements.length, 'new A/B test element(s) in container:', node);
              abElements.forEach(processNewAbTestElement);
              foundNewElements = true;
            }
          }
          
          // Also check for Magic Test elements (popups, modals, dynamic content)
          // These are matched by CSS selectors, not bt-eid attributes
          checkMagicTestSelectors(node);
        });
      }
    });
    
    if (foundNewElements && !tracker.active) {
      tracker.start();
    }
  });

  // Start observing with minimal overhead - only watch for added nodes
  // Safety check: ensure document.body exists before observing
  if (document.body) {
    window.abstDomObserver.observe(document.body, {
      childList: true,
      subtree: true
    });
  } else {
    // If body doesn't exist yet, wait for it
    document.addEventListener('DOMContentLoaded', function() {
      if (document.body && window.abstDomObserver) {
        window.abstDomObserver.observe(document.body, {
          childList: true,
          subtree: true
        });
      }
    });
  }
}

/**
 * Process new A/B test elements - ultra-simple approach
 */
function processNewAbTestElement(element) {
  const experimentId = element.getAttribute('bt-eid');
  const variation = element.getAttribute('bt-variation');
  
  if (!experimentId || !variation) return;
  
  //console.log('ABST: Auto-tracking new element:', experimentId, variation);
  
  // Check if user already has a variation assigned for this experiment
  const cookieVal = abstGetCookie('btab_' + experimentId);
  if (cookieVal) {
    try {
      const btab = JSON.parse(cookieVal);
      // If this element matches the user's assigned variation, show it immediately
      if (btab.variation === variation) {
        //console.log('ABST: Showing dynamic element for assigned variation:', experimentId, variation);
        element.classList.add('bt-show-variation');
      } else {
        //console.log('ABST: Dynamic element variation mismatch. User has:', btab.variation, 'Element is:', variation);
      }
    } catch (e) {
      console.error('ABST: Error parsing cookie for dynamic element:', experimentId, e);
    }
  } else {
    //console.log('ABST: No variation assigned yet for experiment:', experimentId);
  }
  
  // Add to visibility tracker for visit logging
  const selector = '[bt-eid="' + experimentId + '"][bt-variation="' + variation + '"]';
  watch_for_tag_event(experimentId, selector, variation);
}

/**
 * Manual rescan function (kept for backward compatibility and manual triggering)
 */
function abst_rescan_for_elements() {
  document.querySelectorAll('[bt-eid]:not([bt-eid=""])').forEach(processNewAbTestElement);
  
  const tracker = ensureTrackerInitialized();
  if (!tracker.active) {
    tracker.start();
  }
}

// Initialize DOM observer immediately - no need to wait for DOMContentLoaded
// This ensures we catch any dynamically added elements from the very beginning
initAbstDomObserver();













 
 


//generate short paths

function getUniqueSelector(element) {
  // If not an element, return null
  if (!(element instanceof Element)) return null;
  
  const MAX_ANCESTOR_DEPTH = 10;
  const tag = element.tagName.toLowerCase();
  
  // Helper: Try to build a unique selector for a single element
  function tryElementSelector(el) {
      const elTag = el.tagName.toLowerCase();
      
      // Try ID first
      if (el.id && !isIgnored('id', el.id)) {
          const byId = '#' + el.id;
          if (document.querySelectorAll(byId).length === 1) {
              return byId;
          }
      }
      
      // Try tag + single class (rarest first)
      if (el.classList && el.classList.length > 0) {
          const classes = Array.from(el.classList)
              .filter(cls => !isIgnored('class', cls))
              .sort((a, b) => {
                  const aCount = document.querySelectorAll('.' + a).length;
                  const bCount = document.querySelectorAll('.' + b).length;
                  return aCount - bCount;
              });
          
          for (const cls of classes) {
              const candidate = elTag + '.' + cls;
              if (document.querySelectorAll(candidate).length === 1) {
                  return candidate;
              }
          }
          
          // Try tag + two classes
          if (classes.length >= 2) {
              for (let i = 0; i < classes.length - 1; i++) {
                  for (let j = i + 1; j < classes.length; j++) {
                      const candidate = elTag + '.' + classes[i] + '.' + classes[j];
                      if (document.querySelectorAll(candidate).length === 1) {
                          return candidate;
                      }
                  }
              }
          }
      }
      
      return null;
  }
  
  // Helper: Build a short descendant selector from anchor to target
  function buildDescendantSelector(anchor, anchorSelector, target) {
      const targetTag = target.tagName.toLowerCase();
      
      // Try just tag
      let candidate = anchorSelector + ' ' + targetTag;
      if (document.querySelectorAll(candidate).length === 1) {
          return candidate;
      }
      
      // Try tag + class
      if (target.classList && target.classList.length > 0) {
          const classes = Array.from(target.classList)
              .filter(cls => !isIgnored('class', cls));
          
          for (const cls of classes) {
              candidate = anchorSelector + ' ' + targetTag + '.' + cls;
              if (document.querySelectorAll(candidate).length === 1) {
                  return candidate;
              }
          }
          
          // Try tag + two classes
          if (classes.length >= 2) {
              for (let i = 0; i < classes.length - 1; i++) {
                  for (let j = i + 1; j < classes.length; j++) {
                      candidate = anchorSelector + ' ' + targetTag + '.' + classes[i] + '.' + classes[j];
                      if (document.querySelectorAll(candidate).length === 1) {
                          return candidate;
                      }
                  }
              }
          }
      }
      
      // Try important attributes
      const importantAttrs = ['href', 'alt', 'title', 'name', 'value', 'type', 'role'];
      for (const attrName of importantAttrs) {
          if (target.hasAttribute && target.hasAttribute(attrName)) {
              const raw = target.getAttribute(attrName);
              if (raw && raw.length < 100) {
                  candidate = anchorSelector + ' ' + targetTag + '[' + attrName + '="' + raw.replace(/"/g, '\\"') + '"]';
                  if (document.querySelectorAll(candidate).length === 1) {
                      return candidate;
                  }
              }
          }
      }
      
      return null;
  }
  
  // STEP 1: Check if the element itself has a unique selector
  const directSelector = tryElementSelector(element);
  if (directSelector) {
      return directSelector;
  }
  
  // STEP 2: Walk up to 5 ancestors looking for an anchor with unique ID/class
  let current = element.parentElement;
  let depth = 0;
  
  while (current && current !== document.body && depth < MAX_ANCESTOR_DEPTH) {
      const anchorSelector = tryElementSelector(current);
      
      if (anchorSelector) {
          // Found an anchor! Try to build a short descendant selector
          const descendant = buildDescendantSelector(current, anchorSelector, element);
          if (descendant) {
              return descendant;
          }
      }
      
      current = current.parentElement;
      depth++;
  }
  
  // STEP 3: Try attribute-based selectors on the element itself
  const importantAttrs = ['href', 'alt', 'title', 'name', 'value', 'type', 'role'];
  for (const attrName of importantAttrs) {
      if (element.hasAttribute && element.hasAttribute(attrName)) {
          const raw = element.getAttribute(attrName);
          if (raw && raw.length < 100) {
              const candidate = tag + '[' + attrName + '="' + raw.replace(/"/g, '\\"') + '"]';
              if (document.querySelectorAll(candidate).length === 1) {
                  return candidate;
              }
          }
      }
  }
  
  // STEP 4: Fallback to structural path with nth-child (last resort)
  return generateShortPath(element);
}

function getPageClass() {
  var body = document.body;
  return Array.from(body.classList).find(function(cls) {
      return cls.startsWith('postid-') || cls.startsWith('page-id-') || cls.startsWith('post-type-archive-');
  });
}

// Utility: Prepend page class to a selector
function scopeSelectorToPage(selector) {
  var pageClass = getPageClass();
  if (!pageClass) return selector;
  return '.' + pageClass + ' ' + selector;
}

// Scrub HTML that came from an untrusted source (URL parameters, external hand-offs) before
// it reaches innerHTML or gets stored in a magic definition and replayed to every visitor.
// Strips executable/embedding tags, inline event handlers and javascript: URLs.
function abstSanitizeCreatedHtml(html) {
  if (typeof html !== 'string' || !html) return '';
  var tpl = document.createElement('template');
  try { tpl.innerHTML = html; } catch (e) { return ''; }
  var nodes = tpl.content.querySelectorAll('*');
  var STRIP_TAGS = { script: 1, style: 1, iframe: 1, object: 1, embed: 1, link: 1, meta: 1, form: 1, base: 1 };
  Array.prototype.forEach.call(nodes, function (node) {
    if (!node.parentNode) return; // already removed with an ancestor
    var tag = node.tagName ? node.tagName.toLowerCase() : '';
    if (STRIP_TAGS[tag]) {
      node.parentNode.removeChild(node);
      return;
    }
    if (!node.attributes) return;
    for (var i = node.attributes.length - 1; i >= 0; i--) {
      var attr = node.attributes[i];
      var name = attr.name.toLowerCase();
      var val = (attr.value || '').replace(/\s+/g, '').toLowerCase();
      if (name.indexOf('on') === 0 ||
          ((name === 'href' || name === 'src' || name === 'xlink:href') && val.indexOf('javascript:') === 0)) {
        node.removeAttribute(attr.name);
      }
    }
  });
  return tpl.innerHTML;
}

// Nodes the magic editor inserts while editing (variation markers, hidden-element
// placeholders, move anchors). They exist only in the editor DOM — never on the visitor
// page — so nth-child sibling counts must skip them or the saved index is off by one and
// the selector matches the wrong element (or nothing) in production.
function abstIsEditorArtifactNode(el) {
  if (!el || el.nodeType !== 1 || !el.getAttribute) return false;
  if (el.getAttribute('data-abst-created')) return true;
  var cl = el.classList;
  return !!(cl && (cl.contains('abst-variation-marker') || cl.contains('abst-hidden-placeholder') ||
    cl.contains('abst-move-anchor') || cl.contains('abst-create-preview')));
}

// nth-child position of `el` among its parent's REAL children (editor artifacts skipped).
function abstRealNthChildIndex(el) {
  var idx = 0;
  var child = el.parentNode ? el.parentNode.firstElementChild : null;
  while (child) {
    if (!abstIsEditorArtifactNode(child)) {
      idx++;
      if (child === el) return idx;
    }
    child = child.nextElementSibling;
  }
  return -1;
}

function generateShortPath(element) {
  let path = [];
  let current = element;

  // Walk up the DOM tree until we find an ID or reach the body
  while (current && current !== document.body) {
      // If we find an element with ID, use that and stop
      if (current.id && !isIgnored('id', current.id)) {
          path.unshift('#' + current.id);
          break;
      }
      
      // Try to create a unique selector for this level
      const tag = current.tagName.toLowerCase();
      let selector = tag;
      let foundUniqueClass = false;
      
      // Add a class if it helps make it more specific but not too specific
      if (current.classList.length > 0) {
          // Find the most specific useful class
          for (const cls of current.classList) {
              if (isIgnored('class', cls)) continue;
              const testSelector = tag + '.' + cls;
              if (current.parentNode && current.parentNode.querySelectorAll(testSelector).length === 1) {
                  selector = testSelector;
                  foundUniqueClass = true;
                  break;
              }
          }
      }
      
      // If we still don't have a unique selector at this level, add nth-child
      // ALWAYS add nth-child if no unique class was found to ensure uniqueness
      if (!foundUniqueClass && current.parentNode) {
          const siblings = Array.from(current.parentNode.querySelectorAll(':scope > ' + selector))
              .filter(function (s) { return !abstIsEditorArtifactNode(s); });
          if (siblings.length > 1) {
              const index = abstRealNthChildIndex(current);
              if (index > 0) selector += ':nth-child(' + index + ')';
          }
      }
      
      path.unshift(selector);
      current = current.parentNode;
      
      // Check if our path is already unique
      const testPath = path.join(' > ');
      if (document.querySelectorAll(testPath).length === 1) {
          return testPath;
      }
  }
  
  // Final check: if the path still isn't unique, add nth-child to the target element
  let finalPath = path.join(' > ');
  if (document.querySelectorAll(finalPath).length > 1) {
      // Find the index of our element among matching elements
      const matches = document.querySelectorAll(finalPath);
      for (let i = 0; i < matches.length; i++) {
          if (matches[i] === element) {
              // Use :nth-of-type or :eq() style selector - but CSS doesn't have :eq
              // Instead, rebuild with nth-child on the first element in path
              const firstSelector = path[path.length - 1];
              const parent = element.parentNode;
              if (parent) {
                  const index = abstRealNthChildIndex(element);
                  if (index > 0) {
                      path[path.length - 1] = firstSelector.replace(/:nth-child\(\d+\)$/, '') + ':nth-child(' + index + ')';
                  }
              }
              break;
          }
      }
  }
  
  return path.join(' > ');
}
function isIgnored(type, value) {
  if (!value) return false;
  // Ignore EVERY plugin-added class (abst-variation, abst-adjusted-for-magic-bar,
  // abst-variation-marker, abst-hidden-placeholder, …): they exist only while the editor
  // is open, so a selector built on one never matches the visitor page and the op
  // silently no-ops in production.
  if (type === 'class' && value.indexOf('abst-') === 0) return true;

  // Ignore Tailwind-style variants / arbitrary values
  if (type === 'class') {
    // contains variant separator ":", arbitrary value brackets "[]", or slash values "m-2/3"
    if (/[.:\[\]\/]/.test(value)) return true;
  }

  const prefixes = window.abst.ignoreSelectorPrefixes || [];
  const relevantPrefixes = prefixes
      .map(p => (p || '').trim())
      .filter(p => {
          if (type === 'id') return p.startsWith('#');
          if (type === 'class') return !p.startsWith('#'); // Treat no-prefix as class
          return false;
      })
      .map(p => p.startsWith('.') || p.startsWith('#') ? p.slice(1) : p);

  for (const prefix of relevantPrefixes) {
      if (value.startsWith(prefix)) {
          return true;
      }
  }
  return false;
}





// Persist UTM params across internal navigations using sessionStorage
// On landing page, URL has ?utm_source=...&utm_medium=...&utm_campaign=...
// On subsequent pages, these are lost from the URL - we want to keep them for the whole session
try {
  var storedUtm = sessionStorage.getItem('abst_original_utm');
  if (!storedUtm) {
    var utmParams = new URLSearchParams(window.location.search);
    var utmData = {};
    ['utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content'].forEach(function(key) {
      var val = utmParams.get(key);
      if (val) utmData[key] = val;
    });
    // Store as JSON - even if empty, mark session as initialized
    sessionStorage.setItem('abst_original_utm', JSON.stringify(utmData));
  }
} catch(e) {
  // sessionStorage not available - UTMs only captured from current URL
}

// Helper: returns the query string for journey events, ensuring sessionized UTM params are always present
function abstGetEventUrl() {
  var search = window.location.search;
  try {
    var stored = sessionStorage.getItem('abst_original_utm');
    if (stored) {
      var utmData = JSON.parse(stored);
      var params = new URLSearchParams(search);
      var added = false;
      for (var key in utmData) {
        if (!params.has(key)) {
          params.set(key, utmData[key]);
          added = true;
        }
      }
      if (added) {
        search = '?' + params.toString();
      }
    }
  } catch(e) {}
  return search;
}

function enableClickTracking(){

  if(window.abstheatmapScreenSize){
    return; // already enabled
  }
  /*
  type index
  pv : page visit
  c  : click
  tv-1234 : test visit with test id
  tc-1234 : test conversion with test id
  s  : scroll : meta value is maxDepth as percentage (0-100)
  */
  var viewportWidth = window.innerWidth || document.documentElement.clientWidth || 0;
  var dpr = window.devicePixelRatio || 1;
  var screenWidth = window.screen && window.screen.width ? Math.round(window.screen.width / dpr) : 0;
  var effectiveWidth = viewportWidth || screenWidth;
  if (effectiveWidth > 1024)
    window.abstheatmapScreenSize = 'l';
  else if (effectiveWidth > 768)
    window.abstheatmapScreenSize = 'm';
  else
    window.abstheatmapScreenSize = 's';

  // Persist original external referrer across internal navigations using sessionStorage
  // On first arrival from an external site, document.referrer has the source (e.g. google.com)
  // On subsequent internal page loads, document.referrer becomes your own domain - we don't want that
  try {
    var storedReferrer = sessionStorage.getItem('abst_original_referrer');
    if (!storedReferrer) {
      // First page of this session - check if referrer is external
      var ref = document.referrer || '';
      if (ref) {
        try {
          var refHost = new URL(ref).hostname;
          var currentHost = window.location.hostname;
          // Only store if it's from a different domain (external referrer)
          if (refHost !== currentHost) {
            sessionStorage.setItem('abst_original_referrer', ref);
          }
        } catch(e) {
          // Invalid URL in referrer, store as-is
          sessionStorage.setItem('abst_original_referrer', ref);
        }
      }
      // If no referrer at all (direct traffic), store empty string to mark session as initialized
      if (!sessionStorage.getItem('abst_original_referrer')) {
        sessionStorage.setItem('abst_original_referrer', '');
      }
    }
  } catch(e) {
    // sessionStorage not available (private browsing etc) - fall back to document.referrer
  }

  
  //sample line [timestamp | uuid | url | element_id_or_selector | click_x | click_y | screen_size | meta]
  window.abst.clickRegister[new Date().toISOString()] = {
    timestamp: new Date().toISOString(),
    type: 'pv',
    post_id: btab_vars.post_id,
    uuid: abstGetAdvancedId(),
    ab_advanced_id: abstGetAdvancedId(),
    url: abstGetEventUrl(),
    element_id_or_selector: '0',
    click_x: 0,
    click_y: 0,
    screen_size: window.abstheatmapScreenSize,
    meta: '',
  };

  var trackable_elements = ['a','button','input','textarea','select']; // todo filter this to reduce filesize

  // Rage click detection: Track rapid clicks on same element
  // Definition: 3+ clicks on same element within 1000ms = rage click (user frustration)
  var rageClickTracker = {
    clicks: [],
    threshold: 3,        // Number of clicks to qualify as rage
    timeWindow: 2000,    // Time window in ms (1 second)
     
    addClick: function(selector, timestamp) {
      // Remove old clicks outside time window
      var cutoff = timestamp - this.timeWindow;
      this.clicks = this.clicks.filter(function(click) {
        return click.timestamp > cutoff;
      });
      
      // Add new click
      this.clicks.push({ selector: selector, timestamp: timestamp });
      
      // Check if this qualifies as rage click
      var sameElementClicks = this.clicks.filter(function(click) {
        return click.selector === selector;
      });
      
      return sameElementClicks.length >= this.threshold;
    }
  };

  // Helper function to detect if an element is interactive
  function isInteractive(element) {
    // Direct interactive elements
    var tagName = element.tagName.toLowerCase();
    if (tagName === 'a' || tagName === 'button' || tagName === 'input' || 
        tagName === 'select' || tagName === 'textarea') {
      return true;
    }
    
    // Has click handler
    if (element.onclick || element.hasAttribute('onclick')) {
      return true;
    }
    
    // Has interactive role
    var role = element.getAttribute('role');
    if (role === 'button' || role === 'link') {
      return true;
    }
    
    // Has cursor pointer (CSS indicates clickable)
    var style = window.getComputedStyle(element);
    if (style.cursor === 'pointer') {
      return true;
    }
    
    // Inside an interactive parent
    if (element.closest('a, button, [role="button"], [role="link"], [onclick]')) {
      return true;
    }

    //has href
    if (element.hasAttribute('href')) {
      return true;
    }
    
    return false;
  }

  //watch for click events on trackable elements  in the dom now or later
    document.addEventListener('click', function(event) {

      var rect = event.target.getBoundingClientRect();
      var xval = (event.clientX - rect.left) / rect.width;
      var yval = (event.clientY - rect.top) / rect.height;
      xval = Math.round(xval * 1000) / 1000;
      yval = Math.round(yval * 1000) / 1000;

      var target = event.target;
      if (true)  { // todo filter this to reduce filesize
        var selector = getUniqueSelector(target);
        var timestamp = Date.now();
        var isRageClick = rageClickTracker.addClick(selector, timestamp);
        var isDeadClick = !isInteractive(target);
        
        //add line to object
        window.abst.clickRegister[new Date().toISOString()] = {
          timestamp: new Date().toISOString(),
          type: isRageClick ? 'rc' : 'c', // 'rc' = rage click, 'c' = normal click
          post_id: btab_vars.post_id,
          uuid: abstGetAdvancedId(),
          ab_advanced_id: abstGetAdvancedId(),
          url: abstGetEventUrl(),
          element_id_or_selector: selector,
          click_x: xval, // position relative to element_id_or_selector as a % from left
          click_y: yval,// position relative to element_id_or_selector as a % from top 
          screen_size: window.abstheatmapScreenSize,
          meta: isRageClick ? 'rage_click' : (isDeadClick ? 'dead_click' : ''),
        };

        // Immediately flush data for link clicks or form submit buttons (user may navigate away)
        var isLink = event.target.tagName === 'A' || event.target.closest('a');
        var isSubmit = (event.target.tagName === 'BUTTON' && event.target.type === 'submit') || 
                       (event.target.tagName === 'INPUT' && event.target.type === 'submit') ||
                       event.target.closest('button[type="submit"], input[type="submit"]');
        
        if (isLink || isSubmit) {
          flushJourneyData(false);
          // Flush A/B test event queue before navigation
          if (window.abst.hasApproval) {
            abst_process_approved_events();
          }
        }
      }
    });
    
    // Also catch form submissions directly (covers JS-triggered submits)
    document.addEventListener('submit', function(event) {
      flushJourneyData(false);
      if (window.abst.hasApproval) {
        abst_process_approved_events();
      }
    });

    //add scroll, listener, if over max update max value
    window.abst.heatScrollMax = 0;
    // Capture viewport height at load for the "average fold" calculation.
    window.abst.heatViewport = window.innerHeight;
    var scrollEventLock = false;
    document.addEventListener('scroll', function() {
      if (scrollEventLock) return;
      
      scrollEventLock = true;
      
      var windowHeight = window.innerHeight;
      var documentHeight = document.documentElement.scrollHeight;
      var scrollTop = window.pageYOffset || document.documentElement.scrollTop;
      
      // Calculate scroll depth as percentage
      var scrollDepth = Math.round(((scrollTop + windowHeight) / documentHeight) * 100);
      
      if (scrollDepth > window.abst.heatScrollMax) {
        window.abst.heatScrollMax = scrollDepth;
      }
      
      setTimeout(function() { // dont update scroll value another 300ms
        scrollEventLock = false;
      }, 300);
    }, { passive: true });

    // visibilitychange->hidden is the most reliable end-of-pageview signal.
    // Every flush carries latest scroll max + viewport; the server keeps the
    // deepest scroll value per session.
    document.addEventListener('visibilitychange', function() {
      if (document.visibilityState === 'hidden') {
        flushJourneyData();
      }
    });
    
    // pagehide fires when navigating away - flush any remaining events.
    // Safe for bfcache, unlike beforeunload.
    window.addEventListener('pagehide', function() {
      flushJourneyData();
    });
    
    window.addEventListener('pageshow', function(event) {
      // bfcache restore - user hit back/forward button
      if (event.persisted) {
        // Reset scroll tracking for fresh measurement
        window.abst.heatScrollMax = 0;
        window.abst.heatScrollLastSent = 0;
        
        // Log a new page view event for this "return" to the page
        window.abst.clickRegister['pv_' + new Date().toISOString()] = {
          timestamp: new Date().toISOString(),
          type: 'pv',
          post_id: btab_vars.post_id,
          uuid: abstGetAdvancedId(),
          ab_advanced_id: abstGetAdvancedId(),
          url: abstGetEventUrl(),
          element_id_or_selector: '',
          click_x: 0,
          click_y: 0,
          screen_size: window.abstheatmapScreenSize,
          meta: 'bfcache_restore'
        };
      }
    });
}

/**
 * Get active experiments for current user in format: "1234:varA,5678:varB"
 */
function getActiveExperiments() {
  if (typeof bt_experiments === 'undefined' || !bt_experiments) {
    return '';
  }  
  var experimentPairs = [];
  for (var eid in bt_experiments) {
    var cookieVal = abstGetCookie('btab_' + eid);
    if (cookieVal) {
      try {
        var data = JSON.parse(cookieVal);
        // Skip if variation is undefined or missing
        if (data && data.variation && data.variation !== 'undefined') {
          experimentPairs.push(eid + ':' + data.variation);
        }
      } catch (e) {
        // Skip invalid cookie data
      }
    }
  }
  if(experimentPairs.length === 0){
    return '';
  }
  return experimentPairs.join(',');
}

var enable_click_tracking = true;
var abstHeatmapGateLogged = false;
var abstJourneyDisabledLogged = false;
var abstJourneyAdminLogged = false;
var abstJourneyConsentLogged = false;
  
function check_heatmap_tracking() {

  if(typeof btab_vars !== 'undefined' && typeof btab_vars.abst_enable_user_journeys !== 'undefined' && btab_vars.abst_enable_user_journeys === '0') {
    enable_click_tracking = false;
    if (!abstHeatmapGateLogged) {
      console.log('ABST: Heatmap tracking disabled because user journeys are disabled.', {
        abst_enable_user_journeys: btab_vars.abst_enable_user_journeys
      });
      abstHeatmapGateLogged = true;
    }
  }

  // Heatmaps record on every page (no page gate); only admins are skipped.
  var should_track_heatmap = false;
  if(enable_click_tracking && typeof btab_vars !== 'undefined') {
    var current_post_id = window.current_page; //array of post id's or tags if archives 404 etc

    if (btab_vars.is_admin) {
      should_track_heatmap = false;
      console.log('ABST: Heatmap tracking skipped for admin user.', {
        current_page: current_post_id
      });
    } else {
      should_track_heatmap = true;
    }
  }
  // Initialize heatmap tracking if enabled
  if(enable_click_tracking && should_track_heatmap){
    if (!abstGetAdvancedId()) {
      setAbCrypto(); // Create UUID for heatmap tracking
    }
    enableClickTracking();
    console.log('ABST: Heatmap tracking enabled.', {
      url: window.location.href,
      current_page: window.current_page,
      has_approval: window.abst.hasApproval
    });
  } else if (enable_click_tracking && typeof btab_vars !== 'undefined' && !btab_vars.is_admin) {
    console.log('ABST: Heatmap tracking not enabled on this page.', {
      url: window.location.href,
      current_page: window.current_page
    });
  }

}


function flushJourneyData() {
  // Don't send journey data if feature is disabled
  if(typeof btab_vars !== 'undefined' && btab_vars.abst_enable_user_journeys !== '1') {
    if (!abstJourneyDisabledLogged) {
      console.log('ABST: Journey data not sent because user journeys are disabled.', {
        abst_enable_user_journeys: btab_vars.abst_enable_user_journeys
      });
      abstJourneyDisabledLogged = true;
    }
    return;
  }
  
  if(btab_vars.is_admin){
    if (!abstJourneyAdminLogged) {
      console.log('ABST: Journey data not sent for admin user.');
      abstJourneyAdminLogged = true;
    }
    return;
  }
  
  var hasJourneyEvents = Object.keys(window.abst.clickRegister).length > 0;
  var scrollMax = Number(window.abst.heatScrollMax) || 0;
  var lastScrollSent = Number(window.abst.heatScrollLastSent) || 0;
  var hasNewScrollDepth = scrollMax > lastScrollSent;

  // Empty tab switches should not create noise, but scroll-only pageviews still
  // need one lightweight event so the parser can attach the meta scroll value to
  // this page.
  if (!hasJourneyEvents && !hasNewScrollDepth) return;

  // Ensure UUID exists before sending — journeys require one for attribution.
  // setAbCrypto() is consent-aware (sessionStorage until consent, then cookie).
  // If storage is fully blocked it will still return null, in which case skip.
  if (!abstGetAdvancedId()) {
    setAbCrypto();
  }
  var currentUuid = abstGetAdvancedId();
  if (!currentUuid) {
    return;
  }

  // Backfill records written before UUID was available (e.g. when heatmap
  // tracking was off so setAbCrypto was never called before the record was written).
  for (var bfKey in window.abst.clickRegister) {
    if (!window.abst.clickRegister[bfKey].uuid) {
      window.abst.clickRegister[bfKey].uuid = currentUuid;
    }
  }

  // Check if we have approval to send data
  if (!window.abst.hasApproval) {
    if (!abstJourneyConsentLogged) {
      console.log('ABST: Journey data queued but not sent because consent approval is pending.', {
        wait_for_approval: btab_vars.wait_for_approval
      });
      abstJourneyConsentLogged = true;
    }
    return;
  }

  // Create new batch with metadata line first
  var batchToSend = {};
  
  // Add metadata line at the start (includes scroll max if available)
  var metadataKey = 'meta_' + new Date().toISOString();
  batchToSend[metadataKey] = {
    timestamp: new Date().toISOString(),
    type: 'meta',
    post_id: btab_vars.post_id,
    uuid: abstGetAdvancedId(),
    ab_advanced_id: abstGetAdvancedId(),
    url: '',
    element_id_or_selector: '',
    click_x: 0,
    click_y: 0,
    screen_size: window.abstheatmapScreenSize,
    // Scroll max + viewport height are monotonic/constant for the pageview, so
    // send them on EVERY flush. This avoids losing them when visibilitychange
    // drains the click buffer before pagehide can fire.
    meta: window.abst.heatScrollMax,
    experiments: getActiveExperiments(),
    referrer: (function() { try { return sessionStorage.getItem('abst_original_referrer') || ''; } catch(e) { return document.referrer || ''; } })(),
    viewport_height: (window.abst.heatViewport || window.innerHeight)
  };
  if (!hasJourneyEvents && hasNewScrollDepth) {
    var scrollEventKey = 'scroll_' + new Date().toISOString();
    batchToSend[scrollEventKey] = {
      timestamp: new Date().toISOString(),
      type: 's',
      post_id: btab_vars.post_id,
      uuid: currentUuid,
      ab_advanced_id: currentUuid,
      url: abstGetEventUrl(),
      element_id_or_selector: '',
      click_x: 0,
      click_y: 0,
      screen_size: window.abstheatmapScreenSize,
      meta: 'scroll_depth'
    };
  }
  // Copy all existing events
  for (var key in window.abst.clickRegister) {
    batchToSend[key] = window.abst.clickRegister[key];
  }

  var journeyJson = JSON.stringify(batchToSend);
  var payload;
  try {
    payload = new Blob([journeyJson], { type: 'application/json' });
  } catch (e) {
    payload = journeyJson;
  }

  navigator.sendBeacon(bt_ajaxurl + '?action=abst_receive_journey_data', payload);
  console.log('ABST: Journey data sent.', batchToSend);
  window.abst.clickRegister = {}; //reset register
  window.abst.heatScrollLastSent = scrollMax;
}




/**
 * Initialize mutation observer for dynamically created test elements
 * Watches for elements with bt-eid and bt-variation attributes that match active tests
 */
function initAbstDynamicElementObserver() {
  // Get all active test variations from cookies/localStorage
  var activeTests = {};
  
  // Check for active tests in cookies/localStorage
  if (typeof bt_experiments === 'object' && bt_experiments) {
    for (var eid in bt_experiments) {
      var cookieValue = abstGetCookie('btab_' + eid);
      if (cookieValue) {
        try {
          var testData = JSON.parse(cookieValue);
          if (testData.variation && !testData.skipped) {
            activeTests[eid] = testData.variation;
          }
        } catch (e) {
          // Skip invalid cookie data
        }
      }
    }
  }
  
  // If no active tests, no need to observe
  if (Object.keys(activeTests).length === 0) {
    return;
  }
    
  // Create mutation observer
  var observer = new MutationObserver(function(mutations) {
    mutations.forEach(function(mutation) {
      // Check added nodes
      mutation.addedNodes.forEach(function(node) {
        // Only process element nodes
        if (node.nodeType !== 1) return;
        
        // Check if the node itself matches
        processNodeForTests(node, activeTests);
        
        // Check all descendant elements
        if (node.querySelectorAll) {
          var descendants = node.querySelectorAll('[bt-eid][bt-variation]');
          descendants.forEach(function(descendant) {
            processNodeForTests(descendant, activeTests);
          });
        }
      });
    });
  });
  
  // Start observing
  if (document.body) {
    observer.observe(document.body, {
      childList: true,
      subtree: true
    });
    
    // Store observer globally in case we need to disconnect later
    window.abstDynamicObserver = observer;
  }
}

/**
 * Process a node to check if it matches an active test
 * @param {Element} node - DOM element to check
 * @param {Object} activeTests - Object mapping test IDs to variations
 */
function processNodeForTests(node, activeTests) {
  if (!node.getAttribute) return;
  
  var eid = node.getAttribute('bt-eid');
  var variation = node.getAttribute('bt-variation');
  
  // If element has both attributes and matches an active test
  if (eid && variation && activeTests[eid] === variation) {
    // Add the show variation class if not already present
    if (!node.classList.contains('bt-show-variation')) {
      node.classList.add('bt-show-variation');
      console.log('ABST: Added bt-show-variation to dynamically created element', {
        eid: eid,
        variation: variation,
        element: node
      });
    }
  }
}

function abstGetAdvancedId() {
  if (sessionStorage.getItem('ab-advanced-id')) {
    return sessionStorage.getItem('ab-advanced-id');
  } else if (abstGetCookie('ab-advanced-id')) {
    return abstGetCookie('ab-advanced-id');
  } else if (window.abst && window.abst.visitorId) {
    return window.abst.visitorId;
  } else {
    return null;
  }
}
//pagehide
window.addEventListener('pagehide', function() {
  if (window.abst.hasApproval) {
    abst_process_approved_events();
  }
});
window.addEventListener('visibilitychange', function() {
  if (document.visibilityState === 'hidden' && window.abst.hasApproval) {
    abst_process_approved_events();
  }
});

setInterval(function() {
  if (window.abst && window.abst.hasApproval) {
    abst_process_approved_events();
  }
}, 1000);

// Journey/heatmap data - flush every 15s
setInterval(function() {
  if (window.abst && window.abst.hasApproval) {
    if (typeof flushJourneyData === 'function') {
      flushJourneyData(false);
    }
  }
}, 15000); 



function mapTextElementsWithSelectors() {
  // Check for ?abhash=1 query parameter
  const urlParams = new URLSearchParams(window.location.search);
  if (urlParams.get('abhash') !== '1') {
    return;
  }

  const textSelectorMap = [];
  const processedElements = new Set();
  const maxHeight = 3000; // Only include elements in top 3000px

  // Elements to skip
  const ignoreTagNames = ['script', 'style', 'noscript'];
  const ignoreIds = ['wpadminbar'];

  const shouldIgnore = (el) => {
    if (!el || !el.tagName) return false;
    if (ignoreTagNames.includes(el.tagName.toLowerCase())) return true;
    if (ignoreIds.some(id => el.id === id)) return true;
    try {
      return el.closest('#wpadminbar, .wp-admin-bar') !== null;
    } catch (e) {
      return false;
    }
  };

  const isVisible = (el) => {
    const rect = el.getBoundingClientRect();
    const style = window.getComputedStyle(el);
    
    // Check if element is in top 3000px of page
    if (rect.top > maxHeight) return false;
    
    // Check minimum size (50px wide, 20px tall)
    if (rect.width < 50 || rect.height < 20) return false;
    
    // Check if display is none or visibility is hidden
    if (style.display === 'none' || style.visibility === 'hidden') return false;
    
    // Check if opacity is 0
    if (style.opacity === '0') return false;
    
    // Check if overflow is hidden and element is clipped
    let current = el;
    while (current && current !== document.body) {
      const parentStyle = window.getComputedStyle(current);
      if (parentStyle.overflow === 'hidden' || parentStyle.overflow === 'hidden hidden') {
        const parentRect = current.getBoundingClientRect();
        // If parent clips the element, skip it
        if (rect.right > parentRect.right || rect.bottom > parentRect.bottom ||
            rect.left < parentRect.left || rect.top < parentRect.top) {
          return false;
        }
      }
      current = current.parentElement;
    }
    
    return true;
  };

  // Get breadcrumb path from element up to body, with compacted divs
  function getBreadcrumb(el) {
    const path = [];
    let current = el;
    
    while (current && current !== document.body) {
      if (current.tagName) {
        let tag = current.tagName.toLowerCase();
        if (current.id) {
          tag += `#${current.id}`;
        }
        path.unshift(tag);
      }
      current = current.parentElement;
    }
    
    // Add body at the start
    path.unshift('body');
    
    // Compact consecutive divs
    let compacted = [];
    let divCount = 0;
    
    for (let tag of path) {
      if (tag === 'div') {
        divCount++;
      } else {
        if (divCount > 0) {
          compacted.push(divCount > 1 ? `div[×${divCount}]` : 'div');
          divCount = 0;
        }
        compacted.push(tag);
      }
    }
    
    // Handle trailing divs
    if (divCount > 0) {
      compacted.push(divCount > 1 ? `div[×${divCount}]` : 'div');
    }
    
    return compacted.join(' > ');
  }

  function walk(node) {
    if (!node) return;
    if (shouldIgnore(node)) return;

    // If it's a text node with actual content
    if (node.nodeType === Node.TEXT_NODE) {
      const text = node.nodeValue.trim();
      if (text.length >= 3) {
        // Get the parent element
        const parentElement = node.parentElement;
        if (parentElement && !processedElements.has(parentElement)) {
          processedElements.add(parentElement);
          
          // Check if element is visible and big enough
          if (!isVisible(parentElement)) {
            return;
          }
          
          const selector = getUniqueSelector(parentElement);
          const fullText = parentElement.textContent.trim();
          const breadcrumb = getBreadcrumb(parentElement);
          
          textSelectorMap.push({
            text: fullText,
            selector: selector,
            breadcrumb: breadcrumb,
          });
        }
      }
      return;
    }

    // Recurse into child nodes in order
    if (node.nodeType === Node.ELEMENT_NODE) {
      for (let child of node.childNodes) {
        walk(child);
      }
    }
  }

  // Safety check for document.body
  if (!document.body) {
    console.warn('ABST: document.body not available for text selector mapping');
    return [];
  }
  
  walk(document.body);
  createSelectorTable(textSelectorMap);
  
  return textSelectorMap;
}
 
function createSelectorTable(data) {
  // Safety check for document.body
  if (!document.body) {
    console.warn('ABST: document.body not available for selector table creation');
    return;
  }
  
  // Create container
  const container = document.createElement('div');
  container.style.cssText = `
    margin: 40px 20px;
    padding: 20px;
    background: #f0f0f0;
    border: 2px solid #333; 
  `;

  // Title
  const title = document.createElement('h2');
  title.textContent = `Text Selector Map (${data.length} elements in top 3000px)`;
  title.style.cssText = 'margin-top: 0; font-family: system-ui; font-size: 18px;';
  container.appendChild(title);

  // Create table
  const table = document.createElement('table');
  table.style.cssText = `
    width: 100%;
    border-collapse: collapse;
    font-family: monospace;
    font-size: 12px;
    background: white;
    border: 1px solid #ddd;
  `;

  // Header
  const thead = document.createElement('thead');
  thead.innerHTML = `
    <tr style="background: #333; color: white;">
      <th style="padding: 8px; text-align: left; border: 1px solid #ddd; width: 25%;">Text Content</th>
      <th style="padding: 8px; text-align: left; border: 1px solid #ddd; width: 35%;">Unique Selector</th>
      <th style="padding: 8px; text-align: left; border: 1px solid #ddd; width: 35%;">Element Hierarchy</th>
    </tr>
  `;
  table.appendChild(thead);

  // Body
  const tbody = document.createElement('tbody');
  data.forEach((row, idx) => {
    const tr = document.createElement('tr');
    tr.style.cssText = idx % 2 === 0 ? 'background: white;' : 'background: #f9f9f9;';
    
    const textCell = document.createElement('td');
    textCell.style.cssText = 'padding: 8px; border: 1px solid #ddd; max-width: 250px; word-break: break-word; white-space: pre-wrap;';
    textCell.textContent = row.text.substring(0, 80) + (row.text.length > 80 ? '...' : '');
    
    const selectorCell = document.createElement('td');
    selectorCell.style.cssText = 'padding: 8px; border: 1px solid #ddd; font-size: 11px; background: #f5f5f5; word-break: break-all; font-family: "Courier New", monospace;';
    selectorCell.textContent = row.selector;
    
    const breadcrumbCell = document.createElement('td');
    breadcrumbCell.style.cssText = 'padding: 8px; border: 1px solid #ddd; font-size: 11px; background: #fafafa; word-break: break-all; font-family: "Courier New", monospace; color: #555;';
    breadcrumbCell.textContent = row.breadcrumb;
    
    const tagCell = document.createElement('td');
    tagCell.style.cssText = 'padding: 8px; border: 1px solid #ddd; text-align: center; font-weight: bold;';
    tagCell.textContent = row.tagName;
    
    tr.appendChild(textCell);
    tr.appendChild(selectorCell);
    tr.appendChild(breadcrumbCell);
    tr.appendChild(tagCell);
    tbody.appendChild(tr);
  });
  table.appendChild(tbody);

  container.appendChild(table);
  document.body.appendChild(container);
  
  console.log(`ABST: Generated text to selector table with ${data.length} user-facing text elements in top 3000px`);
}

// Run it only when DOM is ready
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', function() {
    mapTextElementsWithSelectors();
  });
} else {
  // DOM already loaded
  mapTextElementsWithSelectors();
}

function abstForgetMe(){
  const urlParams = new URLSearchParams(window.location.search);
  if(urlParams.get('abstforgetme')){
    console.log('ABST: forgetting you, reloading page');
    
    // Get all cookies/localStorage/sessionStorage items starting with btab_ or ab-
    const cookies = document.cookie.split(';');
    for(let i = 0; i < cookies.length; i++){
      const cookie = cookies[i].trim();
      const cookieName = cookie.split('=')[0];
      if(cookieName.startsWith('btab_') || cookieName === 'ab-advanced-id'){
        abstDeleteCookie(cookieName);
      }
    } 
    
    // Also clear from localStorage and sessionStorage
    const storageKeys = Object.keys(localStorage);
    for(let i = 0; i < storageKeys.length; i++){
      if(storageKeys[i].startsWith('btab_') ||  storageKeys[i] === 'ab-advanced-id'){
        localStorage.removeItem(storageKeys[i]);
      }
    }
    
    const sessionKeys = Object.keys(sessionStorage);
    for(let i = 0; i < sessionKeys.length; i++){
      if(sessionKeys[i].startsWith('btab_') || sessionKeys[i] === 'ab-advanced-id'){
        sessionStorage.removeItem(sessionKeys[i]);
      }
    }
    
    //reload without the forgetme parameter
    const url = new URL(window.location.href);
    url.searchParams.delete('abstforgetme');
    window.location.href = url.toString();
    console.log('poof!');
  }
}
abstForgetMe();
