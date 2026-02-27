import classnames from 'classnames';
import { __ } from '@wordpress/i18n';
import { InspectorControls } from '@wordpress/block-editor';
import {
  Panel,
  PanelBody,
  __experimentalToggleGroupControl as ToggleGroupControl, // eslint-disable-line @wordpress/no-unsafe-wp-apis
  __experimentalToggleGroupControlOption as ToggleGroupControlOption, // eslint-disable-line @wordpress/no-unsafe-wp-apis
  __experimentalToggleGroupControlOptionIcon as ToggleGroupControlOptionIcon, // eslint-disable-line @wordpress/no-unsafe-wp-apis
} from '@wordpress/components';
import { createHigherOrderComponent } from '@wordpress/compose';
import { addFilter, applyFilters } from '@wordpress/hooks';

const Edit = createHigherOrderComponent(
  (BlockEdit) => (props) => {
    if (props.name !== 'core/button') {
      return <BlockEdit {...props} />;
    }

    let arrowTypes = [
      {
        label: __('Regular', 'kanahoma-responsive-settings'),
        value: 'regular',
      },
      {
        label: __('Styled', 'kanahoma-responsive-settings'),
        value: 'styled',
      },
    ];

    arrowTypes = applyFilters(
      'kanahoma.responsiveSettings.buttonOptions.arrowTypes',
      arrowTypes
    );

    const hasIcons = arrowTypes.some(({ icon }) => icon);

    const { attributes, setAttributes } = props;
    const { kanahomaArrowType } = attributes;

    return (
      <>
        <BlockEdit {...props} />
        <InspectorControls group="styles">
          <Panel>
            <PanelBody
              title={__('Styles', 'kanahoma-responsive-settings')}
              initialOpen={false}
            >
              <ToggleGroupControl
                label={__(
                  'Arrow Type',
                  'kanahoma-responsive-settings'
                )}
                isDeselectable
                value={kanahomaArrowType || null}
                onChange={(value) => {
                  setAttributes({
                    kanahomaArrowType: value,
                  });
                }}
                {...(!hasIcons && { isBlock: true })}
              >
                {arrowTypes.map(
                  ({ label, value, icon }) => {
                    const TagName = icon
                      ? ToggleGroupControlOptionIcon
                      : ToggleGroupControlOption;

                    return (
                      <TagName
                        key={value}
                        label={label}
                        value={value}
                        {...(icon && { icon })}
                      />
                    );
                  }
                )}
              </ToggleGroupControl>
            </PanelBody>
          </Panel>
        </InspectorControls>
      </>
    );
  },
  'kanahomaResponsiveButtonOptions'
);

const addButtonOptionsClasses = (props, type, attributes) => {
  if (type.name !== 'core/button') {
    return props;
  }

  const { kanahomaArrowType } = attributes;

  if (!kanahomaArrowType) {
    return props;
  }

  props.className = classnames(
    props.className,
    `kanahoma-button--has-${kanahomaArrowType}-arrow`
  );

  return props;
};

export default () => {
  addFilter(
    'blocks.registerBlockType',
    'kanahoma/responsive-settings-button-options',
    (settings) => {
      if (settings.name !== 'core/button') {
        return settings;
      }

      return {
        ...settings,
        attributes: {
          ...settings.attributes,
          kanahomaArrowType: { type: 'string' },
        },
      };
    }
  );

  addFilter('editor.BlockEdit', 'kanahoma/responsive-settings', Edit);

  addFilter(
    'editor.BlockListBlock',
    'kanahoma/responsive-settings-button-options-save',
    function (BlockListBlock) {
      return function (props) {
        const { block } = props;
        if (block.name !== 'core/button') {
          return <BlockListBlock {...props} />;
        }

        const { kanahomaArrowType } = block.attributes;

        if (!kanahomaArrowType) {
          return <BlockListBlock {...props} />;
        }

        return (
          <BlockListBlock
            {...props}
            className={classnames(
              `kanahoma-button--has-${kanahomaArrowType}-arrow`
            )}
          />
        );
      };
    }
  );
  addFilter(
    'blocks.getSaveContent.extraProps',
    'kanahoma/responsive-settings-button-options-save',
    addButtonOptionsClasses
  );
}
