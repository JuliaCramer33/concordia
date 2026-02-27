<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class GFCUI_Start_Terms
 *
 * Generic handler for parsing start term dropdowns into season and year fields.
 * Works on any Gravity Forms form, not just program-specific forms.
 *
 * Usage:
 * 1. Add CSS class 'parse-start-terms' to start terms dropdown field
 * 2. Add two hidden fields with CSS classes:
 *    - 'populate-term-season' (receives: Spring, Summer, Fall, Winter or Undecided)
 *    - 'populate-term-year' (receives: YYYY or empty for Undecided)
 * 3. Map these hidden fields to Salesforce term and year fields
 */
class GFCUI_Start_Terms {

    public function __construct() {
        add_action( 'init', array( $this, 'init' ) );
    }

    public function init() {
        if ( ! class_exists( 'GFForms' ) ) {
            add_action( 'admin_notices', array( $this, 'admin_notice_gravityforms_missing' ) );
            return;
        }

        // Pre-populate season and year fields on form render if dropdown has a default value
        add_filter( 'gform_pre_render', array( $this, 'prepopulate_season_year_fields' ) );
        add_filter( 'gform_pre_validation', array( $this, 'prepopulate_season_year_fields' ) );
        add_filter( 'gform_admin_pre_render', array( $this, 'prepopulate_season_year_fields' ) );

        // Conditionally enqueue script only when form has parse-start-terms field
        add_filter( 'gform_pre_render', array( $this, 'maybe_enqueue_scripts' ) );
    }

    public function admin_notice_gravityforms_missing() {
        if ( ! current_user_can( 'activate_plugins' ) ) {
            return;
        }

        echo '<div class="notice notice-warning is-dismissible">';
        echo '<p><strong>GravityForms CUI Customizations (Start Terms):</strong> Gravity Forms plugin is not active. Start terms parsing will not work until Gravity Forms is installed and activated.</p>';
        echo '</div>';
    }

    /**
     * Parse a start term string into season and year components.
     *
     * @param string $term_value The start term value (e.g., "Spring 2026", "Undecided")
     * @return array Array with 'season' and 'year' keys
     */
    protected function parse_start_term( $term_value ) {
        $result = array(
            'season' => '',
            'year'   => '',
        );

        if ( empty( $term_value ) ) {
            return $result;
        }

        // Check for "Undecided" or similar
        if ( stripos( $term_value, 'undecided' ) !== false ) {
            $result['season'] = 'Undecided';
            $result['year'] = '';
            return $result;
        }

        // Parse "Spring 2026" format
        if ( preg_match( '/(Spring|Summer|Fall|Winter)\s+(\d{4})/i', $term_value, $matches ) ) {
            $result['season'] = ucfirst( strtolower( $matches[1] ) ); // Normalize capitalization
            $result['year'] = $matches[2];
        }

        return $result;
    }

    /**
     * Pre-populate season and year hidden fields based on dropdown default values.
     *
     * @param array $form Gravity Form array
     * @return array Modified form
     */
    public function prepopulate_season_year_fields( $form ) {
        if ( empty( $form ) || empty( $form['fields'] ) ) {
            return $form;
        }

        // First pass: find start terms dropdown and get its default value
        $start_term_value = '';
        foreach ( $form['fields'] as $field ) {
            $css = rtrim( trim( isset( $field->cssClass ) ? $field->cssClass : '' ) );
            if ( ! empty( $css ) && strpos( $css, 'parse-start-terms' ) !== false ) {
                if ( isset( $field->type ) && 'select' === $field->type ) {
                    // Check if field has a default value
                    if ( ! empty( $field->defaultValue ) ) {
                        $start_term_value = $field->defaultValue;
                        break;
                    }
                    // Or check if first choice is auto-selected (not a placeholder)
                    if ( ! empty( $field->choices ) && count( $field->choices ) === 1 ) {
                        $start_term_value = $field->choices[0]['value'];
                        break;
                    }
                }
            }
        }

        // If we have a start term value, parse it and populate season/year fields
        if ( ! empty( $start_term_value ) ) {
            $parsed = $this->parse_start_term( $start_term_value );

            // Second pass: populate season and year fields
            foreach ( $form['fields'] as &$field ) {
                $css = rtrim( trim( isset( $field->cssClass ) ? $field->cssClass : '' ) );

                if ( ! empty( $css ) && strpos( $css, 'populate-term-season' ) !== false ) {
                    if ( isset( $field->type ) && in_array( $field->type, array( 'text', 'hidden' ), true ) ) {
                        $field->defaultValue = $parsed['season'];
                    }
                }

                if ( ! empty( $css ) && strpos( $css, 'populate-term-year' ) !== false ) {
                    if ( isset( $field->type ) && in_array( $field->type, array( 'text', 'hidden' ), true ) ) {
                        $field->defaultValue = $parsed['year'];
                    }
                }
            }
        }

        return $form;
    }

    /**
     * Check if form has a field with parse-start-terms CSS class.
     * If yes, enqueue the JavaScript.
     *
     * @param array $form Gravity Form array
     * @return array Unmodified form
     */
    public function maybe_enqueue_scripts( $form ) {
        if ( empty( $form ) || empty( $form['fields'] ) ) {
            return $form;
        }

        // Check if any field has the parse-start-terms class
        foreach ( $form['fields'] as $field ) {
            $css = rtrim( trim( isset( $field->cssClass ) ? $field->cssClass : '' ) );
            if ( ! empty( $css ) && strpos( $css, 'parse-start-terms' ) !== false ) {
                // Found a field with parse-start-terms, enqueue the script
                $this->enqueue_scripts();
                break; // Only need to enqueue once
            }
        }

        return $form;
    }

    /**
     * Enqueue frontend JavaScript for dynamic start term parsing.
     * Only called when a form with parse-start-terms field is detected.
     */
    protected function enqueue_scripts() {
        // Prefer built assets from assets/dist (webpack output). Fall back to src if missing.
        $dist_js = plugin_dir_path( GFCUI_PLUGIN_FILE ) . 'assets/dist/gfcui-start-terms.js';
        $src_js  = plugin_dir_path( GFCUI_PLUGIN_FILE ) . 'src/js/gfcui-start-terms.js';

        if ( file_exists( $dist_js ) ) {
            $url = plugin_dir_url( GFCUI_PLUGIN_FILE ) . 'assets/dist/gfcui-start-terms.js';
            $ver = filemtime( $dist_js );
            wp_enqueue_script( 'gfcui-start-terms', $url, array(), $ver, true );
        } elseif ( file_exists( $src_js ) ) {
            // Fallback to source file if build doesn't exist
            $url = plugin_dir_url( GFCUI_PLUGIN_FILE ) . 'src/js/gfcui-start-terms.js';
            $ver = filemtime( $src_js );
            wp_enqueue_script( 'gfcui-start-terms', $url, array(), $ver, true );
        }
    }
}
