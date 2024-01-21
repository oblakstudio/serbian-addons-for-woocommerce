<?php
/**
 * Payment Slip utilities
 *
 * @package Serbian Addons for WooCommerce
 * @subpackage Utils
 */

namespace Oblak\WooCommerce\Serbian_Addons\Utils;

use WC_Order;

/**
 * Get the available payment models.
 *
 * @return array The available payment models.
 * @since 2.3.0
 */
function get_payment_models(): array {
    $models = array(
        'auto'  => '',
        'mod97' => '97',
    );

    /**
     * Filters the available payment models,
     *
     * @param array $models The available payment models.
     * @return array
     *
     * @since 2.3.0
     */
    return apply_filters( 'woocommerce_serbian_get_payment_models', $models );
}

/**
 * Format the payment model select options.
 *
 * @return array The formatted payment model select options.
 * @since 2.3.0
 */
function format_payment_model_select(): array {
    $payment_models = array(
        'auto'  => __( 'Automatic', 'serbian-addons-for-woocommerce' ),
        'mod97' => __( 'Model 97', 'serbian-addons-for-woocommerce' ),
    );

    /**
     * Filters the payment model select options
     *
     * @param array $payment_models
     * @return array
     *
     * @since 2.3.0
     */
    return apply_filters( 'woocommerce_serbian_payment_model_select', $payment_models );
}

/**
 * Formats the payment reference description.
 *
 * We need a custom formatter because most of the ouput is HTML.
 *
 * @return string The formatted payment reference description.
 */
function format_payment_reference_description(): string {
    $replacement_pairs = array(
        '%order_id%'     => __( 'Order ID', 'serbian-addons-for-woocommerce' ),
        '%order_number%' => __( 'Order number', 'serbian-addons-for-woocommerce' ),
        '%order_date%'   => __( 'Order date', 'serbian-addons-for-woocommerce' ),
        '%year%'         => __( 'Year', 'default' ),
        '%month%'        => __( 'Month', 'default' ),
        '%day%'          => __( 'Day', 'default' ),
        '%customer_id%'  => __( 'Customer ID', 'serbian-addons-for-woocommerce' ),
    );

    /**
     * Filters the replacement pairs for the payment reference description.
     *
     * @param array $replacement_pairs
     * @return array
     *
     * @since 2.3.0
     */
    $replacement_pairs = apply_filters( 'woocommerce_serbian_payment_reference_replacement_pairs_select', $replacement_pairs );

    $html = __( 'Available tags:', 'default' ) . '<br>';

    foreach ( $replacement_pairs as $key => $value ) {
        $html .= sprintf(
            '<a href="#" data-code="%s" class="button button-secondary replacement" style="margin-right: 10px;">
                <span class="tips" data-tip="%s">%s</span>
            </a>',
            $key,
            $value,
            $key,
        );
    }

    return $html;
}

/**
 * Get the replacement pairs for the payment reference.
 *
 * @param WC_Order $order The order.
 * @return array          The replacement pairs.
 */
function get_payment_reference_replacement_pairs( $order ): array {
    $pairs = array(
		'%order_id%'     => $order->get_id(),
        '%order_number%' => $order->get_order_number(),
        '%order_date%'   => $order->get_date_created()->date( 'd-m-Y' ),
        '%year%'         => $order->get_date_created()->date( 'Y' ),
        '%month%'        => $order->get_date_created()->date( 'm' ),
        '%day%'          => $order->get_date_created()->date( 'd' ),
        '%customer_id%'  => $order->get_customer_id() ?? 0,
        '%mod97%'        => calculate_check_digit(
            (string) $order->get_order_number(),
            (string) $order->get_date_created()->date( 'Y' )
        ),
    );

    /**
     * Filters the replacement pairs for the payment reference.
     *
     * @param array    $pairs The replacement pairs.
     * @param WC_Order $order The order.
     * @return array
     *
     * @since 2.3.0
     */
    return apply_filters( 'woocommerce_serbian_payment_reference_replacement_pairs', $pairs, $order );
}

/**
 * Calculates the check digit for the given order number and year.
 *
 * @param  string $order_number Order number.
 * @param  string $order_year   Order year.
 * @return string               Check digit.
 */
function calculate_check_digit( $order_number, $order_year ): string {
    $number = (int) calculate_number_for_reference( $order_number . $order_year );

    $remainder = $number % 97;

    return str_pad( 98 - $remainder, 2, '0', STR_PAD_LEFT );
}

/**
 * Converts ASCII alphabet letters to reference numbers.
 *
 * @param  string $letter Letter.
 * @return int            Reference number.
 */
function string_to_reference( $letter ): int {
    return ord( strtoupper( $letter ) ) - ord( 'A' ) + 10;
}

/**
 * Calculates the reference number for the given order number.
 *
 * @param  string $order_number Order number.
 * @return int                  Reference number.
 */
function calculate_number_for_reference( $order_number ): int {
    $length = strlen( $order_number );
    $result = '';
    for ( $i = 0; $i < $length; $i++ ) {
		if ( ctype_alpha( $order_number[ $i ] ) ) {
			$result .= string_to_reference( $order_number[ $i ] );
			continue;
		}

        $result .= $order_number[ $i ];
    }
    return (int) $result * 100;
}

/**
 * Get the IPS QR Code base directory.
 *
 * @return string The IPS QR Code base directory.
 */
function get_ips_basedir(): string {
    return wp_upload_dir()['basedir'] . '/wcrs-ips';
}
