<?php
/**
 * StoreSettingsTest class file.
 *
 * @package Serbian Addons for WooCommerce
 * @subpackage Tests
 */

namespace Oblak\WCSRB\Tests\Integration;

use Oblak\WCSRB\Tests\Support\TestCase;

/**
 * Store wide behaviour that follows the Serbian Addons > General settings.
 */
class StoreSettingsTest extends TestCase {
    /**
     * Customer type limits and the payment codes the slip settings offer.
     *
     * @return array<string,array{string,array<string,array<int,int>>}>
     */
    public function provide_customer_types(): array {
        return array(
            'persons only'   => array( 'person', array( 'Person' => array( 289, 290 ) ) ),
            'companies only' => array( 'company', array( 'Company' => array( 220, 221 ) ) ),
            'both'           => array( 'both', array() ),
        );
    }

    /**
     * @dataProvider provide_customer_types
     *
     * @param string                          $types    Enabled customer types.
     * @param array<string,array<int,int>>    $expected Payment code groups besides "Automatic".
     */
    public function test_payment_codes_follow_the_enabled_customer_types( string $types, array $expected ): void {
        $this->set_core_setting( 'enabled_customer_types', $types );

        $options = \wcsrb_format_payment_code_select();

        $this->assertSame( 'Automatic', $options['auto'] );
        unset( $options['auto'] );
        $this->assertSame( $expected, \array_map( 'array_keys', $options ) );
    }

    public function test_dinar_symbol_is_transliterated_unless_disabled(): void {
        $this->assertSame( 'RSD', \get_woocommerce_currency_symbol( 'RSD' ) );
        $this->assertSame( \get_woocommerce_currency_symbols()['EUR'], \get_woocommerce_currency_symbol( 'EUR' ), 'Other currencies keep their symbol' );

        $this->set_core_setting( 'fix_currency_symbol', false );

        $this->assertSame( \get_woocommerce_currency_symbols()['RSD'], \get_woocommerce_currency_symbol( 'RSD' ) );
    }

    public function test_checkout_script_is_loaded_only_where_the_address_form_is(): void {
        $this->go_to( \home_url( '/' ) );

        $this->assertFalse( \apply_filters( 'wcrs_can_register_script', true, 'main' ), 'Not on the home page' );
        $this->assertTrue( \apply_filters( 'wcrs_can_register_script', true, 'admin' ), 'Other scripts are not changed' );
        $this->assertFalse( \apply_filters( 'wcrs_can_register_script', false, 'admin' ) );
    }
}
