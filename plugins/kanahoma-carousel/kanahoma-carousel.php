<?php
/**
 * Plugin Name: Kanahoma Carousel
 * Description: Lightweight, accessible carousel block with bleed and responsive presets.
 * Version: 1.0.0
 * Author: Kanahoma
 * Text Domain: kanahoma
 * Requires at least: 6.0
 * Requires PHP: 7.4
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * Load plugin text domain for translations.
 */
function kanahoma_carousel_load_textdomain() {
    load_plugin_textdomain( 'kanahoma', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}
add_action( 'init', 'kanahoma_carousel_load_textdomain' );

/**
 * Server-side render callback for the carousel block.
 *
 * @param array    $attributes Block attributes.
 * @param string   $content    Block inner content (slides).
 * @param WP_Block $block      Block instance (provides inner blocks when needed).
 * @return string              Rendered HTML.
 */
function kanahoma_carousel_render( $attributes = [], $content = '', $block = null ) {
    $template = plugin_dir_path( __FILE__ ) . 'blocks/carousel/render.php';
    if ( ! file_exists( $template ) ) {
        return '';
    }
    ob_start();
    // phpcs:ignore WordPressVIPMinimum.Files.IncludingFile.UsingVariable
    include $template; // Uses $attributes and $content within the template; $template is built from plugin_dir_path and validated exists.
    return ob_get_clean();
}

/**
 * Server-side render callback for the post carousel block.
 *
 * @param array    $attributes Block attributes.
 * @param string   $content    Block inner content (slides).
 * @param WP_Block $block      Block instance (provides inner blocks when needed).
 * @return string              Rendered HTML.
 */
function kanahoma_post_carousel_render( $attributes = [], $content = '', $block = null ) {
    $template = plugin_dir_path( __FILE__ ) . 'blocks/post-carousel/render.php';
    if ( ! file_exists( $template ) ) {
        return '';
    }
    ob_start();
    // phpcs:ignore WordPressVIPMinimum.Files.IncludingFile.UsingVariable
    include $template; // Uses $attributes and $content within the template; $template is built from plugin_dir_path and validated exists.
    return ob_get_clean();
}

/**
 * Register the Kanahoma Carousel block from metadata.
 */
function kanahoma_carousel_register_block() {
    $dir = plugin_dir_path( __FILE__ ) . 'blocks/carousel';
    if ( file_exists( $dir . '/block.json' ) ) {
        register_block_type_from_metadata( $dir, [
            'render_callback' => 'kanahoma_carousel_render',
        ] );
    }
}
add_action( 'init', 'kanahoma_carousel_register_block' );

/**
 * Register the Kanahoma Post Carousel block from metadata.
 */
function kanahoma_post_carousel_register_block() {
    $dir = plugin_dir_path( __FILE__ ) . 'blocks/post-carousel';
    if ( file_exists( $dir . '/block.json' ) ) {
        register_block_type_from_metadata( $dir, [
            'render_callback' => 'kanahoma_post_carousel_render',
        ] );
    }
}
add_action( 'init', 'kanahoma_post_carousel_register_block' );
