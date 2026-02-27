import { __ } from '@wordpress/i18n';
import { registerFormatType, toggleFormat } from '@wordpress/rich-text';
import { RichTextToolbarButton } from '@wordpress/block-editor';
import { border, formatUnderline } from '@wordpress/icons';

const EditButton = ({ isActive, onChange, value, type, title, icon }) => (
  <RichTextToolbarButton
    icon={icon}
    title={title}
    onClick={() => {
      onChange(
        toggleFormat(value, {
          type,
        })
      );
    }}
    isActive={isActive}
  />
);

export default () => {
  registerFormatType('kanahoma/underline', {
    title: __('Underline', 'kanahoma-responsive-settings'),
    tagName: 'span',
    className: 'kanahoma-underline',
    edit: (props) =>
      EditButton({
        ...props,
        type: 'kanahoma/underline',
        title: __('Underline', 'kanahoma-responsive-settings'),
        icon: formatUnderline,
      }),
  });

  registerFormatType('kanahoma/circle', {
    title: __('Circle', 'kanahoma-responsive-settings'),
    tagName: 'span',
    className: 'kanahoma-circle',
    edit: (props) =>
      EditButton({
        ...props,
        type: 'kanahoma/circle',
        title: __('Circle', 'kanahoma-responsive-settings'),
        icon: border,
      }),
  });
};
