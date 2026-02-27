/**
 * Kanahoma Carousel — behavior
 */

// Debug controls (set window.KANAHOMA_CAROUSEL_DEBUG = true to enable verbose logs)
const DEBUG = typeof window !== 'undefined' && !!window.KANAHOMA_CAROUSEL_DEBUG;

const computeOffsets = (root, viewport) => {
  const bleed = root.getAttribute('data-bleed');
  if (!bleed || bleed === 'none') { root.style.removeProperty('--offset-left'); root.style.removeProperty('--offset-right'); return; }
  const rect = viewport.getBoundingClientRect();
  const left = Math.max(0, rect.left);
  const right = Math.max(0, window.innerWidth - rect.right);
  const set = (prop, val) => root.style.setProperty(prop, `${Math.round(val)}px`);
  if (bleed === 'left') { set('--offset-left', left); set('--offset-right', 0); }
  else if (bleed === 'right') { set('--offset-left', 0); set('--offset-right', right); }
  else { set('--offset-left', left); set('--offset-right', right); }
};

const findTrack = (root) => {
  // Query-loop variant mounts on WP Post Template
  const isQueryCarousel = root.matches && (root.matches('[data-query-carousel]') || root.classList.contains('kanahoma-query-carousel'));
  if (isQueryCarousel) {
    const pt = root.querySelector('.wp-block-post-template');
    if (pt) return pt;
  }
  // Regular carousel variants - look for standard carousel items container
  return (
    root.querySelector('[data-carousel-items]') ||
    root.querySelector('[data-carousel-track]') ||
    root.querySelector('.kanahoma-carousel__items') ||
    root.querySelector('.kanahoma-carousel__track') ||
    null
  );
};

const initCarousel = (root) => {
  // One-time init guard per root element
  if (root.dataset.kcInit === '1') return;
  root.dataset.kcInit = '1';


  let viewport = root.querySelector('[data-carousel-viewport]');
  let items = findTrack(root);
  if (!viewport || !items) {

    requestAnimationFrame(() => {
      viewport = root.querySelector('[data-carousel-viewport]');
      items = findTrack(root);
      if (!viewport || !items) { return; }
      // Re-enter init logic by calling initCarousel again for this root
      initCarousel(root);
    });
    return;
  }
  const prev = root.querySelector('.kanahoma-carousel__prev');
  const next = root.querySelector('.kanahoma-carousel__next');
  const progress = root.querySelector('.kanahoma-carousel__progress-bar');
  if (!viewport || !items) return;

  // Slides count -> CSS var
  // Filter out pseudo-elements and spacer elements
  const slides = Array.from(items.children).filter((el) => {
    if (el.nodeType !== 1) return false;
    // Skip if it's a pseudo-element marker or spacer
    const classes = el.className || '';
    return !classes.includes('kanahoma-carousel__spacer') &&
      !el.matches('.kanahoma-carousel__items::before, .kanahoma-carousel__items::after, .wp-block-post-template::before, .wp-block-post-template::after');
  });
  const count = slides.length;
  root.style.setProperty('--visible', String(count));
  root.classList.toggle('has-multiple', count > 1);

  const debug = () => false;
  const readNum = (style, prop, fallback = 0) => {
    const v = parseFloat(style.getPropertyValue(prop));
    return Number.isFinite(v) ? v : fallback;
  };
  const logState = () => { };

  const isOverflowing = () => items.scrollWidth - items.clientWidth > 1;

  const maybeForceOverflow = () => {
    const mode = root.getAttribute('data-mode');
    if (mode === 'auto' && count > 1 && !isOverflowing()) {
      root.classList.add('force-overflow');
    } else {
      root.classList.remove('force-overflow');
    }
  };

  const updateButtons = () => {
    if (!prev || !next) return;
    if (!isOverflowing()) { prev.disabled = true; next.disabled = true; return; }
    const max = items.scrollWidth - items.clientWidth - 1;
    prev.disabled = items.scrollLeft <= 0;
    next.disabled = items.scrollLeft >= max;
  };

  const updateProgress = () => {
    if (!progress) return;
    const max = items.scrollWidth - items.clientWidth;
    progress.style.width = (max > 0 ? (items.scrollLeft / max) * 100 : 0) + '%';
  };
  let snapTimer = 0; let isAnimating = false;

  // Equalize slide heights to tallest (works for mixed inner layouts)
  const equalizeHeights = () => {
    if (!slides.length) return;
    slides.forEach((s) => { s.style.height = ''; });
    let maxH = 0;
    slides.forEach((s) => {
      const h = s.getBoundingClientRect().height;
      if (h > maxH) maxH = h;
    });
    if (maxH > 0) {
      const h = `${Math.ceil(maxH)}px`;
      slides.forEach((s) => { s.style.height = h; });
    }
  };
  // Wait for images to decode and for layout to settle before equalizing
  const nextFrame = () => new Promise((r) => requestAnimationFrame(r));
  const waitDecode = (img) =>
    img && !img.complete
      ? (img.decode ? img.decode().catch(() => {}) : new Promise((res) => img.addEventListener('load', res, { once: true })))
      : Promise.resolve();
  const equalizeHeightsStable = async () => {
    try {
      const imgs = [];
      slides.forEach((s) => s.querySelectorAll && s.querySelectorAll('img') && imgs.push(...s.querySelectorAll('img')));
      await Promise.all(imgs.map(waitDecode));
      // Let CSS (e.g., aspect-ratio) apply before measuring
      await nextFrame(); await nextFrame();
    } catch (e) {}
    equalizeHeights();
  };

  // Snap to nearest group boundary based on current step (tablet/desktop)
  const snapToGroup = () => {
    const step = getStep();
    const cur = currentIndex();
    const targetIndex = Math.max(0, Math.min(slides.length - 1, Math.round(cur / step) * step));
    isAnimating = true;
    items.scrollTo({ left: slideLeft(targetIndex), behavior: 'smooth' });
    setTimeout(() => { isAnimating = false; }, 220);
  };

  // Drag-to-scroll (pointer events)
  const enableDrag = () => {
    if (!items) return;
    try { items.style.touchAction = 'pan-x pan-y'; } catch (e) { }
    let isDragging = false; let startX = 0; let startScroll = 0; let moved = false; let lastX = 0; let lastT = 0; let ptrType = 'mouse';
    const THRESH = 5;
    const onDown = (e) => {
      ptrType = e.pointerType || 'mouse';
      if (ptrType !== 'mouse') { return; } // let native inertia handle touch/pen
      // Do not start drag from interactive elements; allow native navigation
      if (e.target && e.target.closest && e.target.closest('a, button, input, textarea, select')) {
        isDragging = false; moved = false;
        return;
      }
      isDragging = true; moved = false;
      startX = e.clientX;
      startScroll = items.scrollLeft;
      lastX = startX; lastT = performance.now();
      try { items.setPointerCapture && items.setPointerCapture(e.pointerId); } catch (err) { }
      try { items.style.scrollBehavior = 'auto'; } catch (err) { }
      root.classList.add('is-dragging');
    };
    const onMove = (e) => {
      if (!isDragging) return;
      const dx = e.clientX - startX;
      if (Math.abs(dx) > THRESH) moved = true;
      items.scrollLeft = startScroll - dx;
      lastX = e.clientX; lastT = performance.now();
      e.preventDefault();
    };
    const onUp = (e) => {
      if (!isDragging && ptrType === 'mouse') return;
      isDragging = false;
      root.classList.remove('is-dragging');
      try { items.releasePointerCapture && items.releasePointerCapture(e.pointerId); } catch (err) { }
      try { items.style.scrollBehavior = ''; } catch (err) { }
      if (moved) {
        const cancelOnce = (ev) => { ev.preventDefault(); ev.stopPropagation(); };
        items.addEventListener('click', cancelOnce, { capture: true, once: true });
        if (ptrType === 'mouse') {
          // simple inertia for mouse drags
          const now = performance.now();
          let v = (e.clientX - lastX) / Math.max(1, (now - lastT)); // px/ms
          v *= 18; // approx px per frame
          const stepInertia = () => {
            items.scrollLeft -= v;
            v *= 0.94;
            if (Math.abs(v) > 0.25) requestAnimationFrame(stepInertia);
            else { snapToGroup(); }
          };
          requestAnimationFrame(stepInertia);
        }
      }
      updateButtons(); updateProgress();
    };
    items.addEventListener('pointerdown', onDown, { passive: true });
    items.addEventListener('pointermove', onMove, { passive: false });
    items.addEventListener('pointerup', onUp, { passive: true });
    items.addEventListener('pointercancel', onUp, { passive: true });
    items.addEventListener('pointerleave', onUp, { passive: true });
  };

  // Rect-safe targets (works with edge-cuts and breakout)
  const getStep = () => {
    // Prefer explicit --step if present; otherwise resolve by breakpoint vars
    const style = getComputedStyle(root);
    const explicit = parseInt(style.getPropertyValue('--step'), 10);
    if (Number.isFinite(explicit) && explicit > 0) return explicit;
    const vw = window.innerWidth;
    const m = parseInt(style.getPropertyValue('--spv-mobile'), 10) || 1;
    const t = parseInt(style.getPropertyValue('--spv-tablet'), 10) || m;
    const d = parseInt(style.getPropertyValue('--spv-desktop'), 10) || t;
    if (vw >= 1024) return d;
    if (vw >= 700) return t;
    return m;
  };
  const slideLeft = (i) => {
    const slideRect = slides[i].getBoundingClientRect();
    const itemsRect = items.getBoundingClientRect();
    return items.scrollLeft + (slideRect.left - itemsRect.left);
  };
  const currentIndex = () => {
    const scroll = items.scrollLeft;
    let idx = 0, min = Infinity;
    for (let i = 0; i < slides.length; i++) {
      const left = slideLeft(i);
      const dist = Math.abs(left - scroll);
      if (dist < min) { min = dist; idx = i; }
    }
    return idx;
  };
  const scrollByStep = (dir) => {
    const step = getStep();
    const target = Math.min(slides.length - 1, Math.max(0, currentIndex() + dir * step));
    items.scrollTo({ left: slideLeft(target), behavior: 'smooth' });
  };

  prev?.addEventListener('click', () => scrollByStep(-1));
  next?.addEventListener('click', () => scrollByStep(1));
  items.addEventListener('scroll', () => {
    updateButtons(); updateProgress();
    if (isDragging || isAnimating) return;
    clearTimeout(snapTimer);
    snapTimer = setTimeout(() => { snapToGroup(); }, 90);
  }, { passive: true });
  root.addEventListener('keydown', (e) => {
    if (!root.contains(document.activeElement)) return;
    if (e.key === 'ArrowLeft') { e.preventDefault(); scrollByStep(-1); }
    if (e.key === 'ArrowRight') { e.preventDefault(); scrollByStep(1); }
  });

  enableDrag();

  // Add a debug class to visualize slides if desired
  if (debug()) {
    try { root.classList.add('debug'); } catch (e) { }
  }

  // First layout - run after styles are computed
  const firstLayout = () => {
    computeOffsets(root, viewport);
    maybeForceOverflow();
    // Remove peek/bleed when only one slide per view
    try {
      const one = getStep() === 1;
      root.classList.toggle('no-peek', one);
      root.classList.toggle('no-bleed', one);
    } catch (e) { }
    updateButtons();
    updateProgress();
    // After layout, set equal heights and re-run after images load
    equalizeHeightsStable();
    try {
      // Prevent native image drag ghost that can block clicks
      slides.forEach((s) => {
        s.querySelectorAll('img').forEach((img) => {
          try { img.setAttribute('draggable', 'false'); } catch (e) {}
        });
      });
      slides.forEach((s) => {
        s.querySelectorAll('img').forEach((img) => {
          if (!img.complete) {
            img.addEventListener('load', () => { equalizeHeightsStable(); }, { once: true });
          }
        });
      });
    } catch (e) { }
    logState('after-layout');
  };
  // Use rAF twice to ensure computed styles are ready after initial paint
  logState('init');
  requestAnimationFrame(() => requestAnimationFrame(firstLayout));

  // Resize/layout changes
  const onResize = () => {
    computeOffsets(root, viewport);
    maybeForceOverflow();
    try {
      const one = getStep() === 1;
      root.classList.toggle('no-peek', one);
      root.classList.toggle('no-bleed', one);
    } catch (e) { }
    updateButtons();
    updateProgress();
    equalizeHeightsStable();
    logState('resize');
  };
  window.addEventListener('resize', onResize);

  // Observe content changes and re-equalize
  try {
    const mo = new MutationObserver(() => { equalizeHeightsStable(); });
    mo.observe(items, { childList: true, subtree: true });
  } catch (e) { }
};

const initAll = () => {
  // Supports both regular and query-loop variant
  const all = document.querySelectorAll('[data-query-carousel], .kanahoma-query-carousel, [data-carousel], .kanahoma-carousel');
  all.forEach(initCarousel);
};

if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initAll);
else initAll();
