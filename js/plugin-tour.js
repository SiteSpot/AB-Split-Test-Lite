var tour;

function abst_send_test_email() {
    //get value of #abst_weekly_report_emails
    var email = jQuery('#abst_weekly_report_emails').val();
    //send email to email
    jQuery.ajax({
        url: bt_adminurl + 'admin-ajax.php',
        type: 'POST',
        data: {
            action: 'abst_send_test_email',
            email: email
        },
        success: function(response) {
            alert(response.data);
        }
    });
}
jQuery(function ($) {
    //get url value wizard
    const urlParams = new URLSearchParams(window.location.search);
    const wizard = urlParams.get('wizard');    
    window.wiz = window.localStorage.wiz;
    if(wizard == 3) {
        wizard3();
    }

    if(window.wiz == '3' && urlParams.get('action') == 'edit' && urlParams.get('post')) 
        if (jQuery('#post-status-display').text().includes('Published'))
            wizard4();
    

    if(window.localStorage.wiz == 2) {
        wizard2();
    }


    
    jQuery('.start-tour').click(function() {
        wizard1();
    });

    jQuery('body').on('click', '.show_test_type.shepherd-target label', function() {
        var testType = jQuery(this).attr('for');
        console.log(testType);
        addTourSteps(testType);
        setTimeout(function(){
            Shepherd.activeTour.next();
        },500);
    });

    if(jQuery('.wp-heading-inline').text().includes('New AB Split Test')){
        jQuery('.wp-heading-inline').after('<button class="button button-small start-tour3" style="margin-top: 12px;">Show Tour</button>');
    }
    jQuery(document).on('click', '.start-tour3', function() {
         wizard3();
    });

    jQuery('#abst_enable_user_journeys').on('change', function() {
        if(jQuery('#abst_enable_user_journeys').is(':checked')) {
          jQuery('.ab-test-heatmap-pages, .ab-test-heatmap-retention').show();
        } else {
          jQuery('.ab-test-heatmap-pages, .ab-test-heatmap-retention').hide();
        }
      }).trigger('change');

    jQuery('#use_fingerprint').on('change', function() {
        if(jQuery('#use_fingerprint').is(':checked')) {
          jQuery('.ab-test-fingerprint-length').show();
        } else {
          jQuery('.ab-test-fingerprint-length').hide();
        }
      }).trigger('change');

      jQuery('#use_uuid').on('change', function() {
          if(jQuery('#use_uuid').is(':checked')) {
            jQuery('.ab-test-uuid-length').show();
          } else {
            jQuery('.ab-test-uuid-length').hide();
          }
        }).trigger('change');

        //#abst_server_convert_woo
        jQuery('#abst_server_convert_woo').on('change', function() {
            if (jQuery('#abst_server_convert_woo').is(':checked')) {
                jQuery('.ab-test-woo-goal-status').show();
            } else {
                jQuery('.ab-test-woo-goal-status').hide();
            }
        }).trigger('change');

        jQuery('body').on('click', '#remove_heatmap_data', function() {
            if(!confirm('Are you sure you want to remove all heatmap data?')) return;   
            
            jQuery.ajax({
                url: bt_adminurl + 'admin-ajax.php',
                type: 'POST',
                data: {
                    action: 'abst_remove_heatmap_data',
                    nonce: abstAgencyHubVars.clearHeatmapNonce
                },
                success: function(response) {
                    alert(response.data);
                }
            });
        });
});

function wizard1(){
    window.localStorage.wiz = 1;
    console.log('wiz1');
    jQuery('a[href="post-new.php?post_type=bt_experiments"]').attr('href', 'post-new.php?post_type=bt_experiments&wizard=3');
    tour = new Shepherd.Tour({
        defaultStepOptions: {
            cancelIcon: {
                enabled: true
            },
            classes: 'shadow-md bg-purple-dark',
            scrollTo: true
        }
    });

    tour.addStep({
        name: 'welcome',
        title: '3 minute test setup wizard.',
        text: "Let's get started with a quick setup to unlock all the features you need for success. Click 'Next' to start.",
        buttons: [
            {
                action: tour.next,
                text: 'Next'
            }
        ]
    });

    tour.addStep({
        title: 'Post Types',
        text: 'You can test on Pages and posts by default, but choose anything you want here.',
        attachTo: {
            element: '.ab-test-post-types',
            on: 'top'
        },
        buttons: [
            {
                action: tour.back,
                text: 'Back'
            },
            {
                action: tour.next,
                text: 'Next'
            }
        ]
    });

    if(jQuery('.ab-settings-open-ai').length > 0) {
        tour.addStep({
            title: 'Open AI',
            text: 'Want AI suggestions on how to improve your content? Enable Open AI Here.',
            attachTo: {
                element: '.ab-settings-open-ai',
                on: 'top'
            },
            buttons: [
                {
                    action: tour.back,
                    text: 'Back'
                },
                {
                    action: tour.next,
                    text: 'Next'
                }
            ]
        });
    }
    
    if(jQuery('.ab-settings-webhooks').length > 0) {
        tour.addStep({
            title: 'Webhooks',
            text: 'Send a webhook to your favourite automation tool when a test is created & complete.',
            attachTo: {
                element: '.ab-settings-webhooks',
                on: 'top'
            },
            buttons: [
                {
                    action: tour.back,
                    text: 'Back'
                },
                {
                    action: tour.next,
                    text: 'Next'
                }
            ]
        });
    }


    if(jQuery('.free-notice').length)
    {
        tour.addStep({
            title: 'Upgrade to Pro',
            text: 'You are more likely to create a winning test if you test more variations. Upgrade to AB Split Test Pro for unlimited tests with unlimited variations.',
            attachTo: {
                element: '.free-notice',
                on: 'top'
            },
            buttons: [
                {
                    action: tour.back,
                    text: 'Back'
                },
                {
                    action: function() {
                        window.location.href = 'https://absplittest.com/pricing?ref=ug';
                    },
                    text: 'Upgrade'
                },
                {
                    action: tour.next,
                    text: 'Next'
                }
            ]
        });
    }
    tour.addStep({
        title: 'Save Settings',
        text: 'Remember to save your settings.',
        attachTo: {
            element: '[name="bt_save"]',
            on: 'bottom'
        },
        buttons: [
            {
                action: tour.back,
                text: 'Back'
            },
        ]
    });


    // Add more steps as needed

    tour.start();

    // catch form id bt-bb-ab-form submit
    jQuery('body').on('submit', 'form#bt-bb-ab-form', function(e) {
        window.localStorage.wiz = 2; // set it to promt user to create test
    });

    tour.on('complete', function() {
        window.localStorage.wiz = false;
        console.log('complete');
    })
    tour.on('cancel', function() {
        window.localStorage.wiz = false;
        console.log('cancel');
    })

}

function wizard2(){
    console.log('wiz2');
    tour = new Shepherd.Tour({
        defaultStepOptions: {
            cancelIcon: {
                enabled: true
            },
            classes: 'shadow-md bg-purple-dark',
            scrollTo: true
        }
    });
    tour.addStep({
        title: 'Settings Updated!',
        text: 'Great, now its time to create your first test!',
        buttons: [
            {
                action: function() {
                    window.location.href = 'post-new.php?post_type=bt_experiments&wizard=3';
                },
                text: 'Create A Split Test'
            }
        ]
    });
    tour.start();
    tour.on('complete', function() {
        window.localStorage.wiz = false;
        console.log('complete');
    });
    tour.on('cancel', function() {
        window.localStorage.wiz = false;
        console.log('cancel');
    });

    

}

function wizard3(){
    window.localStorage.wiz = 3;
    const tour = new Shepherd.Tour({
        defaultStepOptions: {
            cancelIcon: {
                enabled: true
            },
            classes: 'shadow-md bg-purple-dark',
            scrollTo: {behavior: 'smooth', block: 'center'}
        }
    });
    tour.addStep({
        title: 'Welcome!',
        text: 'Lets get your first test started in 2 minutes.',
        attachTo: {
            element: 'h1',
            on: 'bottom'
        },
        buttons: [
            {
                action: tour.next,
                text: 'Get Started'
            }
        ]
    });
    tour.addStep({
        title: 'Need ideas?',
        text: 'The built in AI analysis can help you find ideas for your tests.',
        attachTo: {
            element: 'h1',
            on: 'bottom'
        },
        buttons: [
            {
                action: tour.next,
                text: 'I know what to test'
            },
            {
                action: function() {
                    // open https://absplittest.com/ai/?website_url=absplittest.com in a new tab with the current domain home page
                    window.open(bt_adminurl + 'edit.php?post_type=bt_experiments&page=bt_bb_ab_insights');
                },
                text: 'Get ideas for ' + window.location.hostname
            }
        ]
    });
    tour.addStep({
        title: 'Name your test',
        text: 'This will be displayed in your test results so short and sweet is the way.',
        attachTo: {
            element: '#titlewrap',
            on: 'bottom'
        },
        buttons: [
            {
                action: tour.back,
                text: 'Back'
            },
            {
                action: tour.next,
                text: 'Next'
            }
        ]
    });

    tour.addStep({
        name: 'show_test_type',
        title: 'Choose Test Type',
        text: 'Test whole pages, or select elements on your page.<br> Choose a test type to continue.',
        attachTo: {
            element: '.show_test_type',
            on: 'top'
        },
        buttons: [
            {
                action: tour.back,
                text: 'Back'
            }
        ]
    });

    tour.start();
    setTimeout(function(){
        jQuery('[name="post_title"]').focus();
    }, 500);

    tour.on('cancel', function() {
        window.localStorage.wiz = false;
        console.log('cancel');
    });
    //set up listeners to trigger substeps
    // jquery on input[value=ab_test change
}

function addTourSteps(testType){
console.log('addTourSteps', testType);
tour = Shepherd.activeTour;
    if(testType == 'full_page'){
        tour.addStep({
            title: 'Choose Pages',
            text: "This is your existing page, or the page you will send traffic to. We will split the traffic between this page and the variations you choose in the next step.<br> Choose your starting page, then choose your test variation pages.",
            attachTo: {
                element: '.show_full_page_test',
                on: 'bottom'
            },
            buttons: [
                {
                    action: tour.next,
                    text: 'Next'
                }
            ]
        });
    }

    if(testType == 'ab_test'){
        tour.addStep({
            title: 'On Page Test Setup',
            text: "Swap out one or many on page elements in your page. We will split the traffic between these elements.",
            attachTo: {
                element: '.show_css_classes',
                on: 'top'
            },
            buttons: [
                {
                    action: tour.next,
                    text: 'Next'
                }
            ]
        });
    }


    if(testType == 'css_test'){
        tour.addStep({
            title: 'CSS Test Setup',
            text: "Choose how many variations you want, then grab the CSS selectors to use to modify your designs.",
            attachTo: {
                element: '.show_css_test',
                on: 'top'
            },
            buttons: [
                {
                    action: tour.next,
                    text: 'Next'
                }
            ]
        });
    }

    tour.addStep({
        title: 'Choose Conversion / Goal Type',
        text: "This is the thing we're trying to optimize.<br> Choose a page load, purchase, form submission or other goal.",
        attachTo: {
            element: '.bt_experiments_inner_custom_box',
            on: 'top'
        },
        buttons: [
            {
                action: tour.back,
                text: 'Back'
            },
            {
                action: tour.next,
                text: 'Next'
            }
        ]
    });
    tour.addStep({
        title: 'Who do you want to test on?',
        text: 'Choose to test on logged in users, subscribers, or logged out users.<BR> We recommend testing on logged out users only.',
        attachTo: {
            element: '.ab-targeting-roles',
            on: 'bottom'
        },
        buttons: [
            {
                action: tour.back,
                text: 'Back'
            },
            {
                action: tour.next,
                text: 'Next'
            }
        ]
    });
    tour.addStep({
        title: 'Traffic Allocation.',
        text: 'Choose what percentage of your targeted users you will test on. <BR> We recommend testing on 100% of your traffic unless you have a lof of website visitors.',
        attachTo: {
            element: '.ab-target-percentage',
            on: 'bottom'
        },
        buttons: [
            {
                action: tour.back,
                text: 'Back'
            },
            {
                action: tour.next,
                text: 'Next'
            }
        ]
    });

    if(jQuery('.show_autocomplete').length)
    {
        tour.addStep({
            title: 'Autocomplete',
            text: 'End the test as soon as a winner is found. All existing users will see the winning variation as soon as possible. <BR>Recommeded.',
            attachTo: {
                element: '.show_autocomplete',
                on: 'bottom'
            },
            buttons: [
                {
                    action: tour.back,
                    text: 'Back'
                },
                {
                    action: tour.next,
                    text: 'Next'
                }
            ]
        });
    }


    tour.addStep({
        title: 'Start Test.',
        text: 'Begin your test by clicking the Start Test Button.',
        attachTo: {
            element: '#publishing-action',
            on: 'bottom'
        },
        buttons: [
            {
                action: tour.back,
                text: 'Back'
            }
        ]
    });
}


//new ab test wizard after save
function wizard4(){
    // displayed when test has been created
    const tour = new Shepherd.Tour({
        defaultStepOptions: {
            cancelIcon: {
                enabled: true
            },
            classes: 'shadow-md bg-purple-dark',
            scrollTo: {behavior: 'smooth', block: 'center'}
        }
    });

    // todo if its on page, tell eme to go make those changesss
    tour.addStep({
        title: 'You did it!',
        text: 'Your test is now running!',
        buttons: [
            {
                action: tour.next,
                text: 'View Results'
            }
        ]
    });


    if(jQuery('.show_css_classes').is(':visible'))
    {
        tour.addStep({
            title: 'Go create your on page test.',
            text: 'Edit the page or template you want to test elements on. Create your variations, and tag them.',
            attachTo: {
                element: '#menu-pages',
                on: 'bottom'
            },
        });
    }















    tour.addStep({
        title: 'View results',
        text: 'When your results start flowing in, they will be displayed here.',
        attachTo: {
            element: '.ab-tab-results-button',
            on: 'bottom'
        },
        buttons: [
            {
                action: tour.next,
                text: 'Next'
            }
        ]
    });

    tour.addStep({
        title: 'View and update settings',
        text: 'You can change your targeting or reset your test here.',
        attachTo: {
            element: '.config-button',
            on: 'bottom'
        },
        buttons: [
            {
                action: tour.next,
                text: 'Next'
            }
        ]
    });
    tour.addStep({
        title: 'Pause or end your test',
        text: 'Change your test status depending on your needs.',
        attachTo: {
            element: '.misc-pub-post-status',
            on: 'bottom'
        },
        buttons: [
            {
                action: tour.next,
                text: 'Next'
            }
        ]
    });
    tour.addStep({
        title: 'Don\'t forget to save',
        text: 'After you make any changes, update your test settings.',
        attachTo: {
            element: '#starttest',
            on: 'bottom'
        },
        buttons: [
            {
                action: tour.next,
                text: 'Finish Tour'
            }
        ]
    });
    tour.start();
    // Shepherd JS tour end event handler
    tour.on('cancel', function() {
        // Code to run when the tour is completed by the user
        console.log('The tour has been cancelled.');
        window.localStorage.wiz = false;
        // Additional actions to perform at the end of the tour can be added here
    });
    tour.on('complete', function() {
        // Code to run when the tour is completed by the user
        console.log('The tour has been completed.');
        window.localStorage.wiz = false;
        // Additional actions to perform at the end of the tour can be added here
    });


}


jQuery(function($){
    $(document).on('change','.ab-settings-subsection.ab-test-post-types.freelimit input[name="selected_post_types[]"]',function(){
      var $w=$(this).closest('.ab-settings-subsection.ab-test-post-types.freelimit'),
          $c=$w.find('input[name="selected_post_types[]"]:checked');
      if(this.checked && $c.length>2){
        $(this).prop('checked',false);
        if(!$w.find('.abst-freelimit-notice').length)
          $w.append('<div class="notice notice-warning abst-freelimit-notice"><p><strong>You can only test on 2 post types. </strong><a href="https://absplittest.com/pricing?ref=upgradefeaturelink" target="_blank">Upgrade</a>, or uncheck a post type to add more.</p></div>');
      }else if($c.length===1){
        $w.find('.abst-freelimit-notice').remove();
      }
    });
  });
  
  // heatmap settings handler
  jQuery(document).ready(function() {
    jQuery('body').on('change', '#heatmap_all_pages', function() {
        if (jQuery(this).val() === 'chosen') {
            jQuery('#heatmap_pages').parent().find('.select2-container').show();
        } else {
            jQuery('#heatmap_pages').parent().find('.select2-container').hide();
        }
    });
    jQuery('#heatmap_all_pages').trigger('change');




    jQuery('body').on('change', '#abst_heatmap_enable_user_journeys', function() {
      if (jQuery(this).is(':checked')) {
        jQuery('.ab-test-heatmap-pages,.ab-test-heatmap-retention').show();
      } else {
        jQuery('.ab-test-heatmap-pages,.ab-test-heatmap-retention').hide();
      }
    });
    jQuery('#abst_heatmap_enable_user_journeys').trigger('change');
});