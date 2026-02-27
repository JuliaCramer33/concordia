<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class GFCUI_Program_RFI
 *
 * Handles pre-population of universal RFI Gravity Form on Program single pages.
 */
class GFCUI_Program_RFI {

    public function __construct() {
        add_action( 'init', array( $this, 'init' ) );
    }

    public function init() {
        if ( ! class_exists( 'GFForms' ) ) {
            add_action( 'admin_notices', array( $this, 'admin_notice_gravityforms_missing' ) );
            return;
        }

        add_filter( 'gform_pre_render', array( $this, 'populate_universal_rfi_fields' ) );
        add_filter( 'gform_pre_validation', array( $this, 'populate_universal_rfi_fields' ) );
        add_filter( 'gform_admin_pre_render', array( $this, 'populate_universal_rfi_fields' ) );

        add_filter( 'gform_field_value_program_title', array( $this, 'gform_field_value_program_title' ) );
        add_filter( 'gform_field_value_degree_level', array( $this, 'gform_field_value_degree_level' ) );
    }

    public function admin_notice_gravityforms_missing() {
        if ( ! current_user_can( 'activate_plugins' ) ) {
            return;
        }

        echo '<div class="notice notice-warning is-dismissible">';
        echo '<p><strong>GravityForms CUI Customizations (RFI):</strong> Gravity Forms plugin is not active. RFI prefill will not work until Gravity Forms is installed and activated.</p>';
        echo '</div>';
    }

    /**
     * Return current Program context when on a Program single.
     * @return array|null
     */
    protected function get_current_program_context() {
        if ( ! is_singular() ) {
            return null;
        }

        $cpt = apply_filters( 'gfcui_programs_cpt', 'program' );
        if ( get_post_type() !== $cpt ) {
            return null;
        }

        global $post;
        if ( empty( $post ) || ! is_object( $post ) ) {
            return null;
        }

        $tax = apply_filters( 'gfcui_degree_taxonomy', 'degree_level' );

        // Get Salesforce program name (falls back to title if not set)
        $sf_program_name = get_post_meta( $post->ID, '_cui_salesforce_program_name', true );
        if ( empty( $sf_program_name ) ) {
            $sf_program_name = get_the_title( $post );
        }

        $context = array(
            'id'           => $post->ID,
            'title'        => $sf_program_name,
            'slug'         => $post->post_name,
            'permalink'    => get_permalink( $post ),
            'degree_name'  => '',
            'degree_slug'  => '',
            'level'        => '',
            'student_type' => '',
            'start_terms'  => '',
            'term_season'  => '',
            'term_year'    => '',
        );

        $terms = get_the_terms( $post->ID, $tax );
        if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
            $t = array_shift( $terms );
            $context['degree_name'] = $t->name;
            $context['degree_slug'] = $t->slug;
        }

        // Get level taxonomy term
        $level_terms = get_the_terms( $post->ID, 'level' );
        if ( ! empty( $level_terms ) && ! is_wp_error( $level_terms ) ) {
            $level_term = array_shift( $level_terms );
            $context['level'] = $level_term->name;
        }

        // Get student_type taxonomy term
        $student_type_terms = get_the_terms( $post->ID, 'student_type' );
        if ( ! empty( $student_type_terms ) && ! is_wp_error( $student_type_terms ) ) {
            $student_type_term = array_shift( $student_type_terms );
            $context['student_type'] = $student_type_term->name;
        }

        // Get start_term taxonomy terms (can be multiple)
        $start_term_terms = get_the_terms( $post->ID, 'start_term' );
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
            $context['start_terms'] = implode( ', ', $term_names );

            // Parse the first term for season and year (auto-selected if only one term)
            if ( count( $start_term_terms ) === 1 ) {
                $first_term = $start_term_terms[0]->name;
                if ( stripos( $first_term, 'undecided' ) !== false ) {
                    $context['term_season'] = 'Undecided';
                    $context['term_year'] = '';
                } else {
                    preg_match( '/(Spring|Summer|Fall|Winter)\s+(\d{4})/', $first_term, $matches );
                    if ( ! empty( $matches ) ) {
                        $context['term_season'] = $matches[1];
                        $context['term_year'] = $matches[2];
                    }
                }
            }
        }

        return apply_filters( 'gfcui_current_program_context', $context, $post );
    }

    /**
     * Populate universal RFI fields on GF forms rendered on a Program single.
     */
    public function populate_universal_rfi_fields( $form ) {
        if ( empty( $form ) || empty( $form['fields'] ) ) {
            return $form;
        }

        $context = $this->get_current_program_context();
        if ( empty( $context ) ) {
            return $form;
        }

        foreach ( $form['fields'] as &$field ) {
            $css = rtrim( trim( isset( $field->cssClass ) ? $field->cssClass : '' ) );

            if ( ! empty( $css ) && strpos( $css, 'populate-program-title' ) !== false ) {
                if ( isset( $field->type ) && in_array( $field->type, array( 'text', 'hidden' ), true ) ) {
                    $field->defaultValue = $context['title'];
                }
            }

            if ( ! empty( $css ) && strpos( $css, 'populate-degree-level' ) !== false ) {
                if ( isset( $field->type ) && in_array( $field->type, array( 'text', 'hidden' ), true ) ) {
                    $field->defaultValue = $context['degree_name'];
                }
            }

            if ( ! empty( $css ) && strpos( $css, 'populate-level' ) !== false ) {
                if ( isset( $field->type ) && in_array( $field->type, array( 'text', 'hidden' ), true ) ) {
                    $field->defaultValue = $context['level'];
                }
            }

            if ( ! empty( $css ) && strpos( $css, 'populate-student-type' ) !== false ) {
                if ( isset( $field->type ) && in_array( $field->type, array( 'text', 'hidden' ), true ) ) {
                    $field->defaultValue = $context['student_type'];
                }
            }

            if ( ! empty( $css ) && strpos( $css, 'populate-start-terms' ) !== false ) {
                if ( isset( $field->type ) && 'select' === $field->type && ! empty( $context['start_terms'] ) ) {
                    $start_terms_array = array_map( 'trim', explode( ',', $context['start_terms'] ) );
                    $choices = array();

                    // Only add placeholder if multiple terms
                    if ( count( $start_terms_array ) > 1 ) {
                        $choices[] = array(
                            'text'  => '- Select a start term',
                            'value' => '',
                        );
                    }

                    foreach ( $start_terms_array as $term_name ) {
                        $choices[] = array(
                            'text'  => $term_name,
                            'value' => $term_name,
                        );
                    }

                    $field->choices = $choices;

                    // Auto-select if only one term
                    if ( count( $start_terms_array ) === 1 ) {
                        $field->defaultValue = $start_terms_array[0];
                    }
                }
            }

            // Populate term season field
            if ( ! empty( $css ) && strpos( $css, 'populate-term-season' ) !== false ) {
                if ( isset( $field->type ) && in_array( $field->type, array( 'text', 'hidden' ), true ) ) {
                    $field->defaultValue = $context['term_season'];
                }
            }

            // Populate term year field
            if ( ! empty( $css ) && strpos( $css, 'populate-term-year' ) !== false ) {
                if ( isset( $field->type ) && in_array( $field->type, array( 'text', 'hidden' ), true ) ) {
                    $field->defaultValue = $context['term_year'];
                }
            }

            if ( isset( $field->inputName ) && ! empty( $field->inputName ) ) {
                $name = $field->inputName;
                if ( 'program_title' === $name ) {
                    if ( isset( $field->type ) && in_array( $field->type, array( 'text', 'hidden' ), true ) ) {
                        $field->defaultValue = $context['title'];
                    }
                }
                if ( 'degree_level' === $name ) {
                    if ( isset( $field->type ) && in_array( $field->type, array( 'text', 'hidden' ), true ) ) {
                        $field->defaultValue = $context['degree_name'];
                    }
                }
                if ( 'level' === $name ) {
                    if ( isset( $field->type ) && in_array( $field->type, array( 'text', 'hidden' ), true ) ) {
                        $field->defaultValue = $context['level'];
                    }
                }
                if ( 'student_type' === $name ) {
                    if ( isset( $field->type ) && in_array( $field->type, array( 'text', 'hidden' ), true ) ) {
                        $field->defaultValue = $context['student_type'];
                    }
                }
            }
        }

        return $form;
    }

    public function gform_field_value_program_title( $value ) {
        $context = $this->get_current_program_context();
        if ( empty( $context ) ) {
            return $value;
        }
        return $context['title'];
    }

    public function gform_field_value_degree_level( $value ) {
        $context = $this->get_current_program_context();
        if ( empty( $context ) ) {
            return $value;
        }
        return $context['degree_name'];
    }
}
