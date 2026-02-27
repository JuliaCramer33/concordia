<?php

namespace Kanahoma\Responsive_Settings;

class Responsive_Renderer {
	public function __construct() {
		add_filter( 'render_block', array( $this, 'inject_vars' ), 10, 3 );
		add_action( 'wp_head', array( $this, 'print_css' ) );
	}

    /**
     * Get responsive breakpoints (in px) in ascending order.
     * Single source of truth via filter.
     *
     * @return array { size => minWidthPx }
     */
    private function get_breakpoints() {
        $defaults = array(
            'mobile'  => 0,
            'tablet'  => 768,
            'desktop' => 1024,
        );

		/**
		 * Allow themes/plugins to override breakpoints.
		 * Expected ascending order.
		 */
        $bps = apply_filters( 'kanahoma_responsive_breakpoints', $defaults );

		// Ensure ordered by value ascending.
		asort( $bps, SORT_NUMERIC );
		return $bps;
	}

    public function inject_vars( $block_content, $block, $i ) {
        $attrs = $block['attrs'] ?? array();
        if ( ! is_string( $block_content ) || '' === trim( $block_content ) ) {
            return $block_content;
        }

        $c   = ( isset( $attrs['kanahomaResp'] ) && is_array( $attrs['kanahomaResp'] ) ) ? $attrs['kanahomaResp'] : array();
        $pad = isset( $c['pad'] ) && is_array( $c['pad'] ) ? $c['pad'] : array();
        $mar = isset( $c['mar'] ) && is_array( $c['mar'] ) ? $c['mar'] : array();
        $vars = array();
        $bps  = array_keys( $this->get_breakpoints() ); // unified map (mobile, tablet, desktop)

		$allow_value = static function ( $v ) {
			$v = (string) $v;
			if ( preg_match( '#^var\(--wp--preset--spacing--[a-z0-9\-]+\)$#', $v ) ) {
				return $v;
			}
			if ( preg_match( '#^-?\d+(?:\.\d+)?(px|rem|em|%)$#i', $v ) ) {
				return $v;
			}
			return '';
		};

        // Track which breakpoints/sides have usable values
        $side_classes = array( 'pad' => array(), 'mar' => array() );
        // Track which breakpoints actually have usable padding values
        $pad_bp_set = array();
        foreach ( $bps as $bp ) {
			if ( empty( $pad[ $bp ] ) || ! is_array( $pad[ $bp ] ) ) {
				continue;
			}
            // Prefer axis values (y -> top/bottom, x -> left/right). If sides exist they will override axes.
			$y      = isset( $pad[ $bp ]['y'] ) ? $allow_value( $pad[ $bp ]['y'] ) : '';
			$x      = isset( $pad[ $bp ]['x'] ) ? $allow_value( $pad[ $bp ]['x'] ) : '';
			$top    = isset( $pad[ $bp ]['top'] ) ? $allow_value( $pad[ $bp ]['top'] ) : $y;
			$right  = isset( $pad[ $bp ]['right'] ) ? $allow_value( $pad[ $bp ]['right'] ) : $x;
			$bottom = isset( $pad[ $bp ]['bottom'] ) ? $allow_value( $pad[ $bp ]['bottom'] ) : $y;
			$left   = isset( $pad[ $bp ]['left'] ) ? $allow_value( $pad[ $bp ]['left'] ) : $x;

            if ( $top )   { $vars[] = "--kanahoma-pad-top-{$bp}:{$top};";   $pad_bp_set[ $bp ] = true; }
            if ( $right ) { $vars[] = "--kanahoma-pad-right-{$bp}:{$right};"; $pad_bp_set[ $bp ] = true; }
            if ( $bottom ){ $vars[] = "--kanahoma-pad-bottom-{$bp}:{$bottom};"; $pad_bp_set[ $bp ] = true; }
            if ( $left )  { $vars[] = "--kanahoma-pad-left-{$bp}:{$left};";  $pad_bp_set[ $bp ] = true; }

            // Add per-side helper classes
            if ( $top )   { $side_classes['pad'][] = 'kanahoma-has-pad-top-' . $bp; }
            if ( $right ) { $side_classes['pad'][] = 'kanahoma-has-pad-right-' . $bp; }
            if ( $bottom ){ $side_classes['pad'][] = 'kanahoma-has-pad-bottom-' . $bp; }
            if ( $left )  { $side_classes['pad'][] = 'kanahoma-has-pad-left-' . $bp; }

            if ( $top || $right || $bottom || $left ) { $pad_bp_set[ $bp ] = true; }
		}

        // Track which breakpoints actually have usable margin values
        $mar_bp_set = array();
        foreach ( $bps as $bp ) {
            if ( empty( $mar[ $bp ] ) || ! is_array( $mar[ $bp ] ) ) {
                continue;
            }
            $y      = isset( $mar[ $bp ]['y'] ) ? $allow_value( $mar[ $bp ]['y'] ) : '';
            $x      = isset( $mar[ $bp ]['x'] ) ? $allow_value( $mar[ $bp ]['x'] ) : '';
            $top    = isset( $mar[ $bp ]['top'] ) ? $allow_value( $mar[ $bp ]['top'] ) : $y;
            $right  = isset( $mar[ $bp ]['right'] ) ? $allow_value( $mar[ $bp ]['right'] ) : $x;
            $bottom = isset( $mar[ $bp ]['bottom'] ) ? $allow_value( $mar[ $bp ]['bottom'] ) : $y;
            $left   = isset( $mar[ $bp ]['left'] ) ? $allow_value( $mar[ $bp ]['left'] ) : $x;

            if ( $top )   { $vars[] = "--kanahoma-mar-top-{$bp}:{$top};";   $mar_bp_set[ $bp ] = true; }
            if ( $right ) { $vars[] = "--kanahoma-mar-right-{$bp}:{$right};"; $mar_bp_set[ $bp ] = true; }
            if ( $bottom ){ $vars[] = "--kanahoma-mar-bottom-{$bp}:{$bottom};"; $mar_bp_set[ $bp ] = true; }
            if ( $left )  { $vars[] = "--kanahoma-mar-left-{$bp}:{$left};";  $mar_bp_set[ $bp ] = true; }

            // Add per-side helper classes
            if ( $top )   { $side_classes['mar'][] = 'kanahoma-has-mar-top-' . $bp; }
            if ( $right ) { $side_classes['mar'][] = 'kanahoma-has-mar-right-' . $bp; }
            if ( $bottom ){ $side_classes['mar'][] = 'kanahoma-has-mar-bottom-' . $bp; }
            if ( $left )  { $side_classes['mar'][] = 'kanahoma-has-mar-left-' . $bp; }
        }

        // Responsive flow for core/columns on wrapper
		$flow_attr = $attrs['kanahomaRespFlow'] ?? array();
		$flow_vars = array();
		if ( is_array( $flow_attr ) && ! empty( $flow_attr ) && isset( $block['blockName'] ) && $block['blockName'] === 'core/columns' ) {
			foreach ( $bps as $bp_name ) {
				if ( empty( $flow_attr[ $bp_name ] ) ) {
					continue;
				}
				$val = (string) $flow_attr[ $bp_name ];
                if ( in_array( $val, array( 'row', 'row-reverse', 'column', 'column-reverse' ), true ) ) {
					$flow_vars[] = "--kanahoma-flow-{$bp_name}:{$val};";
				}
			}
		}

        // Stacking breakpoint: convert a selected name to default flow=column for that bp and smaller
        $stack_at = isset( $attrs['kanahomaStackAt'] ) ? (string) $attrs['kanahomaStackAt'] : '';
        $stack_flow_vars = array();
        if ( isset( $block['blockName'] ) && $block['blockName'] === 'core/columns' ) {
            $names = array_keys( $this->get_breakpoints() );
            $idx   = array_search( $stack_at, $names, true );
            if ( $idx !== false ) {
                for ( $i = 0; $i <= $idx; $i++ ) {
                    $nm = $names[ $i ];
                    $stack_flow_vars[] = '--kanahoma-flow-' . $nm . ':column;';
                }
            }
        }

        // Core fallback: respect core/columns isStackedOnMobile when our mobile flow is not set
        $base_flow_var = '';
        if ( isset( $block['blockName'] ) && $block['blockName'] === 'core/columns' && array_key_exists( 'isStackedOnMobile', $attrs ) ) {
            // If true -> column; false -> row
            $base_flow_var = '--kanahoma-flow:' . ( $attrs['isStackedOnMobile'] ? 'column' : 'row' ) . ';';
        }

		if ( empty( $vars ) && empty( $flow_vars ) && empty( $stack_flow_vars ) && empty( $base_flow_var ) ) {
			return $block_content;
		}

        $processor = new \WP_HTML_Tag_Processor( $block_content );
        $processor->next_tag();
        $processor->add_class( 'kanahoma-resp' );

        // Append or create style attribute with our vars (pads + margins) and flow/stack.
        $extra_vars = array();
        if ( $base_flow_var ) { $extra_vars[] = $base_flow_var; }
        // Order matters: stack defaults first, explicit flows later to override
        $style_vars = implode( '', array_merge( $vars, $stack_flow_vars, $flow_vars, $extra_vars ) );
        $current   = $processor->get_attribute( 'style' );
        // Remove any previous Kanahoma vars so toggles off truly clear prior values
        $prefix = '--kanahoma-';
        $kept = array();
        // Add per-breakpoint and per-side helper classes
        foreach ( $bps as $bp_name_check ) {
            if ( isset( $mar_bp_set[ $bp_name_check ] ) && true === $mar_bp_set[ $bp_name_check ] ) {
                $processor->add_class( 'kanahoma-has-mar-' . $bp_name_check );
            }
            if ( isset( $pad_bp_set[ $bp_name_check ] ) && true === $pad_bp_set[ $bp_name_check ] ) {
                $processor->add_class( 'kanahoma-has-pad-' . $bp_name_check );
            }
        }
        if ( ! empty( $side_classes['mar'] ) ) {
            foreach ( array_unique( $side_classes['mar'] ) as $cls ) { $processor->add_class( $cls ); }
        }
        if ( ! empty( $side_classes['pad'] ) ) {
            foreach ( array_unique( $side_classes['pad'] ) as $cls ) { $processor->add_class( $cls ); }
        }
        if ( is_string( $current ) && $current !== '' ) {
            $decls = explode( ';', $current );
            foreach ( $decls as $decl ) {
                $d = trim( $decl );
                if ( $d === '' ) { continue; }
                if ( strpos( ltrim( $d ), $prefix ) === 0 ) { continue; }
                // Keep all non-kanahoma decls (including margin) so style pane defaults remain where no responsive value is set
                $kept[] = $d;
            }
        }
        $base = implode( ';', $kept );
        if ( $base !== '' && substr( $base, -1 ) !== ';' ) { $base .= ';'; }
        $joined = $base . $style_vars;
        $processor->set_attribute( 'style', trim( $joined ) );

		return $processor->get_updated_html();
	}

	public function print_css() {
        $css = $this->get_css();
        echo '<style>' . esc_html( $css ) . '</style>';
    }

    /**
     * Build the responsive CSS string (no <style> wrapper).
     * Public so the editor can inline the same CSS for previews.
     *
     * @return string
     */
    public function get_css() {
        $bps   = $this->get_breakpoints();
        $names = array_keys( $bps );
        $css   = '';

		// Helper to build fallback chain var(--pad-side-size, fallback)
		$build_fallback = function( $side, $idx ) use ( $names ) {
			$size = $names[ $idx ];
			$var  = 'var(--kanahoma-pad-' . $side . '-' . $size . ', ';
			// Recurse to previous sizes for fallback, end with 'revert'
			for ( $i = $idx - 1; $i >= 0; $i-- ) {
				$prev = $names[ $i ];
				$var .= 'var(--kanahoma-pad-' . $side . '-' . $prev . ', ';
			}
			$var .= 'revert' . str_repeat( ')', $idx + 1 );
			return $var;
		};

		// Generate CSS per segment.
		foreach ( $names as $idx => $size ) {
			$min = (int) $bps[ $size ];
			$max = null;
			if ( isset( $names[ $idx + 1 ] ) ) {
				$next = (int) $bps[ $names[ $idx + 1 ] ];
				$max  = $next - 1;
			}

            $block = '.kanahoma-resp{}';

            // Only apply paddings on sides that are present for this breakpoint
            $padding_block  = '.kanahoma-resp.kanahoma-has-pad-top-' . $size . '{padding-top:' . ( $idx === 0 ? 'var(--kanahoma-pad-top-' . $size . ', revert) !important' : $build_fallback( 'top', $idx ) . ' !important' ) . ';}';
            $padding_block .= '.kanahoma-resp.kanahoma-has-pad-right-' . $size . '{padding-right:' . ( $idx === 0 ? 'var(--kanahoma-pad-right-' . $size . ', revert) !important' : $build_fallback( 'right', $idx ) . ' !important' ) . ';}';
            $padding_block .= '.kanahoma-resp.kanahoma-has-pad-bottom-' . $size . '{padding-bottom:' . ( $idx === 0 ? 'var(--kanahoma-pad-bottom-' . $size . ', revert) !important' : $build_fallback( 'bottom', $idx ) . ' !important' ) . ';}';
            $padding_block .= '.kanahoma-resp.kanahoma-has-pad-left-' . $size . '{padding-left:' . ( $idx === 0 ? 'var(--kanahoma-pad-left-' . $size . ', revert) !important' : $build_fallback( 'left', $idx ) . ' !important' ) . ';}';

            // Only apply margins on sides that are present for this breakpoint
            $margin_block  = '.kanahoma-resp.kanahoma-has-mar-top-' . $size . '{margin-top:var(--kanahoma-mar-top-' . $size . ') !important;}';
            $margin_block .= '.kanahoma-resp.kanahoma-has-mar-right-' . $size . '{margin-right:var(--kanahoma-mar-right-' . $size . ') !important;}';
            $margin_block .= '.kanahoma-resp.kanahoma-has-mar-bottom-' . $size . '{margin-bottom:var(--kanahoma-mar-bottom-' . $size . ') !important;}';
            $margin_block .= '.kanahoma-resp.kanahoma-has-mar-left-' . $size . '{margin-left:var(--kanahoma-mar-left-' . $size . ') !important;}';

			// Flow direction on core/columns wrapper
            // Strong fallback: mobile defaults to column (or core toggle via --kanahoma-flow),
            // larger breakpoints default to row so they don't inherit mobile stacking.
            $fallback_dir = ( $size === 'mobile' ) ? 'var(--kanahoma-flow, column)' : 'row';
            $flow_css = '.kanahoma-resp.wp-block-columns{'
                . 'display:flex;'
                . 'flex-wrap:wrap;'
                . 'align-items:stretch;'
                . 'flex-direction:' . 'var(--kanahoma-flow-' . $size . ', ' . $fallback_dir . ')' . ';'
				. '}';

            // Stacking defaults are injected via --kanahoma-flow-<size>: column in style attribute; no extra CSS needed here
            $cond_css = '';

            if ( $idx === 0 ) {
                // Mobile: limit margin overrides to mobile range only
                if ( isset( $names[ $idx + 1 ] ) ) {
                    $next = (int) $bps[ $names[ $idx + 1 ] ];
                    $mobile_max = $next - 1;
                    $css .= $block . '@media (max-width:' . $mobile_max . 'px){' . $margin_block . $padding_block . '}' . $flow_css . $cond_css;
                } else {
                    // No next bp defined; apply as base
                    $css .= $block . $margin_block . $padding_block . $flow_css . $cond_css;
                }
			} else {
				$mq = '(min-width:' . $min . 'px)';
				if ( $max ) {
					$mq .= ' and (max-width:' . $max . 'px)';
				}
                $css .= '@media ' . $mq . '{' . $block . $margin_block . $padding_block . $flow_css . $cond_css . '}';
			}
		}

        // Ensure images fill their grid cell width inside Columns
        $css .= '.kanahoma-resp.wp-block-columns figure.wp-block-image{width:100%;margin:0;}';
        $css .= '.kanahoma-resp.wp-block-columns figure.wp-block-image img{display:block;width:100%;height:auto;object-fit:cover;}';
        $css .= '.kanahoma-resp.wp-block-columns .wp-block-cover{height:100%;min-height:0;}';
        $css .= '.kanahoma-resp.wp-block-columns .wp-block-cover__inner-container{height:100%;min-height:0;}';
        $css .= '.kanahoma-resp.wp-block-columns .wp-block-cover img,'
            . '.kanahoma-resp.wp-block-columns .wp-block-cover .wp-block-cover__image-background{width:100%;height:100%;object-fit:cover;}';

        return $css;
    }
}


