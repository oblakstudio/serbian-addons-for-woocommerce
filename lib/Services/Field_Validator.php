<?php
/**
 * Field_Validator class file.
 *
 * @package Serbian Addons for WooCommerce
 */

namespace Oblak\WCSRB\Services;

/**
 * Validates the address fields.
 *
 * @since 3.8.0
 */
class Field_Validator {
    /**
     * Whether the company checkout is active.
     *
     * @var bool
     */
    private bool $company_active;

    /**
     * Constructor
     */
    public function __construct() {
        $this->company_active = \wcsrb_can_checkout_as( 'company' );
    }

    /**
     * Validates the address fields.
     *
     * @param  array<string,array>  $fields Address fields.
     * @param  'billing'|'shipping' $type   Address type being validated.
     * @param  array<string,array>  $address Address fields.
     * @return null|array<int, array{
     *   code: string,
     *   id: string,
     *   message: string,
     * }>
     */
    public function validate_fields( array $fields, string $type, ?array $address = null ): array {
        if ( ! $this->needs_validation( $fields ) ) {
            return array();
        }

        $address ??= \WC()->countries->get_address_fields( $fields[ "{$type}_country" ], $type . '_' );
        $errors    = array();

        foreach ( $this->get_field_validators() as $key => $args ) {
            $errors[] = $this->validate_field( $fields[ $key ] ?? '', $key, $args, $address[ $key ] );

        }

        //phpcs:ignore Universal.Operators.DisallowShortTernary.Found
        return \array_filter( $errors );
    }

    /**
     * Validates the given field.
     *
     * @param  mixed  $value Field value.
     * @param  string $key   Field key.
     * @param  array  $args  Validation arguments.
     * @param  array  $field Field data.
     * @return ?array        Error data if validation fails, null otherwise.
     */
    protected function validate_field( mixed $value, string $key, array $args, array $field ): ?array {
        if ( ! $value ) {
            return array(
                'code'    => "{$key}_required",
                'id'      => $key,
                // Translators: %s: Field label.
                'message' => \sprintf( \__( '%s is a required field.', 'woocommerce' ), $field['label'] ),
            );
        }

        if ( ! $args['callback']( $value ) ) {
            return array(
                'code'    => $args['code'],
                'id'      => $key,
                'message' => $args['message'],
            );
        }

        return null;
    }

    /**
     * Returns the field validators for the given action.
     *
     * @return array<string, array{
     *   code: string,
     *   message: string,
     *   callback: callable,
     * }>
     */
    protected function get_field_validators(): array {
        $args = array(
            'billing_company' => array(
                'callback' => '__return_true',
                'code'     => 'billing_company_required',
                'message'  => \__( 'Company name is required', 'serbian-addons-for-woocommerce' ),
            ),
            'billing_mb'      => array(
                'callback' => '\Oblak\validateMB',
                'code'     => 'billing_mb_validation',
                'message'  => \__( 'Company number is invalid', 'serbian-addons-for-woocommerce' ),
            ),
            'billing_pib'     => array(
                'callback' => '\Oblak\validatePIB',
                'code'     => 'billing_pib_validation',
                'message'  => \__( 'Company Tax Number is invalid', 'serbian-addons-for-woocommerce' ),
            ),
        );

        /**
         * Returns the validation arguments for the given action.
         *
         * @param  array  $args   Validation arguments.
         * @return array
         *
         * @since 3.6.0
         */
        return \apply_filters( 'wcrs_field_validators', $args );
    }

    /**
     * Checks if the current address can be validated.
     *
     * @param  array<string,array>  $fields  Address fields.
     * @param  'billing'|'shipping' $address Address type being validated.
     * @return bool
     */
    protected function needs_validation( array $fields, string $address = 'billing' ): bool {
        return $this->company_active &&
            'billing' === $address &&
            'company' === ( $fields['billing_type'] ?? '' ) &&
            'RS' === ( $fields['billing_country'] ?? '' );
    }
}
