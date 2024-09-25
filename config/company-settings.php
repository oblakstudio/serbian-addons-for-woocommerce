<?php //phpcs:disable SlevomatCodingStandard.Arrays.AlphabeticallySortedByKeys.IncorrectKeyOrder, SlevomatCodingStandard.Functions.RequireMultiLineCall.RequiredMultiLineCall
/**
 * Company settings array from WC Settings API.
 *
 * @package Serbian Addons for WooCommerce
 */

return array(
    array(
        'title' => \__( 'Company information', 'woocommerce' ),
        'type'  => 'title',
        'desc'  => \__( 'This is where your business is located. Tax rates and shipping rates will use this address.', 'woocommerce' ),
        'id'    => 'store_address',
    ),

    array(
        'title'    => \__( 'Business name', 'serbian-addons-for-woocommerce' ),
        'desc'     => \__( 'Name of your business', 'serbian-addons-for-woocommerce' ),
        'id'       => 'woocommerce_store_name',
        'default'  => '',
        'type'     => 'text',
        'desc_tip' => true,
    ),

    array(
        'title'    => \__( 'Address line 1', 'woocommerce' ),
        'desc'     => \__( 'The street address for your business location.', 'woocommerce' ),
        'id'       => 'woocommerce_store_address',
        'default'  => '',
        'type'     => 'text',
        'desc_tip' => true,
    ),

    array(
        'title'    => \__( 'Address line 2', 'woocommerce' ),
        'desc'     => \__( 'An additional, optional address line for your business location.', 'woocommerce' ),
        'id'       => 'woocommerce_store_address_2',
        'default'  => '',
        'type'     => 'text',
        'desc_tip' => true,
    ),

    array(
        'title'    => \__( 'City', 'woocommerce' ),
        'desc'     => \__( 'The city in which your business is located.', 'woocommerce' ),
        'id'       => 'woocommerce_store_city',
        'default'  => '',
        'type'     => 'text',
        'desc_tip' => true,
    ),

    array(
        'title'    => \__( 'Country / State', 'woocommerce' ),
        'desc'     => \__( 'The country and state or province, if any, in which your business is located.', 'woocommerce' ),
        'id'       => 'woocommerce_default_country',
        'default'  => 'US:CA',
        'type'     => 'single_select_country',
        'desc_tip' => true,
    ),

    array(
        'title'    => \__( 'Postcode / ZIP', 'woocommerce' ),
        'desc'     => \__( 'The postal code, if any, in which your business is located.', 'woocommerce' ),
        'id'       => 'woocommerce_store_postcode',
        'css'      => 'min-width:50px;',
        'default'  => '',
        'type'     => 'text',
        'desc_tip' => true,
    ),
);
