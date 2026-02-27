<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Register Schools taxonomy for Programs.
 */
add_action( 'init', function () {
    $labels = array(
        'name'              => _x( 'Schools', 'taxonomy general name', 'concordia' ),
        'singular_name'     => _x( 'School', 'taxonomy singular name', 'concordia' ),
        'search_items'      => __( 'Search Schools', 'concordia' ),
        'all_items'         => __( 'All Schools', 'concordia' ),
        'parent_item'       => __( 'Parent School', 'concordia' ),
        'parent_item_colon' => __( 'Parent School:', 'concordia' ),
        'edit_item'         => __( 'Edit School', 'concordia' ),
        'update_item'       => __( 'Update School', 'concordia' ),
        'add_new_item'      => __( 'Add New School', 'concordia' ),
        'new_item_name'     => __( 'New School Name', 'concordia' ),
        'menu_name'         => __( 'Schools', 'concordia' ),
    );

    $args = array(
        'hierarchical'      => true,
        'labels'            => $labels,
        'show_ui'           => true,
        'show_admin_column' => true,
        'show_in_rest'      => true,
        'query_var'         => true,
        'rewrite'           => array( 'slug' => 'school' ),
    );

    register_taxonomy( 'school', array( 'program', 'person' ), $args );
}, 5 );


