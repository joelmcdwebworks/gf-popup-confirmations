<?php
declare(strict_types=1);

/**
 * Gravity Forms Popup Confirmations - Main Functionality
 * 
 * Provides popup modal functionality for Gravity Forms confirmations
 * with backwards compatibility for CSS class method.
 */

// Functionality based on the following:
// https://anythinggraphic.net/gravity-forms-notification-popup
// https://gist.github.com/davidwolfpaw/0fa37230c9dbb197ed4a8bbc1c7e9547
// https://gist.github.com/stevecordle/e8a27229c92bf4281156

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Check if a form should display popup confirmations
 * 
 * @param array $form The form object
 * @return bool Whether popup confirmations should be used
 */
function gf_popup_confirmations_should_use_popup($form): bool {
    // Check for new displayModal setting in confirmations
    $has_display_modal = false;
    $display_modal_confirmations = [];
    
    if (isset($form['confirmations']) && is_array($form['confirmations'])) {
        foreach ($form['confirmations'] as $confirmation_id => $confirmation) {
            if (isset($confirmation['displayModal']) && $confirmation['displayModal']) {
                $has_display_modal = true;
                $display_modal_confirmations[] = $confirmation_id;
            }
        }
    }
    
    // Fallback to legacy CSS class method
    $has_css_class = isset($form['cssClass']) && strpos($form['cssClass'], 'gf_confirmation_popup') !== false;
    
    return $has_display_modal || $has_css_class;
}

/**
 * Check if a specific confirmation should display as popup
 * 
 * @param array $confirmation The confirmation object
 * @param array $form The form object
 * @return bool Whether this confirmation should display as popup
 */
function gf_popup_confirmations_confirmation_should_popup($confirmation, $form): bool {
    // Check if this specific confirmation has displayModal enabled
    if (isset($confirmation['displayModal']) && $confirmation['displayModal']) {
        return true;
    }
    
    // Fallback to legacy CSS class method
    if (isset($form['cssClass']) && strpos($form['cssClass'], 'gf_confirmation_popup') !== false) {
        return true;
    }
    
    return false;
}

/**
 * Get the actual confirmation object that was selected by Gravity Forms
 * 
 * @param mixed $confirmation The confirmation passed to the filter
 * @param array $form The form object
 * @param array $entry The entry object
 * @return array|null The selected confirmation object or null if not found
 */
function gf_popup_confirmations_get_selected_confirmation($confirmation, $form, $entry): ?array {
    // If confirmation is already an array, return it
    if (is_array($confirmation)) {
        return $confirmation;
    }
    
    // If confirmation is a string, we need to determine which confirmation object it came from
    if (is_string($confirmation)) {
        return gf_popup_confirmations_find_confirmation_by_entry($form, $entry);
    }
    
    return null;
}

/**
 * Find the confirmation that should be displayed based on entry data
 * 
 * @param array $form The form object
 * @param array $entry The entry object
 * @return array|null The confirmation object or null if not found
 */
function gf_popup_confirmations_find_confirmation_by_entry($form, $entry): ?array {
    if (!isset($form['confirmations']) || !is_array($form['confirmations'])) {
        if (function_exists('qm_log')) {
            qm_log('GF Popup Confirmations: No confirmations found in form');
        } else {
            error_log('GF Popup Confirmations: No confirmations found in form');
        }
        return null;
    }
    
    // Debug logging
    if (function_exists('qm_log')) {
        qm_log('GF Popup Confirmations: Finding confirmation by entry', [
            'form_id' => $form['id'] ?? 'unknown',
            'confirmations_count' => count($form['confirmations']),
            'confirmation_keys' => array_keys($form['confirmations']),
            'entry_id' => $entry['id'] ?? 'unknown'
        ]);
    } else {
        error_log('GF Popup Confirmations: Finding confirmation by entry - Form ID: ' . ($form['id'] ?? 'unknown') . ', Confirmations: ' . count($form['confirmations']) . ', Entry ID: ' . ($entry['id'] ?? 'unknown'));
    }
    
    // Check conditional confirmations first
    foreach ($form['confirmations'] as $confirmation_id => $confirmation) {
        if (isset($confirmation['conditionalLogic']) && is_array($confirmation['conditionalLogic'])) {
            $evaluates_to_true = GFCommon::evaluate_conditional_logic($confirmation['conditionalLogic'], $form, $entry);
            
            if (function_exists('qm_log')) {
                qm_log('GF Popup Confirmations: Checking conditional confirmation', [
                    'confirmation_id' => $confirmation_id,
                    'has_conditional_logic' => true,
                    'evaluates_to_true' => $evaluates_to_true,
                    'has_display_modal' => isset($confirmation['displayModal']) ? $confirmation['displayModal'] : 'not_set'
                ]);
            }
            
            if ($evaluates_to_true) {
                return $confirmation;
            }
        }
    }
    
    // If no conditional confirmation matches, return the default confirmation
    $default_confirmation_id = isset($form['confirmations']['default']) ? 'default' : '0';
    
    if (function_exists('qm_log')) {
        qm_log('GF Popup Confirmations: Using default confirmation', [
            'default_confirmation_id' => $default_confirmation_id,
            'has_default_confirmation' => isset($form['confirmations'][$default_confirmation_id]),
            'default_has_display_modal' => isset($form['confirmations'][$default_confirmation_id]['displayModal']) ? $form['confirmations'][$default_confirmation_id]['displayModal'] : 'not_set'
        ]);
    }
    
    if (isset($form['confirmations'][$default_confirmation_id])) {
        return $form['confirmations'][$default_confirmation_id];
    }
    
    return null;
}

// Register additional scripts
add_action('wp_enqueue_scripts', function(): void {
    wp_register_script('trapfocus', plugin_dir_url(__FILE__) . '/js/trapfocus.js', ['jquery'], '1.0.0', true);
});

// Enqueue styles and scripts when popup confirmations are needed
add_action('gform_enqueue_scripts', function($form): void {
    global $this_function;
    
    if (gf_popup_confirmations_should_use_popup($form)) {
        wp_enqueue_style($this_function['style']);
        wp_enqueue_script($this_function['script']);
        wp_enqueue_script('trapfocus');
    }
});

// Turn off AJAX for forms with popup confirmations
add_filter('gform_form_args', function($args): array {
    $form_id = $args['form_id'];
    $form = GFAPI::get_form($form_id);
    
    if ($form && gf_popup_confirmations_should_use_popup($form)) {
        $args['ajax'] = false;
    }
    
    return $args;
}, 10, 1);

/**
 * Enhanced confirmation handling that preserves confirmation context
 * 
 * @param mixed $confirmation The confirmation object (can be string or array)
 * @param array $form The form object
 * @param array $entry The entry object
 * @param bool $ajax Whether this is an AJAX request
 * @return mixed Modified confirmation (same type as input)
 */
function redirect_with_confirmation($confirmation, $form, $entry, $ajax) {
    
    // Basic test log to see if function is called
    error_log('GF Popup Confirmations: redirect_with_confirmation function called');
    
    // Debug logging
    if (function_exists('qm_log')) {
        qm_log('GF Popup Confirmations: redirect_with_confirmation called', [
            'form_id' => $form['id'] ?? 'unknown',
            'confirmation_type' => is_array($confirmation) ? 'array' : 'string',
            'confirmation_value' => is_string($confirmation) ? substr($confirmation, 0, 100) : 'array',
            'entry_id' => $entry['id'] ?? 'unknown',
            'ajax' => $ajax
        ]);
    } else {
        // Fallback logging if qm_log is not available
        error_log('GF Popup Confirmations: redirect_with_confirmation called - Form ID: ' . ($form['id'] ?? 'unknown') . ', Confirmation Type: ' . (is_array($confirmation) ? 'array' : 'string') . ', Entry ID: ' . ($entry['id'] ?? 'unknown'));
    }
    
    // Get the actual confirmation object that was selected
    $selected_confirmation = gf_popup_confirmations_get_selected_confirmation($confirmation, $form, $entry);
    
    // Debug: Log form confirmations to see what's configured
    if (function_exists('qm_log')) {
        qm_log('GF Popup Confirmations: Form confirmations', [
            'form_confirmations' => $form['confirmations'] ?? 'none'
        ]);
    } else {
        error_log('GF Popup Confirmations: Form confirmations - ' . json_encode($form['confirmations'] ?? 'none'));
    }
    
    // Debug logging for selected confirmation
    if (function_exists('qm_log')) {
        qm_log('GF Popup Confirmations: Selected confirmation', [
            'selected_confirmation' => $selected_confirmation ? 'found' : 'null',
            'has_display_modal' => $selected_confirmation && isset($selected_confirmation['displayModal']) ? $selected_confirmation['displayModal'] : 'not_set',
            'confirmation_id' => $selected_confirmation && isset($selected_confirmation['id']) ? $selected_confirmation['id'] : 'unknown'
        ]);
    } else {
        // Fallback logging if qm_log is not available
        error_log('GF Popup Confirmations: Selected confirmation - Found: ' . ($selected_confirmation ? 'yes' : 'no') . ', Display Modal: ' . ($selected_confirmation && isset($selected_confirmation['displayModal']) ? ($selected_confirmation['displayModal'] ? 'true' : 'false') : 'not_set'));
    }
    
    // Check if this specific confirmation should display as popup
    if (!$selected_confirmation || !gf_popup_confirmations_confirmation_should_popup($selected_confirmation, $form)) {
        if (function_exists('qm_log')) {
            qm_log('GF Popup Confirmations: Not displaying as popup - returning confirmation as-is');
        } else {
            error_log('GF Popup Confirmations: Not displaying as popup - returning confirmation as-is');
        }
        return $confirmation; // Return the confirmation as-is
    }
    
    if (function_exists('qm_log')) {
        qm_log('GF Popup Confirmations: Displaying as popup - creating redirect');
    } else {
        error_log('GF Popup Confirmations: Displaying as popup - creating redirect');
    }
    
    // Handle URL parameters from legacy CSS class method
    $urlParams = '';
    if (isset($form['cssClass'])) {
        $formCSS = $form['cssClass'];
        
        // Check for double spacing and replace with single spacing
        if (strpos($formCSS, '  ') !== false) {
            $formCSS = str_replace('  ', ' ', $form['cssClass']);
        }
        
        $formCSS = explode(' ', $formCSS);
        
        // Handle URL params from CSS classes
        foreach ($formCSS as $class) {
            if (strpos($class, 'urlparam') !== false) {
                $urlParam = explode('-', $class);
                if (count($urlParam) >= 3) {
                    $urlParams .= '&' . $urlParam[1] . '=' . $urlParam[2];
                }
            }
        }
    }
    
    // Create popup confirmation redirect
    $message = urlencode(base64_encode($confirmation));
    $url = strtok($_SERVER['HTTP_REFERER'] ?? '', '?');
    
    return ['redirect' => $url . '?gfcnf=' . $message . $urlParams];
}

add_filter('gform_confirmation', 'redirect_with_confirmation', 10, 4);

// Include admin functionality
if (is_admin()) {
    require_once __DIR__ . '/admin.php';
}

?>

