<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Register Faculty Type taxonomy for People.
 */
add_action( 'init', function () {
    $labels = array(
        'name'              => _x( 'Faculty Types', 'taxonomy general name', 'concordia' ),
        'singular_name'     => _x( 'Faculty Type', 'taxonomy singular name', 'concordia' ),
        'search_items'      => __( 'Search Faculty Types', 'concordia' ),
        'all_items'         => __( 'All Faculty Types', 'concordia' ),
        'parent_item'       => __( 'Parent Faculty Type', 'concordia' ),
        'parent_item_colon' => __( 'Parent Faculty Type:', 'concordia' ),
        'edit_item'         => __( 'Edit Faculty Type', 'concordia' ),
        'update_item'       => __( 'Update Faculty Type', 'concordia' ),
        'add_new_item'      => __( 'Add New Faculty Type', 'concordia' ),
        'new_item_name'     => __( 'New Faculty Type Name', 'concordia' ),
        'menu_name'         => __( 'Faculty Types', 'concordia' ),
    );

    $args = array(
        'hierarchical'      => true,
        'labels'            => $labels,
        'show_ui'           => true,
        'show_admin_column' => true,
        'show_in_rest'      => true,
        'query_var'         => true,
        'rewrite'           => array( 'slug' => 'faculty-type' ),
    );

    register_taxonomy( 'faculty_type', array( 'person' ), $args );
}, 5 );



