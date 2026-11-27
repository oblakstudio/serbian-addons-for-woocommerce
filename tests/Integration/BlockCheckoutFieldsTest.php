<?php
/**
 * BlockCheckoutFieldsTest class file.
 *
 * @package Serbian Addons for WooCommerce
 * @subpackage Tests
 */

namespace Oblak\WCSRB\Tests\Integration;

use Automattic\WooCommerce\Blocks\Utils\CartCheckoutUtils;
use Oblak\WCSRB\Core\Block_Checkout_Field_Controller;
use Oblak\WCSRB\Tests\Support\TestCase;

/**
 * Core address fields in the block checkout: Company (and, on request, address 2 and district) are not asked there.
 */
class BlockCheckoutFieldsTest extends TestCase {
    /**
     * Request URI before the test.
     *
     * @var string|null
     */
    private ?string $request_uri = null;

    /**
     * Remember the request and start with a fresh locale.
     */
    public function set_up(): void {
        parent::set_up();

        $this->request_uri                = $_SERVER['REQUEST_URI'] ?? null;
        \WC()->countries->locale          = array();
        \WC()->countries->address_formats = array();
    }

    /**
     * Leave the block checkout and rebuild the locale for the next test.
     */
    public function tear_down(): void {
        $prop = new \ReflectionProperty( Block_Checkout_Field_Controller::class, 'block_checkout' );
        $prop->setAccessible( true );
        $prop->setValue( \xwp_app( 'wcsrb' )->get( Block_Checkout_Field_Controller::class ), false );

        $_SERVER['REQUEST_URI']           = $this->request_uri;
        \WC()->countries->locale          = array();
        \WC()->countries->address_formats = array();

        parent::tear_down();
    }

    public function test_the_block_gets_an_address_format_without_company_number_placeholders(): void {
        // The block formats its address cards in JavaScript, which only knows WooCommerce's placeholders.
        $this->assertStringContainsString( '{mb}', \WC()->countries->get_address_formats()['RS'], 'Classic format, cached' );

        $this->render_block_checkout();

        $format = \WC()->countries->get_address_formats()['RS'];

        $this->assertStringNotContainsString( '{mb}', $format );
        $this->assertStringNotContainsString( '{pib}', $format );
        $this->assertStringContainsString( '{address_1}', $format );
    }

    public function test_store_api_requests_keep_the_company_numbers_in_the_address_format(): void {
        // Order emails are sent during the Store API checkout request - their address shows MB and PIB.
        $_SERVER['REQUEST_URI'] = '/wp-json/wc/store/v1/checkout';

        $format = \WC()->countries->get_address_formats()['RS'];

        $this->assertStringContainsString( '{mb}', $format );
        $this->assertStringContainsString( '{pib}', $format );
    }

    public function test_after_the_block_render_the_classic_fields_are_back(): void {
        \update_option( 'woocommerce_checkout_company_field', 'optional' );

        $this->render_block_checkout();
        \do_action( 'woocommerce_blocks_enqueue_checkout_block_scripts_after' );

        $this->assertSame( 'optional', CartCheckoutUtils::get_company_field_visibility() );
        $this->assertFalse( \WC()->countries->get_country_locale()['RS']['company']['hidden'] ?? false );
        $this->assertStringContainsString( '{mb}', \WC()->countries->get_address_formats()['RS'] );
    }

    public function test_the_block_checkout_hides_the_company_field(): void {
        \update_option( 'woocommerce_checkout_company_field', 'optional' );
        $this->render_block_checkout();

        $this->assertSame( 'hidden', CartCheckoutUtils::get_company_field_visibility() );

        $locale = \WC()->countries->get_country_locale();

        foreach ( array( 'RS', 'DE', 'default' ) as $country ) {
            $this->assertTrue( $locale[ $country ]['company']['hidden'], $country );
            $this->assertFalse( $locale[ $country ]['company']['required'], $country );
        }
    }

    public function test_the_block_gets_a_locale_without_the_company_field(): void {
        \update_option( 'woocommerce_checkout_company_field', 'optional' );

        // Something on the page read the (classic) locale first - WooCommerce caches it per request.
        $this->assertFalse( \WC()->countries->get_country_locale()['RS']['company']['hidden'] );

        $this->render_block_checkout();

        // What the Checkout block sends to the browser (countryData), read before its enqueue_data action fires.
        $country_data = CartCheckoutUtils::get_country_data();

        $this->assertTrue( $country_data['RS']['locale']['company']['hidden'] );
        $this->assertTrue( $country_data['DE']['locale']['company']['hidden'] );
    }

    public function test_store_api_requests_hide_the_company_field(): void {
        \update_option( 'woocommerce_checkout_company_field', 'required' );
        $_SERVER['REQUEST_URI'] = '/wp-json/wc/store/v1/checkout';

        $this->assertSame( 'hidden', CartCheckoutUtils::get_company_field_visibility() );
        $this->assertTrue( \WC()->countries->get_country_locale()['RS']['company']['hidden'] );
    }

    public function test_the_classic_checkout_keeps_the_company_field(): void {
        \update_option( 'woocommerce_checkout_company_field', 'optional' );

        $this->assertSame( 'optional', CartCheckoutUtils::get_company_field_visibility() );
        $fields = \WC()->countries->get_address_fields( 'RS', 'billing_' );

        $this->assertArrayHasKey( 'billing_company', $fields );
        $this->assertFalse( $fields['billing_company']['hidden'] ?? false, 'WooCommerce < 11 has no hidden key when shown' );
    }

    public function test_field_removal_hides_address_2_and_district_for_serbia(): void {
        $this->set_core_setting( 'remove_unneeded_fields', true );
        $this->render_block_checkout();

        $locale = \WC()->countries->get_country_locale();

        foreach ( array( 'address_2', 'state' ) as $field ) {
            $this->assertTrue( $locale['RS'][ $field ]['hidden'], $field );
            $this->assertFalse( $locale['RS'][ $field ]['required'], $field );
        }
        $this->assertFalse( $locale['DE']['address_2']['hidden'] ?? false, 'Only Serbian addresses' );
    }

    public function test_without_field_removal_serbia_keeps_address_2_and_district(): void {
        $this->set_core_setting( 'remove_unneeded_fields', false );
        $this->render_block_checkout();

        $serbia = \WC()->countries->get_country_locale()['RS'];

        $this->assertFalse( $serbia['address_2']['hidden'] ?? false );
        $this->assertFalse( $serbia['state']['hidden'] ?? false );
    }

    public function test_the_block_checkout_keeps_the_serbian_field_order(): void {
        $this->set_core_setting( 'field_ordering', true );
        $this->render_block_checkout();

        $serbia = \WC()->countries->get_country_locale()['RS'];

        $this->assertSame( array( 81, 82 ), array( $serbia['postcode']['priority'], $serbia['city']['priority'] ) );
    }

    /**
     * Start rendering the Checkout block: the action Checkout::enqueue_assets() fires before it reads any data.
     */
    private function render_block_checkout(): void {
        \do_action( 'woocommerce_blocks_enqueue_checkout_block_scripts_before' );
    }
}
