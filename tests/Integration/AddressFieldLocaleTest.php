<?php
/**
 * AddressFieldLocaleTest class file.
 *
 * @package Serbian Addons for WooCommerce
 * @subpackage Tests
 */

namespace Oblak\WCSRB\Tests\Integration;

use Oblak\WCSRB\Core\Address_Field_Controller;
use Oblak\WCSRB\Tests\Support\TestCase;

/**
 * Country locale data of the checkout address fields.
 */
class AddressFieldLocaleTest extends TestCase {
    public function test_serbian_locale_data_does_not_depend_on_hook_registration_order(): void {
        $controller = \xwp_app( 'wcsrb' )->get( Address_Field_Controller::class );
        $priorities = array();

        // The defaults hide the customer fields for every country - Serbia must be applied after them.
        foreach ( array( 'add_default_locale_field_data', 'add_custom_locale_field_data' ) as $method ) {
            $priorities[ $method ] = \has_filter( 'woocommerce_get_country_locale', array( $controller, $method ) );

            \remove_filter( 'woocommerce_get_country_locale', array( $controller, $method ), $priorities[ $method ] );
        }

        foreach ( \array_reverse( $priorities, true ) as $method => $priority ) {
            \add_filter( 'woocommerce_get_country_locale', array( $controller, $method ), $priority );
        }

        $serbia = \apply_filters( 'woocommerce_get_country_locale', array( 'RS' => array() ) )['RS'];

        $this->assertFalse( $serbia['type']['hidden'] );
        $this->assertTrue( $serbia['type']['required'] );
    }

    public function test_serbian_checkout_keeps_the_woocommerce_locale(): void {
        $fields = \WC()->countries->get_address_fields( 'RS', 'billing_' );

        $this->assertFalse( $fields['billing_state']['required'], 'Serbian districts are optional' );
        $this->assertSame( 'District', $fields['billing_state']['label'] );
        $this->assertTrue( $fields['billing_postcode']['required'] );
        $this->assertTrue( $fields['billing_city']['required'] );
    }

    public function test_foreign_customers_get_a_regular_company_field(): void {
        $fields = ( new \WC_Countries() )->get_address_fields( 'DE', 'billing_' );

        // The stylesheet hides .entity-type-toggle rows unless the Serbian customer type shows them.
        $this->assertNotContains( 'entity-type-toggle', $fields['billing_company']['class'] );
    }

    public function test_outside_serbia_the_company_field_follows_the_store_setting(): void {
        \update_option( 'woocommerce_checkout_company_field', 'required' );
        $countries = new \WC_Countries();

        $this->assertTrue( $countries->get_address_fields( 'US', 'billing_' )['billing_company']['required'] );
        $this->assertFalse( $countries->get_address_fields( 'RS', 'billing_' )['billing_company']['required'], 'Serbian persons have no company - companies are validated by customer type' );
    }

    public function test_a_hidden_company_field_stays_hidden(): void {
        \update_option( 'woocommerce_checkout_company_field', 'hidden' );

        $this->assertArrayNotHasKey( 'company', ( new \WC_Countries() )->get_default_address_fields() );
    }

    public function test_a_single_customer_type_is_preset_and_not_asked(): void {
        $this->set_core_setting( 'enabled_customer_types', 'company' );

        $type = ( new \WC_Countries() )->get_default_address_fields()['type'];

        $this->assertSame( 'hidden', $type['type'] );
        $this->assertSame( 'company', $type['default'] );
        $this->assertSame( array( 'data-default' => 'company' ), $type['custom_attributes'] );
        $this->assertSame( 'Company', $type['description'] );
        $this->assertArrayNotHasKey( 'options', $type );
    }

    public function test_both_customer_types_can_be_chosen(): void {
        $type = ( new \WC_Countries() )->get_default_address_fields()['type'];

        $this->assertSame( 'radio', $type['type'] );
        $this->assertSame( \wcsrb_get_entity_types(), $type['options'] );
    }

    public function test_unneeded_address_fields_can_be_removed(): void {
        $this->set_core_setting( 'remove_unneeded_fields', true );
        $countries = new \WC_Countries();

        $this->assertArrayNotHasKey( 'address_2', $countries->get_default_address_fields() );
        $this->assertArrayNotHasKey( 'state', $countries->get_default_address_fields() );
        $this->assertSame( "{name}\n{company}\n{mb}\n{pib}\n{address_1}\n{postcode} {city},  {country}", $countries->get_address_formats()['RS'] );
    }

    public function test_serbian_address_format_includes_the_company_numbers(): void {
        $this->assertSame(
            "{name}\n{company}\n{mb}\n{pib}\n{address_1} {address_2}\n{postcode} {city}, {state} {country}",
            ( new \WC_Countries() )->get_address_formats()['RS'],
        );
    }

    public function test_locale_script_can_find_the_customer_fields(): void {
        $selectors = \apply_filters( 'woocommerce_country_locale_field_selectors', array( 'city' => '#billing_city_field' ) );

        $this->assertSame( '#billing_city_field', $selectors['city'] );
        $this->assertSame( '#billing_type_field', $selectors['type'] );
        $this->assertSame( '#billing_company_field', $selectors['company'] );
        $this->assertSame( '#billing_mb_field', $selectors['mb'] );
        $this->assertSame( '#billing_pib_field', $selectors['pib'] );
    }

    public function test_shipping_addresses_have_no_customer_or_company_fields(): void {
        $fields = ( new \WC_Countries() )->get_address_fields( 'RS', 'shipping_' );

        foreach ( array( 'shipping_type', 'shipping_company', 'shipping_mb', 'shipping_pib' ) as $key ) {
            $this->assertArrayNotHasKey( $key, $fields );
        }

        $this->assertArrayHasKey( 'shipping_address_1', $fields );
    }

    public function test_my_account_shipping_address_is_not_changed(): void {
        $address = array( 'first_name' => 'Petar' );

        $this->assertSame( $address, \apply_filters( 'woocommerce_my_account_my_address_formatted_address', $address, 1, 'shipping' ) );
    }

    public function test_serbian_checkout_shows_the_customer_fields(): void {
        $fields = \WC()->countries->get_address_fields( 'RS', 'billing_' );

        $this->assertTrue( $fields['billing_type']['required'] );
        $this->assertFalse( $fields['billing_type']['hidden'] );
        $this->assertFalse( $fields['billing_mb']['hidden'] );
        $this->assertFalse( $fields['billing_pib']['hidden'] );
        $this->assertSame( array( 81, 82 ), array( $fields['billing_postcode']['priority'], $fields['billing_city']['priority'] ), 'Postcode before city' );
        $this->assertSame( 91, $fields['billing_country']['priority'] );
    }
}
