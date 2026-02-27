<?php
/**
 * Pattern: Video – Overlay (YouTube lazy)
 */

return array(
    'name'       => 'concordia/video-overlay',
    'title'      => __( 'Video – Overlay (YouTube)', 'concordia' ),
    'inserter'   => true,
    'categories' => array( 'cui/components' ),
    'content' => '<!-- wp:group {"className":"c-video","layout":{"type":"constrained"}} -->
<div class="wp-block-group c-video">

  <!-- wp:cover {"url":"https://images.unsplash.com/photo-1520974735194-9e0ce82759ab?w=1600","dimRatio":0,"isUserOverlayColor":true,"className":"c-video__poster","style":{"spacing":{"padding":{"top":"0","bottom":"0","left":"0","right":"0"}}}} -->
  <div class="wp-block-cover c-video__poster" style="padding-top:0;padding-right:0;padding-bottom:0;padding-left:0"><span aria-hidden="true" class="wp-block-cover__background has-background-dim-0 has-background-dim"></span><img class="wp-block-cover__image-background" alt="" src="https://images.unsplash.com/photo-1520974735194-9e0ce82759ab?w=1600" data-object-fit="cover"/><div class="wp-block-cover__inner-container">
    <!-- wp:buttons {"className":"c-video__btn"} -->
    <div class="wp-block-buttons c-video__btn">
      <!-- wp:button {"className":"is-style-fill"} -->
      <div class="wp-block-button is-style-fill"><a class="wp-block-button__link wp-element-button c-video__play" href="#">Play video</a></div>
      <!-- /wp:button -->
    </div>
    <!-- /wp:buttons -->
  </div></div>
  <!-- /wp:cover -->

  <!-- wp:group {"className":"c-video__frame","layout":{"type":"constrained"}} -->
  <div class="wp-block-group c-video__frame">
    <!-- wp:embed {"url":"https://www.youtube.com/watch?v=aqz-KE-bpKQ","type":"video","providerNameSlug":"youtube","responsive":true} -->
    <figure class="wp-block-embed is-type-video is-provider-youtube wp-block-embed-youtube"><div class="wp-block-embed__wrapper">https://www.youtube.com/watch?v=aqz-KE-bpKQ</div></figure>
    <!-- /wp:embed -->
  </div>
  <!-- /wp:group -->

</div>
<!-- /wp:group -->',

);


