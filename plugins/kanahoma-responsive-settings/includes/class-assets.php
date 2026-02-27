<?php

namespace Kanahoma\Responsive_Settings;

class Assets {
	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'enqueue_block_editor_assets', array( $this, 'register_block_editor_assets' ) );
	}

	/**
	 * Registers and enqueues a style.
	 *
	 * @param string $name Name of the style.
	 * @param array  $dependencies Array of dependencies for the style.
	 */
	private function add_style( $name, $dependencies = array() ) {
        wp_enqueue_style(
            "kanahoma-responsive-settings-{$name}-style",
            KANAHOMA_RESPONSIVE_SETTINGS_BUILD_URL . $name . '.css',
            $dependencies,
            KANAHOMA_RESPONSIVE_SETTINGS_VERSION
        );
	}

	/**
	 * Registers and enqueues a script.
	 *
	 * @param string $name Name of the script.
	 * @param array  $l10n Array of parameters to add to the script.
	 * @param array  $dependencies Array of dependencies for the script.
	 */
    private function add_script( $name, $l10n = array(), $dependencies = array() ) {
        // Whitelist expected entry points to satisfy VIP rules about dynamic includes.
        $allowed_entries = array( 'editor' );
        if ( ! in_array( $name, $allowed_entries, true ) ) {
            return;
        }

        $asset_file = array(
            'dependencies' => array(),
            'version'      => KANAHOMA_RESPONSIVE_SETTINGS_VERSION,
        );

        $asset_path = KANAHOMA_RESPONSIVE_SETTINGS_BUILD_PATH . $name . '.asset.php';
        if ( is_readable( $asset_path ) ) {
            // Safe: path built from constant + whitelisted filename.
            $loaded = require $asset_path; // phpcs:ignore WordPressVIPMinimum.Files.IncludingFile.UsingVariable
            if ( is_array( $loaded ) ) {
                $asset_file = $loaded;
            }
        }

        wp_register_script(
            "kanahoma-responsive-settings-{$name}-script",
            KANAHOMA_RESPONSIVE_SETTINGS_BUILD_URL . $name . '.js',
			array_merge( $asset_file['dependencies'], $dependencies ),
			$asset_file['version'],
			true
		);

		if ( ! empty( $l10n ) && is_array( $l10n ) ) {
            wp_localize_script( "kanahoma-responsive-settings-{$name}-script", 'kanahomaResponsiveSettings', $l10n );
		}

        wp_enqueue_script( "kanahoma-responsive-settings-{$name}-script" );
	}

	/**
	 * Registers and enqueues block editor assets.
	 */
	public function register_block_editor_assets() {
		// Localize breakpoints to the editor
		$defaults = array(
			'mobile'  => 0,
			'tablet'  => 768,
			'desktop' => 1024,
		);
		$bps = apply_filters( 'kanahoma_responsive_breakpoints', $defaults );

		$this->add_script( 'editor', array( 'breakpoints' => $bps ) );
		$this->add_style( 'editor' );

		// Inline the same responsive CSS used on the frontend into the editor for accurate preview
		if ( class_exists( '\\Kanahoma\\Responsive_Settings\\Responsive_Renderer' ) ) {
			$renderer = new \Kanahoma\Responsive_Settings\Responsive_Renderer();
			$css = $renderer->get_css();
			if ( is_string( $css ) && $css !== '' ) {
				wp_add_inline_style( 'kanahoma-responsive-settings-editor-style', $css );
			}
		}
	}
}
