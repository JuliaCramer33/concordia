<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Register People custom post type (no taxonomies yet).
 */
add_action( 'init', function () {
    $labels = array(
        'name'               => _x( 'People', 'post type general name', 'concordia' ),
        'singular_name'      => _x( 'Person', 'post type singular name', 'concordia' ),
        'menu_name'          => _x( 'People', 'admin menu', 'concordia' ),
        'name_admin_bar'     => _x( 'Person', 'add new on admin bar', 'concordia' ),
        'add_new'            => _x( 'Add New', 'person', 'concordia' ),
        'add_new_item'       => __( 'Add New Person', 'concordia' ),
        'new_item'           => __( 'New Person', 'concordia' ),
        'edit_item'          => __( 'Edit Person', 'concordia' ),
        'view_item'          => __( 'View Person', 'concordia' ),
        'all_items'          => __( 'All People', 'concordia' ),
        'search_items'       => __( 'Search People', 'concordia' ),
        'parent_item_colon'  => __( 'Parent People:', 'concordia' ),
        'not_found'          => __( 'No people found.', 'concordia' ),
        'not_found_in_trash' => __( 'No people found in Trash.', 'concordia' ),
    );

    $args = array(
        'labels'             => $labels,
        'public'             => true,
        'publicly_queryable' => true,
        'show_ui'            => true,
        'show_in_menu'       => true,
        'show_in_rest'       => true,
        'query_var'          => true,
        'rewrite'            => array( 'slug' => 'people' ),
        'capability_type'    => 'post',
        'has_archive'        => true,
        'hierarchical'       => false,
        'menu_position'      => 21,
        'menu_icon'          => 'dashicons-groups',
        'supports'           => array( 'title', 'editor', 'thumbnail', 'excerpt', 'revisions' ),
        // No taxonomies attached yet; will add later as needed.
    );

    register_post_type( 'person', $args );
}, 5 );



