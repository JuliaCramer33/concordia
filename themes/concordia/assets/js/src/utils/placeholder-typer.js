// Rotating placeholder typer for search inputs
// Usage:
//  - Add class "js-rotate-placeholder" to the input
//  - Optionally add data-phrases as a JSON array or pipe-separated string:
//      data-phrases='["MBA Programs","Nursing","Music"]'
//      or data-phrases="MBA Programs|Nursing|Music"
//  - Optionally add data-speed (ms per char), data-delay (pause at end), data-delete (ms per char when deleting)
//  - Animation pauses when the field is focused or has a non-empty value

function getAttr(el, name) {
  try {
    return (
      el.getAttribute(name) ||
      (el.closest &&
        el.closest('.js-rotate-placeholder')?.getAttribute(name)) ||
      null
    );
  } catch (_) {
    return el.getAttribute(name);
  }
}

function parsePhrases(inputEl) {
  const raw = getAttr(inputEl, 'data-phrases');
  if (!raw) {
    return [
      'MBA Programs',
      'Nursing',
      'Computer Science',
      'Music',
      'Education',
    ];
  }
  try {
    const arr = JSON.parse(raw);
    if (Array.isArray(arr) && arr.length > 0) {
      return arr.map(String);
    }
  } catch (_) {
    // allow pipe-separated list as a simpler authoring format
    const parts = String(raw)
      .split('|')
      .map((s) => s.trim())
      .filter(Boolean);
    if (parts.length > 0) {
      return parts;
    }
  }
  return ['Search programs', 'MBA Programs', 'Nursing'];
}

function initRotatingPlaceholder(root = document) {
  // Allow the class on the input itself OR on a wrapper; pick the actual input
  const nodes = Array.from(root.querySelectorAll('.js-rotate-placeholder'));
  const inputs = [];
  nodes.forEach((node) => {
    const input =
      node.matches && node.matches('input[type="search"], input[type="text"]')
        ? node
        : node.querySelector &&
          node.querySelector('input[type="search"], input[type="text"]');
    if (input && !inputs.includes(input)) {
      inputs.push(input);
    }
  });
  inputs.forEach((inputEl) => {
    if (inputEl._hasTyper) {
      return;
    }
    inputEl._hasTyper = true;

    const phrases = parsePhrases(inputEl);
    const typeMs = Math.max(20, Number(getAttr(inputEl, 'data-speed')) || 65);
    const deleteMs = Math.max(
      15,
      Number(getAttr(inputEl, 'data-delete')) || 40
    );
    const pauseMs = Math.max(
      200,
      Number(getAttr(inputEl, 'data-delay')) || 1200
    );

    let phraseIdx = 0;
    let charIdx = 0;
    let deleting = false;
    let rafId = null;
    let timeoutId = null;
    const originalPlaceholder = inputEl.getAttribute('placeholder') || '';
    // Optional: synchronize typing to additional elements
    // data-targets accepts a CSS selector list (comma-separated)
    // Each target can opt into value typing via data-typer-mode="value" (else textContent)
    const additionalTargets = [];
    const selList = getAttr(inputEl, 'data-targets');
    if (selList) {
      selList
        .split(',')
        .map((s) => s && s.trim())
        .filter(Boolean)
        .forEach((sel) => {
          try {
            root
              .querySelectorAll(sel)
              .forEach((el) => additionalTargets.push(el));
          } catch (_) {}
        });
    }

    const shouldAnimate = () => {
      if (document.hidden) {
        return false;
      }
      const doc = inputEl.ownerDocument || document;
      if (doc.activeElement === inputEl) {
        return false;
      }
      if (inputEl.value && inputEl.value.length > 0) {
        return false;
      }
      // only animate when field is visible
      try {
        const rect = inputEl.getBoundingClientRect();
        if (rect.width <= 0 || rect.height <= 0) {
          return false;
        }
      } catch (_) {}
      return true;
    };

    const step = () => {
      if (!shouldAnimate()) {
        inputEl.setAttribute('placeholder', originalPlaceholder);
        // Reset synced targets to their original data-original or empty
        additionalTargets.forEach((el) => {
          const orig = el.getAttribute('data-original') ?? el.textContent ?? '';
          if (
            String(el.getAttribute('data-typer-mode') || '').toLowerCase() ===
            'value'
          ) {
            if ('value' in el) {
              el.value = orig;
            }
          } else {
            el.textContent = orig;
          }
        });
        schedule(step, 500);
        return;
      }

      const current = phrases[phraseIdx % phrases.length] || '';
      if (!deleting) {
        charIdx = Math.min(current.length, charIdx + 1);
        const part = current.slice(0, charIdx);
        inputEl.setAttribute('placeholder', part);
        // Sync to other targets
        additionalTargets.forEach((el) => {
          if (!el.hasAttribute('data-original')) {
            const mode = String(
              el.getAttribute('data-typer-mode') || ''
            ).toLowerCase();
            let original = '';
            if (mode === 'value') {
              original = 'value' in el && el.value ? el.value : '';
            } else {
              original = el.textContent || '';
            }
            el.setAttribute('data-original', String(original));
          }
          if (
            String(el.getAttribute('data-typer-mode') || '').toLowerCase() ===
            'value'
          ) {
            if ('value' in el) {
              el.value = part;
            }
          } else {
            el.textContent = part;
          }
        });
        if (charIdx >= current.length) {
          deleting = true;
          schedule(step, pauseMs);
          return;
        }
        schedule(step, typeMs);
      } else {
        charIdx = Math.max(0, charIdx - 1);
        const part = current.slice(0, charIdx);
        inputEl.setAttribute('placeholder', part);
        additionalTargets.forEach((el) => {
          if (
            String(el.getAttribute('data-typer-mode') || '').toLowerCase() ===
            'value'
          ) {
            if ('value' in el) {
              el.value = part;
            }
          } else {
            el.textContent = part;
          }
        });
        if (charIdx === 0) {
          deleting = false;
          phraseIdx = (phraseIdx + 1) % phrases.length;
          schedule(step, Math.min(400, pauseMs / 3));
          return;
        }
        schedule(step, deleteMs);
      }
    };

    const schedule = (fn, delay) => {
      if (rafId) {
        cancelAnimationFrame(rafId);
      }
      if (timeoutId) {
        clearTimeout(timeoutId);
      }
      timeoutId = setTimeout(() => {
        rafId = requestAnimationFrame(fn);
      }, delay);
    };

    // Pause/resume on focus/blur and visibility changes
    inputEl.addEventListener('focus', () => {
      if (timeoutId) {
        clearTimeout(timeoutId);
      }
      if (rafId) {
        cancelAnimationFrame(rafId);
      }
    });
    inputEl.addEventListener('blur', () => {
      // restart soon if empty
      if (!inputEl.value) {
        schedule(step, 250);
      }
    });
    document.addEventListener('visibilitychange', () => {
      const doc = inputEl.ownerDocument || document;
      if (!document.hidden && !inputEl.value && doc.activeElement !== inputEl) {
        schedule(step, 250);
      }
    });

    // Kick off
    schedule(step, 400);
  });
}

if (typeof document !== 'undefined') {
  const onReady = () => {
    initRotatingPlaceholder(document);
    const mo = new MutationObserver(() => initRotatingPlaceholder(document));
    mo.observe(document.documentElement, { childList: true, subtree: true });
  };
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', onReady, { once: true });
  } else {
    onReady();
  }
}

export { initRotatingPlaceholder };
