<?php
/**
 * Field_Validator class file.
 *
 * @package Serbian Addons for WooCommerce
 * @subpackage WooCommerce\Checkout
 */

namespace Oblak\WooCommerce\Serbian_Addons\Checkout;

use Oblak\WP\Decorators\Action;
use Oblak\WP\Decorators\Hookable;

/**
 * Handles checkout field validation
 */
#[Hookable( 'woocommerce_init', 99 )]
class Field_Validator {
    /**
     *  Constructor
     */
    public function __construct() {
        if ( \class_exists( '\XWP\Hook\Invoker' ) ) {
            return;
        }

        \xwp_invoke_hooked_methods( $this );
    }
    /**
     * Adds custom validation to billing address field saving
     *
     * @param  int    $user_id Current User ID.
     * @param  string $type    Address type being edited - billing or shipping.
     */
    #[Action( 'woocommerce_after_save_address_validation', 99 )]
    public function validate_saved_address( int $user_id, string $type ) {
        // phpcs:ignore WordPress.Security.NonceVerification.Missing
        $posted = \wc_clean( \wp_unslash( $_POST ) );

        // If we're not validating billing, we don't need to do anything.
        if ( ! $this->can_validate( $posted, $type ) ) {
            return;
        }

        $validators = $this->get_field_validators( \current_filter() );
        $notices    = $this->filter_notices( \array_keys( $validators ) );

        foreach ( $validators as $field => $args ) {
            if ( $args['validator']( $posted[ $field ] ) ) {
                continue;
            }

            $notices['error'][] = array(
                'data'   => array(
                    'id' => $field,
                ),
                'notice' => $args['message'],
            );
        }
        \WC()->session->set( 'wc_notices', $notices );
    }


    /**
     * Adds custom validation to billing address field saving
     *
     * @param  array     $data  Posted data.
     * @param  \WP_Error $error Error object.
     */
    #[Action( 'woocommerce_after_checkout_validation', 0 )]
    public function validate_checkout_fields( $data, $error ) {
        $fields = $this->get_field_validators( \current_filter() );

        foreach ( \array_keys( $fields ) as $field ) {
            $error->remove( $field . '_required' );
        }

        if ( ! $this->can_validate( $data ) ) {
            return;
        }

        foreach ( $fields as $field => $args ) {
            if ( $args['validator']( $data[ $field ] ) ) {
                continue;
            }

            $error->add( $args['code'], $args['message'], array( 'id' => $field ) );
        }
    }

    /**
     * Checks if the current address can be validated.
     *
     * @param  array  $fields    Address fields.
     * @param  string $addr_type Address type being validated.
     * @return bool
     */
    protected function can_validate( array $fields, string $addr_type = 'billing' ): bool {
        $type    = $fields['billing_type'] ??= '';
        $country = $fields['billing_country'] ??= '';

        return 'billing' === $addr_type && 'company' === $type && 'RS' === $country;
    }

    /**
     * Returns the field validators for the given action.
     *
     * @param  string $action Action being performed.
     * @return array
     */
    protected function get_field_validators( string $action ) {
        $args = array(
            'billing_company' => array(
                'code'      => 'billing_company_required',
                'message'   => \__( 'Company name is required', 'serbian-addons-for-woocommerce' ),
                'validator' => static fn( $val ) => '' !== $val,
            ),
            'billing_mb'      => array(
                'code'      => 'billing_mb_validation',
                'message'   => \__( 'Company number is invalid', 'serbian-addons-for-woocommerce' ),
                'validator' => '\Oblak\validateMB',
            ),
            'billing_pib'     => array(
                'code'      => 'billing_pib_validation',
                'message'   => \__( 'Company Tax Number is invalid', 'serbian-addons-for-woocommerce' ),
                'validator' => '\Oblak\validatePIB',
            ),
        );

        /**
         * Returns the validation arguments for the given action.
         *
         * @param array  $args   Validation arguments.
         * @param string $action Action being performed.
         *
         * @return array
         *
         * @since 3.6.0
         */
        return \apply_filters( 'wcrs_field_validators', $args, $action );
    }

    /**
     * Filters out notices for fields that have been validated.
     *
     * @param  array $fields Fields that have been validated.
     * @return array
     */
    protected function filter_notices( array $fields ): array {
        $notices = \WC()->session->get( 'wc_notices', array() );

        $notices['error'] = \array_filter(
            $notices['error'] ?? array(),
            static fn( $e ) => ! \in_array( $e['data']['id'] ?? '', $fields, true )
        );

        return $notices;
    }
}
