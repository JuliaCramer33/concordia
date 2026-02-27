// Toggle active spotlight panel (accessible)
import { onReady } from './utils/dom.js';

function init(root = document) {
  root.querySelectorAll('.c-spotlights').forEach((wrap) => {
    const panels = wrap.querySelectorAll(
      '.c-spotlights__panel, .wp-block-column'
    );
    panels.forEach((panel, idx) => {
      panel.setAttribute('role', 'tab');
      panel.setAttribute(
        'tabindex',
        panel.classList.contains('is-active') ? '0' : '-1'
      );
      panel.setAttribute(
        'aria-selected',
        panel.classList.contains('is-active') ? 'true' : 'false'
      );
      panel.addEventListener('click', () => activate(idx));
      panel.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' || e.key === ' ') {
          e.preventDefault();
          activate(idx);
        }
        if (e.key === 'ArrowRight') {
          e.preventDefault();
          activate((idx + 1) % panels.length);
        }
        if (e.key === 'ArrowLeft') {
          e.preventDefault();
          activate((idx - 1 + panels.length) % panels.length);
        }
      });
    });
    function activate(i) {
      panels.forEach((p, j) => {
        const on = j === i;
        p.classList.toggle('is-active', on);
        p.setAttribute('aria-selected', on ? 'true' : 'false');
        p.setAttribute('tabindex', on ? '0' : '-1');
      });
    }

    // On mobile horizontal swipe, mark the mostly-visible panel as active after scroll
    const scroller = wrap.querySelector('.wp-block-columns');
    if (scroller) {
      let raf;
      const updateActiveByVisibility = () => {
        const rect = scroller.getBoundingClientRect();
        let bestIndex = 0;
        let bestOverlap = -1;
        panels.forEach((p, idx) => {
          const r = p.getBoundingClientRect();
          const overlap = Math.max(
            0,
            Math.min(rect.right, r.right) - Math.max(rect.left, r.left)
          );
          if (overlap > bestOverlap) {
            bestOverlap = overlap;
            bestIndex = idx;
          }
        });
        activate(bestIndex);
      };
      const onScroll = () => {
        if (raf) {
          cancelAnimationFrame(raf);
        }
        raf = requestAnimationFrame(updateActiveByVisibility);
      };
      scroller.addEventListener('scroll', onScroll, { passive: true });
    }
  });
}
onReady(init);
