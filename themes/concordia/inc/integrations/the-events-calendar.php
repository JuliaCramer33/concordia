<?php
/**
 * The Events Calendar integration
 * - Toggleable force of block editor for Events (`tribe_events`)
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Gate: enable by setting the filter to true in theme (see functions.php).
// Always on: force block editor for Events.

// Prefer the generic core filter used by WP to allow the block editor (post type wide).
add_filter(
	'use_block_editor_for_post_type',
	function ( $use_block_editor, $post_type ) {
		if ( 'tribe_events' === $post_type ) {
			return true;
		}
		return $use_block_editor;
	},
	10000,
	2
);

// Additionally, force per-post check to true (some setups gate by post rather than post type).
add_filter(
	'use_block_editor_for_post',
	function ( $use_block_editor, $post ) {
		$post_type = is_object( $post ) ? $post->post_type : get_post_type( $post );
		if ( 'tribe_events' === $post_type ) {
			return true;
		}
		return $use_block_editor;
	},
	10000,
	2
);

// Some environments check the older Gutenberg filter as well.
add_filter(
	'gutenberg_can_edit_post_type',
	function ( $can_edit, $post_type ) {
		if ( 'tribe_events' === $post_type ) {
			return true;
		}
		return $can_edit;
	},
	10000,
	2
);

// Classic Editor plugin compatibility: ensure block editor is allowed for tribe_events.
add_filter(
	'classic_editor_enabled_editors_for_post_type',
	function ( $editors, $post_type ) {
		if ( 'tribe_events' === $post_type ) {
			// Only allow block editor for Events.
			return array( 'block' );
		}
		return $editors;
	},
	10000,
	2
);

