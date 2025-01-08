<?php
/**
 * Field_Admin_Handler class file.
 *
 * @package Serbian Addons for WooCommerce
 * @subpackage Checkout
 */

namespace Oblak\WCSRB\Checkout\Handlers\Block;

use WC_Order;
use XWC\Interfaces\Config_Repository;
use XWP\DI\Decorators\Filter;
use XWP\DI\Decorators\Handler;

/**
 * Handles admin functionality for checkout fields.
 *
 * ! This handler is used only for the block checkout.
 *
 * @since 4.0.0
 */
#[Handler( tag: 'woocommerce_init', priority: 98, context: Handler::CTX_ADMIN, container: 'wcsrb' )]
class Field_Admin_Handler {
    /**
     * Are we using the block checkout?
     *
     * @var bool
     */
    private bool $block;

    /**
     * Constructor
     *
     * @param  Config_Repository $config Config Service.
     */
    public function __construct( Config_Repository $config ) {
        $this->block = $config->get( 'core.block_checkout', false );
    }

    /**
     * Adds fields to the order billing fields.
     *
     * Only for orders not created via the store API (Block Checkout).
     *
     * @param  array    $fields Order billing fields.
     * @param  WC_Order $order Order object.
     * @return array
     */
    #[Filter( tag: 'woocommerce_admin_billing_fields', priority: 999 )]
    public function reorder_order_billing_fields( array $fields, WC_Order $order = null ): array {
        if ( ! $order || ! $this->block || 'store-api' !== $order->get_created_via() ) {
            return $fields;
        }

        $idx = \array_search( 'state', \array_keys( $fields ), true );
        $cmp = \array_search( 'company', \array_keys( $fields ), true );
        $len = 'company' === \wcsrb_get_customer_type( $order ) ? 2 : 0;

        $fields = \array_merge(
            \array_slice( $fields, 0, $cmp, true ),
            \array_slice( $fields, $idx + 1, 1, true ),
            \array_slice( $fields, $cmp, 1, true ),
            \array_map( array( $this, 'change_field' ), \array_slice( $fields, $idx + 2, $len, true ) ),
            \array_slice( $fields, $cmp + 1, $idx - $cmp, true ),
            \array_slice( $fields, $idx + 2 + $len, null, true ),
        );

        return $fields;
    }

    /**
     * Changes the field.
     *
     * @param  array<string,mixed> $field Field.
     * @return array<string,mixed>
     */
    private function change_field( array $field ): array {
        if ( ! isset( $field['id'] ) ) {
            return $field;
        }

        if ( \preg_match( '/billing\/wcsrb\/(mb|pib)$/', $field['id'] ) ) {
            $field['update_callback'] = \wcsrb_get_update_cb( 'block' );
        }

        if ( \preg_match( '/billing\/wcsrb\/pib$/', $field['id'] ) ) {
            $field['wrapper_class'] = '_billing_last_name_field ';
        }

        if ( \preg_match( '/billing\/wcsrb\/mb$/', $field['id'] ) ) {
            $field['wrapper_class'] = '';
        }

        return $field;
    }
}
