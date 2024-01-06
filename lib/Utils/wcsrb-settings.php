<?php
/**
 * Settings helper functions.
 *
 * @package Serbian Addons for WooCommerce
 * @subpackage Utils
 */

namespace Oblak\WooCommerce\Serbian_Addons\Utils;

/**
 * Formats bank account select options.
 *
 * @param  array|null $opt_accounts Optional. Array of bank accounts. Defaults to null.
 * @return array
 *
 * @since 2.3.0
 */
function format_bank_account_select( $opt_accounts = null ) {
	$opt_accounts = $opt_accounts ?? WCSRB()->get_settings( 'company', 'accounts' );
	$banks        = get_serbian_banks();
	$accounts     = array( '' => __( 'Select bank account', 'serbian-addons-for-woocommerce' ) . '...' );

	foreach ( $opt_accounts as $account ) {
		$bank_name = $banks[ substr( $account, 0, 3 ) ];

		if ( ! isset( $accounts[ $bank_name ] ) ) {
			$accounts[ $bank_name ] = array();
		}

		$accounts[ $bank_name ][ $account ] = $account;
	}

    return $accounts;
}

/**
 * Formats the payment code select options.
 *
 * @return array The formatted payment code select options.
 *
 * @since 2.3.0
 */
function format_payment_code_select() {
    $options = array(
        'auto'                                            => __( 'Automatic', 'serbian-addons-for-woocommerce' ),
        __( 'Person', 'serbian-addons-for-woocommerce' )  => array(
            // Translators: %d is the payment code.
            '289' => sprintf( __( '%d - Transactions on behalf of a person', 'serbian-addons-for-woocommerce' ), 289 ),
            // Translators: %d is the payment code.
            '290' => sprintf( __( '%d - Other transactions', 'serbian-addons-for-woocommerce' ), 290 ),
        ),
        __( 'Company', 'serbian-addons-for-woocommerce' ) => array(
            // Translators: %d is the payment code.
            '220' => sprintf( __( '%d - Interim expenses', 'serbian-addons-for-woocommerce' ), 220 ),
            // Translators: %d is the payment code.
            '221' => sprintf( __( '%d - Final expenses', 'serbian-addons-for-woocommerce' ), 221 ),
        ),
    );

    switch ( WCSRB()->get_settings( 'general', 'enabled_customer_types' ) ) {
        case 'both':
            unset( $options[ __( 'Company', 'serbian-addons-for-woocommerce' ) ] );
            unset( $options[ __( 'Person', 'serbian-addons-for-woocommerce' ) ] );
            break;
        case 'person':
            unset( $options[ __( 'Company', 'serbian-addons-for-woocommerce' ) ] );
            break;
        case 'company':
            unset( $options[ __( 'Person', 'serbian-addons-for-woocommerce' ) ] );
            break;
    }

    /**
     * Filters the payment code select options
     *
     * @param array $options
     * @return array
     *
     * @since 2.3.0
     */
    return apply_filters( 'woocommerce_serbian_payment_code_select', $options );
}
