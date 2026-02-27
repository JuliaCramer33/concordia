<?php
/**
 * Rename default 'post' post type to "News" and set permalinks to /news/.
 *
 * This file uses filters to alter the core 'post' registration args so that
 * labels in the admin change to "News" and the rewrite slug and archive are
 * set to 'news'. It also provides a small compatibility redirect for legacy
 * URLs that used /blog/ or /posts/.
 *
 * Follows WP coding standards and avoids side effects on include.
 *
 * @package concordia
 */

defined( 'ABSPATH' ) || exit;


/**
 * Modify registered args for the core 'post' post type.
 *
 * @param array  $args      The registered args for the post type.
 * @param string $post_type The post type key.
 * @return array Modified args for the post type.
 */
function concordia_modify_post_type_args( $args, $post_type ) {
    if ( 'post' !== $post_type ) {
        return $args;
    }

    // Ensure labels exist and merge in our values.
    $existing_labels = isset( $args['labels'] ) && is_array( $args['labels'] ) ? $args['labels'] : array();

    $news_labels = array(
        'name'               => __( 'News', 'concordia' ),
        'singular_name'      => __( 'News Article', 'concordia' ),
        'menu_name'          => __( 'News', 'concordia' ),
        'name_admin_bar'     => __( 'News', 'concordia' ),
        'add_new'            => __( 'Add News Article', 'concordia' ),
        'add_new_item'       => __( 'Add News Article', 'concordia' ),
        'edit_item'          => __( 'Edit News Article', 'concordia' ),
        'new_item'           => __( 'News Article', 'concordia' ),
        'view_item'          => __( 'View News Article', 'concordia' ),
        'search_items'       => __( 'Search News', 'concordia' ),
        'not_found'          => __( 'No News articles found', 'concordia' ),
        'not_found_in_trash' => __( 'No News articles found in Trash', 'concordia' ),
        'all_items'          => __( 'All News', 'concordia' ),
    );

    $args['labels'] = array_merge( $existing_labels, $news_labels );

    // Configure rewrite and archive to use /news/.
    $args['rewrite']     = array( 'slug' => 'news', 'with_front' => false );
    $args['has_archive'] = 'news';

    return $args;
}
add_filter( 'register_post_type_args', 'concordia_modify_post_type_args', 10, 2 );


/**
 * Redirect legacy /blog/... or /posts/... URLs to /news/... if a matching
 * post exists. This preserves access for users/bookmarks that point at the
 * old structure. We don't attempt to rewrite every possible URL — only
 * simple post-name style URLs.
 */
function concordia_redirect_legacy_post_urls() {
    if ( is_admin() ) {
        return;
    }

    if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
        return;
    }

    // Safely read and sanitize the request URI. Use filter_input to avoid
    // directly accessing the superglobal and ensure the index exists.
    $server_request_uri = filter_input( INPUT_SERVER, 'REQUEST_URI', FILTER_SANITIZE_URL );
    $path = trim( wp_unslash( wp_parse_url( $server_request_uri ?: '', PHP_URL_PATH ) ), '/' );

    if ( empty( $path ) ) {
        return;
    }

    // Match /blog/slug or /posts/slug (case-insensitive)
    if ( preg_match( '#^(?:blog|posts)/(.*)$#i', $path, $matches ) ) {
        $maybe_slug = trim( $matches[1], '/' );
        if ( empty( $maybe_slug ) ) {
            return;
        }

        // Try to find a published post by this path.
        $post = get_page_by_path( $maybe_slug, OBJECT, 'post' );
        if ( $post && 'publish' === get_post_status( $post ) ) {
            $new_url = home_url( user_trailingslashit( "news/{$maybe_slug}" ) );
            wp_safe_redirect( $new_url, 301 );
            exit;
        }
    }
}
add_action( 'template_redirect', 'concordia_redirect_legacy_post_urls', 1 );
