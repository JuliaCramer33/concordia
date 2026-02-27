// Entry: front-end
import './hero-arch.js';
import './mega-mobile.js';
import './mega-search.js';
import './mega.js';
import './spotlights.js';
import './sticky-nav.js';
import { initButtonSweepVars } from './utils/button-color.js';
import './utils/placeholder-typer.js';
import './video-overlay.js';
import './library-search.js';
import './gpa-calculator.js';
import { initSkipLinks, handleHashOnLoad } from './utils/skip-links.js';

// sticky-nav.js now contains sticky header logic.
// Initialize button sweep colors after DOM is ready and observe dynamic inserts
if (typeof document !== 'undefined') {
  const onReady = () => {
    initButtonSweepVars(document);
    // Initialize accessible skip link behavior
    initSkipLinks();
    handleHashOnLoad();
    const mo = new MutationObserver(() => initButtonSweepVars(document));
    mo.observe(document.documentElement, { childList: true, subtree: true });
  };

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', onReady, { once: true });
  } else {
    onReady();
  }
  // Fallback: run again at window load to catch late CSS or fonts
  window.addEventListener('load', () => initButtonSweepVars(document), {
    once: true,
    passive: true,
  });
  // Respond to hash changes triggered outside of our click handler
  window.addEventListener('hashchange', handleHashOnLoad, { passive: true });
}
