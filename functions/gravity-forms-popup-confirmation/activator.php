<?php

$is_active = true; // Set to false to deactivate the function.

if( $is_active ) {

    // Register style and script.

    add_action( 'wp_enqueue_scripts', function() {

        global $this_function;

        wp_register_style( $this_function['style'],  plugin_dir_url( __FILE__ ) . 'style.css' );

        wp_register_script( $this_function['script'], plugin_dir_url( __FILE__ ) . 'script.js', array('jquery', 'trapfocus'), false, true ); 

    } );
    
    include_once 'function.php';

} // if

?>