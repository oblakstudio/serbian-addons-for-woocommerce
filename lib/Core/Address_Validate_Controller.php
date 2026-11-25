<?php
/**
 * Address_Field_Controller class file.
 *
 * @package Serbian Addons for WooCommerce
 */

namespace Oblak\WCSRB\Core;

use Oblak\WCSRB\Services\Field_Validator;
use XWP\DI\Decorators\Action;
use XWP\DI\Decorators\Handler;

/**
 * Changes the fields on the checkout page
 *
 * @since 3.8.0
 */
#[Handler( tag: 'woocommerce_init', priority: 99, container: 'wcsrb' )]
class Address_Validate_Controller {
    /**
     * Constructor
     *
     * @param Field_Validator $validator Field validator instance.
     */
    public function __construct( protected Field_Validator $validator ) {
    }

    /**
     * Validates the saved order field.
     *
     * @param  string    $field Field name.
     * @param  mixed     $value Field value.
     * @param  \WC_Order $order Order object.
     */
    #[Action( tag: 'wcsrb_update_order_billing_field' )]
    public function validate_order_field_update( $field, $value, \WC_Order $order ) {
        // WooCommerce applies the posted address props only after all field callbacks ran, so the
        // order still holds the old country/type here - validate against the submitted ones.
        $country  = $this->posted( '_billing_country' ) ?? $order->get_billing_country( 'edit' );
        $type     = $this->posted( '_billing_type' ) ?? $order->get_meta( '_billing_type', true, 'edit' );
        $validate = array(
            'billing_country'     => $country,
            'billing_type'        => $type,
            \ltrim( $field, '_' ) => $value,
        );

        if ( $this->validator->validate( $validate, 'billing' ) ) {

            return;
        }

        $order->update_meta_data( $field, $value );
    }

    /**
     * Validates the saved address.
     *
     * @param  int    $user_id User ID.
     * @param  string $type    Address type being edited - billing or shipping.
     */
    #[Action( tag: 'woocommerce_after_save_address_validation', priority: 99 )]
    public function validate_save_address( int $user_id, string $type ) {
        foreach ( $this->validator->validate( \xwp_post_arr(), $type ) as $error ) {
            \wc_add_notice( $error['message'], 'error', array( 'id' => $error['id'] ) );
        }
    }

    /**
     * Clears the company numbers when a customer saves the billing address as a person.
     *
     * The address form only disables the fields. Runs after validation, before the customer is saved.
     *
     * @param  int          $user_id  User ID.
     * @param  string       $type     Address type being edited - billing or shipping.
     * @param  array        $address  Address fields.
     * @param  \WC_Customer $customer Customer being saved.
     *
     * @since 3.11.0
     */
    #[Action( tag: 'woocommerce_after_save_address_validation', priority: 100 )]
    public function clear_person_company_numbers( int $user_id, string $type, array $address, \WC_Customer $customer ) {
        if ( 'billing' !== $type || 'company' === \wcsrb_get_customer_type( $customer ) ) {
            return;
        }

        $customer->delete_meta_data( 'billing_mb' );
        $customer->delete_meta_data( 'billing_pib' );
    }

    /**
     * Validates the checkout fields.
     *
     * @param  array<string,array> $fields Address fields.
     * @param  \WP_Error           $error  Error object.
     */
    #[Action( 'woocommerce_after_checkout_validation', 0 )]
    public function validate_checkout( array $fields, \WP_Error $error ) {
        foreach ( $this->validator->validate( $fields, 'billing' ) as $err ) {
            $error->add( $err['code'], $err['message'], array( 'id' => $err['id'] ) );
        }
    }

    /**
     * Get a value submitted with the order edit form.
     *
     * Only called from WooCommerce's order data meta box save routine, which verified the nonce.
     *
     * @param  string $key Field key.
     * @return string|null
     */
    private function posted( string $key ): ?string {
        // phpcs:ignore WordPress.Security.NonceVerification.Missing
        return isset( $_POST[ $key ] ) ? \wc_clean( \wp_unslash( $_POST[ $key ] ) ) : null;
    }
}
