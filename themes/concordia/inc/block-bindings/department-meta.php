<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Register a block binding source to expose Department term meta to blocks.
 *
 * Usage example in a block's metadata:
 *
 * <!-- wp:paragraph {
 *   "metadata": {
 *     "bindings": {
 *       "content": {
 *         "source": "concordia/department-meta",
 *         "args": { "field": "phone", "term": "financial-aid" }
 *       }
 *     }
 *   }
 * } -->
 * <p>Fallback text</p>
 * <!-- /wp:paragraph -->
 */
add_action(
	'init',
	static function() {
		if ( ! function_exists( 'register_block_bindings_source' ) ) {
			return;
		}

		register_block_bindings_source(
			'concordia/department-meta',
			array(
				/* translators: Block Binding source label */
				'label'              => __( 'Department Meta', 'concordia' ),
				'get_value_callback' => 'concordia_department_meta_binding_get_value',
				'uses_context'       => array( 'postId', 'postType' ),
			)
		);
	},
	20
);

/**
 * Resolve a Department meta value for a block binding.
 *
 * Args supported:
 * - field: one of "phone", "email", "address", "hours", "email_mailto", "name"
 * - term_id: optional numeric term id to use directly
 * - term: optional term slug to resolve
 * - branch / branch_slug: optional top-level branch slug to prefer among assigned terms
 * - branch_id: optional top-level branch term ID to prefer among assigned terms
 *
 * Resolution priority when a term isn't explicitly provided:
 * 1) primary_department post meta (if present and valid)
 * 2) first assigned 'department' term on the current post
 *
 * @param array     $source_args    Arguments passed in the binding configuration.
 * @param WP_Block  $block_instance The block instance.
 * @param string    $attribute_name The bound attribute name.
 * @return string|null The string value to inject, or null to leave unchanged.
 */
function concordia_department_meta_binding_get_value( array $source_args, $block_instance, string $attribute_name ) {
	// Determine requested field.
	$field = isset( $source_args['field'] ) ? sanitize_key( $source_args['field'] ) : '';
	$field_to_meta_key_map = array(
		'phone'        => 'department_phone',
		'email'        => 'department_email',
		'email_mailto' => 'department_email', // derive mailto from same meta
		'address'      => 'department_address',
		'hours'        => 'department_hours',
		'page_link'    => 'department_page_link',
	);
	// Allow 'name' even though it doesn't map to a term meta key.
	if ( 'name' !== $field && ! isset( $field_to_meta_key_map[ $field ] ) ) {
		return null;
	}
	$meta_key = $field_to_meta_key_map[ $field ] ?? null;

	// Determine the current post id from block context (editor and render-safe).
	$post_id = 0;
	if ( is_object( $block_instance ) && isset( $block_instance->context['postId'] ) ) {
		$post_id = (int) $block_instance->context['postId'];
	}

	// Resolve the term.
	$term = null;

	// 1) Explicit term target by id.
	if ( isset( $source_args['term_id'] ) && is_numeric( $source_args['term_id'] ) ) {
		$term = get_term( (int) $source_args['term_id'], 'department' );
	}

	// 2) Explicit term target by slug.
	if ( ( ! $term || is_wp_error( $term ) ) && ! empty( $source_args['term'] ) ) {
		$slug = sanitize_title( wp_unslash( (string) $source_args['term'] ) );
		$term = get_term_by( 'slug', $slug, 'department' );
	}

	// 3) Branch preference among assigned terms (if provided).
	if ( ( ! $term || is_wp_error( $term ) ) && $post_id ) {
		$branch_slug = '';
		if ( ! empty( $source_args['branch'] ) ) {
			$branch_slug = sanitize_title( wp_unslash( (string) $source_args['branch'] ) );
		} elseif ( ! empty( $source_args['branch_slug'] ) ) {
			$branch_slug = sanitize_title( wp_unslash( (string) $source_args['branch_slug'] ) );
		}
		$branch_id = 0;
		if ( ! empty( $source_args['branch_id'] ) && is_numeric( $source_args['branch_id'] ) ) {
			$branch_id = (int) $source_args['branch_id'];
		}

		if ( $branch_slug || $branch_id ) {
			$terms = get_the_terms( $post_id, 'department' );
			if ( is_array( $terms ) && ! empty( $terms ) ) {
				foreach ( $terms as $candidate ) {
					if ( concordia_department_term_is_within_branch( $candidate, $branch_slug, $branch_id ) ) {
						$term = $candidate;
						break;
					}
				}
			}
		}
	}

	// 4) Primary department post meta (optional convention).
	if ( ( ! $term || is_wp_error( $term ) ) && $post_id ) {
		$primary_id = (int) get_post_meta( $post_id, 'primary_department', true );
		if ( $primary_id ) {
			$maybe_term = get_term( $primary_id, 'department' );
			if ( $maybe_term && ! is_wp_error( $maybe_term ) ) {
				$term = $maybe_term;
			}
		}
	}

	// 5) First assigned department term.
	if ( ( ! $term || is_wp_error( $term ) ) && $post_id ) {
		$terms = get_the_terms( $post_id, 'department' );
		if ( is_array( $terms ) && ! empty( $terms ) ) {
			$term = reset( $terms );
		}
	}

	if ( ! $term || is_wp_error( $term ) ) {
		return null;
	}

	// Special case: name comes from the term object, not meta.
	if ( 'name' === $field ) {
		return is_object( $term ) && isset( $term->name ) ? (string) $term->name : null;
	}

	$value = get_term_meta( (int) $term->term_id, $meta_key, true );
	// Derive link formats as requested (no extra meta fields needed).
	if ( 'email_mailto' === $field ) {
		if ( '' === $value || null === $value ) {
			return null;
		}
		$value = 'mailto:' . sanitize_email( $value );
	}
	// Sanitize page link
	if ( 'page_link' === $field ) {
		$value = esc_url_raw( (string) $value );
	}
	// Normalize address/hours HTML for inline usage inside Paragraph/Heading.
	if ( in_array( $field, array( 'address', 'hours' ), true ) && is_string( $value ) && $value !== '' ) {
		$normalized = $value;
		// Replace paragraph boundaries with <br> and remove leading/trailing <p> wrappers.
		$normalized = preg_replace( '#\s*</p>\s*<p[^>]*>\s*#i', '<br>', $normalized );
		$normalized = preg_replace( '#^<p[^>]*>\s*#i', '', $normalized );
		$normalized = preg_replace( '#\s*</p>\s*$#i', '', $normalized );
		$value      = $normalized;
	}
	if ( '' === $value || null === $value ) {
		return null;
	}

	return (string) $value;
}

/**
 * Shortcode fallback to output department meta using the same resolver as bindings.
 *
 * Usage:
 * [department_meta field="phone"]
 * [department_meta field="email" term="financial-aid"]
 * [department_meta field="address" term_id="123"]
 * [department_meta field="phone" branch="academic-departments"]
 */
add_shortcode(
	'department_meta',
	function ( $atts ) {
		$atts = shortcode_atts(
			array(
				'field'      => '',
				'term'       => '',
				'term_id'    => '',
				'branch'     => '',
				'branch_slug'=> '',
				'branch_id'  => '',
			),
			(array) $atts,
			'department_meta'
		);

		$post_id = 0;
		if ( is_admin() ) {
			$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
			// In editor, rely on global $post.
		}
		// Resolve current post ID from global.
		global $post;
		if ( $post && isset( $post->ID ) ) {
			$post_id = (int) $post->ID;
		}

		$block_like = (object) array(
			'context' => array(
				'postId' => $post_id,
			),
		);

		$value = concordia_department_meta_binding_get_value(
			array(
				'field'       => $atts['field'],
				'term'        => $atts['term'],
				'term_id'     => $atts['term_id'],
				'branch'      => $atts['branch'],
				'branch_slug' => $atts['branch_slug'],
				'branch_id'   => $atts['branch_id'],
			),
			$block_like,
			'content'
		);

		if ( null === $value || '' === $value ) {
			return '';
		}
		return esc_html( $value );
	}
);

/**
 * Check whether a department term is within a given branch (top-level parent)
 * identified by slug and/or term_id.
 *
 * @param WP_Term    $term        The department term to evaluate.
 * @param string     $branch_slug Optional branch slug.
 * @param int        $branch_id   Optional branch term ID.
 * @return bool
 */
function concordia_department_term_is_within_branch( $term, $branch_slug, $branch_id ) {
	if ( ! ( $term instanceof WP_Term ) ) {
		return false;
	}

	// If no branch supplied, nothing to check.
	if ( ! $branch_slug && ! $branch_id ) {
		return false;
	}

	// Gather the full lineage (including the term itself).
	$lineage_ids = get_ancestors( (int) $term->term_id, 'department', 'taxonomy' );
	array_unshift( $lineage_ids, (int) $term->term_id );

	foreach ( $lineage_ids as $lineage_id ) {
		$ancestor = get_term( (int) $lineage_id, 'department' );
		if ( $ancestor && ! is_wp_error( $ancestor ) ) {
			if ( $branch_id && (int) $ancestor->term_id === (int) $branch_id ) {
				return true;
			}
			if ( $branch_slug && sanitize_title( $ancestor->slug ) === $branch_slug ) {
				return true;
			}
		}
	}
	return false;
}


