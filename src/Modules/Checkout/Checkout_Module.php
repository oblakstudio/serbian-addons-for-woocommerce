<?php
/**
 * Checkout_Module class file.
 *
 * @package Serbian Addons for WooCommerce
 * @subpackage Checkout
 */

namespace Oblak\WCSRB\Checkout;

use DI\Attribute\Inject;
use XWC\Interfaces\Config_Repository;
use XWP\DI\Decorators\Module;
use XWP\DI\Interfaces\On_Initialize;

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
    priority: 1,
    handlers: array(
        Handlers\Field_Admin_Handler::class,
        Handlers\Field_Customize_Handler::class,
        Handlers\Field_Display_Handler::class,
        Handlers\Field_Validation_Handler::class,
    ),
)]
class Checkout_Module implements On_Initialize {
    /**
     * Function to run on plugin initialization.
     *
    * @param Config_Repository|null $cfg Config repository instance.
     */
    #[Inject( array( Config_Repository::class ) )]
    public function on_initialize( ?Config_Repository $cfg = null ): void {
        $cp_id = \wc_get_page_id( 'checkout' );

        $cfg->set( 'core.block_checkout', $cp_id && \has_block( 'woocommerce/checkout', $cp_id ) );
    }
}
