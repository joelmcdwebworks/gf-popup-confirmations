<?php

/**
 * Plugin Name:       Gravity Forms Popup Confirmations
 * Plugin URI:        https://mcdwebworks.com
 * Description:       Adds the ability for form submission confirmations to be displayed in modal popups.
 * Version:           2.0.0
 * Author:            Joel McDonald | McDonald Web Works
 * Author URI:        https://mcdwebworks.com
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       gf-popup-confirmations
 * Domain Path:       /languages
 */

// Enable update checker for Github releases.
require 'plugin-update-checker/plugin-update-checker.php';
use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

if( ! class_exists( 'GF_Popup_Confirmations' ) ) {

    class GF_Popup_Confirmations {

        public static function run() {

            $function = 'gf-popup-confirmations';

            global $function;

            // Load text domain for internationalization
            add_action('init', function() {
                load_plugin_textdomain('gf-popup-confirmations', false, dirname(plugin_basename(__FILE__)) . '/languages');
            });

            // Register style and script.

            add_action( 'wp_enqueue_scripts', function( $function) {

                wp_register_style( $function . '-style',  plugin_dir_url( __FILE__ ) . 'style.css' );

                wp_register_script( $function . '-script', plugin_dir_url( __FILE__ ) . 'script.js', array('jquery', 'trapfocus'), false, true ); 

            } );
            
            include_once 'function.php';

            $myUpdateChecker = PucFactory::buildUpdateChecker(
                'https://github.com/joelmcdwebworks/gf-popup-confirmations/',
                __FILE__,
                'gf-popup-confirmations'
            );         

            //Set the branch that contains the stable release.
            $myUpdateChecker->setBranch('main');

            $myUpdateChecker->getVcsApi()->enableReleaseAssets();             

        } // run()

    }

    GF_Popup_Confirmations::run();

 }
