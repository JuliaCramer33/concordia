<?php
/**
 * Manage GTM Front-end integration
 *
 * @package concordia-core-functionality
 */

namespace CUI_Core\Includes\Web_Services\GTM;

/**
 * Integration
 */
class Integration {
	/**
	 * Using construct method to add any actions and filters
	 */
	public function __construct() {
		// Decide which WP hook to attach our script to.
		$gtm_wp_hook = get_option( 'gtm_wp_hook', 'wp_head' );

		add_action( $gtm_wp_hook, [ $this, 'add_gtm_to_wp_head' ] );
		add_action( 'wp_body_open', [ $this, 'add_gtm_no_script' ] );
	}

	/**
	 * Add the markup required by GTM in the wp head
	 *
	 * @return void
	 */
	public function add_gtm_to_wp_head() {
		$gtm_status = get_option( 'gtm_status' );

		if ( $gtm_status ) {
			$gtm_id = get_option( 'gtm_ID' );

			if ( ! empty( $gtm_id ) ) { ?>

				<!-- Google Tag Manager -->
				<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
				new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
				j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
				'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
				})(window,document,'script','dataLayer','<?php echo esc_html( $gtm_id ); ?>');</script>
				<!-- End Google Tag Manager -->

				<?php
			}
		}
	}

	/**
	 * Add the markup required by GTM in the body for no script situations
	 *
	 * @return void
	 */
	public function add_gtm_no_script() {
		$gtm_status = get_option( 'gtm_status' );

		if ( $gtm_status ) {
			$gtm_id = get_option( 'gtm_ID' );

			if ( ! empty( $gtm_id ) ) {
				?>

				<!-- Google Tag Manager (noscript) -->
				<noscript>
					<iframe src="https://www.googletagmanager.com/ns.html?id=<?php echo esc_attr( $gtm_id ); ?>" height="0" width="0" style="display:none;visibility:hidden"></iframe>
				</noscript>
				<!-- End Google Tag Manager (noscript) -->

				<?php
			}
		}
	}
}
