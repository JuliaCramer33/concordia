<?php
/**
 * Taxonomy: Semesters for Dean's List
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

add_action( 'init', function () {
	register_taxonomy(
		'deans_semester',
		array( 'deans_list' ),
		array(
			'labels' => array(
				'name'          => __( 'Semesters', 'concordia' ),
				'singular_name' => __( 'Semester', 'concordia' ),
			),
			'public'            => false,
			'publicly_queryable'=> false,
			'show_ui'           => true,
			'show_in_rest'      => true,
			'hierarchical'      => false, // tag-like
			'rewrite'           => false,
			'show_admin_column' => true,
		)
	);
}, 4 );

