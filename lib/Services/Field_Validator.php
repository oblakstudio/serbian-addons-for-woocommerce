<?php //phpcs:disable Universal.Operators.DisallowShortTernary.Found
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
     * Constructor
     */
    public function __construct() {
        $this->company_active = \wcsrb_can_checkout_as( 'company' );
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
        return \end( $this->errors ) ?: null;
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
        return $this->errors;
    }

    /**
     * Validates the address fields.
     *
     * @param  array<string,array>  $fields  Address fields.
     * @param  'billing'|'shipping' $type    Address type being validated.
     * @param  bool                 $force   Whether to force validation.
     * @param  array<string,array>  $address Address fields.
     * @return array<int, array{
     *   cb_id: int,
     *   code: string,
     *   id: string,
     *   message: string
     * }>
     */
    public function validate( array $fields, string $type, bool $force = false, ?array $address = null ): array {
        if ( ! $this->needs_validation( $fields, $type, $force ) ) {
            return array();
        }

        $address ??= \WC()->countries->get_address_fields( $fields[ "{$type}_country" ], $type . '_' );

        foreach ( $this->get_field_validators( \array_keys( $fields ) ) as $key => $args ) {
            $this->validate_field( $fields[ $key ] ?? '', $key, $args, $address[ $key ] );
        }

        return $this->errors;
    }

    /**
     * Validates the given field.
     *
     * @param  mixed  $value Field value.
     * @param  string $key   Field key.
     * @param  array  $args  Validation arguments.
     * @param  array  $field Field data.
     */
    protected function validate_field( mixed $value, string $key, array $args, array $field ) {
        $error = match ( true ) {
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

		if ( ! $error ) {
			return;
		}

        $this->errors[] = $error;
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

    /**
     * Checks if the current address can be validated.
     *
     * @param  array<string,mixed>  $fields  Address fields.
     * @param  'billing'|'shipping' $address Address type being validated.
     * @param  bool                 $force   Whether to force validation.
     * @return bool
     */
	protected function needs_validation( array $fields, string $address = 'billing', bool $force = false, ): bool {
		$this->errors = array();

		if ( $force ) {
			return true;
		}

		return $this->company_active &&
		'billing' === $address &&
		'company' === ( $fields['billing_type'] ?? '' ) &&
		'RS' === ( $fields['billing_country'] ?? '' );
	}
}
