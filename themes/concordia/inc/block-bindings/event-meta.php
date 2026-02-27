<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Register a block binding source to expose Event (The Events Calendar) meta to blocks.
 *
 * Usage example in a block's metadata:
 *
 * <!-- wp:paragraph {
 *   "metadata": {
 *     "bindings": {
 *       "content": {
 *         "source": "concordia/event-meta",
 *         "args": { "field": "date_range" }
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
			'concordia/event-meta',
			array(
				/* translators: Block Binding source label */
				'label'              => __( 'Event Meta', 'concordia' ),
				'get_value_callback' => 'concordia_event_meta_binding_get_value',
				'uses_context'       => array( 'postId', 'postType' ),
			)
		);
	},
	20
);

/**
 * Resolve an Event meta value for a block binding.
 *
 * Supported args:
 * - field: one of "start_date", "start_time", "end_date", "end_time", "date_range", "time_range", "cost", "cost_formatted", "event_url"
 * - post_id: optional Event post ID to resolve explicitly (works outside Event context)
 *
 * Behavior:
 * - Uses the current post when it is an Event (CPT "tribe_events").
 * - Returns null outside of Event posts (can be extended later to support explicit post_id).
 *
 * @param array     $source_args    Arguments passed in the binding configuration.
 * @param WP_Block  $block_instance The block instance.
 * @param string    $attribute_name The bound attribute name.
 * @return string|null The string value to inject, or null to leave unchanged.
 */
function concordia_event_meta_binding_get_value( array $source_args, $block_instance, string $attribute_name ) {
	$field = isset( $source_args['field'] ) ? sanitize_key( $source_args['field'] ) : '';
	$allowed_fields = array(
		'start_date',
		'start_time',
		'end_date',
		'end_time',
		'date_range',
		'time_range',
		'cost',
		'cost_formatted',
		'event_url',
		'venue',
		'venue_url',
		'venue_map_url',
	);
	if ( ! in_array( $field, $allowed_fields, true ) ) {
		return null;
	}

	// Determine the target post id/type (support explicit args.post_id first).
	$target_id = 0;
	$post_type = '';
	if ( isset( $source_args['post_id'] ) && is_numeric( $source_args['post_id'] ) ) {
		$target_id = (int) $source_args['post_id'];
		$post_type = get_post_type( $target_id ) ?: '';
	}
	// Fallback to block context in editor and on render.
	if ( $target_id <= 0 && is_object( $block_instance ) ) {
		if ( isset( $block_instance->context['postId'] ) ) {
			$target_id = (int) $block_instance->context['postId'];
		}
		if ( isset( $block_instance->context['postType'] ) ) {
			$post_type = (string) $block_instance->context['postType'];
		}
	}
	// Fallback to global $post on the frontend (e.g., template parts, some contexts).
	if ( $target_id <= 0 ) {
		global $post;
		if ( $post && isset( $post->ID ) ) {
			$target_id = (int) $post->ID;
		}
	}
	if ( ! $post_type && $target_id > 0 ) {
		$post_type = (string) get_post_type( $target_id );
	}
	// Require the resolved post to be an Event unless an explicit post_id was provided.
	if ( $target_id <= 0 ) {
		return null;
	}
	if ( 'tribe_events' !== $post_type ) {
		// If explicit post_id was provided but isn't an event, bail; else also bail.
		return null;
	}

	// Raw TEC meta.
	$start_raw = (string) get_post_meta( $target_id, '_EventStartDate', true );
	$end_raw   = (string) get_post_meta( $target_id, '_EventEndDate', true );
	$cost_raw  = (string) get_post_meta( $target_id, '_EventCost', true );
	$url_raw   = (string) get_post_meta( $target_id, '_EventURL', true );

	// Helpers for formatting.
	$date_format = (string) get_option( 'date_format' );
	$time_format = (string) get_option( 'time_format' );

	$start_ts = $start_raw ? strtotime( $start_raw ) : false;
	$end_ts   = $end_raw ? strtotime( $end_raw ) : false;

	switch ( $field ) {
		case 'start_date':
			if ( ! $start_ts ) {
				return null;
			}
			return (string) date_i18n( $date_format, $start_ts );

		case 'start_time':
			if ( ! $start_ts ) {
				return null;
			}
			return (string) date_i18n( $time_format, $start_ts );

		case 'end_date':
			if ( ! $end_ts ) {
				return null;
			}
			return (string) date_i18n( $date_format, $end_ts );

		case 'end_time':
			if ( ! $end_ts ) {
				return null;
			}
			return (string) date_i18n( $time_format, $end_ts );

		case 'date_range':
			// e.g. "Jan 10, 2026 – Jan 12, 2026" or "Jan 10, 2026" if no end.
			if ( $start_ts && $end_ts && date_i18n( 'Ymd', $start_ts ) !== date_i18n( 'Ymd', $end_ts ) ) {
				return sprintf(
					'%s – %s',
					date_i18n( $date_format, $start_ts ),
					date_i18n( $date_format, $end_ts )
				);
			} elseif ( $start_ts ) {
				return (string) date_i18n( $date_format, $start_ts );
			}
			return null;

		case 'time_range':
			// e.g. "3:00 pm – 5:00 pm" or just "3:00 pm".
			if ( $start_ts && $end_ts && date_i18n( 'His', $start_ts ) !== date_i18n( 'His', $end_ts ) ) {
				return sprintf(
					'%s – %s',
					date_i18n( $time_format, $start_ts ),
					date_i18n( $time_format, $end_ts )
				);
			} elseif ( $start_ts ) {
				return (string) date_i18n( $time_format, $start_ts );
			}
			return null;

		case 'cost':
			// Raw cost; theme/template may add currency symbol.
			return ('' !== $cost_raw) ? (string) $cost_raw : null;

		case 'cost_formatted':
			// Prefer TEC formatting (currency symbol, positioning) when available.
			if ( function_exists( 'tribe_get_formatted_cost' ) ) {
				$formatted = tribe_get_formatted_cost( $target_id );
				return ( '' !== $formatted ) ? (string) $formatted : null;
			}
			// Basic fallback: prefix dollar sign if raw present.
			if ( '' !== $cost_raw ) {
				return '$' . (string) $cost_raw;
			}
			return null;

		case 'event_url':
			// External event URL (TEC meta).
			if ( '' === $url_raw ) {
				return null;
			}
			$sanitized = esc_url_raw( $url_raw );
			return $sanitized ? (string) $sanitized : null;

		case 'venue':
			if ( function_exists( 'tribe_get_venue' ) ) {
				$name = tribe_get_venue( $target_id );
				return ( '' !== $name ) ? (string) $name : null;
			}
			return null;

		case 'venue_url':
			// Link to the venue post (not maps).
			if ( function_exists( 'tribe_get_venue_link' ) ) {
				$url = tribe_get_venue_link( $target_id, false ); // false => URL, not full <a>
				$url = is_string( $url ) ? $url : '';
				return ( '' !== $url ) ? esc_url_raw( $url ) : null;
			}
			return null;

		case 'venue_map_url':
			// Direct Google Maps URL for the event's venue/address.
			if ( function_exists( 'tribe_get_map_link' ) ) {
				$map = tribe_get_map_link( $target_id );
				$map = is_string( $map ) ? $map : '';
				return ( '' !== $map ) ? esc_url_raw( $map ) : null;
			}
			return null;
	}

	return null;
}


