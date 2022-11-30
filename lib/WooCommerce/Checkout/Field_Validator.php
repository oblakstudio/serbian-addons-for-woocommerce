<?php
/**
 * Field_Validator class file.
 *
 * @package Serbian Addons for WooCommerce
 * @subpackage WooCommerce\Checkout
 */

namespace Oblak\WCRS\WooCommerce\Checkout;

use function Oblak\validateMB;
use function Oblak\validatePIB;

/**
 * Handles checkout field validation
 */
class Field_Validator {

    /**
     * Fields to validate
     *
     * @var array
     */
    private static $fields_to_check = array(
        'billing_company',
        'billing_pib',
        'billing_mb',
    );

    /**
     * Class constructor
     */
    public function __construct() {
        add_filter( 'woocommerce_after_save_address_validation', array( $this, 'validate_saved_address' ), 99, 2 );
        add_filter( 'woocommerce_after_checkout_validation', array( $this, 'validate_checkout_fields' ), 99, 2 );
    }

    /**
     * Adds custom validation to billing address field saving
     *
     * @param  int    $user_id      Current User ID.
     * @param  string $load_address Address type being edited - billing or shipping.
     */
    public function validate_saved_address( $user_id, $load_address ) {

        if ( 'shipping' === $load_address ) {
            return;
        }

        $post_data = wc_clean( wp_unslash( $_POST ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
        $notices   = WC()->session->get( 'wc_notices', array() );
        $errors    = $notices['error'] ?? array();

        // Unset all errors if they pertain to our fields.
        foreach ( $errors as $index => $error_notice ) {
            if ( in_array( $error_notice['data']['id'], self::$fields_to_check, true ) ) {
                unset( $errors[ $index ] );
            }
        }

        // If the customer is a person, we don't need to validate anything else. Reset the notices, and bailout.
        if ( 'person' === $post_data['billing_type'] ) {
            $notices['error'] = $errors;
            WC()->session->set( 'wc_notices', $notices );

            return;
        }

        if ( ! validateMB( $post_data['billing_mb'] ) ) {
            $errors[] = array(
                'notice' => __( 'Company number is invalid', 'serbian-addons-for-woocommerce' ),
                'data'   => array(
                    'id' => 'billing_mb',
                ),
            );
        }

        if ( ! validatePIB( $post_data['billing_pib'] ) ) {
            $errors[] = array(
                'notice' => __( 'Company Tax Number is invalid', 'serbian-addons-for-woocommerce' ),
                'data'   => array(
                    'id' => 'billing_pib',
                ),
            );
        }

        $notices['error'] = $errors;

        WC()->session->set( 'wc_notices', $notices );

    }

    /**
     * Adds custom validation to billing address field saving
     *
     * @param  array     $data  Posted data.
     * @param  \WP_Error $error Error object.
     */
    public function validate_checkout_fields( $data, $error ) {
        foreach ( self::$fields_to_check as $field ) {
            if ( ! empty( $error->get_all_error_data( $field . '_required' ) ) ) {
                $error->remove( $field . '_required' );
            }
        }

        if ( 'company' !== $data['billing_type'] || 'RS' !== $data['billing_country'] ) {
            return;
        }

        if ( '' === $data['billing_company'] ) {
            $error->add( 'billing_company_required', __( 'Company name is required', 'serbian-addons-for-woocommerce' ) );
        }
        if ( ! validateMB( $data['billing_mb'] ) ) {
            $error->add( 'billing_mb_required', __( 'Company number is invalid', 'serbian-addons-for-woocommerce' ) );
        }

        if ( ! validatePIB( $data['billing_pib'] ) ) {
            $error->add( 'billing_pib_required', __( 'Company Tax Number is invalid', 'serbian-addons-for-woocommerce' ) );
        }

    }

}
