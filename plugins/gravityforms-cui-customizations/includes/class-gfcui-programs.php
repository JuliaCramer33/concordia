<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class GFCUI_Programs
 *
 * Provides helpers to dynamically populate Gravity Forms fields with Program CPT data.
 */
class GFCUI_Programs {

    /**
     * Constructor.
     */
    public function __construct() {
        add_action( 'init', array( $this, 'init' ) );
    }

    /**
     * Init: register hooks if Gravity Forms exists.
     */
    public function init() {
        // If Gravity Forms isn't active, show an admin notice.
        if ( ! class_exists( 'GFForms' ) ) {
            add_action( 'admin_notices', array( $this, 'admin_notice_gravityforms_missing' ) );
            return;
        }

        // Populate select fields on render/validation/admin screens.
        add_filter( 'gform_pre_render', array( $this, 'populate_programs' ) );
        add_filter( 'gform_pre_validation', array( $this, 'populate_programs' ) );
        add_filter( 'gform_admin_pre_render', array( $this, 'populate_programs' ) );
        add_filter( 'gform_pre_submission_filter', array( $this, 'populate_programs' ) );

        // Populate degree-level dropdowns.
        add_filter( 'gform_pre_render', array( $this, 'populate_degree_levels' ) );
        add_filter( 'gform_pre_validation', array( $this, 'populate_degree_levels' ) );
        add_filter( 'gform_admin_pre_render', array( $this, 'populate_degree_levels' ) );

        // Register REST endpoint for dynamic dependent population and enqueue frontend script.
        add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

        // Caching: clear transient when Program posts change.
        add_action( 'save_post_program', array( $this, 'clear_programs_cache' ), 10, 3 );
        add_action( 'deleted_post', array( $this, 'maybe_clear_on_deleted' ) );
        add_action( 'trashed_post', array( $this, 'maybe_clear_on_trashed' ) );
    }

    /**
     * Admin notice when Gravity Forms not active.
     */
    public function admin_notice_gravityforms_missing() {
        if ( ! current_user_can( 'activate_plugins' ) ) {
            return;
        }

        echo '<div class="notice notice-warning is-dismissible">';
        echo '<p><strong>GravityForms CUI Customizations:</strong> Gravity Forms plugin is not active. Some features will not work until Gravity Forms is installed and activated.</p>';
        echo '</div>';
    }

    /**
     * Returns program posts.
     *
     * Filters:
     * - `gfcui_programs_cpt` (string) CPT slug, default 'program'
     * - `gfcui_programs_args` (array) WP_Query args
     *
     * @return WP_Post[]
     */
    public function get_programs( $extra_args = array() ) {
        $cpt = apply_filters( 'gfcui_programs_cpt', 'program' );

        $default_args = array(
            'post_type'      => $cpt,
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'orderby'        => 'title',
            'order'          => 'ASC',
        );

        $args = wp_parse_args( $extra_args, $default_args );
        $args = apply_filters( 'gfcui_programs_args', $args );

        // Ensure filters are applied (and satisfy VIP lint checks); callers can override via filter.
        $args['suppress_filters'] = false;

        /**
         * Transient caching to reduce repeated queries.
         * Cache TTL is filterable via `gfcui_programs_cache_ttl` (defaults to 24 hours).
         */
        $ttl = apply_filters( 'gfcui_programs_cache_ttl', 86400 ); // 24 hours

        // Build a stable transient key based on CPT and query args.
        $transient_key = 'gfcui_programs_' . $cpt . '_' . md5( wp_json_encode( $args ) );

        $cached = get_transient( $transient_key );
        if ( false !== $cached ) {
            return $cached;
        }

        $programs = get_posts( $args );

        set_transient( $transient_key, $programs, (int) $ttl );

        // Register this transient key so we can clear it when programs change.
        $this->register_programs_transient_key( $transient_key );

        return $programs;
    }

    /**
     * Return degree level terms for the `degree_level` taxonomy (filterable).
     *
     * @return WP_Term[]
     */
    public function get_degree_levels() {
        $tax = apply_filters( 'gfcui_degree_taxonomy', 'degree_level' );

        $args = apply_filters( 'gfcui_degree_terms_args', array(
            'taxonomy'   => $tax,
            'hide_empty' => false,
            'orderby'    => 'name',
            'order'      => 'ASC',
        ) );

        return get_terms( $args );
    }

    /**
     * Register a transient key in an option for later invalidation.
     *
     * @param string $key
     */
    protected function register_programs_transient_key( $key ) {
        $opt_name = 'gfcui_programs_transient_keys';
        $keys = (array) get_option( $opt_name, array() );
        if ( ! in_array( $key, $keys, true ) ) {
            $keys[] = $key;
            update_option( $opt_name, $keys );
        }
    }

    /**
     * Clear all registered program transients.
     */
    public function clear_programs_cache() {
        $opt_name = 'gfcui_programs_transient_keys';
        $keys = (array) get_option( $opt_name, array() );
        if ( ! empty( $keys ) ) {
            foreach ( $keys as $k ) {
                delete_transient( $k );
            }
            delete_option( $opt_name );
        }
    }

    /**
     * Called on deleted_post: check if the post is a program and clear cache.
     *
     * @param int $post_id
     */
    public function maybe_clear_on_deleted( $post_id ) {
        $cpt = apply_filters( 'gfcui_programs_cpt', 'program' );
        if ( $cpt === get_post_type( $post_id ) ) {
            $this->clear_programs_cache();
        }
    }

    /**
     * Called on trashed_post: check if the post is a program and clear cache.
     *
     * @param int $post_id
     */
    public function maybe_clear_on_trashed( $post_id ) {
        $cpt = apply_filters( 'gfcui_programs_cpt', 'program' );
        if ( $cpt === get_post_type( $post_id ) ) {
            $this->clear_programs_cache();
        }
    }

    /**
     * Populate Gravity Forms select fields that have the CSS class `populate-programs`.
     *
     * Filters:
     * - `gfcui_program_choice_value` (mixed) modify the value used for each choice. Receives ($value, $post)
     *
     * @param array $form Gravity Form array
     * @return array Modified form
     */
    public function populate_programs( $form ) {
        if ( empty( $form ) || empty( $form['fields'] ) ) {
            return $form;
        }

        $programs = $this->get_programs();
        if ( empty( $programs ) ) {
            return $form;
        }

        foreach ( $form['fields'] as &$field ) {
            $css = rtrim( trim( isset( $field->cssClass ) ? $field->cssClass : '' ) );

            if ( empty( $css ) ) {
                continue;
            }

            // Check for the marker CSS class. You can add this class in the field settings (Appearance -> CSS Class Name).
            if ( strpos( $css, 'populate-programs' ) === false ) {
                continue;
            }

            // Only target select fields (dropdown)
            if ( isset( $field->type ) && 'select' === $field->type ) {
                $choices = array();

                // Add a placeholder first choice to encourage a selection.
                $choices[] = array(
                    'text'  => __( '- Select a program', 'gravityforms-cui-customizations' ),
                    'value' => '',
                );

                foreach ( $programs as $p ) {
                    // Default to using the program title for the choice value (easier for downstream mapping).
                    $text  = get_the_title( $p );
                    $default_value = $text;
                    $value = apply_filters( 'gfcui_program_choice_value', $default_value, $p );

                    $choices[] = array(
                        'text'  => $text,
                        'value' => $value,
                    );
                }

                // Replace field choices.
                $field->choices = $choices;
            }
        }

        return $form;
    }

    /**
     * Populate fields tagged with CSS class `populate-degree-level` with taxonomy terms.
     *
     * @param array $form
     * @return array
     */
    public function populate_degree_levels( $form ) {
        if ( empty( $form ) || empty( $form['fields'] ) ) {
            return $form;
        }

        $terms = $this->get_degree_levels();
        if ( empty( $terms ) ) {
            return $form;
        }

        foreach ( $form['fields'] as &$field ) {
            $css = rtrim( trim( isset( $field->cssClass ) ? $field->cssClass : '' ) );
            if ( empty( $css ) ) {
                continue;
            }

            if ( strpos( $css, 'populate-degree-level' ) === false ) {
                continue;
            }

            if ( isset( $field->type ) && 'select' === $field->type ) {
                $choices = array();
                // placeholder
                $choices[] = array(
                    'text'  => __( '- Select a degree level', 'gravityforms-cui-customizations' ),
                    'value' => '',
                );

                foreach ( $terms as $t ) {
                    // Default to using the term name for degree-level choice values.
                    $default_value = $t->name;
                    $value = apply_filters( 'gfcui_degree_choice_value', $default_value, $t );
                    $choices[] = array(
                        'text'  => $t->name,
                        'value' => $value,
                    );
                }

                $field->choices = $choices;
            }
        }

        return $form;
    }

    /**
     * Register REST routes for plugin.
     */
    public function register_rest_routes() {
        register_rest_route( 'gfcui/v1', '/programs', array(
            'methods'  => 'GET',
            'callback' => array( $this, 'rest_get_programs' ),
            'args'     => array(
                'degree_level' => array(
                    'required' => false,
                ),
            ),
        ) );
    }

    /**
     * REST callback to return programs filtered by degree level slug (optional).
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function rest_get_programs( $request ) {
        $degree = $request->get_param( 'degree_level' );

        $extra = array();
        if ( ! empty( $degree ) ) {
            $tax = apply_filters( 'gfcui_degree_taxonomy', 'degree_level' );

            // Accept term slug, term name, or term ID. Prefer slug for tax_query.
            $term_obj = get_term_by( 'slug', $degree, $tax );
            if ( ! $term_obj ) {
                $term_obj = get_term_by( 'name', $degree, $tax );
            }
            if ( ! $term_obj && is_numeric( $degree ) ) {
                $term_obj = get_term_by( 'id', intval( $degree ), $tax );
            }

            if ( $term_obj ) {
                $extra['tax_query'] = array(
                    array(
                        'taxonomy' => $tax,
                        'field'    => 'slug',
                        'terms'    => $term_obj->slug,
                    ),
                );
            }
        }

        $programs = $this->get_programs( $extra );

        $out = array();
        foreach ( $programs as $p ) {
            // Use Salesforce program name if available, fallback to title
            $sf_program_name = get_post_meta( $p->ID, '_cui_salesforce_program_name', true );
            if ( empty( $sf_program_name ) ) {
                $sf_program_name = get_the_title( $p );
            }

            $text = get_the_title( $p );
            $default_value = $sf_program_name;
            $value = apply_filters( 'gfcui_program_choice_value', $default_value, $p );

            // Get level taxonomy term
            $level_name = '';
            $level_terms = get_the_terms( $p->ID, 'level' );
            if ( ! empty( $level_terms ) && ! is_wp_error( $level_terms ) ) {
                $level_term = array_shift( $level_terms );
                $level_name = $level_term->name;
            }

            // Get student_type taxonomy term
            $student_type_name = '';
            $student_type_terms = get_the_terms( $p->ID, 'student_type' );
            if ( ! empty( $student_type_terms ) && ! is_wp_error( $student_type_terms ) ) {
                $student_type_term = array_shift( $student_type_terms );
                $student_type_name = $student_type_term->name;
            }

            // Get start_term taxonomy terms (can be multiple)
            $start_terms_csv = '';
            $start_term_terms = get_the_terms( $p->ID, 'start_term' );
            if ( ! empty( $start_term_terms ) && ! is_wp_error( $start_term_terms ) ) {
                // Sort terms chronologically (Spring, Summer, Fall, Winter by year)
                usort( $start_term_terms, function( $a, $b ) {
                    // Extract year and season from term names (e.g., "Spring 2024")
                    preg_match( '/(Spring|Summer|Fall|Winter)\s+(\d{4})/', $a->name, $matches_a );
                    preg_match( '/(Spring|Summer|Fall|Winter)\s+(\d{4})/', $b->name, $matches_b );
                    if ( empty( $matches_a ) || empty( $matches_b ) ) {
                        return strcmp( $a->name, $b->name );
                    }

                    $season_order = array( 'Spring' => 1, 'Summer' => 2, 'Fall' => 3, 'Winter' => 4 );
                    $year_a = (int) $matches_a[2];
                    $year_b = (int) $matches_b[2];
                    $season_a = $season_order[ $matches_a[1] ] ?? 0;
                    $season_b = $season_order[ $matches_b[1] ] ?? 0;

                    // Sort by year first, then by season
                    if ( $year_a !== $year_b ) {
                        return $year_a - $year_b;
                    }
                    return $season_a - $season_b;
                } );

                $term_names = array_map( function( $term ) {
                    return $term->name;
                }, $start_term_terms );
                $start_terms_csv = implode( ', ', $term_names );
            }

            $out[] = array(
                'text'         => $text,
                'value'        => $value,
                'level'        => $level_name,
                'student_type' => $student_type_name,
                'start_terms'  => $start_terms_csv,
            );
        }

        return rest_ensure_response( $out );
    }

    /**
     * Enqueue frontend JS used to update program dropdowns when degree-level changes.
     */
    public function enqueue_scripts() {
        // Prefer built assets from assets/dist (webpack output). Fall back to original if missing.
        $dist_js  = plugin_dir_path( GFCUI_PLUGIN_FILE ) . 'assets/dist/gfcui-dependent-programs.js';
        $dist_css = plugin_dir_path( GFCUI_PLUGIN_FILE ) . 'assets/dist/gfcui-styles.css';

        if ( file_exists( $dist_js ) ) {
            $url = plugin_dir_url( GFCUI_PLUGIN_FILE ) . 'assets/dist/gfcui-dependent-programs.js';
            $ver = filemtime( $dist_js );
            wp_register_script( 'gfcui-dependent-programs', $url, array(), $ver, true );
            wp_localize_script( 'gfcui-dependent-programs', 'gfcuiData', array(
                'rest_url' => esc_url_raw( rest_url( 'gfcui/v1/programs' ) ),
            ) );
            wp_enqueue_script( 'gfcui-dependent-programs' );
        } else {
            // Fallback to legacy asset if present
            $js_file = plugin_dir_url( GFCUI_PLUGIN_FILE ) . 'assets/js/gfcui-dependent-programs.js';
            wp_register_script( 'gfcui-dependent-programs', $js_file, array(), '1.0.0', true );
            wp_localize_script( 'gfcui-dependent-programs', 'gfcuiData', array(
                'rest_url' => esc_url_raw( rest_url( 'gfcui/v1/programs' ) ),
            ) );
            wp_enqueue_script( 'gfcui-dependent-programs' );
        }

        if ( file_exists( $dist_css ) ) {
            $css_url = plugin_dir_url( GFCUI_PLUGIN_FILE ) . 'assets/dist/gfcui-styles.css';
            $css_ver = filemtime( $dist_css );
            wp_enqueue_style( 'gfcui-styles', $css_url, array(), $css_ver );
        }
    }
}
