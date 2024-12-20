<?php
/**
 * Admin functions and helpers.
 *
 * @package Serbian Addons for WooCommerce
 * @subpackage Utils
 */

/**
 * Formats bank account select options.
 *
 * @param  array|null $opt_accounts Optional. Array of bank accounts. Defaults to null.
 * @return array
 *
 * @since 2.3.0
 */
function wcsrb_format_bank_account_select( $opt_accounts = null ) {
    $opt_accounts ??= xwp_app( 'wcsrb' )->get( XWC_Config::class )->get( 'company.accounts' );
    $banks          = wcsrb_get_serbian_banks();
    $accounts       = array( '' => __( 'Select bank account', 'serbian-addons-for-woocommerce' ) . '...' );

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
function wcsrb_format_payment_code_select() {
    $options = array(
        'auto'                                            => __(
            'Automatic',
            'serbian-addons-for-woocommerce',
        ),
        __( 'Company', 'serbian-addons-for-woocommerce' ) => array(
            // Translators: %d is the payment code.
            '220' => sprintf( __( '%d - Interim expenses', 'serbian-addons-for-woocommerce' ), 220 ),
            // Translators: %d is the payment code.
            '221' => sprintf( __( '%d - Final expenses', 'serbian-addons-for-woocommerce' ), 221 ),
        ),
        __( 'Person', 'serbian-addons-for-woocommerce' )  => array(
            '289' => sprintf(
                // Translators: %d is the payment code.
                __( '%d - Transactions on behalf of a person', 'serbian-addons-for-woocommerce' ),
                289,
            ),
            // Translators: %d is the payment code.
            '290' => sprintf( __( '%d - Other transactions', 'serbian-addons-for-woocommerce' ), 290 ),
        ),
    );

    switch ( xwp_app( 'wcsrb' )->get( XWC_Config::class )->get( 'core.enabled_customer_types' ) ) {
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

/**
 * Formats the display option select.
 *
 * @param  string $desc Description.
 * @return array<string, mixed>
 *
 * @since 4.0.0
 */
function wcsrb_format_gw_display_option( string $desc ): array {
    return array(
        'class'             => 'wc-enhanced-select',
        'custom_attributes' => array(
            'data-allow_clear' => 'true',
            'data-placeholder' => __( 'Select locations for display', 'serbian-addons-for-woocommerce' ),
        ),
        'default'           => array(),
        'description'       => $desc,
        'desc_tip'          => true,
        'options'           => array(
            'email' => __( 'Customer e-mails', 'serbian-addons-for-woocommerce' ),
            'order' => __( 'Store pages', 'serbian-addons-for-woocommerce' ),
        ),
        'title'             => __( 'Visibility', 'serbian-addons-for-woocommerce' ),
        'type'              => 'multiselect',
    );
}

/**
 * Formats the gateway QR image description.
 *
 * @param  int $icon The site icon ID.
 * @return string
 *
 * @since 4.0.0
 */
function wcsrb_format_gw_qr_img_desc( int $icon ): string {
    $desc = (array) sprintf(
        // translators: %1$s customizer link html.
        __( 'You can set the image via %1$s', 'serbian-addons-for-woocommerce' ),
        sprintf(
            '<a target="_blank" href="%1$s">%2$s</a> (%3$s)',
            esc_url(
                add_query_arg(
                    array( 'autofocus[section]' => 'title_tagline' ),
                    admin_url( 'customize.php' ),
                ),
            ),
            esc_html__( 'Customizer', 'default' ),
            esc_html__( 'Site Identity', 'default' ),
        ),
    );

    if ( 0 < $icon ) {
        $desc[] = sprintf(
            // translators: %s current image HTML.
            __( 'Current image: %s', 'serbian-addons-for-woocommerce' ),
            wp_get_attachment_image(
                get_option( 'site_icon' ),
                array( 16, 16 ),
                false,
            ),
        );
    }

    return implode( '<br>', $desc );
}
