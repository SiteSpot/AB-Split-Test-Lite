<?php
/**
 * Form Submission Conversion Events
 * 
 * Adds form submission as a conversion/goal event type.
 * Supports: Fluent Forms, Contact Form 7, WPForms, Gravity Forms,
 * Ninja Forms, Formidable Forms, Forminator, Elementor Pro Forms,
 * JetFormBuilder, MetForm, MW WP Form, SureForms, FormCraft,
 * MailPoet Forms
 * 
 * @package ABSPLITTEST
 * @since 1.9.0
 */

defined('ABSPATH') || exit;

/**
 * Main class for form conversion handling
 */
class ABST_Form_Conversions {

    /**
     * Singleton instance
     */
    private static $instance = null;

    /**
     * Supported form providers with their detection and configuration
     */
    private $providers = [];

    /**
     * Get singleton instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor - register hooks
     */
    private function __construct() {
        $this->init_providers();
        $this->register_form_hooks();
    }

    /**
     * Initialize provider configurations
     */
    private function init_providers() {
        $this->providers = [
            'fluentform' => [
                'label' => 'Fluent Forms',
                'detect' => function() { return defined('FLUENTFORM') || class_exists('FluentForm\App\Models\Form'); },
                'get_forms' => [$this, 'get_fluentform_forms'],
            ],
            'cf7' => [
                'label' => 'Contact Form 7',
                'detect' => function() { return class_exists('WPCF7') || class_exists('WPCF7_ContactForm'); },
                'get_forms' => [$this, 'get_cf7_forms'],
            ],
            'wpforms' => [
                'label' => 'WPForms',
                'detect' => function() { return function_exists('wpforms') || class_exists('WPForms'); },
                'get_forms' => [$this, 'get_wpforms_forms'],
            ],
            'gravity' => [
                'label' => 'Gravity Forms',
                'detect' => function() { return class_exists('GFForms') || class_exists('GFAPI'); },
                'get_forms' => [$this, 'get_gravity_forms'],
            ],
            'ninjaforms' => [
                'label' => 'Ninja Forms',
                'detect' => function() { return function_exists('Ninja_Forms') || class_exists('Ninja_Forms'); },
                'get_forms' => [$this, 'get_ninja_forms'],
            ],
            'formidable' => [
                'label' => 'Formidable Forms',
                'detect' => function() { return class_exists('FrmForm'); },
                'get_forms' => [$this, 'get_formidable_forms'],
            ],
            'forminator' => [
                'label' => 'Forminator',
                'detect' => function() { return class_exists('Forminator') || class_exists('Forminator_API'); },
                'get_forms' => [$this, 'get_forminator_forms'],
            ],
            'elementor' => [
                'label' => 'Elementor Pro Forms',
                'detect' => function() { return did_action('elementor_pro/init') || class_exists('ElementorPro\Modules\Forms\Module'); },
                'get_forms' => [$this, 'get_elementor_forms'],
            ],
            'jetformbuilder' => [
                'label' => 'JetFormBuilder',
                'detect' => function() { return class_exists('Jet_Form_Builder\Plugin') || defined('JET_FORM_BUILDER_VERSION'); },
                'get_forms' => [$this, 'get_jetformbuilder_forms'],
            ],
            'metform' => [
                'label' => 'MetForm',
                'detect' => function() { return class_exists('MetForm\Plugin') || defined('METFORM_VERSION'); },
                'get_forms' => [$this, 'get_metform_forms'],
            ],
            'mwwpform' => [
                'label' => 'MW WP Form',
                'detect' => function() { return class_exists('MW_WP_Form') || class_exists('MWF_Functions'); },
                'get_forms' => [$this, 'get_mwwpform_forms'],
            ],
            'sureforms' => [
                'label' => 'SureForms',
                'detect' => function() { return defined('SRFM_VER') || class_exists('JETROCKET\SureForms\Plugin'); },
                'get_forms' => [$this, 'get_sureforms_forms'],
            ],
            'formcraft' => [
                'label' => 'FormCraft',
                'detect' => function() { return defined('STARTER_PLUGIN_DIR') && class_exists('FormCraft3'); },
                'get_forms' => [$this, 'get_formcraft_forms'],
            ],
            'bricks' => [
                'label' => 'Bricks Forms',
                'detect' => function() { return defined('BRICKS_VERSION') || class_exists('Bricks\Theme'); },
                'get_forms' => [$this, 'get_bricks_forms'],
            ],
            'breakdance' => [
                'label' => 'Breakdance Forms',
                'detect' => function() { return defined('__BREAKDANCE_VERSION') || class_exists('Breakdance\Plugin'); },
                'get_forms' => [$this, 'get_breakdance_forms'],
            ],
            'beaver' => [
                'label' => 'Beaver Builder Forms',
                'detect' => function() { return class_exists('FLBuilder') || defined('FL_BUILDER_VERSION'); },
                'get_forms' => [$this, 'get_beaver_forms'],
            ],
            'mailpoet' => [
                'label' => 'MailPoet Forms',
                'detect' => function() { return class_exists('\MailPoet\API\API') || defined('MAILPOET_VERSION'); },
                'get_forms' => [$this, 'get_mailpoet_forms'],
            ],
        ];
    }

    /**
     * Register form submission hooks for all active providers
     */
    private function register_form_hooks() {
        // Hidden field injection is now in bt_conversion.js for better caching/optimization
        
        // Fluent Forms
        add_action('fluentform/notify_on_form_submit', [$this, 'handle_fluentform_submit'], 10, 3);
        
        // Contact Form 7
        add_action('wpcf7_submit', [$this, 'handle_cf7_submit'], 10, 2);
        
        // WPForms
        add_action('wpforms_process_complete', [$this, 'handle_wpforms_submit'], 10, 4);
        
        // Gravity Forms
        add_action('gform_after_submission', [$this, 'handle_gravity_submit'], 10, 2);
        
        // Ninja Forms
        add_action('ninja_forms_after_submission', [$this, 'handle_ninja_submit'], 10, 1);
        
        // Formidable Forms
        add_action('frm_after_create_entry', [$this, 'handle_formidable_submit'], 30, 2);
        
        // Forminator - both ajax and non-ajax
        add_action('forminator_form_after_handle_submit', [$this, 'handle_forminator_submit'], 10, 2);
        add_action('forminator_form_after_save_entry', [$this, 'handle_forminator_ajax_submit'], 10, 2);
        
        // Elementor Pro Forms
        add_action('elementor_pro/forms/new_record', [$this, 'handle_elementor_submit'], 10, 2);
        
        // JetFormBuilder
        add_action('jet-form-builder/form-handler/after-send', [$this, 'handle_jetformbuilder_submit'], 10, 1);
        
        // MetForm
        add_action('metform_after_store_form_data', [$this, 'handle_metform_submit'], 10, 1);
        
        // MW WP Form - dynamic hook registered in init
        add_action('init', [$this, 'register_mwwpform_hooks']);
        
        // SureForms
        add_action('srfm_form_submit', [$this, 'handle_sureforms_submit'], 10, 1);
        
        // FormCraft
        add_action('formcraft_before_save', [$this, 'handle_formcraft_submit'], 10, 1);
        
        // Bricks Forms
        add_action('bricks/form/custom_action', [$this, 'handle_bricks_submit'], 10, 1);
        
        // Breakdance Forms - fires when submission is stored
        add_action('save_post_breakdance_form_res', [$this, 'handle_breakdance_submit'], 10, 3);
        
        // Beaver Builder Contact Form
        add_action('fl_module_contact_form_after_send', [$this, 'handle_beaver_submit'], 10, 6);
        
        // MailPoet Forms
        add_action('mailpoet_subscription_before_subscribe', [$this, 'handle_mailpoet_submit'], 10, 3);
    }


    // =========================================================================
    // FORM LISTING FUNCTIONS
    // =========================================================================

    /**
     * Get all active form providers
     * 
     * @return array Provider keys that are currently active
     */
    public function get_active_providers() {
        $active = [];
        foreach ($this->providers as $key => $config) {
            try {
                if (is_callable($config['detect']) && call_user_func($config['detect'])) {
                    $active[] = $key;
                }
            } catch (Exception $e) {
                // Provider detection failed, skip it
                continue;
            }
        }
        return $active;
    }

    /**
     * Get forms for a specific provider
     * 
     * @param string $provider Provider key
     * @return array Forms array with id, title, value keys
     */
    public function get_forms_for_provider($provider) {
        if (!isset($this->providers[$provider])) {
            return [];
        }
        
        $config = $this->providers[$provider];
        
        // Check if provider is active
        try {
            if (!is_callable($config['detect']) || !call_user_func($config['detect'])) {
                return [];
            }
        } catch (Exception $e) {
            return [];
        }
        
        // Get forms
        try {
            if (is_callable($config['get_forms'])) {
                return call_user_func($config['get_forms']);
            }
        } catch (Exception $e) {
            return [];
        }
        
        return [];
    }

    /**
     * Get provider label
     * 
     * @param string $provider Provider key
     * @return string Provider label
     */
    public function get_provider_label($provider) {
        return isset($this->providers[$provider]['label']) ? $this->providers[$provider]['label'] : $provider;
    }

    /**
     * Get all forms grouped by provider (for UI rendering)
     * 
     * @return array ['provider_key' => ['label' => 'Label', 'forms' => [...]]]
     */
    public function get_all_forms_grouped() {
        $grouped = [];
        
        foreach ($this->get_active_providers() as $provider) {
            $forms = $this->get_forms_for_provider($provider);
            if (!empty($forms)) {
                $grouped[$provider] = [
                    'label' => $this->get_provider_label($provider),
                    'forms' => $forms,
                ];
            }
        }
        
        return $grouped;
    }

    /**
     * Render form options as HTML optgroups
     * 
     * @param string $selected_value Currently selected value (for edit mode)
     * @return string HTML optgroups
     */
    public function render_form_optgroups($selected_value = '') {
        $html = '';
        $grouped = $this->get_all_forms_grouped();
        
        foreach ($grouped as $provider => $data) {
            if (empty($data['forms'])) {
                continue;
            }
            
            $html .= '<optgroup label="' . esc_attr($data['label']) . '">';
            foreach ($data['forms'] as $form) {
                $selected = ($selected_value === $form['value']) ? ' selected="selected"' : '';
                $html .= '<option value="' . esc_attr($form['value']) . '"' . $selected . '>' . esc_html($form['title']) . '</option>';
            }
            $html .= '</optgroup>';
        }
        
        return $html;
    }

    // =========================================================================
    // FORM LISTING - PER PROVIDER
    // =========================================================================

    /**
     * Get Fluent Forms
     */
    public function get_fluentform_forms() {
        $forms = [];
        
        if (!class_exists('FluentForm\App\Models\Form')) {
            return $forms;
        }
        
        try {
            $ff_forms = \FluentForm\App\Models\Form::select(['id', 'title'])
                ->where('status', 'published')
                ->orderBy('title')
                ->get();
            
            if ($ff_forms) {
                foreach ($ff_forms as $form) {
                    $forms[] = [
                        'id' => $form->id,
                        'title' => $form->title ?: 'Form #' . $form->id,
                        'value' => 'form-fluentform-' . $form->id,
                    ];
                }
            }
        } catch (Exception $e) {
            // Fluent Forms query failed
        }
        
        return $forms;
    }

    /**
     * Get Contact Form 7 forms
     */
    public function get_cf7_forms() {
        $forms = [];
        
        if (!class_exists('WPCF7_ContactForm')) {
            return $forms;
        }
        
        try {
            $cf7_forms = get_posts([
                'post_type' => 'wpcf7_contact_form',
                'posts_per_page' => -1,
                'orderby' => 'title',
                'order' => 'ASC',
                'post_status' => 'publish',
            ]);
            
            foreach ($cf7_forms as $form) {
                $forms[] = [
                    'id' => $form->ID,
                    'title' => $form->post_title ?: 'Form #' . $form->ID,
                    'value' => 'form-cf7-' . $form->ID,
                ];
            }
        } catch (Exception $e) {
            // CF7 query failed
        }
        
        return $forms;
    }

    /**
     * Get WPForms forms
     */
    public function get_wpforms_forms() {
        $forms = [];
        
        try {
            $wpforms = get_posts([
                'post_type' => 'wpforms',
                'posts_per_page' => -1,
                'orderby' => 'title',
                'order' => 'ASC',
                'post_status' => 'publish',
            ]);
            
            foreach ($wpforms as $form) {
                $forms[] = [
                    'id' => $form->ID,
                    'title' => $form->post_title ?: 'Form #' . $form->ID,
                    'value' => 'form-wpforms-' . $form->ID,
                ];
            }
        } catch (Exception $e) {
            // WPForms query failed
        }
        
        return $forms;
    }

    /**
     * Get Gravity Forms
     */
    public function get_gravity_forms() {
        $forms = [];
        
        if (!class_exists('GFAPI')) {
            return $forms;
        }
        
        try {
            $gf_forms = GFAPI::get_forms();
            
            if (is_array($gf_forms)) {
                foreach ($gf_forms as $form) {
                    $forms[] = [
                        'id' => $form['id'],
                        'title' => $form['title'] ?: 'Form #' . $form['id'],
                        'value' => 'form-gravity-' . $form['id'],
                    ];
                }
            }
        } catch (Exception $e) {
            // Gravity Forms query failed
        }
        
        return $forms;
    }

    /**
     * Get Ninja Forms
     */
    public function get_ninja_forms() {
        $forms = [];
        
        if (!function_exists('Ninja_Forms')) {
            return $forms;
        }
        
        try {
            $nf_forms = Ninja_Forms()->form()->get_forms();
            
            if (is_array($nf_forms)) {
                foreach ($nf_forms as $form) {
                    $forms[] = [
                        'id' => $form->get_id(),
                        'title' => $form->get_setting('title') ?: 'Form #' . $form->get_id(),
                        'value' => 'form-ninjaforms-' . $form->get_id(),
                    ];
                }
            }
        } catch (Exception $e) {
            // Ninja Forms query failed
        }
        
        return $forms;
    }

    /**
     * Get Formidable Forms
     */
    public function get_formidable_forms() {
        $forms = [];
        
        if (!class_exists('FrmForm')) {
            return $forms;
        }
        
        try {
            $frm_forms = FrmForm::getAll();
            
            if (is_array($frm_forms)) {
                foreach ($frm_forms as $form) {
                    $forms[] = [
                        'id' => $form->id,
                        'title' => $form->name ?: 'Form #' . $form->id,
                        'value' => 'form-formidable-' . $form->id,
                    ];
                }
            }
        } catch (Exception $e) {
            // Formidable query failed
        }
        
        return $forms;
    }

    /**
     * Get Forminator forms
     */
    public function get_forminator_forms() {
        $forms = [];
        
        if (!class_exists('Forminator_API')) {
            return $forms;
        }
        
        try {
            $forminator_forms = Forminator_API::get_forms(null, 1, 100);
            
            if (!is_wp_error($forminator_forms) && is_array($forminator_forms)) {
                foreach ($forminator_forms as $form) {
                    if ($form->status === 'publish') {
                        $forms[] = [
                            'id' => $form->id,
                            'title' => $form->settings['formName'] ?? 'Form #' . $form->id,
                            'value' => 'form-forminator-' . $form->id,
                        ];
                    }
                }
            }
        } catch (Exception $e) {
            // Forminator query failed
        }
        
        return $forms;
    }

    /**
     * Get Elementor Pro forms
     * Note: Elementor forms don't have a central registry, we return a placeholder
     */
    public function get_elementor_forms() {
        $forms = [];
        
        // Elementor forms are embedded in pages/posts, not stored centrally
        // We'll provide a "any form" option and let users specify form name
        if (did_action('elementor_pro/init') || class_exists('ElementorPro\Modules\Forms\Module')) {
            $forms[] = [
                'id' => 'any',
                'title' => 'Any Elementor Form (specify name in field)',
                'value' => 'form-elementor-any',
            ];
        }
        
        return $forms;
    }

    /**
     * Get JetFormBuilder forms
     */
    public function get_jetformbuilder_forms() {
        $forms = [];
        
        try {
            $jfb_forms = get_posts([
                'post_type' => 'jet-form-builder',
                'posts_per_page' => -1,
                'orderby' => 'title',
                'order' => 'ASC',
                'post_status' => 'publish',
            ]);
            
            foreach ($jfb_forms as $form) {
                $forms[] = [
                    'id' => $form->ID,
                    'title' => $form->post_title ?: 'Form #' . $form->ID,
                    'value' => 'form-jetformbuilder-' . $form->ID,
                ];
            }
        } catch (Exception $e) {
            // JetFormBuilder query failed
        }
        
        return $forms;
    }

    /**
     * Get MetForm forms
     */
    public function get_metform_forms() {
        $forms = [];
        
        try {
            $mf_forms = get_posts([
                'post_type' => 'metform-form',
                'posts_per_page' => -1,
                'orderby' => 'title',
                'order' => 'ASC',
                'post_status' => 'publish',
            ]);
            
            foreach ($mf_forms as $form) {
                $forms[] = [
                    'id' => $form->ID,
                    'title' => $form->post_title ?: 'Form #' . $form->ID,
                    'value' => 'form-metform-' . $form->ID,
                ];
            }
        } catch (Exception $e) {
            // MetForm query failed
        }
        
        return $forms;
    }

    /**
     * Get MW WP Form forms
     */
    public function get_mwwpform_forms() {
        $forms = [];
        
        try {
            $mw_forms = get_posts([
                'post_type' => 'mw-wp-form',
                'posts_per_page' => -1,
                'orderby' => 'title',
                'order' => 'ASC',
                'post_status' => 'publish',
            ]);
            
            foreach ($mw_forms as $form) {
                $forms[] = [
                    'id' => $form->ID,
                    'title' => $form->post_title ?: 'Form #' . $form->ID,
                    'value' => 'form-mwwpform-' . $form->ID,
                ];
            }
        } catch (Exception $e) {
            // MW WP Form query failed
        }
        
        return $forms;
    }

    /**
     * Get SureForms forms
     */
    public function get_sureforms_forms() {
        $forms = [];
        
        try {
            $sf_forms = get_posts([
                'post_type' => 'sureforms_form',
                'posts_per_page' => -1,
                'orderby' => 'title',
                'order' => 'ASC',
                'post_status' => 'publish',
            ]);
            
            foreach ($sf_forms as $form) {
                $forms[] = [
                    'id' => $form->ID,
                    'title' => $form->post_title ?: 'Form #' . $form->ID,
                    'value' => 'form-sureforms-' . $form->ID,
                ];
            }
        } catch (Exception $e) {
            // SureForms query failed
        }
        
        return $forms;
    }

    /**
     * Get FormCraft forms
     */
    public function get_formcraft_forms() {
        $forms = [];
        
        global $wpdb;
        $table = $wpdb->prefix . 'formcraft_b_forms';

        // Check if table exists
        if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table)) !== $table) {
            return $forms;
        }

        try {
            $fc_forms = $wpdb->get_results($wpdb->prepare("SELECT id, name FROM {$table} ORDER BY name ASC"));
            
            if (is_array($fc_forms)) {
                foreach ($fc_forms as $form) {
                    $forms[] = [
                        'id' => $form->id,
                        'title' => $form->name ?: 'Form #' . $form->id,
                        'value' => 'form-formcraft-' . $form->id,
                    ];
                }
            }
        } catch (Exception $e) {
            // FormCraft query failed
        }
        
        return $forms;
    }

    // =========================================================================
    // FORM SUBMISSION HANDLERS
    // =========================================================================

    /**
     * Central conversion trigger
     * 
     * @param string $provider Provider key
     * @param int|string $form_id Form ID
     * @param string $location Optional location identifier
     */
    public function trigger_form_conversion($provider, $form_id, $location = '') {
        $conversion_value = "form-{$provider}-{$form_id}";
        
        if (empty($location)) {
            $location = "form-{$provider}-submit";
        }
        
        // Get the main plugin instance
        global $btab;
        if (!isset($btab) || !is_object($btab)) {
            abst_log('Form conversion: Could not get plugin instance');
            return;
        }
        
        abst_log("Form conversion triggered: provider={$provider}, form_id={$form_id}, value={$conversion_value}");
        
        // Find tests with this form as primary conversion
        $tests = get_posts([
            'numberposts' => -1,
            'post_type' => 'bt_experiments',
            'meta_key' => 'conversion_page',
            'meta_value' => $conversion_value,
            'post_status' => 'publish',
            'fields' => 'ids',
        ]);
        
        foreach ($tests as $eid) {
            $this->process_conversion_for_test($eid, $location, $btab);
        }
        
        // Also check goals for all running tests
        $this->check_form_goals($conversion_value, $location, $btab);
    }

    /**
     * Process conversion for a specific test
     * 
     * @param int $eid Experiment ID
     * @param string $location Location identifier
     * @param object $btab Plugin instance
     */
    private function process_conversion_for_test($eid, $location, $btab) {
        if (empty($eid)) {
            return;
        }
        
        $uuid = isset($_COOKIE['ab-advanced-id']) ? sanitize_text_field(wp_unslash($_COOKIE['ab-advanced-id'])) : false;
        $variation = '';
        $device_size = '';
        $page_location = '';
        $already_converted = false;

        // Try to get variation from btab cookie first
        if (isset($_COOKIE['btab_' . $eid])) {
            $cookie = json_decode(stripslashes(wp_unslash($_COOKIE['btab_' . $eid])), true);
            
            if (is_array($cookie)) {
                $variation = $cookie['variation'] ?? '';
                $device_size = $cookie['size'] ?? '';
                $page_location = $cookie['location'] ?? '';
                $already_converted = isset($cookie['conversion']) && $cookie['conversion'] == 1;
                
                if (!$already_converted) {
                    // Update cookie to mark as converted
                    $cookie['conversion'] = 1;
                    if (function_exists('abst_set_cookie_with_fallback')) {
                        abst_set_cookie_with_fallback('btab_' . $eid, json_encode($cookie, JSON_UNESCAPED_SLASHES), 1000);
                    }
                }
            }
        }
        // Fallback #1: Try to get variation from hidden form field (abst_data)
        elseif (isset($_POST['abst_data']) || isset($_REQUEST['abst_data'])) {
            $abst_data_raw = isset($_POST['abst_data']) ? wp_unslash($_POST['abst_data']) : wp_unslash($_REQUEST['abst_data']);
            $abst_data = json_decode(stripslashes(sanitize_text_field($abst_data_raw)), true);
            
            if (is_array($abst_data) && isset($abst_data[$eid])) {
                $variation = sanitize_text_field($abst_data[$eid]);
                abst_log("Form conversion: Found variation '{$variation}' from hidden field for eid={$eid}");
            } else {
                abst_log("Form conversion: Hidden field found but no data for eid={$eid}");
            }
        }
        
        // Final check - if still no variation, exit
        if (empty($variation)) {
            abst_log("Form conversion skipped (no cookie, no hidden field, no UUID): eid={$eid}");
            return;
        }

        if (empty($device_size) && function_exists('abst_detect_device_size_from_user_agent')) {
            $device_size = abst_detect_device_size_from_user_agent();
        }
        
        // Skip if already converted
        if ($already_converted) {
            abst_log("Form conversion skipped (already converted): eid={$eid}");
            return;
        }
        
        // Skip if no variation found
        if (empty($variation)) {
            abst_log("Form conversion skipped (no variation found): eid={$eid}");
            return;
        }
        
        // Set server event cookie
        if (method_exists($btab, 'set_server_event_cookie')) {
            $btab->set_server_event_cookie($eid, $variation, 'conversion');
        }
        
        // Log the conversion - JS flushes A/B queue every 2s so visit data should be there
        if (method_exists($btab, 'log_experiment_activity')) {
            $btab->log_experiment_activity($eid, $variation, 'conversion', true, $location, 1, $uuid, $device_size);
        }
        
        abst_log("Form conversion logged: eid={$eid}, variation={$variation}, location={$location}");
    }

    /**
     * Check if form submission matches any test goals
     * 
     * @param string $conversion_value The form conversion value
     * @param string $location Location identifier
     * @param object $btab Plugin instance
     */
    private function check_form_goals($conversion_value, $location, $btab) {
        // Get all running tests
        $tests = get_posts([
            'numberposts' => -1,
            'post_type' => 'bt_experiments',
            'post_status' => 'publish',
            'fields' => 'ids',
        ]);
        
        foreach ($tests as $eid) {
            $goals = get_post_meta($eid, 'goals', true);
            
            if (!is_array($goals)) {
                continue;
            }
            
            foreach ($goals as $goal_index => $goal) {
                if (!is_array($goal)) {
                    continue;
                }
                
                // Check if this goal matches our form
                $goal_key = array_keys($goal)[0] ?? '';
                $goal_value = $goal[$goal_key] ?? '';
                
                // Form goals are stored with the form value as the goal value
                if ($goal_value === $conversion_value) {
                    $this->process_goal_for_test($eid, $goal_index, $location, $btab);
                }
            }
        }
    }

    /**
     * Process goal completion for a specific test
     * 
     * @param int $eid Experiment ID
     * @param int $goal_index Goal index
     * @param string $location Location identifier
     * @param object $btab Plugin instance
     */
    private function process_goal_for_test($eid, $goal_index, $location, $btab) {
        if (empty($eid) || !is_numeric($goal_index)) {
            return;
        }
        
        $uuid = isset($_COOKIE['ab-advanced-id']) ? sanitize_text_field(wp_unslash($_COOKIE['ab-advanced-id'])) : false;
        $variation = '';
        $device_size = '';
        $page_location = '';
        $goal_already_completed = false;

        // Try to get variation from btab cookie first
        if (isset($_COOKIE['btab_' . $eid])) {
            $cookie = json_decode(stripslashes(wp_unslash($_COOKIE['btab_' . $eid])), true);
            
            if (!is_array($cookie)) {
                return;
            }
            
            $variation = $cookie['variation'] ?? '';
            $device_size = $cookie['size'] ?? '';
            $page_location = $cookie['location'] ?? '';
            $goal_already_completed = isset($cookie['goals'][$goal_index]) && $cookie['goals'][$goal_index] == 1;
            
            if (!$goal_already_completed) {
                // Mark goal as completed in cookie
                if (!isset($cookie['goals'])) {
                    $cookie['goals'] = [];
                }
                $cookie['goals'][$goal_index] = 1;
                
                if (function_exists('abst_set_cookie_with_fallback')) {
                    abst_set_cookie_with_fallback('btab_' . $eid, json_encode($cookie, JSON_UNESCAPED_SLASHES), 1000);
                }
            }
        }
        // Fallback #1: Try to get variation from hidden form field (abst_data)
        elseif (isset($_POST['abst_data']) || isset($_REQUEST['abst_data'])) {
            $abst_data_raw = isset($_POST['abst_data']) ? wp_unslash($_POST['abst_data']) : wp_unslash($_REQUEST['abst_data']);
            $abst_data = json_decode(stripslashes(sanitize_text_field($abst_data_raw)), true);
            
            if (is_array($abst_data) && isset($abst_data[$eid])) {
                $variation = sanitize_text_field($abst_data[$eid]);
                abst_log("Form goal: Found variation '{$variation}' from hidden field for eid={$eid}");
            } else {
                abst_log("Form goal: Hidden field found but no data for eid={$eid}");
            }
        }
        
        // Final check - if still no variation, exit
        if (empty($variation)) {
            abst_log("Form goal skipped (no cookie, no hidden field, no UUID): eid={$eid}");
            return;
        }

        if (empty($device_size) && function_exists('abst_detect_device_size_from_user_agent')) {
            $device_size = abst_detect_device_size_from_user_agent();
        }
        
        // Skip if goal already completed
        if ($goal_already_completed) {
            abst_log("Form goal skipped (already completed): eid={$eid}, goal={$goal_index}");
            return;
        }
        
        // Skip if no variation found
        if (empty($variation)) {
            abst_log("Form goal skipped (no variation found): eid={$eid}");
            return;
        }
        
        // Log the goal
        if (method_exists($btab, 'log_experiment_activity')) {
            $btab->log_experiment_activity($eid, $variation, 'goal' . $goal_index, true, $location, 1, $uuid, $device_size);
        }
        
        abst_log("Form goal logged: eid={$eid}, goal={$goal_index}, variation={$variation}");
    }

    // =========================================================================
    // FORM SUBMISSION HOOK HANDLERS
    // =========================================================================

    /**
     * Handle Fluent Forms submission
     * Note: Fluent Forms sends data as serialized string in $_REQUEST['data']
     */
    public function handle_fluentform_submit($entry_id, $form_data, $form) {
        if (!is_object($form) || !isset($form->id)) {
            return;
        }
        
        // Fluent Forms sends form data as serialized string - extract abst_data
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        if (isset($_REQUEST['data']) && is_string($_REQUEST['data'])) {
            try {
                $args = [];
                // phpcs:ignore WordPress.Security.NonceVerification.Recommended
                $pairs = explode('&', sanitize_text_field(wp_unslash($_REQUEST['data'])));
                foreach ($pairs as $pair) {
                    $parts = explode('=', $pair, 2);
                    if (count($parts) === 2) {
                        $args[$parts[0]] = urldecode($parts[1]);
                    }
                }
                // Extract abst_data and add to $_POST/$_REQUEST if not already set
                if (isset($args['abst_data']) && !isset($_REQUEST['abst_data'])) {
                    $_POST['abst_data'] = $args['abst_data'];
                    $_REQUEST['abst_data'] = $args['abst_data'];
                }
            } catch (\Exception $e) {
                // Silently fail
            }
        }
        
        $this->trigger_form_conversion('fluentform', $form->id);
    }

    /**
     * Handle Contact Form 7 submission
     */
    public function handle_cf7_submit($form, $result) {
        // Only trigger on successful submission
        if (!isset($result['status']) || !in_array($result['status'], ['mail_sent', 'demo_mode'])) {
            return;
        }
        
        if (!is_object($form) || !method_exists($form, 'id')) {
            return;
        }
        
        $this->trigger_form_conversion('cf7', $form->id());
    }

    /**
     * Handle WPForms submission
     */
    public function handle_wpforms_submit($fields, $entry, $form_data, $entry_id) {
        if (!isset($form_data['id'])) {
            return;
        }
        
        $this->trigger_form_conversion('wpforms', $form_data['id']);
    }

    /**
     * Handle Gravity Forms submission
     */
    public function handle_gravity_submit($entry, $form) {
        // Skip spam entries
        if (isset($entry['status']) && $entry['status'] === 'spam') {
            return;
        }
        
        if (!isset($form['id'])) {
            return;
        }
        
        $this->trigger_form_conversion('gravity', $form['id']);
    }

    /**
     * Handle Ninja Forms submission
     */
    public function handle_ninja_submit($form_data) {
        if (!isset($form_data['form_id'])) {
            return;
        }
        
        $this->trigger_form_conversion('ninjaforms', $form_data['form_id']);
    }

    /**
     * Handle Formidable Forms submission
     */
    public function handle_formidable_submit($entry_id, $form_id) {
        if (empty($form_id)) {
            return;
        }
        
        $this->trigger_form_conversion('formidable', $form_id);
    }

    /**
     * Handle Forminator submission (non-ajax)
     */
    public function handle_forminator_submit($form_id, $response) {
        if (empty($response['success'])) {
            return;
        }
        
        if (empty($form_id)) {
            return;
        }
        
        $this->trigger_form_conversion('forminator', $form_id);
    }

    /**
     * Handle Forminator submission (ajax)
     */
    public function handle_forminator_ajax_submit($form_id, $response) {
        if (empty($response) || !is_array($response) || empty($response['success'])) {
            return;
        }
        
        if (empty($form_id)) {
            return;
        }
        
        $this->trigger_form_conversion('forminator', $form_id);
    }

    /**
     * Handle Elementor Pro Forms submission
     */
    public function handle_elementor_submit($record, $handler) {
        if (!is_object($record) || !method_exists($record, 'get_form_settings')) {
            return;
        }
        
        $form_name = $record->get_form_settings('form_name');
        $form_id = $record->get_form_settings('id');
        
        // Trigger for "any" elementor form
        $this->trigger_form_conversion('elementor', 'any');
        
        // Also trigger for specific form if we have an ID
        if (!empty($form_id)) {
            $this->trigger_form_conversion('elementor', $form_id);
        }
    }

    /**
     * Handle JetFormBuilder submission
     */
    public function handle_jetformbuilder_submit($form) {
        if (!is_object($form)) {
            return;
        }
        
        // Check for success
        if (property_exists($form, 'is_success') && !$form->is_success) {
            return;
        }
        
        if (!property_exists($form, 'form_id')) {
            return;
        }
        
        $this->trigger_form_conversion('jetformbuilder', $form->form_id);
    }

    /**
     * Handle MetForm submission
     */
    public function handle_metform_submit($form_id) {
        if (empty($form_id)) {
            return;
        }
        
        $this->trigger_form_conversion('metform', $form_id);
    }

    /**
     * Register MW WP Form hooks dynamically
     */
    public function register_mwwpform_hooks() {
        if (!class_exists('MWF_Functions')) {
            return;
        }
        
        // Get all MW WP Form forms and register hooks
        $forms = $this->get_mwwpform_forms();
        
        foreach ($forms as $form) {
            $form_key = MWF_Functions::get_form_key_from_form_id($form['id']);
            if ($form_key) {
                add_action('mwform_after_send_' . $form_key, function() use ($form) {
                    $this->trigger_form_conversion('mwwpform', $form['id']);
                });
            }
        }
    }

    /**
     * Handle SureForms submission
     */
    public function handle_sureforms_submit($form_data) {
        if (!isset($form_data['form_id']) || empty($form_data['form_id'])) {
            return;
        }
        
        $this->trigger_form_conversion('sureforms', $form_data['form_id']);
    }

    /**
     * Handle FormCraft submission
     */
    public function handle_formcraft_submit($form) {
        if (!isset($form['Form ID'])) {
            return;
        }
        
        $this->trigger_form_conversion('formcraft', $form['Form ID']);
    }

    /**
     * Handle Bricks Forms submission
     */
    public function handle_bricks_submit($form) {
        if (!is_object($form) || !method_exists($form, 'get_id')) {
            return;
        }
        
        $form_id = $form->get_id();
        if (empty($form_id)) {
            return;
        }
        
        $this->trigger_form_conversion('bricks', $form_id);
    }

    /**
     * Get Bricks Forms
     */
    public function get_bricks_forms() {
        $forms = [];
        
        if (!defined('BRICKS_VERSION')) {
            return $forms;
        }
        
        // Bricks forms are elements within posts/pages, not separate entities
        // We'll provide a generic "Any Bricks Form" option
        $forms[] = [
            'value' => 'form-bricks-any',
            'title' => 'Any Bricks Form',
        ];
        
        return $forms;
    }

    /**
     * Handle Breakdance Forms submission
     */
    public function handle_breakdance_submit($post_id, $post, $update) {
        // Only on new submissions, not updates
        if ($update) {
            return;
        }
        
        // Get form ID from meta
        $form_id = get_post_meta($post_id, '_breakdance_form_id', true);
        if (empty($form_id)) {
            $form_id = 'any';
        }
        
        $this->trigger_form_conversion('breakdance', $form_id);
    }

    /**
     * Get Breakdance Forms
     */
    public function get_breakdance_forms() {
        $forms = [];
        
        if (!defined('__BREAKDANCE_VERSION')) {
            return $forms;
        }
        
        // Breakdance forms are elements within posts/pages
        $forms[] = [
            'value' => 'form-breakdance-any',
            'title' => 'Any Breakdance Form',
        ];
        
        return $forms;
    }

    /**
     * Handle Beaver Builder Contact Form submission
     */
    public function handle_beaver_submit($mailto, $subject, $template, $headers, $settings, $result) {
        // Only on successful send
        if (!$result) {
            return;
        }
        
        // Beaver Builder contact forms don't have unique IDs
        $this->trigger_form_conversion('beaver', 'any');
    }


    public function handle_mailpoet_submit($data, $segment_ids, $form) {
        abst_log('MailPoet form conversion started');
        try {
            $form_id = $form->getId();
            
            if ($form_id) {
                $this->trigger_form_conversion('mailpoet', $form_id);
            }
        } catch (Exception $e) {
            abst_log('MailPoet form conversion failed: ' . $e->getMessage());
        }
    }


    /**
     * Get Beaver Builder Forms
     */
    public function get_beaver_forms() {
        $forms = [];
        
        if (!class_exists('FLBuilder')) {
            return $forms;
        }
        
        // Beaver Builder contact forms are modules, not separate entities
        $forms[] = [
            'value' => 'form-beaver-any',
            'title' => 'Any Beaver Builder Contact Form',
        ];
        
        return $forms;
    }

    /**
     * Get MailPoet Forms
     */
    public function get_mailpoet_forms() {
        $forms = [];
        
        // Skip if MailPoet isn't active
        if (!defined('MAILPOET_VERSION')) {
            return $forms;
        }
        
        try {
            // APPROACH 1: Direct database query (most reliable)
            $forms = $this->get_mailpoet_forms_from_db();
            if (!empty($forms)) {
                return $forms;
            }
            
            // APPROACH 2: Try MailPoet 3.x API with FormRepository
            if (class_exists('\MailPoet\Form\FormRepository')) {
                $forms = $this->get_mailpoet_forms_via_repository();
                if (!empty($forms)) {
                    return $forms;
                }
            }
            // Log that all approaches failed            
        } catch (Exception $e) {
            abst_log('MailPoet forms query failed: ' . $e->getMessage());
        }
        
        return $forms;
    }
    
    /**
     * Get MailPoet forms directly from the database
     * This is the most reliable method that works across all versions
     */
    private function get_mailpoet_forms_from_db() {
        $forms = [];
        global $wpdb;
        
        // Try the MailPoet 3 table structure first
        $forms_table = $wpdb->prefix . 'mailpoet_forms';
        
        // Check if table exists
        if ($wpdb->get_var("SHOW TABLES LIKE '$forms_table'") === $forms_table) {
            // Check if deleted_at column exists
            $columns = $wpdb->get_results("SHOW COLUMNS FROM {$forms_table} LIKE 'deleted_at'");
            $where_clause = !empty($columns) ? "WHERE deleted_at IS NULL" : "";
            
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQL.InterpolatedNotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter -- $forms_table uses $wpdb->prefix only; $where_clause is a hard-coded literal string.
            $results = $wpdb->get_results( "SELECT id, name FROM {$forms_table} {$where_clause}" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
            
            if (is_array($results)) {
                foreach ($results as $form) {
                    $forms[] = [
                        'id' => $form->id,
                        'title' => $form->name ?: 'Form #' . $form->id,
                        'value' => 'form-mailpoet-' . $form->id,
                    ];
                }
            }
        }
        
        // If no forms found, try legacy MailPoet 2 table
        if (empty($forms)) {
            $legacy_table = $wpdb->prefix . 'wysija_form';
            if ($wpdb->get_var("SHOW TABLES LIKE '$legacy_table'") === $legacy_table) {
                $results = $wpdb->get_results("SELECT form_id as id, name FROM {$legacy_table}");
                
                if (is_array($results)) {
                    foreach ($results as $form) {
                        $forms[] = [
                            'id' => $form->id,
                            'title' => $form->name ?: 'Form #' . $form->id,
                            'value' => 'form-mailpoet-' . $form->id,
                        ];
                    }
                }
            }
        }
        
        return $forms;
    }
    
    /**
     * Get MailPoet forms via FormRepository (MailPoet 3.x)
     */
    private function get_mailpoet_forms_via_repository() {
        $forms = [];
        
        try {
            if (class_exists('\MailPoet\DI\ContainerWrapper') && 
                method_exists('\MailPoet\DI\ContainerWrapper', 'getInstance')) {
                
                $container = \MailPoet\DI\ContainerWrapper::getInstance();
                
                if (method_exists($container, 'get') && 
                    class_exists('\MailPoet\Form\FormRepository')) {
                    
                    $repository = $container->get('\MailPoet\Form\FormRepository');
                    
                    // Try different methods that might exist
                    $mailpoet_forms = null;
                    if (method_exists($repository, 'findAll')) {
                        $mailpoet_forms = $repository->findAll();
                    } elseif (method_exists($repository, 'findBy')) {
                        $mailpoet_forms = $repository->findBy([], null);
                    }
                    
                    if (is_array($mailpoet_forms) || (is_object($mailpoet_forms) && $mailpoet_forms instanceof \Traversable)) {
                        foreach ($mailpoet_forms as $form) {
                            if (is_object($form)) {
                                // Try to access properties or methods to get id and name
                                $id = null;
                                $name = null;
                                
                                if (method_exists($form, 'getId')) {
                                    $id = $form->getId();
                                } elseif (property_exists($form, 'id')) {
                                    $id = $form->id;
                                }
                                
                                if (method_exists($form, 'getName')) {
                                    $name = $form->getName();
                                } elseif (property_exists($form, 'name')) {
                                    $name = $form->name;
                                } elseif (method_exists($form, 'get') && is_callable([$form, 'get'])) {
                                    $name = $form->get('name');
                                }
                                
                                if ($id && $name) {
                                    $forms[] = [
                                        'id' => $id,
                                        'title' => $name ?: 'Form #' . $id,
                                        'value' => 'form-mailpoet-' . $id,
                                    ];
                                }
                            }
                        }
                    }
                }
            }
        } catch (Exception $e) {
            abst_log('MailPoet FormRepository approach failed: ' . $e->getMessage());
        }
        
        return $forms;
    }
    
    
}

/**
 * Initialize form conversions
 */
function abst_init_form_conversions() {
    return ABST_Form_Conversions::get_instance();
}

// Initialize on plugins_loaded to ensure all form plugins are loaded
add_action('plugins_loaded', 'abst_init_form_conversions', 20);

/**
 * Helper function to get form optgroups HTML
 * 
 * @param string $selected_value Currently selected value
 * @return string HTML optgroups
 */
function abst_get_form_optgroups($selected_value = '') {
    $form_conversions = ABST_Form_Conversions::get_instance();
    return $form_conversions->render_form_optgroups($selected_value);
}

/**
 * Helper function to check if any form plugins are active
 * 
 * @return bool
 */
function abst_has_form_plugins() {
    $form_conversions = ABST_Form_Conversions::get_instance();
    $providers = $form_conversions->get_active_providers();
    return !empty($providers);
}
