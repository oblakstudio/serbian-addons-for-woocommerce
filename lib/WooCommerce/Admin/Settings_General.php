<?php
/**
 * Settings_General class file.
 *
 * @package Serbian Addons for WooCommerce
 * @subpackage WooCommerce\Admin
 */

namespace Oblak\WCRS\WooCommerce\Admin;

use function Oblak\WCRS\Utils\get_entity_types;

/**
 * Outputs the settings for the general tab
 */
class Settings_General {

    /**
     * Class constructor
     */
    public function __construct() {
        add_filter( 'woocommerce_get_settings_general', array( $this, 'add_general_settings' ), PHP_INT_MAX, 2 );
    }

    /**
     * Adds the settings fields to the specific position
     *
     * @param  array  $settings Settings fields.
     * @param  string $section  Section name.
     * @return array            Modified settings fields
     */
    public function add_general_settings( $settings, $section ) {
        if ( '' !== $section ) {
            return $settings;
        }

        /**
         * Filters the available entity types on the general settings page
         *
         * @param array $entity_types Entity types
         * @since 1.3.0
         */
        $enabled_customer_types = apply_filters(
            'woocommerce_serbian_enabled_customer_types',
            array_merge(
                array( 'both' => __( 'Companies and persons', 'serbian-addons-for-woocommerce' ) ),
                get_entity_types()
            )
        );

        $type_settings = array(
            array(
                'title'    => __( 'Enabled customer types', 'serbian-addons-for-woocommerce' ),
                'id'       => 'woocommerce_serbian[enabled_customer_type]',
                'type'     => 'select',
                'desc'     => __( 'Which customer types can shop on the store', 'serbian-addons-for-woocommerce' ),
                'options'  => $enabled_customer_types,
                'desc_tip' => true,
                'default'  => 'both',
            ),
            array(
                'title'    => __( 'Field removal', 'serbian-addons-for-woocommerce' ),
                'id'       => 'woocommerce_serbian[remove_unneeded_fields]',
                'type'     => 'checkbox',
                'desc'     => __( 'Remove unneeded fields from the checkout page', 'serbian-addons-for-woocommerce' ),
                'desc_tip' => __( 'Removes Address 2 and State fields', 'serbian-addons-for-woocommerce' ),
            ),
        );

        $type_index = array_search( 'woocommerce_default_customer_address', array_column( $settings, 'id' ), true ) + 1;

        $settings = array_merge(
            array_slice( $settings, 0, $type_index ),
            $type_settings,
            array_slice( $settings, $type_index )
        );

        $currency_settings = array(
            array(
                'title'   => __( 'Transliterate currency symbol', 'serbian-addons-for-woocommerce' ),
                'id'      => 'woocommerce_serbian[fix_currency_symbol]',
                'type'    => 'checkbox',
                'desc'    => __( 'Transliterate currency symbol to latin script', 'serbian-addons-for-woocommerce' ),
                'default' => 'yes',
            ),
        );

        $currency_index = array_search( 'woocommerce_price_num_decimals', array_column( $settings, 'id' ), true ) + 1;

        $settings = array_merge(
            array_slice( $settings, 0, $currency_index ),
            $currency_settings,
            array_slice( $settings, $currency_index )
        );

        return $settings;
    }
}
