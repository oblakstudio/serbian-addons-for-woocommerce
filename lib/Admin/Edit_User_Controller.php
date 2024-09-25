<?php
/**
 * Edit_User_Controller class file.
 *
 * @package Serbian Addons for WooCommerce
 */

namespace Oblak\WCSRB\Admin;

use Oblak\WP\Abstracts\Hook_Caller;
use Oblak\WP\Decorators\Filter;
use Oblak\WP\Decorators\Hookable;

/**
 * Changes the fields on the checkout page
 *
 * @since 3.8.0
 */
#[Hookable( 'admin_init', 99 )]
class Edit_User_Controller extends Hook_Caller {
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
}
