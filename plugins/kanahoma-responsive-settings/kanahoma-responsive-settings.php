<?php
/**
 * Plugin Name: Kanahoma Responsive Settings
 * Description: Responsive settings for Gutenberg blocks (padding, margin, visibility, more).
 * Author: Kanahoma
 * Author URI: https://posty.studio
 * License: GPL-3.0
 * Version: 1.0.0
 * Requires at least: 6.5
 * Requires PHP: 8.1
 * Text Domain: kanahoma-responsive-settings
 *
 * @package kanahoma/responsive-settings
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

// Fallback autoload: load classes directly when Composer isn't installed.
if ( file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
    require __DIR__ . '/vendor/autoload.php';
} else {
    require __DIR__ . '/includes/class-setup.php';
    require __DIR__ . '/includes/class-assets.php';
    require __DIR__ . '/includes/class-block-renderer.php';
}

( new Kanahoma\Responsive_Settings\Setup() )->init();
