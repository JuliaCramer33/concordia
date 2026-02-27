(function () {
  const container = document.getElementById('mega-search');
  if (!container) {
    return; // No mega-search markup present
  }
  // Default growth direction: right (expands leftwards). Author can override via data-grow="left"
  if (!container.getAttribute('data-grow')) {
    container.setAttribute('data-grow', 'right');
  }
  let lastOpener = null;
  // Hardened: use our explicit selectors only; exit cleanly if missing
  const input =
    container.querySelector('#mega-search-input') ||
    document.getElementById('mega-search-input');
  if (!input) {
    return; // Missing expected controls; exit gracefully
  }
  // Assign after early-return guard above
  const wrapper = container.closest('.mega-search-wrap');
  const submitBtn = container.querySelector('.mega-search__submit');
  // Home path for building on-site URLs (multisite/subdirectory safe)
  let homePath = '/';
  function deriveHomePath() {
    try {
      if (window.wpApiSettings && window.wpApiSettings.root) {
        const p = new URL(window.wpApiSettings.root, window.location.origin)
          .pathname;
        return String(p).replace(/\/wp-json\/?$/, '/') || '/';
      }
    } catch (_) {}
    try {
      const apiLink = document.querySelector('link[rel="https://api.w.org/"]');
      if (apiLink && apiLink.href) {
        const p = new URL(apiLink.href, window.location.origin).pathname;
        return String(p).replace(/\/wp-json\/?$/, '/') || '/';
      }
    } catch (_) {}
    try {
      const homeLink =
        document.querySelector('link[rel="home"]') ||
        document.querySelector('link[rel="homepage"]');
      if (homeLink && homeLink.href) {
        const p = new URL(homeLink.href, window.location.origin).pathname;
        return String(p).replace(/\/+$/, '/') || '/';
      }
    } catch (_) {}
    try {
      const logoA = document.querySelector(
        '.site-header__logo a, .wp-block-site-logo a, a.custom-logo-link'
      );
      if (logoA && logoA.href) {
        const p = new URL(logoA.href, window.location.origin).pathname;
        return String(p).replace(/\/+$/, '/') || '/';
      }
    } catch (_) {}
    return '/';
  }
  homePath = deriveHomePath();

  const obs = new MutationObserver(function (mutations) {
    mutations.forEach(function (m) {
      if (m.attributeName === 'class') {
        if (container.classList.contains('is-open')) {
          input.focus();
          // ensure button is aria-hidden/pressed state for a11y
          if (submitBtn) {
            submitBtn.setAttribute('data-open', 'true');
          }
          if (wrapper) {
            wrapper.classList.add('is-open');
          }
          // show default suggestions when opened
          try {
            renderSuggestions();
          } catch (_) {}
        } else {
          if (submitBtn) {
            submitBtn.removeAttribute('data-open');
            // remove focus from submit when closed so keyboard doesn't accidentally activate it
            submitBtn.blur();
          }
          // hide and clear results on close
          try {
            const r = document.getElementById('mega-search-results');
            if (r) {
              while (r.firstChild) {
                r.removeChild(r.firstChild);
              }
              r.hidden = true;
            }
          } catch (_) {}
          if (wrapper) {
            wrapper.classList.remove('is-open');
          }
        }
      }
    });
  });
  obs.observe(container, { attributes: true });

  function openOverlay(openerEl, ev) {
    if (container.classList.contains('is-open')) {
      return;
    }
    if (ev) {
      ev.preventDefault();
    }
    try {
      const bar =
        container.closest('.site-header__bar') ||
        document.querySelector('.site-header__bar');
      if (bar) {
        const barRect = bar.getBoundingClientRect();
        const gapL = 16,
          gapR = 16;
        let leftPx = 0,
          topPx = 0,
          widthPx = 480;
        const logo = bar.querySelector(
          '.site-header__logo, .wp-block-site-logo'
        );
        if (logo) {
          const logoRect = logo.getBoundingClientRect();
          leftPx = Math.max(0, logoRect.right + gapL);
        } else {
          leftPx = Math.round(barRect.left + barRect.width * 0.15 + gapL);
        }
        topPx = Math.max(0, barRect.top);
        widthPx = Math.max(320, Math.round(barRect.right - gapR - leftPx));
        container.style.setProperty('--ms-left', leftPx + 'px');
        container.style.setProperty('--ms-top', topPx + 'px');
        container.style.setProperty('--ms-width', widthPx + 'px');
        const onResize = function () {
          if (!container.classList.contains('is-open')) {
            return;
          }
          const b = bar.getBoundingClientRect();
          const l = bar.querySelector(
            '.site-header__logo, .wp-block-site-logo'
          );
          const tp = Math.max(0, b.top);
          let lp = 0,
            wp = 480;
          if (l) {
            const lr = l.getBoundingClientRect();
            lp = Math.max(0, lr.right + gapL);
          } else {
            lp = Math.round(b.left + b.width * 0.15 + gapL);
          }
          wp = Math.max(320, Math.round(b.right - gapR - lp));
          container.style.setProperty('--ms-left', lp + 'px');
          container.style.setProperty('--ms-top', tp + 'px');
          container.style.setProperty('--ms-width', wp + 'px');
        };
        window.addEventListener('resize', onResize, { passive: true });
        container._msOnResize = onResize;
      }
    } catch (_) {}
    container.classList.add('is-open');
    try {
      container.setAttribute('aria-expanded', 'true');
    } catch (_) {}
    if (wrapper) {
      wrapper.classList.add('is-open');
    }
    lastOpener = openerEl || submitBtn;
    setTimeout(function () {
      input.focus();
    }, 50);
    try {
      renderSuggestions();
    } catch (_) {}
  }
  // Toggle open when the search button is clicked (acts as opener if closed)
  if (submitBtn) {
    submitBtn.addEventListener('click', function (e) {
      openOverlay(submitBtn, e);
    });
  }
  // External icon block triggers
  document.querySelectorAll('.mega-search-trigger').forEach(function (el) {
    el.setAttribute('role', el.getAttribute('role') || 'button');
    el.setAttribute('tabindex', el.getAttribute('tabindex') || '0');
    el.addEventListener('click', function (e) {
      openOverlay(el, e);
    });
    el.addEventListener('keydown', function (e) {
      if (e.key === 'Enter' || e.key === ' ') {
        e.preventDefault();
        openOverlay(el, e);
      }
    });
  });

  // Clear button behavior: clear and reset to initial state
  const clearBtn = container.querySelector('.mega-search__clear');

  if (clearBtn) {
    clearBtn.addEventListener('click', function (ev) {
      ev.preventDefault();
      beginClose();
    });
  }

  function finishClose() {
    // clear dynamic width
    container.style.removeProperty('--ms-left');
    container.style.removeProperty('--ms-top');
    container.style.removeProperty('--ms-width');
    if (container._msOnResize) {
      window.removeEventListener('resize', container._msOnResize);
      container._msOnResize = null;
    }
    // Hard reset results panel in any case
    try {
      const r = document.getElementById('mega-search-results');
      if (r) {
        r.classList.remove('is-slideout');
        r.classList.remove('is-fadeout');
        r.removeAttribute('style');
        while (r.firstChild) {
          r.removeChild(r.firstChild);
        }
        r.hidden = true;
      }
    } catch (_) {}
    // Clear input after animation completes
    try {
      input.value = '';
    } catch (_) {}
    container.classList.remove('is-open');
    container.classList.remove('is-closing');
    try {
      container.setAttribute('aria-expanded', 'false');
    } catch (_) {}
    if (wrapper) {
      wrapper.classList.remove('is-open');
    }
    // Return focus to the opener button so keyboard users can continue
    setTimeout(function () {
      if (lastOpener && typeof lastOpener.focus === 'function') {
        lastOpener.focus();
      } else if (submitBtn && typeof submitBtn.focus === 'function') {
        submitBtn.focus();
      }
    }, 10);
  }

  function beginClose() {
    // Detach and fade out the results panel while the bar animates
    try {
      const r = document.getElementById('mega-search-results');
      if (r && r.firstChild) {
        const rect = r.getBoundingClientRect();
        const grow = String(
          container.getAttribute('data-grow') || 'right'
        ).toLowerCase();
        // Move to body so it no longer follows the shrinking container
        try {
          document.body.appendChild(r);
        } catch (_) {}
        // Snapshot geometry and lock it during fade
        r.style.position = 'fixed';
        r.style.top = rect.top + 'px';
        r.style.width = rect.width + 'px';
        r.style.height = rect.height + 'px';
        r.style.minWidth = rect.width + 'px';
        r.style.maxWidth = rect.width + 'px';
        r.style.minHeight = rect.height + 'px';
        r.style.maxHeight = rect.height + 'px';
        r.style.boxSizing = 'border-box';
        r.style.zIndex = '99999';
        r.style.pointerEvents = 'none';
        if (grow === 'right') {
          r.style.right = window.innerWidth - rect.right + 'px';
          r.style.left = 'auto';
        } else {
          r.style.left = rect.left + 'px';
          r.style.right = 'auto';
        }
        r.classList.add('is-fadeout');
        const onPanelEnd = function () {
          r.removeEventListener('transitionend', onPanelEnd);
          r.classList.remove('is-fadeout');
          // Cleanup content and styles, then reattach under container
          while (r.firstChild) {
            r.removeChild(r.firstChild);
          }
          r.hidden = true;
          r.removeAttribute('style');
          try {
            container.appendChild(r);
          } catch (_) {}
          container._msPanelCleaned = true;
        };
        r.addEventListener('transitionend', onPanelEnd);
        // Fallback cleanup in case transitionend doesn't fire
        clearTimeout(container._msClosePanelTimer);
        container._msPanelCleaned = false;
        container._msClosePanelTimer = setTimeout(function () {
          if (container._msPanelCleaned) {
            return;
          }
          try {
            r.classList.remove('is-fadeout');
            while (r.firstChild) {
              r.removeChild(r.firstChild);
            }
            r.hidden = true;
            r.removeAttribute('style');
            container.appendChild(r);
          } catch (_) {}
        }, 220);
      }
    } catch (_) {}
    // keep fixed while animating closed
    if (!container.classList.contains('is-open')) {
      return finishClose();
    }
    container.classList.add('is-closing');
    const onEnd = function (e) {
      if (
        e &&
        e.propertyName &&
        e.propertyName !== 'width' &&
        e.propertyName !== 'transform' &&
        e.propertyName !== 'opacity'
      ) {
        return;
      }
      container.removeEventListener('transitionend', onEnd);
      clearTimeout(container._msCloseTimer);
      finishClose();
    };
    container.addEventListener('transitionend', onEnd);
    // Fallback in case transitionend doesn't fire (e.g., width not transitioning)
    clearTimeout(container._msCloseTimer);
    container._msCloseTimer = setTimeout(function () {
      try {
        container.removeEventListener('transitionend', onEnd);
      } catch (_) {}
      if (container.classList.contains('is-closing')) {
        finishClose();
      }
    }, 300);
  }
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && container.classList.contains('is-open')) {
      beginClose();
      e.preventDefault();
    }
  });

  const form = container.querySelector('.mega-search__form');
  if (form) {
    // Ensure form posts to the current site's home URL (multisite/subdirectory safe)
    form.setAttribute('action', homePath || '/');
    // Guarantee a submit control exists so mobile keyboards show "Go"
    if (!form.querySelector('[type="submit"]')) {
      const hiddenSubmit = document.createElement('button');
      hiddenSubmit.type = 'submit';
      hiddenSubmit.className = 'mega-search__submit';
      hiddenSubmit.hidden = true;
      form.appendChild(hiddenSubmit);
    }
    form.addEventListener('submit', function () {
      beginClose();
      const results = document.getElementById('mega-search-results');
      if (results) {
        results.textContent = 'Searching...';
        results.hidden = false;
      }
    });
  }

  // Live search (inline results below the input using WP REST search)
  const resultsEl = document.getElementById('mega-search-results');
  // Basic helpers (global scope for suggestions)
  const capG = function (s) {
    s = String(s || '');
    return s.charAt(0).toUpperCase() + s.slice(1);
  };
  // Detect and cache the REST base for the Program CPT (program vs programs)
  let programRestBase;
  async function ensureProgramRestBase() {
    if (programRestBase) {
      return programRestBase;
    }
    try {
      const res = await fetch(route('wp/v2/types/program'), {
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
      });
      const data = await res.json();
      if (data && data.rest_base) {
        programRestBase = String(data.rest_base).replace(/^\/+|\/+$/g, '');
        if (window.MEGA_SEARCH_DEBUG) {
          /* eslint-disable-next-line no-console */
          console.log('[mega-search] rest_base:', programRestBase);
        }
        return programRestBase;
      }
    } catch (_) {}
    // Fallback probe
    const candidates = ['program', 'programs'];
    for (let i = 0; i < candidates.length; i++) {
      try {
        const testUrl = route(
          'wp/v2/' + candidates[i] + '?per_page=1&_fields=id'
        );
        const r = await fetch(testUrl, {
          headers: { 'X-Requested-With': 'XMLHttpRequest' },
        });
        if (r && (r.ok || r.status === 200)) {
          programRestBase = candidates[i];
          if (window.MEGA_SEARCH_DEBUG) {
            /* eslint-disable-next-line no-console */
            console.log('[mega-search] probed rest_base:', programRestBase);
          }
          return programRestBase;
        }
      } catch (_) {}
    }
    programRestBase = 'program';
    return programRestBase;
  }
  // REST API root (multisite/subdirectory safe)
  const restRoot = (function () {
    try {
      if (window.wpApiSettings && window.wpApiSettings.root) {
        return String(window.wpApiSettings.root).replace(/\/+$/, '/');
      }
    } catch (_) {}
    try {
      const link = document.querySelector('link[rel="https://api.w.org/"]');
      if (link && link.href) {
        return String(link.href).replace(/\/+$/, '/');
      }
    } catch (_) {}
    return '/wp-json/';
  })();
  const route = function (path) {
    try {
      // Build a relative path under the current origin, preserving subdirectory (multisite)
      const basePath = new URL(
        restRoot,
        window.location.origin
      ).pathname.replace(/\/+$/, '/');
      const p = String(path || '').replace(/^\/+/, '');
      return basePath + p;
    } catch (_) {
      const p = String(path || '').replace(/^\/+/, '');
      return '/wp-json/' + p;
    }
  };
  // Normalize links to current origin (multisite-safe)
  function normalizeLink(href) {
    try {
      const currentOrigin = window.location.origin;
      const u = new URL(String(href || ''), currentOrigin);
      if (u.origin !== currentOrigin) {
        return currentOrigin + u.pathname + u.search + u.hash;
      }
      return u.href;
    } catch (_) {
      return String(href || '');
    }
  }
  function normalizeExcerpt(value) {
    try {
      let raw = value;
      if (raw && typeof raw === 'object') {
        raw = raw.rendered || raw.raw || '';
      }
      if (typeof raw !== 'string') {
        return '';
      }
      let text = raw
        .replace(/<[^>]*>/g, ' ')
        .replace(/\s+/g, ' ')
        .trim();
      if (!text || text === '[object Object]') {
        return '';
      }
      if (text.length > 160) {
        text = text.slice(0, 157) + '…';
      }
      return text;
    } catch (_) {
      return '';
    }
  }
  async function fetchTypeList(listBase, subtype, qs) {
    const url = listBase + (qs ? '?' + qs : '');
    const r = await fetch(url, {
      headers: { 'X-Requested-With': 'XMLHttpRequest' },
    });
    const j = await r.json();
    if (!Array.isArray(j)) {
      return [];
    }
    return j.map(function (p) {
      const title = (p.title && (p.title.rendered || p.title)) || '';
      let excerpt = (p.excerpt && (p.excerpt.rendered || p.excerpt)) || '';
      excerpt = String(excerpt)
        .replace(/<[^>]*>/g, '')
        .replace(/\s+/g, ' ')
        .trim();
      if (excerpt.length > 160) {
        excerpt = excerpt.slice(0, 157) + '…';
      }
      return {
        id: p.id,
        title,
        url: p.link,
        subtype,
        excerpt,
      };
    });
  }
  async function renderSuggestions() {
    if (!resultsEl) {
      return;
    }
    try {
      await ensureProgramRestBase();
      const [progs, posts, pages] = await Promise.all([
        fetchTypeList(
          route('wp/v2/' + programRestBase),
          'program',
          'per_page=4&_fields=id,title,link,excerpt'
        ).catch(function () {
          return [];
        }),
        fetchTypeList(
          route('wp/v2/posts'),
          'post',
          'per_page=4&_fields=id,title,link,excerpt'
        ).catch(function () {
          return [];
        }),
        fetchTypeList(
          route('wp/v2/pages'),
          'page',
          'per_page=4&_fields=id,title,link,excerpt'
        ).catch(function () {
          return [];
        }),
      ]);
      const items = []
        .concat(progs, posts, pages)
        .map(function (it) {
          return Object.assign({}, it, {
            excerpt: normalizeExcerpt(it.excerpt),
          });
        })
        .slice(0, 4);

      // Clear container
      resultsEl.hidden = false;
      while (resultsEl.firstChild) {
        resultsEl.removeChild(resultsEl.firstChild);
      }

      const headingEl = document.createElement('div');
      headingEl.className = 'mega-search__results-title';
      headingEl.textContent = 'Popular right now';
      headingEl.id = 'mega-search-heading';
      container.setAttribute('aria-labelledby', 'mega-search-heading');
      resultsEl.appendChild(headingEl);

      if (items.length === 0) {
        const emptyEl = document.createElement('div');
        emptyEl.className = 'mega-search__empty';
        emptyEl.textContent = 'No suggestions available.';
        resultsEl.appendChild(emptyEl);
        return;
      }

      const grid = document.createElement('div');
      grid.className = 'mega-search__grid';
      grid.setAttribute('role', 'list');
      items.forEach(function (it) {
        const a = document.createElement('a');
        a.className = 'mega-search__result';
        a.href = normalizeLink(it.url);
        a.setAttribute('role', 'listitem');

        if (it.subtype) {
          const chip = document.createElement('span');
          chip.className = 'mega-search__chip';
          chip.textContent = capG(it.subtype);
          a.appendChild(chip);
        }
        const titleEl = document.createElement('span');
        titleEl.className = 'mega-search__title';
        titleEl.textContent = String(it.title || '');
        a.appendChild(titleEl);

        if (it.excerpt) {
          const ex = document.createElement('span');
          ex.className = 'mega-search__excerpt';
          ex.textContent = it.excerpt;
          a.appendChild(ex);
        }
        grid.appendChild(a);
      });
      resultsEl.appendChild(grid);
    } catch (_) {}
  }
  // Reduce native browser suggestion popovers
  try {
    input.setAttribute('autocomplete', 'off');
    input.setAttribute('autocorrect', 'off');
    input.setAttribute('autocapitalize', 'off');
    input.setAttribute('spellcheck', 'false');
    input.setAttribute('aria-autocomplete', 'list');
  } catch (_) {}

  let debounceId;
  let ac; // AbortController
  const searchCache = new Map(); // q -> { ts, items }
  let lastQuery = '';
  if (resultsEl) {
    resultsEl.hidden = true;
  }
  input.addEventListener('input', function () {
    clearTimeout(debounceId);
    if (!resultsEl) {
      return;
    }
    const q = input.value.trim();
    if (!q || q.length < 2) {
      renderSuggestions();
      return;
    }
    if (q === lastQuery) {
      return;
    }
    lastQuery = q;
    debounceId = setTimeout(async function () {
      try {
        ac?.abort();
        ac = new AbortController();
        try {
          resultsEl.setAttribute('aria-busy', 'true');
        } catch (_) {}
        // Serve from cache if fresh (<60s)
        const cached = searchCache.get(q);
        if (cached && Date.now() - cached.ts < 60000) {
          renderResults(q, cached.items);
          try {
            resultsEl.removeAttribute('aria-busy');
          } catch (_) {}
          return;
        }
        // Universal search across all types, then hydrate excerpts for supported types
        await ensureProgramRestBase();
        const searchUrl =
          route('wp/v2/search') +
          '?search=' +
          encodeURIComponent(q) +
          '&per_page=12&_fields=id,type,subtype,title,url';
        const sr = await fetch(searchUrl, {
          headers: { 'X-Requested-With': 'XMLHttpRequest' },
          signal: ac.signal,
        });
        const sj = await sr.json();
        const all = Array.isArray(sj)
          ? sj.map(function (hit) {
              const t =
                (hit.title && (hit.title.rendered || hit.title)) ||
                hit.title ||
                '';
              return {
                id: hit.id,
                title: String(t),
                url: hit.url,
                subtype: hit.subtype || hit.type || '',
                excerpt: '',
              };
            })
          : [];
        const pr = String(programRestBase || 'program').toLowerCase();
        const top = all.slice(0, 4);
        async function hydrate(item) {
          try {
            const sub = String(item.subtype || '').toLowerCase();
            let base = '';
            if (sub === 'post' || sub === 'posts') {
              base = 'posts';
            } else if (sub === 'page' || sub === 'pages') {
              base = 'pages';
            } else if (sub === pr) {
              base = programRestBase;
            } else {
              return item;
            } // unknown type; keep title-only
            const hr = await fetch(
              route(
                'wp/v2/' +
                  base +
                  '/' +
                  encodeURIComponent(item.id) +
                  '?_fields=excerpt,link,title'
              ),
              {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                signal: ac.signal,
              }
            );
            const hj = await hr.json();
            const title =
              (hj && hj.title && (hj.title.rendered || hj.title)) ||
              item.title ||
              '';
            const excerpt = hj ? normalizeExcerpt(hj.excerpt) : '';
            const url = hj && hj.link ? hj.link : item.url;
            return { id: item.id, title, url, subtype: item.subtype, excerpt };
          } catch (_) {
            return item;
          }
        }
        const items = await Promise.all(top.map(hydrate));
        searchCache.set(q, { ts: Date.now(), items });
        renderResults(q, items);
        try {
          resultsEl.removeAttribute('aria-busy');
        } catch (_) {}
      } catch (_) {
        // ignore aborts/errors
      }
    }, 200);
  });

  function renderResults(q, items) {
    const render = items.slice(0, 4);
    resultsEl.hidden = false;
    while (resultsEl.firstChild) {
      resultsEl.removeChild(resultsEl.firstChild);
    }

    const headingEl = document.createElement('div');
    headingEl.className = 'mega-search__results-title';
    headingEl.textContent = 'Results for “' + String(q) + '”';
    headingEl.id = 'mega-search-heading';
    container.setAttribute('aria-labelledby', 'mega-search-heading');
    resultsEl.appendChild(headingEl);

    if (render.length === 0) {
      const emptyEl = document.createElement('div');
      emptyEl.className = 'mega-search__empty';
      emptyEl.textContent = 'No results found. Try a different search term.';
      resultsEl.appendChild(emptyEl);

      const moreWrap = document.createElement('div');
      const moreA = document.createElement('a');
      moreA.className = 'mega-search__more';
      moreA.href =
        String(homePath || '/').replace(/\/+$/, '/') +
        '?s=' +
        encodeURIComponent(q);
      moreA.textContent = 'Find more for “' + String(q) + '”';
      moreWrap.appendChild(moreA);
      resultsEl.appendChild(moreWrap);
      return;
    }

    const grid = document.createElement('div');
    grid.className = 'mega-search__grid';
    grid.setAttribute('role', 'list');
    render.forEach(function (it) {
      const a = document.createElement('a');
      a.className = 'mega-search__result';
      a.href = normalizeLink(it.url);
      a.setAttribute('role', 'listitem');

      if (it.subtype) {
        const chip = document.createElement('span');
        chip.className = 'mega-search__chip';
        chip.textContent = (function (s) {
          s = String(s || '');
          return s.charAt(0).toUpperCase() + s.slice(1);
        })(it.subtype);
        a.appendChild(chip);
      }
      const titleEl = document.createElement('span');
      titleEl.className = 'mega-search__title';
      titleEl.textContent = String(it.title || '');
      a.appendChild(titleEl);

      const exText = normalizeExcerpt(it.excerpt);
      if (exText) {
        const ex = document.createElement('span');
        ex.className = 'mega-search__excerpt';
        ex.textContent = exText;
        a.appendChild(ex);
      }
      grid.appendChild(a);
    });
    resultsEl.appendChild(grid);

    const moreWrap = document.createElement('div');
    const moreA = document.createElement('a');
    moreA.className = 'mega-search__more';
    moreA.href =
      String(homePath || '/').replace(/\/+$/, '/') +
      '?s=' +
      encodeURIComponent(q);
    moreA.textContent = 'More results for “' + String(q) + '”';
    moreWrap.appendChild(moreA);
    resultsEl.appendChild(moreWrap);
  }
})();
