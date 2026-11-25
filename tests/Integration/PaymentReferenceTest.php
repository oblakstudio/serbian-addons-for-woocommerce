<?php
/**
 * PaymentReferenceTest class file.
 *
 * @package Serbian Addons for WooCommerce
 * @subpackage Tests
 */

namespace Oblak\WCSRB\Tests\Integration;

use Oblak\WCSRB\Services\Payments;
use Oblak\WCSRB\Tests\Support\TestCase;

/**
 * Model 97 (ISO 7064 MOD 97-10) payment reference control numbers.
 *
 * @see https://ips.nbs.rs/PDF/Smernice_Generator_Validator_latinica.pdf (RO:97163220000111111111000)
 */
class PaymentReferenceTest extends TestCase {
    /**
     * Independent MOD 97-10 implementation (IBAN style chunking) used as the test oracle.
     *
     * @param  string $base Reference without the control number.
     * @return string
     */
    private static function oracle( string $base ): string {
        $digits = '';

        foreach ( \str_split( \strtoupper( \preg_replace( '/[^a-z0-9]/i', '', $base ) ) ) as $char ) {
            $digits .= \ctype_alpha( $char ) ? (string) ( \ord( $char ) - 55 ) : $char;
        }

        $remainder = 0;

        foreach ( \str_split( $digits . '00', 7 ) as $chunk ) {
            $remainder = (int) ( $remainder . $chunk ) % 97;
        }

        return \str_pad( (string) ( 98 - $remainder ), 2, '0', \STR_PAD_LEFT );
    }

    /**
     * Reference bases with their known control numbers.
     *
     * @return array<string,array{string,string}>
     */
    public function provide_references(): array {
        return array(
            'NBS example'           => array( '3220000111111111000', '16' ),
            'order id + year'       => array( '1232024', '17' ),
            'dashes are ignored'    => array( 'WC-1234-2024', '36' ),
            'slashes are ignored'   => array( '2024/000123/2024', '85' ),
            'letters are converted' => array( 'ABCDEF1234562024', '79' ),
            'number only'           => array( '1500', '60' ),
            'empty'                 => array( '', '98' ),
            'only separators'       => array( '--/--', '98' ),
            'longer than int'       => array( '123456789012345678901234567890', self::oracle( '123456789012345678901234567890' ) ),
        );
    }

    /**
     * @dataProvider provide_references
     *
     * @param string $base     Reference base.
     * @param string $expected Expected control number.
     */
    public function test_mod97_control_number( string $base, string $expected ): void {
        $this->assertSame( $expected, \wcsrb_calculate_mod97( $base ) );
        $this->assertSame( self::oracle( $base ), $expected );
    }

    public function test_legacy_check_digit_helper_no_longer_overflows(): void {
        $this->assertSame( '79', \wcsrb_calculate_check_digit( 'ABCDEF123456', '2024' ) );
        $this->assertSame( '36', \wcsrb_calculate_check_digit( 'WC-1234', '2024' ) );
    }

    public function test_control_number_matches_sequential_order_number_template(): void {
        \add_filter( 'woocommerce_order_number', static fn() => 'WC-1500' );

        $this->update_gateway_settings(
            array(
                'payment_model'     => 'mod97',
                'payment_reference' => '%mod97%-%order_number%',
            ),
        );

        $order = $this->create_order();
        $data  = $this->payments()->get_data( $order );

        $this->assertSame( '97', $data['model'] );
        $this->assertSame( self::oracle( 'WC1500' ) . '-WC-1500', $data['reference'] );
        $this->assertSame( '97' . self::oracle( 'WC1500' ) . 'WC1500', $this->payments()->get_data( $order, 'ips' )['RO'] );
    }

    public function test_control_number_uses_order_id_when_template_uses_order_id(): void {
        \add_filter( 'woocommerce_order_number', static fn() => 'SOMETHING-ELSE' );

        $this->update_gateway_settings(
            array(
                'payment_model'     => 'mod97',
                'payment_reference' => '%mod97%-%order_id%-%year%',
            ),
        );

        $order = $this->create_order();
        $year  = $order->get_date_created()->date( 'Y' );
        $base  = $order->get_id() . '-' . $year;

        $this->assertSame( self::oracle( $base ) . '-' . $base, $this->payments()->get_data( $order )['reference'] );
    }

    public function test_control_number_follows_filtered_replacement_pairs(): void {
        \add_filter(
            'woocommerce_serbian_payment_reference_replacement_pairs',
            static fn( array $pairs ) => \array_merge( $pairs, array( '%order_number%' => '777' ) ),
        );

        $this->update_gateway_settings(
            array(
                'payment_model'     => 'mod97',
                'payment_reference' => '%mod97%-%order_number%',
            ),
        );

        $order = $this->create_order();

        $this->assertSame( self::oracle( '777' ) . '-777', $this->payments()->get_data( $order )['reference'] );
    }

    public function test_filter_can_still_override_the_control_number(): void {
        \add_filter(
            'woocommerce_serbian_payment_reference_replacement_pairs',
            static fn( array $pairs ) => \array_merge( $pairs, array( '%mod97%' => '00' ) ),
        );

        $this->update_gateway_settings(
            array(
                'payment_model'     => 'mod97',
                'payment_reference' => '%mod97%-%order_id%',
            ),
        );

        $order = $this->create_order();

        $this->assertSame( '00-' . $order->get_id(), $this->payments()->get_data( $order )['reference'] );
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
