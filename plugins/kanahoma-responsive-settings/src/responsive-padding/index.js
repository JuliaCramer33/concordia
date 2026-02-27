import { __ } from '@wordpress/i18n';
import { InspectorControls } from '@wordpress/block-editor';
import { PanelBody, TabPanel, SelectControl, Flex, FlexItem, UnitControl, TextControl } from '@wordpress/components';
import { addFilter } from '@wordpress/hooks';
import { createHigherOrderComponent } from '@wordpress/compose';
import { useEffect } from '@wordpress/element';
import styles from '../responsive-controls/styles.module.scss';

const BREAKPOINTS = ['mobile', 'tablet', 'desktop'];
const getEditorSettings = () => {
  try { return (wp?.data?.select?.('core/block-editor')?.getSettings?.()) || {}; } catch (e) { return {}; }
};
const getSpacingOptions = () => {
  const s = getEditorSettings();
  const sources = [
    s?.spacing?.spacingSizes,
    s?.spacing?.sizes,
    s?.spacingSizes,
    s?.__experimentalFeatures?.spacing?.spacingSizes,
    s?.__experimentalFeatures?.spacing?.sizes,
  ];

  // Pick the first non-empty source only to avoid duplicates
  let src = sources.find((v) => (Array.isArray(v) && v.length) || (v && typeof v === 'object' && Object.keys(v).length));
  let arr = [];
  if (Array.isArray(src)) {
    arr = src;
  } else if (src && typeof src === 'object') {
    arr = Object.values(src).flat();
  }

  if (!arr.length) {
    arr = [
      { slug: 'none', name: 'None', size: '0' },
      { slug: '10', name: '10', size: '0.625em' },
      { slug: '20', name: '20', size: '1.25em' },
      { slug: '30', name: '30', size: '1.875em' },
      { slug: '40', name: '40', size: '2.5em' },
      { slug: '50', name: '50', size: '3.125em' },
      { slug: '60', name: '60', size: '3.75em' },
      { slug: '70', name: '70', size: '4.375em' },
      { slug: '80', name: '80', size: '5em' },
      { slug: '90', name: '90', size: '5.625em' },
      { slug: '100', name: '100', size: '6.25em' },
    ];
  }

  // Deduplicate by slug
  const seen = new Set();
  const unique = [];
  for (const item of arr) {
    if (!item || !item.slug || seen.has(item.slug)) continue;
    seen.add(item.slug);
    unique.push(item);
  }

  // Prefer theme numeric presets (10,20,...) when available to avoid WP default labels like "X-Small"
  const numeric = unique.filter((item) => /^\d+$/.test(item.slug)).sort((a, b) => parseInt(a.slug) - parseInt(b.slug));
  const finalList = numeric.length ? numeric : unique;

  // Force labels to the slug to avoid WP default names like "2X-Small"
  return finalList.map((item) => ({ label: String(item.slug), value: `var(--wp--preset--spacing--${item.slug})` }));
};

// Allowed units for custom input; supports negative values.
const ALLOWED_UNITS = ['px', 'rem', 'em', '%', 'vw'];

// Normalize free-form input: allow CSS var(), or numeric with/without unit.
const normalizeValue = (v) => {
  if (!v && v !== 0) return '';
  const s = String(v).trim();
  if (!s) return '';
  if (/^var\(.*\)$/.test(s)) return s;
  // If only number (possibly negative/decimal), default to px
  if (/^-?\d+(?:\.\d+)?$/.test(s)) return `${s}px`;
  // If number with allowed unit
  if (/^-?\d+(?:\.\d+)?(px|rem|em|%|vw)$/i.test(s)) return s;
  return s; // fallback (UnitControl will still show it)
};

const DimensionField = ({ label, value, onChange, presets = [] }) => {
  const current = value || '';
  const handleUnitChange = (next) => {
    onChange(normalizeValue(next));
  };

  // Runtime component compatibility
  const UnitComp = UnitControl || (wp?.components?.UnitControl ?? wp?.components?.__experimentalUnitControl);

  // Select shows presets (primary), input on the right for custom value
  const selectedPreset = presets.find((p) => p.value === current) ? current : '';

  return (
    <>
      {label ? <div>{label}</div> : null}
      <Flex
        gap={3}
        align="flex-end"
        direction="row"
        wrap
        style={{ flexWrap: 'wrap', flexDirection: 'row', alignItems: 'flex-start', justifyContent: 'space-between', width: '100%' }}
      >
        <FlexItem style={{ flex: '0 0 35%' }}>
          <SelectControl
            value={selectedPreset}
            options={[{ label: '—', value: '' }, ...presets]}
            onChange={(v) => onChange(v)}
          />
        </FlexItem>
        <FlexItem style={{ flex: '0 0 60%' }}>
          {UnitComp ? (
            <UnitComp
              value={current}
              onChange={handleUnitChange}
              units={ALLOWED_UNITS.map((u) => ({ value: u, label: u }))}
              __unstableInputWidth="100%"
            />
          ) : (
            <TextControl
              value={current}
              onChange={handleUnitChange}
            />
          )}
        </FlexItem>
      </Flex>
    </>
  );
};

const withAttr = (settings) => ({
  ...settings,
  attributes: {
    ...settings.attributes,
    kanahomaResp: {
      type: 'object',
      default: { pad: {}, mar: {} },
    },
  },
});

const Edit = createHigherOrderComponent((BlockEdit) => (props) => {
  const { attributes, setAttributes } = props;
  const resp = attributes.kanahomaResp || {};
  const pad = resp.pad || {};

  // (Link/unlink removed per request)

  // Normalize any legacy attributes into the current shape once per block
  useEffect(() => {
    if (!attributes) return;
    if (!attributes.kanahomaResp) {
      const legacyPad = attributes.kanahomaResponsivePadding || attributes.kanahomaPad;
      const legacyMar = attributes.kanahomaResponsiveMargin || attributes.kanahomaMar;
      if (legacyPad || legacyMar) {
        try {
          const next = {
            pad: typeof legacyPad === 'object' && legacyPad ? legacyPad : {},
            mar: typeof legacyMar === 'object' && legacyMar ? legacyMar : {},
          };
          setAttributes({ kanahomaResp: next });
        } catch (e) { /* noop */ }
      }
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);

  const setPad = (bp, side, val) => {
    const next = { ...pad[bp] };
    next[side] = val;
    setAttributes({ kanahomaResp: { ...resp, pad: { ...pad, [bp]: next } } });
  };


  const selectsFor = (bp) => {
    const presets = getSpacingOptions();
    const renderSide = (side, labelText) => {
      const raw = pad?.[bp]?.[side] || '';
      return (
        <FlexItem key={`item-${bp}-${side}`}>
          <Flex direction="column">
            <div>{labelText}</div>
            <DimensionField
              label={null}
              value={raw || ''}
              presets={presets}
              onChange={(v) => setPad(bp, side, v)}
            />
          </Flex>
        </FlexItem>
      );
    };

    return (
      <>
        <Flex direction="column" gap={4}>
          {renderSide('top', __('Top', 'kanahoma-responsive-settings'))}
          {renderSide('right', __('Right', 'kanahoma-responsive-settings'))}
          {renderSide('bottom', __('Bottom', 'kanahoma-responsive-settings'))}
          {renderSide('left', __('Left', 'kanahoma-responsive-settings'))}
        </Flex>
        <p className={styles.help}>{__('You can type negative values (e.g., -24px). Unset sides inherit from the previous breakpoint.', 'kanahoma-responsive-settings')}</p>
      </>
    );
  };


  return (
    <>
      <BlockEdit {...props} />
      <InspectorControls>
        <PanelBody title={__('Responsive Padding', 'kanahoma-responsive-settings')} initialOpen={false}>
          <div className={styles.tabsTight}>
            <TabPanel tabs={[
              { name: 'mobile', title: __('Mobile', 'kanahoma-responsive-settings') },
              { name: 'tablet', title: __('Tablet', 'kanahoma-responsive-settings') },
              { name: 'desktop', title: __('Desktop', 'kanahoma-responsive-settings') },
            ]}>
              {(tab) => (<div key={`tab-${tab.name}`}>{selectsFor(tab.name)}</div>)}
            </TabPanel>
          </div>
        </PanelBody>
      </InspectorControls>
    </>
  );
}, 'kanahomaResponsivePadding');

export default () => {
  addFilter('blocks.registerBlockType', 'kanahoma/responsive-padding/attr', withAttr);
  addFilter('editor.BlockEdit', 'kanahoma/responsive-padding/ui', Edit);

  // Fallback: extend already-registered block types so the attribute serializes on save.
  try {
    const api = wp?.blocks;
    if (api?.getBlockTypes && api?.unregisterBlockType && api?.registerBlockType) {
      const extendIfMissing = (bt) => {
        try {
          if (!bt?.attributes || bt.attributes.kanahomaResp) return;
          const newSettings = {
            ...bt,
            attributes: {
              ...bt.attributes,
              kanahomaResp: {
                type: 'object',
                default: { pad: {}, mar: {} },
              },
            },
          };
          api.unregisterBlockType(bt.name);
          api.registerBlockType(bt.name, newSettings);
        } catch (e) { }
      };

      // run now and on domReady
      api.getBlockTypes().forEach(extendIfMissing);
      if (wp?.domReady) {
        wp.domReady(() => {
          try { api.getBlockTypes().forEach(extendIfMissing); } catch (e) { }
        });
      }
    }
  } catch (e) { }
};


