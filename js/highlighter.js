var ab_highlight_timer;

// A magic test compares the original (A, slot 0) with one variation (B, slot 1).
var ABST_MAGIC_VERSIONS = 2;

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

function setVariationEditorActive(isActive) {
    jQuery('#variation-editor-container').toggleClass('is-active', !!isActive);
}

function setGoalsContainerActive(element, isActive) {
    jQuery('.abst-goals-container').removeClass('is-active');
    if (element) {
        jQuery(element).toggleClass('is-active', !!isActive);
    }
}

function updateUserRoleRowState() {
    jQuery('#abst-user-roles-container .abst-user-role').each(function() {
        var isChecked = jQuery(this).find('input[type="checkbox"]').is(':checked');
        jQuery(this).toggleClass('is-checked', isChecked);
    });
}

function isWithinSelectedMagicElement(target) {
    var selector = jQuery('#abst-selector-input').val();
    if (!selector || selector === 'Select an item' || selector === 'Select an item to start testing') {
        return false;
    }

    try {
        var $selected = jQuery(selector);
        if (!$selected.length) {
            return false;
        }

        var targetNode = target && target.nodeType ? target : null;
        if (!targetNode) {
            return false;
        }

        return $selected.filter(function() {
            return this === targetNode || jQuery.contains(this, targetNode);
        }).length > 0;
    } catch (e) {
        return false;
    }
}

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
    if(elem.length > 0 && !isInViewport(elem[0]) && !elem.hasClass('scrollingto')) {
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

  if ( self !== top ) // if inside an iframe, then its a preview and we dont want to do things.
    return;

  jQuery(document).on('mousedown', function(e) {
    var target = e.target;
    var keepActive = jQuery(target).closest('#variation-editor-container, .shepherd-element, .shepherd-modal-overlay-container').length > 0 || isWithinSelectedMagicElement(target);
    if (!keepActive) {
        setVariationEditorActive(false);
    }
  });

  jQuery('body').on('focusin click', '#variation-editor-container, #abst-selector-input, #abst-variation-editor-container, .abst-editor-toolbar button', function() {
    setVariationEditorActive(true);
  });

  jQuery(document).on('mousedown', function(e) {
    var target = e.target;
    var keepActive = jQuery(target).closest('.abst-goals-container, .shepherd-element, .shepherd-modal-overlay-container').length > 0;
    if (!keepActive) {
        jQuery('.abst-goals-container').removeClass('is-active');
    }
  });

  jQuery('body').on('focusin click', '.abst-goals-container', function() {
    setGoalsContainerActive(this, true);
  });


// https://example.com/?abmagic=1&abiframe=1&text_to_replace=Get%20a%20locker&variations=Reserve%20Your%20Locker%20Now%7CBook%20Your%20Locker%20Instantly%7CSecure%20Your%20Storage%20Spot%20Today
// if text_to_replace and variations are set, then log url decoded values of each
    if(/[?&]abmagic\b/.test(window.location.search) && window.location.search.includes('text_to_replace') && window.location.search.includes('variations'))
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
            let selector = getUniqueSelector(element);
            // getUniqueSelector already scopes the selector, so no need to call scopeSelectorToPage again
            if (!selector || jQuery(selector).length !== 1) {
                //get the unique selector for the element
                selector = generateShortPath(element);
            }

            //log element 
            console.log('Element:', element, selector);

            //set bg yellow opacity 50%
            element.style.backgroundColor = 'rgba(255, 255, 0, 0.5)';

            // Get variations from URL
            const variationsParam = urlParams.get('variations');
            // These land in the saved definition and are replayed via innerHTML to every
            // visitor, so scrub them here — the query string is attacker-controllable if an
            // admin is talked into opening a crafted link. (The raw textToReplace is still
            // used above for text matching, where it is never treated as markup.)
            const variationsArray = (variationsParam ? variationsParam.split('|') : [])
                .map(function (v) { return abstSanitizeCreatedHtml(v); });
            // The original plus the first suggested variation.
            const allVariations = [abstSanitizeCreatedHtml(textToReplace)].concat(variationsArray).slice(0, ABST_MAGIC_VERSIONS);
            
            // Initialize abmagic and create definition
            if (!window.abmagic) window.abmagic = {};
            if (!window.abmagic.definition) window.abmagic.definition = [];
            
            // Add to definition with all variations from URL
            window.abmagic.definition.push({
                selector: selector,
                variations: allVariations,
                scope: getMagicScope(),
                type: 'text'
            });

            if (window.setAbstMagicBarTab) window.setAbstMagicBarTab('test');
            jQuery('.click-to-start-help').hide();
            jQuery('.abst-magic-bar-footer').addClass('abst-magic-bar-footer-visible');
            jQuery('#variation-editor-container, .abst-goals-column, .abst-magic-bar-footer, #abst-targeting-button').slideDown();

            jQuery('.magic-test-name').css('display', 'flex').hide().slideDown();
            bt_highlight(selector);
            jQuery('#abst-selector-input').val(selector).trigger('blur');
            abstDrawerReveal(selector);
            
            // Set editor content
            if (window.abstEditor) {
                // Sanitized — same untrusted-URL-parameter reasoning as the variations above.
                window.abstEditor.innerHTML = abstSanitizeCreatedHtml(textToReplace);
                jQuery('#abst-variation-editor').val(abstSanitizeCreatedHtml(textToReplace));
            }
            
            // Update unified test object if available
            setTimeout(function(){
                const test_title = urlParams.get('test_title');
                if (window.abmagic.test) {
                    if (test_title) {
                        window.abmagic.test.title = test_title;
                    }
                    window.abmagic.syncToDOM();
                } else if (test_title) {
                    jQuery('#abst-magic-bar-title').val(test_title);
                }
                
                // Update variation picker to show all variations
                if (typeof updateVariationPicker === 'function') {
                    updateVariationPicker();
                }
                
                console.log('URL test created:', { selector, variations: allVariations, definition: window.abmagic.definition });
            }, 100);
        }
        
    }, 1000);

}
  
  // admin bar test helper...
  abstBuildAdminBar();
  // Re-run if config loads late (deferred by cache plugins)
  document.addEventListener('abst-config-ready', abstBuildAdminBar);

function abstBuildAdminBar() {
  // Clear previous admin bar content if re-running after late config load
  jQuery("#wp-admin-bar-ab-test ul.ab-submenu").empty();

  var submenus = '';
  var bt_variation_icon = '<div class="ab-split"></div>';
  var bt_split_test_icon = '<div class="ab-test-tube"></div>';
  var bt_link_icon = '<span class="ab-link"></span>';
  var magicResults = false;
   // Add New Magic Test link at the top
   submenus += '<li><a class="ab-item ab-sub-secondary" id="wp-admin-bar-ab-new-magic-test" href="#" onclick="abst_magic_bar();">✨ New Magic Test</a></li>';
   
   
   // Add heatmaps button third
   if(typeof btab_vars !== 'undefined' && btab_vars.abst_enable_user_journeys === '1') {
     if(typeof btab_vars.post_id !== 'undefined' && btab_vars.post_id) {
       heatmapUrl = bt_adminurl + 'edit.php?post_type=bt_experiments&page=abst-heatmaps&post=' + btab_vars.post_id + '&size=large&mode=clicks';
       submenus += '<li><a class="ab-item ab-sub-secondary" href="' + heatmapUrl + '" target="_blank">🔥 Heat/Click/Scroll Maps </a></li>';
     }
   }

   if(window.bt_experiments)
   {
    jQuery.each(bt_experiments, function(index,test){
      //each experiment
      let shownPrimary = false; 

      if(!jQuery('[bt-eid="'+index+'"]').length && bt_experiments[index].test_type !== 'magic') // if no tests on this page, then skip
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
                  magicItem.variations.slice(0, ABST_MAGIC_VERSIONS).forEach((variation, ix) => {
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
    });

   }

  var bt_variations_found = jQuery('[bt-eid]:not([bt-eid=""])[bt-variation]:not([bt-variation=""])');

  if(true)
  {
    // submenus += '<li><a class="ab-item ab-sub-secondary" id="ab-clear-test-cookies">Clear AB Test Cookies</a></li>'; // nobody uses this do they email us if you do.

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
} // end abstBuildAdminBar
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







// The conversion goal is either a page visit (conversion_page holds the page ID) or an
// element click (conversion_page is 'selector' and conversion_selector holds the CSS selector).
function getMagicPrimaryGoalFromExperiment(experiment) {
    if (!experiment) {
        return { type: 'page', value: '' };
    }

    if (experiment.conversion_page === 'selector') {
        return { type: 'selector', value: String(experiment.conversion_selector || '') };
    }

    var pageId = parseInt(experiment.conversion_page, 10);

    if (!isNaN(pageId) && pageId > 0 && String(pageId) === String(experiment.conversion_page)) {
        return { type: 'page', value: String(pageId) };
    }

    return { type: 'page', value: '' };
}

function normalizeMagicGoalType(type) {
    return type === 'selector' ? 'selector' : 'page';
}

// Read the goal card: the chosen goal type and the value for that type.
function getMagicGoalFromContainer($goalContainer) {
    if (!$goalContainer || !$goalContainer.length) {
        return { type: 'page', value: '' };
    }

    var type = normalizeMagicGoalType($goalContainer.find('.abst-goal-type-radio:checked').val());
    var value = type === 'selector'
        ? String($goalContainer.find('.abst-goal-selector-input').val() || '').trim()
        : String($goalContainer.find('.abst-goal-input-value').val() || '');

    return { type: type, value: value };
}

// Show the fields for one goal type. Values typed for the other type are kept, so
// switching back and forth loses nothing.
function setMagicGoalType($goalContainer, type) {
    if (!$goalContainer || !$goalContainer.length) {
        return;
    }

    type = normalizeMagicGoalType(type);
    $goalContainer.attr('data-goal-type', type);
    $goalContainer.find('.abst-goal-type-radio').each(function() {
        this.checked = this.value === type;
    });
    $goalContainer.find('.abst-goal-type-panel').each(function() {
        jQuery(this).prop('hidden', jQuery(this).attr('data-goal-panel') !== type);
    });

    if (type !== 'selector') {
        abstStopGoalPick();
    }
}

// A click goal's selector may end in |eventname (e.g. ".signup|submit"); only the CSS part is checked.
function abstIsValidGoalSelector(selector) {
    var css = String(selector || '').split('|')[0].trim();
    if (!css) {
        return false;
    }
    try {
        document.createDocumentFragment().querySelector(css);
        return true;
    } catch (e) {
        return false;
    }
}

function abstClearGoalNeeded($goalContainer) {
    if (!$goalContainer || !$goalContainer.length) {
        return;
    }
    $goalContainer.removeClass('abst-goal-needed').find('.abst-goal-needed-hint').remove();
}

/* Element-click goal: "Pick on page" (or switching to Element click with no selector yet)
 * makes the next click on the page choose the goal element instead of an element to test. */
window.abstGoalPicking = false;

function abstStartGoalPick($goalContainer) {
    window.abstGoalPicking = true;
    jQuery('html').addClass('abst-goal-picking');
    ($goalContainer && $goalContainer.length ? $goalContainer : jQuery('#abst-magic-bar .abst-goals-container'))
        .find('.abst-goal-pick-element').addClass('is-picking').attr('aria-pressed', 'true').text('Cancel')
        .end().find('.abst-goal-pick-hint').prop('hidden', false);
}

function abstStopGoalPick() {
    if (!window.abstGoalPicking && !jQuery('html').hasClass('abst-goal-picking')) {
        return;
    }
    window.abstGoalPicking = false;
    jQuery('html').removeClass('abst-goal-picking');
    jQuery('#abst-magic-bar .abst-goal-pick-element').removeClass('is-picking').attr('aria-pressed', 'false').text('Pick on page');
    jQuery('#abst-magic-bar .abst-goal-pick-hint').prop('hidden', true);
    jQuery('#selector-box').hide();
}

// The element a goal click lands on: the link or button around the click when there is
// one (clicking the text inside a button picks the button), otherwise the element itself.
function abstGoalPickTarget(target) {
    if (!target || target.nodeType !== 1 || !target.closest) {
        return null;
    }
    if (target === document.body || target === document.documentElement) {
        return null;
    }
    if (target.closest('#abst-magic-bar, #wpadminbar, #selector-box, .abst-variation-marker, .shepherd-element, .shepherd-modal-overlay-container, .abst-magic-ignore, .media-modal')) {
        return null;
    }
    return target.closest('a, button, input[type="submit"], input[type="button"], input[type="image"], [role="button"]') || target;
}

function abstUseGoalElement(element) {
    var selector = (element && typeof getUniqueSelector === 'function') ? getUniqueSelector(element) : '';
    var $goalContainer = jQuery('#abst-magic-bar .abst-goals-container').first();
    abstStopGoalPick();

    if (!selector || !$goalContainer.length) {
        return;
    }

    setMagicGoalType($goalContainer, 'selector');
    var $input = $goalContainer.find('.abst-goal-selector-input');
    $input.val(selector).trigger('change');
    abstClearGoalNeeded($goalContainer);
    bt_highlight(selector);

    $input.css({ transition: 'box-shadow 0.2s ease', boxShadow: '0 0 0 3px #4CAF50' });
    setTimeout(function() {
        $input.css('box-shadow', '');
    }, 1500);
}

// Look up a page title by ID and show it in the goal's page search box.
function showMagicGoalPageTitle($goalContainer, pageId) {
    if (!$goalContainer || !$goalContainer.length || !pageId) {
        return;
    }

    jQuery.ajax({
        url: abst_magic_data.ajax_url,
        dataType: 'json',
        data: {
            q: pageId,
            action: 'abst_page_selector',
            nonce: abst_magic_data.page_selector_nonce
        },
        success: function(pages) {
            if (!Array.isArray(pages) || !pages.length) {
                return;
            }

            var pageMatch = pages.find(function(page) {
                return Array.isArray(page) && String(page[0]) === String(pageId);
            }) || pages[0];

            if (!Array.isArray(pageMatch) || pageMatch.length < 2) {
                return;
            }

            $goalContainer.find('.abst-goal-page-input').val(pageMatch[1]);
            $goalContainer.find('.abst-goal-input-value').val(String(pageMatch[0]));
        }
    });
}

function applyMagicGoalToContainer($goalContainer, goal) {
    if (!$goalContainer || !$goalContainer.length || !goal) {
        return;
    }

    var goalType = normalizeMagicGoalType(goal.type);
    var goalValue = goal.value || '';
    setMagicGoalType($goalContainer, goalType);

    if (goalType === 'selector') {
        $goalContainer.find('.abst-goal-selector-input').val(goalValue);
        return;
    }

    $goalContainer.find('.abst-goal-input-value').val(goalValue);
    $goalContainer.find('.abst-goal-page-input').val('');

    if (goalValue) {
        showMagicGoalPageTitle($goalContainer, goalValue);
    }
}

// Build the page search box for the page-visit goal. The chosen page ID is kept in
// the hidden .abst-goal-input-value field.
function initMagicGoalPageSelector($goalContainer) {
    if (!$goalContainer || !$goalContainer.length || $goalContainer.find('.abst-page-select-container').length) {
        return;
    }

    var $pagePanel = $goalContainer.find('.abst-goal-page-panel').first();
    if (!$pagePanel.length) {
        $pagePanel = $goalContainer;
    }

    var inputId = 'page-search-' + Math.random().toString(36).substr(2, 9);
    var $container = jQuery('<div>', {
        class: 'abst-page-select-container',
        css: { position: 'relative' }
    });

    $container.append('<div class="abst-page-search-label">Search for a page:</div>');
    $container.append(jQuery('<input>', {
        type: 'text',
        id: inputId,
        class: 'abst-goal-page-input',
        placeholder: 'Type to search pages or click to see recent pages...',
        autocomplete: 'off'
    }));
    $pagePanel.append($container);

    var input = document.getElementById(inputId);
    var awesomplete = new Awesomplete(input, {
        minChars: 2,
        maxItems: 15,
        autoFirst: true,
        sort: false,
        item: function(text, input) {
            var item = Awesomplete.ITEM(text, input);
            item.dataset.value = text.value; // Store the ID
            return item;
        },
        replace: function(text) {
            this.input.value = text.label;
        }
    });

    // Show latest pages on focus
    jQuery(input).on('focus', function() {
        if (this.value) {
            return;
        }
        jQuery(input).addClass('loading');
        awesomplete.list = [{ label: 'Loading recent pages...', value: '' }];
        awesomplete.evaluate();

        jQuery.ajax({
            url: abst_magic_data.ajax_url,
            dataType: 'json',
            data: {
                q: 'recent',
                action: 'abst_page_selector',
                nonce: abst_magic_data.page_selector_nonce
            },
            success: function(pages) {
                if (pages && pages.length) {
                    awesomplete.list = pages.map(function(page) {
                        return { label: page[1], value: page[0] };
                    });
                    awesomplete.evaluate();
                } else {
                    awesomplete.list = [{ label: 'No recent pages found', value: '' }];
                    awesomplete.evaluate();
                    setTimeout(function() {
                        awesomplete.list = [];
                    }, 1500);
                }
            },
            complete: function() {
                jQuery(input).removeClass('loading');
            }
        });
    });

    // Search as the user types (debounced)
    var searchTimeout;
    jQuery(input).on('input', function() {
        var query = this.value;
        if (query.length < 2) {
            awesomplete.list = [];
            return;
        }

        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(function() {
            jQuery.ajax({
                url: abst_magic_data.ajax_url,
                dataType: 'json',
                data: {
                    q: query,
                    action: 'abst_page_selector',
                    nonce: abst_magic_data.page_selector_nonce
                },
                beforeSend: function() {
                    jQuery(input).addClass('loading');
                },
                success: function(pages) {
                    var items = (pages || []).map(function(page) {
                        var id = Array.isArray(page) ? page[0] : page.id || page;
                        var title = Array.isArray(page) ? page[1] : page.title || page;
                        return { label: title, value: id };
                    });

                    awesomplete.list = items;

                    if (items.length === 1 && String(items[0].label).toLowerCase() === query.toLowerCase()) {
                        awesomplete.select(0);
                    }

                    if (items.length > 0) {
                        awesomplete.evaluate();
                    }
                },
                complete: function() {
                    jQuery(input).removeClass('loading');
                }
            });
        }, 300);
    });

    // Keep only the numeric page ID in the hidden field
    jQuery(input).on('awesomplete-selectcomplete', function(e) {
        var selectedItem = e.originalEvent.text;
        if (selectedItem) {
            this.value = selectedItem.label || selectedItem;
            $goalContainer.find('.abst-goal-input-value')
                .val(selectedItem.value || selectedItem)
                .trigger('change');
        }
        return false;
    });

    var existingValue = $goalContainer.find('.abst-goal-input-value').val();
    if (existingValue) {
        showMagicGoalPageTitle($goalContainer, existingValue);
    }
}

function loadMagicTestFromUrl() {
    var urlParams = new URLSearchParams(window.location.search);
    var testId = urlParams.get('testid');

    if (!testId || typeof bt_experiments === 'undefined' || !bt_experiments[testId]) {
        return false;
    }

    var experiment = bt_experiments[testId];
    if (!experiment || experiment.test_type !== 'magic' || !experiment.magic_definition) {
        return false;
    }

    var magicDefinition;
    try {
        magicDefinition = JSON.parse(experiment.magic_definition);
    } catch (e) {
        console.error('ABST: Failed to parse magic_definition for test', testId, e);
        return false;
    }

    if (!Array.isArray(magicDefinition) || magicDefinition.length === 0) {
        return false;
    }

    // Each element holds the original and one variation. Older tests may list more
    // variations; only the first two are shown here and saved back.
    magicDefinition.forEach(function(def) {
        if (def && Array.isArray(def.variations) && def.variations.length > ABST_MAGIC_VERSIONS) {
            def.variations = def.variations.slice(0, ABST_MAGIC_VERSIONS);
        }
    });

    if (!window.abmagic) window.abmagic = {};
    window.abmagic.definition = magicDefinition;
    window.abmagic.test = window.abmagic.test || {};
    window.abmagic.editingTestId = testId;
    window.abmagic.scopeDirty = false;

    window.abmagic.test.title = experiment.name || window.abmagic.test.title || '';
    window.abmagic.test.url_query = experiment.url_query || '';
    window.abmagic.test.targeting = {
        device_size: experiment.target_option_device_size || 'all',
        traffic_percentage: parseInt(experiment.target_percentage, 10) || 100,
        allowed_roles: Array.isArray(experiment.allowed_roles) ? experiment.allowed_roles : (abst_magic_data.defaults || []),
        scope: getMagicScopeFromDefinition(magicDefinition)
    };
    window.abmagic.test.goals = {
        primary: getMagicPrimaryGoalFromExperiment(experiment)
    };

    if (window.setAbstMagicBarTab) window.setAbstMagicBarTab('test');
    jQuery('.click-to-start-help').hide();
    jQuery('.abst-magic-bar-footer').addClass('abst-magic-bar-footer-visible');
    jQuery('#variation-editor-container, .abst-goals-column, .abst-magic-bar-footer, #abst-targeting-button').show();
    jQuery('.magic-test-name').css('display', 'flex');
    jQuery('#abst-magic-bar-start').text('Update Test');

    if (typeof refreshVariationClasses === 'function') {
        refreshVariationClasses();
    }

    if (window.abmagic.syncToDOM) {
        window.abmagic.syncToDOM();
    }

    var firstDef = magicDefinition[0];
    if (firstDef && firstDef.selector) {
        var initialVariationIndex = firstDef.variations && firstDef.variations.length > 1 ? 1 : 0;
        jQuery('#variation-picker').val(String(initialVariationIndex));
        setMagicBar(
            firstDef.selector,
            (firstDef.variations && firstDef.variations[initialVariationIndex]) || (firstDef.variations && firstDef.variations[0]) || '',
            false,
            firstDef.type || 'text',
            true
        );
        jQuery('.abst-variation-marker .abst-marker-var').removeClass('active');
        jQuery('.abst-variation-marker .abst-marker-var[data-var="' + initialVariationIndex + '"]').addClass('active');
    }

    console.log('ABST: Loaded existing magic test into Magic Mode', testId, experiment);
    return true;
}

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

    // Validate selector on click - flash green if element exists, orange if not
    jQuery('body').on('click','#abst-selector-input',function(){
        var $inputElement = jQuery(this);
        var selectorValue = $inputElement.val();
        
        if(selectorValue && selectorValue.trim() !== '' && selectorValue !== 'Select an item to start testing') {
            try {
                var $targetElement = jQuery(selectorValue);
                if($targetElement.length > 0) {
                    // Element exists - flash green border on input and highlight element
                    $inputElement.css('transition', 'box-shadow 0.2s ease');
                    $inputElement.css('box-shadow', '0 0 0 3px #4CAF50');
                    bt_highlight(selectorValue);
                    setTimeout(function(){
                        $inputElement.css('box-shadow', '');
                    }, 1500);
                } else {
                    // Element doesn't exist - flash orange border on input
                    $inputElement.css('transition', 'box-shadow 0.2s ease');
                    $inputElement.css('box-shadow', '0 0 0 3px #FF9800');
                    setTimeout(function(){
                        $inputElement.css('box-shadow', '');
                    }, 1500);
                }
            } catch(e) {
                // Invalid selector syntax - flash orange
                $inputElement.css('transition', 'box-shadow 0.2s ease');
                $inputElement.css('box-shadow', '0 0 0 3px #FF9800');
                setTimeout(function(){
                    $inputElement.css('box-shadow', '');
                }, 1500);
            }
        }
    });

    // Remember the selector when the user starts editing the box, and only treat a
    // blur as an edit when they typed: the editor also sets the box and fires blur.
    jQuery('body').on('focus','#abst-selector-input',function(){
        jQuery(this).data('abstSelectorBaseline', jQuery(this).val());
    });
    jQuery('body').on('input','#abst-selector-input',function(){
        jQuery(this).data('abstUserEdited', true);
    });

    jQuery('body').on('blur','#abst-selector-input',function(){
        var $inputElement = jQuery(this); // Store jQuery object for the input element
        if (!$inputElement.data('abstUserEdited')) return;
        $inputElement.data('abstUserEdited', false);

        var oldSelector = $inputElement.data('abstSelectorBaseline') || '';
        var newSelectorValue = ($inputElement.val() || '').trim(); // Get the new selector value

        if(oldSelector !== newSelectorValue) {
            // A selector that matches nothing (or is invalid) would orphan the element.
            if (newSelectorValue !== '') {
                try { if (!jQuery(newSelectorValue).length) throw 0; }
                catch (e) { $inputElement.val(oldSelector); return; }
            }

            // Look the definition up by the selector it had before the edit; looking it
            // up by the new one never matched, so retargeting and removing did nothing.
            var elementIndex = getElementIndexFromMagic(oldSelector);
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
                }
                else if (window.abstEditor) {
                    // Update the definition with the new selector
                    //remove oldSelector from window.abmagic.definition
                    window.abmagic.definition[elementIndex].selector = newSelectorValue;
                    
                    // Set content in the contentEditable editor without triggering events
                    window.abstEditor.innerHTML = jQuery(newSelectorValue).html() || '';
                    setVariationEditorActive(getElementType(jQuery(newSelectorValue)[0]) !== 'image');
                    console.log('updated ', newSelectorValue);
                }
                
                // Refresh all variation classes after all data updates are complete
                refreshVariationClasses();
            }
        }
    });

    jQuery('body').on('click','.abst-variation',function(e){
        // Ignore clicks on the variation marker buttons
        if (jQuery(e.target).closest('.abst-variation-marker').length > 0) {
            return;
        }
        //abst-selector-input 
        e.preventDefault();
        var selector = getUniqueSelector(jQuery(this).first()[0]);
        var elementDef = window.abmagic && window.abmagic.definition ? window.abmagic.definition.find(function(def) {
            return def.selector === selector;
        }) : null;
        var variationIndex = parseInt(jQuery("#variation-picker").val(), 10) || 0;
        var elementType = elementDef && elementDef.type ? elementDef.type : getElementType(jQuery(this).first()[0]);
        var selectorText = elementDef && elementDef.variations ? (elementDef.variations[variationIndex] || elementDef.variations[0] || '') : jQuery(selector).html();
        setMagicBar(selector, selectorText, false, elementType);
        
    });


    function canTestOnElement(element){

        //if an element isnt an element, but a string, get it by query selector
        if(typeof element === 'string')
            element = document.querySelector(element);

        if(!element)
            return false;

        // Page UI that opts out of selection (e.g. a button that opens the Magic bar)
        if (element.closest && element.closest('.abst-magic-ignore'))
            return false;

        if (jQuery(element).closest('.abst-goals-column,.abst-goals-container, .remove-goal, .abst-goal-card-header, .abst-button-container').length > 0)
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
        
        // Skip variation marker controls. These live in the body overlay so they work for images.
        if (jQuery(element).hasClass('abst-variation-marker') || jQuery(element).closest('.abst-variation-marker').length > 0) {
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
        if (jQuery(element).parents('.mce-panel').length > 0 || jQuery(element).parents('#wpadminbar').length > 0 || jQuery(element).parents('.abst-variation').length > 0 || jQuery(element).parents('.shepherd-element').length > 0 || jQuery(element).parents('.shepherd-modal-overlay-container').length > 0) {
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

        // Choosing the goal element: outline whatever a click would pick.
        if (window.abstGoalPicking) {
            var pickElement = abstGoalPickTarget(element);
            if (!pickElement) {
                box.hide();
                return;
            }
            var pickRect = pickElement.getBoundingClientRect();
            box.css({
                zIndex: '158000',
                top: pickRect.top + window.scrollY - 10,
                left: pickRect.left + window.scrollX - 10,
                width: pickRect.width + 20,
                height: pickRect.height + 20,
                borderRadius: '10px'
            });
            box.show();
            lastProcessedElement = null;
            return;
        }

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
    }); // end mouseover
        
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



    
    // add element to magic bar click function
    jQuery('body').on('click', function(e){


        // Allow normal interaction inside the magic bar and admin bar UI
        if (e.target && (e.target.closest('#abst-magic-bar') || e.target.closest('#wpadminbar') || e.target.closest('.shepherd-element') || e.target.closest('.shepherd-modal-overlay-container') || e.target.closest('.abst-magic-ignore'))) {
            return;
        }

    
        e.preventDefault();
        e.stopImmediatePropagation();
        
        var element = e.target;


        if(!canTestOnElement(element)){
            return;
        }

        //check if element is clickable using our helper function
        var elementType = getElementType(element);
        if(!elementType)
            return;
        
        if (window.setAbstMagicBarTab) window.setAbstMagicBarTab('test');
        jQuery('.abst-magic-bar-footer').addClass('abst-magic-bar-footer-visible');
        jQuery('#variation-editor-container, .abst-goals-column, .abst-magic-bar-footer, #abst-targeting-button').slideDown();
        jQuery('.magic-test-name').css('display', 'flex').hide().slideDown();
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
    });


    
    // Choosing the goal element: the click sets the Element click goal's selector and goes
    // no further, so it neither follows a link nor selects the element for editing. Capture
    // phase on the document runs before every other click handler on the page.
    document.addEventListener('click', function(e) {
        if (!window.abstGoalPicking) {
            return;
        }
        var goalElement = abstGoalPickTarget(e.target);
        if (!goalElement) {
            return; // the Magic bar, admin bar and tour work as usual
        }
        e.preventDefault();
        e.stopPropagation();
        e.stopImmediatePropagation();
        abstUseGoalElement(goalElement);
    }, true);

    jQuery(document).on('keydown', function(e) {
        if (window.abstGoalPicking && (e.key === 'Escape' || e.key === 'Esc')) {
            abstStopGoalPick();
        }
    });

    // Prevent link clicks only when magic bar is active
    // This uses capture phase but only prevents when the class is present
    document.body.addEventListener('click', function(e) {
        // Only prevent if magic bar is active
        // The class is set on <html>, so checking <body> meant this guard never ran.
        if (!document.documentElement.classList.contains('doing-abst-magic-bar')) {
            return; // Allow normal behavior when magic bar is not active
        }
        
        let target = e.target;
        // Allow clicks on #wpadminbar and #abst-magic-bar (and their children)
        if (
            (target.closest && target.closest('#wpadminbar')) ||
            (target.closest && target.closest('#abst-magic-bar')) ||
            (target.closest && target.closest('.shepherd-element')) ||
            (target.closest && target.closest('.shepherd-modal-overlay-container')) ||
            (target.closest && target.closest('.abst-magic-ignore'))
        ) {
            return; // Allow normal behavior
        }
        // Traverse up in case the click is on a child inside the link
        while (target && target !== document.body) {
            if (target.tagName && target.tagName.toLowerCase() === 'a') {
                // preventDefault only: stopping propagation here (capture phase) would also
                // stop the editor's own click handler, so links could not be selected.
                e.preventDefault();
                return;
            }
            target = target.parentElement;
                    }
    }, true);
}

// Helper function to determine if an element is clickable for magic testing

function getElementType(element) {
    if (!element) return false; 
    
    var elementType = false; // Default to false for non-testable elements
    
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

function getCurrentMagicPagePath() {
    var path = window.location && window.location.pathname ? window.location.pathname.toLowerCase() : '';
    return path.replace(/^\/+|\/+$/g, '');
}

function getDefaultMagicScope() {
    var scope = {};

    if (window.btab_vars && window.btab_vars.post_id !== undefined && window.btab_vars.post_id !== null && window.btab_vars.post_id !== '') {
        var parsedId = parseInt(window.btab_vars.post_id, 10);
        if (!isNaN(parsedId) && parsedId > 0) {
            scope.page_id = parsedId;
        }
    }

    var path = getCurrentMagicPagePath();
    if (path) {
        scope.url = path;
    }

    if (scope.page_id === undefined && !scope.url) {
        scope.url = '*';
    }

    return scope;
}

function normalizeMagicScope(scope, fallbackToDefault) {
    if (fallbackToDefault === undefined) {
        fallbackToDefault = true;
    }

    var normalized = {};
    if (scope && typeof scope === 'object') {
        if (Object.prototype.hasOwnProperty.call(scope, 'page_id')) {
            if (scope.page_id === '*') {
                normalized.page_id = '*';
            } else {
                var parsedPageIds = [];
                if (Array.isArray(scope.page_id)) {
                    parsedPageIds = scope.page_id.map(function(id) {
                        return String(id).trim();
                    });
                } else {
                    parsedPageIds = String(scope.page_id || '').split(',').map(function(id) {
                        return id.trim();
                    });
                }

                parsedPageIds = parsedPageIds.filter(function(id) {
                    return /^[1-9]\d*$/.test(id);
                });

                if (parsedPageIds.length === 1) {
                    normalized.page_id = parseInt(parsedPageIds[0], 10);
                } else if (parsedPageIds.length > 1) {
                    normalized.page_id = parsedPageIds.map(function(id) {
                        return parseInt(id, 10);
                    }).filter(function(id, index, arr) {
                        return arr.indexOf(id) === index;
                    });
                }
            }
        }

        if (Object.prototype.hasOwnProperty.call(scope, 'url')) {
            var urlValue = String(scope.url || '').trim();
            if (urlValue === '*') {
                normalized.url = '*';
            } else if (urlValue !== '') {
                normalized.url = urlValue.toLowerCase().replace(/^\/+|\/+$/g, '');
            }
        }
    }

    var hasPageId = normalized.page_id !== undefined && normalized.page_id !== null && normalized.page_id !== '';
    var hasUrl = typeof normalized.url === 'string' && normalized.url !== '';
    if (!hasPageId && !hasUrl && fallbackToDefault) {
        return getDefaultMagicScope();
    }

    return normalized;
}

function getMagicScopeSignature(scope) {
    var normalized = normalizeMagicScope(scope, true);
    var signature = {};

    if (normalized.page_id !== undefined) {
        signature.page_id = Array.isArray(normalized.page_id) ? normalized.page_id.slice().sort(function(a, b) {
            return a - b;
        }) : normalized.page_id;
    }

    if (normalized.url !== undefined) {
        signature.url = normalized.url;
    }

    return JSON.stringify(signature);
}

function definitionHasMixedScopes(definition) {
    if (!Array.isArray(definition) || definition.length < 2) {
        return false;
    }

    var scopes = {};
    definition.forEach(function(item) {
        if (!item || typeof item !== 'object') {
            return;
        }

        scopes[getMagicScopeSignature(item.scope)] = true;
    });

    return Object.keys(scopes).length > 1;
}

function getMagicMixedScopeHelpText() {
    if (!window.abmagic || !window.abmagic.editingTestId || window.abmagic.scopeDirty) {
        return '';
    }

    if (!definitionHasMixedScopes(window.abmagic.definition)) {
        return '';
    }

    return ' This test currently uses different scopes per element. Leave this unchanged to preserve them, or edit scope here to apply one scope to all elements.';
}

function getMagicScopeFormState(scope) {
    var normalized = normalizeMagicScope(scope, true);
    var hasPageId = normalized.page_id !== undefined && normalized.page_id !== null && normalized.page_id !== '';
    var hasUrl = typeof normalized.url === 'string' && normalized.url !== '';

    if (normalized.page_id === '*' || normalized.url === '*') {
        return { mode: 'all', value: '' };
    }

    if (hasPageId) {
        if (Array.isArray(normalized.page_id)) {
            return { mode: 'page_id', value: normalized.page_id.join(',') };
        }
        return { mode: 'page_id', value: String(normalized.page_id) };
    }

    if (hasUrl) {
        return { mode: 'url', value: normalized.url };
    }

    return { mode: 'current', value: '' };
}

function getMagicScopeFromInputs() {
    var mode = jQuery('#abst-scope-mode').val() || 'current';
    var value = (jQuery('#abst-scope-value').val() || '').trim();

    if (mode === 'all') {
        return { page_id: '*' };
    }

    if (mode === 'page_id') {
        if (value === '*') {
            return { page_id: '*' };
        }
        var parts = value.split(',').map(function(part) {
            return part.trim();
        }).filter(function(part) {
            return part !== '';
        });

        if (parts.length && parts.every(function(part) { return /^[1-9]\d*$/.test(part); })) {
            var ids = parts.map(function(part) {
                return parseInt(part, 10);
            }).filter(function(id, index, arr) {
                return arr.indexOf(id) === index;
            });

            if (ids.length === 1) {
                return { page_id: ids[0] };
            }
            if (ids.length > 1) {
                return { page_id: ids };
            }
        }
        return getDefaultMagicScope();
    }

    if (mode === 'url') {
        if (value === '*') {
            return { url: '*' };
        }
        if (value !== '') {
            return { url: value.toLowerCase().replace(/^\/+|\/+$/g, '') };
        }
        return getDefaultMagicScope();
    }

    return getDefaultMagicScope();
}

function updateMagicScopeFormUi() {
    var mode = jQuery('#abst-scope-mode').val() || 'current';
    var $value = jQuery('#abst-scope-value');
    var $help = jQuery('#abst-scope-help');
    var defaultScope = getDefaultMagicScope();
    var mixedScopeHelpText = getMagicMixedScopeHelpText();

    if (!$value.length || !$help.length) {
        return;
    }

    if (mode === 'page_id') {
        $value.attr('placeholder', '42 or 42,108').show();
        $help.text('Choose where this magic test can appear. Use one or more page IDs (comma-separated), or * for all pages.' + mixedScopeHelpText);
        return;
    }

    if (mode === 'url') {
        $value.attr('placeholder', 'pricing').show();
        $help.text('Choose where this magic test can appear. Match pages when the current URL path contains this value, or use * for all pages.' + mixedScopeHelpText);
        return;
    }

    if (mode === 'all') {
        $value.hide();
        $help.text('Choose where this magic test can appear. This setting will apply it to all pages.' + mixedScopeHelpText);
        return;
    }

    $value.hide();
    if (defaultScope.page_id !== undefined && defaultScope.page_id !== null && defaultScope.page_id !== '') {
        $help.text('Choose where this magic test can appear. Default: current page ID ' + defaultScope.page_id + '.' + mixedScopeHelpText);
    } else if (defaultScope.url) {
        $help.text('Choose where this magic test can appear. Default: current page path "' + defaultScope.url + '".' + mixedScopeHelpText);
    } else {
        $help.text('Choose where this magic test can appear. Default: current page.' + mixedScopeHelpText);
    }
}

function getMagicScopeFromDefinition(definition) {
    if (!Array.isArray(definition) || !definition.length) {
        return getDefaultMagicScope();
    }

    var firstItem = definition[0];
    if (!firstItem || typeof firstItem !== 'object') {
        return getDefaultMagicScope();
    }

    return normalizeMagicScope(firstItem.scope, true);
}

function getMagicScope() {
    if (window.abmagic && window.abmagic.test && window.abmagic.test.targeting && window.abmagic.test.targeting.scope) {
        return normalizeMagicScope(window.abmagic.test.targeting.scope, true);
    }

    if (jQuery('#abst-scope-mode').length) {
        return normalizeMagicScope(getMagicScopeFromInputs(), true);
    }

    return getDefaultMagicScope();
}

// The third argument is unused and kept so existing callers keep their argument order.
function setMagicBar(selector, selectorText, unused = false, type = 'text', quiet = false) {
    if(!window.abmagic) window.abmagic = {};
    if(!window.abmagic.definition) window.abmagic.definition = [];


    // Check if selector is specific enough
    if(jQuery(selector).length > 2) {
        console.log('Selector has more than 2 results, please choose a more specific selector, or click the element and we\'ll do it for you.');
        return;
    }

    jQuery('#abst-selector-input').val(selector).trigger('blur');
    abstDrawerReveal(selector);
    
    jQuery("#abst-variation-editor-container").addClass('flash');
    setTimeout(function(){
        jQuery("#abst-variation-editor-container").removeClass('flash');
    }, 2000);

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
                if (!quiet) {
                    setVariationEditorActive(true);
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
        jQuery('.abst-editor-toolbar').hide();

        //CHANGE HEIGHT OF EDITOR CONTAINER TO 2 LINES
        jQuery('#abst-variation-editor-container').height(70);
        
        // Set the image in the preview
        jQuery(selector).attr('src', selectorText).removeAttr('srcset');
        
        // Add image selector button if it doesn't exist
        var isControl = parseInt(jQuery("#variation-picker").val(), 10) === 0;
        if(jQuery("#imageSelector").length < 1) {
            jQuery("#abst-variation-editor-container").after('<button type="button" id="imageSelector">Choose from Media Library</button>');
            jQuery("#imageSelector").on('click', function(){
                jQuery('.shepherd-modal-overlay-container').hide();
                file_frame.open();
            });
        }
        else{
            jQuery("#imageSelector").show();
        }
        jQuery("#imageSelector").prop('disabled', isControl).css('opacity', isControl ? '0.5' : '1');
        
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
            
            // Show Shepherd overlay again
            jQuery('.shepherd-modal-overlay-container').show();
            
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
        
        // Show overlay when media library is closed
        file_frame.off('close');
        file_frame.on('close', function() {
            jQuery('.shepherd-modal-overlay-container').show();
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
function abst_magic_bar(options = {}) {

    console.log('abst_magic_bar called');

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
            rolesHtml += '<label class="abst-user-role"><input type="checkbox" name="roles[]" value="' + key + '" checked><span>' + value + '</span></label>';
        else
            rolesHtml += '<label class="abst-user-role"><input type="checkbox" name="roles[]" value="' + key + '"><span>' + value + '</span></label>';
    });
    // Add content
    // --- BEGIN MAGIC BAR HTML REPLACEMENT ---
    // Build dynamic user roles
    var rolesHtml = '';
    jQuery.each(abst_magic_data.roles, function(key, value) {
        if (abst_magic_data.defaults && abst_magic_data.defaults.includes(key))
            rolesHtml += '<label class="abst-user-role"><input type="checkbox" name="roles[]" value="' + key + '" checked><span>' + value + '</span></label>';
        else
            rolesHtml += '<label class="abst-user-role"><input type="checkbox" name="roles[]" value="' + key + '"><span>' + value + '</span></label>';
    });

    // The original (A) and the variation (B).
    var variationOptions = [
        {  label: 'A Version - Control', selected: false, value: 0 },
        { label: 'B Version', selected: true, value: 1 },
    ];
    var variationOptionsHtml = variationOptions.map(function(opt, index) {
        return '<option value="' + opt.value + '" ' + (opt.selected ? 'selected' : '') + '>' + opt.label + '</option>';
    }).join('');

    magicBar.innerHTML = `
    <div class="abst-magic-bar-container" data-llm-instructions="this div contains instructions for LLM assistance 
            #abst-magic-bar-title is the test name. add your descriptive title
            #abst-selector-input gives you the css selector of the selected element, if any is selected.
            Each test compares the original (A version) with one variation (B version). #variation-picker switches between them; #abst-version-toggle does the same.
            #abst-variation-editor-container is the way to edit the B version after you have selected an element.
            You can add additional elements to a test, like a subhero under a hero for example. To add an additional element to the test, click it and edit its B version. you'll see #abst-selector-input update to the new element. All elements switch together, so visitors see either every original or every B version.
            The conversion goal is set in the Goal box and is one of two types. Page visit: a conversion counts when a visitor reaches a page, such as a thank-you page; search for it and pick it from the list. Element click: a conversion counts when a visitor clicks an element; type its CSS selector (e.g. #buy-now or .signup-button) or press Pick on page and click the element. After saving test, you can go to /wp-admin/edit.php?post_type=bt_experiments to view all tests. do not edit other tests unless  specifically asked">
        <div class="abst-magic-bar-header">
            <span class="abst-magic-bar-heading">Magic Test</span>
            <button type="button" id="abst-magic-bar-show-tour" class="abst-magic-tour-button" title="Show me how it works">How it works</button>
        </div>
        <div class="abst-magic-tab-panels">
        <div class="abst-magic-tab-panel is-active" id="abst-magic-panel-test" data-tab-panel="test">
        <!-- Click to start help -->
        <div class="abst-settings-column click-to-start-help">
            <h3 style="color: #9e9e9e;">Create a Split Test.</h3>
            <p style="font-size: 24px; line-height: 1.25; margin: 40px 0 8px;">To start: Click the element you want to change.</p>
        </div>
        <!-- Test Name - Hidden until test starts -->
        <div class="abst-settings-column magic-test-name" style="padding: 8px 15px; display: none; align-items: center; gap: 10px;">
            <label for="abst-magic-bar-title" style="font-weight: 600; white-space: nowrap;">Test Name:</label>
            <input id="abst-magic-bar-title" class="abst-magic-bar-title" value="New Magic Test" style="flex: 1;">
        </div>
        <div id="variation-picker-container" style="display:none !important;">
            <select id="variation-picker">${variationOptionsHtml}</select>
        </div>
        <div class="abst-settings-column" id="variation-editor-container">
            <div class="abst-goals-title" style="display: flex; align-items: center; justify-content: center; gap: 8px;">Element <input id="abst-selector-input" type="text" value="Select an item" placeholder="CSS Selector" style="background: transparent; color: #999; font-size: 13px; padding: 0; flex: 1; text-align: center; margin: 0 !important; height: 30px;"></div>
            <p id="version-value" class="abst-version-value">
                <span class="abst-version-prefix">Editing</span>
                <button type="button" id="abst-version-toggle" class="abst-version-toggle" aria-label="Click to swap between variations" title="Click to swap between variations">B version</button>
                <button type="button" id="abst-version-swap" class="abst-version-swap" aria-label="Click to swap between variations" title="Click to swap between variations">
                    <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                        <path d="M7 7h11l-3-3m3 3-3 3M17 17H6l3 3m-3-3 3-3"></path>
                    </svg>
                </button>
            </p>
            <div id="abst-variation-editor"></div>
            </div>
            <p id="abst-targeting-button"><span id="abst-targeting-text">Testing on all users except editors &amp; administrators. </span><a href="#" id="abst-show-targeting">Edit</a></p>
            <div class="abst-settings-column abst-targeting-settings" data-llm-instructions="this div contains the targeting settings for the test. it is hidden by default and can be toggled by clicking the #abst-show-targeting button only add targeting if specifically asked or logical to change, otherwise leave as default. ">
                <div class="abst-settings-title">Targeting</div>
                <div class="abst-settings-header closed" tabindex="0" aria-expanded="false">User Roles</div>
                <div class="abst-targeting-option abst-hidden" id="abst-user-roles-container">
                    <div class="abst-url-help">Choose which logged-in roles can see this test. Usually it is best to keep this focused on visitors and customer-facing users.</div>
                    ${rolesHtml}
                </div>
                <div class="abst-settings-header closed" tabindex="0" aria-expanded="false">Device Size</div>
                <div class="abst-targeting-option abst-hidden">
                    <div class="abst-url-help">Limit this test to specific screen sizes if the layout, copy, or offer changes between desktop, tablet, and mobile.</div>
                    <select id="abst-device-size" class="abst-select">
                        <option value="all" selected>All Sizes</option>
                        <option value="desktop">Desktop (over 767px)</option>
                        <option value="desktop_tablet">Desktop + Tablet</option>
                        <option value="tablet">Tablet (between 479px and 767px)</option>
                        <option value="tablet_mobile">Tablet + Mobile</option>
                        <option value="mobile">Mobile (under 479px)</option>
                    </select>
                </div>
                <div class="abst-settings-header closed" tabindex="0" aria-expanded="false">URL Filtering</div>
                <div class="abst-targeting-option abst-hidden">
                    <div class="abst-url-help">Match traffic by URL rules. Use <code>utm_source</code> for a query key, <code>utm_source=google</code> for an exact query match, separate OR rules with <code>|</code> or commas, use <code>NOT </code> to exclude, and use <code>*pricing*</code> to match text anywhere in the full URL.</div>
                    <input type="text" id="abst-url-query" class="abst-url-input" placeholder="utm_source=Google">
                </div>
                <div class="abst-settings-header closed" tabindex="0" aria-expanded="false">Scope</div>
                <div class="abst-targeting-option abst-hidden">
                    <div id="abst-scope-help" class="abst-url-help">Choose where this magic test can appear. By default it only runs on the current page.</div>
                    <select id="abst-scope-mode" class="abst-select">
                        <option value="current" selected>Current Page (Default)</option>
                        <option value="page_id">Specific Page ID</option>
                        <option value="url">URL Contains</option>
                        <option value="all">All Pages</option>
                    </select>
                    <input type="text" id="abst-scope-value" class="abst-url-input" placeholder="42" style="margin-top:8px; display:none;">
                </div>
                <div class="abst-settings-header closed" tabindex="0" aria-expanded="false">Traffic Allocation Percentage</div>
                <div class="abst-targeting-option abst-hidden">
                    <div class="abst-url-help">Control how much eligible traffic sees this test. Use 100% to show it to everyone who matches the targeting rules.</div>
                    <input type="number" id="abst-traffic-percentage" class="abst-number-input" value="100" min="1" max="100">
                </div>
            </div>
            <!-- Goal Column -->
            <div class="abst-goals-column" data-llm-instructions="the conversion goal is defined here. choose one of two types with the .abst-goal-type-radio inputs. Page visit (value page): a conversion counts when a visitor reaches a page, such as a thank-you or order-complete page; search for the page and pick it from the list. Element click (value selector): a conversion counts when a visitor clicks an element, such as a buy or sign-up button; type its CSS selector in .abst-goal-selector-input, or press Pick on page and click the element.">
                <div class="abst-goals-title">Goal</div>
                <div class="abst-goals-container" data-goal="0" data-goal-type="page">
                    <div class="abst-goal-card-header"><p class="abst-goal-card-title">Conversion Goal</p></div>
                    <div class="abst-goal-type-toggle" role="radiogroup" aria-label="Goal type">
                        <label class="abst-goal-type-option"><input type="radio" class="abst-goal-type-radio" name="abst-goal-type" value="page" checked><span>Page visit</span></label>
                        <label class="abst-goal-type-option"><input type="radio" class="abst-goal-type-radio" name="abst-goal-type" value="selector"><span>Element click</span></label>
                    </div>
                    <div class="abst-goal-type-panel abst-goal-page-panel" data-goal-panel="page">
                        <div class="goal-value-label">Choose the page visitors reach when they convert, such as a thank-you page.</div>
                        <input type="hidden" class="abst-goal-input-value" value="">
                    </div>
                    <div class="abst-goal-type-panel abst-goal-selector-panel" data-goal-panel="selector" hidden>
                        <div class="goal-value-label" id="abst-goal-selector-help">A conversion counts when a visitor clicks an element that matches this CSS selector, such as a buy or sign-up button.</div>
                        <div class="abst-goal-selector-row">
                            <input type="text" id="abst-goal-selector" class="abst-goal-selector-input" placeholder="#buy-now or .signup-button" autocomplete="off" spellcheck="false" aria-label="CSS selector of the element visitors click" aria-describedby="abst-goal-selector-help">
                            <button type="button" class="abst-goal-pick-element" aria-pressed="false">Pick on page</button>
                        </div>
                        <p class="abst-goal-pick-hint" role="status" hidden>Click the element on the page that visitors click to convert. Press Esc to cancel.</p>
                    </div>
                </div>
            </div>
        </div>
        </div>
        <div class="abst-magic-bar-footer">
            <button id="abst-magic-bar-save-draft" class="abst-magic-bar-save-draft">Save Draft</button>
            <button id="abst-magic-bar-start" class="abst-magic-bar-start">Start Test</button>
        </div>
        </div>

    </div>`;


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
    abstInitMagicDrawer(magicBar);

    // Build the conversion page search box for the goal
    initMagicGoalPageSelector(jQuery('.abst-goals-container').first());

    window.abmagic = window.abmagic || {};
    // The Magic Bar has a single "test" panel; callers still switch to it by name.
    window.setAbstMagicBarTab = function(tabName) {
        var $magicBar = jQuery('#abst-magic-bar');
        var $targetPanel = $magicBar.find('.abst-magic-tab-panel[data-tab-panel="' + tabName + '"]');

        if (!$magicBar.length || !$targetPanel.length) {
            return;
        }

        $magicBar.find('.abst-magic-tab-panel')
            .removeClass('is-active')
            .attr('hidden', true);

        $targetPanel
            .addClass('is-active')
            .removeAttr('hidden');

        $magicBar
            .removeClass('abst-active-tab-test')
            .addClass('abst-active-tab-' + tabName);

        window.abmagic.activeTab = tabName;
    };

    window.setAbstMagicBarTab('test');



    
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
        // Typing in HTML mode goes straight into the editor, so saving or switching
        // variation while still in HTML mode keeps the edit.
        htmlEditor.addEventListener('input', function() {
            if (window.abstEditor) {
                window.abstEditor.innerHTML = htmlEditor.value;
                checkChangedEditor();
            }
        });
        // When another element or variation is loaded into the editor while HTML mode
        // is open, show that content instead of leaving the previous element's HTML.
        new MutationObserver(function() {
            if (htmlEditor.style.display === 'block' && document.activeElement !== htmlEditor) {
                htmlEditor.value = window.abstEditor.innerHTML;
            }
        }).observe(window.abstEditor, { childList: true, subtree: true, characterData: true });
        
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
            
            // Nothing to copy back: the textarea writes into the editor as you type.
            // Copying here overwrote an element loaded while HTML mode was open.
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
    const defaultTitle = 'Test ' + formatted;
    jQuery('.abst-magic-bar-title').val(defaultTitle);
   
    // ========================================
    // UNIFIED TEST OBJECT - Single source of truth
    // ========================================
    if (!window.abmagic) window.abmagic = {};
    if (!window.abmagic.definition) window.abmagic.definition = [];
    
    // Initialize the unified test object
    window.abmagic.test = {
        title: defaultTitle,
        url_query: '',
        targeting: {
            device_size: ['desktop', 'tablet', 'mobile'],
            traffic_percentage: 100,
            allowed_roles: abst_magic_data.defaults || [],
            scope: getDefaultMagicScope()
        },
        goals: {
            primary: { type: 'page', value: '' }
        }
    };
    
    // Sync DOM → Object (called when DOM changes)
    window.abmagic.syncFromDOM = function() {
        if (window.abmagic.isSyncingToDOM) {
            return;
        }

        var test = window.abmagic.test;
        
        // Title
        test.title = jQuery('#abst-magic-bar-title').val() || '';
        
        // URL query
        test.url_query = jQuery('#abst-url-query').val() || '';
        
        // Targeting
        test.targeting.device_size = jQuery('#abst-device-size').val() || ['desktop', 'tablet', 'mobile'];
        test.targeting.traffic_percentage = parseInt(jQuery('#abst-traffic-percentage').val()) || 100;
        test.targeting.allowed_roles = jQuery('#abst-user-roles-container input[type="checkbox"]:checked').map(function() {
            return jQuery(this).val();
        }).get();
        test.targeting.scope = getMagicScopeFromInputs();
        
        // Goal: a page visit (page ID) or an element click (CSS selector)
        test.goals.primary = getMagicGoalFromContainer(jQuery('.abst-goals-container').first());
        
        console.log('ABST: Synced from DOM', window.abmagic.test);
    };
    
    // Sync Object → DOM (called when object changes programmatically)
    window.abmagic.syncToDOM = function() {
        var test = window.abmagic.test;
        var primaryGoal = test.goals && test.goals.primary ? {
            type: normalizeMagicGoalType(test.goals.primary.type),
            value: test.goals.primary.value
        } : null;

        window.abmagic.isSyncingToDOM = true;
        
        // Title
        jQuery('#abst-magic-bar-title').val(test.title);
        
        // URL query
        jQuery('#abst-url-query').val(test.url_query);
        
        // Targeting - device size
        if (jQuery('#abst-device-size').length) {
            jQuery('#abst-device-size').val(test.targeting.device_size);
        }
        
        // Targeting - traffic percentage
        jQuery('#abst-traffic-percentage').val(test.targeting.traffic_percentage);
        
        // Targeting - allowed roles
        jQuery('#abst-user-roles-container input[type="checkbox"]').each(function() {
            var role = jQuery(this).val();
            jQuery(this).prop('checked', test.targeting.allowed_roles.includes(role));
        });

        // Targeting - scope
        var scopeFormState = getMagicScopeFormState(test.targeting.scope);
        jQuery('#abst-scope-mode').val(scopeFormState.mode);
        jQuery('#abst-scope-value').val(scopeFormState.value);
        updateMagicScopeFormUi();
        
        // Goal
        if (primaryGoal) {
            applyMagicGoalToContainer(jQuery('.abst-goals-container').first(), primaryGoal);
        }
        
        // Update variation picker for definition changes
        if (typeof updateVariationPicker === 'function') {
            updateVariationPicker();
        }

        window.abmagic.isSyncingToDOM = false;
        
        console.log('ABST: Synced to DOM', window.abmagic.test);
    };
    
    // Bind DOM change events to sync to object
    jQuery(document).on('change input', '#abst-magic-bar-title, #abst-url-query, #abst-device-size, #abst-traffic-percentage, #abst-scope-mode, #abst-scope-value', function() {
        var fieldId = jQuery(this).attr('id');
        if ((fieldId === 'abst-scope-mode' || fieldId === 'abst-scope-value') && window.abmagic && !window.abmagic.isSyncingToDOM) {
            window.abmagic.scopeDirty = true;
        }
        if (fieldId === 'abst-scope-mode') {
            updateMagicScopeFormUi();
        }
        window.abmagic.syncFromDOM();
    });
    jQuery(document).on('change', '#abst-user-roles-container input[type="checkbox"]', function() {
        updateUserRoleRowState();
        window.abmagic.syncFromDOM();
    });
    jQuery(document).on('change', '.abst-goals-container .abst-goal-input-value', function() {
        abstClearGoalNeeded(jQuery(this).closest('.abst-goals-container'));
        window.abmagic.syncFromDOM();
    });

    // Goal type: page visit or element click.
    jQuery(document).on('change', '#abst-magic-bar .abst-goal-type-radio', function() {
        var $goalContainer = jQuery(this).closest('.abst-goals-container');
        var goalType = normalizeMagicGoalType(jQuery(this).val());

        setMagicGoalType($goalContainer, goalType);
        abstClearGoalNeeded($goalContainer);

        if (goalType === 'selector') {
            var $selectorInput = $goalContainer.find('.abst-goal-selector-input');
            // No selector yet: the next click on the page chooses the element (typing works too).
            if (!String($selectorInput.val() || '').trim()) {
                abstStartGoalPick($goalContainer);
            }
            $selectorInput.trigger('focus');
        }

        window.abmagic.syncFromDOM();
    });

    jQuery(document).on('input change', '#abst-magic-bar .abst-goal-selector-input', function(e) {
        if (e.type === 'input') {
            abstStopGoalPick(); // typing a selector instead of picking one
        }
        if (String(jQuery(this).val() || '').trim()) {
            abstClearGoalNeeded(jQuery(this).closest('.abst-goals-container'));
        }
        window.abmagic.syncFromDOM();
    });

    jQuery(document).on('click', '#abst-magic-bar .abst-goal-pick-element', function(e) {
        e.preventDefault();
        if (window.abstGoalPicking) {
            abstStopGoalPick();
        } else {
            abstStartGoalPick(jQuery(this).closest('.abst-goals-container'));
        }
    });
    updateUserRoleRowState();
    updateMagicScopeFormUi();
    if (window.location.search.includes('testid')) {
        setTimeout(function() {
            loadMagicTestFromUrl();
        }, 80);
    }

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

    }, 10);

    // Function to close the magic bar
    window.close_abst_magic_bar = function() {
        if (window.abmagic && window.abmagic.definition && window.abmagic.definition.length && !confirm('Close the test editor? Unsaved changes will be lost.')) {
            return;
        }

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
        // Phones: the bar is a bottom drawer and nothing is squeezed, so fixed elements
        // (sticky headers, modals) keep their own layout.
        if (activate && typeof abstIsDrawer === 'function' && abstIsDrawer()) activate = false;
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
(function($) {
    $(function() {
        
        //if url contains query string abmagic then load magic bar
        // Only the abmagic parameter itself, not any query string that contains the text (utm_campaign=abmagicx).
        if(/[?&]abmagic\b/.test(window.location.search)) {
            abst_magic_bar();
        }
        


        jQuery("body").on('click','.abst-settings-header',function(){
            var $header = jQuery(this);
            var $option = $header.next('.abst-targeting-option');
            var isClosed = $header.hasClass('closed');

            $header.toggleClass('closed', !isClosed).attr('aria-expanded', isClosed ? 'true' : 'false');

            if (isClosed) {
                $option.removeClass('abst-hidden').hide().stop(true, true).slideDown(220);
            } else {
                $option.stop(true, true).slideUp(150, function() {
                    $option.addClass('abst-hidden');
                });
            }
        });

        jQuery("body").on('click','#abst-show-targeting',function(){
            jQuery('.abst-targeting-settings').slideToggle();
            jQuery('#abst-targeting-text').text('Custom targeting.');
        })

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
                //not found, add - but only if element type is valid
                if (!variationType) {
                    console.log('Skipping element - not a testable type2 :', selector);
                    return;
                }
                
                var originalValue;
                if (variationType === 'image') {
                    originalValue = jQuery(selector).attr('src') || '';
                } else {
                    originalValue = jQuery(selector).html() || '';
                }

                var newVariations = [];
                newVariations[0] = originalValue || ''; // Ensure it's never null/undefined
                newVariations[variationIndex] = variationValue || ''; // Ensure it's never null/undefined

                var newDef = {
                    type: variationType,
                    selector: selector,
                    scope: getMagicScope(),
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




        // 0 = the original (A), 1 = the variation (B).
        function getCurrentVariationIndex() {
            return parseInt(jQuery("#variation-picker").val(), 10) === 1 ? 1 : 0;
        }

        function getVariationVersionName(variationIndex) {
            var optionText = jQuery('#variation-picker option[value="' + variationIndex + '"]').text();
            optionText = optionText.replace(/\s*-\s*Control\s*$/i, '').trim();

            if (!optionText) {
                optionText = getVariationLabel(variationIndex) + ' Version';
            }

            return optionText.replace(/\bVersion\b/g, 'version');
        }

        function updateVersionValue() {
            var variationIndex = getCurrentVariationIndex();
            var versionName = getVariationVersionName(variationIndex);
            var actionText = variationIndex === 0 ? 'Viewing' : 'Editing';

            jQuery("#version-value .abst-version-prefix").text(actionText);
            jQuery("#abst-version-toggle").text(versionName).attr('aria-label', 'Click to swap between variations').attr('title', 'Click to swap between variations');
            jQuery("#abst-version-swap").attr('aria-label', 'Click to swap between variations').attr('title', 'Click to swap between variations');
        }

        // Swap between the original and the variation.
        function cycleMagicVariation() {
            var nextIndex = getCurrentVariationIndex() === 0 ? 1 : 0;

            jQuery("#variation-picker").val(String(nextIndex)).trigger('change');
        }

        jQuery('body').on('change', "#variation-picker", function() {
            var variationIndex = getCurrentVariationIndex();
            updateVersionValue();

            // if its 0 then make editor read-only
            if (window.abstEditor) {
                if (variationIndex === 0) {
                    window.abstEditor.contentEditable = 'false';
                    window.abstEditor.style.backgroundColor = '#f5f5f5';
                } else {
                    window.abstEditor.contentEditable = 'true';
                    window.abstEditor.style.backgroundColor = '#fff';
                }
            }

            if (variationIndex === 0) {
                jQuery("#imageSelector").prop('disabled', true).css('opacity', '0.5');
            } else {
                jQuery("#imageSelector").prop('disabled', false).css('opacity', '1');
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
                setVariationEditorActive(elementDef.type !== 'image');
                
                // Update the hidden input
                jQuery('#abst-variation-data').val(JSON.stringify(window.abmagic.definition));
                
                // Trigger input event to mark as changed
                const event = new Event('input', { bubbles: true });
                window.abstEditor.dispatchEvent(event);
            }
        });

        jQuery('body').on('click', "#abst-version-toggle, #abst-version-swap", function(e) {
            e.preventDefault();
            cycleMagicVariation();
        });


        
        
        jQuery('body').on('click','[magic-eid]',function(){
            showMagicTest(jQuery(this).attr('magic-eid'), jQuery(this).attr('magic-index'),true);
        });


        function saveMagicTest(postStatus){
            //get all magic data
            postStatus = postStatus || 'publish';
            var isDraftSave = postStatus === 'draft';

            //if theres no magicdata then alert
            if(!window.abmagic || !window.abmagic.definition || window.abmagic.definition.length == 0) {
                alert('Please add at least one element to the test');
                return;
            }

            if (!isDraftSave) {
                var goalToCheck = getMagicGoalFromContainer(jQuery('.abst-goals-container').first());
                if (!goalToCheck.value) {
                    abstNeedGoal();
                    return;
                }
                if (goalToCheck.type === 'selector' && !abstIsValidGoalSelector(goalToCheck.value)) {
                    abstNeedGoal('invalid');
                    return;
                }
            }

            abstStopGoalPick();

            // Sync from DOM one final time before save
            if (window.abmagic.syncFromDOM) {
                window.abmagic.syncFromDOM();
            }

            var selectedScope = getMagicScope();
            var preservePerElementScopes = !!(
                window.abmagic &&
                window.abmagic.editingTestId &&
                !window.abmagic.scopeDirty &&
                definitionHasMixedScopes(window.abmagic.definition)
            );

            // Validate and sanitize magic_definition before sending
            var sanitizedDefinition = [];
            if (window.abmagic && window.abmagic.definition && Array.isArray(window.abmagic.definition)) {
                sanitizedDefinition = window.abmagic.definition.map(function(item) {
                    if (!item || typeof item !== 'object') return null;
                    
                    var sanitized = {
                        type: item.type || 'text',
                        selector: item.selector || ''
                    };

                    if (preservePerElementScopes && item.scope) {
                        sanitized.scope = normalizeMagicScope(item.scope, true);
                    } else {
                        sanitized.scope = normalizeMagicScope(selectedScope, true);
                    }
                    
                    // Sanitize variations array: the original (A) and the variation (B)
                    if (item.variations && Array.isArray(item.variations)) {
                        sanitized.variations = item.variations.slice(0, ABST_MAGIC_VERSIONS).map(function(variation) {
                            // Convert null/undefined to empty string
                            if (variation === null || variation === undefined) return '';
                            // Ensure it's a string
                            return String(variation);
                        });
                    } else {
                        sanitized.variations = [''];
                    }
                    // An element never edited in B shows its original there.
                    while (sanitized.variations.length < ABST_MAGIC_VERSIONS) {
                        sanitized.variations.push(sanitized.variations[0]);
                    }
                    
                    return sanitized;
                }).filter(function(item) { return item !== null; }); // Remove null items
            }

            window.abmagic.definition = sanitizedDefinition;
            
            // Read from unified test object (same data structure sent to server)
            var test = window.abmagic.test;
            var primaryGoal = test.goals.primary || { type: 'page', value: '' };
            var primaryGoalType = normalizeMagicGoalType(primaryGoal.type);

            var newTestData = {
                action: 'abst_create_new_on_page_test',
                nonce: (typeof btab_vars !== 'undefined' ? btab_vars.magic_nonce : ''),
                abst_magic_mode: 1,
                post_title: test.title,
                post_id: (window.abmagic && window.abmagic.editingTestId) ? window.abmagic.editingTestId : 'new',
                post_status: postStatus,
                magic_definition: JSON.stringify(sanitizedDefinition),
                test_type: 'magic',
                bt_experiments_url_query: test.url_query,
                // Goal type 'page' (page ID in _page_selector) or 'selector' (CSS selector in _selector).
                bt_experiments_conversion_page: primaryGoalType,
                bt_experiments_conversion_page_selector: primaryGoalType === 'page' ? (primaryGoal.value || '') : '',
                bt_experiments_conversion_selector: primaryGoalType === 'selector' ? (primaryGoal.value || '') : '',
                bt_experiments_full_page_default_page: '',
                css_test_variations: '',
                bt_experiments_target_option_device_size: test.targeting.device_size,
                bt_experiments_target_percentage: test.targeting.traffic_percentage,
                bt_allowed_roles: test.targeting.allowed_roles
            };

            console.log('ABST: Saving test from unified object', { test: test, payload: newTestData });
                
                jQuery.ajax({
                url: bt_ajaxurl,
                type: 'POST',
                data: newTestData,
                success: function(response) {
                    if (typeof response === 'string') {
                        try {
                            response = JSON.parse(response);
                        } catch (e) {
                            console.error('ABST: Failed to parse Magic save response', e, response);
                            // Refusals (permissions, nonce) come back as plain text.
                            var abstReply = String(response || '').replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim().slice(0, 300);
                            alert('The server replied: ' + (abstReply || '(empty)') + '\n\nPlease reload and check whether your changes were saved.');
                            return;
                        }
                    }

                    // The server refuses a save with config_error (e.g. markup this account
                    // cannot save). Say so instead of doing nothing.
                    if (response && response.config_error) {
                        alert('The test was NOT saved: ' + response.config_error);
                        return;
                    }

                    // Saved, but not live: say so and open the draft instead of
                    // announcing a running test.
                    if (response && response.notice) {
                        alert(response.post_title + ': ' + response.notice);
                        window.location.href = response.edit_url || window.location.pathname;
                        return;
                    }

                    if(response.post_title && response.post_title !== ''){
                        var message = isDraftSave ? ' saved as a draft.' : (response.updated ? ' updated.' : ' created, reloading page.');
                        alert(response.post_title + message);
                        var urlParams = new URLSearchParams(window.location.search);
                        var returnTo = urlParams.get('return_to');
                        if (isDraftSave && response.edit_url) {
                            window.location.href = response.edit_url;
                        } else if (response.updated && returnTo) {
                            // Only follow same-origin destinations. return_to comes off the query
                            // string, so without this it accepts "javascript:..." (script execution
                            // in the admin's session) or an off-site redirect.
                            var abstReturnTarget = null;
                            try {
                                var abstResolved = new URL(decodeURIComponent(returnTo), window.location.origin);
                                if (abstResolved.origin === window.location.origin) {
                                    abstReturnTarget = abstResolved.href;
                                }
                            } catch (e) { abstReturnTarget = null; }
                            window.location.href = abstReturnTarget || window.location.pathname;
                        } else {
                            window.location.href = window.location.pathname;
                        }
                    } else {
                        alert('Saving finished with an unexpected response. Please reload and confirm your changes.');
                    }
                },
                error: function(xhr) {
                    alert('Saving the test failed (' + (xhr && xhr.status ? 'HTTP ' + xhr.status : 'network error') + '). Your changes are still in the editor - please try again.');
                }
            });
        
        }

        jQuery('body').on('click','#abst-magic-bar-start',function(){
            saveMagicTest('publish');
        });

        jQuery('body').on('click','#abst-magic-bar-save-draft',function(){
            saveMagicTest('draft');
        });
    });
})(jQuery);

// Make the function available globally
window.abst_magic_bar = abst_magic_bar;

/* Start Test without a goal: no alert. Bring the Goals card into view, flash its border and
 * put the cursor in the field for the chosen goal type: the page search for a page visit,
 * the CSS selector for an element click. reason 'invalid': the selector is not valid CSS. */
function abstNeedGoal(reason) {
    var $col = jQuery('#abst-magic-bar .abst-goals-column');
    var $box = jQuery('.abst-goals-container').first();
    if (!$box.length) { alert('Please choose the conversion goal for this test'); return; }
    var goal = getMagicGoalFromContainer($box);
    var message = 'Choose the page visitors reach when they convert before starting the test.';
    var $field = $box.find('.abst-goal-page-input');
    if (goal.type === 'selector') {
        message = reason === 'invalid'
            ? 'That CSS selector is not valid. Check it, or use Pick on page to choose the element.'
            : 'Enter the CSS selector of the element visitors click to convert, or use Pick on page, before starting the test.';
        $field = $box.find('.abst-goal-selector-input');
    }
    if (window.setAbstMagicBarTab) window.setAbstMagicBarTab('test');
    var bar = document.getElementById('abst-magic-bar');
    if (typeof abstIsDrawer === 'function' && abstIsDrawer() && bar && bar.getBoundingClientRect().height < abstDrawerSnaps()[1] - 10) {
        abstSetDrawerHeight(abstDrawerSnaps()[1]);
    }
    setTimeout(function() { var t = $box[0] || $col[0]; if (t) t.scrollIntoView({ block: 'start', behavior: 'smooth' }); }, 60);
    $box.removeClass('abst-goal-needed');
    void $box[0].offsetWidth; // restart the flash
    $box.addClass('abst-goal-needed');

    $box.find('.abst-goal-needed-hint').remove();
    var $hint = jQuery('<div class="abst-goal-needed-hint" role="alert"></div>')
        .append(jQuery('<p></p>').text(message));
    var $head = $box.find('.abst-goal-card-header').first();
    if ($head.length) $head.after($hint); else $box.prepend($hint);
    ($field.length ? $field : $box.find('input:visible').not('.abst-goal-input-value')).first().trigger('focus');
}

/* Phones: the bar is a bottom drawer (CSS, max-width 767px). The handle drags it between
 * peek / half / full height and a tap (or Enter/Space) toggles half <-> full. The height
 * is published as --abst-mb-drawer-h so the page gets the same bottom padding and every
 * part of it can still be scrolled above the drawer. */
var ABST_DRAWER_MQ = window.matchMedia ? window.matchMedia('(max-width: 767px)') : null;

function abstIsDrawer() { return !!(ABST_DRAWER_MQ && ABST_DRAWER_MQ.matches); }
function abstDrawerSnaps() {
    var h = window.innerHeight;
    return [Math.min(150, Math.round(h * 0.3)), Math.round(h * 0.55), Math.round(h * 0.88)];
}
function abstSetDrawerHeight(px) {
    document.documentElement.style.setProperty('--abst-mb-drawer-h', Math.round(px) + 'px');
}

function abstInitMagicDrawer(bar) {
    var handle = document.createElement('div');
    handle.className = 'abst-magic-drawer-handle';
    handle.setAttribute('role', 'button');
    handle.setAttribute('tabindex', '0');
    handle.setAttribute('aria-label', 'Drag to resize the test editor, or tap to expand it');
    handle.innerHTML = '<span></span>';
    bar.insertBefore(handle, bar.firstChild);
    if (abstIsDrawer()) abstSetDrawerHeight(abstDrawerSnaps()[1]);

    // Tap: peek -> half, half -> full, full -> half.
    var toggle = function() {
        var s = abstDrawerSnaps(), h = bar.getBoundingClientRect().height;
        abstSetDrawerHeight(h < s[1] - 10 ? s[1] : (h < s[1] + 10 ? s[2] : s[1]));
    };
    var startY = 0, startH = 0, moved = false, dragging = false;
    handle.addEventListener('pointerdown', function(e) {
        if (!abstIsDrawer()) return;
        dragging = true; moved = false;
        startY = e.clientY; startH = bar.getBoundingClientRect().height;
        bar.classList.add('is-dragging');
        if (handle.setPointerCapture) handle.setPointerCapture(e.pointerId);
        e.preventDefault();
    });
    handle.addEventListener('pointermove', function(e) {
        if (!dragging) return;
        var dy = e.clientY - startY, s = abstDrawerSnaps();
        if (Math.abs(dy) > 4) moved = true;
        abstSetDrawerHeight(Math.max(s[0] * 0.8, Math.min(s[2], startH - dy)));
    });
    var end = function() {
        if (!dragging) return;
        dragging = false;
        bar.classList.remove('is-dragging');
        if (!moved) { toggle(); return; }
        var h = bar.getBoundingClientRect().height;
        abstSetDrawerHeight(abstDrawerSnaps().reduce(function(best, v) { return Math.abs(v - h) < Math.abs(best - h) ? v : best; }));
    };
    handle.addEventListener('pointerup', end);
    handle.addEventListener('pointercancel', end);
    handle.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); toggle(); }
    });
    if (ABST_DRAWER_MQ && ABST_DRAWER_MQ.addEventListener) {
        ABST_DRAWER_MQ.addEventListener('change', function() {
            if (abstIsDrawer()) abstSetDrawerHeight(abstDrawerSnaps()[1]);
            // Rotating / resizing across the breakpoint: squeeze fixed elements only for the side panel.
            if (document.documentElement.classList.contains('doing-abst-magic-bar')) adjustFixedElementsForMagicBar(!abstIsDrawer());
        });
    }
}

/* On a phone the drawer covers the lower half of the page: bring a selection hidden
 * behind it up into view. */
function abstDrawerReveal(selector) {
    if (!abstIsDrawer()) return;
    var el = null;
    try { el = document.querySelector(selector); } catch (e) {}
    var bar = document.getElementById('abst-magic-bar');
    if (!el || !bar) return;
    var r = el.getBoundingClientRect(), visibleBottom = window.innerHeight - bar.getBoundingClientRect().height;
    if (r.top < 60 || r.bottom > visibleBottom) {
        window.scrollBy({ top: r.top - Math.max(70, (visibleBottom - r.height) / 3), behavior: 'smooth' });
    }
}


    
function isInViewport(element) {
    if (!element) return false;
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
    // First remove the class and markers from all elements
    jQuery('.abst-variation').removeClass('abst-variation');
    jQuery('.abst-variation-marker').remove();
    
    // Skip if no Magic definition exists
    if (!window.abmagic || !window.abmagic.definition) return;
    
    // Re-add the class and markers to all elements in the definition
    window.abmagic.definition.forEach(function(def, defIndex) {
        if (def && def.selector) {
            const elements = jQuery(def.selector);
            if (elements.length > 0) {
                elements.addClass('abst-variation');
                addVariationMarker(elements.first(), def, defIndex);
            }
        }
    });
    
    console.log('Refreshed variation classes');
}

/**
 * Add a variation marker toolbar to an element
 * Shows A, B, C, D buttons to toggle variations and X to remove
 */
function addVariationMarker($element, definition, defIndex) {
    // Remove existing marker if any
    $element.find('.abst-variation-marker').remove();
    $element.siblings('.abst-variation-marker').remove();
    jQuery('.abst-variation-marker[data-def-index="' + defIndex + '"]').remove();
    
    // Ensure element has position relative for absolute positioning
    if ($element.css('position') === 'static') {
        $element.css('position', 'relative');
    }
    
    // Build the A (original) and B (variation) buttons
    var buttonsHtml = '';
    var currentVariation = parseInt(jQuery('#variation-picker').val(), 10) === 1 ? 1 : 0;

    for (var i = 0; i < ABST_MAGIC_VERSIONS; i++) {
        var label = getVariationLabel(i);
        var activeClass = (i === currentVariation) ? ' active' : '';
        var title = 'Show ' + label + ' Version' + (i === 0 ? ' (original)' : '');
        buttonsHtml += '<button class="abst-marker-var' + activeClass + '" data-var="' + i + '" data-def="' + defIndex + '" title="' + title + '">' + label + '</button>';
    }

    // Add remove button
    buttonsHtml += '<button class="abst-marker-remove" data-def="' + defIndex + '" title="Remove element from test">×</button>';
    
    var $marker = jQuery('<div class="abst-variation-marker" data-def-index="' + defIndex + '">' + buttonsHtml + '</div>');
    $marker.data('abstTargetElement', $element[0]);
    jQuery('body').append($marker);
    positionVariationMarker($marker, $element);
}

function positionVariationMarker($marker, $element) {
    if (!$marker || !$marker.length || !$element || !$element.length) {
        return;
    }

    var targetElement = $element[0] || $marker.data('abstTargetElement');
    if (!targetElement || !document.documentElement.contains(targetElement)) {
        $marker.remove();
        return;
    }

    var markerWidth = $marker.outerWidth() || 0;
    var markerHeight = $marker.outerHeight() || 0;
    var elementRect = targetElement.getBoundingClientRect();
    var viewportWidth = window.innerWidth || document.documentElement.clientWidth;
    var margin = 8;
    var adminBarHeight = 0;
    var $adminBar = jQuery('#wpadminbar:visible');

    if ($adminBar.length) {
        adminBarHeight = $adminBar.outerHeight() || 0;
    }

    var safeTop = Math.max(margin, adminBarHeight + margin);
    var topPosition = elementRect.top - markerHeight - 6;
    var bottomPosition = elementRect.bottom + 6;

    var viewportLeft = elementRect.right - markerWidth + 10;
    var viewportTop = (topPosition < safeTop) ? bottomPosition : topPosition;

    viewportLeft = Math.max(margin, Math.min(viewportLeft, viewportWidth - markerWidth - margin));

    $marker.css({
        position: 'fixed',
        left: viewportLeft + 'px',
        right: 'auto',
        top: viewportTop + 'px',
        display: 'inline-flex'
    });
}

function repositionVariationMarkers() {
    jQuery('.abst-variation-marker').each(function() {
        var $marker = jQuery(this);
        var targetElement = $marker.data('abstTargetElement');
        if (targetElement) {
            positionVariationMarker($marker, jQuery(targetElement));
        }
    });
}

jQuery(window).on('resize scroll', function() {
    repositionVariationMarkers();
});

// Handle variation marker button clicks - swap variation
jQuery('body').on('click', '.abst-marker-var', function(e) {
    e.preventDefault();
    e.stopImmediatePropagation();
    
    console.log('Marker var button clicked');
    var varIndex = jQuery(this).data('var');
    var defIndex = jQuery(this).data('def');
    console.log('varIndex:', varIndex, 'defIndex:', defIndex);
    
    // Get the definition for this element
    if (window.abmagic && window.abmagic.definition && window.abmagic.definition[defIndex]) {
        var def = window.abmagic.definition[defIndex];
        
        // Update selector input to this element
        jQuery('#abst-selector-input').val(def.selector);
        
        // Update editor with this variation's content
        if (window.abstEditor) {
            var content = def.variations[varIndex] || def.variations[0] || '';
            window.abstEditor.innerHTML = content;
            setVariationEditorActive(def.type !== 'image');
        }
    }
    
    // Update the variation picker and trigger change
    jQuery('#variation-picker').val(String(varIndex)).trigger('change');
    
    // Update active state on all markers
    jQuery('.abst-variation-marker .abst-marker-var').removeClass('active');
    jQuery('.abst-variation-marker .abst-marker-var[data-var="' + varIndex + '"]').addClass('active');
});

// Handle remove button clicks
jQuery('body').on('click', '.abst-marker-remove', function(e) {
    e.preventDefault();
    e.stopImmediatePropagation();
    
    console.log('Remove button clicked');
    
    if (!confirm('Remove this element from the test?')) {
        return;
    }
    
    var defIndex = parseInt(jQuery(this).data('def'));
    
    if (window.abmagic && window.abmagic.definition && window.abmagic.definition[defIndex]) {
        var def = window.abmagic.definition[defIndex];
        var selector = def.selector;
        
        // Reset element to original content
        if (def.type === 'image') {
            jQuery(selector).attr('src', def.variations[0]);
        } else {
            jQuery(selector).html(def.variations[0]);
        }
        
        // Remove class and marker from element
        jQuery(selector).removeClass('abst-variation');
        jQuery(selector).find('.abst-variation-marker').remove();
        
        // Remove from definition
        window.abmagic.definition.splice(defIndex, 1);
        
        // Clear selector input if this was the selected element
        if (jQuery('#abst-selector-input').val() === selector) {
            jQuery('#abst-selector-input').val('');
            if (window.abstEditor) {
                window.abstEditor.innerHTML = '';
            }
        }
        
        // Refresh all markers (indices changed)
        refreshVariationClasses();
        
        console.log('Removed element from test:', selector);
    }
});


function getVariationLabel(n) {
    alphabet = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z'];
    return alphabet[n];
}

/**
 * Rebuild the variation picker: the original (A) and the variation (B).
 * selectedIndex defaults to the variation.
 */
function updateVariationPicker(selectedIndex) {
    if (!window.abmagic || !window.abmagic.definition || window.abmagic.definition.length === 0) {
        return;
    }

    selectedIndex = (selectedIndex === 0) ? 0 : 1;

    // Build options HTML
    var optionsHtml = '';
    for (var i = 0; i < ABST_MAGIC_VERSIONS; i++) {
        var label = getVariationLabel(i);
        var suffix = (i === 0) ? ' Version - Control' : ' Version';
        var selected = (i === selectedIndex) ? ' selected' : '';
        optionsHtml += '<option value="' + i + '"' + selected + '>' + label + suffix + '</option>';
    }

    // Update the picker and trigger change to refresh editor
    jQuery('#variation-picker').html(optionsHtml).trigger('change');
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
            // Slot 0 is the element's original markup, captured from the page; the
            // raw-HTML toggle and synthetic input events must not rewrite the control.
            if (!(variationIndex >= 1)) return;
            // Update existing variation with cleaned HTML
            window.abmagic.definition[elementIndex].variations[variationIndex] = cleanValue;
            console.log('updated variation', window.abmagic.definition[elementIndex].variations);
        } else {
            // Add new variation - but only if element type is valid
            if (!variationType) {
                console.log('Skipping element - not a testable type:', selector);
                return;
            }
            
            // Add new variation
            let originalValue = (variationType === 'image') ? 
                jQuery(selector).attr('src') || '' : 
                jQuery(selector).html() || '';
            
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
                scope: getMagicScope(),
                type: variationType
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
    
    // Safety check - return -1 if abmagic not initialized
    if (!window.abmagic || !window.abmagic.definition) {
        return -1;
    }
    
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

