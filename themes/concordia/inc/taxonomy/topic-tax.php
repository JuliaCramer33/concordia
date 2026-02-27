<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Register Topic taxonomy for News (core 'post').
 */
add_action( 'init', function () {
    $labels = array(
        'name'              => _x( 'Topics', 'taxonomy general name', 'concordia' ),
        'singular_name'     => _x( 'Topic', 'taxonomy singular name', 'concordia' ),
        'search_items'      => __( 'Search Topics', 'concordia' ),
        'all_items'         => __( 'All Topics', 'concordia' ),
        'parent_item'       => __( 'Parent Topic', 'concordia' ),
        'parent_item_colon' => __( 'Parent Topic:', 'concordia' ),
        'edit_item'         => __( 'Edit Topic', 'concordia' ),
        'update_item'       => __( 'Update Topic', 'concordia' ),
        'add_new_item'      => __( 'Add New Topic', 'concordia' ),
        'new_item_name'     => __( 'New Topic Name', 'concordia' ),
        'menu_name'         => __( 'Topics', 'concordia' ),
    );

    $args = array(
        'hierarchical'      => true,
        'labels'            => $labels,
        'show_ui'           => true,
        'show_admin_column' => true,
        'show_in_rest'      => true,
        'query_var'         => true,
        'rewrite'           => array( 'slug' => 'topic' ),
    );

    register_taxonomy( 'topic', array( 'post' ), $args );
}, 5 );


