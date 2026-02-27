<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Register Level taxonomy for Programs.
 */
add_action( 'init', function () {
    $labels = array(
        'name'              => _x( 'Levels', 'taxonomy general name', 'concordia' ),
        'singular_name'     => _x( 'Level', 'taxonomy singular name', 'concordia' ),
        'search_items'      => __( 'Search Levels', 'concordia' ),
        'all_items'         => __( 'All Levels', 'concordia' ),
        'parent_item'       => null,
        'parent_item_colon' => null,
        'edit_item'         => __( 'Edit Level', 'concordia' ),
        'update_item'       => __( 'Update Level', 'concordia' ),
        'add_new_item'      => __( 'Add New Level', 'concordia' ),
        'new_item_name'     => __( 'New Level Name', 'concordia' ),
        'menu_name'         => __( 'Levels', 'concordia' ),
    );

    $args = array(
        'hierarchical'      => false,
        'labels'            => $labels,
        'show_ui'           => true,
        'show_admin_column' => true,
        'show_in_rest'      => true,
        'query_var'         => true,
        'rewrite'           => array( 'slug' => 'level' ),
        'public'            => false,
    );

    register_taxonomy( 'level', array( 'program' ), $args );
}, 5 );
