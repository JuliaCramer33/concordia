// Entry: gutenberg-editor
// Add a toggle to core/button to control an arrow decoration via class "has-arrow".

(function (wp) {
  const { addFilter } = wp.hooks;
  const { Fragment, createElement: el } = wp.element;
  const { createHigherOrderComponent } = wp.compose;
  const { InspectorControls } = wp.blockEditor || wp.editor;
  const { PanelBody, ToggleControl } = wp.components;

  const TARGET_BLOCK = 'core/button';
  const ATTR_NAME = 'concordiaHasArrow';

  // 1) Extend attributes
  addFilter(
    'blocks.registerBlockType',
    'concordia/button-arrow/attributes',
    (settings, name) => {
      if (name !== TARGET_BLOCK) {
        return settings;
      }
      settings.attributes = Object.assign({}, settings.attributes, {
        [ATTR_NAME]: { type: 'boolean', default: false },
      });
      return settings;
    }
  );

  // 2) Add inspector toggle
  const withArrowToggle = createHigherOrderComponent((BlockEdit) => {
    return (props) => {
      if (props.name !== TARGET_BLOCK) {
        return el(BlockEdit, props);
      }
      const value = !!props.attributes[ATTR_NAME];
      return el(
        Fragment,
        {},
        el(BlockEdit, props),
        el(
          InspectorControls,
          {},
          el(
            PanelBody,
            { title: 'Button', initialOpen: true },
            el(ToggleControl, {
              label: 'Show arrow',
              checked: value,
              onChange: (next) => props.setAttributes({ [ATTR_NAME]: !!next }),
            })
          )
        )
      );
    };
  }, 'withArrowToggle');

  addFilter(
    'editor.BlockEdit',
    'concordia/button-arrow/inspector',
    withArrowToggle
  );

  // 2b) Add class on the editor wrapper for live preview
  const withArrowWrapperClass = createHigherOrderComponent((BlockListBlock) => {
    return (props) => {
      if (props.name !== TARGET_BLOCK) {
        return wp.element.createElement(BlockListBlock, props);
      }
      const value = !!(props.attributes && props.attributes[ATTR_NAME]);
      if (value) {
        const prev = props.className || '';
        props = { ...props, className: (prev + ' has-arrow').trim() };
      }
      return wp.element.createElement(BlockListBlock, props);
    };
  }, 'withArrowWrapperClass');

  addFilter(
    'editor.BlockListBlock',
    'concordia/button-arrow/wrapper',
    withArrowWrapperClass
  );

  // 3) On save, add class when enabled
  addFilter(
    'blocks.getSaveContent.extraProps',
    'concordia/button-arrow/save-class',
    (extraProps, blockType, attributes) => {
      if (blockType.name !== TARGET_BLOCK) {
        return extraProps;
      }
      if (attributes && attributes[ATTR_NAME]) {
        const prev = extraProps.className || '';
        extraProps.className = (prev + ' has-arrow').trim();
      }
      return extraProps;
    }
  );
})(window.wp || {});

// Ensure custom server-rendered blocks are registered in the editor (defensive)

/* Inline: Block Bindings UI + Preview (moved from inc/ and separate file into the editor bundle) */
(function () {
  if (!window.wp || !wp.data || !wp.plugins || !wp.components) {
    return;
  }
  const { PanelBody, SelectControl, TextControl, ComboboxControl } =
    wp.components;
  const { Button } = wp.components;

  const SUPPORTED_ATTRS = {
    'core/paragraph': ['content'],
    'core/heading': ['content'],
    'core/button': ['content', 'url'],
    'core/navigation-link': ['url'],
  };

  // Lightweight InspectorControls launcher so editors see an entry in the block settings
  (function addInspectorPanel() {
    if (!wp.hooks || !wp.compose) {
      return;
    }
    const { addFilter } = wp.hooks;
    const { createHigherOrderComponent } = wp.compose;
    const { InspectorControls } = wp.blockEditor || wp.editor || {};
    // Reuse top-level components destructured earlier to avoid shadowing.
    if (!InspectorControls || !PanelBody || !SelectControl || !TextControl) {
      return;
    }

    const withBindingsInspector = createHigherOrderComponent((BlockEdit) => {
      return (props) => {
        const supported = SUPPORTED_ATTRS[props.name] || [];
        if (supported.length === 0) {
          return wp.element.createElement(BlockEdit, props);
        }
        const { attributes, setAttributes } = props;
        const currentMeta = attributes?.metadata
          ? { ...attributes.metadata }
          : {};
        const bindings = currentMeta.bindings
          ? { ...currentMeta.bindings }
          : {};

        const [attribute, setAttribute] = wp.element.useState(
          supported[0] || ''
        );
        // Source selector: 'department' | 'events'
        const [source, setSource] = wp.element.useState('department');
        // Department state
        const [deptField, setDeptField] = wp.element.useState('phone');
        const [term, setTerm] = wp.element.useState('');
        const [termId, setTermId] = wp.element.useState('');
        const [termQuery, setTermQuery] = wp.element.useState('');
        // Events state
        const [eventField, setEventField] = wp.element.useState('date_range');
        const [eventPostId, setEventPostId] = wp.element.useState('');

        const departments = wp.data.useSelect(
          (select) => {
            const core = select('core');
            return core.getEntityRecords('taxonomy', 'department', {
              per_page: 50,
              search: termQuery || undefined,
              hide_empty: false,
            });
          },
          [termQuery]
        );

        function loadBindingFromProps(attrName) {
          try {
            const b = bindings?.[attrName];
            if (b && b.source === 'concordia/department-meta') {
              setSource('department');
              const args = b.args || {};
              setDeptField(args.field ? String(args.field) : 'phone');
              setTerm(args.term ? String(args.term) : '');
              setTermId(args.term_id ? String(args.term_id) : '');
            } else if (b && b.source === 'concordia/event-meta') {
              setSource('events');
              const args = b.args || {};
              setEventField(args.field ? String(args.field) : 'date_range');
              setEventPostId(
                args.post_id && Number.isFinite(Number(args.post_id))
                  ? String(args.post_id)
                  : ''
              );
            }
          } catch (e) {}
        }

        wp.element.useEffect(() => {
          const existingAttr = supported.find(
            (a) =>
              bindings?.[a]?.source === 'concordia/department-meta' ||
              bindings?.[a]?.source === 'concordia/event-meta'
          );
          const nextAttr = existingAttr || supported[0] || '';
          setAttribute(nextAttr);
          if (nextAttr) {
            loadBindingFromProps(nextAttr);
          }
          // eslint-disable-next-line react-hooks/exhaustive-deps
        }, [props.clientId, props.name]);

        wp.element.useEffect(() => {
          if (attribute) {
            loadBindingFromProps(attribute);
          }
          // eslint-disable-next-line react-hooks/exhaustive-deps
        }, [attribute]);

        function applyInline() {
          if (!attribute) {
            return;
          }
          let args, srcName;
          if (source === 'department') {
            args = (() => {
              const out = { field: deptField };
              if (term) {
                out.term = term;
              }
              if (termId && Number.isFinite(Number(termId))) {
                out.term_id = Number(termId);
              }
              return out;
            })();
            srcName = 'concordia/department-meta';
          } else {
            // events
            args = { field: eventField };
            if (eventPostId && Number.isFinite(Number(eventPostId))) {
              args.post_id = Number(eventPostId);
            }
            srcName = 'concordia/event-meta';
          }
          const newMeta = attributes?.metadata
            ? { ...attributes.metadata }
            : {};
          const newBindings = newMeta.bindings ? { ...newMeta.bindings } : {};
          newBindings[attribute] = {
            source: srcName,
            args,
          };
          newMeta.bindings = newBindings;
          setAttributes({ metadata: newMeta });
        }

        function removeInline() {
          if (!attribute) {
            return;
          }
          const newMeta = attributes?.metadata
            ? { ...attributes.metadata }
            : {};
          const newBindings = newMeta.bindings ? { ...newMeta.bindings } : {};

          // Always remove the selected attribute
          if (newBindings[attribute]) {
            delete newBindings[attribute];
          }
          // For core/button, content and text are interchangeable across versions.
          if (props.name === 'core/button') {
            if (attribute === 'content' && newBindings.text) {
              delete newBindings.text;
            }
            if (attribute === 'text' && newBindings.content) {
              delete newBindings.content;
            }
          }

          newMeta.bindings = newBindings;
          setAttributes({ metadata: newMeta });
        }

        function removeAllInline() {
          const newMeta = attributes?.metadata
            ? { ...attributes.metadata }
            : {};
          const newBindings = newMeta.bindings ? { ...newMeta.bindings } : {};
          delete newBindings.content;
          delete newBindings.text;
          delete newBindings.url;
          newMeta.bindings = newBindings;
          setAttributes({ metadata: newMeta });
        }

        return wp.element.createElement(
          wp.element.Fragment,
          null,
          wp.element.createElement(BlockEdit, props),
          wp.element.createElement(
            InspectorControls,
            null,
            wp.element.createElement(
              PanelBody,
              { title: 'Bindings', initialOpen: false },
              wp.element.createElement(SelectControl, {
                label: 'Source',
                value: source,
                options: [
                  { label: 'Department', value: 'department' },
                  { label: 'Events', value: 'events' },
                ],
                onChange: setSource,
              }),
              wp.element.createElement(SelectControl, {
                label: 'Attribute',
                value: attribute,
                options: supported.map((a) => ({ label: a, value: a })),
                onChange: setAttribute,
              }),
              source === 'department' &&
                wp.element.createElement(
                  wp.element.Fragment,
                  null,
                  wp.element.createElement(SelectControl, {
                    label: 'Field',
                    value: deptField,
                    options: [
                      { label: 'Phone', value: 'phone' },
                      { label: 'Email', value: 'email' },
                      { label: 'Email (mailto link)', value: 'email_mailto' },
                      { label: 'Hours', value: 'hours' },
                      { label: 'Name', value: 'name' },
                      { label: 'Address', value: 'address' },
                      { label: 'Page Link (URL)', value: 'page_link' },
                    ],
                    onChange: setDeptField,
                  }),
                  wp.element.createElement(ComboboxControl, {
                    label: 'Department (search)',
                    value: termId,
                    onChange: (v) => {
                      setTermId(String(v || ''));
                      setTerm('');
                    },
                    onFilterValueChange: setTermQuery,
                    options: Array.isArray(departments)
                      ? departments.map((t) => ({
                          label: `${t.name} (${t.id})`,
                          value: String(t.id),
                        }))
                      : [],
                  }),
                  wp.element.createElement(TextControl, {
                    label: 'Department slug (optional)',
                    value: term,
                    onChange: setTerm,
                    placeholder: 'e.g. financial-aid',
                  }),
                  wp.element.createElement(TextControl, {
                    label: 'Department ID (optional)',
                    value: termId,
                    onChange: setTermId,
                    placeholder: 'e.g. 123',
                    type: 'number',
                  })
                ),
              source === 'events' &&
                wp.element.createElement(
                  wp.element.Fragment,
                  null,
                  wp.element.createElement(SelectControl, {
                    label: 'Field',
                    value: eventField,
                    options: [
                      { label: 'Start Date (formatted)', value: 'start_date' },
                      { label: 'Start Time (formatted)', value: 'start_time' },
                      { label: 'End Date (formatted)', value: 'end_date' },
                      { label: 'End Time (formatted)', value: 'end_time' },
                      { label: 'Date Range', value: 'date_range' },
                      { label: 'Time Range', value: 'time_range' },
                      { label: 'Cost (raw)', value: 'cost' },
                      { label: 'Cost (formatted)', value: 'cost_formatted' },
                      { label: 'Event URL', value: 'event_url' },
                      { label: 'Venue Name', value: 'venue' },
                      { label: 'Venue URL', value: 'venue_url' },
                      { label: 'Venue Map URL', value: 'venue_map_url' },
                    ],
                    onChange: setEventField,
                  }),
                  wp.element.createElement(TextControl, {
                    label: 'Event ID (optional)',
                    help: 'Bind to a specific event when editing a non-event post.',
                    value: eventPostId,
                    onChange: (v) => setEventPostId(String(v || '')),
                    type: 'number',
                    placeholder: 'e.g. 123',
                  })
                ),
              wp.element.createElement(
                'div',
                { style: { display: 'flex', gap: '8px', marginTop: '8px' } },
                wp.element.createElement(
                  Button,
                  { variant: 'primary', onClick: applyInline },
                  'Apply'
                ),
                wp.element.createElement(
                  Button,
                  { variant: 'secondary', onClick: removeInline },
                  'Remove'
                ),
                wp.element.createElement(
                  Button,
                  { variant: 'secondary', onClick: removeAllInline },
                  'Remove All'
                )
              )
            )
          )
        );
      };
    }, 'withBindingsInspector');

    addFilter(
      'editor.BlockEdit',
      'concordia/bindings/inspector-panel',
      withBindingsInspector
    );
  })();
  if (wp.blocks && wp.blocks.registerBlockBindingsSource && wp.data) {
    try {
      const { registerBlockBindingsSource, getBlockBindingsSource } = wp.blocks;
      const existing =
        getBlockBindingsSource &&
        getBlockBindingsSource('concordia/department-meta');
      const hasPreview = existing && typeof existing.getValues === 'function';
      if (!hasPreview) {
        registerBlockBindingsSource({
          name: 'concordia/department-meta',
          label: 'Department Meta',
          usesContext: ['postId', 'postType'],
          getValues({ select, bindings }) {
            const contentBinding = bindings?.content;
            const textBinding = bindings?.text;
            const urlBinding = bindings?.url;
            let activeBinding = null;
            if (contentBinding?.source === 'concordia/department-meta') {
              activeBinding = { binding: contentBinding, attr: 'content' };
            } else if (textBinding?.source === 'concordia/department-meta') {
              activeBinding = { binding: textBinding, attr: 'text' };
            } else if (urlBinding?.source === 'concordia/department-meta') {
              activeBinding = { binding: urlBinding, attr: 'url' };
            }
            if (!activeBinding) {
              return {};
            }
            const args = activeBinding.binding.args || {};
            const field = args.field || 'phone';
            const fieldToMeta = {
              phone: 'department_phone',
              email: 'department_email',
              address: 'department_address',
              hours: 'department_hours',
              page_link: 'department_page_link',
            };
            const metaKey = fieldToMeta[field];
            if (!metaKey && field !== 'email_mailto' && field !== 'name') {
              return {};
            }
            const core = select('core');
            let termRecord;
            if (args.term_id && Number.isFinite(Number(args.term_id))) {
              termRecord = core.getEntityRecord(
                'taxonomy',
                'department',
                Number(args.term_id)
              );
            } else if (args.term) {
              const list = core.getEntityRecords('taxonomy', 'department', {
                slug: [String(args.term)],
              });
              if (Array.isArray(list) && list.length) {
                termRecord = list[0];
              }
            }
            if (!termRecord) {
              return {};
            }
            let value = null;
            if (field === 'name') {
              value = termRecord.name || '';
            } else if (field === 'email_mailto') {
              const email = termRecord.meta?.department_email;
              value = email ? `mailto:${email}` : '';
            } else {
              value = termRecord.meta?.[metaKey];
            }
            if (!value) {
              return {};
            }
            if (activeBinding.attr === 'content') {
              return { content: String(value) };
            }
            if (activeBinding.attr === 'text') {
              return { text: String(value) };
            }
            if (activeBinding.attr === 'url') {
              return { url: String(value) };
            }
            return {};
          },
        });
      }
    } catch (e) {}
  }
})();
