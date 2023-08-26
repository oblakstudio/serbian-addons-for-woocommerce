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
        add_filter( 'admin_body_class', array( $this, 'add_router_classes' ), 9999 );
    }

    /**
     * Add needed classes for WPRouter
     *
     * @param  string $classes Current classes.
     * @return string          Updated classes.
     */
    public function add_router_classes( $classes ) {
        global $pagenow;

        $tab     = wc_clean( wp_unslash( $_GET['tab'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $section = wc_clean( wp_unslash( $_GET['section'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

        if ( 'admin.php' !== $pagenow ) {
            return $classes;
        }

        if ( 'wcsrb' === $tab && 'company' === $section ) {
            $classes .= ' wcsrb-company-settings';
        }

        if ( 'checkout' === $tab ?? '' && 'wcsrb_payment_slip' === $section ) {
            $classes .= ' wcsrb-slip-settings ';
        }

        return $classes;
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
