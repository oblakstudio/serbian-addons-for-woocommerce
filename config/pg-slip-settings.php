<?php // phpcs:disable SlevomatCodingStandard.Arrays.AlphabeticallySortedByKeys.IncorrectKeyOrder, SlevomatCodingStandard.Functions.RequireMultiLineCall.RequiredMultiLineCall
/**
 * Payment_Slip_Gateway config file
 *
 * @package Serbian Addons for WooCommerce
 * @see Payment_Slip_Gateway
 */

use Automattic\WooCommerce\Utilities\LoggingUtil;

defined( 'ABSPATH' ) || exit;

$display_opts = static fn( $desc ) => array(
    'title'             => __( 'Visibility', 'serbian-addons-for-woocommerce' ),
    'type'              => 'multiselect',
    'options'           => array(
        'order' => __( 'Store pages', 'serbian-addons-for-woocommerce' ),
        'email' => __( 'Customer e-mails', 'serbian-addons-for-woocommerce' ),
    ),
    'default'           => array(),
    'description'       => $desc,
    'desc_tip'          => true,
    'custom_attributes' => array(
        'data-placeholder' => __( 'Select locations for display', 'serbian-addons-for-woocommerce' ),
        'data-allow_clear' => 'true',
    ),
    'class'             => 'wc-enhanced-select',
);

$qr_img_desc = static function ( int $icon ) {
    $desc = array();

    $desc[] = sprintf(
        // translators: %1$s customizer link html.
        __( 'You can set the image via %1$s', 'serbian-addons-for-woocommerce' ),
        sprintf(
            '<a target="_blank" href="%1$s">%2$s</a> (%3$s)',
            esc_url( admin_url( 'customize.php' ) ),
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
};


return array(
    // Basic Settings.
    'basic'               => array(
        'title'       => __( 'Slip settings', 'serbian-addons-for-woocommerce' ),
        'type'        => 'title',
        'description' => '',
    ),
    'display'             => $display_opts( __( 'Where to display the QR Code', 'serbian-addons-for-woocommerce' ) ),

    'style'               => array(
        'title'       => __( 'Style', 'serbian-addons-for-woocommerce' ),
        'type'        => 'select',
        'options'     => array(
            'classic' => __( 'Classic', 'serbian-addons-for-woocommerce' ),
            'modern'  => __( 'Modern', 'serbian-addons-for-woocommerce' ),
        ),
        'default'     => 'modern',
        'description' => __( 'Defines the style of the payment slip', 'serbian-addons-for-woocommerce' ),
        'desc_tip'    => true,
    ),
    'bank_account'        => array(
        'title'       => __( 'Bank account', 'serbian-addons-for-woocommerce' ),
        'type'        => 'select',
        'options'     => static fn() => wcsrb_format_bank_account_select(),
        'desc_tip'    => __( 'Bank account number', 'serbian-addons-for-woocommerce' ),
        'description' => sprintf(
            // translators: %1$s opening link tag, %2$s closing link tag.
            __( 'You can add your bank account details in the %1$sCompany settings%2$s .', 'serbian-addons-for-woocommerce' ),
            '<a href="' . admin_url( 'admin.php?page=wc-settings&tab=wcsrb&section=company' ) . '">',
            '</a>',
        ),
        'default'     => '',
    ),
    'payment_code'        => array(
        'title'             => __( 'Payment code', 'serbian-addons-for-woocommerce' ),
        'type'              => 'select',
        'options'           => static fn() => wcsrb_format_payment_code_select(),
        'default'           => 'auto',
        'description'       => __( 'You can choose a payment code only if you limit checkout to a single customer type.', 'serbian-addons-for-woocommerce' ),
        'desc_tip'          => __( 'Payment code on the payment slip', 'serbian-addons-for-woocommerce' ),
        'custom_attributes' => static fn() => array_merge(
            array(),
            1 === count( wcsrb_format_payment_code_select() ) ? array( 'readonly' => 'readonly' ) : array(),
        ),
    ),

    'payment_model'       => array(
        'title'       => __( 'Payment model', 'serbian-addons-for-woocommerce' ),
        'type'        => 'select',
        'options'     => static fn() => wcsrb_format_payment_model_select(),
        'default'     => 'auto',
        'desc_tip'    => __( 'Payment model for the payment reference', 'serbian-addons-for-woocommerce' ),
        'description' => sprintf(
            // translators: %1$s line break.
            __( 'Choosing the model 97 will automatically set the payment reference.%1$sWe recommend using model 97 because payment processor guarantees verbatim reference transfer only if it is done via model 97 ', 'serbian-addons-for-woocommerce' ),
            '<br>',
        ),
    ),

    'payment_reference'   => array(
        'title'             => __( 'Payment reference', 'serbian-addons-for-woocommerce' ),
        'type'              => 'text',
        'default'           => has_filter( 'woocommerce_order_number' ) ? '%order_number%' : '%order_id%-%year%',
        'description'       => static fn() => wcsrb_format_payment_reference_description(),
        'custom_attributes' => static fn() => array(
            'data-auto'  => has_filter( 'woocommerce_order_number' ) ? '%order_number%' : '%order_id%-%year%',
            'data-mod97' => has_filter( 'woocommerce_order_number' ) ? '%mod97%-%order_number%' : '%mod97%-%order_id%-%year%',
        ),
    ),

    'payment_purpose'     => array(
        'title'   => __( 'Payment purpose', 'serbian-addons-for-woocommerce' ),
        'type'    => 'text',
        'default' => __( 'Order payment', 'serbian-addons-for-woocommerce' ),
    ),

    'qrcode'              => array(
        'title'       => __( 'QR Code', 'serbian-addons-for-woocommerce' ),
        'type'        => 'title',
        'description' => __( 'Settings for NBS IPS QR Code', 'serbian-addons-for-woocommerce' ),
    ),

    'qrcode_shown'        => $display_opts( __( 'Where to display the payment slip', 'serbian-addons-for-woocommerce' ) ),

    'qrcode_color'        => array(
        'title'       => __( 'Dot color', 'serbian-addons-for-woocommerce' ),
        'type'        => 'color',
        'default'     => '#000',
        'description' => __( 'Color of the dots on the QR code', 'serbian-addons-for-woocommerce' ),
        'desc_tip'    => true,
    ),

    'qrcode_corner_color' => array(
        'title'       => __( 'Corner dot color', 'serbian-addons-for-woocommerce' ),
        'type'        => 'color',
        'default'     => '#000',
        'description' => __( 'Color of the corner dots on the QR code', 'serbian-addons-for-woocommerce' ),
        'desc_tip'    => true,
    ),

    'qrcode_image'        => array(
        'title'             => __( 'Show image', 'serbian-addons-for-woocommerce' ),
        'type'              => 'checkbox',
        'label'             => __( 'Show image on QR code', 'serbian-addons-for-woocommerce' ),
        'default'           => 'yes',
        'desc_tip'          => __( 'Image that will be shown on the QR code. ', 'serbian-addons-for-woocommerce' ),
        'description'       => static fn() => $qr_img_desc( intval( get_option( 'site_icon', 0 ) ) ),
        'custom_attributes' => static fn() => 0 === intval( get_option( 'site_icon', 0 ) )
            ? array( 'disabled' => 'disabled' )
            : array(),
    ),

    // Advanced Settings.
    'advanced'            => array(
        'title'       => __( 'Advanced Settings', 'serbian-addons-for-woocommerce' ),
        'type'        => 'title',
        'description' => '',
    ),
    'debug'               => array(
        'title'       => __( 'Debug log', 'woocommerce' ),
        'type'        => 'checkbox',
        'label'       => __( 'Enable logging', 'woocommerce' ),
        'default'     => 'no',
        'description' => static fn() => sprintf(
            // translators: %s is a placeholder for a URL.
            __( 'Log Payment Slip events and review them on the <a href="%s">Logs screen</a>.<br>Note: this may log personal information. We recommend using this for debugging purposes only and deleting the logs when finished.', 'serbian-addons-for-woocommerce' ),
            esc_url( LoggingUtil::get_logs_tab_url() ),
        ),
    ),

);
