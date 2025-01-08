<?php
/**
 * Field_Validate_Handler class file.
 *
 * @package Serbian Addons for WooCommerce
 * @subpackage Checkout
 */

namespace Oblak\WCSRB\Checkout\Handlers\Shared;

use Automattic\WooCommerce\Blocks\Domain\Services\CheckoutFields;
use Oblak\WCSRB\Checkout\Services\Field_Validator;
use WC_Order;
use XWP\DI\Decorators\Action;
use XWP\DI\Decorators\Filter;
use XWP\DI\Decorators\Handler;

/**
 * Handles the validation of the checkout fields.
 *
 * ! This handler is used both for the block and classic checkout.
 *
 * @since 4.0.0
 */
#[Handler( tag: 'woocommerce_init', priority: 99, container: 'wcsrb' )]
class Field_Validate_Handler {
    /**
     * Old field names mapped to new field names.
     *
     * @var array<string,string>
     */
    private array $map = array(
        'billing_company' => 'billing_company',
        'billing_country' => 'billing_country',
    );

    /**
     * Field values.
     *
     * @var array<string,string>
     */
    private array $fields;

    /**
     * Constructor
     *
     * @param Field_Validator $validator Field validator instance.
     */
    public function __construct( protected Field_Validator $validator ) {
        $pfx = CheckoutFields::get_group_key( 'billing' );

        $this->map['billing_type'] = $pfx . 'wcsrb/type';
        $this->map['billing_mb']   = $pfx . 'wcsrb/mb';
        $this->map['billing_pib']  = $pfx . 'wcsrb/pib';
    }

    /**
     * Validates the saved address.
     *
     * @param  int    $user_id User ID.
     * @param  string $type    Address type being edited - billing or shipping.
     */
    #[Action( tag: 'woocommerce_after_save_address_validation', priority: 0 )]
    public function validate_save_address( int $user_id, string $type ) {
        if ( 'billing' !== $type ) {
            return;
        }

        $this->fields = $this->validator->remap_fields( \xwp_post_arr(), $this->map );

        foreach ( $this->validator->validate_address( $this->fields, $type ) as $error ) {
            \wc_add_notice( $error['message'], 'error', array( 'id' => $error['id'] ) );
        }
    }

    /**
     * Sets the address field value to an empty string if the type is person.
     *
     * @param  mixed  $value The field value.
     * @param  string $key   The field key.
     * @return mixed
     */
    #[Filter( tag: 'woocommerce_sanitize_additional_field' )]
    public function sanitize_address_field( mixed $value, string $key ): mixed {
        if ( $this->can_sanitize( $key ) ) {
            $value = '';
        }

        return $value;
    }

    /**
     * Checks if the field can be sanitized.
     *
     * @param  string $key The field key.
     * @return bool
     */
    private function can_sanitize( string $key ): bool {
        return \doing_action( 'woocommerce_after_save_address_validation' ) &&
            isset( $this->fields ) &&
            'person' === $this->fields['billing_type'] &&
            \preg_match( '/^wcsrb\/(mb|pib)$/', $key );
    }

    /**
     * Updates a field value for a block checkout order.
     *
     * @param string   $key The field key.
     * @param mixed    $value The field value.
     * @param WC_Order $order The order to update the field for.
     */
    #[Action( tag: 'wcsrb_update_block_order_field' )]
    public function update_block_order_field( string $key, mixed $value, WC_Order $order ): void {
        [ $group, $id ] = \explode( '/', $key, 2 );

        $group = CheckoutFields::get_group_name( $group );

        if ( 'billing' !== $group ) {
            return;
        }

        $field   = \array_flip( $this->map )[ $key ] ?? $id;
        $country = $order->get_billing_country( 'edit' );
        $type    = \wcsrb_get_customer_type( $order );

        // phpcs:ignore SlevomatCodingStandard.Functions.RequireMultiLineCall.RequiredMultiLineCall
        if ( ! $this->validator->can_validate( $country, $type, $field ) || $this->validator->validate_field( $field, $value ) ) {
            return;
        }

        \wcsrb_get_cf()->persist_field_for_order( $id, $value, $order, $group, false );
    }

    /**
     * Validates the saved order field.
     *
     * @param string   $key The field key.
     * @param mixed    $value Field value.
     * @param WC_Order $order Order object.
     */
    #[Action( tag: 'wcsrb_update_classic_order_field' )]
    public function update_clasic_order_field( string $key, mixed $value, WC_Order $order ): void {
        $key = $this->map[ \ltrim( $key, '_' ) ];

        // Documented in wcsrb-entity-fns.php.
        \do_action( 'wcsrb_update_block_order_field', $key, $value, $order );
    }
}
