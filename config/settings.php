<?php
/**
 * Plugin settings array
 *
 * @package Serbian Addons for WooCommerce
 */

use function Oblak\WCRS\Utils\get_entity_types;

return array(
    ''        => array(
        'section_name' => __( 'General', 'woocommerce' ),
        'priority'     => 10,
        'enabled'      => true,
        'fields'       => array(
            array(
                'title' => __( 'General settings', 'serbian-addons-for-woocommerce' ),
                'type'  => 'title',
                'desc'  => __( 'General settings for Serbian Addons for WooCommerce', 'serbian-addons-for-woocommerce' ),
                'id'    => 'wcsrb_general_settings',
            ),

            array(
                'title'    => __( 'Enabled customer types', 'serbian-addons-for-woocommerce' ),
                'id'       => 'enabled_customer_types',
                'type'     => 'select',
                'desc'     => __( 'Which customer types can shop on the store', 'serbian-addons-for-woocommerce' ),
                'options'  => array_merge(
                    array( 'both' => __( 'Companies and persons', 'serbian-addons-for-woocommerce' ) ),
                    get_entity_types()
                ),
                'desc_tip' => true,
                'default'  => 'both',
            ),
            array(
                'title'   => __( 'Field removal', 'serbian-addons-for-woocommerce' ),
                'id'      => 'remove_unneeded_fields',
                'type'    => 'checkbox',
                'desc'    => __( 'Remove unneeded fields from the checkout page', 'serbian-addons-for-woocommerce' ),
                'tooltip' => __( 'Removes Address 2 and State fields', 'serbian-addons-for-woocommerce' ),
            ),

            array(
                'title'   => __( 'Transliterate currency symbol', 'serbian-addons-for-woocommerce' ),
                'id'      => 'fix_currency_symbol',
                'type'    => 'checkbox',
                'desc'    => __( 'Transliterate currency symbol to latin script', 'serbian-addons-for-woocommerce' ),
                'tooltip' => __( 'By default, currency is displayed in cyrillic. This will transliterate it', 'serbian-addons-for-woocommerce' ),
                'default' => 'yes',
            ),

            array(
                'type' => 'sectionend',
                'id'   => 'wcsrb_general_settings',
            ),
        ),
    ),
    'company' => array(
        'section_name' => __( 'Company information', 'serbian-addons-for-woocommerce' ),
        'priority'     => 10,
        'enabled'      => true,
        'fields'       => array(
            array(
                'title'       => __( 'Bank accounts', 'serbian-addons-for-woocommerce' ),
                'type'        => 'repeater_text',
                'desc'        => __( 'Bank accounts of your business.', 'serbian-addons-for-woocommerce' ),
                'desc_tip'    => true,
                'placeholder' => __( 'Enter bank account', 'serbian-addons-for-woocommerce' ),
                'id'          => 'woocommerce_store_bank_accounts',
                'field_name'  => 'woocommerce_store_bank_accounts[acct][]',
            ),
        ),
    ),
);
