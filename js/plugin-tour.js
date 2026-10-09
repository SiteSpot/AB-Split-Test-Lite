var tour;

jQuery(function ($) {
    //get url value wizard
    const urlParams = new URLSearchParams(window.location.search);
    const wizard = urlParams.get('wizard');
    window.wiz = window.localStorage.wiz;
    if(wizard == 3) {
        wizard3();
    }

    if(window.wiz == '3' && urlParams.get('action') == 'edit' && urlParams.get('post'))
        // experiment.js may already have renamed "Published" to "Test Running".
        if (/Published|Test Running/.test(jQuery('#post-status-display').text()))
            wizard4();


    if(window.localStorage.wiz == 2) {
        wizard2();
    }



    jQuery('.start-tour').click(function() {
        wizard1();
    });

    // Choosing a test type on the "Choose Test Type" step adds the steps for that type.
    jQuery('body').on('click', '.show_test_type.driver-active-element label', function() {
        var testType = jQuery(this).attr('for');
        addTourSteps(testType);
        setTimeout(function(){
            if (tour && tour.isActive()) {
                tour.moveNext();
            }
        },500);
    });

    // Escape closes the tour, except while typing in a field.
    jQuery(document).on('keyup', function(e) {
        if (e.key !== 'Escape' || !tour || !tour.isActive()) {
            return;
        }
        if (jQuery(e.target).is('input, textarea, select, [contenteditable="true"]')) {
            return;
        }
        tour.destroy();
    });

    // New test screen
    if(jQuery('body').hasClass('post-new-php') && jQuery('body').hasClass('post-type-abst_experiments')){
        jQuery('.wp-heading-inline').after('<button class="button button-small start-tour3" style="margin-top: 12px;">Show Tour</button>');
    }
    jQuery(document).on('click', '.start-tour3', function() {
         wizard3();
    });

        jQuery('body').on('click', '#remove_heatmap_data', function() {
            if(!confirm('Are you sure you want to remove all heatmap data?')) return;

            jQuery.ajax({
                url: abstTourVars.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'abst_remove_heatmap_data',
                    nonce: abstTourVars.clearHeatmapNonce
                },
                success: function(response) {
                    alert(response.data);
                }
            });
        });
});

/**
 * One tour step. buttons lists the footer buttons it shows ('next', 'previous');
 * the close button is always there. A step without an element, or whose element
 * is hidden (e.g. on another settings tab), is centered.
 */
function abstTourStep(element, side, title, text, buttons, popover) {
    var step = {
        popover: jQuery.extend({
            title: title,
            description: text,
            side: side || 'bottom',
            align: 'start',
            showButtons: (buttons || ['next']).concat(['close'])
        }, popover || {})
    };
    if (element) {
        step.element = function() {
            var el = document.querySelector(element);
            return el && el.getClientRects().length ? el : null;
        };
    }
    return step;
}

/** Start a tour with Driver.js (js/driver.min.js). Ending it any way clears the wizard. */
function abstStartTour(steps) {
    if (!window.driver || !window.driver.js || typeof window.driver.js.driver !== 'function') {
        return null;
    }
    if (tour && tour.isActive()) {
        var previous = tour;
        tour = null;
        previous.destroy();
    }
    var instance = window.driver.js.driver({
        steps: steps,
        popoverClass: 'abst-tour',
        prevBtnText: 'Back',
        nextBtnText: 'Next',
        doneBtnText: 'Finish',
        overlayOpacity: 0.5,
        stagePadding: 6,
        smoothScroll: true,
        // Arrow keys would change the step while someone types in a highlighted field.
        allowKeyboardControl: false,
        onDestroyed: function() {
            // A tour replaced by another one leaves the wizard state to the new tour.
            if (tour === instance) {
                window.localStorage.wiz = false;
            }
        }
    });
    tour = instance;
    tour.drive();
    return tour;
}

function wizard1(){
    window.localStorage.wiz = 1;
    jQuery('a[href="post-new.php?post_type=abst_experiments"]').attr('href', 'post-new.php?post_type=abst_experiments&wizard=3');

    abstStartTour([
        abstTourStep(null, null, '3 minute test setup wizard.', "Let's get started with a quick setup of the settings you need. Click 'Next' to start.", ['next']),
        abstTourStep('.ab-test-post-types', 'top', 'Post Types', 'You can test on Pages and posts by default, but choose anything you want here.', ['previous', 'next']),
        abstTourStep('[name="bt_save"]', 'bottom', 'Save Settings', 'Remember to save your settings.', ['previous'])
    ]);

    // Saving the settings prompts the user to create a test on the next page.
    jQuery('body').off('submit.abstTour').on('submit.abstTour', 'form#bt-bb-ab-form', function() {
        window.localStorage.wiz = 2;
    });
}

function wizard2(){
    abstStartTour([
        abstTourStep(null, null, 'Settings Updated!', 'Great, now its time to create your first test!', ['next'], {
            doneBtnText: 'Create A Split Test',
            onNextClick: function() {
                window.location.href = 'post-new.php?post_type=abst_experiments&wizard=3';
            }
        })
    ]);
}

function wizard3(){
    window.localStorage.wiz = 3;
    var nameStep = abstTourStep('#titlewrap', 'bottom', 'Name your test', 'This will be displayed in your test results so short and sweet is the way.', ['previous', 'next']);
    // The popover takes focus when it opens; hand it to the title field.
    nameStep.onHighlighted = function() {
        jQuery('[name="post_title"]').focus();
    };
    abstStartTour([
        abstTourStep('h1', 'bottom', 'Welcome!', 'Lets get your first test started in 2 minutes.', ['next'], { nextBtnText: 'Get Started' }),
        nameStep,
        // No Next button: choosing a test type adds that type's steps and moves on.
        abstTourStep('.show_test_type', 'top', 'Choose Test Type', 'Test whole pages, or select elements on your page.<br> Choose a test type to continue.', ['previous'])
    ]);
    setTimeout(function(){
        jQuery('[name="post_title"]').focus();
    }, 500);
}

function addTourSteps(testType){
    if (!tour || !tour.isActive()) {
        return;
    }
    // Replace anything added for an earlier choice.
    var steps = (tour.getConfig('steps') || []).slice(0, (tour.getActiveIndex() || 0) + 1);

    if(testType == 'full_page'){
        steps.push(abstTourStep('.show_full_page_test', 'bottom', 'Choose Pages', "This is your existing page, or the page you will send traffic to. We will split the traffic evenly between this page and the variation pages you choose next.<br> Choose your starting page, then choose one or more variation pages.", ['next']));
    }

    if(testType == 'ab_test'){
        steps.push(abstTourStep('.show_css_classes', 'top', 'On Page Test Setup', "Swap out one or many on page elements in your page. We will split the traffic between the original and your variation.", ['next']));
    }

    steps.push(
        abstTourStep('.bt_experiments_inner_custom_box', 'top', 'Choose Conversion / Goal Type', "This is the thing we're trying to optimize.<br> Choose a page visit (visitors reach a page such as a thank-you page) or an element click (visitors click an element such as a buy button).", ['previous', 'next']),
        abstTourStep('.ab-targeting-roles', 'bottom', 'Who do you want to test on?', 'Choose to test on logged in users, subscribers, or logged out users.<BR> We recommend testing on logged out users only.', ['previous', 'next']),
        abstTourStep('.ab-target-percentage', 'bottom', 'Traffic Allocation.', 'Choose what percentage of your targeted users you will test on. <BR> We recommend testing on 100% of your traffic unless you have a lof of website visitors.', ['previous', 'next']),
        abstTourStep('#publishing-action', 'bottom', 'Start Test.', 'Begin your test by clicking the Start Test Button.', ['previous'])
    );

    tour.setConfig(jQuery.extend({}, tour.getConfig(), { steps: steps }));
}


//new ab test wizard after save
function wizard4(){
    // displayed when test has been created
    var steps = [
        abstTourStep(null, null, 'You did it!', 'Your test is now running!', ['next'], { nextBtnText: 'View Results' })
    ];

    if(jQuery('.show_css_classes').is(':visible'))
    {
        steps.push(abstTourStep('#menu-pages', 'right', 'Go create your on page test.', 'Edit the page or template you want to test elements on. Create your variations, and tag them.', ['next']));
    }

    steps.push(
        abstTourStep('.ab-tab-results-button', 'bottom', 'View results', 'When your results start flowing in, they will be displayed here.', ['next']),
        abstTourStep('.config-button', 'bottom', 'View and update settings', 'You can change your targeting or reset your test here.', ['next']),
        abstTourStep('.misc-pub-post-status', 'bottom', 'Pause or end your test', 'Change your test status depending on your needs.', ['next']),
        abstTourStep('#starttest', 'bottom', 'Don\'t forget to save', 'After you make any changes, update your test settings.', ['next'], { doneBtnText: 'Finish Tour' })
    );

    abstStartTour(steps);
}
