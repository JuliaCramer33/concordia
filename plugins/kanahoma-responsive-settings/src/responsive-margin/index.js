import { __ } from '@wordpress/i18n';
import { InspectorControls } from '@wordpress/block-editor';
import { PanelBody, TabPanel, SelectControl, Flex, FlexItem, UnitControl, TextControl } from '@wordpress/components';
import { addFilter } from '@wordpress/hooks';
import { createHigherOrderComponent } from '@wordpress/compose';
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
  let src = sources.find((v) => (Array.isArray(v) && v.length) || (v && typeof v === 'object' && Object.keys(v).length));
  let arr = [];
  if (Array.isArray(src)) arr = src; else if (src && typeof src === 'object') arr = Object.values(src).flat();
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
  const seen = new Set();
  const unique = [];
  for (const item of arr) { if (!item || !item.slug || seen.has(item.slug)) continue; seen.add(item.slug); unique.push(item); }
  const numeric = unique.filter((item) => /^\d+$/.test(item.slug)).sort((a, b) => parseInt(a.slug) - parseInt(b.slug));
  const finalList = numeric.length ? numeric : unique;
  return finalList.map((item) => ({ label: String(item.slug), value: `var(--wp--preset--spacing--${item.slug})` }));
};

const ALLOWED_UNITS = ['px', 'rem', 'em', '%', 'vw'];
const normalizeValue = (v) => {
  if (!v && v !== 0) return '';
  const s = String(v).trim();
  if (!s) return '';
  if (/^var\(.*\)$/.test(s)) return s;
  if (/^-?\d+(?:\.\d+)?$/.test(s)) return `${s}px`;
  if (/^-?\d+(?:\.\d+)?(px|rem|em|%|vw)$/i.test(s)) return s;
  return s;
};

const DimensionField = ({ value, onChange, presets = [] }) => {
  const current = value || '';
  const UnitComp = UnitControl || (wp?.components?.UnitControl ?? wp?.components?.__experimentalUnitControl);
  const selectedPreset = presets.find((p) => p.value === current) ? current : '';
  return (
    <Flex gap={3} align="flex-end" direction="row" wrap style={{ flexWrap: 'wrap', flexDirection: 'row', alignItems: 'flex-start', justifyContent: 'space-between', width: '100%' }}>
      <FlexItem style={{ flex: '0 0 35%' }}>
        <SelectControl value={selectedPreset} options={[{ label: '—', value: '' }, ...presets]} onChange={(v) => onChange(v)} />
      </FlexItem>
      <FlexItem style={{ flex: '0 0 60%' }}>
        {UnitComp ? (
          <UnitComp value={current} onChange={(v) => onChange(normalizeValue(v))} units={ALLOWED_UNITS.map((u) => ({ value: u, label: u }))} __unstableInputWidth="100%" />
        ) : (
          <TextControl value={current} onChange={(v) => onChange(normalizeValue(v))} />
        )}
      </FlexItem>
    </Flex>
  );
};

const withAttr = (settings) => ({
  ...settings,
  attributes: {
    ...settings.attributes,
    kanahomaResp: { type: 'object', default: { pad: {}, mar: {} } },
  },
});

const Edit = createHigherOrderComponent((BlockEdit) => (props) => {
  const { attributes, setAttributes } = props;
  const resp = attributes.kanahomaResp || {};
  const mar = resp.mar || {};

  const setMar = (bp, side, val) => {
    const next = { ...mar[bp] };
    next[side] = val;
    setAttributes({ kanahomaResp: { ...resp, mar: { ...mar, [bp]: next } } });
  };

  const panelFor = (bp) => {
    const presets = getSpacingOptions();
    const renderSide = (side, labelText) => {
      const raw = mar?.[bp]?.[side] || '';
      return (
        <FlexItem key={`item-mar-${bp}-${side}`}>
          <Flex direction="column">
            <div>{labelText}</div>
            <DimensionField value={raw || ''} presets={presets} onChange={(v) => setMar(bp, side, v)} />
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
        <PanelBody title={__('Responsive Margin', 'kanahoma-responsive-settings')} initialOpen={false}>
          <div className={styles.tabsTight}>
            <TabPanel tabs={BREAKPOINTS.map((bp) => ({ name: bp, title: __(bp.charAt(0).toUpperCase() + bp.slice(1), 'kanahoma-responsive-settings') }))}>
              {(tab) => (<div key={`tab-mar-${tab.name}`}>{panelFor(tab.name)}</div>)}
            </TabPanel>
          </div>
        </PanelBody>
      </InspectorControls>
    </>
  );
}, 'kanahomaResponsiveMargin');

export default () => {
  addFilter('blocks.registerBlockType', 'kanahoma/responsive-margin/attr', withAttr);
  addFilter('editor.BlockEdit', 'kanahoma/responsive-margin/ui', Edit);

  // Ensure attribute exists even if core registered earlier
  try {
    const api = wp?.blocks;
    if (api?.getBlockTypes && api?.unregisterBlockType && api?.registerBlockType) {
      const extendIfMissing = (bt) => {
        try {
          if (!bt?.attributes || bt.attributes.kanahomaResp) return;
          const newSettings = { ...bt, attributes: { ...bt.attributes, kanahomaResp: { type: 'object', default: { pad: {}, mar: {} } } } };
          api.unregisterBlockType(bt.name);
          api.registerBlockType(bt.name, newSettings);
        } catch (e) { }
      };
      api.getBlockTypes().forEach(extendIfMissing);
      if (wp?.domReady) { wp.domReady(() => { try { api.getBlockTypes().forEach(extendIfMissing); } catch (e) { } }); }
    }
  } catch (e) { }
};


