<?php
/**
 * Customizer control for a site-wide fallback featured image (posts only).
 *
 * @package concordia-core-functionality
 */

namespace CUI_Core\Includes\Media\Fallback_Featured_Image;

use WP_Customize_Manager;
use WP_Customize_Media_Control;

class Customizer {
	/**
	 * Hook into customize_register.
	 */
	public function __construct() {
		add_action( 'customize_register', [ $this, 'register' ] );
	}

	/**
	 * Register setting and control.
	 *
	 * @param WP_Customize_Manager $wp_customize Manager.
	 * @return void
	 */
	public function register( $wp_customize ) {
		$wp_customize->add_section(
			'cui_media_fallbacks',
			[
				'title'       => __( 'Media Fallbacks', 'concordia' ),
				'description' => __( 'Fallbacks used when content does not specify media.', 'concordia' ),
				'priority'    => 210,
			]
		);

		$wp_customize->add_setting(
			'cui_fallback_featured_image_id',
			[
				'type'              => 'option',
				'capability'        => 'manage_options',
				'sanitize_callback' => 'absint',
				'default'           => 0,
			]
		);

		$wp_customize->add_control(
			new WP_Customize_Media_Control(
				$wp_customize,
				'cui_fallback_featured_image_id',
				[
					'label'       => __( 'Blog Fallback Featured Image', 'concordia' ),
					'description' => __( 'Displayed on Posts when no featured image is set.', 'concordia' ),
					'section'     => 'cui_media_fallbacks',
					'mime_type'   => 'image',
				]
			)
		);
	}
}

