var ab_highlight_timer;
var abstSpecialPages = ['woo-order-pay', 'woo-order-received', 'woo', 'javascript', 'edd-purchase', 'surecart-order-paid', 'wp-pizza-is-checkout', 'wp-pizza-is-order-history'];

function bt_highlight(selector){
    if(window.ab_highlight_timer) {
        clearTimeout(window.ab_highlight_timer);
        jQuery('.ab-highlight').removeClass('ab-highlight');
    }
    var elem;
    if(jQuery("#elementor-preview-iframe").length)
    {
      elem = jQuery("#elementor-preview-iframe").contents().find(selector);
      jQuery("#elementor-preview-iframe").contents().find('.ab-highlight').removeClass('ab-highlight');
    }
    else
    {
      elem = jQuery(selector);
      jQuery('.ab-highlight').removeClass('ab-highlight');
    }
   elem.addClass("ab-highlight");
    window.ab_highlight_timer = setTimeout(function(){
        elem.removeClass('ab-highlight');
    },2000);
    if(!isInViewport(elem[0]) && !elem.hasClass('scrollingto')) {
        elem.addClass('scrollingto');
        jQuery('html, body').animate({
            //scroll element to 1/3 down view
            scrollTop: elem.offset().top - window.innerHeight / 3
        }, 600, function() {
            elem.removeClass('scrollingto');
        });
    }

}


jQuery(function(){

// add ai button to pages sao far gb and BB toolbars, need to do the rest
//  jQuery('.edit-post-header-toolbar__left, .fl-builder-bar-actions').append('<button><STRONG>AI</strong></button>')


  if ( self !== top ) // if inside an iframe, then its a preview and we dont want to do things.
    return;

  jQuery('body').on('change blur','[name="bt_click_conversion_selector"], .bt_click_conversion_selector input, [data-setting="bt_click_conversion_selector"]',function(){      
    bt_highlight(jQuery(this).val());
  });

  //if url contains query string abmagic then load magic bar
  if(window.location.search.includes('abmagic'))
    window.abst_magic_bar();
// http://tom.test/?abmagic=1&abiframe=1&text_to_replace=Get%20a%20locker&variations=Reserve%20Your%20Locker%20Now%7CBook%20Your%20Locker%20Instantly%7CSecure%20Your%20Storage%20Spot%20Today
// if text_to_replace and variations are set, then log url decoded values of each
    if(window.location.search.includes('abmagic') && window.location.search.includes('text_to_replace') && window.location.search.includes('variations'))
    {
      setTimeout(function(){
        console.log('Magic bar loaded adding url testvars');
        const urlParams = new URLSearchParams(window.location.search);
        const textToReplace = urlParams.get('text_to_replace');

        // Find the deepest element whose direct text node contains the target string
        function findDeepestElementWithText(root, text) {
            // If this node is a text node and contains the text, return its parent
            if (root.nodeType === Node.TEXT_NODE && root.nodeValue && root.nodeValue.includes(text)) {
                return root.parentElement;
            }
            // If this node is an element, check its children
            if (root.nodeType === Node.ELEMENT_NODE) {
                // Skip admin bar, magic bar, and hidden elements
                if (root.id === 'wpadminbar' || root.id === 'abst-magic-bar' || root.style.display === 'none') {
                    return null;
                }
                
                let found = null;
                for (let i = 0; i < root.childNodes.length; i++) {
                    found = findDeepestElementWithText(root.childNodes[i], text);
                    if (found) return found;
                }
            }
            return null;
        }

        //filter docuemnt body to remove magic bar, admin bar and any other elements that are not visible

        let element = findDeepestElementWithText(document.body, textToReplace);

        if (element) {

            // Always use the orchestrator that guarantees a page-scoped unique selector
            const selector = getUniqueSelector(element);
            if (selector && jQuery(selector).length === 1) {
                selector = scopeSelectorToPage(selector);
            }
            else
            {
                //get the unique selector for the element
                selector = generateShortPath(element);
            }

            //log element 
            console.log('Element:', element, selector);

            //set bg yellow opacity 50%
            element.style.backgroundColor = 'rgba(255, 255, 0, 0.5)';

            //scroll to it

            jQuery('.click-to-start-help').hide();
            jQuery('#variation-picker-container, #variation-editor-container, .abst-goals-column, #abst-magic-bar-start, #abst-targeting-button, .magic-test-name, .winning-mode').slideDown();
            setMagicBar(selector, textToReplace, false, 'text');
            bt_highlight(selector);
            //create a test with the selector
            setTimeout(function(){
                test_title = urlParams.get('test_title');
                if(test_title)
                {
                    jQuery('#abst-magic-bar-title').val(test_title);
                }
            }, 100);
        }
        
    }, 1000);

}
  
  // admin bar test helper...
  var submenus = '';
  var bt_conversion_icon = '<div class="ab-flag-filled"></div>';
  var bt_variation_icon = '<div class="ab-split"></div>';
  var bt_split_test_icon = '<div class="ab-test-tube"></div>';
  var bt_link_icon = '<span class="ab-link"></span>';
  var magicResults = false;
  var conversions = {};

   //clean up conversions
   if(window.bt_conversion_vars)
   {
      jQuery.each(bt_conversion_vars, function(index,value){
        conversions[value.eid] = value;
      });
   }

   if(window.bt_experiments)
   {
    jQuery.each(bt_experiments, function(index,test){
      //each experiment
      let shownPrimary = false; 

      if(!jQuery('[bt-eid="'+index+'"]').length && !conversions[index] && bt_experiments[index].test_type !== 'magic') // if no tests or conversions, then skip er{}
        return;

      //create experiment menu
      
      // magic tests
      if(test.test_type == 'magic' && test.magic_definition && test.magic_definition.length > 0)
      {
        try {
          // Parse the magic_definition if it's a string
          let magicDefs = parseMagicTestDefinition(test.magic_definition);
          
          if(Array.isArray(magicDefs)) {
            // Each magic definition
            // We only need the first item
            magicItem = magicDefs[0];
            if(jQuery(magicItem.selector).length > 0) {
                if(!shownPrimary) {
                  submenus += '<li><a class="ab-item" target="_blank" href="'+bt_adminurl+'post.php?post='+index+'&action=edit">'+bt_split_test_icon+'<strong>'+ test.name +'</strong></a></li>';
                  shownPrimary = true;
                  magicResults = true;
                }
                // Get variation display text
                if (magicItem.variations && magicItem.variations.length > 0) {
                  const varName = ["A","B","C","D","E","F","G","H","I","J","K","L","M","N","O","P","Q","R","S","T","U","V","W","X","Y","Z"];
                  magicItem.variations.forEach((variation, ix) => {
                    let variationText = bt_variation_icon + " Variation " + varName[ix];
                    if(ix == 0)
                      variationText += " (Original)";
                    //generate link_html and preview_url like below
                    let preview_url = new URL(window.location.href);
                    preview_url.searchParams.set('abtid', index);
                    preview_url.searchParams.set('abtv', 'magic-'+ix);
                    let link_html = '<span title="Copy Preview URL" class="ab-copy-link" data-preview="'+preview_url.toString()+'">'+bt_link_icon+'</span>';
                    
                    submenus += '<li><a class="ab-item" magic-eid="' + index + '" magic-index="' + ix + '"> '+ variationText + link_html + '</a></li>';
                  });
                }
              }
          } else {
            console.log('Invalid magic test definition:', magicItem);
          }
        } catch(e) {
          console.log('Error parsing magic test definition:', e);
        }
      } // end magic test
      else
        submenus += '<li><a class="ab-item" target="_blank" href="'+bt_adminurl+'post.php?post='+index+'&action=edit">'+bt_split_test_icon+'<strong>'+ test.name +'</strong></a></li>';

      let variations = [];
      //list test variations
      jQuery('[bt-eid="'+index+'"]').each(function(){
        let spantext = '';
        if(jQuery(this).attr('bt-url'))
          spantext = jQuery(this).attr('bt-url').replace(/^.*\/\/[^\/]+/, '');
        else if(typeof current_page !== 'undefined' && Array.isArray(current_page) && current_page.includes(jQuery(this).attr('bt-variation')))
          spantext = 'Current Page';
        else
          spantext = jQuery(this).attr('bt-variation');

        if( variations.indexOf(jQuery(this).attr('bt-variation')) === -1)
        {
          variations.push(jQuery(this).attr('bt-variation'));
          var preview_url = new URL(jQuery(this).attr('bt-url') || window.location.href);
          preview_url.searchParams.set('abtid', jQuery(this).attr('bt-eid'));
          preview_url.searchParams.set('abtv', jQuery(this).attr('bt-variation'));
          var link_html = '<span title="Copy Preview URL" class="ab-copy-link" data-preview="'+preview_url.toString()+'">'+bt_link_icon+'</span>';
          submenus += '<li><a class="ab-item ab-test" show-css="'+jQuery(this).attr('bt-variation')+'" show-url="' + jQuery(this).attr('bt-url') + '" show-eid="' + jQuery(this).attr('bt-eid') + '" show-variation="' + jQuery(this).attr('bt-variation') + '"> ' + bt_variation_icon + ' <span class="variation-tag-button">' + spantext + '</span>' + link_html + '</a></li>';
        }
      });

      //list conversions 
      if(conversions[index])
      {
        if(conversions[index]['type'] != 'load')
          submenus += '<li><a class="ab-item ab-test test-conversion">'+bt_conversion_icon+' '+ conversions[index]['type'] + " <div class='ab-test-selector'>" + conversions[index]['selector'] + '</div></a></li>';
        else
          submenus += '<li><a class="ab-item ab-test test-conversion">'+bt_conversion_icon+' On page load</a></li>';   
      }
    });

   }

  var bt_variations_found = jQuery('[bt-eid]:not([bt-eid=""])[bt-variation]:not([bt-variation=""])');

  if(bt_variations_found.length || !jQuery.isEmptyObject(conversions) || magicResults)
  { 
    submenus += '<li><a class="ab-item ab-sub-secondary" id="ab-clear-test-cookies">Clear AB Test Cookies</a></li>';

    //add admin bar if not there
    if(!jQuery('#wp-admin-bar-ab-test').length)
      jQuery("#wp-admin-bar-root-default").append('<li id="wp-admin-bar-ab-test" class="menupop"><div class="ab-item ab-empty-item" aria-haspopup="true">'+bt_split_test_icon+'A/B Split Test</div><div class="ab-sub-wrapper"><ul class="ab-submenu"></ul></div></li>');
    
    jQuery("#wp-admin-bar-ab-test ul.ab-submenu").prepend(submenus);
    
    jQuery('#wp-admin-bar-ab-test [show-variation]').click(function(){
      
      var showeid = jQuery(this).attr('show-eid');
      var showevar = jQuery(this).attr('show-variation');
      var showurl = jQuery(this).attr('show-url');

      if(showevar.indexOf('test-css-') != -1)
      {
        let result = showevar.replace(/-\d+$/, '');
        removeTestClasses(jQuery('body'),showeid );
        jQuery('body').addClass(showevar);
      }

      if(showurl != 'undefined')
      {
        var win = window.open(showurl, '_blank');
        if (win) {
            //Browser has allowed it to be opened
            win.focus();
        } else {
            //Browser has blocked it
            alert('Please allow popups to preview full page test pages');
        }
      }

      jQuery('[bt-eid="'+showeid+'"]').removeClass('bt-show-variation');
      jQuery('[bt-eid="'+showeid+'"][bt-variation="'+showevar+'"]').addClass('bt-show-variation');
      bt_highlight('[bt-eid="'+showeid+'"][bt-variation="'+showevar+'"]');
      jQuery('body').trigger('ab-test-setup-complete');
      window.dispatchEvent(new Event('resize')); // trigger a window resize event. Useful for sliders etc. that dynamically resize

    }); // end show variation

    jQuery('#wp-admin-bar-ab-test').on('click','.ab-copy-link',function(e){
      e.preventDefault();
      e.stopPropagation();
      copyText(jQuery(this).data('preview'));
      console.log('copied' + jQuery(this).data('preview'));
    });

    jQuery('#wp-admin-bar-ab-test>a').mouseenter(function(){
      bt_highlight("[bt-eid]");
    });

    jQuery('#wp-admin-bar-ab-test>a').click(function(){
      bt_highlight("[bt-eid]");
    });

    jQuery('.test-conversion').click(function(){
      bt_highlight(jQuery(this).find('.ab-test-selector').text());
    });
    
    jQuery('#ab-clear-test-cookies').click(function(){
      if(confirm("Clear your A/B Split Test cookies?"))
      {
        alert('A/B split test cookies cleared\n\nRefresh your page to see another random variation.');
        //get all experiments on page
        jQuery('[bt-eid]').each(function(){
          abstSetCookie('btab_' + jQuery(this).attr('bt-eid'), '', -1);
        });
      }
    });

    if ( typeof(jQuery.fn.hoverIntent) == 'undefined' )
      !function(I){I.fn.hoverIntent=function(e,t,n){function r(e){o=e.pageX,v=e.pageY}var o,v,i,u,s={interval:100,sensitivity:6,timeout:0},s="object"==typeof e?I.extend(s,e):I.isFunction(t)?I.extend(s,{over:e,out:t,selector:n}):I.extend(s,{over:e,out:e,selector:t}),h=function(e,t){if(t.hoverIntent_t=clearTimeout(t.hoverIntent_t),Math.sqrt((i-o)*(i-o)+(u-v)*(u-v))<s.sensitivity)return I(t).off("mousemove.hoverIntent",r),t.hoverIntent_s=!0,s.over.apply(t,[e]);i=o,u=v,t.hoverIntent_t=setTimeout(function(){h(e,t)},s.interval)},t=function(e){var n=I.extend({},e),o=this;o.hoverIntent_t&&(o.hoverIntent_t=clearTimeout(o.hoverIntent_t)),"mouseenter"===e.type?(i=n.pageX,u=n.pageY,I(o).on("mousemove.hoverIntent",r),o.hoverIntent_s||(o.hoverIntent_t=setTimeout(function(){h(n,o)},s.interval))):(I(o).off("mousemove.hoverIntent",r),o.hoverIntent_s&&(o.hoverIntent_t=setTimeout(function(){var e,t;e=n,(t=o).hoverIntent_t=clearTimeout(t.hoverIntent_t),t.hoverIntent_s=!1,s.out.apply(t,[e])},s.timeout)))};return this.on({"mouseenter.hoverIntent":t,"mouseleave.hoverIntent":t},s.selector)}}(jQuery);
    //add hoverintent for the data
    jQuery('#wp-admin-bar-ab-test').hoverIntent({
        over: function(e){
              jQuery(this).addClass('hover');
        },
        out: function(e){
                jQuery(this).removeClass('hover');
        },
        timeout: 180,
        sensitivity: 7,
        interval: 100
    });
  }
});






function removeTestClasses(element, testId) {
    var classNames = element.attr('class').split(/\s+/);
    jQuery.each(classNames, function (index, className) {
        var regex = new RegExp('test-css-' + testId + '-\\d+');
        if (regex.test(className)) {
            element.removeClass(className);
        }
    });
}

// AI AI AI AI AI AI AI AI AI AI AI AI AI AI AI AI AI AI AI AI 
// AI AI AI AI AI AI AI AI AI AI AI AI AI AI AI AI AI AI AI AI 
// AI AI AI AI AI AI AI AI AI AI AI AI AI AI AI AI AI AI AI AI 



function abai(){
    var script = document.createElement('script');
    script.src = btab_vars.plugins_uri + "js/turndown.js";
    document.head.appendChild(script);
    script.onload = function() {
        console.log('Turndown script loaded to create context AI FORM');
    };

    jQuery.magnificPopup.open({
        items: {
            src: '#ab-ai-form', // can be a HTML string, jQuery object, or CSS selector
            type: 'inline'
        }
    });
}


jQuery(function(){

    jQuery('body',parent.window.document).on('click','#ab-ai, #wp-admin-bar-ab-ai, .ab-ai-launch',function(){
        abai();
    });

    jQuery('body').on('click','.ai-option',function(){
        var theText = jQuery(this).text();
        copyText(theText);
    });

    jQuery('#ab-rewrite-form').on('submit', function(event) {
        event.preventDefault();

        //display loading screen
        jQuery("#result .ai-responses").html('<p>'+loadingMessage()+' may take up to 30 seconds...</p>');
        jQuery('#result').fadeIn();
        jQuery('.ai-loading').show();

        //send request
        callOpenAI('suggestions','#result .ai-responses');
        
    });
    

    jQuery('input[type=radio][name=abaitype]').on('change', function() {
        jQuery("#result .ai-responses").text('submit to see response.');
        jQuery("#ab-ai-submit").show();
        if (this.value == 'suggestions') {
            jQuery('#suggestions-div').show();
            jQuery('#rewrite-div').hide();
        }
        else if (this.value == 'rewrite') {
            jQuery('#suggestions-div').hide();
            jQuery('#rewrite-div').show();
        }
    });

    // add to builders
    setTimeout(function(){ // improve this

        // bb
        jQuery( '.fl-builder-bar-actions', parent.window.document ).append( '<button class="ab-ai-launch fl-builder-button"> AI </button>' );
        
        //  gb
        jQuery('.edit-post-header-toolbar__left').append('<button type="button" data-toolbar-item="true" aria-disabled="false" class="components-button ab-ai-launch has-icon" aria-label="Launch AI"><svg xmlns="http://www.w3.org/2000/svg" x="0px" y="0px" width="15" height="15" viewBox="0 0 24 24"><path d="M 17 2 A 2 2 0 0 0 15 4 A 2 2 0 0 0 16 5.7285156 L 16 7 L 13 7 L 13 5 L 13.001953 3.5546875 A 1.0001 1.0001 0 0 0 12.503906 2.6894531 C 11.787176 2.2732724 10.988534 2.0496274 10.183594 2.0175781 C 9.91528 2.006895 9.6455955 2.0167036 9.3789062 2.0488281 C 8.31215 2.1773261 7.2814338 2.6482536 6.4648438 3.4648438 C 6.1441089 3.7855785 5.8954006 4.1406575 5.6992188 4.515625 L 5.6699219 4.5 C 5.0630052 5.5507072 4.9071497 6.7326156 5.1015625 7.8476562 C 3.2754904 8.8728198 2 10.76268 2 13 C 2 14.819816 2.8864861 16.388036 4.1660156 17.484375 C 4.6408757 20.032141 6.8174874 22 9.5 22 C 10.627523 22 11.683838 21.655029 12.556641 21.070312 A 1.0001 1.0001 0 0 0 13 20.240234 L 13 19 L 13 17 L 16 17 L 16 18.269531 A 2 2 0 0 0 15 20 A 2 2 0 0 0 17 22 A 2 2 0 0 0 19 20 A 2 2 0 0 0 18 18.271484 L 18 16 A 1.0001 1.0001 0 0 0 17 15 L 13 15 L 13 13 L 19.271484 13 A 2 2 0 0 0 21 14 A 2 2 0 0 0 23 12 A 2 2 0 0 0 21 10 A 2 2 0 0 0 19.269531 11 L 13 11 L 13 9 L 17 9 A 1.0001 1.0001 0 0 0 18 8 L 18 5.7304688 A 2 2 0 0 0 19 4 A 2 2 0 0 0 17 2 z M 9.9765625 4.0253906 C 10.323274 4.0594887 10.663125 4.1899373 11 4.3144531 L 11 5 L 11 7.8320312 A 1.0001 1.0001 0 0 0 11 8.1582031 L 11 10.001953 L 10.994141 10.001953 C 10.995487 11.115594 10.11489 11.998654 9.0019531 12 L 9.0039062 14 C 9.7330113 13.999103 10.409972 13.784291 11 13.4375 L 11 15.832031 A 1.0001 1.0001 0 0 0 11 16.158203 L 11 19 L 11 19.544922 C 10.53433 19.775688 10.05763 20 9.5 20 C 7.6963955 20 6.2496408 18.652222 6.0449219 16.904297 A 1.0001 1.0001 0 0 0 5.6445312 16.214844 C 4.6481295 15.482432 4 14.327105 4 13 C 4 11.598815 4.7246346 10.392988 5.8105469 9.6816406 C 6.2276287 10.337914 6.7833892 10.916519 7.5 11.330078 L 8.5 9.5976562 C 7.8796927 9.2396745 7.4474748 8.6957359 7.2089844 8.0820312 A 1.0001 1.0001 0 0 0 7.1855469 8 C 7.0449376 7.6024542 6.9871315 7.1827317 7.015625 6.7695312 C 7.0230903 6.6612728 7.0432757 6.5542362 7.0625 6.4472656 C 7.076659 6.3735269 7.0914327 6.2997442 7.1113281 6.2265625 C 7.1310767 6.1505802 7.1498827 6.0745236 7.1757812 6 C 7.2330841 5.8402322 7.3023195 5.6825058 7.3886719 5.5292969 C 7.5187762 5.2975857 7.6804734 5.0773393 7.8789062 4.8789062 C 8.3733162 4.3844964 8.9892096 4.1023458 9.6269531 4.0273438 C 9.7439583 4.0135832 9.860992 4.0140246 9.9765625 4.0253906 z"></path></svg> AI</button>');
        //  elementor
        //todo
        //  oxy
        //todo

        //  breakdance
        //soon // jQuery('.undo-redo-top-bar-section',window.parent.document).before('<div class="topbar-section topbar-section-bl"><button type="button" class="v-btn v-btn--outlined theme--light elevation-0 v-size--default breakdance-toolbar-button ab-ai-launch" style="height: 37px; margin-left: 3px; margin-right: 3px;"><span class="v-btn__content"> AI </span></button></div>');

        // bricks
        
        //todo

    },2000);
});






/**
 * Copies the specified text to the clipboard.
 * @param {String} text The text to copy.
 */

 function copyText(text) {
    if (!navigator.clipboard) {
    console.info('Cant copy to navigator.clipboard, you are probably on localhost where window.clipboard isnt allowed.');
    return;
}

  navigator.clipboard.writeText(text).then(function() {
    alert('Copied!');
  }, function(err) {
    console.info('Cant copy, you are probably on localhost where window.clipboard isnt allowed. Full error: ', err);
  });
}



async function callOpenAI(abAiType,outputSelector) {

    // send text and ai response required type
    var abAiType = jQuery('input[type="radio"][name="abaitype"]:checked')[0]['value'];
    if(abAiType == 'rewrite')
        var query = jQuery('[name="inputText"]').val().trim();
    else
    {
        var cleanedHtml = getAbPageContent();
         
        query = cleanedHtml;
    }
    sendToOpenAI(query,abAiType,outputSelector);

}

function getAbPageContent(){

    
    var selectorList = [
        '.wp-block-post-content',
        '[itemprop="mainContentOfPage"]',
        '[role="main"]',
        'main',
        '#mainContent',
        'article',
        '.article',
        '.content',
        '#content',
        '.entry-content',
        'body',
        ] 
    var abPageContent = false;
    jQuery.each( selectorList, function( key, selector ) { // run through the selectors until we find one, cant fail with good ol body at the end
        if(jQuery(selector).length)
        {
            abPageContent = jQuery(selector).clone(); // Clone the content to avoid modifying the actual page
            return false; // break loop
        }
    });

    // Remove non understandable things, media, and links
    abPageContent.find('source, header,footer, iframe, #wpadminbar,script,style,#ab-ai-form,meta,script,style,link, #wpadminbar').remove();

    // remove classes and styles from html
    abPageContent.find('*').removeAttr('style').removeAttr('data-*').removeAttr('data-node');
    // remove spaces between html elements
    abPageContent.find('*').contents().filter(function() {
        return this.nodeType === 3 && !/\S/.test(this.nodeValue);
    }).remove();

    // Convert the cleaned content to HTML
    var cleanedHtml = abPageContent.html();

    return cleanedHtml;
}



/**
 * Sends a query to OpenAI and returns the response.
 * @param {string} query - The query to send to OpenAI.
 * @param {string} abAiType - The type of AI to use. (rewrite, suggestions, magic)
 * @param {string} outputSelector - The selector for the output element.
 */
function sendToOpenAI(query,abAiType,outputSelector) {
    if(btab_vars.abst_disable_ai == '1'){
        console.log('ABST AI is disabled');
        return;
    }

    jQuery("#ai-suggestions").slideDown();
    if(!jQuery("#ai-suggestions p.ai-loading").length) 
        jQuery("#ai-suggestions").append('<p class="ai-loading">Generating AI Suggestions ✨</p>');

    context = getAbPageContent();

    //convert to markdown w turndown
    var turndownService = new TurndownService();
    var markdown = turndownService.turndown(context);
    query = query.trim();

    if (!window.abmagic) window.abmagic = {};
    if (!window.abmagic.aicache) window.abmagic.aicache = {};

    var aicachekey = abAiType + '_' + query;

    //check for magic cache
    if( window.abmagic.aicache[aicachekey])
    {
        show_magic_ai(window.abmagic.aicache[aicachekey]);
        return;
    }
    aidata = {
        'action': 'send_to_openai',
        'input_text': query,
        'type': abAiType,
        'title': abAiType,
        'context':markdown,
        'domain': btab_vars.domain,
    };
    if(window.abaiScreenshot)
        aidata['screenshot'] = window.abaiScreenshot;

    //otherwise get it
    jQuery.ajax({
        url: bt_ajaxurl,
        type : 'post',
        data : aidata,
        success: function( response ) {
            if(response && typeof response.error !== 'undefined')
            {
                if(typeof response.error.message !== 'undefined')
                {
                    jQuery(outputSelector).parent().empty().html('<small><strong>' + response.error.message + '</strong></small>');
                }
                else
                {
                    jQuery(outputSelector).parent().empty().html('<small><strong>' + response.error + '</strong></small>');
                }
            }
            else
            {
                console.log(response.choices[0]['message']['content']);
                var outt = '';
                if(abAiType == 'suggestions')
                {
                    //trim the content before the first square bracket and after the last square bracket
                    respo = response.choices[0]['message']['content'];
                    // remove ```json
                    respo = respo.replace(/```json/g, '');
                    // remove ```
                    respo = respo.replace(/```/g, '');

                    var ideas = JSON.parse(respo);

                    console.log(ideas);
                    //remove ```json and ``` from response
                    outt += "<h3>CRO Page Score: " + ideas.overall_page_rating + "%</h3><h4> You should consider adding:</h4><p> " + ideas.missing_content +"</p>";
                    jQuery.each(ideas.suggestions,function(index, content){
        
                        outt += "<div class='ai-option'><h4>" +  content.test_name + "</h4><p> " + content.reason_why +"</p><p>Original text:<BR><strong>" + content.original_string + "</strong></p><p>Suggestions:</p>";
                        jQuery.each(content.suggestions,function(index, suggestion){
        
                            outt += "<p class='ai-suggestion-item'>" + suggestion + "</p>";
                        });
                        outt += "</div>";
                    });
                }
                else if(abAiType == 'magic')
                {
                    window.abmagic.aicache[aicachekey] = response.choices[0]['message']['content'];
                    //console.log('allcache',window.abmagic.aicache);
                    //dont do if #imageSelector is visible
                    if(!jQuery('#imageSelector').is(':visible'))
                    {
                        show_magic_ai(response.choices[0]['message']['content']);
                    }
                }
                else
                {
                    suggestions = JSON.parse(response.choices[0]['message']['content']);
                    jQuery.each(suggestions.suggestions,function(index, choice){
                        outt += "<div class='ai-option'>" + choice +"</div>";
                    });                  
                }

                if(abAiType == 'test-idea')
                {
                    testIdeaResponse = JSON.parse(response.choices[0]['message']['content']);
                    testIdeasOut  = '<p>Generating ideas that will: ' + query + '</p>';
                    testIdeasOut += '<p><strong>' + testIdeaResponse.response + '</strong></p>';
                    jQuery.each(testIdeaResponse.ideas,function(index, choice){
                        testIdeasOut += '<div class="test-ideas-suggestion">';
                        testIdeasOut += "<h4>" + choice.testtitle +"</h4>";
                        testIdeasOut += "<p><strong>" + choice.theorytitle +" </strong> " + choice.theory +"</p>";
                        jQuery.each(choice.elements,function(index, element){
                            testIdeasOut += "<div class='test-element'>";
                            testIdeasOut += "<p>" + element.original +"</p>";
                            testIdeasOut += "<p>" + element.variations.join(', ') +"</p>";
                            testIdeasOut += "</div>";   
                        });
                        testIdeasOut += "<button class='ai-create-test'>Create Test</button></div>";
                    });
                    jQuery(outputSelector).html(testIdeasOut);
                    
                }
                else if(abAiType != 'magic')
                {
                    jQuery(outputSelector).html(outt);
                }
                jQuery('.ai-loading').hide();
            }
        }
    });
}

function show_magic_ai(response){
    outt='';
    jQuery("#ai-suggestions-list").empty();
    suggestions = JSON.parse(response).suggestions;
    //console.log(suggestions);
    jQuery.each(suggestions,function(index, choice){
        outt += "<li class='ai-suggestion-item'>" + choice + "</li>";
    });
    jQuery("#ai-suggestions p").remove();
    jQuery("#ai-suggestions").prepend('<p>AI Suggestions ✨ <small>Click to add to test variations & edit.</small></p>').slideDown(1000);
    //console.log(outt);
    jQuery("#ai-suggestions-list").html(outt);
    jQuery("#ai-suggestions-list").slideDown(1000);
}


function loadingMessage(){

    var loadingMessages = [
    "Loading, please wait...",
    "Fetching unicorns from the cloud...",
    "Initiating data transfer...",
    "Preparing to dazzle you...",
    "Calculating the meaning of life...",
    "Polishing up the pixels...",
    "Preparing to amaze you...",
    "Transmitting awesomeness...",
    "Crafting your results...",
    "Preparing for blast off...",
    "Assembling the pieces...",
    "Elevating your experience...",
    "Generating quantum states...",
    "Refining your results...",
    "Transforming data into gold...",
    "Accelerating electrons...",
    "Integrating over functions...",
    "Deconstructing the code...",
    "Tuning the engine...",
    "Synchronizing the clocks...",
    "Translating into ones and zeros...",
    "Archiving history...",
    "Empowering your experience...",
    "Conducting the orchestra...",
    "Compiling the modules...",
    "Scaling the heights...",
    "Breaking the sound barrier...",
    "Hyperspace travel engaged...",
    "Charging the capacitors...",
    "Aligning the planets...",
    "Distributing the load...",
    "Mapping the unknown...",
    "Decompressing the data...",
    "Filtering out the noise...",
    "Quantum entangling particles...",
    "Deciphering the glyphs...",
    "Optimizing the algorithm...",
    "Defragging the disk...",
    "Unleashing the power...",
    "Leveraging the network...",
    "Expanding the universe...",
    "Ruling out the impossible...",
    "Crunching the numbers...",
    "Synthesizing reality...",
    "Solving the puzzle...",
    "Unearthing the truth...",
    "Breaking the deadlock...",
    "Refining the solution...",
    ];

    return loadingMessages[Math.floor(Math.random()*loadingMessages.length)];
}


/*



jQuery('.fl-builder-bar-actions').append('<button class="ab-ai-launch fl-builder-button"> AI </button>');



*/












/**
 * ABST Magic Bar
 * Displays a modal at the top of the website and pushes down content
 */


function selectorDetection(){
    
    if(jQuery('body').hasClass('abst-selector-detection'))
        return; // only once
    
    jQuery('body').css('pointer-events', 'auto').addClass('abst-selector-detection');
    jQuery('img').css('pointer-events', 'auto');
    // Create the  box elements once, outside the event handlers
    var box = jQuery('<div id="selector-box"></div>');
    jQuery('body').append(box);

    // Track the current element being hovered
    var currentElement = null;
    var hoverTimer = null;

    // Add a debounce mechanism at the start of your code
    var selectorHoverDebounce = null;
    var lastProcessedElement = null;

    jQuery('body').on('blur','#abst-selector-input',function(){
        console.log('blur');
        var $inputElement = jQuery(this); // Store jQuery object for the input element

        if(!$inputElement.attr('lastvalue')) {
            $inputElement.attr('lastvalue', $inputElement.val());
        }

        var newSelectorValue = $inputElement.val(); // Get the new selector value
        var oldSelector = $inputElement.attr('lastvalue');
        console.log('oldSelector', oldSelector);
        console.log('newSelectorValue', newSelectorValue);

        if(oldSelector !== newSelectorValue) {
            $inputElement.attr('lastvalue', newSelectorValue);
            
            // Use getElementIndexFromMagic for consistent element identification
            var elementIndex = getElementIndexFromMagic(newSelectorValue);
            console.log('elementIndex from blur handler:', elementIndex);
            
            if(elementIndex !== -1) {
                if(newSelectorValue == ''){
                    // Reset the element content to original
                    jQuery(oldSelector).html(window.abmagic.definition[elementIndex].variations[0]);
                    window.abmagic.definition.splice(elementIndex, 1); // Remove the definition if the selector is empty
                    console.log('removed', oldSelector);
                    console.log(window.abmagic.definition);
                    
                    // Update editor without triggering events
                    if (window.abstEditor) {
                        window.abstEditor.innerHTML = '';
                    }
                    jQuery('#ai-suggestions-list').hide().empty();
                }
                else if (window.abstEditor) {
                    // Update the definition with the new selector
                    //remove oldSelector from window.abmagic.definition
                    window.abmagic.definition[elementIndex].selector = newSelectorValue;
                    
                    // Set content in the contentEditable editor without triggering events
                    window.abstEditor.innerHTML = jQuery(newSelectorValue).html() || '';
                    console.log('updated ', newSelectorValue);
                }
                
                // Refresh all variation classes after all data updates are complete
                refreshVariationClasses();
            }
        }
    });

    jQuery('body').on('click','#abst-idea-button',function(e){
        e.preventDefault();
        var prompt = jQuery('#abst-idea-input').val();
        sendToOpenAI(prompt, 'test-idea', '.prompt-response .results');        
        jQuery('.prompt-response').slideDown();
        jQuery('.click-to-start-help').slideUp();

    });

    jQuery('body').on('click','.abst-variation',function(e){
        //abst-selector-input 
        e.preventDefault();
        var selector = getUniqueSelector(jQuery(this).first()[0]);
        jQuery('#abst-selector-input').val(selector).trigger('blur');
        
    });

    jQuery('body').on('contextmenu','.abst-variation',function(e){
        e.preventDefault();
        //abst-selector-input 
        if(!confirm('Are you sure you want to remove this element from this test?'))
            return;

        deleteItem = jQuery(this).first();

        //find the unselector in the window.abmagic.definition
        window.abmagic.definition.forEach(function(value, key) {
            
            console.log(value['selector']);
            //if deleteitem is the same element as jquery(value['selector']) selector element
            if (deleteItem.is(value['selector'])) {
                console.log('found',value['selector']);
                console.log(value.variations[0]);
                // Use Pell editor API instead of TinyMCE
                if (window.abstEditor) {
                    window.abstEditor.innerHTML = value.variations[0];
                    // Trigger input event to mark as changed
                    const event = new Event('input', { bubbles: true });
                    window.abstEditor.dispatchEvent(event);
                }
                jQuery("#abst-selector-input").val('').trigger('blur');
                //hide ai suggestions
                jQuery('#ai-suggestions').slideUp();
                //remove from window.abmagic.definition
                console.log('removed',value['selector']);
                console.log(window.abmagic.definition);
                //if its an image then set src to 0
                if (value.type === 'image') {
                    deleteItem.attr('src',value.variations[0] );
                }
                else{
                    deleteItem.html(value.variations[0]);
                }
                // Refresh all variation classes after removing the item
                setTimeout(function() {
                    refreshVariationClasses();
                }, 50);
                window.abmagic.definition.splice(key, 1);
                return;
            }
        });
    });

    function canTestOnElement(element){

        //if an element isnt an element, but a string, get it by query selector
        if(typeof element === 'string')
            element = document.querySelector(element);

        if(!element)
            return false;

        //dont show if on the abst-magic-bar or any parent is abst-magic-bar
        if(element.id !== 'abst-magic-bar') {
            // Check all parents for the ID
            var parent = element.parentElement;
            while(parent) {
                if(parent.id == 'abst-magic-bar')
                    return false;
                parent = parent.parentElement;
            }
        }

        // Skip if hovering over the box or their children
        if (element.id === 'selector-box' || jQuery.contains(document.getElementById('selector-box'), element)) {
            return false;
        }

        //dont do if parents or childeren contain class abst-variation
        if (jQuery(element).parents('.abst-variation').length > 0 || jQuery(element).children('.abst-variation').length > 0) {
            return false;
        }
        
        // If we're already showing for this element, don't do anything
        if (jQuery(jQuery("#abst-selector-input").val())[0] === element)
            return false;
        
        if(jQuery(element).parents('.media-modal.wp-core-ui').length > 0)//not inside media modal
            return false;

        if(!jQuery('#abst-magic-bar').is(':visible'))
            return false;

        // dont do for #wpadminbar parent or .mce-panel
        if (jQuery(element).parents('.mce-panel').length > 0 || jQuery(element).parents('#wpadminbar').length > 0 || jQuery(element).parents('.abst-variation').length > 0) {
            return false;
        }

        
        //no gates hit
        return true;
    }

jQuery('body').on('mouseover', function(e){
    // Clear any existing debounce timer
    if (selectorHoverDebounce) {
        clearTimeout(selectorHoverDebounce);
    }
    
    // Set a small delay before processing
    selectorHoverDebounce = setTimeout(function() {

        
        showBar = true;

        var element = e.target;
        
        if(!canTestOnElement(element))
        {
            box.hide();
            return;
        }
    
        //filters
        var elementType = false;
        
        currentElement = element;
        var selector = getUniqueSelector(element);



        //fin if elementr has inntertext or is an image
        if(element.tagName == 'IMG' || element.tagName == 'SVG')
            elementType = 'image';

        // Check if element has direct text (not just from child elements)
        var hasDirectText = false;
        for (var i = 0; i < element.childNodes.length; i++) {
            var node = element.childNodes[i];
            if (node.nodeType === 3 && node.textContent.trim() !== '') { // Text node
                hasDirectText = true;
                break;
            }
        }

        // Check for text elements that typically contain direct text content
        var textTags = ['P', 'H1', 'H2', 'H3', 'H4', 'H5', 'H6', 'SPAN', 'A', 'BUTTON', 'LABEL', 'LI', 'TD', 'TH', 'STRONG', 'EM', 'B', 'I'];
        if (hasDirectText || (textTags.includes(element.tagName) && element.textContent && element.textContent.trim() !== '')) {
            elementType = 'text';
        }

        //if element has bg img
//        if(element.style.backgroundImage && element.style.backgroundImage !== 'none')
  //          elementType = 'bgimage';

        if(!elementType)
            return;

        // Clear any existing timer
        if (hoverTimer) {
            clearTimeout(hoverTimer);           
            hoverTimer = null;
        }
        
        // Get position of element to display a box around
        var rect = element.getBoundingClientRect();
        box.css({
            zIndex: '158000',
            top: rect.top + window.scrollY - 10,
            left: rect.left + window.scrollX - 10,
            width: rect.width + 20,
            height: rect.height + 20,
            borderRadius: '10px'
        });
        box.show();
        // Improved element comparison to avoid flickering
        if (lastProcessedElement === element) {
            return;
        }
        
        lastProcessedElement = element;

        
        }, 50); // Small delay to debounce
    });
        
    // Add a mouseleave handler to hide the tooltip and box when leaving the element
    jQuery('body').on('mouseout', function(e) {
        if (jQuery(e.relatedTarget).closest(jQuery(this)).length === 0) {
            // Only hide when truly leaving the element (not entering a child)
            if (hoverTimer) {
                clearTimeout(hoverTimer);
            }
            
            // Add a small delay before hiding
            hoverTimer = setTimeout(function() {
                box.hide();
                lastProcessedElement = null;
            }, 150);
        }
    });
    window.magicLastFocus = null;


    
    // add element to magic bar click function
    jQuery('body').on('click', function(e){
    
        e.preventDefault();
        e.stopImmediatePropagation();
        
        var element = e.target;


        if(!canTestOnElement(element)){
            console.log('cant test on', element);
            return;
        }

        //check if element is clickable using our helper function
        var elementType = getElementType(element);
        if(!elementType)
            return;
        
        jQuery('#variation-picker-container, #variation-editor-container, .abst-goals-column, #abst-magic-bar-start, #abst-targeting-button, .magic-test-name, .winning-mode').slideDown();
        jQuery('.click-to-start-help').slideUp();

    
        var selector = getUniqueSelector(element);
    
        //modes image or text
        //if its an image
        if(e.target.tagName == 'IMG')
        {
            //get unique selector for this page
            width = jQuery(element).width();
            height = jQuery(element).height();
            setMagicBar(selector, jQuery(element).attr('src'),false, 'image');
        }
        else if(e.target.tagName == 'SVG')
        {   
            width = jQuery(element).width();
            height = jQuery(element).height();
            var imgSrc = jQuery(element).attr('src');
            //remove srcset so its not broken
            jQuery(element).removeAttr('srcset');
            jQuery(element).attr('alt', 'SWAPPED TEXT STRING');
            setMagicBar(selector, imgSrc,false, 'image');
            setTimeout(function(){
                jQuery(element).attr('src', imgSrc);
            }, 800);
        }
        else
        {
            if(jQuery(selector).text() != '')
            {
                sText = jQuery(selector).html();
                //create newtext that is the same number of chars s current text
                setMagicBar(selector, sText);
            }
            else
            {
                console.log('no text');
            }
            //select and swap
        }
        window.magicLastFocus = element;
    });

}



// Prevent all link clicks if .doing-abst-magic-bar is present on body
document.body.addEventListener('click', function(e) {
    if (document.body.classList.contains('doing-abst-magic-bar')) {
        let target = e.target;
        // Allow clicks on #wpadminbar and #abst-magic-bar (and their children)
        if (
            (target.closest && target.closest('#wpadminbar')) ||
            (target.closest && target.closest('#abst-magic-bar'))
        ) {
            return; // Allow normal behavior
        }
        // Traverse up in case the click is on a child inside the link
        while (target && target !== document.body) {
            if (target.tagName && target.tagName.toLowerCase() === 'a') {
                console.log('preventing click because we\'re in test setup mode');
                e.preventDefault();
                e.stopImmediatePropagation();
                return false;
            }
            target = target.parentElement;
        }
    }
}, true);

// Helper function to determine if an element is clickable for magic testing
function getElementType(element) {
    if (!element) return false;
    
    var elementType = false;
    
    // Check for images
    if(element.tagName == 'IMG' || element.tagName == 'SVG')
        elementType = 'image';
    
    // Check if element has direct text (not just from child elements)
    var hasDirectText = false;
    for (var i = 0; i < element.childNodes.length; i++) {
        var node = element.childNodes[i];
        if (node.nodeType === 3 && node.textContent.trim() !== '') { // Text node
            hasDirectText = true;
            break;
        }
    }
    
    // Check for text elements that typically contain direct text content
    var textTags = ['P', 'H1', 'H2', 'H3', 'H4', 'H5', 'H6', 'SPAN', 'A', 'BUTTON', 'LABEL', 'LI', 'TD', 'TH', 'STRONG', 'EM', 'B', 'I'];
    if (hasDirectText || (textTags.includes(element.tagName) && element.textContent && element.textContent.trim() !== '')) {
        elementType = 'text';
    }
    
    return elementType;
}

function setMagicBar(selector, selectorText, goal = false, type = 'text') {
    if(!window.abmagic) window.abmagic = {};
    if(!window.abmagic.definition) window.abmagic.definition = [];
    
    // Check if we have an active element for goal input
    if(window.magicLastFocus && (window.magicLastFocus.className.includes('abst-goal-input-value'))) {
        console.log('goal input value');
        // Set value if confirmed
        if(window.magicLastFocus.value !== '') {
            if(!confirm('This will replace the current goal selector. Are you sure?')) {
                return;
            }
        }
        window.magicLastFocus.value = selector;
        return;
    }

    // Check if selector is specific enough
    if(jQuery(selector).length > 2) {
        console.log('Selector has more than 2 results, please choose a more specific selector, or click the element and we\'ll do it for you.');
        return;
    }

    jQuery("#ai-suggestions-list").hide();
    jQuery('#abst-selector-input').val(selector).trigger('blur');
    
    jQuery("#abst-variation-editor-container").addClass('flash');
    setTimeout(function(){
        jQuery("#abst-variation-editor-container").removeClass('flash');
    }, 2000);
    
    jQuery('#ai-suggestions').hide();
    
    if(goal) {
        jQuery('#abst-goal').val(goal).trigger('change');
    }
    //CHANGE HEIGHT OF EDITOR CONTAINER TO 10 LINES
    jQuery('#abst-variation-editor-container').height(150);
    // Set the content in the editor
    if (window.abstEditor) {
        // Skip if we're already updating to prevent loops
        
        try {
            const content = selectorText || '';
            
            // Set the content directly in the editor
            if (type === 'image') {
                // For images, we want to show the image URL in the editor
                window.abstEditor.textContent = content;
                // Trigger input event to mark as changed
                const event = new Event('input', { bubbles: true });
                window.abstEditor.dispatchEvent(event);
            } else {
                // For text content, use innerHTML
                window.abstEditor.innerHTML = content;
                // Trigger input event to mark as changed
                const event = new Event('input', { bubbles: true });
                window.abstEditor.dispatchEvent(event);
            }
            
            // Update the hidden input
            jQuery('#abst-variation-editor').val(content);
            if (type === 'text') {
                // Show AI suggestions if it's text content
                urlParams = new URLSearchParams(window.location.search);
                //if url var text_to_replace == url textToReplace then add variations from URL to 
                if(urlParams.get('text_to_replace') && urlParams.get('variations')){
                    console.log('MAGIC TEST DEF IN URL PARAMS, building suggestions');
                    suggestions = [];
                    var variations = urlParams.get('variations');
                    variations = variations.split('|');
                    for(var i = 0; i < variations.length; i++){
                        jQuery("#ai-suggestions-list").append('<li class="ai-suggestion-item">' + variations[i] + '</li>');
                    }
                    setTimeout(function(){
                        jQuery("#ai-suggestions, #ai-suggestions-list").slideDown(400); // Fix AI suggestions display issue by ensuring proper slideDown animation
                    }, 1000);
                    //remove url params text_to_replace and variations
                //    urlParams.delete('text_to_replace');
                //    urlParams.delete('variations');
                //    urlParams.delete('test_title');
                    //update url
                    window.history.replaceState(null, '', window.location.pathname + '?' + urlParams.toString());
                }
                else{
                    console.log('showing AI suggestions');
                    sendToOpenAI(content, 'magic', '#ai-suggestions-list');
                }
                jQuery('#imageSelector').slideUp();
                // Show our custom toolbar
                jQuery('.abst-editor-toolbar').show();
            }
        } catch (error) {
            console.error('Error in setMagicBar:', error);
        } 
    }

    if(type === 'image') { 
        jQuery('#ai-suggestions').slideUp();
        jQuery('.abst-editor-toolbar').hide();

        //CHANGE HEIGHT OF EDITOR CONTAINER TO 2 LINES
        jQuery('#abst-variation-editor-container').height(70);
        
        // Set the image in the preview
        jQuery(selector).attr('src', selectorText).removeAttr('srcset');
        
        // Add image selector button if it doesn't exist
        if(jQuery("#imageSelector").length < 1) {
            jQuery("#abst-variation-editor-container").after('<button type="button" id="imageSelector">Choose from Media Library</button>');
            jQuery("#imageSelector").on('click', function(){
                file_frame.open();
            });
        }
        else{
            jQuery("#imageSelector").show();
        }
        
        // Check if we have a file frame already
        if (typeof file_frame === 'undefined') {
            file_frame = wp.media({
                title: 'Select or Upload an Image',
                button: {
                    text: 'Use this image',
                },
                multiple: false
            });
        }

        file_frame.off('select');
        file_frame.on('select', function() {
            const attachments = file_frame.state().get('selection').first().toJSON();
            
            // Update the editor with the image URL
            if (window.abstEditor) {
                window.abstEditor.textContent = attachments.url;
                checkChangedEditor();
            }
            
            // Update the preview
            jQuery(selector).attr('src', attachments.url).removeAttr('srcset').addClass('abst-variation');
            
            // Update the variation data
            const variationIndex = parseInt(jQuery("#variation-picker").val(), 10);
            const elementDef = window.abmagic.definition.find(def => def.selector === selector);
            if (elementDef) {
                elementDef.variations[variationIndex] = attachments.url;
            }
            
            // Update the hidden input
            jQuery('#abst-variation-data').val(JSON.stringify(window.abmagic.definition));
        });
    }
    
    // Check if we already have this selector in our definitions
    const existingDefinition = window.abmagic.definition.find(def => def.selector === selector);
    const variationIndex = parseInt(jQuery("#variation-picker").val(), 10);
    
    if (existingDefinition) {
        // If we have a definition but not this variation, initialize it with the first variation's content
        if (!existingDefinition.variations[variationIndex] && existingDefinition.variations[0]) {
            existingDefinition.variations[variationIndex] = existingDefinition.variations[0];
        }
        
        // Update the editor with the current variation's content
        const currentVariation = existingDefinition.variations[variationIndex] || '';
        if (currentVariation && window.abstEditor) {
            window.abstEditor.innerHTML = currentVariation;
        }
    }
}
window.abstIgnoreSelectorPrefixes = ['abst-variation','stk-'];
/**
 * Checks if a class or ID should be ignored based on a single global prefix list.
 * 
 * - Entries starting with '.' are class prefixes.
 * - Entries starting with '#' are ID prefixes.
 * - Entries with no prefix are treated as class prefixes (for back-compat).
 */
function isIgnored(type, value) {
    if (!value) return false;
    if (type === 'class' && value === 'abst-variation') return true; // Always ignore our own marker

    const prefixes = window.abstIgnoreSelectorPrefixes || [];
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

function getUniqueSelector(element) {
    // If not an element, return null
    if (!(element instanceof Element)) return null;
    
    // Prefer ID if present and uniquely resolves (after scoping)
    if (element.id && !isIgnored('id', element.id)) {
        const byId = scopeSelectorToPage('#' + element.id);
        if (jQuery(byId).length === 1) {
            return byId;
        }
        // If ID isn't unique (rare), continue to try other strategies
    }

    // Try to find a short, human-friendly selector (classes/attributes)
    const tag = element.tagName.toLowerCase();

    // 1) Try tag + a single class (rarer classes first)
    if (element.classList && element.classList.length > 0) {
        const classes = Array.from(element.classList)
            .filter(cls => !isIgnored('class', cls))
            .sort((a, b) => {
                const aCount = jQuery('.' + a).length;
                const bCount = jQuery('.' + b).length;
                return aCount - bCount;
            });

        for (const cls of classes) {
            const candidate = tag + '.' + cls;
            const scoped = scopeSelectorToPage(candidate);
            if (jQuery(scoped).length === 1) {
                return scoped;
            }
        }

        // 2) Try tag + two classes
        if (classes.length >= 2) {
            for (let i = 0; i < classes.length - 1; i++) {
                for (let j = i + 1; j < classes.length; j++) {
                    const candidate = tag + '.' + classes[i] + '.' + classes[j];
                    const scoped = scopeSelectorToPage(candidate);
                    if (jQuery(scoped).length === 1) {
                        return scoped;
                    }
                }
            }
        }
    }

    // 3) Try a single important attribute
    const importantAttrs = ['href', 'alt', 'title', 'name', 'value', 'type', 'role'];
    for (const attrName of importantAttrs) {
        if (element.hasAttribute && element.hasAttribute(attrName)) {
            const raw = element.getAttribute(attrName);
            if (raw && raw.length < 100) {
                const candidate = tag + '[' + attrName + '="' + raw.replace(/"/g, '\\"') + '"]';
                const scoped = scopeSelectorToPage(candidate);
                if (jQuery(scoped).length === 1) {
                    return scoped;
                }
            }
        }
    }
    
    // Fallback to the shortest structural path (already page-scoped internally)
    return generateShortPath(element);
}

// getSimpleSelector has been inlined into getUniqueSelector to simplify the API and ensure a single
// entry point for selector resolution.

// Generate a short path to the element
// Utility: Get the current page class (e.g., postid-123 or page-id-456)
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
        
        // Add a class if it helps make it more specific but not too specific
        if (current.classList.length > 0) {
            // Find the most specific useful class
            for (const cls of current.classList) {
                if (isIgnored('class', cls)) continue;
                const testSelector = tag + '.' + cls;
                const matches = jQuery(testSelector, current.parentNode).length;
                if (matches === 1) {
                    selector = testSelector;
                    break;
                }
            }
        }
        
        // If we still don't have a unique selector at this level, add nth-child
        if (jQuery(selector, current.parentNode).length > 1) {
            const index = Array.from(current.parentNode.children).indexOf(current) + 1;
            selector += ':nth-child(' + index + ')';
        }
        
        path.unshift(selector);
        current = current.parentNode;
        
        // Check if our path is already unique
        
        const testPath = path.join(' > ');
        if (jQuery(testPath).length === 1) {
            // Scope the selector to the current page
            return scopeSelectorToPage(testPath);
        }
    }
    // Scope the selector to the current page
    return scopeSelectorToPage(path.join(' > '));
}

async function takeScreenshot(node, options, retryCount = 0) {
    const maxRetries = 3;

    try {
        // Use global modernScreenshot object from UMD build
        const dataUrl = await modernScreenshot.domToPng(node, options);
        console.log('Screenshot generated successfully');
        //log the image from dataurl to actual console image
        //const img = new Image();
        //img.src = dataUrl;
        //document.body.appendChild(img);
        window.abaiScreenshot = dataUrl;
    } catch (error) {
        console.error('Error generating screenshot:', error);
        if (retryCount < maxRetries) {
            console.log(`Retrying screenshot (attempt ${retryCount + 1}/${maxRetries})`);
            setTimeout(() => takeScreenshot(node, options, retryCount + 1), 1000);
        } else {
            console.error('Max retries reached, giving up on screenshot');
        }
    }
}

function updateScreenshot() {
    if (window.abTakingScreenshot) {
        console.log('Screenshot already in progress, skipping update');
        return;
    }
    console.log('Updating screenshot................................................');
    // Screenshot options with performance optimizations
      
    const options = {

        quality: 0.8,  // Reduce quality for faster processing
        width: Math.min(window.innerWidth, 1000),  // Cap width to reduce processing
        height: Math.min(window.innerHeight, 3000), // Cap height to reduce processing
        debug: true,
        timeout: 4000,
        // Performance optimizations to reduce wait time
        skipAutoScale: true,       // Skip auto scaling
        fetchRequestTimeout: 3000, // 3 second timeout for resources
        imagePlaceholder: 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMSIgaGVpZ2h0PSIxIiB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciPjxyZWN0IHdpZHRoPSIxMDAlIiBoZWlnaHQ9IjEwMCUiIGZpbGw9IiNjY2MiLz48L3N2Zz4='
    };

    // Find the element to screenshot
    const node = document.getElementById('page-container') ||
                 document.getElementById('page') ||
                 document.querySelector('main') ||
                 document.querySelector('.site-content') ||
                 document.body;

            

    if (!node) {
        console.error('No suitable element found for screenshot');
        return;
    }

    jQuery('#admin-bar').addClass('admin-bar');
    const filter = (node) => {
        const exclusionClasses = ['abst-magic-bar','admin-bar'];
        return !exclusionClasses.some((classname) => node.classList?.contains(classname));
    }

    options.filter = filter;

    // Check if the modern-screenshot library is loaded
    if (typeof modernScreenshot !== 'undefined') {
        window.abTakingScreenshot = true;
        takeScreenshot(node, options);
        return;
    } else {
        console.log('modernScreenshot not loaded yet, retrying in 500ms');
        setTimeout(updateScreenshot, 500);
    }
}


function abst_magic_bar(options = {}) {

    // Load dom-to-image if not already loaded and ai is enabled
    if(!window.abaiScreenshot && btab_vars.abst_disable_ai != '1') {
        updateScreenshot();
        setTimeout(function(){
            abst_magic_bar(options);
        }, 500);
    }

    selectorDetection();
    // Default options
    const defaults = {
        height: '250px',
        backgroundColor: '#f5f5f5',
        borderColor: '#ddd',
        content: '',
        closeButton: true,
        animation: true,
        onOpen: null,
        onClose: null,
        // Default values for the form elements
        selector: '.selector',
        goal: 'Woo Order Received',
        versionA: 'Test anything WordPress',
        versionB: 'Helping WordPress business sell more',
        versionC: 'Your private testing cloud'
    };

    // Merge defaults with user options
    const settings = Object.assign({}, defaults, options);

    // Check if the magic bar already exists
    if (document.getElementById('abst-magic-bar')) {
        return;
    }

    // Create the magic bar element
    const magicBar = document.createElement('div');
    magicBar.id = 'abst-magic-bar';
    magicBar.className = 'abst-magic-bar';
    
    // Add no-animation class if animation is disabled
    if (!settings.animation) {
        magicBar.classList.add('no-animation');
    }

    var rolesHtml = '';
    //    foreach abst_magic_data.roles
    jQuery.each(abst_magic_data.roles, function(key, value) {
        //if key in array abst_magic_data.defaults
        if (abst_magic_data.defaults.includes(key))
            rolesHtml += '<div class="abst-user-role"><input type="checkbox" name="roles[]" value="' + key + '" checked> ' + value + '</div>';
        else
            rolesHtml += '<div class="abst-user-role"><input type="checkbox" name="roles[]" value="' + key + '"> ' + value + '</div>';
    });
    // Add content
    // --- BEGIN MAGIC BAR HTML REPLACEMENT ---
    // Build dynamic user roles
    var rolesHtml = '';
    jQuery.each(abst_magic_data.roles, function(key, value) {
        if (abst_magic_data.defaults && abst_magic_data.defaults.includes(key))
            rolesHtml += '<div class="abst-user-role"><input type="checkbox" name="roles[]" value="' + key + '" checked> ' + value + '</div>';
        else
            rolesHtml += '<div class="abst-user-role"><input type="checkbox" name="roles[]" value="' + key + '"> ' + value + '</div>';
    });

    var variationOptions = [
        {  label: 'A Version - Control', selected: false, value: 0 },
        { label: 'B Version', selected: true, value: 1 },
        { label: '+ Add Version', selected: false, value: 'addAnother' },
    ];
    var variationOptionsHtml = variationOptions.map(function(opt, index) {
        return '<option value="' + opt.value + '" ' + (opt.selected ? 'selected' : '') + '>' + opt.label + '</option>';
    }).join('');

    magicBar.innerHTML = `
    <div class="abst-magic-bar-container">
            <div class="abst-settings-column help" data-llm-instructions="this div contains instructions for LLM assistance 
            #abst-magic-bar-title is the test name. add your descriptive title
            #abst-selector-input gives you the css selector of the selected element, if any is selected.
            #variation-picker is the way to choose and add a variation to the test. 
            #abst-variation-editor-container is the way to edit the variation after you have selected it. 
            You can add additional elements to a test, like a subhero under a hero for example. To add an additional element to the test, click it and add a variation. you'll see #abst-selector-input update to the new element. You can then edit the text in the editor and  swap the #variation-picker to other variations to edit those too. 
            Click .abst-add-goal-button to add a subgoal to the test. add page visits to funnel pages with the URL visit goal, or time active of 15 seconds to gauge overall engagement. you can also add page click goals to measure if someone is clicking after seeing a test.  
            #ai-suggestions-list contains our trained suggestions for the highlighted element - you can click on a suggestion to add it, then create a new variation. You can also define the variation on your own if you have a better option. #abst-variation-editor-container is the way to edit the variation after you have selected it. After saving test, you can go to /wp-admin/edit.php?post_type=bt_experiments to view all tests. do not edit other tests unless  specifically asked">
            <h4>How to create a new test</h4>
            <ol>
                <li>Click any element on the page to begin.</li>
                <li>In the editor, change the text to create your first variation.</li>
                <li>Want to make multiple changes in the same variation? Just click and edit the next element.</li>
                <li>To test an alternate version, use the “Variation” dropdown and click + Add another.</li>
                <li>Choose your test goal(s)</li>
                <li>Start your test</li>
            </ol>
            <span class="hide-magic-bar" id="close-magic-bar" data-llm-instructions="this span is used to hide this help window, LLM should click it so that users can see you creating the test.">Hide Help</span>
            </div>
        <!-- settings Column -->
        <div class="abst-settings-column magic-test-name">
            <h4>New AB Test</h4>
            <p>Test Name:</p><input id="abst-magic-bar-title" class="abst-magic-bar-title" value="New Magic Test">
        </div>
        <!-- settings Column -->
        <div class="abst-settings-column prompt-response">
            <h4>CROassist</h4>
            <div class="results"><p>Loading...</p></div>
        </div>
        <div class="abst-settings-column click-to-start-help" >
            <h3>Click any text or image to start</h3>
            <p>Select the element you want to optimize, then you can edit the content to create your first variation.</p>
            <!-- 
            <p><small>or</small></p>
            <p>Generate test ideas below</p>
            <div id="abst-ideas-general">
            <h4>Ideas generator</h4>
                <input type="text" id="abst-idea-input" placeholder="Make the hero headline more engaging"><button id="abst-idea-button">Generate Ideas</button>
            </div>
            -->
        </div>
        <div class="abst-settings-column" id="variation-picker-container">
            <p>You are now editing and viewing:</p><select id="variation-picker">${variationOptionsHtml}</select>
            
            </div>
            <div class="abst-settings-column" id="variation-editor-container">
            <span>Test Element Selector</span>
            <input id="abst-selector-input" type="text" value="Select an item to start testing" placeholder="CSS Selector">
                <p id="version-value">Editing the B Version of the test</p><div id="abst-variation-editor"></div>
                <div id="ai-suggestions" class="abst-ai-loading" style="display: none;">
                <p>AI Suggestions ✨ <small>Click to add to test variations &amp; edit.</small></p>
                <ul id="ai-suggestions-list"></ul>
                </div>
            </div>
            <p id="abst-targeting-button" style="
    padding: 10px 20px;
    opacity: 0.6;
"><span id="abst-targeting-text">Testing on all users except editors &amp; administrators. </span><a href="#" id="abst-show-targeting">Edit</a> </p>
            <div class="abst-settings-column abst-targeting-settings" data-llm-instructions="this div contains the targeting settings for the test. it is hidden by default and can be toggled by clicking the #abst-show-targeting button only add targeting if specifically asked or logical to change, otherwise leave as default. ">
                <div class="abst-settings-title">Targeting</div>
                <div class="abst-settings-header closed" tabindex="0" aria-expanded="false">User Roles</div>
                <div class="abst-targeting-option" id="abst-user-roles-container">
                    ${rolesHtml}
                </div>
                <div class="abst-settings-header closed" tabindex="0" aria-expanded="false">Device Size</div>
                <div class="abst-targeting-option abst-hidden">
                    <select id="abst-device-size" class="abst-select">
                        <option value="all" selected>All Sizes</option>
                        <option value="desktop">Desktop</option>
                        <option value="tablet">Tablet</option>
                        <option value="mobile">Mobile</option>
                    </select>
                </div>
                <div class="abst-settings-header closed" tabindex="0" aria-expanded="false">URL Filtering</div>
                <div class="abst-targeting-option abst-hidden">
                    <input type="text" id="abst-url-query" class="abst-url-input" placeholder="utm_source=Google">
                    <div class="abst-url-help">Test on traffic with matching URL query strings, or use a * to search the entire URL for a specific string</div>
                </div>
                <div class="abst-settings-header closed" tabindex="0" aria-expanded="false">Traffic Allocation Percentage</div>
                <div class="abst-targeting-option abst-hidden">
                    <input type="number" id="abst-traffic-percentage" class="abst-number-input" value="100" min="1" max="100">
                </div>
            </div>
            ${abst_magic_data.is_agency ? `<div class="abst-settings-column winning-mode"><p>Winning Mode:</p><select id="abst-conversion-style"><option value="bayesian">Standard - Bayesian</option><option value="thompson">Dynamic - Multi Armed Bandit</option></select></div>` : ''}
            <!-- Goals Column -->
            <div class="abst-goals-column" data-llm-instructions="conversion goals are defined here. it's usually best to specify the overall website goal, like a purchase or a form contact, rather than a generic button, unless you are testing the text or text around that button">
                <div class="abst-goals-title">Goals</div>
                <div class="abst-goals-container" data-goal="0">
                <h5>Primary Conversion Goal</h5>
                    <div id="abst-primary-goal-container">${abst_magic_data.goals}</div>
                    <label id="conversion_use_order_value_container" for="conversion_use_order_value"><input type="checkbox" id="conversion_use_order_value" class="abst-checkbox" name="conversion_use_order_value"> Use Order Value as Conversion Value</label>
                    <div class="goal-value-label"></div>
                    <input type="text" class="abst-goal-input-value" placeholder="">
                </div>
                <div class="abst-button-container">
                    <button class="abst-add-goal-button">+ Add Goal</button>
                </div>
            </div>
        </div>
        <button id="abst-magic-bar-start" class="abst-magic-bar-start">Start Test</button>

    </div>`;

    get_abst_pageselector();

    // Add close button if enabled
    if (settings.closeButton) {
        const closeButton = document.createElement('div');

        closeButton.className = 'abst-magic-bar-close';
        closeButton.innerHTML = '×';
        closeButton.title = 'Cancel New Test';
        closeButton.addEventListener('click', function() {
            close_abst_magic_bar();
        });
        magicBar.appendChild(closeButton);
    }

    // Add the magic bar to the body
    document.body.appendChild(magicBar);
    //if localstorage localStorage.setItem('abst-magic-help', 'false'); then hide
    if(localStorage.getItem('abst-magic-help') === 'false'){
        jQuery('.abst-settings-column.help').hide();
    }

    if( btab_vars.is_free == '1'){
        jQuery('#abst-magic-bar').append('<div id="abst-magic-upgrade-overlay"><p>This is a pro feature</p><h3>Upgrade to create a Magic Test</h3><p>Also in any premium version:</p><ul><li>Unlimited Magic Tests</li><li>AI Assistant</li><li>AI Rewriter</li><li>Reports</li><li>Advanced Targeting</li><li>Analytics integrations</li></ul><a href="https://absplittest.com/pricing/?utm_source=magicupgradetab" target="_blank" class="abst-button button">Get AB Split Test Premium</a><BR><p>Your free account enables traditional tests from the block editor or <a href="'+bt_adminurl+'edit.php?post_type=bt_experiments" target="_blank">WP admin.</a></p><p class="upgrade-testy"><strong>Works well and makes me more money</strong><BR><em>This plugin is really a great addition to our website. Testing things is crucial and we have gained priceless insights so far!</em><BR>Christian - verified buyer.</p></div>'); 
    }


    jQuery('.abst-goal-page-input').hide();
    
    // Initialize simple contentEditable editor
    setTimeout(function() {
        const editorContainer = document.createElement('div');
        editorContainer.id = 'abst-variation-editor-container';
        editorContainer.className = 'abst-editor';
        editorContainer.contentEditable = true;
        

        const editorWrapper = document.querySelector('#abst-variation-editor');
        if (editorWrapper) {
            editorWrapper.appendChild(editorContainer);
        }


        // Store reference to the editor
        window.abstEditor = editorContainer;



        // Create and add toolbar
        createEditorToolbar();

        // Add event listeners
        if (window.abstEditor) {
            window.abstEditor.addEventListener('input', checkChangedEditor);
            window.abstEditor.addEventListener('paste', (e) => {
                e.preventDefault();
                const text = (e.clipboardData || window.clipboardData).getData('text/plain');
                document.execCommand('insertHTML', false, text);
            });
        }
    }, 10);
    
    // Function to create editor toolbar
    function createEditorToolbar() {
        const toolbar = document.createElement('div');
        toolbar.className = 'abst-editor-toolbar';
        
        const buttons = [
            { command: 'bold', text: 'B', title: 'Bold' },
            { command: 'italic', text: 'I', title: 'Italic' },
            { command: 'underline', text: 'U', title: 'Underline' },
            { command: 'insertUnorderedList', text: '• List', title: 'Bullet List' },
            { command: 'insertOrderedList', text: '1. List', title: 'Numbered List' },
            { command: 'createLink', text: '🔗', title: 'Insert Link' },
            { command: 'html', text: '</>', title: 'HTML Mode', htmlButton: true }
        ];
        
        // Add HTML editor textarea
        const htmlEditor = document.createElement('textarea');
        htmlEditor.id = 'abst-html-editor';
        htmlEditor.className = 'abst-html-editor';
        
        buttons.forEach(btn => {
            const button = document.createElement('button');
            button.type = 'button';
            button.textContent = btn.text;
            button.title = btn.title;
            button.dataset.command = btn.command;
            button.onclick = (e) => {
                e.preventDefault();
                if (btn.command === 'createLink') {
                    const url = prompt('Enter URL:');
                    if (url) document.execCommand(btn.command, false, url);
                } else if (btn.command === 'html') {
                    toggleHtmlMode(button);
                } else {
                    document.execCommand(btn.command, false, null);
                }
                window.abstEditor.focus();
            };
            toolbar.appendChild(button);
        });
        
        // Add HTML editor and toolbar to the container
        const editorContainer = document.getElementById('abst-variation-editor-container');
        if (editorContainer) {
            // Insert toolbar before the editor container
            editorContainer.parentNode.insertBefore(toolbar, editorContainer);
            
            // Insert HTML editor before the image selector button if it exists, otherwise before the editor container
            const imageSelector = document.getElementById('imageSelector');
            if (imageSelector) {
                imageSelector.parentNode.insertBefore(htmlEditor, imageSelector);
            } else {
                editorContainer.parentNode.insertBefore(htmlEditor, editorContainer);
            }
        }
    }
    
    // Toggle between WYSIWYG and HTML modes
    function toggleHtmlMode(button) {
        const editor = document.getElementById('abst-variation-editor-container');
        const htmlEditor = document.getElementById('abst-html-editor');
        
        if (editor.style.display === 'none') {
            // Switch to WYSIWYG mode
            editor.style.display = '';
            htmlEditor.style.display = 'none';
            button.textContent = '</>';
            button.title = 'HTML Mode';
            
            // Update the editor content from HTML
            window.abstEditor.innerHTML = htmlEditor.value;
            checkChangedEditor();
        } else {
            // Switch to HTML mode
            editor.style.display = 'none';
            htmlEditor.style.display = 'block';
            button.textContent = '👁️';
            button.title = 'Switch to WYSIWYG Editor';
            
            // Update HTML content from editor
            htmlEditor.value = window.abstEditor.innerHTML;
        }
        
        // Focus the active editor
        (editor.style.display === 'none' ? htmlEditor : window.abstEditor).focus();
    }

    // add time date in nice format to new test title
    const now = new Date();
    const months = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
    currentPageTitle = jQuery('h1').first().text().trim() || jQuery('title').text().trim() || 'Magic';
    const formatted = `${months[now.getMonth()]} ${now.getDate()}`;
    jQuery('.abst-magic-bar-title').val(currentPageTitle + ' Page Test ' + formatted);
   

    // Show the magic bar
    setTimeout(() => {
        jQuery('html').addClass('doing-abst-magic-bar');
        
        // Find and adjust fixed elements
        adjustFixedElementsForMagicBar(true);
        

        magicBar.style.transform = 'translateY(0)';

        // Call onOpen callback if provided
        if (typeof settings.onOpen === 'function') {
            settings.onOpen();
        }
        
        // Add event listeners for the selector field
        const selectorField = document.getElementById('abst-selector');
        if (selectorField) {
            selectorField.addEventListener('blur', function() {
                // You could add validation or other functionality here
                console.log('Selector updated:', this.value);
            });
        }


        jQuery('.abst-goal-input-value').hide();
    }, 10);

    // Function to close the magic bar
    window.close_abst_magic_bar = function() {

        //reload page without ?abmagic if its there
        const url = new URL(window.location);
        url.searchParams.delete('abmagic');
        window.location.href = url.toString();

    };
}

    /**
     * Finds and adjusts fixed elements when the magic bar is active
     * @param {boolean} activate - Whether to activate or deactivate adjustments
     */
    function adjustFixedElementsForMagicBar(activate) {
        // Get all elements in the document
        const allElements = document.querySelectorAll('*');
    
    // Process each element
    allElements.forEach(element => {
        // Skip elements in the magic bar itself
        if (element.closest('#abst-magic-bar')) return;
        if (element.closest('#wpadminbar')) return;
        
        const style = window.getComputedStyle(element);
        const position = style.getPropertyValue('position');
        
        // Check if the element has fixed positioning
        if (position === 'fixed') {
            if (activate) {
                // Add the adjustment class
                element.classList.add('abst-adjusted-for-magic-bar');
                console.log('added correction css to fixed')
            } else {
                // Remove the adjustment class
                element.classList.remove('abst-adjusted-for-magic-bar');
            }
        }
    });
}

// Make the function available globally
window.abst_magic_bar = abst_magic_bar;

// jQuery wrapper for the function
(function($) {
    $(function() {


        jQuery("body").on('click','.abst-settings-header',function(){
            jQuery(this).toggleClass('closed').next('.abst-targeting-option').toggleClass('abst-hidden');
        });

        jQuery("body").on('click','.abst-add-goal-button',function(){
           // scroll to bottom of abst-magic-bar
            //if theres less than 11 goals
            if(jQuery('.abst-goals-container').length < 10){
                goalCount = jQuery('.abst-goals-container').length + 1;
                jQuery('<div class="abst-goals-container" data-goal="'+goalCount+'"><h5>Goal #'+goalCount+'</h5><div class="remove-goal"> X</div> ' +abst_magic_data.goals+'<div class="goal-value-label"></div><input type="text" class="abst-goal-input-value" placeholder="Enter goal"></div>').insertBefore('.abst-button-container');
            }
            else{
                alert('You can have maximum 10 goals');
            }
            jQuery('#abst-magic-bar').animate({
                scrollTop: jQuery('#abst-magic-bar').prop('scrollHeight')
            }, 1500);

        });

        jQuery("body").on('click','#abst-show-targeting',function(){
            jQuery('.abst-targeting-settings').slideToggle();
            jQuery('#abst-targeting-text').text('Custom targeting.');
            jQuery('.abst-settings-column.help').slideUp();
        })

        // Make the function available in the jQuery namespace
        $.abst_magic_bar = function(options) {
            abst_magic_bar(options);
        };
        
        // Add event listener for the admin bar menu item
        $(document).on('click', '#wp-admin-bar-ab-new-magic-test a', function(e) {
            e.preventDefault();
            // Make sure the magic bar is properly initialized
            if (typeof abst_magic_bar === 'function') {
                abst_magic_bar();
            } else {
                console.error('abst_magic_bar function not found');
            }
            jQuery('.menupop.hover').removeClass('hover');
        });

        jQuery('body').on('click','.hide-magic-bar',function(){
            jQuery('.abst-settings-column.help').slideUp();
            //set localStorage
            localStorage.setItem('abst-magic-help', 'false');
        })
        
        jQuery('body').on('click','.ai-suggestion-item',function(){
            if(jQuery("#variation-picker").val() == '0'){
                alert('Please select a variation first');
                return;
            }

            //remove the item clicked
            var theText = jQuery(this).text(); 
            jQuery(this).fadeOut(200);
            console.log(jQuery("#variation-picker").val());
            // Insert HTML into editor if available
            if (window.abstEditor) {
                window.abstEditor.innerHTML = theText;
                // Trigger input event to mark as changed
                const event = new Event('input', { bubbles: true });
                window.abstEditor.dispatchEvent(event);
            }

        });


        jQuery('body').on('change', "#abst-variation-editor-container", function() {
            var variationIndex = parseInt(jQuery("#variation-picker").val(), 10);
            var selector = jQuery("#abst-selector-input").val();
            var variationValue = window.abstEditor ? window.abstEditor.innerHTML.replace(/^<p>(.*?)<\/p>$/i, '$1').trim() : '';
            var variationType = getElementType(jQuery(selector)[0]);

            if (!window.abmagic) window.abmagic = {};
            if (!window.abmagic.definition) window.abmagic.definition = [];

            elementIndex = getElementIndexFromMagic(selector);
            
            
            console.log('elementIndex',elementIndex);

            if (elementIndex !== -1) {
                // found
                window.abmagic.definition[elementIndex]['variations'][variationIndex] = variationValue;
                console.log('updated variation',window.abmagic.definition[elementIndex]['variations']);
            } else {
                //not found, add
                var originalValue;
                if (variationType === 'image') {
                    originalValue = jQuery(selector).attr('src');
                } else {
                    originalValue = jQuery(selector).html();
                }

                var newVariations = [];
                newVariations[0] = originalValue;
                newVariations[variationIndex] = variationValue;

                var newDef = {
                    type: variationType,
                    selector: selector,
                    variations: newVariations
                };

                window.abmagic.definition.push(newDef);
                console.log('added new variation',window.abmagic.definition[window.abmagic.definition.length - 1]['variations'][variationIndex]);
            }

            if (variationType === 'image') {
                console.log('setting image src', variationValue);
                jQuery(selector).attr('src', variationValue).addClass('abst-variation');

            } else {
                jQuery(selector).html(variationValue).addClass('abst-variation');
            }
        });




        jQuery('body').on('change', "#variation-picker", function() {
            //get variation
            if(jQuery("#variation-picker :selected").text().includes('Control')){
                jQuery("#version-value").text("View the original text.");
            }
            else{
                jQuery("#version-value").text("Edit the " + jQuery("#variation-picker :selected").text() + " of the test");
            }
            var variationIndex = jQuery("#variation-picker").val();

            // if its 0 then make editor read-only
            if (window.abstEditor) {
                if (variationIndex == 0) {
                    window.abstEditor.contentEditable = 'false';
                    window.abstEditor.style.backgroundColor = '#f5f5f5';
                } else {
                    window.abstEditor.contentEditable = 'true';
                    window.abstEditor.style.backgroundColor = '#fff';
                }
            }
            
            //if the variation index is the last one, then change the label to Variation C/d/e/f/g etc and add another option below with label " Add Variation"
            
            if(variationIndex == 'addAnother'){
                //remove existing add another option
                jQuery("#variation-picker option[value='addAnother']").remove();
                console.log('add variation');
                var nextIndex = jQuery("#variation-picker option").length;
                var label = getVariationLabel(nextIndex);
                var newOptions = '<option value="'+nextIndex+'">'+label+' Version</option><option value="addAnother"> + Add Version</option>';
                jQuery("#variation-picker").append(newOptions);
                jQuery("#variation-picker").val(nextIndex);
                jQuery("#variation-picker").trigger('change');

                console.log(newOptions,jQuery("#variation-picker"),nextIndex,label);
                //add remobe button below if theres more than 2 variations otherwise remove
                // change text of variation picker
            }


            //remove button here if index not 0 or 1
            if(variationIndex == 0 || variationIndex == 1){
                jQuery(".abst-variation-remove").remove();
            }
            else if(!jQuery(".abst-variation-remove").length) {
                jQuery("#variation-picker").after('<a class="abst-variation-remove" href="#">Remove Last Variation</a>');
            }


            if(!window.abmagic)                window.abmagic = {};
            if(!window.abmagic.definition)                window.abmagic.definition = [];

            // Update all elements with their respective variations
            window.abmagic.definition.forEach(function(def) {
                const content = def.variations[variationIndex] || def.variations[0] || '';
                if (def.type === 'image') {
                    jQuery(def.selector).attr('src', content);
                } else {
                    jQuery(def.selector).html(content);
                }
            });
            
            // Refresh all variation classes
            refreshVariationClasses();
            
            // Update the editor with the current element's variation
            const selector = jQuery("#abst-selector-input").val();
            const elementDef = window.abmagic.definition.find(def => def.selector === selector);
            
            if (elementDef && window.abstEditor) {
                const currentText = elementDef.variations[variationIndex] || elementDef.variations[0] || '';
                
                // Set the content directly in the contentEditable div
                window.abstEditor.innerHTML = currentText;
                
                // Update the hidden input
                jQuery('#abst-variation-data').val(JSON.stringify(window.abmagic.definition));
                
                // Trigger input event to mark as changed
                const event = new Event('input', { bubbles: true });
                window.abstEditor.dispatchEvent(event);
            }
        });

        jQuery('body').on('click', ".abst-variation-remove", function() {
            //remove variation
            if(jQuery("#variation-picker option:last").val() == 'addAnother'){
                jQuery("#variation-picker option:last").remove();
            }
            if(jQuery("#variation-picker option").length > 2){

                if(window.abmagic.definition){
                    //each window.abmagic.definition
                    variationIndex = parseInt(jQuery("#variation-picker option:last").val());
                    console.log('Removing variation at index:' , variationIndex);
                    window.abmagic.definition.forEach(function(currentDefinition) { 
                        if (currentDefinition.variations && currentDefinition.variations.length > variationIndex) {
                            // Remove the variation at the specified 'variationIndex'
                            currentDefinition.variations.splice(variationIndex, 1); 
                            console.log('Removed variation for selector:', currentDefinition.selector);
                        } else if (currentDefinition.variations && currentDefinition.variations.length === variationIndex && variationIndex === 0 && currentDefinition.variations.length === 1){
                            // Edge case: if removing the only variation (index 0) when variations array has 1 element.
                            currentDefinition.variations.splice(variationIndex, 1);
                            console.log('Removed the only variation for selector:', currentDefinition.selector);
                        }
                    });
                }

                jQuery("#variation-picker option:last").remove();
                console.log(window.abmagic.definition);
            }
            //select last item
            jQuery("#variation-picker").val(jQuery("#variation-picker option").length - 1);
            jQuery("#variation-picker").trigger('change');
            //add it back in
            jQuery("#variation-picker").append('<option value="addAnother"> + Add Version</option>');
        });


        
        
        jQuery('body').on('click','.remove-goal',function(){
            //remove the container
            jQuery(this).parent('.abst-goals-container').remove();
        });

        jQuery('body').on('click','[magic-eid]',function(){
            showMagicTest(jQuery(this).attr('magic-eid'), jQuery(this).attr('magic-index'),true);
        });


        //on select.goal-type change if it equals 'page' then append a pageselector dropdown that updates the input value next to it
        jQuery('body').on('change', '.abst-goals-container select.goal-type', function() { // update hidden input value
            jQuery(this).parents('.abst-goals-container').find('.abst-goal-input-value').val('');
            jQuery('#conversion_use_order_value_container').hide(); // use order value hidden unless woo order
            var type = jQuery(this).val();
            var goalContainer = jQuery(this).parents('.abst-goals-container');  
            //array of special pages to tranfer value into value
            // Ensure any existing Select2 is cleaned up before processing the current type
            var existingPageSelectContainer = goalContainer.find('.abst-page-select-container');
            if (existingPageSelectContainer.length) {
                var select2Instance = existingPageSelectContainer.find('.abst-goal-page-input');
                if (select2Instance.data('select2')) {
                    select2Instance.select2('destroy');
                }
                existingPageSelectContainer.remove();
            }
            // By default, show the text input. It will be hidden again if type is 'page' or certain abstSpecialPages.
            goalContainer.find('.abst-goal-input-value').show();
            // jQuery('#conversion_use_order_value_container').hide(); // This is done at line 1725, before this block

            if (type == 'page') {
                goalContainer.find('.abst-goal-input-value').hide();
                goalContainer.find('.goal-value-label').text('Choose Page that will trigger a goal when visited').show();
                
                const inputId = 'page-search-' + Math.random().toString(36).substr(2, 9);
                const $container = $('<div>', { 
                    class: 'abst-page-select-container',
                    css: { position: 'relative' }
                });
                
                // Add a label above the input field
                const $label = $('<div class="abst-page-search-label">Search for a page:</div>');
                $container.append($label);
                
                const $input = $('<input>', {
                    type: 'text',
                    id: inputId,
                    class: 'abst-goal-page-input',
                    placeholder: 'Type to search pages or click to see recent pages...',
                    autocomplete: 'off'
                });
                
                $container.append($input);
                
                // Replace existing container if it exists
                const existingContainer = goalContainer.find('.abst-page-select-container');
                if (existingContainer.length) {
                    existingContainer.replaceWith($container);
                } else {
                    goalContainer.append($container);
                }
                
                // Initialize Awesomplete
                const input = document.getElementById(inputId);
                const awesomplete = new Awesomplete(input, {
                    minChars: 2,
                    maxItems: 15,
                    autoFirst: true,
                    sort: false,
                    // Add this to properly format the selected item
                    item: function(text, input) {
                        const item = Awesomplete.ITEM(text, input);
                        item.dataset.value = text.value; // Store the ID
                        return item;
                    },
                    // Add this to properly display the label
                    replace: function(text) {
                        this.input.value = text.label;
                    }
                });
                
                // Show latest pages on focus
                $(input).on('focus', function() {
                    if (!this.value) {
                        $(input).addClass('loading');
                        
                        // Show loading message in dropdown
                        awesomplete.list = [{ label: 'Loading recent pages...', value: '' }];
                        awesomplete.evaluate();
                        
                        $.ajax({
                            url: abst_magic_data.ajax_url,
                            dataType: 'json',
                            data: {
                                q: 'recent',
                                action: 'ab_page_selector'
                            },
                            success: function(pages) {
                                if (pages && pages.length) {
                                    const items = pages.map(page => ({
                                        label: page[1], // Title
                                        value: page[0]  // ID
                                    }));
                                    awesomplete.list = items;
                                    awesomplete.evaluate();
                                } else {
                                    // Show no results message
                                    awesomplete.list = [{ label: 'No recent pages found', value: '' }];
                                    awesomplete.evaluate();
                                    setTimeout(() => {
                                        awesomplete.list = [];
                                    }, 1500);
                                }
                            },
                            complete: function() {
                                $(input).removeClass('loading');
                            }
                        });
                    }
                });
                
                // Handle input with debounce
                let searchTimeout;
                $(input).on('input', function() {
                    const query = this.value;
                    if (query.length < 2) {
                        awesomplete.list = [];
                        return;
                    }
                    
                    clearTimeout(searchTimeout);
                    searchTimeout = setTimeout(() => {
                        $.ajax({
                            url: abst_magic_data.ajax_url,
                            dataType: 'json',
                            data: {
                                q: query,
                                action: 'ab_page_selector'
                            },
                            beforeSend: function() {
                                $(input).addClass('loading');
                            },
                            success: function(pages) {
                                const items = pages.map(page => {
                                    // Ensure we're working with the correct data format
                                    // page[0] should be the numeric ID, page[1] should be the title
                                    const id = Array.isArray(page) ? page[0] : page.id || page;
                                    const title = Array.isArray(page) ? page[1] : page.title || page;
                                    
                                    return {
                                        label: title,  // Title for display
                                        value: id      // ID for storage (should be numeric)
                                    };
                                });
                                
                                awesomplete.list = items;
                                
                                if (items.length === 1 && 
                                    items[0].label.toLowerCase() === query.toLowerCase()) {
                                    awesomplete.select(0);
                                }
                                
                                if (items.length > 0) {
                                    awesomplete.evaluate();
                                }
                            },
                            complete: function() {
                                $(input).removeClass('loading');
                            }
                        });
                    }, 300); // 300ms debounce
                });
                
                // Handle selection
                $(input).on('awesomplete-selectcomplete', function(e) {
                    const selectedItem = e.originalEvent.text;
                    if (selectedItem) {
                        // Set visible input to show the page title
                        this.value = selectedItem.label || selectedItem;
                        
                        // Set hidden input to store ONLY the numeric ID
                        const pageId = selectedItem.value || selectedItem;
                        goalContainer.find('.abst-goal-input-value')
                            .val(pageId)
                            .trigger('change');
                        
                        // Debug to verify correct ID storage
                        console.log('Stored page ID:', pageId);
                    }
                    return false;
                });
                                
                // Handle existing value
                const existingValue = goalContainer.find('.abst-goal-input-value').val();
                if (existingValue) {
                    $.ajax({
                        url: abst_magic_data.ajax_url,
                        dataType: 'json',
                        data: {
                            q: existingValue,
                            action: 'ab_page_selector'
                        },
                        success: function(pages) {
                            if (pages.length > 0 && Array.isArray(pages[0]) && pages[0].length >= 2) {
                                input.value = pages[0][1];
                                goalContainer.find('.abst-goal-input-value')
                                    .val(pages[0][0])
                                    .trigger('change');
                            }
                        }
                    });
                }
                
                // Cleanup
                const cleanup = function() {
                    clearTimeout(searchTimeout);
                    $(input).off('input awesomplete-selectcomplete');
                    $container.off('remove', cleanup);
                    if (awesomplete) {
                        awesomplete.destroy();
                    }
                };
                $container.on('remove', cleanup);
            }else if (type == 'javascript'){
                goalContainer.find('.goal-value-label').text('Create test, then view in admin to see conversion script').show();
            }
            if (abstSpecialPages.includes(type)) { // abstSpecialPages is defined at line 1729
                goalContainer.find('.abst-goal-input-value').val(type).hide();
            }
            else if (type == 'block'){
                goalContainer.find('.goal-value-label').text('Create test, then view in admin to track this block').show();
            }
            else if (type == 'time'){
                goalContainer.find('.goal-value-label').text('Enter a time in seconds that will trigger a goal. Active time counts as when the user is moving mouse / interacting with the page.').show();
            }
            else if (type == 'scroll'){
                goalContainer.find('.goal-value-label').text('Enter a scroll depth percentage (0-100) that will trigger a goal.').show();
            }
            else if (type == 'text'){
                goalContainer.find('.goal-value-label').text('Enter the exact text that will trigger a goal when loaded on the page. e.g. "Thank You For Subscribing" or "Order Complete"').show();
            }
            else if (type == 'link'){
                goalContainer.find('.goal-value-label').text('Enter the URl (or part of the URL) that will trigger a goal when clicked. It can be a link to an external website. e.g. /thank-you/ or https://youtube.com/yourvideo').show();
            }
            else if (type == 'url'){
                goalContainer.find('.goal-value-label').text('Similar to page visit, but enter the URL that will trigger a goal when visited. Cannot be an external URL. e.g. contact or /checkout/').show();
            }
            else if (type == 'selector'){
                goalContainer.find('.goal-value-label').text('Select an item on the page, or enter the CSS selector that when clicked will trigger a goal. e.g. "#submit-order" or ".header button"').show();
                // Note: The window.magicLastFocus logic is handled by a separate if block that follows this main conditional chain.
            }
            else if (abstSpecialPages.includes(type)) { // Handles 'woo-order-pay', 'woo-order-received', 'woo'
                 goalContainer.find('.abst-goal-input-value').val(type).hide();
                 goalContainer.find('.goal-value-label').text('Goal for: ' + type).show(); 
                 if(type == 'woo-order-received' ){
                    jQuery('#conversion_use_order_value_container').show();
                 }
            }
            else if (type && type !== '' && !abIsInt(type)) { // Default for other known types like 'link', 'text', 'url', but not numeric page IDs from old dropdown
                 goalContainer.find('.goal-value-label').text('Enter a value for the goal.').show();
            }
            else if (abIsInt(type)) { // Handles numeric page IDs if they somehow get selected (e.g. from old data)
                goalContainer.find('.goal-value-label').text('Will be triggered when the user visits: ' + goalContainer.find('option:selected').text()).show();
                goalContainer.find('.abst-goal-input-value').val(type).hide(); // Hide input as it's a direct page ID
            }
            else { // type is '' (empty, e.g., "Select Goal Event...")
                 goalContainer.find('.goal-value-label').text('Select a goal type to define its value.').show(); 
            }

            if(type == 'selector'){
                console.log('selector');
                window.magicLastFocus = jQuery(this).parents('.abst-goals-container').find('.abst-goal-input-value')[0];
                console.log('window.magicLastFocus', window.magicLastFocus);
              }
        
        }).trigger('change');


        jQuery('body').on('click','#abst-magic-bar-start',function(){
            //get all magic data

            //if theres no magicdata then alert
            if(!window.abmagic || !window.abmagic.definition || window.abmagic.definition.length == 0) {
                alert('Please add at least one element to the test');
                return;
            }

            var goalType = jQuery('.abst-goals-container').first().find('.goal-type').val();
            if (!jQuery('.abst-goals-container').first().find('.abst-goal-input-value').val() && !abstSpecialPages.includes(goalType)) {
                alert('Please add at least one goal to the test');
                //scroll #abst-magic-bar to the bottom
                jQuery('#abst-magic-bar').animate({
                    scrollTop: jQuery('#abst-magic-bar').height()
                }, 1000);
                return;
            }

            var newTestData = {
                action: 'create_new_on_page_test',
                post_title: jQuery('#abst-magic-bar-title').val(),
                post_id: 'new',
                magic_definition: JSON.stringify(window.abmagic.definition),
                test_type: 'magic',
                conversion_style: jQuery('#abst-conversion-style').length ? jQuery('#abst-conversion-style').val() : 'bayesian',
                bt_experiments_url_query: jQuery("#abst-url-query").val(),
                bt_experiments_conversion_page: jQuery(".abst-goals-container").first().find('select').val(),
                bt_experiments_conversion_page_selector: '',
                bt_experiments_conversion_url: '',
                bt_experiments_conversion_selector: '',
                bt_experiments_conversion_link_pattern: '',
                bt_experiments_full_page_default_page: '',
                css_test_variations: '',
                bt_experiments_conversion_order_value: jQuery('#conversion_use_order_value').is(':checked') ? 1 : 0,
                bt_experiments_conversion_time: '',
                bt_experiments_conversion_text: '',
                bt_experiments_target_option_device_size: jQuery('#abst-device-size').val(),
                bt_experiments_target_percentage: jQuery('#abst-traffic-percentage').val(),
                bt_allowed_roles: jQuery('#abst-user-roles-container input[type="checkbox"]:checked').map(function() {
                    return jQuery(this).val();
                }).get(),
                goal: get_goals_from_dom(),
                
            };

            if(jQuery(".abst-goals-container").first().find('select').val() == 'page')
                newTestData.bt_experiments_conversion_page = jQuery(".abst-goals-container").first().find('.abst-goal-input-value').val();

            if(jQuery(".abst-goals-container").first().find('select').val() == 'url')
                newTestData.bt_experiments_conversion_url = jQuery(".abst-goals-container").first().find('.abst-goal-input-value').val();

            if(jQuery(".abst-goals-container").first().find('select').val() == 'time')
                newTestData.bt_experiments_conversion_time = jQuery(".abst-goals-container").first().find('.abst-goal-input-value').val();

            if(jQuery(".abst-goals-container").first().find('select').val() == 'text')
                newTestData.bt_experiments_conversion_text = jQuery(".abst-goals-container").first().find('.abst-goal-input-value').val();

            if(jQuery(".abst-goals-container").first().find('select').val() == 'link')
                newTestData.bt_experiments_conversion_link_pattern = jQuery(".abst-goals-container").first().find('.abst-goal-input-value').val();
            
            if(jQuery(".abst-goals-container").first().find('select').val() == 'selector')
                newTestData.bt_experiments_conversion_selector = jQuery(".abst-goals-container").first().find('.abst-goal-input-value').val();
                
                jQuery.ajax({
                url: bt_ajaxurl,
                type: 'POST',
                data: newTestData,
                success: function(response) {
                    //redirect to experiment page
                    response = JSON.parse(response);
                    if(response.post_title && response.post_title !== ''){
                        alert(response.post_title + " created, reloading page.");
                        window.location.href = window.location.pathname;
                    }
                }
            });
        
      });
    });
})(jQuery);

function get_goals_from_dom(){

    var goals = {}; 
    jQuery('.abst-goals-container').each(function(index){
        if(index === 0)
            return;
        
        var sel = jQuery(this).find('select').val();
        var val = jQuery(this).find('input').val();
        
        // Create an object with the dynamically named property
        var goalObj = {};
        goalObj[sel] = val;
        
        // Add to goals object with index as key (starting from 1)
        goals[index] = goalObj;
    });
    console.log(goals);

    return goals;
}

//not from dom anymore but from window variable
function get_abst_pageselector() {
    jQuery.ajax({
        url: bt_ajaxurl,
        type: 'GET',
        data: {
            action: 'ab_page_selector'
        },
        success: function(response) {
           response = JSON.parse(response);
           response.unshift(['', "Choose a Page"]);
           window.abst_magic_data.pages = response;
        }
    });
}
function abIsInt(value) {
    return !isNaN(value) && parseInt(Number(value)) == value && !isNaN(parseInt(value, 10));
}


        
function isInViewport(element) {
    const rect = element.getBoundingClientRect();
    return (
        rect.top >= 0 &&
        rect.left >= 0 &&
        rect.bottom <= (window.innerHeight || document.documentElement.clientHeight) &&
        rect.right <= (window.innerWidth || document.documentElement.clientWidth)
    );
}

// Refreshes all variation classes by removing the class from all elements
// and then re-adding it only to elements in the current Magic definition
function refreshVariationClasses() {
    // First remove the class from all elements
    jQuery('.abst-variation').removeClass('abst-variation');
    
    // Skip if no Magic definition exists
    if (!window.abmagic || !window.abmagic.definition) return;
    
    // Re-add the class to all elements in the definition
    window.abmagic.definition.forEach(function(def) {
        if (def && def.selector) {
            const elements = jQuery(def.selector);
            if (elements.length > 0) {
                elements.addClass('abst-variation');
            }
        }
    });
    
    console.log('Refreshed variation classes');
}




function getVariationLabel(n) {
    alphabet = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z'];
    return alphabet[n];
}
    /**
     * Parse CSS selector string into individual tags while preserving spaces in square brackets
     * @param {string} selector - CSS selector string
     * @return {array} Array of tag values
     */
    function parseCssSelector(selector) {
        if (!selector) return [];
        
        // Replace spaces in square brackets with a temporary marker
        var processedSelector = '';
        var inBrackets = false;
        var bracketContent = '';
        
        for (var i = 0; i < selector.length; i++) {
            var char = selector[i];
            
            if (char === '[') {
                inBrackets = true;
                bracketContent = '[';
            } else if (char === ']' && inBrackets) {
                inBrackets = false;
                bracketContent += ']';
                processedSelector += bracketContent.replace(/ /g, '___SPACE___');
                bracketContent = '';
            } else if (inBrackets) {
                bracketContent += char;
            } else {
                // Handle special CSS selector characters by adding spaces around them
                if (char === '>' || char === '+' || char === '~') {
                    // Add space before if there isn't one already
                    if (processedSelector.length > 0 && processedSelector[processedSelector.length - 1] !== ' ') {
                        processedSelector += ' ';
                    }
                    processedSelector += char;
                    // Add space after
                    if (i < selector.length - 1 && selector[i + 1] !== ' ') {
                        processedSelector += ' ';
                    }
                } else {
                    processedSelector += char;
                }
            }
        }
        
        // If we ended while still in brackets, add the remaining content
        if (bracketContent) {
            processedSelector += bracketContent;
        }
        
        // Split by spaces (not inside brackets)
        var tags = processedSelector.split(' ')
            .filter(tag => tag.trim() !== '')
            .map(tag => tag.replace(/___SPACE___/g, ' '));
        
        return tags;
    }  
    
    // Flag to prevent infinite loops
    let isUpdating = false;
    
    function checkChangedEditor() {
        try {
            
            // Get the HTML content from the editor
            const variationIndex = parseInt(jQuery("#variation-picker").val(), 10);
            let variationValue = window.abstEditor ? window.abstEditor.innerHTML : '';
            
            // Only clean if there's exactly one top-level div/p tag
            let cleanValue = variationValue || '';
            if (cleanValue && (cleanValue.trim().startsWith('<div') || cleanValue.trim().startsWith('<p'))) {
                const temp = document.createElement('div');
                temp.innerHTML = cleanValue;
                
                // Only clean if there's exactly one top-level element that's a div or p
                if (temp.children.length === 1 && 
                    (temp.firstElementChild.tagName === 'DIV' || 
                     temp.firstElementChild.tagName === 'P')) {
                    // Only use innerHTML if it's not empty
                    const inner = temp.firstElementChild.innerHTML.trim();
                    cleanValue = inner || cleanValue;
                }
            }
                    
            const selector = jQuery("#abst-selector-input").val();
            const variationType = getElementType(jQuery(selector)[0]);
            
            console.log('variation index', variationIndex);
            console.log('selector', selector);
            console.log('variation value', cleanValue);
            console.log('variation type', variationType);
            

            
            if (!window.abmagic) window.abmagic = {};
            if (!window.abmagic.definition) window.abmagic.definition = [];

            // Find if we already have this element in our definitions
            let elementIndex = -1;
            const currentElement = jQuery(selector)[0]; // Get the actual DOM element
            
            if (currentElement) {
                elementIndex = getElementIndexFromMagic(selector);
            }
            
            console.log('elementIndex after DOM element match:', elementIndex);

            if (elementIndex !== -1) {
                // Update existing variation with cleaned HTML
                window.abmagic.definition[elementIndex].variations[variationIndex] = cleanValue;
                console.log('updated variation', window.abmagic.definition[elementIndex].variations);
            } else {
                // Add new variation
                let originalValue = (variationType === 'image') ? 
                    jQuery(selector).attr('src') : 
                    jQuery(selector).html();
                
                // Process original value through the same cleaning as the editor content
                if (originalValue && variationType !== 'image') {
                    const temp = document.createElement('div');
                    temp.innerHTML = originalValue;
                    const firstChild = temp.firstElementChild;
                    if (firstChild && temp.children.length === 1 && 
                        (firstChild.tagName === 'DIV' || firstChild.tagName === 'P')) {
                        originalValue = firstChild.innerHTML;
                    } else {
                        originalValue = temp.innerHTML;
                    }
                }

                const newVariations = [];
                newVariations[0] = originalValue;
                newVariations[variationIndex] = cleanValue;
                
                window.abmagic.definition.push({
                    selector: selector,
                    variations: newVariations,
                    type: variationType || 'text'
                });
                
                elementIndex = window.abmagic.definition.length - 1;
            }
            
            // Update the hidden input for form submission
            jQuery('#abst-variation-data').val(JSON.stringify(window.abmagic.definition));
            
            // Update the preview
            if (variationType === 'image') {
                console.log('setting image src', cleanValue);
                jQuery(selector).attr('src', cleanValue);
            } else {
                jQuery(selector).html(cleanValue);
            }
            
            // Refresh all variation classes
            refreshVariationClasses();
            
        } catch (error) {
            console.error('Error in checkChangedEditor:', error);
        } 
    }


    function getElementIndexFromMagic(selector) {
        console.log('selector', selector);
        let foundIndex = -1;
        window.abmagic.definition.forEach(function(def, index) {
            console.log('def.selector', def.selector);
            if (def.selector === selector) {
                foundIndex = index;
                return;
            }
            if(jQuery(selector).is(def.selector)) {
                foundIndex = index;
                return;
            }
        });
        return foundIndex;
    }