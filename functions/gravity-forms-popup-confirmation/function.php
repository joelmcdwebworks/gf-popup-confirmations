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
        return null;
    }
    
    // Check conditional confirmations first
    foreach ($form['confirmations'] as $confirmation_id => $confirmation) {
        if (isset($confirmation['conditionalLogic']) && is_array($confirmation['conditionalLogic'])) {
            $evaluates_to_true = GFCommon::evaluate_conditional_logic($confirmation['conditionalLogic'], $form, $entry);
            
            if ($evaluates_to_true) {
                return $confirmation;
            }
        }
    }
    
    // If no conditional confirmation matches, return the default confirmation
    $default_confirmation_id = isset($form['confirmations']['default']) ? 'default' : '0';
    
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
    // Get the actual confirmation object that was selected
    $selected_confirmation = gf_popup_confirmations_get_selected_confirmation($confirmation, $form, $entry);
    
    // Check if this specific confirmation should display as popup
    if (!$selected_confirmation || !gf_popup_confirmations_confirmation_should_popup($selected_confirmation, $form)) {
        return $confirmation; // Return the confirmation as-is
    }
    
    // Build URL parameters
    $url_params = [];
    
    // Handle URL parameters from confirmation settings (queryString field)
    if (isset($selected_confirmation['queryString']) && !empty($selected_confirmation['queryString'])) {
        $query_string = $selected_confirmation['queryString'];
        
        // Process merge tags in the query string
        $processed_query_string = GFCommon::replace_variables($query_string, $form, $entry);
        
        // Parse the URL parameters string
        $param_pairs = explode('&', $processed_query_string);
        foreach ($param_pairs as $pair) {
            $key_value = explode('=', $pair, 2);
            if (count($key_value) === 2) {
                $key = trim($key_value[0]);
                $value = trim($key_value[1]);
                $url_params[$key] = $value;
            }
        }
    }
    
    // Handle URL parameters from legacy CSS class method (backwards compatibility)
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
                    $key = $urlParam[1];
                    $value = $urlParam[2];
                    
                    // Process merge tags if present
                    $processed_value = GFCommon::replace_variables($value, $form, $entry);
                    $url_params[$key] = $processed_value;
                }
            }
        }
    }
    
    // Build the final URL
    $message = urlencode(base64_encode($confirmation));
    $url = strtok($_SERVER['HTTP_REFERER'] ?? '', '?');
    
    // Start with the base URL and gfcnf parameter
    $final_url = $url . '?gfcnf=' . $message;
    
    // Add URL parameters
    if (!empty($url_params)) {
        $param_string = http_build_query($url_params);
        $final_url .= '&' . $param_string;
    }
    
    return ['redirect' => $final_url];
}

add_filter('gform_confirmation', 'redirect_with_confirmation', 10, 4);

// Include admin functionality
if (is_admin()) {
    require_once __DIR__ . '/admin.php';
}

