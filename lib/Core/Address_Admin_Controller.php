<?php //phpcs:disable Universal.Operators.DisallowShortTernary.Found

namespace Oblak\WCSRB\Core;

use Oblak\WCSRB\Services\Field_Validator;
use WC_Order;
use XWP\DI\Decorators\Filter;
use XWP\DI\Decorators\Handler;

/**
 * Changes the fields on the checkout page
 *
 * @since 3.8.0
 */
#[Handler( tag: 'woocommerce_init', priority: 99, context: Handler::CTX_ADMIN, container: 'wcsrb' )]
class Address_Admin_Controller {
    /**
     * Constructor
     *
     * @param Field_Validator $validator Field validator instance.
     */
    public function __construct( protected Field_Validator $validator ) {
    }

    /**
     * Adds the invalid field messages to the order update messages.
     *
     * @param  array $msg Order update messages.
     * @return array      Modified order update messages.
     */
    #[Filter( tag: 'woocommerce_order_updated_messages', priority: 99 )]
    public function add_order_update_message( $msg ): array {
        $msg['shop_order'][665] = \__( 'Field is required', 'serbian-addons-for-woocommerce' );
        $msg['shop_order'][666] = \__( 'Company Tax Number is invalid', 'serbian-addons-for-woocommerce' );
        $msg['shop_order'][667] = \__( 'Company Number is invalid', 'serbian-addons-for-woocommerce' );

        return $msg;
    }

    /**
     * Change the order update redirect URL to include the error message.
     *
     * @param  string $r Redirect URL.
     * @return string
     */
    #[Filter( tag: 'woocommerce_redirect_order_location', priority: 99 )]
    public function modify_order_update_redirect_url( string $r ): string {
        if ( $this->validator->has_errors() ) {
            $r = \add_query_arg( 'message', $this->validator->last_error()['cb_id'], $r );
        }

        return $r;
    }

    /**
     * Adds the company information to the customer meta fields.
     *
     * @param  array $fields Customer meta fields.
     * @return array         Modified customer meta fields
     */
    #[Filter( 'woocommerce_customer_meta_fields' )]
    public function modify_customer_meta_fields( array $fields ): array {
        $company = \array_search( 'billing_company', \array_keys( $fields['billing']['fields'] ), true );

        //phpcs:disable SlevomatCodingStandard.Arrays.AlphabeticallySortedByKeys.IncorrectKeyOrder
        $fields['billing']['fields'] = \array_merge(
            \array_slice( $fields['billing']['fields'], 0, $company ),
            array(
                'billing_type'    => array(
                    'description' => '',
                    'label'       => \__( 'Customer type', 'serbian-addons-for-woocommerce' ),
                    'options'     => \wcsrb_get_entity_types(),
                    'type'        => 'select',
                ),
                'billing_company' => $fields['billing']['fields']['billing_company'],
                'billing_mb'      => array(
                    'description' => '',
                    'label'       => \__( 'Company Number', 'serbian-addons-for-woocommerce' ),
                    'type'        => 'text',
                ),
                'billing_pib'     => array(
                    'description' => '',
                    'label'       => \__( 'Tax Number', 'serbian-addons-for-woocommerce' ),
                    'type'        => 'text',
                ),

            ),
            \array_slice( $fields['billing']['fields'], $company ),
        );
        //phpcs:enable SlevomatCodingStandard.Arrays.AlphabeticallySortedByKeys.IncorrectKeyOrder

        return $fields;
    }

    /**
     * Modifies the buyer name in the admin order page to include necessary company information
     *
     * @param  string   $buyer Buyer name.
     * @param  WC_Order $order Order object.
     * @return string           Modified Buyer name
     */
    #[Filter( 'woocommerce_admin_order_buyer_name', 99 )]
    public function modify_order_buyer_name( string $buyer, WC_Order $order ): string {
        $data = \wcsrb_get_company_data( $order );

        if ( 'RS' === $order->get_billing_country() && 'company' === $data['type'] ) {
            $buyer = $order->get_billing_company();
        }

        return $buyer;
    }

    /**
     * Adds fields to the order billing fields.
     *
     * @param  array $fields Order billing fields.
     * @return array
     */
    #[Filter( tag: 'woocommerce_admin_billing_fields', priority: 99 )]
    public function add_order_billing_fields( array $fields ): array {
        $index = \array_search( 'company', \array_keys( $fields ), true );

        /**
         * Callback to validate and update the order billing field.
         *
         * @param  string    $id Field ID.
         * @param  mixed     $v  Field value.
         * @param  \WC_Order $o  Order object.
         *
         * @since 3.8.0
         */
        $cb = static fn( $id, $v, $o ) => \do_action( 'wcsrb_update_order_billing_field', $id, $v, $o );

        //phpcs:disable SlevomatCodingStandard.Arrays.AlphabeticallySortedByKeys.IncorrectKeyOrder
        return \array_merge(
            \array_slice( $fields, 0, $index ),
            array(
                'type'    => array(
                    'label'   => \__( 'Customer type', 'serbian-addons-for-woocommerce' ),
                    'show'    => false,
                    'type'    => 'select',
                    'options' => \wcsrb_get_entity_types(),
                ),
                'company' => $fields['company'],

                'mb'      => array(
                    'label'           => \__( 'Company Number', 'serbian-addons-for-woocommerce' ),
                    'show'            => false,
                    'update_callback' => $cb,
                ),
                'pib'     => array(
                    'label'           => \__( 'Tax Number', 'serbian-addons-for-woocommerce' ),
                    'show'            => false,
                    'update_callback' => $cb,
                    'wrapper_class'   => '_billing_last_name_field ',
                ),

            ),
            \array_slice( $fields, $index + 1 ),
        );
        //phpcs:enable SlevomatCodingStandard.Arrays.AlphabeticallySortedByKeys.IncorrectKeyOrder
    }
}
