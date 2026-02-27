<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Register Start Term taxonomy for Programs.
 */
add_action( 'init', function () {
    $labels = array(
        'name'              => _x( 'Start Terms', 'taxonomy general name', 'concordia' ),
        'singular_name'     => _x( 'Start Term', 'taxonomy singular name', 'concordia' ),
        'search_items'      => __( 'Search Start Terms', 'concordia' ),
        'all_items'         => __( 'All Start Terms', 'concordia' ),
        'parent_item'       => null,
        'parent_item_colon' => null,
        'edit_item'         => __( 'Edit Start Term', 'concordia' ),
        'update_item'       => __( 'Update Start Term', 'concordia' ),
        'add_new_item'      => __( 'Add New Start Term', 'concordia' ),
        'new_item_name'     => __( 'New Start Term Name', 'concordia' ),
        'menu_name'         => __( 'Start Terms', 'concordia' ),
    );

    $args = array(
        'hierarchical'      => false,
        'labels'            => $labels,
        'show_ui'           => true,
        'show_admin_column' => true,
        'show_in_rest'      => true,
        'query_var'         => true,
        'rewrite'           => array( 'slug' => 'start-term' ),
        'public'            => false,
    );

    register_taxonomy( 'start_term', array( 'program' ), $args );
}, 5 );
