(function($) {
    'use strict';

    var magicTour = null;
    var elementSelected = false;
    var selectionObserver = null;
    var paused = false;
    // Set while the tour is ended on purpose (restart), so closing it doesn't count as "don't show again".
    var quietEnd = false;

    function driverLoaded() {
        return window.driver && window.driver.js && typeof window.driver.js.driver === 'function';
    }

    function initMagicTour() {
        // Check if tour=1 parameter is present to force show tour
        var urlParams = new URLSearchParams(window.location.search);
        var forceTour = urlParams.get('tour') === '1';

        if (!forceTour && window.localStorage.getItem('abst_magic_tour_dismissed') === 'true') {
            return;
        }

        if (!driverLoaded()) {
            console.log('Driver.js not loaded, skipping magic tour');
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

    /**
     * Driver.js makes everything outside the highlighted element unclickable while a
     * tour runs (the driver-active body class). The first step needs the whole page,
     * so it shows its popover without the overlay and leaves the page clickable.
     */
    function applyStepMode(step) {
        var passThrough = !!(step && step.data && step.data.passThrough);
        document.body.classList.toggle('abst-tour-passthrough', passThrough);
        document.body.classList.toggle('driver-active', !passThrough && !paused);
    }

    function visibleElement(selector) {
        return function() {
            var el = document.querySelector(selector);
            return el && el.getClientRects().length ? el : null;
        };
    }

    function startMagicTour() {
        if (magicTour && magicTour.isActive()) {
            return;
        }
        elementSelected = false;
        magicTour = window.driver.js.driver({
            popoverClass: 'abst-magic-tour',
            overlayOpacity: 0.5,
            stagePadding: 4,
            prevBtnText: 'Back',
            nextBtnText: 'Next',
            doneBtnText: 'Finish',
            // Arrow keys would change the step while someone types a variation.
            allowKeyboardControl: false,
            // A stray click beside the bar shouldn't end the tour; close, Skip or Escape do.
            overlayClickBehavior: 'none',
            onHighlightStarted: function(element, step) {
                applyStepMode(step);
            },
            onDestroyed: function(element, step) {
                var name = step && step.data ? step.data.name : '';
                document.body.classList.remove('abst-magic-touring', 'abst-tour-passthrough', 'abst-tour-hidden');
                paused = false;
                cleanupSelectionListener();
                // Closing the tour on its first step means "don't show it again".
                if (name === 'magic-welcome' && !quietEnd) {
                    dismissMagicTour();
                }
            },
            steps: [
                // Step 1: Prompt user to click an element on the page
                {
                    element: visibleElement('.click-to-start-help'),
                    data: { name: 'magic-welcome', passThrough: true },
                    popover: {
                        title: 'Create a Test',
                        description: '<strong>Click on any element on the page to begin</strong> your test. Try a headline, button, or image. An orange box will appear around the element to help you identify it.',
                        side: 'left',
                        align: 'start',
                        showButtons: ['next', 'close'],
                        doneBtnText: 'Don\'t Show Again',
                        onPopoverRender: function(popover) {
                            popover.nextButton.classList.add('abst-tour-secondary');
                        },
                        onNextClick: function() {
                            magicTour.destroy();
                        }
                    }
                }
            ]
        });

        document.body.classList.add('abst-magic-touring');
        magicTour.drive();
        setupElementSelectionListener();
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
        if (!magicTour || !magicTour.isActive()) {
            return;
        }

        var steps = (magicTour.getConfig('steps') || []).slice(0, 1).concat([
            // Step 2: Variation Editor
            {
                element: '#variation-editor-container',
                data: { name: 'magic-editor' },
                popover: {
                    title: 'Edit Your Variation',
                    description: 'Change the text here to create your test variation. This is what visitors will see instead of the original.',
                    side: 'left',
                    align: 'start',
                    showButtons: ['previous', 'next', 'close'],
                    prevBtnText: 'Skip Tour',
                    onPrevClick: function() {
                        magicTour.destroy();
                    }
                }
            },
            // Step 3: Variation Toggle
            {
                element: '#version-value',
                data: { name: 'magic-toggle' },
                popover: {
                    title: 'Switch Variations',
                    description: 'Click here to switch between the original and your variation to preview both versions.',
                    side: 'left',
                    align: 'start'
                }
            },
            // Step 4: Goals
            {
                element: '.abst-goals-column',
                data: { name: 'magic-goals' },
                popover: {
                    title: 'Set Your Goal',
                    description: 'Choose how a conversion is counted: a <strong>page visit</strong> (visitors reach a page such as a thank-you page) or an <strong>element click</strong> (visitors click an element such as a buy button).',
                    side: 'left',
                    align: 'start'
                }
            },
            // Step 5: Start Test
            {
                element: '.abst-magic-bar-footer',
                data: { name: 'magic-start' },
                popover: {
                    title: 'Start Your Test',
                    description: 'Click "Start Test" to begin your A/B test, or "Save Draft" to save it for later. After saving, you can share preview links for each variation.',
                    side: 'left',
                    align: 'start'
                }
            }
        ]);

        magicTour.setConfig($.extend({}, magicTour.getConfig(), { steps: steps }));

        // Advance from welcome step to the newly added editor step
        magicTour.moveNext();
    }

    function dismissMagicTour() {
        window.localStorage.setItem('abst_magic_tour_dismissed', 'true');
        cleanupSelectionListener();
    }

    function restartTour() {
        // Clear the dismissed flag
        window.localStorage.removeItem('abst_magic_tour_dismissed');
        // End any existing tour
        if (magicTour && magicTour.isActive()) {
            quietEnd = true;
            magicTour.destroy();
            quietEnd = false;
        }
        // Reset state
        elementSelected = false;
        cleanupSelectionListener();
        // Start fresh
        initMagicTour();
    }

    /**
     * Hide the tour while another dialog (the media library) is open, and let that
     * dialog be clicked; resume puts the tour back on the same step.
     */
    function pauseTour() {
        if (!magicTour || !magicTour.isActive()) {
            return;
        }
        paused = true;
        document.body.classList.add('abst-tour-hidden');
        document.body.classList.remove('driver-active');
    }

    function resumeTour() {
        if (!paused) {
            return;
        }
        paused = false;
        document.body.classList.remove('abst-tour-hidden');
        if (magicTour && magicTour.isActive()) {
            applyStepMode(magicTour.getActiveStep());
            magicTour.refresh();
        }
    }

    // Expose restartTour globally
    window.restartMagicTour = restartTour;
    window.abstMagicTourPause = pauseTour;
    window.abstMagicTourResume = resumeTour;

    // Add event listener for Show Tour button
    $(document).on('click', '#abst-magic-bar-show-tour', function() {
        restartTour();
    });

    // Escape closes the tour, except while typing in a field or the variation editor.
    $(document).on('keyup', function(e) {
        if (e.key !== 'Escape' || !magicTour || !magicTour.isActive() || paused) {
            return;
        }
        if ($(e.target).is('input, textarea, select, [contenteditable], [contenteditable] *')) {
            return;
        }
        magicTour.destroy();
    });

    // Start when DOM is ready
    $(function() {
        // Only run if ?abmagic is in the URL
        if (window.location.search.includes('abmagic')) {
            initMagicTour();
        }
    });

})(jQuery);
