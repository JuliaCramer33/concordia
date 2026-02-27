<?php
/**
 * Manage GTM customizer setup
 *
 * @package concordia-core-functionality
 */

namespace CUI_Core\Includes\Web_Services\GTM;

/**
 * Customizer
 */
class Customizer {
	/**
	 * Using construct method to add any actions and filters
	 */
	public function __construct() {
		add_action( 'customize_register', [ $this, 'gtm_fields' ] );
	}

	/**
	 * Add the setting and controls pertaining to GTM
	 *
	 * @param WP_Customize_Manager $wp_customize WP Customize instance.
	 *
	 * @return void
	 */
	public function gtm_fields( $wp_customize ) {
		$wp_customize->add_section( 'google_tag_manager', [
			'title' => 'Google Tag Manager',
			'panel' => 'web_services_integration',
		] );

		$wp_customize->add_setting( 'gtm_status', [
			'type' => 'option',
		] );

		$wp_customize->add_control( 'gtm_status', [
			'label'       => __( 'Status' ),
			'description' => __( 'Toggle GTM use on/off.' ),
			'section'     => 'google_tag_manager',
			'type'        => 'checkbox',
		] );

		$wp_customize->add_setting( 'gtm_ID', [
			'type' => 'option',
		] );

		$wp_customize->add_control( 'gtm_ID', [
			'label'       => __( 'Container ID' ),
			'description' => __( 'Insert the Container ID here. Example: <strong>GTM-XXXXXXX</strong>.' ),
			'section'     => 'google_tag_manager',
			'type'        => 'text',
		] );

		$wp_customize->add_setting( 'gtm_wp_hook', [
			'default' => 'wp_head',
			'type'    => 'option',
		] );

		$wp_customize->add_control( 'gtm_wp_hook', [
			'label'       => __( 'WP Hook' ),
			'description' => __( 'Hook to use to add GTM tag.' ),
			'section'     => 'google_tag_manager',
			'type'        => 'select',
			'choices'     => [
				'wp_head'     => __( 'wp_head' ),
				'head_begins' => __( 'head_begins' ),
			],
		] );
	}
}
