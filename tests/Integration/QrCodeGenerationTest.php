<?php
/**
 * QrCodeGenerationTest class file.
 *
 * @package Serbian Addons for WooCommerce
 * @subpackage Tests
 */

namespace Oblak\WCSRB\Tests\Integration;

use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Oblak\WCSRB\Services\Payments;
use Oblak\WCSRB\Services\QR_Code_Manager;
use Oblak\WCSRB\Tests\Support\TestCase;
use WC_Order;

/**
 * The generated IPS QR images decode to exactly the order's IPS string.
 */
class QrCodeGenerationTest extends TestCase {
    public function test_qr_code_decodes_to_the_order_ips_string(): void {
        $order = $this->create_order();

        $this->assertSame( $this->payments()->get_qr_string( $order ), $this->decode( $order ) );
    }

    public function test_each_qr_code_contains_only_its_own_order(): void {
        $first  = $this->create_order( array( 'total' => 100 ) );
        $second = $this->create_order( array( 'total' => 200 ) );

        $this->assertSame( $this->payments()->get_qr_string( $first ), $this->decode( $first ) );
        $this->assertSame( $this->payments()->get_qr_string( $second ), $this->decode( $second ) );
    }

    public function test_qr_codes_keep_working_after_many_orders(): void {
        for ( $i = 1; $i <= 20; $i++ ) {
            $order = $this->create_order( array( 'total' => $i * 100 ) );
        }

        $this->assertSame( $this->payments()->get_qr_string( $order ), $this->decode( $order ) );
    }

    /**
     * Decode the order's QR code image.
     *
     * @param  WC_Order $order Order object.
     * @return string
     */
    private function decode( WC_Order $order ): string {
        $file = \xwp_app( 'wcsrb' )->get( QR_Code_Manager::class )->get_filename( $order );

        $this->assertFileExists( $file );

        $reader = new QRCode( new QROptions( array( 'readerUseImagickIfAvailable' => false ) ) );

        return (string) $reader->readFromFile( $file );
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
