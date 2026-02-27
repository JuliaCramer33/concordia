<?php
/**
 * Manage Plugin initialization
 *
 * @package concordia-core-functionality
 */

namespace CUI_Core\Autoload;

/**
 * Init
 */
class Init {
	/**
	 * Classes (namespace structure)
	 * In specific order to loop through so things load accordingly.
	 *
	 * @var array
	 */
	private $class_names = [
		// Web Services setup.
		'Includes\Web_Services\Initial_Setup',
		'Includes\Web_Services\GTM\Customizer',
		'Includes\Web_Services\GTM\Integration',
			'Includes\Web_Services\Superflow\Customizer',
			'Includes\Web_Services\Superflow\Integration',
		'Includes\Web_Services\VWO\Init',
		// Media fallbacks.
		'Includes\Media\Fallback_Featured_Image\Customizer',
		'Includes\Media\Fallback_Featured_Image\Integration',
	];

	/**
	 * Using construct method to add any actions and filters
	 */
	public function __construct() {
		$this->initiate_classes();
	}

	/**
	 * Call our classes so they are instantiated (one level)
	 *
	 * @return void
	 */
	public function initiate_classes() {
		foreach ( $this->class_names as $class_name ) {
			$full_name = 'CUI_Core\\' . $class_name;

			new $full_name();
		}
	}
}
