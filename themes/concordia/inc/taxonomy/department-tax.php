<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
* Register Department taxonomy for People and Programs.
*/
add_action( 'init', function () {
    $labels = array(
        'name'              => _x( 'Departments', 'taxonomy general name', 'concordia' ),
        'singular_name'     => _x( 'Department', 'taxonomy singular name', 'concordia' ),
        'search_items'      => __( 'Search Departments', 'concordia' ),
        'all_items'         => __( 'All Departments', 'concordia' ),
        'parent_item'       => __( 'Parent Department', 'concordia' ),
        'parent_item_colon' => __( 'Parent Department:', 'concordia' ),
        'edit_item'         => __( 'Edit Department', 'concordia' ),
        'update_item'       => __( 'Update Department', 'concordia' ),
        'add_new_item'      => __( 'Add New Department', 'concordia' ),
        'new_item_name'     => __( 'New Department Name', 'concordia' ),
        'menu_name'         => __( 'Departments', 'concordia' ),
    );

    $args = array(
        'hierarchical'      => true,
        'labels'            => $labels,
        'show_ui'           => true,
        'show_admin_column' => true,
        'show_in_rest'      => true,
        'query_var'         => true,
        'rewrite'           => array( 'slug' => 'department' ),
    );

    register_taxonomy( 'department', array( 'person', 'program', 'post' ), $args );
}, 5 );

/**
 * Register Department term metadata (Phone, Email, Address) with REST exposure.
 */
add_action( 'init', function () {
    // Phone
    register_term_meta(
        'department',
        'department_phone',
        array(
            'type'              => 'string',
            'single'            => true,
            'sanitize_callback' => 'sanitize_text_field',
            'show_in_rest'      => array(
                'schema' => array(
                    'type' => 'string',
                ),
            ),
            'auth_callback'     => function () {
                return current_user_can( 'edit_terms' );
            },
        )
    );

    // Email
    register_term_meta(
        'department',
        'department_email',
        array(
            'type'              => 'string',
            'single'            => true,
            'sanitize_callback' => 'sanitize_email',
            'show_in_rest'      => array(
                'schema' => array(
                    'type'   => 'string',
                    'format' => 'email',
                ),
            ),
            'auth_callback'     => function () {
                return current_user_can( 'edit_terms' );
            },
        )
    );

    // Address (WYSIWYG-safe HTML)
    register_term_meta(
        'department',
        'department_address',
        array(
            'type'              => 'string',
            'single'            => true,
            'sanitize_callback' => 'wp_kses_post',
            'show_in_rest'      => array(
                'schema' => array(
                    'type' => 'string',
                ),
            ),
            'auth_callback'     => function () {
                return current_user_can( 'edit_terms' );
            },
        )
    );

    // Hours (WYSIWYG-safe HTML)
    register_term_meta(
        'department',
        'department_hours',
        array(
            'type'              => 'string',
            'single'            => true,
            'sanitize_callback' => 'wp_kses_post',
            'show_in_rest'      => array(
                'schema' => array(
                    'type' => 'string',
                ),
            ),
            'auth_callback'     => function () {
                return current_user_can( 'edit_terms' );
            },
        )
    );
    // Page Link (URL)
    register_term_meta(
        'department',
        'department_page_link',
        array(
            'type'              => 'string',
            'single'            => true,
            'sanitize_callback' => 'esc_url_raw',
            'show_in_rest'      => array(
                'schema' => array(
                    'type'   => 'string',
                    'format' => 'uri',
                ),
            ),
            'auth_callback'     => function () {
                return current_user_can( 'edit_terms' );
            },
        )
    );
}, 10 );

/**
 * Admin UI: Add fields to the Add New Department form.
 */
add_action( 'department_add_form_fields', function () {
    ?>
    <?php wp_nonce_field( 'concordia_department_meta', 'concordia_department_meta_nonce' ); ?>
    <div class="form-field term-phone-wrap">
        <label for="department_phone"><?php esc_html_e( 'Phone', 'concordia' ); ?></label>
        <input type="text" name="department_phone" id="department_phone" value="" />
        <p class="description"><?php esc_html_e( 'Contact phone number for this department.', 'concordia' ); ?></p>
    </div>
    <div class="form-field term-email-wrap">
        <label for="department_email"><?php esc_html_e( 'Email', 'concordia' ); ?></label>
        <input type="email" name="department_email" id="department_email" value="" />
        <p class="description"><?php esc_html_e( 'Contact email address for this department.', 'concordia' ); ?></p>
    </div>
    <div class="form-field term-address-wrap">
        <label for="department_address"><?php esc_html_e( 'Address', 'concordia' ); ?></label>
        <?php
        if ( function_exists( 'wp_editor' ) ) {
            wp_editor(
                '',
                'department_address_editor_add',
                array(
                    'textarea_name' => 'department_address',
                    'media_buttons' => false,
                    'textarea_rows' => 5,
                    'tinymce'       => array(
                        'toolbar1' => 'bold,italic,link,unlink,bullist,numlist',
                    ),
                    'quicktags'     => true,
                )
            );
        } else {
            echo '<textarea name="department_address" id="department_address" rows="4" cols="40"></textarea>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        }
        ?>
        <p class="description"><?php esc_html_e( 'Mailing or physical address for this department. Basic formatting allowed.', 'concordia' ); ?></p>
    </div>
    <div class="form-field term-hours-wrap">
        <label for="department_hours"><?php esc_html_e( 'Hours', 'concordia' ); ?></label>
        <?php
        if ( function_exists( 'wp_editor' ) ) {
            wp_editor(
                '',
                'department_hours_editor_add',
                array(
                    'textarea_name' => 'department_hours',
                    'media_buttons' => false,
                    'textarea_rows' => 5,
                    'tinymce'       => array(
                        'toolbar1' => 'bold,italic,link,unlink,bullist,numlist',
                    ),
                    'quicktags'     => true,
                )
            );
        } else {
            echo '<textarea name="department_hours" id="department_hours" rows="4" cols="40"></textarea>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        }
        ?>
        <p class="description"><?php esc_html_e( 'Office hours or availability. Basic formatting allowed.', 'concordia' ); ?></p>
    </div>
    <div class="form-field term-page-link-wrap">
        <label for="department_page_link"><?php esc_html_e( 'Page Link (URL)', 'concordia' ); ?></label>
        <input type="url" name="department_page_link" id="department_page_link" value="" placeholder="https://example.edu/department" />
        <p class="description"><?php esc_html_e( 'Optional canonical page URL for this department.', 'concordia' ); ?></p>
    </div>
    <?php
}, 10 );

/**
 * Admin UI: Add fields to the Edit Department form.
 */
add_action( 'department_edit_form_fields', function ( $term ) {
    $phone   = get_term_meta( $term->term_id, 'department_phone', true );
    $email   = get_term_meta( $term->term_id, 'department_email', true );
    $address = get_term_meta( $term->term_id, 'department_address', true );
    $hours   = get_term_meta( $term->term_id, 'department_hours', true );
    $pageurl = get_term_meta( $term->term_id, 'department_page_link', true );
    ?>
    <?php wp_nonce_field( 'concordia_department_meta', 'concordia_department_meta_nonce' ); ?>
    <tr class="form-field term-phone-wrap">
        <th scope="row"><label for="department_phone"><?php esc_html_e( 'Phone', 'concordia' ); ?></label></th>
        <td>
            <input name="department_phone" id="department_phone" type="text" value="<?php echo esc_attr( $phone ); ?>" class="regular-text" />
            <p class="description"><?php esc_html_e( 'Contact phone number for this department.', 'concordia' ); ?></p>
        </td>
    </tr>
    <tr class="form-field term-email-wrap">
        <th scope="row"><label for="department_email"><?php esc_html_e( 'Email', 'concordia' ); ?></label></th>
        <td>
            <input name="department_email" id="department_email" type="email" value="<?php echo esc_attr( $email ); ?>" class="regular-text" />
            <p class="description"><?php esc_html_e( 'Contact email address for this department.', 'concordia' ); ?></p>
        </td>
    </tr>
    <tr class="form-field term-address-wrap">
        <th scope="row"><label for="department_address"><?php esc_html_e( 'Address', 'concordia' ); ?></label></th>
        <td>
            <?php
            if ( function_exists( 'wp_editor' ) ) {
                wp_editor(
                    $address,
                    'department_address_editor_edit',
                    array(
                        'textarea_name' => 'department_address',
                        'media_buttons' => false,
                        'textarea_rows' => 5,
                        'tinymce'       => array(
                            'toolbar1' => 'bold,italic,link,unlink,bullist,numlist',
                        ),
                        'quicktags'     => true,
                    )
                );
            } else {
                echo '<textarea name="department_address" id="department_address" rows="4" cols="50" class="large-text">' . esc_textarea( $address ) . '</textarea>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            }
            ?>
            <p class="description"><?php esc_html_e( 'Mailing or physical address for this department. Basic formatting allowed.', 'concordia' ); ?></p>
        </td>
    </tr>
    <tr class="form-field term-hours-wrap">
        <th scope="row"><label for="department_hours"><?php esc_html_e( 'Hours', 'concordia' ); ?></label></th>
        <td>
            <?php
            if ( function_exists( 'wp_editor' ) ) {
                wp_editor(
                    $hours,
                    'department_hours_editor_edit',
                    array(
                        'textarea_name' => 'department_hours',
                        'media_buttons' => false,
                        'textarea_rows' => 5,
                        'tinymce'       => array(
                            'toolbar1' => 'bold,italic,link,unlink,bullist,numlist',
                        ),
                        'quicktags'     => true,
                    )
                );
            } else {
                echo '<textarea name="department_hours" id="department_hours" rows="4" cols="50" class="large-text">' . esc_textarea( $hours ) . '</textarea>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            }
            ?>
            <p class="description"><?php esc_html_e( 'Office hours or availability. Basic formatting allowed.', 'concordia' ); ?></p>
        </td>
    </tr>
    <tr class="form-field term-page-link-wrap">
        <th scope="row"><label for="department_page_link"><?php esc_html_e( 'Page Link (URL)', 'concordia' ); ?></label></th>
        <td>
            <input name="department_page_link" id="department_page_link" type="url" value="<?php echo esc_attr( $pageurl ); ?>" class="regular-text" placeholder="https://example.edu/department" />
            <p class="description"><?php esc_html_e( 'Optional canonical page URL for this department.', 'concordia' ); ?></p>
        </td>
    </tr>
    <?php
}, 10, 1 );

/**
 * Save handlers for Department term metadata on create/edit.
 */
function concordia_save_department_meta( $term_id ) {
    if ( ! isset( $_POST['concordia_department_meta_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['concordia_department_meta_nonce'] ) ), 'concordia_department_meta' ) ) {
        return;
    }

    if ( ! current_user_can( 'edit_term', $term_id ) ) {
        return;
    }

    if ( isset( $_POST['department_phone'] ) ) {
        update_term_meta( $term_id, 'department_phone', sanitize_text_field( wp_unslash( $_POST['department_phone'] ) ) );
    }
    if ( isset( $_POST['department_email'] ) ) {
        update_term_meta( $term_id, 'department_email', sanitize_email( wp_unslash( $_POST['department_email'] ) ) );
    }
    if ( isset( $_POST['department_address'] ) ) {
        update_term_meta( $term_id, 'department_address', wp_kses_post( wp_unslash( $_POST['department_address'] ) ) );
    }
    if ( isset( $_POST['department_hours'] ) ) {
        update_term_meta( $term_id, 'department_hours', wp_kses_post( wp_unslash( $_POST['department_hours'] ) ) );
    }
    if ( isset( $_POST['department_page_link'] ) ) {
        update_term_meta( $term_id, 'department_page_link', esc_url_raw( wp_unslash( $_POST['department_page_link'] ) ) );
    }
}
add_action( 'created_department', 'concordia_save_department_meta', 10, 1 );
add_action( 'edited_department', 'concordia_save_department_meta', 10, 1 );


