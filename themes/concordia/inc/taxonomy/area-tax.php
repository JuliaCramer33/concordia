<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Register Area taxonomy for News (core 'post').
 */
add_action( 'init', function () {
    $labels = array(
        'name'              => _x( 'Areas', 'taxonomy general name', 'concordia' ),
        'singular_name'     => _x( 'Area', 'taxonomy singular name', 'concordia' ),
        'search_items'      => __( 'Search Areas', 'concordia' ),
        'all_items'         => __( 'All Areas', 'concordia' ),
        'parent_item'       => __( 'Parent Area', 'concordia' ),
        'parent_item_colon' => __( 'Parent Area:', 'concordia' ),
        'edit_item'         => __( 'Edit Area', 'concordia' ),
        'update_item'       => __( 'Update Area', 'concordia' ),
        'add_new_item'      => __( 'Add New Area', 'concordia' ),
        'new_item_name'     => __( 'New Area Name', 'concordia' ),
        'menu_name'         => __( 'Areas', 'concordia' ),
    );

    $args = array(
        'hierarchical'      => true,
        'labels'            => $labels,
        'show_ui'           => true,
        'show_admin_column' => true,
        'show_in_rest'      => true,
        'query_var'         => true,
        'rewrite'           => array( 'slug' => 'area' ),
    );

    register_taxonomy( 'area', array( 'post' ), $args );
}, 5 );


