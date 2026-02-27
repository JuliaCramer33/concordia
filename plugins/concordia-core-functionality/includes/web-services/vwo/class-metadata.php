<?php
/**
 * Manage VWO component Metadata in admin.
 *
 * @category   Class
 * @package    concordia-core-functionality
 * @subpackage vwo
 * @author     Kanahoma
 */

namespace CUI_Core\Includes\Web_Services\VWO;

/**
 * Metadata
 */
class Metadata {
	/**
	 * Using construct function to add any actions and filters associated with the VWO component
	 */
	public function __construct() {
		add_action( 'fm_post', [ $this, 'register_metabox' ] );
	}

	/**
	 * Register the page metabox that holds all our meta fields
	 *
	 * @return void
	 */
	public function register_metabox() {
		$fm = new \Fieldmanager_Group( [
			'name'           => 'vwo', // "name" id deceiving, used as the key/ID.
			'serialize_data' => false,
			'children'       => [
				'status'        => new \Fieldmanager_Checkbox( [
					'label'       => __( 'Enable VWO on page', 'concordia-core-functionality' ),
					'description' => __( 'Will insert the VWO embed code specifically on this page.', 'concordia-core-functionality' ),
				] ),
				'account_id'    => new \Fieldmanager_TextField( [
					'label'      => __( 'VWO Account ID', 'concordia-core-functionality' ),
					'description' => __( 'Insert the "var account_id" provided by VWO. It can be found in the VWO Async SmartCode.', 'concordia-core-functionality' ),
				] ),
			],
		] );

		/**
		 * Initiate the metabox
		 */
		$fm->add_meta_box( 'VWO A/B Testing', [ 'page', 'post', 'program' ] );
	}
}
