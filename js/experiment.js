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
            args[0] = args[0].replace(/^\s*ABST\s*:\s*/i, '');
            args[0] = 'ABST: ' + args[0];
        } else {
            args.unshift('ABST:');
        }
        originalLog.apply(console, args);
    };
})();

window.acattrs = {

  dropdownAutoWidth:true,

  width:'100%',

  placeholder: 'Please choose a page…',

  allowClear: true,

  ajax: {

    url: ajaxurl, // AJAX URL is predefined in WordPress admin

    dataType: 'json',

    delay: 250, // delay in ms while typing when to perform a AJAX search

    data: function (params) {

        return {

          q: params.term, // search query

          type:'control', // or 'variations'

          action: 'abst_page_selector', // AJAX action for admin-ajax.php

          nonce: abst_exturl.page_selector_nonce

        };

    },

    processResults: function( data ) {

      var options = [];

      if ( data ) {

    

        // data is the array of arrays, and each of them contains ID and the Label of the option

        jQuery.each( data, function( index, text ) { // do not forget that "index" is just auto incremented value

          options.push( { id: text[0], text: text[1]  } );

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





jQuery(document).ready(function() {






  const urlParams = new URLSearchParams(window.location.search);

  const testType = urlParams.get('test_type'); // Get the 'test_type' parameter

  

  // Check if a radio button is already selected

  if (!jQuery('input[name="test_type"]:checked').length) {

      // Select the radio button with the value matching the URL parameter

      if (testType) {

          jQuery(`input[name="test_type"][value="${testType}"]`).prop('checked', true);

      }

  }



  // if name="post_title" is empty then set it to the url query title

  if (jQuery('input[name="post_title"]').val() == '') {

    //if url param name rateExists

    if(urlParams.get('name') !== null){

      jQuery('input[name="post_title"]').val(urlParams.get('name'));

    }

  }

  





  jQuery('.show_css_classes>h4').on('click', function() {

    //show all siblings

    jQuery(this).nextUntil('h4').slideToggle();

  });



  // remove conversion page options with duplicate values

  jQuery("#page_variations option").each(function(){

    //find options with duplicate values and remove them

      jQuery("#page_variations").find("option[value='"+jQuery(this).val()+"']").eq(1).remove(); // eq 1 means the second value, not the first cause we want it

  });



  // Initialize icon select dropdowns with custom templates

  function formatIconOption(option) {

    if (!option.id && !option.element) return option.text;

    var icon = jQuery(option.element).data('icon') || '';

    if (!icon) return option.text;

    return jQuery('<span class="abst-select-option"><span class="abst-select-icon">' + icon + '</span> ' + option.text + '</span>');

  }

  

  jQuery('.abst-icon-select').each(function() {

    jQuery(this).select2({

      dropdownAutoWidth: true,

      width: '100%',

      minimumResultsForSearch: -1,

      templateResult: formatIconOption,

      templateSelection: formatIconOption

    });

  });



  // Only make specific sections collapsible - NOT the main card sections

  jQuery('#configuration_settings > div.show_targeting_options > h3, #configuration_settings > div.restart_test > h3').on('click', function() {

    var $section = jQuery(this).parent();
    var $content = $section.children().not('h3');

    $content.stop(true, true);

    if ($section.hasClass('collapsed')) {
      $section.addClass('ab-accordion-animating').removeClass('collapsed').addClass('expanded');
      $content.hide().slideDown(220, function() {
        $section.removeClass('ab-accordion-animating');
        jQuery(this).css('display', '');
      });
    } else {
      $section.addClass('ab-accordion-animating');
      $content.slideUp(180, function() {
        $section.removeClass('expanded ab-accordion-animating').addClass('collapsed');
        jQuery(this).css('display', '');
      });
    }

  });



  //ajax post getter 

  jQuery(function($){  

    jQuery( '#bt_experiments_full_page_default_page' ).select2(window.acattrs);

    // Initialize conversion page selector separately with its own config (no AJAX, uses server-rendered options)
    var conversionPageAttrs = {
        width: '100%', // a fixed 25rem overflowed the goals card; capped in bt-bb-ab-admin.css
        dropdownAutoWidth: true,
        placeholder: 'Choose Page',
        allowClear: true
    };
    jQuery( '#bt_experiments_conversion_page_selector' ).select2(conversionPageAttrs);

    // select2 has just added its box: show or hide it for the current goal type.
    refreshConversionGoalType();



    



    window.acattrs.multiple=true;

    window.acattrs['ajax'] = {

      url: ajaxurl, // AJAX URL is predefined in WordPress admin

      dataType: 'json',

      delay: 250, // delay in ms while typing when to perform a AJAX search

      data: function (params) {

          return {

            q: params.term, // search query

            type:'variations', // 'control' or 'variations'

            action: 'abst_page_selector', // AJAX action for admin-ajax.php

            nonce: abst_exturl.page_selector_nonce

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

  };







  jQuery( '#page_variations' ).select2(window.acattrs);



  function validatePageVariations() {

    var defaultPage = jQuery('#bt_experiments_full_page_default_page').val();

    var variationPages = jQuery('#page_variations').val() || [];



    if (!defaultPage || variationPages.length === 0) {

        return; // Nothing to validate against.

    }



    if (variationPages.includes(defaultPage)) {

        alert('A page cannot be both the default and a variation. The conflicting variation will be removed.');

        var newVariationPages = variationPages.filter(function (val) {

            return val !== defaultPage;

        });

        jQuery('#page_variations').val(newVariationPages).trigger('change.select2'); // Update value and refresh Select2 UI

    }

  }



  // Validate when a variation is selected or removed.

  jQuery('#page_variations').on('change', function () {

      validatePageVariations();

  });



  // Validate when the default page is changed.

  jQuery('#bt_experiments_full_page_default_page').on('change', function() {

      validatePageVariations();

  });



  // Initial validation on page load.

  validatePageVariations();



  //update labels

  if(jQuery("#timestamp").length > 0)

    jQuery("#timestamp").html(jQuery("#timestamp").html().replace('Published on','Running since'));



  if(jQuery('[data-colname="Test started on"]').length > 0)

  jQuery('[data-colname="Test started on"]').each(function(){

    jQuery(this).html(jQuery(this).html().replace('Published','Started'));

  });

  function setExperimentPostStatus(status) {
    jQuery('#post_status').val(status);
    jQuery('#hidden_post_status').val(status);
    jQuery('select#post_status option:selected').prop('selected', false);
    jQuery('select#post_status option[value="' + status + '"]').prop('selected', true);
  }

  function validateExperimentCanLaunch() {
    var hasTestType = jQuery('#full_page').is(':checked') || jQuery('#ab_test').is(':checked') || jQuery('#magic').is(':checked');
    // Page visit: a goal page must be chosen. Element click: a valid CSS selector is needed.
    var goalType = getConversionGoalType();
    var $goalField = goalType === 'selector' ? jQuery('#bt_experiments_conversion_selector') : jQuery('#bt_experiments_conversion_page_selector');
    var goalValue = String($goalField.val() || '').trim();
    var hasConversion = goalValue !== '' && (goalType !== 'selector' || isValidGoalSelector(goalValue));

    if (hasTestType && hasConversion) {
      return true;
    }

    if (!hasTestType) {
      jQuery('.show_test_type').addClass('err');
    }

    if (!hasConversion) {
      $goalField
        .closest('.conversion-goal, .bt_experiments_inner_custom_box')
        .addClass('err');

      if (goalType === 'selector') {
        $goalField.trigger('focus');
      }
    }

    setTimeout(function(){
      jQuery('.show_test_type').removeClass("err");
      jQuery('.conversion-goal, .bt_experiments_inner_custom_box').removeClass("err");
    },1000);

    return false;
  }

  function submitExperimentWithStatus(status, requireValidation) {
    if (requireValidation && !validateExperimentCanLaunch()) {
      return;
    }

    setExperimentPostStatus(status);

    if(status === 'publish') {
      jQuery("#publish").trigger('click');
      return;
    }

    if(jQuery("#save-post").length) {
      jQuery("#save-post").trigger('click');
      return;
    }

    // Fallback for screens without a dedicated save-draft button.
    jQuery("#publish").trigger('click');
  }

  if(!jQuery('#starttest').length) {
    jQuery('<button type="button" class="button button-primary button-large" id="starttest" style="display:none">Start Test</button>').insertAfter("#publish");
  }

  jQuery('#starttest').off('click').on('click', function(e){
    e.preventDefault();
    submitExperimentWithStatus('publish', true);
  });

  jQuery(document).on('click', '.abst-lifecycle-action', function(e){
    e.preventDefault();

    var action = jQuery(this).data('action') || '';

    if(action === 'save-draft') {
      submitExperimentWithStatus('draft', false);
      return;
    }

    if(action === 'launch-test' || action === 'update-test') {
      submitExperimentWithStatus('publish', true);
      return;
    }

    if(action === 'pause-test') {
      submitExperimentWithStatus('pending', false);
      return;
    }

    if(action === 'resume-test') {
      submitExperimentWithStatus('publish', true);
      return;
    }

    if(action === 'mark-complete') {
      jQuery('#abst-mark-complete-modal').css('display', 'flex');
      return;
    }
    if(action === 'view-results') {
      var $resultsTab = jQuery('[href="#results"]').first();
      if($resultsTab.length) {
        $resultsTab.trigger('click');
        jQuery('html, body').animate({ scrollTop: jQuery('#post-body').offset().top - 32 }, 200);
      }
    }
  });







  

  /* mark complete modal: ends the test, every visitor sees the original again */
  jQuery(document).on('click', '#abst-mark-complete-modal .abst-modal-cancel', function(){
    jQuery('#abst-mark-complete-modal').hide();
  });

  jQuery(document).on('click', '#abst-mark-complete-modal', function(e){
    if(e.target === this) {
      jQuery(this).hide();
    }
  });

  jQuery(document).on('click', '#abst-mark-complete-modal .abst-modal-confirm', function(){
    var $modal = jQuery('#abst-mark-complete-modal');
    var $button = jQuery(this);
    $button.prop('disabled', true).text('Completing…');
    jQuery.post(ajaxurl, {
      action: 'abst_mark_test_complete',
      test_id: $modal.data('test-id'),
      nonce: $modal.data('nonce')
    }, function(response){
      if(response && response.success) {
        window.location.reload();
        return;
      }
      $button.prop('disabled', false).text('Complete test');
      alert((response && response.data && response.data.message) ? response.data.message : 'Could not complete the test.');
    }).fail(function(){
      $button.prop('disabled', false).text('Complete test');
      alert('Could not complete the test. Please try again.');
    });
  });

  jQuery('.test-variation-info input').on('click',function(){



    copyToClipboard(jQuery(this).val());

    

    alert(jQuery(this).val() + ' copied! \n\nRemember to replace {name} with a name of your choice. e.g...\nab-var-new');



  });



  jQuery('body').on('click','.urlqueryexamples',function(){

      jQuery('.target-example').slideDown();

  });



  

  /* show pages that test visits and conversions are observed */

    jQuery('body').on('click','.results-visits, .results-conversions',function(){

      jQuery(this).parents('.results_variation').next('.seen-on').slideToggle();

  });





  jQuery('select#post_status option[value="publish"]').text('Test Running');

  if(jQuery('#post-status-display').text().includes('Published'))

    jQuery("#post-status-display").text("Test Running");

});





  function isValidURL(string) {

    var res = string.match(/(http(s)?:\/\/.)?(www\.)?[-a-zA-Z0-9@:%._\+~#=]{2,256}\.[a-z]{2,6}\b([-a-zA-Z0-9@:%_\+.~#?&//=]*)/g);

    return (res !== null);

  }



  //experiment admin tabs



  function showExperimentTab(target){

    jQuery('#configuration_settings, #idea_settings, .abst_show_experiment_results').hide();

    if(target === '#config'){

      jQuery('#configuration_settings').show();

    }else if(target === '#results'){

      jQuery('.abst_show_experiment_results').show();

    }else if(target === '#idea'){

      jQuery('#idea_settings').show();

    }

  }

  function updateIdeaTotal(){

    var impact = jQuery('[name="abst_idea_impact"]').val();
    var reach = jQuery('[name="abst_idea_reach"]').val();
    var confidence = jQuery('[name="abst_idea_confidence"]').val();
    var effort = jQuery('[name="abst_idea_effort"]').val();
    var totalField = jQuery('.abst-idea-total-field');

    if(!totalField.length){

      return;

    }

    if(impact === '' || reach === '' || confidence === '' || effort === ''){

      totalField.val('—');
      return;

    }

    var total = parseInt(impact, 10) + parseInt(reach, 10) + parseInt(confidence, 10) + (6 - parseInt(effort, 10));
    totalField.val(total);

  }




  function hasIdeaContent(){

    return jQuery.trim(jQuery('[name="abst_idea_hypothesis"]').val() || '') !== '';

  }

  function hasConfiguredTestType(){

    return jQuery('input[name="test_type"]:checked').length > 0;

  }

  function applyExperimentTab(target){

    var tabExists = jQuery('[href="' + target + '"]').length > 0;
    var resolvedTarget = tabExists ? target : '#config';

    jQuery('.tab-active').removeClass('tab-active');
    jQuery('[href="' + resolvedTarget + '"]').addClass('tab-active');
    showExperimentTab(resolvedTarget);

  }

  function getRequestedFocusTab(){

    try{
      var params = new URLSearchParams(window.location.search || '');
      var focus = (params.get('focus') || '').toLowerCase();

      if(focus === 'settings' || focus === 'config'){
        return '#config';
      }
      if(focus === 'results'){
        return '#results';
      }
      if(focus === 'idea' || focus === 'ideas'){
        return '#idea';
      }
    }catch(err){
      return '';
    }

    return '';

  }



  if(jQuery('.results_variation').length >1)

  {
    applyExperimentTab('#results');

  } else if (jQuery('[href="#idea"]').length && hasIdeaContent() && !hasConfiguredTestType()) {
    applyExperimentTab('#idea');

  } else {
    applyExperimentTab('#config');
  }

  var requestedTab = getRequestedFocusTab();
  if(requestedTab){
    applyExperimentTab(requestedTab);
  }



  jQuery('[href="#config"]').on('click',function(e){

    jQuery('.tab-active').removeClass('tab-active');

    jQuery(this).addClass('tab-active');

    e.preventDefault();

    showExperimentTab('#config');

  });



  jQuery('[href="#results"]').on('click',function(e){

    jQuery('.tab-active').removeClass('tab-active');

    jQuery(this).addClass('tab-active');

    e.preventDefault();

    showExperimentTab('#results');

  });

  jQuery('[href="#idea"]').on('click',function(e){

    jQuery('.tab-active').removeClass('tab-active');

    jQuery(this).addClass('tab-active');

    e.preventDefault();

    showExperimentTab('#idea');

  });




  jQuery('[name="abst_idea_impact"], [name="abst_idea_reach"], [name="abst_idea_confidence"], [name="abst_idea_effort"]').on('change', function(){

    updateIdeaTotal();

  });

  updateIdeaTotal();





  jQuery('#bt_clear_experiment_results').on('click',function(event){

    var eid = jQuery(this).attr('eid');

    event.preventDefault();

    if (jQuery('#restart-confirm').val().toLowerCase() == 'delete') {

        var data = {

          'action': 'abst_clear_experiment_results',

          'eid': eid,

          'bt_action': 'clear',

          'nonce': abst_exturl.clear_results_nonce,

        };

        jQuery.post(bt_ajaxurl, data, function(response) {

        response = JSON.parse(response);

        alert(response.text);

        if(response.success)

          location.reload();

        });

    }

    else{

        alert('To restart the test, please enter "DELETE" in the delete box');

    }



  }); 



  // Attach input event listeners to trigger description update

  jQuery("#bt_experiments_target_percentage").on("input", updateDescription);



  jQuery(".bt_variation_container").on('click',function(){

    window.location = jQuery(this).parents('tr').find('.row-actions a').attr('href');

  });





  refreshTestType();

  refreshTestPages();

  refreshConversionPage();

  jQuery('input:radio[name="test_type"]').change(function(e){

    refreshTestType();

  });





  if(jQuery("#magicjsonerror").length > 0) {

    let magicElement = document.querySelector('#magic_definition');

    let raw = magicElement.textContent;



    // 2. Fix malformed unescaped quotes like Jumbo"s

    let fixed = raw.replace(/(?<=[a-zA-Z])"(?=[a-zA-Z])/g, '\\"');



    // 3. Optional: Try to parse and re-stringify for formatting validation

    try {

        let parsed = JSON.parse(fixed);

        // Optionally: prettify back to JSON string

        fixed = JSON.stringify(parsed, null, 4);

        // 4. Replace the original content with the fixed JSON

        magicElement.textContent = fixed;

        console.log("Fixed and updated #magic_definition content refreshing real quick.");

        //click save #starttest

        jQuery("#post-body-content").prepend("<h1 style='color: red; font-size: 3em; font-weight: bold;'>Found and fixed an encoding issue, the page will reload, please wait...</H1>")



        jQuery("#postbox-container-2").css('opacity','0.2')



        setTimeout(function(){

          jQuery("#starttest").trigger('click');

        },500);

    } catch (e) {

        console.error("Error parsing JSON after fix:", e.message);

    }



  }

  jQuery("body").on('click','.show-css-classes',function(e){

    e.preventDefault();

    jQuery(".test-variation-info").toggle();

  });



  jQuery("#bt_experiments_full_page_default_page").change(function(){

    refreshTestPages();

  });

  jQuery("#bt_experiments_conversion_page_selector").change(function(){

    refreshConversionPage();

  });

  // The conversion goal is a page visit ('page') or an element click ('selector').
  jQuery("#bt_experiments_conversion_page").on('change', function(){

    refreshConversionGoalType();

  });

  refreshConversionGoalType();







  jQuery('body').on('click', '#abst-results-table .tabulator-row', function(e) {

    if (e.detail !== 3) return; // triple click



    //if no test or variation then dont continue

    if(!jQuery(this).find('[tabulator-field="id"]').text())

      return;

    

    // confirm if delete?

    if(confirm('Delete Variation data for ' + jQuery(this).find('[tabulator-field="id"]').text() + '?')){



      console.log('Delete ' + jQuery(this).find('[tabulator-field="id"]').text() + ' ID:' + jQuery(this).find('[tabulator-field="id"]').text() );



      jQuery.ajax({

        type: "POST",

        url: window.ajaxurl,

        data: {

          'action': 'abst_delete_variation',

          'pid': abstpid,

          'variation': jQuery(this).find('[tabulator-field="id"]').text(),

          'nonce': abst_exturl.delete_variation_nonce,

        },

        success: function(data) {

          alert(data);

          location.reload();

        },

        error: function(data) {

          alert(JSON.stringify(data));

        }

      });

    }

  });












  jQuery(".results_variation.na").each(function(index,el){

    var magicVars = jQuery("#magic_definition").val();

    if(!magicVars || magicVars == '')

      return;

    

    magicVars = JSON.parse(magicVars);

    if(magicVars && magicVars[0] && magicVars[0].variations && magicVars[0].variations[index] !== undefined){

      if(magicVars[0].variations[index] !== '') {

        var varText = document.createElement('p');
        varText.className = 'seen-on-text';
        varText.textContent = '"' + magicVars[0].variations[index] + '"';
        jQuery(el).next('.seen-on').prepend(varText);

      } else {

        jQuery(el).next('.seen-on').prepend('<p class="seen-on-text">Empty / None</p>');

      }

    }

  });

  



  // Read ?abst_device_size= from the URL so the filter is deep-linkable / bookmarkable.
  var __abstInitialSize = '';
  try {
    var __abstParams = new URLSearchParams(window.location.search);
    var __abstRaw = __abstParams.get('abst_device_size');
    if (__abstRaw === 'mobile' || __abstRaw === 'tablet' || __abstRaw === 'desktop') {
      __abstInitialSize = __abstRaw;
    }
  } catch (e) { /* older browsers: skip */ }

  if (__abstInitialSize) {
    jQuery('#abst-device-size-select').val(__abstInitialSize);
  }

  createTable(__abstInitialSize);



  createGraph(__abstInitialSize);

  // Device-size filter: re-render results table + chart using the per-size observations slice
  jQuery(document).off('change.abstDeviceSize', '#abst-device-size-select').on('change.abstDeviceSize', '#abst-device-size-select', function(){
    var size = this.value;
    jQuery('#abst-results-table').empty();
    createTable(size);
    createGraph(size);

    // Update URL without creating a history entry so back-button doesn't trap users.
    try {
      var url = new URL(window.location.href);
      if (size) {
        url.searchParams.set('abst_device_size', size);
      } else {
        url.searchParams.delete('abst_device_size');
      }
      window.history.replaceState({}, '', url.toString());
    } catch (e) { /* noop */ }
  });

});// end on ready function







// Global variables for table and chart

var table;

var abtestChart;



function createTable(deviceSize){

 //use tabulator and  window.abtestChartData



 if(!window.abtestChartData){

  console.log('no abtestChartData');

  return;

 }

// Device-size filter: swap observations for a per-size slice (mobile/tablet/desktop).
// Per-size probability and rate are computed server-side by abst_analyze_device_sizes().
// Restored before createTable returns so nothing else sees the filtered view.
var __abstOriginalObservations = abtestChartData.observations;
var __abstInsufficientData = false;
var __abstUnderpowered = false;
var __abstMinVisitsPerVariation = 50; // below this, confidence is not computed
var __abstConfidenceThreshold = 95;   // matches includes/statistics.php winner threshold
if(deviceSize && abtestChartData.observations){
  var __abstFiltered = {};
  for(var __abstKey in abtestChartData.observations){
    var __abstSrc = abtestChartData.observations[__abstKey];
    if(!__abstSrc) continue;
    var __abstView = Object.assign({}, __abstSrc);
    if(__abstSrc.device_size && __abstSrc.device_size[deviceSize]){
      var __abstDs = __abstSrc.device_size[deviceSize];
      __abstView.visit = __abstDs.visit || 0;
      __abstView.conversion = __abstDs.conversion || 0;
      __abstView.rate = (typeof __abstDs.rate !== 'undefined')
        ? __abstDs.rate
        : ((__abstView.visit > 0) ? Math.round(((__abstView.conversion / __abstView.visit) * 100) * 100) / 100 : 0);
      __abstView.probability = (typeof __abstDs.probability !== 'undefined') ? __abstDs.probability : 0;
    } else {
      __abstView.visit = 0;
      __abstView.conversion = 0;
      __abstView.rate = 0;
      __abstView.probability = 0;
    }
    __abstFiltered[__abstKey] = __abstView;
  }
  abtestChartData.observations = __abstFiltered;
}

// Evaluate sample-size / underpowered flags on whichever view is active (filtered or full).
if (abtestChartData.observations) {
  var __abstMaxProb = 0;
  for (var __abstGateKey in abtestChartData.observations) {
    var __abstGateObs = abtestChartData.observations[__abstGateKey];
    if (!__abstGateObs) continue;
    if ((__abstGateObs.visit || 0) < __abstMinVisitsPerVariation) {
      __abstInsufficientData = true;
    }
    if ((__abstGateObs.probability || 0) > __abstMaxProb) {
      __abstMaxProb = __abstGateObs.probability || 0;
    }
  }
  if (!__abstInsufficientData && __abstMaxProb < __abstConfidenceThreshold) {
    __abstUnderpowered = true;
  }
}

// Surface / clear the sample-size warning directly under the results table.
(function(){
  var $w = jQuery('#abst-sample-size-warning');
  if (!$w.length) {
    $w = jQuery('<div id="abst-sample-size-warning" class="abst-sample-size-warning" style="display:none;"></div>');
    jQuery('#abst-results-table').after($w);
  }
  jQuery('#abst-device-size-warning').remove();
  if (__abstInsufficientData) {
    var scope = deviceSize ? (' on ' + deviceSize) : '';
    $w.text('Insufficient sample size' + scope + ' - need at least ' + __abstMinVisitsPerVariation + ' visits per variation before computing confidence.').show();
  } else {
    $w.hide();
  }
})();

//reset nevessarty vars

var newTableData = [];

var controlVariationRate = 0;



// Safety check for observations

if(!abtestChartData.observations || Object.keys(abtestChartData.observations).length === 0) {

  console.log('No observations data');

  return;

}



// Determine control variation - use explicit control, or fall back to default page, or first observation

var controlVariation = abtestChartData.control_variation;

if(!controlVariation || !abtestChartData.observations[controlVariation]) {

  // For full page tests, try the default page

  if(abtestChartData.test_type === 'full_page' && abtestChartData.full_page_default_page) {

    controlVariation = abtestChartData.full_page_default_page;

  }

  // If still not found in observations, use first available observation

  if(!controlVariation || !abtestChartData.observations[controlVariation]) {

    controlVariation = Object.keys(abtestChartData.observations)[0];

  }

}



var controlVariationRate = abtestChartData.observations[controlVariation] ? abtestChartData.observations[controlVariation]['rate'] : 0;

for (let observationKey in abtestChartData.observations) {

    const observation = abtestChartData.observations[observationKey];

    

    // Safety check for required observation properties

    if(typeof observation.rate === 'undefined' || observation.rate === null) {

      observation.rate = 0;

    }

    if(typeof observation.visit === 'undefined' || observation.visit === null) {

      observation.visit = 0;

    }

    if(typeof observation.conversion === 'undefined' || observation.conversion === null) {

      observation.conversion = 0;

    }

    

    // Guard divide-by-zero: when the control has no traffic in the current slice,
    // lift is undefined — show "—" instead of Infinity/NaN.
    var lift_raw, lift_display;
    if (!controlVariationRate || controlVariationRate === 0) {
      lift_raw = 0;
      lift_display = "—";
    } else {
      lift_raw = Math.round(((observation.rate - controlVariationRate) / controlVariationRate) * 100 * 10) / 10;
      lift_display = lift_raw + "%";
    }

    var conversion_rate_raw = observation.rate;

    var conversion_rate_display = observation.rate + "%";



    // Ensure variation_meta exists before accessing it

    if(observation.variation_meta === undefined){

      observation.variation_meta = {};

    }



    var chance_of_winning_raw = observation.probability || 0;

    var chance_of_winning_display = chance_of_winning_raw + "%";

    // Sample-size gate: below 50 visits per variation, don't show confidence at all.
    // Between 50 visits and the 95% winner threshold, flag the row as Underpowered.
    if (__abstInsufficientData) {
      chance_of_winning_raw = 0;
      chance_of_winning_display = "—";
    } else if (__abstUnderpowered) {
      chance_of_winning_display = chance_of_winning_raw + "% <span class=\"abst-underpowered-icon\" title=\"Below the " + __abstConfidenceThreshold + "% confidence threshold. Keep the test running.\" aria-label=\"Underpowered\" role=\"img\">!</span>";
    }



    var variationLabel = observationKey;

    if(abtestChartData.observations[observationKey]['variation_meta'] && abtestChartData.observations[observationKey]['variation_meta']['label']){  

      variationLabel = abtestChartData.observations[observationKey]['variation_meta']['label'];

    }

    //else if it's magic-0, magic-1, magic-2, etc. convert to Variation A, Variation B, Variation C, etc.

    else if(observationKey.startsWith("magic-")){

      var magicNumber = parseInt(observationKey.replace("magic-", ""));

      var letters = ['A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z'];

      if(magicNumber >= 0 && magicNumber < letters.length){

        variationLabel = "Variation " + letters[magicNumber];

      } else {

        variationLabel = observationKey; // fallback to original if number is out of range

      }

    }




    var conversions = observation.conversion;



    var link = '';

    if(abtestChartData.observations[observationKey]['variation_meta'] && abtestChartData.observations[observationKey]['variation_meta']['link']){

      link = abtestChartData.observations[observationKey]['variation_meta']['link'];

      

      // Add heatmap link if heatmaps are enabled and we have the required data

      if(window.abTestShowheatmapLinks) {

        console.log(abtestChartData);

        var varMeta = abtestChartData.observations[observationKey]['variation_meta'];

        if(varMeta.eid && varMeta.variation && varMeta.page_id) {

          var heatmapUrl = window.bt_adminurl + 'edit.php?post_type=abst_experiments&page=abst-heatmaps';

          heatmapUrl += '&post=' + varMeta.page_id;

          heatmapUrl += '&eid=' + varMeta.eid;

          heatmapUrl += '&variation=' + varMeta.variation;

          heatmapUrl += '&size=large&mode=clicks';

          link += ' <a href="' + heatmapUrl + '" title="View Heatmap for this variation">🔥</a>';

        }

      }





    }



    console.log(link);



    formattedObservations = {

      link: link,

      id: observationKey,

      variation_label: variationLabel,

      visits: observation.visit,

      conversions: conversions,

      conversion_rate: conversion_rate_raw,

      conversion_rate_display: conversion_rate_display,

      chance_of_winning: chance_of_winning_raw,

      chance_of_winning_display: chance_of_winning_display,

      lift: lift_raw,

      lift_display: lift_display

  }



  newTableData.push(formattedObservations);

}



var conversion_rate_label = "Conversion<BR>Rate";

var chance_of_winning_label = "Confidence";



var table = new Tabulator("#abst-results-table", {

  data: newTableData,

  layout: "fitColumns",

  responsiveLayout: "collapse",

  // Without a minimum width, fitColumns squeezes every column to an ellipsis on a
  // phone and collapse never triggers. With one, columns that do not fit fold
  // into a details row under each variation.
  columnDefaults: { minWidth: 90 },

  pagination: false,

  height: "auto",

  headerFilterPlaceholder: "Filter...",

  initialSort:[

    {column:"lift", dir:"desc"}

  ],

  columns:[

      {title:" ", field:"link", visible:true, headerSort:false, width:70, minWidth:60, responsive:0, formatter:"html"},

      {title:"ID", field:"id", sorter:"string", visible:false },

      {title:"Variation", field:"variation_label", minWidth:130, responsive:0, hozAlign:"left",headerHozAlign:"left", sorter:"string", editor:true, frozen:true, cellEdited:function(cell){

        //send to wp ajax to save variation label

        abtestChartData.observations[cell.getRow().getData().id].variation_meta = abtestChartData.observations[cell.getRow().getData().id].variation_meta || {};

        abtestChartData.observations[cell.getRow().getData().id].variation_meta[cell.getField()] = cell.getValue();

        jQuery.ajax({

          type: "POST",

          url: window.ajaxurl,

          data: {

            'action': 'abst_save_variation_label',

            'pid': window.abstpid,

            'variation_name': cell.getValue(),

            'variation_id': cell.getRow().getData().id,

            'nonce': abst_exturl.save_label_nonce,

          },

          success: function(response){

            console.log('saved label');

            cell.getElement().classList.add('abst-flash-green');

            setTimeout(function(){

              cell.getElement().classList.remove('abst-flash-green');

            }, 1000);  

          },

          error: function(error){

            console.log('error saving label');

            console.log(error);

          },

        });

        

        },

    },

      {title:"Uplift", field:"lift", hozAlign:"left",headerHozAlign:"left", sorter:"number", formatter:function(cell, formatterParams){

        var rowData = cell.getRow().getData();
        return rowData.lift_display;

      }},

      {title:chance_of_winning_label, field:"chance_of_winning", sorter:"number", headerHozAlign:"left", hozAlign:"left", formatter:function(cell, formatterParams){

        var rowData = cell.getRow().getData();

        return rowData.chance_of_winning_display; 

      }},

      {title:conversion_rate_label, field:"conversion_rate", sorter:function(a, b, aRow, bRow, column, dir, sorterParams){

        // Extract numeric values from percentage strings if needed

        var aVal = typeof a === 'string' ? parseFloat(a.replace(/[%$,]/g, '')) : parseFloat(a) || 0;

        var bVal = typeof b === 'string' ? parseFloat(b.replace(/[%$,]/g, '')) : parseFloat(b) || 0;

        return aVal - bVal;

      },

      headerHozAlign:"left", hozAlign:"left", headerSortStartingDir:"desc", formatter:function(cell, formatterParams){

        var rowData = cell.getRow().getData();

        return rowData.conversion_rate_display;

      }},

      {title:"Visits", field:"visits", hozAlign:"left",headerHozAlign:"left", },

      {title:"Conversions", field:"conversions",  hozAlign:"left",headerHozAlign:"left", headerSortStartingDir:"desc"},

  ],

  autoResizeColumns:true,

});

// Expose table and restore full observations so later code sees unfiltered data
window.abstResultsTable = table;
abtestChartData.observations = __abstOriginalObservations;



// Use per-size winner for highlighting when a device filter is active; fall back to overall.
var __abstWinnerForHighlight = abtestChartData.test_winner;
if (deviceSize && abtestChartData.device_size_winners && abtestChartData.device_size_winners[deviceSize]) {
  __abstWinnerForHighlight = abtestChartData.device_size_winners[deviceSize];
}

setTimeout(function() {

  //add class to test winner defined at abtestChartData.test_winner

  jQuery('#abst-results-table .tabulator-row').each(function() {

    var id = jQuery(this).find('.tabulator-cell').eq(1).text();

    if(id == __abstWinnerForHighlight){

      jQuery(this).addClass('abtest-winner');

    }

    if(id == abtestChartData.control_variation){

      jQuery(this).addClass('abst-control');

    }

  });

}, 1000);



}

/**

 * createGraph

 * 

 * Creates a line chart using Chart.js, displaying both actual and projected A/B test results.

 * 

 * @param {Object} observations Data object containing actual observations for each variant.

 * @param {Date} abtestStart Date when the A/B test started.

 * @param {Number} estimatedDuration Duration in days for which the test is expected to run.

 */

function createGraph(deviceSize){



if(!window.abtestChartData)

  return;



if (abtestChart && typeof abtestChart.destroy === 'function') {

  abtestChart.destroy();

}

// Device-size filter: swap observations for a per-size slice (mobile/tablet/desktop).
// Restored before createGraph returns so the original object isn't mutated.
var __abstGraphOriginalObservations = abtestChartData.observations;
if (deviceSize && abtestChartData.observations) {
  var __abstGraphFiltered = {};
  for (var __abstGraphKey in abtestChartData.observations) {
    var __abstGraphSrc = abtestChartData.observations[__abstGraphKey];
    if (!__abstGraphSrc) continue;
    var __abstGraphView = Object.assign({}, __abstGraphSrc);
    if (__abstGraphSrc.device_size && __abstGraphSrc.device_size[deviceSize]) {
      var __abstGraphDs = __abstGraphSrc.device_size[deviceSize];
      __abstGraphView.visit = __abstGraphDs.visit || 0;
      __abstGraphView.conversion = __abstGraphDs.conversion || 0;
      __abstGraphView.rate = (typeof __abstGraphDs.rate !== 'undefined')
        ? __abstGraphDs.rate
        : ((__abstGraphView.visit > 0) ? Math.round(((__abstGraphView.conversion / __abstGraphView.visit) * 100) * 100) / 100 : 0);
      __abstGraphView.probability = (typeof __abstGraphDs.probability !== 'undefined') ? __abstGraphDs.probability : 0;
    } else {
      __abstGraphView.visit = 0;
      __abstGraphView.conversion = 0;
      __abstGraphView.rate = 0;
      __abstGraphView.probability = 0;
    }
    __abstGraphFiltered[__abstGraphKey] = __abstGraphView;
  }
  abtestChartData.observations = __abstGraphFiltered;
}



var observations = abtestChartData.observations;



// Use global variables defined in PHP

var testAge = window.testAge || observations.test_age || 0;

var likelyDuration = window.likelyDuration || observations.likely_duration || 0;



// Prepare color palette (array of 15 distinct colors)

var colorPalette = [

  'rgba(54, 162, 235, 1)',   // Blue

  'rgba(255, 99, 132, 1)',   // Red

  'rgba(255, 206, 86, 1)',   // Yellow

  'rgba(75, 192, 192, 1)',   // Teal

  'rgba(153, 102, 255, 1)',  // Purple

  'rgba(255, 159, 64, 1)',   // Orange

  'rgba(199, 199, 199, 1)',  // Grey

  'rgba(83, 102, 255, 1)',   // Indigo

  'rgba(255, 102, 255, 1)',  // Pink

  'rgba(102, 255, 102, 1)',  // Light Green

  'rgba(255, 153, 51, 1)',   // Amber

  'rgba(0, 204, 204, 1)',    // Cyan

  'rgba(204, 0, 204, 1)',    // Magenta

  'rgba(102, 0, 204, 1)',    // Deep Purple

  'rgba(255, 51, 153, 1)'    // Hot Pink

];



// Object to keep track of variant colors

var variantColors = {};

var colorIndex = 0;



// Calculate projections and prepare datasets

var datasets = [];

for (var key in observations) {

  if (observations.hasOwnProperty(key) && key !== 'test_type' && key !== 'test_winner') {

      var variant = observations[key];



      // Assign a color to the variant if not already assigned

      if (!variantColors[key]) {

          variantColors[key] = colorPalette[colorIndex % colorPalette.length];

          colorIndex++;

      }



      var color = variantColors[key];

      var projectedColor = color.replace('1)', '0.5)'); // Make it lighter by reducing opacity



      // Current data

      var currentVisits = variant.visit;

      var currentConversions = variant.conversion;



      var avgDailyVisits, avgDailyConversions, projectedVisits, projectedConversions;

      var historicalDataPoints = [

          { x: 0, y: 0 }, // Historical starting point

          { x: currentVisits, y: currentConversions } // Current data point

      ];

      

      var projectedDataPoints = [];

      

      if (likelyDuration > 0) {

        // Calculate average daily visits and conversions

        avgDailyVisits = currentVisits / testAge;

        avgDailyConversions = currentConversions / testAge;



        // Projected total visits and conversions

        projectedVisits = avgDailyVisits * likelyDuration;

        projectedConversions = avgDailyConversions * likelyDuration;



        // Projected data points

        projectedDataPoints = [

            { x: currentVisits, y: currentConversions }, // Current data point

            { x: projectedVisits, y: projectedConversions } // Projected data point

        ];

      }



      // Add dataset for the historical data (solid line)

      

      function decodeHtmlEntities(str) {

        var txt = document.createElement('textarea');

        txt.innerHTML = str;

        return txt.value;

      }

      

      // Priority order: variation_meta.label > variant.name > key

      var labelText = key; // Default fallback

      

      if(variant.variation_meta && variant.variation_meta.label) {

        labelText = decodeHtmlEntities(variant.variation_meta.label);

      } else if(variant.name) {

        labelText = decodeHtmlEntities(variant.name);

      } else {

        labelText = decodeHtmlEntities(key);

      }



      // loop through and see if there are duplicate titles, then swap to all using the slug instead of the name

      for(var i = 0; i < datasets.length; i++){

        if(datasets[i].label == labelText){

          labelText = variant.slug || key;

          break;

        }

      }



      //loop through labeltext if its magic-0 replace with Variation A, magic-1 with Variation B etc up to Z

      ['A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z'].forEach(function(letter, index) {

        labelText = labelText.replace('magic-' + index, 'Variation ' + letter);

      });

      

      datasets.push({

          label: labelText,

          data: historicalDataPoints,

          showLine: true,

          fill: false,

          backgroundColor: color,

          borderColor: color,

          pointRadius: 5,

          tension: 0.1, // Add some curve to the line

          datalabels: {

              display: true,

              align: 'top',

              formatter: function(value, context) {

                  if (context.dataIndex === 1) {

                      return '(' + value.x.toFixed(0) + ', ' + value.y.toFixed(0) + ')';

                  } else {

                      return '';

                  }

              }

          }

      });



      // Add dataset for the projected data (dashed line)

      if(likelyDuration > 0 && projectedDataPoints.length > 0)

        datasets.push({

          label: labelText + ' (Projected)',

          data: projectedDataPoints,

          showLine: true,

          fill: false,

          backgroundColor: projectedColor,

          borderColor: projectedColor,

          borderDash: [5, 5],

          pointRadius: 5,

          tension: 0.1, // Add some curve to the line

          datalabels: {

              display: true,

              align: 'bottom',

              formatter: function(value, context) {

                  if (context.dataIndex === 1) {

                      return '(' + value.x.toFixed(0) + ', ' + value.y.toFixed(0) + ')';

                  } else {

                      return '';

                  }

              }

          }

      });

  }

}



var ctx = document.getElementById('abtestChart').getContext('2d');



abtestChart = new Chart(ctx, {

  type: 'scatter',

  data: {

      datasets: datasets

  },

  options: {

      responsive: true,

      plugins: {

          datalabels: {

              // Global options (can be overridden per dataset)

              color: 'black',

              font: {

                  weight: 'bold'

              }

          },

          title: {

              display: false,

          },

          tooltip: {

              callbacks: {

                  label: function(context) {

                      var label = context.dataset.label || '';

                      var value = context.parsed;

                      label += ': (' + value.x.toFixed(0) + ', ' + value.y.toFixed(0) + ')';

                      return label;

                  }

              }

          },

          legend: {

              display: true

          }

      },

      scales: {

          x: {

              type: 'linear',

              position: 'bottom',

              title: {

                  display: true,

                  text: 'Visits'

              },

              beginAtZero: true

          },

          y: {

              title: {

                  display: true,

                  text: 'Conversions'

              },

              beginAtZero: true

          }

      }

  }

});

// Restore full observations so callers that read abtestChartData later see unfiltered data.
abtestChartData.observations = __abstGraphOriginalObservations;

}













function refreshTestType(){



  jQuery("#postbox-container-1, .bt_experiments_inner_custom_box").show();
  var sharedSettingsSelector = ".show_targeting_options, .restart_test";



  if(jQuery("input:radio[value=\'full_page\']").is(":checked")){

    jQuery("#configuration_settings>div").show();

    jQuery('.show_css_classes').hide(); // hide element css classes helper

    jQuery("#magic_settings").hide();

    jQuery(".show_full_page_test").show();

  }

  else if(jQuery("input:radio[value=\'ab_test\']").is(":checked")){

    jQuery('.show_css_classes').show(); // show element css classes helper

    jQuery("#configuration_settings>div").show(); 

    jQuery("#magic_settings").hide();

    jQuery(".show_full_page_test").hide();

  }

  else if(jQuery("input:radio[value=\'magic\']").is(":checked")){

    jQuery("#magic_settings").slideDown();

    jQuery("#configuration_settings>div").hide(); 

    jQuery(".show_test_type, #magic_settings, .bt_experiments_inner_custom_box, " + sharedSettingsSelector).show();

    jQuery('.show_css_classes').hide(); // show element css classes helper

    jQuery(".show_full_page_test").hide();

  }

  else

  {

    jQuery(".show_test_type").slideDown();

  }

}



function refreshTestPages(){



  var defaultPage = jQuery("#bt_experiments_full_page_default_page").val();

  if(defaultPage == "false"){

    jQuery(".page-variations-wrapper, #full-page-test-page-preview").hide();

  }

  else{

    jQuery("#full-page-test-page-preview").show().attr("href",bt_homeurl + "/?page_id="+defaultPage);

    jQuery(".page-variations-wrapper").slideDown();

  }

  if(defaultPage){

    var variations = jQuery("#page_variations").val() || [];

    if(variations.indexOf(defaultPage) !== -1){

      variations = variations.filter(function(v){ return v !== defaultPage; });

      jQuery("#page_variations").val(variations).trigger('change');

    }

  }

}



// The conversion goal type: 'page' (a visitor reaches a page) or 'selector' (a visitor clicks an element).
function getConversionGoalType(){

  return jQuery("#bt_experiments_conversion_page").val() === "selector" ? "selector" : "page";

}



// Element-click goals are matched with Element.matches(); a selector may end in |eventname.
function isValidGoalSelector(selector){

  var css = String(selector || '').split('|')[0].trim();

  if(!css)
    return false;

  try {
    document.createDocumentFragment().querySelector(css);
    return true;
  } catch (e) {
    return false;
  }

}



// Show the page picker for a page-visit goal, the CSS selector field for an element-click goal.
function refreshConversionGoalType(){

  var isSelectorGoal = getConversionGoalType() === "selector";

  jQuery(".conversion_page_selector").toggle(!isSelectorGoal);

  // The select2 box sits next to the page select; hide it too when it is not inside the label.
  jQuery("#bt_experiments_conversion_page_selector").next(".select2-container").toggle(!isSelectorGoal);

  jQuery(".conversion_selector_input").toggle(isSelectorGoal);

  refreshConversionPage();

}



function refreshConversionPage(){



  var conv_page = getConversionGoalType();

  

  // Only show preview link when a goal page is chosen

  if(conv_page === "page") {

    var selectedPageId = jQuery("#bt_experiments_conversion_page_selector").val();

    if(selectedPageId && selectedPageId !== "" && !isNaN(selectedPageId)) {

      jQuery("#bt_experiments_conversion_page_preview").show().attr("href", bt_homeurl + "/?page_id=" + selectedPageId);

    } else {

      jQuery("#bt_experiments_conversion_page_preview").hide();

    }

  } else {

    jQuery("#bt_experiments_conversion_page_preview").hide();

  }

}



function copyToClipboard(text) {

    var sampleTextarea = document.createElement("textarea");

    document.body.appendChild(sampleTextarea);

    sampleTextarea.value = text; //save main text in it

    sampleTextarea.select(); //select textarea contenrs

    document.execCommand("copy");

    document.body.removeChild(sampleTextarea);

}



function updateDescription(full = true) {

  // Get the values of the input fields

  var percentage = parseInt(jQuery("#bt_experiments_target_percentage").val());



  // Calculate the description based on the percentage giving examples for 2 3 and 4 variations

  if(percentage < 100)

    var description = "You are testing on " + percentage + "% of your visitors. The remaining " + (100 - percentage) + "% will see the default variation and be ignored.";

  else

    var description = "You are testing on " + percentage + "% of your visitors.";



    if(full)

    {

      // Traffic is split evenly between the original and every variation.
      description += "<BR>Traffic split examples:";

      for (var versions = 2; versions <= 4; versions++) {

        description += "<BR>" + versions + " versions: about " + Math.round(percentage / versions) + "% of your total traffic sees each one.";

      }

    }

  // Update the description

  jQuery("#percentage_description").html(description);

}



