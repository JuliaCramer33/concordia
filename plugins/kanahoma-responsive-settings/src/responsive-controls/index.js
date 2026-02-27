import { __ } from '@wordpress/i18n';
import { InspectorControls } from '@wordpress/block-editor';
import { Panel, PanelBody, ButtonGroup, Button } from '@wordpress/components';
import { createHigherOrderComponent } from '@wordpress/compose';
import { addFilter } from '@wordpress/hooks';
import styles from './styles.module.scss';

const VIEWPORT_SIZES = {
  mobile: __('Mobile', 'kanahoma-responsive-settings'),
  tablet: __('Tablet', 'kanahoma-responsive-settings'),
  desktop: __('Desktop', 'kanahoma-responsive-settings'),
  wide: __('Wide Screen', 'kanahoma-responsive-settings'),
};

const Edit = createHigherOrderComponent(
  (BlockEdit) => (props) => {
    const { attributes, setAttributes } = props;
    const { kanahomaResponsiveControls } = attributes;
    const controls = kanahomaResponsiveControls || {};

    return (
      <>
        <BlockEdit {...props} />
        <InspectorControls>
          <Panel>
            <PanelBody
              title={__('Responsive Visibility', 'kanahoma-responsive-settings')}
              initialOpen={false}
            >
              <ButtonGroup>
                {Object.keys(VIEWPORT_SIZES).map(
                  (size) => (
                    <Button
                      key={size}
                      isSmall
                      variant={controls?.[size] === 'hide' ? undefined : 'primary'}
                      onClick={() => {
                        const next = controls?.[size] === 'hide' ? 'show' : 'hide';
                        setAttributes({ kanahomaResponsiveControls: { ...controls, [size]: next } });
                      }}
                    >
                      {VIEWPORT_SIZES[size]}
                    </Button>
                  )
                )}
              </ButtonGroup>
              <p className={styles.help}>{__('Show or hide this block at certain sizes.', 'kanahoma-responsive-settings')}</p>
            </PanelBody>
          </Panel>
        </InspectorControls>
      </>
    );
  },
  'kanahomaResponsiveControls'
);

export default () => {
  addFilter(
    'blocks.registerBlockType',
    'kanahoma/responsive-settings-controls',
    (settings) => {
      return {
        ...settings,
        attributes: {
          ...settings.attributes,
          kanahomaResponsiveControls: { type: 'object' },
        },
      };
    }
  );

  addFilter('editor.BlockEdit', 'kanahoma/responsive-settings', Edit);

  // Fallback: extend already-registered block types so the attribute serializes on save.
  try {
    const api = wp?.blocks;
    if (api?.getBlockTypes && api?.unregisterBlockType && api?.registerBlockType) {
      const extendIfMissing = (bt) => {
        try {
          if (!bt?.attributes || bt.attributes.kanahomaResponsiveControls) return;
          const newSettings = {
            ...bt,
            attributes: {
              ...bt.attributes,
              kanahomaResponsiveControls: { type: 'object' },
            },
          };
          api.unregisterBlockType(bt.name);
          api.registerBlockType(bt.name, newSettings);
        } catch (e) {}
      };

      // run now and on domReady
      api.getBlockTypes().forEach(extendIfMissing);
      if (wp?.domReady) {
        wp.domReady(() => {
          try { api.getBlockTypes().forEach(extendIfMissing); } catch (e) {}
        });
      }
    }
  } catch (e) {}
};
