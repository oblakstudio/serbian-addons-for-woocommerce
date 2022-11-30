<?php
/**
 * Admin_Core class file.
 *
 * @package Serbian Addons for WooCommerce
 * @subpackage WooCommerce\Admin
 */

namespace Oblak\WCRS\WooCommerce\Admin;

/**
 * Handles WP-Admin stuff
 */
class Admin_Core {

    /**
     * Class constructor
     */
    public function __construct() {
        add_action( 'init', array( $this, 'init_classes' ) );
    }

    /**
     * Load needed classes on init
     */
    public function init_classes() {
        new Settings_General();
    }
}
