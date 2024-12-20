<?php //phpcs:disable Universal.Operators.DisallowShortTernary.Found
/**
 * Field_Validator class file.
 *
 * @package Serbian Addons for WooCommerce
 */

namespace Oblak\WCSRB\Checkout\Services;

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
     * Validation errors.
     *
     * @var array<int,array{
     *   cb_id: int,
     *   code: string,
     *   id: string,
     *   message: string
     * }>
     */
    private array $errors = array();

    /**
     * Address fields.
     *
     * @var array<string,array<string,array<string,mixed>>>
     */
    private array $address = array();

    /**
     * Field validators.
     *
     * @var array<string,array{
     *   cb_id: int,
     *   code: string,
     *   message: string,
     *   callback: callable,
     * }>
     */
    private array $validators;

    /**
     * Constructor
     */
    public function __construct() {
        $this->company_active = \wcsrb_can_checkout_as( 'company' );
    }

    /**
     * Get the address fields for the given country and type.
     *
     * @param  string $country Country code.
     * @param  string $type    Customer type.
     * @return array<string,array<string,mixed>>
     */
    private function get_address( string $country, string $type ): array {
        $this->address[ $country ] ??= array();

        return $this->address[ $country ][ $type ] ??= \WC()
            ->countries
            ->get_address_fields( $country, $type . '_' );
    }

    /**
     * Get the validators for the address fields.
     *
     * @return array<string,array{
     *   cb_id: int,
     *   code: string,
     *   message: string,
     *   callback: callable,
     * }>
     */
    public function get_validators(): array {
        if ( ! isset( $this->validators ) ) {
            $validators = array(
                'billing_company' => array(
                    'callback' => static fn( $v ) => '' !== $v,
                    'code'     => 'billing_company_required',
                    'message'  => \__( 'Company name is required', 'serbian-addons-for-woocommerce' ),
                ),
                'billing_mb'      => array(
                    'callback' => '\Oblak\validateMB',
                    'cb_id'    => 666,
                    'code'     => 'billing_mb_validation',
                    'message'  => \__( 'Company number is invalid', 'serbian-addons-for-woocommerce' ),
                ),
                'billing_pib'     => array(
                    'callback' => '\Oblak\validatePIB',
                    'cb_id'    => 667,
                    'code'     => 'billing_pib_validation',
                    'message'  => \__( 'Company Tax Number is invalid', 'serbian-addons-for-woocommerce' ),
                ),
            );

            /**
             * Returns the validation arguments for the given action.
             *
             * @param  array<string,array<string,mixed>> $validators   Validation arguments.
             * @return array<string,array<string,mixed>>
             *
             * @since 3.6.0
             * @since 4.0.0 Removed fields parameter.
             */
            $this->validators = \apply_filters( 'wcrs_field_validators', $validators );
        }

        return $this->validators;
    }

    /**
     * Get the validator for the given field.
     *
     * @param  string $field Field name.
     * @return null|array{
     *   cb_id: int,
     *   code: string,
     *   message: string,
     *   callback: callable,
     * }
     */
    public function get_validator( string $field ): ?array {
        return $this->get_validators()[ $field ] ?? null;
    }

    /**
     * Checks if there are any validation errors.
     *
     * @return bool
     */
    public function has_errors(): bool {
        return \count( $this->errors ) > 0;
    }

    /**
     * Returns the last validation error.
     *
     * @return null|array{
     *   cb_id: int,
     *   code: string,
     *   id: string,
     *   message: string,
     * }
     */
    public function last_error(): ?array {
        return $this->errors ? $this->errors[ \array_key_last( $this->errors ) ] : null;
    }

    /**
     * Returns the validation errors.
     *
     * @return array<int, array{
     *   cb_id: int,
     *   code: string,
     *   id: string,
     *   message: string,
     * }>
     */
    public function get_errors(): array {
        return \array_filter( $this->errors );
    }

    /**
     * Check if the validator can validate the given field.
     *
     * @param  string  $country Country code.
     * @param  string  $type    Customer type.
     * @param  ?string $field  Field name.
     * @return bool
     */
    public function can_validate( string $country, string $type, ?string $field = null ): bool {
        return $this->company_active &&
            'company' === $type &&
            'RS' === $country &&
            ( ! $field || null !== $this->get_validator( $field ) );
    }

    /**
     * Validates the given field.
     *
     * @param  string $field Field name.
     * @param  mixed  $value Field value.
     * @return array{}|array{
     *   cb_id: int,
     *   code: string,
     *   id: string,
     *   message: string
     * }
     */
    public function validate_field( string $field, mixed $value ): array {
        $args = $this->get_validator( $field );

        if ( ! $args ) {
            return array();
        }

        $res = $this->validate_cb( $value, $field, $args );

        if ( $res ) {
            $this->errors[] = $res;
        }

        return $res;
    }

    /**
     * Validates the address fields.
     *
     * @param  array<string,mixed>  $fields  Address fields.
     * @param  'billing'|'shipping' $type    Address type being validated.
     * @return array<int, array{
     *   cb_id: int,
     *   code: string,
     *   id: string,
     *   message: string
     * }>
     */
    public function validate_address( array $fields, string $type ): array {
        if ( ! $this->can_validate( $fields[ "{$type}_country" ], $fields[ "{$type}_type" ] ?? '' ) ) {
            return array();
        }

        foreach ( $this->get_validators() as $key => $args ) {
            $this->errors[] = $this->validate_cb( $fields[ $key ] ?? '', $key, $args );
        }

        return $this->get_errors();
    }

    /**
     * Validates the given field.
     *
     * @param  mixed  $value Field value.
     * @param  string $key   Field key.
     * @param  array  $args  Validation arguments.
     * @param  ?array $field Field data.
     */
    protected function validate_cb( mixed $value, string $key, array $args, ?array $field = null ): ?array {
        $field ??= $this->get_address( 'RS', 'billing' )[ $key ] ?? array( 'label' => $key );

        return match ( true ) {
            ! $value => array(
                'cb_id'   => $args['cb_id'] ?? 665,
                'code'    => "{$key}_required",
                'id'      => $key,
                // Translators: %s is the field label.
                'message' => \sprintf( \__( '%s is a required field.', 'woocommerce' ), $field['label'] ),
            ),
            ! $args['callback']( $value ) => array(
                'cb_id'   => $args['cb_id'] ?? 0,
                'code'    => $args['code'],
                'id'      => $key,
                'message' => $args['message'],
            ),
            default       => null,
        };
    }

    /**
     * Returns the field validators for the given action.
     *
     * @param  array<string> $fields Address field keys.
     * @return array<string, array{
     *   cb_id: int,
     *   code: string,
     *   message: string,
     *   callback: callable,
     * }>
     */
    protected function get_field_validators( array $fields ): array {
        $args = array(
            'billing_company' => array(
                'callback' => '__return_true',
                'code'     => 'billing_company_required',
                'message'  => \__( 'Company name is required', 'serbian-addons-for-woocommerce' ),
            ),
            'billing_mb'      => array(
                'callback' => '\Oblak\validateMB',
                'cb_id'    => 666,
                'code'     => 'billing_mb_validation',
                'message'  => \__( 'Company number is invalid', 'serbian-addons-for-woocommerce' ),
            ),
            'billing_pib'     => array(
                'callback' => '\Oblak\validatePIB',
                'cb_id'    => 667,
                'code'     => 'billing_pib_validation',
                'message'  => \__( 'Company Tax Number is invalid', 'serbian-addons-for-woocommerce' ),
            ),
        );
        /**
         * Returns the validation arguments for the given action.
         *
         * @param  array $args   Validation arguments.
         * @param  array $fields Address field keys.
         * @return array
         *
         * @since 3.6.0
         */
        $args = \apply_filters( 'wcrs_field_validators', $args, $fields );

        return \xwp_array_slice_assoc( $args, ...$fields );
    }
}
