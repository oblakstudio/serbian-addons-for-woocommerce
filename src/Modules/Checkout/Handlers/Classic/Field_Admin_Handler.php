<?php

namespace Oblak\WCSRB\Checkout\Handlers\Classic;

use WC_Order;
use XWC\Interfaces\Config_Repository;
use XWP\DI\Decorators\Filter;
use XWP\DI\Decorators\Handler;

#[Handler( tag: 'woocommerce_init', priority: 98, context: Handler::CTX_ADMIN, container: 'wcsrb' )]
class Field_Admin_Handler {
    private bool $block;

    /**
     * Constructor
     *
     * @param  Config_Repository $config Config Service.
     */
    public function __construct( Config_Repository $config ) {
        $this->block = $config->get( 'core.block_checkout', false );
    }

    /**
     * Adds fields to the order billing fields.
     *
     * Only for orders not created via the store API (Block Checkout).
     *
     * @param  array    $fields Order billing fields.
     * @param  WC_Order $order Order object.
     * @return array
     */
    #[Filter( tag: 'woocommerce_admin_billing_fields', priority: 99 )]
    public function add_order_billing_fields( array $fields, WC_Order $order = null ): array {
        if ( $this->block && 'store-api' === $order->get_created_via() ) {
            return $fields;
        }
        // if ( ! $order || ( 'store-api' === $order->get_created_via() && $this->block ) ) {
            // return $fields;
        // }

        $index = \array_search( 'company', \array_keys( $fields ), true );
        $data  = \wcsrb_get_company_data( $order );

        //phpcs:disable SlevomatCodingStandard.Arrays.AlphabeticallySortedByKeys.IncorrectKeyOrder
        $fields = \array_merge(
            \array_slice( $fields, 0, $index ),
            array(
                'type'    => array(
                    'label'   => \__( 'Customer type', 'serbian-addons-for-woocommerce' ),
                    'show'    => false,
                    'type'    => 'select',
                    'options' => \wcsrb_get_entity_types(),
                    'value'   => $data['type'],
                ),
                'company' => $fields['company'],

                'mb'      => array(
                    'label'           => \__( 'Company Number', 'serbian-addons-for-woocommerce' ),
                    'show'            => false,
                    'update_callback' => \wcsrb_get_update_cb( 'classic' ),
                    'value'           => $data['mb'],
                ),
                'pib'     => array(
                    'label'           => \__( 'Tax Number', 'serbian-addons-for-woocommerce' ),
                    'show'            => false,
                    'update_callback' => \wcsrb_get_update_cb( 'classic' ),
                    'wrapper_class'   => '_billing_last_name_field ',
                    'value'           => $data['pib'],
                ),

            ),
            \array_slice( $fields, $index + 1 ),
        );

        // \dump( $fields );
        // die;

        return $fields;
        //phpcs:enable SlevomatCodingStandard.Arrays.AlphabeticallySortedByKeys.IncorrectKeyOrder
    }
}
