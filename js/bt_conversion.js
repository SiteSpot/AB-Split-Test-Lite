
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
  var maxWait = 5000; // Max 5 seconds
  var interval = 50;  // Check every 50ms
  var waited = 0;
  
  function configReady() {
    return (window.ABST_CONFIG && window.ABST_CONFIG.btab_vars) || 
           (window.btab_vars && Object.keys(window.btab_vars).length > 0);
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
          // trigger the experiment setup that was missed
          if (document.readyState !== 'loading' && hasExperiments() && !document.body.classList.contains('ab-test-setup-complete')) {
            console.log('ABST: Running delayed experiment initialization...');
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

// is user active global vars
window.abst = window.abst || {};
// activity timer
window.abst.eventQueue = [];
window.abst.abconvertpartner = {};
window.abst.ignoreSelectorPrefixes = ['abst-variation','stk-'];
window.abst.clickRegister = window.abst.clickRegister || {};
window.abst.pageUnloading = false;
window.abst.scrollSentThisPageview = false;
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
  
// activity timer - initialize once as an object
try {
  window.abst.timer = localStorage.getItem('absttimer') === null ? {} : JSON.parse(localStorage.getItem('absttimer') || '{}');
} catch (e) {
  window.abst.timer = {};
}

window.abst.currscroll = window.scrollY;
window.abst.currentMousePos = -1;
window.abst.oldMousePos = -2;
window.abst.abactive = true;
window.abst.timeoutTime = 3000; // how much inactivity before we stop logging in milliseconds
window.abst.intervals = {};
// Only set to true if undefined, respecting any intentional false value
if(window.abst.isTrackingAllowed === undefined) 
  window.abst.isTrackingAllowed = true;

// Initialize service partners
const services = ['abawp', 'clarity', 'gai', 'abmix', 'abumav', 'umami', 'cabin', 'plausible', 'fathom', 'ga4', 'posthog'];
window.abst.abconvertpartner = Object.fromEntries(services.map((service) => [service, false]));

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

// add server events to cookie to be evented on next page load
function addServerEvents(data) {
  const serverEvents = abstGetCookie('abst_server_events');

  if (serverEvents) {
    try {
      const events = JSON.parse(decodeURIComponent(serverEvents));
      if (!events.some(event => event.eid === data.eid && event.variation === data.variation && event.type === data.type)) {
        events.push(data);
        abstSetCookie('abst_server_events', JSON.stringify(events), 2);
        console.log('ABST: Server event added to existing', data);
      }
    } catch (e) {
      // Cookie corrupted, start fresh
      abstSetCookie('abst_server_events', JSON.stringify([data]), 2);
    }
  } else {
    abstSetCookie('abst_server_events', JSON.stringify([data]), 2);
    console.log('ABST: Server event added none existing creating', data);
  }
}

function abstParseCurrencyValue(rawValue) {
  if (rawValue === null || rawValue === undefined) {
    return null;
  }

  let value = String(rawValue).trim();
  if (!value) {
    return null;
  }

  value = value.replace(/\u00a0/g, ' ').replace(/\s+/g, ' ');

  let negative = false;
  if (/^\(.*\)$/.test(value)) {
    negative = true;
    value = value.slice(1, -1);
  }

  if (value.indexOf('-') !== -1) {
    negative = true;
  }

  value = value.replace(/[^0-9,.\-]/g, '');
  if (!/[0-9]/.test(value)) {
    return null;
  }

  const lastComma = value.lastIndexOf(',');
  const lastDot = value.lastIndexOf('.');
  let normalized = value;

  if (lastComma !== -1 && lastDot !== -1) {
    if (lastComma > lastDot) {
      normalized = normalized.replace(/\./g, '').replace(',', '.');
    } else {
      normalized = normalized.replace(/,/g, '');
    }
  } else if (lastComma !== -1) {
    const decimals = normalized.length - lastComma - 1;
    if (decimals > 0 && decimals <= 2) {
      normalized = normalized.replace(/\./g, '').replace(',', '.');
    } else {
      normalized = normalized.replace(/,/g, '');
    }
  } else if (lastDot !== -1) {
    const decimals = normalized.length - lastDot - 1;
    if (decimals > 2) {
      normalized = normalized.replace(/\./g, '');
    }
  }

  normalized = normalized.replace(/(?!^)-/g, '');
  let parsed = parseFloat(normalized);
  if (!isFinite(parsed) || parsed <= 0) {
    return null;
  }

  if (negative) {
    parsed = parsed * -1;
  }

  if (!isFinite(parsed) || parsed <= 0) {
    return null;
  }

  return Math.round(parsed * 100) / 100;
}

function abstEscapeRegExp(value) {
  return String(value).replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
}

function abstRememberDetectedOrderValue(rawValue, source) {
  const parsed = abstParseCurrencyValue(rawValue);
  if (parsed === null) {
    return null;
  }

  window.abst.abConversionValue = parsed.toFixed(2);
  window.abst.abConversionValueSource = source || 'detected';
  console.log('ABST: Detected order value', window.abst.abConversionValue, 'from', window.abst.abConversionValueSource);
  return {
    value: parsed,
    formatted: parsed.toFixed(2),
    source: window.abst.abConversionValueSource
  };
}

function abstSetDetectedOrderValue(rawValue, source) {
  return !!abstRememberDetectedOrderValue(rawValue, source);
}

function abstNeedsOrderValueDetection() {
  if (typeof conversion_details !== 'undefined' && conversion_details) {
    for (const detail of Object.values(conversion_details)) {
      if (detail && (detail.use_order_value === true || detail.use_order_value === '1')) {
        return true;
      }
    }
  }

  if (typeof bt_experiments !== 'undefined' && bt_experiments) {
    for (const experiment of Object.values(bt_experiments)) {
      if (experiment && (experiment.use_order_value === true || experiment.use_order_value === '1')) {
        return true;
      }
    }
  }

  return false;
}

function abstGetCustomOrderValue() {
  if (!window.abst) {
    return null;
  }

  const parsed = abstParseCurrencyValue(window.abst.abConversionValue);
  if (parsed === null || parsed <= 0) {
    return null;
  }

  const source = window.abst.abConversionValueSource || 'preset';
  if (source === 'default' && parsed === 1) {
    return null;
  }

  return {
    value: parsed,
    formatted: parsed.toFixed(2),
    source: source
  };
}

function abstGetOrderValueFromQuery(preferredKey) {
  try {
    const params = new URLSearchParams(window.location.search);
    const exactKeys = preferredKey
      ? [String(preferredKey)]
      : ['total_paid', 'amount_paid', 'payment_total', 'grand_total', 'order_total', 'ordertotal','total'];

    for (const key of exactKeys) {
      const lowerKey = key.toLowerCase();
      for (const pair of params.entries()) {
        if (String(pair[0] || '').toLowerCase() !== lowerKey) {
          continue;
        }

        const remembered = abstRememberDetectedOrderValue(pair[1], 'query:' + pair[0]);
        if (remembered) {
          return remembered;
        }
      }
    }
  } catch (e) {
    console.warn('ABST: Failed to inspect query params for order value', e);
  }

  return null;
}

function abstDetectOrderValueFromQuery() {
  return !!abstGetOrderValueFromQuery();
}

function abstDetectOrderValueFromDom() {
  const selectorCandidates = [
    '[data-order-total]',
    '.woocommerce-order-overview__total .amount',
    '.order-total .amount',
    '.order-total .woocommerce-Price-amount',
    '.edd_purchase_total',
    '.edd-order-total',
    '.surecart-order-total',
    '.fluentcart-order-total'
  ];

  for (const selector of selectorCandidates) {
    const nodes = document.querySelectorAll(selector);
    for (const node of nodes) {
      const text = (node.getAttribute('data-order-total') || node.getAttribute('data-total') || node.textContent || '').trim();
      if (text && abstSetDetectedOrderValue(text, 'dom:' + selector)) {
        return true;
      }
    }
  }

  try {
    const bodyText = ((document.body && document.body.innerText) || '').slice(0, 150000);
    const labelPatterns = [
      /order total[^\d$€£]{0,30}([$€£]?\s*[\d.,]+)/i,
      /grand total[^\d$€£]{0,30}([$€£]?\s*[\d.,]+)/i,
      /amount paid[^\d$€£]{0,30}([$€£]?\s*[\d.,]+)/i,
      /total paid[^\d$€£]{0,30}([$€£]?\s*[\d.,]+)/i,
      /payment total[^\d$€£]{0,30}([$€£]?\s*[\d.,]+)/i
    ];

    for (const pattern of labelPatterns) {
      const match = bodyText.match(pattern);
      if (match && match[1] && abstSetDetectedOrderValue(match[1], 'text')) {
        return true;
      }
    }
  } catch (e) {
    console.warn('ABST: Failed to inspect page text for order value', e);
  }

  return false;
}

function abstDetectOrderValue() {
  if (!window.abst || !abstNeedsOrderValueDetection()) {
    return false;
  }

  const existingValue = abstParseCurrencyValue(window.abst.abConversionValue);
  if (existingValue !== null && existingValue > 1 && window.abst.abConversionValueSource !== 'default') {
    return true;
  }

  if (abstDetectOrderValueFromQuery()) {
    return true;
  }

  if (abstDetectOrderValueFromDom()) {
    return true;
  }

  return false;
}

function abstGetOrderValueFromSelector(selector) {
  if (!selector) {
    return null;
  }

  try {
    const nodes = document.querySelectorAll(selector);
    for (const node of nodes) {
      const text = (node.getAttribute('data-order-total') || node.getAttribute('data-total') || node.textContent || '').trim();
      if (!text) {
        continue;
      }

      const remembered = abstRememberDetectedOrderValue(text, 'dom:' + selector);
      if (remembered) {
        return remembered;
      }
    }
  } catch (e) {
    console.warn('ABST: Failed to inspect selector for order value', selector, e);
  }

  return null;
}

function abstGetOrderValueFromTextLabel(label) {
  try {
    const bodyText = ((document.body && document.body.innerText) || '').slice(0, 150000);
    const labelPatterns = label
      ? [new RegExp(abstEscapeRegExp(label) + '[^\\d$€£]{0,30}([$€£]?\\s*[\\d.,]+)', 'i')]
      : [
          /order total[^\d$€£]{0,30}([$€£]?\s*[\d.,]+)/i,
          /grand total[^\d$€£]{0,30}([$€£]?\s*[\d.,]+)/i,
          /amount paid[^\d$€£]{0,30}([$€£]?\s*[\d.,]+)/i,
          /total paid[^\d$€£]{0,30}([$€£]?\s*[\d.,]+)/i,
          /payment total[^\d$€£]{0,30}([$€£]?\s*[\d.,]+)/i
        ];

    for (const pattern of labelPatterns) {
      const match = bodyText.match(pattern);
      if (!match || !match[1]) {
        continue;
      }

      const remembered = abstRememberDetectedOrderValue(match[1], label ? 'text:' + label : 'text');
      if (remembered) {
        return remembered;
      }
    }
  } catch (e) {
    console.warn('ABST: Failed to inspect page text for order value', e);
  }

  return null;
}

function abstGetAutoOrderValue() {
  const queryMatch = abstGetOrderValueFromQuery();
  if (queryMatch) {
    return queryMatch;
  }

  const selectorCandidates = [
    '[data-order-total]',
    '.woocommerce-order-overview__total .amount',
    '.order-total .amount',
    '.order-total .woocommerce-Price-amount',
    '.edd_purchase_total',
    '.edd-order-total',
    '.surecart-order-total',
    '.fluentcart-order-total'
  ];

  for (const selector of selectorCandidates) {
    const selectorMatch = abstGetOrderValueFromSelector(selector);
    if (selectorMatch) {
      return selectorMatch;
    }
  }

  return abstGetOrderValueFromTextLabel('');
}

function abstResolveOrderValue(config) {
  const customValue = abstGetCustomOrderValue();
  if (customValue) {
    return customValue;
  }

  if (!config || !(config.use_order_value === true || config.use_order_value === '1')) {
    return null;
  }

  const fallbackMethod = config.order_value_fallback_method || 'auto';
  if (fallbackMethod === 'url_parameter') {
    return abstGetOrderValueFromQuery(config.order_value_url_parameter || '');
  }

  if (fallbackMethod === 'css_selector') {
    return abstGetOrderValueFromSelector(config.order_value_css_selector || '');
  }

  if (fallbackMethod === 'page_text_label') {
    return abstGetOrderValueFromTextLabel(config.order_value_text_label || '');
  }

  return abstGetAutoOrderValue();
}

// Main initialization function - can be called on DOMContentLoaded or when deferred config loads
function abstMainInit() {
  // Prevent running twice
  if (document.body && document.body.classList.contains('ab-test-setup-complete')) {
    return;
  }
  setupConsentPartners();
  const serverEvents = abstGetCookie('abst_server_events');
  if (serverEvents) {
    //console.log('server events found, going to process them', serverEvents);
    let events;
    try {
      events = JSON.parse(decodeURIComponent(serverEvents));
    } catch (e) {
      events = [];
    }
    if (Array.isArray(events)) {
      events.forEach(event => {
        //console.log('server event', event);
        btab_track_event(event);
      });
    }
    abstDeleteCookie('abst_server_events');
    console.log('ABST: Server events processed');
  }

  if (window.btab_vars && !window.btab_vars.is_preview) {
    var abTestRedirects = document.querySelectorAll('.ab-test-page-redirect');
    abTestRedirects.forEach(function (el) { el.remove(); });
  }

  var btHiddenEls = document.querySelectorAll('[bt_hidden="true"]');
  btHiddenEls.forEach(function (el) { el.remove(); });


  // update scroll status
  document.addEventListener('mousemove', function (event) {
    window.abst.currentMousePos = event.pageX;
  });
  // catch mouse / keyboard action
  document.body.addEventListener('mousedown', userActiveNow);
  document.body.addEventListener('keydown', userActiveNow);
  document.body.addEventListener('touchstart', userActiveNow);

  if (!('abConversionValue' in window.abst))
    window.abst.abConversionValue = 1;
  if (!('abConversionValueSource' in window.abst)) {
    const existingValue = abstParseCurrencyValue(window.abst.abConversionValue);
    window.abst.abConversionValueSource = (existingValue !== null && existingValue > 1) ? 'preset' : 'default';
  }

  window.abst.timerInterval = setInterval(function () {
    //check if scroll's changed
    if ((window.abst.currscroll != window.scrollY) || (window.abst.currentMousePos != window.abst.oldMousePos)) {
      window.abst.currscroll = window.scrollY;
      window.abst.oldMousePos = window.abst.currentMousePos;
      userActiveNow();
    }
    // check for active class and decrement all active timers
    if (window.abst.abactive) //for each active timer, increment
      abstOneSecond();
  }, 1000); // every second


  //conversion things
  if (typeof conversion_details !== 'undefined' && conversion_details) {
    var eid = null;
    var variation = null;

    // current p info - use complete URL for user clarity
    var page_url = window.location.href;
    //loop through each conversion d
    Object.entries(conversion_details).forEach(function ([key, detail]) {

      //simple contains matching for url's with *   *about*
      var urlMatched = false;
      if (detail.conversion_page_url && page_url) {
        // Remove * wildcards and check if the remaining text is contained in the URL

        //try regex matching
        var regexPattern = detail.conversion_page_url.replace(/\*/g, '.*');
        var regex = new RegExp(regexPattern);
        if (regex.test(page_url)) {
          console.log('ABST: ' + key + ' URL matched, regex, converting: ' + detail.conversion_page_url + ' to ' + page_url);
          urlMatched = true;
        }

        var searchPattern = detail.conversion_page_url.replace(/\*/g, '');
        if (!urlMatched && page_url.includes(searchPattern)) {
          console.log('ABST: ' + key + ' URL matched, contains, converting: ' + detail.conversion_page_url + ' to ' + page_url);
          urlMatched = true;
        }
      }

      if (urlMatched) {
        eid = key;
      }
      else if ((detail.conversion_page_url === page_url) && (detail.conversion_page_url !== undefined) && (detail.conversion_page_url != '')) {
        eid = key;
      }
      else if (typeof current_page !== 'undefined' && Array.isArray(current_page) && (current_page.includes(detail.conversion_page_id) || current_page.includes(parseInt(detail.conversion_page_id)))) {
        eid = key;
      }
      else {
        return true; // skip to the next one
      }

      variation = abstGetCookie('btab_' + eid);

      if (!variation)
        return true; // skip to the next one

      try {
        variation = JSON.parse(variation);
      } catch (e) {
        return true; // skip - corrupted cookie
      }

      //skip if its already converted
      if (variation.conversion == 1)
        return true; // skip to the next one
      conversionValue = 1;
      if (detail.use_order_value === true || detail.use_order_value === '1') {
        const resolvedOrderValue = abstResolveOrderValue(detail);
        if (resolvedOrderValue && resolvedOrderValue.formatted) {
          conversionValue = resolvedOrderValue.formatted;
        }
      }

      bt_experiment_w(eid, variation.variation, 'conversion', false, conversionValue);
    });
  }
  
  //foreach experiment
  if (typeof bt_experiments !== 'undefined') {
    
    // check for css classes, then add attributes
    document.querySelectorAll("[class^='ab-'],[class*=' ab-']").forEach(function (el, e) {
      if (el.className.includes('ab-var-') || el.className.includes('ab-convert')) {
        var allClasses = el.className;
        allClasses = allClasses.split(" "); // into an array
        var thisTestVar = false;
        var thisTestGoal = false;
        var thisTestId = false;
        var thisTestConversion = false;
        allClasses.forEach(function (element) {

          if (element.startsWith('ab-var-'))
            thisTestVar = element;
          else if (element.startsWith('ab-goal-'))
            thisTestGoal = element;
          else if (element.startsWith('ab-') && !element.includes('ab-convert'))
            thisTestId = element;
          if (element == 'ab-convert')
            thisTestConversion = true;

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

        if (thisTestConversion == true && thisTestId !== false) {
          // it's a conversion, convert!
          abstConvert(thisTestId.replace("ab-", ""));
        }

        if (thisTestGoal == true && thisTestId !== false) {
          //its a goal, record it!
          abstGoal(thisTestId.replace("ab-", ""), thisTestGoal.replace("ab-goal-", ""));
        }
      }
      if (el.className.includes('ab-click-convert-')) {
        Array.from(el.classList).forEach(function (element) { // loop through all classes
          if (element.trim().startsWith('ab-click-convert-'))
            abClickListener(element.replace("ab-click-convert-", ""), "." + element);
        });
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

      showSkippedVisitorDefault(abtid, true, abtv);
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



    //TEST WINNER FUNCTIONS
    Object.entries(bt_experiments).forEach((([experimentId, experiment]) => {
      try {
      // if there is a winner, AND the current page is the default page and AUTOCOMPLETE then just go straight there do not pass go
      if (experiment.test_winner) {
        if (experiment.test_type == 'full_page' && (experiment.full_page_default_page == btab_vars.post_id)) // if full p and the p is this page
        {
          if (experiment.test_winner !== btab_vars.post_id) // if its not the current page
          {
            if (experiment.page_variations[experiment.test_winner] == undefined) // if its not defined
            {
              //console.log('split test winner is not found, not redirecting');
              return false; // skip
            }

            //console.log('Split Test winner is page redirect. Redirecting to '+ experiment.page_variations[experiment.test_winner]);
            abstRedirect(experiment.page_variations[experiment.test_winner]);

            return false;
          }
          else // it is the current page so show it
          {
            //('Test winner is current page. Showing ' + experiment.test_winner);
            abstShowPage();
            return false; // skip 2 next experiment
          }

        }
        else if (experiment.test_type == 'magic') {
          console.log('Magic winner is ' + experiment.test_winner + ' showing magic test');
          showMagicTest(experimentId, experiment.test_winner);
        }
        else if (experiment.test_type == 'css_test') {
          console.log('Split Test CSS winner. Showing ' + experiment.test_winner);
          document.body.classList.add(experiment.test_winner);
        } // text test winner todo
        else // on page test
        {
          //show variation
          //console.log('Split Test winner is on this page. Showing '+ experiment.test_winner);
          document.querySelectorAll('[bt-eid="' + experimentId + '"][bt-variation="' + experiment.test_winner + '"]').forEach(function (el) { el.classList.add('bt-show-variation'); });
        }
        return true;
        //skip this experiment
      } // end of if there is a winner

      //

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


      //add conversion click handlers
      var conversionType = experiment.conversion_type || experiment.conversion_page || '';
      var conversionPageId = experiment.conversion_page_id;
      if ((conversionPageId === undefined || conversionPageId === null || conversionPageId === '') && experiment.conversion_page !== undefined && experiment.conversion_page !== null && experiment.conversion_page !== '' && !isNaN(experiment.conversion_page)) {
        conversionPageId = parseInt(experiment.conversion_page, 10);
      }

      if (conversionType == 'selector') {
        if (experiment.conversion_selector != '') {
          var conversionSelector = experiment.conversion_selector;
          abClickListener(experimentId, conversionSelector, 0);
        }
      }

      //add conversion link handlers (fuzzy href match)
      if (conversionType == 'link') {
        if (experiment.conversion_link_pattern != '') {
          var conversionLinkPattern = experiment.conversion_link_pattern;
          abLinkPatternListener(experimentId, conversionLinkPattern);
        }
      }

      //add conversion scroll handlers
      if (conversionType == 'scroll') {
        if (experiment.conversion_scroll !== undefined && experiment.conversion_scroll !== '') {
          // Only set up scroll listener if contextually appropriate
          if (shouldSetupScrollListener(experimentId, experiment)) {
            abScrollListener(experimentId, experiment.conversion_scroll);
          }
        }
      }

      // Form submission conversion - inject hidden fields
      if (typeof conversionType === 'string' && conversionType.indexOf('form-') === 0) {
        abstInjectFormFields();
      }


      // if experiment goals exists
      //loop through
      if (experiment['goals'])
      {
        for (var i = 1; i < Object.keys(experiment['goals']).length; i++) {
          // if experiment.goals[i] key is click
          //get key name for goal
          const firstKey = Object.keys(experiment['goals'][i])[0];

          // click selector
          if (firstKey === 'selector') {
            abClickListener(experimentId, experiment['goals'][i]['selector'], i);
          }

          if(firstKey === 'link') {
            abLinkPatternListener(experimentId, experiment['goals'][i]['link'], i);
          }

          if (firstKey === 'scroll') {
            // Only set up scroll listener if contextually appropriate
            if (shouldSetupScrollListener(experimentId, experiment)) {
              abScrollListener(experimentId, experiment['goals'][i]['scroll'], i);
            }
          }
          
          // text subgoall
          startInverval = false;
          if (firstKey === 'text') {
            startInverval = true;

            convstatus = abstGetCookie('btab_' + experimentId);
            if (startInverval && convstatus) // if test exists and not false
            {
              try {
                convstatus = JSON.parse(convstatus);
                if (convstatus && convstatus['goals'] && convstatus['goals'][i] == 1)
                  startInverval = false;
              } catch (e) {
                // Skip - corrupted cookie
              }
            }

            if (experiment['goals'][i]['text'] == '')
              startInverval = false;

            //if text exists and not complete
            if (startInverval) {
              startTextWatcher(experimentId, experiment['goals'][i]['text'], i);
            }
          }

          if (firstKey === 'page') {
            var goalPage = experiment['goals'][i]['page'];

            if (goalPage == btab_vars.post_id) {
              abstGoal(experimentId, i);
            }
          }

          if (firstKey === 'url') {
            page_url = normalizeUrl(window.location.href);

            goal_url = normalizeUrl(experiment['goals'][i]['url']);

            if (page_url == goal_url) {
              abstGoal(experimentId, i);
            }
          }

          // Form submission goal - inject hidden fields
          var goalValue = experiment['goals'][i][firstKey];
          if (typeof goalValue === 'string' && goalValue.indexOf('form-') === 0) {
            abstInjectFormFields();
          }

        }
      }


      //text conversion
      if (conversionType == 'text') {
        startInverval = true;
        if (!bt_experiments[experimentId])
          startInverval = false; // not if not defined

        convstatus = abstGetCookie('btab_' + experimentId);
        if (startInverval && convstatus) // if test exists and not false
        {
          try {
            convstatus = JSON.parse(convstatus);
            if (convstatus.conversion == 1) // if its converted
              startInverval = false;
          } catch (e) {
            // Skip - corrupted cookie
          }
        }

        if (experiment.conversion_text == '')
          startInverval = false;

        //if text exists and not complete
        if (startInverval) {
          startTextWatcher(experimentId, experiment.conversion_text);
        }
      }

      //text subgoals




      if (conversionType == 'surecart-order-paid' && window.scData) // if surecart is slected and surecart js is detected
      {
        document.addEventListener('scOrderPaid', (function (e) { // add listener
          console.log('surecart OrderPaid');
          const checkout = e.detail;

          if (checkout && checkout.amount_due) {
            if (experiment.use_order_value == true)
              window.abst.abConversionValue = (checkout.amount_due / 100).toFixed(2); // set value
            abstConvert(experimentId, window.abst.abConversionValue);
          }
        }));
      }


      if (conversionType == 'fingerprint' && !localStorage.getItem("ab-uuid")) {
        console.log("ab-uuid: set fingerprint");
        setAbFingerprint();
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
        current_exp[experimentId] = []; // create
        exp_redirect[experimentId] = [];
        
        // Register all variations: magic-0 is original (variations[0]), magic-1..N are test variations
        for (var i = 0; i < magic_definition[0].variations.length; i++) {
          current_exp[experimentId].push('magic-' + experimentId + '-' + i);
          exp_redirect[experimentId]['magic-' + experimentId + '-' + i] = '';
        }
      }
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

      // if there is a winner for the test, then do no more - its already done above
      if (bt_experiments[experimentId].test_winner)
        return true; // continue to next exp


      if (bt_experiments[experimentId]['is_current_user_track'] == false || window.abst.isTrackingAllowed === false ) { // if we arent tracking the user show default
        showSkippedVisitorDefault(experimentId);
        return true; // continue to next exp
      }
      else // we are tracking the user so check if previously skipped and remove cookie
      {
        var btab = abstGetCookie('btab_' + experimentId);
        try {
          if (btab && JSON.parse(btab).skipped) {
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
          if (bt_experiments[experimentId]['conversion_style'] == 'thompson' && btab_vars.is_agency) {
            //MAB
            console.log('ABST MAB: starting selection for experiment', experimentId);
            var meta = bt_experiments[experimentId]['variation_meta'] || {};
            var weights = [];
            variations.forEach(function(v){
              var w = meta[v] && meta[v].weight ? parseFloat(meta[v].weight) : 1;
              weights.push({variation:v, weight:w});
            });
            console.log('ABST MAB: raw weights', weights);
            var total = weights.reduce(function(sum,w){ return sum + w.weight; }, 0);
            weights.forEach(function(w){ w.weight = w.weight / total; });
            console.log('ABST MAB: normalized weights', weights);
            var minWeight = (variations.length==2)?0.1:0.05;
            var deficit = 0;
            weights.forEach(function(w){ if(w.weight < minWeight){ deficit += (minWeight - w.weight); w.weight = minWeight; }});
            if(deficit>0){
              var reducibles = weights.filter(function(w){ return w.weight > minWeight; });
              var reducibleTotal = reducibles.reduce(function(s,w){ return s + w.weight; },0);
              if(reducibleTotal > 0) {
                reducibles.forEach(function(w){
                  w.weight -= (w.weight/reducibleTotal)*deficit; 
                });
              }
            }
            console.log('ABST MAB: adjusted weights', weights);
            var r = Math.random(), c = 0;
            var randVar = 0; // capture the selected index
            for(var i=0;i<weights.length;i++){ c += weights[i].weight; if(r <= c){ experimentVariation = weights[i].variation; randVar = i; console.log('ABST MAB: chose variation', experimentVariation); break; } }
            if(!experimentVariation){ experimentVariation = weights[weights.length-1].variation; randVar = weights.length - 1; }
          } else {
            //Standard
            var randVar = getRandomInt(0, variations.length - 1);
            experimentVariation = variations[randVar];
          }
        }
        if (btab_vars.is_free == '1' && current_exp[experimentId].length > 2) {
          var randVar = getRandomInt(0, 1);  //limit to 2
          console.info('Free version of AB Split Test is limited to 1 variation. Your others will not be shown. Upgrade: https://absplittest.com/pricing?ref=ug');
          experimentVariation = current_exp[experimentId][randVar];
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

        if (targetPercentage == '')
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

  abst_find_analytics();
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

  processAbstConvert();
  processAbstGoal();
  check_heatmap_tracking();
  
  // Initialize mutation observer for dynamically created test elements
  initAbstDynamicElementObserver();
}

// Run on DOMContentLoaded
document.addEventListener('DOMContentLoaded', abstMainInit);

// Also run if config loads late (deferred by cache plugins like LiteSpeed)
document.addEventListener('abst-config-ready', abstMainInit);

function processAbstConvert() {
  if (window.abConvert && window.abConvert.length > 0) {
    var pending = window.abConvert.slice();
    window.abConvert.length = 0;
    pending.forEach(function (convert) {
      abstConvert(convert);
    });
  }
}

function processAbstGoal() {
  if (window.abGoal && window.abGoal.length > 0) {
    var pending = window.abGoal.slice();
    window.abGoal.length = 0;
    pending.forEach(function (goal) {
      console.log('processAbstGoal', goal);
      abstGoal(goal[0], goal[1]);
    });
  }
}

function startTextWatcher(experimentId, word, goalId = null) {
  if (!goalId)
    goalId = 0;

  if(!bt_experiments[experimentId]) return; 

  if (bt_experiments[experimentId].test_status == 'draft') return;

  if (typeof window.abst.intervals[experimentId] === 'undefined') {
    window.abst.intervals[experimentId] = {};
  }

  // Clear any existing interval for this goal to prevent duplicates
  if (window.abst.intervals[experimentId] && window.abst.intervals[experimentId][goalId]) {
    clearInterval(window.abst.intervals[experimentId][goalId]);

  }

  // Split pipe-separated text strings into array for multiple text checks
  var textArray = [];
  if (word && typeof word === 'string') {
    if (word.indexOf('|') >= 0) {
      textArray = word.split('|').map(function(text) {
        return text.trim();
      }).filter(function(text) {
        return text.length > 0;
      });
    } else {
      textArray = [word.trim()];
    }
  }

  // If no valid text to search for, return early
  if (textArray.length === 0) {
    return;
  }

  window.abst.intervals[experimentId][goalId] = setInterval(function() {
    try {
      let found = false;
      var foundText = '';

      // Loop through each text string to check
      for (var textIndex = 0; textIndex < textArray.length && !found; textIndex++) {
        var currentWord = textArray[textIndex];
        if (!currentWord) continue;

        var escapedWord = currentWord.replace(/'/g, "\\'"); // Escape single quotes

        // Search within the main page more efficiently
        var allElements = document.body.getElementsByTagName('*');
        for (var i = 0; i < allElements.length; i++) {
          var node = allElements[i];
          if (node.textContent && node.textContent.indexOf(currentWord) >= 0 && node.offsetParent !== null) {
            found = true;
            foundText = currentWord;
            //console.log('ABST: ' + experimentId + ' found text: ' + currentWord);
            break;
          }
        }

        // If not found in main page, check iframes
        if (!found) {
          var iframes = document.querySelectorAll('iframe');
          for (var j = 0; j < iframes.length; j++) {
            try {
              var iframe = iframes[j];
              var iframeBody = iframe.contentDocument ? iframe.contentDocument.body : null;
              if (iframeBody) {
                if (typeof jQuery !== 'undefined' && window.jQuery) {
                  // Use jQuery if available
                  if (jQuery(iframeBody).find('*').filter(function() { 
                    return this.textContent && this.textContent.indexOf(currentWord) >= 0 && this.offsetParent !== null; 
                  }).length > 0) {
                    found = true;
                    foundText = currentWord;
                    //console.log('ABST: ' + experimentId + ' found text in iframe: ' + currentWord);
                    break;
                  }
                } else {
                  // Fallback to plain JavaScript
                  var iframeElements = iframeBody.getElementsByTagName('*');
                  for (var k = 0; k < iframeElements.length; k++) {
                    var el = iframeElements[k];
                    if (el.textContent && el.textContent.indexOf(currentWord) >= 0 && el.offsetParent !== null) {
                      found = true;
                      foundText = currentWord;
                      //console.log('ABST: ' + experimentId + ' found text in iframe: ' + currentWord);
                      break;
                    }
                  }
                  if (found) break;
                }
              }
            } catch (error) {
              // Silently handle errors in iframe access
              continue;
            }
          }
        }
      }

      if (found) {
        if (goalId == 0) {
          abstConvert(experimentId);
        } else {
          abstGoal(experimentId, goalId);
        }
        clearInterval(window.abst.intervals[experimentId][goalId]);
      }
    } catch (e) {
      console.error('Error in text watcher:', e);
      clearInterval(window.abst.intervals[experimentId][goalId]);
    }
  }, 1000);
}


function abstConvert(testId = '', orderValue = 1) {
    if (!window.bt_experiments[testId]) { // if no experiment, return
        // console.log('no test with that ID found, ending conversion early.');
        return true;
    }

    if (
      orderValue == 1 &&
      window.bt_experiments[testId] &&
      (window.bt_experiments[testId].use_order_value === true || window.bt_experiments[testId].use_order_value === '1')
    ) {
      const resolvedOrderValue = abstResolveOrderValue(window.bt_experiments[testId]);
      if (resolvedOrderValue && resolvedOrderValue.formatted) {
        orderValue = resolvedOrderValue.formatted;
      }
    }

    console.log('abstConvert', testId, orderValue);

    if (testId !== '') {
    var btab = abstGetCookie('btab_' + testId);
    try {
      btab = JSON.parse(btab);
    } catch(e) {
      btab = null;
    }

    if (btab) {
      if (btab.conversion == 0) {
        if(bt_experiments[testId].is_current_user_track == false || window.abst.isTrackingAllowed === false)
        {
          //skip this experiment
          return false;
        }
        bt_experiment_w(testId, btab.variation, 'conversion', false, orderValue);
        btab.conversion = 1;
        var experiment_vars = JSON.stringify(btab);
        abstSetCookie('btab_' + testId, experiment_vars, 1000);
        
        // Clear all intervals for this test (conversion timer and all goal timers)
        if (window.abst.intervals && window.abst.intervals[testId]) {
          // Clear conversion interval if exists
          if (typeof window.abst.intervals[testId] === 'number') {
            clearInterval(window.abst.intervals[testId]);
          } else if (typeof window.abst.intervals[testId] === 'object') {
            // Clear all goal intervals
            Object.values(window.abst.intervals[testId]).forEach(function(intervalId) {
              if (intervalId) clearInterval(intervalId);
            });
          } 
          delete window.abst.intervals[testId];
        }
        
        // Also clear any goal-based timer entries in window.abst.timer
        if (window.abst.timer) {
          Object.keys(window.abst.timer).forEach(function(key) {
            if (key.startsWith('goal-' + testId + '-')) {
              delete window.abst.timer[key];
            }
          });
        }
      }
      else {
        console.log("ABST: " + bt_experiments[testId].name + ': Visitor has already converted');
      }
    }
    else {
      if (!bt_experiments[testId])
        console.log("ABST: " + 'Test ID not found or test not active');
    }
  }
}

function abstGoal(testId = '', goal = '') {
  if (testId !== '' && goal !== '') {
    var btab = abstGetCookie('btab_' + testId);
    if (btab) {
      try {
        btab = JSON.parse(btab);
      } catch (e) {
        return false; // skip - corrupted cookie
      }
      if (btab.conversion !== 1) // no goals after conversion
      {
        // Safety check: ensure experiment exists before accessing properties
        if (!bt_experiments || !bt_experiments[testId]) {
          return false; // Test no longer active or not loaded on this page
        }
        if(bt_experiments[testId].is_current_user_track == false || window.abst.isTrackingAllowed === false)
        {
          //console.log('ABST: skipping experiment ' + experimentId + ' no tracking');
          //skip this experiment
          return false;
        }
        if (Array.isArray(btab.goals)) {
          //if its not in the goal
          if (!btab.goals[goal]) {
            btab.goals[goal] = 1;
            bt_experiment_w(testId, btab.variation, goal, false, 1);
            
            var experiment_vars = JSON.stringify(btab);
            abstSetCookie('btab_' + testId, experiment_vars, 1000);
            
            // Clear interval for this specific goal
            if (window.abst.intervals && window.abst.intervals[testId]) {
              if (typeof window.abst.intervals[testId] === 'object' && window.abst.intervals[testId][goal]) {
                clearInterval(window.abst.intervals[testId][goal]);
                delete window.abst.intervals[testId][goal];
              }
            }
            
            // Clear timer entry for this goal
            if (window.abst.timer) {
              var timerKey = 'goal-' + testId + '-' + goal;
              if (window.abst.timer[timerKey] !== undefined) {
                delete window.abst.timer[timerKey];
              }
            }
          }
        }
        else {
          console.log("ABST: " + (bt_experiments[testId]?.name || testId) + ': Visitor has already goaled');
        }
      }
      else {
        console.log('ABST: Goals are not logged after primary conversion');
      }
    }
    else {
      if (!bt_experiments[testId])
        console.log("ABST: " + 'Test ID not found or test not active');
    }
  }
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
      skippedCookie(eid, variation);
    }
    return true;
  }

  if (bt_experiments[eid].test_winner !== '') { // if we have a winner

    if (bt_experiments[eid].test_type == "full_page") // full page winner
    {
      url = bt_experiments[eid].page_variations[bt_experiments[eid].test_winner];
      if (url !== undefined) {
        abstRedirect(url); // follow the link w search params

        return true;
      }
      else {
        console.log("ABST: " + 'Full page test complete without matching page winner. Showing current page.');
      }
      abstShowPage();
    }

    if (bt_experiments[eid].test_type == "css_test") // css winner
    {
      console.log('ABST: css test winner, showing ver ' + bt_experiments[eid]['test_winner']);
      document.body.classList.add('test-css-' + eid + '-' + bt_experiments[eid]['test_winner']);
      return true; // next
    }

    if (bt_experiments[eid].test_type == "magic") // magic winner
    {
      console.log('ABST: magic test winner, showing ver ' + bt_experiments[eid]['test_winner']);
      showMagicTest(eid, bt_experiments[eid]['test_winner']);
      return true; // next
    }
  }

  if (bt_experiments[eid].test_type == "full_page") {
    abstShowPage();
    if (createCookie)
      skippedCookie(eid, bt_experiments[eid].full_page_default_page);

    return true; // next
  }

  if (bt_experiments[eid].test_type == "css_test") // css version 1
  {
    document.body.classList.add('test-css-' + eid + '-1');
    if (createCookie)
      skippedCookie(eid, 'test-css-' + eid + '-1');
    return true; // next
  }

  //on page tests only from here

  if (!eid)
    return;
  var foundSpecial = false;
  document.querySelectorAll('[bt-eid="' + eid + '"]').forEach((function (element, index) {
    var variationName = element.getAttribute('bt-variation').toLowerCase();
    var defaultNames = ["original", "one", "1", "default", "standard", "a", "control"];
    if (defaultNames.includes(variationName)) {
      btv = variationName;
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
    skippedCookie(eid, btv);

} 
function skippedCookie(eid, btv) {
  var experiment_vars = {
    eid: eid,
    variation: btv,
    conversion: 1,
    skipped: 1
  };
  experiment_vars = JSON.stringify(experiment_vars);
  abstSetCookie('btab_' + eid, experiment_vars, 1000);
  return true;
}

//takes input slug or url and ends url suitable for window/replace
function abRedirectUrl(url) {
  // Only add query params and hash if they don't already exist in the URL
  var hasQuery = url.includes('?');
  var hasHash = url.includes('#');
  
  if (!hasQuery && window.location.search) {
    url += window.location.search;
  }
  
  if (!hasHash && window.location.hash) {
    url += window.location.hash;
  }
  
  // if it starts with http/s do nothing
  if (url.startsWith('http') || url.startsWith('/'))
    return url;
  else
    return '/' + url;

}

function abstOneSecond() {
  if (Object.keys(window.abst.timer).length > 0) {
    Object.entries(window.abst.timer).forEach(([index, item]) => {
      if (window.abst.timer[index] > -1) // dont decrease below -1
        window.abst.timer[index] = window.abst.timer[index] - 1;

      if (window.abst.timer[index] == 0) {  // convert if its counted down, only fired once
        //console.log('time active converting ' + index);
        //index could be goal-eid-goalid
        if (index.includes('goal-')) {
          var parts = index.split('-');
          abstGoal(parts[1], parts[2]);
        }
        else {
          abstConvert(index);
        }
        
        // Set to -1 after triggering to prevent repeated firing
        window.abst.timer[index] = -1;
      }
    });

    // update localstorage
    localStorage.setItem('absttimer', JSON.stringify(window.abst.timer)); // localstorage need strings
  }
}


function userActiveNow() {
  window.abst.currscroll = window.scrollY; // update last known scroll n mouse
  window.abst.abactive = true; // we active
  clearTimeout(window.abst.timeoutTimer); // delete the old timout
  window.abst.timeoutTimer = setTimeout(abstActiveTimeout, window.abst.timeoutTime); // create new timeout
}

function abstActiveTimeout() {
  window.abst.abactive = false;
}


function getRandomInt(min, max) {
  min = Math.ceil(min);
  max = Math.floor(max);
  return Math.floor(Math.random() * (max - min + 1)) + min;
}

function normalRandom() {
  var u = 0, v = 0;
  while(u === 0) u = Math.random();
  while(v === 0) v = Math.random();
  return Math.sqrt(-2.0 * Math.log(u)) * Math.cos(2.0 * Math.PI * v);
}
function gammaRandom(shape) {
  if (shape < 1) {
    var u = Math.random();
    return gammaRandom(1 + shape) * Math.pow(u, 1 / shape);
  }
  var d = shape - 1/3;
  var c = 1/Math.sqrt(9*d);
  while (true) {
    var x = normalRandom();
    var v = Math.pow(1 + c * x, 3);
    if (v <= 0) continue;
    var u = Math.random();
    if (u < 1 - 0.331 * Math.pow(x,4) || Math.log(u) < 0.5*x*x + d*(1 - v + Math.log(v))) {
      return d * v;
    }
  }
}
function betaRandom(alpha, beta) {
  var x = gammaRandom(alpha);
  var y = gammaRandom(beta);
  return x / (x + y);
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
  if (btIsLocalhost())
    return btDeleteLocal(c_name);

  var hostname = window.location.hostname;
  var domain = hostname.replace(/^www\./, '').split('.').slice(-2).join('.');
  domain = '.' + domain;

  document.cookie = c_name + "=; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=/; SameSite=None; Secure; domain=" + domain;
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
  

  if (btab_vars.is_free == '1') {
    //only return first 2 variations
    variation = variation.slice(0, 2);
  }

  return variation;
}

function bt_experiment_w(eid, variation, type, url, orderValue = 1) {

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
  
  // if its a fingerprinter and we dont have a uuid, then wait for it
  var experimentConversionType = bt_experiments[eid].conversion_type || bt_experiments[eid].conversion_page || '';
  if (experimentConversionType == 'fingerprint' && !localStorage.getItem('ab-uuid')) {
    console.log('ABST: bt_exp_w: waiting for fingerprint');
    setTimeout(bt_experiment_w, 500, eid, variation, type, url, orderValue);
    return true; //back in 500ms
  }
  console.log('ABST: bt_experiment_w',eid,variation,type,url,orderValue);
  if (bt_experiments[eid] && bt_experiments[eid].conversion_style == 'thompson') {
    console.log('ABST: MAB: logging', type, 'for', variation);
  }

  var data = {
    'action': 'bt_experiment_w',
    'eid': eid,
    'variation': variation,
    'type': type,
    'size': abst.size,
    'location': btab_vars.post_id,
    'orderValue': orderValue
  };

  var experiment_vars = {
    eid: eid,
    variation: variation,
    conversion: 0,
    goals: [],
    size: abst.size,
    location: btab_vars.post_id,
  };


  // set up conversion
  if (type == 'conversion')
    experiment_vars.conversion = 1;
  else if (type == 'visit') { 
    // not conversion or goal
  }
  else // goal
  {
    experiment_vars.goals[type] = 1; // add to goals list
  } 
 
  //add uuid if necessary
  if (experimentConversionType == 'fingerprint')
    data.uuid = localStorage.getItem('ab-uuid');


  //add advanced id if necessary
  if (btab_vars.advanced_tracking == '1')
    data.ab_advanced_id = abstGetAdvancedId();

  experiment_vars = JSON.stringify(experiment_vars);
  
  // Queue the event (global link click handler will flush before navigation)
  queueEventData(data, url);
  
  abstSetCookie('btab_' + eid, experiment_vars, 1000);
  

  //start time watchers b4 redirecting
  if (experimentConversionType == 'time') {
    window.abst.timer[eid] = bt_experiments[eid]['conversion_time'];
  }
  // Process goals for timer setup
  var goals = bt_experiments[eid].goals;
  
  // Handle different potential formats of goals data
  if (type == 'visit' && goals) {
    // If goals is a string, try to parse it as JSON
    if (typeof goals === 'string') {
      try {
        goals = JSON.parse(goals);
        console.log('ABST: Parsed goals from string:', goals);
      } catch (e) {
        console.error('ABST: Error parsing goals string:', e);
        goals = [];
      }
    }
    
    // Handle different goal formats (could be array or object)
    // If it's an object, iterate with Object.entries
    Object.entries(goals).forEach(([idx, goalDef]) => {
      const entries = Object.entries(goalDef);
      // Handle time goals
      if (entries[0][0] === 'time') {
        window.abst.timer["goal-" + eid + "-" + idx] = entries[0][1];
        console.log('ABST: Added timer for goal ' + eid + '-' + idx + ' with time: ' + entries[0][1]);
      }
    });
  }

  if (url && url !== "ex") {
    addServerEvents({
      eid: eid,
      variation: variation,
      type: 'visit',
    });
    console.log('ABST: addServerEvents Redirecting to ' + url);
    abstRedirect(url);
  }
  else {
    btab_track_event(data);

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
  sessionStorage.setItem('abstTestDataQueue', JSON.stringify(queue));
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
      bt_ajaxurl + '?action=abstdata',
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

/**
 * Track an event
 * This function is used to track events in the analytics partners
 * @param {Object} data - The event data
 * @param {string} data.eid - The experiment ID
 * @param {string} data.variation - The variation
 * @param {string} data.type - The event type
 */
async function btab_track_event(data) {

  if (btab_vars.is_free == '1')
    return false;

  check_heatmap_tracking();

  if (btab_vars.tagging == '0') {
    //console.log('event tagging turned off');
    return false;
  }

  // Safety check for bt_experiments
  if (typeof bt_experiments === 'undefined' || !bt_experiments || !bt_experiments[data.eid]) {
    console.warn('ABST: bt_experiments not defined or experiment not found for eid:', data.eid);
    return false;
  }

  const exp = bt_experiments[data.eid];

  // Only add to clickRegister if journey tracking is enabled
  if (btab_vars.abst_enable_user_journeys === '1') {
    let typeFormatted;
    if (data.type === 'visit') {
      typeFormatted = 'tv-' + data.eid;
    } else if (data.type === 'conversion') {
      typeFormatted = 'tc-' + data.eid;
    } else {
      // It's a goal number (e.g., '1', '2', '3')
      typeFormatted = 'tg-' + data.type + '-' + data.eid;
    }

    window.abst.clickRegister[new Date().toISOString()] = {
      timestamp: new Date().toISOString(),
      type: typeFormatted,
      post_id: btab_vars.post_id,
      uuid: abstGetAdvancedId(),
      ab_advanced_id: abstGetAdvancedId(),
      url: abstGetEventUrl(),
      element_id_or_selector: '0',
      click_x: 0,
      click_y: 0,
      screen_size: window.abstheatmapScreenSize,
      meta: data.variation
    };
  }

  window.abst.eventQueue.push(data);

  trackName = exp.name || data.eid;
  //gtag always
  gtm_data = {
    'event': 'ab_split_test',
    'test_name': trackName,
    'test_variation': data.variation,
    'test_event': data.type,
    'test_id': data.eid,
  };
  var expConversionType = exp.conversion_type || exp.conversion_page || '';
  if (expConversionType == 'fingerprint')
    gtm_data.abuuid = localStorage.getItem('ab-uuid');
  else if (abstGetAdvancedId())
    gtm_data.abuuid = abstGetAdvancedId();

  window.dataLayer = window.dataLayer || [];
  window.dataLayer.push(gtm_data); // add to gtm data layer

  if (window.abst.abconvertpartner.ga4) //ga4 add
    gtag('event', 'ab_split_test', {
      'test_name': data.eid,
      'test_variation': data.variation,
      'test_event': data.type,
    });


  if (window.abst.abconvertpartner.abawp) { //analyticswp
    AnalyticsWP.event('Test: ' + exp.name, {
      test_id: data.eid,
      test_name: exp.name,
      test_variation: data.variation,
      test_visit_type: data.type
    });
  }

  if (window.abst.abconvertpartner.clarity) { //clarity
    clarity("set", exp.name + "-" + data.type, data.variation);
  }

  if (window.abst.abconvertpartner.gai) { //google analytics
    if (typeof ga === "function" && typeof ga.getAll === "function") {
      var trackers = ga.getAll();
      var tracker = trackers && trackers[0];
      if (tracker) {
        tracker.send("event", exp.name, data.type, data.variation, { nonInteraction: true }); // send non interactive event to GA
        window.abst.abconvertpartner.gai = true;
      }
    } else {
      // Optionally log a warning for debugging
      // console.warn("Google Analytics (Universal Analytics) not detected or ga.getAll unavailable.");
    }
  }

  if (window.abst.abconvertpartner.abmix) { //abmix
    mixpanel.track(exp.name, { 'type': data.type, 'variation': data.variation }, { send_immediately: true });
  }

  if (window.abst.abconvertpartner.abumav) { //umami
    usermaven("track", exp.name, {
      type: data.type,
      variation: data.variation
    });

  }

  if (window.abst.abconvertpartner.umami) { //umami
    umami.track(exp.name, {
      type: data.type,
      variation: data.variation
    });
  }

  if (window.abst.abconvertpartner.cabin) { //cabin
    cabin.event(exp.name + ' | ' + data.type + ' | ' + data.variation);
  }

  if (window.abst.abconvertpartner.plausible) { //plausible
    plausible(exp.name, {
      props: {
        type: data.type,
        variation: data.variation
      },
      callback: {
        interactive: false
      }
    });
  }

  if (window.abst.abconvertpartner.fathom) { //fathom
    fathom.trackGoal(exp.name, {
      type: data.type,
      variation: data.variation
    });
  }
  if (window.abst.abconvertpartner.posthog) {
    posthog.capture(exp.name, {
      type: data.type,
      variation: data.variation
    });
  }
}

function abst_find_analytics() {
  if (btab_vars.is_free == '1')
    return false;

  window.abeventstarted = new Date().getTime();

  window.dataLayer || (window.dataLayer = []); //gtag

  // Safety check for bt_experiments
  if (typeof bt_experiments === 'undefined' || !bt_experiments) {
    console.warn('ABST: bt_experiments not defined in abst_find_analytics');
    return false;
  }

  // Define all analytics providers we want to check for
  const analyticsProviders = [
    {
      name: 'ga4',
      check: () => typeof gtag === "function",
      process: () => {
        window.abst.eventQueue.forEach((element) => {
          gtag('event', 'ab_split_test', {
            'test_name': (bt_experiments[element.eid] && bt_experiments[element.eid].name) || element.eid,
            'test_variation': element.variation,
            'test_event': element.type,
            'ab_uuid': element.uuid,
          });
        });
      }
    },
    {
      name: 'clarity',
      check: () => typeof clarity === "function",
      process: () => {
        window.abst.eventQueue.forEach((element) => {
          clarity("set", element.eid + "-" + element.type, element.variation);
        });
      }
    },
    {
      name: 'gai',
      check: () => {
        if (typeof ga === "function" && typeof ga.getAll === "function") {
          const trackers = ga.getAll();
          const tracker = trackers && trackers[0];
          return !!tracker;
        }
        return false;
      },
      process: () => {
        if (typeof ga === "function" && typeof ga.getAll === "function") {
          const trackers = ga.getAll();
          const tracker = trackers && trackers[0];
          if (tracker) {
            window.abst.eventQueue.forEach((element) => {
              tracker.send("event", element.eid, element.type, element.variation, { nonInteraction: true });
            });
          }
        } else {
          // Optionally log a warning for debugging
          // console.warn("Google Analytics (Universal Analytics) not detected or ga.getAll unavailable.");
        }
      }
    },
    {
      name: 'fathom',
      check: () => !!window.fathom,
      process: () => {
        window.abst.eventQueue.forEach((element) => {
          window.fathom.trackEvent(element.eid + ", " + element.type + ": " + element.variation);
        });
      }
    },
    {
      name: 'posthog',
      check: () => !!window.posthog,
      process: () => {
        window.abst.eventQueue.forEach((element) => {
          posthog.capture((bt_experiments[element.eid] && bt_experiments[element.eid].name) || element.eid, {
            type: element.type,
            variation: element.variation
          });
        });
      }
    },
    {
      name: 'abmix',
      check: () => typeof mixpanel === "object",
      process: () => {
        window.abst.eventQueue.forEach((element) => {
          mixpanel.track((bt_experiments[element.eid] && bt_experiments[element.eid].name) || element.eid, { 'type': element.type, 'variation': element.variation }, { send_immediately: true });
        });
      }
    },
    {
      name: 'abumav',
      check: () => typeof usermaven === "function",
      process: () => {
        window.abst.eventQueue.forEach((element) => {
          usermaven("track", (bt_experiments[element.eid] && bt_experiments[element.eid].name) || element.eid, {
            type: element.type,
            variation: element.variation
          });
        });
      }
    },
    {
      name: 'umami',
      check: () => !!window.umami,
      process: () => {
        window.abst.eventQueue.forEach((element) => {
          umami.track((bt_experiments[element.eid] && bt_experiments[element.eid].name) || element.eid, {
            type: element.type,
            variation: element.variation
          });
        });
      }
    },
    {
      name: 'cabin',
      check: () => !!window.cabin,
      process: () => {
        window.abst.eventQueue.forEach((element) => {
          cabin.event((bt_experiments[element.eid] && bt_experiments[element.eid].name) || element.eid + ' | ' + element.type + ' | ' + element.variation);
        });
      }
    },
    {
      name: 'plausible',
      check: () => !!window.plausible,
      process: () => {
        window.abst.eventQueue.forEach((element) => {
          plausible((bt_experiments[element.eid] && bt_experiments[element.eid].name) || element.eid, {
            props: {
              type: element.type,
              variation: element.variation
            },
            callback: {
              interactive: false
            }
          });
        });
      }
    },
    {
      name: 'abawp',
      check: () => typeof AnalyticsWP === "object",
      process: () => {
        window.abst.eventQueue.forEach((element) => {
          AnalyticsWP.event('Test: ' + (bt_experiments[element.eid] && bt_experiments[element.eid].name) || element.eid, {
            test_id: element.eid,
            test_name: (bt_experiments[element.eid] && bt_experiments[element.eid].name) || element.eid,
            test_variation: element.variation,
            test_visit_type: element.type
          });
        });
      }
    },
    {
      name: 'dom',
      check: () => true,
      process: () => {
        window.abst.eventQueue.forEach((element) => {
          try {
            var target = document.body || document;
            var evt = new CustomEvent('abst_event', { detail: element, bubbles: true });
            target.dispatchEvent(evt);
          } catch (e) {
          }
        });
      }
    },
    {
      name: 'jquery',
      check: () => !!window.jQuery,
      process: () => {
        window.abst.eventQueue.forEach((element) => {
          jQuery('body').trigger('abst_event', [element]);
        });
      }
    }
  ];

  // Initialize partner object if not exists
  window.abst.abconvertpartner = window.abst.abconvertpartner || {};

  // Maximum time to wait for analytics providers (in milliseconds)
  const MAX_WAIT_TIME = 10000; // 10 seconds
  const CHECK_INTERVAL = 500; // Check every 500ms

  // Function to check a single analytics provider
  const checkProvider = (provider) => {
    // Skip if already processed
    if (window.abst.abconvertpartner[provider.name]) {
      return true;
    }

    // Check if provider is available
    if (provider.check()) {
      window.abst.abconvertpartner[provider.name] = true;
      provider.process();
      return true;
    }

    return false;
  };

  // Function to check all providers
  const checkAllProviders = () => {
    return analyticsProviders.map((provider) => checkProvider(provider));
  };

  // Start time to calculate timeout
  const startTime = new Date().getTime();

  // Create a promise that resolves when all providers are found or timeout
  const findAnalyticsPromise = new Promise((resolve) => {
    const checkAnalytics = () => {
      // Check all providers
      const results = checkAllProviders();

      // If all providers are found or we've exceeded the max wait time, resolve
      const allFound = results.every(result => result === true);
      const timeElapsed = new Date().getTime() - startTime;

      if (allFound || timeElapsed > MAX_WAIT_TIME) {
        resolve();
      } else {
        // Check again after interval
        setTimeout(checkAnalytics, CHECK_INTERVAL);
      }
    };

    // Start checking
    checkAnalytics();
  });

  // Return the promise for potential chaining
  return findAnalyticsPromise;
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

function abLinkPatternListener(experimentId, conversionLinkPattern, goalId = 0) {
  abClickListener(experimentId, "a[href*='" + conversionLinkPattern + "']", goalId);
}

function abClickListener(experimentId, conversionSelector, goalId = 0) {
  var testCookie = abstGetCookie('btab_' + experimentId);
  if (testCookie) {
    try {
      testCookie = JSON.parse(testCookie);
    } catch (e) {
      return; // skip - corrupted cookie
    }
    if (goalId == 0) {
      if (testCookie.conversion == 1) {
        //console.log('abClickListener: already converted');
        return;
      }
    }
    else // issa goal
    {
      if (testCookie.goals[goalId] == 1) {
        //console.log('abClickListener: goal already converted');
        return;
      }
    }
  }
  var eventType = 'click'; // Default event type
  // If a pipe symbol exists, split it into the conversion selector and the event type
  if (conversionSelector.indexOf('|') !== -1) {
    var conversionParts = conversionSelector.split('|');
    // Check if there are at least two conversionParts
    if (conversionParts.length >= 2) {
      conversionSelector = conversionParts[0]; // First part is the conversion selector
      eventType = conversionParts[1]; // Second part is the event type
    }
  }

  var subselector  = "ab-click-convert-" + experimentId;

  // Prevent duplicate event listeners: use a registry keyed by experimentId+eventType+selector+goalId
  window.abst = window.abst || {};
  window.abst._abstClickListenerRegistry = window.abst._abstClickListenerRegistry || {};
  var registryKey = experimentId + '|' + eventType + '|' + conversionSelector + '|' + goalId;
  if (window.abst._abstClickListenerRegistry[registryKey]) {
    return;
  }
  window.abst._abstClickListenerRegistry[registryKey] = true;

  try {
    // Listen for clicks on elements matching the class selector
    document.addEventListener(eventType, (function (event) {
      var target = event.target;
      while (target && target !== document) {
        if(conversionSelector) //check main selector
        {
          if (
  target instanceof Element &&
  (
    (typeof conversionSelector === 'string' && conversionSelector.trim() !== '' && target.matches(conversionSelector)) ||
    (typeof conversionSelector === 'string' && conversionSelector.trim() === '' ? false : false) // disables target.matches() with no selector
  )
) { //check main selector
            console.log(eventType + ' conversion on ' + conversionSelector + ' type ' + goalId);
            if (goalId > 0)
              abstGoal(experimentId, goalId);
            else 
              abstConvert(experimentId);
            break;
          }
        }
        if(subselector && typeof subselector === 'string' && subselector.trim() !== '' && target instanceof Element && target.matches(subselector)) //check subselector
        {
          console.log(eventType + ' subselector conversion on ' + conversionSelector + ' type ' + goalId);
          if (goalId > 0)
            abstGoal(experimentId, goalId);
          else 
            abstConvert(experimentId);
          break;
        }
        target = target.parentNode;
      }
    }), true);

    // Listen for clicks on any links with ab-click-convert- query parameter
    document.addEventListener('click', (function (event) {
      var target = event.target;
      while (target && target !== document) {
        if (target.tagName === 'A' && target.href) {
          try {
            var url = new URL(target.href);
            var params = new URLSearchParams(url.search);

            // Check for the ab-click-convert-ID parameter
            for (const [key, value] of params.entries()) {
              if (key.startsWith('ab-click-convert-')) {
                var linkExperimentId = key.replace('ab-click-convert-', '');
                if (linkExperimentId === experimentId) {
                  event.preventDefault(); // Prevent default link behavior
                  console.log('Query string conversion for experiment ID: ' + experimentId);
                  if (goalId > 0)
                    abstGoal(experimentId, goalId);
                  else
                    abstConvert(experimentId);

                  // Continue to the link after a short delay to allow conversion to be processed
                  setTimeout((function () {
                    window.location.href = target.href;
                  }), 300);
                  break;
                }
              }
            }
          } catch (e) {
            // Invalid URL, just continue
          }
        }
        target = target.parentNode;
      }
    }), true);
  } catch (error) {
    console.info('ABST: Invalid conversion selector:' + conversionSelector + ' ' + error + ' ' + goalId);
  }
  var iframes = document.querySelectorAll('iframe');
  iframes.forEach((function (iframe) {
    try { 
      var iframeDoc = iframe.contentWindow.document;
      iframeDoc.addEventListener(eventType, (function (event) {
        if (event.target.matches(conversionSelector)) {
          console.log(eventType + ' IFRAME conversion on ' + conversionSelector);
          abstConvert(experimentId);
        }
      }), true);
    } catch (error) {
      //console.error("Error accessing cross-origin iframe:", error); // CORS issue, the iframe is not in the same origin and does not allow cross origin access.
    }
  }));
}
async function setAbFingerprint() {
  if (!localStorage.getItem("ab-uuid")) {
    try {
      const module = await import(window.bt_pluginurl + "/js/ab-fingerprint.js");
      const fp = await ThumbmarkJS.getFingerprint();
      localStorage.setItem("ab-uuid", fp);
      console.log("ABST: set Fingerprint: " + fp);
    } catch (error) {
      console.error("ABST: Error setting fingerprint:", error);
    }
  }
  else {
    console.log("ab-uuid: already set: " + localStorage.getItem("ab-uuid"));
  }
}

/**
 * Revoke approval for tracking (for testing or when user revokes consent)
 */
function abst_revoke_approval() {
  setAbstApprovalStatus(false);
  console.log('ABST: Approval revoked, events will be queued until approval is given again');
  return true;
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
      console.warn('ABST: Failed to parse magic_definition after fixes. Please recreate this split test.', e2);
      return null;
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

/**
 * Setup conversion and goal listeners for all experiments
 */
// Initialize btab_vars if it doesn't exist
window.btab_vars = window.btab_vars || {};

window.btab_vars.setupConversionListeners = function() {
  // Loop through all experiments
  if (!bt_experiments) return;
  for (const eid in bt_experiments) {
    if (!bt_experiments.hasOwnProperty(eid)) continue;
    const exp = bt_experiments[eid];
    const expConversionType = exp.conversion_type || exp.conversion_page || '';
    let expConversionPageId = exp.conversion_page_id;
    if ((expConversionPageId === undefined || expConversionPageId === null || expConversionPageId === '') && exp.conversion_page !== undefined && exp.conversion_page !== null && exp.conversion_page !== '' && !isNaN(exp.conversion_page)) {
      expConversionPageId = parseInt(exp.conversion_page, 10);
    }
    
    // Setup goal listeners if available
    if (Array.isArray(exp.goals)) { 
      exp.goals.forEach((goalDef, idx) => {
        const [kind, value] = Object.entries(goalDef)[0];
        switch (kind) {
          case 'selector':
            abClickListener(eid, value, idx);
            break;
          case 'text':
            startTextWatcher(eid, value, idx);
            break;
          case 'url':
            // Check if this goal has already been triggered
            const existingCookie = abstGetCookie('btab_' + eid);
            if (existingCookie) {
              try {
                const cookieData = JSON.parse(existingCookie);
                if (cookieData.goals && cookieData.goals[idx] === 1) {
                  console.log('Goal already triggered:', eid, 'Goal #', idx);
                  break; // Skip further processing if goal already triggered
                }
              } catch (e) {
                // Skip - corrupted cookie
              }
            }

            // Only do URL comparison if value is a reasonable URL path
            // This prevents comparisons with empty strings or invalid values
            if (value && value.length > 1) {
              if (normalizeUrl(location.href) === normalizeUrl(value)) {
                console.log('URL MATCH! Firing goal for', eid, idx);
                abstGoal(eid, idx);
              } else {
                //console.log('URL DID NOT MATCH for goal', eid, idx);
              }
            } else {
              //console.log('Invalid URL value for goal', eid, idx, '- skipping check');
            }
            break;
          case 'page':
            if(window.btab_vars.post_id == value) {
              abstGoal(eid, idx);
            }
            break;
        }
      });
    }
    
    // Setup primary conversion listener
    switch (expConversionType) {
      case 'selector':
        abClickListener(eid, exp.conversion_selector);
        break;
        case 'text':
          startTextWatcher(eid, exp.conversion_text);
          break;
          case 'surecart-order-paid':
            document.addEventListener('scOrderPaid', function (e) {
              const amt = e.detail?.amount_due;
              if (amt) {
                if (exp.use_order_value) {
                  window.abst.abConversionValue = (amt / 100).toFixed(2);
                }
                abstConvert(eid, window.abst.abConversionValue);
              }
            });
            break;
            default:
              if (expConversionType == 'page' && window.btab_vars.post_id == expConversionPageId) {
                abstConvert(eid);
              }
              break;
            }
            // time listeners are attached to the experiment when it is triggered
    
    // Check conversion URL if set
    if (exp.conversion_url && exp.conversion_url !== '') {
      if (normalizeUrl(location.href) === normalizeUrl(exp.conversion_url)) {
        abstConvert(eid);
      }
    }
  }
};
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

function normalizeUrl(url) {
  let page_url = url.replace(window.location.origin, '');
  if (page_url.charAt(0) == "/") page_url = page_url.substr(1);
  if (page_url.charAt(page_url.length - 1) == "/") page_url = page_url.substr(0, page_url.length - 1);
  return page_url;
}


// setup conversion listeners
document.addEventListener('DOMContentLoaded', function() {
  
  window.btab_vars.setupConversionListeners();
  if( !btab_vars.is_preview && document.querySelectorAll('.conversion-module').length > 0 ) {
    document.querySelectorAll('.conversion-module').forEach(function(el) {
      el.remove();
    });
  }

  if( window.bt_conversion_vars ) {

    Object.entries(bt_conversion_vars).forEach(function([key, conversion_el]) {

      // page load conversion
      if ( conversion_el.type !== 'click' )
      {
        var eid = conversion_el.eid;
        var variationObj = abstGetCookie('btab_'+eid);

        if( !variationObj ) {
          return true;
        }

        try {
          variationObj = JSON.parse(variationObj);
        } catch (e) {
          return true; // skip - corrupted cookie
        }

       if( bt_experiments[eid] === undefined ) {
          return false;
        }

        // if its converted already
        if( variationObj.conversion == 1 ) {
            console.info('AB Split test already converted.');
          return true;
        }

        // if its not an empty conversion URL or page, then it must be defined elsewhere
        var moduleConversionType = bt_experiments[eid].conversion_type || bt_experiments[eid].conversion_page || '';
        if( bt_experiments[eid].conversion_url != '' || moduleConversionType != '' ) {
          if(bt_experiments[eid].conversion_url == 'embed')
            console.info('AB Split Test conversion defined as external URL, but conversion module used. Please check your configuration settings. This is a soft error and a conversion event has not been blocked.');
          else
            return true;
        }


        variation = variationObj.variation;

        var convertType = conversion_el.type;
        var convertClickSelector = conversion_el.selector;
       
        if (typeof conversion_details !== 'undefined' ) // we have a conversion page URL set, 
        {
          if(typeof conversion_details[eid] !== 'undefined')
          {
            console.log('Possible duplicate conversion event. Check your set up.');
          }
        }
        
        if(variation){
          bt_experiment_w(eid,variation,'conversion',false);
          variationObj.conversion = 1;
          variationObj = JSON.stringify(variationObj);
          abstSetCookie('btab_'+eid, variationObj, 1000);
        }
      }

      // click conversion
      if( conversion_el.type == 'click' )
      {
        var convertClickSelector = conversion_el.selector;
        //find link
        if( document.querySelectorAll(convertClickSelector).length > 0 ) {
          //cool
        }
        else if( document.querySelectorAll(convertClickSelector + " a").length > 0 ) {
          //add the "a"
          convertClickSelector += " a"; 
        }
        else if( document.querySelectorAll(convertClickSelector + " img").length > 0 ) {
          //add the "a"
          convertClickSelector += " img";
        }
        else
        {
          //console.log("no conversion elements found");
        }
        
        if( document.querySelectorAll(convertClickSelector).length > 0 ) {

          // Instead of using jQuery's event delegation, we'll create a proper event delegation handler
          document.body.addEventListener('click', function(event) {
            // Check if the click target matches or is a child of the selector
            let clickTarget = event.target;
            let convertElement = null;
            
            // Try to match the element or find a matching ancestor
            try {
              if (clickTarget.matches(convertClickSelector)) {
                convertElement = clickTarget;
              } else {
                convertElement = clickTarget.closest(convertClickSelector);
              }
            } catch(e) {
              // Invalid selector, ignore
              return;
            }
            
            // Not our target element
            if (!convertElement) return;
            
            var url = convertElement.getAttribute('href');
            var target = convertElement.getAttribute('target');
            var eid = conversion_el.eid;

            var variationObj = abstGetCookie('btab_'+eid);
            try {
              variationObj = JSON.parse(variationObj);
            } catch (e) {
              return; // skip - corrupted cookie
            }

            //console.log('variationObj', variationObj);

            if( bt_experiments[eid] === undefined ) {
              return false;
            }

            if( variationObj.conversion == 1 ) {
                console.log('ab test already converted.');
              return true;
            }

            variation = variationObj.variation;

            if(url && (target !== '_blank'))
            {
              event.preventDefault();
              bt_experiment_w(eid,variation,'conversion',url);
            }
            else
            {
              bt_experiment_w(eid,variation,'conversion',false);
            }

            variationObj.conversion = 1;
            variationObj = JSON.stringify(variationObj);
            abstSetCookie('btab_'+eid, variationObj, 1000);
          });
        }

      }
    });
  }
});

function abstContainsHtml(str) {
  if (!str || typeof str !== 'string') {
    return false;
  }
  // This regex looks for a pattern that starts with '<', is followed by a letter,
  // and eventually has a '>' character. This is a much more reliable
  // indicator of an HTML tag than just checking for the brackets separately.
  return /<[a-z][\s\S]*>/i.test(str);
}

function shouldSetupScrollListener(experimentId, experiment) {
  // For full page tests: Only set up scroll listener if we're on a variation page
  if (experiment.test_type === 'full_page') {
    // Check if current page is the default page being tested (consistent with existing pattern)
    if (typeof current_page !== 'undefined' && Array.isArray(current_page) && current_page.some(page => String(page) === String(experiment.full_page_default_page))) {
      return true; // We're on the default page, scroll listener should be active
    }
    
    // Check if we're on one of the variation pages by checking page IDs
    if (experiment.page_variations && typeof current_page !== 'undefined' && Array.isArray(current_page)) {
      for (const [varId, variationUrl] of Object.entries(experiment.page_variations)) {
        if (current_page.some(page => String(page) === String(varId))) {
          return true; // We're on a variation page
        }
      }
    }  
    return false; // Not on a page related to this full page test
  }
  
  // For on-page tests (magic, ab_test, css_test): Only set up if test elements exist on current page
  if (experiment.test_type === 'magic' || experiment.test_type === 'ab_test' || experiment.test_type === 'css_test') {
    // Check if any test elements exist on the current page
    const testElements = document.querySelectorAll('[bt-eid="' + experimentId + '"]');
    if (testElements.length > 0) {
      return true; // Test elements found, scroll listener should be active
    }
    
    // For magic tests, check if magic definition elements exist
    if (experiment.test_type === 'magic' && experiment.magic_definition) {
      try {
        const magicDef = Array.isArray(experiment.magic_definition) ? experiment.magic_definition : JSON.parse(experiment.magic_definition);
        for (const def of magicDef) {
          if (!matchesMagicScope(def.scope)) {
            continue;
          }
          if (def.selector && document.querySelector(def.selector)) {
            return true; // Magic test selector found on page
          }
        }
      } catch (e) {
        console.warn('ABST: Error parsing magic definition for experiment', experimentId, e);
      }
    }
    
    //console.log('ABST Scroll conversion will not watch. No test elements found on current page for experiment', experimentId);
    return false; // No test elements found on current page
  }
  
  // For other test types, default to true (maintain existing behavior)
  return true;
}

function abScrollListener(experimentId, depth, goalId = 0) {
  depth = parseInt(depth);
  if (isNaN(depth)) return;
  var testCookie = abstGetCookie('btab_' + experimentId);
  if (testCookie) {
    try {
      testCookie = JSON.parse(testCookie);
    } catch (e) {
      return; // skip - corrupted cookie
    }
    if (goalId == 0) {
      if (testCookie.conversion == 1) return;
    } else {
      if (testCookie.goals && testCookie.goals[goalId] == 1) return;
    }
  }
  window.abst = window.abst || {};
  window.abst._abstScrollListenerRegistry = window.abst._abstScrollListenerRegistry || {};
  var registryKey = depth + '|' + experimentId + '|' + goalId;
  if (window.abst._abstScrollListenerRegistry[registryKey]) {
    return;
  }
  window.abst._abstScrollListenerRegistry[registryKey] = true;

  function checkScroll() {
    var scrollPos = window.scrollY + window.innerHeight;
    var docHeight = Math.max(document.documentElement.scrollHeight, document.body.scrollHeight);
    var percent = (scrollPos / docHeight) * 100;
    if (percent >= depth) {
      if (goalId > 0) abstGoal(experimentId, goalId);
      else abstConvert(experimentId);
      window.removeEventListener('scroll', checkScroll);
    }
  }
  window.addEventListener('scroll', checkScroll);
  checkScroll();
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
      magic_definition.forEach(function(swapElement) {
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
            // Skip if already swapped (prevent double-processing)
            if (el.hasAttribute('data-abst-swapped')) return;
            el.setAttribute('data-abst-swapped', eid);
            
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
          const siblings = current.parentNode.querySelectorAll(':scope > ' + selector);
          if (siblings.length > 1) {
              const index = Array.from(current.parentNode.children).indexOf(current) + 1;
              selector += ':nth-child(' + index + ')';
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
                  const index = Array.from(parent.children).indexOf(element) + 1;
                  path[path.length - 1] = firstSelector.replace(/:nth-child\(\d+\)$/, '') + ':nth-child(' + index + ')';
              }
              break;
          }
      }
  }
  
  return path.join(' > ');
}
function isIgnored(type, value) {
  if (!value) return false;
  if (type === 'class' && value === 'abst-variation') return true; // Always ignore our own marker

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
  tg-1-1234 : test goal with goal id and test id
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

    //most realiable page blur before unset
    document.addEventListener('visibilitychange', function() {
      if (document.visibilityState === 'hidden') {
        flushJourneyData(window.abst.pageUnloading);        
      }
    });
    
    // pagehide fires when navigating away - flush data immediately
    // Safe for bfcache, unlike beforeunload
    window.addEventListener('pagehide', function() {
      window.abst.pageUnloading = true;
      // Only send scroll depth once per pageview
      if (!window.abst.scrollSentThisPageview) {
        window.abst.scrollSentThisPageview = true;
        flushJourneyData(true); // Include scroll depth on page unload
      } else {
        flushJourneyData(false); // Already sent scroll, just flush remaining events
      }
    });
    
    window.addEventListener('pageshow', function(event) {
      window.abst.pageUnloading = false;
      
      // bfcache restore - user hit back/forward button
      if (event.persisted) {
        // Reset scroll tracking for fresh measurement
        window.abst.heatScrollMax = 0;
        window.abst.scrollSentThisPageview = false; // Reset scroll flag for new pageview
        
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
  
function check_heatmap_tracking() {

  if(typeof btab_vars !== 'undefined' && typeof btab_vars.abst_enable_user_journeys !== 'undefined' && btab_vars.abst_enable_user_journeys === '0') {
    enable_click_tracking = false;
  }

  // Check if current page should have heatmap tracking
  var should_track_heatmap = false;
  if(enable_click_tracking && typeof btab_vars !== 'undefined') {
    var heatmap_pages = btab_vars.heatmap_pages;
    var heatmap_all_pages = btab_vars.heatmap_all_pages || false;
    var current_post_id = window.current_page; //array of post id's or tags if archives 404 etc
    var has_active_test = false;
    
    // Check if user is in any active test (has test variation cookie with valid variation)
    if (typeof bt_experiments === 'object' && bt_experiments) {
      for (var eid in bt_experiments) {
        var btab_cookie = abstGetCookie('btab_' + eid);
        if (btab_cookie) {
          try {
            var cookie_data = JSON.parse(btab_cookie);
            // Only count as active if has a variation and not skipped
            if (cookie_data.variation && cookie_data.variation !== 'undefined' && !cookie_data.skipped) {
              has_active_test = true;
              break;
            }
          } catch (e) {
            // Skip invalid cookie data
          }
        }
      }
    }
    
    // Track heatmap if:
    // 1. User is in an active test, OR
    // 2. Current page is in the heatmap_pages list
    if (has_active_test) {
      // User is in a test - track all pages for session replay
      should_track_heatmap = true;
    } else if (btab_vars.is_admin) {
      should_track_heatmap = false;
    }else if (heatmap_all_pages && heatmap_all_pages === 'all') {
      should_track_heatmap = true;
    } else if (typeof heatmap_pages !== 'undefined' && Array.isArray(heatmap_pages) && heatmap_pages.length > 0 ) {
      console.log('heatmap_pages', heatmap_pages);
      console.log('current_post_id', current_post_id);
      // User is not in a test - only track if page is in heatmap_pages list
      var idsToCheck = Array.isArray(current_post_id) ? current_post_id : [current_post_id];
      should_track_heatmap = idsToCheck.some(function(id) {
        if (id === null || typeof id === 'undefined') {
          return false;
        }
        return heatmap_pages.includes(id) || heatmap_pages.includes(String(id));
      });
    }
  }
  // Initialize heatmap tracking if enabled
  if(enable_click_tracking && should_track_heatmap){
    if (!abstGetAdvancedId()) {
      setAbCrypto(); // Create UUID for heatmap tracking
    }
    enableClickTracking();
  }

}


function flushJourneyData(includeScrollMax) {
  // Don't send journey data if feature is disabled
  if(typeof btab_vars !== 'undefined' && btab_vars.abst_enable_user_journeys !== '1') {
    return;
  }
  
  if(btab_vars.is_admin){
    return;
  }
  
  // Only send if there are actual events (not just empty register)
  // This prevents wasteful duplicate meta lines from tab switches
  if (!Object.keys(window.abst.clickRegister).length) return;

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
    meta: includeScrollMax ? window.abst.heatScrollMax : '',
    experiments: getActiveExperiments(),
    referrer: (function() { try { return sessionStorage.getItem('abst_original_referrer') || ''; } catch(e) { return document.referrer || ''; } })()
  };
  // Copy all existing events
  for (var key in window.abst.clickRegister) {
    batchToSend[key] = window.abst.clickRegister[key];
  }

  const payload = new URLSearchParams({
    action: 'abst_receive_journey_data',
    data: JSON.stringify(batchToSend),
    // nonce: btab_vars.journeyNonce // include if you add one
  });
  navigator.sendBeacon(bt_ajaxurl, payload);
  window.abst.clickRegister = {}; //reset register
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


// Inject hidden fields into forms for server-side form conversion tracking
// Called only when a form- conversion or goal is detected
function abstInjectFormFields() {
  if (window.abst && window.abst.formFieldsInjected) return;
  
  var cookies = document.cookie.split(';');
  var abstData = {};
  
  for (var i = 0; i < cookies.length; i++) {
    var cookie = cookies[i].trim();
    if (cookie.indexOf('btab_') === 0) {
      var parts = cookie.split('=');
      var eid = parts[0].replace('btab_', '');
      try {
        var data = JSON.parse(decodeURIComponent(parts[1]));
        if (data && data.variation) {
          abstData[eid] = data.variation;
        }
      } catch(e) {}
    }
  }
  
  if (Object.keys(abstData).length === 0) return;
  
  var forms = document.querySelectorAll('form');
  forms.forEach(function(form) {
    if (form.querySelector('input[name="abst_data"]')) return;
    var input = document.createElement('input');
    input.type = 'hidden';
    input.name = 'abst_data';
    input.value = JSON.stringify(abstData);
    form.appendChild(input);
  });
  
  window.abst = window.abst || {};
  window.abst.formFieldsInjected = true;
  
  // Use MutationObserver for dynamic forms instead of polling interval
  // This is more efficient and doesn't run forever
  if (window.MutationObserver && !window.abst.formObserver) {
    window.abst.formObserver = new MutationObserver(function(mutations) {
      mutations.forEach(function(mutation) {
        mutation.addedNodes.forEach(function(node) {
          if (node.nodeType !== Node.ELEMENT_NODE) return;
          
          // Check if the added node is a form
          if (node.tagName === 'FORM' && !node.querySelector('input[name="abst_data"]')) {
            var input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'abst_data';
            input.value = JSON.stringify(abstData);
            node.appendChild(input);
          }
          
          // Check for forms inside the added node
          if (node.querySelectorAll) {
            node.querySelectorAll('form').forEach(function(form) {
              if (form.querySelector('input[name="abst_data"]')) return;
              var input = document.createElement('input');
              input.type = 'hidden';
              input.name = 'abst_data';
              input.value = JSON.stringify(abstData);
              form.appendChild(input);
            });
          }
        });
      });
    });
    
    window.abst.formObserver.observe(document.body, {
      childList: true,
      subtree: true
    });
  }
}





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
      if(cookieName.startsWith('btab_') || cookieName === 'ab-advanced-id' || cookieName === 'absttimer'){
        abstDeleteCookie(cookieName);
      }
    } 
    
    // Also clear from localStorage and sessionStorage
    const storageKeys = Object.keys(localStorage);
    for(let i = 0; i < storageKeys.length; i++){
      if(storageKeys[i].startsWith('btab_') ||  storageKeys[i] === 'ab-advanced-id' || storageKeys[i] === 'absttimer'){
        localStorage.removeItem(storageKeys[i]);
      }
    }
    
    const sessionKeys = Object.keys(sessionStorage);
    for(let i = 0; i < sessionKeys.length; i++){
      if(sessionKeys[i].startsWith('btab_') || sessionKeys[i] === 'ab-advanced-id' || sessionKeys[i] === 'absttimer'){
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
