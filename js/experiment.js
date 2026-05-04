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

          action: 'ab_page_selector' // AJAX action for admin-ajax.php

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

  jQuery('#configuration_settings > div.show_targeting_options > h3, #configuration_settings > div.show_autocomplete > h3, #configuration_settings > div.webhooks_settings > h3, #configuration_settings > div.restart_test > h3').on('click', function() {

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

    jQuery('body').on('click', '.conversion_order_value_info', function(e) {
      e.preventDefault();
      e.stopPropagation();

      var $button = jQuery(this);
      var expanded = $button.attr('aria-expanded') === 'true';
      $button.attr('aria-expanded', expanded ? 'false' : 'true');
      $button
        .closest('#conversion_order_value')
        .find('.conversion_order_value_help_panel')
        .first()
        .stop(true, true)
        .slideToggle(180);
    });

    

    jQuery( '#bt_experiments_full_page_default_page, #bt_experiments_conversion_page_selector' ).select2(acattrs);



    



    acattrs.multiple=true;

    acattrs['ajax'] = {

      url: ajaxurl, // AJAX URL is predefined in WordPress admin

      dataType: 'json',

      delay: 250, // delay in ms while typing when to perform a AJAX search

      data: function (params) {

          return {

            q: params.term, // search query

            type:'variations', // 'control' or 'variations'

            action: 'ab_page_selector' // AJAX action for admin-ajax.php

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







  jQuery( '#page_variations' ).select2(acattrs);



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



  acattrs.multiple=false;

  jQuery( '.goal-page' ).select2(acattrs);



  var bt_ext_url_xhr = null;



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
    var hasTestType = jQuery('#full_page').is(':checked') || jQuery('#ab_test').is(':checked') || jQuery('#css_test').is(':checked') || jQuery('#magic').is(':checked');
    var conversionType = jQuery.trim(jQuery('#bt_experiments_conversion_page').val() || '');
    var hasConversion = conversionType !== '';

    if (hasConversion && conversionType === 'page') {
      hasConversion = jQuery.trim(jQuery('#bt_experiments_conversion_page_selector').val() || '') !== '';
    }

    if (hasTestType && hasConversion) {
      return true;
    }

    if (!hasTestType) {
      jQuery('.show_test_type').addClass('err');
    }

    if (!hasConversion) {
      jQuery('#bt_experiments_conversion_page')
        .closest('.conversion-goal, .bt_experiments_inner_custom_box')
        .addClass('err');
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

    if(action === 'view-results') {
      var $resultsTab = jQuery('[href="#results"]').first();
      if($resultsTab.length) {
        $resultsTab.trigger('click');
        jQuery('html, body').animate({ scrollTop: jQuery('#post-body').offset().top - 32 }, 200);
      }
    }
  });







  

  jQuery('body').on('click', '.bt_exturl_copy', function(e) {



    var copyText = document.getElementById('bt-embed-code');



    copyText.select();

    copyText.setSelectionRange(0, 99999);



    document.execCommand('copy');



    jQuery('.bt-copied').css('visibility', 'visible');

  });



  jQuery('body').on('click', '.bt_js_copy', function(e) {

    e.preventDefault();

    var copyText = document.getElementById('conversion_javascript_area');

    copyText.select();

    copyText.setSelectionRange(0, 99999);

    document.execCommand('copy');

    jQuery('.bt_js_copied').fadeIn().delay(800).fadeOut();

  });



  jQuery('.test-variation-info input').on('click',function(){



    copyToClipboard(jQuery(this).val());

    

    alert(jQuery(this).val() + ' copied! \n\nRemember to replace {name} with a name of your choice. e.g...\nab-var-new');



  });



  jQuery('.test-conversion-info input').on('click',function(){



    copyToClipboard(jQuery(this).val());

    

    alert(jQuery(this).val() + ' copied! Wherever you add this class a conversion will be triggered.');



  });



  jQuery('body').on('click','.urlqueryexamples',function(){

      jQuery('.target-example').slideDown();

  });



  

  /* show pages that test visits and conversions are observed */

    jQuery('body').on('click','.results-visits, .results-conversions',function(){

      jQuery(this).parents('.results_variation').next('.seen-on').slideToggle();

  });





  jQuery('body').on('click', '.bt-generate-code', function(e) {



    var button = jQuery(this);



    if( bt_ext_url_xhr != null ) {

      bt_ext_url_xhr.abort();

      bt_ext_url_xhr = null;

    }      



    bt_ext_url_xhr = jQuery.ajax({

      url: bt_exturl.ajax_url,

      dataType: "html",

      type: 'POST',

      data: {

        action: bt_exturl.action,

        nonce: bt_exturl.nonce,

        eid: bt_exturl.eid

      },

      beforeSend: function() {

        button.text('Generating code...');

      },

      success: function( response ) {

        jQuery('#bt-embed-code').text(response).show();

        button.text('Copy Conversion Pixel').removeClass('bt-generate-code').addClass('bt_exturl_copy').addClass('button-primary');

      },

      error: function( xhr ) {

        console.log(xhr);

      }

    });

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

    jQuery('#configuration_settings, #idea_settings, .show_experiment_results').hide();

    if(target === '#config'){

      jQuery('#configuration_settings').show();

    }else if(target === '#results'){

      jQuery('.show_experiment_results').show();

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



  jQuery('[href="#webhooks"]').on('click',function(e){

    jQuery('.tab-active').removeClass('tab-active');

    jQuery(this).addClass('tab-active');

    e.preventDefault();

    showExperimentTab('#config');

    jQuery(".webhooks_settings").show();

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

          'action': 'bt_clear_experiment_results',

          'eid': eid,

          'bt_action': 'clear',

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



  jQuery('.close-goal').on('click',function(){

    jQuery(this).parents('.subgoal').find('.goal-value').val('');

    jQuery(this).parents('.subgoal').find('.goal-type :selected').removeAttr("selected");



    // unserlect from goal page

    jQuery(this).parents('.subgoal').find(".goal-page :selected").removeAttr("selected");

    jQuery(this).parents('.subgoal').hide();

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





  jQuery('.goal-select').change(function(e){

    //get value of select

    var selectedValue = jQuery(this).val();



    jQuery('.results-goal').hide();

   //show goal then append span inside thje tag

   

    jQuery('[data-goal="'+selectedValue+'"]').show().each(function(){

      goalVisits = parseInt(jQuery(this).text());

      parent = jQuery(this).parents('.results_variation');

      visits = parseInt(parent.find('.results-visits').text());

      //console.log(goalVisits,visits);



      rateExists = parent.children('.goal-conversion-rate').length;



      rate = Math.round((goalVisits/visits)*100);

      jQuery(this).find('.goal-conversion-rate').remove();

        jQuery(this).append( ' <span class="goal-conversion-rate">'+rate+'%</span>' );

    });

  });



  jQuery('#css_test_variations').on('change',function(e){

    jQuery('.css-test-helper-zone').empty();

    var testId = jQuery("#post_ID").val();

    for (var i = 1; i <= parseInt(jQuery('#css_test_variations').val()); i++) {

        jQuery(".css-test-helper-zone").append('<code style="background:#f1f5f9; padding:6px 12px; border-radius:4px; font-size:13px;"><span style="color:#64748b;">body.</span>test-css-'+testId+'-'+i+'</code>');

    }

  });

  jQuery('#css_test_variations').trigger('change');



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

        console.log("✅ Fixed and updated #magic_definition content refreshing real quick.");

        //click save #starttest

        jQuery("#post-body-content").prepend("<h1 style='color: red; font-size: 3em; font-weight: bold;'>Found and fixed an encoding issue, the page will reload, please wait...</H1>")



        jQuery("#postbox-container-2").css('opacity','0.2')



        setTimeout(function(){

          jQuery("#starttest").trigger('click');

        },500);

    } catch (e) {

        console.error("❌ Error parsing JSON after fix:", e.message);

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

  function abstPositionAdminOrderValue() {
    var $orderValue = jQuery('#conversion_order_value');
    var targetSelector = '#conversion_order_value_bottom_slot';

    if ($orderValue.length && jQuery(targetSelector).length) {
      $orderValue.appendTo(targetSelector);
    }
  }

  jQuery("#bt_experiments_conversion_page").change(function(){

    jQuery('#selector_explanation').remove();



    refreshConversionPage();



    selectval = jQuery(this).find('option:selected').attr('value');



    if( selectval !== 'page')

      jQuery('.conversion_page_selector').hide();

    else

      jQuery('.conversion_page_selector').show();



      if( selectval == 'link' )

        jQuery('.conversion_link_pattern_input').show();

      else

        jQuery('.conversion_link_pattern_input').hide();

    if( selectval !== 'url')

      jQuery('.conversion_url_input').hide();

    else

      jQuery('.conversion_url_input').show();

      

      if( selectval == 'embed' )

        jQuery('.embed-code-area').show();

      else

        jQuery('.embed-code-area').hide();

      

      if( selectval == 'fingerprint' )

        jQuery('.fingerprint-code-area').show();

      else

        jQuery('.fingerprint-code-area').hide();





      if( selectval == 'time' )

      jQuery('.conversion_time_input').show();

    else

      jQuery('.conversion_time_input').hide();



      if( selectval == 'scroll' )

      jQuery('.conversion_scroll_input').show();

    else

      jQuery('.conversion_scroll_input').hide();



      if( selectval == 'text' )

        jQuery('.conversion_text_input').show();

      else

        jQuery('.conversion_text_input').hide();



    abstPositionAdminOrderValue();

    jQuery('#conversion_order_value').toggle(selectval !== '');



    if( selectval == 'selector' )

      jQuery('.conversion_selector_input').show();

    else

      jQuery('.conversion_selector_input').hide();



    if( selectval == 'javascript' )

      jQuery('#conversion_javascript').show();

    else

      jQuery('#conversion_javascript').hide();



      if(selectval == 'block' ){

        jQuery('.test-conversion-tags-mode').show();

      }

      else

      {

        jQuery('.test-conversion-tags-mode').hide();

      }





  }).change();







  // Show an unshown subgoal when the "+ Add Sub Goal" button is clicked

  jQuery('.add-goal').on('click', function(e) {

    e.preventDefault();

      var hiddenSubgoal = jQuery('.subgoal:hidden').first();

      if (hiddenSubgoal.length > 0) {

          hiddenSubgoal.show();

          hiddenSubgoal.find('.select2-container').hide();

          hiddenSubgoal.find('input').hide(); 

          hiddenSubgoal.find('label').hide();

          if(!jQuery('.subgoal:hidden').length)

            jQuery('.add-goal').hide();

      } else {

          alert('Maximum '+jQuery('.subgoal').length+' subgoals.');

      }

  });



  // on change of select name="goal[1]" get the value of the option and put it in variable 

  jQuery('body').on('change', '.goal-type', function() {

    // get the goal number from the select[name^="goal["]

    var goalNumber = jQuery(this).attr('name').replace('goal[', '').replace(']', '');

    //get selected value

    var selectedValue = jQuery(this).val();

    //is this option inside an optgroup?

    //if setup clear input

    if(jQuery( this ).parents('.subgoal').hasClass('absetupcompletegoal'))

      jQuery( this ).parents('.subgoal').find("input").val('');



    jQuery( this ).parents('.subgoal').addClass('absetupcompletegoal')

    var isOptgroup = jQuery(this).find('option:selected').parent().is('optgroup');

    if(selectedValue == 'page'){

      jQuery( this ).parents('.subgoal').find(".select2-container").show(); 

      jQuery( this ).parents('.subgoal').find("input").hide();

      jQuery( this ).parents('.subgoal').find(".goal-value-label").html('Choose a page that when visited, the goal will be triggered.<br><br> e.g. "Choose a thankyou page or order complete page."').show();

    }

    //text

    else if(selectedValue == 'text'){

      jQuery( this ).parents('.subgoal').find(".select2-container").hide();

      jQuery( this ).parents('.subgoal').find("input").show();

      jQuery( this ).parents('.subgoal').find(".goal-value-label").html('Choose an exact string of text that when visible on the page, the goal will be triggered. Separate multiple trigger strings with a pipe "|".<br><br> e.g. "Thank you for your order"').show();

    }

    //link

    else if(selectedValue == 'link'){

      jQuery( this ).parents('.subgoal').find(".select2-container").hide();

      jQuery( this ).parents('.subgoal').find("input").show();

      jQuery( this ).parents('.subgoal').find(".goal-value-label").html('Enter some of the URL that when clicked, the goal will be triggered. <BR>Can be a local or remote URL. <BR>Can be a full or partial URL e.g. /buynow').show();

    }

    //selector

    else if(selectedValue == 'selector'){

      jQuery( this ).parents('.subgoal').find(".select2-container").hide();

      jQuery( this ).parents('.subgoal').find("input").show();

      jQuery( this ).parents('.subgoal').find(".goal-value-label").html('Enter a CSS selector that when visible on the page, the goal will be triggered. Use # for ID, . for class, or any other css selector. <BR> You can add multiple selectors by separating them with a comma. <BR>e.g. #my-id, .my-class, .another-class .my-child-class').show();

    }

    //url

    else if(selectedValue == 'url'){

      jQuery( this ).parents('.subgoal').find(".select2-container").hide();

      jQuery( this ).parents('.subgoal').find("input").show();

      jQuery( this ).parents('.subgoal').find(".goal-value-label").html('Enter part of the URL that when found, the goal will be triggered. <BR>Can be a full or partial URL. Can include query strings but must be in the correct order. <BR> e.g. /buynow').show();

    }

    else if(selectedValue == 'javascript'){

      jQuery( this ).parents('.subgoal').find(".select2-container").hide();

      jQuery( this ).parents('.subgoal').find("input").hide();

      var conversion_code = "ab-" + window.abstpid + " ab-goal-" + goalNumber;

      jQuery( this ).parents('.subgoal').find(".goal-value-label").html('Add this JavaScript code to your website to trigger this goal: <br><br> <code>&lt;script&gt;(window.abGoal = window.abGoal || []).push(['+window.abstpid+','+goalNumber+']); processAbstGoal?.();&lt;/script&gt;</code>').show();

    }

    else if(selectedValue == 'block'){

      jQuery( this ).parents('.subgoal').find(".select2-container").hide();

      jQuery( this ).parents('.subgoal').find("input").hide();

      var conversion_code = "ab-" + window.abstpid + " ab-goal-" + goalNumber;

      jQuery( this ).parents('.subgoal').find(".goal-value-label").html('Add these classes to any element, that when visible on the page, the goal will be triggered: <br><br> <code>'+conversion_code+'</code>').show();

    }

    else if(selectedValue == 'woo'){

      jQuery( this ).parents('.subgoal').find(".select2-container").hide();

      jQuery( this ).parents('.subgoal').find("input").hide();

      jQuery( this ).parents('.subgoal').find(".goal-value-label").html('ab-'+window.abstpid+' ab-goal-'+goalNumber).show();

    }

    else if(selectedValue == 'scroll'){

      jQuery( this ).parents('.subgoal').find(".select2-container").hide();

      jQuery( this ).parents('.subgoal').find("input").show();

      jQuery( this ).parents('.subgoal').find(".goal-value-label").html('Triggers a goal when you scroll to a certain percentage of the test page. <br>Enter scroll depth as a percentage (0-100) e.g. 50').show();

    }

    else if(isOptgroup || selectedValue == ''){ // is not set or is a woocommerce / surecart special select

      jQuery( this ).parents('.subgoal').find(".select2-container").hide();

      jQuery( this ).parents('.subgoal').find("input").hide();

      jQuery( this ).parents('.subgoal').find(".goal-value-label").hide();

    }

    //time

    else if(selectedValue == 'time'){

      jQuery( this ).parents('.subgoal').find(".select2-container").hide();

      jQuery( this ).parents('.subgoal').find("input").show();

      jQuery( this ).parents('.subgoal').find(".goal-value-label").html('Monitors user activity (scrolling, mouse movement, clicks, etc.)<br>Enter seconds of activity needed to trigger goal. e.g.  60').show();

    }

    else

    {

      jQuery( this ).parents('.subgoal').find(".select2-container").hide();

      jQuery( this ).parents('.subgoal').find("input").show();

      jQuery( this ).parents('.subgoal').find(".goal-value-label").text('Enter ' + selectedValue).show();    

    }



    

  });



  setTimeout(function(){ // why

    jQuery('.goal-type').trigger('change').show();

    if(!jQuery('.subgoal:hidden').length)  // hide if no more to add

      jQuery('.add-goal').hide();

  }, 10);



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









 jQuery('#autocomplete_on').on('change', function() {



  if(jQuery(this).is(":checked")){

    jQuery('.ac_options').show();

  }

  else

  {

    jQuery('.ac_options').hide();

  }

  

  }).trigger('change');



  // Add event listener for conversion selector input to explain CSS selectors

  jQuery("#bt_experiments_conversion_selector").on("input", explainCssSelector);



  // Function to explain CSS selectors in human terms

  function explainCssSelector() {

    const selectorValue = jQuery(this).val().trim();

    let explanation = "";

    let selectorType = "Complex";

    let selectorExplanation = "";

    

    if (!selectorValue) {

      // Clear explanation if input is empty

      jQuery("#selector_explanation").remove();

      return;

    }

    

    // Create explanation container if it doesn't exist

    if (jQuery("#selector_explanation").length === 0) {

      jQuery(this).after('<div id="selector_explanation" style="margin-top: 8px; padding: 10px; background-color: #f8f8f8; border-left: 4px solid #0073aa; font-size: 13px;"></div>');

    }

    

    // Check if we have comma-separated selectors

    if (selectorValue.includes(",")) {

      const selectors = selectorValue.split(",").map(s => s.trim());

      selectorType = "Group Selector";

      let selectorDescriptions = [];

      

      // Analyze each selector in the group

      selectors.forEach(selector => {

        selectorDescriptions.push(`<code>${selector}</code>`);

      });

      

      selectorExplanation = `Any element matching any of these selectors: ${selectorDescriptions.join(", ")}`;

      explanation = `<strong>${selectorType}:</strong> ${selectorExplanation} will trigger a conversion when clicked.`;

      

      

      // Update the explanation

      jQuery("#selector_explanation").html(explanation);

      return;

    }

    

    // Analyze the selector - handle complex selectors better

    

    // Check for complex selectors with combinators first

    if (selectorValue.includes(">")) {

      const parts = selectorValue.split(">");

      const parent = parts[0].trim();

      const child = parts[1].trim();

      selectorType = "Child Combinator";

      selectorExplanation = `Elements matching <code>${child}</code> that are direct children of elements matching <code>${parent}</code>`;

    } else if (selectorValue.includes(" ") && !selectorValue.includes("[")) {

      const parts = selectorValue.split(" ").filter(p => p.trim() !== "");

      const ancestor = parts[0].trim();

      const descendant = parts[parts.length-1].trim();

      selectorType = "Descendant Combinator";

      selectorExplanation = `Elements matching <code>${descendant}</code> that are descendants of elements matching <code>${ancestor}</code>`;

    } else if (selectorValue.includes("+")) {

      const parts = selectorValue.split("+");

      const previous = parts[0].trim();

      const next = parts[1].trim();

      selectorType = "Adjacent Sibling";

      selectorExplanation = `Elements matching <code>${next}</code> that are immediately preceded by a sibling matching <code>${previous}</code>`;

    } else if (selectorValue.includes("~")) {

      const parts = selectorValue.split("~");

      const previous = parts[0].trim();

      const siblings = parts[1].trim();

      selectorType = "General Sibling";

      selectorExplanation = `Elements matching <code>${siblings}</code> that are preceded by a sibling matching <code>${previous}</code>`;

    } else if (selectorValue.startsWith("#")) {

      // ID selector

      const idName = selectorValue.substring(1);

      selectorType = "ID Selector";

      selectorExplanation = `A single element with the ID "${idName}"`;

    } else if (selectorValue.startsWith(".")) {

      // Class selector

      const className = selectorValue.substring(1);

      selectorType = "Class Selector";

      selectorExplanation = `Elements with the class "${className}". Multiple elements can have the same class`;

    } else if (selectorValue.includes(".")) {

      // Element with class

      const parts = selectorValue.split(".");

      const element = parts[0];

      const className = parts[1].split(/[\s\[\]\+\~\>]/)[0]; // Get class name before any combinator

      selectorType = "Element with Class";

      selectorExplanation = `<code>&lt;${element}&gt;</code> elements with the class "${className}"`;

    } else if (selectorValue.includes("#")) {

      // Element with ID

      const parts = selectorValue.split("#");

      const element = parts[0];

      const idName = parts[1].split(/[\s\[\]\+\~\>]/)[0]; // Get ID before any combinator

      selectorType = "Element with ID";

      selectorExplanation = `The <code>&lt;${element}&gt;</code> element with the ID "${idName}"`;

    } else if (selectorValue.includes("[") && selectorValue.includes("]")) {

      // Attribute selector

      selectorType = "Attribute Selector";

      selectorExplanation = `Elements based on the presence or value of the specified attribute`;

    } else if (selectorValue.includes(":")) {

      // Pseudo-class or pseudo-element

      selectorType = "Pseudo-class/element";

      selectorExplanation = `Elements based on a special state or position`;

    } else {

      // Element selector

      selectorType = "Element Selector";

      selectorExplanation = `All <code>&lt;${selectorValue}&gt;</code> elements on the website. <br><small>Did you mean to target a class or ID? Add a . for class or # for ID before the selector <br> Example: <code>.${selectorValue}</code> for class or <code>#${selectorValue}</code> for ID</small>`;

    }

    

    explanation = `<strong>${selectorType}:</strong> ${selectorExplanation} will trigger a conversion when clicked.`;

        

    // Update the explanation

    jQuery("#selector_explanation").html(explanation);

  }









  jQuery(".results_variation.na").each(function(index,el){

    var magicVars = jQuery("#magic_definition").val();

    if(!magicVars || magicVars == '')

      return;

    

    magicVars = JSON.parse(magicVars);

    if(magicVars && magicVars[0] && magicVars[0].variations && magicVars[0].variations[index] !== undefined){

      if(magicVars[0].variations[index] !== '')

        jQuery(el).next('.seen-on').prepend('<p class="seen-on-text">"'+magicVars[0].variations[index]+'"</p>');

      else

        jQuery(el).next('.seen-on').prepend('<p class="seen-on-text">Empty / None</p>');

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



  createGraph(0, __abstInitialSize);

  // Device-size filter: re-render results table + chart using the per-size observations slice
  jQuery(document).off('change.abstDeviceSize', '#abst-device-size-select').on('change.abstDeviceSize', '#abst-device-size-select', function(){
    var size = this.value;
    jQuery('#abst-results-table').empty();
    createTable(size);
    createGraph(jQuery('#abst-goal-select').val() || 0, size);

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
// Per-size probability and rate are computed server-side by bt_bb_ab_analyze_device_sizes().
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
      __abstView.goals = __abstDs.goals || {};
      __abstView.rate = (typeof __abstDs.rate !== 'undefined')
        ? __abstDs.rate
        : ((__abstView.visit > 0) ? Math.round(((__abstView.conversion / __abstView.visit) * 100) * 100) / 100 : 0);
      __abstView.probability = (typeof __abstDs.probability !== 'undefined') ? __abstDs.probability : 0;
    } else {
      __abstView.visit = 0;
      __abstView.conversion = 0;
      __abstView.goals = {};
      __abstView.rate = 0;
      __abstView.probability = 0;
    }
    __abstFiltered[__abstKey] = __abstView;
  }
  abtestChartData.observations = __abstFiltered;
}

// Evaluate sample-size / underpowered flags on whichever view is active (filtered or full).
// Does not apply to Thompson mode, where the column shows traffic weight, not confidence.
if (abtestChartData.conversion_style !== 'thompson' && abtestChartData.observations) {
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

const goalArray = [];

//reset nevessarty vars

var newTableData = [];

var controlVariationRate = 0;



//create goal array from abtestChartData.goals or thin air

for (let i = 1; i < 11; i++) {

  if(!window.abtestChartData.goals || !window.abtestChartData.goals[i]) {

    goalArray[i] = ''; // Set empty string for missing goals

    continue;

  }

  const goal = window.abtestChartData.goals[i];

  const goalKeys = Object.keys(goal);

  const goalValue = goalKeys.length > 0 && goal[goalKeys[0]] !== '' ? goal[goalKeys[0]] : '';

  if(goalValue !== '')

    goalArray[i] = "<small>Goal: " + goalKeys.toString().toUpperCase() + "</small><BR>" + goalValue;

}



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

    if(abtestChartData.conversion_use_order_value == "1"){

      conversion_rate_display = "$" + (observation.rate/100).toFixed(3); //3 decimal places

      conversion_rate_raw = observation.rate/100; // Store as decimal for sorting

    }



    // Ensure variation_meta exists before accessing it

    if(observation.variation_meta === undefined){

      observation.variation_meta = {};

    }



    var chance_of_winning_raw = observation.probability || 0;

    var chance_of_winning_display = chance_of_winning_raw + "%";

    

    // Safety check for variation_meta and weight

    if(abtestChartData.conversion_style == "thompson" && observation.variation_meta && observation.variation_meta.weight){

      chance_of_winning_raw = Math.round(observation.variation_meta.weight * 1000) / 10; // Convert decimal to percentage with 1 decimal place

      chance_of_winning_display = chance_of_winning_raw + "%";

    }

    // Sample-size gate: below 50 visits per variation, don't show confidence at all.
    // Between 50 visits and the 95% winner threshold, flag the row as Underpowered.
    // Neither applies to Thompson weight (column represents real traffic allocation).
    if (abtestChartData.conversion_style !== "thompson") {
      if (__abstInsufficientData) {
        chance_of_winning_raw = 0;
        chance_of_winning_display = "—";
      } else if (__abstUnderpowered) {
        chance_of_winning_display = chance_of_winning_raw + "% <span class=\"abst-underpowered-icon\" title=\"Below the " + __abstConfidenceThreshold + "% confidence threshold. Keep the test running.\" aria-label=\"Underpowered\" role=\"img\">!</span>";
      }
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

    if(abtestChartData.conversion_use_order_value == "1"){

      conversions = "$" + (Math.round(observation.conversion * 100) / 100).toFixed(2); // Properly format currency

    }



    var link = '';

    if(abtestChartData.observations[observationKey]['variation_meta'] && abtestChartData.observations[observationKey]['variation_meta']['link']){

      link = abtestChartData.observations[observationKey]['variation_meta']['link'];

      

      // Add heatmap link if heatmaps are enabled and we have the required data

      if(window.abTestShowheatmapLinks) {

        console.log(abtestChartData);

        var varMeta = abtestChartData.observations[observationKey]['variation_meta'];

        if(varMeta.eid && varMeta.variation && varMeta.page_id) {

          var heatmapUrl = window.location.origin + '/wp-admin/edit.php?post_type=bt_experiments&page=abst-heatmaps';

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



  // Safety check for observation goals

  if(observation.goals) {

    for (let goalId in observation.goals) {

      const goal = observation.goals[goalId];

      if(!goal) continue;

      const goalProperties = Object.keys(goal);

      const firstProperty = goalProperties[0];

      formattedObservations['subgoal'+goalId] = goal;

      formattedObservations['subgoal'+goalId+'rate'] = ((goal / observation.visit) * 100).toFixed(2) + "%";

    }

  }

  newTableData.push(formattedObservations);

}



conversion_rate_label = "Conversion<BR>Rate";

if(abtestChartData.conversion_use_order_value == "1"){

  conversion_rate_label = "Revenue / <BR>Visit";

}

chance_of_winning_label = "Confidence";

//if thompson

if(abtestChartData.conversion_style == "thompson"){

  chance_of_winning_label = "Weight";

}



var table = new Tabulator("#abst-results-table", {

  data: newTableData,

  layout: "fitColumns",

  responsiveLayout: "collapse",

  pagination: false,

  height: "auto",

  headerFilterPlaceholder: "Filter...",

  initialSort:[

    {column:"lift", dir:"desc"}

  ],

  columns:[

      {title:" ", field:"link", visible:true, headerSort:false, width:70, formatter:"html"},

      {title:"ID", field:"id", sorter:"string", visible:false },

      {title:"Variation", field:"variation_label", hozAlign:"left",headerHozAlign:"left", sorter:"string", editor:true, frozen:true, cellEdited:function(cell){

        //send to wp ajax to save variation label

        abtestChartData.observations[cell.getRow().getData().id].variation_meta = abtestChartData.observations[cell.getRow().getData().id].variation_meta || {};

        abtestChartData.observations[cell.getRow().getData().id].variation_meta[cell.getField()] = cell.getValue();

        jQuery.ajax({

          type: "POST",

          url: window.ajaxurl,

          data: {

            'action': 'save_variation_label',

            'pid': window.abstpid,

            'variation_name': cell.getValue(),

            'variation_id': cell.getRow().getData().id,

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

      {title: goalArray[1], field:"subgoal1", hozAlign:"left",headerHozAlign:"left", sorter:"string", visible:false},

      {title: 'Subgoal<br> Rate', field:"subgoal1rate", hozAlign:"left",headerHozAlign:"left", sorter:"string", visible:false},

      {title: goalArray[2], field:"subgoal2", hozAlign:"left",headerHozAlign:"left", sorter:"string", visible:false},

      {title: 'Subgoal<br> Rate', field:"subgoal2rate", hozAlign:"left",headerHozAlign:"left", sorter:"string", visible:false},

      {title: goalArray[3], field:"subgoal3", hozAlign:"left",headerHozAlign:"left", sorter:"string", visible:false},

      {title: 'Subgoal<br> Rate', field:"subgoal3rate", hozAlign:"left",headerHozAlign:"left", sorter:"string", visible:false},

      {title: goalArray[4], field:"subgoal4", hozAlign:"left",headerHozAlign:"left", sorter:"string", visible:false},

      {title: 'Subgoal<br> Rate', field:"subgoal4rate", hozAlign:"left",headerHozAlign:"left", sorter:"string", visible:false},

      {title: goalArray[5], field:"subgoal5", hozAlign:"left",headerHozAlign:"left", sorter:"string", visible:false},

      {title: 'Subgoal<br> Rate', field:"subgoal5rate", hozAlign:"left",headerHozAlign:"left", sorter:"string", visible:false},

      {title: goalArray[6], field:"subgoal6", hozAlign:"left",headerHozAlign:"left", sorter:"string", visible:false},

      {title: 'Subgoal<br> Rate', field:"subgoal6rate", hozAlign:"left",headerHozAlign:"left", sorter:"string", visible:false},

      {title: goalArray[7], field:"subgoal7", hozAlign:"left",headerHozAlign:"left", sorter:"string", visible:false},

      {title: 'Subgoal<br> Rate', field:"subgoal7rate", hozAlign:"left",headerHozAlign:"left", sorter:"string", visible:false},

      {title: goalArray[8], field:"subgoal8", hozAlign:"left",headerHozAlign:"left", sorter:"string", visible:false},

      {title: 'Subgoal<br> Rate', field:"subgoal8rate", hozAlign:"left",headerHozAlign:"left", sorter:"string", visible:false},

      {title: goalArray[9], field:"subgoal9", hozAlign:"left",headerHozAlign:"left", sorter:"string", visible:false},

      {title: 'Subgoal<br> Rate', field:"subgoal9rate", hozAlign:"left",headerHozAlign:"left", sorter:"string", visible:false},

      {title: goalArray[10], field:"subgoal10", hozAlign:"left",headerHozAlign:"left", sorter:"string", visible:false},

      {title: 'Subgoal<br> Rate', field:"subgoal10rate", hozAlign:"left",headerHozAlign:"left", sorter:"string", visible:false},

      

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



//<select id="abst-goal-select" data-dashlane-rid="c47198ec68c30d70" data-dashlane-classification="other"><option value="">Primary Conversion</option><option value="subgoal1">Link absplittest.com</option><option value="subgoal2">Url pricing</option></select>



//on select change hide all subgoals columns in the tabulator show column except the selected one

jQuery('#abst-goal-select').off('change.abstGoal').on('change.abstGoal', function() {

  table.hideColumn('subgoal1');

  table.hideColumn('subgoal1rate');

  table.hideColumn('subgoal2');

  table.hideColumn('subgoal2rate');

  table.hideColumn('subgoal3');

  table.hideColumn('subgoal3rate');

  table.hideColumn('subgoal4');

  table.hideColumn('subgoal4rate');

  table.hideColumn('subgoal5');

  table.hideColumn('subgoal5rate');

  table.hideColumn('subgoal6');

  table.hideColumn('subgoal6rate');

  table.hideColumn('subgoal7');

  table.hideColumn('subgoal7rate');

  table.hideColumn('subgoal8');

  table.hideColumn('subgoal8rate');

  table.hideColumn('subgoal9');

  table.hideColumn('subgoal9rate');

  table.hideColumn('subgoal10');

  table.hideColumn('subgoal10rate');

  

  // Only show columns if a subgoal is selected (not empty value for Primary Conversion)

  if (this.value && this.value !== '') {

    table.showColumn(this.value);

    table.showColumn(this.value+'rate');

  }

  

  table.redraw(true);

  createGraph(this.value, jQuery('#abst-device-size-select').val() || '');

//also update chart data 

});



// Function to update table data when subgoal selection changes

function updateTableData(selectedGoalId) {

    if (!window.abtestChartData || !window.abtestChartData.observations) {

        return;

    }

    

    const tableData = [];

    const observations = window.abtestChartData.observations;

    

    for (let variationKey in observations) {

        if (variationKey === 'bt_bb_ab_stats') continue;

        

        const variation = observations[variationKey];

        const goalConversions = variation.goals && variation.goals[selectedGoalId] ? variation.goals[selectedGoalId] : 0;

        

        tableData.push({

            variation_label: variationKey,

            visits: variation.visit || 0,

            subgoal: selectedGoalId,

            conversions: goalConversions,

            conversion_rate: variation.rate ? variation.rate + '%' : '0%',

            chance_of_winning: variation.probability  ? variation.probability + '%' : '0%'

        });

    }

    console.log(tableData);

    table.setData(tableData);

}



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

function createGraph(goal=0, deviceSize){



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
      __abstGraphView.goals = Object.assign({}, __abstGraphDs.goals || {});
      __abstGraphView.rate = (typeof __abstGraphDs.rate !== 'undefined')
        ? __abstGraphDs.rate
        : ((__abstGraphView.visit > 0) ? Math.round(((__abstGraphView.conversion / __abstGraphView.visit) * 100) * 100) / 100 : 0);
      __abstGraphView.probability = (typeof __abstGraphDs.probability !== 'undefined') ? __abstGraphDs.probability : 0;
    } else {
      __abstGraphView.visit = 0;
      __abstGraphView.conversion = 0;
      __abstGraphView.goals = {};
      __abstGraphView.rate = 0;
      __abstGraphView.probability = 0;
    }
    __abstGraphFiltered[__abstGraphKey] = __abstGraphView;
  }
  abtestChartData.observations = __abstGraphFiltered;
}



var observations = abtestChartData.observations;



if(goal){

  // Extract numeric part from goal (e.g., "subgoal1" -> "1")

  const goalNumber = goal.replace('subgoal', '');

  

  for(var observation in observations){

    if(observations[observation].goals && observations[observation].goals[goalNumber]) {

      observations[observation].conversion = observations[observation].goals[goalNumber];

    }

  }

}



// Check if Thompson sampling mode is enabled

var isThompsonSampling = observations.conversion_style === 'thompson';



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

  if (observations.hasOwnProperty(key) && key !== 'conversion_style' && key !== 'test_type' && key !== 'test_winner') {

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

      

      // For revenue/AOV tests, use rate instead of raw conversions for the graph

      // BUT only for primary conversion - subgoals use raw conversion counts

      if(abtestChartData.conversion_use_order_value == "1" && !goal) {

        currentConversions = variant.rate || 0;

      }



      // Calculate average daily visits and conversions only if not Thompson sampling

      var avgDailyVisits, avgDailyConversions, projectedVisits, projectedConversions;

      var historicalDataPoints = [

          { x: 0, y: 0 }, // Historical starting point

          { x: currentVisits, y: currentConversions } // Current data point

      ];

      

      var projectedDataPoints = [];

      

      if (!isThompsonSampling && likelyDuration > 0) {

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

      if(likelyDuration > 0 && !isThompsonSampling && projectedDataPoints.length > 0)

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

                  text: (abtestChartData.conversion_use_order_value == "1" && !goal) ? 'Revenue per Visit (cents)' : 'Conversions'

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
  var sharedSettingsSelector = ".show_targeting_options, .show_autocomplete, .webhooks_settings, .restart_test";



  if(jQuery("input:radio[value=\'full_page\']").is(":checked")){

    jQuery("#configuration_settings>div").show();

    jQuery('.show_css_classes').hide(); // hide element css classes helper

    jQuery(".css_test_variations").hide();

    jQuery("#magic_settings").hide();

    jQuery(".show_full_page_test").show();

  }

  else if(jQuery("input:radio[value=\'ab_test\']").is(":checked")){

    jQuery('.show_css_classes').show(); // show element css classes helper

    jQuery("#configuration_settings>div").show(); 

    jQuery("#magic_settings").hide();

    jQuery(".show_full_page_test").hide();

    jQuery(".css_test_variations").hide();

  }

  else if(jQuery("input:radio[value=\'css_test\']").is(":checked")){

    jQuery("#configuration_settings>div").show(); 

    jQuery("#magic_settings").hide();

    jQuery(".css_test_variations").show();    

    jQuery('.show_css_classes').hide(); // show element css classes helper

    jQuery(".show_full_page_test").hide();

  }

  else if(jQuery("input:radio[value=\'magic\']").is(":checked")){

    jQuery("#magic_settings").slideDown();

    jQuery("#configuration_settings>div").hide(); 

    jQuery(".show_test_type, #magic_settings, .test_conversion_styles, .bt_experiments_inner_custom_box, " + sharedSettingsSelector).show();

    jQuery('.show_css_classes').hide(); // show element css classes helper

    jQuery(".show_full_page_test").hide();

  }

  else

  {

    jQuery(".show_test_type").slideDown();

  }

  if(jQuery("input:radio[name='test_type']:checked").length){
    handleConversionStyleChange();
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



function refreshConversionPage(){



  var conv_page = jQuery("#bt_experiments_conversion_page").val();

  

  // Only show preview link when "Page or Post Visit" is selected AND a page is chosen

  if(conv_page === "page") {

    var selectedPageId = jQuery("#bt_experiments_conversion_page_selector").val();

    if(selectedPageId && selectedPageId !== "" && !isNaN(selectedPageId)) {

      jQuery("#bt_experiments_conversion_page_preview").show().attr("href", bt_homeurl + "/?page_id=" + selectedPageId);

    } else {

      jQuery("#bt_experiments_conversion_page_preview").hide();

    }

  } else {

    // Hide for all other conversion types (url, selector, javascript, time, etc.)

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

      description += "<BR>Traffic split examples:";

      var splitamount =  Math.round(percentage/2,0);

      description += "<BR>2 variations: "+ splitamount + "% of your total traffic will see each variation.";

      splitamount =  Math.round(percentage/3,0);

      description += "<BR> 3 variations: "+ splitamount + "% of your total traffic will see each variation.";

      splitamount =  Math.round(percentage/4,0);

      description += "<BR> 4 variations: "+ splitamount + "% of your total traffic will see each variation.";

    }

  // Update the description

  jQuery("#percentage_description").html(description);

}



// Function to handle conversion style changes

function handleConversionStyleChange() {

  var conversionStyle = jQuery('#conversion_style').val();

  if (conversionStyle == 'thompson') {

    jQuery('.show_autocomplete').hide();

  } else {

    jQuery('.show_autocomplete').show();

  }

}



// Initialize conversion style handling on document ready

jQuery(document).ready(function($) {

  // Handle initial load

  var conversionStyle = jQuery('#conversion_style').val();

  if (conversionStyle == 'thompson') 

    jQuery('.show_autocomplete').hide();



  // Handle change events

  $('body').on('change', '#conversion_style', handleConversionStyleChange);







  // onabst-export-data click

  $('body').on('click', '.abst-export-data', function(e) {

    e.preventDefault();

    console.log('exporting data');

    $('.abst-export-data-response').remove();

    var test_id = $(this).attr('test_id');

    jQuery.ajax({

      type: "POST",

      url: window.ajaxurl,

      data: {

        'action': 'abst_export_data',

        'test_id': test_id,

        'nonce': bt_exturl.export_nonce,

      },

      success: function(response){

        console.log(response.data);

        var explain_csv = "<H5>Table Column descriptions:</H5>";

        explain_csv += "<br/><strong>uuid:</strong> unique identifier for each visitor";

        explain_csv += "<br/><strong>type:</strong> type of event. starts as 'visit' and changes to 'conversion' when a conversion is detected";

        explain_csv += "<br/><strong>variation:</strong> variation name";

        explain_csv += "<br/><strong>testId:</strong> test id";

        explain_csv += "<br/><strong>location:</strong> location of event (page ID or URL)";

        explain_csv += "<br/><strong>size:</strong> screen size of device (desktop, tablet, mobile)";

        explain_csv += "<br/><strong>timestamp:</strong> timestamp of last visit/goal/conversion";

        explain_csv += "<br/><strong>goals:</strong> array of goals, by ID. If goal is not set or met, it will be empty.</p>";

        $('.abst-export-data').after('<p class="abst-export-data-response">' + response.data + '</p>' + explain_csv);

      },

      error: function(error){

        console.log('error exporting data');

        console.log(error);

      },

    });

  });



  

});
