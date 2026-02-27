// Hero Arch Parallax
// Applies a subtle translateY parallax to Cover images inside .hero-arch
(() => {
  if (typeof window === 'undefined' || typeof document === 'undefined') {
    return;
  }

  const reduceMotion = matchMedia('(prefers-reduced-motion: reduce)').matches;
  const covers = () =>
    Array.from(document.querySelectorAll('.wp-block-cover.hero-arch'));

  const getVarNumber = (el, name, fallback) => {
    const v = getComputedStyle(el).getPropertyValue(name).trim();
    const n = parseFloat(v);
    return Number.isFinite(n) ? n : fallback;
  };

  const getMaxShiftPx = (el) => {
    const cs = getComputedStyle(el);
    const v = cs.getPropertyValue('--hero-parallax-max').trim();
    if (!v) {
      return 60;
    } // px
    const n = parseFloat(v);
    return Number.isFinite(n) ? n : 60;
  };

  const update = () => {
    covers().forEach((cover) => {
      const rect = cover.getBoundingClientRect();
      // progress: 0 when top at viewport top; increases as you scroll past
      const progress = Math.min(
        1,
        Math.max(0, (0 - rect.top) / Math.max(rect.height || 1, 1))
      );
      const maxShiftY = getMaxShiftPx(cover);
      const shiftY = -progress * maxShiftY;

      // Ken Burns: scale from 1 to max, and pan X/Y subtly
      const maxScale = getVarNumber(cover, '--hero-kenburns-max-scale', 1.08);
      const scale = 1 + (maxScale - 1) * progress;
      const maxPanX = getVarNumber(cover, '--hero-pan-x-max', 0);
      const panX = -progress * maxPanX; // move left as we scroll down
      const maxPanY = getVarNumber(cover, '--hero-pan-y-max', maxShiftY);
      const panY = -progress * maxPanY; // move upward slightly as we scroll down

      cover.style.setProperty(
        '--hero-parallax',
        reduceMotion ? '0px' : `${shiftY.toFixed(2)}px`
      );
      cover.style.setProperty(
        '--hero-scale',
        reduceMotion ? '1' : `${scale.toFixed(4)}`
      );
      cover.style.setProperty(
        '--hero-pan-x',
        reduceMotion ? '0px' : `${panX.toFixed(2)}px`
      );
      cover.style.setProperty(
        '--hero-pan-y',
        reduceMotion ? '0px' : `${panY.toFixed(2)}px`
      );

      // Text parallax (gated by class)
      const text = cover.querySelector('.wp-block-cover__inner-container');
      const enableTextParallax =
        text && cover.classList.contains('is-text-parallax');
      if (text) {
        if (!reduceMotion && enableTextParallax) {
          const textMax = getVarNumber(cover, '--hero-text-shift-max', 160);
          // Move text up faster than image: 1.5x of progress distance
          const textShift = -progress * textMax * 1.0;
          // Optional fade starting after fade-start threshold
          const fadeStart = Math.min(
            1,
            Math.max(0, getVarNumber(cover, '--hero-text-fade-start', 0.35))
          );
          const fadeProgress =
            Math.max(0, progress - fadeStart) / Math.max(1 - fadeStart, 0.0001);
          const textOpacity = 1 - fadeProgress; // fades to 0 at full progress
          cover.style.setProperty(
            '--hero-text-shift',
            `${textShift.toFixed(2)}px`
          );
          cover.style.setProperty(
            '--hero-text-opacity',
            textOpacity.toFixed(3)
          );
        } else {
          cover.style.setProperty('--hero-text-shift', '0px');
          cover.style.setProperty('--hero-text-opacity', '1');
        }
      }
    });
  };

  let ticking = false;
  const onScrollOrResize = () => {
    if (ticking) {
      return;
    }
    ticking = true;
    requestAnimationFrame(() => {
      update();
      ticking = false;
    });
  };

  const init = () => {
    if (!covers().length) {
      return;
    }
    update();
    if (reduceMotion) {
      return;
    }
    window.addEventListener('scroll', onScrollOrResize, { passive: true });
    window.addEventListener('resize', onScrollOrResize, { passive: true });
    window.addEventListener('load', onScrollOrResize, {
      once: true,
      passive: true,
    });
  };

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init, { once: true });
  } else {
    init();
  }
})();
