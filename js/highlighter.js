var ab_highlight_timer;
var abstSpecialPages = ['woo-order-pay', 'woo-order-received', 'woo', 'javascript', 'edd-purchase', 'surecart-order-paid', 'wp-pizza-is-checkout', 'wp-pizza-is-order-history', 'fluentcart-order-paid'];
var abstOrderPages = [ 'woo-order-received', 'javascript', 'edd-purchase', 'surecart-order-paid', 'fluentcart-order-paid'];
// Form submission conversions are prefixed with 'form-' and handled dynamically
var abstFormConversionPrefix = 'form-';

// ============================================
// AI SUGGESTION CACHING SYSTEM
// ============================================
if (!window.abstAISuggestions) {
    window.abstAISuggestions = {}; // Keyed by selector - current suggestions
}
if (!window.abstAISuggestionHistory) {
    window.abstAISuggestionHistory = {}; // Keyed by selector - all past suggestions (to avoid repeats)
}

/**
 * Cache AI suggestions for a selector
 * @param {string} selector - CSS selector
 * @param {array} suggestions - Array of suggestion strings
 */
function cacheAISuggestions(selector, suggestions) {
    if (!selector || !suggestions) return;
    window.abstAISuggestions[selector] = suggestions;
    
    // Also add to history to track all suggestions ever shown
    if (!window.abstAISuggestionHistory[selector]) {
        window.abstAISuggestionHistory[selector] = [];
    }
    suggestions.forEach(function(s) {
        var text = typeof s === 'object' ? s.text : s;
        if (window.abstAISuggestionHistory[selector].indexOf(text) === -1) {
            window.abstAISuggestionHistory[selector].push(text);
        }
    });
    
    console.log('Cached AI suggestions for:', selector, suggestions);
}

/**
 * Get cached AI suggestions for a selector
 * @param {string} selector - CSS selector
 * @returns {array|null} - Array of suggestions or null
 */
function getCachedAISuggestions(selector) {
    return window.abstAISuggestions[selector] || null;
}

/**
 * Get suggestion history for a selector (used to exclude from new generations)
 * @param {string} selector - CSS selector
 * @returns {array} - Array of all past suggestion strings
 */
function getSuggestionHistory(selector) {
    return window.abstAISuggestionHistory[selector] || [];
}

function normalizeSuggestionText(suggestion) {
    return String(typeof suggestion === 'object' ? suggestion.text : suggestion || '').trim();
}

function mergeUniqueSuggestions(existingSuggestions, incomingSuggestions) {
    var merged = [];
    var seen = {};

    function addSuggestion(suggestion) {
        var text = normalizeSuggestionText(suggestion);
        if (!text) return;
        var key = text.toLowerCase();
        if (seen[key]) return;
        seen[key] = true;
        merged.push(suggestion);
    }

    (existingSuggestions || []).forEach(addSuggestion);
    (incomingSuggestions || []).forEach(addSuggestion);

    return merged;
}

function formatCroChatSuggestions(variations) {
    return (variations || []).map(function(variation) {
        var text = typeof variation === 'object' ? variation.text : variation;
        var style = typeof variation === 'object' && variation.style ? variation.style : 'CRO CHAT SUGGESTION';
        return {
            text: text,
            style: style
        };
    });
}

function updateAISuggestionToggleCount() {
    return;
}

function formatCroChatInline(text) {
    return text
        .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
        .replace(/\*(.*?)\*/g, '<em>$1</em>')
        .replace(/`([^`]+)`/g, '<code>$1</code>');
}

function formatCroChatResponse(response) {
    var safeResponse = jQuery('<div>').text(String(response || '').replace(/\r\n/g, '\n').trim()).html();
    var lines = safeResponse.split('\n');
    var html = [];
    var inList = false;

    function closeList() {
        if (inList) {
            html.push('</ul>');
            inList = false;
        }
    }

    lines.forEach(function(line) {
        var trimmed = line.trim();

        if (!trimmed) {
            closeList();
            return;
        }

        if (/^---+$/.test(trimmed)) {
            closeList();
            html.push('<hr class="abst-cro-chat-rule">');
            return;
        }

        var headingMatch = trimmed.match(/^#{1,6}\s+(.*)$/);
        if (headingMatch) {
            closeList();
            html.push('<p class="abst-cro-chat-heading">' + formatCroChatInline(headingMatch[1]) + '</p>');
            return;
        }

        var bulletMatch = trimmed.match(/^[-*]\s+(.*)$/);
        if (bulletMatch) {
            if (!inList) {
                html.push('<ul class="abst-cro-chat-list">');
                inList = true;
            }
            html.push('<li>' + formatCroChatInline(bulletMatch[1]) + '</li>');
            return;
        }

        closeList();
        html.push('<p>' + formatCroChatInline(trimmed) + '</p>');
    });

    closeList();
    return html.join('');
}

function setAISuggestionsExpanded(expanded) {
    var $toggle = jQuery('.abst-ai-suggestions-toggle');
    var $content = $toggle.next('.abst-ai-suggestions-content');
    window.abmagic = window.abmagic || {};

    if (!$toggle.length || !$content.length) {
        return;
    }

    $toggle.attr('aria-expanded', expanded ? 'true' : 'false');
    jQuery('#ai-suggestions').toggleClass('is-expanded', !!expanded);
    window.abmagic.aiSuggestionsDismissed = !expanded;

    if (expanded) {
        $content.stop(true, true).slideDown(750);
        $content.find('#ai-suggestions-list').show();
    } else {
        $content.stop(true, true).slideUp(250);
    }
}

function setVariationEditorActive(isActive) {
    jQuery('#variation-editor-container').toggleClass('is-active', !!isActive);
}

function renderAiUpgradeState() {
    jQuery('.abst-ai-suggestions-inline-loading').hide();
    jQuery('#ai-generate-more').hide();
    jQuery('#ai-suggestions-list')
        .show()
        .html('<li class="abst-ai-upsell-item"><p class="abst-ai-suggestions-hint">AI suggestions are a Pro feature.<a href="https://absplittest.com/pricing/?utm_source=lite-plugin&utm_medium=magic-bar&utm_campaign=ai-suggestions-upsell" target="_blank" rel="noopener noreferrer">7 day trial</a></p></li>');
    updateAISuggestionToggleCount();
}

function renderCroChatUpgradeState() {
    jQuery('#abst-cro-chat-messages').html(
        '<div class="abst-cro-chat-response abst-cro-chat-response--assistant"><p>Subscribe for AI features.</p><p><a href="https://absplittest.com/pricing/?utm_source=lite-plugin&utm_medium=magic-bar&utm_campaign=chatcro-upsell" target="_blank" rel="noopener noreferrer">Upgrade to unlock ChatCRO</a></p></div>'
    );
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

/**
 * Populate the AI suggestions panel with suggestions and action buttons
 * @param {array} suggestions - Array of suggestion strings
 * @param {boolean} append - If true, append to existing suggestions instead of replacing
 */
function populateAISuggestionsPanel(suggestions, append) {
    // Always hide loading indicators when populating
    jQuery('.ai-loading').hide();
    jQuery('.abst-ai-suggestions-inline-loading').hide();

    if (!suggestions || suggestions.length === 0) {
        if (!append) {
            jQuery('#ai-suggestions').slideUp();
        }
        return;
    }
    
    var html = '';
    suggestions.forEach(function(suggestion, idx) {
        // Handle both string and object formats
        var text = typeof suggestion === 'object' ? suggestion.text : suggestion;
        var style = typeof suggestion === 'object' ? suggestion.style : '';
        
        html += '<li class="ai-suggestion-item" data-suggestion-text="' + jQuery('<div>').text(text).html().replace(/"/g, '&quot;') + '">';
        if (style) {
            html += '<span class="ai-suggestion-style">' + jQuery('<div>').text(style).html() + '</span>';
        }
        html += '<span class="ai-suggestion-text">' + jQuery('<div>').text(text).html() + '</span>';
        html += '<div class="ai-suggestion-actions">';
        html += '<button class="ai-add-variation" title="Add as new variation">+ Add to new Variation</button>';
        html += '<button class="ai-replace-current" title="Replace current variation">↻ Update current Variation</button>';
        html += '</div>';
        html += '</li>';
    });
    
    if (append) {
        jQuery('#ai-suggestions-list').append(html);
    } else {
        jQuery('#ai-suggestions-list').html(html);
    }

    // Show and enable the "Generate More" button
    jQuery('#ai-generate-more').show().prop('disabled', false);

    updateAISuggestionToggleCount();

    // Show container and auto-expand suggestions content
    jQuery('#ai-suggestions').slideDown(650, function() {
        setAISuggestionsExpanded(true);
    });
}

/**
 * Add a new variation with the given text
 * @param {string} text - The variation text to add
 */
function addVariationFromSuggestion(text) {
    var selector = jQuery('#abst-selector-input').val();
    if (!selector) {
        alert('Please select an element first');
        return;
    }
    
    // Ensure abmagic is initialized
    if (!window.abmagic) window.abmagic = {};
    if (!window.abmagic.definition) window.abmagic.definition = [];
    
    // Get current element definition
    var elementIndex = getElementIndexFromMagic(selector);
    
    if (elementIndex === -1) {
        // Element not in definition yet - need to add it first
        var variationType = getElementType(jQuery(selector)[0]);
        if (!variationType) {
            alert('Cannot test this element type');
            return;
        }
        
        var originalValue;
        if (variationType === 'image') {
            originalValue = jQuery(selector).attr('src') || '';
        } else {
            originalValue = jQuery(selector).html() || '';
        }
        
        window.abmagic.definition.push({
            type: variationType,
            selector: selector,
            scope: getMagicScope(),
            variations: [originalValue, text]
        });
        elementIndex = window.abmagic.definition.length - 1;
    } else {
        // Add to existing element's variations
        window.abmagic.definition[elementIndex].variations.push(text);
    }
    
    // Update the variation picker to show new variation
    updateVariationPicker();
    
    // Switch to the new variation
    var newVariationIndex = window.abmagic.definition[elementIndex].variations.length - 1;
    jQuery('#variation-picker').val(newVariationIndex).trigger('change');
    
    // Update editor with the new text
    if (window.abstEditor) {
        window.abstEditor.innerHTML = text;
    }
    
    console.log('Added variation from suggestion:', text, 'at index', newVariationIndex);
}

// Normalize text for comparison - remove extra whitespace, normalize quotes
function normalizeTextForMatch(str) {
    if (!str) return '';
    return str
        .replace(/^["']+|["']+$/g, '') // Strip leading/trailing quotes
        .replace(/[\u2018\u2019\u201C\u201D]/g, "'") // Smart quotes to regular
        .replace(/[\u2014\u2013]/g, '-')             // Em/en dashes to regular
        .replace(/\.\.\./g, '')  // Remove ellipsis
        .replace(/…/g, '')       // Unicode ellipsis
        .replace(/[\r\n\t]+/g, ' ')  // All line breaks and tabs to space
        .replace(/\s+/g, ' ')    // Multiple spaces to single space
        .replace(/,\s*/g, ', ')  // Normalize comma spacing
        .trim()
        .toLowerCase();
}

// Get element priority bonus (prefer semantic elements over divs/spans)
function getElementMatchBonus(el) {
    var tag = el.tagName.toLowerCase();
    if (['h1', 'h2', 'h3', 'h4', 'h5', 'h6'].includes(tag)) return 50;
    if (['p', 'a', 'button', 'li'].includes(tag)) return 30;
    if (tag === 'span') return 10;
    return 0; // div and others get no bonus
}

// Find the best matching element for a given search text
function findBestMatchingElement(searchText) {
    var searchTextNorm = normalizeTextForMatch(searchText);
    var searchTextStart = searchTextNorm.substring(0, 20);
    
    var bestMatch = null;
    var bestMatchScore = 0;
    
    jQuery('h1, h2, h3, h4, h5, h6, p, a, button, span, li, div').not('#wpadminbar *, #abst-magic-bar *, .cro-chat-test-bubble, .abst-cro-chat-message *').each(function() {
        if (jQuery(this).is(':hidden') || jQuery(this).closest('#wpadminbar, #abst-magic-bar').length) return;
        
        // Get text content - replace <br> with space
        var $clone = jQuery(this).clone();
        $clone.find('br').replaceWith(' ');
        $clone.html($clone.html().replace(/<br\s*\/?>/gi, ' '));
        var textWithChildren = normalizeTextForMatch($clone.text());
        
        var $cloneDirect = jQuery(this).clone();
        $cloneDirect.find('br').replaceWith(' ');
        $cloneDirect.html($cloneDirect.html().replace(/<br\s*\/?>/gi, ' '));
        $cloneDirect.children().remove();
        var textDirect = normalizeTextForMatch($cloneDirect.text());
        
        var childCount = jQuery(this).find('*').length;
        var elementBonus = getElementMatchBonus(this);
        var score = 0;
        
        // Exact match on direct text (highest priority)
        if (textDirect === searchTextNorm) {
            score = 300 + elementBonus - childCount;
        }
        // Exact match including children
        else if (textWithChildren === searchTextNorm) {
            score = 250 + elementBonus - childCount;
        }
        // "Starts with" match
        else if (textDirect.startsWith(searchTextNorm) || textWithChildren.startsWith(searchTextNorm)) {
            score = 150 + elementBonus - childCount;
        }
        // Search text starts with element text
        else if (searchTextNorm.startsWith(textDirect) && textDirect.length > 15) {
            score = 140 + elementBonus - childCount;
        }
        // First 20 chars match
        else if (searchTextStart.length >= 15 && (textDirect.startsWith(searchTextStart) || textWithChildren.startsWith(searchTextStart))) {
            score = 120 + elementBonus - childCount;
        }
        // Contains match
        else if (textDirect.includes(searchTextNorm) || textWithChildren.includes(searchTextNorm)) {
            score = 100 + elementBonus - childCount;
        }
        // Partial contains on first 20 chars
        else if (searchTextStart.length >= 15 && (textDirect.includes(searchTextStart) || textWithChildren.includes(searchTextStart))) {
            score = 50 + elementBonus - childCount;
        }
        
        if (score > bestMatchScore) {
            bestMatchScore = score;
            bestMatch = this;
        }
    });
    
    return { element: bestMatch, score: bestMatchScore };
}

// Add persistent AI suggest buttons to elements matching suggestions
function addAiSuggestButtonsToElements(suggestions) {
    console.log('addAiSuggestButtonsToElements called with', suggestions);
    
    // Remove any existing AI suggest buttons and highlight classes
    jQuery('.abst-ai-suggest-inline').remove();
    jQuery('.abst-ai-suggested').removeClass('abst-ai-suggested');
    
    if (!suggestions || suggestions.length === 0) return;
    
    suggestions.forEach(function(suggestion) {
        console.log('Looking for:', suggestion.original.substring(0, 50));
        
        var result = findBestMatchingElement(suggestion.original);
        
        if (result.element) {
            var $el = jQuery(result.element);
            console.log('Best match:', result.element, 'score:', result.score);
            
            // Make element relative if not already positioned
            if ($el.css('position') === 'static') {
                $el.css('position', 'relative');
            }
            $el.css('overflow', 'visible');
            
            // Add light green highlight box around the element
            $el.addClass('abst-ai-suggested');
            
            var testData = JSON.stringify(suggestion).replace(/"/g, '&quot;');
            var btn = jQuery('<button class="abst-ai-suggest-inline" data-test="' + testData + '" style="position:absolute; top:-28px; left:0; padding:3px 8px; background:#e8f5e9; color:#2e7d32; border:1px solid #c8e6c9; border-radius:10px; font-size:10px; cursor:pointer; white-space:nowrap; z-index:99999; box-shadow:0 1px 4px rgba(0,0,0,0.1);">+ AI suggested test element</button>');
            
            $el.append(btn);
        }
    });
}

// Handle inline AI suggest button clicks
jQuery('body').on('click', '.abst-ai-suggest-inline', function(e) {
    e.preventDefault();
    e.stopPropagation();
    
    var $btn = jQuery(this);
    var $parentEl = $btn.parent();
    var testData = $btn.attr('data-test');
    var suggestion = JSON.parse(testData.replace(/&quot;/g, '"'));
    
    // Remove green highlight from parent element (will get orange from abst-variation)
    $parentEl.removeClass('abst-ai-suggested');
    
    // Remove this button before triggering bubble click
    $btn.remove();
    
    // Find and click the matching bubble
    jQuery('.cro-chat-test-bubble').each(function() {
        var bubbleData = JSON.parse(jQuery(this).attr('data-test').replace(/&quot;/g, '"'));
        if (bubbleData.element === suggestion.element && bubbleData.original === suggestion.original) {
            jQuery(this).click();
            return false;
        }
    });
});

// Handle clicks on AI suggested elements (green box) - same as clicking the button
jQuery('body').on('click', '.abst-ai-suggested', function(e) {
    // Don't trigger if clicking on the button itself (it has its own handler)
    if (jQuery(e.target).hasClass('abst-ai-suggest-inline')) {
        return;
    }
    
    e.preventDefault();
    e.stopPropagation();
    
    var $el = jQuery(this);
    var $btn = $el.find('.abst-ai-suggest-inline');
    
    if ($btn.length > 0) {
        // Trigger the button click
        $btn.click();
    }
});


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

// add ai button to pages sao far gb and BB toolbars, need to do the rest
//  jQuery('.edit-post-header-toolbar__left, .fl-builder-bar-actions').append('<button><STRONG>AI</strong></button>')


  if ( self !== top ) // if inside an iframe, then its a preview and we dont want to do things.
    return;

  jQuery('body').on('change blur','[name="bt_click_conversion_selector"], .bt_click_conversion_selector input, [data-setting="bt_click_conversion_selector"]',function(){      
    bt_highlight(jQuery(this).val());
  });

  jQuery(document).on('mousedown', function(e) {
    var target = e.target;
    var keepActive = jQuery(target).closest('#variation-editor-container, #ai-suggestions').length > 0 || isWithinSelectedMagicElement(target);
    if (!keepActive) {
        setVariationEditorActive(false);
    }
  });

  jQuery('body').on('focusin click', '#variation-editor-container, #abst-selector-input, #abst-variation-editor-container, .abst-editor-toolbar button, .ai-suggestion-item, .ai-add-variation, .ai-replace-current, .abst-ai-suggestions-toggle', function() {
    setVariationEditorActive(true);
  });

  jQuery(document).on('mousedown', function(e) {
    var target = e.target;
    var keepActive = jQuery(target).closest('.abst-goals-container').length > 0;
    if (!keepActive) {
        jQuery('.abst-goals-container').removeClass('is-active');
    }
  });

  jQuery('body').on('focusin click', '.abst-goals-container', function() {
    setGoalsContainerActive(this, true);
  });


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
            const variationsArray = variationsParam ? variationsParam.split('|') : [];
            const allVariations = [textToReplace].concat(variationsArray);
            
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
            jQuery('#variation-editor-container, .abst-goals-column, .abst-magic-bar-footer, #abst-targeting-button, .winning-mode').slideDown();

            jQuery('.magic-test-name').css('display', 'flex').hide().slideDown();
            bt_highlight(selector);
            jQuery('#abst-selector-input').val(selector).trigger('blur');
            
            // Set editor content
            if (window.abstEditor) {
                window.abstEditor.innerHTML = textToReplace;
                jQuery('#abst-variation-editor').val(textToReplace);
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

   // Add New Magic Test link at the top
   submenus += '<li><a class="ab-item ab-sub-secondary" id="wp-admin-bar-ab-new-magic-test" href="#" onclick="abst_magic_bar();">✨ New Magic Test</a></li>';
   
   // Add AI Suggestions link
   if(typeof btab_vars !== 'undefined' && btab_vars.abst_disable_ai !== '1') {
     submenus += '<li><a class="ab-item ab-sub-secondary" id="ab-ai">🤖 AI Suggestions</a></li>';
   }
   
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
function sendToOpenAI(query,abAiType,outputSelector, selectorContext) {
    if(btab_vars.abst_disable_ai == '1'){
        console.log('ABST AI is disabled');
        return;
    }

    showAILoadingState();

    // Use enhanced context if available, fallback to legacy
    var markdown;
    if (window.abstAI && window.abstAI.buildContext) {
        var aiContext = window.abstAI.buildContext({ maxContentChars: 30000 });
        markdown = window.abstAI.formatContext(aiContext);
        console.log('ABST AI: Using enhanced context (' + markdown.length + ' chars)');
    } else {
        // Fallback to legacy method
        var context = getAbPageContent();
        var turndownService = new TurndownService();
        markdown = turndownService.turndown(context);
    }
    query = query.trim();

    if (!window.abmagic) window.abmagic = {};
    if (!window.abmagic.aicache) window.abmagic.aicache = {};

    var requestSelector = selectorContext || jQuery('#abst-selector-input').val() || '';
    var aicachekey = abAiType + '_' + query;

    //check for magic cache
    if( window.abmagic.aicache[aicachekey])
    {
        show_magic_ai(window.abmagic.aicache[aicachekey], requestSelector);
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
        error: function(xhr, status, error) {
            console.error('ABST AI: AJAX request failed', status, error);
            jQuery('.ai-loading').hide();
            jQuery('#ai-suggestions').html('<p style="color: red;">AI request failed: ' + (error || status) + '</p>');
        },
        success: function( response ) {
            console.log('ABST AI: Response received', response);
            if(response && typeof response.error !== 'undefined')
            {
                jQuery('.ai-loading').hide();
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

                    //console.log(ideas);
                    //remove ```json and ``` from response
                    outt += "<h3>CRO Page Score: " + ideas.overall_page_rating + "%</h3><h4> You should consider adding:</h4><p> " + ideas.missing_content +"</p>";
                    jQuery.each(ideas.suggestions,function(index, content){
        
                        outt += "<div class='ai-option'><h4>" +  content.test_name + "</h4><p> " + content.reason_why +"</p><p>Original text:<BR><strong>" + content.original_string + "</strong></p><p>Suggestions:</p>";
                        jQuery.each(content.suggestions,function(index, suggestion){
                            console.log(suggestion);
        
                            outt += "<p class='ai-suggestion-item'>" + suggestion + "</p>";
                        });
                        outt += "</div>";
                    });
                }
                else if(abAiType == 'magic')
                {
                    // Check if response has the expected structure
                    if (!response.choices || !response.choices[0] || !response.choices[0]['message'] || !response.choices[0]['message']['content']) {
                        console.error('ABST AI: Invalid response structure', response);
                        jQuery('.ai-loading').hide();
                        jQuery('#ai-suggestions').html('<p style="color: red;">AI response format error. Please try again.</p>');
                        return;
                    }
                    window.abmagic.aicache[aicachekey] = response.choices[0]['message']['content'];
                    //console.log('allcache',window.abmagic.aicache);
                    //dont do if #imageSelector is visible
                    if(!jQuery('#imageSelector').is(':visible'))
                    {
                        show_magic_ai(response.choices[0]['message']['content'], requestSelector);
                    }
                }
                else
                {
                    suggestions = JSON.parse(response.choices[0]['message']['content']);
                    jQuery.each(suggestions.suggestions,function(index, choice){
                        outt += "<div class='ai-option'>" + choice.text +" <small style='text-transform: uppercase; color: #8a8a8aff; display: block;'>" + choice.style + "</small></div>";
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

function show_magic_ai(response, selectorContext){
    try {
        // Clean up response if it contains markdown code blocks
        var cleanResponse = response;
        if (typeof cleanResponse === 'string') {
            cleanResponse = cleanResponse.replace(/```json/g, '').replace(/```/g, '').trim();
        }
        
        var parsed = JSON.parse(cleanResponse);
        var suggestions = parsed.suggestions;
        
        if (!suggestions || !Array.isArray(suggestions)) {
            console.error('ABST AI: No suggestions array in response', parsed);
            jQuery('.ai-loading').hide();
            jQuery('#ai-suggestions').html('<p style="color: orange;">No suggestions received. Try clicking a different element.</p>');
            return;
        }
        
        console.log('ABST AI: Received ' + suggestions.length + ' suggestions');
        
        // Cache suggestions for current selector
        var selector = selectorContext || jQuery('#abst-selector-input').val();
        var existingSuggestions = selector ? (getCachedAISuggestions(selector) || []) : [];
        var mergedSuggestions = mergeUniqueSuggestions(existingSuggestions, suggestions);

        if (selector) {
            cacheAISuggestions(selector, mergedSuggestions);
        }

        if (selectorContext && jQuery('#abst-selector-input').val() !== selectorContext) {
            console.log('ABST AI: Cached suggestions for non-selected element:', selectorContext);
            return;
        }

        if (existingSuggestions.length > 0 && mergedSuggestions.length === existingSuggestions.length) {
            jQuery('.ai-loading').hide();
            console.log('ABST AI: No unique suggestions to append for:', selector);
            return;
        }

        // Use the unified panel population function
        populateAISuggestionsPanel(mergedSuggestions);
    } catch (e) {
        console.error('ABST AI: Failed to parse AI response', e, response);
        jQuery('.ai-loading').hide();
        jQuery('#ai-suggestions').html('<p style="color: red;">Failed to parse AI response. Please try again.</p>');
    }
}

function hideAISuggestionsPanel() {
    window.abmagic = window.abmagic || {};
    window.abmagic.aiSuggestionsDismissed = false;
    jQuery('.ai-loading').hide();
    jQuery('.abst-ai-suggestions-inline-loading').hide();
    jQuery('#ai-suggestions-list').hide().empty();
    jQuery('#ai-generate-more').hide();
    updateAISuggestionToggleCount();
    setAISuggestionsExpanded(false);
    jQuery('#ai-suggestions').slideUp();
}

function showAILoadingState() {
    window.abmagic = window.abmagic || {};
    jQuery('#ai-suggestions p.ai-loading').remove();
    updateAISuggestionToggleCount();

    // Ensure skeleton items are shown (in case called independently)
    if (jQuery('#ai-suggestions-list').children().length === 0) {
        var skeletonHtml = '';
        for (var i = 0; i < 3; i++) {
            skeletonHtml += '<div class="ai-skeleton-item">';
            skeletonHtml += '<div class="ai-skeleton ai-skeleton-style"></div>';
            skeletonHtml += '<div class="ai-skeleton ai-skeleton-text"></div>';
            skeletonHtml += '</div>';
        }
        jQuery('#ai-suggestions-list').html(skeletonHtml).show();
    }

    jQuery('#ai-generate-more').show().prop('disabled', true);
    jQuery('.abst-ai-suggestions-inline-loading').show();
}

function showAISuggestionsForSelector(selector, sourceText, type) {
    if (type === 'image') {
        setVariationEditorActive(false);
        hideAISuggestionsPanel();
        return true;
    }

    if (!selector) {
        setVariationEditorActive(false);
        hideAISuggestionsPanel();
        return true;
    }

    setVariationEditorActive(true);

    renderAiUpgradeState();
    jQuery('#ai-suggestions').slideDown(650, function() {
        setAISuggestionsExpanded(false);
    });

    return true;
}

function getMagicPrimaryGoalFromExperiment(experiment) {
    if (!experiment) {
        return { type: 'click', value: '' };
    }

    var conversionType = experiment.conversion_page || 'click';
    var goalValue = '';

    if (!isNaN(parseInt(conversionType, 10)) && String(parseInt(conversionType, 10)) === String(conversionType)) {
        return {
            type: 'page',
            value: String(conversionType)
        };
    }

    if (conversionType === 'selector') {
        goalValue = experiment.conversion_selector || '';
    } else if (conversionType === 'url') {
        goalValue = experiment.conversion_url || '';
    } else if (conversionType === 'time') {
        goalValue = experiment.conversion_time || '';
    } else if (conversionType === 'text') {
        goalValue = experiment.conversion_text || '';
    } else if (conversionType === 'link') {
        goalValue = experiment.conversion_link_pattern || '';
    } else if (conversionType === 'scroll') {
        goalValue = experiment.conversion_scroll || '';
    }

    return {
        type: conversionType,
        value: goalValue
    };
}

function getMagicSecondaryGoalsFromExperiment(experiment) {
    var secondaryGoals = [];
    var goals = experiment ? experiment.goals : null;

    if (typeof goals === 'string') {
        try {
            goals = JSON.parse(goals);
        } catch (e) {
            goals = null;
        }
    }

    if (!goals || typeof goals !== 'object') {
        return secondaryGoals;
    }

    if (Array.isArray(goals)) {
        goals.forEach(function(goal) {
            if (goal && typeof goal === 'object') {
                if (goal.type) {
                    secondaryGoals.push({
                        type: goal.type,
                        value: goal.value || ''
                    });
                    return;
                }

                var nestedType = Object.keys(goal)[0];
                if (nestedType) {
                    secondaryGoals.push({
                        type: nestedType,
                        value: goal[nestedType] || ''
                    });
                }
            }
        });
        return secondaryGoals;
    }

    var directGoalKeys = Object.keys(goals);
    var looksLikeSingleGoalMap = directGoalKeys.length > 0 && directGoalKeys.every(function(key) {
        return typeof goals[key] !== 'object';
    });

    if (looksLikeSingleGoalMap) {
        var singleGoalType = directGoalKeys[0];
        secondaryGoals.push({
            type: singleGoalType,
            value: goals[singleGoalType] || ''
        });
        return secondaryGoals;
    }

    Object.keys(goals).forEach(function(key) {
        var goal = goals[key];
        if (!goal) {
            return;
        }

        if (goal.type) {
            secondaryGoals.push({
                type: goal.type,
                value: goal.value || ''
            });
        } else if (typeof goal === 'object') {
            var goalType = Object.keys(goal)[0];
            if (!goalType) {
                return;
            }

            secondaryGoals.push({
                type: goalType,
                value: goal[goalType] || ''
            });
        }
    });

    return secondaryGoals;
}

function applyMagicGoalToContainer($goalContainer, goal) {
    if (!$goalContainer || !$goalContainer.length || !goal || !goal.type) {
        return;
    }

    var goalType = goal.type;
    var goalValue = goal.value || '';
    var $goalSelect = $goalContainer.find('select.goal-type').first();

    if (!$goalSelect.length) {
        return;
    }

    $goalSelect.val(goalType).trigger('change');
    $goalContainer.find('.abst-goal-input-value').val(goalValue);

    if (goalType === 'page' && goalValue) {
        jQuery.ajax({
            url: abst_magic_data.ajax_url,
            dataType: 'json',
            data: {
                q: goalValue,
                action: 'ab_page_selector'
            },
            success: function(pages) {
                if (!Array.isArray(pages) || !pages.length) {
                    return;
                }

                var pageMatch = pages.find(function(page) {
                    return Array.isArray(page) && String(page[0]) === String(goalValue);
                }) || pages[0];

                if (!Array.isArray(pageMatch) || pageMatch.length < 2) {
                    return;
                }

                var $pageInput = $goalContainer.find('.abst-goal-page-input');
                if ($pageInput.length) {
                    $pageInput.val(pageMatch[1]);
                }
                $goalContainer.find('.abst-goal-input-value').val(String(pageMatch[0]));
            }
        });
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

    if (!window.abmagic) window.abmagic = {};
    window.abmagic.definition = magicDefinition;
    window.abmagic.test = window.abmagic.test || {};
    window.abmagic.editingTestId = testId;
    window.abmagic.scopeDirty = false;

    window.abmagic.test.title = experiment.name || window.abmagic.test.title || '';
    window.abmagic.test.conversion_style = experiment.conversion_style || 'bayesian';
    window.abmagic.test.use_order_value = experiment.use_order_value === '1' || experiment.use_order_value === 1;
    window.abmagic.test.url_query = experiment.url_query || '';
    window.abmagic.test.targeting = {
        device_size: experiment.target_option_device_size || 'all',
        traffic_percentage: parseInt(experiment.target_percentage, 10) || 100,
        allowed_roles: Array.isArray(experiment.allowed_roles) ? experiment.allowed_roles : (abst_magic_data.defaults || []),
        scope: getMagicScopeFromDefinition(magicDefinition)
    };
    window.abmagic.test.goals = {
        primary: getMagicPrimaryGoalFromExperiment(experiment),
        secondary: getMagicSecondaryGoalsFromExperiment(experiment)
    };

    if (window.setAbstMagicBarTab) window.setAbstMagicBarTab('test');
    jQuery('.click-to-start-help').hide();
    jQuery('.abst-magic-bar-footer').addClass('abst-magic-bar-footer-visible');
    jQuery('#variation-editor-container, .abst-goals-column, .abst-magic-bar-footer, #abst-targeting-button, .winning-mode').show();
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
 * Generate more AI suggestions, excluding previously shown ones
 * @param {string} text - The text to generate suggestions for
 * @param {string} excludeList - Pipe-separated list of suggestions to exclude
 * @param {function} callback - Callback function(suggestions)
 */
function generateMoreSuggestions(text, excludeList, callback) {
    if (btab_vars.abst_disable_ai == '1') {
        console.log('ABST AI is disabled');
        callback([]);
        return;
    }
    
    // Use enhanced context if available
    var markdown = '';
    if (window.abstAI && window.abstAI.buildContext) {
        var aiContext = window.abstAI.buildContext({ maxContentChars: 15000 });
        markdown = window.abstAI.formatContext(aiContext);
    }
    
    var aidata = {
        'action': 'send_to_openai',
        'input_text': text,
        'type': 'magic',
        'title': 'magic',
        'context': markdown,
        'domain': btab_vars.domain,
        'exclude_suggestions': excludeList
    };
    
    if (window.abaiScreenshot) {
        aidata['screenshot'] = window.abaiScreenshot;
    }
    
    jQuery.ajax({
        url: bt_ajaxurl,
        type: 'post',
        data: aidata,
        success: function(response) {
            if (response && typeof response.error !== 'undefined') {
                console.log('AI Error:', response.error);
                callback([]);
                return;
            }
            
            try {
                var content = response.choices[0]['message']['content'];
                // Clean up response
                content = content.replace(/```json/g, '').replace(/```/g, '');
                var parsed = JSON.parse(content);
                var suggestions = parsed.suggestions || [];
                
                // Filter out any that are still in the exclude list (AI sometimes repeats)
                if (excludeList) {
                    var excludeArray = excludeList.split('|||');
                    suggestions = suggestions.filter(function(s) {
                        var text = typeof s === 'object' ? s.text : s;
                        return excludeArray.indexOf(text) === -1;
                    });
                }
                
                callback(suggestions);
            } catch (e) {
                console.log('Error parsing AI response:', e);
                callback([]);
            }
        },
        error: function(xhr, status, error) {
            console.log('AI request failed:', error);
            callback([]);
        }
    });
}

// ============================================
// CRO CHAT - Conversational AI Assistant
// ============================================

/**
 * Initialize CRO Chat state
 */
if (!window.abstCroChat) {
    window.abstCroChat = {
        history: [],
        isOpen: false,
        pageContext: null
    };
}

/**
 * Send a message to the CRO Chat AI
 * @param {string} userMessage - The user's question
 * @param {function} callback - Callback with (error, response)
 */
function sendCroChatMessage(userMessage, callback) {
    if (!userMessage || !userMessage.trim()) {
        callback('Please enter a message', null);
        return;
    }

    // Build context on first message
    if (!window.abstCroChat.pageContext && window.abstAI) {
        window.abstCroChat.pageContext = window.abstAI.formatContext(
            window.abstAI.buildContext({ maxContentChars: 30000 })
        );
    }

    var aidata = {
        'action': 'send_to_openai',
        'type': 'cro-chat',
        'input_text': window.abstCroChat.history.length ? '' : (window.abstCroChat.pageContext || ''),
        'user_question': userMessage.trim(),
        'conversation_history': JSON.stringify(window.abstCroChat.history),
        'domain': btab_vars.domain
    };

    if (window.abaiScreenshot && !window.abstCroChat.history.length) {
        aidata['screenshot'] = window.abaiScreenshot;
    }

    jQuery.ajax({
        url: bt_ajaxurl,
        type: 'post',
        data: aidata,
        success: function(response) {
            if (response && response.error) {
                callback(response.error.message || response.error, null);
                return;
            }

            var aiResponse = '';
            if (response && response.choices && response.choices[0]) {
                aiResponse = response.choices[0].message.content;
            }

            // Add to conversation history
            window.abstCroChat.history.push({ role: 'user', content: userMessage });
            window.abstCroChat.history.push({ role: 'assistant', content: aiResponse });

            // Keep history manageable (last 10 exchanges)
            if (window.abstCroChat.history.length > 20) {
                window.abstCroChat.history = window.abstCroChat.history.slice(-20);
            }

            callback(null, aiResponse);
        },
        error: function(xhr, status, error) {
            callback('Request failed: ' + error, null);
        }
    });
}

/**
 * Clear CRO Chat history
 */
function clearCroChatHistory() {
    window.abstCroChat.history = [];
    window.abstCroChat.pageContext = null;
}

// ============================================
// FULL PAGE OPTIMIZE - Bulk Element Changes
// ============================================

/**
 * Request full page optimization suggestions
 * @param {string} optimizationGoal - What the user wants to optimize for
 * @param {function} callback - Callback with (error, response)
 */
function requestFullPageOptimize(optimizationGoal, callback) {
    if (!optimizationGoal || !optimizationGoal.trim()) {
        optimizationGoal = 'Improve overall conversion rate';
    }

    // Build context
    var pageContext = '';
    if (window.abstAI) {
        pageContext = window.abstAI.formatContext(
            window.abstAI.buildContext({ maxContentChars: 30000 })
        );
    }

    var aidata = {
        'action': 'send_to_openai',
        'type': 'fullpage-optimize',
        'input_text': pageContext,
        'optimization_goal': optimizationGoal.trim(),
        'domain': btab_vars.domain
    };

    if (window.abaiScreenshot) {
        aidata['screenshot'] = window.abaiScreenshot;
    }

    jQuery.ajax({
        url: bt_ajaxurl,
        type: 'post',
        data: aidata,
        success: function(response) {
            if (response && response.error) {
                callback(response.error.message || response.error, null);
                return;
            }

            try {
                var content = response.choices[0].message.content;
                // Clean up JSON if wrapped in markdown
                content = content.replace(/```json\n?/g, '').replace(/```\n?/g, '');
                var parsed = JSON.parse(content);
                callback(null, parsed);
            } catch (e) {
                callback('Failed to parse AI response: ' + e.message, null);
            }
        },
        error: function(xhr, status, error) {
            callback('Request failed: ' + error, null);
        }
    });
}

/**
 * Apply full page optimization to create a test
 * @param {object} optimization - The parsed optimization response
 * @returns {object} Magic test definition ready to save
 */
function applyFullPageOptimization(optimization) {
    if (!optimization || !optimization.elements) {
        console.error('Invalid optimization data');
        return null;
    }

    var magicDefinitions = [];
    
    optimization.elements.forEach(function(element) {
        // Try to find the element on the page
        var foundElement = null;
        var selector = '';
        
        // Search for the original text in the page
        jQuery('h1, h2, h3, h4, h5, h6, p, a, button, span, li, td, th, label, div').each(function() {
            var text = jQuery(this).clone().children().remove().end().text().trim();
            if (text === element.original || text.includes(element.original)) {
                foundElement = this;
                return false; // break
            }
        });

        if (foundElement) {
            selector = getUniqueSelector(foundElement);
            
            // Build variations array
            var variations = [element.original]; // A = original
            if (element.variations) {
                if (element.variations.B) variations.push(element.variations.B);
                if (element.variations.C) variations.push(element.variations.C);
                if (element.variations.D) variations.push(element.variations.D);
            }

            magicDefinitions.push({
                selector: selector,
                type: 'text',
                original: element.original,
                scope: getMagicScope(),
                variations: variations,
                why: element.why || ''
            });
        } else {
            console.warn('Could not find element on page:', element.original.substring(0, 50) + '...');
        }
    });

    return {
        test_name: optimization.test_name || 'AI Full Page Optimization',
        reasoning: optimization.reasoning || '',
        elements: magicDefinitions
    };
}

// CRO Chat and Full Page Optimize are exported at the end of the file with other abstAI functions


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
                    showAISuggestionsForSelector(newSelectorValue, window.abstEditor.innerHTML, getElementType(jQuery(newSelectorValue)[0]));
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

    // CRO Chat - Send message on button click
    jQuery('body').on('click', '#abst-cro-chat-send', function(e) {
        e.preventDefault();
        renderCroChatUpgradeState();
    });

    // CRO Chat - Send on Enter key
    jQuery('body').on('keypress', '#abst-cro-chat-input', function(e) {
        if (e.which === 13) {
            e.preventDefault();
            renderCroChatUpgradeState();
        }
    });

    jQuery('body').on('click', '#abst-test-empty-chat-send', function() {
        renderCroChatUpgradeState();
    });

    jQuery('body').on('keypress', '#abst-test-empty-chat-input', function(e) {
        if (e.which === 13) {
            e.preventDefault();
            jQuery('#abst-test-empty-chat-send').trigger('click');
        }
    });

    // CRO Chat - Click test bubble to SELECT element and show AI suggestions
    // (Changed from auto-creating 4 variations to integrating with variation editor)
    jQuery('body').on('click', '.cro-chat-test-bubble', function(e) {
        e.preventDefault();
        var testData = JSON.parse(jQuery(this).attr('data-test'));
        
        // Use shared function to find matching element
        var result = findBestMatchingElement(testData.original);
        var foundElement = result.element;
        
        console.log('CRO Chat: Searching for "' + testData.original.substring(0, 20) + '...", found:', foundElement, 'score:', result.score);

        var $bubble = jQuery(this);
        
        if (foundElement) {
            var selector = getUniqueSelector(foundElement);
            
            // Initialize abmagic if needed
            if (!window.abmagic) window.abmagic = {};
            if (!window.abmagic.definition) window.abmagic.definition = [];
            
            // Hide help, show test editor (first time only)
            if (window.setAbstMagicBarTab) window.setAbstMagicBarTab('test');
            jQuery('.click-to-start-help').slideUp();
            jQuery('.abst-magic-bar-footer').addClass('abst-magic-bar-footer-visible');
            jQuery('#variation-editor-container, .abst-goals-column, .abst-magic-bar-footer, #abst-targeting-button, .winning-mode').slideDown();

            jQuery('.magic-test-name').css('display', 'flex').hide().slideDown();
            
            // Remove green AI suggestion styling from this element
            jQuery(foundElement).removeClass('abst-ai-suggested');
            jQuery(foundElement).find('.abst-ai-suggest-inline').remove();
            
            // Highlight the element
            bt_highlight(selector);
            
            // Set selector input to this element (this triggers the blur handler)
            jQuery('#abst-selector-input').val(selector);
            
            // Get first suggestion text for B variation
            var firstSuggestion = '';
            if (testData.variations && testData.variations.length > 0) {
                firstSuggestion = typeof testData.variations[0] === 'object' ? testData.variations[0].text : testData.variations[0];
            }
            
            // Add element to definition with original (A) and first suggestion (B)
            var elementIndex = getElementIndexFromMagic(selector);
            if (elementIndex === -1) {
                // Not in definition yet - check how many variations exist in the test
                var existingVariationCount = 2; // Default: A (original) + B (suggestion)
                if (window.abmagic.definition.length > 0) {
                    // Match the number of variations from existing elements
                    // variations array includes original at index 0
                    existingVariationCount = Math.max(2, window.abmagic.definition[0].variations.length);
                }
                
                // Build variations array with original + suggestions, padding with original if needed
                var newVariations = [testData.original];
                for (var i = 1; i < existingVariationCount; i++) {
                    if (testData.variations && testData.variations[i - 1]) {
                        var suggestionText = typeof testData.variations[i - 1] === 'object' ? testData.variations[i - 1].text : testData.variations[i - 1];
                        newVariations.push(suggestionText);
                    } else {
                        // Pad with original if not enough suggestions
                        newVariations.push(testData.original);
                    }
                }
                
                window.abmagic.definition.push({
                    selector: selector,
                    variations: newVariations,
                    scope: getMagicScope(),
                    type: 'text'
                });
                console.log('CRO Chat: Added element with', newVariations.length, 'variations to match existing test');
            }
            
            // Update variation picker to show correct number of variations
            updateVariationPicker();
            
            // Select B variation (index 1) in the picker without triggering change (to avoid jump)
            jQuery('#variation-picker').val('1');
            
            // Set editor content to the first suggestion (B variation)
            if (window.abstEditor) {
                window.abstEditor.innerHTML = firstSuggestion || testData.original;
            }
            
            var croChatSuggestions = formatCroChatSuggestions(testData.variations);

            // Cache the AI suggestions for this selector
            cacheAISuggestions(selector, croChatSuggestions);
            
            // Populate the AI suggestions panel with the variations
            populateAISuggestionsPanel(croChatSuggestions);
            
            // Update test title if this is the first element
            if (window.abmagic.test && window.abmagic.definition.length === 1) {
                window.abmagic.test.title = 'Test: ' + testData.element;
                window.abmagic.syncToDOM();
            }
            
            // Mark this bubble as selected (not added yet - user chooses suggestions)
            $bubble.css({
                'background': '#e3f2fd',
                'border-color': '#1976D2'
            }).addClass('cro-bubble-selected');
            
            console.log('Element selected from CRO chat:', testData.element, 'Suggestions shown:', testData.variations.length);
        } else {
            // Mark bubble as not found
            $bubble.css({
                'background': '#ffcdd2',
                'border-color': '#c62828'
            });
            alert('Could not find "' + testData.original.substring(0, 30) + '..." on the page. Try selecting the element manually.');
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

        if (jQuery(element).closest('.abst-goals-column, .abst-goals-container, .remove-goal, .abst-goal-card-header, .abst-button-container').length > 0)
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
        
        // Skip AI suggest inline buttons
        if (jQuery(element).hasClass('abst-ai-suggest-inline') || jQuery(element).closest('.abst-ai-suggest-inline').length > 0) {
            return false;
        }
        
        // Skip AI suggested elements (green box) - let them handle their own clicks
        if (jQuery(element).hasClass('abst-ai-suggested') || jQuery(element).closest('.abst-ai-suggested').length > 0) {
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
    
    window.magicLastFocus = null;


    
    // add element to magic bar click function
    jQuery('body').on('click', function(e){


        // Allow normal interaction inside the magic bar and admin bar UI
        if (e.target && (e.target.closest('#abst-magic-bar') || e.target.closest('#wpadminbar'))) {
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
        jQuery('#variation-editor-container, .abst-goals-column, .abst-magic-bar-footer, #abst-targeting-button, .winning-mode').slideDown();
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
        window.magicLastFocus = element;
    });


    
    // Prevent link clicks only when magic bar is active
    // This uses capture phase but only prevents when the class is present
    document.body.addEventListener('click', function(e) {
        // Only prevent if magic bar is active
        if (!document.body.classList.contains('doing-abst-magic-bar')) {
            return; // Allow normal behavior when magic bar is not active
        }
        
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

function setMagicBar(selector, selectorText, goal = false, type = 'text', suppressAI = false) {
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

    jQuery('#abst-selector-input').val(selector).trigger('blur');
    
    jQuery("#abst-variation-editor-container").addClass('flash');
    setTimeout(function(){
        jQuery("#abst-variation-editor-container").removeClass('flash');
    }, 2000);
    
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
                if (!suppressAI) {
                    showAISuggestionsForSelector(selector, content, type);
                } else {
                    hideAISuggestionsPanel();
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
/**
 * Checks if a class or ID should be ignored based on a single global prefix list.
 * 
 * - Entries starting with '.' are class prefixes.
 * - Entries starting with '#' are ID prefixes.
 * - Entries with no prefix are treated as class prefixes (for back-compat).
 */

async function takeScreenshot(node, options, retryCount = 0) {
    const maxRetries = 3;

    window.abTakingScreenshot = true;

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
    } finally {
        window.abTakingScreenshot = false;
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
        width: window.innerWidth || document.body.clientWidth,  // Capture the full visible viewport width
        height: Math.min((window.innerHeight || document.body.clientHeight), 3000), // Cap height to reduce processing
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
        takeScreenshot(node, options);
        return;
    } else {
        console.log('modernScreenshot not loaded yet, retrying in 500ms');
        setTimeout(updateScreenshot, 500);
    }
}


function abst_magic_bar(options = {}) {

    console.log('abst_magic_bar called');
    const hasBtabVars = typeof btab_vars !== 'undefined';
    const isAiEnabled = hasBtabVars && btab_vars.abst_disable_ai !== '1';

    // Load dom-to-image if not already loaded and ai is enabled (non-blocking)
    if(!window.abaiScreenshot && isAiEnabled) {
        console.log('Loading dom-to-image for magic bar');
        // Load screenshot in background without blocking magic bar display
        updateScreenshot();
        // Don't wait for screenshot - continue with magic bar setup
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
            Additional goals are a Pro feature.  
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
        <div class="abst-magic-tabs" role="tablist" aria-label="Magic Bar sections">
            <button type="button" class="abst-magic-tab-button" id="abst-magic-tab-chat" data-tab="chat" role="tab" aria-selected="false" aria-controls="abst-magic-panel-chat" tabindex="-1">ChatCRO</button>
            <button type="button" class="abst-magic-tab-button is-active" id="abst-magic-tab-test" data-tab="test" role="tab" aria-selected="true" aria-controls="abst-magic-panel-test">Test</button>
        </div>
        <div class="abst-magic-tab-panels">
        <div class="abst-magic-tab-panel" id="abst-magic-panel-chat" data-tab-panel="chat" role="tabpanel" aria-labelledby="abst-magic-tab-chat" hidden>
        <!-- CRO Expert Chat - Subtle styling -->
        <div class="abst-settings-column" id="abst-cro-chat-column" style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 0; margin: 0 !important; padding: 12px; width:100%;">
            <div id="abst-cro-chat-container" style="display: flex; flex-direction: column;">
                <h4 style="margin: 0 0 8px 0; color: #94a3b8; font-size: 14px;">💬 ChatCRO</h4>
                <div id="abst-cro-chat-messages" style="flex: 1; max-height: 200px; overflow-y: auto; margin-bottom: 8px; padding: 8px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 4px; font-size: 12px; color: #64748b; opacity: 0.7;">
                    <div class="abst-cro-chat-response abst-cro-chat-response--assistant"><p>Try AI features.</p><p><a href="https://absplittest.com/pricing/?utm_source=lite-plugin&utm_medium=magic-bar&utm_campaign=chatcro-upsell" target="_blank" rel="noopener noreferrer">Try ChatCRO free for 7 days</a></p></div>
                </div>
                <div id="abst-cro-chat-footer">
                    <div id="abst-cro-chat-suggestions" style="display: none; margin-bottom: 8px; padding: 8px; background: #f0fdf4; border-radius: 4px; border: 1px solid #bbf7d0;">
                        <p style="margin: 0 0 6px 0; font-size: 11px; color: #166534;"><strong>Suggestions:</strong><small>Click to create the test</small></p>
                        <div id="abst-cro-chat-suggestions-list"></div>
                    </div>
                    <div id="abst-cro-chat-input-row" style="display: flex; gap: 6px; opacity: 0.6; pointer-events: none;">
                        <input type="text" id="abst-cro-chat-input" placeholder="Subscribe for AI features" disabled style="flex: 1; padding: 6px 8px; border: 1px solid #cbd5e1; border-radius: 4px; font-size: 12px; background: #f1f5f9;">
                        <button id="abst-cro-chat-send" disabled style="padding: 6px 12px; background: #94a3b8; color: white; border: none; border-radius: 4px; cursor: not-allowed; font-size: 12px;">Ask</button>
                    </div>
                </div>
            </div>
        </div>
        </div>
        <div class="abst-magic-tab-panel is-active" id="abst-magic-panel-test" data-tab-panel="test" role="tabpanel" aria-labelledby="abst-magic-tab-test">
        <!-- Click to start help -->
        <div class="abst-settings-column click-to-start-help">
            <h3 style="color: #9e9e9e;">Create a Split Test.</h3>
            <p style="font-size: 24px; line-height: 1.25; margin: 40px 0 8px;">To start: Click the element you want to change.</p>
            <p style="font-size: 12px; line-height: 1.6; margin: 40px 0 20px 0; color: #9e9e9e;">Not sure what to test? Upgrade to unlock AI agent suggestions and ChatCRO guidance.</p>
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
            <p id="version-value">Editing the B Version of the test</p>
            <div id="abst-variation-editor"></div>
                <div id="ai-suggestions" style="display: none;">
                <button class="abst-ai-suggestions-toggle" aria-expanded="false">
                    <span>AI Suggestions ✨</span>
                    <span class="abst-ai-toggle-chevron">▾</span>
                </button>
                <div class="abst-ai-suggestions-content" style="display:none;">
                    <ul id="ai-suggestions-list"></ul>
                </div>
                </div>
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
            ${abst_magic_data.is_agency ? `<div class="abst-settings-column winning-mode"><p>Winning Mode:</p><select id="abst-conversion-style"><option value="bayesian">Standard - Bayesian</option><option value="thompson">Dynamic - Multi Armed Bandit</option></select></div>` : ''}
            <!-- Goals Column -->
            <div class="abst-goals-column" data-llm-instructions="conversion goals are defined here. it's usually best to specify the overall website goal, like a purchase or a form contact, rather than a generic button, unless you are testing the text or text around that button">
                <div class="abst-goals-title">Goals</div>
                <div class="abst-goals-container" data-goal="0">
                <div class="abst-goal-card-header"><p class="abst-goal-card-title">Primary Goal</p></div>
                    <div id="abst-primary-goal-container">${abst_magic_data.goals}</div>
                    <div class="goal-value-label"></div>
                    <input type="text" class="abst-goal-input-value" placeholder="">
                </div>
                <div class="abst-button-container">
                    <p class="abst-goal-upgrade-text">Add additional goals, external conversions & revenue optimization (Woo, EDD, SureCart etc) by going <a href="https://absplittest.com/pricing?utm_source=lite_magic_goals" target="_blank" rel="noopener noreferrer">Pro</a>.</p>
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

    jQuery('.abst-ai-suggestions-toggle').html('<span class="abst-ai-suggestions-toggle-label"><span class="abst-ai-suggestions-icon">*</span><span>AI Suggestions</span></span><span class="abst-ai-suggestions-inline-loading ai-loading" style="display:none;"><span class="abst-ai-loading-text">Upgrade for AI Suggestions</span></span><span class="abst-ai-toggle-chevron">v</span>');

    renderAiUpgradeState();
    renderCroChatUpgradeState();

    // AI suggestions toggle
    jQuery(document).on('click', '.abst-ai-suggestions-toggle', function() {
        var $toggle = jQuery(this);
        var expanded = $toggle.attr('aria-expanded') === 'true';
        setAISuggestionsExpanded(!expanded);
    });

    window.abmagic = window.abmagic || {};
    window.setAbstMagicBarTab = function(tabName) {
        var $magicBar = jQuery('#abst-magic-bar');
        var $targetButton = $magicBar.find('.abst-magic-tab-button[data-tab="' + tabName + '"]');
        var $targetPanel = $magicBar.find('.abst-magic-tab-panel[data-tab-panel="' + tabName + '"]');

        if (!$magicBar.length || !$targetButton.length || !$targetPanel.length) {
            return;
        }

        $magicBar.find('.abst-magic-tab-button')
            .removeClass('is-active')
            .attr('aria-selected', 'false')
            .attr('tabindex', '-1');

        $targetButton
            .addClass('is-active')
            .attr('aria-selected', 'true')
            .attr('tabindex', '0');

        $magicBar.find('.abst-magic-tab-panel')
            .removeClass('is-active')
            .attr('hidden', true);

        $targetPanel
            .addClass('is-active')
            .removeAttr('hidden');

        $magicBar
            .removeClass('abst-active-tab-chat abst-active-tab-test')
            .addClass('abst-active-tab-' + tabName);

        window.abmagic.activeTab = tabName;
    };

    jQuery(magicBar).on('click', '.abst-magic-tab-button', function() {
        window.setAbstMagicBarTab(jQuery(this).data('tab'));
    });

    window.setAbstMagicBarTab('test');

    //if localstorage localStorage.setItem('abst-magic-help', 'false'); then hide
    if(localStorage.getItem('abst-magic-help') === 'false'){
        jQuery('.abst-settings-column.help').hide();
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
        conversion_style: 'bayesian',
        use_order_value: false,
        url_query: '',
        targeting: {
            device_size: ['desktop', 'tablet', 'mobile'],
            traffic_percentage: 100,
            allowed_roles: abst_magic_data.defaults || [],
            scope: getDefaultMagicScope()
        },
        goals: {
            primary: { type: 'click', value: '' },
            secondary: []
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
        
        // Conversion style
        test.conversion_style = jQuery('#abst-conversion-style').val() || 'bayesian';
        test.use_order_value = false;
        
        // URL query
        test.url_query = jQuery('#abst-url-query').val() || '';
        
        // Targeting
        test.targeting.device_size = jQuery('#abst-device-size').val() || ['desktop', 'tablet', 'mobile'];
        test.targeting.traffic_percentage = parseInt(jQuery('#abst-traffic-percentage').val()) || 100;
        test.targeting.allowed_roles = jQuery('#abst-user-roles-container input[type="checkbox"]:checked').map(function() {
            return jQuery(this).val();
        }).get();
        test.targeting.scope = getMagicScopeFromInputs();
        
        // Primary goal
        var primaryGoalType = jQuery('.abst-goals-container').first().find('select.goal-type').first().val() || 'click';
        var primaryGoalValue = jQuery('.abst-goals-container').first().find('.abst-goal-input-value').val() || '';
        test.goals.primary = { type: primaryGoalType, value: primaryGoalValue };
        
        // Secondary goals (from get_goals_from_dom)
        test.goals.secondary = [];
        jQuery('.abst-goals-container').each(function(index) {
            if (index === 0) return; // Skip primary
            var goalType = jQuery(this).find('select.goal-type').first().val();
            var goalValue = jQuery(this).find('.abst-goal-input-value').val() || '';
            if (goalType) {
                test.goals.secondary.push({ type: goalType, value: goalValue });
            }
        });
        
        console.log('ABST: Synced from DOM', window.abmagic.test);
    };
    
    // Sync Object → DOM (called when object changes programmatically)
    window.abmagic.syncToDOM = function() {
        var test = window.abmagic.test;
        var primaryGoal = test.goals && test.goals.primary ? {
            type: test.goals.primary.type,
            value: test.goals.primary.value
        } : null;
        var secondaryGoals = Array.isArray(test.goals && test.goals.secondary) ? test.goals.secondary.map(function(goal) {
            return {
                type: goal.type,
                value: goal.value
            };
        }) : [];

        window.abmagic.isSyncingToDOM = true;
        
        // Title
        jQuery('#abst-magic-bar-title').val(test.title);
        
        // Conversion style
        if (jQuery('#abst-conversion-style').length) {
            jQuery('#abst-conversion-style').val(test.conversion_style);
        }

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
        
        // Primary goal
        if (primaryGoal && primaryGoal.type) {
            applyMagicGoalToContainer(jQuery('.abst-goals-container').first(), primaryGoal);
        }

        jQuery('.abst-goals-container').slice(1).remove();
        if (secondaryGoals.length) {
            secondaryGoals.forEach(function(goal, index) {
                var goalNumber = index + 2;
                jQuery('<div class="abst-goals-container" data-goal="' + goalNumber + '"><div class="abst-goal-card-header"><p class="abst-goal-card-title">Goal ' + goalNumber + '</p><div class="remove-goal" aria-label="Remove goal">X</div></div>' + abst_magic_data.goals + '<div class="goal-value-label"></div><input type="text" class="abst-goal-input-value" placeholder="Enter goal"></div>').insertBefore('.abst-button-container');
                var $goalContainer = jQuery('.abst-goals-container').last();
                applyMagicGoalToContainer($goalContainer, goal);
            });
        }
        
        // Update variation picker for definition changes
        if (typeof updateVariationPicker === 'function') {
            updateVariationPicker();
        }

        window.abmagic.isSyncingToDOM = false;
        
        console.log('ABST: Synced to DOM', window.abmagic.test);
    };
    
    // Bind DOM change events to sync to object
    jQuery(document).on('change input', '#abst-magic-bar-title, #abst-conversion-style, #abst-url-query, #abst-device-size, #abst-traffic-percentage, #abst-scope-mode, #abst-scope-value', function() {
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
    jQuery(document).on('change', '.abst-goals-container select, .abst-goals-container .abst-goal-input-value', function() {
        window.abmagic.syncFromDOM();
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
(function($) {
    $(function() {
        
        //if url contains query string abmagic then load magic bar
        if(window.location.search.includes('abmagic')) {
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

        jQuery("body").on('click','.abst-add-goal-button',function(){
            //if theres less than 11 goals
            if(jQuery('.abst-goals-container').length < 10){
                // Remove is-active from all existing goals containers
                goalCount = jQuery('.abst-goals-container').length + 1;
                var $newGoal = jQuery('<div class="abst-goals-container" data-goal="'+goalCount+'"><div class="abst-goal-card-header"><p class="abst-goal-card-title">Goal '+goalCount+'</p><div class="remove-goal" aria-label="Remove goal">X</div></div>' +abst_magic_data.goals+'<div class="goal-value-label"></div><input type="text" class="abst-goal-input-value" placeholder="Enter goal"></div>').insertBefore('.abst-button-container');
                // Add is-active to the new goal
                setGoalsContainerActive($newGoal[0], true);
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

        jQuery('body').on('click','.hide-magic-bar',function(){
            jQuery('.abst-settings-column.help').toggleClass('abst-hidden');
            //set localStorage
            localStorage.setItem('abst-magic-help', 'false');
        })
        
        // AI Suggestion - "Add as Variation" button click
        jQuery('body').on('click', '.ai-add-variation', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            var $item = jQuery(this).closest('.ai-suggestion-item');
            var text = $item.attr('data-suggestion-text') || $item.find('.ai-suggestion-text').text().trim();
            
            // Add as new variation
            addVariationFromSuggestion(text);
            
            // Mark item as added
            $item.css({
                'background': '#c8e6c9',
                'opacity': '0.7'
            }).find('.ai-suggestion-actions').html('<span style="color: #2e7d32;">✓ Added</span>');
            
            console.log('Added suggestion as new variation:', text);
        });
        
        // AI Suggestion - "Replace Current" button click
        jQuery('body').on('click', '.ai-replace-current', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            var $item = jQuery(this).closest('.ai-suggestion-item');
            var text = $item.attr('data-suggestion-text') || $item.find('.ai-suggestion-text').text().trim();
            
            // Check if we're on a variation (not control)
            if (jQuery("#variation-picker").val() == '0') {
                alert('Please select a variation first (not the Control)');
                return;
            }
            
            // Replace current editor content
            if (window.abstEditor) {
                window.abstEditor.innerHTML = text;
                
                // Trigger input event to mark as changed
                const event = new Event('input', { bubbles: true });
                window.abstEditor.dispatchEvent(event);
            }
            
            // Fade out the item
            $item.fadeOut(200);
            
            console.log('Replaced current variation with:', text);
        });
        
        // AI Suggestion - Click on item text (legacy behavior - replace current)
        jQuery('body').on('click', '.ai-suggestion-item', function(e) {
            // Don't trigger if clicking on buttons
            if (jQuery(e.target).closest('.ai-suggestion-actions').length > 0) {
                return;
            }
            
            // Legacy behavior: clicking the item replaces current
            if (jQuery("#variation-picker").val() == '0') {
                alert('Please select a variation first');
                return;
            }

            var text = jQuery(this).attr('data-suggestion-text') || jQuery(this).find('.ai-suggestion-text').text().trim();
            
            jQuery(this).fadeOut(200);
            
            if (window.abstEditor) {
                window.abstEditor.innerHTML = text;

                // Trigger input event to mark as changed
                const event = new Event('input', { bubbles: true });
                window.abstEditor.dispatchEvent(event);
            }
        });
        
        // AI Suggestion - "Generate More" button click
        jQuery('body').on('click', '#ai-generate-more', function(e) {
            e.preventDefault();
            
            var selector = jQuery('#abst-selector-input').val();
            if (!selector) {
                alert('Please select an element first');
                return;
            }
            
            // Get current text from editor
            var currentText = '';
            if (window.abstEditor) {
                currentText = window.abstEditor.innerText || window.abstEditor.textContent || '';
            }
            if (!currentText) {
                currentText = jQuery(selector).text().trim();
            }
            
            if (!currentText) {
                alert('No text content to generate suggestions for');
                return;
            }
            
            // Get history of suggestions to exclude
            var history = getSuggestionHistory(selector);
            
            // Show loading state
            var $btn = jQuery(this);
            var originalText = $btn.text();
            $btn.text('Generating...').prop('disabled', true);
            
            // Build exclusion list for the AI prompt
            var excludeList = history.length > 0 ? history.join('|||') : '';
            
            console.log('Generating more suggestions, excluding:', history.length, 'previous suggestions');
            
            // Call AI with exclusion list
            generateMoreSuggestions(currentText, excludeList, function(newSuggestions) {
                $btn.text(originalText).prop('disabled', false);
                
                if (newSuggestions && newSuggestions.length > 0) {
                    // Cache the new suggestions (adds to history automatically)
                    cacheAISuggestions(selector, newSuggestions);
                    // Append to existing list
                    populateAISuggestionsPanel(newSuggestions, true);
                } else {
                    alert('No new suggestions available. Try editing the text first.');
                }
            });
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




        jQuery('body').on('change', "#variation-picker", function() {
            //get variation
            if(jQuery("#variation-picker :selected").text().includes('Control')){
                jQuery("#version-value").text("View the original text.");
            }
            else{
                jQuery("#version-value").text("Edit the " + jQuery("#variation-picker :selected").text() + " of the test");
            }
            var variationIndexRaw = jQuery("#variation-picker").val();
            var variationIndex = (variationIndexRaw === 'addAnother') ? 'addAnother' : (parseInt(variationIndexRaw) || 0);

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
            
            //if the variation index is the last one, then change the label to Variation C/d/e/f/g etc and add another option below with label " Add Variation"
            
            if(variationIndex === 'addAnother'){
                //remove existing add another option
                jQuery("#variation-picker option[value='addAnother']").remove();
                console.log('add variation');
                var nextIndex = jQuery("#variation-picker option").length;
                var label = getVariationLabel(nextIndex);
                var newOptions = '<option value="'+nextIndex+'">'+label+' Version</option><option value="addAnother"> + Add Version</option>';
                jQuery("#variation-picker").append(newOptions);
                jQuery("#variation-picker").val(nextIndex);
                
                // Add new variation to ALL elements in the definition using their original (control) text
                if (window.abmagic && window.abmagic.definition) {
                    window.abmagic.definition.forEach(function(def) {
                        if (def.variations && def.variations.length < nextIndex + 1) {
                            // Pad with the original (control) text for any missing variations
                            while (def.variations.length < nextIndex + 1) {
                                def.variations.push(def.variations[0]); // Use original/control text
                            }
                        }
                    });
                    console.log('Added variation to all elements:', window.abmagic.definition);
                }
                
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
                showAISuggestionsForSelector(selector, currentText, elementDef.type);
                
                // Update the hidden input
                jQuery('#abst-variation-data').val(JSON.stringify(window.abmagic.definition));
                
                // Trigger input event to mark as changed
                const event = new Event('input', { bubbles: true });
                window.abstEditor.dispatchEvent(event);
            }
        });

        jQuery('body').on('click', ".abst-variation-remove", function(e) {
            e.preventDefault();
            removeMagicVariation(getMagicVariationCount() - 1);
        });


        
        
        jQuery('body').on('click','.remove-goal',function(){
            //remove the container
            jQuery(this).closest('.abst-goals-container').remove();
        });

        jQuery('body').on('click','[magic-eid]',function(){
            showMagicTest(jQuery(this).attr('magic-eid'), jQuery(this).attr('magic-index'),true);
        });


        //on select.goal-type change if it equals 'page' then append a pageselector dropdown that updates the input value next to it
        jQuery('body').on('change', '.abst-goals-container select.goal-type', function() { // update hidden input value
            jQuery(this).parents('.abst-goals-container').find('.abst-goal-input-value').val('');
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
                goalContainer.find('.goal-value-label').text('Create test, then view the test in the admin to see conversion script that you can paste anywhere on your site.').show();
            }
            if (type == 'page') {
                goalContainer.find('.goal-value-label').text('Choose Page that will trigger a goal when visited').show();
            }
            else if (abstSpecialPages.includes(type) || (type && type.startsWith(abstFormConversionPrefix))) { // abstSpecialPages or form conversions
                goalContainer.find('.abst-goal-input-value').val(type).hide();
                if (type && type.startsWith(abstFormConversionPrefix)) {
                    goalContainer.find('.goal-value-label').text('Form submission will trigger this goal').show();
                }
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
            else if (abstSpecialPages.includes(type) || (type && type.startsWith(abstFormConversionPrefix))) { // Handles 'woo-order-pay', 'woo-order-received', 'woo', form-*
                 goalContainer.find('.abst-goal-input-value').val(type).hide();
                 if (type && type.startsWith(abstFormConversionPrefix)) {
                     goalContainer.find('.goal-value-label').text('Form submission will trigger this goal').show();
                 } else {
                     goalContainer.find('.goal-value-label').text('Goal for: ' + type).show(); 
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


        function saveMagicTest(postStatus){
            //get all magic data
            postStatus = postStatus || 'publish';
            var isDraftSave = postStatus === 'draft';

            //if theres no magicdata then alert
            if(!window.abmagic || !window.abmagic.definition || window.abmagic.definition.length == 0) {
                alert('Please add at least one element to the test');
                return;
            }

            var goalType = jQuery('.abst-goals-container').first().find('.goal-type').val();
            if (!isDraftSave && !jQuery('.abst-goals-container').first().find('.abst-goal-input-value').val() && !abstSpecialPages.includes(goalType) && !(goalType && goalType.startsWith(abstFormConversionPrefix))) {
                alert('Please add at least one goal to the test');
                //scroll #abst-magic-bar to the bottom
                jQuery('#abst-magic-bar').animate({
                    scrollTop: jQuery('#abst-magic-bar').height()
                }, 1000);
                return;
            }

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
                    
                    // Sanitize variations array
                    if (item.variations && Array.isArray(item.variations)) {
                        sanitized.variations = item.variations.map(function(variation) {
                            // Convert null/undefined to empty string
                            if (variation === null || variation === undefined) return '';
                            // Ensure it's a string
                            return String(variation);
                        });
                    } else {
                        sanitized.variations = [''];
                    }
                    
                    return sanitized;
                }).filter(function(item) { return item !== null; }); // Remove null items
            }

            window.abmagic.definition = sanitizedDefinition;
            
            // Read from unified test object (same data structure sent to server)
            var test = window.abmagic.test;
            var primaryGoal = test.goals.primary;
            
            var newTestData = {
                action: 'create_new_on_page_test',
                abst_magic_mode: 1,
                post_title: test.title,
                post_id: (window.abmagic && window.abmagic.editingTestId) ? window.abmagic.editingTestId : 'new',
                post_status: postStatus,
                magic_definition: JSON.stringify(sanitizedDefinition),
                test_type: 'magic',
                conversion_style: test.conversion_style,
                bt_experiments_url_query: test.url_query,
                bt_experiments_conversion_page: primaryGoal.type,
                bt_experiments_conversion_page_selector: '',
                bt_experiments_conversion_url: '',
                bt_experiments_conversion_selector: '',
                bt_experiments_conversion_link_pattern: '',
                bt_experiments_full_page_default_page: '',
                css_test_variations: '',
                bt_experiments_conversion_order_value: 0,
                bt_experiments_conversion_time: '',
                bt_experiments_conversion_text: '',
                bt_experiments_target_option_device_size: test.targeting.device_size,
                bt_experiments_target_percentage: test.targeting.traffic_percentage,
                bt_allowed_roles: test.targeting.allowed_roles,
                goal: get_goals_from_dom(),
                
            };

            // Set the appropriate conversion field based on goal type
            if (primaryGoal.type === 'page')
                newTestData.bt_experiments_conversion_page = primaryGoal.value;

            if (primaryGoal.type === 'url')
                newTestData.bt_experiments_conversion_url = primaryGoal.value;

            if (primaryGoal.type === 'time')
                newTestData.bt_experiments_conversion_time = primaryGoal.value;

            if (primaryGoal.type === 'text')
                newTestData.bt_experiments_conversion_text = primaryGoal.value;

            if (primaryGoal.type === 'link')
                newTestData.bt_experiments_conversion_link_pattern = primaryGoal.value;
            
            if (primaryGoal.type === 'selector')
                newTestData.bt_experiments_conversion_selector = primaryGoal.value;
            
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
                            alert('The test was saved, but the response could not be understood. Please reload and confirm your changes.');
                            return;
                        }
                    }

                    if(response.post_title && response.post_title !== ''){
                        var message = isDraftSave ? ' saved as a draft.' : (response.updated ? ' updated.' : ' created, reloading page.');
                        alert(response.post_title + message);
                        var urlParams = new URLSearchParams(window.location.search);
                        var returnTo = urlParams.get('return_to');
                        if (isDraftSave && response.edit_url) {
                            window.location.href = response.edit_url;
                        } else if (response.updated && returnTo) {
                            window.location.href = decodeURIComponent(returnTo);
                        } else {
                            window.location.href = window.location.pathname;
                        }
                    }
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
    
    // Ensure element has position relative for absolute positioning
    if ($element.css('position') === 'static') {
        $element.css('position', 'relative');
    }
    
    // Build variation buttons
    var buttonsHtml = '';
    var numVariations = definition.variations ? definition.variations.length : 2;
    var currentVariation = parseInt(jQuery('#variation-picker').val()) || 0;
    
    for (var i = 0; i < numVariations; i++) {
        var label = getVariationLabel(i);
        var activeClass = (i === currentVariation) ? ' active' : '';
        var title = 'Show ' + label + ' Version' + (i > 0 ? ' (right-click to remove)' : '');
        buttonsHtml += '<button class="abst-marker-var' + activeClass + '" data-var="' + i + '" data-def="' + defIndex + '" title="' + title + '">' + label + '</button>';
    }
    
    // Add "add variation" button
    buttonsHtml += '<button class="abst-marker-add" data-def="' + defIndex + '" title="Add new variation">+</button>';
    
    // Add remove button
    buttonsHtml += '<button class="abst-marker-remove" data-def="' + defIndex + '" title="Remove element from test">×</button>';
    
    var $marker = jQuery('<div class="abst-variation-marker">' + buttonsHtml + '</div>');
    $element.append($marker);
    positionVariationMarker($marker, $element);
}

function positionVariationMarker($marker, $element) {
    if (!$marker || !$marker.length || !$element || !$element.length) {
        return;
    }

    var markerWidth = $marker.outerWidth() || 0;
    var markerHeight = $marker.outerHeight() || 0;
    var elementRect = $element[0].getBoundingClientRect();
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
        left: (viewportLeft - elementRect.left) + 'px',
        right: 'auto',
        top: (viewportTop - elementRect.top) + 'px',
        display: 'inline-flex'
    });
}

function repositionVariationMarkers() {
    jQuery('.abst-variation-marker').each(function() {
        var $marker = jQuery(this);
        positionVariationMarker($marker, $marker.parent());
    });
}

jQuery(window).on('resize scroll', function() {
    repositionVariationMarkers();
});

// Handle variation marker button clicks - swap variation
jQuery('body').on('click', '.abst-marker-var', function(e) {
    e.preventDefault();
    e.stopPropagation();
    
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
            showAISuggestionsForSelector(def.selector, content, def.type);
        }
    }
    
    // Update the variation picker and trigger change
    jQuery('#variation-picker').val(String(varIndex)).trigger('change');
    
    // Update active state on all markers
    jQuery('.abst-variation-marker .abst-marker-var').removeClass('active');
    jQuery('.abst-variation-marker .abst-marker-var[data-var="' + varIndex + '"]').addClass('active');
});

// Handle right-click on a variation label - remove that variation across the test
jQuery('body').on('contextmenu', '.abst-marker-var', function(e) {
    e.preventDefault();
    e.stopPropagation();

    var varIndex = parseInt(jQuery(this).data('var'), 10);
    removeMagicVariation(varIndex);
});

// Handle add variation button clicks
jQuery('body').on('click', '.abst-marker-add', function(e) {
    e.preventDefault();
    e.stopPropagation();
    
    console.log('Add variation button clicked');
    var defIndex = jQuery(this).data('def');
    
    // Get the definition for this element and set it as current
    if (window.abmagic && window.abmagic.definition && window.abmagic.definition[defIndex]) {
        var def = window.abmagic.definition[defIndex];
        
        // Update selector input to this element
        jQuery('#abst-selector-input').val(def.selector);
        showAISuggestionsForSelector(def.selector, def.variations[1] || def.variations[0] || '', def.type);
    }
    
    // Trigger the "Add Version" option in the picker
    jQuery('#variation-picker').val('addAnother').trigger('change');
});

// Handle remove button clicks
jQuery('body').on('click', '.abst-marker-remove', function(e) {
    e.preventDefault();
    e.stopPropagation();
    
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

function getMagicVariationCount() {
    var maxVariations = 1;

    if (!window.abmagic || !window.abmagic.definition) {
        return maxVariations;
    }

    window.abmagic.definition.forEach(function(def) {
        if (def.variations && def.variations.length > maxVariations) {
            maxVariations = def.variations.length;
        }
    });

    return maxVariations;
}

function removeMagicVariation(variationIndex) {
    if (!window.abmagic || !window.abmagic.definition || !Array.isArray(window.abmagic.definition)) {
        return;
    }

    if (variationIndex === 0) {
        alert('The Control version cannot be removed.');
        return;
    }

    var variationCount = getMagicVariationCount();
    if (variationCount <= 2) {
        alert('A magic test needs at least one variation.');
        return;
    }

    if (variationIndex < 1 || variationIndex >= variationCount) {
        return;
    }

    var label = getVariationLabel(variationIndex);
    if (!confirm('Remove Variation ' + label + ' from this magic test?')) {
        return;
    }

    var currentValue = jQuery('#variation-picker').val();
    var currentIndex = currentValue === 'addAnother' ? 1 : (parseInt(currentValue, 10) || 0);

    window.abmagic.definition.forEach(function(def) {
        if (def.variations && def.variations.length > variationIndex) {
            def.variations.splice(variationIndex, 1);
        }
    });

    var nextCount = getMagicVariationCount();
    var nextIndex = currentIndex;
    if (currentIndex === variationIndex) {
        nextIndex = Math.min(variationIndex, nextCount - 1);
    } else if (currentIndex > variationIndex) {
        nextIndex = currentIndex - 1;
    }
    nextIndex = Math.max(1, Math.min(nextIndex, nextCount - 1));

    jQuery('#abst-variation-data').val(JSON.stringify(window.abmagic.definition));
    updateVariationPicker(nextIndex);
}

/**
 * Update the variation picker dropdown based on current definition
 * Rebuilds options to match the number of variations in the first definition entry
 */
function updateVariationPicker(selectedIndex) {
    if (!window.abmagic || !window.abmagic.definition || window.abmagic.definition.length === 0) {
        return;
    }
    
    var maxVariations = getMagicVariationCount();
    selectedIndex = (typeof selectedIndex === 'number') ? selectedIndex : 1;
    selectedIndex = Math.max(0, Math.min(selectedIndex, maxVariations - 1));
    
    // Build options HTML
    var optionsHtml = '';
    for (var i = 0; i < maxVariations; i++) {
        var label = getVariationLabel(i);
        var suffix = (i === 0) ? ' Version - Control' : ' Version';
        var selected = (i === selectedIndex) ? ' selected' : '';
        optionsHtml += '<option value="' + i + '"' + selected + '>' + label + suffix + '</option>';
    }
    optionsHtml += '<option value="addAnother"> + Add Version</option>';
    
    // Update the picker and trigger change to refresh editor
    jQuery('#variation-picker').html(optionsHtml).trigger('change');
    
    console.log('ABST: Variation picker updated with', maxVariations, 'variations');
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

// Added proper file ending to fix syntax error

/**
 * ABST AI Context Helper Functions
 * Provides enhanced context generation for AI features with token limiting and page metadata
 */

/**
 * Gets page content with smart truncation to prevent token overflow
 * @param {number} maxChars - Maximum characters to return (default 30000 ~ 8000 tokens)
 * @returns {string} Cleaned HTML content, truncated if necessary
 */
function getAbPageContentEnhanced(maxChars) {
    if (typeof maxChars === 'undefined') maxChars = 30000;
    
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
    ];
    
    var abPageContent = false;
    jQuery.each(selectorList, function(key, selector) {
        if (jQuery(selector).length) {
            abPageContent = jQuery(selector).clone();
            return false;
        }
    });

    if (!abPageContent || !abPageContent.length) {
        return '';
    }

    // Remove non-content elements (keep header/footer for context)
    abPageContent.find('source, iframe, #wpadminbar, #abst-magic-bar, script, style, #ab-ai-form, meta, link, noscript, svg, canvas, .screen-reader-text').remove();

    // Clean attributes but preserve structure
    abPageContent.find('*').removeAttr('style').removeAttr('onclick').removeAttr('onload');
    
    // Remove data attributes
    abPageContent.find('*').each(function() {
        var el = jQuery(this);
        var attrsToRemove = [];
        jQuery.each(this.attributes || [], function() {
            if (this && this.name && this.name.startsWith('data-')) {
                attrsToRemove.push(this.name);
            }
        });
        for (var i = 0; i < attrsToRemove.length; i++) {
            el.removeAttr(attrsToRemove[i]);
        }
    });

    // Remove empty whitespace nodes
    abPageContent.find('*').contents().filter(function() {
        return this.nodeType === 3 && !/\S/.test(this.nodeValue);
    }).remove();

    var cleanedHtml = abPageContent.html() || '';

    // Smart truncation: keep beginning (hero/intro) and end (CTAs/footer)
    if (cleanedHtml.length > maxChars) {
        var keepStart = Math.floor(maxChars * 0.80);
        var keepEnd = Math.floor(maxChars * 0.15);
        var truncatedLength = cleanedHtml.length;
        
        cleanedHtml = cleanedHtml.substring(0, keepStart) + 
                   '\n\n<!-- ... ' + Math.round((truncatedLength - keepStart - keepEnd) / 1000) + 'k chars truncated ... -->\n\n' + 
                   cleanedHtml.substring(cleanedHtml.length - keepEnd);
        
        console.log('ABST AI: Content truncated from ' + truncatedLength + ' to ' + cleanedHtml.length + ' chars');
    }

    return cleanedHtml;
}

/**
 * Detects the type of page based on body classes and content
 * @returns {string} Page type identifier
 */
function detectPageType() {
    var body = jQuery('body');
    
    if (body.hasClass('home') || body.hasClass('front-page') || window.location.pathname === '/') 
        return 'homepage';
    if (body.hasClass('single-product') || jQuery('.product, .woocommerce-product').length) 
        return 'product';
    if (body.hasClass('single-post') || (body.hasClass('single') && jQuery('article.post').length)) 
        return 'blog-post';
    if (body.hasClass('archive') || body.hasClass('category') || body.hasClass('tag')) 
        return 'archive';
    if (body.hasClass('page-template-landing') || jQuery('.landing-page, [class*="landing"]').length) 
        return 'landing-page';
    if (jQuery('form.checkout, .woocommerce-checkout').length) 
        return 'checkout';
    if (jQuery('form.cart, .add-to-cart').length) 
        return 'product';
    if (jQuery('form:not([role="search"])').length > 0) 
        return 'lead-capture';
    
    return 'page';
}

/**
 * Gets structured page metadata for AI context
 * @returns {object} Page metadata object
 */
function getPageMetadata() {
    var h1Text = jQuery('h1').first().text().trim();
    var metaDesc = jQuery('meta[name="description"]').attr('content') || '';
    
    // Find primary CTA
    var primaryCTA = '';
    var ctaSelectors = [
        'a.btn-primary, button.btn-primary',
        '.hero a.btn, .hero button',
        'a.cta, button.cta',
        '.wp-block-button a',
        'a[class*="button"]:first',
        'button[type="submit"]'
    ];
    for (var i = 0; i < ctaSelectors.length; i++) {
        var cta = jQuery(ctaSelectors[i]).first().text().trim();
        if (cta && cta.length > 2 && cta.length < 50) {
            primaryCTA = cta;
            break;
        }
    }

    return {
        url: window.location.href,
        path: window.location.pathname,
        title: document.title,
        pageType: detectPageType(),
        h1: h1Text.substring(0, 200),
        primaryCTA: primaryCTA,
        metaDescription: metaDesc.substring(0, 300),
        hasForm: jQuery('form:not([role="search"])').length > 0,
        hasVideo: jQuery('video, iframe[src*="youtube"], iframe[src*="vimeo"]').length > 0,
        wordCount: jQuery('body').text().split(/\s+/).length
    };
}

/**
 * Extracts all headlines (h1-h3) from the page
 * @returns {array} Array of headline objects with tag and text
 */
function getHeadlines() {
    var headlines = [];
    jQuery('h1, h2, h3').each(function() {
        var text = jQuery(this).text().trim();
        if (text && text.length > 2 && text.length < 300) {
            headlines.push({
                tag: this.tagName.toLowerCase(),
                text: text
            });
        }
    });
    return headlines.slice(0, 20);
}

/**
 * Extracts CTA buttons and links from the page
 * @returns {array} Array of CTA text strings
 */
function getCTAs() {
    var ctas = [];
    var seen = {};
    
    // Exclude Magic Bar and admin bar elements
    jQuery('a.btn, button, .cta, .wp-block-button a, [class*="button"], a[class*="btn"], input[type="submit"]')
        .not('#abst-magic-bar *, #wpadminbar *, #ab-ai-form *')
        .each(function() {
        var text = jQuery(this).text().trim() || jQuery(this).val() || '';
        text = text.substring(0, 50);
        
        if (text && text.length > 1 && !seen[text.toLowerCase()]) {
            seen[text.toLowerCase()] = true;
            ctas.push(text);
        }
    });
    
    return ctas.slice(0, 15);
}

/**
 * Builds complete AI context object with all page information
 * @param {object} options - Optional settings
 * @returns {object} Complete context object for AI
 */
function buildAIContext(options) {
    options = options || {};
    var settings = {
        maxContentChars: options.maxContentChars || 30000,
        includeScreenshot: options.includeScreenshot !== false,
        includeHeadlines: options.includeHeadlines !== false,
        includeCTAs: options.includeCTAs !== false
    };
    
    var context = {
        metadata: getPageMetadata(),
        content: getAbPageContentEnhanced(settings.maxContentChars)
    };
    
    if (settings.includeHeadlines) {
        context.headlines = getHeadlines();
    }
    
    if (settings.includeCTAs) {
        context.ctas = getCTAs();
    }
    
    if (settings.includeScreenshot && window.abaiScreenshot) {
        context.hasScreenshot = true;
    }
    
    context.stats = {
        contentLength: context.content.length,
        headlineCount: context.headlines ? context.headlines.length : 0,
        ctaCount: context.ctas ? context.ctas.length : 0,
        wasTruncated: context.content.indexOf('chars truncated') > -1
    };
    
    return context;
}

/**
 * Formats AI context into a string for the API
 * @param {object} context - Context object from buildAIContext()
 * @returns {string} Formatted context string
 */
function formatAIContext(context) {
    var output = '';
    
    // Page metadata section
    output += '<page_metadata>\n';
    output += 'URL: ' + context.metadata.url + '\n';
    output += 'Page Type: ' + context.metadata.pageType + '\n';
    output += 'Title: ' + context.metadata.title + '\n';
    if (context.metadata.h1) output += 'H1: ' + context.metadata.h1 + '\n';
    if (context.metadata.primaryCTA) output += 'Primary CTA: ' + context.metadata.primaryCTA + '\n';
    if (context.metadata.metaDescription) output += 'Meta Description: ' + context.metadata.metaDescription + '\n';
    output += 'Has Form: ' + (context.metadata.hasForm ? 'Yes' : 'No') + '\n';
    output += 'Word Count: ~' + context.metadata.wordCount + '\n';
    output += '</page_metadata>\n\n';
    
    // Headlines section
    if (context.headlines && context.headlines.length > 0) {
        output += '<page_headlines>\n';
        for (var i = 0; i < context.headlines.length; i++) {
            output += context.headlines[i].tag.toUpperCase() + ': ' + context.headlines[i].text + '\n';
        }
        output += '</page_headlines>\n\n';
    }
    
    // CTAs section
    if (context.ctas && context.ctas.length > 0) {
        output += '<page_ctas>\n';
        output += context.ctas.join(' | ') + '\n';
        output += '</page_ctas>\n\n';
    }
    
    // Main content (convert to markdown if TurndownService available)
    var contentMarkdown = context.content;
    if (typeof TurndownService !== 'undefined') {
        var turndownService = new TurndownService({
            headingStyle: 'atx',
            codeBlockStyle: 'fenced'
        });
        contentMarkdown = turndownService.turndown(context.content || '');
    }
    
    output += '<page_content>\n';
    output += contentMarkdown;
    output += '\n</page_content>';
    
    if (context.stats.wasTruncated) {
        output += '\n\n[Note: Page content was truncated to fit context limits]';
    }
    
    return output;
}

// Export to window for global access
window.abstAI = {
    // Context helpers
    getPageContent: getAbPageContentEnhanced,
    getPageMetadata: getPageMetadata,
    getHeadlines: getHeadlines,
    getCTAs: getCTAs,
    buildContext: buildAIContext,
    formatContext: formatAIContext,
    detectPageType: detectPageType,
    // CRO Chat
    croChat: {
        send: sendCroChatMessage,
        clear: clearCroChatHistory,
        getHistory: function() { return window.abstCroChat.history; }
    },
    // Full Page Optimize
    fullPageOptimize: {
        request: requestFullPageOptimize,
        apply: applyFullPageOptimization
    }
};

