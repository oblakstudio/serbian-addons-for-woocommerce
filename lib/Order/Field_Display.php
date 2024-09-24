<?php
/**
 * Field_Display class file.
 *
 * @package Serbian Addons for WooCommerce
 * @subpackage WooCommerce\Order
 */

namespace Oblak\WooCommerce\Serbian_Addons\Order;

use Oblak\WP\Abstracts\Hook_Caller;
use Oblak\WP\Decorators\Filter;
use Oblak\WP\Decorators\Hookable;
use WC_Customer;
use WC_Order;

/**
 * Handles the address display customizations for orders and addresses
 */
#[Hookable( 'woocommerce_init', 99 )]
class Field_Display extends Hook_Caller {
    /**
     * Modifies the address format for Serbia to include necessary company information
     *
     * @param  string[] $formats Address formats.
     * @return string[]          Modified address formats
     */
    #[Filter( 'woocommerce_localisation_address_formats', 'wcrs_localization_address_priority' )]
    public function modify_address_format( $formats ) {
        \add_filter( 'woocommerce_formatted_address_force_country_display', '__return_true' );

        $formats['RS'] = "{name}\n{company}\n{mb}\n{pib}\n{address_1}\n{address_2}\n{postcode} {city}, {state} {country}";

        if ( \WCSRB()->get_settings( 'core', 'remove_unneeded_fields' ) ) {
            $formats['RS'] = \str_replace( array( '{state}', '{address_2}' ), '', $formats['RS'] );
        }

        return $formats;
    }

    /**
     * Adds custom replacements to the replacements array.
     *
     * Custom fields added are:
     *  - Type
     *  - Company Number
     *  - Tax Identification Number
     *
     * @param  string[] $replacements  Replacements array.
     * @param  array    $args          Address data.
     * @return string[]                Modified replacements array
     */
    #[Filter( 'woocommerce_formatted_address_replacements', 99 )]
    public function modify_address_replacements( $replacements, $args ) {
        $replacements['{type}'] = $args['type'] ?? "\n";
        $replacements['{mb}']   = $args['mb'] ?? "\n";
        $replacements['{pib}']  = $args['pib'] ?? "\n";

        return $replacements;
    }

    /**
     * Modifies the address data array to include neccecary company information.
     *
     * This is used in the My Account > Addresses page.
     *
     * @param  array  $address      Address data array.
     * @param  int    $customer_id  Customer ID.
     * @param  string $address_type Address type (billing or shipping).
     * @return array                Modified address data array
     */
    #[Filter( 'woocommerce_my_account_my_address_formatted_address', 99 )]
    public function modify_account_formatted_address( $address, $customer_id, $address_type ) {
        if ( 'billing' !== $address_type ) {
            return $address;
        }

        $customer = new WC_Customer( $customer_id );

        // phpcs:ignore Universal.Operators.DisallowShortTernary.Found
        $user_type   = $customer->get_meta( 'billing_type', true ) ?: 'person';
        $company_num = $customer->get_meta( 'billing_mb', true );
        $company_tax = $customer->get_meta( 'billing_pib', true );

        return $this->address_modifier( $address, $user_type, $company_num, $company_tax );
    }

    /**
     * Modifies the address data array to include neccecary company information.
     *
     * This is used for the order addresses.
     *
     * @param  array    $address Address data array.
     * @param  WC_Order $order   Order object.
     * @return array             Modified address data array
     */
    #[Filter( 'woocommerce_order_formatted_billing_address', 99 )]
    public function modify_order_formatted_address( $address, $order ) {
        return $this->address_modifier(
            $address,
            $order->get_meta( '_billing_type', true ),
            $order->get_meta( '_billing_mb', true ),
            $order->get_meta( '_billing_pib', true ),
        );
    }

    /**
     * Billing address modifier function
     *
     * Depending on the customer(user) type we add the needed rows to the address.
     * If the customer is a company we prepend the number type before the number itself
     *
     * @param  array  $address        Billing address data array.
     * @param  string $type           User type (person or company).
     * @param  string $company_number Company number.
     * @param  string $tax_number     Company tax number.
     * @return array                  Modified billing address data array.
     */
    private function address_modifier( $address, $type, $company_number, $tax_number ) {
        $address['type'] = $type;
        $address['mb']   = "\n";
        $address['pib']  = "\n";

        if ( 'company' !== $type ) {
            return $address;
        }

        $address['first_name'] = "\n";
        $address['last_name']  = "\n";

        if ( $company_number ) {
            $address['mb'] = \sprintf(
                '%s: %s',
                \_x( 'Company Number', 'Address display', 'serbian-addons-for-woocommerce' ),
                $company_number,
            );
        }
        $address['pib'] = \sprintf(
            '%s: %s',
            \_x( 'Tax Identification Number', 'Address display', 'serbian-addons-for-woocommerce' ),
            $tax_number,
        );

        return $address;
    }

    /**
     * Modifies the buyer name in the admin order page to include necessary company information
     *
     * @param  string   $buyer Buyer name.
     * @param  WC_Order $order Order object.
     * @return string          Modified Buyer name
     */
    #[Filter( 'woocommerce_admin_order_buyer_name', 99 )]
    public function modify_order_buyer_name( $buyer, $order ) {
        return 'RS' === $order->get_billing_country() && 'company' === $order->get_meta( '_billing_type', true )
            ? $order->get_billing_company()
            : $buyer;
    }

    /**
     * Adds the company information to the customer meta fields.
     *
     * @param  array $fields Customer meta fields.
     * @return array         Modified customer meta fields
     */
    #[Filter( 'woocommerce_customer_meta_fields' )]
    public function modify_customer_meta_fields( array $fields ): array {
        $billing = array();

        foreach ( $fields['billing']['fields'] as $field => $args ) {
            if ( 'billing_company' !== $field ) {
                $billing[ $field ] = $args;
                continue;
            }

            $billing['billing_type'] = array(
                'label'   => \__( 'Customer type', 'serbian-addons-for-woocommerce' ),
                'options' => \wcsrb_get_entity_types(),
                'type'    => 'select',
            );

            $billing[ $field ] = $args;

            $billing['billing_mb'] = array(
                'label' => \__( 'Company Number', 'serbian-addons-for-woocommerce' ),
                'type'  => 'text',
            );

            $billing['billing_pib'] = array(
                'label' => \__( 'Tax Number', 'serbian-addons-for-woocommerce' ),
                'type'  => 'text',
            );
        }

        $fields['billing']['fields'] = $billing;

        return $fields;
    }
}
