/**
 * Accessible skip-link behavior (lean)
 * - Works for `.skip-link` / `[data-skip-to]` and in-content same-page anchors
 * - Respects `prefers-reduced-motion` (handled via CSS)
 * - Focuses the target even if not natively focusable
 * - Relies on CSS `scroll-padding-top: var(--cui-scroll-offset)` for offset
 */
function prefersReducedMotion() {
  try {
    return (
      window.matchMedia &&
      window.matchMedia('(prefers-reduced-motion: reduce)').matches
    );
  } catch (e) {
    return false;
  }
}

// Safely retrieve the current hash without directly referencing window.location
function safeGetHash() {
  try {
    const hasGlobal = typeof globalThis !== 'undefined';
    const loc =
      hasGlobal && 'location' in globalThis ? globalThis.location : null;
    if (!loc || typeof loc.hash !== 'string') {
      return '';
    }
    return String(loc.hash);
  } catch (e) {
    return '';
  }
}

function getTargetFromTrigger(trigger) {
  // Support anchors with href="#id"
  if (trigger instanceof HTMLAnchorElement && trigger.hash) {
    return document.getElementById(trigger.hash.slice(1));
  }
  // Support generic elements with data-skip-to="#id" or "selector"
  const selector = trigger.getAttribute('data-skip-to');
  if (selector) {
    if (selector.startsWith('#')) {
      return (
        document.getElementById(selector.slice(1)) ||
        document.querySelector(selector)
      );
    }
    return document.querySelector(selector);
  }
  return null;
}

function focusTarget(target) {
  if (!target) {
    return;
  }
  const previouslyHadTabindex = target.hasAttribute('tabindex');
  if (!previouslyHadTabindex) {
    target.setAttribute('tabindex', '-1');
  }
  // Avoid native scroll during focus; we handle scrolling separately
  try {
    target.focus({ preventScroll: true });
  } catch (e) {
    target.focus();
  }
  if (!previouslyHadTabindex) {
    const remove = () => {
      target.removeEventListener('blur', remove);
      target.removeAttribute('tabindex');
    };
    target.addEventListener('blur', remove, { once: true });
  }
}

function scrollToTarget(target) {
  if (!target) {
    return;
  }
  const reduce = prefersReducedMotion();
  // Let CSS scroll-padding-top handle the offset
  try {
    target.scrollIntoView({
      behavior: reduce ? 'auto' : 'smooth',
      block: 'start',
    });
  } catch (e) {
    target.scrollIntoView(true);
  }
}

// Open ancestor <details> accordions (Core Details block / our accordion pattern)
function expandAccordionsFor(target) {
  if (!target) {
    return false;
  }
  // Start at the target itself, then walk up.
  let node = target;
  let expanded = false;
  while (node && node !== document.documentElement) {
    if (node.tagName && node.tagName.toLowerCase() === 'details') {
      if (!node.open) {
        node.open = true;
        expanded = true;
      }
    }
    node = node.parentElement;
  }
  return expanded;
}

function activateSkip(target, updateHash = true) {
  if (!target) {
    return;
  }
  // If the target is a <summary>, ensure its parent <details> opens.
  if (target.tagName && target.tagName.toLowerCase() === 'summary') {
    const ownerDetails = target.closest('details');
    if (ownerDetails && !ownerDetails.open) {
      ownerDetails.open = true;
    }
  }
  // Derive the element we will actually focus/scroll to:
  // - If hash points to <details>, prefer its <summary> for focus/scroll.
  let focusEl = target;
  if (target.tagName && target.tagName.toLowerCase() === 'details') {
    const sum = target.querySelector('summary');
    if (sum) {
      focusEl = sum;
    }
    // Ensure details is open even if expandAccordionsFor skips (it won't).
    if (!target.open) {
      target.open = true;
    }
  }

  const run = () => {
    scrollToTarget(focusEl);
    focusTarget(focusEl);
    if (updateHash) {
      const id = target.getAttribute('id');
      if (id) {
        try {
          history.replaceState(null, '', `#${id}`);
        } catch (e) {
          // no-op
        }
      }
    }
  };
  // Ensure any ancestor accordions are open before scrolling/focusing
  const expanded = expandAccordionsFor(target);
  if (expanded) {
    // Wait one frame so layout updates before scrolling
    requestAnimationFrame(run);
  } else {
    run();
  }
}

export function initSkipLinks(root = document) {
  if (!root || !root.addEventListener) {
    return;
  }
  // Delegate click for any `.skip-link` or `[data-skip-to]`
  root.addEventListener('click', (e) => {
    const explicit =
      e.target &&
      (e.target.closest('.skip-link') || e.target.closest('[data-skip-to]'));
    let target = null;
    if (explicit) {
      target = getTargetFromTrigger(explicit);
      if (!target) {
        return;
      }
      e.preventDefault();
      activateSkip(target, true);
      return;
    }
    // Also enhance any same-page anchor link within content
    const anchor = e.target && e.target.closest('a[href^="#"]');
    if (!anchor) {
      return;
    }
    const href = anchor.getAttribute('href') || '';
    // Ignore empty or non-target hashes
    if (href === '#' || href === '#0') {
      return;
    }
    // Avoid interfering with UI controls (tabs, accordions, collapses)
    if (
      anchor.hasAttribute('data-bs-toggle') ||
      anchor.hasAttribute('data-toggle') ||
      anchor.getAttribute('role') === 'tab' ||
      anchor.hasAttribute('aria-controls')
    ) {
      return;
    }
    const id = href.slice(1);
    // Only handle when target exists in the current document
    target = document.getElementById(id);
    if (!target) {
      return;
    }
    e.preventDefault();
    activateSkip(target, true);
  });
}

export function handleHashOnLoad() {
  if (typeof document === 'undefined') {
    return;
  }
  // Sanitize hash → id: decode and allow only safe characters
  const rawHash = safeGetHash();
  if (!rawHash) {
    return;
  }
  let raw = '';
  try {
    raw = decodeURIComponent(String(rawHash || ''));
  } catch (e) {
    raw = String(rawHash || '');
  }
  if (!raw || raw.charAt(0) !== '#') {
    return;
  }
  const candidate = raw.slice(1);
  // Allow typical HTML id characters only: letters, digits, underscore, hyphen, colon, period
  // Must start with a letter to avoid edge cases
  const safeId = /^[A-Za-z][A-Za-z0-9_\-:.]*$/.test(candidate) ? candidate : '';
  if (!safeId) {
    return;
  }
  const target = document.getElementById(safeId);
  if (!target) {
    return;
  }
  // When navigating directly with a hash, ensure focus and respectful scroll
  // Use replaceState so it doesn't add extra history entries
  activateSkip(target, false);
}
