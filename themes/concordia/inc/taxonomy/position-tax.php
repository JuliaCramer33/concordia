<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Register Position taxonomy for People.
 */
add_action( 'init', function () {
    $labels = array(
        'name'              => _x( 'Positions', 'taxonomy general name', 'concordia' ),
        'singular_name'     => _x( 'Position', 'taxonomy singular name', 'concordia' ),
        'search_items'      => __( 'Search Positions', 'concordia' ),
        'all_items'         => __( 'All Positions', 'concordia' ),
        'edit_item'         => __( 'Edit Position', 'concordia' ),
        'update_item'       => __( 'Update Position', 'concordia' ),
        'add_new_item'      => __( 'Add New Position', 'concordia' ),
        'new_item_name'     => __( 'New Position Name', 'concordia' ),
        'menu_name'         => __( 'Positions', 'concordia' ),
    );

    $args = array(
        'hierarchical'      => true,
        'labels'            => $labels,
        'show_ui'           => true,
        'show_admin_column' => true,
        'show_in_rest'      => true,
        'query_var'         => true,
        'rewrite'           => array( 'slug' => 'position' ),
    );

    register_taxonomy( 'position', array( 'person' ), $args );
}, 5 );



