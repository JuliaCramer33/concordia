<?php
/**
 * Pattern: Hero – Simple
 * Usage: returns an array consumed by register_block_pattern.
 */

return array(
    'title'       => __( 'Hero – Simple', 'concordia' ),
    'description' => __( 'A simple hero with centered heading and paragraph.', 'concordia' ),
    'categories'  => array( 'cui/sections' ),
    'name'        => 'concordia/hero-simple',
    'content'     => '<!-- wp:group {"align":"full","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull"><div class="wp-block-group__inner-container"><!-- wp:heading {"textAlign":"center"} -->
<h2 class="wp-block-heading has-text-align-center">' . esc_html__( 'Welcome to Concordia', 'concordia' ) . '</h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"align":"center"} -->
<p class="has-text-align-center">' . esc_html__( 'Edit this hero pattern to suit your page.', 'concordia' ) . '</p>
<!-- /wp:paragraph --></div></div>
<!-- /wp:group -->',
);


