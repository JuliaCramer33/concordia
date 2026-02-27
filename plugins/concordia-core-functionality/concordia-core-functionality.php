<?php
/**
 * Plugin Name: Concordia Core Functionality
 * Description: Provides core functionality and custom features for the Concordia University Irvine website.
 * Version: 1.0.0
 * Author: Kanahoma
 * Author URI: https://kanahoma.com/
 *
 * This data is info that will be used across the site, no matter what the theme is.
 * Therefore this is implemented through a plugin
 *
 * @package concordia-core-functionality
 */

namespace CUI_Core;

if ( ! defined( 'WPINC' ) ) {
	die( 'YOU SHALL! NOT! PASS!' );
}

define( 'CUI_CORE_FUNC_PATH', plugin_dir_path( __FILE__ ) );
define( 'CUI_CORE_FUNC_URL', plugin_dir_url( __FILE__ ) );
define( 'CUI_REST_API_VERSION', '1.0.0' );
define( 'CUI_REST_API_NAMESPACE', 'CUI/v' . CUI_REST_API_VERSION );

use CUI_Core\Autoload\Init;

add_action( 'plugins_loaded', function() {
	require_once CUI_CORE_FUNC_PATH . 'autoload/autoloader.php'; // phpcs:ignore WordPressVIPMinimum.Files.IncludingFile.UsingCustomConstant

	// Initializing file is in includes/class-init.php. Refer to file for setup.
	new Init();
} );
