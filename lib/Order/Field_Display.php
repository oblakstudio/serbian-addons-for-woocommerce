<?php
/**
 * Field_Display class file.
 *
 * @package Serbian Addons for WooCommerce
 * @subpackage WooCommerce\Order
 */

namespace Oblak\WooCommerce\Serbian_Addons\Order;

use Oblak\WP\Abstracts\Hook_Runner;
use Oblak\WP\Decorators\Hookable;
use WC_Customer;
use WC_Order;

/**
 * Handles the address display customizations for orders and addresses
 */
#[Hookable( 'woocommerce_init', 99 )]
class Field_Display extends Hook_Runner {
    /**
     * Modifies the address format for Serbia to include necessary company information
     *
     * @param  string[] $formats Address formats.
     * @return string[]          Modified address formats
     *
     * @hook     woocommerce_localisation_address_formats
     * @type     filter
     * @priority filter:woocommerce_serbian_localization_address_priority:100
     */
    public function modify_address_format( $formats ) {
        add_filter( 'woocommerce_formatted_address_force_country_display', '__return_true' );

        $formats['RS'] = "{name}\n{company}\n{mb}\n{pib}\n{address_1}\n{address_2}\n{postcode} {city}, {state} {country}";

        if ( WCSRB()->get_settings( 'general', 'remove_unneeded_fields' ) ) {
            $formats['RS'] = strtr(
                $formats['RS'],
                array(
					'{state}'     => '',
					'{address_2}' => '',
				)
            );
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
     *
     * @hook     woocommerce_formatted_address_replacements
     * @type     filter
     * @priority 99
     */
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
     *
     * @hook     woocommerce_my_account_my_address_formatted_address
     * @type     filter
     * @priority 99
     */
    public function modify_account_formatted_address( $address, $customer_id, $address_type ) {
        if ( 'billing' !== $address_type ) {
            return $address;
        }

        $customer = new WC_Customer( $customer_id );

        $user_type   = ! empty( $customer->get_meta( 'billing_type', true ) ) ? $customer->get_meta( 'billing_type', true ) : 'person';
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
     *
     * @hook     woocommerce_order_formatted_billing_address
     * @type     filter
     * @priority 99
     */
    public function modify_order_formatted_address( $address, $order ) {
        return $this->address_modifier(
            $address,
            $order->get_meta( '_billing_type', true ),
            $order->get_meta( '_billing_mb', true ),
            $order->get_meta( '_billing_pib', true )
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

        $address['mb']  = sprintf(
            '%s: %s',
            _x( 'Company Number', 'Address display', 'serbian-addons-for-woocommerce' ),
            $company_number
        );
        $address['pib'] = sprintf(
            '%s: %s',
            _x( 'Tax Identification Number', 'Address display', 'serbian-addons-for-woocommerce' ),
            $tax_number
        );

        return $address;
    }

    /**
     * Modifies the buyer name in the admin order page to include necessary company information
     *
     * @param  string   $buyer Buyer name.
     * @param  WC_Order $order Order object.
     * @return string          Modified Buyer name
     *
     * @hook     woocommerce_admin_order_buyer_name
     * @type     filter
     * @priority 99
     */
    public function modify_order_buyer_name( $buyer, $order ) {
        return ( 'RS' === $order->get_billing_country() && 'company' === $order->get_meta( '_billing_type', true ) )
            ? $order->get_billing_company()
            : $buyer;
    }
}
