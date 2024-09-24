<?php
/**
 * Field_Customizer class file.
 *
 * @package Serbian Addons for WooCommerce
 * @subpackage WooCommerce\Checkout
 */

namespace Oblak\WooCommerce\Serbian_Addons\Checkout;

use Oblak\WP\Abstracts\Hook_Caller;
use Oblak\WP\Decorators\Filter;
use Oblak\WP\Decorators\Hookable;

/**
 * Changes the fields on the checkout page
 */
#[Hookable( 'woocommerce_init', 99 )]
class Field_Customizer extends Hook_Caller {
    /**
     * Modifies the default customer type to person in the edit address form.
     *
     * @param  mixed  $value Field value.
     * @param  string $key   Field key.
     * @return mixed
     */
    #[Filter( tag: 'woocommerce_my_account_edit_address_field_value', priority: 100 )]
    public function modify_default_customer_type( mixed $value, string $key ): mixed {
        if ( 'billing_type' === $key && ! $value ) {
            $value = 'person';
        }

        return $value;
    }

    /**
     * Modifies the billing fields to add the customer type and additional company fields
     *
     * @param  array $fields Billing fields.
     * @return array         Modified billing fields
     */
    #[Filter( tag: 'woocommerce_billing_fields', priority: 'woocommerce_serbian_checkout_fields_priority' )]
    public function modify_billing_fields( $fields ) {
        $enabled_type = \WCSRB()->get_settings( 'core', 'enabled_customer_types' );

        $fields = $this->maybe_remove_fields( $fields );

        $fields['billing_type'] = $this->add_billing_type_field( $enabled_type );

        $fields = \array_merge(
            $fields,
            $this->maybe_add_company_fields( $enabled_type ),
        );

        // If the billing type is not both or company, remove the company field.
        if ( ! \in_array( $enabled_type, array( 'both', 'company' ), true ) ) {
            unset( $fields['billing_company'] );
        } else { // Else, addin some extra data.
            $fields['billing_company']['class'][]  = 'hide-if-person';
            $fields['billing_company']['required'] = true;
        }

        return $fields;
    }

    /**
     * Modifies shipping fields to remove the unneded fields.
     *
     * @param  array $fields Shipping fields.
     * @return array         Modified shipping fields
     */
    #[Filter( tag: 'woocommerce_shipping_fields', priority: 'woocommerce_serbian_checkout_fields_priority' )]
    public function modify_shipping_fields( $fields ) {
        $fields = $this->maybe_remove_fields( $fields, 'shipping' );

        return $fields;
    }

    /**
     * Removes unnecessary fields from the checkout ajax request
     *
     * @param  array $fields Fields to modify.
     * @return array         Modified fields
     */
    #[Filter( tag: 'woocommerce_shipping_fields', priority: 'woocommerce_serbian_checkout_fields_priority' )]
    public function modify_ajax_checkout_fields( $fields ) {
        if ( ! \wp_doing_ajax() ) {
            return $fields;
        }

        $checkout_customer_type = \xwp_fetch_post_var( 'billing_type', 'person' );

        if ( 'person' === $checkout_customer_type ) {
            unset( $fields['billing']['billing_company'] );
            unset( $fields['billing']['billing_mb'] );
            unset( $fields['billing']['billing_pib'] );
        }

        return $fields;
    }

    /**
     * Removes the fields that are not needed, and changes fields priority.
     *
     * @param  array  $fields Fields.
     * @param  string $type   Field type - billing or shipping.
     * @return array          Modified fields
     */
    private function maybe_remove_fields( $fields, $type = 'billing' ) {
        $fields[ "{$type}_postcode" ]['priority'] = 81;
        $fields[ "{$type}_city" ]['priority']     = 91;
        $fields[ "{$type}_country" ]['priority']  = 91;

        $to_remove = \WCSRB()->get_settings(
            'general',
            'remove_unneeded_fields',
        ) ? array( 'address_2', 'state' ) : array();

        /**
         * Filters the fields that should be removed from the checkout page
         *
         * @param  array $to_remove Fields to remove
         * @return array
         * @since 1.3.0
         */
        $to_remove = \apply_filters( 'woocommerce_serbian_checkout_fields_to_remove', $to_remove );

        foreach ( $to_remove as $field_name ) {
            unset( $fields[ "{$type}_{$field_name}" ] );
        }

        return $fields;
    }

    /**
     * Adds the billing type field to the checkout page.
     *
     * Depending on the plugin settings, field can be a radio button or a hidden input
     *
     * @param  string $enabled_type Enabled customer type.
     * @return array                Billing type field data.
     *
     * @since 1.3.0
     */
    private function add_billing_type_field( $enabled_type ) {
        $billing_type = array(
            'class'    => array( 'form-row-wide', 'entity-type-control', 'update_totals_on_change' ),
            'default'  => 'person',
            'label'    => \__( 'Customer type', 'serbian-addons-for-woocommerce' ),
            'options'  => \wcsrb_get_entity_types(),
            'priority' => 21,
            'required' => true,
            'type'     => 'radio',
        );

        if ( 'both' !== $enabled_type ) {

            $billing_type['type']        = 'hidden';
            $billing_type['default']     = $enabled_type;
            $billing_type['description'] = \wcsrb_get_entity_types()[ $enabled_type ];

            unset( $billing_type['options'] );

        }

        return $billing_type;
    }

    /**
     * Add needed company fields if the customer can checkout as a company
     *
     * @param  string $enabled_type Enabled customer type.
     * @return array                Company fields data.
     */
    private function maybe_add_company_fields( $enabled_type ) {
        if ( ! \in_array( $enabled_type, array( 'both', 'company' ), true ) ) {
            return array();
        }

        $extra_fields = array(
            'billing_mb'  => array(
                'class'       => array( 'form-row-first', 'hide-if-person' ),
                'label'       => \__( 'Company Number', 'serbian-addons-for-woocommerce' ),
                'placeholder' => \__( 'Enter MB', 'serbian-addons-for-woocommerce' ),
                'priority'    => 31,
                'required'    => true,
                'type'        => 'text',
                'validate'    => array( 'mb' ),
            ),
            'billing_pib' => array(
                'class'       => array( 'form-row-last', 'hide-if-person' ),
                'label'       => \__( 'Tax Number', 'serbian-addons-for-woocommerce' ),
                'placeholder' => \__( 'Enter PIB', 'serbian-addons-for-woocommerce' ),
                'priority'    => 32,
                'required'    => true,
                'type'        => 'text',
                'validate'    => array( 'pib' ),
            ),
        );

        if ( 'company' !== $enabled_type ) {
            $extra_fields['billing_pib']['custom_attributes']['disabled'] = 'disabled';
            $extra_fields['billing_mb']['custom_attributes']['disabled']  = 'disabled';
        }

        return $extra_fields;
    }
}
