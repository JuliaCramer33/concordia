import { __ } from '@wordpress/i18n';
import { InspectorControls } from '@wordpress/block-editor';
import { PanelBody, ToggleControl } from '@wordpress/components';
import { addFilter } from '@wordpress/hooks';
import { createHigherOrderComponent } from '@wordpress/compose';

// Stacking breakpoint: pick the smallest breakpoint at which Columns should switch to column flow.
// We tie this to our known breakpoint names, and resolve to CSS via vars.

const withAttr = (settings) => {
  if (settings.name !== 'core/columns') return settings;
  return {
    ...settings,
    attributes: {
      ...settings.attributes,
      kanahomaStackAt: { type: 'string', default: '' }, // '', 'mobile', 'tablet', 'desktop', 'wide'
    },
  };
};

const Edit = createHigherOrderComponent((BlockEdit) => (props) => {
  if (props.name !== 'core/columns') return <BlockEdit {...props} />;
  const { attributes, setAttributes } = props;
  const val = attributes.kanahomaStackAt || '';

  const setPoint = (bp) => (on) => {
    setAttributes({ kanahomaStackAt: on ? bp : '' });
  };

  return (
    <>
      <BlockEdit {...props} />
      <InspectorControls>
        <PanelBody title={__('Stack at breakpoint', 'kanahoma-responsive-settings')} initialOpen={false}>
          <p style={{ marginTop: 0 }}>
            {__('Choose the first breakpoint to stack (column). Smaller sizes inherit. Explicit Responsive Flow overrides.', 'kanahoma-responsive-settings')}
          </p>
          <ToggleControl
            label={__('Mobile', 'kanahoma-responsive-settings')}
            checked={val === 'mobile'}
            onChange={setPoint('mobile')}
          />
          <ToggleControl
            label={__('Tablet', 'kanahoma-responsive-settings')}
            checked={val === 'tablet'}
            onChange={setPoint('tablet')}
          />
          <ToggleControl
            label={__('Desktop', 'kanahoma-responsive-settings')}
            checked={val === 'desktop'}
            onChange={setPoint('desktop')}
          />
          <ToggleControl
            label={__('Wide', 'kanahoma-responsive-settings')}
            checked={val === 'wide'}
            onChange={setPoint('wide')}
          />
        </PanelBody>
      </InspectorControls>
    </>
  );
}, 'kanahomaResponsiveStack');

export default () => {
  addFilter('blocks.registerBlockType', 'kanahoma/responsive-stack/attr', withAttr);
  addFilter('editor.BlockEdit', 'kanahoma/responsive-stack/ui', Edit);

  // Ensure attribute exists even if core registered earlier
  try {
    const api = wp?.blocks;
    if (api?.getBlockTypes && api?.unregisterBlockType && api?.registerBlockType) {
      const extendColumns = (bt) => {
        try {
          if (bt?.name !== 'core/columns') return;
          if (bt?.attributes && bt.attributes.kanahomaStackAt) return;
          const newSettings = {
            ...bt,
            attributes: { ...bt.attributes, kanahomaStackAt: { type: 'string', default: '' } },
          };
          api.unregisterBlockType(bt.name);
          api.registerBlockType(bt.name, newSettings);
        } catch (e) { }
      };
      api.getBlockTypes().forEach(extendColumns);
      if (wp?.domReady) {
        wp.domReady(() => { try { api.getBlockTypes().forEach(extendColumns); } catch (e) { } });
      }
    }
  } catch (e) { }
};


