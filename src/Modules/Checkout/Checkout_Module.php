<?php
/**
 * Checkout_Module class file.
 *
 * @package Serbian Addons for WooCommerce
 * @subpackage Checkout
 */

namespace Oblak\WCSRB\Checkout;

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
    priority: 1,
    handlers: array(
        Handlers\Field_Admin_Handler::class,
        Handlers\Field_Customize_Handler::class,
        Handlers\Field_Display_Handler::class,
        Handlers\Field_Validation_Handler::class,
    ),
)]
class Checkout_Module {
}
