<?php

namespace Oblak\WCSRB\Checkout\Handlers;

use WC_Data;
use WC_Order;
use XWC\Interfaces\Config_Repository;
use XWP\DI\Decorators\Filter;
use XWP\DI\Decorators\Handler;

#[Handler( tag: 'woocommerce_loaded', priority: 9999, container: 'wcsrb' )]
class Field_Appearance_Handler_Block {
    /**
     * Checks if the module can be initialized.
     *
     * @param  Config_Repository $cfg Config repository instance.
     * @return bool
     */
    public static function can_initialize( Config_Repository $cfg ): bool {
        return $cfg->get( 'core.block_checkout', true );
    }

    /**
     * Constructor
     *
     * @param  Config_Repository $config Config Service.
     */
    public function __construct( private Config_Repository $config ) {
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
     * Modifies the address format for Serbia to include necessary company information
     *
     * @param  array<string,string> $formats Address formats.
     * @return array<string,string>
     */
    #[Filter( 'woocommerce_localisation_address_formats', 'wcrs_localization_address_priority' )]
    public function modify_address_format( $formats ) {
        $formats['RS'] = "{name}\n{company}\n{address_1} {address_2}\n{postcode} {city}, {state} {country}";

        if ( $this->config->get( 'core.remove_unneeded_fields' ) ) {
            $formats['RS'] = \str_replace( array( '{state}', ' {address_2}' ), '', $formats['RS'] );
        }

        return $formats;
    }

    #[Filter(
        tag: 'woocommerce_get_default_value_for_wcsrb/type',
        priority: 10,
        invoke: Filter::INV_PROXIED,
        args: 3,
        params: array(
            '!value:type',
        ),
    )]
    #[Filter(
        tag: 'woocommerce_get_default_value_for_wcsrb/mb',
        priority: 10,
        invoke: Filter::INV_PROXIED,
        args: 3,
        params: array(
            '!value:mb',
        ),
    )]
    public function remap_legacy_field_value( mixed $value, string $group, WC_Data $wc_obj, string $field ): string {
        if ( 'billing' === $group ) {
            $key = $wc_obj instanceof WC_Order
                ? "_billing_{$field}"
                : "billing_{$field}";

            $value = $wc_obj->get_meta( $key, true );
        }

        return $value ?? '';
    }
}
