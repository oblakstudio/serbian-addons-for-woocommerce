<?php
/**
 * Fiscalization settings array
 *
 * @package Serbian Addons for WooCommerce
 * @subpackage Config
 */

use function Oblak\WCRS\Utils\format_tax_rate_select;

defined( 'ABSPATH' ) || exit;

return array(
    // General settings.
    ''         => array(
        'section_name' => __( 'General', 'woocommerce' ),
        'priority'     => 0,
        'enabled'      => true,
        'fields'       => array(
            // Module settings.
            array(
                'title' => __( 'Module settings', 'serbian-addons-for-woocommerce' ),
                'type'  => 'title',
                'id'    => 'wcsrb_module_options',
            ),
            array(
                'title'   => __( 'Enable eFiscalization', 'serbian-addons-for-woocommerce' ),
                'id'      => 'enable_efiscalization',
                'type'    => 'checkbox',
                'default' => 'no',
                'desc'    => __( 'Enables eFiscalization for this website', 'serbian-addons-for-woocommerce' ),
            ),
            array(
                'type' => 'sectionend',
                'id'   => 'wcsrb_module_options',
            ),

            // Connection status.
            array(
                'title' => __( 'Connection status', 'serbian-addons-for-woocommerce' ),
                'type'  => 'title',
                'id'    => 'wcsrb_connection_options',
            ),
            array(
                'title'   => 'WakaWaka',
                'id'      => 'wcsrb_connection_status',
                'type'    => 'wcsrb_fiscalization_connection_status',
                'default' => 'online',
            ),
            array(
                'type' => 'sectionend',
                'id'   => 'wcsrb_connection_options',
            ),

            // Api Parameters.
            array(
                'title' => __( 'API Parameters', 'serbian-addons-for-woocommerce' ),
                'type'  => 'title',
                'id'    => 'wcsrb_api_options',
            ),
            array(
                'title'    => __( 'API Username', 'serbian-addons-for-woocommerce' ),
                'id'       => 'api_username',
                'default'  => '',
                'constant' => 'EFIS_API_USER',
                'core'     => 'EFIS_ON',
                'type'     => 'wcsrb_fiscalization_constant_field',
            ),
            array(
                'title'    => __( 'API Key', 'serbian-addons-for-woocommerce' ),
                'id'       => 'api_key',
                'default'  => '',
                'constant' => 'EFIS_API_KEY',
                'core'     => 'EFIS_ON',
                'type'     => 'wcsrb_fiscalization_constant_field',
            ),
            array(
                'title'   => __( 'Language', 'serbian-addons-for-woocommerce' ),
                'id'      => 'language',
                'type'    => 'select',
                'class'   => 'short',
                'options' => array(
                    'sr-Cyrl-RS' => __( 'Serbian', 'serbian-addons-for-woocommerce' ),
                    'en-US'      => __( 'English', 'serbian-addons-for-woocommerce' ),
                ),
                'desc'    => __( 'Language for eFiscalization', 'serbian-addons-for-woocommerce' ),
            ),
            array(
                'type' => 'sectionend',
                'id'   => 'wcsrb_api_options',
            ),

            // Journal settings.
            array(
                'title' => __( 'Journal options', 'serbian-addons-for-woocommerce' ),
                'type'  => 'title',
                'id'    => 'wcsrb_journal_options',
            ),
            array(
                'title'   => __( 'Generate QR code', 'serbian-addons-for-woocommerce' ),
                'id'      => 'generate_qr_code',
                'type'    => 'checkbox',
                'desc'    => __( 'Include QR code in generated journal', 'serbian-addons-for-woocommerce' ),
                'default' => 'yes',
            ),
            array(
                'title'    => __( 'Debug log', 'serbian-addons-for-woocommerce' ),
                'id'       => 'debug',
                'type'     => 'checkbox',
                'desc'     => __( 'Enable logging', 'serbian-addons-for-woocommerce' ),
                'default'  => 'no',
                'desc_tip' => sprintf(
                    // translators: %s log file path.
                    __(
                        'Log fiscalization events, inside %s Note: this may log personal information. We recommend using this for debugging purposes only and deleting the logs when finished.',
                        'serbian-addons-for-woocommerce'
                    ),
                    '<code>' . WC_Log_Handler_File::get_log_file_path( 'efis' ) . '</code><br>'
                ),
            ),
            array(
                'type' => 'sectionend',
                'id'   => 'wcsrb_journal_options',
            ),
        ),
    ),
    // Tax settings.
    'taxes'    => array(
        'section_name' => __( 'Taxes', 'woocommerce' ),
        'priority'     => 10,
        'enabled'      => true,
        'fields'       => array(
            array(
                'title' => __( 'Available tax rates', 'serbian-addons-for-woocommerce' ),
                'type'  => 'title',
                'id'    => 'wcsrb_taxrate_options',
            ),
            array(
                'title'   => __( 'Tax rate table', 'serbian-addons-for-woocommerce' ),
                'id'      => 'taxrate_table',
                'type'    => 'wcsrb_fiscalization_tax_rates',
                'default' => '',
            ),
            array(
                'type' => 'sectionend',
                'id'   => 'wcsrb_taxrate_options',
            ),

            array(
                'title' => __( 'Tax options', 'serbian-addons-for-woocommerce' ),
                'type'  => 'title',
                'id'    => 'wcsrb_tax_options',
            ),
            array(
                'title'   => __( 'Tax rate', 'serbian-addons-for-woocommerce' ),
                'id'      => 'tax_rate',
                'type'    => 'select',
                'class'   => 'short',
                'options' => format_tax_rate_select(),
                'desc'    => __( 'Tax rate for eFiscalization', 'serbian-addons-for-woocommerce' ),
                'default' => '',
            ),
            array(
                'type' => 'sectionend',
                'id'   => 'wcsrb_tax_options',
            ),
        ),

    ),
    // Payment settings.
    'payments' => array(
        'section_name' => __( 'Payments', 'woocommerce' ),
        'priority'     => 20,
        'enabled'      => true,
        'fields'       => array(
            array(
                'title' => __( 'Shipping options', 'serbian-addons-for-woocommerce' ),
                'type'  => 'title',
                'id'    => 'wcsrb_shipping_options',
            ),
            array(
                'title'   => __( 'Shipping', 'serbian-addons-for-woocommerce' ),
                'id'      => 'include_shipping',
                'type'    => 'checkbox',
                'desc'    => __( 'Include shipping in journal', 'serbian-addons-for-woocommerce' ),
                'default' => 'no',
            ),
            array(
                'title'    => __( 'Shipping item name', 'serbian-addons-for-woocommerce' ),
                'id'       => 'shipping_item_name',
                'type'     => 'text',
                'desc'     => __( 'Name of the shipping item in journal', 'serbian-addons-for-woocommerce' ),
                'default'  => '',
                'desc_tip' => true,
            ),
            array(
                'type' => 'sectionend',
                'id'   => 'wcsrb_shipping_options',
            ),
        ),

    ),
);

