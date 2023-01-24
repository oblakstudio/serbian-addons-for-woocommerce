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
        add_filter( 'woocommerce_get_settings_pages', array( $this, 'add_settings_pages' ), 150 );
    }

    /**
     * Load needed classes on init
     */
    public function init_classes() {
        new Settings_General();
        new Settings\Custom_Fields();
    }

    /**
     * Adds the settings page to WooCommerce settings
     *
     * @param  array $settings Settings pages.
     * @return array           Modified settings pages.
     */
    public function add_settings_pages( $settings ) {
        $settings = array_merge(
            $settings,
            array(
                new Settings\Addons_Page(),
                new Settings\Fiscalization_Page(),
            )
        );

        return $settings;
    }
}
