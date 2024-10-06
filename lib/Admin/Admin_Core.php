<?php
/**
 * Admin_Core class file.
 *
 * @package Serbian Addons for WooCommerce
 * @subpackage WooCommerce\Admin
 */

namespace Oblak\WCSRB\Admin;

use Automattic\WooCommerce\Utilities\OrderUtil;
use XWP\DI\Decorators\Filter;
use XWP\DI\Decorators\Handler;

/**
 * Handles WP-Admin stuff
 */
#[Handler( tag: 'woocommerce_init', priority: 99, context: Handler::CTX_ADMIN, container: 'wcsrb' )]
class Admin_Core {
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

        return $classes;
    }

    /**
     * Add needed classes for the order edit screen
     *
     * @param  string $classes Current classes.
     * @return string          Updated classes.
     */
    #[Filter( tag: 'admin_body_class', priority: 9998 )]
    public function add_order_screen_classes( string $classes ): string {
        if ( OrderUtil::is_new_order_screen() || OrderUtil::is_order_edit_screen() ) {
            $classes .= ' wcsrb-order-edit ';
        }

        return $classes;
    }

    /**
     * Adds the custom settings page
     *
     * @param  array<int,\WC_Settings_Page> $pages Settings pages.
     * @param  Plugin_Settings_Page         $page  Plugin settings page.
     * @return array<int,\WC_Settings_Page>
     */
    #[Filter(
        tag: 'woocommerce_get_settings_pages',
        priority: 100,
        invoke: Filter::INV_PROXIED,
        args: 1,
        params: array( Plugin_Settings_Page::class ),
    )]
    public function add_settings_page( array $pages, Plugin_Settings_Page $page ): array {
        $pages[] = $page;

        return $pages;
    }
}
