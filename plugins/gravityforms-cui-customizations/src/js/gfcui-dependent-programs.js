/*
 * Updates Program dropdowns when Degree Level changes.
 * Fetches filtered programs via the plugin REST endpoint and replaces options.
 */
(function () {
    'use strict';

    function fetchPrograms(degreeSlug, restUrl) {
        var url = restUrl;
        if ( degreeSlug ) {
            url += '?degree_level=' + encodeURIComponent( degreeSlug );
        }

        return fetch( url, {
            method: 'GET',
            credentials: 'same-origin',
        } ).then(function (resp) {
            if ( ! resp.ok ) {
                throw new Error('Network response was not ok');
            }
            return resp.json();
        });
    }

    function updateProgramSelect(selectEl, choices) {
        if ( ! selectEl ) return;

        while ( selectEl.firstChild ) {
            selectEl.removeChild( selectEl.firstChild );
        }

        var placeholder = document.createElement('option');
        placeholder.value = '';
        placeholder.textContent = '– Select a program';
        selectEl.appendChild( placeholder );

        choices.forEach(function (c) {
            var opt = document.createElement('option');
            opt.value = c.value;
            opt.textContent = c.text;
            // Store level, student_type, and start_terms as data attributes
            if ( c.level ) {
                opt.setAttribute( 'data-level', c.level );
            }
            if ( c.student_type ) {
                opt.setAttribute( 'data-student-type', c.student_type );
            }
            if ( c.start_terms ) {
                opt.setAttribute( 'data-start-terms', c.start_terms );
            }
            selectEl.appendChild( opt );
        });

        var evt = new Event('change', { bubbles: true });
        selectEl.dispatchEvent( evt );
    }

    function updateHiddenFields(form, selectedOption) {
        if ( ! form || ! selectedOption ) return;

        var level = selectedOption.getAttribute( 'data-level' ) || '';
        var studentType = selectedOption.getAttribute( 'data-student-type' ) || '';
        var startTerms = selectedOption.getAttribute( 'data-start-terms' ) || '';

        // Find and update level hidden fields
        var levelFields = form.querySelectorAll('input.populate-level, .populate-level input');
        levelFields.forEach(function (field) {
            field.value = level;
        });

        // Find and update student type hidden fields
        var studentTypeFields = form.querySelectorAll('input.populate-student-type, .populate-student-type input');
        studentTypeFields.forEach(function (field) {
            field.value = studentType;
        });

        // Find and update start terms dropdowns
        var startTermSelects = form.querySelectorAll('select.populate-start-terms, .populate-start-terms select');
        startTermSelects.forEach(function (select) {
            updateStartTermsDropdown( select, startTerms );
        });
    }

    function updateStartTermsDropdown(selectEl, startTermsCSV) {
        if ( ! selectEl ) return;

        // Clear existing options
        while ( selectEl.firstChild ) {
            selectEl.removeChild( selectEl.firstChild );
        }

        if ( ! startTermsCSV ) {
            var noTerms = document.createElement('option');
            noTerms.value = '';
            noTerms.textContent = 'No start terms available';
            selectEl.appendChild( noTerms );
            return;
        }

        // Split comma-separated values
        var terms = startTermsCSV.split(',').map(function(t) { return t.trim(); });

        // Add placeholder if multiple terms
        if ( terms.length > 1 ) {
            var placeholder = document.createElement('option');
            placeholder.value = '';
            placeholder.textContent = '– Select a start term';
            selectEl.appendChild( placeholder );
        }

        // Add all start terms as options
        terms.forEach(function (term) {
            var opt = document.createElement('option');
            opt.value = term;
            opt.textContent = term;
            selectEl.appendChild( opt );
        });

        // Auto-select if only one term
        if ( terms.length === 1 ) {
            selectEl.value = terms[0];
            // Trigger season/year population for auto-selected term
            updateTermSeasonAndYear( selectEl.closest('form'), terms[0] );
        }

        // Trigger change event
        var evt = new Event('change', { bubbles: true });
        selectEl.dispatchEvent( evt );
    }

    function updateTermSeasonAndYear(form, selectedTerm) {
        if ( ! form || ! selectedTerm ) {
            // Clear fields if no term selected
            var seasonFields = form.querySelectorAll('input.populate-term-season, .populate-term-season input');
            seasonFields.forEach(function (field) {
                field.value = '';
            });
            var yearFields = form.querySelectorAll('input.populate-term-year, .populate-term-year input');
            yearFields.forEach(function (field) {
                field.value = '';
            });
            return;
        }

        var season = '';
        var year = '';

        // Check for "Undecided" or similar
        if ( selectedTerm.toLowerCase().indexOf('undecided') !== -1 ) {
            season = 'Undecided';
            year = '';
        } else {
            // Parse "Spring 2026" format
            var match = selectedTerm.match(/(Spring|Summer|Fall)\s+(\d{4})/);
            if ( match ) {
                season = match[1];
                year = match[2];
            }
        }

        // Update season fields
        var seasonFields = form.querySelectorAll('input.populate-term-season, .populate-term-season input');
        seasonFields.forEach(function (field) {
            field.value = season;
        });

        // Update year fields
        var yearFields = form.querySelectorAll('input.populate-term-year, .populate-term-year input');
        yearFields.forEach(function (field) {
            field.value = year;
        });
    }

    document.addEventListener('change', function (e) {
        var target = e.target;
        if ( ! target ) return;

        // The select element may live inside a wrapper that has the class
        // `populate-degree-level`. Detect either:
        // - the select itself has the class `populate-degree-level`, OR
        // - the changed element is a select contained within an ancestor that has `.populate-degree-level`.
        var isDegreeSelect = false;
        if ( target.tagName === 'SELECT' ) {
            if ( target.matches && target.matches('select.populate-degree-level') ) {
                isDegreeSelect = true;
            } else if ( target.closest && target.closest('.populate-degree-level') ) {
                isDegreeSelect = true;
            }
        }

        if ( isDegreeSelect ) {
            var degree = target.value;
            var form = target.closest('form');
            if ( ! form ) return;

            // Find program selects either marked directly with `.populate-programs`
            // or selects that live inside a wrapper with that class.
            var programSelects = form.querySelectorAll('select.populate-programs, .populate-programs select');

            // Find the localized rest_url on the page from window.gfcuiData if available
            var restUrl = (window.gfcuiData && window.gfcuiData.rest_url) ? window.gfcuiData.rest_url : '/wp-json/gfcui/v1/programs';

            fetchPrograms( degree, restUrl ).then(function (choices) {
                programSelects.forEach(function (sel) {
                    updateProgramSelect( sel, choices );
                });
            }).catch(function (err) {
                // eslint-disable-next-line no-console
                console.error('gfcui fetch programs error', err);
            });
        }
    });

    // Listen for program selection changes to update level and student type hidden fields
    document.addEventListener('change', function (e) {
        var target = e.target;
        if ( ! target || target.tagName !== 'SELECT' ) return;

        // Check if this is a program select
        var isProgramSelect = false;
        if ( target.matches && target.matches('select.populate-programs') ) {
            isProgramSelect = true;
        } else if ( target.closest && target.closest('.populate-programs') ) {
            isProgramSelect = true;
        }

        if ( isProgramSelect ) {
            var form = target.closest('form');
            var selectedOption = target.options[target.selectedIndex];
            if ( form && selectedOption ) {
                updateHiddenFields( form, selectedOption );
            }
        }
    });

    // Listen for start term selection changes to update season and year fields
    document.addEventListener('change', function (e) {
        var target = e.target;
        if ( ! target || target.tagName !== 'SELECT' ) return;

        // Check if this is a start terms select
        var isStartTermSelect = false;
        if ( target.matches && target.matches('select.populate-start-terms') ) {
            isStartTermSelect = true;
        } else if ( target.closest && target.closest('.populate-start-terms') ) {
            isStartTermSelect = true;
        }

        if ( isStartTermSelect ) {
            var form = target.closest('form');
            var selectedTerm = target.value;
            if ( form ) {
                updateTermSeasonAndYear( form, selectedTerm );
            }
        }
    });
})();
