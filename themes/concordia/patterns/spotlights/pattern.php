<?php
/**
 * Pattern: Spotlights (3-up, interactive)
 */

return array(
    'name'       => 'concordia/spotlights',
    'title'      => __( 'Spotlights (3-up)', 'concordia' ),
    'inserter'   => true,
    'categories' => array( 'cui/components' ),
    'content'    => '<!-- wp:group {"className":"c-spotlights c-spotlights--interactive","layout":{"type":"constrained"}} -->
<div class="wp-block-group c-spotlights c-spotlights--interactive"><!-- wp:columns {"className":"c-spotlights__columns","isStackedOnMobile":false} -->
<div class="wp-block-columns is-not-stacked-on-mobile c-spotlights__columns"><!-- wp:column {"className":"c-spotlights__panel is-active"} -->
<div class="wp-block-column c-spotlights__panel is-active"><!-- wp:cover {"dimRatio":0,"className":"c-spotlights__cover","overlayColor":"transparent"} -->
<div class="wp-block-cover c-spotlights__cover"><span aria-hidden="true" class="wp-block-cover__background has-transparent-background-color has-background-dim-0 has-background-dim"></span><div class="wp-block-cover__inner-container"><!-- wp:paragraph -->
<p>Insert an image or use the Video – Overlay pattern here.</p>
<!-- /wp:paragraph --></div></div>
<!-- /wp:cover --></div>
<!-- /wp:column -->

<!-- wp:column {"className":"c-spotlights__panel"} -->
<div class="wp-block-column c-spotlights__panel"><!-- wp:cover {"dimRatio":0,"className":"c-spotlights__cover","overlayColor":"transparent"} -->
<div class="wp-block-cover c-spotlights__cover"><span aria-hidden="true" class="wp-block-cover__background has-transparent-background-color has-background-dim-0 has-background-dim"></span><div class="wp-block-cover__inner-container"><!-- wp:paragraph -->
<p>Insert an image or use the Video – Overlay pattern here.</p>
<!-- /wp:paragraph --></div></div>
<!-- /wp:cover --></div>
<!-- /wp:column -->

<!-- wp:column {"className":"c-spotlights__panel"} -->
<div class="wp-block-column c-spotlights__panel"><!-- wp:cover {"dimRatio":0,"className":"c-spotlights__cover","overlayColor":"transparent"} -->
<div class="wp-block-cover c-spotlights__cover"><span aria-hidden="true" class="wp-block-cover__background has-transparent-background-color has-background-dim-0 has-background-dim"></span><div class="wp-block-cover__inner-container"><!-- wp:paragraph -->
<p>Insert an image or use the Video – Overlay pattern here.</p>
<!-- /wp:paragraph --></div></div>
<!-- /wp:cover --></div>
<!-- /wp:column --></div>
<!-- /wp:columns --></div>
<!-- /wp:group -->',
);


