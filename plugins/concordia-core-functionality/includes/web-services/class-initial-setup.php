<?php
/**
 * Holds our class for Initial Setup of the Web Services integration
 * Customizer and Functionality for the site's External Web services
 *
 * Functionality:
 * - Adds settings/options to functionality
 * - Adds the markup via theme specific hook
 *
 * @category   Class
 * @package    CUI_Core\Includes\Web_Services
 * @author     Kanahoma
 */

namespace CUI_Core\Includes\Web_Services;

/**
 * Web Services Integration | Set up for WP Customizer
 */
class Initial_Setup {
	/**
	 * Using construct function to add any actions and filters associated with the CPT
	 */
	public function __construct() {
		// Customizer hooks.
		add_action( 'customize_register', [ $this, 'register_section' ] );
	}

	/**
	 * Add custom section to the WP Customizer for this class
	 *
	 * @param WP_Customize_Manager $wp_customize WP Customize instance.
	 *
	 * @return void
	 */
	public function register_section( $wp_customize ) {
		// "Custom Scripts" section
		$wp_customize->add_panel( 'web_services_integration', [
			'title'    => __( 'Web Services Integration' ),
			'priority' => 200,
		] );
	}
}
