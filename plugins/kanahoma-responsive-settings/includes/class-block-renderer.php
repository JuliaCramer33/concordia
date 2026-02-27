<?php

namespace Kanahoma\Responsive_Settings;

class Block_Renderer {
	/**
	 * Constructor.
	 */
	public function __construct() {
		add_filter( 'render_block', array( $this, 'add_responsive_classes_to_block' ), 10, 3 );
		add_action( 'wp_head', array( $this, 'add_inline_responsive_classes' ) );
	}

	/**
	 * Add relevant classes to block.
	 *
	 * @param string $block_content The block content about to be rendered.
	 * @param array  $block The block data.
	 * @return string
	 */
	public function add_responsive_classes_to_block( $block_content, $block ) {
		$attributes = $block['attrs'] ?? array();

        $controls = $attributes['kanahomaResponsiveControls'] ?? null;
        if ( ! is_array( $controls ) ) {
            return $block_content;
        }

		$classes = array_map(
			function( $size, $control ) {
                return $control === 'hide' ? "kanahoma-hide-{$size}" : '';
			},
			array_keys( $controls ),
			$controls
		);

		$class = implode( ' ', array_filter( $classes ) );

		if ( empty( $class ) ) {
			return $block_content;
		}

        $processor = new \WP_HTML_Tag_Processor( $block_content );
        $processor->next_tag();
        $processor->add_class( esc_attr( $class ) );
        $html = $processor->get_updated_html();

        return is_string( $html ) ? $html : $block_content;
	}

	/**
	 * Add responsive classes to <head>.
	 */
    public function add_inline_responsive_classes() {
        // Expect ascending map of name => minWidthPx (e.g., mobile=>0, tablet=>768, desktop=>1024 [, wide=>1440])
        $defaults = array(
            'mobile'  => 0,
            'tablet'  => 768,
            'desktop' => 1024,
        );

        $bps = apply_filters( 'kanahoma_responsive_breakpoints', $defaults );
        if ( empty( $bps ) || ! is_array( $bps ) ) {
            return;
        }

        // Ensure ascending order by value
        asort( $bps, SORT_NUMERIC );
        $names  = array_keys( $bps );
        $widths = array_values( $bps );

        $css = '';
        $count = count( $names );
        for ( $i = 0; $i < $count; $i++ ) {
            $size = $names[ $i ];
            $min  = (int) $widths[ $i ];
            $has_next = ($i + 1) < $count;
            $mq = '';
            if ( $min > 0 && $has_next ) {
                $max = (int) $widths[ $i + 1 ] - 1;
                $mq  = '(min-width: ' . $min . 'px) and (max-width: ' . $max . 'px)';
            } elseif ( $min > 0 && ! $has_next ) {
                $mq  = '(min-width: ' . $min . 'px)';
            } elseif ( $min === 0 && $has_next ) {
                $max = (int) $widths[ $i + 1 ] - 1;
                $mq  = '(max-width: ' . $max . 'px)';
            } else {
                // Single zero breakpoint only; hide always would be nonsensical, skip
                continue;
            }

            $css .= '@media ' . $mq . ' { .kanahoma-hide-' . $size . ' { display: none !important; } }';
        }

        echo '<style>' . esc_html( $css ) . '</style>';
    }
}
