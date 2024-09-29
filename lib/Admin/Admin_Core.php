<?php
/**
 * Admin_Core class file.
 *
 * @package Serbian Addons for WooCommerce
 * @subpackage WooCommerce\Admin
 */

namespace Oblak\WCSRB\Admin;

use Automattic\WooCommerce\Utilities\OrderUtil;
use Oblak\WP\Abstracts\Hook_Caller;
use Oblak\WP\Decorators\Filter;
use Oblak\WP\Decorators\Hookable;

/**
 * Handles WP-Admin stuff
 */
#[Hookable( 'woocommerce_init', 99, 'is_admin' )]
class Admin_Core extends Hook_Caller {
    /**
     * Add needed classes for WPRouter
     *
     * @param  string $classes Current classes.
     * @return string          Updated classes.
     */
    #[Filter( tag: 'admin_body_class', priority: 9999 )]
    public function add_router_classes( $classes ) {
        global $pagenow, $current_tab, $current_section;

        if ( 'admin.php' !== $pagenow ) {
            return $classes;
        }

        if ( 'wcsrb' === ( $current_tab ?? '' ) && 'company' === ( $current_section ?? '' ) ) {
            $classes .= ' wcsrb-company-settings';
        }

        if ( 'checkout' === ( $current_tab ?? '' ) && 'wcsrb_payment_slip' === ( $current_section ?? '' ) ) {
            $classes .= ' wcsrb-slip-settings ';
        }

        if ( OrderUtil::is_new_order_screen() || OrderUtil::is_order_edit_screen() ) {
            $classes .= ' wcsrb-order-edit ';
        }

        return $classes;
    }

    /**
     * Adds the custom settings page
     *
     * @param  array<int,\WC_Settings_Page> $pages Settings pages.
     * @return array<int,\WC_Settings_Page>
     */
    #[Filter( tag: 'woocommerce_get_settings_pages', priority: 100 )]
    public function add_settings_page( $pages ) {
        $pages[] = new Plugin_Settings_Page();

        return $pages;
    }
}
