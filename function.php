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

/**
 * Check if we're in Gravity Forms preview mode
 * 
 * @return bool Whether we're in preview mode
 */
function gf_popup_confirmations_is_preview_mode(): bool {
    return (
        (isset($_GET['gf_page']) && $_GET['gf_page'] === 'preview')
    );
}

// Register additional scripts
add_action('wp_enqueue_scripts', function(): void {
    wp_register_script('trapfocus', plugin_dir_url(__FILE__) . '/js/trapfocus.js', ['jquery'], '1.0.0', true);
});

// Always enqueue popup script and style on Gravity Forms preview pages
add_action('wp_enqueue_scripts', function() {
    if (isset($_GET['gf_page']) && $_GET['gf_page'] === 'preview') {
        wp_enqueue_style('gf-popup-confirmations-style', plugin_dir_url(__FILE__) . 'style.css');
        wp_enqueue_script('gf-popup-confirmations-script', plugin_dir_url(__FILE__) . 'script.js', array('jquery'), false, true);
    }
});

// Alternative approach: Force load scripts on preview pages using wp_head
add_action('wp_head', function() {
    if (isset($_GET['gf_page']) && $_GET['gf_page'] === 'preview') {
        echo '<link rel="stylesheet" type="text/css" href="' . plugin_dir_url(__FILE__) . 'style.css" />';
    }
});

// Force load scripts on preview pages using wp_footer
add_action('wp_footer', function() {
    if (isset($_GET['gf_page']) && $_GET['gf_page'] === 'preview') {
        echo '<script type="text/javascript" src="' . plugin_dir_url(__FILE__) . 'script.js"></script>';
    }
});

// Hook into Gravity Forms' own script loading mechanism for preview mode
add_action('gform_enqueue_scripts', function($form) {
    if (isset($_GET['gf_page']) && $_GET['gf_page'] === 'preview') {
        wp_enqueue_style('gf-popup-confirmations-style', plugin_dir_url(__FILE__) . 'style.css');
        wp_enqueue_script('gf-popup-confirmations-script', plugin_dir_url(__FILE__) . 'script.js', array('jquery'), false, true);
    }
});

// Alternative: Use Gravity Forms' init scripts mechanism
add_action('gform_register_init_scripts', function($form) {
    if (isset($_GET['gf_page']) && $_GET['gf_page'] === 'preview') {
        wp_enqueue_style('gf-popup-confirmations-style', plugin_dir_url(__FILE__) . 'style.css');
        wp_enqueue_script('gf-popup-confirmations-script', plugin_dir_url(__FILE__) . 'script.js', array('jquery'), false, true);
    }
});

// Inject script and style directly into form output in preview mode
add_filter('gform_get_form_filter', function($form_string, $form) {
    if (isset($_GET['gf_page']) && $_GET['gf_page'] === 'preview') {
        $style_url = plugin_dir_url(__FILE__) . 'style.css';
        $script_url = plugin_dir_url(__FILE__) . 'script.js';
        
        $injected_assets = "
        <link rel='stylesheet' type='text/css' href='{$style_url}' />
        <script type='text/javascript' src='{$script_url}'></script>
        ";
        
        return $injected_assets . $form_string;
    }
    return $form_string;
}, 10, 2);

// Enqueue styles and scripts when popup confirmations are needed
add_action('gform_enqueue_scripts', function($form): void {
    global $function;
    
    if (gf_popup_confirmations_should_use_popup($form)) {
        wp_enqueue_style($function . '-style');
        wp_enqueue_script($function . '-script');
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
    
    // Handle preview mode differently
    if (gf_popup_confirmations_is_preview_mode()) {
        return gf_popup_confirmations_build_preview_redirect_url($confirmation, $form, $entry);
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



/**
 * Check if a form has problematic confirmation types when using legacy CSS class
 * 
 * @param array $form The form object
 * @return bool Whether the form has problematic confirmations
 */
function gf_popup_confirmations_has_problematic_confirmations($form): bool {
    // Only check forms that have the legacy CSS class
    if (!isset($form['cssClass']) || strpos($form['cssClass'], 'gf_confirmation_popup') === false) {
        return false;
    }
    
    // Check if any confirmations are of type 'page' or 'redirect'
    if (isset($form['confirmations']) && is_array($form['confirmations'])) {
        foreach ($form['confirmations'] as $confirmation_id => $confirmation) {
            if (isset($confirmation['type']) && in_array($confirmation['type'], ['page', 'redirect'])) {
                // Check if this confirmation has conditional logic that might be triggered
                $should_block = true;
                
                if (isset($confirmation['conditionalLogic']) && is_array($confirmation['conditionalLogic'])) {
                    $has_rules = isset($confirmation['conditionalLogic']['rules']) && 
                                is_array($confirmation['conditionalLogic']['rules']) && 
                                !empty($confirmation['conditionalLogic']['rules']);
                    
                    if (!$has_rules) {
                        $should_block = false; // No rules means this confirmation won't be used
                    }
                }
                
                if ($should_block) {
                    return true; // Found a problematic confirmation
                }
            }
        }
    }
    
    return false;
}

// Hook into validation to prevent submission ONLY if the selected confirmation is problematic
add_filter('gform_validation', function($validation_result) {
    $form = $validation_result['form'];

    // Only check if the legacy CSS class is present
    if (!isset($form['cssClass']) || strpos($form['cssClass'], 'gf_confirmation_popup') === false) {
        return $validation_result;
    }

    // Build a simulated entry array from POST data
    $entry = [];
    foreach ($form['fields'] as $field) {
        $field_id = is_object($field) ? $field->id : (isset($field['id']) ? $field['id'] : null);
        if ($field_id !== null) {
            $input_name = "input_{$field_id}";
            if (isset($_POST[$input_name])) {
                $entry[$field_id] = $_POST[$input_name];
            }
        }
    }

    // Use your helper to get the selected confirmation
    if (function_exists('gf_popup_confirmations_find_confirmation_by_entry')) {
        $selected_confirmation = gf_popup_confirmations_find_confirmation_by_entry($form, $entry);
        if ($selected_confirmation && isset($selected_confirmation['type']) && in_array($selected_confirmation['type'], ['page', 'redirect'])) {
            $validation_result['is_valid'] = false;
            $error_message = __(
                'Unable to submit. Confirmation type cannot be used with a form that has the class gf_confirmation_popup. Please contact the site administrator.',
                'gf-popup-confirmations'
            );
            // Add error to the first field
            if (!empty($validation_result['form']['fields'])) {
                $validation_result['form']['fields'][0]['failed_validation'] = true;
                $validation_result['form']['fields'][0]['validation_message'] = $error_message;
            }
            // Add a general form error
            add_filter('gform_form_validation_message', function($message, $form) use ($error_message) {
                return '<div class="validation_error">' . esc_html($error_message) . '</div>';
            }, 10, 2);
        }
    }

    return $validation_result;
}, 10, 1);

/**
 * Build redirect URL specifically for preview mode
 * 
 * @param mixed $confirmation The confirmation message
 * @param array $form The form object
 * @param array $entry The entry object
 * @return array Redirect array
 */
function gf_popup_confirmations_build_preview_redirect_url($confirmation, $form, $entry): array {
    // Start with existing preview parameters
    $params = [];
    if (isset($_GET['gf_page'])) $params['gf_page'] = $_GET['gf_page'];
    if (isset($_GET['id'])) $params['id'] = $_GET['id'];
    
    // Handle URL parameters from confirmation settings (queryString field)
    $selected_confirmation = gf_popup_confirmations_get_selected_confirmation($confirmation, $form, $entry);
    if ($selected_confirmation && isset($selected_confirmation['queryString']) && !empty($selected_confirmation['queryString'])) {
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
                $params[$key] = $value;
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
                    $params[$key] = $processed_value;
                }
            }
        }
    }
    
    // Add/replace the gfcnf param
    $params['gfcnf'] = urlencode(base64_encode($confirmation));
    
    // Build the URL (always relative to site root)
    $final_url = add_query_arg($params, home_url('/'));
    return ['redirect' => $final_url];
}

