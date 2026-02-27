<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Register Degree Level taxonomy for Programs.
 */
add_action( 'init', function () {
    $labels = array(
        'name'              => _x( 'Degree Levels', 'taxonomy general name', 'concordia' ),
        'singular_name'     => _x( 'Degree Level', 'taxonomy singular name', 'concordia' ),
        'search_items'      => __( 'Search Degree Levels', 'concordia' ),
        'all_items'         => __( 'All Degree Levels', 'concordia' ),
        'parent_item'       => __( 'Parent Degree Level', 'concordia' ),
        'parent_item_colon' => __( 'Parent Degree Level:', 'concordia' ),
        'edit_item'         => __( 'Edit Degree Level', 'concordia' ),
        'update_item'       => __( 'Update Degree Level', 'concordia' ),
        'add_new_item'      => __( 'Add New Degree Level', 'concordia' ),
        'new_item_name'     => __( 'New Degree Level Name', 'concordia' ),
        'menu_name'         => __( 'Degree Levels', 'concordia' ),
    );

    $args = array(
        'hierarchical'      => true,
        'labels'            => $labels,
        'show_ui'           => true,
        'show_admin_column' => true,
        'show_in_rest'      => true,
        'query_var'         => true,
        'rewrite'           => array( 'slug' => 'degree-level' ),
    );

    register_taxonomy( 'degree_level', array( 'program' ), $args );
}, 5 );


