
jQuery(document).ready(function(){
    // to create iframe in page builders and block editors and manage drupdoowns for them
    
        // if inside an iframe, then its a page builder preview and we dont want to do things.
        // check if the window is inside an iframe
        if (window.parent.document !== window.document) {
            console.log('in iframe');
            //uf url contains ?fl_builder
            if(window.location.href.indexOf('fl_builder') <1)
            {
                return;
            }
        }

        // sanitize variation titles before submitting to server
        function abstSanitizeVariation(value){
            return value.replace(/[^a-zA-Z0-9_\- ]/g,'');
        }

        function attachVariationSanitizer(doc){
            const selectors = [
                'input[name="bt_variation_name"]',
                'input[name="bt_variation"]',
                'input[data-setting="bt_variation"]',
                'input[data-controlkey="bt_variation"]',
                'input[data-controlkey="bt_variation_name"]'
            ].join(',');

            jQuery(doc).on('input', selectors, function(){
                const sanitized = abstSanitizeVariation(this.value);
                if (sanitized !== this.value) {
                    this.value = sanitized;
                    if(!jQuery(this).next('.bt-variation-warning').length){
                        jQuery(this).after('<span class="bt-variation-warning" style="color:#d63638;font-size:12px;">Only letters, numbers, spaces, underscores and dashes are allowed.</span>');
                    }
                } else {
                    jQuery(this).next('.bt-variation-warning').remove();
                }
            });
        }

        attachVariationSanitizer(document);
        if(window.parent && window.parent !== window){
            attachVariationSanitizer(window.parent.document);
        }

        jQuery(window.parent.document.body).on("click", ".new-on-page-test-button", function(e){
                
            e.preventDefault();
            console.log('clicked');
            var testName = '';
            jQuery('.newabpanel').remove();
    
            if(jQuery(this).parents('#bricks-panel-element').length > 0) // if its bricks get its name
                testName = '&name=' + jQuery(this).parents('#bricks-panel-element').find('#bricks-panel-header input').val() + ' Test';
    
            if(jQuery(this).parents('#elementor-panel-inner').length > 0) // if its elementor get its name
                testName = '&name=' + jQuery(this).parents('#elementor-panel-inner').find('#elementor-panel-header-title').text().replace('Edit ','') + ' Test';
    
            if(jQuery(this).parents('.fl-builder-module-settings').length > 0) // if its bb get its name
                testName = '&name=' + jQuery(this).parents('.fl-builder-module-settings').find('.fl-lightbox-header h1').text() + ' Test';
            
            if(jQuery(this).parents('.block-editor-block-inspector').length > 0) // if its blocks get its name
                testName = '&name=' + jQuery(this).parents('.block-editor-block-inspector').find('h2.block-editor-block-card__title').text() + ' Test';
    
            //if its wp bakery  parent .vc_edit_form_elements
            if(jQuery(this).parents('.vc_edit_form_elements').length > 0) // if its wp bakery get its name
                testName = '&name=' + 'Row Test';
    
    
            //create close button that floats above the iframe
            var newabclose = document.createElement('button');
            newabclose.style.position = 'fixed';
            newabclose.style.top = 'calc(5vh - 20px)';
            newabclose.style.right = 'calc(50% - 230px)';
            newabclose.classList.add('newabpanel');
            newabclose.style.zIndex = '9999';
            newabclose.style.backgroundColor = '#b30202';
            newabclose.style.color = 'white';
            newabclose.style.border = 'none';
            newabclose.style.borderRadius = '10px 10px 0 0';
            newabclose.style.padding = '10px';
            newabclose.style.fontWeight = 'bold';
            newabclose.style.cursor = 'pointer';
            newabclose.style.boxShadow = '0 1px 10px -3px black';
            newabclose.innerHTML = 'CLOSE X';
            document.body.appendChild(newabclose);
    
            //CREATE IFRAME AS A MODAL POPOVER TAKING UP 600PX WIDE FULL HEIGHT
            var newabiframe = document.createElement('iframe');
            
            // Function to close and cleanup
            function closeNewAbPanel() {
                if (newabiframe && newabiframe.parentNode) {
                    newabiframe.parentNode.removeChild(newabiframe);
                }
                if (newabclose && newabclose.parentNode) {
                    newabclose.parentNode.removeChild(newabclose);
                }
                // Remove event listeners
                document.removeEventListener('keydown', escapeHandler);
                document.removeEventListener('click', clickOutsideHandler);
            }
            
            // Event handlers
            function escapeHandler(e) {
                if (e.key == 'Escape') {
                    closeNewAbPanel();
                }
            }
            
            function clickOutsideHandler(e) {
                if (e.target == document.body) {
                    closeNewAbPanel();
                }
            }

            newabclose.addEventListener('click', closeNewAbPanel);
    
            // close on escape key
            document.addEventListener('keydown', escapeHandler);
    
            //close on click outside the iframe on parent body
            document.addEventListener('click', clickOutsideHandler);
    
            // generate source
            const wp_ajax_on_page_test_create = window.ajaxurl 
                ? `${window.ajaxurl}?action=on_page_test_create` 
                : `${window.location.origin}/wp-admin/admin-ajax.php?action=on_page_test_create`;
    
            newabiframe.classList.add('newabpanel');
            document.body.appendChild(newabiframe);
            newabiframe.style.display = 'block';
            newabiframe.src = wp_ajax_on_page_test_create + testName;
    
            //POPUP THE IFRAME
            newabiframe.contentWindow.focus();
            newabiframe.contentWindow.open();
            newabiframe.contentWindow.moveTo(0, 0);
            newabiframe.contentWindow.resizeTo(screen.width, screen.height);
        });
    
        //catch postmessage from iframe then update inputs and hide iframe
    //    data array keys id and name
        window.addEventListener('message', function(e){
    
            var data = e.data;
    
            if(data.id && data.name && jQuery('.newabpanel').length > 0){
    
                // add to blocks dropdown - check if we are in Gutenberg context and have a selected block
                if (typeof wp !== 'undefined' && wp.data && wp.data.dispatch && wp.data.select('core/block-editor')) {
                    const selectedBlockId = wp.data.select('core/block-editor').getSelectedBlockClientId();
                    if (selectedBlockId) {
                        wp.data.dispatch('core/block-editor').updateBlockAttributes( selectedBlockId, { 
                            'bt-eid': data.id,
                            'bt-variation': 'default'
                        } );
                    }
                }
    
                //add to bricks bricks bricks bricks bricks bricks bricks bricks bricks bricks bricks bricks bricks bricks bricks
                //if bricks save and reload page
                if(jQuery('[data-controlkey="bt_experiment"]').length > 0)
                {

                    Object.values(bricksData.elements).forEach(element => {
                        if (
                            element.controls &&
                            element.controls.bt_experiment &&
                            element.controls.bt_experiment.options
                        ) {
                            // Add your option (example: using test name as both id and label)
                            element.controls.bt_experiment.options[data.id] = data.name
                        }
                    });

                    //current element
                    var currentBrickElement = jQuery('#bricks-structure .bricks-draggable-item.active');
                    //click first not active
                    jQuery('#bricks-structure .bricks-draggable-item:not(".active")').first().find('.structure-item').first().click()
                    //click currentBrickElement
                    setTimeout(function () {
                        currentBrickElement.first().find('.structure-item').first().click()
                        //load dropdown
                        //setTimeout(function () {
                          //  jQuery('[data-controlkey="bt_experiment"] .input').click()
                         //   console.log('clicked dropdown');

                            //click last li
                            setTimeout(function () {
                                jQuery('[data-controlkey="bt_experiment"] .dropdown li').last().click();
                                //if empty set to original
                                console.log('clicked added and clicked bricks dropdown element');
                            }, 100);
                      //  }, 100);
                    }, 100);

                }
           
                //elementor
                var newOption = new Option(data.name, data.id, true, true);
                if(jQuery('.elementor-select2[data-setting="bt_experiment"]').length > 0)
                {
                    console.log('ABST adding new test to elementor list ' + data.name + ' to options');
                    jQuery('.elementor-select2[data-setting="bt_experiment"]').append(newOption).trigger('change');
                    this.setInterval(function(){
                        if((jQuery('.elementor-select2[data-setting="bt_experiment"]').length > 0) && jQuery('.elementor-select2[data-setting="bt_experiment"] option[value="' + data.id + '"]').length == 0) /// if the option doesnt exist
                        {
                            var newOption = new Option(data.name, data.id, false, false);
                            jQuery('.elementor-select2[data-setting="bt_experiment"]').prepend(newOption).trigger('change');
                            console.log('ABST re added new test option: ' + data.name);
                        }
                    },1000)
                }
    
                // bb gotta type in
                jQuery('#ab_test').val('' + data.name + '').trigger('focus');
    
                // if wpbakery is visible
                if(jQuery('[data-vc-shortcode-param-name="test_id"] .vc_auto_complete_param').length > 0)
                {
                    jQuery('[data-vc-shortcode-param-name="test_id"] .vc_autocomplete-remove').click()
                    jQuery('[data-vc-shortcode-param-name="test_id"] .vc_auto_complete_param').val(data.name).trigger('input');
                    this.setTimeout(function(){
                        if(jQuery('[data-vc-shortcode-param-name="test_id"] .vc_auto_complete_param').length > 0)
                        {
                            jQuery('.vc_autocomplete-item').first().trigger('click');
                        }
                    },2000);
                }
    
                //window closing in 1 second
                this.setTimeout(function(){
                    jQuery('.newabpanel').remove();
                },1500);
            }
    
            if (data == 'abclosemodal'){
                console.log('ABST close modal');
                jQuery('.newabpanel').remove();
            //    jQuery('.newabpanel',window.iframe).remove();//bricks in iframe
            }
    
        }, false);
        
    });