<?php
/**
 * Plugin Name: Kanahoma Block Utilities
 * Description: Small Gutenberg utilities (e.g., Equal-height Columns).
 * Author: Kanahoma
 * Version: 0.1.0
 * Requires at least: 6.5
 * Requires PHP: 8.0
 * Text Domain: kanahoma-block-utilities
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'KANAHOMA_BU_PATH', plugin_dir_path( __FILE__ ) );
define( 'KANAHOMA_BU_URL', plugin_dir_url( __FILE__ ) );

// Load translations (safe if folder is missing).
add_action( 'init', function () {
    load_plugin_textdomain( 'kanahoma-block-utilities', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
} );

// Enqueue editor script and shared styles.
add_action( 'enqueue_block_editor_assets', function () {
    $editor_js   = KANAHOMA_BU_PATH . 'editor.js';
    $style_css   = KANAHOMA_BU_PATH . 'style.css';
    $editor_ver  = file_exists( $editor_js ) ? (string) filemtime( $editor_js ) : '1.0.0';
    $style_ver   = file_exists( $style_css ) ? (string) filemtime( $style_css ) : '1.0.0';

    wp_enqueue_script(
        'kanahoma-block-utilities-editor',
        KANAHOMA_BU_URL . 'editor.js',
        array( 'wp-blocks', 'wp-hooks', 'wp-compose', 'wp-element', 'wp-components', 'wp-block-editor', 'wp-i18n' ),
        $editor_ver,
        true
    );

    // Editor also needs the utility CSS for accurate preview.
    wp_enqueue_style(
        'kanahoma-block-utilities-style',
        KANAHOMA_BU_URL . 'style.css',
        array(),
        $style_ver
    );
} );

// Frontend styles (small, safe).
add_action( 'wp_enqueue_scripts', function () {
    $style_css = KANAHOMA_BU_PATH . 'style.css';
    $style_ver = file_exists( $style_css ) ? (string) filemtime( $style_css ) : '1.0.0';
    wp_enqueue_style(
        'kanahoma-block-utilities-style',
        KANAHOMA_BU_URL . 'style.css',
        array(),
        $style_ver
    );
} );

// SSR: Add class on Columns when attribute is enabled.
add_filter( 'render_block', function ( $content, $block ) {
    if ( ! is_string( $content ) || empty( $block['blockName'] ) ) {
        return $content;
    }
    if ( $block['blockName'] !== 'core/columns' ) {
        return $content;
    }
    $attrs = $block['attrs'] ?? array();
    if ( empty( $attrs['kanahomaEqualHeight'] ) ) {
        return $content;
    }

    $processor = new WP_HTML_Tag_Processor( $content );
    if ( $processor->next_tag() ) {
        $processor->add_class( 'has-equal-height' );
        $updated = $processor->get_updated_html();
        return is_string( $updated ) ? $updated : $content;
    }
    return $content;
}, 10, 2 );


