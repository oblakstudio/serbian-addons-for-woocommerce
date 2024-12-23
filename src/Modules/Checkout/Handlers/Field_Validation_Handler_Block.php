<?php
/**
 * Checkout_Validation_Handler class file.
 *
 * @package Serbian Addons for WooCommerce
 */

namespace Oblak\WCSRB\Checkout\Handlers;

use Oblak\WCSRB\Checkout\Services\Field_Validator;
use WC_Order;
use XWC\Interfaces\Config_Repository;
use XWP\DI\Decorators\Action;
use XWP\DI\Decorators\Handler;

/**
 * Validates the address fields.
 *
 * @since 4.0.0
 */
#[Handler( tag: 'woocommerce_init', priority: 99, container: 'wcsrb' )]
class Field_Validation_Handler_Block {
    /**
     * Checks if the module can be initialized.
     *
     * @param  Config_Repository $cfg Config repository instance.
     * @return bool
     */
    public static function can_initialize( Config_Repository $cfg ): bool {
        return $cfg->get( 'core.block_checkout', true );
    }

    /**
     * Constructor
     *
     * @param Field_Validator $validator Field validator instance.
     */
    public function __construct( protected Field_Validator $validator ) {
    }

    /**
     * Validates the block checkout fields.
     *
     * @param  \WP_Error           $err    Error object.
     * @param  array<string,mixed> $fields Address fields.
     * @param  string              $group  Address group.
     */
    #[Action( tag: 'woocommerce_blocks_validate_location_address_fields', priority: 1000 )]
    public function validate_block_checkout( \WP_Error $err, array $fields, string $group ): void {
        $fields = array(
            'billing_company' => $fields['company'],
            'billing_country' => $fields['country'],
            'billing_mb'      => $fields['wcsrb/mb'],
            'billing_pib'     => $fields['wcsrb/pib'],
            'billing_type'    => $fields['wcsrb/type'],
        );

        foreach ( $this->validator->validate_address( $fields, $group ) as $error ) {
            $err->add( $error['code'], $error['message'], array( 'id' => $error['id'] ) );
        }
    }

    #[Action( tag: 'woocommerce_store_api_checkout_order_processed', priority: 10 )]
    public function maybe_remove_company_data( WC_Order $order ): void {
        if ( 'company' === \wcsrb_get_cf()->get_field_from_object( 'wcsrb/type', $order, 'billing' ) ) {
            return;
        }

        $order->set_billing_company( '' );
        $order->update_meta_data( '_wcbilling/wcsrb/mb', '' );
        $order->update_meta_data( '_wcbilling/wcsrb/pib', '' );

        $order->save();

        if ( ! $order->get_customer_id() ) {
            return;
        }

        $customer = new \WC_Customer( $order->get_customer_id() );

        $customer->set_billing_company( '' );
        $customer->update_meta_data( '_wcbilling/wcsrb/mb', '' );
        $customer->update_meta_data( '_wcbilling/wcsrb/pib', '' );
        $customer->save();
    }
}
