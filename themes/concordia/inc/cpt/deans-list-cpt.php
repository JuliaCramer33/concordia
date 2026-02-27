<?php
/**
 * Dean's List CPT (theme)
 * - No single, no archive, excluded from search
 * - Taxonomies: Semester (tag-like), Subject (category-like)
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

add_action( 'init', function () {
	$labels = array(
		'name'               => __( "Dean's List", 'concordia' ),
		'singular_name'      => __( "Dean's List Entry", 'concordia' ),
		'add_new'            => __( 'Add New', 'concordia' ),
		'add_new_item'       => __( "Add New Dean's List Entry", 'concordia' ),
		'edit_item'          => __( "Edit Dean's List Entry", 'concordia' ),
		'new_item'           => __( "New Dean's List Entry", 'concordia' ),
		'view_item'          => __( "View Dean's List Entry", 'concordia' ),
		'search_items'       => __( "Search Dean's List", 'concordia' ),
		'not_found'          => __( 'No entries found', 'concordia' ),
		'not_found_in_trash' => __( 'No entries found in Trash', 'concordia' ),
		'menu_name'          => __( "Dean's List", 'concordia' ),
	);

	register_post_type(
		'deans_list',
		array(
			'labels'             => $labels,
			'description'        => __( "Dean's List entries", 'concordia' ),
			'public'             => false,
			'publicly_queryable' => false,
			'has_archive'        => false,
			'exclude_from_search'=> true,
			'rewrite'            => false,
			'taxonomies'         => array( 'deans_semester', 'deans_subject' ),
			'show_ui'            => true,
			'show_in_menu'       => true,
			'show_in_nav_menus'  => false,
			'show_in_rest'       => true,
			'menu_icon'          => 'dashicons-awards',
			'supports'           => array( 'title', 'editor', 'thumbnail' ),
			'capability_type'    => 'post',
		)
	);

}, 5 );

// Force 404 if directly requested.
add_action( 'template_redirect', function () {
	if ( is_singular( 'deans_list' ) ) {
		global $wp_query;
		$wp_query->set_404();
		status_header( 404 );
	}
} );

