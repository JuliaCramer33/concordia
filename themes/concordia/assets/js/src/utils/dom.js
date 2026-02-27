// Small DOM helpers shared across front-end modules
export function onReady(callback) {
  if (typeof document === 'undefined') {
    return;
  }
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => callback(document), {
      once: true,
    });
  } else {
    callback(document);
  }
}

export function qs(root, selector) {
  return (root || document).querySelector(selector);
}

export function qsa(root, selector) {
  return Array.from((root || document).querySelectorAll(selector));
}

export function debounce(fn, wait = 150) {
  let t;
  return function debounced(...args) {
    clearTimeout(t);
    t = setTimeout(() => fn.apply(this, args), wait);
  };
}
