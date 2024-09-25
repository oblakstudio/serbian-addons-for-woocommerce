<?php
/**
 * Address_Field_Controller class file.
 *
 * @package Serbian Addons for WooCommerce
 */

namespace Oblak\WCSRB\Core;

use Oblak\WP\Abstracts\Hook_Caller;
use Oblak\WP\Decorators\Filter;
use Oblak\WP\Decorators\Hookable;

/**
 * Changes the fields on the checkout page
 *
 * @since 3.8.0
 */
#[Hookable( 'woocommerce_init', 99 )]
class Address_Field_Controller extends Hook_Caller {
    /**
     * Adds the customer type field to the default address fields.
     *
     * @param  array<string,array> $fields Default address fields.
     * @return array<string,array>
     */
    #[Filter( tag: 'woocommerce_default_address_fields', priority: 999999 )]
    public function add_customer_type_field( array $fields ): array {
        $enabled_type = \WCSRB()->get_settings( 'core', 'enabled_customer_types' );
        $type_field   = array(
            'class'    => array( 'form-row-wide', 'entity-type-control', 'update_totals_on_change', 'address-field' ),
            'default'  => 'person',
            'label'    => \__( 'Customer type', 'serbian-addons-for-woocommerce' ),
            'options'  => \wcsrb_get_entity_types(),
            'priority' => 21,
            'required' => true,
            'type'     => 'radio',
        );

        if ( 'both' !== $enabled_type ) {

            $type_field = \array_merge(
                $type_field,
                array(
                    'default'     => $enabled_type,
                    'description' => \wcsrb_get_entity_types()[ $enabled_type ],
                    'type'        => 'hidden',
                    'value'       => $enabled_type,
                ),
            );

            unset( $type_field['options'] );
        }

        $fields['type'] = $type_field;

        return $fields;
    }

    /**
     * Adds the extra fields to the default address fields
     *
     * @param  array<string,array> $fields Default address fields.
     * @return array<string,array>
     */
    #[Filter( tag: 'woocommerce_default_address_fields', priority: 'wcsrb_address_fields_priority' )]
    public function add_company_fields( array $fields ): array {
        if ( \WCSRB()->get_settings( 'core', 'remove_unneeded_fields' ) ) {
            unset( $fields['address_2'], $fields['state'] );
        }

        $fields['company']['class'][] = 'entity-type-toggle';

        return \array_merge( $fields, \wcsrb_get_company_fields() );
    }

    /**
     * Unsets I18n label for the customer type field.
     *
     * @param  array<string, array> $fields Default country locale fields.
     * @return array<string, array>
     */
    #[Filter( tag: 'woocommerce_get_country_locale_default', priority: 999999 )]
    public function modify_default_locale_field_data( array $fields ): array {
        unset( $fields['type']['label'] );

        return $fields;
    }

    /**
     * Set the JS locale fields data.
     *
     * Adds the hidden and required properties for all countries.
     * All are set to be hidden and NOT required by default.
     *
     * @param  array<string, array> $locale Default locale fields data.
     * @return array<string, array>
     */
    #[Filter( tag: 'woocommerce_get_country_locale', priority: 1000 )]
    public function add_default_locale_field_data( array $locale ): array {
        foreach ( $locale as &$fields ) {
            $fields['company']['required'] = false;
            $fields['type']                = array(
                'hidden'   => true,
                'required' => false,
            );
            $fields['mb']                  = array(
                'hidden'   => true,
                'required' => false,
            );
            $fields['pib']                 = array(
                'hidden'   => true,
                'required' => false,
            );
        }

        return $locale;
    }

    /**
     * Adds the custom locale field data
     *
     * We unhide the fields and enable them only if the company type is active.
     *
     * @param  array<string, array> $locale Default locale fields data.
     * @return array<string, array>
     */
    #[Filter( tag: 'woocommerce_get_country_locale', priority: 1000 )]
    public function add_custom_locale_field_data( array $locale ): array {
        $company_active = \wcsrb_can_checkout_as( 'company' );
        $company_props  = array( 'hidden' => ! $company_active );

        // phpcs:disable SlevomatCodingStandard.Arrays.AlphabeticallySortedByKeys.IncorrectKeyOrder
        $locale['RS'] = array(
            'type'     => array(
                'required' => true,
                'hidden'   => false,
            ),
            'company'  => \array_merge(
                array( 'class' => array( 'form-row-wide', 'entity-type-toggle', 'shown' ) ),
                $company_props,
            ),
            'mb'       => $company_props,
            'pib'      => $company_props,
            'postcode' => array(
                'priority' => 81,
            ),
            'city'     => array(
                'priority' => 82,
            ),
            'country'  => array(
                'priority' => 91,
            ),
        );
        // phpcs:enable SlevomatCodingStandard.Arrays.AlphabeticallySortedByKeys.IncorrectKeyOrder

        return $locale;
    }

    /**
     * Modifies the locale field selectors
     *
     * @param  array<string,string> $selectors Field selectors.
     * @return array<string,string>
     */
    #[Filter( tag: 'woocommerce_country_locale_field_selectors', priority: 99999 )]
    public function locale_field_selectors( array $selectors ): array {
        return \array_merge(
            $selectors,
            array(
                'company' => '#billing_company_field',
                'mb'      => '#billing_mb_field',
                'pib'     => '#billing_pib_field',
                'type'    => '#billing_type_field',
            ),
        );
    }

    /**
     * Modifies the billing fields to add the customer type and additional company fields
     *
     * @param  array $fields Billing fields.
     * @return array         Modified billing fields
     */
    #[Filter( tag: 'woocommerce_shipping_fields', priority: 'woocommerce_serbian_checkout_fields_priority' )]
    public function modify_shipping_fields( array $fields ) {
        $to_remove = array( 'company', 'mb', 'pib', 'type' );

        return \xwp_array_diff_assoc(
            $fields,
            ...\array_map( static fn( $f ) => "shipping_{$f}", $to_remove ),
        );
    }
}
