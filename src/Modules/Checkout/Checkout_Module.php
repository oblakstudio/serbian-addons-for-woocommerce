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
        // Block checkout.
        Handlers\Block\Field_Admin_Handler::class,
        Handlers\Block\Field_Customize_Handler::class,
        Handlers\Block\Field_Validate_Handler::class,

        // Classic checkout.
        Handlers\Classic\Field_Admin_Handler::class,
        Handlers\Classic\Field_Customize_Handler::class,
        Handlers\Classic\Field_Validate_Handler::class,

        // Block + Classic checkout.
        Handlers\Shared\Field_Admin_Handler::class,
        Handlers\Shared\Field_Customize_Handler::class,
        Handlers\Shared\Field_Validate_Handler::class,
    ),
)]
class Checkout_Module {
    /**
     * Are we using block checkout?
     *
     * @var bool
     */
    private bool $block;

    /**
     * Constructor
     *
     * @param Config_Repository $cfg Config repository instance.
     */
    public function __construct( Config_Repository $cfg ) {
        $this->block = $cfg->get( 'core.block_checkout', true );
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
            $classes[] = $this->block ? 'wc-block-checkout' : 'wc-classic-checkout';
        }

        return $classes;
    }
}
