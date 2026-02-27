<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Register Student Type taxonomy for Programs.
 */
add_action( 'init', function () {
    $labels = array(
        'name'              => _x( 'Student Types', 'taxonomy general name', 'concordia' ),
        'singular_name'     => _x( 'Student Type', 'taxonomy singular name', 'concordia' ),
        'search_items'      => __( 'Search Student Types', 'concordia' ),
        'all_items'         => __( 'All Student Types', 'concordia' ),
        'parent_item'       => null,
        'parent_item_colon' => null,
        'edit_item'         => __( 'Edit Student Type', 'concordia' ),
        'update_item'       => __( 'Update Student Type', 'concordia' ),
        'add_new_item'      => __( 'Add New Student Type', 'concordia' ),
        'new_item_name'     => __( 'New Student Type Name', 'concordia' ),
        'menu_name'         => __( 'Student Types', 'concordia' ),
    );

    $args = array(
        'hierarchical'      => false,
        'labels'            => $labels,
        'show_ui'           => true,
        'show_admin_column' => true,
        'show_in_rest'      => true,
        'query_var'         => true,
        'rewrite'           => array( 'slug' => 'student-type' ),
        'public'            => false,
    );

    register_taxonomy( 'student_type', array( 'program' ), $args );
}, 5 );
