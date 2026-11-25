<?php
/**
 * Payment Slip utilities
 *
 * @package Serbian Addons for WooCommerce
 * @subpackage Utils
 */

/**
 * Check if the currency is RSD.
 *
 * @param  string $currency The currency.
 * @return bool
 */
function wcsrb_is_rsd( string $currency ): bool {
    $currency = trim( trim( $currency ), '.' );

    return 1 === preg_match( '/^(rsd|рсд|din|дин|dinar|динар)$/iu', $currency );
}

/**
 * Get the Payment Slip gateway.
 *
 * @return \Oblak\WCSRB\Gateway\Gateway_Payment_Slip The Payment Slip gateway.
 */
function wcsrb_slip_gw(): \Oblak\WCSRB\Gateway\Gateway_Payment_Slip {
    return xwp_app( 'wcsrb' )->get( \Oblak\WCSRB\Gateway\Gateway_Payment_Slip::class );
}

/**
 * Check if the order has a payment slip.
 *
 * @param  null|int|WC_Order $order       The order.
 * @param  bool              $unpaid_only Whether to check only for unpaid orders.
 * @return bool                           Whether the order has a payment slip.
 */
function wcsrb_order_has_slip( null|int|WC_Order $order, bool $unpaid_only = false ): bool {
    if ( ! ( $order instanceof WC_Order ) ) {
        $order = wc_get_order( $order ?? false );
    }

    return $order &&
        'wcsrb_payment_slip' === $order->get_payment_method() &&
        ( ! $unpaid_only || ! $order->is_paid() );
}

/**
 * Check if we can display the payment slip.
 *
 * @param  null|int|WC_Order $order  The order.
 * @param  'order'|'email'   $where  Where to display the payment slip.
 * @param  bool              $unpaid Whether to display only unpaid orders.
 * @return bool
 */
function wcsrb_can_display_slip( null|int|WC_Order $order, string $where, bool $unpaid = true ): bool {
    return wcsrb_order_has_slip( $order, $unpaid ) &&
        in_array( $where, wcsrb_slip_gw()->display, true );
}

/**
 * Check if we can display the QR Code.
 *
 * @param  null|int|WC_Order $order  The order.
 * @param  'order'|'email'   $where  Where to display the payment slip.
 * @param  bool              $unpaid Whether to display only unpaid orders.
 * @return bool
 */
function wcsrb_can_display_qr( null|int|WC_Order $order, string $where, bool $unpaid = true ): bool {
    return wcsrb_order_has_slip( $order, $unpaid ) &&
        in_array( $where, wcsrb_slip_gw()->qrcode_shown, true );
}

/**
 * Get the available payment models.
 *
 * @return array The available payment models.
 * @since 2.3.0
 */
function wcsrb_get_payment_models(): array {
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
function wcsrb_format_payment_model_select(): array {
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
function wcsrb_format_payment_reference_description(): string {
    $pairs = array(
        '%customer_id%'  => __( 'Customer ID', 'serbian-addons-for-woocommerce' ),
        '%day%'          => __( 'Day', 'default' ),
        '%month%'        => __( 'Month', 'default' ),
        '%order_date%'   => __( 'Order date', 'serbian-addons-for-woocommerce' ),
        '%order_id%'     => __( 'Order ID', 'serbian-addons-for-woocommerce' ),
        '%order_number%' => __( 'Order number', 'serbian-addons-for-woocommerce' ),
        '%year%'         => __( 'Year', 'default' ),
    );

    /**
     * Filters the replacement pairs for the payment reference description.
     *
     * @param  array $replacement_pairs The replacement pairs.
     * @return array
     *
     * @since 2.3.0
     */
    $pairs = apply_filters( 'woocommerce_serbian_payment_reference_replacement_pairs_select', $pairs );

    $html = __( 'Available tags:', 'default' ) . '<br>';

    foreach ( $pairs as $key => $value ) {
        $html .= sprintf(
            '<a href="#" data-code="%s" class="button button-secondary replacement" style="margin-right: 10px;">
                <span class="tips" data-tip="%s">%s</span>
            </a>',
            esc_attr( $key ),
            esc_attr( $value ),
            esc_html( $key ),
        );
    }

    return $html;
}

/**
 * Get the replacement pairs for the payment reference.
 *
 * The `%mod97%` control number is calculated from the reference the pairs are applied to, so it
 * always matches the rest of that reference (a model 97 requirement).
 *
 * @param  WC_Order $order     The order.
 * @param  string   $reference Optional. Reference template the pairs will be applied to.
 *                             Default `%order_number%%year%` (the pre 3.9.5 behaviour).
 * @return array               The replacement pairs.
 *
 * @since 3.9.5 Added the `$reference` parameter.
 */
function wcsrb_get_payment_reference_replacement_pairs( $order, string $reference = '%order_number%%year%' ): array {
    $date  = $order->get_date_created();
    $pairs = array(
        '%customer_id%'  => $order->get_customer_id(),
        '%day%'          => $date->date( 'd' ),
        '%month%'        => $date->date( 'm' ),
        '%order_date%'   => $date->date( 'd-m-Y' ),
        '%order_id%'     => $order->get_id(),
        '%order_number%' => $order->get_order_number(),
        '%year%'         => $date->date( 'Y' ),
    );

    $mod97 = static fn( array $pairs ) => wcsrb_calculate_mod97(
        strtr( str_replace( '%mod97%', '', $reference ), array_diff_key( $pairs, array( '%mod97%' => '' ) ) ),
    );

    $pairs['%mod97%'] = $mod97( $pairs );
    $control_number   = $pairs['%mod97%'];

    /**
     * Filters the replacement pairs for the payment reference.
     *
     * @param array    $pairs The replacement pairs.
     * @param WC_Order $order The order.
     * @return array
     *
     * @since 2.3.0
     */
    $pairs = apply_filters( 'woocommerce_serbian_payment_reference_replacement_pairs', $pairs, $order );

    // The filter may have changed other pairs - keep the control number in sync unless it was overridden.
    if ( ( $pairs['%mod97%'] ?? null ) === $control_number ) {
        $pairs['%mod97%'] = $mod97( $pairs );
    }

    return $pairs;
}

/**
 * Calculates the model 97 control number (ISO 7064 MOD 97-10) for a payment reference.
 *
 * Characters other than ASCII letters and digits are ignored and letters are converted to numbers
 * (A = 10 ... Z = 35). The remainder is calculated digit by digit, so references of any length work.
 *
 * @param  string $reference Payment reference without the control number.
 * @return string            Two digit control number.
 *
 * @since 3.9.5
 */
function wcsrb_calculate_mod97( string $reference ): string {
    $digits    = preg_replace_callback(
        '/[A-Z]/',
        static fn( array $m ) => (string) wcsrb_string_to_reference( $m[0] ),
        strtoupper( preg_replace( '/[^a-z0-9]/i', '', $reference ) ),
    );
    $remainder = 0;

    foreach ( str_split( $digits . '00' ) as $digit ) {
        $remainder = ( $remainder * 10 + (int) $digit ) % 97;
    }

    return str_pad( (string) ( 98 - $remainder ), 2, '0', STR_PAD_LEFT );
}

/**
 * Calculates the check digit for the given order number and year.
 *
 * @param  string $order_number Order number.
 * @param  string $order_year   Order year.
 * @return string               Check digit.
 */
function wcsrb_calculate_check_digit( $order_number, $order_year ): string {
    return wcsrb_calculate_mod97( $order_number . $order_year );
}

/**
 * Converts ASCII alphabet letters to reference numbers.
 *
 * @param  string $letter Letter.
 * @return int            Reference number.
 */
function wcsrb_string_to_reference( $letter ): int {
    return ord( strtoupper( $letter ) ) - ord( 'A' ) + 10;
}

/**
 * Calculates the reference number for the given order number.
 *
 * @param  string $order_number Order number.
 * @return int                  Reference number.
 *
 * @deprecated 3.9.5 Overflows for long references. Use wcsrb_calculate_mod97().
 */
function wcsrb_calculate_number_for_reference( $order_number ): int {
    _deprecated_function( __FUNCTION__, '3.9.5', 'wcsrb_calculate_mod97' );

    $length = strlen( $order_number );
    $result = '';

    for ( $i = 0; $i < $length; $i++ ) {
        $result .= ctype_alpha( $order_number[ $i ] )
            ? wcsrb_string_to_reference( $order_number[ $i ] )
            : $order_number[ $i ];
    }

    return (int) $result * 100;
}

/**
 * Get the IPS QR Code base directory.
 *
 * @return string The IPS QR Code base directory (`WCRS_IPS_DIR` if defined).
 */
function wcsrb_get_ips_basedir(): string {
    return xwp_app( 'wcsrb' )->get( 'ips.basedir' );
}
