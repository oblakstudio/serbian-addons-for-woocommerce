<?php
/**
 * Addons_Page class file.
 *
 * @package Serbian Addons for WooCommerce
 * @subpackage WooCommerce\Admin\Settings
 */

namespace Oblak\WCRS\WooCommerce\Admin\Settings;

use WC_Settings_Page;

/**
 * Addons settings page
 */
class Addons_Page extends WC_Settings_Page {

    /**
     * Class Constructor
     */
    public function __construct() {
        $this->id    = 'wcsrb_addons';
        $this->label = __( 'Serbian Addons', 'serbian-addons-for-woocommerce' );

        parent::__construct();
    }
}
