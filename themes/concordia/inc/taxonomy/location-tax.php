<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Register Location taxonomy for People.
 */
add_action( 'init', function () {
    $labels = array(
        'name'              => _x( 'Locations', 'taxonomy general name', 'concordia' ),
        'singular_name'     => _x( 'Location', 'taxonomy singular name', 'concordia' ),
        'search_items'      => __( 'Search Locations', 'concordia' ),
        'all_items'         => __( 'All Locations', 'concordia' ),
        'parent_item'       => __( 'Parent Location', 'concordia' ),
        'parent_item_colon' => __( 'Parent Location:', 'concordia' ),
        'edit_item'         => __( 'Edit Location', 'concordia' ),
        'update_item'       => __( 'Update Location', 'concordia' ),
        'add_new_item'      => __( 'Add New Location', 'concordia' ),
        'new_item_name'     => __( 'New Location Name', 'concordia' ),
        'menu_name'         => __( 'Locations', 'concordia' ),
    );

    $args = array(
        'hierarchical'      => true,
        'labels'            => $labels,
        'show_ui'           => true,
        'show_admin_column' => true,
        'show_in_rest'      => true,
        'query_var'         => true,
        'rewrite'           => array( 'slug' => 'location' ),
    );

    register_taxonomy( 'location', array( 'person' ), $args );
}, 5 );



