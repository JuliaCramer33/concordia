<?php
/**
 * Functions and definitions
 *
 * @link https://developer.wordpress.org/themes/basics/theme-functions/
 *
 * @package concordia
 * @since 1.0.0
 */

/**
 * Setup theme supports and editor styles.
 */
function concordia_setup() {
    add_theme_support( 'wp-block-styles' );
    add_theme_support( 'responsive-embeds' );
    add_theme_support( 'editor-styles' );
    add_theme_support( 'appearance-tools' );
    add_theme_support( 'title-tag' );
    add_theme_support( 'post-thumbnails' );

    add_editor_style( 'css/prod/gutenberg-editor-styles.css' );
}
add_action( 'after_setup_theme', 'concordia_setup' );

/**
 * Enqueue front and nav assets (keep core block styles).
 */
function concordia_assets() {
    // Adobe Fonts (Typekit)
    wp_enqueue_style( 'cui-typekit', 'https://use.typekit.net/agh7kkr.css', array(), null );

    $css_rel = 'css/prod/global.css';
    $css_abs = get_theme_file_path( $css_rel );
    if ( file_exists( $css_abs ) && filesize( $css_abs ) > 0 ) {
        wp_enqueue_style( 'cui-front', get_theme_file_uri( $css_rel ), array(), filemtime( $css_abs ) );
    }

    $js_rel = 'js/prod/front-end.js';
    $js_abs = get_theme_file_path( $js_rel );
    if ( file_exists( $js_abs ) && filesize( $js_abs ) > 0 ) {
        wp_enqueue_script( 'cui-nav', get_theme_file_uri( $js_rel ), array(), filemtime( $js_abs ), true );
    }
}
add_action( 'wp_enqueue_scripts', 'concordia_assets' );

/**
 * Editor-only assets (JS parity for editor behaviors).
 */
function concordia_editor_assets() {
    $ver = wp_get_theme()->get( 'Version' );
    // Adobe Fonts (Typekit) in editor
    wp_enqueue_style( 'cui-typekit', 'https://use.typekit.net/agh7kkr.css', array(), null );
    $editor_js_rel = 'js/prod/gutenberg-editor.js';
    $editor_js_abs = get_theme_file_path( $editor_js_rel );
    if ( file_exists( $editor_js_abs ) && filesize( $editor_js_abs ) > 0 ) {
        // Ensure core editor packages are loaded before our bundle
        $deps = array(
            'wp-blocks',
            'wp-element',
            'wp-components',
            'wp-data',
            'wp-edit-post',
            'wp-block-editor',
            'wp-plugins',
            'wp-core-data',
        );
        // Use file mtime for reliable cache-busting in the editor
        $built_ver = filemtime( $editor_js_abs );
        wp_enqueue_script( 'cui-editor', get_theme_file_uri( $editor_js_rel ), $deps, $built_ver, true );
    }
}
add_action( 'enqueue_block_editor_assets', 'concordia_editor_assets' );

/**
 * Ensure block binding sources registered server-side are also registered in the post editor.
 * Some WP versions preload only in Site Editor; this mirrors core's preload approach.
 */
function concordia_register_block_binding_sources_in_editor() {
	if ( ! function_exists( 'get_all_registered_block_bindings_sources' ) ) {
		return;
	}
	$sources = get_all_registered_block_bindings_sources();
	if ( empty( $sources ) ) {
		return;
	}
	$filtered = array();
	foreach ( $sources as $source ) {
		$filtered[] = array(
			'name'        => isset( $source->name ) ? $source->name : '',
			'label'       => isset( $source->label ) ? $source->label : '',
			'usesContext' => isset( $source->uses_context ) ? $source->uses_context : array(),
		);
	}
	$script = sprintf(
		'for ( const source of %s ) { if ( window.wp && wp.blocks && wp.blocks.registerBlockBindingsSource ) { wp.blocks.registerBlockBindingsSource( source ); } }',
		wp_json_encode( $filtered )
	);
	wp_add_inline_script( 'wp-blocks', $script, 'after' );
}
add_action( 'enqueue_block_editor_assets', 'concordia_register_block_binding_sources_in_editor', 100 );

/**
 * Resource hints for Adobe Fonts
 */
function concordia_resource_hints( $hints, $relation_type ) {
    if ( 'preconnect' === $relation_type ) {
        $hints[] = 'https://use.typekit.net';
        $hints[] = 'https://p.typekit.net';
    }
    return $hints;
}
add_filter( 'wp_resource_hints', 'concordia_resource_hints', 10, 2 );

/**
 * Load theme includes
 */
require_once get_theme_file_path( 'inc/patterns.php' );
require_once get_theme_file_path( 'inc/cpt/loader.php' );
require_once get_theme_file_path( 'inc/taxonomy/loader.php' );
require_once get_theme_file_path( 'inc/post-type-news.php' );
require_once get_theme_file_path( 'inc/block-bindings/loader.php' );
require_once get_theme_file_path( 'inc/integrations/the-events-calendar.php' );

/**
 * Register block styles.
 */
add_action( 'init', function () {
    // Button: No Animation (uses CSS utility to neutralize sweep and fade text to gold)
    if ( function_exists( 'register_block_style' ) ) {
        register_block_style(
            'core/button',
            array(
                'name'  => 'no-button-anim',
                'label' => __( 'No Animation (Gold Hover)', 'concordia' ),
            )
        );
    }
}, 20 );

// Removed legacy editor-panel enqueue (bindings now bundled into editor JS).

/**
 * Align responsive breakpoints with Kanahoma Responsive Settings plugin.
 * Tablet starts at 768px; Desktop starts at 1024px; Wide at 1600px.
 */
function concordia_kanahoma_responsive_breakpoints( $breakpoints ) {
    return array(
        'mobile'  => 0,
        'tablet'  => 768,
        'desktop' => 1024,
        'wide'    => 1600,
    );
}
add_filter( 'kanahoma_responsive_breakpoints', 'concordia_kanahoma_responsive_breakpoints' );

/**
 * Enable excerpts on Pages.
 */
function concordia_enable_page_excerpts() {
    add_post_type_support( 'page', 'excerpt' );
}
add_action( 'init', 'concordia_enable_page_excerpts' );
