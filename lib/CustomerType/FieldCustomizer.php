<?php
namespace Oblak\WCRS\CustomerType;

use function Oblak\WCRS\Utils\getEntityTypes;

class FieldCustomizer {

    private $types = [];

    public function __construct() {
        $this->types = getEntityTypes();

        add_filter('woocommerce_billing_fields', [$this, 'modifyBillingFields'], PHP_INT_MAX, 1);
        add_filter('woocommerce_shipping_fields', [$this, 'modifyShippingFields'], PHP_INT_MAX, 1);
        add_filter('woocommerce_checkout_fields', [$this, 'modifyAjaxCheckoutFields'], PHP_INT_MAX, 1);
    }

    private function removeUnneededFields($fields, $type) {
        $options = WCSRB()->getOptions();

        if ($options['remove_unneeded_fields'] == 'yes') {
            unset($fields["{$type}_address_2"]);
            unset($fields["{$type}_state"]);
        }

        $fields["{$type}_postcode"]['priority'] = 81;
        $fields["{$type}_city"]['priority'] = 91;
        $fields["{$type}_country"]['priority'] = 91;

        return $fields;
    }

    /**
     * Modifies the billing fields to add the customer type and additional company fields
     *
     * @param  array $fields
     * @return array
     */
    public function modifyBillingFields($fields) {
        $options = WCSRB()->getOptions();

        $fields = $this->removeUnneededFields($fields, 'billing');

        $billing_type = [
            'type'     => 'radio',
            'label'    => __('Customer type', 'serbian-addons-for-woocommerce'),
            'class'    => ['form-row-wide', 'entity-type-control', 'update_totals_on_change'],
            'required' => true,
            'default'  => 'person',
            'options'  => $this->types,
            'priority' => 21,
        ];

        if ($options['enabled_customer_type'] != 'both') {
            $billing_type['default'] = $options['enabled_customer_type'];
            $billing_type['type']    = 'hidden';
            $billing_type['description'] = $this->types[$options['enabled_customer_type']];
            unset($billing_type['options']);
        }

        $fields['billing_type'] = $billing_type;

        if (!in_array($options['enabled_customer_type'], ['both', 'company'])) {
            unset($fields['billing_company']);
            return $fields;
        }


        $fields['billing_mb'] = [
            'type'              => 'text',
            'label'             => __('Company Number', 'serbian-addons-for-woocommerce'),
            'class'             => ['form-row-first', 'hide-if-person'],
            'required'          => true,
            'placeholder'       => '66143627',
            'priority'          => 31,
            'validate'          => ['mb'],
        ];

        $fields['billing_pib'] = [
            'type'              => 'text',
            'label'             => __('Tax Number', 'serbian-addons-for-woocommerce'),
            'class'             => ['form-row-last', 'hide-if-person'],
            'required'          => true,
            'placeholder'       => '112497859',
            'priority'          => 32,
            'validate'          => ['pib'],
        ];

        if ($options['enabled_customer_type'] != 'company') {
            $fields['billing_company']['custom_attributes']['disabled'] = 'disabled';
            $fields['billing_pib']['custom_attributes']['disabled'] = 'disabled';
            $fields['billing_mb']['custom_attributes']['disabled'] = 'disabled';
        }
        $fields['billing_company']['class'][] = 'hide-if-person';
        $fields['billing_company']['required'] = true;

        return $fields;
    }

    public function modifyShippingFields($fields) {
        return $this->removeUnneededFields($fields, 'shipping');
    }

    /**
     * Removes unnecessary fields from the checkout ajax request
     *
     * @param  array $fields Fields to modify
     * @return array         Modified fields
     */
    public function modifyAjaxCheckoutFields($fields) {
        if (!wp_doing_ajax()) {
            return $fields;
        }

        $posted_data = $_POST;

        if ($posted_data['billing_type'] == 'fizicko') {
            unset($fields['billing']['billing_company']);
            unset($fields['billing']['billing_mb']);
            unset($fields['billing']['billing_pib']);
        }

        return $fields;
    }
}
