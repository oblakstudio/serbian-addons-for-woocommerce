<?php
/**
 * Address_Field_Controller class file.
 *
 * @package Serbian Addons for WooCommerce
 */

namespace Oblak\WCSRB\Checkout\Handlers\Shared;

use WC_Customer;
use WC_Data;
use WC_Order;
use XWC\Interfaces\Config_Repository;
use XWP\DI\Decorators\Filter;
use XWP\DI\Decorators\Handler;

/**
 * Changes the fields on the checkout page
 *
 * @since 3.8.0
 */
#[Handler( tag: 'woocommerce_loaded', priority: 9999, container: 'wcsrb' )]
class Field_Customize_Handler {
    /**
     * Constructor
     *
     * @param  Config_Repository $cfg   Config Service.
     */
    public function __construct( private Config_Repository $cfg, ) {
    }

    /**
     * Modifies the address format for Serbia to include necessary company information
     *
     * @param  array<string,string> $formats Address formats.
     * @return array<string,string>
     */
    #[Filter( 'woocommerce_localisation_address_formats', 'wcrs_localization_address_priority' )]
    public function modify_address_format( $formats ) {
        $formats['RS'] = "{name}\n{company}\n{mb}\n{pib}\n{address_1} {address_2}\n{postcode} {city}, {state} {country}";

        if ( $this->cfg->get( 'core.remove_unneeded_fields' ) ) {
            $formats['RS'] = \str_replace( array( '{state}', ' {address_2}' ), '', $formats['RS'] );
        }

        return $formats;
    }

    /**
     * Always display the country in the address.
     *
     * @return bool
     */
    #[Filter( tag: 'woocommerce_formatted_address_force_country_display', priority: 100 )]
    public function always_display_country_in_address(): bool {
        return true;
    }

    /**
     * Adds the proper values for the custom fields on the edit address page.
     *
     * @param  array<string,array> $address Address fields.
     * @param  string              $type    Address type.
     * @return array<string,array>
     */
    #[Filter( tag: 'woocommerce_address_to_edit', priority: 100 )]
    public function edit_address_field_value( array $address, string $type ): array {
        $fields = array( 'billing_type', 'billing_mb', 'billing_pib' );

        if ( 'billing' !== $type || ! \array_intersect_key( $address, \array_flip( $fields ) ) ) {
            return $address;
        }

        $data = \wcsrb_get_company_data( new WC_Customer( \get_current_user_id() ) );

        foreach ( $fields as $field ) {
            if ( ( $address[ $field ]['value'] ?? '' ) ) {
                continue;
            }

            $address[ $field ]['value'] = $data[ \str_replace( 'billing_', '', $field ) ] ?? '';
        }

        return $address;
    }

    /**
     * Change default value for the custom fields.
     *
     * @param  mixed   $value  Default value.
     * @param  string  $group  Field group.
     * @param  WC_Data $wc_obj WC Data object.
     * @param  string  $field  Field name.
     * @return mixed
     */
    #[Filter(
        tag: 'woocommerce_get_default_value_for_wcsrb/type',
        priority: 10,
        invoke: Filter::INV_PROXIED,
        args: 3,
        params: array( '!value:type' ),
    )]
    #[Filter(
        tag: 'woocommerce_get_default_value_for_wcsrb/mb',
        priority: 10,
        invoke: Filter::INV_PROXIED,
        args: 3,
        params: array( '!value:mb' ),
    )]
    #[Filter(
        tag: 'woocommerce_get_default_value_for_wcsrb/pib',
        priority: 10,
        invoke: Filter::INV_PROXIED,
        args: 3,
        params: array( '!value:pib' ),
    )]
    public function remap_legacy_field_value( mixed $value, string $group, WC_Data $wc_obj, string $field ): mixed {
        if ( 'billing' === $group ) {
            $key   = \sprintf( '%s_%s', $wc_obj instanceof WC_Order ? '_billing' : 'billing', $field );
            $value = $wc_obj->get_meta( $key, true );
        }

        return $value ?? '';
    }
}
