<?php
namespace Oblak\WCRS\CustomerType;

use WC_Order;

class FieldDisplay {
    public function __construct() {
        add_filter('woocommerce_localisation_address_formats', [$this, 'localisationAddressFormats'], PHP_INT_MAX, 1);
        add_filter('woocommerce_my_account_my_address_formatted_address', [$this, 'modifyFormattedAddress'], 99, 3);
        add_filter('woocommerce_formatted_address_replacements', [$this, 'modifyAddressReplacements'], 99, 2);
        add_filter('woocommerce_order_formatted_billing_address', [$this, 'modifyOrderbillingAddress'], 99, 2);
        add_filter('woocommerce_admin_order_buyer_name', [$this, 'modifyAdminOrderBuyerName'], 99, 2);
    }

    /**
     * Modifies the address format for Serbia to include necessary company information
     *
     * @param  mixed $formats Array of formats by state
     * @return mixed          Modified array of formats by state
     */
    public function localisationAddressFormats($formats) {
        $formats['RS'] = "{name}\n{company}\n{mb}\n{pib}\n{address_1}\n{address_2}\n{postcode} {city}\n{state}\n{country}";

        return $formats;
    }

    /**
     * Modifies the address data array to include neccecary company information
     *
     * @param  array  $address
     * @param  int    $customer_id  Customer ID
     * @param  string $address_type Address type (billing or shipping)
     * @return array                Modified address data array
     */
    public function modifyFormattedAddress($address, $customer_id, $address_type) {
        if ($address_type != 'billing') {
            return $address;
        }

        $user_type   = ($type = get_user_meta($customer_id, 'billing_type', true)) ? $type : 'person';
        $company_num = get_user_meta($customer_id, 'billing_mb', true);
        $company_tax = get_user_meta($customer_id, 'billing_pib', true);

        return $this->addressModifier($address, $user_type, $company_num, $company_tax);
    }

    public function modifyAddressReplacements($replacements, $args) {
        $replacements['{type}'] = $args['type'] ?? "\n";
        $replacements['{mb}']  = $args['mb'] ?? "\n";
        $replacements['{pib}'] = $args['pib'] ?? "\n";

        return $replacements;
    }

    /**
     * Modifies the formatted billing address saved in WC_Order to include necessary company information
     *
     * @param  array    $address
     * @param  WC_Order $order
     * @return array
     */
    public function modifyOrderbillingAddress($address, $order) {
        return $this->addressModifier(
            $address,
            $order->get_meta('_billing_type'),
            $order->get_meta('_billing_mb'),
            $order->get_meta('_billing_pib')
        );
    }

    /**
     * Billing address modifier function
     *
     * Depending on the customer(user) type we add the needed rows to the address.
     * If the customer is a company we prepend the number type before the number itself
     *
     * @param  array  $address        Billing address data array
     * @param  string $user_type      User type (person or company)
     * @param  string $company_number Company number
     * @param  string $company_tax    Company tax number
     * @return array                  Modified billing address data array
     */
    private function addressModifier($address, $user_type, $company_number, $company_tax) {
        $address['type'] = $user_type;
        $address['mb']   = "\n";
        $address['pib']  = "\n";

        if ($user_type == 'company') {
            $address['first_name'] = "\n";
            $address['last_name'] = "\n";

            $address['mb']  = sprintf(
                '%s: %s',
                _x('Company Number', 'Address display', 'serbian-addons-for-woocommerce'),
                $company_number
            );
            $address['pib'] = sprintf(
                '%s: %s',
                _x('Tax Identification Number', 'Address display', 'serbian-addons-for-woocommerce'),
                $company_tax
            );
        }

        return $address;
    }

    /**
     * Modifies the buyer name in the admin order page to include necessary company information
     *
     * @param  string   $name  Original Buyer name
     * @param  WC_Order $order Order object
     * @return string          Modified Buyer name
     */
    public function modifyAdminOrderBuyerName($buyer, $order) {
        if ('RS' != $order->get_billing_country()) {
            return $buyer;
        }

        $user_type = $order->get_meta('_billing_type');

        if ($user_type == 'company') {
            $buyer = $order->get_billing_company();
        }

        return $buyer;
    }
}
