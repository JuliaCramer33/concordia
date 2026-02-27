// blocks/post-carousel/edit.js — rewritten lightweight editor implementation
(function () {
  if (!(window.wp && wp.blocks && wp.blockEditor && wp.components && wp.element && wp.i18n)) return;

  var __ = wp.i18n.__;
  var registerBlockType = wp.blocks.registerBlockType;

  var be = wp.blockEditor;
  var InspectorControls = be.InspectorControls;
  var BlockControls = be.BlockControls;
  var useBlockProps = be.useBlockProps;
  var InnerBlocks = be.InnerBlocks;
  var RichText = be.RichText;
  var ColorPalette = be.ColorPalette;

  var c = wp.components;
  var PanelBody = c.PanelBody;
  var SelectControl = c.SelectControl;
  var RangeControl = c.RangeControl;
  var UnitControl = c.__experimentalUnitControl;
  var ToggleControl = c.ToggleControl;
  var ToolbarGroup = c.ToolbarGroup;
  var ToolbarButton = c.ToolbarButton;

  var el = wp.element.createElement;
  var Fragment = wp.element.Fragment;
  var useRef = wp.element.useRef;
  var useEffect = wp.element.useEffect;

  function getStepFromAttrs(attrs) {
    var mode = attrs.mode || 'auto';
    if (mode === 'single') return 1;
    var vw = window.innerWidth || 1024;
    if (vw >= 1024) return parseInt(attrs.spvDesktop || 2, 10) || 2;
    if (vw >= 700) return parseInt(attrs.spvTablet || 1, 10) || 1;
    return parseInt(attrs.spvMobile || 1, 10) || 1;
  }

  function normalizeQueryTrack(root, attrs) {
    var track = root && root.querySelector && root.querySelector('.wp-block-post-template');
    if (!track) return null;

    // Force track to be a horizontal scroller
    track.style.display = 'flex';
    track.style.flexDirection = 'row';
    track.style.flexWrap = 'nowrap';
    track.style.gap = attrs.gap || '1rem';
    track.style.overflowX = 'auto';
    track.style.scrollSnapType = 'x mandatory';

    var step = getStepFromAttrs(attrs);
    var basis = 'calc((100% - (' + step + ' - 1) * var(--gap, ' + (attrs.gap || '1rem') + ')) / ' + step + ')';
    Array.prototype.forEach.call(track.children || [], function (li) {
      if (!li || li.nodeType !== 1) return;
      li.style.flex = '0 0 ' + basis;
      li.style.minWidth = basis;
      li.style.scrollSnapAlign = 'start';
      // Ensure inner wrapper fills slide
      var inner = li.querySelector('.block-editor-block-list__block');
      if (inner) { inner.style.width = '100%'; inner.style.maxWidth = '100%'; }
    });

    // End padding so last slide can fully scroll into view
    track.style.paddingRight = 'calc(80px / ' + step + ')';
    return track;
  }

  function Edit(props) {
    var attributes = props.attributes || {};
    var setAttributes = props.setAttributes;

    var spvMobile = attributes.spvMobile || 1;
    var spvTablet = attributes.spvTablet || 2;
    var spvDesktop = attributes.spvDesktop || 4;
    var gap = attributes.gap || '1rem';
    var arrows = attributes.arrows || 'top-right';
    if (arrows === 'top-left') { setAttributes({ arrows: 'top-right' }); arrows = 'top-right'; }
    var showProgress = !!attributes.showProgress;
    var titleText = attributes.titleText || '';
    var titleLevel = attributes.titleLevel || 'h3';
    var titleColor = attributes.titleColor || '';
    var titleFontSize = attributes.titleFontSize || '';
    var mode = attributes.mode || 'auto';
    var bleed = attributes.bleed || 'none';

    var carouselRef = useRef(null);

    function scrollByDir(dir) {
      var root = carouselRef.current; if (!root) return;
      var track = root.querySelector('.wp-block-post-template');
      if (!track) return;
      var slides = Array.prototype.filter.call(track.children || [], function (n) { return n && n.nodeType === 1; });
      if (!slides.length) return;

      function leftOf(i) {
        var r = slides[i].getBoundingClientRect();
        var t = track.getBoundingClientRect();
        return track.scrollLeft + (r.left - t.left);
      }
      var scroll = track.scrollLeft; var idx = 0; var min = Infinity;
      for (var i = 0; i < slides.length; i++) {
        var l = leftOf(i); var d = Math.abs(l - scroll); if (d < min) { min = d; idx = i; }
      }
      var step = (mode === 'single') ? 1 : getStepFromAttrs({ spvMobile: spvMobile, spvTablet: spvTablet, spvDesktop: spvDesktop, mode: mode });
      var target = Math.min(slides.length - 1, Math.max(0, idx + (dir < 0 ? -step : step)));
      track.scrollTo({ left: leftOf(target), behavior: 'smooth' });
    }

    useEffect(function () {
      var root = carouselRef.current; if (!root) return;
      normalizeQueryTrack(root, { spvMobile: spvMobile, spvTablet: spvTablet, spvDesktop: spvDesktop, gap: gap, mode: mode });
      function onResize() { normalizeQueryTrack(root, { spvMobile: spvMobile, spvTablet: spvTablet, spvDesktop: spvDesktop, gap: gap, mode: mode }); }
      window.addEventListener('resize', onResize);
      return function () { window.removeEventListener('resize', onResize); };
    }, [spvMobile, spvTablet, spvDesktop, gap, mode]);

    // Compute bleed offsets in editor to support edge spacers
    useEffect(function () {
      var root = carouselRef.current; if (!root) return;
      var viewport = root.querySelector('[data-carousel-viewport]');
      if (!viewport) return;
      function compute() {
        var rect = viewport.getBoundingClientRect();
        var left = Math.max(0, rect.left);
        var right = Math.max(0, (window.innerWidth || document.documentElement.clientWidth) - rect.right);
        root.style.setProperty('--offset-left', Math.round(left) + 'px');
        root.style.setProperty('--offset-right', Math.round(right) + 'px');
      }
      var raf = requestAnimationFrame(function () { requestAnimationFrame(compute); });
      function onResize() { compute(); }
      window.addEventListener('resize', onResize);
      return function () { window.removeEventListener('resize', onResize); cancelAnimationFrame(raf); };
    }, [bleed, mode]);

    var baseBlockProps = useBlockProps({
      ref: carouselRef,
      className: 'kanahoma-post-carousel',
      style: {
        '--gap': gap,
        '--spv-mobile': spvMobile,
        '--spv-tablet': spvTablet,
        '--spv-desktop': spvDesktop
      },
      'data-mode': mode,
      'data-controls-pos': arrows,
      'data-bleed': bleed,
      'data-post-carousel': true
    });

    return el(
      Fragment,
      null,
      el(
        BlockControls,
        null,
        el(
          ToolbarGroup,
          null,
          el(ToolbarButton, { icon: 'arrow-left-alt2', label: __('Previous slide', 'kanahoma'), onClick: function (e) { e.preventDefault(); e.stopPropagation(); scrollByDir(-1); } }),
          el(ToolbarButton, { icon: 'arrow-right-alt2', label: __('Next slide', 'kanahoma'), onClick: function (e) { e.preventDefault(); e.stopPropagation(); scrollByDir(1); } })
        )
      ),

      el(
        InspectorControls,
        null,
        el(
          PanelBody,
          { title: __('Slides per view', 'kanahoma'), initialOpen: true },
          el(SelectControl, {
            label: __('Mode', 'kanahoma'),
            value: mode,
            onChange: function (v) { setAttributes({ mode: v }); },
            options: [
              { label: __('Single (always 1)', 'kanahoma'), value: 'single' },
              { label: __('Auto (use breakpoints)', 'kanahoma'), value: 'auto' }
            ]
          }),
          mode === 'auto' && el(Fragment, null,
            el(RangeControl, { label: __('Mobile (≤700px)', 'kanahoma'), value: spvMobile, min: 1, max: 4, onChange: function (v) { setAttributes({ spvMobile: v || 1 }); } }),
            el(RangeControl, { label: __('Tablet (≥700px)', 'kanahoma'), value: spvTablet, min: 1, max: 4, onChange: function (v) { setAttributes({ spvTablet: v || 2 }); } }),
            el(RangeControl, { label: __('Desktop (≥1024px)', 'kanahoma'), value: spvDesktop, min: 1, max: 6, onChange: function (v) { setAttributes({ spvDesktop: v || 4 }); } })
          ),
          el(UnitControl, { label: __('Gap between slides', 'kanahoma'), value: gap, onChange: function (v) { setAttributes({ gap: v || '1rem' }); }, units: ['px', 'rem'] })
        ),
        el(
          PanelBody,
          { title: __('Arrow placement', 'kanahoma'), initialOpen: false },
          el(SelectControl, {
            value: arrows,
            onChange: function (v) { setAttributes({ arrows: v }); },
            options: [
              { label: __('Above (header)', 'kanahoma'), value: 'top-right' },
              { label: __('Bottom right (below track)', 'kanahoma'), value: 'bottom-right' },
              { label: __('Bottom left (below track)', 'kanahoma'), value: 'bottom-left' },
              { label: __('Bottom center (below track)', 'kanahoma'), value: 'bottom-center' },
              { label: __('Hide arrows', 'kanahoma'), value: 'none' }
            ]
          }),
          el(ToggleControl, { label: __('Show progress bar', 'kanahoma'), checked: !!showProgress, onChange: function (v) { setAttributes({ showProgress: v }); } })
        ),
        el(
          PanelBody,
          { title: __('Edge bleed', 'kanahoma'), initialOpen: false },
          el(SelectControl, {
            label: __('Bleed side', 'kanahoma'),
            value: bleed,
            onChange: function (v) { setAttributes({ bleed: v }); },
            options: [
              { label: __('None', 'kanahoma'), value: 'none' },
              { label: __('Left', 'kanahoma'), value: 'left' },
              { label: __('Right', 'kanahoma'), value: 'right' },
              { label: __('Both', 'kanahoma'), value: 'both' }
            ]
          })
        ),
        el(
          PanelBody,
          { title: __('Title settings', 'kanahoma'), initialOpen: false },
          el(SelectControl, {
            label: __('Title level', 'kanahoma'),
            value: titleLevel,
            onChange: function (v) { setAttributes({ titleLevel: v }); },
            options: [
              { label: 'H2', value: 'h2' },
              { label: 'H3', value: 'h3' },
              { label: 'H4', value: 'h4' },
              { label: 'H5', value: 'h5' }
            ]
          }),
          el(UnitControl, { label: __('Title size', 'kanahoma'), value: titleFontSize, onChange: function (v) { setAttributes({ titleFontSize: v || '' }); }, units: ['px', 'rem', 'em'] }),
          el('div', { className: 'components-base-control' },
            el('label', { className: 'components-base-control__label' }, __('Title color', 'kanahoma')),
            el(ColorPalette, { value: titleColor, onChange: function (v) { setAttributes({ titleColor: v || '' }); } })
          )
        )
      ),

      el(
        'section',
        baseBlockProps,
        el(
          'div',
          { className: 'kanahoma-carousel__header' },
          el(RichText, { tagName: titleLevel, className: 'kanahoma-carousel__title', placeholder: __('Optional title…', 'kanahoma'), value: titleText, onChange: function (v) { setAttributes({ titleText: v }); } }),
          (arrows === 'top-right') && el('div', { className: 'kanahoma-carousel__controls', 'data-carousel-controls': true },
            el('button', { className: 'kanahoma-carousel__prev', type: 'button', 'aria-label': __('Previous slide', 'kanahoma'), onClick: function (e) { e.preventDefault(); e.stopPropagation(); scrollByDir(-1); } }, '‹'),
            el('button', { className: 'kanahoma-carousel__next', type: 'button', 'aria-label': __('Next slide', 'kanahoma'), onClick: function (e) { e.preventDefault(); e.stopPropagation(); scrollByDir(1); } }, '›')
          )
        ),

        (!!showProgress) && el('div', { className: 'kanahoma-carousel__progress', 'aria-hidden': 'true' }, el('div', { className: 'kanahoma-carousel__progress-bar' })),

        el(
          'div',
          { className: 'kanahoma-carousel__viewport', 'data-carousel-viewport': true },
          el(
            'div',
            { className: 'kanahoma-carousel__items', 'data-carousel-items': true },
            el(InnerBlocks, { allowedBlocks: ['core/query'], renderAppender: InnerBlocks.ButtonBlockAppender })
          )
        ),

        ((arrows === 'bottom-right') || (arrows === 'bottom-left') || (arrows === 'bottom-center')) && el(
          'div',
          { className: 'kanahoma-carousel__controls kanahoma-carousel__controls--bottom', 'data-carousel-controls': true },
          el('button', { className: 'kanahoma-carousel__prev', type: 'button', 'aria-label': __('Previous slide', 'kanahoma'), onClick: function (e) { e.preventDefault(); e.stopPropagation(); scrollByDir(-1); } }, '‹'),
          el('button', { className: 'kanahoma-carousel__next', type: 'button', 'aria-label': __('Next slide', 'kanahoma'), onClick: function (e) { e.preventDefault(); e.stopPropagation(); scrollByDir(1); } }, '›')
        ),

        (arrows === 'bottom-center') && el('style', null, '.kanahoma-carousel__controls--bottom{justify-content:center}')
      )
    );
  }

  registerBlockType('kanahoma/post-carousel', {
    edit: Edit,
    save: function () { return wp.element.createElement(wp.blockEditor.InnerBlocks.Content, null); }
  });
})();

// blocks/carousel/edit.js — plain JS (no JSX), matches your JSX behavior
(function () {
  if (!(window.wp && wp.blocks && wp.blockEditor && wp.components && wp.element && wp.i18n)) return;

  var __ = wp.i18n.__;
  var registerBlockType = wp.blocks.registerBlockType;

  var be = wp.blockEditor;
  var InspectorControls = be.InspectorControls;
  var BlockControls = be.BlockControls;
  var useBlockProps = be.useBlockProps;
  var InnerBlocks = be.InnerBlocks;
  var RichText = be.RichText;

  var c = wp.components;
  var PanelBody = c.PanelBody;
  var SelectControl = c.SelectControl;
  var RangeControl = c.RangeControl;
  var UnitControl = c.__experimentalUnitControl; // experimental control (works in WP core)
  var ToggleControl = c.ToggleControl;
  var ToolbarGroup = c.ToolbarGroup;
  var ToolbarButton = c.ToolbarButton;

  var el = wp.element.createElement;
  var Fragment = wp.element.Fragment;
  var useRef = wp.element.useRef;
  var useEffect = wp.element.useEffect;

  var EDEBUG = (typeof window !== 'undefined') && !!window.KANAHOMA_CAROUSEL_DEBUG;

  function Edit(props) {
    var attributes = props.attributes || {};
    var setAttributes = props.setAttributes;

    var spvMobile = attributes.spvMobile;
    var spvTablet = attributes.spvTablet;
    var spvDesktop = attributes.spvDesktop;
    var gap = attributes.gap;
    var peek = attributes.peek;
    var arrows = attributes.arrows;
    // Normalize legacy 'top-left' to 'top-right' (renamed to 'Above (header)')
    if (arrows === 'top-left') { setAttributes({ arrows: 'top-right' }); arrows = 'top-right'; }
    var showProgress = attributes.showProgress;
    var titleText = attributes.titleText;
    var mode = attributes.mode;
    var bleed = attributes.bleed;

    var itemsRef = useRef(null);

    function scrollByDir(dir) {
      var items = itemsRef.current; if (!items) return;

      // Prefer the Query Loop post template as the track when present
      var track = items.querySelector('.wp-block-post-template');
      var layout = items.querySelector(':scope > .block-editor-inner-blocks > .block-editor-block-list__layout');
      var candidates = [track, items, layout, layout && layout.parentElement].filter(Boolean);
      var container = candidates.find(function (elmt) {
        return (elmt.scrollWidth - elmt.clientWidth) > 1;
      }) || (track || items);

      var slides = track ? Array.prototype.slice.call(track.children)
        : (layout ? Array.prototype.slice.call(layout.children)
          : Array.prototype.slice.call(items.children));
      if (!slides.length) return;

      // Editor debug logs removed

      function slideLeft(i) {
        var slideRect = slides[i].getBoundingClientRect();
        var itemsRect = container.getBoundingClientRect();
        return container.scrollLeft + (slideRect.left - itemsRect.left);
      }

      function currentIndex() {
        var scroll = container.scrollLeft;
        var idx = 0, min = Infinity;
        for (var i = 0; i < slides.length; i++) {
          var left = slideLeft(i);
          var dist = Math.abs(left - scroll);
          if (dist < min) { min = dist; idx = i; }
        }
        return idx;
      }

      var target = Math.min(slides.length - 1, Math.max(0, currentIndex() + dir * 1));
      var targetLeft = slideLeft(target);

      // Editor debug logs removed

      // Editor debug outline removed

      var prevSnapInline = container.style.scrollSnapType;
      var prevSnapComputed = getComputedStyle(container).scrollSnapType;

      var before = container.scrollLeft;
      container.style.scrollSnapType = 'none';
      container.scrollLeft = targetLeft;
      var afterImmediate = container.scrollLeft;

      // Editor debug logs removed

      setTimeout(function () {
        container.scrollLeft = targetLeft;
        container.style.scrollSnapType = prevSnapInline || prevSnapComputed || '';

      }, 16);


    }

    // Force Gutenberg inner layout to be horizontal in the editor (safety net; keep your editor.css too)
    useEffect(function () {
      var items = itemsRef.current;
      if (!items) return;
      var layout = items.querySelector(':scope > .block-editor-block-list__layout');
      if (!layout) return;
      layout.style.display = 'flex';
      layout.style.flexWrap = 'nowrap';
      layout.style.gap = 'var(--gap)';
    });

    // Calculate offset for bleed effect in editor
    var carouselRef = useRef(null);
    useEffect(function () {
      if (!bleed || bleed === 'none') return;

      function calculateOffset() {
        var carousel = carouselRef.current;
        if (!carousel) return;

        // For bleed effect, calculate offset to viewport edge
        // This creates space so adjacent slides can "peek" into view
        var carouselRect = carousel.getBoundingClientRect();
        var viewport = {
          left: 0,
          right: window.innerWidth
        };

        // Calculate distance from carousel edge to viewport edge
        var offsetLeft = Math.max(0, carouselRect.left - viewport.left);
        var offsetRight = Math.max(0, viewport.right - carouselRect.right);

        // Always use at least 60px for visible peek, or calculated value + 20px if larger
        // This ensures you can always see a meaningful peek of adjacent content
        var leftOffset = Math.max(60, offsetLeft + 20);
        var rightOffset = Math.max(60, offsetRight + 20);

        // Only set offset if bleed is enabled for that side
        if (bleed === 'left' || bleed === 'both') {
          carousel.style.setProperty('--offset-left', leftOffset + 'px');
        }
        if (bleed === 'right' || bleed === 'both') {
          carousel.style.setProperty('--offset-right', rightOffset + 'px');
        }
        if (bleed === 'none') {
          carousel.style.removeProperty('--offset-left');
          carousel.style.removeProperty('--offset-right');
        }
      }

      // Delay initial calculation to ensure DOM is ready
      var timeoutId = setTimeout(calculateOffset, 100);

      // Recalculate on resize
      var resizeTimeout;
      function handleResize() {
        clearTimeout(resizeTimeout);
        resizeTimeout = setTimeout(calculateOffset, 100);
      }
      window.addEventListener('resize', handleResize);

      // Recalculate when carousel position might change
      var carousel = carouselRef.current;
      var observer = carousel ? new MutationObserver(calculateOffset) : null;
      if (observer && carousel) {
        observer.observe(carousel, { attributes: true, attributeFilter: ['style', 'class'] });
        // Also observe parent changes
        if (carousel.parentElement) {
          observer.observe(carousel.parentElement, { childList: true, subtree: false });
        }
      }

      return function () {
        clearTimeout(timeoutId);
        clearTimeout(resizeTimeout);
        window.removeEventListener('resize', handleResize);
        if (observer) observer.disconnect();
      };
    }, [bleed]);

    // Force Query Loop track to horizontal in editor (in case theme/editor CSS overrides)
    useEffect(function () {
      var root = carouselRef.current; if (!root) return;
      var track = root.querySelector('.wp-block-post-template');
      if (!track) return;
      try {
        track.style.display = 'flex';
        track.style.flexDirection = 'row';
        track.style.flexWrap = 'nowrap';
        track.style.gap = 'var(--gap)';
        track.style.overflowX = 'auto';
        track.style.scrollSnapType = 'x mandatory';
        var basisAuto = 'calc((100% - (var(--step, 1) - 1) * var(--gap)) / var(--step, 1))';
        var basisPeek = 'calc(((100% - (var(--step, 1) - 1) * var(--gap)) / var(--step, 1)) - (80px / var(--step, 1)))';
        var basis = (mode === 'auto') ? basisPeek : '100%';
        Array.prototype.forEach.call(track.children, function (li) {
          if (!(li && li.nodeType === 1)) return;
          li.style.flex = '0 0 ' + basis;
          li.style.minWidth = (mode === 'auto') ? basisAuto : '100%';
          li.style.scrollSnapAlign = 'start';
          // neutralize any inner wrappers that constrain width
          var inner = li.querySelector('.block-editor-block-list__block');
          if (inner) { inner.style.maxWidth = '100%'; inner.style.width = '100%'; }
        });
      } catch (e) { }
    }, [mode, gap, spvMobile, spvTablet, spvDesktop]);

    var baseBlockProps = useBlockProps({
      className: 'kanahoma-carousel kanahoma-query-carousel',
      style: {
        '--gap': gap,
        '--peek': peek,
        '--spv-mobile': spvMobile,
        '--spv-tablet': spvTablet,
        '--spv-desktop': spvDesktop
      },
      'data-mode': mode,
      'data-controls-pos': arrows,
      'data-bleed': bleed,
      'data-query-carousel': true
    });

    // Attach ref to block props
    var blockProps = {
      ...baseBlockProps,
      ref: function (element) {
        carouselRef.current = element;
        // Call original ref if it exists
        if (baseBlockProps.ref) {
          if (typeof baseBlockProps.ref === 'function') {
            baseBlockProps.ref(element);
          } else if (baseBlockProps.ref.current !== undefined) {
            baseBlockProps.ref.current = element;
          }
        }
      }
    };

    return el(
      Fragment,
      null,

      // Block toolbar (prev/next)
      el(
        BlockControls,
        null,
        el(
          ToolbarGroup,
          null,
          el(ToolbarButton, {
            icon: 'arrow-left-alt2',
            label: __('Previous slide', 'kanahoma'),
            onClick: function (e) { e.preventDefault(); e.stopPropagation(); scrollByDir(-1); }
          }),
          el(ToolbarButton, {
            icon: 'arrow-right-alt2',
            label: __('Next slide', 'kanahoma'),
            onClick: function (e) { e.preventDefault(); e.stopPropagation(); scrollByDir(1); }
          })
        )
      ),

      // Inspector controls
      el(
        InspectorControls,
        null,

        // Slides per view panel
        el(
          PanelBody,
          { title: __('Slides per view', 'kanahoma'), initialOpen: true },

          el(SelectControl, {
            label: __('Mode', 'kanahoma'),
            value: mode,
            onChange: function (v) { setAttributes({ mode: v }); },
            __next40pxDefaultSize: true,
            __nextHasNoMarginBottom: true,
            options: [
              { label: __('Single (always 1)', 'kanahoma'), value: 'single' },
              { label: __('Auto (use breakpoints)', 'kanahoma'), value: 'auto' }
            ]
          }),

          mode === 'auto' && el(Fragment, null,
            el(RangeControl, {
              label: __('Mobile (≤700px)', 'kanahoma'),
              value: spvMobile,
              min: 1, max: 4,
              onChange: function (v) { setAttributes({ spvMobile: v || 1 }); }
            }),
            el(RangeControl, {
              label: __('Tablet (≥700px)', 'kanahoma'),
              value: spvTablet,
              min: 1, max: 4,
              onChange: function (v) { setAttributes({ spvTablet: v || 2 }); }
            }),
            el(RangeControl, {
              label: __('Desktop (≥1024px)', 'kanahoma'),
              value: spvDesktop,
              min: 1, max: 6,
              onChange: function (v) { setAttributes({ spvDesktop: v || 4 }); }
            })
          ),

          el(UnitControl, {
            label: __('Gap between slides', 'kanahoma'),
            value: gap,
            onChange: function (v) { setAttributes({ gap: v || '1rem' }); },
            __next40pxDefaultSize: true,
            __nextHasNoMarginBottom: true,
            units: ['px', 'rem']
          }),

          el(UnitControl, {
            label: __('Right “peek” when equal', 'kanahoma'),
            value: peek,
            onChange: function (v) { setAttributes({ peek: v || '0px' }); },
            __next40pxDefaultSize: true,
            __nextHasNoMarginBottom: true,
            units: ['px', 'rem']
          })
        ),

        // Arrow placement
        el(
          PanelBody,
          { title: __('Arrow placement', 'kanahoma'), initialOpen: false },
          el(SelectControl, {
            value: arrows,
            onChange: function (v) { setAttributes({ arrows: v }); },
            __next40pxDefaultSize: true,
            __nextHasNoMarginBottom: true,
            options: [
              { label: __('Above (header)', 'kanahoma'), value: 'top-right' },
              { label: 'Bottom right (below track)', value: 'bottom-right' },
              { label: 'Bottom left (below track)', value: 'bottom-left' },
              { label: 'Bottom center (below track)', value: 'bottom-center' },
              { label: 'Hide arrows', value: 'none' }
            ]
          }),
          el(ToggleControl, {
            label: __('Show progress bar', 'kanahoma'),
            checked: !!showProgress,
            onChange: function (v) { setAttributes({ showProgress: v }); }
          })
        ),

        // Bleed
        el(
          PanelBody,
          { title: __('Bleed (edge breakout)', 'kanahoma'), initialOpen: false },
          el(SelectControl, {
            label: __('Edge bleed', 'kanahoma'),
            value: bleed,
            onChange: function (v) { setAttributes({ bleed: v }); },
            __next40pxDefaultSize: true,
            __nextHasNoMarginBottom: true,
            options: [
              { label: __('None', 'kanahoma'), value: 'none' },
              { label: __('Left', 'kanahoma'), value: 'left' },
              { label: __('Right', 'kanahoma'), value: 'right' },
              { label: __('Both', 'kanahoma'), value: 'both' }
            ]
          })
        )
      ),

      // Canvas
      el(
        'section',
        blockProps,

        // Header
        el(
          'div',
          { className: 'kanahoma-carousel__header' },

          el(RichText, {
            tagName: 'h3',
            className: 'kanahoma-carousel__title',
            placeholder: __('Optional title…', 'kanahoma'),
            value: titleText,
            onChange: function (v) { setAttributes({ titleText: v }); }
          }),

          (arrows === 'top-right') && el(
            'div',
            { className: 'kanahoma-carousel__controls', 'data-carousel-controls': true },
            el('button', {
              className: 'kanahoma-carousel__prev',
              type: 'button',
              'aria-label': __('Previous slide', 'kanahoma'),
              onClick: function (e) { e.preventDefault(); e.stopPropagation(); scrollByDir(-1); }
            }, '‹'),
            el('button', {
              className: 'kanahoma-carousel__next',
              type: 'button',
              'aria-label': __('Next slide', 'kanahoma'),
              onClick: function (e) { e.preventDefault(); e.stopPropagation(); scrollByDir(1); }
            }, '›')
          )
        ),

        // Progress
        (!!showProgress) && el(
          'div',
          { className: 'kanahoma-carousel__progress', 'aria-hidden': 'true' },
          el('div', { className: 'kanahoma-carousel__progress-bar' })
        ),

        // Track
        el(
          'div',
          { className: 'kanahoma-carousel__viewport', 'data-carousel-viewport': true },

          (arrows === 'inside-right') && el(
            'div',
            { className: 'kanahoma-carousel__controls kanahoma-carousel__controls--inside', 'data-carousel-controls': true },
            el('button', {
              className: 'kanahoma-carousel__prev',
              type: 'button',
              'aria-label': __('Previous slide', 'kanahoma'),
              onClick: function (e) { e.preventDefault(); e.stopPropagation(); scrollByDir(-1); }
            }, '‹'),
            el('button', {
              className: 'kanahoma-carousel__next',
              type: 'button',
              'aria-label': __('Next slide', 'kanahoma'),
              onClick: function (e) { e.preventDefault(); e.stopPropagation(); scrollByDir(1); }
            }, '›')
          ),

          el(
            'div',
            { className: 'kanahoma-carousel__items', 'data-carousel-items': true, ref: itemsRef },
            el(
              InnerBlocks,
              {
                allowedBlocks: [
                  'core/query',
                  'core/group',
                  'core/image',
                  'core/cover',
                  'core/post-template',
                  'core/post-title',
                  'core/post-featured-image',
                  'core/paragraph'
                ],
                renderAppender: InnerBlocks.ButtonBlockAppender
              }
            )
          )
        ),

        // Bottom controls
        ((arrows === 'bottom-right') || (arrows === 'bottom-left') || (arrows === 'bottom-center')) && el(
          'div',
          { className: 'kanahoma-carousel__controls kanahoma-carousel__controls--bottom', 'data-carousel-controls': true },
          el('button', {
            className: 'kanahoma-carousel__prev',
            type: 'button',
            'aria-label': __('Previous slide', 'kanahoma'),
            onClick: function (e) { e.preventDefault(); e.stopPropagation(); scrollByDir(-1); }
          }, '‹'),
          el('button', {
            className: 'kanahoma-carousel__next',
            type: 'button',
            'aria-label': __('Next slide', 'kanahoma'),
            onClick: function (e) { e.preventDefault(); e.stopPropagation(); scrollByDir(1); }
          }, '›')
        ),

        (arrows === 'bottom-center') && el(
          'style',
          null,
          '.kanahoma-carousel__controls--bottom{justify-content:center}'
        )
      )
    );
  }

  registerBlockType('kanahoma/post-carousel', {
    edit: Edit,
    // Persist inner blocks content for dynamic render
    save: function () { return wp.element.createElement(wp.blockEditor.InnerBlocks.Content, null); }
  });
})();
