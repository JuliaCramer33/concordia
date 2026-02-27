<?php
/**
 * Kanahoma Carousel – server render (dynamic title + stable JS hooks)
 */

$attrs = wp_parse_args($attributes, [
  'spvMobile'   => 1,
  'spvTablet'   => 2,
  'spvDesktop'  => 4,
  'gap'         => '1rem',
  'peek'        => '0px',
  'arrows'      => 'top-right',
  'showProgress'=> true,
  'titleText'   => '',
  'titleLevel'  => 'h3',
  'titleColor'  => '',
  'titleAlign'  => 'left',
  'titleFontSize' => '',
  'mode'        => 'auto',
  'bleed'       => 'none',
]);

$style = sprintf(
  '--gap:%1$s;--peek:%2$s;--spv-mobile:%3$d;--spv-tablet:%4$d;--spv-desktop:%5$d;',
  esc_attr($attrs['gap']),
  esc_attr($attrs['peek']),
  (int) $attrs['spvMobile'],
  (int) $attrs['spvTablet'],
  (int) $attrs['spvDesktop']
);

// Wrapper attributes from Gutenberg (align/spacing)
$arrows_pos = isset($attrs['arrows']) ? (string) $attrs['arrows'] : '';
if ($arrows_pos === 'top-left') { $arrows_pos = 'top-right'; }
$wrapper_attributes = get_block_wrapper_attributes([
  'class'             => 'kanahoma-carousel',
  'style'             => $style,
  'data-mode'         => $attrs['mode'],
  'data-controls-pos' => $arrows_pos,
  'data-bleed'        => $attrs['bleed'],
]);

// Title rendering config
$title_tag = in_array($attrs['titleLevel'], ['h2','h3','h4','h5'], true) ? $attrs['titleLevel'] : 'h3';
$title_style_inline = '';
if (!empty($attrs['titleColor']))     $title_style_inline .= 'color:' . esc_attr($attrs['titleColor']) . ';';
if (!empty($attrs['titleFontSize']))  $title_style_inline .= 'font-size:' . esc_attr($attrs['titleFontSize']) . ';';
$title_align_class = !empty($attrs['titleAlign']) ? 'has-text-align-' . esc_attr($attrs['titleAlign']) : '';
?>
<section <?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> data-carousel="1">
  <div class="kanahoma-carousel__header">
    <?php if ($attrs['titleText']) : ?>
      <<?php echo esc_attr($title_tag); ?>
        class="kanahoma-carousel__title <?php echo esc_attr($title_align_class); ?>"
        style="<?php echo esc_attr($title_style_inline); ?>">
        <?php echo esc_html($attrs['titleText']); ?>
      </<?php echo esc_attr($title_tag); ?>>
    <?php else : ?>
      <span class="kanahoma-carousel__title-spacer" aria-hidden="true"></span>
    <?php endif; ?>

    <?php if ($arrows_pos === 'top-right') : ?>
      <div class="kanahoma-carousel__controls" data-carousel-controls>
        <button class="kanahoma-carousel__prev" type="button" aria-label="<?php esc_attr_e('Previous slide','kanahoma'); ?>">‹</button>
        <button class="kanahoma-carousel__next" type="button" aria-label="<?php esc_attr_e('Next slide','kanahoma'); ?>">›</button>
      </div>
    <?php endif; ?>
  </div>

  <?php if ($attrs['showProgress']) : ?>
    <div class="kanahoma-carousel__progress" aria-hidden="true">
      <div class="kanahoma-carousel__progress-bar"></div>
    </div>
  <?php endif; ?>

  <div class="kanahoma-carousel__viewport" data-carousel-viewport>

    <div class="kanahoma-carousel__items" data-carousel-items>
      <?php
        // Render inner blocks/content
        $final_html = '';
        $rendered = trim( do_blocks( $content ) );
        if ( $rendered !== '' ) {
          $final_html = $rendered;
        } else {
          $raw = trim( (string) $content );
          if ( $raw !== '' ) {
            $final_html = $raw;
          } else {
            $inner_blocks = isset( $block ) && is_object( $block ) ? $block->inner_blocks : [];
            if ( ! empty( $inner_blocks ) ) {
              foreach ( $inner_blocks as $inner ) {
                if ( method_exists( $inner, 'render' ) ) {
                  $final_html .= $inner->render();
                } elseif ( isset( $inner->parsed_block ) ) {
                  $final_html .= render_block( $inner->parsed_block );
                }
              }
            }
          }
        }
        if ( trim( $final_html ) !== '' ) {
          echo $final_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        }
      ?>
    </div>
  </div>

  <?php if ($arrows_pos === 'bottom-right' || $arrows_pos === 'bottom-left') : ?>
    <div class="kanahoma-carousel__controls kanahoma-carousel__controls--bottom" data-carousel-controls>
      <button class="kanahoma-carousel__prev" type="button" aria-label="<?php esc_attr_e('Previous slide','kanahoma'); ?>">‹</button>
      <button class="kanahoma-carousel__next" type="button" aria-label="<?php esc_attr_e('Next slide','kanahoma'); ?>">›</button>
    </div>
  <?php elseif ($arrows_pos === 'bottom-center') : ?>
    <div class="kanahoma-carousel__controls kanahoma-carousel__controls--bottom kanahoma-carousel__controls--center" data-carousel-controls>
      <button class="kanahoma-carousel__prev" type="button" aria-label="<?php esc_attr_e('Previous slide','kanahoma'); ?>">‹</button>
      <button class="kanahoma-carousel__next" type="button" aria-label="<?php esc_attr_e('Next slide','kanahoma'); ?>">›</button>
    </div>
  <?php endif; ?>
</section>
