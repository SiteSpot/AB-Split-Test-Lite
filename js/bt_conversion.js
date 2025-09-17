// is user active global vars
window.abst = window.abst || {};
// activity timer
window.abst.eventQueue = [];
window.abst.abconvertpartner = {};
// Check localStorage for existing approval status, default to waiting if not found
window.abst.waitForApproval = false;//localStorage.getItem('abstApprovalStatus') !== 'approved';


// Helper function to set approval status
function setAbstApprovalStatus(approved) {
  window.abst.waitForApproval = !approved;
  if (approved) {
    localStorage.setItem('abstApprovalStatus', 'approved');
  } else {
    localStorage.removeItem('abstApprovalStatus');
  }
} 


// activity timer - initialize once as an object
window.abst.timer = localStorage.getItem('absttimer') === null ? {} : JSON.parse(localStorage.getItem('absttimer') || '{}');

window.abst.currscroll = window.scrollY;
window.abst.currentMousePos = -1;
window.abst.oldMousePos = -2;
window.abst.abactive = true;
window.abst.timeoutTime = 3000; // how much inactivity before we stop logging in milliseconds
window.abst.intervals = {};
// Initialize service partners
const services = ['abawp', 'clarity', 'gai', 'abmix', 'abumav', 'umami', 'cabin', 'plausible', 'fathom', 'ga4', 'posthog'];
window.abst.abconvertpartner = Object.fromEntries(services.map((service) => [service, false]));

//what size, mobile, tablet or desktop 
if (window.innerWidth < 768) {
  window.abst.size = 'mobile';
}
else if (window.innerWidth < 1024) {
  window.abst.size = 'tablet';
}
else {
  window.abst.size = 'desktop';
}

if (window.btab_vars && window.btab_vars.advanced_tracking == '1') {
  setAbCrypto();
}

// add server events to cookie to be evented on next page load
function addServerEvents(data) {
  const serverEvents = abstGetCookie('abst_server_events');

  if (serverEvents) {
    const events = JSON.parse(decodeURIComponent(serverEvents));
    if (!events.some(event => event.eid === data.eid && event.variation === data.variation && event.type === data.type)) {
      events.push(data);
      abstSetCookie('abst_server_events', JSON.stringify(events), 2);
      console.log('server event added to existing', data);
    }
  } else {
    abstSetCookie('abst_server_events', JSON.stringify([data]), 2);
    console.log('server event added none existing creating', data);
  }
}

// process server events on next page load
document.addEventListener('DOMContentLoaded', function () {
  const serverEvents = abstGetCookie('abst_server_events');
  if (serverEvents) {
    console.log('server events found, going to process them', serverEvents);
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
    deleteCookie('abst_server_events');
    console.log('server events processed');
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
    page_url = window.location.href;
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

      variation = JSON.parse(variation);

      //skip if its already converted
      if (variation.conversion == 1)
        return true; // skip to the next one
      conversionValue = 1;
      if (window.abst.abConversionValue && detail.use_order_value == true)// woo etc
        conversionValue = window.abst.abConversionValue;

      bt_experiment_w(eid, variation.variation, 'conversion', false, conversionValue);
    });

  }

  //foreach experiment
  if (typeof bt_experiments !== 'undefined') {
    // check for css classes, then add attributes
    document.querySelectorAll("[class^='ab-'],[class*=' ab-']").forEach(function (el, e) {
      if (el.className.includes('ab-var-') || el.className.includes('ab-convert')) {
        allClasses = el.className;
        allClasses = allClasses.split(" "); // into an array
        thisTestVar = false;
        thisTestGoal = false;
        thisTestId = false;
        thisTestConversion = false;
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
            console.log('Test winner is current page. Showing ' + experiment.test_winner);
            document.body.classList.add('abst-show-page');
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
        console.log('Full Page Test: ' + experimentId);
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
      if (experiment.conversion_page == 'selector') {
        if (experiment.conversion_selector != '') {
          var conversionSelector = experiment.conversion_selector;
          abClickListener(experimentId, conversionSelector, 0);
        }
      }

      //add conversion link handlers (fuzzy href match)
      if (experiment.conversion_page == 'link') {
        if (experiment.conversion_link_pattern != '') {
          var conversionLinkPattern = experiment.conversion_link_pattern;
          abLinkPatternListener(experimentId, conversionLinkPattern);
        }
      }

      //add conversion scroll handlers
      if (experiment.conversion_page == 'scroll') {
        if (experiment.conversion_scroll !== undefined && experiment.conversion_scroll !== '') {
          // Only set up scroll listener if contextually appropriate
          if (shouldSetupScrollListener(experimentId, experiment)) {
            abScrollListener(experimentId, experiment.conversion_scroll);
          }
        }
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
              convstatus = JSON.parse(convstatus);
              if (convstatus && convstatus['goals'] && convstatus['goals'][i] == 1)
                startInverval = false;
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

        }
      }


      //text conversion
      if (experiment.conversion_page == 'text') {
        startInverval = true;
        if (!bt_experiments[experimentId])
          startInverval = false; // not if not defined

        convstatus = abstGetCookie('btab_' + experimentId);
        if (startInverval && convstatus) // if test exists and not false
        {
          convstatus = JSON.parse(convstatus);
          if (convstatus.conversion == 1) // if its converted
            startInverval = false;
        }

        if (experiment.conversion_text == '')
          startInverval = false;

        //if text exists and not complete
        if (startInverval) {
          startTextWatcher(experimentId, experiment.conversion_text);
        }
      }

      //text subgoals




      if (experiment.conversion_page == 'surecart-order-paid' && window.scData) // if surecart is slected and surecart js is detected
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


      if (experiment.conversion_page == 'fingerprint' && !localStorage.getItem("ab-uuid")) {
        console.log("ab-uuid: set fingerprint");
        setAbFingerprint();
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
        magic_definition = parseMagicTestDefinition(bt_experiments[experimentId]['magic_definition']);
        current_exp[experimentId] = []; // create
        exp_redirect[experimentId] = [];
        var randVar = getRandomInt(0, magic_definition[0].variations.length - 1);
        current_exp[experimentId].push('test-magic-' + experimentId + '-' + randVar);
        exp_redirect[experimentId]['test-magic-' + experimentId + '-' + randVar] = '';
      }
    });

    Object.keys(current_exp).forEach((function (experimentId) {
      //check it exists
      if (bt_experiments[experimentId] === undefined) {
        console.info("ABST: " + 'Test ID ' + experimentId + ' does not exist.');
        showSkippedVisitorDefault(experimentId);
        return true; // continue to next exp
      }



      // if there is a winner for the test, then do no more - its already done above
      if (bt_experiments[experimentId].test_winner)
        return true; // continue to next exp


      if (bt_experiments[experimentId]['is_current_user_track'] == false) { // if we arent tracking the user show default
        showSkippedVisitorDefault(experimentId);
        return true; // continue to next exp
      }
      else // we are tracking the user so check if previously skipped and remove cookie
      {
        var btab = abstGetCookie('btab_' + experimentId);
        if (btab && JSON.parse(btab).skipped) {
          deleteCookie('btab_' + experimentId);
          console.info('ABST: previously skipped experiment will begin ' + experimentId);
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
        else {
          var variations = current_exp[experimentId];

            var randVar = getRandomInt(0, variations.length - 1);
            experimentVariation = variations[randVar];
          
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
        btab = JSON.parse(btab);
        var redirect_url = exp_redirect[experimentId];
        redirect_url = redirect_url[experimentVariation];
        if (redirect_url && !btab_vars.is_preview) {
          abstRedirect(redirect_url);
          return true; // finished
        }
        else {
          abstShowPage(); // full page
          if (variation_element && variation_element.length > 0)
            variation_element.forEach(function (el) { el.classList.add('bt-show-variation'); });
  
          if (bt_experiments[experimentId]['test_type'] == 'magic' && 
            bt_experiments[experimentId]['magic_definition'] && 
            bt_experiments[experimentId]['magic_definition'].length > 0) {
            var vartn = btab.variation.replace('magic-', '');
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
        urlQueryResult = null;

        //we've got a url query
        if (url_query !== '') {
          var isNot = false;
          if (url_query.startsWith('NOT')) {
            isNot = true;
            url_query = url_query.replace('NOT ', '').replace('NOT', '');
          }
          if (url_query.includes('*')) {
            //console.log('wildcard search the entire URL ' + url_query);
            //              wildcard search the entire URL
            //remove the *
            url_query = url_query.replace(/\*/g, '');
            console.log('url_query', url_query);
            console.log('window.location.href', window.location.href);
            targetVisitor = window.location.href.includes(url_query);
            // if href includes string 
            //console.log('targetVisitor',targetVisitor);
          }
          else {
            var exploded_query = url_query.trim().split("=");
            if (exploded_query.length == 1) // just the query key
            {
              targetVisitor = bt_getQueryVariable(exploded_query[0]);
            }
            else if (exploded_query.length == 2) //query key and value
            {
              urlQueryResult = bt_getQueryVariable(exploded_query[0]);
              targetVisitor = exploded_query[1] == urlQueryResult;
            } // else if the string contains an * then we are going to wildcard search the entire URL
          }

          //if url query start with 'NOT '
          if (isNot) {
            targetVisitor = !targetVisitor;
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
        console.log('ABST variation doesnt exist, or doesnt match');
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
        showMagicTest(experimentId, parseInt(randVar));
      }

      if (variation_element && variation_element.length > 0) {
        variation_element.forEach(function (el) { 
          if (el) el.classList.add('bt-show-variation'); 
        });
      }


      /// if its magic or on page, then set up visible listeners
      if (bt_experiments[experimentId]['test_type'] == 'ab_test') {
        watch_for_tag_event(experimentId, undefined, experimentVariation);
      }
      else if (bt_experiments[experimentId]['test_type'] == 'magic') {
        //loop through the magic elements and watch for them
        magic_definition.forEach((element, index) => {
          watch_for_tag_event(experimentId, element.selector, experimentVariation); // watch 4 visible instead of insta visit
        });
      }
      else {
        bt_experiment_w(experimentId, experimentVariation, 'visit', redirect_url);
      }
    }));
  }

  // warn users on localhost
  if (btIsLocalhost())
    console.info("AB Split Test: It looks like you're on a localhost, using local storage instead of cookies. External Conversion Pixels will not work on Local web servers.");

  abst_find_analytics();
  window.dispatchEvent(new Event('resize')); // trigger a window resize event. Useful for sliders etc. that dynamically resize
  var event = new Event('ab-test-setup-complete' ); 
  //example usage
  //document.addEventListener('ab-test-setup-complete', function() {
  //  console.log('ab-test-setup-complete');
  //});
  document.body.dispatchEvent(event);
  //add class ab-test-setup-complete to body
  document.body.classList.add('ab-test-setup-complete');

  processAbstConvert();
  processAbstGoal();
});

function processAbstConvert() {
  if (window.abConvert && window.abConvert.length > 0) {
    window.abConvert.forEach(function (convert) {
      console.log('processAbstConvert', convert);
      abstConvert(convert);
      //remove from array
      window.abConvert.splice(window.abConvert.indexOf(convert), 1);
    });
  }
}

function processAbstGoal() {
  if (window.abGoal && window.abGoal.length > 0) {
    window.abGoal.forEach(function (goal) {
      console.log('processAbstGoal', goal);
      abstGoal(goal[0], goal[1]);
      //remove from array
      window.abGoal.splice(window.abGoal.indexOf(goal), 1);
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
            console.log('ABST: ' + experimentId + ' found text: ' + currentWord);
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
    console.log('abstConvert', testId, orderValue);

    if (testId !== '') {
    var btab = abstGetCookie('btab_' + testId);
    if (btab) {
      btab = JSON.parse(btab);
      if (btab.conversion == 0) {
        if(bt_experiments[testId].is_current_user_track == false)
        {
          //skip this experiment
          return false;
        }
        bt_experiment_w(testId, btab.variation, 'conversion', false, orderValue);
        btab.conversion = 1;
        experiment_vars = JSON.stringify(btab);
        abstSetCookie('btab_' + testId, experiment_vars, 1000);
        if (abst.intervals[testId]) {
          clearInterval(abst.intervals[testId]);
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
    console.log('start abstGoal',testId,goal);
    var btab = abstGetCookie('btab_' + testId);
    if (btab) {
      btab = JSON.parse(btab);
      if (btab.conversion !== 1) // no goals after conversion
      {
        if(bt_experiments[testId].is_current_user_track == false)
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
            
            experiment_vars = JSON.stringify(btab);
            abstSetCookie('btab_' + testId, experiment_vars, 1000);
            if (abst.intervals[testId + "" + goal]) {
              clearInterval(abst.intervals[testId + "" + goal]);
            }
          }
        }
        else {
          console.log("ABST: " + bt_experiments[testId].name + ': Visitor has already goaled');
        }
      }
      else {
        console.log('no goals after conversion');
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
        console.log('ABST: ' + bt_experiments[eid].name + ': full_page Visitor has already redirected here');
        return true;
      }
      url = bt_experiments[eid].page_variations[variation];
      if (url !== undefined) {
        abstRedirect(url); // follow the link w search params
        return true;
      }
      else {
        console.log('No matching page found, must be default page no redirect.');
        //add show class to page
        document.body.classList.add('abst-show-page');
        return false;
      }
    }
    else if (bt_experiments[eid].test_type == "magic") {
      console.log('magic test, showing ver ' + variation);
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
      console.log('css test winner, showing ver ' + bt_experiments[eid]['test_winner']);
      document.body.classList.add('test-css-' + eid + '-' + bt_experiments[eid]['test_winner']);
      return true; // next
    }

    if (bt_experiments[eid].test_type == "magic") // magic winner
    {
      console.log('magic test winner, showing ver ' + bt_experiments[eid]['test_winner']);
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

  url = url + window.location.search + window.location.hash;
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
  
  // Check if main domain cookie worked
  if (document.cookie.includes(c_name)) {
    return true;
  }
  
  // Strategy 2: Fallback to current subdomain only
  console.log('⚠️ Main domain failed, trying subdomain fallback');
  var subdomainCookie = escape(value) + expiryString + sameSiteAttrs;
  document.cookie = c_name + '=' + subdomainCookie;
  
  // Check if subdomain cookie worked
  if (document.cookie.includes(c_name)) {
    console.log('ABST Cookie set on subdomain:', hostname);
    return true;
  }
  
  // Strategy 3: Last resort - minimal cookie
  console.log('⚠️ Subdomain failed, trying minimal cookie');
  var minimalCookie = escape(value) + ';path=/';
  document.cookie = c_name + '=' + minimalCookie;
  
  if (document.cookie.includes(c_name)) {
    console.log('ABST Cookie set on minimal cookie');
    return true;
  }

  console.log('ABST Cookie set on localStorage backup. ALERT COOKIES ARE BEING BLOCKED.');
  console.log('ABST: Server side conversions will not work. Client side conversions will work.');
  
  // All failed - use localStorage backup
  return btSetLocal(c_name, value);
}

function deleteCookie(c_name) {
  if (btIsLocalhost())
    return btDeleteLocal(c_name);

  var hostname = window.location.hostname;
  var domain = hostname.replace(/^www\./, '').split('.').slice(-2).join('.');
  domain = '.' + domain;

  document.cookie = c_name + "=; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=/; SameSite=None; Secure; domain=" + domain;
}

function abstGetCookie(c_name) {
  if (btIsLocalhost())
    return btGetLocal(c_name);

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
    console.log('ABST Cookie found on localStorage backup.');
    return localValue;
  }
  
  return false;
}

function abstShowPage() {
  document.body.classList.add('abst-show-page');
  try {
    parent.window.document.body.classList.add('abst-show-page');
  } catch (e) { } // ignore if not allowed
}

function btSetLocal(c_name, value) {
  localStorage.setItem(c_name, value);
}

function btGetLocal(c_name) {
  return localStorage.getItem(c_name);
}

function btDeleteLocal(c_name) {
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

  if(!bt_experiments[eid].is_current_user_track) {
    console.log('bt_experiment_w: ignoring ' + eid + ' because user is not tracked');
    return true;
  }

  //if its magic get the value after the last dash
  if (bt_experiments[eid].test_type == 'magic' && variation.includes('-')) {
    variation = 'magic-' + variation.split('-').pop();
  }
  
  // if its a fingerprinter and we dont have a uuid, then wait for it
  if (bt_experiments[eid].conversion_page == 'fingerprint' && !localStorage.getItem('ab-uuid')) {
    console.log("bt_exp_w: waiting for fingerprint");
    setTimeout(bt_experiment_w, 500, eid, variation, type, url, orderValue);
    return true; //back in 500ms
  }
  console.log('bt_experiment_w',eid,variation,type,url,orderValue);


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
  if (bt_experiments[eid].conversion_page == 'fingerprint')
    data.uuid = localStorage.getItem('ab-uuid');


  //add advanced id if necessary
  if (btab_vars.advanced_tracking == '1')
    data.ab_advanced_id = localStorage.getItem('ab-advanced-id');

  experiment_vars = JSON.stringify(experiment_vars);
  // For non-redirect events, check if we're waiting for approval
  if (window.abst.waitForApproval) {
    queueEventData(data, url);
  } else {
    // Process normally if not waiting for approval
    // Send beacon
    console.log('sent beacon', navigator.sendBeacon(bt_ajaxurl + "?action=bt_experiment_w&method=beacon", JSON.stringify(data)));
  }
  abstSetCookie('btab_' + eid, experiment_vars, 1000);
  //start time watchers b4 redirecting
  if ( bt_experiments[eid]['conversion_page'] == 'time') {
    window.abst.timer[eid] = bt_experiments[eid]['conversion_time'];
    console.log('added timer conversion fr visit' + eid + '-' + bt_experiments[eid]['conversion_time']);
  }
  // Process goals for timer setup
  var goals = bt_experiments[eid].goals;
  
  // Handle different potential formats of goals data
  if (type == 'visit' && goals) {
    // If goals is a string, try to parse it as JSON
    if (typeof goals === 'string') {
      try {
        goals = JSON.parse(goals);
        console.log('Parsed goals from string:', goals);
      } catch (e) {
        console.error('Error parsing goals string:', e);
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
        console.log('Added timer for goal ' + eid + '-' + idx + ' with time: ' + entries[0][1]);
      }
    });
  }

  if (url && url !== "ex") {
    addServerEvents({
      eid: eid,
      variation: variation,
      type: 'visit',
    });
    abstRedirect(url);
  }
  else {
    btab_track_event(data); // analytics tagger    
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
    const queueString = localStorage.getItem('abstTestDataQueue');
    if (queueString) {
      queue = JSON.parse(queueString);
    }
  } catch (e) {
    console.error('Error parsing abstTestDataQueue', e);
  }

  // Add new item to queue
  queue.push(queueData);
 
  // Save updated queue
  localStorage.setItem('abstTestDataQueue', JSON.stringify(queue));

  console.log('Event queued for approval', queueData);
}
 
/**
 * Process events when approval is given
 * This function should be called when cookie consent or other approval is given
 */
function abst_process_approved_events() {
  // Set approval flag to true and save to localStorage
  setAbstApprovalStatus(true);

  // Get queued events
  let queue = [];
  try {
    const queueString = localStorage.getItem('abstTestDataQueue');
    if (queueString) {
      queue = JSON.parse(queueString);
    }
  } catch (e) {
    console.error('Error parsing abstTestDataQueue', e);
    return false;
  }

  if (!queue.length) {
    console.log('No queued events to process');
    return true;
  }

  console.log('Processing ' + queue.length + ' queued events');

  // Process each queued event
  queue.forEach((queueItem) => {
    // Track the event
    btab_track_event(queueItem.data);

    // Send beacon for the event
    console.log('Sending queued beacon', queueItem.data);
    console.log(navigator.sendBeacon(bt_ajaxurl + "?action=bt_experiment_w&method=beacon", JSON.stringify(queueItem.data)));
  });

  // Clear the queue
  localStorage.removeItem('abstTestDataQueue');

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

  if (btab_vars.tagging == '0') {
    //console.log('event tagging turned off');
    return false;
  }

  window.abst.eventQueue.push(data);

  trackName = bt_experiments[data.eid].name || data.eid;
  //gtag always
  gtm_data = {
    'event': 'ab_split_test',
    'test_name': trackName,
    'test_variation': data.variation,
    'test_event': data.type,
    'test_id': data.eid,
  };
  if (bt_experiments[data.eid]['conversion_page'] == 'fingerprint')
    gtm_data.abuuid = localStorage.getItem('ab-uuid');
  else if (localStorage.getItem('ab-advanced-id'))
    gtm_data.abuuid = localStorage.getItem('ab-advanced-id');

  window.dataLayer = window.dataLayer || [];
  window.dataLayer.push(gtm_data); // add to gtm data layer

  if (window.abst.abconvertpartner.ga4) //ga4 add
    gtag('event', 'ab_split_test', {
      'test_name': data.eid,
      'test_variation': data.variation,
      'test_event': data.type,
    });


  if (window.abst.abconvertpartner.abawp) { //analyticswp
    AnalyticsWP.event('Test: ' + bt_experiments[data.eid].name, {
      test_id: data.eid,
      test_name: bt_experiments[data.eid].name,
      test_variation: data.variation,
      test_visit_type: data.type
    });
  }

  if (window.abst.abconvertpartner.clarity) { //clarity
    clarity("set", bt_experiments[data.eid].name + "-" + data.type, data.variation);
  }

  if (window.abst.abconvertpartner.gai) { //google analytics
    if (typeof ga === "function" && typeof ga.getAll === "function") {
      var trackers = ga.getAll();
      var tracker = trackers && trackers[0];
      if (tracker) {
        tracker.send("event", bt_experiments[data.eid].name, data.type, data.variation, { nonInteraction: true }); // send non interactive event to GA
        window.abst.abconvertpartner.gai = true;
      }
    } else {
      // Optionally log a warning for debugging
      // console.warn("Google Analytics (Universal Analytics) not detected or ga.getAll unavailable.");
    }
  }

  if (window.abst.abconvertpartner.abmix) { //abmix
    mixpanel.track(bt_experiments[data.eid].name, { 'type': data.type, 'variation': data.variation }, { send_immediately: true });
  }

  if (window.abst.abconvertpartner.abumav) { //umami
    usermaven("track", bt_experiments[data.eid].name, {
      type: data.type,
      variation: data.variation
    });

  }

  if (window.abst.abconvertpartner.umami) { //umami
    umami.track(bt_experiments[data.eid].name, {
      type: data.type,
      variation: data.variation
    });
  }

  if (window.abst.abconvertpartner.cabin) { //cabin
    cabin.event(bt_experiments[data.eid].name + ' | ' + data.type + ' | ' + data.variation);
  }

  if (window.abst.abconvertpartner.plausible) { //plausible
    plausible(bt_experiments[data.eid].name, {
      props: {
        type: data.type,
        variation: data.variation
      }
    });
  }

  if (window.abst.abconvertpartner.fathom) { //fathom
    fathom.trackGoal(bt_experiments[data.eid].name, {
      type: data.type,
      variation: data.variation
    });
  }
  if (window.abst.abconvertpartner.posthog) {
    posthog.capture(bt_experiments[data.eid].name, {
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

  // Define all analytics providers we want to check for
  const analyticsProviders = [
    {
      name: 'ga4',
      check: () => typeof gtag === "function",
      process: () => {
        window.abst.eventQueue.forEach((element) => {
          gtag('event', 'ab_split_test', {
            'test_name': bt_experiments[element.eid].name,
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
          posthog.capture(bt_experiments[element.eid].name, {
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
          mixpanel.track(bt_experiments[element.eid].name, { 'type': element.type, 'variation': element.variation }, { send_immediately: true });
        });
      }
    },
    {
      name: 'abumav',
      check: () => typeof usermaven === "function",
      process: () => {
        window.abst.eventQueue.forEach((element) => {
          usermaven("track", bt_experiments[element.eid].name, {
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
          umami.track(bt_experiments[element.eid].name, {
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
          cabin.event(bt_experiments[element.eid].name + ' | ' + element.type + ' | ' + element.variation);
        });
      }
    },
    {
      name: 'plausible',
      check: () => !!window.plausible,
      process: () => {
        window.abst.eventQueue.forEach((element) => {
          plausible(bt_experiments[element.eid].name, {
            props: {
              type: element.type,
              variation: element.variation
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
          AnalyticsWP.event('Test: ' + bt_experiments[element.eid].name, {
            test_id: element.eid,
            test_name: bt_experiments[element.eid].name,
            test_variation: element.variation,
            test_visit_type: element.type
          });
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
    testCookie = JSON.parse(testCookie);
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

  // Prevent duplicate event listeners: use a registry keyed by eventType+selector+goalId
  window.abst = window.abst || {};
  window.abst._abstClickListenerRegistry = window.abst._abstClickListenerRegistry || {};
  var registryKey = eventType + '|' + conversionSelector + '|' + goalId;
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
      console.log("set Fingerprint: " + fp);
    } catch (error) {
      console.error("Error setting fingerprint:", error);
    }
  }
  else {
    console.log("ab-uuid: already set: " + localStorage.getItem("ab-uuid"));
  }
}
async function setAbCrypto() {
  if (!localStorage.getItem("ab-advanced-id")) {
    let fp;
    if(crypto.randomUUID) {
      try {
        fp = crypto.randomUUID();
        console.log("Set advanced id: " + fp);
      } catch (e) { // localhost, http
        fp = "10000000-1000-4000-8000-100000000000".replace(/[018]/g, c => (+c ^ crypto.getRandomValues(new Uint8Array(1))[0] & 15 >> +c / 4).toString(16));
        console.log("Set advanced ID: " + fp);
      }
    }
    else {
      fp = "10000000-1000-4000-8000-100000000000".replace(/[018]/g, c => (+c ^ crypto.getRandomValues(new Uint8Array(1))[0] & 15 >> +c / 4).toString(16));
      console.log("Set advanced ID: " + fp);
    }
    localStorage.setItem("ab-advanced-id", fp);
    //set cookie same
    abstSetCookie("ab-advanced-id", fp, 365);
  }
}

/**
 * Revoke approval for tracking (for testing or when user revokes consent)
 */
function abst_revoke_approval() {
  setAbstApprovalStatus(false);
  console.log('Approval revoked, events will be queued until approval is given again');
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
      console.log('Successfully fixed magic_definition quotes issue. Edit your split test in the WordPress admin to remove this console log.');
    } catch (e2) {
      console.warn('Failed to parse magic_definition after fixes. Please recreate this split test.', e2);
      return null;
    }
  }
  return magic_definition;
}

function showMagicTest(eid, index,scroll = false) { // called from magic bar so we arent logging anything
  var magic_definition = parseMagicTestDefinition(bt_experiments[eid].magic_definition);
  if (!Array.isArray(magic_definition) || !magic_definition.length) return;
  // foreach swapElement in magic_definition
  magic_definition.forEach(function (swapElement) {
    if (!swapElement || typeof swapElement.selector !== 'string'){
      console.error('ABST: Invalid swapElement in showMagicTest:', swapElement);
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
    var variation;
    if (Array.isArray(swapElement.variations)) {
        variation = swapElement.variations[index];
        // If variation is null, undefined, or empty string, dont do anything and skip this variation
        if ((variation === null || variation === undefined || variation === '') && swapElement.variations[0] !== undefined) {
            console.log('Variation element value not set - Using default value for:', swapElement.selector);
            variation = swapElement.variations[0]; //show og
        }
    } else {
        variation = undefined;
    }
    if (variation === undefined){
      console.error('ABST: No variation found for selector in showMagicTest:', swapElement.selector);
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
    selector = magic_definition[0] && typeof magic_definition[0].selector === 'string' ? magic_definition[0].selector : null;
    scrollAndHighlightElement(selector);
  }
}


function scrollAndHighlightElement(selector) {
  setTimeout(function(){
    
  console.log('scrollAndHighlightElement',selector);
  var firstElements = [];
  if (selector) {
    try {
      firstElements = document.querySelectorAll(selector);
      console.log('scrollAndHighlightElement: firstElements',firstElements);
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
              const cookieData = JSON.parse(existingCookie);
              if (cookieData.goals && cookieData.goals[idx] === 1) {
                console.log('Goal already triggered:', eid, 'Goal #', idx);
                break; // Skip further processing if goal already triggered
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
    switch (exp.conversion_page) {
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
              if (window.btab_vars.post_id == exp.conversion_page) {
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

// For backward compatibility, point abtracker to the tracker's checkVisibility method
window.btab_vars.abtracker = function() {
  const tracker = ensureTrackerInitialized();
  tracker.checkVisibility();
};

// Initialize the unified tracker object
function ensureTrackerInitialized() {
  if (!window.btab_vars.tracker) {
    window.btab_vars.tracker = {
      elements: {},   // keyed by `${eid}_${selector}`
      active: false,
      interval: null,
      trackedElements: new Set(),  // Track which elements have fired events
      
      // Check visibility and fire events
      checkVisibility: function() {
        // Process tracked elements first
        for (const key in this.elements) {
          if (!this.elements.hasOwnProperty(key)) continue;
          
          const { eid, selector, variation } = this.elements[key];
          const els = document.querySelectorAll(selector);
          
          // Check if any element is visible (simplified and more reliable)
          const isAnyVisible = Array.from(els).some(el => {
            if (!el) return false;
            
            // Check basic visibility - skip hidden elements
            //if (el.offsetParent === null) return false;
            
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
            
            return (
              rect.bottom > 0 && 
              rect.top < viewportHeight && 
              rect.right > 0 && 
              rect.left < viewportWidth
            );
          });
          
          // If element is visible and not tracked yet
          if (isAnyVisible && !this.trackedElements.has(key)) {
            let shouldFireVisit = false;
            
            // Check cookie to see if we should fire visit event
            const cookieVal = abstGetCookie('btab_' + eid);
            if (cookieVal) {
              try {
                // If we have a valid cookie, don't fire visit event
                JSON.parse(cookieVal);
                shouldFireVisit = false;
              } catch (e) {
                console.error('Error parsing cookie for ' + eid, e);
                shouldFireVisit = true;
              }
            } else {
              shouldFireVisit = true;
            }
            
            if (shouldFireVisit) {
              try {
                bt_experiment_w(eid, variation, 'visit', false);
                this.trackedElements.add(key);
                // Also add the eid_variation key for backward compatibility
                this.trackedElements.add(`${eid}_${variation}`);
              } catch (e) {
                console.error('Error in bt_experiment_w:', e);
              }
            }
          }
        }
        
        // Also process all experiments (for backward compatibility)
        if (typeof bt_experiments === 'object' && bt_experiments) {
          for (const eid in bt_experiments) {
            if (!bt_experiments.hasOwnProperty(eid)) continue;
            const exp = bt_experiments[eid];
            if (!exp || !exp.variation) continue;
            
            // Skip already tracked experiments
            if (this.trackedElements.has(`${eid}_${exp.variation}`)) continue;
          
          // Get all variations for this experiment
          let variations = [exp.variation];
          if (typeof bt_get_variations === 'function') {
            variations = bt_get_variations(eid);
          }
          
          variations.forEach((variation) => {
            // Skip if already tracked
            const trackingKey = `${eid}_${variation}`;
            if (this.trackedElements.has(trackingKey)) return;
            
            // Find selector for this variation
            let selector = exp.selector || '[bt-eid="' + eid + '"]';
            if (exp.selectors && exp.selectors[variation]) {
              selector = exp.selectors[variation];
            }
            
            // Add to tracked elements if not already there
            if (!this.elements[`${eid}_${selector}`]) {
              this.elements[`${eid}_${selector}`] = { eid, selector, variation };
            }
          });
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
  const key = `${eid}_${selector}`;
  
  tracker.elements[key] = { eid, selector, variation };
  
  tracker.start();
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

        variationObj = JSON.parse(variationObj);

       if( bt_experiments[eid] === undefined ) {
          return false;
        }

        // if its converted already
        if( variationObj.conversion == 1 ) {
            console.info('AB Split test already converted.');
          return true;
        }

        // if its not an empty conversion URL or page, then it must be defined elsewhere
        if( bt_experiments[eid].conversion_url != '' || bt_experiments[eid].conversion_page != '' ) {
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
          console.log("no conversion elements found");
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
            variationObj = JSON.parse(variationObj);

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
      console.log('ABST Scroll conversion will watch. We are on the default page');
      return true; // We're on the default page, scroll listener should be active
    }
    
    // Check if we're on one of the variation pages by checking page IDs
    if (experiment.page_variations && typeof current_page !== 'undefined' && Array.isArray(current_page)) {
      for (const [varId, variationUrl] of Object.entries(experiment.page_variations)) {
        if (current_page.some(page => String(page) === String(varId))) {
          console.log('ABST Scroll conversion will watch. We are on a variation page');
          return true; // We're on a variation page
        }
      }
    }
    console.log('ABST Scroll conversion will not watch. Not on a page related to this full page test');
    return false; // Not on a page related to this full page test
  }
  
  // For on-page tests (magic, ab_test, css_test): Only set up if test elements exist on current page
  if (experiment.test_type === 'magic' || experiment.test_type === 'ab_test' || experiment.test_type === 'css_test') {
    // Check if any test elements exist on the current page
    const testElements = document.querySelectorAll('[bt-eid="' + experimentId + '"]');
    if (testElements.length > 0) {
      console.log('ABST Scroll conversion will watch. Test elements found on page for experiment', experimentId);
      return true; // Test elements found, scroll listener should be active
    }
    
    // For magic tests, check if magic definition elements exist
    if (experiment.test_type === 'magic' && experiment.magic_definition) {
      try {
        const magicDef = Array.isArray(experiment.magic_definition) ? experiment.magic_definition : JSON.parse(experiment.magic_definition);
        for (const def of magicDef) {
          if (def.selector && document.querySelector(def.selector)) {
            console.log('ABST Scroll conversion will watch. Magic test selector found on page for experiment', experimentId, 'selector:', def.selector);
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
    testCookie = JSON.parse(testCookie);
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
  window.abstRedirecting = true;
  document.documentElement.style.opacity = '0';
  document.documentElement.style.transition = 'none';
  window.location.replace(abRedirectUrl(url));
}

/* fallback to ensure nobody is ever left with a blank page */
setTimeout(function() {
  if (!window.abstRedirecting && !document.body.classList.contains('abst-show-page') ) {
    document.documentElement.style.opacity = '1';
    document.documentElement.style.transition = '';
    document.body.classList.add('abst-show-page');
  } 
}, 500);