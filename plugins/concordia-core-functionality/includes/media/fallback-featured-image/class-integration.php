<?php
/**
 * Output fallbacks for featured images (posts only).
 *
 * @package concordia-core-functionality
 */

namespace CUI_Core\Includes\Media\Fallback_Featured_Image;

class Integration {
	/**
	 * Hook filters.
	 */
	public function __construct() {
		add_filter( 'render_block_core/post-featured-image', [ $this, 'block_fallback' ], 10, 2 );
		add_filter( 'post_thumbnail_html', [ $this, 'classic_fallback' ], 10, 4 );
	}

	/**
	 * Resolve fallback attachment ID.
	 *
	 * @return int
	 */
	private function get_fallback_id() {
		$id = absint( get_option( 'cui_fallback_featured_image_id', 0 ) );
		/** This filter allows overriding the fallback image ID. */
		return (int) apply_filters( 'cui_fallback_featured_image_id', $id );
	}

	/**
	 * Block editor fallback for core/post-featured-image.
	 *
	 * @param string $html  Existing HTML.
	 * @param array  $block Block data.
	 * @return string
	 */
	public function block_fallback( $html, $block ) {
		$post_id = isset( $block['context']['postId'] ) ? (int) $block['context']['postId'] : 0;
		if ( $post_id && ( 'post' !== get_post_type( $post_id ) || has_post_thumbnail( $post_id ) ) ) {
			return $html;
		}

		$fallback_id = $this->get_fallback_id();
		if ( ! $fallback_id ) {
			return $html;
		}

		$src = wp_get_attachment_image_url( $fallback_id, 'large' );
		if ( ! $src ) {
			return $html;
		}

		$alt = $post_id ? get_the_title( $post_id ) : get_bloginfo( 'name' );
		return sprintf(
			'<figure class="%1$s"><img src="%2$s" alt="%3$s" loading="lazy" decoding="async" /></figure>',
			esc_attr( 'wp-block-post-featured-image is-fallback' ),
			esc_url( $src ),
			esc_attr( $alt )
		);
	}

	/**
	 * Classic fallback for the_post_thumbnail().
	 *
	 * @param string      $html     Existing HTML.
	 * @param int         $post_id  Post ID.
	 * @param int|string  $thumb_id Thumbnail ID.
	 * @param string|int[] $size    Size.
	 * @return string
	 */
	public function classic_fallback( $html, $post_id, $thumb_id, $size ) {
		if ( $html || 'post' !== get_post_type( $post_id ) ) {
			return $html;
		}
		$fallback_id = $this->get_fallback_id();
		if ( ! $fallback_id ) {
			return $html;
		}
		$src = wp_get_attachment_image_url( $fallback_id, is_string( $size ) ? $size : 'large' );
		if ( ! $src ) {
			return $html;
		}
		$alt = get_the_title( $post_id ) ?: get_bloginfo( 'name' );
		return sprintf(
			'<img class="attachment-%1$s size-%1$s wp-post-image is-fallback" src="%2$s" alt="%3$s" loading="lazy" decoding="async" />',
			esc_attr( is_string( $size ) ? $size : 'large' ),
			esc_url( $src ),
			esc_attr( $alt )
		);
	}
}
