// Sticky header behavior: float initially, stick to edges after threshold
(() => {
  const header =
    document.querySelector('.site-header__container') ||
    document.querySelector('.site-header');
  if (!header) {
    return;
  }

  const isTabletUp = () => window.innerWidth >= 1024;

  const threshold = 1; // engage stickiness almost immediately
  let lastIsStuck = null;
  let lastY = window.scrollY;
  let pivotY = lastY; // last point where visibility changed
  let interacting = false;
  const HIDE_AFTER = 24; // px scrolled down after which to hide
  const REVEAL_AFTER = 12; // px scrolled up after which to show
  let viewportH = window.innerHeight || 0; // start of scroll-intent
  let enabled = false;

  const getAdminBarOffset = () => {
    const bar = document.getElementById('wpadminbar');
    return bar ? bar.offsetHeight : 0;
  };

  const setStuck = (stuck) => {
    header.classList.toggle('is-stuck', stuck);
    document.documentElement.classList.toggle('has-stuck-header', stuck);
    document.body.classList.toggle('has-stuck-header', stuck);
    const offset = stuck ? getAdminBarOffset() : 0;
    header.style.top = offset ? `${offset}px` : '';
    // Update CSS scroll padding offset so in-page anchors land below overlays
    const topOverlay =
      (stuck ? header.offsetHeight || 0 : 0) + getAdminBarOffset();
    document.documentElement.style.setProperty(
      '--cui-scroll-offset',
      `${topOverlay}px`
    );
    if (!stuck) {
      header.classList.remove('is-hidden');
    }
  };

  const hide = () => {
    if (!header.classList.contains('is-hidden')) {
      header.classList.add('is-hidden');
      pivotY = window.scrollY;
    }
  };

  const show = () => {
    if (header.classList.contains('is-hidden')) {
      header.classList.remove('is-hidden');
      pivotY = window.scrollY;
    }
  };

  const update = () => {
    const y = window.scrollY;
    const isStuck = y > threshold;
    if (isStuck !== lastIsStuck) {
      setStuck(isStuck);
      lastIsStuck = isStuck;
      pivotY = y;
    }

    if (!isStuck) {
      // Always show when floating
      show();
      lastY = y;
      return;
    }

    // Activate scroll-intent only after passing one viewport height
    const intentActive = y > viewportH;
    if (!intentActive) {
      show();
      lastY = y;
      return;
    }

    if (interacting) {
      show();
      lastY = y;
      return;
    }

    const delta = y - lastY;
    if (delta > 0) {
      // scrolling down
      if (y - pivotY > HIDE_AFTER) {
        hide();
      }
    } else if (delta < 0) {
      // scrolling up
      if (pivotY - y > REVEAL_AFTER || y <= threshold + 1) {
        show();
      }
    }

    lastY = y;
  };

  const onScroll = () => enabled && update();
  const onResize = () => {
    viewportH = window.innerHeight || viewportH;
    if (!isTabletUp()) {
      disable();
    } else {
      enable();
    }
  };
  const onEnter = () => {
    if (enabled) {
      interacting = true;
      show();
    }
  };
  const onLeave = () => {
    if (enabled) {
      interacting = false;
    }
  };
  const onFocusIn = () => {
    if (enabled) {
      interacting = true;
      show();
    }
  };
  const onFocusOut = () => {
    if (enabled) {
      interacting = false;
    }
  };

  function enable() {
    if (enabled) {
      return;
    }
    enabled = true;
    window.addEventListener('scroll', onScroll, { passive: true });
    header.addEventListener('pointerenter', onEnter);
    header.addEventListener('pointerleave', onLeave);
    header.addEventListener('focusin', onFocusIn);
    header.addEventListener('focusout', onFocusOut);
    update();
  }

  function disable() {
    if (!enabled) {
      // ensure header is visible and fixed at top with no sticky toggles
      header.classList.remove('is-hidden');
      header.classList.remove('is-stuck');
      document.documentElement.classList.remove('has-stuck-header');
      document.body.classList.remove('has-stuck-header');
      header.style.top = '';
      // On mobile / disabled mode, default offset to admin bar only
      document.documentElement.style.setProperty(
        '--cui-scroll-offset',
        `${getAdminBarOffset()}px`
      );
      return;
    }
    enabled = false;
    window.removeEventListener('scroll', onScroll);
    header.removeEventListener('pointerenter', onEnter);
    header.removeEventListener('pointerleave', onLeave);
    header.removeEventListener('focusin', onFocusIn);
    header.removeEventListener('focusout', onFocusOut);
    // Reset state for mobile
    header.classList.remove('is-hidden');
    header.classList.remove('is-stuck');
    document.documentElement.classList.remove('has-stuck-header');
    document.body.classList.remove('has-stuck-header');
    header.style.top = '';
    document.documentElement.style.setProperty(
      '--cui-scroll-offset',
      `${getAdminBarOffset()}px`
    );
  }

  window.addEventListener('resize', onResize, { passive: true });
  // initial mode
  if (isTabletUp()) {
    enable();
    // Initialize scroll offset on first run
    document.documentElement.style.setProperty(
      '--cui-scroll-offset',
      `${getAdminBarOffset()}px`
    );
  } else {
    disable();
  }
})();
