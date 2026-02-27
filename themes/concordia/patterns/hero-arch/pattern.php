<?php
/**
 * Hero with Arch Mask and Parallax
 *
 * Name: concordia/hero-arch
 * Title: Hero – Arch Mask with Parallax
 * Slug: concordia/hero-arch
 * Categories: cui/sections
 * Block Types: core/group
 * Inserter: true
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

// Pattern content: using Cover block variant provided (with hero-arch class).
$content = <<<'HTML'
<!-- wp:cover {"url":"http://concordia.local/wp-content/uploads/2025/12/URFALIAN_CON_6119.1-scaled.webp","id":1051,"dimRatio":0,"customOverlayColor":"#FFF","isUserOverlayColor":false,"focalPoint":{"x":0.49,"y":0},"minHeightUnit":"vh","isDark":false,"sizeSlug":"full","metadata":{"categories":["cui/sections"],"patternName":"concordia/hero-arch","name":"Hero – Arch Mask with Parallax"},"align":"wide","className":"hero-arch is-hero-scroll is-text-parallax","style":{"border":{"radius":{"topLeft":"16px","topRight":"16px"}},"spacing":{"margin":{"top":"var:preset|spacing|20","bottom":"var:preset|spacing|20"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-cover alignwide is-light hero-arch is-hero-scroll is-text-parallax" style="border-top-left-radius:16px;border-top-right-radius:16px;margin-top:var(--wp--preset--spacing--20);margin-bottom:var(--wp--preset--spacing--20)"><img class="wp-block-cover__image-background wp-image-1051 size-full" alt="" src="http://concordia.local/wp-content/uploads/2025/12/URFALIAN_CON_6119.1-scaled.webp" style="object-position:49% 0%" data-object-fit="cover" data-object-position="49% 0%"/><span aria-hidden="true" class="wp-block-cover__background has-background-dim-0 has-background-dim" style="background-color:#FFF"></span><div class="wp-block-cover__inner-container"><!-- wp:group {"style":{"spacing":{"padding":{"top":"var:preset|spacing|40"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group" style="padding-top:var(--wp--preset--spacing--40)"><!-- wp:heading {"textAlign":"center","level":1,"style":{"elements":{"link":{"color":{"text":"var:preset|color|base"}}},"typography":{"fontStyle":"normal","fontWeight":"500","lineHeight":"1.2"}},"textColor":"base","fontSize":"h-1-large"} -->
<h1 class="wp-block-heading has-text-align-center has-base-color has-text-color has-link-color has-h-1-large-font-size" style="font-style:normal;font-weight:500;line-height:1.2">
				Freedom to <em>explore</em><br>your <em>future</em> and <em>faith</em>
			</h1>
<!-- /wp:heading --></div>
<!-- /wp:group --></div></div>
<!-- /wp:cover -->
HTML;

return array(
	'name'        => 'concordia/hero-arch',
	'title'       => __( 'Hero – Arch Mask with Parallax', 'concordia' ),
	'description' => __( 'A hero section with an SVG arch mask and subtle parallax on scroll.', 'concordia' ),
	'categories'  => array( 'cui/sections' ),
	'content'     => $content,
);


