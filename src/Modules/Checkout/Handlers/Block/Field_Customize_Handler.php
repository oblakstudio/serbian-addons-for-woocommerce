<?php
/**
 * Field_Customize_Handler class file.
 *
 * @package Serbian Addons for WooCommerce
 * @subpackage Checkout
 */

namespace Oblak\WCSRB\Checkout\Handlers\Block;

use Automattic\WooCommerce\Blocks\Domain\Services\CheckoutFields;
use WC_Customer;
use WC_Order;
use XWC\Interfaces\Config_Repository;
use XWP\DI\Decorators\Action;
use XWP\DI\Decorators\Filter;
use XWP\DI\Decorators\Handler;

/**
 * Handles the customization of the checkout fields.
 *
 * ! This handler is used only for the block checkout.
 *
 * @since 4.0.0
 */
#[Handler( tag: 'woocommerce_loaded', priority: 9999, container: 'wcsrb' )]
class Field_Customize_Handler {
    /**
     * Field map.
     *
     * @var array<string,array<string,mixed>>
     */
    private array $fields = array(
        'wcsrb/mb'   => array(
            'class'    => array( 'form-row-first', 'address-field', 'entity-type-toggle', 'shown' ),
            'priority' => 31,
        ),
        'wcsrb/pib'  => array(
            'class'    => array( 'form-row-last', 'address-field', 'entity-type-toggle', 'shown' ),
            'priority' => 32,
        ),
        'wcsrb/type' => array(
            'class'    => array( 'form-row-wide', 'entity-type-control', 'update_totals_on_change', 'address-field' ),
            'priority' => 21,
            'type'     => 'radio',
        ),
    );

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
     * Tweak the checkout block data.
     *
     * @param  array<string,mixed> $b Block data.
     * @return array<string,mixed>
     */
    #[Filter( tag: 'render_block_data' )]
    public function tweak_checkout_block( array $b ): array {
        if ( 'woocommerce/checkout' === $b['blockName'] ) {
            $b['attrs']['showCompanyField']   = \wcsrb_can_checkout_as( 'company' );
            $b['attrs']['showApartmentField'] = ! $this->cfg->get( 'core.remove_unneeded_fields' );
        }

        return $b;
    }

    /**
     * Adds the customer type field to the default address fields.
     */
    #[Action( tag: 'woocommerce_init', priority: 10 )]
    public function add_additional_fields(): void {
        $enabled_type = $this->cfg->get( 'core.enabled_customer_types' );
        $type_field   = array(
            'attributes'        => array(
                'data-force' => '',
            ),
            'id'                => 'wcsrb/type',
            'label'             => \__( 'Customer type', 'serbian-addons-for-woocommerce' ),
            'location'          => 'address',
            'options'           => \array_map(
                static fn( $v, $k ) => array(
                    'label' => $v,
                    'value' => $k,
                ),
                \wcsrb_get_entity_types(),
                \array_keys( \wcsrb_get_entity_types() ),
            ),
            'placeholder'       => \__(
                'Are you ordering on behalf of a company?',
                'serbian-addons-for-woocommerce',
            ),
            'required'          => true,
            'sanitize_callback' => static function ( $v ) use ( $enabled_type ) {
                if ( '' === $v || 'both' === $enabled_type ) {
                    return $v;
                }

                return $enabled_type;
            },
            'type'              => 'select',
        );

        if ( 'both' !== $enabled_type ) {
            $type_field['options'] = \wp_list_filter(
                $type_field['options'],
                array( 'value' => $enabled_type ),
            );
            unset( $type_field['placeholder'] );
        }

        \woocommerce_register_additional_checkout_field( $type_field );

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
     * Modifies the address fields on the edit address page.
     *
     * @param  array<string,array<string,mixed>> $address Address fields.
     * @param  string                            $type    Address type.
     * @return array<string,array<string,mixed>>
     */
    #[Filter( tag: 'woocommerce_address_to_edit', priority: 99 )]
    public function modify_edit_address_fields( array $address, string $type ): array {
        if ( 'billing' !== $type ) {
            return $address;
        }

        $pfx = CheckoutFields::get_group_key( 'billing' );

        foreach ( $this->fields as $field => $data ) {
            $address[ $pfx . $field ] = \array_merge( $address[ $pfx . $field ], $data );
        }

        $address['billing_company']['class'][] = 'entity-type-toggle';

        return $address;
    }


    /**
     * Modifies the address format for Serbia to include necessary company information
     *
     * @param  array<string,string> $formats Address formats.
     * @return array<string,string>
     */
    #[Filter(
        tag: 'woocommerce_localisation_address_formats',
        priority: \PHP_INT_MAX,
        context: Filter::CTX_FRONTEND,
    )]
    public function modify_address_format( $formats ) {
        $formats['RS'] = \str_replace( array( '{mb}', '{pib}' ), '', $formats['RS'] );

        return $formats;
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
            $fields['wcsrb/type'] = array( 'priority' => 21 );
            $fields['wcsrb/mb']   = \array_merge( array( 'priority' => 31 ), $cmp_props );
            $fields['wcsrb/pib']  = \array_merge( array( 'priority' => 32 ), $cmp_props );
            $fields['postcode']   = \array_merge( $fields['postcode'] ?? array(), array( 'priority' => $pfp ) );
            $fields['city']       = \array_merge( $fields['city'] ?? array(), array( 'priority' => $cfp ) );
            $fields['country']    = \array_merge( $fields['country'] ?? array(), array( 'priority' => 91 ) );
            $fields['state']      = \array_merge(
                $fields['state'] ?? array(),
                array( 'hidden' => $this->cfg->get( 'core.remove_unneeded_fields' ) ),
            );
        }

        $locale['RS']['wcsrb/type']['required'] = true;

        return $locale;
    }



    /**
     * Remove first and last name from the formatted address if the customer is a company.
     *
     * @param  array<string,string> $address      Formatted address.
     * @param  int|WC_Order         $order_or_uid Order or user ID.
     * @param  string               $type         Address type.
     * @return array<string,string>
     */
    #[Filter( 'woocommerce_my_account_my_address_formatted_address', 99, args:3 )]
    #[Filter( 'woocommerce_order_formatted_billing_address', 99, args:2 )]
    public function modify_formatted_address( array $address, int|WC_Order $order_or_uid, string $type = 'billing' ): array {
        if ( 'billing' !== $type ) {
            return $address;
        }

        $tgt  = \is_int( $order_or_uid ) ? new WC_Customer( $order_or_uid ) : $order_or_uid;
        $data = \wcsrb_get_company_data( $tgt );

        if ( 'company' === $data['type'] ) {
            $address['first_name'] = "\n";
            $address['last_name']  = "\n";
        }

        return $address;
    }
}
