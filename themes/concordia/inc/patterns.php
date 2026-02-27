<?php
/**
 * Register block pattern categories and load pattern files.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

add_action( 'init', function () {
	// Categories
	$categories = array(
		'cui/layout'   => array( 'label' => __( 'Concordia Layout', 'concordia' ) ),
		'cui/sections' => array( 'label' => __( 'Concordia Sections', 'concordia' ) ),
		'cui/components'=> array( 'label' => __( 'Concordia Components', 'concordia' ) ),
	);
	foreach ( $categories as $slug => $args ) {
		if ( function_exists( 'register_block_pattern_category' ) ) {
			register_block_pattern_category( $slug, $args );
		}
	}

    // Load patterns from patterns/ directory:
    // 1) Any top-level *.php files
    // 2) Any subfolder containing a pattern.php file (recommended structure)
    $dir = realpath( get_theme_file_path( 'patterns' ) );
    if ( $dir && is_dir( $dir ) ) {
        // Top-level PHP patterns (optional)
        $top_level = glob( trailingslashit( $dir ) . '*.php' );
        foreach ( (array) $top_level as $file ) {
            $real = realpath( $file );
            if ( ! $real || strpos( $real, $dir ) !== 0 || ! is_readable( $real ) ) {
                continue;
            }
            // phpcs:ignore WordPressVIPMinimum.Files.IncludingFile.UsingVariable,Squiz.PHP.CommentedOutCode.Found
            $pattern = include $real; // expects `return array(...)`
            if ( is_array( $pattern ) && ! empty( $pattern['name'] ) ) {
                register_block_pattern( $pattern['name'], $pattern );
            }
        }

        // Subfolders with pattern.php (preferred)
        $subdirs = glob( trailingslashit( $dir ) . '*', GLOB_ONLYDIR );
        foreach ( (array) $subdirs as $subdir ) {
            $pattern_file = trailingslashit( $subdir ) . 'pattern.php';
            $real = realpath( $pattern_file );
            if ( ! $real || strpos( $real, $dir ) !== 0 || ! is_readable( $real ) ) {
                continue;
            }
            // phpcs:ignore WordPressVIPMinimum.Files.IncludingFile.UsingVariable,Squiz.PHP.CommentedOutCode.Found
            $pattern = include $real; // expects `return array(...)`
            if ( is_array( $pattern ) && ! empty( $pattern['name'] ) ) {
                register_block_pattern( $pattern['name'], $pattern );
            }
        }
    }
} );
