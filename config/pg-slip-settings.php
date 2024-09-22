<?php // phpcs:disable SlevomatCodingStandard.Arrays.AlphabeticallySortedByKeys.IncorrectKeyOrder, SlevomatCodingStandard.Functions.RequireMultiLineCall.RequiredMultiLineCall
/**
 * Payment_Slip_Gateway config file
 *
 * @package Serbian Addons for WooCommerce
 * @see Payment_Slip_Gateway
 */

defined( 'ABSPATH' ) || exit;

return array(
    'enabled'             => array(
        'title'   => __( 'Enable/Disable', 'serbian-addons-for-woocommerce' ),
        'label'   => __( 'Enable Payment Slip', 'serbian-addons-for-woocommerce' ),
        'type'    => 'checkbox',
        'default' => 'no',
    ),
    'title'               => array(
        'title'       => __( 'Title', 'serbian-addons-for-woocommerce' ),
        'type'        => 'text',
        'description' => __( 'This controls the title which the user sees during checkout.', 'serbian-addons-for-woocommerce' ),
        'default'     => 'Payment Slip',
        'desc_tip'    => true,
    ),
    'description'         => array(
        'title'       => __( 'Description', 'serbian-addons-for-woocommerce' ),
        'type'        => 'text',
        'description' => __( 'This controls the description which the user sees during checkout.', 'serbian-addons-for-woocommerce' ),
        'default'     => __( 'Pay by sending us money via wire transfer', 'serbian-addons-for-woocommerce' ),
        'desc_tip'    => true,
    ),

    // Basic Settings.
    'basic'               => array(
        'title'       => __( 'Slip settings', 'serbian-addons-for-woocommerce' ),
        'type'        => 'title',
        'description' => '',
    ),
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
        'default'           => static fn() => has_filter( 'woocommerce_order_number' ) ? '%order_number%' : '%order_id%-%year%',
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

    'qrcode_shown'        => array(
        'title'   => __( 'Show QR code', 'serbian-addons-for-woocommerce' ),
        'type'    => 'checkbox',
        'label'   => __( 'Show QR code on the payment slip', 'serbian-addons-for-woocommerce' ),
        'default' => 'yes',
    ),

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
        'title'       => __( 'Show image', 'serbian-addons-for-woocommerce' ),
        'type'        => 'checkbox',
        'label'       => __( 'Show image on QR code', 'serbian-addons-for-woocommerce' ),
        'default'     => 'yes',
        'desc_tip'    => __( 'Image that will be shown on the QR code. ', 'serbian-addons-for-woocommerce' ),
        'description' => static fn() => sprintf(
            // translators: %1$s opening link tag, %2$s Customizer title, %3$s closing link tag, %3$s current image HTML.
            __( 'You can set it in %1$s%2$s%3$s. Current image is: %4$s', 'serbian-addons-for-woocommerce' ),
            '<a target="_blank" href="' . admin_url( 'customize.php' ) . '">',
            __( 'Customizer', 'default' ),
            '</a>',
            wp_get_attachment_image(
                get_option( 'site_icon' ),
                array( 16, 16 ),
                false,
            ),
        ),
    ),

    // Advanced Settings.
    'advanced'            => array(
        'title'       => __( 'Advanced Settings', 'serbian-addons-for-woocommerce' ),
        'type'        => 'title',
        'description' => '',
    ),
    'debug'               => array(
        'title'       => __( 'Debug log', 'serbian-addons-for-woocommerce' ),
        'type'        => 'checkbox',
        'label'       => __( 'Enable logging', 'serbian-addons-for-woocommerce' ),
        'default'     => 'no',
        'description' => static fn() => sprintf(
            // translators: %1$s log file path, %2$s line break.
            __(
                'Log Payment Slip events, inside %1$s %2$sNote: this may log personal information. We recommend using this for debugging purposes only and deleting the logs when finished.',
                'serbian-addons-for-woocommerce',
            ),
            '<code>' . WC_Log_Handler_File::get_log_file_path( 'payment-slip' ) . '</code>',
            '<br>',
        ),
    ),

);
