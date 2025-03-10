<?php

// Functionality based on the following:
// https://anythinggraphic.net/gravity-forms-notification-popup
// https://gist.github.com/davidwolfpaw/0fa37230c9dbb197ed4a8bbc1c7e9547
// https://gist.github.com/stevecordle/e8a27229c92bf4281156

// Register additional scripts

add_action( 'wp_enqueue_scripts', function() {

    wp_register_script( 'trapfocus', plugin_dir_url( __FILE__ ) . '/js/trapfocus.js', array('jquery'), false, true );

});

// Enqueue styles and scripts only when a form with the class of gf_confirmation_popup
// is loaded.

add_action( 'gform_enqueue_scripts', function( $form ) {

    global $this_function;

    // If no classes are set for the form, do nothing.

    if( ! isset( $form['cssClass'] ) ) {

        return;

    }

    if( strpos( $form['cssClass'], 'gf_confirmation_popup' ) !== false ) {

        wp_enqueue_style( $this_function['style'] );

        wp_enqueue_script( $this_function['script'] );

        wp_enqueue_script( 'trapfocus' );
    
    }

});

// Turn off AJAX for forms with gf_confirmation_popup, as this isn't currently compatible.

add_filter('gform_form_args', function( $args ) {   

    $form_id = $args['form_id'];

    $form = GFAPI::get_form( $form_id );

    // If no classes are set for the form, do nothing.

    if( ! isset( $form['cssClass'] ) ) {

        return $args;

    }     

    if( strpos( $form['cssClass'], 'gf_confirmation_popup' ) !== false ) {

        $args['ajax'] = false;

    }

    return $args;

}, 10, 1);

function redirect_with_confirmation( $confirmation, $form, $entry, $ajax ) {

    // If no CSS classes are being used, do nothing else
    // and return unaltered confirmation.

    if( ! isset( $form['cssClass']) ) {

        return $confirmation;

    }

    $formCSS = $form['cssClass'];

    // Check for double spacing. Replace with single spacing.

    if( strpos( $formCSS, '  ') !== false ) {

        $formCSS = str_replace('  ', ' ', $form['cssClass'] );

    }

    $formCSS = explode(' ', $formCSS ); // Make array.

    // Handle URL params.

    $urlParam = '';
    $urlParams = '&';

    foreach( $formCSS as $class ) {

        if( strpos( $class, 'urlparam') !== false ) {

            $urlParam = explode('-', $class);

            $urlParams .= $urlParam[1] . '=' . $urlParam[2] . '&';

        }

    }

    if( strlen( $urlParams ) < 2 ) {

        $urlParams = '';

    } else {

        $urlParams = substr( $urlParams, 0, strlen( $urlParams ) - 1) ;

    }

    // Check for controlling class. If class not used, don't alter the confirmation.    

    if( strpos( $form['cssClass'], 'gf_confirmation_popup' ) !== false ) {

        global $post;

        $message = urlencode( base64_encode( $confirmation ) );

        $url = strtok( $_SERVER['HTTP_REFERER'], '?' );

        $confirmation = array( 'redirect' => $url . '?gfcnf=' . $message . $urlParams );

    } // if

    return $confirmation;

} // redirect_with_confirmation()

add_filter( 'gform_confirmation', 'redirect_with_confirmation', 10, 4 );

?>
