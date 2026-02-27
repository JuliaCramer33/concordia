(() => {
  // Scope to the header main navigation explicitly
  const nav =
    document.querySelector('.site-header__container .wp-block-navigation') ||
    document.querySelector('.wp-block-navigation.main-nav, nav.main-nav') ||
    document.querySelector('.wp-block-navigation');
  const panelsRoot = document.querySelector('.mega-panels');
  if (!nav || !panelsRoot) {
    return;
  }

  // Disable desktop mega logic on tablet/mobile; mobile uses its own panel logic
  if (window.innerWidth < 1024) {
    return;
  }

  const mobileMegaOpen = () =>
    document.documentElement.classList.contains('has-mobile-mega-open') ||
    document.body.classList.contains('has-mobile-mega-open');

  const hoverCapable = matchMedia('(hover: hover)').matches;
  let hoverCloseTimeoutId = null;

  const closeAll = () => {
    if (mobileMegaOpen()) {
      return;
    }
    nav
      .querySelectorAll('.has-mega > a[aria-expanded="true"]')
      .forEach((a) => a.setAttribute('aria-expanded', 'false'));
    panelsRoot.querySelectorAll('.mega-panel').forEach((p) => {
      // Make panel non-interactive and remove from a11y tree
      try {
        p.setAttribute('inert', '');
      } catch (_) {}
      p.hidden = true;
      p.setAttribute('aria-hidden', 'true');
    });
  };

  const scheduleClose = () => {
    if (mobileMegaOpen()) {
      return;
    }
    if (window.innerWidth >= 1024) {
      if (hoverCloseTimeoutId) {
        clearTimeout(hoverCloseTimeoutId);
      }
      hoverCloseTimeoutId = setTimeout(() => {
        closeAll();
        hoverCloseTimeoutId = null;
      }, 200);
    }
  };

  const cancelClose = () => {
    if (mobileMegaOpen()) {
      return;
    }
    if (hoverCloseTimeoutId && window.innerWidth >= 1024) {
      clearTimeout(hoverCloseTimeoutId);
      hoverCloseTimeoutId = null;
    }
  };

  // Ensure panels start hidden without relying on static markup attributes
  closeAll();

  const wireItem = (item) => {
    const anchor = item.querySelector('a');
    if (!anchor) {
      return;
    }
    if (!item.classList.contains('has-mega')) {
      return;
    }

    // Derive key with fallbacks:
    // 1) from link hash (e.g., href="#services" -> key = "services")
    // 2) from a class starting with "mega-" (e.g., "mega-academics" -> "academics")
    // 3) from any additional class on the item besides core ones
    const href = anchor.getAttribute('href') || '';
    let key = href.startsWith('#') && href.length > 1 ? href.slice(1) : null;
    if (!key) {
      const prefixed = Array.from(item.classList).find((c) =>
        c.startsWith('mega-')
      );
      if (prefixed) {
        key = prefixed.replace(/^mega-/, '');
      }
    }
    if (!key) {
      const extra = Array.from(item.classList).find(
        (c) =>
          ![
            'has-mega',
            'wp-block-navigation-item',
            'current-menu-item',
            'current_page_item',
          ].includes(c)
      );
      if (extra) {
        key = extra;
      }
    }
    if (!key) {
      return;
    }

    let panel = panelsRoot.querySelector(`#mega-${key}`);
    if (!panel) {
      panel = panelsRoot.querySelector(`[id^="mega-${key}"]`);
    }
    if (!panel) {
      return;
    }

    anchor.setAttribute('aria-expanded', 'false');
    anchor.setAttribute('aria-controls', `mega-${key}`);

    const open = () => {
      if (mobileMegaOpen()) {
        return;
      }
      closeAll();
      anchor.setAttribute('aria-expanded', 'true');
      // Restore interactivity and a11y visibility
      try {
        panel.removeAttribute('inert');
      } catch (_) {}
      panel.hidden = false;
      panel.setAttribute('aria-hidden', 'false');
    };

    const toggle = (evt) => {
      const isOpen = anchor.getAttribute('aria-expanded') === 'true';
      if (!isOpen) {
        // First click opens; do not navigate
        evt.preventDefault();
        open();
        const focusable = panel.querySelector(
          'a,button,input,select,textarea,[tabindex]:not([tabindex="-1"])'
        );
        if (focusable) {
          focusable.focus({ preventScroll: true });
        }
      } else {
        // When already open, allow navigation by not preventing default
        // Optionally close other panels
        closeAll();
      }
    };

    anchor.addEventListener('click', toggle);
    item.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') {
        closeAll();
        anchor.focus();
      }
    });
    if (hoverCapable) {
      item.addEventListener('pointerenter', () => {
        cancelClose();
        open();
      });
    }
  };

  nav.querySelectorAll('.wp-block-navigation-item').forEach(wireItem);
  document.addEventListener('click', (e) => {
    if (mobileMegaOpen()) {
      return;
    }
    if (!nav.contains(e.target) && !panelsRoot.contains(e.target)) {
      closeAll();
    }
  });

  // Hover leave/enter management between triggers and panel area
  if (hoverCapable) {
    nav.addEventListener('pointerleave', scheduleClose);
    nav.addEventListener('pointerenter', cancelClose);
    panelsRoot.addEventListener('pointerleave', scheduleClose);
    panelsRoot.addEventListener('pointerenter', cancelClose);
  }

  // Close mobile Mega Menus
  document.querySelectorAll('.close-mega').forEach((btn) => {
    btn.addEventListener('click', () => {
      closeAll();
    });
  });
})();
