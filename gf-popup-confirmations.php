<?php

/**
 * Plugin Name:       Gravity Forms Popup Confirmations
 * Plugin URI:        https://mcdwebworks.com
 * Description:       Adds the ability for form submission confirmations to be displayed on modal popups.
 * Version:           0.0.17
 * Author:            Joel McDonald | McDonald Web Works
 * Author URI:        https://mcdwebworks.com
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       gf-popup-confirmations
 */

if( ! class_exists( 'GF_Popup_Confirmations' ) ) {

    class GF_Popup_Confirmations {

        public static function run() {
            
            $this_function = array(
                'slug' => '',
                'style' => '',
                'script' => '',
            );

            global $this_function;

            // Loop through all functions directories and include functions if active.

            $functions = scandir( plugin_dir_path(__FILE__) . 'functions' );

            foreach( $functions as $function ) {

                if( $function != '.' && $function != '..' ) {
                    
                    $this_function['slug'] = $function;
                    $this_function['style'] = $function . '-style';
                    $this_function['script'] = $function . '-script';

                    $file = 'functions/' . $this_function['slug'] . '/activator.php';

                    include_once $file;

                } //if

            } //foreach

            // Enable update checker for Github releases.

            require 'plugin-update-checker/plugin-update-checker.php';

            $myUpdateChecker = Puc_v4_Factory::buildUpdateChecker(
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

?>
