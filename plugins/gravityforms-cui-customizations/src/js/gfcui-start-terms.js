/*
 * Generic Start Terms Parser for Gravity Forms
 *
 * Parses start term dropdown values (e.g., "Spring 2026") into separate
 * season and year components for Salesforce integration.
 *
 * Works on any form - just add the appropriate CSS classes:
 * - Dropdown: parse-start-terms
 * - Season field: populate-term-season
 * - Year field: populate-term-year
 */
(function () {
    'use strict';

    /**
     * Parse a start term string into season and year.
     * @param {string} termValue - The selected term (e.g., "Spring 2026", "Undecided")
     * @returns {object} Object with season and year properties
     */
    function parseStartTerm(termValue) {
        var result = {
            season: '',
            year: ''
        };

        if ( ! termValue ) {
            return result;
        }

        // Check for "Undecided" or similar
        if ( termValue.toLowerCase().indexOf('undecided') !== -1 ) {
            result.season = 'Undecided';
            result.year = '';
            return result;
        }

        // Parse "Spring 2026" format (case-insensitive)
        var match = termValue.match(/(Spring|Summer|Fall|Winter)\s+(\d{4})/i);
        if ( match ) {
            // Normalize capitalization (Spring, not SPRING or spring)
            result.season = match[1].charAt(0).toUpperCase() + match[1].slice(1).toLowerCase();
            result.year = match[2];
        }

        return result;
    }

    /**
     * Update season and year hidden fields based on selected term.
     * @param {HTMLElement} form - The form containing the fields
     * @param {string} selectedTerm - The selected start term value
     */
    function updateSeasonAndYearFields(form, selectedTerm) {
        if ( ! form ) return;

        var parsed = parseStartTerm( selectedTerm );

        // Find and update season fields
        var seasonFields = form.querySelectorAll('input.populate-term-season, .populate-term-season input');
        seasonFields.forEach(function (field) {
            field.value = parsed.season;
            // Trigger change event for any listening code
            var evt = new Event('change', { bubbles: true });
            field.dispatchEvent( evt );
        });

        // Find and update year fields
        var yearFields = form.querySelectorAll('input.populate-term-year, .populate-term-year input');
        yearFields.forEach(function (field) {
            field.value = parsed.year;
            // Trigger change event for any listening code
            var evt = new Event('change', { bubbles: true });
            field.dispatchEvent( evt );
        });
    }

    /**
     * Initialize parsing for a start terms dropdown on page load.
     * Useful when the dropdown has a pre-selected value.
     * @param {HTMLElement} selectEl - The select element
     */
    function initializeStartTermsDropdown(selectEl) {
        if ( ! selectEl || ! selectEl.value ) return;

        var form = selectEl.closest('form');
        if ( form ) {
            updateSeasonAndYearFields( form, selectEl.value );
        }
    }

    // Listen for start term dropdown changes
    document.addEventListener('change', function (e) {
        var target = e.target;
        if ( ! target || target.tagName !== 'SELECT' ) return;

        // Check if this is a start terms dropdown with parse-start-terms class
        var isStartTermSelect = false;
        if ( target.matches && target.matches('select.parse-start-terms') ) {
            isStartTermSelect = true;
        } else if ( target.closest && target.closest('.parse-start-terms') ) {
            isStartTermSelect = true;
        }

        if ( isStartTermSelect ) {
            var form = target.closest('form');
            var selectedTerm = target.value;
            if ( form ) {
                updateSeasonAndYearFields( form, selectedTerm );
            }
        }
    });

    // Initialize any pre-selected dropdowns on page load
    document.addEventListener('DOMContentLoaded', function () {
        var startTermDropdowns = document.querySelectorAll('select.parse-start-terms, .parse-start-terms select');
        startTermDropdowns.forEach(function (dropdown) {
            initializeStartTermsDropdown( dropdown );
        });
    });

    // Also initialize after Gravity Forms renders (for AJAX forms)
    document.addEventListener('gform_post_render', function (e) {
        // e.detail contains form ID if available
        var formId = e.detail ? e.detail[0] : null;
        var selector = formId
            ? '#gform_' + formId + ' select.parse-start-terms, #gform_' + formId + ' .parse-start-terms select'
            : 'select.parse-start-terms, .parse-start-terms select';

        var startTermDropdowns = document.querySelectorAll( selector );
        startTermDropdowns.forEach(function (dropdown) {
            initializeStartTermsDropdown( dropdown );
        });
    });
})();
