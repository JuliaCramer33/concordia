<?php
/**
 * Manage VWO customizer setup.
 *
 * @category   Class
 * @package    concordia-core-functionality
 * @subpackage vwo
 * @author     Kanahoma
 */

namespace CUI_Core\Includes\Web_Services\VWO;

/**
 * Customizer
 */
class Customizer {
	/**
	 * Using construct function to add any actions and filters
	 */
	public function __construct() {
		add_action( 'customize_register', [ $this, 'vwo_fields' ] );
	}

	/**
	 * Add the setting and controls pertaining to VWO
	 *
	 * @param WP_Customize_Manager $wp_customize WP Customize instance.
	 *
	 * @return void
	 */
	public function vwo_fields( $wp_customize ) {
		$wp_customize->add_section( 'vwo_testing', [
			'title' => 'VWO A/B Testing',
			'panel' => 'web_services_integration',
		] );

		$wp_customize->add_setting( 'global_vwo_status', [
			'type' => 'option',
		] );

		$wp_customize->add_control( 'global_vwo_status', [
			'label'       => __( 'Status' ),
			'description' => __( 'Toggle VWO A/B Testing use on/off.' ),
			'section'     => 'vwo_testing',
			'type'        => 'checkbox',
		] );

		$wp_customize->add_setting( 'global_vwo_account_id', [
			'type'              => 'option',
			'sanitize_callback' => 'wp_kses_post',
		] );

		$wp_customize->add_control( 'global_vwo_account_id', [
			'label'       => __( 'VWO Account ID' ),
			'description' => __( 'Insert the <strong>var account_id</strong> provided by VWO. It can be found in the VWO Async SmartCode.' ),
			'section'     => 'vwo_testing',
			'type'        => 'text',
		] );
	}
}
