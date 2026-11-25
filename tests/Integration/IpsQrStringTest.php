<?php
/**
 * IpsQrStringTest class file.
 *
 * @package Serbian Addons for WooCommerce
 * @subpackage Tests
 */

namespace Oblak\WCSRB\Tests\Integration;

use Oblak\WCSRB\Services\Payments;
use Oblak\WCSRB\Tests\Support\TestCase;
use WC_Order;

/**
 * NBS IPS QR string format.
 *
 * @see https://ips.nbs.rs/PDF/pdfPreporukeValidacijaLat.pdf
 */
class IpsQrStringTest extends TestCase {
    public function test_qr_string_has_nbs_tags_in_order(): void {
        $order = $this->create_order();
        $tags  = $this->tags( $order );

        $this->assertSame( array( 'K', 'V', 'C', 'R', 'N', 'I', 'P', 'SF', 'S', 'RO' ), \array_keys( $tags ) );
        $this->assertSame( 'PR', $tags['K'] );
        $this->assertSame( '01', $tags['V'] );
        $this->assertSame( '1', $tags['C'] );
        $this->assertSame( '160000000012345654', $tags['R'] );
        $this->assertSame( 'RSD1234,50', $tags['I'] );
        $this->assertSame( '289', $tags['SF'] );
        $this->assertSame( 'Order payment', $tags['S'] );
        $this->assertSame( '00' . $order->get_id() . $order->get_date_created()->date( 'Y' ), $tags['RO'] );
    }

    public function test_purpose_is_limited_to_35_characters(): void {
        $purpose = 'Plaćanje porudžbine – šifra ćevapčići đuveč žđčćš 1234567890';

        $this->update_gateway_settings( array( 'payment_purpose' => $purpose ) );

        $order = $this->create_order();
        $tags  = $this->tags( $order );

        $this->assertSame( \mb_substr( $purpose, 0, 35 ), $tags['S'] );
        $this->assertTrue( \mb_check_encoding( $tags['S'], 'UTF-8' ) );
        $this->assertSame( $purpose, $this->payments()->get_data( $order )['purpose'], 'Display context keeps the full purpose' );
    }

    public function test_long_multibyte_payer_is_truncated_on_character_boundary(): void {
        $order = $this->create_order(
            array(
                'props' => array(
                    'billing_first_name' => 'Александар',
                    'billing_last_name'  => \str_repeat( 'Ђорђевић-', 8 ),
                ),
            ),
        );

        $payer = $this->tags( $order )['P'];

        $this->assertTrue( \mb_check_encoding( $payer, 'UTF-8' ), 'Payer must stay valid UTF-8' );
        $this->assertSame( 70, \mb_strlen( $payer ) );
        $this->assertStringStartsWith( 'Александар Ђорђевић', $payer );
    }

    public function test_multibyte_address_is_not_over_truncated(): void {
        $order = $this->create_order(
            array(
                'props' => array(
                    'billing_address_1'  => 'Булевар ослобођења 10',
                    'billing_city'       => 'Нови Сад',
                    'billing_first_name' => 'Ђорђе',
                    'billing_last_name'  => 'Ђурђевић',
                ),
            ),
        );

        $payer = $this->tags( $order )['P'];

        // 44 characters, but 80+ bytes: every line fits the 70 character limit.
        $this->assertStringContainsString( 'Булевар ослобођења 10', $payer );
        $this->assertStringContainsString( 'Нови Сад', $payer );
    }

    public function test_empty_optional_tags_are_omitted(): void {
        $this->update_gateway_settings(
            array(
                'payment_purpose'   => '',
                'payment_reference' => '',
            ),
        );

        $tags = $this->tags( $this->create_order() );

        $this->assertArrayNotHasKey( 'S', $tags );
        $this->assertArrayNotHasKey( 'RO', $tags );
        $this->assertStringNotContainsString( '||', $this->payments()->get_qr_string( $this->create_order() ) );
    }

    /**
     * Parse the QR string into tag => value pairs.
     *
     * @param  WC_Order $order Order object.
     * @return array<string,string>
     */
    private function tags( WC_Order $order ): array {
        $tags = array();

        foreach ( \explode( '|', $this->payments()->get_qr_string( $order ) ) as $part ) {
            [ $tag, $value ] = \explode( ':', $part, 2 );

            $tags[ $tag ] = $value;
        }

        return $tags;
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
