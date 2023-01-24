<?php
/**
 * Dynamic fields for payments gateways
 *
 * @package Serbian Addons for WooCommerce
 * @subpackage Config
 */

defined( 'ABSPATH' ) || exit;

return array(
    array(
        'title' => __( 'Settings', 'woocommerce' ) . ' - {{GWNAME}}',
        'type'  => 'title',
        'id'    => 'wcsrb_{{GWSLUG}}_options',
    ),
    array(
        'title'   => __( 'Payment type', 'serbian-addons-for-woocommerce' ),
        'id'      => '{{GWSLUG}}-payment_type',
        'type'    => 'select',
        'options' => array(
            '0' => __( 'Other', 'serbian-addons-for-woocommerce' ),
            '1' => __( 'Cash', 'serbian-addons-for-woocommerce' ),
            '2' => __( 'Payment card', 'serbian-addons-for-woocommerce' ),
            '3' => __( 'Check', 'serbian-addons-for-woocommerce' ),
            '4' => __( 'Wire transfer', 'serbian-addons-for-woocommerce' ),
            '5' => __( 'Voucher', 'serbian-addons-for-woocommerce' ),
            '6' => __( 'Mobile Money', 'serbian-addons-for-woocommerce' ),
        ),
        'default' => '0',
    ),
    array(
        'title'    => __( 'Journal order status', 'serbian-addons-for-woocommerce' ),
        'id'       => '{{GWSLUG}}-journal_order_status',
        'type'     => 'select',
        'options'  => wc_get_order_statuses(),
        'default'  => 'wc-processing',
        'desc'     => __( 'Journals will be sent to the eFiscalization API when the order status is set to this value.', 'serbian-addons-for-woocommerce' ),
        'desc_tip' => true,
    ),
    array(
        'type' => 'sectionend',
        'id'   => 'wcsrb_{{GWSLUG}}_options',
    ),
);
