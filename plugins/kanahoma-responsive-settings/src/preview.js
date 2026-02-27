const { addFilter } = wp.hooks;
const { createElement } = wp.element;

// Enhance editor preview by adding our classes and CSS vars to block wrappers
addFilter('editor.BlockListBlock', 'kanahoma/responsive/preview', (BlockListBlock) => (props) => {
  try {
    const block = props.block || {};
    const a = block.attributes || {};
    const resp = a.kanahomaResp || {};
    const pad = resp.pad || {};
    const mar = resp.mar || {};

    const vars = {};
    const setSides = (prefix, bp, obj) => {
      if (!obj[bp]) return;
      const o = obj[bp];
      const val = (k) => (typeof o[k] === 'string' && o[k]) || '';
      const t = val('top'); if (t) vars[`--kanahoma-${prefix}-top-${bp}`] = t;
      const r = val('right'); if (r) vars[`--kanahoma-${prefix}-right-${bp}`] = r;
      const b = val('bottom'); if (b) vars[`--kanahoma-${prefix}-bottom-${bp}`] = b;
      const l = val('left'); if (l) vars[`--kanahoma-${prefix}-left-${bp}`] = l;
    };

    const bps = (window.kanahomaResponsiveSettings && window.kanahomaResponsiveSettings.breakpoints)
      || { mobile: 0, tablet: 768, desktop: 1024 };
    const order = Object.keys(bps);
    order.forEach((bp) => { setSides('pad', bp, pad); setSides('mar', bp, mar); });

    const hasAny = (obj, bp) => !!(obj[bp] && (obj[bp].top || obj[bp].right || obj[bp].bottom || obj[bp].left));

    const extra = [
      'kanahoma-resp',
      hasAny(pad,'mobile')  && 'kanahoma-has-pad-mobile',
      hasAny(pad,'tablet')  && 'kanahoma-has-pad-tablet',
      hasAny(pad,'desktop') && 'kanahoma-has-pad-desktop',
      hasAny(mar,'mobile')  && 'kanahoma-has-mar-mobile',
      hasAny(mar,'tablet')  && 'kanahoma-has-mar-tablet',
      hasAny(mar,'desktop') && 'kanahoma-has-mar-desktop',
    ].filter(Boolean).join(' ');

    const className = ((props.className || '') + ' ' + extra).trim();
    const wrapperProps = { ...(props.wrapperProps || {}), style: { ...(props.wrapperProps?.style || {}), ...vars } };
    return createElement(BlockListBlock, { ...props, className, wrapperProps });
  } catch (e) {
    return createElement(BlockListBlock, props);
  }
});


