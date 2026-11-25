<?php
/**
 * PaymentSlipHelpersTest class file.
 *
 * @package Serbian Addons for WooCommerce
 * @subpackage Tests
 */

namespace Oblak\WCSRB\Tests\Integration;

use Oblak\WCSRB\Gateway\Gateway_Payment_Slip;
use Oblak\WCSRB\Tests\Support\Fixtures;
use Oblak\WCSRB\Tests\Support\TestCase;

/**
 * Functions in lib/Functions/wcsrb-payment-slip.php.
 */
class PaymentSlipHelpersTest extends TestCase {
    /**
     * Currency strings.
     *
     * @return array<string,array{string,bool}>
     */
    public function provide_currencies(): array {
        return array(
            'ISO code'          => array( 'RSD', true ),
            'lowercase'         => array( 'rsd', true ),
            'padded with dot'   => array( ' din. ', true ),
            'dinar'             => array( 'Dinar', true ),
            'cyrillic'          => array( 'РСД', true ),
            'cyrillic din'      => array( 'дин', true ),
            'cyrillic dinar'    => array( 'Динар', true ),
            'other currency'    => array( 'EUR', false ),
            'contains rsd only' => array( 'RSDX', false ),
        );
    }

    /**
     * @dataProvider provide_currencies
     *
     * @param string $currency Currency.
     * @param bool   $expected Expected result.
     */
    public function test_is_rsd( string $currency, bool $expected ): void {
        $this->assertSame( $expected, \wcsrb_is_rsd( $currency ) );
    }

    public function test_slip_gateway_accessor(): void {
        $this->assertInstanceOf( Gateway_Payment_Slip::class, \wcsrb_slip_gw() );
        $this->assertSame( \WC()->payment_gateways()->payment_gateways()['wcsrb_payment_slip'], \wcsrb_slip_gw() );
    }

    public function test_bank_accounts_are_read_from_the_store_settings(): void {
        $this->assertSame( array( Fixtures::BANK_ACCOUNT_SHORT ), \wcsrb_get_bank_accounts() );

        // The company settings repeater field stores the list under "acct".
        \update_option( 'woocommerce_store_bank_accounts', array( 'acct' => array( '160-123456-54', '205-1001-62' ) ) );
        $this->assertSame( array( '160-123456-54', '205-1001-62' ), \wcsrb_get_bank_accounts() );

        \delete_option( 'woocommerce_store_bank_accounts' );
        $this->assertSame( array(), \wcsrb_get_bank_accounts() );
    }

    public function test_order_has_slip(): void {
        $order = $this->create_order();

        $this->assertTrue( \wcsrb_order_has_slip( $order ) );
        $this->assertTrue( \wcsrb_order_has_slip( $order->get_id() ), 'Accepts order IDs' );
        $this->assertFalse( \wcsrb_order_has_slip( null ), 'No global order' );
        $this->assertFalse( \wcsrb_order_has_slip( $this->create_order( array( 'payment_method' => 'bacs' ) ) ) );
    }

    public function test_order_has_slip_unpaid_only(): void {
        $paid = $this->create_order( array( 'status' => 'processing' ) );

        $this->assertTrue( \wcsrb_order_has_slip( $paid ) );
        $this->assertFalse( \wcsrb_order_has_slip( $paid, true ) );
        $this->assertTrue( \wcsrb_order_has_slip( $this->create_order( array( 'status' => 'on-hold' ) ), true ) );
    }

    public function test_display_locations_follow_gateway_settings(): void {
        $order = $this->create_order();

        $this->assertTrue( \wcsrb_can_display_slip( $order, 'order' ) );
        $this->assertTrue( \wcsrb_can_display_qr( $order, 'email' ) );

        $this->update_gateway_settings(
            array(
                'display'      => array( 'email' ),
                'qrcode_shown' => array(),
            ),
        );

        $this->assertFalse( \wcsrb_can_display_slip( $order, 'order' ) );
        $this->assertTrue( \wcsrb_can_display_slip( $order, 'email' ) );
        $this->assertFalse( \wcsrb_can_display_qr( $order, 'email' ) );
        $this->assertFalse( \wcsrb_can_display_qr( $order, 'order' ) );
    }

    public function test_paid_orders_do_not_display_slip_or_qr(): void {
        $order = $this->create_order( array( 'status' => 'completed' ) );

        $this->assertFalse( \wcsrb_can_display_slip( $order, 'order' ) );
        $this->assertFalse( \wcsrb_can_display_qr( $order, 'order' ) );
        $this->assertTrue( \wcsrb_can_display_slip( $order, 'order', false ) );
    }

    public function test_payment_models(): void {
        $this->assertSame( array( 'auto' => '', 'mod97' => '97' ), \wcsrb_get_payment_models() ); // phpcs:ignore

        \add_filter( 'woocommerce_serbian_get_payment_models', static fn( $m ) => $m + array( 'mod11' => '11' ) );

        $this->assertSame( '11', \wcsrb_get_payment_models()['mod11'] );
    }

    public function test_payment_model_select(): void {
        $this->assertSame( array( 'auto', 'mod97' ), \array_keys( \wcsrb_format_payment_model_select() ) );

        \add_filter( 'woocommerce_serbian_payment_model_select', static fn( $m ) => \array_slice( $m, 0, 1 ) );

        $this->assertSame( array( 'auto' ), \array_keys( \wcsrb_format_payment_model_select() ) );
    }

    public function test_payment_reference_description_lists_every_tag(): void {
        $html = \wcsrb_format_payment_reference_description();

        $this->assertStringStartsWith( 'Available tags:<br>', $html );

        foreach ( array( '%customer_id%', '%day%', '%month%', '%order_date%', '%order_id%', '%order_number%', '%year%' ) as $tag ) {
            $this->assertStringContainsString( 'data-code="' . $tag . '"', $html );
        }
    }

    public function test_payment_reference_description_escapes_filtered_tags(): void {
        \add_filter(
            'woocommerce_serbian_payment_reference_replacement_pairs_select',
            static fn() => array( '%x"><b>%' => 'Tip "quoted" <i>' ),
        );

        $html = \wcsrb_format_payment_reference_description();

        $this->assertStringNotContainsString( '<b>', $html );
        $this->assertStringNotContainsString( '<i>', $html );
        $this->assertStringContainsString( 'data-tip="Tip &quot;quoted&quot; &lt;i&gt;"', $html );
    }

    public function test_replacement_pairs_default_to_order_number_and_year(): void {
        $order = $this->create_order();
        $pairs = \wcsrb_get_payment_reference_replacement_pairs( $order );
        $date  = $order->get_date_created();

        $this->assertSame( $order->get_id(), $pairs['%order_id%'] );
        $this->assertSame( $order->get_order_number(), $pairs['%order_number%'] );
        $this->assertSame( $order->get_customer_id(), $pairs['%customer_id%'] );
        $this->assertSame( $date->date( 'd' ), $pairs['%day%'] );
        $this->assertSame( $date->date( 'm' ), $pairs['%month%'] );
        $this->assertSame( $date->date( 'Y' ), $pairs['%year%'] );
        $this->assertSame( $date->date( 'd-m-Y' ), $pairs['%order_date%'] );
        $this->assertSame( \wcsrb_calculate_mod97( $order->get_order_number() . $date->date( 'Y' ) ), $pairs['%mod97%'] );
    }

    public function test_string_to_reference(): void {
        $this->assertSame( 10, \wcsrb_string_to_reference( 'a' ) );
        $this->assertSame( 35, \wcsrb_string_to_reference( 'Z' ) );
    }

    public function test_deprecated_number_for_reference(): void {
        $this->setExpectedDeprecated( 'wcsrb_calculate_number_for_reference' );

        $this->assertSame( 32121200, \wcsrb_calculate_number_for_reference( 'WC12' ) );
    }

    public function test_ips_basedir_matches_the_configured_directory(): void {
        $this->assertSame( \WCRS_IPS_DIR, \wcsrb_get_ips_basedir() );
    }
}
