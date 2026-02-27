(() => {
  // Trigger class can be added to any block/button
  const triggers = document.querySelectorAll('.mobile-mega-trigger');
  const getPanel = () => {
    const candidates = Array.from(document.querySelectorAll('#mega-mobile'));
    if (candidates.length === 0) {
      return null;
    }
    // Prefer explicit dialog markup if duplicates exist
    const byRole = candidates.find((el) => {
      try {
        return String(el.getAttribute('role') || '').toLowerCase() === 'dialog';
      } catch (_) {
        return false;
      }
    });
    return byRole || candidates[candidates.length - 1];
  };
  const getPanelsRoot = (panelEl) => {
    return (
      (panelEl && panelEl.closest('.mega-panels')) ||
      document.querySelector('.site-header .mega-panels') ||
      document.querySelector('.mega-panels')
    );
  };
  const initialPanel = getPanel();
  const initialRoot = getPanelsRoot(initialPanel);
  if (!initialPanel || triggers.length === 0 || !initialRoot) {
    return;
  }

  const headerContainer =
    document.querySelector('.site-header__container') ||
    document.querySelector('.site-header');

  // Treat tablet-down as < 1024px (mobile experience for tablet and below)
  const isTabletDown = () => window.innerWidth < 1024;
  const isElementVisible = (el) => {
    if (!el) {
      return false;
    }
    try {
      const cs = window.getComputedStyle(el);
      if (cs.display === 'none' || cs.visibility === 'hidden') {
        return false;
      }
    } catch (_) {}
    const rect = el.getBoundingClientRect();
    return !!(rect && rect.width > 0 && rect.height > 0);
  };
  const shouldEnable = () =>
    isTabletDown() || Array.from(triggers).some((t) => isElementVisible(t));

  const isAriaHidden = (el) => {
    if (!el) {
      return false;
    }
    try {
      if (
        String(el.getAttribute('aria-hidden') || '').toLowerCase() === 'true'
      ) {
        return true;
      }
      const cs = window.getComputedStyle(el);
      return cs.display === 'none' || cs.visibility === 'hidden';
    } catch (_) {
      return false;
    }
  };

  const focusSafely = () => {
    const candidates = [
      () => triggers[0],
      () =>
        headerContainer &&
        headerContainer.querySelector(
          'a,button,[tabindex]:not([tabindex="-1"])'
        ),
      () => document.body,
    ];
    for (let i = 0; i < candidates.length; i++) {
      let el;
      try {
        el = candidates[i]();
      } catch (_) {
        el = null;
      }
      if (el && !isAriaHidden(el) && isElementVisible(el)) {
        try {
          el.focus({ preventScroll: true });
          return true;
        } catch (_) {}
      }
    }
    return false;
  };

  // Close all mega panels (mirror of behavior in mega.js)
  const closeAllPanels = (exceptEl) => {
    const root = getPanelsRoot(getPanel());
    try {
      // Close desktop triggers
      document
        .querySelectorAll('.has-mega > a[aria-expanded="true"]')
        .forEach((a) => a.setAttribute('aria-expanded', 'false'));
    } catch (_) {}
    if (!root) {
      return;
    }
    root.querySelectorAll('.mega-panel').forEach((p) => {
      if (exceptEl && p === exceptEl) {
        return;
      }
      p.hidden = true;
      p.setAttribute('aria-hidden', 'true');
    });
  };

  const setOpenState = (isOpen) => {
    const panel = getPanel();
    triggers.forEach((t) => {
      t.setAttribute('aria-controls', 'mega-mobile');
      t.classList.toggle('is-active', isOpen);
      t.setAttribute('aria-label', isOpen ? 'Close menu' : 'Open menu');
    });
    // Manage a11y visibility and focusability
    if (isOpen) {
      try {
        panel.removeAttribute('inert');
      } catch (_) {}
      panel.hidden = false;
      panel.setAttribute('aria-hidden', 'false');
      panel.setAttribute('aria-expanded', 'true');
    } else {
      // Ensure focus is moved out before hiding to avoid aria-hidden conflicts
      try {
        // Make the panel inert immediately to prevent further focus
        panel.setAttribute('inert', '');
      } catch (_) {}
      try {
        const doc =
          (panel && panel.ownerDocument) ||
          (headerContainer && headerContainer.ownerDocument) ||
          document;
        const activeEl = doc.activeElement;
        if (activeEl && panel.contains(activeEl)) {
          // Prefer moving focus to a safe visible element
          const moved = focusSafely();
          if (!moved) {
            // Fallback: temporary off-DOM sentinel without aria-hidden
            const sentinel = document.createElement('button');
            sentinel.type = 'button';
            sentinel.tabIndex = -1;
            sentinel.style.position = 'fixed';
            sentinel.style.width = '1px';
            sentinel.style.height = '1px';
            sentinel.style.left = '-9999px';
            sentinel.style.top = '0';
            document.body.appendChild(sentinel);
            try {
              sentinel.focus({ preventScroll: true });
            } catch (_) {}
            // Clean up sentinel shortly after
            setTimeout(() => {
              try {
                document.body.removeChild(sentinel);
              } catch (_) {}
            }, 0);
          }
        }
      } catch (_) {}
      // Defer aria-hidden/hidden updates until after focus has moved
      requestAnimationFrame(() => {
        panel.setAttribute('aria-hidden', 'true');
        panel.setAttribute('aria-expanded', 'false');
        panel.hidden = true;
      });
    }
    document.documentElement.classList.toggle('has-mobile-mega-open', isOpen);
    document.body.classList.toggle('has-mobile-mega-open', isOpen);
    if (headerContainer) {
      headerContainer.classList.toggle('is-menu-open', isOpen);
    }
  };

  const isPanelOpen = () => {
    const panel = getPanel();
    try {
      const h = panel.hidden;
      if (typeof h === 'boolean') {
        return !h;
      }
    } catch (_) {}
    return (
      panel.getAttribute('aria-hidden') === 'false' ||
      panel.getAttribute('aria-expanded') === 'true'
    );
  };

  // Scroll lock helpers to prevent background page scroll while panel is open
  let _mmScrollY = 0;
  const lockScroll = () => {
    try {
      _mmScrollY =
        window.pageYOffset ||
        document.documentElement.scrollTop ||
        document.body.scrollTop ||
        0;
      // Compensate for scrollbar disappearance to avoid layout shift
      const scrollbarWidth =
        window.innerWidth - document.documentElement.clientWidth;
      document.documentElement.style.overflow = 'hidden';
      document.body.style.overflow = 'hidden';
      if (scrollbarWidth > 0) {
        document.documentElement.style.paddingRight = scrollbarWidth + 'px';
        document.body.style.paddingRight = scrollbarWidth + 'px';
      }
      document.body.style.position = 'fixed';
      document.body.style.top = `-${_mmScrollY}px`;
      document.body.style.width = '100%';
    } catch (_) {}
  };
  const unlockScroll = () => {
    try {
      document.documentElement.style.overflow = '';
      document.body.style.overflow = '';
      document.documentElement.style.paddingRight = '';
      document.body.style.paddingRight = '';
      document.body.style.position = '';
      document.body.style.top = '';
      document.body.style.width = '';
      if (typeof _mmScrollY === 'number' && _mmScrollY >= 0) {
        window.scrollTo(0, _mmScrollY);
      }
    } catch (_) {}
  };

  const openPanel = () => {
    const panel = getPanel();
    closeAllPanels(panel);
    setOpenState(true);
    lockScroll();
    // Compute and set the header bottom offset (distance from viewport top to bottom of header)
    try {
      const headerRect = headerContainer
        ? headerContainer.getBoundingClientRect()
        : null;
      const bottom = headerRect
        ? Math.max(0, Math.round(headerRect.bottom))
        : 0;
      panel.style.setProperty('--mobile-header-total', bottom + 'px');
      // keep it fresh on resize while panel is open
      const onResize = () => {
        const hr = headerContainer
          ? headerContainer.getBoundingClientRect()
          : null;
        const b = hr ? Math.max(0, Math.round(hr.bottom)) : 0;
        panel.style.setProperty('--mobile-header-total', b + 'px');
      };
      window.addEventListener('resize', onResize, { passive: true });
      panel._mmOnResize = onResize;
      // also observe header class/attribute changes (e.g., stuck toggles) and recalc
      if (headerContainer && !panel._mmHeaderObs) {
        const mo = new MutationObserver(() => onResize());
        mo.observe(headerContainer, { attributes: true });
        panel._mmHeaderObs = mo;
      }
    } catch (_) {}
    // Guard against immediate outside-click close from touch/click sequence
    try {
      window.__megaMobileIgnoreOutsideUntil = performance.now() + 200;
    } catch (_) {
      window.__megaMobileIgnoreOutsideUntil = Date.now() + 200;
    }
    const focusable = panel.querySelector(
      'a,button,input,select,textarea,[tabindex]:not([tabindex="-1"])'
    );
    if (focusable) {
      focusable.focus({ preventScroll: true });
    }
  };

  const closePanel = () => {
    setOpenState(false);
    unlockScroll();
    try {
      const p = getPanel();
      if (p && p._mmOnResize) {
        window.removeEventListener('resize', p._mmOnResize);
        p._mmOnResize = null;
      }
      if (p && p._mmHeaderObs) {
        try {
          p._mmHeaderObs.disconnect();
        } catch (_) {}
        p._mmHeaderObs = null;
      }
      if (p) {
        p.style.removeProperty('--mobile-header-total');
      }
    } catch (_) {}
  };

  // Start closed
  setOpenState(false);

  const onTrigger = (e) => {
    e.preventDefault();
    e.stopPropagation();
    // If the trigger currently has focus and a plugin might toggle aria-hidden on it,
    // blur proactively so aria-hidden won't conflict with focused element.
    try {
      const p = getPanel();
      const doc =
        (p && p.ownerDocument) ||
        (headerContainer && headerContainer.ownerDocument) ||
        document;
      const ae = doc.activeElement;
      if (ae && Array.from(triggers).some((t) => t === ae || t.contains(ae))) {
        ae.blur();
      }
    } catch (_) {}
    // Enable if tablet-down OR the trigger is visibly rendered (plugin visibility overrides)
    if (!shouldEnable()) {
      return;
    }
    // Debounce multi-event firing on mobile (touchend/click)
    try {
      const now = performance.now ? performance.now() : Date.now();
      if (now < (window.__megaMobileLastToggleTs || 0) + 250) {
        return;
      }
      window.__megaMobileLastToggleTs = now;
    } catch (_) {}
    const isOpen = isPanelOpen();
    if (isOpen) {
      closePanel();
    } else {
      openPanel();
    }
  };

  triggers.forEach((t) => {
    t.setAttribute('role', t.getAttribute('role') || 'button');
    // Use click only; pointer/touch can double-fire with click on mobile
    t.addEventListener('click', onTrigger);
    t.addEventListener('keydown', (ev) => {
      if (ev.key === 'Enter' || ev.key === ' ') {
        ev.preventDefault();
        onTrigger(ev);
      }
    });
  });

  // Click outside closes
  document.addEventListener('click', (e) => {
    try {
      const now = performance.now ? performance.now() : Date.now();
      if (now < (window.__megaMobileIgnoreOutsideUntil || 0)) {
        return;
      }
    } catch (_) {}
    const isOpen = isPanelOpen();
    if (!isOpen) {
      return;
    }
    const panel = getPanel();
    const clickedTrigger = Array.from(triggers).some((t) =>
      t.contains(e.target)
    );
    if (!panel.contains(e.target) && !clickedTrigger) {
      closePanel();
    }
  });

  // Escape closes
  document.addEventListener('keydown', (e) => {
    const isOpen = isPanelOpen();
    if (isOpen && e.key === 'Escape') {
      e.preventDefault();
      closePanel();
      // Avoid focusing an aria-hidden element; pick the first visible, safe target
      try {
        focusSafely();
      } catch (_) {}
    }
  });

  // Resize behavior: close when moving up to desktop
  window.addEventListener(
    'resize',
    () => {
      const isOpen = isPanelOpen();
      if (!shouldEnable() && isOpen) {
        closePanel();
      }
    },
    { passive: true }
  );
})();
