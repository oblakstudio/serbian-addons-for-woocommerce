<?php

namespace Oblak\WCSRB\Checkout\Handlers;

use WC_Data;
use WC_Order;
use XWC\Interfaces\Config_Repository;
use XWP\DI\Decorators\Action;
use XWP\DI\Decorators\Filter;
use XWP\DI\Decorators\Handler;

#[Handler( tag: 'woocommerce_loaded', priority: 10000, container: 'wcsrb' )]
class Field_Customize_Block_Handler {
    /**
     * Checks if the module can be initialized.
     *
     * @param  Config_Repository $cfg Config repository instance.
     * @return bool
     */
    public static function can_initialize( Config_Repository $cfg ): bool {
        return false;
    }

    /**
     * Adds the customer type field to the default address fields.
     *
     * @param  array<string,array> $fields Default address fields.
     * @return array<string,array>
     */
    #[Filter( tag: 'woocommerce_default_address_fields', priority: 9999999 )]
    public function remove_customer_type_field( array $fields ): array {
        foreach ( array( 'mb', 'type', 'pib' ) as $field ) {
            if ( ! isset( $fields[ $field ] ) ) {
                continue;
            }

            $fields[ "wcsrb/{$field}" ] = $fields[ $field ];
            unset( $fields[ $field ] );
        }

        return $fields;
    }

    /**
     * Adds the custom locale field data
     *
     * We unhide the fields and enable them only if the company type is active.
     *
     * @param  array<string, array> $locale Default locale fields data.
     * @return array<string, array>
     */
    #[Filter( tag: 'woocommerce_get_country_locale', priority: 10000 )]
    public function add_custom_locale_field_data( array $locale ): array {
        foreach ( array( 'mb', 'type', 'pib' ) as $field ) {
            $locale['RS'][ "wcsrb/{$field}" ] = $locale['RS'][ $field ];
            unset( $locale['RS'][ $field ] );
        }

        return $locale;
    }


    #[Action( tag: 'woocommerce_init', priority: 10 )]
    public function add_fields() {
        \woocommerce_register_additional_checkout_field(
            array(
                'id'       => 'wcsrb/type',
                'label'    => \__( 'Customer type', 'serbian-addons-for-woocommerce' ),
                'location' => 'address',
                'options'  => \array_map(
                    static fn( $v, $k ) => array(
                        'label' => $v,
                        'value' => $k,
                    ),
                    \wcsrb_get_entity_types(),
                    \array_keys( \wcsrb_get_entity_types() ),
                ),
                'required' => true,
                'type'     => 'select',
            ),
        );

        \woocommerce_register_additional_checkout_field(
            array(
                'attributes'    => array(),
                'id'            => 'wcsrb/mb',
                'label'         => \__( 'Company Number', 'serbian-addons-for-woocommerce' ),
                'location'      => 'address',
                'optionalLabel' => \__( 'Company Number', 'serbian-addons-for-woocommerce' ),
                'type'          => 'text',
            ),
        );

        \woocommerce_register_additional_checkout_field(
            array(
                'attributes'    => array(),
                'id'            => 'wcsrb/pib',
                'label'         => \__( 'Tax Number', 'serbian-addons-for-woocommerce' ),
                'location'      => 'address',
                'optionalLabel' => \__( 'Tax Number', 'serbian-addons-for-woocommerce' ),
                'type'          => 'text',
            ),
        );
    }

    #[Filter( tag: 'woocommerce_get_default_value_for_wcsrb/type', priority: 10 )]
    public function get_default_value( mixed $value, string $group, WC_Data $wc_obj ): mixed {
        if ( 'billing' !== $group ) {
            return $value;
        }

        return $wc_obj instanceof WC_Order
            ? $wc_obj->get_meta( '_billing_type', true )
            : $wc_obj->get_meta( 'billing_type', true );
    }

    /**
     * Undocumented function
     *
     * @template TObj of \WC_Customer
     *
     * @param  string $key    The key of the field.
     * @param  mixed  $value  The value of the field.
     * @param  string $group  The group of the field.
     * @param  TObj   $wc_obj The object to update.
     */
    // #[Action( tag: 'woocommerce_set_additional_field_value', priority: 10 )]
    public function set_field_value( string $key, mixed $value, string $group, WC_Data $wc_obj ): void {
        if ( ! \str_starts_with( $key, 'wcsrb/' ) || 'billing' !== $group ) {
            return;
        }
        $key = \str_replace( 'wcsrb/', '', $key );

        $wc_obj->update_meta_data( "billing_{$key}", $value );
        // $wc_obj->update_meta_data( "shipping_{$key}", $value );
    }

    // #[Action( tag: 'woocommerce_blocks_validate_location_address_fields' )]
    public function validate_fields( \WP_Error $err, $fields, $group ) {
        \error_log( \wc_print_r( $fields, true ) );
    }
}
