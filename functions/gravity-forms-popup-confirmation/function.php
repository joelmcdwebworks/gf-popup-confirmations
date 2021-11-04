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

    if( strpos( $form['cssClass'], 'gf_confirmation_popup' ) !== false ) {

        wp_enqueue_style( $this_function['style'] );

        wp_enqueue_script( $this_function['script'] );

        wp_enqueue_script( 'trapfocus' );
    
    }

});

// Turn off AJAX for forms with gf_confirmation_popup, as this isn't compatible right now.

add_filter('gform_form_args', function( $args ) {

    $form_id = $args['form_id'];

    $form = GFAPI::get_form( $form_id );

    if( strpos( $form['cssClass'], 'gf_confirmation_popup' ) !== false ) {

        $args['ajax'] = false;

    }

    return $args;

}, 10, 1);

// Alter the form's confirmation to include the form and markup for popup.

add_filter( 'gform_pre_submission_filter', function ( $form ) {

    // Check for the gf_confirmation_popup class on the loaded form.

    if( strpos( $form['cssClass'], 'gf_confirmation_popup' ) !== false ) {

        // If the gf_confirmation_popup class is preseent on the loaded form, add
        // markup to the footer where the confirmation modal markup will be added.

        add_filter( 'wp_footer', function() {

            echo '<div id="overlay"></div>';

        });

        // Generate shortcode output for the current form for inclusion in the confirmation.

        $shortcode = '[gravityform id="' . $form['id'] . '" title="false" description="false"]';

        ob_start();

        echo do_shortcode( $shortcode );

        $html = str_replace(array("\r","\n"),'',trim(ob_get_clean()));

        // Add the form and popup markup to all confirmations for the form.

        if ( array_key_exists( 'confirmations', $form ) ) {

            foreach ( $form['confirmations'] as $key => $confirmation ) {

                $form['confirmations'][ $key ]['message'] = $html . '<div id="gform-notification" aria-modal="true" role="dialog"><a class="close">&times;</a><div class="message">' . $form['confirmations'][ $key ]['message'] . '</div><a class="button" rel="nofollow" href="javascript:void(0);">OK</a></div>';
            }

        }

    } // if

    return $form;

});   

?>