<?php
/**
 * Manage Superflow Front-end integration
 *
 * @package concordia-core-functionality
 */

namespace CUI_Core\Includes\Web_Services\Superflow;

/**
 * Integration
 */
class Integration {
	/**
	 * Using construct method to add any actions and filters
	 */
	public function __construct() {
		add_action( 'wp_head', [ $this, 'output_head_snippet' ], 1 );
	}

	/**
	 * Output the Superflow snippet in the head when enabled.
	 *
	 * @return void
	 */
	public function output_head_snippet() {
		$status = get_option( 'superflow_status' );
		if ( ! $status ) {
			return;
		}

		$snippet = get_option( 'superflow_head' );
		if ( empty( $snippet ) ) {
			return;
		}

		// Intentionally echo raw admin-provided snippet.
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo "\n<!-- Superflow -->\n" . $snippet . "\n<!-- /Superflow -->\n";
	}
}


