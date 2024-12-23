<?php
/**
 * Checkout_Module class file.
 *
 * @package Serbian Addons for WooCommerce
 * @subpackage Checkout
 */

namespace Oblak\WCSRB\Checkout;

use XWC\Interfaces\Config_Repository;
use XWP\DI\Decorators\Filter;
use XWP\DI\Decorators\Module;

/**
 * Checkout module
 *
 * Encapsulates address field functionality.
 *
 * @since 4.0.0
 */
#[Module(
    container: 'wcsrb',
    hook: 'woocommerce_loaded',
    priority: 2,
    handlers: array(
        // Handlers\Field_Appearance_Handler_Block::class,
        // Handlers\Field_Customization_Handler_Block::class,
        Handlers\Field_Administration_Handler_Classic::class,
        // Handlers\Field_Customize_Handler::class,
        // Handlers\Field_Display_Handler::class,
        Handlers\Field_Validation_Handler::class,
        // Handlers\Field_Validation_Handler_Block::class,
    ),
)]
class Checkout_Module {
    /**
     * Checks if the module can be initialized.
     *
     * @param  Config_Repository $cfg Config repository instance.
     * @return bool
     */
    public static function can_initialize( Config_Repository $cfg ): bool {
        return ! $cfg->get( 'core.block_checkout', true );
    }

    /**
     * Change the body class for the checkout page.
     *
     * @param  array<string> $classes Body classes.
     * @return array<string>
     */
    #[Filter( tag: 'body_class', priority: 100, context: Filter::CTX_FRONTEND )]
    public function change_checkout_body_class( array $classes ): array {
        if ( \is_checkout() && ! \is_order_received_page() && ! \is_checkout_pay_page() ) {
            $classes[] = 'wc-classic-checkout';
        }

        return $classes;
    }
}
