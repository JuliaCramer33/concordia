<?php

namespace Kanahoma\Responsive_Settings;

class Setup {
	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->set_constants();
	}

	/**
	 * Set constants.
	 */
	private function set_constants() {
        define( 'KANAHOMA_RESPONSIVE_SETTINGS_VERSION', '1.0.0' );
        define( 'KANAHOMA_RESPONSIVE_SETTINGS_SLUG', 'kanahoma-responsive-settings' );
        define( 'KANAHOMA_RESPONSIVE_SETTINGS_PATH', plugin_dir_path( __DIR__ ) );
        define( 'KANAHOMA_RESPONSIVE_SETTINGS_BUILD_PATH', KANAHOMA_RESPONSIVE_SETTINGS_PATH . 'build/' );
        define( 'KANAHOMA_RESPONSIVE_SETTINGS_BLOCKS_PATH', KANAHOMA_RESPONSIVE_SETTINGS_PATH . 'build/blocks/' );
        define( 'KANAHOMA_RESPONSIVE_SETTINGS_TEMPLATES_PATH', KANAHOMA_RESPONSIVE_SETTINGS_PATH . 'templates/' );
        define( 'KANAHOMA_RESPONSIVE_SETTINGS_LANGUAGES_PATH', KANAHOMA_RESPONSIVE_SETTINGS_PATH . 'languages/' );
        define( 'KANAHOMA_RESPONSIVE_SETTINGS_BUILD_URL', plugin_dir_url( __DIR__ ) . 'build/' );
	}

	/**
	 * Initialize.
	 */
	public function init() {
        new Assets();
        new Block_Renderer();

		// Enforce standard breakpoints site-wide unless explicitly overridden later.
		// This keeps behavior consistent (desktop applies to >=1024px).
		add_filter( 'kanahoma_responsive_breakpoints', function () {
			return array(
				'mobile'  => 0,
				'tablet'  => 768,
				'desktop' => 1024,
			);
		}, 9999 );
		// Add responsive padding renderer (non-destructive; only applies when values exist).
		if ( file_exists( __DIR__ . '/class-responsive-renderer.php' ) ) {
			require_once __DIR__ . '/class-responsive-renderer.php';
            new Responsive_Renderer();
		}
        // Blocks removed; only editor enhancements remain.
	}
}
