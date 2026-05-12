(function($) {
    'use strict';

    var magicTour = null;
    var elementSelected = false;
    var selectionObserver = null;

    function initMagicTour() {
        // Check if tour=1 parameter is present to force show tour
        var urlParams = new URLSearchParams(window.location.search);
        var forceTour = urlParams.get('tour') === '1';

        if (!forceTour && window.localStorage.getItem('abst_magic_tour_dismissed') === 'true') {
            return;
        }

        if (typeof Shepherd === 'undefined') {
            console.log('Shepherd not loaded, skipping magic tour');
            return;
        }

        // Wait for the magic bar to exist before starting
        waitForMagicBar(function() {
            startMagicTour();
        });
    }

    function waitForMagicBar(callback) {
        var attempts = 0;
        var maxAttempts = 100;
        var interval = setInterval(function() {
            attempts++;
            if (document.getElementById('abst-magic-bar')) {
                clearInterval(interval);
                callback();
            } else if (attempts >= maxAttempts) {
                clearInterval(interval);
            }
        }, 200);
    }

    function startMagicTour() {
        magicTour = new Shepherd.Tour({
            defaultStepOptions: {
                cancelIcon: {
                    enabled: true
                },
                classes: 'shadow-md bg-purple-dark',
                scrollTo: true
            },
            useModalOverlay: true
        });

        // Step 1: Prompt user to click an element on the page
        magicTour.addStep({
            name: 'magic-welcome',
            title: 'Create a Test',
            text: '<strong>Click on any element on the page to begin</strong> your test. Try a headline, button, or image. An orange box will appear around the element to help you identify it.',
            attachTo: {
                element: 'body',
                on: 'bottom'
            },
            buttons: [
                {
                    action: function() {
                        dismissMagicTour();
                        return magicTour.cancel();
                    },
                    text: 'Don\'t Show Again',
                    classes: 'shepherd-button-secondary'
                }
            ],
            modalOverlayOpeningPadding: 0,
            when: {
                show: function() {
                    setupElementSelectionListener();
                },
                cancel: function() {
                    dismissMagicTour();
                }
            }
        });

        magicTour.start();

        magicTour.on('cancel', function() {
            cleanupSelectionListener();
        });
    }

    function setupElementSelectionListener() {
        // Watch for the click-to-start-help element being hidden (indicates element selected)
        var helpEl = document.querySelector('.click-to-start-help');
        if (helpEl) {
            selectionObserver = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    if (mutation.type === 'attributes' && mutation.attributeName === 'style') {
                        var display = $(helpEl).css('display');
                        if (display === 'none' && !elementSelected) {
                            elementSelected = true;
                            cleanupSelectionListener();
                            showEditorTourSteps();
                        }
                    }
                });
            });
            selectionObserver.observe(helpEl, { attributes: true, attributeFilter: ['style'] });
        }

        // Fallback: also watch for #variation-editor-container becoming visible
        var editorEl = document.getElementById('variation-editor-container');
        if (editorEl) {
            var editorObserver = new MutationObserver(function(mutations) {
                if ($(editorEl).is(':visible') && !elementSelected) {
                    elementSelected = true;
                    cleanupSelectionListener();
                    showEditorTourSteps();
                }
            });
            editorObserver.observe(editorEl, { attributes: true, attributeFilter: ['style'] });
            window._magicTourEditorObserver = editorObserver;
        }

        // Also listen for selector input change as another signal
        $(document).on('blur.abstMagicTour', '#abst-selector-input', function() {
            var val = $(this).val();
            if (val && val !== 'Select an item' && val !== 'Select an item to start testing' && !elementSelected) {
                elementSelected = true;
                cleanupSelectionListener();
                // Small delay to let UI settle
                setTimeout(showEditorTourSteps, 300);
            }
        });
    }

    function cleanupSelectionListener() {
        if (selectionObserver) {
            selectionObserver.disconnect();
            selectionObserver = null;
        }
        if (window._magicTourEditorObserver) {
            window._magicTourEditorObserver.disconnect();
            window._magicTourEditorObserver = null;
        }
        $(document).off('blur.abstMagicTour', '#abst-selector-input');
    }

    function showEditorTourSteps() {
        if (!magicTour) {
            return;
        }

        // Step 2: Variation Editor
        magicTour.addStep({
            name: 'magic-editor',
            title: 'Edit Your Variation',
            text: 'Change the text here to create your test variation. This is what visitors will see instead of the original.',
            attachTo: {
                element: '#variation-editor-container',
                on: 'left'
            },
            scrollTo: false,
            useModalOverlay: false,
            buttons: [
                {
                    action: function() {
                        return magicTour.cancel();
                    },
                    text: 'Skip Tour',
                    classes: 'shepherd-button-secondary'
                },
                {
                    action: magicTour.next,
                    text: 'Next'
                }
            ]
        });

        // Step 3: Variation Toggle
        magicTour.addStep({
            name: 'magic-toggle',
            title: 'Switch Variations',
            text: 'Click here to toggle between your original and your new variation to preview both versions.',
            attachTo: {
                element: '#version-value',
                on: 'left'
            },
            useModalOverlay: true,
            buttons: [
                {
                    action: magicTour.back,
                    text: 'Back'
                },
                {
                    action: magicTour.next,
                    text: 'Next'
                }
            ]
        });

        // Step 4: Goals
        magicTour.addStep({
            name: 'magic-goals',
            title: 'Set Your Goal',
            text: 'Choose what you want to improve. This could be a purchase, form submission, page visit, or button click.',
            attachTo: {
                element: '.abst-goals-column',
                on: 'left'
            },
            buttons: [
                {
                    action: magicTour.back,
                    text: 'Back'
                },
                {
                    action: magicTour.next,
                    text: 'Next'
                }
            ]
        });

        // Step 5: Start Test
        magicTour.addStep({
            name: 'magic-start',
            title: 'Start Your Test',
            text: 'Click "Start Test" to begin your A/B test, or "Save Draft" to save it for later. After saving, you can share preview links for each variation.',
            attachTo: {
                element: '.abst-magic-bar-footer',
                on: 'left'
            },
            buttons: [
                {
                    action: magicTour.back,
                    text: 'Back'
                },
                {
                    action: function() {
                        return magicTour.complete();
                    },
                    text: 'Finish'
                }
            ]
        });

        // Advance from welcome step to the newly added editor step
        magicTour.next();
    }

    function dismissMagicTour() {
        window.localStorage.setItem('abst_magic_tour_dismissed', 'true');
        cleanupSelectionListener();
    }

    function restartTour() {
        // Clear the dismissed flag
        window.localStorage.removeItem('abst_magic_tour_dismissed');
        // Cancel any existing tour
        if (magicTour) {
            magicTour.cancel();
        }
        // Reset state
        elementSelected = false;
        cleanupSelectionListener();
        // Start fresh
        initMagicTour();
    }

    // Expose restartTour globally
    window.restartMagicTour = restartTour;

    // Add event listener for Show Tour button
    $(document).on('click', '#abst-magic-bar-show-tour', function() {
        restartTour();
    });

    // Start when DOM is ready
    $(function() {
        // Only run if ?abmagic is in the URL
        if (window.location.search.includes('abmagic')) {
            initMagicTour();
        }
    });

})(jQuery);
