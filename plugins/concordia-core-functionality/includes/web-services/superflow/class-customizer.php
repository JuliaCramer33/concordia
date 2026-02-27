<?php
/**
 * Manage Superflow customizer setup
 *
 * @package concordia-core-functionality
 */

namespace CUI_Core\Includes\Web_Services\Superflow;

/**
 * Customizer
 */
class Customizer {
	/**
	 * Using construct method to add any actions and filters
	 */
	public function __construct() {
		add_action( 'customize_register', [ $this, 'fields' ] );
	}

	/**
	 * Add the setting and controls for Superflow script injection
	 *
	 * @param \WP_Customize_Manager $wp_customize WP Customize instance.
	 *
	 * @return void
	 */
	public function fields( $wp_customize ) {
		$wp_customize->add_section( 'superflow', [
			'title' => 'Superflow',
			'panel' => 'web_services_integration',
		] );

		$wp_customize->add_setting( 'superflow_status', [
			'type' => 'option',
		] );

		$wp_customize->add_control( 'superflow_status', [
			'label'       => __( 'Enable Superflow' ),
			'description' => __( 'Toggle Superflow script output site-wide.' ),
			'section'     => 'superflow',
			'type'        => 'checkbox',
		] );

		$wp_customize->add_setting( 'superflow_head', [
			'type' => 'option',
		] );

		$wp_customize->add_control( 'superflow_head', [
			'label'       => __( 'Head Snippet' ),
			'description' => __( 'Paste the Superflow snippet to output in the <head> on every page.' ),
			'section'     => 'superflow',
			'type'        => 'textarea',
		] );
	}
}


