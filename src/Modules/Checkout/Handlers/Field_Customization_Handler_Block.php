<?php

namespace Oblak\WCSRB\Checkout\Handlers;

use XWC\Interfaces\Config_Repository;
use XWP\DI\Decorators\Action;
use XWP\DI\Decorators\Filter;
use XWP\DI\Decorators\Handler;

#[Handler( tag: 'woocommerce_loaded', priority: 9999, container: 'wcsrb' )]
class Field_Customization_Handler_Block {
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
     * @param  Config_Repository $cfg Config Service.
     */
    public function __construct( private Config_Repository $cfg ) {
    }


    /**
     * Adds the customer type field to the default address fields.
     *
     * @return void
     */
    #[Action( tag: 'woocommerce_init', priority: 10 )]
    public function add_additional_fields(): void {
        \woocommerce_register_additional_checkout_field(
            array(
                'id'          => 'wcsrb/type',
                'label'       => \__( 'Customer type', 'serbian-addons-for-woocommerce' ),
                'location'    => 'address',
                'options'     => \array_map(
                    static fn( $v, $k ) => array(
                        'label' => $v,
                        'value' => $k,
                    ),
                    \wcsrb_get_entity_types(),
                    \array_keys( \wcsrb_get_entity_types() ),
                ),
                'placeholder' => \__(
                    'Are you ordering on behalf of a company?',
                    'serbian-addons-for-woocommerce',
                ),
                'required'    => true,
                'type'        => 'select',
            ),
        );

        \woocommerce_register_additional_checkout_field(
            array(
                'attributes'    => array(
                    'data-shown-type' => 'company',
                ),
                'id'            => 'wcsrb/mb',
                'label'         => \__( 'Company Number', 'serbian-addons-for-woocommerce' ),
                'location'      => 'address',
                'optionalLabel' => \__( 'Company Number', 'serbian-addons-for-woocommerce' ),
                'type'          => 'text',
            ),
        );

        \woocommerce_register_additional_checkout_field(
            array(
                'attributes'    => array(
                    'data-shown-type' => 'company',
                ),
                'id'            => 'wcsrb/pib',
                'label'         => \__( 'Tax Number', 'serbian-addons-for-woocommerce' ),
                'location'      => 'address',
                'optionalLabel' => \__( 'Tax Number', 'serbian-addons-for-woocommerce' ),
                'type'          => 'text',
            ),
        );
    }

    /**
     * Add block checkout field locale data.
     *
     * @param  array<string,array<string,mixed>> $locale Default locale fields data.
     * @return array<string,array<string,mixed>>
     */
    #[Filter( tag: 'woocommerce_get_country_locale', priority: 1000 )]
    public function add_field_data_to_locale( array $locale ): array {
        [ $pfp, $cfp ] = $this->cfg->get( 'core.field_ordering' ) ? array( 81, 82 ) : array( 82, 81 );
        $cmp_props     = array(
            'hidden'   => ! \wcsrb_can_checkout_as( 'company' ),
            'required' => false,
        );

        foreach ( $locale as &$fields ) {
            $fields['company']    = \array_merge( $fields['company'] ?? array(), $cmp_props );
            $fields['wcsrb/type'] = \array_merge( array( 'priority' => 21 ), $cmp_props );
            $fields['wcsrb/mb']   = \array_merge( array( 'priority' => 31 ), $cmp_props );
            $fields['wcsrb/pib']  = \array_merge( array( 'priority' => 32 ), $cmp_props );
            $fields['postcode']   = \array_merge( $fields['postcode'] ?? array(), array( 'priority' => $pfp ) );
            $fields['city']       = \array_merge( $fields['city'] ?? array(), array( 'priority' => $cfp ) );
            $fields['country']    = \array_merge( $fields['country'] ?? array(), array( 'priority' => 91 ) );
        }

        $locale['RS']['wcsrb/type']['required'] = true;

        return $locale;
    }
}
