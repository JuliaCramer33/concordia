<?php
/**
 * Taxonomy: Subjects for Dean's List
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

add_action( 'init', function () {
	register_taxonomy(
		'deans_subject',
		array( 'deans_list' ),
		array(
			'labels' => array(
				'name'          => __( 'Subjects', 'concordia' ),
				'singular_name' => __( 'Subject', 'concordia' ),
			),
			'public'            => false,
			'publicly_queryable'=> false,
			'show_ui'           => true,
			'show_in_rest'      => true,
			'hierarchical'      => true, // category-like
			'rewrite'           => false,
			'show_admin_column' => true,
		)
	);
}, 4 );

