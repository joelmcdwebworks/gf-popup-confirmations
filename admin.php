<?php
declare(strict_types=1);

/**
 * Admin functionality for Gravity Forms Popup Confirmations
 * 
 * Uses a simpler approach that works with Gravity Forms' actual architecture
 */

if (!defined('ABSPATH')) {
    exit;
}

class GF_Popup_Confirmations_Admin {
    
    /**
     * Initialize the admin functionality
     */
    public function __construct() {
        add_action('admin_init', [$this, 'init']);
        add_action('admin_footer', [$this, 'maybe_show_confirmation_type_warning']);
    }
    
    /**
     * Initialize admin hooks
     */
    public function init(): void {
        // Always enqueue admin scripts and styles, let JavaScript handle the targeting
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
        
        // Handle AJAX requests for popup settings (only load, save is handled by form submission)
        add_action('wp_ajax_gf_load_popup_confirmation', [$this, 'ajax_load_popup_confirmation']);
        
        // Handle confirmation settings save
        add_action('gform_after_save_form', [$this, 'save_confirmation_popup_setting'], 10, 2);
        
        // Add admin notices for problematic confirmation configurations
        // add_action('admin_notices', [$this, 'display_confirmation_warnings']); // Removed
        
        // Add a test notice to verify the hook is working
        add_action('admin_notices', function() {
            // Show a test notice on Gravity Forms pages
            if ($this->is_gravity_forms_admin()) {
                echo '<div class="notice notice-info is-dismissible"><p><strong>GF Popup Confirmations:</strong> Admin notices hook is working. Check the debug log for more details.</p></div>';
            }
        });
    }
    
    /**
     * Check if we're on a Gravity Forms admin page
     */
    private function is_gravity_forms_admin(): bool {
        $current_screen = get_current_screen();
        
        // Check for various Gravity Forms admin pages
        $is_gf_admin = false;
        
        if ($current_screen) {
            $screen_id = $current_screen->id;
            $is_gf_admin = (
                strpos($screen_id, 'gravityforms') !== false ||
                strpos($screen_id, 'gf_') !== false ||
                strpos($screen_id, 'toplevel_page_gf_') !== false ||
                (isset($_GET['page']) && strpos($_GET['page'], 'gf_') !== false) ||
                (isset($_GET['page']) && $_GET['page'] === 'gf_edit_forms')
            );
        }
        
        return $is_gf_admin;
    }
    
    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_admin_assets(): void {
        wp_enqueue_script(
            'gf-popup-confirmations-admin',
            plugin_dir_url(__FILE__) . 'admin.js',
            ['jquery'],
            '1.0.0',
            true
        );
        
        wp_enqueue_style(
            'gf-popup-confirmations-admin',
            plugin_dir_url(__FILE__) . 'admin.css',
            [],
            '1.0.0'
        );
        
        // Localize script for AJAX
        $nonce = wp_create_nonce('gf_popup_confirmations_nonce');
        
        wp_localize_script('gf-popup-confirmations-admin', 'gfPopupConfirmations', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => $nonce,
        ]);
    }
    

    
    /**
     * Handle AJAX request to load popup confirmation setting
     */
    public function ajax_load_popup_confirmation(): void {
        // Verify nonce
        $nonce_verified = wp_verify_nonce($_POST['nonce'] ?? '', 'gf_popup_confirmations_nonce');
        
        if (!$nonce_verified) {
            wp_die('Security check failed');
        }
        
        // Check user capabilities - try multiple capabilities
        $has_permission = (
            current_user_can('gravityforms_view_forms') ||
            current_user_can('gravityforms_edit_forms') ||
            current_user_can('gravityforms_full_access') ||
            current_user_can('manage_options')
        );
        
        if (!$has_permission) {
            wp_die('Insufficient permissions');
        }
        
        $form_id = intval($_POST['form_id'] ?? 0);
        $confirmation_id = sanitize_text_field($_POST['confirmation_id'] ?? '');
        
        if (!$form_id || !$confirmation_id) {
            wp_die('Invalid parameters');
        }
        
        // Get the form
        $form = GFAPI::get_form($form_id);
        if (!$form) {
            wp_die('Form not found');
        }
        
        // Get the confirmation setting
        $display_modal = false;
        $query_string = '';
        
        // Try different confirmation ID formats
        $possible_ids = [$confirmation_id];
        
        // If confirmation_id is 'default', also try '0' and '1'
        if ($confirmation_id === 'default') {
            $possible_ids[] = '0';
            $possible_ids[] = '1';
        }
        
        // If confirmation_id is numeric, also try as string
        if (is_numeric($confirmation_id)) {
            $possible_ids[] = (string)$confirmation_id;
        }
        
        // Check each possible ID
        foreach ($possible_ids as $id) {
            if (isset($form['confirmations'][$id]['displayModal'])) {
                $display_modal = boolval($form['confirmations'][$id]['displayModal']);
                $query_string = $form['confirmations'][$id]['queryString'] ?? '';
                break;
            }
        }
        
        wp_send_json_success([
            'displayModal' => $display_modal,
            'queryString' => $query_string
        ]);
    }
    
    /**
     * Save popup setting when confirmation settings are saved
     * 
     * @param array $form The form object
     * @param bool $is_new Whether this is a new form
     */
    public function save_confirmation_popup_setting($form, $is_new): void {
        // Check if we're saving confirmation settings
        if (!isset($_POST['_gform_setting_displayModal'])) {
            return;
        }
        
        $display_modal = boolval($_POST['_gform_setting_displayModal']);
        $query_string = sanitize_text_field($_POST['_gform_setting_queryString'] ?? '');
        
        // Get the current confirmation being edited
        $confirmation_id = $_POST['_gform_setting_id'] ?? 'default';
        
        // Update the confirmation setting
        if (isset($form['confirmations'][$confirmation_id])) {
            if ($display_modal) {
                $form['confirmations'][$confirmation_id]['displayModal'] = true;
                
                // Save queryString if provided
                if (!empty($query_string)) {
                    $form['confirmations'][$confirmation_id]['queryString'] = $query_string;
                } else {
                    unset($form['confirmations'][$confirmation_id]['queryString']);
                }
            } else {
                unset($form['confirmations'][$confirmation_id]['displayModal']);
                unset($form['confirmations'][$confirmation_id]['queryString']);
            }
            
            // Save the form
            $result = GFAPI::update_form($form);
        } else {
        }
    }
    
    /**
     * Display admin warnings for problematic confirmation configurations
     */
    public function display_confirmation_warnings(): void {
        // Only show on Gravity Forms admin pages
        if (!$this->is_gravity_forms_admin()) {
            return;
        }
        
        // Check if we're on a form edit page
        $current_screen = get_current_screen();
        if (!$current_screen || strpos($current_screen->id, 'gravityforms') === false) {
            return;
        }
        
        // Get the current form ID from URL
        $form_id = 0;
        if (isset($_GET['id'])) {
            $form_id = intval($_GET['id']);
        }
        
        if (!$form_id) {
            return;
        }
        
        // Get the form
        $form = GFAPI::get_form($form_id);
        if (!$form) {
            return;
        }
        
        // Check if this form has the legacy CSS class
        if (!isset($form['cssClass']) || strpos($form['cssClass'], 'gf_confirmation_popup') === false) {
            return;
        }
        
        // Check for problematic confirmations
        $problematic_confirmations = [];
        if (isset($form['confirmations']) && is_array($form['confirmations'])) {
            foreach ($form['confirmations'] as $confirmation_id => $confirmation) {
                if (isset($confirmation['type']) && in_array($confirmation['type'], ['page', 'redirect'])) {
                    $confirmation_name = isset($confirmation['name']) ? $confirmation['name'] : $confirmation_id;
                    $is_conditional = isset($confirmation['conditionalLogic']) && is_array($confirmation['conditionalLogic']);
                    $has_rules = $is_conditional && isset($confirmation['conditionalLogic']['rules']) && 
                                is_array($confirmation['conditionalLogic']['rules']) && 
                                !empty($confirmation['conditionalLogic']['rules']);
                    
                    // Only include if it has rules (meaning it could be triggered)
                    if (!$is_conditional || $has_rules) {
                        $problematic_confirmations[] = [
                            'name' => $confirmation_name,
                            'type' => $confirmation['type'],
                            'is_conditional' => $is_conditional
                        ];
                    }
                }
            }
        }
        
        if (!empty($problematic_confirmations)) {
            $confirmation_list = [];
            foreach ($problematic_confirmations as $conf) {
                $type_label = $conf['type'] === 'page' ? 'Page' : 'Redirect';
                $conditional_label = $conf['is_conditional'] ? ' (conditional)' : '';
                $confirmation_list[] = "• {$conf['name']} ({$type_label}){$conditional_label}";
            }
            
            $confirmation_text = implode('<br>', $confirmation_list);
            
            $warning_message = sprintf(
                '<div class="notice notice-warning is-dismissible"><p><strong>%s</strong> %s</p><p><strong>%s</strong></p><p>%s</p><p><strong>%s</strong></p></div>',
                __('Gravity Forms Popup Confirmations Warning:', 'gf-popup-confirmations'),
                __('This form has the CSS class "gf_confirmation_popup" but contains the following confirmations that will cause fatal errors when triggered:', 'gf-popup-confirmations'),
                __('Problematic Confirmations:', 'gf-popup-confirmations'),
                $confirmation_text,
                __('To fix this issue, either remove the CSS class "gf_confirmation_popup" from the form or change these confirmation types to "Text".', 'gf-popup-confirmations')
            );
            
            echo $warning_message;
        }
    }

    public function maybe_show_confirmation_type_warning() {
        // Only show on the confirmation subview after save
        if (
            isset($_GET['page'], $_GET['view'], $_GET['subview'], $_GET['id'], $_GET['cid']) &&
            $_GET['page'] === 'gf_edit_forms' &&
            $_GET['view'] === 'settings' &&
            $_GET['subview'] === 'confirmation'
        ) {
            $form_id = intval($_GET['id']);
            $confirmation_id = sanitize_text_field($_GET['cid']);
            $form = GFAPI::get_form($form_id);

            if (
                $form &&
                isset($form['cssClass']) &&
                strpos($form['cssClass'], 'gf_confirmation_popup') !== false &&
                isset($form['confirmations'][$confirmation_id]) &&
                in_array($form['confirmations'][$confirmation_id]['type'], ['page', 'redirect'])
            ) {
                echo '<div id="gf-popup-warning-admin" class="notice notice-warning" style="margin-top:20px; display:none;"><p><strong>Warning:</strong> This form has the CSS class <code>gf_confirmation_popup</code>, but this confirmation is set to <strong>Page</strong> or <strong>Redirect</strong>. This will cause errors for users. Please use a <strong>Text</strong> confirmation or remove the CSS class.</p></div>';
            }
        }
    }
}

// Initialize the admin class
new GF_Popup_Confirmations_Admin(); 