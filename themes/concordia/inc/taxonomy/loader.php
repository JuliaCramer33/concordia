<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Load all taxonomies from this directory.
 */
add_action( 'init', function () {
    $dir = __DIR__;

    // Include each taxonomy file ending with -tax.php
    foreach ( glob( $dir . '/*-tax.php' ) as $file ) {
        // phpcs:ignore WordPressVIPMinimum.Files.IncludingFile.UsingVariable,Squiz.PHP.CommentedOutCode.Found
        require_once $file;
    }
}, 0 );


