<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
* Register Delivery taxonomy for Programs (formerly Type).
*/
add_action( 'init', function () {
    $labels = array(
        'name'              => _x( 'Deliveries', 'taxonomy general name', 'concordia' ),
        'singular_name'     => _x( 'Delivery', 'taxonomy singular name', 'concordia' ),
        'search_items'      => __( 'Search Deliveries', 'concordia' ),
        'all_items'         => __( 'All Deliveries', 'concordia' ),
        'parent_item'       => __( 'Parent Delivery', 'concordia' ),
        'parent_item_colon' => __( 'Parent Delivery:', 'concordia' ),
        'edit_item'         => __( 'Edit Delivery', 'concordia' ),
        'update_item'       => __( 'Update Delivery', 'concordia' ),
        'add_new_item'      => __( 'Add New Delivery', 'concordia' ),
        'new_item_name'     => __( 'New Delivery Name', 'concordia' ),
        'menu_name'         => __( 'Delivery', 'concordia' ),
    );

    $args = array(
        'hierarchical'      => true,
        'labels'            => $labels,
        'show_ui'           => true,
        'show_admin_column' => true,
        'show_in_rest'      => true,
        'query_var'         => true,
        'rewrite'           => array( 'slug' => 'delivery' ),
    );

    register_taxonomy( 'program_delivery', array( 'program' ), $args );
}, 5 );


