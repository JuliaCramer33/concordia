import { __ } from '@wordpress/i18n';
import { InspectorControls } from '@wordpress/block-editor';
import { PanelBody, SelectControl, Flex, FlexItem } from '@wordpress/components';
import { addFilter } from '@wordpress/hooks';
import { createHigherOrderComponent } from '@wordpress/compose';

const BREAKPOINTS = ['mobile', 'tablet', 'desktop', 'wide'];

const withAttr = (settings) => {
  if (settings.name !== 'core/columns') return settings;
  return {
    ...settings,
    attributes: {
      ...settings.attributes,
      kanahomaRespFlow: {
        type: 'object',
        default: {},
      },
    },
  };
};

const Edit = createHigherOrderComponent((BlockEdit) => (props) => {
  if (props.name !== 'core/columns') {
    return <BlockEdit {...props} />;
  }

  const { attributes, setAttributes } = props;
  const flow = attributes.kanahomaRespFlow || {};

  const optionsFor = (bp) => {
    if (bp === 'mobile' || bp === 'tablet') {
      return [
        { label: '—', value: '' },
        { label: __('Column', 'kanahoma-responsive-settings'), value: 'column' },
        { label: __('Column Reverse', 'kanahoma-responsive-settings'), value: 'column-reverse' },
        { label: __('Row', 'kanahoma-responsive-settings'), value: 'row' },
        { label: __('Row Reverse', 'kanahoma-responsive-settings'), value: 'row-reverse' },
      ];
    }
    return [
      { label: '—', value: '' },
      { label: __('Row', 'kanahoma-responsive-settings'), value: 'row' },
      { label: __('Row Reverse', 'kanahoma-responsive-settings'), value: 'row-reverse' },
    ];
  };

  const onChangeFlow = (bp, val) => {
    setAttributes({ kanahomaRespFlow: { ...flow, [bp]: val } });
  };

  return (
    <>
      <BlockEdit {...props} />
      <InspectorControls>
        <PanelBody title={__('Responsive Flow', 'kanahoma-responsive-settings')} initialOpen={false}>
          <Flex gap={4} align="flex-start" style={{ flexWrap: 'wrap' }}>
            {BREAKPOINTS.map((bp) => (
              <FlexItem style={{ flex: '1 1 180px', minWidth: 180 }} key={`flow-${bp}`}>
                <SelectControl
                  label={
                    bp === 'mobile'
                      ? __('Mobile', 'kanahoma-responsive-settings')
                      : bp === 'tablet'
                        ? __('Tablet', 'kanahoma-responsive-settings')
                        : bp === 'desktop'
                          ? __('Desktop', 'kanahoma-responsive-settings')
                          : __('Wide', 'kanahoma-responsive-settings')
                  }
                  value={flow[bp] || ''}
                  options={optionsFor(bp)}
                  onChange={(v) => onChangeFlow(bp, v)}
                />
              </FlexItem>
            ))}
          </Flex>
        </PanelBody>
      </InspectorControls>
    </>
  );
}, 'kanahomaResponsiveFlow');

export default () => {
  addFilter('blocks.registerBlockType', 'kanahoma/responsive-flow/attr', withAttr);
  addFilter('editor.BlockEdit', 'kanahoma/responsive-flow/ui', Edit);

  // Fallback: extend already-registered core/columns so the attribute serializes on save.
  try {
    const api = wp?.blocks;
    if (api?.getBlockTypes && api?.unregisterBlockType && api?.registerBlockType) {
      const extendColumns = (bt) => {
        try {
          if (bt?.name !== 'core/columns') return;
          if (bt?.attributes && bt.attributes.kanahomaRespFlow) return;
          const newSettings = {
            ...bt,
            attributes: {
              ...bt.attributes,
              kanahomaRespFlow: { type: 'object', default: {} },
            },
          };
          api.unregisterBlockType(bt.name);
          api.registerBlockType(bt.name, newSettings);
        } catch (e) { }
      };

      api.getBlockTypes().forEach(extendColumns);
      if (wp?.domReady) {
        wp.domReady(() => {
          try { api.getBlockTypes().forEach(extendColumns); } catch (e) { }
        });
      }
    }
  } catch (e) { }
};


