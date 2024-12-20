<?php
/**
 * Checkout_Validation_Handler class file.
 *
 * @package Serbian Addons for WooCommerce
 */

namespace Oblak\WCSRB\Checkout\Handlers;

use Oblak\WCSRB\Checkout\Services\Field_Validator;
use WC_Order;
use XWP\DI\Decorators\Action;
use XWP\DI\Decorators\Handler;

/**
 * Validates the address fields.
 *
 * @since 4.0.0
 */
#[Handler( tag: 'woocommerce_init', priority: 99, container: 'wcsrb' )]
class Field_Validation_Handler {
    /**
     * Constructor
     *
     * @param Field_Validator $validator Field validator instance.
     */
    public function __construct( protected Field_Validator $validator ) {
    }

    /**
     * Validates the saved address.
     *
     * @param  int    $user_id User ID.
     * @param  string $type    Address type being edited - billing or shipping.
     */
    #[Action( tag: 'woocommerce_after_save_address_validation', priority: 99 )]
    public function validate_save_address( int $user_id, string $type ) {
        foreach ( $this->validator->validate_address( \xwp_post_arr(), $type ) as $error ) {
            \wc_add_notice( $error['message'], 'error', array( 'id' => $error['id'] ) );
        }
    }


    /**
     * Validates the checkout fields.
     *
     * @param  array<string,array> $fields Address fields.
     * @param  \WP_Error           $error  Error object.
     */
    #[Action( 'woocommerce_after_checkout_validation', 0 )]
    public function validate_checkout( array $fields, \WP_Error $error ) {
        foreach ( $this->validator->validate_address( $fields, 'billing' ) as $err ) {
            $error->add( $err['code'], $err['message'], array( 'id' => $err['id'] ) );
        }
    }

    /**
     * Validates the saved order field.
     *
     * @param  string   $field Field name.
     * @param  mixed    $value Field value.
     * @param  WC_Order $order Order object.
     */
    #[Action( tag: 'wcsrb_update_order_billing_field' )]
    public function validate_field_update( string $field, mixed $value, WC_Order $order ): void {
        $country = $order->get_billing_country( 'edit' );
        $type    = $order->get_meta( '_billing_type', true, 'edit' );

        if ( ! $this->validator->can_validate( $country, $type, $field ) ) {
            return;
        }

        if ( $this->validator->validate_field( $field, $value ) ) {
            return;
        }

        $order->update_meta_data( '_' . $field, $value );
    }

    /**
     * Validates the block checkout fields.
     *
     * @param  \WP_Error           $err    Error object.
     * @param  array<string,mixed> $fields Address fields.
     * @param  string              $group  Address group.
     */
    #[Action( tag: 'woocommerce_blocks_validate_location_address_fieldsa', priority: 1000 )]
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
}
