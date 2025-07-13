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
    }
    
    /**
     * Initialize admin hooks
     */
    public function init(): void {
        // Debug: Log that we're initializing
        if (function_exists('qm_log')) {
            qm_log('GF Popup Confirmations: Admin class initialized');
        }
        
        // Always enqueue admin scripts and styles, let JavaScript handle the targeting
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
        
        // Handle AJAX requests for popup settings (only load, save is handled by form submission)
        add_action('wp_ajax_gf_load_popup_confirmation', [$this, 'ajax_load_popup_confirmation']);
        
        // Handle confirmation settings save
        add_action('gform_after_save_form', [$this, 'save_confirmation_popup_setting'], 10, 2);
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
        
        // Debug: Log the screen check
        if (function_exists('qm_log')) {
            qm_log('GF Popup Confirmations: Screen check - ' . ($current_screen ? $current_screen->id : 'no screen') . ' - GET page: ' . ($_GET['page'] ?? 'none') . ' - Result: ' . ($is_gf_admin ? 'true' : 'false'));
        }
        
        return $is_gf_admin;
    }
    
    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_admin_assets(): void {
        // Debug: Log that we're enqueuing assets
        if (function_exists('qm_log')) {
            qm_log('GF Popup Confirmations: Enqueuing admin assets');
        }
        
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
        
        // Debug: Log the nonce
        if (function_exists('qm_log')) {
            qm_log('GF Popup Confirmations: Generated nonce', [
                'nonce' => $nonce,
                'nonce_length' => strlen($nonce)
            ]);
        }
        
        wp_localize_script('gf-popup-confirmations-admin', 'gfPopupConfirmations', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => $nonce,
        ]);
        
        // Debug: Log the script URL
        if (function_exists('qm_log')) {
            qm_log('GF Popup Confirmations: Admin script URL - ' . plugin_dir_url(__FILE__) . 'admin.js');
        }
    }
    

    
    /**
     * Handle AJAX request to load popup confirmation setting
     */
    public function ajax_load_popup_confirmation(): void {
        // Debug: Log the incoming request
        if (function_exists('qm_log')) {
            qm_log('GF Popup Confirmations: AJAX request received', [
                'post_data' => $_POST,
                'nonce_received' => $_POST['nonce'] ?? 'none',
                'action_received' => $_POST['action'] ?? 'none'
            ]);
        }
        
        // Verify nonce
        $nonce_verified = wp_verify_nonce($_POST['nonce'] ?? '', 'gf_popup_confirmations_nonce');
        
        // Debug: Log nonce verification
        if (function_exists('qm_log')) {
            qm_log('GF Popup Confirmations: Nonce verification', [
                'nonce_verified' => $nonce_verified,
                'nonce_received' => $_POST['nonce'] ?? 'none',
                'nonce_expected' => wp_create_nonce('gf_popup_confirmations_nonce')
            ]);
        }
        
        if (!$nonce_verified) {
            wp_die('Security check failed');
        }
        
        // Debug: Log user capabilities
        if (function_exists('qm_log')) {
            qm_log('GF Popup Confirmations: User capabilities check', [
                'user_id' => get_current_user_id(),
                'user_roles' => wp_get_current_user()->roles,
                'can_edit_forms' => current_user_can('gravityforms_edit_forms'),
                'can_view_forms' => current_user_can('gravityforms_view_forms'),
                'can_full_access' => current_user_can('gravityforms_full_access'),
                'can_manage_options' => current_user_can('manage_options'),
                'can_edit_posts' => current_user_can('edit_posts')
            ]);
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
        
        // Debug: Log the AJAX load request
        if (function_exists('qm_log')) {
            qm_log('GF Popup Confirmations: AJAX load request', [
                'form_id' => $form_id,
                'confirmation_id' => $confirmation_id,
                'user_can_edit' => current_user_can('gravityforms_edit_forms'),
                'user_can_view' => current_user_can('gravityforms_view_forms'),
                'user_can_manage' => current_user_can('gravityforms_full_access')
            ]);
        }
        
        if (!$form_id || !$confirmation_id) {
            wp_die('Invalid parameters');
        }
        
        // Get the form
        $form = GFAPI::get_form($form_id);
        if (!$form) {
            wp_die('Form not found');
        }
        
        // Debug: Log the form data
        if (function_exists('qm_log')) {
            qm_log('GF Popup Confirmations: Form data for loading', [
                'form_id' => $form_id,
                'confirmation_id' => $confirmation_id,
                'has_confirmations' => isset($form['confirmations']),
                'confirmations_keys' => isset($form['confirmations']) ? array_keys($form['confirmations']) : 'none',
                'form_confirmations' => $form['confirmations'] ?? 'none'
            ]);
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
        
        // Debug: Log the confirmation ID search
        if (function_exists('qm_log')) {
            qm_log('GF Popup Confirmations: Confirmation ID search', [
                'original_id' => $confirmation_id,
                'possible_ids' => $possible_ids,
                'display_modal' => $display_modal
            ]);
        }
        
        // Debug: Log the load result
        if (function_exists('qm_log')) {
            qm_log('GF Popup Confirmations: Load result', [
                'form_confirmations' => isset($form['confirmations']) ? array_keys($form['confirmations']) : 'none',
                'confirmation_id' => $confirmation_id,
                'display_modal' => $display_modal,
                'has_display_modal' => isset($form['confirmations'][$confirmation_id]['displayModal'])
            ]);
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
        // Debug: Log all POST data to see what's being submitted
        if (function_exists('qm_log')) {
            qm_log('GF Popup Confirmations: Save function called', [
                'form_id' => $form['id'] ?? 'unknown',
                'is_new' => $is_new,
                'post_keys' => array_keys($_POST),
                'has_display_modal' => isset($_POST['_gform_setting_displayModal']),
                'display_modal_value' => $_POST['_gform_setting_displayModal'] ?? 'not_set',
                'has_confirmation_id' => isset($_POST['_gform_setting_id']),
                'confirmation_id' => $_POST['_gform_setting_id'] ?? 'not_set'
            ]);
        }
        
        // Check if we're saving confirmation settings
        if (!isset($_POST['_gform_setting_displayModal'])) {
            if (function_exists('qm_log')) {
                qm_log('GF Popup Confirmations: No displayModal setting found in POST data');
            }
            return;
        }
        
        $display_modal = boolval($_POST['_gform_setting_displayModal']);
        $query_string = sanitize_text_field($_POST['_gform_setting_queryString'] ?? '');
        
        // Debug: Log the confirmation save
        if (function_exists('qm_log')) {
            qm_log('GF Popup Confirmations: Saving confirmation popup setting', [
                'form_id' => $form['id'],
                'display_modal' => $display_modal,
                'is_new' => $is_new
            ]);
        }
        
        // Get the current confirmation being edited
        $confirmation_id = $_POST['_gform_setting_id'] ?? 'default';
        
        // Debug: Log the confirmation ID
        if (function_exists('qm_log')) {
            qm_log('GF Popup Confirmations: Confirmation ID for saving', [
                'confirmation_id' => $confirmation_id,
                'has_confirmations' => isset($form['confirmations']),
                'confirmation_keys' => isset($form['confirmations']) ? array_keys($form['confirmations']) : 'none'
            ]);
        }
        
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
            
            // Debug: Log the updated confirmation
            if (function_exists('qm_log')) {
                qm_log('GF Popup Confirmations: Updated confirmation data', [
                    'confirmation_id' => $confirmation_id,
                    'confirmation_data' => $form['confirmations'][$confirmation_id]
                ]);
            }
            
            // Save the form
            $result = GFAPI::update_form($form);
            
            // Debug: Log the save result
            if (function_exists('qm_log')) {
                qm_log('GF Popup Confirmations: Confirmation save result', [
                    'result' => $result,
                    'is_wp_error' => is_wp_error($result)
                ]);
            }
        } else {
            if (function_exists('qm_log')) {
                qm_log('GF Popup Confirmations: Confirmation not found for saving', [
                    'confirmation_id' => $confirmation_id,
                    'available_confirmations' => isset($form['confirmations']) ? array_keys($form['confirmations']) : 'none'
                ]);
            }
        }
    }
}

// Initialize the admin class
new GF_Popup_Confirmations_Admin(); 