<?php
/**
 * Entity functions and helpers.
 *
 * @package Serbian Addons for WooCommerce
 * @subpackage Checkout
 */

use Automattic\WooCommerce\Blocks\Domain\Services\CheckoutFields;
use Automattic\WooCommerce\Blocks\Package;

/**
 * Get the entity types for the customer type field
 *
 * @return string[] Entity types
 */
function wcsrb_get_entity_types() {
    //phpcs:disable SlevomatCodingStandard.Arrays.AlphabeticallySortedByKeys.IncorrectKeyOrder
    $entity_types = array(
        'person'  => __( 'Person', 'serbian-addons-for-woocommerce' ),
        'company' => __( 'Company', 'serbian-addons-for-woocommerce' ),
    );
    //phpcs:enable SlevomatCodingStandard.Arrays.AlphabeticallySortedByKeys.IncorrectKeyOrder

    /**
     * Filters the available entity types
     *
     * @param  string[] $entity_types
     * @return string[]
     * @since 1.3.0
     */
    return apply_filters( 'woocommerce_serbian_get_entity_types', $entity_types );
}

/**
 * Checks if the customer can checkout as a given type.
 *
 * @param  'person'|'company'|'both' $type Customer type.
 * @return bool
 */
function wcsrb_can_checkout_as( string $type ): bool {
    static $types;

    $types ??= xwp_app( 'wcsrb' )
        ->get( XWC_Config::class )
        ->get( 'core.enabled_customer_types' );

    return 'both' === $types || $type === $types;
}


/**
 * Get the customer type for the given customer.
 *
 * @param  WC_Order|WC_Customer $target Customer ID or object.
 * @return 'person'|'company'
 */
function wcsrb_get_customer_type( WC_Order|WC_Customer $target ): string {
    $type = wcsrb_get_cf()->get_field_from_object( 'wcsrb/type', $target, 'billing' );

    //phpcs:ignore Universal.Operators.DisallowShortTernary.Found
    return $type ?? 'person' ?: 'person';
}

/**
 * Get the company data for the given customer.
 *
 * @param  WC_Order|WC_Customer $target Customer ID or object.
 * @return array{mb: string, pib: string, type: 'company'|'person'}
 */
function wcsrb_get_company_data( WC_Order|WC_Customer $target ): array {
    return array(
        'mb'   => wcsrb_get_cf()->get_field_from_object( 'wcsrb/mb', $target, 'billing' ),
        'pib'  => wcsrb_get_cf()->get_field_from_object( 'wcsrb/pib', $target, 'billing' ),
        'type' => wcsrb_get_customer_type( $target ),
    );
}

/**
 * Gets the company address fields.
 *
 * @return array
 *
 * @since 3.8.0
 */
function wcsrb_get_company_fields(): array {
    $fields = array(
        'mb'  => array(
            'class'    => array( 'form-row-first', 'address-field', 'entity-type-toggle', 'shown' ),
            'label'    => \__( 'Company Number', 'serbian-addons-for-woocommerce' ),
            'priority' => 31,
            'type'     => 'text',
            'validate' => array( 'mb' ),
        ),
        'pib' => array(
            'class'    => array( 'form-row-last', 'address-field', 'entity-type-toggle', 'shown' ),
            'label'    => \__( 'Tax Number', 'serbian-addons-for-woocommerce' ),
            'priority' => 32,
            'type'     => 'text',
            'validate' => array( 'pib' ),
        ),
    );

    /**
     * Filters the company address fields.
     *
     * @param  array $fields Company address fields.
     * @return array
     *
     * @since 3.8.0
     */
    return apply_filters( 'wcsrb_company_address_fields', $fields );
}

/**
 * Get the checkout fields instance.
 *
 * @return CheckoutFields
 */
function wcsrb_get_cf(): CheckoutFields {
    /**
     * Variable to hold the container instance.
     *
     * @var Automattic\WooCommerce\Blocks\Registry\Container $ctr
     */
    $ctr = Package::container();

    return $ctr->get( CheckoutFields::class );
}

/**
 * Get the update callback function for the order billing fields.
 *
 * @param 'block'|'classic' $type Field type.
 * @return callable
 */
function wcsrb_get_update_cb( string $type ): callable {
    /**
     * Callback to validate and update the order billing field.
     *
     * @param  string    $id Field ID.
     * @param  mixed     $v  Field value.
     * @param  \WC_Order $o  Order object.
     *
     * @since 3.8.0
     */
    return static fn( $k, $v, $o ) => do_action( "wcsrb_update_{$type}_order_field", $k, $v, $o );
}
