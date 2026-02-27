<?php
/**
 * Plugin Name: GravityForms CUI Customizations
 * Description: Custom Gravity Forms integrations for the CUI site.
 * Version:     1.2.0
 * Author:      Kanahoma
 * Author URI:  https://kanahoma.com/
 * Text Domain: gravityforms-cui-customizations
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'GFCUI_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'GFCUI_PLUGIN_FILE', __FILE__ );

require_once GFCUI_PLUGIN_DIR . 'includes/class-gfcui-programs.php';
require_once GFCUI_PLUGIN_DIR . 'includes/class-gfcui-program-rfi.php';
require_once GFCUI_PLUGIN_DIR . 'includes/class-gfcui-start-terms.php';
require_once GFCUI_PLUGIN_DIR . 'includes/class-gfcui-salesforce-contact-inquiry.php';

/**
 * Initialize integrations and hooks.
 */
function gfcui_init_plugin() {
    // Instantiate main integration class.
    if ( class_exists( 'GFCUI_Programs' ) ) {
        // Only instantiate if Gravity Forms is present; class will check internally as well.
        new GFCUI_Programs();
    }
    if ( class_exists( 'GFCUI_Program_RFI' ) ) {
        new GFCUI_Program_RFI();
    }
    if ( class_exists( 'GFCUI_Start_Terms' ) ) {
        new GFCUI_Start_Terms();
    }
    if ( class_exists( 'GFCUI_Salesforce_Contact_Inquiry' ) ) {
        new GFCUI_Salesforce_Contact_Inquiry();
    }
}

add_action( 'plugins_loaded', 'gfcui_init_plugin' );
