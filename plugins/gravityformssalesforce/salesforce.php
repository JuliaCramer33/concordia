<?php
/*
Plugin Name: Gravity Forms Salesforce Add-On
Plugin URI: https://gravityforms.com
Description: An add-on to allow your Gravity Forms to map fields to a Salesforce object.
Version: 1.1.0
Author: Gravity Forms
Author URI: https://gravityforms.com
License: GPL-3.0+
Text Domain: gravityformssalesforce
Domain Path: /languages

------------------------------------------------------------------------
Copyright 2024-2025 Rocketgenius Inc.

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see http://www.gnu.org/licenses.

*/

defined( 'ABSPATH' ) || die();

// Defines the current version of the Gravity Forms Salesforce Add-On.
define( 'GF_SALESFORCE_VERSION', '1.1.0' );

// Defines the minimum version of Gravity Forms required to run Gravity Forms Salesforce Add-On.
define( 'GF_SALESFORCE_MIN_GF_VERSION', '2.7' );

// Defines the API version.
define( 'GF_SALESFORCE_API_VERSION', 'v58.0' );

// After Gravity Forms is loaded, load the Add-On.
add_action( 'gform_loaded', array( 'GF_Salesforce_Bootstrap', 'load_addon' ), 5 );

/**
 * Loads the Gravity Forms Salesforce Add-On.
 *
 * Includes the main class and registers it with GFAddOn.
 *
 * @since 1.0
 */
class GF_Salesforce_Bootstrap {

	/**
	 * Loads the required files.
	 *
	 * @since  1.0
	 */
	public static function load_addon() {

		// Requires the class file.
		require_once plugin_dir_path( __FILE__ ) . '/class-gf-salesforce.php';

		// Registers the class name with GFAddOn.
		GFAddOn::register( 'GF_Salesforce' );
	}

}

/**
 * Returns an instance of the GF_Salesforce class
 *
 * @since  1.0
 *
 * @return GF_Salesforce|bool An instance of the GF_Salesforce class
 */
function gf_salesforce() {
	return class_exists( 'GF_Salesforce' ) ? GF_Salesforce::get_instance() : false;
}
