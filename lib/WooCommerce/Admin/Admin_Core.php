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
        add_action( 'woocommerce_get_settings_pages', array( $this, 'add_settings_page' ) );
    }

    /**
     * Adds the custom settings page
     *
     * @param  \WC_Settings_Page[] $pages Settings pages.
     * @return \WC_Settings_Page[]        Modified Settings pages
     */
    public function add_settings_page( $pages ) {
        $pages[] = new Plugin_Settings_Page();

        return $pages;
    }
}
