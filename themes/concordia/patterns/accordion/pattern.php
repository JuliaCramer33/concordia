<?php
/**
 * Accordion pattern using <details>/<summary> (CSS-only)
 */

return array(
    'name'       => 'concordia/accordion',
    'title'      => __( 'Accordion', 'concordia' ),
    'inserter'   => true,
    'categories' => array( 'cui/components' ),
    'content'    => '<!-- wp:group {"metadata":{"categories":["cui/components"],"patternName":"concordia/accordion","name":"Accordion"},"className":"c-accordion","style":{"border":{"width":"0px","style":"none"}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group c-accordion" style="border-style:none;border-width:0px"><!-- wp:details {"style":{"spacing":{"padding":{"top":"0","bottom":"0","left":"0","right":"0"},"margin":{"bottom":"var:preset|spacing|50"}},"elements":{"link":{"color":{"text":"var:preset|color|primary"}}}},"textColor":"primary","fontSize":"large","fontFamily":"source-serif-4"} -->
<details class="wp-block-details has-primary-color has-text-color has-link-color has-source-serif-4-font-family has-large-font-size" style="margin-bottom:var(--wp--preset--spacing--50);padding-top:0;padding-right:0;padding-bottom:0;padding-left:0"><summary>Courses</summary><!-- wp:group {"style":{"spacing":{"padding":{"top":"var:preset|spacing|20","bottom":"var:preset|spacing|20"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group" style="padding-top:var(--wp--preset--spacing--20);padding-bottom:var(--wp--preset--spacing--20)"><!-- wp:paragraph {"style":{"elements":{"link":{"color":{"text":"var:preset|color|contrast"}}}},"textColor":"contrast","fontSize":"body","fontFamily":"source-sans-3"} -->
<p class="has-contrast-color has-text-color has-link-color has-source-sans-3-font-family has-body-font-size">Place course content here. Lists and links supported.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --></details>
<!-- /wp:details -->

<!-- wp:details {"style":{"spacing":{"padding":{"top":"0","bottom":"0","left":"0","right":"0"},"margin":{"bottom":"var:preset|spacing|50"}},"elements":{"link":{"color":{"text":"var:preset|color|primary"}}}},"textColor":"primary","fontSize":"large","fontFamily":"source-serif-4"} -->
<details class="wp-block-details has-primary-color has-text-color has-link-color has-source-serif-4-font-family has-large-font-size" style="margin-bottom:var(--wp--preset--spacing--50);padding-top:0;padding-right:0;padding-bottom:0;padding-left:0"><summary>Program Emphases</summary><!-- wp:paragraph {"style":{"spacing":{"padding":{"top":"var:preset|spacing|20","bottom":"var:preset|spacing|20"}}},"fontSize":"body"} -->
<p class="has-body-font-size" style="padding-top:var(--wp--preset--spacing--20);padding-bottom:var(--wp--preset--spacing--20)">Describe program emphases here.</p>
<!-- /wp:paragraph --></details>
<!-- /wp:details -->

<!-- wp:details {"style":{"spacing":{"padding":{"top":"0","bottom":"0","left":"0","right":"0"},"margin":{"bottom":"var:preset|spacing|50"}},"elements":{"link":{"color":{"text":"var:preset|color|primary"}}}},"textColor":"primary","fontSize":"large","fontFamily":"source-serif-4"} -->
<details class="wp-block-details has-primary-color has-text-color has-link-color has-source-serif-4-font-family has-large-font-size" style="margin-bottom:var(--wp--preset--spacing--50);padding-top:0;padding-right:0;padding-bottom:0;padding-left:0"><summary>Tuition and Fees</summary><!-- wp:paragraph {"style":{"spacing":{"padding":{"top":"var:preset|spacing|20","bottom":"var:preset|spacing|20"}}},"fontSize":"body"} -->
<p class="has-body-font-size" style="padding-top:var(--wp--preset--spacing--20);padding-bottom:var(--wp--preset--spacing--20)">Tuition details and links.</p>
<!-- /wp:paragraph --></details>
<!-- /wp:details --></div>
<!-- /wp:group -->',
);


