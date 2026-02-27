(function () {
  const { addFilter } = wp.hooks;
  const { createHigherOrderComponent } = wp.compose;
  const { InspectorControls } = wp.blockEditor || wp.editor;
  const { PanelBody, ToggleControl } = wp.components;
  const { __ } = wp.i18n;

  const withAttr = (settings) => {
    if (settings.name !== 'core/columns') return settings;
    return {
      ...settings,
      attributes: {
        ...settings.attributes,
        kanahomaEqualHeight: { type: 'boolean', default: false },
      },
    };
  };

  const withUI = createHigherOrderComponent((BlockEdit) => {
    return function (props) {
      if (props.name !== 'core/columns') return wp.element.createElement(BlockEdit, props);
      const value = !!(props.attributes && props.attributes.kanahomaEqualHeight);
      const onChange = (v) => props.setAttributes({ kanahomaEqualHeight: !!v });

      return wp.element.createElement(
        wp.element.Fragment,
        null,
        wp.element.createElement(BlockEdit, props),
        wp.element.createElement(
          InspectorControls,
          null,
          wp.element.createElement(
            PanelBody,
            { title: __('Layout utilities', 'kanahoma-block-utilities'), initialOpen: false },
            wp.element.createElement(ToggleControl, {
              label: __('Equal height columns', 'kanahoma-block-utilities'),
              checked: value,
              onChange,
            })
          )
        )
      );
    };
  }, 'kanahomaEqualHeightUI');

  // Editor-only: inject class so the preview matches when toggled.
  const withClass = (BlockListBlock) => (props) => {
    if (props.block && props.block.name === 'core/columns' && props.block.attributes && props.block.attributes.kanahomaEqualHeight) {
      const cls = (props.className || '') + ' has-equal-height';
      return wp.element.createElement(BlockListBlock, { ...props, className: cls });
    }
    return wp.element.createElement(BlockListBlock, props);
  };

  addFilter('blocks.registerBlockType', 'kanahoma/utils/columns-attr', withAttr);
  addFilter('editor.BlockEdit', 'kanahoma/utils/columns-ui', withUI);
  addFilter('editor.BlockListBlock', 'kanahoma/utils/columns-class', withClass);
})();

// Card grid styles and auto-utilities (user-friendly)
(function () {
  const { registerBlockStyle } = wp.blocks || {};
  const { addFilter } = wp.hooks;
  const { __ } = wp.i18n;

  // Register block styles
  try {
    if (registerBlockStyle) {
      registerBlockStyle('core/post-template', {
        name: 'card-grid',
        label: __('Card grid', 'kanahoma-block-utilities'),
      });
      registerBlockStyle('core/group', {
        name: 'card',
        label: __('Card', 'kanahoma-block-utilities'),
      });
    }
  } catch (e) {}

  // When styles are used, add helper classes so frontend JS/CSS can enhance
  const saveClassesForStyles = (extraProps, blockType, attributes) => {
    try {
      if (!extraProps) extraProps = {};
      const classes = (extraProps.className || '').split(' ').filter(Boolean);
      const hasStyleCardGrid = classes.includes('is-style-card-grid');
      const hasStyleCard = classes.includes('is-style-card');
      if (blockType.name === 'core/post-template' && hasStyleCardGrid) {
        if (!classes.includes('u-equalize')) classes.push('u-equalize');
      }
      if (blockType.name === 'core/group' && hasStyleCard) {
        if (!classes.includes('u-card')) classes.push('u-card');
      }
      if (classes.length) {
        extraProps.className = classes.join(' ');
      }
      return extraProps;
    } catch (e) {
      return extraProps || {};
    }
  };
  addFilter('blocks.getSaveContent.extraProps', 'kanahoma/utils/save-classes-styles', saveClassesForStyles);
})();


