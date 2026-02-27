(function () {
  'use strict';

  // Check if GPA calculator exists on the page
  const gradeRowsContainer = document.getElementById('gradeRows');
  if (!gradeRowsContainer) {
    return;
  }

  // Get DOM elements
  const cumulativeForm = document.querySelector('form[name="form11"]');
  const totalCreditsDisplay = document.getElementById('totalCredits');
  const gpaDisplay = document.getElementById('gpa');
  const totalGradePointsDisplay = document.getElementById('totalGradePoints');

  // Grade point mapping
  const GRADE_MAP = {
    a: 4.0,
    'a-': 3.7,
    'b+': 3.3,
    b: 3.0,
    'b-': 2.7,
    'c+': 2.3,
    c: 2.0,
    'c-': 1.7,
    'd+': 1.3,
    d: 1.0,
    'd-': 0.7,
    f: 0.0,
  };

  // Truncate to 2 decimal places, no rounding
  function formatDecimal(value) {
    return value.toFixed(2);
  }

  // Validate number input
  function isValidNumber(value, min, max) {
    const num = parseFloat(value);
    return !isNaN(num) && num >= min && num <= max;
  }

  // Convert grade letter to point value
  function getGradePoints(grade) {
    if (!grade) {
      return null;
    }
    const normalizedGrade = grade.toLowerCase().trim();
    return GRADE_MAP[normalizedGrade] !== undefined
      ? GRADE_MAP[normalizedGrade]
      : null;
  }

  // Show validation error on input
  function showInputError(input, show = true) {
    if (show) {
      input.classList.add('input-error');
      input.setAttribute('aria-invalid', 'true');
    } else {
      input.classList.remove('input-error');
      input.removeAttribute('aria-invalid');
    }
  }

  // Compute grade points for a single row
  function computeField(input) {
    const row = input.closest('tr');
    if (!row) {
      return;
    }

    const unitsInput = row.querySelector('input[name="units"]');
    const gradeInput = row.querySelector('input[name="grade"]');
    const gradePointsField = row.querySelector('input[name="gradepoints"]');

    if (!unitsInput || !gradeInput || !gradePointsField) {
      return;
    }

    const unitsValue = unitsInput.value.trim();
    const gradeValue = gradeInput.value.trim(); // eslint-disable-line @wordpress/no-unused-vars-before-return

    // Validate units
    if (unitsValue && !isValidNumber(unitsValue, 0.5, 200)) {
      showInputError(unitsInput, true);
      gradePointsField.value = '';
      computeTotals();
      return;
    }

    // Calculate grade points if both fields have valid values
    if (unitsValue && gradeValue) {
      const units = parseFloat(unitsValue);
      const gradePoints = getGradePoints(gradeValue);

      if (gradePoints !== null) {
        showInputError(gradeInput, false);
        gradePointsField.value = formatDecimal(gradePoints * units);
      } else {
        showInputError(gradeInput, true);
        gradePointsField.value = '';
      }
    } else {
      gradePointsField.value = '';
    }

    computeTotals();
  }

  // Calculate totals and GPA
  function computeTotals() {
    const rows = document.querySelectorAll('.grade-row');
    let totalGradePoints = 0;
    let totalUnits = 0;

    rows.forEach((row) => {
      const units =
        parseFloat(row.querySelector('input[name="units"]').value) || 0;
      const gradePoints =
        parseFloat(row.querySelector('input[name="gradepoints"]').value) || 0;

      totalUnits += units;
      totalGradePoints += gradePoints;
    });

    const gpa = totalUnits
      ? Math.trunc((totalGradePoints / totalUnits) * 100) / 100
      : 0;

    totalCreditsDisplay.textContent = formatDecimal(totalUnits);
    gpaDisplay.textContent = formatDecimal(gpa);
    totalGradePointsDisplay.textContent = formatDecimal(totalGradePoints);
  }

  // Add a new row
  function addRow() {
    const newRow = document.createElement('tr');
    newRow.classList.add('grade-row');

    const unitsCell = document.createElement('td');
    const unitsInput = document.createElement('input');
    unitsInput.type = 'text';
    unitsInput.name = 'units';
    unitsInput.setAttribute('aria-label', 'Number of credits');
    unitsInput.addEventListener('input', (e) => computeField(e.target));
    unitsCell.appendChild(unitsInput);

    const gradeCell = document.createElement('td');
    const gradeInput = document.createElement('input');
    gradeInput.type = 'text';
    gradeInput.name = 'grade';
    gradeInput.setAttribute('aria-label', 'Letter grade');
    gradeInput.addEventListener('input', (e) => computeField(e.target));
    gradeCell.appendChild(gradeInput);

    const gradePointsCell = document.createElement('td');
    const gradePointsInput = document.createElement('input');
    gradePointsInput.type = 'text';
    gradePointsInput.name = 'gradepoints';
    gradePointsInput.readOnly = true;
    gradePointsInput.setAttribute('aria-label', 'Grade points (calculated)');
    gradePointsCell.appendChild(gradePointsInput);

    const actionsCell = document.createElement('td');
    const removeButton = document.createElement('button');
    removeButton.type = 'button';
    removeButton.className = 'button--remove';
    removeButton.textContent = 'Remove';
    removeButton.setAttribute('aria-label', 'Remove this row');
    removeButton.addEventListener('click', () => removeRow(removeButton));
    actionsCell.appendChild(removeButton);

    newRow.appendChild(unitsCell);
    newRow.appendChild(gradeCell);
    newRow.appendChild(gradePointsCell);
    newRow.appendChild(actionsCell);

    gradeRowsContainer.appendChild(newRow);
  }

  // Remove a row
  function removeRow(button) {
    const row = button.closest('tr');
    if (row) {
      row.remove();
      computeTotals();
    }
  }

  // Calculate cumulative GPA
  function calculateCumulativeGPA() {
    if (!cumulativeForm) {
      return;
    }

    const priorCredits = parseFloat(cumulativeForm.priorCredits.value) || 0;
    const priorGPA = parseFloat(cumulativeForm.priorGPA.value) || 0;
    const currentCredits = parseFloat(totalCreditsDisplay.textContent) || 0;
    const currentGradePoints =
      parseFloat(totalGradePointsDisplay.textContent) || 0;

    const totalCredits = priorCredits + currentCredits;
    const cumulativeGPA = totalCredits
      ? (priorGPA * priorCredits + currentGradePoints) / totalCredits
      : 0;

    cumulativeForm.cumgpa.value = formatDecimal(cumulativeGPA);
  }

  // Clear and reset the form
  function clearForm() {
    if (cumulativeForm) {
      cumulativeForm.reset();
    }
    gradeRowsContainer.innerHTML = '';
    totalCreditsDisplay.textContent = '0.00';
    gpaDisplay.textContent = '0.00';
    totalGradePointsDisplay.textContent = '0.00';

    // Add 5 initial rows
    for (let i = 0; i < 5; i++) {
      addRow();
    }
  }

  // Set up event listeners
  function initializeEventListeners() {
    // Add row button
    const addRowButtons = document.querySelectorAll(
      'button[onclick*="addRow"]'
    );
    addRowButtons.forEach((button) => {
      button.removeAttribute('onclick');
      button.addEventListener('click', addRow);
    });

    // Calculate cumulative GPA button
    const calcButtons = document.querySelectorAll(
      'button[onclick*="calculateCumulativeGPA"]'
    );
    calcButtons.forEach((button) => {
      button.removeAttribute('onclick');
      button.addEventListener('click', calculateCumulativeGPA);
    });

    // Reset button
    const resetButtons = document.querySelectorAll(
      'button[onclick*="clearForm"]'
    );
    resetButtons.forEach((button) => {
      button.removeAttribute('onclick');
      button.addEventListener('click', clearForm);
    });

    // Existing row inputs
    const existingRows = document.querySelectorAll('.grade-row');
    existingRows.forEach((row) => {
      const unitsInput = row.querySelector('input[name="units"]');
      const gradeInput = row.querySelector('input[name="grade"]');
      const removeButton = row.querySelector('button');

      if (unitsInput) {
        unitsInput.removeAttribute('oninput');
        unitsInput.addEventListener('input', (e) => computeField(e.target));
      }
      if (gradeInput) {
        gradeInput.removeAttribute('oninput');
        gradeInput.addEventListener('input', (e) => computeField(e.target));
      }
      if (removeButton) {
        removeButton.removeAttribute('onclick');
        removeButton.addEventListener('click', () => removeRow(removeButton));
      }
    });
  }

  // Initialize calculator
  function init() {
    initializeEventListeners();

    // Add initial rows if none exist
    const existingRows = document.querySelectorAll('.grade-row');
    if (existingRows.length === 0) {
      for (let i = 0; i < 5; i++) {
        addRow();
      }
    }
  }

  // Run initialization when DOM is ready
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
