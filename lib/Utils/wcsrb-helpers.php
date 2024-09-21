<?php
/**
 * Helper functions
 *
 * @package Serbian Addons for WooCommerce
 * @subpackage Utils
 */

/**
 * Get the entity types for the customer type field
 *
 * @return string[] Entity types
 */
function wcsrb_get_entity_types() {
    $entity_types = array(
        'company' => __( 'Company', 'serbian-addons-for-woocommerce' ),
        'person'  => __( 'Person', 'serbian-addons-for-woocommerce' ),
    );

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
 * Get the list of leading bank numbers with bank names
 *
 * @return string[] Bank numbers with bank names
 */
function wcsrb_get_serbian_banks() {
    // Translators: %s is the bank name.
    $bank_string = __( '%s Bank', 'serbian-addons-for-woocommerce' );

    return array(
		'105' => sprintf( $bank_string, __( 'AIK', 'serbian-addons-for-woocommerce' ) ),
		'115' => sprintf( $bank_string, __( 'Mobi', 'serbian-addons-for-woocommerce' ) ),
		'145' => __( 'Expobank', 'serbian-addons-for-woocommerce' ),
		'150' => sprintf( $bank_string, __( 'Direct', 'serbian-addons-for-woocommerce' ) ),
		'155' => __( 'Halkbank', 'serbian-addons-for-woocommerce' ),
		'160' => __( 'Banca Intesa', 'serbian-addons-for-woocommerce' ),
		'165' => sprintf( $bank_string, __( 'Addiko', 'serbian-addons-for-woocommerce' ) ),
		'170' => sprintf( $bank_string, __( 'UniCredit', 'serbian-addons-for-woocommerce' ) ),
		'190' => sprintf( $bank_string, __( 'Alta', 'serbian-addons-for-woocommerce' ) ),
		'200' => sprintf( $bank_string, __( 'Postal Savings', 'serbian-addons-for-woocommerce' ) ),
		'205' => sprintf( $bank_string, __( 'NLB Commercial', 'serbian-addons-for-woocommerce' ) ),
		'220' => sprintf( $bank_string, __( 'ProCredit', 'serbian-addons-for-woocommerce' ) ),
		'250' => sprintf( $bank_string, __( 'Eurobank Direct', 'serbian-addons-for-woocommerce' ) ),
		'265' => sprintf( $bank_string, __( 'Raiffeisen', 'serbian-addons-for-woocommerce' ) ),
		'275' => sprintf( $bank_string, __( 'OTP', 'serbian-addons-for-woocommerce' ) ),
		'285' => __( 'Sberbank', 'serbian-addons-for-woocommerce' ),
		'295' => sprintf( $bank_string, __( 'Serbian', 'serbian-addons-for-woocommerce' ) ),
		'310' => sprintf( $bank_string, __( 'NLB', 'serbian-addons-for-woocommerce' ) ),
		'325' => sprintf( $bank_string, __( 'Vojvodjanska', 'serbian-addons-for-woocommerce' ) ),
		'330' => sprintf( $bank_string, __( 'Credit Agricole', 'serbian-addons-for-woocommerce' ) ),
		'340' => sprintf( $bank_string, __( 'Erste', 'serbian-addons-for-woocommerce' ) ),
		'360' => sprintf( $bank_string, __( 'MTS', 'serbian-addons-for-woocommerce' ) ),
		'370' => sprintf( $bank_string, __( 'Opportunity', 'serbian-addons-for-woocommerce' ) ),
		'375' => sprintf( $bank_string, __( 'API', 'serbian-addons-for-woocommerce' ) ),
		'380' => __( 'Mirabank', 'serbian-addons-for-woocommerce' ),
		'385' => __( 'Bank of China', 'serbian-addons-for-woocommerce' ),
    );
}
