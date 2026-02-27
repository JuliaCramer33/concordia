<?php
/**
 * Initialize needed objects/blocks for VWO component.
 *
 * @category   Class
 * @package    concordia-core-functionality
 * @subpackage vwo
 * @author     Kanahoma
 */

namespace CUI_Core\Includes\Web_Services\VWO;

/**
 * Init
 */
class Init {
	/**
	 * Using construct to call blocks of component
	 */
	public function __construct() {
		new Customizer();
		new Metadata();
		new Integration();
	}
}
