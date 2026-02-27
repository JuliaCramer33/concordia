<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Register Program custom post type.
 */
add_action( 'init', function () {
    $labels = array(
        'name'               => _x( 'Programs', 'post type general name', 'concordia' ),
        'singular_name'      => _x( 'Program', 'post type singular name', 'concordia' ),
        'menu_name'          => _x( 'Programs', 'admin menu', 'concordia' ),
        'name_admin_bar'     => _x( 'Program', 'add new on admin bar', 'concordia' ),
        'add_new'            => _x( 'Add New', 'program', 'concordia' ),
        'add_new_item'       => __( 'Add New Program', 'concordia' ),
        'new_item'           => __( 'New Program', 'concordia' ),
        'edit_item'          => __( 'Edit Program', 'concordia' ),
        'view_item'          => __( 'View Program', 'concordia' ),
        'all_items'          => __( 'All Programs', 'concordia' ),
        'search_items'       => __( 'Search Programs', 'concordia' ),
        'parent_item_colon'  => __( 'Parent Programs:', 'concordia' ),
        'not_found'          => __( 'No programs found.', 'concordia' ),
        'not_found_in_trash' => __( 'No programs found in Trash.', 'concordia' )
    );

    $args = array(
        'labels'             => $labels,
        'public'             => true,
        'publicly_queryable' => true,
        'show_ui'            => true,
        'show_in_menu'       => true,
        'show_in_rest'       => true,
        'query_var'          => true,
        'rewrite'            => array( 'slug' => 'program' ),
        'capability_type'    => 'post',
        'has_archive'        => true,
        'hierarchical'       => false,
        'menu_position'      => 20,
        'menu_icon'          => 'dashicons-welcome-learn-more',
        'supports'           => array( 'title', 'editor', 'thumbnail', 'excerpt', 'revisions' ),
        'taxonomies'         => array( 'school', 'degree_level', 'program_delivery', 'department', 'start_term', 'level', 'student_type' ),
    );

    register_post_type( 'program', $args );
}, 5 );

/**
 * Add Salesforce Program Name meta box to Program CPT.
 */
add_action( 'add_meta_boxes', function () {
    add_meta_box(
        'cui_salesforce_program_name',
        __( 'Salesforce Program Name', 'concordia' ),
        'cui_salesforce_program_name_callback',
        'program',
        'side',
        'high'
    );
} );

/**
 * Meta box callback: Render the Salesforce Program Name field.
 *
 * @param WP_Post $post Current post object.
 */
function cui_salesforce_program_name_callback( $post ) {
    // Add nonce for security.
    wp_nonce_field( 'cui_save_salesforce_program_name', 'cui_salesforce_program_name_nonce' );

    // Get current value.
    $value = get_post_meta( $post->ID, '_cui_salesforce_program_name', true );

    // Fallback to post title if empty.
    if ( empty( $value ) ) {
        $value = $post->post_title;
    }

    echo '<p>';
    echo '<label for="cui_salesforce_program_name" style="display:block; margin-bottom:5px; font-weight:600;">';
    esc_html_e( 'Program Name (for Salesforce)', 'concordia' );
    echo '</label>';
    echo '<input type="text" id="cui_salesforce_program_name" name="cui_salesforce_program_name" value="' . esc_attr( $value ) . '" style="width:100%;" required />';
    echo '</p>';
    echo '<p class="description">';
    esc_html_e( 'Enter the exact program name as it appears in Salesforce. This value will be sent with form submissions.', 'concordia' );
    echo '</p>';
}

/**
 * Save the Salesforce Program Name meta field.
 *
 * @param int $post_id Post ID.
 */
add_action( 'save_post_program', function ( $post_id ) {
    // Check if nonce is set and valid.
    if ( ! isset( $_POST['cui_salesforce_program_name_nonce'] ) ||
         ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['cui_salesforce_program_name_nonce'] ) ), 'cui_save_salesforce_program_name' ) ) {
        return;
    }

    // Check if this is an autosave.
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }

    // Check user permissions.
    if ( ! current_user_can( 'edit_post', $post_id ) ) {
        return;
    }

    // Sanitize and save the field.
    if ( isset( $_POST['cui_salesforce_program_name'] ) ) {
        $salesforce_name = sanitize_text_field( wp_unslash( $_POST['cui_salesforce_program_name'] ) );
        update_post_meta( $post_id, '_cui_salesforce_program_name', $salesforce_name );
    }
}, 10, 1 );

/**
 * Helper function to get the Salesforce program name.
 * Falls back to post title if not set.
 *
 * @param int $post_id Post ID (optional, defaults to current post).
 * @return string Salesforce program name.
 */
function cui_get_salesforce_program_name( $post_id = 0 ) {
    if ( ! $post_id ) {
        $post_id = get_the_ID();
    }

    $sf_name = get_post_meta( $post_id, '_cui_salesforce_program_name', true );

    // Fallback to post title if empty.
    if ( empty( $sf_name ) ) {
        $sf_name = get_the_title( $post_id );
    }

    return $sf_name;
}
