<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Load all Custom Post Types from this directory.
 */
add_action( 'init', function () {
    $dir = __DIR__;

    // Include each CPT file ending with -cpt.php
    foreach ( glob( $dir . '/*-cpt.php' ) as $file ) {
        // phpcs:ignore WordPressVIPMinimum.Files.IncludingFile.UsingVariable,Squiz.PHP.CommentedOutCode.Found
        require_once $file;
    }
}, 0 );


