<?php
/**
 * Admin_Module class file.
 *
 * @package Serbian Addons for WooCommerce
 * @subpackage Admin
 */

namespace Oblak\WCSRB\Admin;

use Automattic\WooCommerce\Utilities\OrderUtil;
use XWP\DI\Decorators\Filter;
use XWP\DI\Decorators\Module;

/**
 * Admin module definition.
 *
 * @since 4.0.0
 */
#[Module(
    container: 'wcsrb',
    hook: 'woocommerce_loaded',
    priority: 1,
    handlers: array( Handlers\Settings_Data_Handler::class ),
)]
class Admin_Module {
    /**
     * Get the DI definitions for the module
     *
     * @return array<string,mixed>
     */
    public static function configure(): array {
        return array(
            Services\Settings_Page::class => \DI\autowire( Services\Settings_Page::class )
                ->constructor( 'wcsrb', \__( 'Serbian Addons', 'serbian-addons-for-woocommerce' ) ),
        );
    }
    /**
     * Add needed classes for WPRouter
     *
     * @param  string $classes Current classes.
     * @return string
     */
    #[Filter( tag: 'admin_body_class', priority: 9999, context: Filter::CTX_ADMIN )]
    public function add_router_classes( string $classes ): string {
        global $pagenow, $current_tab, $current_section;

        if ( 'admin.php' !== $pagenow ) {
            return $classes;
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
    #[Filter( tag: 'admin_body_class', priority: 9998, context: Filter::CTX_ADMIN )]
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
     * @param  Services\Settings_Page       $page  Plugin settings page.
     * @return array<int,\WC_Settings_Page>
     */
    #[Filter(
        tag: 'woocommerce_get_settings_pages',
        priority: 100,
        context: Filter::CTX_ADMIN,
        invoke: Filter::INV_PROXIED,
        args: 1,
        params: array( Services\Settings_Page::class ),
    )]
    public function add_settings_page( array $pages, Services\Settings_Page $page ): array {
        $pages[] = $page;

        return $pages;
    }
}
