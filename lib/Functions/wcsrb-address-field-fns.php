<?php
/**
 * Address field functions.
 *
 * @package Serbian Addons for WooCommerce
 */

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
            'class'       => array( 'form-row-first', 'address-field', 'entity-type-toggle', 'shown' ),
            'label'       => \__( 'Company Number', 'serbian-addons-for-woocommerce' ),
            'placeholder' => \__( 'Enter MB', 'serbian-addons-for-woocommerce' ),
            'priority'    => 31,
            'type'        => 'text',
            'validate'    => array( 'mb' ),
        ),
        'pib' => array(
            'class'       => array( 'form-row-last', 'address-field', 'entity-type-toggle', 'shown' ),
            'label'       => \__( 'Tax Number', 'serbian-addons-for-woocommerce' ),
            'placeholder' => \__( 'Enter PIB', 'serbian-addons-for-woocommerce' ),
            'priority'    => 32,
            'type'        => 'text',
            'validate'    => array( 'pib' ),
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
 * Checks if the customer can checkout as a given type.
 *
 * @param  'person'|'company'|'both' $type Customer type.
 * @return bool
 */
function wcsrb_can_checkout_as( string $type ): bool {
    static $types;

    $types ??= WCSRB()->get_settings( 'core', 'enabled_customer_types' );

    return 'both' === $types || $type === $types;
}


/**
 * Get the customer type for the given customer.
 *
 * @param  WC_Order|WC_Customer $target Customer ID or object.
 * @return 'person'|'company'
 */
function wcsrb_get_customer_type( WC_Order|WC_Customer $target ): string {
    $key = $target instanceof WC_Order ? '_billing_type' : 'billing_type';

    // phpcs:ignore Universal
    return $target->get_meta( $key, true ) ?: 'person';
}

/**
 * Get the company data for the given customer.
 *
 * @param  WC_Order|WC_Customer $target Customer ID or object.
 * @return false|array{mb: string, pib: string, type: 'company'|'person'}
 */
function wcsrb_get_company_data( WC_Order|WC_Customer $target ): bool|array {
    if ( 'company' !== wcsrb_get_customer_type( $target ) ) {
        return array(
            'mb'   => '',
            'pib'  => '',
            'type' => 'person',
        );
    }

    $key = $target instanceof WC_Order ? '_billing' : 'billing';

    return array(
        'mb'   => $target->get_meta( "{$key}_mb", true ),
        'pib'  => $target->get_meta( "{$key}_pib", true ),
        'type' => 'company',
    );
}
