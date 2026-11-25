<?php
/**
 * PaymentsTest class file.
 *
 * @package Serbian Addons for WooCommerce
 * @subpackage Tests
 */

namespace Oblak\WCSRB\Tests\Integration;

use Oblak\WCSRB\Services\Payments;
use Oblak\WCSRB\Tests\Support\Fixtures;
use Oblak\WCSRB\Tests\Support\TestCase;
use WC_Customer;

/**
 * Payment slip / IPS data formatting.
 */
class PaymentsTest extends TestCase {
    /**
     * Payment code inputs.
     *
     * @return array<string,array{string,string,string}>
     */
    public function provide_payment_codes(): array {
        return array(
            'auto person'      => array( 'auto', 'person', '289' ),
            'auto company'     => array( 'auto', 'company', '221' ),
            'explicit code'    => array( '220', 'company', '220' ),
            'explicit person'  => array( '290', 'person', '290' ),
        );
    }

    /**
     * @dataProvider provide_payment_codes
     *
     * @param string $code     Configured code.
     * @param string $type     Customer type.
     * @param string $expected Expected code.
     */
    public function test_payment_code( string $code, string $type, string $expected ): void {
        $this->update_gateway_settings( array( 'payment_code' => $code ) );

        $order = $this->create_order( array( 'type' => $type ) );

        $this->assertSame( $expected, $this->payments()->get_data( $order )['code'] );
        $this->assertSame( $expected, $this->payments()->get_data( $order, 'ips' )['SF'] );
    }

    public function test_auto_code_defaults_to_person_without_customer_type(): void {
        $order = $this->create_order();
        $order->delete_meta_data( '_billing_type' );
        $order->save();

        $this->assertSame( '289', $this->payments()->get_data( $order )['code'] );
    }

    public function test_model(): void {
        $order = $this->create_order();

        $this->assertSame( '', $this->payments()->get_data( $order )['model'], 'No model on the slip' );
        $this->assertStringStartsWith( '00', $this->payments()->get_data( $order, 'ips' )['RO'], 'IPS needs model 00' );

        $this->update_gateway_settings( array( 'payment_model' => 'mod97' ) );
        $order = $this->create_order();

        $this->assertSame( '97', $this->payments()->get_data( $order )['model'] );
        $this->assertStringStartsWith( '97', $this->payments()->get_data( $order, 'ips' )['RO'] );
    }

    public function test_reference_keeps_dashes_on_the_slip_only(): void {
        $order = $this->create_order();
        $year  = $order->get_date_created()->date( 'Y' );

        $this->assertSame( $order->get_id() . '-' . $year, $this->payments()->get_data( $order )['reference'] );
        $this->assertSame( '00' . $order->get_id() . $year, $this->payments()->get_data( $order, 'ips' )['RO'] );
    }

    public function test_total_and_currency(): void {
        $order = $this->create_order( array( 'total' => 98765.4 ) );
        $slip  = $this->payments()->get_data( $order );

        $this->assertSame( 98765.4, $slip['total'] );
        $this->assertSame( 'RSD', $slip['currency'] );
        $this->assertSame( 'RSD98765,40', $this->payments()->get_data( $order, 'ips' )['I'] );
    }

    public function test_non_rsd_currency_is_kept(): void {
        $order = $this->create_order( array( 'currency' => 'EUR' ) );

        $this->assertSame( 'EUR', $this->payments()->get_data( $order )['currency'] );
    }

    public function test_account_formats(): void {
        $order = $this->create_order();

        $this->assertSame( Fixtures::BANK_ACCOUNT_SHORT, $this->payments()->get_data( $order )['account'] );
        $this->assertSame( '160000000012345654', $this->payments()->get_data( $order, 'ips' )['R'] );
    }

    public function test_company_is_the_payee(): void {
        $order = $this->create_order();
        $payee = $this->payments()->get_data( $order, 'ips' )['N'];

        $this->assertStringStartsWith( "Test Prodavnica d.o.o.\nKnez Mihailova 1", $payee );
        $this->assertStringContainsString( 'Beograd', $payee );
    }

    public function test_company_customer_is_shown_without_registration_numbers(): void {
        $payer = $this->payments()->get_data( $this->create_order( array( 'type' => 'company' ) ), 'ips' )['P'];

        $this->assertStringStartsWith( 'Firma d.o.o.', $payer );
        $this->assertStringNotContainsString( Fixtures::MB, $payer );
        $this->assertStringNotContainsString( 'Petar', $payer );
    }

    public function test_format_address_for_customer_and_raw_address(): void {
        $user     = $this->factory()->user->create();
        $customer = new WC_Customer( $user );
        $customer->set_billing_first_name( 'Jovana' );
        $customer->set_billing_last_name( 'Jovanović' );
        $customer->set_billing_address_1( 'Zmaj Jovina 5' );
        $customer->set_billing_city( 'Novi Sad' );
        $customer->set_billing_postcode( '21000' );
        $customer->set_billing_country( 'RS' );
        $customer->save();

        $this->assertStringContainsString( 'Zmaj Jovina 5', $this->payments()->format_address( $customer ) );
        $this->assertFalse( $this->payments()->is_formatting(), 'Formatting flag is reset' );

        $raw = $this->payments()->format_address(
            array(
                'address_1' => 'Nemanjina 4',
                'city'      => 'Beograd',
                'company'   => 'Ministarstvo',
                'country'   => 'RS',
            ),
            'ips',
        );

        $this->assertStringStartsWith( "Ministarstvo\nNemanjina 4", $raw );
    }

    public function test_formatting_flag_is_set_while_formatting(): void {
        $seen = null;

        \add_filter(
            'woocommerce_formatted_address_replacements',
            function ( $replacements ) use ( &$seen ) {
                $seen = $this->payments()->is_formatting();
                return $replacements;
            },
        );

        $this->payments()->format_address( array( 'country' => 'RS' ) );

        $this->assertTrue( $seen );
    }

    public function test_new_instance_formats_with_the_given_config(): void {
        $payments = new Payments( new \Oblak\WCSRB\Services\Config() );

        $this->assertFalse( $payments->is_formatting() );
        $this->assertSame( $this->payments()->get_data( $this->create_order() )['company'], $payments->get_data( $this->create_order() )['company'] );
    }

    /**
     * Get the payments service.
     *
     * @return Payments
     */
    private function payments(): Payments {
        return \xwp_app( 'wcsrb' )->get( Payments::class );
    }
}
