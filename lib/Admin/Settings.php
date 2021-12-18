<?php
namespace Oblak\WCRS\Admin;

class Settings {
    public function __construct() {
        add_filter('woocommerce_get_settings_general', [$this, 'addPluginSettings'], PHP_INT_MAX, 2);
    }

    public function addPluginSettings($settings, $section) {
        if ($section != '') {
            return $settings;
        }

        $type_settings = [
            [
                'title'    => __('Enabled customer types', 'serbian-addons-for-woocommerce'),
                'id'       => 'woocommerce_serbian[enabled_customer_type]',
                'type'     => 'select',
                'desc'     => __('Which customer types can shop on the store', 'serbian-addons-for-woocommerce'),
                'options'  => [
                    'both'   => __('Persons and companies', 'serbian-addons-for-woocommerce'),
                    'person' => __('Persons', 'serbian-addons-for-woocommerce'),
                    'company' => __('Companies', 'serbian-addons-for-woocommerce'),
                ],
                'desc_tip' => true,
                'default'  => 'both',
            ],
            [
                'title'    => __('Field removal', 'serbian-addons-for-woocommerce'),
                'id'       => 'woocommerce_serbian[remove_unneeded_fields]',
                'type'     => 'checkbox',
                'desc'     => __('Remove unneeded fields from the checkout page', 'serbian-addons-for-woocommerce'),
                'desc_tip' => __('Removes Address 2 and State fields', 'serbian-addons-for-woocommerce'),
            ]
        ];

        $type_index = array_search('woocommerce_default_customer_address', array_column($settings, 'id')) + 1;


        $settings =  array_merge(
            array_slice($settings, 0, $type_index),
            $type_settings,
            array_slice($settings, $type_index)
        );

        $currency_settings = [
            [
                'title'    => __('Transliterate currency symbol', 'serbian-addons-for-woocommerce'),
                'id'       => 'woocommerce_serbian[fix_currency_symbol]',
                'type'     => 'checkbox',
                'desc'     => __('Transliterate currency symbol to latin script', 'serbian-addons-for-woocommerce'),
                'default'  => 'yes',
            ],
        ];

        $currency_index = array_search('woocommerce_price_num_decimals', array_column($settings, 'id')) + 1;

        $settings =  array_merge(
            array_slice($settings, 0, $currency_index),
            $currency_settings,
            array_slice($settings, $currency_index)
        );

        return $settings;
    }
}
