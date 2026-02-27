// Initialize stable sweep color (--btn-sweep) from initial text color
function isTransparentColor(value) {
  return (
    !value ||
    value === 'transparent' ||
    value === 'rgba(0, 0, 0, 0)' ||
    value === 'hsla(0, 0%, 0%, 0)'
  );
}

function getEffectiveBackgroundColor(element) {
  let node = element;
  while (node && node !== document.documentElement) {
    const cs = getComputedStyle(node);
    const bg = cs.backgroundColor;
    if (!isTransparentColor(bg)) {
      return bg;
    }
    node = node.parentElement;
  }
  // Fallback to current text color if nothing found
  return getComputedStyle(element).color;
}

function initOneButton(btn) {
  const cs = getComputedStyle(btn);
  // Stable overlay color = initial text color
  btn.style.setProperty('--btn-sweep', cs.color);
  // Hover target = effective background (walk up if transparent)
  const effective = getEffectiveBackgroundColor(btn);
  btn.style.setProperty('--btn-bg', effective);
}

export function initButtonSweepVars(root = document) {
  const SF_SUBMIT = '.searchandfilter .sf-field-submit input[type="submit"]';
  const selector =
    '.wp-element-button, .wp-block-button .wp-block-button__link, ' +
    '.gform_wrapper .gform_button, .gform_wrapper button[type="submit"], .gform_wrapper input[type="submit"], ' +
    '.gform_wrapper .gform_next_button, .gform_wrapper .gform_previous_button, ' +
    '.searchandfilter button[type="submit"], .searchandfilter input[type="submit"], ' +
    SF_SUBMIT;
  // Defer to next paint to ensure computed styles (esp. from async CSS) are ready
  const run = () => root.querySelectorAll(selector).forEach(initOneButton);
  if (typeof requestAnimationFrame === 'function') {
    requestAnimationFrame(() => requestAnimationFrame(run));
  } else {
    run();
  }

  // Re-evaluate on hover entry in case background is applied dynamically
  root.addEventListener?.(
    'mouseenter',
    (e) => {
      const target = e.target;
      if (
        target &&
        (target.matches?.('.wp-element-button') ||
          target.matches?.('.wp-block-button .wp-block-button__link') ||
          target.matches?.('.gform_wrapper .gform_button') ||
          target.matches?.('.gform_wrapper button[type="submit"]') ||
          target.matches?.('.gform_wrapper input[type="submit"]') ||
          target.matches?.('.gform_wrapper .gform_next_button') ||
          target.matches?.('.gform_wrapper .gform_previous_button') ||
          target.matches?.('.searchandfilter button[type="submit"]') ||
          target.matches?.('.searchandfilter input[type="submit"]') ||
          target.matches?.(SF_SUBMIT))
      ) {
        initOneButton(target);
      }
    },
    true
  );
  // Re-evaluate on focus (keyboard/touch)
  root.addEventListener?.(
    'focusin',
    (e) => {
      const target = e.target;
      if (
        target &&
        (target.matches?.('.wp-element-button') ||
          target.matches?.('.wp-block-button .wp-block-button__link') ||
          target.matches?.('.gform_wrapper .gform_button') ||
          target.matches?.('.gform_wrapper button[type="submit"]') ||
          target.matches?.('.gform_wrapper input[type="submit"]') ||
          target.matches?.('.gform_wrapper .gform_next_button') ||
          target.matches?.('.gform_wrapper .gform_previous_button') ||
          target.matches?.('.searchandfilter button[type="submit"]') ||
          target.matches?.('.searchandfilter input[type="submit"]') ||
          target.matches?.(SF_SUBMIT))
      ) {
        initOneButton(target);
      }
    },
    true
  );
}
